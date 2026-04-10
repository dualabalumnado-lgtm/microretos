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

        return response()->json($demo);
    }
}