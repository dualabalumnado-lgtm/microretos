<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $adminEmail    = config('admin.email');
        $adminPassword = config('admin.password');

        if (
            $request->email !== $adminEmail ||
            $request->password !== $adminPassword
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // Genera un token simple firmado (no requiere Sanctum ni Passport)
        $token = base64_encode(hash_hmac(
            'sha256',
            $adminEmail . now()->format('Y-m-d'),
            config('app.key')
        ));

        return response()->json([
            'success' => true,
            'token'   => $token,
            'message' => 'Acceso concedido.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Si en el futuro usas Sanctum, aquí revocarías el token
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }
}