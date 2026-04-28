<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [
            'Artes Gráficas',
            'Artes y Artesanías',
            'Edificación y Obra Civil',
            'Hostelería y Turismo',
            'Instalación y Mantenimiento',
            'Marítimo-Pesquera',
            'Química',
            'Textil, Confección y Piel',
            'Vidrio y Cerámica',
            'Actividades Físicas y Deportivas',
            'Aeronáutica',
            'Biotecnología',
            'Logística y Almacén',
        ];

        foreach ($missing as $nombre) {
            DB::table('familias')->insertOrIgnore([
                'nombre'     => $nombre,
                'imagen_url' => null,
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('familias')->whereIn('nombre', [
            'Artes Gráficas',
            'Artes y Artesanías',
            'Edificación y Obra Civil',
            'Hostelería y Turismo',
            'Instalación y Mantenimiento',
            'Marítimo-Pesquera',
            'Química',
            'Textil, Confección y Piel',
            'Vidrio y Cerámica',
            'Actividades Físicas y Deportivas',
            'Aeronáutica',
            'Biotecnología',
            'Logística y Almacén',
        ])->delete();
    }
};
