<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialAuditoria extends Model
{
    protected $table = 'historial_auditorias';

    protected $fillable = [
        'product_id', // <-- Cambiado a product_id
        'hallazgo_id',
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

    // El hallazgo concreto que originó este renglón de auditoría (puede ser null en
    // registros viejos, o si el hallazgo ya se borró).
    public function hallazgo()
    {
        return $this->belongsTo(HallazgoCenso::class, 'hallazgo_id');
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