<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialAuditoria extends Model
{
    protected $table = 'historial_auditorias';

    protected $fillable = [
        'product_id', // <-- Cambiado a product_id
        'user_id', 
        'supervisor_id',
        'accion', 
        'detalle_anterior', 
        'detalle_nuevo'
    ];

    public function product() // <-- Cambiado a product
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user() // Quien ejecutó
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supervisor() // El par que autorizó
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}