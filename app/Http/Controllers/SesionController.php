<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSesionLoteRequest;
use App\Http\Requests\StoreSesionRequest;
use App\Models\Microreto;
use App\Models\Sesion;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user  = $request->user();
        $query = Sesion::with([
            'docente:id,name',
            'microreto.empresa.centroEducativo',
            'microreto.empresa.familias',
        ])->orderBy('created_at', 'desc');

        if ($user?->isDocente()) {
            // Docente: solo sus propias sesiones
            $query->where('user_id', $user->id);
        } elseif ($user?->isAdmin() && $user->centro_educativo_id) {
            // Admin: todas las sesiones de su centro educativo
            $nombreCentro = $user->centroEducativo?->nombre;
            if ($nombreCentro) {
                $query->where('centro_educativo', $nombreCentro);
            }
        }
        // Superadmin: sin filtro, ve todo

        return $query->get();
    }

    public function store(StoreSesionRequest $request)
    {
        $validated = $request->validated();
        $validated['microreto_id'] = $this->resolverMicroretoId($validated['microreto_id'] ?? null);
        $validated['user_id']      = $request->user()->id;

        return response()->json(Sesion::create($validated), 201);
    }

    public function storeLote(StoreSesionLoteRequest $request)
    {
        $userId = $request->user()->id;
        foreach ($request->validated()['sesiones'] as $s) {
            $s['microreto_id'] = $this->resolverMicroretoId($s['microreto_id'] ?? null);
            $s['user_id']      = $userId;
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
