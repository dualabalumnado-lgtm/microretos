<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Completa la configuración de familias profesionales iniciada en
// 2026_04_28_000001. Hace tres cosas:
//   1. Borra el duplicado de "Energía y Agua" con encoding roto (id=9).
//   2. Inserta las 8 familias sin tildes que se añadieron vía SQL seeder
//      y que la migración anterior no incluyó.
//   3. Corrige la familia_id de dos ciclos mal clasificados.
// Todo es idempotente: safe para re-ejecutar o para BD ya parcialmente fix.
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Limpiar duplicado roto ────────────────────────────────────
        // Mueve referencias antes de borrar (empresa_familia tiene RESTRICT)
        DB::table('empresa_familia')
            ->where('familia_id', 9)
            ->update(['familia_id' => 16, 'familia' => 'Energía y Agua']);

        DB::table('ciclos_formativos')
            ->where('familia_id', 9)
            ->update(['familia_id' => 16, 'familia' => 'Energía y Agua']);

        DB::table('familias')->where('id', 9)->delete();

        // ── 2. Insertar familias sin tildes (las insertó el SQL seeder) ──
        $familiasSinTildes = [
            'Electricidad y Electrónica',
            'Imagen Personal',
            'Industrias Alimentarias',
            'Industrias Extractivas',
            'Sanidad',
            'Seguridad y Medio Ambiente',
            'Servicios Socioculturales y a la Comunidad',
            'Telecomunicaciones',
        ];

        foreach ($familiasSinTildes as $nombre) {
            DB::table('familias')->insertOrIgnore([
                'nombre'     => $nombre,
                'imagen_url' => null,
                'created_at' => now(),
                'updated_at' => null,
            ]);
        }

        // ── 3. Corregir ciclos con familia incorrecta ────────────────────
        // Ciclo 114: Electromecánica de Maquinaria → Electricidad y Electrónica
        $electricidad = DB::table('familias')
            ->where('nombre', 'Electricidad y Electrónica')
            ->value('id');

        if ($electricidad) {
            // Cubre dos casos: BD fresca (familia_id=8/TMV) y BD local donde
            // el SQL seeder con encoding malo dejó familia_id=NULL tras el DELETE.
            DB::table('ciclos_formativos')
                ->where('id', 114)
                ->where(fn ($q) => $q->where('familia_id', 8)->orWhereNull('familia_id'))
                ->update(['familia_id' => $electricidad, 'familia' => 'Electricidad y Electrónica']);
        }

        // Ciclo 115: Mantenimiento de embarcaciones de recreo → Marítimo-Pesquera
        $maritimo = DB::table('familias')
            ->where('nombre', 'Marítimo-Pesquera')
            ->value('id');

        if ($maritimo) {
            DB::table('ciclos_formativos')
                ->where('id', 115)
                ->where(fn ($q) => $q->where('familia_id', 8)->orWhereNull('familia_id'))
                ->update(['familia_id' => $maritimo, 'familia' => 'Marítimo-Pesquera']);
        }
    }

    public function down(): void
    {
        // Revierte solo lo reversible sin destruir datos de producción
        DB::table('familias')->whereIn('nombre', [
            'Electricidad y Electrónica',
            'Imagen Personal',
            'Industrias Alimentarias',
            'Industrias Extractivas',
            'Sanidad',
            'Seguridad y Medio Ambiente',
            'Servicios Socioculturales y a la Comunidad',
            'Telecomunicaciones',
        ])->delete();
    }
};
