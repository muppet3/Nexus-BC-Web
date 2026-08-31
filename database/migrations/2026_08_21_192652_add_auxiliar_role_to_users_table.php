<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('master', 'par', 'auxiliar') NOT NULL DEFAULT 'par'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'par' WHERE role = 'auxiliar'");
        DB::statement("ALTER TABLE users MODIFY role ENUM('master', 'par') NOT NULL DEFAULT 'par'");
    }
};
