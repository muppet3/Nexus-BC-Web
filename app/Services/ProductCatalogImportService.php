<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lee un .xlsx con el mismo formato que exporta Microsip (Clave, Nombre,
 * U.compra, Almacenable, Ultima compra) — el mismo formato que se usa tanto
 * para una carga manual como para la sincronización periódica contra Microsip,
 * así que este es el único lector de Excel en toda la app.
 */
class ProductCatalogImportService
{
    public function read(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $result = [
            'rows' => [],
            'sin_clave' => [],
            'duplicados_internos' => [],
            'excluidos_servicio' => 0,
            'excluidos_no_almacenable' => 0,
        ];

        if (count($rows) < 2) {
            return $result;
        }

        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0]);
        $idxClave = array_search('clave', $header, true);
        $idxNombre = array_search('nombre', $header, true);
        $idxUnidad = array_search('u.compra', $header, true);
        $idxAlmacenable = array_search('almacenable', $header, true);

        $seen = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($idxAlmacenable !== false) {
                $almacenable = strtoupper(trim((string) ($row[$idxAlmacenable] ?? '')));
                if ($almacenable !== 'S') {
                    $result['excluidos_no_almacenable']++;

                    continue;
                }
            }

            $unidadRaw = $idxUnidad !== false ? (string) ($row[$idxUnidad] ?? '') : '';
            if (UnitNormalizer::isExcluded($unidadRaw)) {
                $result['excluidos_servicio']++;

                continue;
            }

            $sku = ProductMatcher::normalizeCode($idxClave !== false ? ($row[$idxClave] ?? null) : null);
            $nombre = trim((string) ($idxNombre !== false ? ($row[$idxNombre] ?? '') : ''));

            if ($sku === null) {
                $result['sin_clave'][] = ['nombre' => $nombre, 'unidad' => $unidadRaw];

                continue;
            }

            if (isset($seen[$sku])) {
                $result['duplicados_internos'][] = $sku;

                continue;
            }
            $seen[$sku] = true;

            $result['rows'][] = [
                'sku' => $sku,
                'name' => $nombre !== '' ? $nombre : $sku,
                'unit' => UnitNormalizer::normalize($unidadRaw),
            ];
        }

        return $result;
    }
}
