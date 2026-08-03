<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentNote; // <--- Importamos el modelo nuevo

class ShipmentIndex extends Component
{
    use WithPagination;

    public $search = '';
    
    // --- VARIABLES VISTA RÁPIDA (YA LAS TENÍAS) ---
    public $showModal = false;
    public $selectedShipment = null;

    // --- VARIABLES BITÁCORA DE NOTAS (NUEVO) 💬 ---
    public $showNotesModal = false;
    public $noteShipmentId = null; // ID del viaje al que le escribimos
    public $noteShipmentTrip = ''; // Para mostrar en el título "Viaje 123"
    public $currentNotes = [];     // Lista de notas cargadas
    public $newNoteText = '';      // El texto que estás escribiendo

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // --- LÓGICA VISTA RÁPIDA (OJITO) ---
    public function openQuickView($shipmentId)
    {
        $this->selectedShipment = Shipment::with('items')->find($shipmentId);
        $this->showModal = true;
    }

    // --- LÓGICA BITÁCORA DE NOTAS (GLOBITO) 💬 ---
    public function openNotes($shipmentId)
    {
        $shipment = Shipment::find($shipmentId);
        if ($shipment) {
            $this->noteShipmentId = $shipment->id;
            $this->noteShipmentTrip = $shipment->trip_number;
            $this->loadNotes();
            $this->showNotesModal = true;
        }
    }

    public function loadNotes()
    {
        // Cargamos las notas ordenadas por fecha (más reciente arriba)
        $this->currentNotes = ShipmentNote::where('shipment_id', $this->noteShipmentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function saveNote()
    {
        $this->validate([
            'newNoteText' => 'required|string|min:3'
        ]);

        ShipmentNote::create([
            'shipment_id' => $this->noteShipmentId,
            'note' => $this->newNoteText,
            'user_id' => auth()->id(), // Guardamos quién la escribió
        ]);

        $this->newNoteText = ''; // Limpiamos el campo
        $this->loadNotes();      // Recargamos la lista
        
        // Opcional: Mandar una alerta visual pequeña
        $this->dispatch('notify', 'Nota agregada correctamente');
    }

    public function deleteNote($noteId)
    {
        ShipmentNote::find($noteId)?->delete();
        $this->loadNotes();
    }

    public function render()
    {
        // Consulta Base
        $query = Shipment::query()
            ->withCount(['items', 'notes']) // <--- AGREGAMOS 'notes' AQUÍ PARA EL GLOBITO ROJO
            ->orderByDesc('arrival_date');

        // --- LÓGICA DE BÚSQUEDA PROFUNDA 🔍 ---
        if ($this->search) {
            $term = $this->search;
            
            $query->where(function($q) use ($term) {
                // 1. Buscar en Cabecera
                $q->where('trip_number', 'like', "%{$term}%")
                  ->orWhere('pedimento', 'like', "%{$term}%")
                  ->orWhere('system', 'like', "%{$term}%")
                  
                // 2. Buscar en Partidas
                  ->orWhereHas('items', function ($subQ) use ($term) {
                      $subQ->where('supplier_raw', 'like', "%{$term}%")
                           ->orWhere('description_raw', 'like', "%{$term}%")
                           ->orWhere('dr_raw', 'like', "%{$term}%")
                           ->orWhere('oci_ms_raw', 'like', "%{$term}%")
                           ->orWhere('oc_group_raw', 'like', "%{$term}%")
                           ->orWhere('invoice_provider_raw', 'like', "%{$term}%")
                           ->orWhere('invoice_out_folio', 'like', "%{$term}%")
                           ->orWhere('entry_ms_folio', 'like', "%{$term}%")
                           ->orWhere('entry_gpo_folio', 'like', "%{$term}%");
                  });
            });
        }

        $shipments = $query->paginate(10);

        // KPIs
        $stats = [
            'pending_ms' => ShipmentItem::where('is_entered_ms', false)->count(),
            'pending_invoice' => ShipmentItem::whereNull('invoice_out_folio')->orWhere('invoice_out_folio', '')->count(),
            'active_trips' => Shipment::whereYear('arrival_date', date('Y'))->count(),
        ];

        return view('livewire.shipment-index', [
            'shipments' => $shipments,
            'stats' => $stats
        ])->layout('layouts.app');
    }
}