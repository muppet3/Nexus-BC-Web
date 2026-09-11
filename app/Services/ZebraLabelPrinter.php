<?php

namespace App\Services;

use App\Models\Product;

/**
 * Genera e imprime las etiquetas Zebra (ZPL) — usado tanto por la API del
 * celular como por las pantallas web que corren en la misma máquina que la
 * impresora compartida. Un solo lugar con la plantilla ZPL para que ambos
 * lados impriman siempre exactamente igual.
 */
class ZebraLabelPrinter
{
    /**
     * @param  string  $tipo  'barras' (descripción + código, 2 etiquetas), 'texto'
     *                        (solo descripción) o 'solo_codigo' (solo código de barras).
     */
    public function imprimir(Product $producto, ?string $codigoImpreso, string $tipo = 'barras', int $cantidad = 1): array
    {
        $codigo = $codigoImpreso ?: ($producto->codigo_barras ?: $producto->sku);

        // La impresora Zebra no entiende UTF-8 multibyte (usa su propia tabla de un solo byte),
        // así que hay que bajar acentos y comillas/guiones "tipográficos" a su equivalente ASCII
        // antes de mandarlos, o salen como basura (ej. ” se imprime como "ÔÇØ").
        $descripcion = str_replace(
            ['Ñ', 'ñ', '”', '“', '’', '‘', '–', '—'],
            ['N', 'n', '"', '"', "'", "'", '-', '-'],
            strtoupper($producto->name)
        );
        $descripcion = substr($descripcion, 0, 48);
        $sku = strtoupper($producto->sku);
        $unidad = strtoupper($producto->unit);

        $zpl = '';

        // Etiqueta de texto: descripción, unidad y SKU, sin código de barras.
        if (in_array($tipo, ['barras', 'texto'], true)) {
            // Etiqueta de 2 1/4" x 1" @ 203 dpi -> 457 x 203 dots.
            $zpl .= "^XA\n";
            $zpl .= "~SD20\n";
            $zpl .= "^PW457\n";
            $zpl .= "^LL203\n";

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
            $longitud = strlen($codigo);

            if ($longitud <= 15) {
                $grosor = 2;
                $posicionX = 40;
                $fuenteTexto = 30;
            } else {
                $grosor = 1;
                $posicionX = 20;
                $fuenteTexto = 22;
            }

            // Etiqueta de 2 1/4" x 1" @ 203 dpi -> 457 x 203 dots.
            $zpl .= "^XA\n";
            $zpl .= "~SD20\n";
            $zpl .= "^PW457\n";
            $zpl .= "^LL203\n";

            // Franja superior: el texto arranca en y=32, no pegado al borde.
            $zpl .= "^FO0,32^FB457,1,0,C^A0N,{$fuenteTexto},{$fuenteTexto}^FDSKU: {$codigo}^FS\n";
            $zpl .= "^FO{$posicionX},68^BY{$grosor}^BCN,95,Y,N,N^FD{$codigo}^FS\n";
            $zpl .= "^PQ{$cantidad}\n";
            $zpl .= "^XZ\n";
        }

        try {
            $nombreArchivo = 'etiqueta_' . time() . '.txt';
            $archivoTemporal = storage_path('app/' . $nombreArchivo);
            $archivoTemporal = str_replace('/', '\\', $archivoTemporal);

            file_put_contents($archivoTemporal, $zpl);

            $impresoraCompartida = "\\\\127.0.0.1\\ZEBRA";
            $impresion = copy($archivoTemporal, $impresoraCompartida);

            if (file_exists($archivoTemporal)) {
                unlink($archivoTemporal);
            }

            if (! $impresion) {
                return [
                    'success' => false,
                    'message' => 'No se pudo copiar el archivo a la impresora compartida.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Etiquetas enviadas a impresión correctamente.',
                'zpl_generado' => $zpl,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al intentar imprimir: ' . $e->getMessage(),
            ];
        }
    }
}
