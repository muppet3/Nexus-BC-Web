<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentNote extends Model
{
    use HasFactory;

    protected $fillable = ['shipment_id', 'note', 'user_id'];

    // Una nota pertenece a un Viaje
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    // Una nota pertenece a un Usuario (el autor)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}