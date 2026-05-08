<?php

namespace App\Http\Controllers;

use App\Models\Microreto;
use App\Models\Sesion;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function index()
    {
        return Sesion::with([
            'microreto.empresa.centroEducativo',
            'microreto.empresa.familias',
        ])->orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'microreto_titulo' => 'required|string|max:500',
            'fecha'            => 'required|date',
            'microreto_id'     => 'nullable',
            'centro_educativo' => 'nullable|string|max:255',
            'ciclo_formativo'  => 'nullable|string|max:255',
            'curso'            => 'nullable|string|max:10',
            'grupo'            => 'nullable|string|max:10',
            'num_alumnos'      => 'nullable|integer|min:1|max:999',
            'notas'            => 'nullable|string|max:5000',
        ]);

        $validated['microreto_id'] = $this->resolverMicroretoId($validated['microreto_id'] ?? null);

        return response()->json(Sesion::create($validated), 201);
    }

    public function storeLote(Request $request)
    {
        $data = $request->validate([
            'sesiones'                    => 'required|array',
            'sesiones.*.microreto_titulo' => 'required|string|max:500',
            'sesiones.*.fecha'            => 'required|date',
            'sesiones.*.microreto_id'     => 'nullable',
            'sesiones.*.centro_educativo' => 'nullable|string|max:255',
            'sesiones.*.ciclo_formativo'  => 'nullable|string|max:255',
            'sesiones.*.curso'            => 'nullable|string|max:10',
            'sesiones.*.grupo'            => 'nullable|string|max:10',
            'sesiones.*.num_alumnos'      => 'nullable|integer|min:1|max:999',
            'sesiones.*.notas'            => 'nullable|string|max:5000',
        ]);

        foreach ($data['sesiones'] as $s) {
            $s['microreto_id'] = $this->resolverMicroretoId($s['microreto_id'] ?? null);
            Sesion::create($s);
        }

        return response()->noContent();
    }

    public function show($id)
    {
        return Sesion::findOrFail($id);
    }

    public function destroy($id)
    {
        $sesion = Sesion::findOrFail($id);
        $sesion->delete();
        return response()->noContent();
    }

    // Acepta tanto UUID string (migración desde localStorage) como ID entero
    private function resolverMicroretoId(mixed $value): ?int
    {
        if (!$value) return null;
        if (is_numeric($value)) return (int) $value;
        return Microreto::where('uuid', $value)->value('id');
    }
}
