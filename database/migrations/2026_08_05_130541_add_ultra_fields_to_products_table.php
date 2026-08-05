<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Clasificación
            $table->string('grupo')->nullable()->after('name');
            $table->string('linea')->nullable()->after('grupo');
            $table->string('marca')->nullable()->after('linea');
            
            // Código de Barras y Switch
            $table->string('codigo_barras')->unique()->nullable()->after('marca');
            $table->boolean('sin_codigo_fisico')->default(false)->after('codigo_barras');
            
            // Inventario Físico (Remplaza al viejo current_location)
            $table->integer('stock_teorico')->default(0)->after('sin_codigo_fisico');
            $table->integer('stock_real')->default(0)->after('stock_teorico');
            $table->string('seccion')->nullable()->after('stock_real');
            $table->string('mueble_tipo')->nullable()->after('seccion');
            $table->string('mueble_numero')->nullable()->after('mueble_tipo');
            $table->string('entrepano')->nullable()->after('mueble_numero');

            // Borramos la ubicación vieja de Nexus ya que ahora usaremos las 4 de arriba
            $table->dropColumn('current_location');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'grupo', 'linea', 'marca', 'codigo_barras', 'sin_codigo_fisico',
                'stock_teorico', 'stock_real', 'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano'
            ]);
            $table->string('current_location')->nullable();
        });
    }
};