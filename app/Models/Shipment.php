<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $casts = [
        'arrival_date' => 'date', // <--- Agrega esto
    ];
    protected $fillable = [
        'system',
        'trip_number',
        'pedimento',
        'exchange_rate',
        'status',
        'notes',
        'arrival_date',
        
    ];

    // Un Viaje tiene muchas partidas (filas del Excel)
    public function items()
    {
        return $this->hasMany(ShipmentItem::class);
    }

    // Un Viaje puede tener documentos generales (Pedimento Global, Factura Flete)
    public function documents()
    {
        return $this->hasMany(Document::class)->whereNull('shipment_item_id');
    }
    // NUEVO: Calcular porcentaje de completado (0-100%)
    public function getProgressAttribute()
    {
        $totalItems = $this->items()->count();
        if ($totalItems === 0) return 0;

        // Calculamos avance basado en si tienen documentos
        $itemsWithDocs = $this->items()->has('documents')->count();
        
        return round(($itemsWithDocs / $totalItems) * 100);
    }
    // Relación: Un Viaje tiene muchas notas de bitácora
    public function notes()
    {
        return $this->hasMany(ShipmentNote::class)->orderByDesc('created_at');
    }
}