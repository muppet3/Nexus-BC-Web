<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CreateShipment;
use App\Livewire\GlobalSearch;
use App\Livewire\ShipmentIndex; 

// Redirección inteligente: Si entras a la raíz, te manda al dashboard.
// Si no estás logueado, Laravel te mandará solito al Login.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Rutas protegidas
Route::middleware(['auth'])->group(function () {
    Route::get('/shipments/create', CreateShipment::class)->name('shipments.create');
});

Route::get('/shipments/{shipment}', \App\Livewire\ShipmentDetails::class)
    ->middleware(['auth'])
    ->name('shipments.show');

Route::get('/biblioteca', GlobalSearch::class)->name('global-search'); 

Route::get('/dashboard', \App\Livewire\ShipmentIndex::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';