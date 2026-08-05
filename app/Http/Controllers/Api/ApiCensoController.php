<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product; // <-- Usamos Product de Nexus
use App\Models\HallazgoCenso;
use App\Models\HistorialAuditoria;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // <-- Para enviar a la impresora

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

        // ⚠️ CAMBIO CLAVE: Comparamos contra la nueva columna 'pin', no contra 'password'
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

        // Buscamos primero si coincide exacto con código de barras, luego por SKU o Nombre
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
        // 🔥 AHORA EXIGIMOS MARCA Y CÓDIGO DE BARRAS
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

        $hallazgoPrevio = null;
        if ($request->has('hallazgo_id') && $request->hallazgo_id) {
            $hallazgoPrevio = HallazgoCenso::where('id', $request->hallazgo_id)
                ->where('user_id', $user->id)
                ->first();
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
            } else {
                HallazgoCenso::create([
                    'product_id' => $producto->id,
                    'user_id' => $user->id,
                    'cantidad' => $request->cantidad,
                    'seccion' => $request->seccion,
                    'mueble_tipo' => $request->mueble_tipo,
                    'mueble_numero' => $request->mueble_numero,
                    'entrepano' => $request->entrepano,
                ]);
                $accion = "Nuevo hallazgo registrado";
            }

            $nuevoStockTotal = $producto->stock_real + $request->cantidad;
            
            HistorialAuditoria::create([
                'product_id' => $producto->id,
                'user_id' => $user->id,
                'supervisor_id' => $supervisorId,
                'accion' => $accion,
                'detalle_anterior' => "Ubi: " . ($ubicacionActual ?? 'S/U') . " | Stock: " . ($producto->stock_real + ($hallazgoPrevio ? $hallazgoPrevio->cantidad : 0)),
                'detalle_nuevo' => "Ubi: {$nuevaUbicacion} | Stock final: {$nuevoStockTotal}",
            ]);

            // 🔥 GUARDAMOS TODO, INCLUYENDO LA MARCA Y EL CÓDIGO ACTUALIZADOS
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
    public function historialProducto($id)
    {
        $auditorias = HistorialAuditoria::with(['user:id,name', 'supervisor:id,name'])
            ->where('product_id', $id) // <-- product_id
            ->orderBy('created_at', 'desc')
            ->get();

        $hallazgos = HallazgoCenso::with('user:id,name')
            ->where('product_id', $id) // <-- product_id
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
    
        // ⚠️ CAMBIO CLAVE: Extraemos los campos de Nexus (sku, name, unit) en lugar de clave, descripcion
        $hallazgos = HallazgoCenso::with('producto:id,sku,name,unit,grupo,linea,marca,codigo_barras,stock_teorico,stock_real,seccion,mueble_tipo,mueble_numero,entrepano')
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
    // 6. IMPRIMIR ZEBRA 2x1 HORIZONTAL (DOBLE ETIQUETA)
    // --------------------------------------------------------
    public function imprimirEtiqueta(Request $request)
{
    $request->validate([
        'producto_id' => 'required|exists:products,id'
    ]);

    $producto = Product::find($request->producto_id);
    
    // 1. Preparamos los datos
    $codigo = $producto->codigo_barras ?: $producto->sku;
    $descripcion = substr(str_replace(['Ñ','ñ'], ['N','n'], strtoupper($producto->name)), 0, 48);
    $sku = strtoupper($producto->sku);
    $unidad = strtoupper($producto->unit);

    // 2. Lógica de ajuste para Code 128
    $longitud = strlen($codigo);

    // El Code 128 es más comprimido, casi siempre entra con grosor 2
    if ($longitud <= 14) {
        $grosor = 2;      
        $posicionX = 35; // Margen para centrarlo bonito
    } else {
        $grosor = 1;      
        $posicionX = 15; // Pegado a la izquierda para SKUs gigantes
    }

    // 3. CONSTRUCCIÓN DEL ZPL (Para etiqueta 2x1 pulgadas - 406x203 puntos)
    $zpl = "^XA\n";
    $zpl .= "~SD30\n"; 
    $zpl .= "^PW406\n"; // Forzar ancho de 2 pulgadas (203 dpi * 2)
    $zpl .= "^LL203\n"; // Forzar alto de 1 pulgada (203 dpi * 1)

    // --- ETIQUETA 1: Descripción, Unidad y SKU ---
    // Bajamos el inicio (Y=40) y reducimos un poco la letra (24) para que no se coma el texto
    $zpl .= "^FO20,40^FB370,2,0,L^A0N,24,24^FD{$descripcion}^FS\n";
    $zpl .= "^FO20,105^A0N,24,24^FDUNIDAD: {$unidad}^FS\n";
    $zpl .= "^FO20,145^A0N,24,24^FDSKU: {$sku}^FS\n";
    $zpl .= "^XZ\n";

    // --- ETIQUETA 2: Texto centrado + Código 128 Horizontal ---
    $zpl .= "^XA\n";
    $zpl .= "~SD30\n";
    $zpl .= "^PW406\n"; 
    $zpl .= "^LL203\n"; 
    
    // Texto SKU centrado, bajamos a Y=30
    $zpl .= "^FO0,30^FB406,1,0,C^A0N,24,24^FDSKU: {$codigo}^FS\n";
    
    // ^BCN = Code 128 Normal (Horizontal). 70 es la altura de las barras.
    $zpl .= "^FO{$posicionX},70^BY{$grosor}^BCN,70,Y,N,N^FD{$codigo}^FS\n";
    $zpl .= "^XZ\n";

    try {
        $nombreArchivo = 'etiqueta_' . time() . '.txt';
        
        // 1. Obtenemos la ruta y arreglamos las diagonales para Windows
        $archivoTemporal = storage_path('app/' . $nombreArchivo);
        $archivoTemporal = str_replace('/', '\\', $archivoTemporal);
        
        // Creamos el archivo ZPL
        file_put_contents($archivoTemporal, $zpl);

        // 2. Usamos la función copy() nativa de PHP (Mucho más seguro que shell_exec en Laravel)
        $impresoraCompartida = "\\\\127.0.0.1\\ZEBRA";
        
        $impresion = copy($archivoTemporal, $impresoraCompartida);

        // 3. Limpiamos el archivo temporal
        if(file_exists($archivoTemporal)){
            unlink($archivoTemporal);
        }

        // Si la función copy falla, lanzamos un error claro
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
}