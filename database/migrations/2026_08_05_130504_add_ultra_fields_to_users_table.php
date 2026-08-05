<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agregamos los campos de UltraWeb. 
            // username va nullable temporalmente para no chocar con los usuarios que ya tienes en Nexus
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('pin')->nullable()->after('password');
            $table->enum('role', ['master', 'par'])->default('par')->after('pin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'pin', 'role']);
        });
    }
};