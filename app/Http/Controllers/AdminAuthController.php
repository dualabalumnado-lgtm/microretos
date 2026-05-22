<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();

        if ($user->is_blocked) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta está bloqueada. Contacta con el administrador.',
            ], 403);
        }

        if (!$user->isAdmin() && $user->email_verified_at === null) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Esta cuenta aún no ha sido activada. Contacta con el administrador.',
            ], 403);
        }

        // Limitar a 10 tokens concurrentes: si se supera, borrar los más antiguos
        $count = $user->tokens()->count();
        if ($count >= 10) {
            $user->tokens()->orderBy('created_at')->limit($count - 9)->delete();
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success'             => true,
            'token'               => $token,
            'role'                => $user->role,
            'name'                => $user->name,
            'centro_educativo_id' => $user->centro_educativo_id,
            'centro_nombre'       => $user->centroEducativo?->nombre,
            'message'             => 'Acceso concedido.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada.',
        ]);
    }

    public function verifyPassword(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['success' => false, 'message' => 'Contraseña incorrecta.'], 401);
        }

        return response()->json(['success' => true]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revocar el token actual y emitir uno nuevo (rota la sesión sin pedir contraseña)
        $user->currentAccessToken()->delete();
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
        ]);
    }
}