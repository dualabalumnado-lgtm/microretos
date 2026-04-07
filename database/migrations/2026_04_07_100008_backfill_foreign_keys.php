<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: rellena las columnas FK nuevas (empresa_id, ciclo_id, familia_id, centro_id)
 * a partir de los datos de texto que ya existen en la BD.
 * Las columnas de texto legacy se mantienen intactas.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // 1. Poblar tabla centro_educativo con los centros únicos
        //    que existen en empresas.centro_educativo
        // ---------------------------------------------------------------
        $centros = DB::table('empresas')
            ->whereNotNull('centro_educativo')
            ->where('centro_educativo', '!=', '')
            ->distinct()
            ->pluck('centro_educativo');

        foreach ($centros as $nombre) {
            DB::table('centro_educativo')->insertOrIgnore([
                'nombre'     => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ---------------------------------------------------------------
        // 2. Rellenar empresas.centro_id
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE empresas e
            JOIN centro_educativo ce ON ce.nombre = e.centro_educativo
            SET e.centro_id = ce.id
            WHERE e.centro_educativo IS NOT NULL
              AND e.centro_educativo != \'\'
        ');

        // ---------------------------------------------------------------
        // 3. Rellenar empresa_familia.familia_id
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE empresa_familia ef
            JOIN familias f ON f.nombre = ef.familia
            SET ef.familia_id = f.id
            WHERE ef.familia IS NOT NULL
              AND ef.familia != \'\'
        ');

        // ---------------------------------------------------------------
        // 4. Rellenar centro_ciclo.centro_id
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE centro_ciclo cc
            JOIN centro_educativo ce ON ce.nombre = cc.centro_educativo
            SET cc.centro_id = ce.id
            WHERE cc.centro_educativo IS NOT NULL
              AND cc.centro_educativo != \'\'
        ');

        // ---------------------------------------------------------------
        // 5. Rellenar microretos.empresa_id
        //    Los 12 huérfanos (sin empresa en la tabla) quedan NULL
        // ---------------------------------------------------------------
        DB::statement('
            UPDATE microretos m
            JOIN empresas e ON e.nombre_comercial = m.empresa_nombre
            SET m.empresa_id = e.id
            WHERE m.empresa_nombre IS NOT NULL
              AND m.empresa_nombre != \'\'
        ');

        // ---------------------------------------------------------------
        // 6. Rellenar microretos.ciclo_id
        // ---------------------------------------------------------------
        // ciclos_formativos usa utf8mb4_0900_ai_ci, microretos usa utf8mb4_unicode_ci
        // Forzamos collation en el JOIN para evitar error 1267
        DB::statement('
            UPDATE microretos m
            JOIN ciclos_formativos cf ON cf.nombre COLLATE utf8mb4_unicode_ci = m.ciclo
            SET m.ciclo_id = cf.id
            WHERE m.ciclo IS NOT NULL
              AND m.ciclo != \'\'
        ');
    }

    public function down(): void
    {
        // Revertir: vaciar las columnas FK (los datos legacy siguen intactos)
        DB::statement('UPDATE empresas SET centro_id = NULL');
        DB::statement('UPDATE empresa_familia SET familia_id = NULL');
        DB::statement('UPDATE centro_ciclo SET centro_id = NULL');
        DB::statement('UPDATE microretos SET empresa_id = NULL, ciclo_id = NULL');
        DB::table('centro_educativo')->truncate();
    }
};
