<?php

namespace App\Livewire;

use App\Models\HistorialAuditoria;
use App\Models\Product;
use App\Services\ProductMatcher;
use Livewire\Component;

/**
 * Herramienta de piso: buscar un producto por SKU (el código de barras no da
 * resultado porque justo es lo que falta) y asignarle únicamente su código de
 * barras — no toca cantidad, ubicación ni stock. Pensada para un lector
 * USB/Bluetooth conectado a la laptop, sin ocupar un teléfono.
 */
class AsignarCodigoBarras extends Component
{
    public string $search = '';

    public array $resultados = [];

    public ?Product $productoActivo = null;

    public string $codigoNuevo = '';

    public function updatedSearch()
    {
        $this->productoActivo = null;

        if (strlen($this->search) < 2) {
            $this->resultados = [];

            return;
        }

        $this->resultados = Product::where('sku', 'like', "%{$this->search}%")
            ->orWhere('name', 'like', "%{$this->search}%")
            ->orderBy('sku')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'codigo_barras', 'sin_codigo_fisico'])
            ->all();
    }

    public function seleccionar($productId)
    {
        $this->productoActivo = Product::find($productId);
        $this->codigoNuevo = '';
        $this->resultados = [];
    }

    public function cambiarProducto()
    {
        $this->productoActivo = null;
        $this->codigoNuevo = '';
        $this->search = '';
    }

    public function guardarCodigo()
    {
        $this->validate(['codigoNuevo' => 'required|string']);

        if (! $this->productoActivo) {
            return;
        }

        $codigo = ProductMatcher::normalizeCode($this->codigoNuevo);

        $existente = Product::where('codigo_barras', $codigo)
            ->where('id', '!=', $this->productoActivo->id)
            ->first();

        if ($existente) {
            $this->addError('codigoNuevo', "Ese código ya está asignado a: {$existente->sku} — {$existente->name}");

            return;
        }

        $anterior = $this->productoActivo->codigo_barras;

        $this->productoActivo->update([
            'codigo_barras' => $codigo,
            'sin_codigo_fisico' => false,
        ]);

        HistorialAuditoria::create([
            'product_id' => $this->productoActivo->id,
            'hallazgo_id' => null,
            'user_id' => auth()->id(),
            'supervisor_id' => null,
            'accion' => 'Código de barras asignado',
            'detalle_anterior' => 'Código: ' . ($anterior ?: 'sin código'),
            'detalle_nuevo' => "Código: {$codigo}",
        ]);

        session()->flash('success', "Código asignado a {$this->productoActivo->sku}.");
        $this->cambiarProducto();
    }

    public function render()
    {
        return view('livewire.asignar-codigo-barras')->layout('layouts.app');
    }
}
