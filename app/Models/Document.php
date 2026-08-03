<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Opcional: Para que sepa quién es su dueño
    public function item()
    {
        return $this->belongsTo(ShipmentItem::class, 'shipment_item_id');
    }
}