<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_name',
        'order_date',
        'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(MsOrderItem::class);
    }
}