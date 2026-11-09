<?php

namespace App\Services;

use App\Models\HistorialAuditoria;
use App\Models\Product;
use App\Models\User;

/**
 * Asignar un código de barras a un producto que no lo tiene — usado tanto por
 * la herramienta web "Asignar Código de Barras" como por su equivalente en la
 * app, para que las dos plataformas validen y guarden exactamente igual.
 */
class BarcodeAssignmentService
{
    /**
     * @return array{success: bool, message: string, producto?: Product}
     */
    public function asignar(Product $producto, string $codigoCrudo, User $usuario): array
    {
        // Tal cual se escaneó (ver ProductMatcher::codigoEscaneado).
        $codigo = ProductMatcher::codigoEscaneado($codigoCrudo);

        if ($codigo === null) {
            return ['success' => false, 'message' => 'El código no puede estar vacío.'];
        }

        $existente = Product::where('codigo_barras', $codigo)
            ->where('id', '!=', $producto->id)
            ->first();

        if ($existente) {
            return [
                'success' => false,
                'message' => "Ese código ya está asignado a: {$existente->sku} — {$existente->name}",
            ];
        }

        // Otro producto con el mismo código salvo por espacios ("X  Y" vs "X Y"): casi
        // seguro es el mismo código capturado distinto. Se detiene para no dejar dos
        // códigos casi iguales en productos diferentes.
        $casiIgual = Product::whereRaw("REPLACE(codigo_barras, ' ', '') = ?", [ProductMatcher::sinEspacios($codigo)])
            ->where('id', '!=', $producto->id)
            ->first();

        if ($casiIgual) {
            $guardado = str_replace(' ', '␣', $casiIgual->codigo_barras);

            return [
                'success' => false,
                'message' => "Ese código es igual al de {$casiIgual->sku} — {$casiIgual->name} (guardado como \"{$guardado}\"), solo cambian los espacios. Revisa cuál producto es el correcto.",
            ];
        }

        $anterior = $producto->codigo_barras;

        $producto->update([
            'codigo_barras' => $codigo,
            'sin_codigo_fisico' => false,
        ]);

        HistorialAuditoria::create([
            'product_id' => $producto->id,
            'hallazgo_id' => null,
            'user_id' => $usuario->id,
            'supervisor_id' => null,
            'accion' => 'Código de barras asignado',
            'detalle_anterior' => 'Código: ' . ($anterior ?: 'sin código'),
            'detalle_nuevo' => "Código: {$codigo}",
        ]);

        return [
            'success' => true,
            'message' => "Código asignado a {$producto->sku}.",
            'producto' => $producto,
        ];
    }
}
