<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\ProductCatalogImportService;
use App\Services\ProductMatcher;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Importador universal de catálogo por Excel. Sirve para dos casos de uso
 * (misma UI, mismo botón): una carga manual ad-hoc, o la sincronización
 * periódica contra el export de Microsip — en ambos casos el archivo trae
 * las columnas Clave/Nombre/U.compra/Almacenable y solo se crean los
 * productos que todavía no existen en el catálogo.
 */
class ImportProducts extends Component
{
    use WithFileUploads;

    public $file;

    public bool $showPreview = false;

    public array $nuevos = [];

    public int $existentesCount = 0;

    public array $sinClave = [];

    public array $duplicadosInternos = [];

    public int $excluidosServicio = 0;

    public int $excluidosNoAlmacenable = 0;

    public ?string $resultado = null;

    public function mount()
    {
        abort_unless(auth()->user()->puedeGestionarCatalogo(), 403);
    }

    public function previsualizar(ProductCatalogImportService $service)
    {
        $this->resultado = null;
        $this->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $data = $service->read($this->file->getRealPath());

        $this->sinClave = $data['sin_clave'];
        $this->duplicadosInternos = $data['duplicados_internos'];
        $this->excluidosServicio = $data['excluidos_servicio'];
        $this->excluidosNoAlmacenable = $data['excluidos_no_almacenable'];

        $nuevos = [];
        $existentes = 0;

        foreach ($data['rows'] as $row) {
            if (ProductMatcher::findExisting($row['sku'])) {
                $existentes++;
            } else {
                $nuevos[] = $row;
            }
        }

        $this->nuevos = $nuevos;
        $this->existentesCount = $existentes;
        $this->showPreview = true;
    }

    public function quitarNuevo(int $index)
    {
        unset($this->nuevos[$index]);
        $this->nuevos = array_values($this->nuevos);
    }

    public function confirmarImportacion()
    {
        abort_unless(auth()->user()->puedeGestionarCatalogo(), 403);

        $creados = 0;

        DB::transaction(function () use (&$creados) {
            foreach ($this->nuevos as $row) {
                // Re-chequeo por si otra importación corrió mientras se revisaba el preview.
                if (ProductMatcher::findExisting($row['sku'])) {
                    continue;
                }

                Product::create([
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'codigo_barras' => null,
                    'sin_codigo_fisico' => true,
                ]);
                $creados++;
            }
        });

        $this->resultado = "Se crearon {$creados} productos nuevos en el catálogo.";
        $this->showPreview = false;
        $this->nuevos = [];
        $this->existentesCount = 0;
        $this->file = null;
    }

    public function render()
    {
        return view('livewire.import-products')->layout('layouts.app');
    }
}
