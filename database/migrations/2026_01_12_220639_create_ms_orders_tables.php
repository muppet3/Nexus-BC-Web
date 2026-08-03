<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ms_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // El número clave (67, 176)
            $table->string('supplier_name')->nullable();
            $table->date('order_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ms_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ms_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained(); // Link al catálogo
            $table->string('raw_sku')->nullable();
            $table->string('raw_description')->nullable();
            $table->integer('quantity_ordered')->default(0);
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ms_order_items');
        Schema::dropIfExists('ms_orders');
    }
};