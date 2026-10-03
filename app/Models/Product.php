<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Secciones de piso de la bodega — una sola lista para todas las pantallas web
    // (Inventario, Acomodar Mercancía). Debe coincidir con la de la app
    // (home_screen.dart y acomodar_mercancia_screen.dart); la primera es la default.
    public const SECCIONES = ['Mara', 'OP1', 'OP2', 'OP3', 'PATIO 1', 'PATIO 2', 'AZOTEA'];

    // Expandimos los campos fillable con la nueva base de datos unificada
    protected $fillable = [
        'sku', 'name', 'unit', 'company', 
        'grupo', 'linea', 'marca', 
        'codigo_barras', 'sin_codigo_fisico',
        'stock_teorico', 'stock_real',
        'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano'
    ];

    // Ubicación armada a partir de sección/mueble/entrepaño, igual criterio que HallazgoCenso.
    public function getUbicacionCompletaAttribute()
    {
        if (! $this->seccion) {
            return null;
        }

        return "{$this->seccion}-{$this->mueble_tipo} {$this->mueble_numero}-{$this->entrepano}";
    }

    /**
     * Buscador de piso (app y web): código de barras exacto, o SKU/nombre que contenga
     * el texto — ordenado por parecido, para que al buscar "X11" salga primero el X11
     * y no AX11, X110, etc.:
     *   0. SKU o código de barras exactamente igual
     *   1. SKU que empieza con el texto
     *   2. SKU que lo contiene
     *   3. solo el nombre lo contiene
     */
    public function scopeBuscarEnPiso(Builder $query, string $termino): Builder
    {
        // % y _ son comodines de LIKE: se escapan para que un SKU como "A_1" se busque tal cual.
        $like = addcslashes($termino, '%_\\');

        return $query
            ->where(fn (Builder $q) => $q
                ->where('codigo_barras', $termino)
                ->orWhere('sku', 'like', "%{$like}%")
                ->orWhere('name', 'like', "%{$like}%"))
            ->orderByRaw(
                'CASE WHEN sku = ? OR codigo_barras = ? THEN 0 WHEN sku LIKE ? THEN 1 WHEN sku LIKE ? THEN 2 ELSE 3 END',
                [$termino, $termino, "{$like}%", "%{$like}%"]
            )
            ->orderBy('sku');
    }

    // Relación original de Nexus (Historial de movimientos viejos)
    public function locationHistory()
    {
        return $this->hasMany(ProductLocationHistory::class)->orderByDesc('changed_at');
    }

    // Relaciones traídas de Ultramar
    public function hallazgos()
    {
        return $this->hasMany(HallazgoCenso::class);
    }

    public function auditorias()
    {
        return $this->hasMany(HistorialAuditoria::class)->orderBy('created_at', 'desc');
    }
}