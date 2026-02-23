<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MicroretoController extends Controller
{
    public function generar(Request $request)
    {
        // Validamos los inputs (Empresa + Académico)
        $request->validate([
            'sector' => 'required|string',
            'necesidad' => 'required|string', 
            'ciclo_nombre' => 'required|string',
            'modulo_nombre' => 'required|string',
            'resultados_aprendizaje' => 'array', 
        ]);

        // Construimos el Prompt
        $raTexto = implode(", ", $request->resultados_aprendizaje);
        
        $systemPrompt = "Eres un experto en pedagogía de FP. Tu misión es crear un microreto basado en una necesidad real de empresa que sirva como 'Situación de Aprendizaje'.";
        
        $userPrompt = "Empresa del sector {$request->sector} necesita: {$request->necesidad}. 
                       El reto es para alumnos del ciclo {$request->ciclo_nombre}, módulo {$request->modulo_nombre}.
                       Debe cubrir estos Resultados de Aprendizaje: {$raTexto}.
                       Responde en JSON con: titulo, contexto_empresa, reto_tecnico, entregable_esperado, indicadores_resiliencia.";

        $response = Http::withToken(env('OPENAI_API_KEY'))
        ->post("https://api.openai.com/v1/chat/completions", [
            "model" => "gpt-4o", 
            "messages" => [
                [
                    "role" => "system",
                    "content" => $systemPrompt
                ],
                [
                    "role" => "user",
                    "content" => $userPrompt
                ]
            ],
            "response_format" => ["type" => "json_object"], // Obliga a OpenAI a devolver JSON
            "temperature" => 0.7
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // OpenAI devuelve el texto en esta ruta específica:
            $textoJson = $data['choices'][0]['message']['content'];
            
            return response()->json(json_decode($textoJson, true));
        }

        return response()->json(['error' => 'Error al conectar con OpenAI'], 500);
    }
}