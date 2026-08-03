<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Fernando Morales',
            'email' => 'fmorales@aquaworld.com.mx', // O tu correo real
            'password' => bcrypt('14725fer'), // Cambia esto después
        ]);
        \App\Models\User::create([
            'name' => 'Silvana Arias',
            'email' => 'almacen.01@ultracarga.com', // O tu correo real
            'password' => bcrypt('Murc13l@g0'), // Cambia esto después
        ]);
    }
}
