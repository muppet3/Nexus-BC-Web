<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product; // <-- Apunta a Product de Nexus
use App\Models\HallazgoCenso;
use App\Models\HistorialAuditoria;
use App\Models\User;
use App\Services\ProductMatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class CensoDashboard extends Component
{
    use WithPagination;

    public $search = '';
    public $filtroEstado = 'todos'; 

    // --- VARIABLES DEL PANEL LATERAL (SLIDE-OVER) ---
    public $showSlideOver = false;
    public $productoActivo = null;
    
    // Formulario
    public $cantidad = 1;
    public $seccionSeleccionada = 'Mara';
    public $muebleTipo = 'A';
    public $anaquel = '';
    public $entrepano = '';
    public $codigoBarras = '';
    public $hallazgoEditandoId = null;

    // Alertas y Modales
    public $showAlertCensado = false;
    public $showModalMovimiento = false;
    public $showModalConsolidacion = false;
    public $showModalAuth = false;
    
    // Modales de Historial
    public $showModalMiHistorial = false;
    public $miHistorialData = [];
    public $showModalHistorialProducto = false;
    public $historialProductoData = [];

    // Administración de registros (solo master): ver y borrar hallazgos de cualquier usuario.
    public $showModalAdminRegistros = false;
    public $adminRegistrosData = [];

    // Autorización
    public $authMotivo = '';
    public $supervisorUsername = '';
    public $supervisorPin = '';

    public $secciones = ['Mara', 'Ref ', 'OP1', 'OP2', 'OP3', 'PATIO 1', 'PATIO 2', 'AZOTEA'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // 🔥 1. ABRIR PANEL PARA REGISTRO NUEVO
    public function abrirPanel($id)
    {
        $this->resetForm();
        $this->productoActivo = Product::find($id);

        $this->hallazgoEditandoId = null;
        $this->cantidad = 1;
        $this->codigoBarras = $this->productoActivo->codigo_barras ?? '';

        // Leemos directamente las columnas que creamos en la migración de Nexus
        if ($this->productoActivo->seccion) {
            $this->seccionSeleccionada = $this->productoActivo->seccion;
            $this->muebleTipo = $this->productoActivo->mueble_tipo;
            $this->anaquel = $this->productoActivo->mueble_numero;
            $this->entrepano = $this->productoActivo->entrepano;
            
            $this->showAlertCensado = true;
        }

        $this->showSlideOver = true;
        $this->dispatch('open-modal', 'modal-censo');
    }

    // 🔥 2. MI HISTORIAL (Ver lo que he hecho hoy y Editar)
    public function abrirMiHistorial()
    {
        $user = auth()->user();
        if($user) {
            $this->miHistorialData = HallazgoCenso::with('product') // <-- product
                ->where('user_id', $user->id)
                ->whereDate('created_at', date('Y-m-d'))
                ->orderBy('updated_at', 'desc')
                ->get();
            $this->showModalMiHistorial = true;
            $this->dispatch('open-modal', 'modal-mi-historial');
        }
    }

    public function cerrarModalMiHistorial()
    {
        $this->showModalMiHistorial = false;
        $this->dispatch('close-modal', 'modal-mi-historial');
    }

    public function editarMiHistorial($id)
    {
        $this->showModalMiHistorial = false;
        $this->dispatch('close-modal', 'modal-mi-historial');
        $this->resetForm();

        $hallazgo = HallazgoCenso::with('product')->find($id); // <-- product
        if ($hallazgo) {
            $this->productoActivo = $hallazgo->product;
            $this->hallazgoEditandoId = $hallazgo->id;
            $this->cantidad = $hallazgo->cantidad;
            $this->codigoBarras = $this->productoActivo->codigo_barras ?? '';
            $this->seccionSeleccionada = $hallazgo->seccion;
            $this->muebleTipo = $hallazgo->mueble_tipo;
            $this->anaquel = $hallazgo->mueble_numero;
            $this->entrepano = $hallazgo->entrepano;
            $this->showSlideOver = true;
            $this->dispatch('open-modal', 'modal-censo');
        }
    }

    // 🔥 3. HISTORIAL DE AUDITORÍA DEL PRODUCTO (todos los registros, no solo los de hoy)
    public function abrirHistorialProducto()
    {
        if (! $this->productoActivo) {
            return;
        }

        $auditorias = HistorialAuditoria::with(['user:id,name', 'supervisor:id,name', 'hallazgo'])
            ->where('product_id', $this->productoActivo->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Mismo criterio que la API móvil: para renglones viejos sin hallazgo_id, se
        // resuelve al vuelo usando el hallazgo más reciente del mismo usuario para este
        // producto — así siempre hay algo que editar, aunque haya más de un candidato.
        $auditorias->whereNull('hallazgo_id')->each(function ($aud) {
            $masReciente = HallazgoCenso::where('product_id', $aud->product_id)
                ->where('user_id', $aud->user_id)
                ->orderByDesc('updated_at')
                ->first();

            if ($masReciente) {
                $aud->setRelation('hallazgo', $masReciente);
            }
        });

        $this->historialProductoData = $auditorias;
        $this->showModalHistorialProducto = true;
        $this->dispatch('open-modal', 'modal-historial-producto');
    }

    public function cerrarModalHistorialProducto()
    {
        $this->showModalHistorialProducto = false;
        $this->dispatch('close-modal', 'modal-historial-producto');
    }

    // Editar cualquier registro desde el historial del producto (no solo los de hoy/propios).
    public function editarDesdeHistorial($hallazgoId)
    {
        $hallazgo = HallazgoCenso::with('product')->find($hallazgoId);
        if (! $hallazgo) {
            session()->flash('error', 'Ese registro ya no existe (pudo haber sido borrado).');
            return;
        }

        $this->showModalHistorialProducto = false;
        $this->dispatch('close-modal', 'modal-historial-producto');

        $this->resetForm();
        $this->productoActivo = $hallazgo->product;
        $this->hallazgoEditandoId = $hallazgo->id;
        $this->cantidad = $hallazgo->cantidad;
        $this->seccionSeleccionada = $hallazgo->seccion;
        $this->muebleTipo = $hallazgo->mueble_tipo;
        $this->anaquel = $hallazgo->mueble_numero;
        $this->entrepano = $hallazgo->entrepano;
        $this->codigoBarras = $this->productoActivo->codigo_barras ?? '';
        $this->showSlideOver = true;
        $this->dispatch('open-modal', 'modal-censo');
    }

    // 🔥 4. ADMINISTRACIÓN DE REGISTROS (solo master): ver de todos los usuarios y borrar
    public function abrirAdminRegistros()
    {
        if (auth()->user()?->role !== 'master') {
            return;
        }

        $this->cargarAdminRegistros();
        $this->showModalAdminRegistros = true;
        $this->dispatch('open-modal', 'modal-admin-registros');
    }

    private function cargarAdminRegistros()
    {
        $this->adminRegistrosData = HallazgoCenso::with(['product', 'user'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function cerrarAdminRegistros()
    {
        $this->showModalAdminRegistros = false;
        $this->dispatch('close-modal', 'modal-admin-registros');
    }

    public function borrarHallazgo($id)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'master') {
            session()->flash('error', 'No tienes permiso para borrar registros.');
            return;
        }

        $hallazgo = HallazgoCenso::with('product')->find($id);
        if (!$hallazgo || !$hallazgo->product) {
            return;
        }

        DB::beginTransaction();
        try {
            $producto = $hallazgo->product;
            $stockAnterior = $producto->stock_real;
            $producto->stock_real = max(0, $producto->stock_real - $hallazgo->cantidad);
            $producto->save();

            HistorialAuditoria::create([
                'product_id' => $producto->id,
                'user_id' => $user->id,
                'supervisor_id' => null,
                'accion' => 'Registro eliminado por administrador',
                'detalle_anterior' => "Cantidad: {$hallazgo->cantidad} | Stock: {$stockAnterior}",
                'detalle_nuevo' => "Stock final: {$producto->stock_real}",
            ]);

            $hallazgo->delete();

            DB::commit();
            $this->cargarAdminRegistros();
            session()->flash('success', 'Registro eliminado y stock corregido.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al borrar: ' . $e->getMessage());
        }
    }

    public function cerrarPanel()
    {
        $this->showSlideOver = false;
        $this->dispatch('close-modal', 'modal-censo');
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->cantidad = 1;
        $this->seccionSeleccionada = 'Mara';
        $this->muebleTipo = 'A';
        $this->anaquel = '';
        $this->entrepano = '';
        $this->codigoBarras = '';
        $this->hallazgoEditandoId = null;
        $this->showAlertCensado = false;
        $this->showModalMovimiento = false;
        $this->showModalConsolidacion = false;
        $this->showModalAuth = false;
        $this->dispatch('close-modal', 'modal-movimiento');
        $this->dispatch('close-modal', 'modal-consolidacion');
        $this->dispatch('close-modal', 'modal-auth');
        $this->supervisorUsername = '';
        $this->supervisorPin = '';
    }

    public function setMuebleTipo($tipo)
    {
        $this->muebleTipo = $tipo;
    }

    public function cerrarAlertaCensado()
    {
        $this->showAlertCensado = false;
    }

    public function validarYGuardar()
    {
        if (empty($this->anaquel) || empty($this->entrepano)) {
            session()->flash('error', 'Falta número de anaquel o entrepaño.');
            return;
        }

        $codigo = ProductMatcher::normalizeCode($this->codigoBarras);
        if ($codigo !== null) {
            $duplicado = Product::where('codigo_barras', $codigo)
                ->where('id', '!=', $this->productoActivo->id)
                ->first();

            if ($duplicado) {
                session()->flash('error', "Ese código de barras ya está asignado a: {$duplicado->sku} — {$duplicado->name}");
                return;
            }
        }
        $this->codigoBarras = $codigo ?? '';

        $seccionActual = $this->productoActivo->seccion ?? '';

        if (!empty($seccionActual) && $seccionActual !== $this->seccionSeleccionada) {
            $this->showModalMovimiento = true;
            $this->dispatch('open-modal', 'modal-movimiento');
            return;
        }
        $this->continuarValidacionConsolidacion();
    }

    public function cerrarModalMovimiento()
    {
        $this->showModalMovimiento = false;
        $this->dispatch('close-modal', 'modal-movimiento');
    }

    public function confirmarMovimientoFisico()
    {
        $this->showModalMovimiento = false;
        $this->dispatch('close-modal', 'modal-movimiento');
        $this->continuarValidacionConsolidacion();
    }

    public function continuarValidacionConsolidacion()
    {
        $seccionActual = $this->productoActivo->seccion ?? '';

        // Este aviso es para cuando se registra un hallazgo NUEVO sobre una ubicación que
        // ya tiene stock (ahí sí se suma). Al editar un registro existente no aplica: el
        // guardado resta la cantidad anterior antes de sumar la nueva, así que es una
        // corrección, no una suma — mostrar el aviso aquí solo generaba confusión.
        if (!empty($seccionActual) && !$this->hallazgoEditandoId) {
            $this->showModalConsolidacion = true;
            $this->dispatch('open-modal', 'modal-consolidacion');
            return;
        }
        $this->ejecutarGuardado();
    }

    public function cerrarModalConsolidacion()
    {
        $this->showModalConsolidacion = false;
        $this->dispatch('close-modal', 'modal-consolidacion');
    }

    public function confirmarConsolidacion()
    {
        $this->showModalConsolidacion = false;
        $this->dispatch('close-modal', 'modal-consolidacion');
        $this->ejecutarGuardado();
    }

    public function cerrarModalAuth()
    {
        $this->showModalAuth = false;
        $this->dispatch('close-modal', 'modal-auth');
    }

    public function ejecutarGuardado($conAutorizacion = false)
    {
        $user = auth()->user();
        
        $ubicacionActual = null;
        if ($this->productoActivo->seccion) {
            $ubicacionActual = "{$this->productoActivo->seccion}-{$this->productoActivo->mueble_tipo} {$this->productoActivo->mueble_numero}-{$this->productoActivo->entrepano}";
        }
        $nuevaUbicacion = "{$this->seccionSeleccionada}-{$this->muebleTipo} {$this->anaquel}-E{$this->entrepano}";
        
        $requiereAutorizacion = false;
        $supervisorId = null;

        // Validar Cambio Fijo
        if ($ubicacionActual && $ubicacionActual !== $nuevaUbicacion) {
            if ($user->role === 'par') {
                $requiereAutorizacion = true;
                $this->authMotivo = "Cambio de ubicación ya establecida.";
            }
        }

        // Validar Edición — solo master puede editar el hallazgo de otro usuario.
        $hallazgoPrevio = null;
        if ($this->hallazgoEditandoId) {
            $query = HallazgoCenso::where('id', $this->hallazgoEditandoId);
            if ($user->role !== 'master') {
                $query->where('user_id', $user->id);
            }
            $hallazgoPrevio = $query->first();

            if (!$hallazgoPrevio) {
                session()->flash('error', 'No tienes permiso para editar este registro.');
                $this->cerrarPanel();
                return;
            }

            if ((int)$hallazgoPrevio->cantidad !== (int)$this->cantidad) {
                if ($user->role === 'par') {
                    $requiereAutorizacion = true;
                    $this->authMotivo = "Modificando una cantidad histórica.";
                }
            }
        }

        if ($requiereAutorizacion && !$conAutorizacion) {
            $this->showModalAuth = true;
            $this->dispatch('open-modal', 'modal-auth');
            return;
        }

        // ⚠️ CAMBIO CLAVE: Checamos contra el campo 'pin', no 'password'
        if ($conAutorizacion) {
            $supervisor = User::where('username', $this->supervisorUsername)->first();
            if (!$supervisor || !Hash::check($this->supervisorPin, $supervisor->pin)) {
                session()->flash('auth_error', 'PIN incorrecto.');
                return;
            }
            if ($supervisor->id === $user->id) {
                session()->flash('auth_error', 'No puedes autorizarte a ti mismo.');
                return;
            }
            $supervisorId = $supervisor->id;
            $this->showModalAuth = false;
            $this->dispatch('close-modal', 'modal-auth');
        }

        DB::beginTransaction();
        try {
            if ($hallazgoPrevio) {
                $this->productoActivo->stock_real -= $hallazgoPrevio->cantidad;
                $hallazgoPrevio->update([
                    'cantidad' => $this->cantidad,
                    'seccion' => $this->seccionSeleccionada,
                    'mueble_tipo' => $this->muebleTipo,
                    'mueble_numero' => $this->anaquel,
                    'entrepano' => $this->entrepano,
                ]);
                $accion = "Edición de hallazgo";
                $hallazgoId = $hallazgoPrevio->id;
            } else {
                $nuevoHallazgo = HallazgoCenso::create([
                    'product_id' => $this->productoActivo->id, // <-- product_id
                    'user_id' => $user->id,
                    'cantidad' => $this->cantidad,
                    'seccion' => $this->seccionSeleccionada,
                    'mueble_tipo' => $this->muebleTipo,
                    'mueble_numero' => $this->anaquel,
                    'entrepano' => $this->entrepano,
                ]);
                $accion = "Nuevo hallazgo registrado";
                $hallazgoId = $nuevoHallazgo->id;
            }

            $nuevoStockTotal = $this->productoActivo->stock_real + $this->cantidad;

            HistorialAuditoria::create([
                'product_id' => $this->productoActivo->id, // <-- product_id
                'hallazgo_id' => $hallazgoId,
                'user_id' => $user->id,
                'supervisor_id' => $supervisorId,
                'accion' => $accion,
                'detalle_anterior' => "Ubi: " . ($ubicacionActual ?? 'S/U') . " | Stock: " . ($this->productoActivo->stock_real + ($hallazgoPrevio ? $hallazgoPrevio->cantidad : 0)),
                'detalle_nuevo' => "Ubi: {$nuevaUbicacion} | Stock final: {$nuevoStockTotal}",
            ]);

            // Guardamos todo en la tabla Product de Nexus
            $this->productoActivo->update([
                'stock_real' => $nuevoStockTotal,
                'seccion' => $this->seccionSeleccionada,
                'mueble_tipo' => $this->muebleTipo,
                'mueble_numero' => $this->anaquel,
                'entrepano' => $this->entrepano,
                'codigo_barras' => $this->codigoBarras !== '' ? $this->codigoBarras : null,
                'sin_codigo_fisico' => $this->codigoBarras === '',
            ]);

            DB::commit();
            session()->flash('success', '¡Inventario actualizado con éxito!');
            $this->cerrarPanel();

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    // Misma lógica de búsqueda/filtro que usa la tabla en pantalla — la reutiliza
    // también el export a Excel, para que siempre exporte exactamente lo que ves.
    private function getProductosFiltradosQuery()
    {
        $query = Product::query();

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('codigo_barras', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filtroEstado === 'censados') {
            $query->where('stock_real', '>', 0)->orWhereNotNull('seccion');
        } elseif ($this->filtroEstado === 'pendientes') {
            $query->where('stock_real', 0)->whereNull('seccion');
        }

        return $query;
    }

    // 🔥 EXPORTAR CATÁLOGO A EXCEL (.xlsx real, con PhpSpreadsheet — no es un CSV disfrazado)
    public function exportarExcel()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Catálogo');

        $headers = ['ID', 'SKU', 'Descripción', 'Unidad de Medida', 'Código de Barras', 'Cantidad Inventariada', 'Marca'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

        // SKU y Código de Barras se fuerzan a texto explícito: si son puramente numéricos
        // (muy común en códigos de barras reales), Excel los reinterpreta como número y
        // puede mostrarlos en notación científica o comerse ceros a la izquierda — con
        // setCellValueExplicit(TYPE_STRING) eso no pasa, queda idéntico a como está en la BD.
        $row = 2;
        $this->getProductosFiltradosQuery()->orderBy('sku')->chunk(500, function ($productos) use ($sheet, &$row) {
            foreach ($productos as $p) {
                $sheet->setCellValue("A{$row}", $p->id);
                $sheet->setCellValueExplicit("B{$row}", (string) $p->sku, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", (string) $p->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", (string) $p->unit, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", (string) ($p->codigo_barras ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("F{$row}", $p->stock_real);
                $sheet->setCellValueExplicit("G{$row}", (string) ($p->marca ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $row++;
            }
        });

        // Refuerzo a nivel de columna (formato de celda "texto") por si algún visor de Excel
        // reevalúa el contenido al abrir el archivo.
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('E:E')->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'catalogo_nexus_' . date('Y-m-d_H-i') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function render()
    {
        $totalTeorico = Product::sum('stock_teorico'); // <-- Product
        $totalReal = Product::sum('stock_real');
        $diferencia = $totalReal - $totalTeorico;

        $productos = $this->getProductosFiltradosQuery()->paginate(15);

        return view('livewire.censo-dashboard', [
            'productos' => $productos,
            'totalTeorico' => $totalTeorico,
            'totalReal' => $totalReal,
            'diferencia' => $diferencia
        ])->layout('layouts.app');
    }
}