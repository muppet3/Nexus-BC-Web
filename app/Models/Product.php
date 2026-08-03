<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'unit',
        'current_location',
        'company',
    ];

    // Relación: Un producto tiene un historial de ubicaciones
    public function locationHistory()
    {
        return $this->hasMany(ProductLocationHistory::class)->orderByDesc('changed_at');
    }
}