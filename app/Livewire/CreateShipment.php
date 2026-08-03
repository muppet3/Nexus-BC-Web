<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\ImportParsingService;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

class CreateShipment extends Component
{
    // Campos de Cabecera
    public $trip_number;
    public $pedimento;
    public $system = 'MARINA'; 
    public $exchange_rate;
    public $arrival_date;

    // Área de pegado y resultados
    public $rawText = '';
    public $parsedItems = []; 
    public $showPreview = false;

    // ✅ BLINDAJE ABSOLUTO: Todas las columnas declaradas para evitar la pérdida de datos
    protected $rules = [
        'trip_number' => 'required|string',
        'pedimento' => 'required|string',
        'system' => 'required|in:MARA,MARINA',
        'arrival_date' => 'nullable|date',
        'parsedItems' => 'required|array|min:1',
        
        // Reglas para cada campo de la fila (coinciden con el ImportParsingService)
        'parsedItems.*.dr_raw' => 'nullable|string',
        'parsedItems.*.supplier_raw' => 'nullable|string',
        'parsedItems.*.oc_group_raw' => 'nullable|string',
        'parsedItems.*.description_raw' => 'nullable|string',
        'parsedItems.*.cotizacion_raw' => 'nullable|string',
        'parsedItems.*.oci_ms_raw' => 'nullable|string',
        'parsedItems.*.anticipo_raw' => 'nullable|string',
        'parsedItems.*.invoice_provider_raw' => 'nullable|string',
        'parsedItems.*.value_raw' => 'nullable|string',
        'parsedItems.*.other_costs_raw' => 'nullable|string',
        'parsedItems.*.freight_raw' => 'nullable|string',
        'parsedItems.*.total_raw' => 'nullable|string',
        'parsedItems.*.system_origin' => 'nullable|string',
    ];

    public function parse(ImportParsingService $parser)
    {
        $this->validate(['rawText' => 'required|string|min:10']);
        $this->parsedItems = $parser->parse($this->rawText);
        $this->showPreview = true;
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function () {
            $shipment = Shipment::create([
                'trip_number' => $this->trip_number,
                'pedimento' => $this->pedimento,
                'system' => $this->system,
                'exchange_rate' => $this->exchange_rate,
                'status' => 'ABIERTO',
                'arrival_date' => $this->arrival_date,
            ]);

            foreach ($this->parsedItems as $item) {
                // Quitamos IDs temporales antes de insertar en la BD
                $dataToSave = collect($item)
                    ->except(['_temp_id', 'needs_invoice'])
                    ->toArray();

                $shipment->items()->create($dataToSave);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Viaje cargado correctamente.');
    }

    public function removeRow($index)
    {
        unset($this->parsedItems[$index]);
        $this->parsedItems = array_values($this->parsedItems);
    }

    public function render()
    {
        return view('livewire.create-shipment')->layout('layouts.app');
    }
}