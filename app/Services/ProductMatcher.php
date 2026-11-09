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
    // Limpia espacios normales y NBSP (&nbsp;) SOLO al inicio/final, que es lo que
    // ensucia PDFs y exports de Excel (ej. "SKU123 " con espacio invisible al final).
    // Ojo: NO colapsa espacios internos — algunos códigos de barras reales sí llevan
    // más de un espacio a propósito (ej. terminaciones "12  %"), y aplastarlos a uno
    // solo guardaba un código distinto al que en realidad se escaneó.
    public static function normalizeCode(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = trim(str_replace(["\xC2\xA0", "\xA0"], ' ', (string) $raw));

        return $clean === '' ? null : $clean;
    }

    // Código de barras ESCANEADO: se guarda tal cual lo leyó el lector — con sus
    // espacios (también al inicio/final), guiones, mayúsculas/minúsculas y prefijos
    // como "]C1". Solo se quitan Enter/Tab, que el lector manda al terminar de leer y
    // nunca son parte del código. Si no queda nada más que espacios, cuenta como vacío.
    // (normalizeCode sigue igual para SKUs de PDFs, Excel y altas a mano.)
    public static function codigoEscaneado(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $codigo = str_replace(["\r", "\n", "\t"], '', (string) $raw);

        return trim($codigo) === '' ? null : $codigo;
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
