<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ApiResponse;

    /** GET /api/v1/auth/users */
    public function index(Request $request)
    {
        $query = User::query()->with('roles:id,code,libelle');

        if ($s = $request->query('search')) {
            $query->where(fn ($q) => $q->where('nom', 'ilike', "%$s%")
                ->orWhere('prenom', 'ilike', "%$s%")
                ->orWhere('email', 'ilike', "%$s%"));
        }
        if ($structure = $request->query('structure_id')) {
            $query->where('structure_id', $structure);
        }
        if ($statut = $request->query('statut')) {
            $query->where('statut', $statut);
        }

        $users = $query->latest()->paginate(min((int) $request->query('per_page', 20), 100));
        return $this->ok($users->items(), 200, [
            'total' => $users->total(),
            'page' => $users->currentPage(),
            'per_page' => $users->perPage(),
        ]);
    }

    /** POST /api/v1/auth/users */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'structure_id' => ['nullable', 'uuid'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
        ]);

        $password = Str::password(14, true, true, false, false);
        $user = User::create([
            ...$data,
            'mot_de_passe_hash' => Hash::make($password),
            'must_change_password_at' => now(),
        ]);
        if (! empty($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return $this->created([
            'user' => $user->load('roles:id,code,libelle'),
            'temporary_password' => $password, // a transmettre a l'utilisateur (canal securise)
        ]);
    }

    /** GET /api/v1/auth/users/{id} */
    public function show(string $id)
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        return $this->ok($user);
    }

    /** PUT /api/v1/auth/users/{id} */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'nom' => ['sometimes', 'string', 'max:100'],
            'prenom' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'unique:users,email,'.$id],
            'structure_id' => ['nullable', 'uuid'],
            'statut' => ['sometimes', 'in:actif,suspendu,desactive'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        if (! empty($data['password'])) {
            $data['mot_de_passe_hash'] = Hash::make($data['password']);
            unset($data['password'], $data['password_confirmation']);
        }
        $user->update(collect($data)->except('role_ids')->toArray());
        if (array_key_exists('role_ids', $data)) {
            $user->roles()->sync($data['role_ids'] ?? []);
        }
        return $this->ok($user->load('roles:id,code,libelle'));
    }

    /** DELETE /api/v1/auth/users/{id} (soft delete) */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return $this->ok(['message' => 'Utilisateur supprime.']);
    }
}
