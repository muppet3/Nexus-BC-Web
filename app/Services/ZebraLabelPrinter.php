<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Genera e imprime las etiquetas — usado tanto por la API del celular como por
 * las pantallas web que corren en la misma máquina que las impresoras
 * compartidas. Un solo lugar con las plantillas para que todos los lados
 * impriman siempre exactamente igual.
 *
 * A pesar del nombre de la clase, no es exclusivo de Zebra: cada impresora
 * recibe su lenguaje nativo con el mismo diseño de etiqueta —
 *   - Zebra   -> ZPL
 *   - Ribetec -> TSPL (placa 4BARCODE 4B-2054A). Su emulación de ZPL imprimía
 *                el texto literal de los comandos en vez de interpretarlos, así
 *                que se le habla directo en TSPL.
 */
class ZebraLabelPrinter
{
    // Nombre del recurso compartido de cada impresora en Windows. Si se agrega
    // una impresora nueva, hay que sumar su entrada aquí y en LENGUAJE.
    private const IMPRESORAS = [
        'zebra' => '\\\\127.0.0.1\\ZEBRA',
        'ribetec' => '\\\\127.0.0.1\\RIBETEC2',
    ];

    private const LENGUAJE = [
        'zebra' => 'zpl',
        'ribetec' => 'tspl',
    ];

    // Solo ZPL (Zebra). ^MT le dice a la impresora si debe esperar cinta/ribbon
    // (transferencia térmica) o no (térmica directa). null = no mandar el comando.
    private const MODO_IMPRESION = '^MTT';

    // Solo ZPL (Zebra). ~SD = oscuridad (0-30). null = no mandar el comando.
    private const OSCURIDAD = 20;

    // Solo ZPL (Zebra). ^PR = velocidad (pulgadas/seg). null = no mandar el comando.
    private const VELOCIDAD = null;

    // Fuentes internas de TSPL: nombre => ancho de cada carácter en dots (203 dpi).
    // Se usan para centrar el texto a mano y elegir la más grande que quepa
    // (el orden importa: de la más grande a la más chica).
    private const FUENTES_TSPL = ['3' => 16, '2' => 12, '1' => 8];

    /**
     * @param  string  $tipo  'barras' (descripción + código, 2 etiquetas), 'texto'
     *                        (solo descripción) o 'solo_codigo' (solo código de barras).
     * @param  string  $impresora  'zebra' (default) o 'ribetec'.
     */
    public function imprimir(Product $producto, ?string $codigoImpreso, string $tipo = 'barras', int $cantidad = 1, string $impresora = 'zebra'): array
    {
        if (! isset(self::IMPRESORAS[$impresora])) {
            $impresora = 'zebra';
        }

        $codigo = $codigoImpreso ?: ($producto->codigo_barras ?: $producto->sku);

        // Ninguna de las dos impresoras entiende UTF-8 multibyte (usan su propia tabla de un
        // solo byte), así que hay que bajar acentos y comillas/guiones "tipográficos" a su
        // equivalente ASCII antes de mandarlos, o salen como basura (ej. ” se imprime como "ÔÇØ").
        $descripcion = str_replace(
            ['Ñ', 'ñ', '”', '“', '’', '‘', '–', '—'],
            ['N', 'n', '"', '"', "'", "'", '-', '-'],
            strtoupper($producto->name)
        );
        $descripcion = substr($descripcion, 0, 48);
        $sku = strtoupper($producto->sku);
        $unidad = strtoupper($producto->unit);

        $contenido = self::LENGUAJE[$impresora] === 'tspl'
            ? $this->generarTspl($tipo, $descripcion, $sku, $unidad, $codigo, $cantidad)
            : $this->generarZpl($tipo, $descripcion, $sku, $unidad, $codigo, $cantidad);

        // Nombre único por trabajo: con time() dos impresiones en el mismo segundo
        // compartían archivo y una pisaba (o borraba) la etiqueta de la otra.
        $archivoTemporal = str_replace('/', '\\', storage_path('app/etiqueta_' . Str::uuid() . '.txt'));

        try {
            file_put_contents($archivoTemporal, $contenido);

            $impresion = copy($archivoTemporal, self::IMPRESORAS[$impresora]);

            if (! $impresion) {
                return [
                    'success' => false,
                    'message' => 'No se pudo copiar el archivo a la impresora compartida.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Etiquetas enviadas a impresión correctamente.',
                'codigo_generado' => $contenido,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al intentar imprimir: ' . $e->getMessage(),
            ];
        } finally {
            if (file_exists($archivoTemporal)) {
                unlink($archivoTemporal);
            }
        }
    }

    // --------------------------------------------------------
    // ZPL (Zebra)
    // --------------------------------------------------------
    private function generarZpl(string $tipo, string $descripcion, string $sku, string $unidad, string $codigo, int $cantidad): string
    {
        $encabezado = "^XA\n";
        $encabezado .= self::MODO_IMPRESION !== null ? self::MODO_IMPRESION . "\n" : '';
        $encabezado .= self::OSCURIDAD !== null ? '~SD' . self::OSCURIDAD . "\n" : '';
        $encabezado .= self::VELOCIDAD !== null ? '^PR' . self::VELOCIDAD . "\n" : '';
        // Etiqueta de 2 1/4" x 1" @ 203 dpi -> 457 x 203 dots.
        $encabezado .= "^PW457\n";
        $encabezado .= "^LL203\n";

        $zpl = '';

        // Etiqueta de texto: descripción, unidad y SKU, sin código de barras.
        if (in_array($tipo, ['barras', 'texto'], true)) {
            $zpl .= $encabezado;
            $zpl .= "^FO20,35^FB420,2,0,L^A0N,26,26^FD{$descripcion}^FS\n";
            $zpl .= "^FO20,110^A0N,26,26^FDUNIDAD: {$unidad}^FS\n";
            $zpl .= "^FO20,150^A0N,26,26^FDSKU: {$sku}^FS\n";
            $zpl .= "^PQ{$cantidad}\n";
            $zpl .= "^XZ\n";
        }

        // Etiqueta de código de barras.
        if (in_array($tipo, ['barras', 'solo_codigo'], true)) {
            // Lógica de ajuste para Code 128 sobre etiqueta de 2 1/4" (457 dots de ancho).
            // A grosor 2 caben ~15 caracteres antes de desbordar el ancho; los más largos
            // bajan a grosor 1 (barras más finas) y así entran hasta ~36 caracteres.
            if (strlen($codigo) <= 15) {
                $grosor = 2;
                $posicionX = 40;
                $fuenteTexto = 30;
            } else {
                $grosor = 1;
                $posicionX = 20;
                $fuenteTexto = 22;
            }

            $zpl .= $encabezado;
            // Franja superior: el texto arranca en y=32, no pegado al borde.
            $zpl .= "^FO0,32^FB457,1,0,C^A0N,{$fuenteTexto},{$fuenteTexto}^FDSKU: {$codigo}^FS\n";
            $zpl .= "^FO{$posicionX},68^BY{$grosor}^BCN,95,Y,N,N^FD{$codigo}^FS\n";
            $zpl .= "^PQ{$cantidad}\n";
            $zpl .= "^XZ\n";
        }

        return $zpl;
    }

    // --------------------------------------------------------
    // TSPL (Ribetec) — mismo diseño que el ZPL, en coordenadas de dots.
    // --------------------------------------------------------
    private function generarTspl(string $tipo, string $descripcion, string $sku, string $unidad, string $codigo, int $cantidad): string
    {
        // En TSPL el texto va entre comillas dobles; una " dentro (ej. 1/2") cerraría
        // el campo antes de tiempo, así que se cambia por dos comillas simples.
        $limpiar = fn (string $texto) => str_replace('"', "''", $texto);
        $descripcion = $limpiar($descripcion);
        $sku = $limpiar($sku);
        $unidad = $limpiar($unidad);
        $codigo = $limpiar($codigo);

        // Etiqueta de 2 1/4" x 1" con separación de ~3 mm entre etiquetas.
        $encabezado = "SIZE 2.25,1\r\n";
        $encabezado .= "GAP 0.12,0\r\n";
        $encabezado .= "DIRECTION 1\r\n";
        $encabezado .= "CLS\r\n";

        $tspl = '';

        // Etiqueta de texto: descripción (hasta 2 renglones), unidad y SKU.
        if (in_array($tipo, ['barras', 'texto'], true)) {
            // TSPL no parte renglones solo: fuente "3" = 16 dots por carácter -> 26 caben en 420.
            $renglones = array_slice(explode("\n", wordwrap($descripcion, 26, "\n", true)), 0, 2);

            $tspl .= $encabezado;
            foreach ($renglones as $i => $renglon) {
                $y = 35 + ($i * 32);
                $tspl .= "TEXT 20,{$y},\"3\",0,1,1,\"{$renglon}\"\r\n";
            }
            $tspl .= "TEXT 20,110,\"3\",0,1,1,\"UNIDAD: {$unidad}\"\r\n";
            $tspl .= "TEXT 20,150,\"3\",0,1,1,\"SKU: {$sku}\"\r\n";
            $tspl .= "PRINT {$cantidad}\r\n";
        }

        // Etiqueta de código de barras (mismo ajuste de grosor que en ZPL): arriba el
        // SKU real del producto, abajo el código que contienen las barras.
        if (in_array($tipo, ['barras', 'solo_codigo'], true)) {
            $grosor = strlen($codigo) <= 15 ? 2 : 1;

            // Barras centradas en la etiqueta según su ancho estimado.
            $anchoBarras = $this->anchoCode128($codigo) * $grosor;
            $xBarras = max(0, intdiv(457 - $anchoBarras, 2));

            $tspl .= $encabezado;
            $tspl .= $this->textoCentradoTspl("SKU: {$sku}", 32, '3');
            // BARCODE x,y,"128",alto,texto legible,rotación,barra angosta,barra ancha,"contenido"
            // Texto legible en 0: el de la impresora sale pegado a la izquierda, así que
            // se dibuja aparte, centrado debajo de las barras (68 + 95 de alto + 5 de aire).
            $tspl .= "BARCODE {$xBarras},68,\"128\",95,0,0,{$grosor},{$grosor},\"{$codigo}\"\r\n";
            $tspl .= $this->textoCentradoTspl($codigo, 168, '2');
            $tspl .= "PRINT {$cantidad}\r\n";
        }

        return $tspl;
    }

    /**
     * TEXT de TSPL centrado a mano en los 457 dots de ancho (TSPL no centra solo),
     * con la fuente preferida o, si no cabe, la más grande de las chicas que sí quepa.
     */
    private function textoCentradoTspl(string $texto, int $y, string $fuentePreferida): string
    {
        $fuente = '1';
        $anchoCaracter = self::FUENTES_TSPL['1'];
        foreach (self::FUENTES_TSPL as $nombre => $ancho) {
            if ($ancho > self::FUENTES_TSPL[$fuentePreferida]) {
                continue; // más grande que la preferida
            }
            if (strlen($texto) * $ancho <= 440) {
                $fuente = (string) $nombre;
                $anchoCaracter = $ancho;
                break;
            }
        }
        $x = max(0, intdiv(457 - strlen($texto) * $anchoCaracter, 2));

        return "TEXT {$x},{$y},\"{$fuente}\",0,1,1,\"{$texto}\"\r\n";
    }

    /**
     * Ancho aproximado en módulos (a barra angosta = 1 dot) de un Code 128 automático:
     * inicio + símbolos + dígito verificador (11 c/u) + fin (13). Los tramos de 4+ dígitos
     * se empacan de 2 en 2 (subconjunto C), como lo hace la impresora. Es una estimación
     * para centrar: si la impresora elige cambios de subconjunto distintos puede variar
     * un símbolo (~11 módulos).
     */
    private function anchoCode128(string $codigo): int
    {
        $simbolos = 0;
        $cambios = 0;
        $subconjuntoAnterior = null;

        preg_match_all('/\d{4,}|\D+|\d{1,3}/', $codigo, $tramos);
        foreach ($tramos[0] as $tramo) {
            $esNumerico = ctype_digit($tramo) && strlen($tramo) >= 4;
            $subconjunto = $esNumerico ? 'C' : 'B';

            if ($esNumerico) {
                // Un dígito impar sobrante se va en subconjunto B (con su cambio).
                $simbolos += intdiv(strlen($tramo), 2) + (strlen($tramo) % 2);
                $cambios += strlen($tramo) % 2;
            } else {
                $simbolos += strlen($tramo);
            }

            if ($subconjuntoAnterior !== null && $subconjuntoAnterior !== $subconjunto) {
                $cambios++;
            }
            $subconjuntoAnterior = $subconjunto;
        }

        return 11 * (1 + $simbolos + $cambios + 1) + 13;
    }
}
