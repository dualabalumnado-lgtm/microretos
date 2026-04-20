<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CicloFormativo;
use App\Models\Modulo;
use App\Models\ResultadoAprendizaje;
use App\Models\Empresa;
use App\Models\Familia;
use App\Models\CentroEducativo;

class DatosFPController extends Controller
{
    // ==========================================
    // FLUJO B2B: Empresas
    // ==========================================

    /**
     * GET /empresas
     * Devuelve todas las empresas con su familia asociada.
     */
    public function getEmpresas()
    {
        // Sin 'centroEducativo' en with(): la columna centro_educativo (string) y la relación
        // tienen el mismo nombre JSON → la relación machaca el string y rompe el frontend.
        // El string legacy es suficiente para el selector de centros.
        return response()->json(
            Empresa::orderBy('nombre_comercial')->get()
        );
    }

    /**
     * GET /empresas/{id}/familias
     * Devuelve las familias profesionales vinculadas a una empresa.
     */
    public function getFamiliasPorEmpresa($idEmpresa)
    {
        $empresa = Empresa::with('familias')->find($idEmpresa);

        if (!$empresa) {
            return response()->json([], 404);
        }

        // Devolvemos siempre strings (nombres) para mantener compatibilidad con el frontend.
        // El frontend usa el nombre directamente en la URL /familias/{nombre}/ciclos.
        if ($empresa->familias->isNotEmpty()) {
            return response()->json($empresa->familias->pluck('nombre'));
        }

        // Fallback legacy: columna 'familia' string todavía sin normalizar
        return response()->json(
            DB::table('empresa_familia')
                ->where('empresa_id', $idEmpresa)
                ->whereNotNull('familia')
                ->pluck('familia')
        );
    }

    // ==========================================
    // ENDPOINTS ACADÉMICOS
    // ==========================================

    public function getFamilias()
    {
        $familias = Familia::select('id', 'nombre', 'imagen_url')
            ->orderBy('nombre')
            ->get()
            ->map(function ($familia) {
                if ($familia->imagen_url) {
                    $familia->imagen_url = asset($familia->imagen_url);
                }
                return $familia;
            });

        return response()->json($familias);
    }

    /**
     * GET /familias/{familia}/ciclos?centro=...
     */
    public function getCiclos(Request $request, $familia)
    {
        $nombreFamilia = urldecode($familia);

        $familiaModel = Familia::where('nombre', $nombreFamilia)->first();
        $query = $familiaModel
            ? CicloFormativo::where('familia_id', $familiaModel->id)
            : CicloFormativo::where('familia', $nombreFamilia); // fallback defensivo

        if ($request->filled('centro')) {
            $centro = $request->centro;

            // Primero buscamos por centro_id normalizado
            $centroModel = CentroEducativo::where('nombre', $centro)->first();

            if ($centroModel) {
                $ciclosDelCentro = DB::table('centro_ciclo')
                    ->where('centro_id', $centroModel->id)
                    ->pluck('ciclo_id');
            } else {
                // Fallback legacy: columna 'centro_educativo' string
                $ciclosDelCentro = DB::table('centro_ciclo')
                    ->where('centro_educativo', $centro)
                    ->pluck('ciclo_id');
            }

            // Solo filtramos si el centro ya tiene ciclos vinculados.
            // Si está vacío (centro recién creado o sin configurar), devolvemos
            // todos los ciclos de la familia para que la empresa sea usable.
            if ($ciclosDelCentro->isNotEmpty()) {
                $query->whereIn('id', $ciclosDelCentro);
            }
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    public function getModulos($idCiclo)
    {
        return response()->json(
            Modulo::where('idcicloformativo', $idCiclo)
                ->orderBy('curso')
                ->orderBy('nombre')
                ->get()
        );
    }

    public function getRaCe($idModulo)
    {
        $ras = ResultadoAprendizaje::with('criteriosEvaluacion')
            ->where('idmodulo', $idModulo)
            ->get();

        return response()->json($ras);
    }

    // ==========================================
    // CENTROS EDUCATIVOS
    // ==========================================

    /**
     * GET /centros
     * Devuelve todos los centros con sus ciclos agrupados por familia.
     */
    public function getCentros()
    {
        $centros = CentroEducativo::orderBy('nombre')->get();

        return response()->json($centros->map(function ($centro) {
            $ciclos = DB::table('centro_ciclo')
                ->where('centro_id', $centro->id)
                ->join('ciclos_formativos', 'ciclos_formativos.id', '=', 'centro_ciclo.ciclo_id')
                ->leftJoin('familias', 'familias.id', '=', 'ciclos_formativos.familia_id')
                ->select(
                    'ciclos_formativos.id',
                    'ciclos_formativos.nombre',
                    'familias.id as familia_id',
                    'familias.nombre as familia_nombre'
                )
                ->distinct()
                ->orderBy('familias.nombre')
                ->orderBy('ciclos_formativos.nombre')
                ->get();

            return [
                'id'     => $centro->id,
                'nombre' => $centro->nombre,
                'ciclos' => $ciclos,
            ];
        }));
    }

    /**
     * DELETE /centros/{id}
     * Elimina un centro: desvincula sus ciclos y deja sus empresas sin centro asignado.
     */
    public function eliminarCentro($id)
    {
        $centro = CentroEducativo::find($id);

        if (!$centro) {
            return response()->json(['error' => 'Centro no encontrado'], 404);
        }

        $numEmpresas = Empresa::where('centro_id', $id)->count();

        // Desvincular empresas (quedan sin centro asignado)
        Empresa::where('centro_id', $id)->update([
            'centro_id'        => null,
            'centro_educativo' => null,
        ]);

        // Eliminar relaciones ciclo-centro
        DB::table('centro_ciclo')->where('centro_id', $id)->delete();

        // Eliminar el centro
        $centro->delete();

        return response()->json([
            'message'           => 'Centro eliminado correctamente',
            'empresas_afectadas' => $numEmpresas,
        ]);
    }

    /**
     * PUT /centros/{id}
     * Actualiza el nombre y los ciclos de un centro educativo.
     * Los ciclos anteriores se reemplazan completamente por los nuevos.
     */
    public function actualizarCentro(Request $request, $id)
    {
        $centro = CentroEducativo::find($id);

        if (!$centro) {
            return response()->json(['error' => 'Centro no encontrado'], 404);
        }

        $request->validate([
            'nombre'      => 'required|string|max:255|unique:centro_educativo,nombre,' . $id,
            'ciclosIds'   => 'required|array|min:1',
            'ciclosIds.*' => 'integer|exists:ciclos_formativos,id',
        ]);

        $nombreAnterior = $centro->nombre;
        $centro->update(['nombre' => $request->nombre]);

        // Si cambió el nombre, actualizar el campo legacy en empresas y en centro_ciclo
        if ($nombreAnterior !== $request->nombre) {
            Empresa::where('centro_educativo', $nombreAnterior)
                ->update(['centro_educativo' => $request->nombre]);
            DB::table('centro_ciclo')
                ->where('centro_id', $id)
                ->update(['centro_educativo' => $request->nombre]);
        }

        // Reemplazar todos los ciclos del centro
        DB::table('centro_ciclo')->where('centro_id', $id)->delete();

        $rows = collect($request->ciclosIds)->map(fn($cicloId) => [
            'centro_id'        => $id,
            'centro_educativo' => $centro->nombre,
            'ciclo_id'         => $cicloId,
        ])->all();

        DB::table('centro_ciclo')->insertOrIgnore($rows);

        return response()->json([
            'message' => 'Centro actualizado correctamente',
            'centro'  => ['id' => $centro->id, 'nombre' => $centro->nombre],
        ]);
    }

    /**
     * POST /centros
     * Crea un centro educativo con sus ciclos asociados.
     */
    public function guardarCentro(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:255|unique:centro_educativo,nombre',
            'ciclosIds'   => 'required|array|min:1',
            'ciclosIds.*' => 'integer|exists:ciclos_formativos,id',
        ]);

        $centro = CentroEducativo::create(['nombre' => $request->nombre]);

        $rows = collect($request->ciclosIds)->map(fn($cicloId) => [
            'centro_id'        => $centro->id,
            'centro_educativo' => $centro->nombre,
            'ciclo_id'         => $cicloId,
        ])->all();

        DB::table('centro_ciclo')->insertOrIgnore($rows);

        return response()->json([
            'message' => 'Centro educativo creado correctamente',
            'centro'  => ['id' => $centro->id, 'nombre' => $centro->nombre],
        ], 201);
    }

    // ==========================================
    // GUARDADO Y ACTUALIZACIÓN DE EMPRESAS
    // ==========================================

    public function guardarEmpresa(Request $request)
    {
        $estadosPermitidos = ['Pendiente de llamar', 'Llamado - Información obtenida', 'Llamado - Negativa', 'Llamado - Llamar más tarde', 'En colaboración activa', 'Descartada'];

        $request->validate([
            'nombreComercial'  => 'required|string|max:255',
            'diaANormal'       => 'nullable|string|max:1000',
            'friccionArea'     => 'nullable|string|max:400',
            'friccionProblema' => 'nullable|string|max:1200',
            'restricciones'    => 'nullable|string|max:600',
            'loQueNoQuieren'   => 'nullable|string|max:500',
            'consecuencias'    => 'nullable',
            'esSimulada'       => 'nullable|boolean',
            'estadoContacto'   => 'nullable|string|in:' . implode(',', $estadosPermitidos),
        ]);

        $consecuenciasTexto = is_array($request->consecuencias)
            ? implode(', ', $request->consecuencias)
            : $request->consecuencias;

        // Resolvemos o creamos el centro educativo
        $centroId = null;
        if ($request->filled('centroEducativo')) {
            $centro   = CentroEducativo::firstOrCreate(['nombre' => $request->centroEducativo]);
            $centroId = $centro->id;
        }

        $empresa = Empresa::create([
            'nombre_comercial'  => $request->nombreComercial,
            'centro_educativo'  => $request->centroEducativo, // legacy
            'centro_id'         => $centroId,
            'sector'            => $request->sector,
            'tamano'            => $request->tamano,
            'web'               => $request->web,
            'dia_a_normal'      => $request->diaANormal,
            'friccion_area'     => $request->friccionArea,
            'friccion_problema' => $request->friccionProblema,
            'consecuencias'     => $consecuenciasTexto,
            'restricciones'     => $request->restricciones,
            'lo_que_no_quieren' => $request->loQueNoQuieren,
            'es_simulada'       => $request->boolean('esSimulada', false),
            'estado_contacto'   => $request->estadoContacto,
        ]);

        // Guardamos la familia usando FK si existe, además del string legacy
        if ($request->filled('familia')) {
            $familiaModel = Familia::where('nombre', $request->familia)->first();
            DB::table('empresa_familia')->insert([
                'empresa_id' => $empresa->id,
                'familia'    => $request->familia,                    // legacy
                'familia_id' => $familiaModel?->id,                   // normalizado
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Vinculamos los ciclos al nuevo centro si el usuario los seleccionó
        if ($centroId && $request->filled('ciclosIds') && is_array($request->ciclosIds)) {
            $rows = collect($request->ciclosIds)->map(fn($cicloId) => [
                'centro_id'         => $centroId,
                'centro_educativo'  => $request->centroEducativo,  // legacy
                'ciclo_id'          => $cicloId,
            ])->all();
            DB::table('centro_ciclo')->insertOrIgnore($rows);
        }

        return response()->json([
            'message' => 'Empresa creada correctamente',
            'empresa' => $empresa,
        ]);
    }

    /**
     * GET /empresas/dashboard
     * Devuelve todas las empresas con sus familias para el dashboard de base de datos.
     */
    public function getDashboardEmpresas()
    {
        $empresas = Empresa::with('familias')
            ->orderBy('nombre_comercial')
            ->get();

        return response()->json($empresas->map(function ($empresa) {
            $data = $empresa->toArray();
            $data['familias_nombres'] = $empresa->familias->pluck('nombre')->toArray();
            return $data;
        }));
    }

    /**
     * DELETE /empresas/{id}
     * Elimina una empresa y sus relaciones pivot.
     */
    public function eliminarEmpresa($id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        DB::table('empresa_familia')->where('empresa_id', $id)->delete();
        $empresa->delete();

        return response()->json(['message' => 'Empresa eliminada correctamente']);
    }

    public function actualizarEmpresa(Request $request, $id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $estadosPermitidos = ['Pendiente de llamar', 'Llamado - Información obtenida', 'Llamado - Negativa', 'Llamado - Llamar más tarde', 'En colaboración activa', 'Descartada'];

        $request->validate([
            'diaANormal'       => 'nullable|string|max:1000',
            'friccionArea'     => 'nullable|string|max:400',
            'friccionProblema' => 'nullable|string|max:1200',
            'restricciones'    => 'nullable|string|max:600',
            'loQueNoQuieren'   => 'nullable|string|max:500',
            'consecuencias'    => 'nullable',
            'esSimulada'       => 'nullable|boolean',
            'estadoContacto'   => 'nullable|string|in:' . implode(',', $estadosPermitidos),
        ]);

        $consecuenciasTexto = is_array($request->consecuencias)
            ? implode(', ', $request->consecuencias)
            : $request->consecuencias;

        // Resolvemos o creamos el centro educativo
        $centroId = $empresa->centro_id;
        if ($request->filled('centroEducativo')) {
            $centro   = CentroEducativo::firstOrCreate(['nombre' => $request->centroEducativo]);
            $centroId = $centro->id;
        }

        $updateData = [
            'centro_educativo'  => $request->centroEducativo, // legacy
            'centro_id'         => $centroId,
            'sector'            => $request->sector,
            'tamano'            => $request->tamano,
            'web'               => $request->web,
            'dia_a_normal'      => $request->diaANormal,
            'friccion_area'     => $request->friccionArea,
            'friccion_problema' => $request->friccionProblema,
            'consecuencias'     => $consecuenciasTexto,
            'restricciones'     => $request->restricciones,
            'lo_que_no_quieren' => $request->loQueNoQuieren,
        ];

        if ($request->has('esSimulada')) {
            $updateData['es_simulada'] = $request->boolean('esSimulada');
        }
        if ($request->has('estadoContacto')) {
            $updateData['estado_contacto'] = $request->estadoContacto;
        }

        $empresa->update($updateData);

        if ($request->filled('familia')) {
            $familiaModel = Familia::where('nombre', $request->familia)->first();
            DB::table('empresa_familia')->updateOrInsert(
                ['empresa_id' => $id],
                [
                    'familia'    => $request->familia,      // legacy
                    'familia_id' => $familiaModel?->id,     // normalizado
                    'updated_at' => now(),
                ]
            );
        }

        // Vinculamos los ciclos al nuevo centro si el usuario los seleccionó
        if ($centroId && $request->filled('ciclosIds') && is_array($request->ciclosIds)) {
            $rows = collect($request->ciclosIds)->map(fn($cicloId) => [
                'centro_id'         => $centroId,
                'centro_educativo'  => $request->centroEducativo,  // legacy
                'ciclo_id'          => $cicloId,
            ])->all();
            DB::table('centro_ciclo')->insertOrIgnore($rows);
        }

        return response()->json([
            'message' => 'Empresa actualizada correctamente',
            'empresa' => $empresa,
        ]);
    }

    public function actualizarEstadoEmpresa(Request $request, $id)
    {
        $empresa = Empresa::find($id);
        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $estadosPermitidos = ['Pendiente de llamar', 'Llamado - Información obtenida', 'Llamado - Negativa', 'Llamado - Llamar más tarde', 'En colaboración activa', 'Descartada'];

        $request->validate([
            'estadoContacto' => 'nullable|string|in:' . implode(',', $estadosPermitidos),
        ]);

        $empresa->update(['estado_contacto' => $request->estadoContacto ?: null]);

        return response()->json(['message' => 'Estado actualizado', 'empresa' => $empresa]);
    }
}