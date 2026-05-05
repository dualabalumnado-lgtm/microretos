<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Correcciones estructurales de la BD de familias y ciclos formativos.
// Tres bloques:
//   1. Ciclos mal asignados (incluyendo deshacer errores de migration _0002).
//   2. Eliminar pseudo-familias (subsectores) y reasignar sus ciclos.
//   3. Añadir ciclos representativos a familias que estaban vacías.
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. CORREGIR CICLOS MAL ASIGNADOS ────────────────────────────────

        $tmv    = DB::table('familias')->where('nombre', 'Transporte y Mantenimiento de Vehículos')->value('id');
        $madera = DB::table('familias')->where('nombre', 'Madera, Mueble y Corcho')->value('id');

        if ($tmv) {
            // Ciclo 114: migration _0002 lo movió erróneamente a Electricidad → vuelve a TMV
            DB::table('ciclos_formativos')->where('id', 114)->update([
                'familia_id'    => $tmv,
                'familia'       => 'Transporte y Mantenimiento de Vehículos',
                'siglasGrado'   => 'TMV',
                'referenciaBOE' => 'BOE-A-2011-6231',
            ]);

            // Ciclo 115: migration _0002 lo movió erróneamente a Marítimo-Pesquera → vuelve a TMV
            DB::table('ciclos_formativos')->where('id', 115)->update([
                'familia_id'    => $tmv,
                'familia'       => 'Transporte y Mantenimiento de Vehículos',
                'siglasGrado'   => 'TMV',
                'referenciaBOE' => 'BOE-A-2018-2999',
            ]);
        }

        if ($madera) {
            // Ciclo 116: estaba en TMV pero el BOE lo asigna a Madera, Mueble y Corcho
            DB::table('ciclos_formativos')->where('id', 116)->update([
                'familia_id'    => $madera,
                'familia'       => 'Madera, Mueble y Corcho',
                'siglasGrado'   => 'MAM',
                'referenciaBOE' => 'BOE-A-2018-2998',
            ]);
        }

        // Ciclos IFC que pertenecen a Administración y Gestión → prefijo correcto ADG
        DB::table('ciclos_formativos')
            ->where('familia', 'Administración y Gestión')
            ->where('nombre', 'like', 'IFC+21 %')
            ->get()
            ->each(function ($ciclo) {
                DB::table('ciclos_formativos')->where('id', $ciclo->id)->update([
                    'nombre'      => preg_replace('/^IFC\+21 /', 'ADG ', $ciclo->nombre),
                    'siglasGrado' => 'ADG',
                ]);
            });

        // ── 2. ELIMINAR PSEUDO-FAMILIAS (SUBSECTORES) ───────────────────────
        // Estos no son familias del MEC sino subsectores sin reconocimiento oficial.
        // Se reasignan sus ciclos y empresas a la familia real antes de borrar.

        $subsectores = [
            ['nombre' => 'Aeronáutica',         'destino' => 'Transporte y Mantenimiento de Vehículos'],
            ['nombre' => 'Logística y Almacén', 'destino' => 'Comercio y Marketing'],
            ['nombre' => 'Telecomunicaciones',  'destino' => 'Electricidad y Electrónica'],
            ['nombre' => 'Biotecnología',       'destino' => 'Química'],
        ];

        foreach ($subsectores as $item) {
            $origenId  = DB::table('familias')->where('nombre', $item['nombre'])->value('id');
            $destinoId = DB::table('familias')->where('nombre', $item['destino'])->value('id');

            if (!$origenId) {
                continue; // ya fue borrado o nunca existió
            }

            if ($destinoId) {
                DB::table('ciclos_formativos')
                    ->where('familia_id', $origenId)
                    ->update(['familia_id' => $destinoId, 'familia' => $item['destino']]);

                DB::table('empresa_familia')
                    ->where('familia_id', $origenId)
                    ->update(['familia_id' => $destinoId, 'familia' => $item['destino']]);
            }

            DB::table('familias')->where('id', $origenId)->delete();
        }

        // ── 3. AÑADIR CICLOS A FAMILIAS VACÍAS ──────────────────────────────
        // Solo inserta si la referenciaBOE no existe ya en la tabla.

        $ciclosNuevos = [
            // Sanidad
            [
                'familia_nombre' => 'Sanidad',
                'nombre'         => 'Cuidados Auxiliares de Enfermería',
                'grado'          => 'Formación Profesional de Grado Medio',
                'referenciaBOE'  => 'BOE-A-1995-12154',
            ],
            [
                'familia_nombre' => 'Sanidad',
                'nombre'         => 'Laboratorio Clínico y Biomédico',
                'grado'          => 'Formación Profesional de Grado Superior',
                'referenciaBOE'  => 'BOE-A-2014-9988',
            ],
            // Energía y Agua
            [
                'familia_nombre' => 'Energía y Agua',
                'nombre'         => 'Redes y Estaciones de Tratamiento de Aguas',
                'grado'          => 'Formación Profesional de Grado Medio',
                'referenciaBOE'  => 'BOE-A-2013-8067',
            ],
            [
                'familia_nombre' => 'Energía y Agua',
                'nombre'         => 'Energías Renovables',
                'grado'          => 'Formación Profesional de Grado Superior',
                'referenciaBOE'  => 'BOE-A-2013-2035',
            ],
            // Seguridad y Medio Ambiente
            [
                'familia_nombre' => 'Seguridad y Medio Ambiente',
                'nombre'         => 'Emergencias Sanitarias',
                'grado'          => 'Formación Profesional de Grado Medio',
                'referenciaBOE'  => 'BOE-A-2007-19154',
            ],
            [
                'familia_nombre' => 'Seguridad y Medio Ambiente',
                'nombre'         => 'Educación y Control Ambiental',
                'grado'          => 'Formación Profesional de Grado Superior',
                'referenciaBOE'  => 'BOE-A-2011-19603',
            ],
            // Imagen Personal
            [
                'familia_nombre' => 'Imagen Personal',
                'nombre'         => 'Estética y Belleza',
                'grado'          => 'Formación Profesional de Grado Medio',
                'referenciaBOE'  => 'BOE-A-2011-19355',
            ],
            [
                'familia_nombre' => 'Imagen Personal',
                'nombre'         => 'Estilismo y Dirección de Peluquería',
                'grado'          => 'Formación Profesional de Grado Superior',
                'referenciaBOE'  => 'BOE-A-2011-19356',
            ],
            // Edificación y Obra Civil
            [
                'familia_nombre' => 'Edificación y Obra Civil',
                'nombre'         => 'Construcción',
                'grado'          => 'Formación Profesional de Grado Medio',
                'referenciaBOE'  => 'BOE-A-2011-19602',
            ],
            [
                'familia_nombre' => 'Edificación y Obra Civil',
                'nombre'         => 'Proyectos de Edificación',
                'grado'          => 'Formación Profesional de Grado Superior',
                'referenciaBOE'  => 'BOE-A-2023-13218',
            ],
        ];

        foreach ($ciclosNuevos as $datos) {
            $familiaId = DB::table('familias')->where('nombre', $datos['familia_nombre'])->value('id');

            if (!$familiaId) {
                continue; // familia aún no existe en esta BD
            }

            $yaExiste = DB::table('ciclos_formativos')
                ->where('referenciaBOE', $datos['referenciaBOE'])
                ->exists();

            if ($yaExiste) {
                continue; // idempotente: no duplicar
            }

            DB::table('ciclos_formativos')->insert([
                'idCiclo'       => 0,
                'nombre'        => $datos['nombre'],
                'familia'       => $datos['familia_nombre'],
                'familia_id'    => $familiaId,
                'grado'         => $datos['grado'],
                'referenciaBOE' => $datos['referenciaBOE'],
                'siglasGrado'   => '',
            ]);
        }
    }

    public function down(): void
    {
        // Elimina los ciclos nuevos insertados en el bloque 3
        $boeNuevos = [
            'BOE-A-1995-12154', 'BOE-A-2014-9988',
            'BOE-A-2013-8067',  'BOE-A-2013-2035',
            'BOE-A-2007-19154', 'BOE-A-2011-19603',
            'BOE-A-2011-19355', 'BOE-A-2011-19356',
            'BOE-A-2011-19602', 'BOE-A-2023-13218',
        ];
        DB::table('ciclos_formativos')->whereIn('referenciaBOE', $boeNuevos)->delete();

        // Nota: la eliminación de pseudo-familias y la reasignación de ciclos
        // no se revierten automáticamente para no destruir datos de producción.
    }
};
