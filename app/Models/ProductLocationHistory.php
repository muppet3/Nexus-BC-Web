<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLocationHistory extends Model
{
    use HasFactory;

    // Indicamos que no usa 'created_at'/'updated_at' estándar, sino 'changed_at'
    public $timestamps = false; 

    protected $table = 'product_location_history';

    protected $fillable = [
        'product_id',
        'previous_location',
        'new_location',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}