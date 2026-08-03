<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ShipmentItem;
use App\Models\Shipment;
use Illuminate\Support\Facades\Storage;

class GlobalSearch extends Component
{
    use WithPagination;

    // Filtros
    public $search = '';
    public $selectedYear = '';
    public $statusFilter = '';

    // Variables Modal
    public $showModal = false;
    public $activeItem = null;
    public $itemDocuments = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedYear' => ['except' => ''],
        'statusFilter' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openDocuments($itemId)
    {
        $this->activeItem = ShipmentItem::find($itemId);
        if ($this->activeItem) {
            $this->itemDocuments = $this->activeItem->documents()->orderByDesc('created_at')->get();
            $this->showModal = true;
        }
    }

    // --- LÓGICA CENTRAL DE FILTRADO (REUTILIZABLE) ---
    private function getFilteredQuery()
    {
        $query = ShipmentItem::query()->with(['shipment', 'documents']);

        // 1. Buscador Texto
        if (!empty($this->search)) {
            $term = $this->search;
            $query->where(function($q) use ($term) {
                $q->where('supplier_raw', 'like', "%{$term}%")
                  ->orWhere('description_raw', 'like', "%{$term}%")
                  ->orWhere('oc_group_raw', 'like', "%{$term}%")
                  ->orWhere('invoice_out_folio', 'like', "%{$term}%")
                  ->orWhere('entry_ms_folio', 'like', "%{$term}%")
                  ->orWhere('entry_gpo_folio', 'like', "%{$term}%")
                  ->orWhereHas('shipment', function($subQ) use ($term) {
                      $subQ->where('trip_number', 'like', "%{$term}%")
                           ->orWhere('pedimento', 'like', "%{$term}%");
                  });
            });
        }

        // 2. Filtro Año
        if (!empty($this->selectedYear)) {
            $query->whereHas('shipment', function($q) {
                $q->whereYear('arrival_date', $this->selectedYear);
            });
        }

        // 3. Filtro Estatus
        if ($this->statusFilter === 'pending_ms') {
            $query->where('is_entered_ms', false);
        }
        elseif ($this->statusFilter === 'pending_invoice') {
            $query->whereDoesntHave('documents', function($q) {
                $q->where('category', 'factura_salida');
            });
        }
        elseif ($this->statusFilter === 'closed') {
            $query->where('is_closed_accounting', true);
        }

        // Ordenar siempre por lo más nuevo
        return $query->orderByDesc('id');
    }

    // --- NUEVA FUNCIÓN: EXPORTAR A EXCEL (CSV) ---
    public function export()
    {
        $fileName = 'Rastreo_Completo_' . date('Y-m-d_H-i') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM para tildes en Excel

            // ENCABEZADOS (Nombres Amigables)
            fputcsv($handle, [
                'Sistema', 
                'Fecha Llegada',
                'Viaje', 
                'Pedimento', 
                'DR',                // dr_raw
                'Proveedor',         //supplier_raw
                'Descripción',       //description_raw
                'Factura Proveedor', // invoice_provider_raw
                'OC Marine (Mara)',  // oci_ms_raw
                'Ingreso MS',        // Folio MS entry_ms_folio
                'Factura Venta',     // Salida invoice_out_folio
                'OC Grupo',          // oc_group_raw
                'Ingreso GPO',       // Folio GPO entry_gpo_folio
                'Cotización',        // cotizacion_raw
                'Anticipo',          // anticipo_raw
                'Estatus Conta', 
                'Confirmado MS',
                'Valor',             // value_raw (Informativo)
                'Total'              // total_raw (Informativo)
            ]);

            $this->getFilteredQuery()->chunk(100, function ($items) use ($handle) {
                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item->shipment->system, 
                        $item->shipment->arrival_date ? $item->shipment->arrival_date->format('Y-m-d') : '',
                        $item->shipment->trip_number,
                        $item->shipment->pedimento,    
                        $item->dr_raw,        
                        $item->supplier_raw,
                        $item->description_raw,
                        $item->invoice_provider_raw, // CAMPO CORREGIDO
                        $item->oci_ms_raw,           // CAMPO CORREGIDO   
                        $item->entry_ms_folio,
                        $item->invoice_out_folio,
                        $item->oc_group_raw,  
                        $item->entry_gpo_folio,
                        $item->cotizacion_raw,          
                        $item->anticipo_raw,    
                        $item->is_closed_accounting ? 'CERRADO' : 'ABIERTO',
                        $item->is_entered_ms ? 'SI' : 'NO',
                        $item->value_raw,            // Informativo del Excel
                        $item->total_raw             // Informativo del Excel
                    ]);
                }
            });

            fclose($handle);
        }, $fileName);
    }

    public function render()
    {
        // Reutilizamos la misma consulta para la tabla
        $items = $this->getFilteredQuery()->paginate(50);

        $years = Shipment::selectRaw('YEAR(arrival_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('livewire.global-search', [
            'items' => $items,
            'years' => $years
        ])->layout('layouts.app');
    }
}