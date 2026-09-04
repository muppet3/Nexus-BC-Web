<?php

namespace App\Services;

/**
 * Un único punto para homologar "pieza"/"Pieza"/"PZA"/"Pza" (y variantes de las
 * demás unidades) a una sola forma canónica, sin importar si el dato viene de
 * Microsip, de un PDF de OC o de una alta manual.
 */
class UnitNormalizer
{
    private const MAP = [
        'pieza' => 'pieza', 'piezas' => 'pieza', 'pza' => 'pieza', 'pzas' => 'pieza', 'pz' => 'pieza',
        // CUB y R: abreviaturas sin significado claro, se homologan a pieza por indicación directa.
        'cub' => 'pieza', 'r' => 'pieza',
        'kit' => 'kit', 'kits' => 'kit',
        'metro' => 'metro', 'metros' => 'metro', 'mt' => 'metro', 'mts' => 'metro', 'mtr' => 'metro',
        'pie' => 'pie', 'pies' => 'pie',
        'cm' => 'centímetro', 'centimetro' => 'centímetro', 'centimetros' => 'centímetro', 'centímetro' => 'centímetro', 'centímetros' => 'centímetro',
        'caja' => 'caja', 'cajas' => 'caja',
        'paquete' => 'paquete', 'paquetes' => 'paquete', 'paq' => 'paquete',
        'kilogramo' => 'kilogramo', 'kilogramos' => 'kilogramo', 'kg' => 'kilogramo',
        'galon' => 'galón', 'galones' => 'galón', 'gal' => 'galón', 'galón' => 'galón',
    ];

    // Unidades que en realidad son servicios (no artículos almacenables): se excluyen del import.
    private const EXCLUDED = ['servicio', 'servicios', 'ser'];

    public static function isExcluded(?string $raw): bool
    {
        $key = self::normalizeKey($raw);

        return $key !== null && in_array($key, self::EXCLUDED, true);
    }

    // Sin unidad especificada -> "pieza" por default, como se pidió.
    public static function normalize(?string $raw): string
    {
        $key = self::normalizeKey($raw);

        if ($key === null) {
            return 'pieza';
        }

        return self::MAP[$key] ?? trim((string) $raw);
    }

    private static function normalizeKey(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $clean = trim(str_replace(["\xC2\xA0", "\xA0"], ' ', (string) $raw));

        return $clean === '' ? null : mb_strtolower($clean);
    }
}
