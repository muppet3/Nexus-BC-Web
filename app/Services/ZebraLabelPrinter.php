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
 * recibe su lenguaje nativo, y cada una usa su propio rollo y diseño —
 *   - Zebra   -> ZPL. Etiqueta de 2 1/4" x 1 1/4" (457 x 254 dots @ 203 dpi):
 *                UNA sola etiqueta con todo, sin importar el tipo pedido:
 *
 *                ┌──────────────────────────────────────────┐
 *                │ SKU (grande)                  ┌───────┐  │
 *                │                               │  PZA  │  │
 *                │ DESCRIPCIÓN (hasta 2 renglones)└───────┘ │
 *                │ ──────────────────────────────────────── │
 *                │        ║│║║│║│║║│║ (centrado abajo)         │
 *                │          7501234567890                   │
 *                └──────────────────────────────────────────┘
 *
 *   - Ribetec -> TSPL (placa 4BARCODE 4B-2054A). Su emulación de ZPL imprimía
 *                el texto literal de los comandos en vez de interpretarlos, así
 *                que se le habla directo en TSPL. Sigue con su etiqueta de
 *                2 1/4" x 1" y sus etiquetas separadas por tipo.
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

    // Solo ZPL (Zebra). Medidas de su etiqueta en dots (203 dpi), margen y
    // recuadro de la unidad (esquina superior derecha).
    private const ANCHO = 457;

    private const ALTO = 254;

    // Franja horizontal donde va el contenido, ajustada con pruebas impresas. El
    // contenido es más angosto que el ancho nominal (457) porque la etiqueta real es
    // más angosta que el rollo: de 14 a 443 la unidad se cortaba a la derecha; de 10
    // a 420 se cortaba la primera letra a la izquierda y sobraba a la derecha. Queda
    // del 26 al 436. Ojo: si las guías del rollo quedan flojas la etiqueta se corre
    // de lado entre impresiones y ningún margen sirve; revisar eso primero.
    private const MARGEN_IZQ = 26;

    private const ANCHO_CONTENIDO = 410;

    private const UNIDAD_ANCHO = 92;

    private const UNIDAD_ALTO = 40;

    // Solo ZPL (Zebra). ^LT = corrimiento vertical de TODO el contenido, en dots
    // (+ baja, - sube; 8 dots = 1 mm). Sin él el SKU se salía por arriba; con 30 el
    // número bajo las barras quedaba sobre la orilla de abajo; 22 quedó bien.
    // Si después de calibrar la Zebra (botón FEED) se descuadra, regresar a 0.
    private const AJUSTE_VERTICAL = 22;

    // Fuentes internas de TSPL: nombre => ancho de cada carácter en dots (203 dpi).
    // Se usan para centrar el texto a mano y elegir la más grande que quepa
    // (el orden importa: de la más grande a la más chica).
    private const FUENTES_TSPL = ['3' => 16, '2' => 12, '1' => 8];

    /**
     * @param  string  $tipo  'barras' (descripción + código), 'texto' (solo descripción)
     *                        o 'solo_codigo'. Solo cambia algo en la Ribetec (etiquetas
     *                        separadas); en la Zebra los tres imprimen la etiqueta completa.
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
        $sku = strtoupper($producto->sku);
        $unidad = strtoupper($producto->unit);

        // La Zebra recibe la descripción completa (ella la acomoda en sus 2 renglones);
        // la Ribetec sigue con sus 48 caracteres de siempre.
        $contenido = self::LENGUAJE[$impresora] === 'tspl'
            ? $this->generarTspl($tipo, substr($descripcion, 0, 48), $sku, $unidad, $codigo, $cantidad)
            : $this->generarZpl($descripcion, $sku, $unidad, $codigo, $cantidad);

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
    private function generarZpl(string $descripcion, string $sku, string $unidad, string $codigo, int $cantidad): string
    {
        $m = self::MARGEN_IZQ;
        $anchoUtil = self::ANCHO_CONTENIDO;

        // Acentos que quedaron sin convertir (ej. "galón" -> "GALóN") a ASCII: la Zebra
        // no entiende UTF-8 y los imprimiría como basura.
        $aAscii = fn (string $texto) => strtoupper(Str::ascii($texto));
        $descripcion = $aAscii($descripcion);
        $unidad = $aAscii($unidad);

        // Code 128 en modo automático (^BC...,A): la Zebra empaca los dígitos de 2 en 2,
        // así códigos numéricos largos caben con barras normales. Grosor 2 (barras de
        // ~0.25 mm, las que mejor lee cualquier lector) si cabe en el ancho; si no, 1.
        $grosor = $this->anchoCode128($codigo) * 2 <= $anchoUtil ? 2 : 1;
        // Barras centradas en el ancho del contenido (el número legible sale centrado
        // debajo de ellas solo). El ancho es estimado: puede variar unos dots si la
        // Zebra empaca los dígitos distinto.
        $xBarras = $m + max(0, intdiv($anchoUtil - $this->anchoCode128($codigo) * $grosor, 2));

        // Unidad: hasta 6 caracteres en letra normal (PIEZA, METRO); SERVICIO, PAQUETE...
        // van en letra más chica para que entren completas en el recuadro.
        $unidad = substr($unidad, 0, 8);
        $fuenteUnidad = strlen($unidad) <= 6 ? '26,24' : '22,16';

        // Descripción en 2 renglones de ~34 caracteres (lo que cabe a esta letra). Se
        // parte aquí porque ^FB encima el texto sobrante en el último renglón.
        $renglones = array_slice(explode("\n", wordwrap($descripcion, 34, "\n", true)), 0, 2);
        $descripcion = implode('\&', $renglones);

        $zpl = "^XA\n";
        $zpl .= self::MODO_IMPRESION !== null ? self::MODO_IMPRESION . "\n" : '';
        $zpl .= self::OSCURIDAD !== null ? '~SD' . self::OSCURIDAD . "\n" : '';
        $zpl .= self::VELOCIDAD !== null ? '^PR' . self::VELOCIDAD . "\n" : '';
        $zpl .= '^PW' . self::ANCHO . "\n";
        $zpl .= '^LL' . self::ALTO . "\n";
        $zpl .= '^LT' . self::AJUSTE_VERTICAL . "\n";
        $zpl .= "^CI0\n";

        // SKU grande + recuadro de unidad.
        $xUnidad = $m + $anchoUtil - self::UNIDAD_ANCHO;
        $fuenteSku = strlen($sku) <= 15 ? '38,32' : (strlen($sku) <= 20 ? '30,24' : '24,20');

        $zpl .= "^FO{$m},12^FB" . ($xUnidad - $m - 8) . ",1,0,L^A0N,{$fuenteSku}^FD{$sku}^FS\n";
        $zpl .= "^FO{$xUnidad},10^GB" . self::UNIDAD_ANCHO . ',' . self::UNIDAD_ALTO . ",3^FS\n";
        $zpl .= "^FO{$xUnidad},20^FB" . self::UNIDAD_ANCHO . ",1,0,C^A0N,{$fuenteUnidad}^FD{$unidad}^FS\n";

        // Descripción, línea y código de barras centrado abajo.
        $zpl .= "^FO{$m},58^FB{$anchoUtil},2,2,L^A0N,24,22^FD{$descripcion}^FS\n";
        $zpl .= "^FO{$m},106^GB{$anchoUtil},2,2^FS\n";
        // ^BC con texto legible = Y (el número sale centrado debajo de las barras) y
        // modo A = automático (empaca dígitos).
        $zpl .= "^FO{$xBarras},116^BY{$grosor}^BCN,100,Y,N,N,A^FD{$codigo}^FS\n";

        $zpl .= "^PQ{$cantidad}\n";
        $zpl .= "^XZ\n";

        return $zpl;
    }

    // --------------------------------------------------------
    // TSPL (Ribetec) — su diseño de siempre: etiqueta de 2 1/4" x 1", separadas por tipo.
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
