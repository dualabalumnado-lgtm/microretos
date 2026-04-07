<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CicloFormativo;
use App\Models\Modulo;
use App\Models\ResultadoAprendizaje;
use App\Models\Empresa;

class DatosFPController extends Controller
{
    // ─────────────────────────────────────────────
    //  EMPRESAS
    // ─────────────────────────────────────────────

    /**
     * GET /empresas
     * Devuelve todas las empresas con su familia asociada (JOIN con empresa_familia).
     */
    public function getEmpresas()
    {
        $empresas = DB::table('empresas')
            ->leftJoin('empresa_familia', 'empresas.id', '=', 'empresa_familia.empresa_id')
            ->select('empresas.*', 'empresa_familia.familia')
            ->orderBy('empresas.nombre_comercial')
            ->get();

        return response()->json($empresas);
    }

    /**
     * GET /empresas/{id}/familias
     * Devuelve las familias profesionales vinculadas a una empresa.
     */
    public function getFamiliasPorEmpresa($idEmpresa)
    {
        $familias = DB::table('empresa_familia')
            ->where('empresa_id', $idEmpresa)
            ->pluck('familia');

        return response()->json($familias);
    }

    /**
     * POST /empresas  (protegido por Sanctum)
     * Crea una empresa nueva e inserta su familia en empresa_familia.
     * Devuelve la empresa completa con su familia para que el frontend
     * pueda añadirla a la lista y seleccionarla directamente.
     */
    public function guardarEmpresa(Request $request)
    {
        $request->validate([
            'nombreComercial' => 'required|string|max:255',
            'sector'          => 'required|string|max:255',
            'tamano'          => 'required|string|max:255',
            'familia'         => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $empresa = Empresa::create([
                'nombre_comercial'  => $request->nombreComercial,
                'razon_social'      => $request->razonSocial      ?? $request->nombreComercial,
                'cif'               => $request->cif              ?? null,
                'sector'            => $request->sector,
                'tamano'            => $request->tamano,
                'web'               => $request->web              ?? null,
                'centro_educativo'  => $request->centroEducativo  ?? null,
                'persona_contacto'  => $request->personaContacto  ?? null,
                'telefono'          => $request->telefono         ?? null,
                'email_general'     => $request->emailGeneral     ?? null,
                'direccion'         => $request->direccion        ?? null,
                'municipio'         => $request->municipio        ?? null,
                'provincia'         => $request->provincia        ?? null,
                'codigo_postal'     => $request->codigoPostal     ?? null,
                'actividad'         => $request->actividad        ?? null,
            ]);

            DB::table('empresa_familia')->insert([
                'empresa_id' => $empresa->id,
                'familia'    => $request->familia,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Añadir familia al objeto para que el frontend lo use directamente
            $empresa->familia = $request->familia;

            DB::commit();

            return response()->json([
                'success' => true,
                'empresa' => $empresa,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la empresa: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /empresas/{id}  (protegido por Sanctum)
     * Actualiza los campos de una empresa existente.
     * Si se envía 'familia', actualiza también la tabla pivote.
     * Devuelve la empresa completa actualizada con JOIN de familia.
     */
    public function actualizarEmpresa(Request $request, $id)
    {
        $empresa = Empresa::findOrFail($id);

        $empresa->update([
            'nombre_comercial'  => $request->nombreComercial  ?? $empresa->nombre_comercial,
            'razon_social'      => $request->razonSocial      ?? $empresa->razon_social,
            'cif'               => $request->cif              ?? $empresa->cif,
            'sector'            => $request->sector           ?? $empresa->sector,
            'tamano'            => $request->tamano           ?? $empresa->tamano,
            'web'               => $request->web              ?? $empresa->web,
            'centro_educativo'  => $request->centroEducativo  ?? $empresa->centro_educativo,
            'persona_contacto'  => $request->personaContacto  ?? $empresa->persona_contacto,
            'telefono'          => $request->telefono         ?? $empresa->telefono,
            'email_general'     => $request->emailGeneral     ?? $empresa->email_general,
            'direccion'         => $request->direccion        ?? $empresa->direccion,
            'municipio'         => $request->municipio        ?? $empresa->municipio,
            'provincia'         => $request->provincia        ?? $empresa->provincia,
            'codigo_postal'     => $request->codigoPostal     ?? $empresa->codigo_postal,
            'actividad'         => $request->actividad        ?? $empresa->actividad,
            // Campos de diagnóstico (usados desde el Paso 3 del generador)
            'dia_a_normal'      => $request->diaANormal       ?? $empresa->dia_a_normal,
            'friccion_area'     => $request->friccionArea     ?? $empresa->friccion_area,
            'friccion_problema' => $request->friccionProblema ?? $empresa->friccion_problema,
            'consecuencias'     => $request->consecuencias    ?? $empresa->consecuencias,
            'restricciones'     => $request->restricciones    ?? $empresa->restricciones,
            'lo_que_no_quieren' => $request->loQueNoQuieren   ?? $empresa->lo_que_no_quieren,
        ]);

        if ($request->filled('familia')) {
            DB::table('empresa_familia')->updateOrInsert(
                ['empresa_id' => $id],
                ['familia' => $request->familia, 'updated_at' => now()]
            );
        }

        // Devolver empresa completa con familia para sincronizar el frontend
        $empresaCompleta = DB::table('empresas')
            ->leftJoin('empresa_familia', 'empresas.id', '=', 'empresa_familia.empresa_id')
            ->select('empresas.*', 'empresa_familia.familia')
            ->where('empresas.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'empresa' => $empresaCompleta,
        ]);
    }

    // ─────────────────────────────────────────────
    //  FAMILIAS PROFESIONALES
    // ─────────────────────────────────────────────

    /**
     * GET /familias
     * Las familias vienen de ciclos_formativos (no hay tabla familias separada en el dump).
     */
    public function getFamilias()
    {
        $familias = DB::table('ciclos_formativos')
            ->whereNotNull('familia')
            ->distinct()
            ->orderBy('familia')
            ->pluck('familia');

        return response()->json($familias);
    }

    // ─────────────────────────────────────────────
    //  CICLOS, MÓDULOS Y RA/CE
    // ─────────────────────────────────────────────

    /**
     * GET /familias/{familia}/ciclos?centro=...
     */
    public function getCiclos(Request $request, $familia)
    {
        $query = CicloFormativo::where('familia', urldecode($familia));

        if ($request->filled('centro')) {
            $ciclosDelCentro = DB::table('centro_ciclo')
                ->where('centro_educativo', $request->centro)
                ->pluck('ciclo_id');

            $query->whereIn('id', $ciclosDelCentro);
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    /**
     * GET /ciclos/{idCiclo}/modulos
     */
    public function getModulos($idCiclo)
    {
        return response()->json(
            Modulo::where('idcicloformativo', $idCiclo)
                ->orderBy('curso')
                ->orderBy('nombre')
                ->get()
        );
    }

    /**
     * GET /modulos/{idModulo}/ra-ce
     */
    public function getRaCe($idModulo)
    {
        $ras = ResultadoAprendizaje::with('criteriosEvaluacion')
            ->where('idmodulo', $idModulo)
            ->get();

        return response()->json($ras);
    }
}