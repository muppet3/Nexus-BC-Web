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
        $codigo = ProductMatcher::normalizeCode($codigoCrudo);

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
