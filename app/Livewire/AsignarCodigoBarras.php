<?php

namespace App\Livewire;

use App\Livewire\Concerns\BuscaProductosPorPiso;
use App\Models\Product;
use App\Services\BarcodeAssignmentService;
use App\Services\ZebraLabelPrinter;
use Livewire\Component;

/**
 * Herramienta de piso: buscar un producto por SKU (el código de barras no da
 * resultado porque justo es lo que falta) y asignarle únicamente su código de
 * barras — no toca cantidad, ubicación ni stock. Pensada para un lector
 * USB/Bluetooth conectado a la laptop, sin ocupar un teléfono.
 */
class AsignarCodigoBarras extends Component
{
    use BuscaProductosPorPiso;

    public string $search = '';

    public array $resultados = [];

    public ?Product $productoActivo = null;

    public string $codigoNuevo = '';

    public function limpiarCodigoNuevo()
    {
        $this->codigoNuevo = '';
        $this->resetErrorBag('codigoNuevo');
    }

    public function seleccionar($productId)
    {
        $this->productoActivo = Product::find($productId);
        $this->codigoNuevo = '';
        $this->resultados = [];
        // Avisa al navegador que ya se ve el campo de código nuevo, para que Alpine le
        // ponga el foco (ver mismo mecanismo en guardarCodigo()).
        $this->dispatch('producto-seleccionado');
    }

    public function cambiarProducto()
    {
        $this->productoActivo = null;
        $this->codigoNuevo = '';
        $this->search = '';
    }

    public function guardarCodigo(BarcodeAssignmentService $service)
    {
        $this->validate(['codigoNuevo' => 'required|string']);

        if (! $this->productoActivo) {
            return;
        }

        $resultado = $service->asignar($this->productoActivo, $this->codigoNuevo, auth()->user());

        if (! $resultado['success']) {
            $this->addError('codigoNuevo', $resultado['message']);

            return;
        }

        session()->flash('success', $resultado['message']);
        $this->cambiarProducto();
        // Avisa al navegador que ya regresó al buscador, para que Alpine le devuelva
        // el foco (un $wire.metodo().then() no fue confiable aquí, esto sí).
        $this->dispatch('codigo-guardado');
    }

    public function imprimirCodigo(ZebraLabelPrinter $printer)
    {
        if (! $this->productoActivo) {
            return;
        }

        $resultado = $printer->imprimir($this->productoActivo, null, 'solo_codigo');
        session()->flash($resultado['success'] ? 'success' : 'error', $resultado['message']);
    }

    public function imprimirDescripcion(ZebraLabelPrinter $printer)
    {
        if (! $this->productoActivo) {
            return;
        }

        $resultado = $printer->imprimir($this->productoActivo, null, 'texto');
        session()->flash($resultado['success'] ? 'success' : 'error', $resultado['message']);
    }

    public function render()
    {
        return view('livewire.asignar-codigo-barras')->layout('layouts.app');
    }
}
