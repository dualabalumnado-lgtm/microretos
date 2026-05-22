<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    // Listado de usuarios activos (no eliminados), excluyendo al propio admin
    public function index(Request $request): JsonResponse
    {
        $usuarios = User::with('centroEducativo')
            ->whereNot('id', $request->user()->id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $this->formatUsers($usuarios)]);
    }

    // Crear nueva cuenta (docente o empresa)
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()],
            'role'     => 'required|in:2,3',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => $data['password'],
            'role'              => (int) $data['role'],
            'is_blocked'        => false,
            'email_verified_at' => null, // debe ser activada por el admin
        ]);

        return response()->json([
            'success' => true,
            'data'    => $this->formatUser($user),
            'message' => 'Cuenta creada. Actívala para que pueda iniciar sesión.',
        ], 201);
    }

    // Activar cuenta (establece email_verified_at)
    public function activar(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No se puede modificar una cuenta de administrador.'], 403);
        }

        $user->update(['email_verified_at' => now()]);

        return response()->json(['success' => true, 'data' => $this->formatUser($user->fresh())]);
    }

    // Bloquear / desbloquear cuenta
    public function toggleBloquear(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No se puede bloquear una cuenta de administrador.'], 403);
        }

        $user->update(['is_blocked' => !$user->is_blocked]);

        // Revocar tokens activos si se bloquea
        if ($user->is_blocked) {
            $user->tokens()->delete();
        }

        return response()->json(['success' => true, 'data' => $this->formatUser($user->fresh())]);
    }

    // Asociar (o quitar) centro educativo a un docente
    public function asociarCentro(Request $request, User $user): JsonResponse
    {
        if (!$user->isDocente()) {
            return response()->json(['success' => false, 'message' => 'Solo los docentes pueden tener un centro asociado.'], 422);
        }

        $data = $request->validate([
            'centro_educativo_id' => 'nullable|exists:centro_educativo,id',
        ]);

        $user->update(['centro_educativo_id' => $data['centro_educativo_id']]);

        return response()->json(['success' => true, 'data' => $this->formatUser($user->fresh()->load('centroEducativo'))]);
    }

    // Editar datos de un usuario (nombre, email, rol, contraseña opcional)
    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No se puede modificar una cuenta de administrador.'], 403);
        }

        $rules = [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required|in:2,3',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', Password::min(8)->mixedCase()->numbers()];
        }

        $data = $request->validate($rules);

        $user->name  = $data['name'];
        $user->email = $data['email'];
        $user->role  = (int) $data['role'];

        if ($request->filled('password')) {
            $user->password = $data['password'];
        }

        $user->save();

        return response()->json(['success' => true, 'data' => $this->formatUser($user->fresh()->load('centroEducativo'))]);
    }

    // Soft delete — envía a la papelera
    public function destroy(User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar una cuenta de administrador.'], 403);
        }

        // Revocar todos los tokens antes de eliminar
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'Cuenta enviada a la papelera.']);
    }

    // Papelera — usuarios eliminados (soft deleted)
    public function papelera(): JsonResponse
    {
        $usuarios = User::onlyTrashed()
            ->with('centroEducativo')
            ->orderBy('deleted_at', 'desc')
            ->get();

        return response()->json(['data' => $this->formatUsers($usuarios)]);
    }

    // Restaurar desde papelera
    public function restaurar(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return response()->json(['success' => true, 'data' => $this->formatUser($user->fresh())]);
    }

    // Eliminación definitiva (solo desde papelera)
    public function destruir(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar una cuenta de administrador.'], 403);
        }

        $user->tokens()->delete();
        $user->forceDelete();

        return response()->json(['success' => true, 'message' => 'Cuenta eliminada definitivamente.']);
    }

    // ── Helpers de formato ────────────────────────────────────────────
    private function formatUsers($collection): array
    {
        return $collection->map(fn($u) => $this->formatUser($u))->values()->all();
    }

    private function formatUser(User $user): array
    {
        return [
            'id'                  => $user->id,
            'name'                => $user->name,
            'email'               => $user->email,
            'role'                => $user->role,
            'role_label'          => $user->roleName(),
            'is_blocked'          => $user->is_blocked,
            'is_active'           => $user->email_verified_at !== null,
            'centro_educativo_id' => $user->centro_educativo_id,
            'centro_nombre'       => $user->centroEducativo?->nombre,
            'created_at'          => $user->created_at?->toDateTimeString(),
            'deleted_at'          => $user->deleted_at?->toDateTimeString(),
        ];
    }
}
