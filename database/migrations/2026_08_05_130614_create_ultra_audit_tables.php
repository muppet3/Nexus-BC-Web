<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Hallazgos (El historial de lo que cuenta cada quien en el día)
        Schema::create('hallazgos_censo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('cantidad');
            $table->string('seccion');
            $table->string('mueble_tipo');
            $table->string('mueble_numero');
            $table->string('entrepano');
            $table->timestamps();
        });

        // Tabla de Historial de Auditorías (La caja negra)
        Schema::create('historial_auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Referencia al supervisor que puso el PIN
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->text('detalle_anterior');
            $table->text('detalle_nuevo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_auditorias');
        Schema::dropIfExists('hallazgos_censo');
    }
};