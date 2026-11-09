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

    /** Código sin ningún espacio, para comparar "igual salvo por espacios". */
    public static function sinEspacios(?string $codigo): string
    {
        return preg_replace('/\s+/u', '', (string) $codigo);
    }

    /**
     * Etiquetas Yanmar: después de la clave traen espacios + 1 carácter de control
     * ("119195-18880  Y"). Devuelve la clave sin ese sufijo, o null si no lo trae.
     */
    public static function claveSinSufijoYanmar(?string $codigo): ?string
    {
        return preg_match('/^(.+?)\s+\S$/u', trim((string) $codigo), $m) ? $m[1] : null;
    }

    /**
     * Compara lo ESCANEADO contra el código guardado del producto. Devuelve null si
     * son idénticos (o si no tienen relación), o el tipo de diferencia:
     *   - 'espacios': mismo código, solo cambian los espacios.
     *   - 'sufijo':   la etiqueta trae la letra de control Yanmar que el código guardado no tiene.
     * Se compara en PHP y no en MySQL porque MySQL ignora los espacios al final.
     */
    public static function diferenciaConEscaneado(?string $codigoGuardado, string $sku, ?string $escaneado): ?string
    {
        $escaneado = self::codigoEscaneado($escaneado);
        if ($escaneado === null || $codigoGuardado === $escaneado) {
            return null;
        }
        if ($codigoGuardado !== null && self::sinEspacios($codigoGuardado) === self::sinEspacios($escaneado)) {
            return 'espacios';
        }
        $base = self::claveSinSufijoYanmar($escaneado);
        if ($base !== null && in_array(self::sinEspacios($base), [self::sinEspacios($codigoGuardado), self::sinEspacios($sku)], true)) {
            return 'sufijo';
        }

        return null;
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
