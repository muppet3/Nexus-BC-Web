<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipment_notes', function (Blueprint $table) {
            $table->id();
            
            // Conexión con el Viaje (Si se borra el viaje, se borran sus notas)
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
            
            // El contenido de la nota
            $table->text('note'); 
            
            // Quién escribió la nota (Opcional, pero muy útil para saber a quién reclamarle jaja)
            $table->foreignId('user_id')->nullable()->constrained();
            
            $table->timestamps(); // Crea created_at (fecha) y updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_notes');
    }
};
