<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_item_id')->nullable()->constrained()->cascadeOnDelete();
            
            $table->enum('type', [
                'GENERAL_VIAJE', 
                'RECEPCION_MS', 
                'FACTURA_SALIDA', 
                'RECEPCION_GPO', 
                'PIEDRA_CUBICA'
            ]);
            
            $table->string('generated_filename');
            $table->string('file_path');
            $table->json('metadata')->nullable(); // Folios guardados
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};