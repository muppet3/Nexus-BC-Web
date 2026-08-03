<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Rompemos el candado usando el nombre exacto que nos dio el error de hace rato
            $table->dropUnique('shipments_trip_number_pedimento_unique');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Por si algún día queremos volver a poner el candado
            $table->unique(['trip_number', 'pedimento']);
        });
    }
};