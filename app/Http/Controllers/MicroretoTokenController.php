<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\Microreto;
use App\Models\MicroretoToken;

class MicroretoTokenController extends Controller
{
    /**
     * Devuelve el token activo del microreto, si existe.
     * Solo accesible por admins autenticados.
     * GET /api/microretos/{id}/token
     */
    public function get($id)
    {
        Microreto::findOrFail($id);

        // Limpia tokens expirados de este microreto antes de consultar
        MicroretoToken::where('microreto_id', $id)
                       ->where('expires_at', '<=', now())
                       ->delete();

        $token = MicroretoToken::where('microreto_id', $id)->first();

        if (! $token) {
            return response()->json(['token' => null]);
        }

        return response()->json([
            'token'      => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Crea un token nuevo para el microreto.
     * Solo se llama si no existe uno activo (el frontend lo controla).
     * Solo accesible por admins autenticados.
     * POST /api/microretos/{id}/token
     */
    public function generate($id)
    {
        Microreto::findOrFail($id);

        // Elimina cualquier token previo (activo o expirado) de este microreto
        MicroretoToken::where('microreto_id', $id)->delete();

        $token = MicroretoToken::create([
            'microreto_id' => $id,
            'token'        => Str::random(48),
            'expires_at'   => now()->addHours(48),
        ]);

        return response()->json([
            'token'      => $token->token,
            'expires_at' => $token->expires_at->toIso8601String(),
        ], 201);
    }

    /**
     * Resuelve un token público y devuelve los datos del microreto.
     * Público — sin autenticación.
     * GET /api/public/microreto/{token}
     */
    public function show($token)
    {
        $record = MicroretoToken::where('token', $token)->first();

        if (! $record || $record->isExpired()) {
            return response()->json(['error' => 'Token inválido o expirado'], 404);
        }

        $reto = Microreto::with([
            'empresa.centroEducativo',
            'empresa.familias',
        ])->findOrFail($record->microreto_id);

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

        return response()->json([
            'microreto'  => $reto,
            'expires_at' => $record->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Revoca (elimina) el token activo del microreto.
     * Solo accesible por admins autenticados.
     * DELETE /api/microretos/{id}/token
     */
    public function destroy($id)
    {
        Microreto::findOrFail($id);
        MicroretoToken::where('microreto_id', $id)->delete();

        return response()->json(['message' => 'Acceso QR revocado correctamente']);
    }
}
