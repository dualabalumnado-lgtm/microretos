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

        $data = $request->only($allowed);

        // Si el proyecto está validado y se modifica un campo relevante para la empresa, se invalida la validación
        if ($proyecto->empresa_validado) {
            $camposCriticos = [
                'titulo', 'empresa_id', 'datos_empresa', 'datos_centro', 'equipo',
                'modulos_seleccionados', 'ra_ce', 'fundamentacion', 'diseno_reto',
                'diseno_microproyecto', 'resumen', 'objetivos', 'kpis',
            ];

            foreach ($camposCriticos as $campo) {
                if (!array_key_exists($campo, $data)) continue;

                $anterior = is_array($proyecto->$campo)
                    ? json_encode($proyecto->$campo)
                    : (string) ($proyecto->$campo ?? '');
                $nuevo = is_array($data[$campo])
                    ? json_encode($data[$campo])
                    : (string) ($data[$campo] ?? '');

                if ($anterior !== $nuevo) {
                    $data['empresa_validado']   = false;
                    $data['validacion_empresa'] = null;
                    break;
                }
            }
        }

        $proyecto->update($data);

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
        $proyecto = Microproyecto::with(['recursos', 'microreto'])
            ->where('token_empresa', $token)
            ->where('estado', 'publicado')
            ->firstOrFail();

        $formato = fn($r) => [
            'url'           => $r->url,
            'public_id'     => $r->public_id,
            'resource_type' => $r->resource_type,
            'filename'      => $r->filename,
            'label'         => $r->label ?? '',
        ];

        $mr = $proyecto->microreto;

        return response()->json([
            'uuid'                   => $proyecto->uuid,
            'titulo'                 => $proyecto->titulo,
            'datos_empresa'          => $proyecto->datos_empresa,
            'datos_centro'           => $proyecto->datos_centro,
            'fundamentacion'         => $proyecto->fundamentacion,
            'diseno_reto'            => $proyecto->diseno_reto,
            'diseno_microproyecto'   => $proyecto->diseno_microproyecto,
            'objetivos'              => $proyecto->objetivos,
            'kpis'                   => $proyecto->kpis,
            'equipo'                 => $proyecto->equipo,
            'modulos_seleccionados'  => $proyecto->modulos_seleccionados,
            'ra_ce'                  => $proyecto->ra_ce,
            'resumen'                => $proyecto->resumen,
            'recursos'               => [
                'videos'     => $proyecto->recursos->where('tipo', 'video')->map($formato)->values(),
                'documentos' => $proyecto->recursos->where('tipo', 'documento')->map($formato)->values(),
            ],
            'reto_origen'            => $mr ? [
                'titulo'        => $mr->titulo,
                'quien_es'      => $mr->quien_es,
                'dia_a_dia'     => $mr->dia_a_dia,
                'que_necesitan' => $mr->que_necesitan,
                'dificultades'  => $mr->dificultades,
            ] : null,
            'empresa_validado'   => $proyecto->empresa_validado,
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

    // --- IA: sugerencia de RA/CE ---

    public function sugerirRaCe(Request $request)
    {
        $request->validate([
            'modulo_ids'   => 'required|array|min:1',
            'modulo_ids.*' => 'integer|exists:modulos,id',
        ]);

        $modulos = \App\Models\Modulo::with(['ras.criteriosEvaluacion'])
            ->whereIn('id', $request->modulo_ids)
            ->get();

        if ($modulos->isEmpty()) {
            return response()->json(['error' => 'No se encontraron módulos'], 404);
        }

        $curriculo = '';
        foreach ($modulos as $modulo) {
            $curriculo .= "[MÓDULO]: {$modulo->nombre}\n";
            foreach ($modulo->ras as $ra) {
                $curriculo .= "  RA: {$ra->ra}\n";
                foreach ($ra->criteriosEvaluacion as $ce) {
                    $curriculo .= "    CE: {$ce->ce}\n";
                }
            }
            $curriculo .= "\n";
        }

        $contexto = '';
        if ($request->filled('titulo'))        $contexto .= "Título: {$request->titulo}\n";
        if ($request->filled('pregunta_reto')) $contexto .= "Reto: {$request->pregunta_reto}\n";
        if ($request->filled('descripcion'))   $contexto .= "Descripción: {$request->descripcion}\n";
        if ($request->filled('contexto'))      $contexto .= "Contexto empresa: {$request->contexto}\n";

        if (!$contexto) $contexto = "Sin contexto adicional.\n";

        $systemPrompt = "Eres un experto en currículum de Formación Profesional española. Selecciona los Resultados de Aprendizaje (RA) y Criterios de Evaluación (CE) más relevantes para el microproyecto descrito. Elige SOLO los que se trabajan directamente en el microproyecto. Usa los textos EXACTOS del catálogo.";

        $userPrompt = "Microproyecto:\n{$contexto}\nCatálogo RA/CE de los módulos seleccionados:\n{$curriculo}\nDevuelve SOLO este JSON:\n{\"seleccion\":[{\"modulo\":\"Nombre exacto del módulo\",\"ra\":\"Texto exacto del RA\",\"ce\":[\"Texto exacto del CE\"]}]}";

        $response = \Illuminate\Support\Facades\Http::withToken(config('services.openai.key'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'           => 'gpt-4o',
                'messages'        => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userPrompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature'     => 0.2,
            ]);

        if (!$response->successful()) {
            return response()->json(['error' => 'Error al contactar con la IA'], 500);
        }

        $data      = json_decode($response->json()['choices'][0]['message']['content'], true);
        $seleccion = $data['seleccion'] ?? [];

        $texto = collect($seleccion)->map(function ($item) {
            $ces = collect($item['ce'] ?? [])->map(fn($c) => "  • {$c}")->join("\n");
            return "[{$item['modulo']}]\nRA: {$item['ra']}\nCE:\n{$ces}";
        })->join("\n\n");

        return response()->json([
            'seleccion'   => $seleccion,
            'ra_ce_texto' => $texto,
        ]);
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
