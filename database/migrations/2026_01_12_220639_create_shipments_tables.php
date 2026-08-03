<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cabecera (Viaje)
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('system'); // MARA / MARINA
            $table->string('trip_number')->index();
            $table->string('pedimento')->index();
            $table->decimal('exchange_rate', 10, 4)->nullable();
            $table->enum('status', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['trip_number', 'pedimento']);
        });

        // Detalle (Filas)
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            
            // Informativos (Copy-Paste)
            $table->string('dr_raw')->nullable()->index();
            $table->string('supplier_raw')->nullable();
            $table->string('oc_group_raw')->nullable()->index();
            $table->text('description_raw')->nullable();
            $table->string('cotizacion_raw')->nullable();
            $table->string('oci_ms_raw')->nullable()->index();
            $table->string('anticipo_raw')->nullable();
            $table->string('invoice_provider_raw')->nullable();
            $table->string('value_raw')->nullable();
            $table->string('freight_raw')->nullable();
            $table->string('other_costs_raw')->nullable();
            $table->string('total_raw')->nullable();
            $table->string('system_origin')->nullable();
            
            // Link Inteligente
            $table->foreignId('linked_ms_order_id')->nullable()->constrained('ms_orders');

            // Semáforo (Controles)
            $table->string('ms_reception_status')->nullable(); // Texto libre
            $table->string('group_reception_status')->nullable(); // Texto libre
            
            $table->boolean('is_entered_ms')->default(false);
            $table->date('ms_entry_date')->nullable();
            
            $table->string('invoice_out_folio')->nullable();
            
            $table->boolean('is_entered_group')->default(false);
            $table->date('group_entry_date')->nullable();
            
            $table->boolean('is_closed_accounting')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};