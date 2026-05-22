<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Añadimos la columna uuid como nullable primero para poder hacer backfill
        Schema::table('microretos', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Backfill: generamos un UUID único para cada registro existente
        DB::table('microretos')->orderBy('id')->each(function ($reto) {
            DB::table('microretos')
                ->where('id', $reto->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        // Una vez backfilleado, marcamos la columna como NOT NULL
        Schema::table('microretos', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('microretos', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
