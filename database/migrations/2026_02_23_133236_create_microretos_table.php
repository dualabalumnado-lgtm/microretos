<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Migración duplicada — convertida en no-op.
// La tabla microretos la crea 2026_02_23_133058_create_microretos_table.php
return new class extends Migration
{
    public function up(): void
    {
        // No-op: la tabla ya existe por la migración anterior
    }

    public function down(): void
    {
        Schema::dropIfExists('microretos');
    }
};
