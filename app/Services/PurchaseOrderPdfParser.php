<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfTextParser;

/**
 * Lee el PDF de "Orden de compra" generado por el sistema de la empresa (Mara).
 * Calibrado contra un PDF real: smalot/pdfparser extrae el texto ya tabulado
 * con \t entre columnas para los renglones de artículos, y algunas etiquetas
 * de cabecera quedan concatenadas sin separador (ej. "24/jun./2026ALM67" es
 * Fecha+Folio pegados), por lo que cada patrón de abajo está pensado para ese
 * formato exacto, no para PDFs genéricos.
 */
class PurchaseOrderPdfParser
{
    private const MESES = [
        'ene' => '01', 'feb' => '02', 'mar' => '03', 'abr' => '04', 'may' => '05', 'jun' => '06',
        'jul' => '07', 'ago' => '08', 'sep' => '09', 'oct' => '10', 'nov' => '11', 'dic' => '12',
    ];

    public function parse(string $filePath): array
    {
        $parser = new PdfTextParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        $folio = null;
        $fechaRaw = null;
        $proveedor = null;
        $items = [];

        foreach ($lines as $i => $line) {
            // "24/jun./2026ALM67" -> Fecha="24/jun./2026", Folio="ALM67"
            if ($folio === null && preg_match('/^(\d{2}\/[^\/]+\/\d{4})(.+)$/u', $line, $m)) {
                $fechaRaw = $m[1];
                $folio = trim($m[2]);

                continue;
            }

            // La línea justo antes de la que trae juntas las etiquetas "Proveedor"/"Consignatario"
            // es el nombre del proveedor.
            if ($proveedor === null && preg_match('/Proveedor/iu', $line) && preg_match('/Consignatario/iu', $line)) {
                $proveedor = $lines[$i - 1] ?? null;

                continue;
            }

            if (! str_contains($line, "\t")) {
                continue;
            }

            $cols = explode("\t", $line);
            if (count($cols) !== 4) {
                continue;
            }

            [$skuRaw, $precioUnidad, $cantidad, $importeNombre] = $cols;
            $skuRaw = trim($skuRaw);

            if ($skuRaw === '' || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9\-\.\/]*$/', $skuRaw)) {
                continue; // no es un renglón de artículo real (ej. resto de la fila de encabezados)
            }

            if (! preg_match('/^([\d,]+\.\d{2})([A-Za-zÀ-ÿ]+)$/u', trim($precioUnidad), $pm)) {
                continue;
            }
            $precio = (float) str_replace(',', '', $pm[1]);
            $unidad = $pm[2];

            // El importe siempre trae exactamente 2 decimales (formato de dinero); si se usara
            // \d+ sin límite aquí, un nombre que empieza con dígitos (el SKU repetido en el
            // texto, ej. "119574-44150 ZINC...") se "comería" junto con el importe.
            $nombre = '';
            if (preg_match('/^([\d,]+\.\d{2})(.*)$/u', trim($importeNombre), $nm)) {
                $nombre = trim($nm[2]);
            }

            $items[] = [
                'raw_sku' => $skuRaw,
                'raw_description' => $nombre !== '' ? $nombre : $skuRaw,
                'unit' => $unidad,
                'quantity_ordered' => (int) trim($cantidad),
                'unit_price' => $precio,
            ];
        }

        return [
            'po_number' => $folio,
            'order_date' => $this->parseFecha($fechaRaw),
            'supplier_name' => $proveedor,
            'items' => $items,
        ];
    }

    private function parseFecha(?string $fecha): ?string
    {
        if (! $fecha || ! preg_match('/^(\d{2})\/([a-záéíóúñ]+)\.?\/(\d{4})$/iu', $fecha, $m)) {
            return null;
        }

        $mesKey = mb_strtolower(mb_substr($m[2], 0, 3));
        $mes = self::MESES[$mesKey] ?? null;

        return $mes ? "{$m[3]}-{$mes}-{$m[1]}" : null;
    }
}
