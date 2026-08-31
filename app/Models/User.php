<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- Agregado de Ultramar para la App

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable; // <-- Fusionado

    protected $fillable = [
        'name',
        'email',
        'password',
        'username', // <-- De Ultramar
        'pin',      // <-- De Ultramar
        'role',     // <-- De Ultramar
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin', // Ocultamos el PIN por seguridad en respuestas JSON
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relación: Un usuario hace muchos hallazgos
    public function hallazgos()
    {
        return $this->hasMany(HallazgoCenso::class);
    }

    // master y auxiliar pueden crear OC y dar de alta productos; par no.
    public function puedeGestionarCatalogo(): bool
    {
        return in_array($this->role, ['master', 'auxiliar'], true);
    }
}