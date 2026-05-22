<?php

namespace App\Http\Controllers;

use App\Models\Demo;
use Illuminate\Http\JsonResponse;

class DemoController extends Controller
{
    /**
     * GET /demos
     * Devuelve la lista de familias disponibles para el selector del frontend.
     */
    public function index(): JsonResponse
    {
        $demos = Demo::select('id', 'familia_profesional', 'etiqueta')
            ->orderBy('etiqueta')
            ->get();

        return response()->json($demos);
    }

    /**
     * GET /demos/{familia}
     * Devuelve todos los datos de una demo concreta.
     * El parámetro llega URL-encoded desde el frontend.
     */
    public function show(string $familia): JsonResponse
    {
        $demo = Demo::where('familia_profesional', urldecode($familia))->firstOrFail();

        // Añade ciclo y módulo del primer microreto asociado para que el frontend
        // pueda pre-seleccionarlos automáticamente en el paso 3.
        $primerReto = $demo->microretos()->first(['ciclo', 'modulo']);
        $demo->ciclo_nombre  = $primerReto?->ciclo  ?? null;
        $demo->modulo_nombre = $primerReto?->modulo ?? null;

        return response()->json($demo);
    }

    /**
     * GET /demos/{familia}/microretos
     * Devuelve los microretos pre-guardados asociados a una demo.
     */
    public function microretos(string $familia): JsonResponse
    {
        $demo = Demo::where('familia_profesional', urldecode($familia))->firstOrFail();

        $microretos = $demo->microretos()->get();

        return response()->json(['microretos' => $microretos]);
    }
}