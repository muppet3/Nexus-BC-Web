<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\ProductMatcher;
use App\Services\UnitNormalizer;
use Livewire\Component;

class CreateProduct extends Component
{
    public string $sku = '';

    public string $name = '';

    public string $unit = 'pieza';

    public string $codigo_barras = '';

    public string $marca = '';

    public string $grupo = '';

    public string $linea = '';

    public ?Product $productoExistente = null;

    public bool $creado = false;

    protected $rules = [
        'sku' => 'required|string',
        'name' => 'required|string',
        'unit' => 'required|string',
        'codigo_barras' => 'nullable|string',
        'marca' => 'nullable|string',
        'grupo' => 'nullable|string',
        'linea' => 'nullable|string',
    ];

    public function mount()
    {
        abort_unless(auth()->user()->puedeGestionarCatalogo(), 403);
    }

    public function guardar()
    {
        $this->validate();
        $this->productoExistente = null;
        $this->creado = false;

        $existente = ProductMatcher::findExisting($this->sku, $this->codigo_barras ?: null);

        if ($existente) {
            $this->productoExistente = $existente;

            return;
        }

        Product::create([
            'sku' => ProductMatcher::normalizeCode($this->sku),
            'name' => $this->name,
            'unit' => UnitNormalizer::normalize($this->unit),
            'codigo_barras' => ProductMatcher::normalizeCode($this->codigo_barras),
            'sin_codigo_fisico' => $this->codigo_barras === '',
            'marca' => $this->marca ?: null,
            'grupo' => $this->grupo ?: null,
            'linea' => $this->linea ?: null,
        ]);

        $this->creado = true;
        $this->reset(['sku', 'name', 'codigo_barras', 'marca', 'grupo', 'linea']);
        $this->unit = 'pieza';
    }

    public function render()
    {
        return view('livewire.create-product')->layout('layouts.app');
    }
}
