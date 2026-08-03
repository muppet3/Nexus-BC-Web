<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo Maestro
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique()->index(); // SKU Limpio
            $table->string('name'); // Descripción principal
            $table->string('unit')->default('pza');
            $table->string('current_location')->nullable()->index(); 
            $table->string('company')->default('Mara Marlin'); 
            $table->timestamps();
        });

        // Historial de Ubicaciones
        Schema::create('product_location_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('previous_location')->nullable();
            $table->string('new_location');
            $table->string('changed_by')->nullable();
            $table->timestamp('changed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_location_history');
        Schema::dropIfExists('products');
    }
};