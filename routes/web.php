<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\CreateShipment;
use App\Livewire\GlobalSearch;
use App\Livewire\ShipmentIndex; 
use App\Livewire\CensoDashboard; // <-- Importamos el nuevo dashboard de inventario

// Redirección inteligente: Si entras a la raíz, te manda al dashboard de embarques.
// Si no estás logueado, Laravel te mandará solito al Login de Volt.
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Rutas protegidas genéricas
Route::middleware(['auth'])->group(function () {
    Route::get('/shipments/create', CreateShipment::class)->name('shipments.create');
    Route::get('/shipments/{shipment}', \App\Livewire\ShipmentDetails::class)->name('shipments.show');
    Route::get('/biblioteca', GlobalSearch::class)->name('global-search'); 
    Route::view('profile', 'profile')->name('profile');
});

// Rutas de Dashboards (Verificadas)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard original de Nexus (Avisos de llegada)
    Route::get('/dashboard', ShipmentIndex::class)->name('dashboard');
    
    // NUEVO: Dashboard de Inventario Físico (El que era de UltraWeb)
    Route::get('/censo', CensoDashboard::class)->name('censo.dashboard');
    
});

require __DIR__.'/auth.php';