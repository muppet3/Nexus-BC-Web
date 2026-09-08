<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_auditorias', function (Blueprint $table) {
            // Liga cada renglón de auditoría con el hallazgo que originó (nullable: si el
            // hallazgo se borra después, o para registros viejos anteriores a esta columna,
            // se queda NULL en vez de perder el renglón de auditoría).
            $table->foreignId('hallazgo_id')->nullable()->after('product_id')
                ->constrained('hallazgos_censo')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('historial_auditorias', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hallazgo_id');
        });
    }
};
