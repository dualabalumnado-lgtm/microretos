<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // ¡Añadido para consultar la tabla pivote!
use App\Models\CicloFormativo;
use App\Models\Modulo;
use App\Models\ResultadoAprendizaje;
use App\Models\Empresa; // ¡Añadido el nuevo modelo!

class DatosFPController extends Controller
{
    // ==========================================
    // NUEVOS ENDPOINTS: FLUJO B2B (Empresa -> Familia)
    // ==========================================

    public function getEmpresas()
    {
        // Ahora traemos TODOS los campos para pintar la tarjeta de contacto
        return response()->json(
            Empresa::orderBy('nombre_comercial')->get()
        );
    }

    public function getFamiliasPorEmpresa($idEmpresa)
    {
        // Buscamos directamente en la tabla pivote qué familias tiene asociadas esta empresa concreta
        $familias = DB::table('empresa_familia')
            ->where('empresa_id', $idEmpresa)
            ->pluck('familia');

        return response()->json($familias);
    }

    // ==========================================
    // ENDPOINTS ACADÉMICOS ORIGINALES
    // ==========================================

    public function getFamilias()
    {
        return response()->json(
            CicloFormativo::whereNotNull('familia')
                ->where('familia', '!=', '')
                ->distinct()
                ->orderBy('familia')
                ->pluck('familia')
        );
    }

    public function getCiclos(Request $request, $familia)
    {
        $query = CicloFormativo::where('familia', urldecode($familia));

        // Si el frontend nos envía el parámetro 'centro', filtramos
        if ($request->filled('centro')) {
            $centro = $request->centro;
            
            // Buscamos qué IDs de ciclo pertenecen a ese centro en la tabla pivote
            $ciclosDelCentro = \Illuminate\Support\Facades\DB::table('centro_ciclo')
                ->where('centro_educativo', $centro)
                ->pluck('ciclo_id');
                
            // Le decimos a la consulta principal que solo traiga esos IDs
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

    // Corregido a getRaCe (sin el guion bajo) para que coincida con tus rutas
    public function getRaCe($idModulo)
    {
        $ras = ResultadoAprendizaje::with('criteriosEvaluacion')
            ->where('idmodulo', $idModulo)
            ->get();

        return response()->json($ras);
    }

    // ==========================================
    // ENDPOINTS DE GUARDADO Y ACTUALIZACIÓN
    // ==========================================

    // NUEVO: Para guardar una empresa que NO existe
    public function guardarEmpresa(Request $request)
    {
        $consecuenciasTexto = is_array($request->consecuencias) 
            ? implode(', ', $request->consecuencias) 
            : $request->consecuencias;

        // 1. Creamos la empresa
        $empresa = Empresa::create([
            'nombre_comercial'  => $request->nombreComercial, // Asegúrate de pedir este campo en el frontend
            'centro_educativo'  => $request->centroEducativo,
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

        // 2. Si nos mandan la familia desde el frontend, la guardamos en la tabla pivote
        if ($request->filled('familia')) {
            DB::table('empresa_familia')->insert([
                'empresa_id' => $empresa->id,
                'familia'    => $request->familia
            ]);
        }

        return response()->json([
            'message' => 'Empresa creada correctamente', 
            'empresa' => $empresa
        ]);
    }

    // MODIFICADO: Actualiza empresa existente Y su familia
    public function actualizarEmpresa(Request $request, $id)
    {
        $empresa = Empresa::find($id);
        
        if ($empresa) {
            $consecuenciasTexto = is_array($request->consecuencias) 
                ? implode(', ', $request->consecuencias) 
                : $request->consecuencias;

            $empresa->update([
                'centro_educativo'  => $request->centroEducativo,
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
            
            // Actualizamos o insertamos la familia en la tabla pivote
            if ($request->filled('familia')) {
                DB::table('empresa_familia')->updateOrInsert(
                    ['empresa_id' => $id],
                    ['familia'    => $request->familia]
                );
            }
            
            return response()->json(['message' => 'Empresa actualizada correctamente']);
        }
        return response()->json(['error' => 'Empresa no encontrada'], 404);
    }


    
}