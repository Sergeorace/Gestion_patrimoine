<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->ok(Role::with('permissions:id,code,module,action')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:roles,code'],
            'libelle' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);
        $role = Role::create($data);
        if (! empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }
        return $this->created($role->load('permissions'));
    }

    public function update(Request $request, string $id)
    {
        $role = Role::findOrFail($id);
        $data = $request->validate([
            'libelle' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);
        $role->update($data);
        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids'] ?? []);
        }
        return $this->ok($role->load('permissions'));
    }

    public function permissions()
    {
        return $this->ok(Permission::orderBy('module')->orderBy('action')->get());
    }
}
