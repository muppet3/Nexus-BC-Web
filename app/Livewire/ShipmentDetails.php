<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShipmentDetails extends Component
{
    use WithFileUploads;

    public Shipment $shipment;
    public $items;
    public $search = '';
    
    public $globalMsDoc = null; 
    public $fileDrops = []; 
    public $debugMessage = '';

    public $showModal = false;
    public $activeItem = null; 
    public $newFile; 
    public $existingDocuments = [];

    protected $listeners = ['refreshItems' => '$refresh'];

    public function updatedSearch()
    {
        $this->loadItems();
    }

    public function mount(Shipment $shipment)
    {
        $this->shipment = $shipment;
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->globalMsDoc = $this->shipment->documents()
            ->where('category', 'ingreso_ms_global')
            ->latest()
            ->first();

        $query = $this->shipment->items()
            ->with(['documents']) 
            ->withCount('documents')
            ->orderBy('id');

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function($q) use ($search) {
                $q->where('supplier_raw', 'like', "%{$search}%")
                  ->orWhere('description_raw', 'like', "%{$search}%")
                  ->orWhere('oc_group_raw', 'like', "%{$search}%")
                  ->orWhere('invoice_out_folio', 'like', "%{$search}%")
                  ->orWhere('entry_ms_folio', 'like', "%{$search}%")
                  ->orWhere('entry_gpo_folio', 'like', "%{$search}%");
            });
        }

        $this->items = $query->get();
    }

    public function updateArrivalDate($date)
    {
        if ($date) {
            $this->shipment->arrival_date = $date;
            $this->shipment->save();
            $this->shipment->refresh(); 
        }
    }

    private function getStoragePath($category)
    {
        $date = $this->shipment->arrival_date ?? $this->shipment->created_at;
        $year = $date ? $date->format('Y') : date('Y');
        $system = Str::slug($this->shipment->system ?? 'sistema');
        $tripFolder = "Viaje_{$this->shipment->trip_number}_Ped_{$this->shipment->pedimento}";
        $basePath = "importaciones/{$year}/{$system}/{$tripFolder}";

        if ($category === 'ingreso_ms_global') {
            return $basePath;
        }

        if (in_array($category, ['ingreso_ms', 'GLOBAL', 'PEDIMENTO', 'FOTOS', 'COSTOS', 'GENERAL_VIAJE'])) {
            return $basePath;
        }

        return "{$basePath}/Grupo";
    }

    public function updatedFileDrops($value, $key)
    {
        $this->debugMessage = '';

        try {
            $parts = explode('.', $key);
            if (count($parts) !== 2) return;

            $itemId = $parts[0]; 
            $category = $parts[1];
            $file = $value;

            // --- ARCHIVO MAESTRO GLOBAL ---
            if ($itemId === 'header' && $category === 'ingreso_ms') {
                $this->validate(["fileDrops.header.ingreso_ms" => 'file|max:51200']);
                
                $originalName = $file->getClientOriginalName();
                $directory = $this->getStoragePath('ingreso_ms_global'); 
                $path = $file->storeAs($directory, "GLOBAL_MS_" . $originalName, 'public');

                Document::where('shipment_id', $this->shipment->id)
                    ->where('category', 'ingreso_ms_global')
                    ->delete();

                Document::create([
                    'shipment_id' => $this->shipment->id,
                    'shipment_item_id' => null, 
                    'type' => 'EVIDENCIA_GLOBAL',
                    'category' => 'ingreso_ms_global',
                    'generated_filename' => $originalName,
                    'file_path' => $path,
                ]);

                $this->shipment->items()->update(['is_entered_ms' => true]);

                unset($this->fileDrops['header']['ingreso_ms']);
                $this->loadItems();
                return;
            }

            // --- LÓGICA POR PARTIDA ---
            $itemToCheck = ShipmentItem::find($itemId);
            if ($itemToCheck && $itemToCheck->is_closed_accounting) {
                $this->addError("fileDrops.$itemId.$category", '⛔ CERRADO: No se permiten cambios.');
                unset($this->fileDrops[$itemId][$category]);
                return;
            }

            $this->validate(["fileDrops.$itemId.$category" => 'file|max:51200']);
            
            $originalName = $file->getClientOriginalName();
            $directory = $this->getStoragePath($category);
            $safeName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $safeName, 'public');

            // 1. Guardar documento PRINCIPAL
            Document::create([
                'shipment_id' => $this->shipment->id,
                'shipment_item_id' => $itemId,
                'type' => 'EVIDENCIA',
                'category' => $category,
                'generated_filename' => $originalName,
                'file_path' => $path,
            ]);

            $this->updateStatusCheck($itemId, $category, true);

            // 2. ✨ MAGIA DE ADN: Buscar a las hermanas y vincularlas automáticamente
            $count = $this->distributeFileToSiblings($itemToCheck, $category, $path, $originalName);

            if ($count > 0) {
                $this->dispatch('notify', "Archivo vinculado automáticamente a $count partidas hermanas.");
            }

            unset($this->fileDrops[$itemId][$category]);
            $this->loadItems();

        } catch (\Exception $e) {
            $this->debugMessage = "❌ ERROR: " . $e->getMessage();
        }
    }

    /**
     * MAGIA DE ADN: Vincula archivos basado en OC Grupo o Factura de Proveedor
     */
    private function distributeFileToSiblings(ShipmentItem $originalItem, string $category, string $filePath, string $fileName)
    {
        $siblingsCount = 0;
        $columnToMatch = null;
        $valueToMatch = null;

        // LECTURA DE ADN SEGÚN LA CASILLA DONDE SE SOLTÓ EL ARCHIVO
        switch ($category) {
            case 'oc_grupo':
            case 'factura_salida':
            case 'ingreso_gpo':
                $columnToMatch = 'oc_group_raw';
                $valueToMatch = $originalItem->oc_group_raw;
                break;
            
            case 'ingreso_ms':
                $columnToMatch = 'invoice_provider_raw';
                $valueToMatch = $originalItem->invoice_provider_raw;
                break;
        }

        // Si la celda está vacía, o dice "X", ignorar replicación
        if (!$columnToMatch || empty($valueToMatch) || strtolower(trim($valueToMatch)) === 'x' || strtolower(trim($valueToMatch)) === 'n/a') {
            return 0;
        }

        // Buscar hermanas
        $siblings = $this->shipment->items()
            ->where($columnToMatch, $valueToMatch)
            ->where('id', '!=', $originalItem->id) // No duplicar al original
            ->get();

        // Vincular
        foreach ($siblings as $sibling) {
            Document::create([
                'shipment_id' => $this->shipment->id,
                'shipment_item_id' => $sibling->id,
                'type' => 'EVIDENCIA',
                'category' => $category,
                'generated_filename' => $fileName, 
                'file_path' => $filePath,          
            ]);

            $this->updateStatusCheck($sibling->id, $category, true);
            $siblingsCount++;
        }

        return $siblingsCount;
    }

    private function updateStatusCheck($itemId, $category, $status)
    {
        if ($category === 'ingreso_ms') ShipmentItem::where('id', $itemId)->update(['is_entered_ms' => $status]);
        if ($category === 'ingreso_gpo') ShipmentItem::where('id', $itemId)->update(['is_entered_group' => $status]);
    }

    public function openDocuments($itemId = null)
    {
        $this->resetErrorBag();
        $this->newFile = null;
        $this->activeItem = $itemId ? ShipmentItem::find($itemId) : null;
        $this->loadDocuments();
        $this->showModal = true;
    }

    public function loadDocuments()
    {
        $query = $this->activeItem 
            ? $this->activeItem->documents() 
            : $this->shipment->documents();
            
        $this->existingDocuments = $query->orderByDesc('created_at')->get();
    }

    public function saveDocument()
    {
        $this->validate(['newFile' => 'required|file|max:51200']);

        $category = is_null($this->activeItem) ? 'GLOBAL' : 'MANUAL';
        $originalName = $this->newFile->getClientOriginalName();
        $directory = $this->getStoragePath($category);
        
        $safeName = pathinfo($originalName, PATHINFO_FILENAME) . '_' . time() . '.' . $this->newFile->getClientOriginalExtension();
        $path = $this->newFile->storeAs($directory, $safeName, 'public');

        Document::create([
            'shipment_id' => $this->shipment->id,
            'shipment_item_id' => $this->activeItem?->id,
            'type' => is_null($this->activeItem) ? 'GENERAL_VIAJE' : 'EVIDENCIA',
            'category' => $category,
            'generated_filename' => $originalName,
            'file_path' => $path,
        ]);

        $this->newFile = null;
        $this->loadDocuments();
        $this->loadItems();
    }

    public function deleteDocument($documentId)
    {
        $doc = Document::find($documentId);
        if ($doc) {
            if ($doc->item && $doc->item->is_closed_accounting) {
                $this->js("alert('⛔ ACCIÓN DENEGADA:\\n\\nEsta partida está CERRADA.')");
                return;
            }

            if (Storage::disk('public')->exists($doc->file_path)) {
                $usageCount = Document::where('file_path', $doc->file_path)->count();
                if ($usageCount <= 1) {
                    Storage::disk('public')->delete($doc->file_path);
                }
            }
            
            if ($doc->category === 'ingreso_ms_global') {
                $this->shipment->items()->update(['is_entered_ms' => false]);
            }

            if ($doc->shipment_item_id) {
                $remainingDocs = Document::where('shipment_item_id', $doc->shipment_item_id)
                    ->where('category', $doc->category)
                    ->where('id', '!=', $doc->id)
                    ->count();
                
                if ($remainingDocs === 0) {
                    if ($doc->category === 'ingreso_ms') ShipmentItem::where('id', $doc->shipment_item_id)->update(['is_entered_ms' => false]);
                    if ($doc->category === 'ingreso_gpo') ShipmentItem::where('id', $doc->shipment_item_id)->update(['is_entered_group' => false]);
                }
            }

            $doc->delete();
            $this->loadDocuments();
            $this->loadItems();
        }
    }

    public function updateItem($itemId, $field, $value)
    {
        ShipmentItem::find($itemId)?->update([$field => $value]);
        $this->loadItems();
    }

    public function toggleCheck($itemId, $field)
    {
        $item = ShipmentItem::find($itemId);
        if ($item) {
            $item->update([$field => !$item->$field]);
            $this->loadItems();
        }
    }

    public function render()
    {
        return view('livewire.shipment-details')->layout('layouts.app');
    }
}