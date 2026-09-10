<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product; 
use App\Models\HallazgoCenso;
use App\Models\HistorialAuditoria;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; 

class ApiCensoController extends Controller
{
    // --------------------------------------------------------
    // 1. LOGIN DE LA APP
    // --------------------------------------------------------
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'pin' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->pin, $user->pin)) {
            return response()->json(['success' => false, 'message' => 'Usuario o PIN incorrectos'], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken('ultra_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role
            ]
        ]);
    }

    // --------------------------------------------------------
    // 2. BUSCADOR INTELIGENTE
    // --------------------------------------------------------
    public function buscar(Request $request)
    {
        $q = $request->get('q');
        
        if (strlen($q) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $productos = Product::where('codigo_barras', $q)
            ->orWhere('sku', 'like', "%$q%")
            ->orWhere('name', 'like', "%$q%")
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos
        ]);
    }

    // --------------------------------------------------------
    // 3. GUARDAR CENSO Y DOBLE VALIDACIÓN
    // --------------------------------------------------------
    public function guardar(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:products,id',
            'cantidad' => 'required|numeric|min:1',
            'seccion' => 'required',
            'mueble_tipo' => 'required',
            'mueble_numero' => 'required',
            'entrepano' => 'required',
            'marca' => 'required|string',
            'codigo_barras' => 'required|string',
        ]);

        $user = $request->user();
        $producto = Product::find($request->producto_id);
        
        $nuevaUbicacion = "{$request->seccion}-{$request->mueble_tipo} {$request->mueble_numero}-{$request->entrepano}";
        
        $ubicacionActual = null;
        if ($producto->seccion) {
            $ubicacionActual = "{$producto->seccion}-{$producto->mueble_tipo} {$producto->mueble_numero}-{$producto->entrepano}";
        }
        
        $requiereAutorizacion = false;
        $mensajeAutorizacion = "";
        $supervisorId = null;

        if ($ubicacionActual && $ubicacionActual !== $nuevaUbicacion) {
            if ($user->role === 'par') {
                $requiereAutorizacion = true;
                $mensajeAutorizacion = "Estás cambiando una ubicación ya establecida. Un compañero debe autorizar.";
            }
        }

        // 🔒 Solo master puede editar el hallazgo de otro usuario. Antes, si no era tuyo,
        // la búsqueda regresaba null y el código de abajo creaba un hallazgo NUEVO duplicado
        // en silencio en vez de avisar que no tenías permiso — ya no.
        $hallazgoPrevio = null;
        if ($request->filled('hallazgo_id')) {
            $query = HallazgoCenso::where('id', $request->hallazgo_id);
            if ($user->role !== 'master') {
                $query->where('user_id', $user->id);
            }
            $hallazgoPrevio = $query->first();

            if (!$hallazgoPrevio) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso para editar este registro.'], 403);
            }
        }

        if ($hallazgoPrevio && (int)$hallazgoPrevio->cantidad !== (int)$request->cantidad) {
            if ($user->role === 'par') {
                $requiereAutorizacion = true;
                $mensajeAutorizacion = "Estás modificando una cantidad ya registrada. Un compañero debe autorizar.";
            }
        }

        if ($requiereAutorizacion) {
            if (!$request->supervisor_username || !$request->supervisor_pin) {
                return response()->json([
                    'success' => false, 
                    'needs_auth' => true, 
                    'message' => $mensajeAutorizacion
                ], 403);
            }

            $supervisor = User::where('username', $request->supervisor_username)->first();
            if (!$supervisor || !Hash::check($request->supervisor_pin, $supervisor->pin)) {
                return response()->json(['success' => false, 'message' => 'PIN de compañero incorrecto.'], 401);
            }
            if ($supervisor->id === $user->id) {
                return response()->json(['success' => false, 'message' => 'No puedes autorizarte a ti mismo.'], 403);
            }
            $supervisorId = $supervisor->id;
        }

        DB::beginTransaction();
        try {
            if ($hallazgoPrevio) {
                $producto->stock_real -= $hallazgoPrevio->cantidad;
                $hallazgoPrevio->update([
                    'cantidad' => $request->cantidad,
                    'seccion' => $request->seccion,
                    'mueble_tipo' => $request->mueble_tipo,
                    'mueble_numero' => $request->mueble_numero,
                    'entrepano' => $request->entrepano,
                ]);
                $accion = "Edición de hallazgo";
                $hallazgoId = $hallazgoPrevio->id;
            } else {
                $nuevoHallazgo = HallazgoCenso::create([
                    'product_id' => $producto->id,
                    'user_id' => $user->id,
                    'cantidad' => $request->cantidad,
                    'seccion' => $request->seccion,
                    'mueble_tipo' => $request->mueble_tipo,
                    'mueble_numero' => $request->mueble_numero,
                    'entrepano' => $request->entrepano,
                ]);
                $accion = "Nuevo hallazgo registrado";
                $hallazgoId = $nuevoHallazgo->id;
            }

            $nuevoStockTotal = $producto->stock_real + $request->cantidad;

            HistorialAuditoria::create([
                'product_id' => $producto->id,
                'hallazgo_id' => $hallazgoId,
                'user_id' => $user->id,
                'supervisor_id' => $supervisorId,
                'accion' => $accion,
                'detalle_anterior' => "Ubi: " . ($ubicacionActual ?? 'S/U') . " | Stock: " . ($producto->stock_real + ($hallazgoPrevio ? $hallazgoPrevio->cantidad : 0)),
                'detalle_nuevo' => "Ubi: {$nuevaUbicacion} | Stock final: {$nuevoStockTotal}",
            ]);

            $producto->update([
                'stock_real' => $nuevoStockTotal,
                'seccion' => $request->seccion,
                'mueble_tipo' => $request->mueble_tipo,
                'mueble_numero' => $request->mueble_numero,
                'entrepano' => $request->entrepano,
                'marca' => $request->marca,
                'codigo_barras' => $request->codigo_barras,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => '¡Inventario actualizado!', 'producto' => $producto]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // --------------------------------------------------------
    // 4. HISTORIAL DE UN PRODUCTO ESPECÍFICO
    // --------------------------------------------------------
    public function historialProducto(Request $request, $id)
    {
        $auditorias = HistorialAuditoria::with([
                'user:id,name',
                'supervisor:id,name',
                'hallazgo:id,user_id,cantidad,seccion,mueble_tipo,mueble_numero,entrepano',
            ])
            ->where('product_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Para renglones viejos (de antes de que existiera hallazgo_id) master puede seguir
        // editando/borrando: se resuelve la liga al vuelo, sin guardar nada, y solo si hay un
        // único hallazgo candidato (mismo producto+usuario) — si es ambiguo, se deja igual que
        // a cualquier otro usuario, sin ícono, en vez de arriesgarse a apuntar al equivocado.
        if ($request->user()->role === 'master') {
            $auditorias->whereNull('hallazgo_id')->each(function ($aud) {
                $candidatos = HallazgoCenso::where('product_id', $aud->product_id)
                    ->where('user_id', $aud->user_id)
                    ->get(['id', 'user_id', 'cantidad', 'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano']);

                if ($candidatos->count() === 1) {
                    $aud->setRelation('hallazgo', $candidatos->first());
                }
            });
        }

        $hallazgos = HallazgoCenso::with('user:id,name')
            ->where('product_id', $id) 
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'auditorias' => $auditorias,
            'hallazgos' => $hallazgos
        ]);
    }

    // --------------------------------------------------------
    // 5. MI HISTORIAL (Lo que el usuario ha hecho HOY)
    // --------------------------------------------------------
    public function miHistorial(Request $request)
    {
        $user = $request->user();
    
        // 🔥 CORRECCIÓN: Cambiamos 'producto' por 'product' para que coincida con tu modelo
        $hallazgos = HallazgoCenso::with('product:id,sku,name,unit,grupo,linea,marca,codigo_barras,stock_teorico,stock_real,seccion,mueble_tipo,mueble_numero,entrepano')
            ->where('user_id', $user->id)
            ->whereDate('created_at', date('Y-m-d'))
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $hallazgos
        ]);
    }

    // --------------------------------------------------------
    // 6. IMPRIMIR ZEBRA — 'barras' (descripción + código, 2 etiquetas),
    //    'texto' (solo descripción) o 'solo_codigo' (solo código de barras).
    // --------------------------------------------------------
    public function imprimirEtiqueta(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:products,id',
            'tipo' => 'nullable|in:barras,texto,solo_codigo',
            'cantidad' => 'nullable|integer|min:1|max:50',
        ]);

        $producto = Product::find($request->producto_id);
        $tipo = $request->tipo ?: 'barras';
        $cantidad = $request->cantidad ?: 1;

        // 🔥 CORRECCIÓN: Toma primero el código que le manda la App, si no hay, usa el de la BD
        $codigo = $request->codigo_impreso ?: ($producto->codigo_barras ?: $producto->sku);

        // La impresora Zebra no entiende UTF-8 multibyte (usa su propia tabla de un solo byte),
        // así que hay que bajar acentos y comillas/guiones "tipográficos" a su equivalente ASCII
        // antes de mandarlos, o salen como basura (ej. ” se imprime como "ÔÇØ").
        $descripcion = str_replace(
            ['Ñ', 'ñ', '”', '“', '’', '‘', '–', '—'],
            ['N', 'n', '"', '"', "'", "'", '-', '-'],
            strtoupper($producto->name)
        );
        $descripcion = substr($descripcion, 0, 48);
        $sku = strtoupper($producto->sku);
        $unidad = strtoupper($producto->unit);

        $zpl = '';

        // Etiqueta de texto: descripción, unidad y SKU, sin código de barras.
        if (in_array($tipo, ['barras', 'texto'], true)) {
            // Etiqueta de 2 1/4" x 1" @ 203 dpi -> 457 x 203 dots.
            $zpl .= "^XA\n";
            $zpl .= "~SD20\n";
            $zpl .= "^PW457\n";
            $zpl .= "^LL203\n";

            $zpl .= "^FO20,35^FB420,2,0,L^A0N,26,26^FD{$descripcion}^FS\n";
            $zpl .= "^FO20,110^A0N,26,26^FDUNIDAD: {$unidad}^FS\n";
            $zpl .= "^FO20,150^A0N,26,26^FDSKU: {$sku}^FS\n";
            $zpl .= "^PQ{$cantidad}\n";
            $zpl .= "^XZ\n";
        }

        // Etiqueta de código de barras.
        if (in_array($tipo, ['barras', 'solo_codigo'], true)) {
            // Lógica de ajuste para Code 128 sobre etiqueta de 2 1/4" (457 dots de ancho).
            // A grosor 2 caben ~15 caracteres antes de desbordar el ancho; los más largos
            // bajan a grosor 1 (barras más finas) y así entran hasta ~36 caracteres.
            $longitud = strlen($codigo);

            if ($longitud <= 15) {
                $grosor = 2;
                $posicionX = 40;
                $fuenteTexto = 30;
            } else {
                $grosor = 1;
                $posicionX = 20;
                $fuenteTexto = 22;
            }

            // Etiqueta de 2 1/4" x 1" @ 203 dpi -> 457 x 203 dots.
            $zpl .= "^XA\n";
            $zpl .= "~SD20\n";
            $zpl .= "^PW457\n";
            $zpl .= "^LL203\n";

            // Franja superior: el texto arranca en y=32, no pegado al borde.
            $zpl .= "^FO0,32^FB457,1,0,C^A0N,{$fuenteTexto},{$fuenteTexto}^FDSKU: {$codigo}^FS\n";
            $zpl .= "^FO{$posicionX},68^BY{$grosor}^BCN,95,Y,N,N^FD{$codigo}^FS\n";
            $zpl .= "^PQ{$cantidad}\n";
            $zpl .= "^XZ\n";
        }

        try {
            $nombreArchivo = 'etiqueta_' . time() . '.txt';
            $archivoTemporal = storage_path('app/' . $nombreArchivo);
            $archivoTemporal = str_replace('/', '\\', $archivoTemporal);
            
            file_put_contents($archivoTemporal, $zpl);

            $impresoraCompartida = "\\\\127.0.0.1\\ZEBRA";
            $impresion = copy($archivoTemporal, $impresoraCompartida);

            if(file_exists($archivoTemporal)){
                unlink($archivoTemporal);
            }

            if (!$impresion) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No se pudo copiar el archivo a la impresora compartida.'
                ], 500);
            }

            return response()->json([
                'success' => true, 
                'message' => 'Etiquetas enviadas a impresión correctamente.',
                'zpl_generado' => $zpl 
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al intentar imprimir: ' . $e->getMessage()
            ], 500);
        }
    }

    // --------------------------------------------------------
    // 7. BORRAR HALLAZGO (solo master) — corrige el stock y deja rastro en auditoría
    // --------------------------------------------------------
    public function borrarHallazgo(Request $request)
    {
        $request->validate([
            'hallazgo_id' => 'required|exists:hallazgos_censo,id',
        ]);

        $user = $request->user();
        if ($user->role !== 'master') {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para borrar registros.'], 403);
        }

        $hallazgo = HallazgoCenso::with('product')->find($request->hallazgo_id);
        if (!$hallazgo || !$hallazgo->product) {
            return response()->json(['success' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        DB::beginTransaction();
        try {
            $producto = $hallazgo->product;
            $stockAnterior = $producto->stock_real;
            $producto->stock_real = max(0, $producto->stock_real - $hallazgo->cantidad);
            $producto->save();

            HistorialAuditoria::create([
                'product_id' => $producto->id,
                'hallazgo_id' => null, // el hallazgo está a punto de borrarse, no tiene caso apuntar a un id que dejará de existir
                'user_id' => $user->id,
                'supervisor_id' => null,
                'accion' => 'Registro eliminado por administrador',
                'detalle_anterior' => "Cantidad: {$hallazgo->cantidad} | Stock: {$stockAnterior}",
                'detalle_nuevo' => "Stock final: {$producto->stock_real}",
            ]);

            $hallazgo->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Registro eliminado y stock corregido.',
                'producto' => $producto,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al borrar: ' . $e->getMessage()], 500);
        }
    }
}