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
        Schema::table('shipment_items', function (Blueprint $table) {
            // Agregamos los campos de texto para los folios
            $table->string('entry_ms_folio')->nullable()->after('is_entered_ms');
            $table->string('entry_gpo_folio')->nullable()->after('is_entered_group');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            //
        });
    }
};
