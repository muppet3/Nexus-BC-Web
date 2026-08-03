<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    use HasFactory;

    // Al dejar "guarded" vacío y eliminar el "fillable" restrictivo que tenías,
    // Laravel ahora permite guardar TODAS las columnas que vengan del Excel.
    protected $guarded = [];
    
    // Esta es la conexión con el Viaje padre
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Un Item tiene muchos documentos
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}