<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Microreto;
use App\Models\Modulo;

class MicroretoIAController extends Controller
{
    public function generar(Request $request)
    {
        $request->validate([
            'empresaNombre' => 'required|string',
            'empresaSector' => 'required|string',
            'friccionProblema' => 'required|string',
            'ciclo_nombre' => 'required|string',
            'ciclo_id' => 'required',
            'nivelGrupo' => 'required|string',
            'cursoSeleccionado' => 'required|integer', // NUEVO: Validamos que llegue el curso
            'modulo_id' => 'nullable|array'
        ]);

        // Convertimos las consecuencias (Pregunta 4) en texto para el contexto
        $consecuencias = implode(", ", $request->consecuencias ?? []);
        
        // 1. Obtener currículo de los módulos seleccionados (o del curso del ciclo)
        $query = Modulo::with(['ras.criteriosEvaluacion']);
        
        if ($request->filled('modulo_id') && is_array($request->modulo_id) && count($request->modulo_id) > 0) {
            $query->whereIn('id', $request->modulo_id);
        } else {
            // NUEVO: Si la IA decide, le pasamos solo los módulos del curso seleccionado
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

        // NUEVO: Añadimos el contexto del curso a las reglas
        $esBasica = ($request->nivelGrupo === 'Básico');
        $reglaExtra = $esBasica ? "REGLA: Nivel Básico (FP Básica). Reto eminentemente manual, paso a paso y muy guiado." : "REGLA: Nivel {$request->nivelGrupo}. Adapta la complejidad técnica al nivel indicado.";
        $reglaExtra .= " TEN EN CUENTA QUE ES PARA ALUMNADO DE {$request->cursoSeleccionado}º CURSO. Adapta el prototipo a sus conocimientos.";

        // 2. Construcción del Contexto de la Entrevista (Paso 2 enriquecido)
        $contextoEmpresa = "EMPRESA: {$request->empresaNombre} (Sector: {$request->empresaSector}). ";
        if ($request->filled('empresaTamano')) $contextoEmpresa .= "Tamaño: {$request->empresaTamano}. ";
        if ($request->filled('empresaUbicacion')) $contextoEmpresa .= "Ubicación: {$request->empresaUbicacion}. ";

        $contextoFriccion = "OPERATIVA Y OFERTA (P1): {$request->diaANormal}\n";
        $contextoFriccion .= "PROCESO QUE DA TRABAJO EXTRA (P2): {$request->friccionArea}\n";
        $contextoFriccion .= "DETALLE DEL PROBLEMA (P2b): {$request->friccionProblema}\n";
        $contextoFriccion .= "OBJETIVOS DE MEJORA / CONSECUENCIAS (P4): {$consecuencias}\n";

        // Añadimos la Pregunta 5 (Expectativa) si existe
        if ($request->filled('expectativasAlumno')) {
            $contextoFriccion .= "EXPECTATIVA DE LO QUE DEBE HACER EL ALUMNO (P5): {$request->expectativasAlumno}\n";
        }

        $systemPrompt = "Eres un consultor de innovación y diseñador instruccional experto en formación profesional y ciclos formativos. 
        Tu objetivo es redactar un documento técnico, atractivo, formal y corporativo. No uses lenguaje robótico ni emojis.";

        $userPrompt = "
        {$contextoEmpresa}
        {$contextoFriccion}
        LIMITACIONES TÉCNICAS Y LOGÍSTICAS (P3): {$request->restricciones}. 
        LO QUE NO QUIEREN (P3b): {$request->loQueNoQuieren}.
        DURACIÓN: {$request->duracion}.
        
        {$curriculumTexto}
        {$reglaExtra}

        Basándote estrictamente en los módulos del currículo proporcionado y en la EXPECTATIVA de la empresa (P5), DEVUELVE ESTE JSON EXACTO:
        {
            \"titulo\": \"Título corto y directo del reto\",
            \"subtitulo\": \"Descripción de 1 línea de lo que se va a desarrollar\",
            \"empresa_nombre\": \"{$request->empresaNombre}\",
            \"quien_es\": \"1-2 frases sobre la actividad de la empresa basándote en su sector y operativa.\",
            \"dia_a_dia\": \"1 frase clara sobre cómo operan y dónde falla el proceso actualmente.\",
            \"dificultades\": [\"Fallo 1\", \"Fallo 2\"],
            \"pregunta_reto\": \"Formula el desafío técnico en forma de pregunta directa empezando por ¿Cómo podríamos...?\",
            \"que_necesitan\": [\"Necesidad 1\", \"Necesidad 2\"],
            \"limitaciones\": [\"Restricción 1\", \"Restricción 2\"],
            \"prototipos\": [\"Entregable concreto 1\", \"Entregable concreto 2\"],
            \"ods_sugeridos\": [\"ODS X: Nombre completo del ODS\", \"ODS Y: Nombre completo del ODS\"],
            \"evaluacion_oficial\": [
                {
                    \"modulo\": \"Nombre exacto del Módulo 1\",
                    \"ra\": \"Texto del RA asociado\",
                    \"ce\": [\"Texto CE 1\", \"Texto CE 2\"],
                    \"aplicacion\": \"Breve frase explicando cómo se aterriza este aprendizaje en la resolución del problema de la empresa.\"
                },
                {
                    \"modulo\": \"Nombre exacto del Módulo 2 (OBLIGATORIO: Incluye siempre al menos 2 módulos para que el reto sea transversal)\",
                    \"ra\": \"Texto del RA asociado\",
                    \"ce\": [\"Texto CE 1\"],
                    \"aplicacion\": \"Breve frase explicando la utilidad práctica aquí.\"
                }
            ],
            \"variantes\": [
                \"Nombre de la Variante: Descripción de una modificación del reto para adaptarlo a otros escenarios.\"
            ],
            \"tips_profesorado\": [
                \"Gestión de Aula: [Instrucciones sobre dinámicas o roles para organizar al alumnado].\",
                \"Prevención de Bloqueos: [Explicación de dónde se atascarán técnicamente y cómo guiarles].\"
            ]
        }";

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->timeout(90)
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o",
                "messages" => [
                    ["role" => "system", "content" => $systemPrompt],
                    ["role" => "user", "content" => $userPrompt]
                ],
                "response_format" => ["type" => "json_object"],
                "temperature" => 0.7
            ]);

        if ($response->successful()) {
            return response()->json(json_decode($response->json()['choices'][0]['message']['content'], true));
        }

        return response()->json(['error' => 'Error al contactar con la IA'], 500);
    }

    public function guardarEnBD(Request $request)
    {
        try {
            $microreto = Microreto::create($request->all());

            return response()->json([
                'mensaje' => 'Micro-reto archivado en la biblioteca con éxito',
                'reto' => $microreto
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al guardar en BD: ' . $e->getMessage()
            ], 500);
        }
    }
}