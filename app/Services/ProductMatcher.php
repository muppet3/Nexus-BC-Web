<?php

namespace App\Services;

use App\Models\Product;

/**
 * Único criterio de "¿ya existe?" en toda la app (móvil, importador de OC,
 * importador de Excel, alta unitaria): match exacto por sku o codigo_barras,
 * que son los dos únicos campos con constraint UNIQUE en la tabla products.
 * El nombre nunca se usa como criterio autoritativo, solo esos dos campos.
 */
class ProductMatcher
{
    // Limpia espacios normales y NBSP (&nbsp;) que vienen de PDFs y exports de Excel,
    // para que "SKU123" y "SKU123 " (con espacio invisible) no se traten como distintos.
    public static function normalizeCode(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = trim(str_replace(["\xC2\xA0", "\xA0"], ' ', (string) $raw));
        $clean = preg_replace('/\s+/', ' ', $clean);

        return $clean === '' ? null : $clean;
    }

    public static function findExisting(?string $sku, ?string $codigoBarras = null): ?Product
    {
        $sku = self::normalizeCode($sku);
        $codigoBarras = self::normalizeCode($codigoBarras);

        if ($sku === null && $codigoBarras === null) {
            return null;
        }

        return Product::where(function ($q) use ($sku, $codigoBarras) {
            if ($sku !== null) {
                $q->orWhere('sku', $sku);
            }
            if ($codigoBarras !== null) {
                $q->orWhere('codigo_barras', $codigoBarras);
            }
        })->first();
    }
}
