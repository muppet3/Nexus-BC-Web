<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Expandimos los campos fillable con la nueva base de datos unificada
    protected $fillable = [
        'sku', 'name', 'unit', 'company', 
        'grupo', 'linea', 'marca', 
        'codigo_barras', 'sin_codigo_fisico',
        'stock_teorico', 'stock_real',
        'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano'
    ];

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