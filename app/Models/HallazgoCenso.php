<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HallazgoCenso extends Model
{
    protected $table = 'hallazgos_censo';

    protected $fillable = [
        'product_id', // <-- Cambiado de producto_id a product_id para Nexus
        'user_id', 
        'cantidad',
        'seccion', 
        'mueble_tipo', 
        'mueble_numero', 
        'entrepano'
    ];

    // Para obtener la ubicación formateada rápido
    public function getUbicacionCompletaAttribute()
    {
        return "{$this->seccion}-{$this->mueble_tipo} {$this->mueble_numero}-{$this->entrepano}";
    }

    public function product() // <-- Cambiado a product
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}