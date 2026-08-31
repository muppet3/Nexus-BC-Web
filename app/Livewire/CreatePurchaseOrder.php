<?php

namespace App\Livewire;

use App\Models\MsOrder;
use App\Models\Product;
use App\Services\ProductMatcher;
use App\Services\PurchaseOrderPdfParser;
use App\Services\UnitNormalizer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreatePurchaseOrder extends Component
{
    use WithFileUploads;

    public $file;

    public bool $showPreview = false;

    public string $po_number = '';

    public ?string $order_date = null;

    public string $supplier_name = '';

    public array $items = [];

    protected $rules = [
        'po_number' => 'required|string',
        'order_date' => 'nullable|date',
        'supplier_name' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.raw_sku' => 'required|string',
        'items.*.raw_description' => 'nullable|string',
        'items.*.unit' => 'nullable|string',
        'items.*.quantity_ordered' => 'required|integer|min:0',
        'items.*.unit_price' => 'nullable|numeric',
    ];

    public function mount()
    {
        abort_unless(auth()->user()->puedeGestionarCatalogo(), 403);
    }

    public function leerPdf(PurchaseOrderPdfParser $parser)
    {
        $this->validate(['file' => 'required|file|mimes:pdf']);

        $data = $parser->parse($this->file->getRealPath());

        $this->po_number = $data['po_number'] ?? '';
        $this->order_date = $data['order_date'];
        $this->supplier_name = $data['supplier_name'] ?? '';

        $this->items = array_map(function ($item) {
            $existente = ProductMatcher::findExisting($item['raw_sku']);

            return array_merge($item, [
                'existe' => (bool) $existente,
                'existing_name' => $existente?->name,
            ]);
        }, $data['items']);

        $this->showPreview = true;
    }

    public function removeItem(int $index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function guardar()
    {
        $this->validate();

        if (MsOrder::where('po_number', $this->po_number)->exists()) {
            $this->addError('po_number', 'Ya existe una Orden de Compra registrada con este folio.');

            return;
        }

        $productosNuevos = 0;

        DB::transaction(function () use (&$productosNuevos) {
            $order = MsOrder::create([
                'po_number' => $this->po_number,
                'supplier_name' => $this->supplier_name ?: null,
                'order_date' => $this->order_date ?: null,
            ]);

            foreach ($this->items as $item) {
                $sku = ProductMatcher::normalizeCode($item['raw_sku']);
                $product = ProductMatcher::findExisting($sku);

                if (! $product) {
                    $product = Product::create([
                        'sku' => $sku,
                        'name' => $item['raw_description'] ?: $sku,
                        'unit' => UnitNormalizer::normalize($item['unit'] ?? null),
                        'codigo_barras' => null,
                        'sin_codigo_fisico' => true,
                    ]);
                    $productosNuevos++;
                }

                $order->items()->create([
                    'product_id' => $product->id,
                    'raw_sku' => $sku,
                    'raw_description' => $item['raw_description'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'] !== '' ? $item['unit_price'] : null,
                ]);
            }
        });

        return redirect()->route('dashboard')
            ->with('success', "Orden de compra {$this->po_number} registrada. {$productosNuevos} productos nuevos agregados al catálogo.");
    }

    public function render()
    {
        return view('livewire.create-purchase-order')->layout('layouts.app');
    }
}
