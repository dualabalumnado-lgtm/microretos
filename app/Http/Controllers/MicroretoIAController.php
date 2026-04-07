<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Microreto;
use App\Models\Modulo;
use App\Models\Empresa;

class MicroretoIAController extends Controller
{
    public function index(Request $request)
    {
        // Límite de seguridad: máximo 500 registros por llamada.
        // El frontend filtra en cliente, así que cargamos todo pero con techo.
        // Cuando el volumen crezca habrá que añadir filtros server-side.
        $limit = min((int) $request->query('limit', 500), 500);

        $microretos = Microreto::with([
            'empresa.centroEducativo',
            'empresa.familias',
        ])
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get()
        ->map(function ($reto) {

            $reto->es_simulado = (bool) $reto->es_simulado;

            if ($reto->empresa) {
                $reto->centro_educativo = $reto->empresa->centroEducativo?->nombre
                    ?? $reto->empresa->centro_educativo
                    ?? 'Centro Desconocido';

                $reto->familia = $reto->empresa->familias->first()?->nombre
                    ?? 'Familia Desconocida';
            } else {
                $reto->centro_educativo = 'Centro Desconocido';
                $reto->familia          = 'Familia Desconocida';
            }

            return $reto;
        });

        return response()->json($microretos);
    }

    public function show($id)
    {
        $reto = Microreto::with([
            'empresa.centroEducativo',
            'empresa.familias',
        ])->findOrFail($id);

        $reto->es_simulado = (bool) $reto->es_simulado;

        if ($reto->empresa) {
            $reto->centro_educativo = $reto->empresa->centroEducativo?->nombre
                ?? $reto->empresa->centro_educativo
                ?? 'Centro Desconocido';

            $reto->familia = $reto->empresa->familias->first()?->nombre
                ?? 'Familia Desconocida';
        } else {
            $reto->centro_educativo = 'Centro Desconocido';
            $reto->familia          = 'Familia Desconocida';
        }

        return response()->json($reto);
    }

    public function generar(Request $request)
    {
        // El frontend manda empresaId (camelCase), normalizamos antes de validar
        $request->merge([
            'empresa_id' => $request->empresa_id ?? $request->empresaId,
        ]);

        $request->validate([
            'empresa_id'       => 'required|integer|exists:empresas,id',
            'empresaNombre'    => 'required|string',
            'empresaSector'    => 'required|string',
            'friccionProblema' => 'required|string',
            'ciclo_nombre'     => 'required|string',
            'ciclo_id'         => 'required',
            'nivelGrupo'       => 'required|string',
            'cursoSeleccionado'=> 'required|integer',
            'modulo_id'        => 'nullable|array',
            'cantidad'         => 'required|integer|min:1|max:15',
        ]);

        $consecuencias = implode(", ", $request->consecuencias ?? []);

        $query = Modulo::with(['ras.criteriosEvaluacion']);

        if ($request->filled('modulo_id') && is_array($request->modulo_id) && count($request->modulo_id) > 0) {
            $query->whereIn('id', $request->modulo_id);
        } else {
            $query->where('idcicloformativo', $request->ciclo_id)
                  ->where('curso', $request->cursoSeleccionado);
        }
        $modulos = $query->get();

        $curriculumTexto = "--- INICIO CURRÍCULO DE {$request->cursoSeleccionado}º CURSO ---\n";
        foreach ($modulos as $modulo) {
            $curriculumTexto .= "\n[MÓDULO]: {$modulo->nombre}\n";
            foreach ($modulo->ras as $ra) {
                $curriculumTexto .= "  - [RA]: {$ra->descripcion}\n";
                foreach ($ra->criteriosEvaluacion as $ce) {
                    $curriculumTexto .= "    * [CE]: {$ce->descripcion}\n";
                }
            }
        }
        $curriculumTexto .= "\n--- FIN CURRÍCULO ---";

        $esBasica    = ($request->nivelGrupo === 'Básico');
        $reglaExtra  = $esBasica
            ? "REGLA: Nivel Básico (FP Básica). Reto eminentemente manual, paso a paso y muy guiado."
            : "REGLA: Nivel {$request->nivelGrupo}. Adapta la complejidad técnica al nivel indicado.";
        $reglaExtra .= " TEN EN CUENTA QUE ES PARA ALUMNADO DE {$request->cursoSeleccionado}º CURSO. Adapta el prototipo a sus conocimientos.";

        $contextoEmpresa = "EMPRESA: {$request->empresaNombre} (Sector: {$request->empresaSector}). ";
        if ($request->filled('empresaTamano'))    $contextoEmpresa .= "Tamaño: {$request->empresaTamano}. ";
        if ($request->filled('empresaUbicacion')) $contextoEmpresa .= "Ubicación: {$request->empresaUbicacion}. ";

        $contextoFriccion  = "OPERATIVA Y OFERTA (P1): {$request->diaANormal}\n";
        $contextoFriccion .= "PROCESO QUE DA TRABAJO EXTRA (P2): {$request->friccionArea}\n";
        $contextoFriccion .= "DETALLE DEL PROBLEMA (P2b): {$request->friccionProblema}\n";
        $contextoFriccion .= "OBJETIVOS DE MEJORA / CONSECUENCIAS (P4): {$consecuencias}\n";
        if ($request->filled('expectativasAlumno')) {
            $contextoFriccion .= "EXPECTATIVA DE LO QUE DEBE HACER EL ALUMNO (P5): {$request->expectativasAlumno}\n";
        }

        $systemPrompt = "Eres un consultor de innovación y diseñador instruccional experto en formación profesional y metodologías ágiles (Design Thinking).
        REGLAS ESTRICTAS:
        1. NO proponer soluciones cerradas. Puedes sugerir el tipo de prototipo a entregar. El alumno debe idear la solución final.
        2. Genera EXACTAMENTE {$request->cantidad} microreto(s) totalmente distintos entre sí para la misma empresa.
        3. Para lograr variedad, selecciona diferentes Resultados de Aprendizaje (RA) y Criterios de Evaluación (CE) para cada reto.";

        $userPrompt = "
        {$contextoEmpresa}
        {$contextoFriccion}
        LIMITACIONES TÉCNICAS Y LOGÍSTICAS (P3): {$request->restricciones}.
        LO QUE NO QUIEREN (P3b): {$request->loQueNoQuieren}.
        DURACIÓN: {$request->duracion}.

        {$curriculumTexto}
        {$reglaExtra}

        Basándote en los módulos del currículo, DEVUELVE ESTE JSON EXACTO CON UN ARRAY DE EXACTAMENTE {$request->cantidad} MICRORETO(S):
        {
            \"microretos\": [
                {
                    \"titulo\": \"Título corto y directo del reto\",
                    \"subtitulo\": \"Descripción de 1 línea del desafío (sin revelar la solución)\",
                    \"empresa_nombre\": \"{$request->empresaNombre}\",
                    \"quien_es\": \"1-2 frases sobre la actividad de la empresa basándote en su sector y operativa.\",
                    \"dia_a_dia\": \"1 frase clara sobre cómo operan y dónde falla el proceso actualmente.\",
                    \"dificultades\": [\"Fallo 1\", \"Fallo 2\"],
                    \"pregunta_reto\": \"Formula el desafío en forma de pregunta abierta empezando por ¿Cómo podríamos...?\",
                    \"que_necesitan\": [\"Necesidad 1\", \"Necesidad 2\"],
                    \"limitaciones\": [\"Restricción 1\", \"Restricción 2\"],
                    \"prototipos\": [\"Entregable concreto 1 (ej: Diagrama de flujo)\", \"Entregable concreto 2 (ej: Guion de entrevista)\"],
                    \"ods_sugeridos\": [\"ODS X: Nombre completo del ODS\"],
                    \"evaluacion_oficial\": [
                        {
                            \"modulo\": \"Nombre exacto del Módulo 1\",
                            \"ra\": \"Texto del RA asociado\",
                            \"ce\": [\"Texto CE 1\"],
                            \"aplicacion\": \"Breve frase explicando cómo se aterriza este aprendizaje.\"
                        }
                    ],
                    \"variantes\": [
                        \"Nombre de la Variante: Descripción de una modificación del reto.\"
                    ],
                    \"tips_profesorado\": [
                        \"Gestión de Aula: [Instrucciones sobre dinámicas o roles].\"
                    ]
                }
            ]
        }";

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(120)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model"           => "gpt-4o",
                "messages"        => [
                    ["role" => "system", "content" => $systemPrompt],
                    ["role" => "user",   "content" => $userPrompt],
                ],
                "response_format" => ["type" => "json_object"],
                "temperature"     => 0.9,
            ]);

        if ($response->successful()) {
            return response()->json(json_decode($response->json()['choices'][0]['message']['content'], true));
        }

        return response()->json(['error' => 'Error al contactar con la IA'], 500);
    }

    public function guardarEnBD(Request $request)
    {
        try {
            $datos = $request->except(['_ui_guardado', '_ui_guardando']);
            $microreto = Microreto::create($datos);
            return response()->json(['mensaje' => 'Micro-reto archivado', 'reto' => $microreto], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar en BD: ' . $e->getMessage()], 500);
        }
    }

    public function guardarLote(Request $request)
    {
        $request->validate([
            'microretos' => 'required|array',
        ]);

        try {
            $insertados = [];
            foreach ($request->microretos as $retoData) {
                unset($retoData['_ui_guardado'], $retoData['_ui_guardando']);
                $insertados[] = Microreto::create($retoData);
            }
            return response()->json(['mensaje' => count($insertados) . ' Micro-retos archivados en lote con éxito'], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar el lote en BD: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            Microreto::findOrFail($id)->delete();
            return response()->json(['mensaje' => 'Micro-reto eliminado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }
}
