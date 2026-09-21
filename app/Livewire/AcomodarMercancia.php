<?php

namespace App\Livewire;

use App\Livewire\Concerns\BuscaProductosPorPiso;
use App\Models\HistorialAuditoria;
use App\Models\Product;
use App\Models\User;
use App\Services\BarcodeAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Herramienta de piso: reubicar mercancía sin contar — solo actualiza la
 * ubicación del producto (y de paso el código de barras, si hace falta).
 * A diferencia de Censo, NO crea un hallazgo ni toca stock_real: no es un
 * conteo, es acomodar mercancía de lugar.
 */
class AcomodarMercancia extends Component
{
    use BuscaProductosPorPiso;

    public string $search = '';

    public array $resultados = [];

    public ?Product $productoActivo = null;

    public array $secciones = ['Ref ', 'OP1', 'OP2', 'OP3', 'PATIO 1', 'PATIO 2', 'AZOTEA'];

    public string $seccionSeleccionada = 'Ref ';

    public string $muebleTipo = 'A';

    public string $anaquel = '';

    public string $entrepano = '';

    public string $codigoBarras = '';

    // Autorización (mismo candado que Censo, solo para 'par')
    public bool $showModalAuth = false;

    public string $authMotivo = '';

    public string $supervisorUsername = '';

    public string $supervisorPin = '';

    public function seleccionar($productId)
    {
        $producto = Product::find($productId);
        $this->productoActivo = $producto;
        // Mismo criterio que Inventario: si ya tiene código, se queda ese; si no,
        // se precarga con el SKU (muchos productos usan el SKU como código físico).
        $this->codigoBarras = $producto->codigo_barras ?: $producto->sku;

        if ($producto->seccion) {
            $this->seccionSeleccionada = $producto->seccion;
            $this->muebleTipo = $producto->mueble_tipo;
            $this->anaquel = $producto->mueble_numero;
            $this->entrepano = $producto->entrepano;
        } else {
            $this->seccionSeleccionada = 'Ref ';
            $this->muebleTipo = 'A';
            $this->anaquel = '';
            $this->entrepano = '';
        }

        $this->resultados = [];
        $this->search = '';
        $this->resetErrorBag();
        $this->dispatch('producto-seleccionado');
    }

    public function cambiarProducto()
    {
        $this->productoActivo = null;
        $this->anaquel = '';
        $this->entrepano = '';
        $this->codigoBarras = '';
        $this->resetErrorBag();
    }

    public function setMuebleTipo($tipo)
    {
        $this->muebleTipo = $tipo;
    }

    public function guardarUbicacion($conAutorizacion = false)
    {
        if (! $this->productoActivo) {
            return;
        }

        if (empty($this->anaquel) || empty($this->entrepano)) {
            session()->flash('error', 'Falta número de anaquel o entrepaño.');

            return;
        }

        $user = auth()->user();

        $ubicacionActual = null;
        if ($this->productoActivo->seccion) {
            $ubicacionActual = "{$this->productoActivo->seccion}-{$this->productoActivo->mueble_tipo} {$this->productoActivo->mueble_numero}-{$this->productoActivo->entrepano}";
        }
        $nuevaUbicacion = "{$this->seccionSeleccionada}-{$this->muebleTipo} {$this->anaquel}-E{$this->entrepano}";

        $requiereAutorizacion = $ubicacionActual && $ubicacionActual !== $nuevaUbicacion && $user->role === 'par';
        $supervisorId = null;

        if ($requiereAutorizacion && ! $conAutorizacion) {
            $this->authMotivo = 'Cambio de ubicación ya establecida.';
            $this->showModalAuth = true;

            return;
        }

        if ($conAutorizacion) {
            $supervisor = User::where('username', $this->supervisorUsername)->first();
            if (! $supervisor || ! Hash::check($this->supervisorPin, $supervisor->pin)) {
                session()->flash('auth_error', 'PIN incorrecto.');

                return;
            }
            if ($supervisor->id === $user->id) {
                session()->flash('auth_error', 'No puedes autorizarte a ti mismo.');

                return;
            }
            $supervisorId = $supervisor->id;
            $this->showModalAuth = false;
        }

        DB::beginTransaction();
        try {
            $this->productoActivo->update([
                'seccion' => $this->seccionSeleccionada,
                'mueble_tipo' => $this->muebleTipo,
                'mueble_numero' => $this->anaquel,
                'entrepano' => $this->entrepano,
            ]);

            HistorialAuditoria::create([
                'product_id' => $this->productoActivo->id,
                'hallazgo_id' => null, // no es un conteo, no hay hallazgo asociado
                'user_id' => $user->id,
                'supervisor_id' => $supervisorId,
                'accion' => 'Reubicación sin conteo',
                'detalle_anterior' => 'Ubi: ' . ($ubicacionActual ?? 'S/U'),
                'detalle_nuevo' => "Ubi: {$nuevaUbicacion}",
            ]);

            $codigoNuevo = trim($this->codigoBarras);
            if ($codigoNuevo !== '' && $codigoNuevo !== $this->productoActivo->codigo_barras) {
                $resultadoCodigo = app(BarcodeAssignmentService::class)->asignar($this->productoActivo, $codigoNuevo, $user);
                if (! $resultadoCodigo['success']) {
                    DB::rollBack();
                    $this->addError('codigoBarras', $resultadoCodigo['message']);

                    return;
                }
            }

            DB::commit();

            session()->flash('success', 'Ubicación actualizada correctamente.');
            $this->supervisorUsername = '';
            $this->supervisorPin = '';
            $this->cambiarProducto();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.acomodar-mercancia')->layout('layouts.app');
    }
}
