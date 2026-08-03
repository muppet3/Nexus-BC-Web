<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ms_order_id',
        'product_id',
        'raw_sku',
        'raw_description',
        'quantity_ordered',
        'unit_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}