<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Microproyecto;

class MicroproyectoController extends Controller
{
    public function index(Request $request)
    {
        $proyectos = Microproyecto::with(['empresa', 'centroEducativo', 'cicloFormativo', 'microreto'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn($p) => $this->formatProyecto($p));

        return response()->json($proyectos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'microreto_id' => 'required|exists:microretos,id',
            'sesion_id'    => 'nullable|exists:sesiones,id',
            'empresa_id'   => 'nullable|exists:empresas,id',
            'centro_id'    => 'nullable|exists:centros_educativos,id',
            'familia_id'   => 'nullable|exists:familias,id',
            'ciclo_id'     => 'nullable|exists:ciclos_formativos,id',
            'curso'        => 'nullable|string',
        ]);

        $proyecto = Microproyecto::create($data);

        return response()->json($this->formatProyecto($proyecto->fresh()), 201);
    }

    public function show($uuid)
    {
        $proyecto = Microproyecto::with(['empresa', 'centroEducativo', 'cicloFormativo', 'microreto'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json($this->formatProyecto($proyecto));
    }

    public function update(Request $request, $uuid)
    {
        $proyecto = Microproyecto::where('uuid', $uuid)->firstOrFail();

        $allowed = [
            'titulo', 'curso', 'sesion_id', 'empresa_id', 'centro_id', 'familia_id', 'ciclo_id',
            'datos_empresa', 'datos_centro', 'equipo', 'modulos_seleccionados', 'ra_ce',
            'fundamentacion', 'diseno_reto', 'diseno_microproyecto', 'resumen',
            'objetivos', 'kpis', 'validacion_empresa',
            'paso_actual', 'estado',
        ];

        $proyecto->update($request->only($allowed));

        return response()->json($this->formatProyecto($proyecto->fresh()));
    }

    public function destroy($uuid)
    {
        $proyecto = Microproyecto::where('uuid', $uuid)->firstOrFail();
        $proyecto->delete();

        return response()->json(['ok' => true]);
    }

    // --- Validación pública empresa (acceso por token) ---

    public function showByToken($token)
    {
        $proyecto = Microproyecto::where('token_empresa', $token)
            ->where('estado', 'publicado')
            ->firstOrFail();

        return response()->json([
            'uuid'            => $proyecto->uuid,
            'titulo'          => $proyecto->titulo,
            'datos_empresa'   => $proyecto->datos_empresa,
            'diseno_reto'     => $proyecto->diseno_reto,
            'objetivos'       => $proyecto->objetivos,
            'equipo'          => $proyecto->equipo,
            'empresa_validado'=> $proyecto->empresa_validado,
            'validacion_empresa' => $proyecto->validacion_empresa,
        ]);
    }

    public function validarEmpresa(Request $request, $token)
    {
        $proyecto = Microproyecto::where('token_empresa', $token)
            ->where('estado', 'publicado')
            ->firstOrFail();

        $data = $request->validate([
            'respuestas' => 'required|array',
            'comentarios'=> 'nullable|string|max:2000',
        ]);

        $proyecto->update([
            'validacion_empresa' => $data,
            'empresa_validado'   => true,
        ]);

        return response()->json(['ok' => true]);
    }

    // --- Helper ---

    private function formatProyecto(Microproyecto $p): array
    {
        return [
            'id'               => $p->id,
            'uuid'             => $p->uuid,
            'titulo'           => $p->titulo,
            'curso'            => $p->curso,
            'estado'           => $p->estado,
            'paso_actual'      => $p->paso_actual,
            'empresa_validado' => $p->empresa_validado,
            'token_empresa'    => $p->token_empresa,
            'empresa_id'       => $p->empresa_id,
            'empresa_nombre'   => $p->empresa?->nombre_comercial,
            'centro_id'        => $p->centro_id,
            'centro_nombre'    => $p->centroEducativo?->nombre,
            'ciclo_id'         => $p->ciclo_id,
            'ciclo_nombre'     => $p->cicloFormativo?->nombre,
            'familia_id'       => $p->familia_id,
            'microreto_id'     => $p->microreto_id,
            'microreto_titulo' => $p->microreto?->titulo,
            'sesion_id'        => $p->sesion_id,
            'datos_empresa'    => $p->datos_empresa,
            'datos_centro'     => $p->datos_centro,
            'equipo'           => $p->equipo,
            'modulos_seleccionados' => $p->modulos_seleccionados,
            'ra_ce'            => $p->ra_ce,
            'fundamentacion'   => $p->fundamentacion,
            'diseno_reto'      => $p->diseno_reto,
            'diseno_microproyecto' => $p->diseno_microproyecto,
            'resumen'          => $p->resumen,
            'objetivos'        => $p->objetivos,
            'kpis'             => $p->kpis,
            'validacion_empresa' => $p->validacion_empresa,
            'created_at'       => $p->created_at,
            'updated_at'       => $p->updated_at,
        ];
    }
}
