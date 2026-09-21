<?php

namespace App\Livewire;

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
    public string $search = '';

    public array $resultados = [];

    public ?Product $productoActivo = null;

    public string $codigoNuevo = '';

    // Código "comando" para el lector: sin importar qué haya escrito ya en el campo,
    // al escanear esta etiqueta se limpia todo. Como el lector solo escribe texto,
    // esta palabra queda pegada a lo que hubiera antes (ej. "119574-33150LIMPIAR"),
    // por eso se busca como subcadena y no como coincidencia exacta.
    private const CODIGO_LIMPIAR = 'LIMPIAR';

    public function updatedSearch()
    {
        $this->productoActivo = null;

        if (str_contains(strtoupper($this->search), self::CODIGO_LIMPIAR)) {
            $this->search = '';
            $this->resultados = [];

            return;
        }

        if (strlen($this->search) < 2) {
            $this->resultados = [];

            return;
        }

        // Mismo criterio que usa la app: código de barras exacto, sku/nombre por coincidencia parcial.
        // Sirve también para encontrar un producto que YA tiene un código (ej. mal asignado) al escanearlo.
        $this->resultados = Product::where('codigo_barras', $this->search)
            ->orWhere('sku', 'like', "%{$this->search}%")
            ->orWhere('name', 'like', "%{$this->search}%")
            ->orderBy('sku')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'codigo_barras', 'sin_codigo_fisico', 'stock_real', 'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'codigo_barras' => $p->codigo_barras,
                'sin_codigo_fisico' => $p->sin_codigo_fisico,
                'stock_real' => $p->stock_real,
                'ubicacion' => $p->ubicacion_completa,
            ])
            ->all();
    }

    public function limpiarBusqueda()
    {
        $this->search = '';
        $this->resultados = [];
    }

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
