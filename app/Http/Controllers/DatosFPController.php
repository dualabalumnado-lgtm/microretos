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

            $query->whereIn('id', $ciclosDelCentro);
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
    // GUARDADO Y ACTUALIZACIÓN DE EMPRESAS
    // ==========================================

    public function guardarEmpresa(Request $request)
    {
        $request->validate([
            'nombreComercial'  => 'required|string|max:255',
            'diaANormal'       => 'nullable|string|max:1000',
            'friccionArea'     => 'nullable|string|max:400',
            'friccionProblema' => 'nullable|string|max:1200',
            'restricciones'    => 'nullable|string|max:600',
            'loQueNoQuieren'   => 'nullable|string|max:500',
            'consecuencias'    => 'nullable',
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

        return response()->json([
            'message' => 'Empresa creada correctamente',
            'empresa' => $empresa->load('centroEducativo', 'familias'),
        ]);
    }

    public function actualizarEmpresa(Request $request, $id)
    {
        $empresa = Empresa::find($id);

        if (!$empresa) {
            return response()->json(['error' => 'Empresa no encontrada'], 404);
        }

        $request->validate([
            'diaANormal'       => 'nullable|string|max:1000',
            'friccionArea'     => 'nullable|string|max:400',
            'friccionProblema' => 'nullable|string|max:1200',
            'restricciones'    => 'nullable|string|max:600',
            'loQueNoQuieren'   => 'nullable|string|max:500',
            'consecuencias'    => 'nullable',
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

        $empresa->update([
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
        ]);

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

        return response()->json([
            'message' => 'Empresa actualizada correctamente',
            'empresa' => $empresa->load('centroEducativo', 'familias'),
        ]);
    }
}