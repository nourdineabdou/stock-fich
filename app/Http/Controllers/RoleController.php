<?php

namespace App\Http\Controllers;

use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])->orderBy('name')->get();
        $totalPermissions = PermissionCatalog::all()->count();

        return view('roles.index', compact('roles', 'totalPermissions'));
    }

    public function create()
    {
        return $this->form(new Role());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $this->syncPermissions($role, $data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Rôle « ' . $role->name . ' » créé.');
    }

    public function edit(Role $role)
    {
        return $this->form($role);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validated($request, $role);

        if ($role->name === 'Super Admin') {
            return back()->with('info', 'Le Super Admin a toujours toutes les permissions.');
        }

        $role->update(['name' => $data['name']]);
        $this->syncPermissions($role, $data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Rôle « ' . $role->name . ' » mis à jour.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return back()->withErrors(['role' => 'Le rôle Super Admin ne peut pas être supprimé.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Ce rôle est attribué à des utilisateurs : changez-leur de rôle avant de le supprimer.']);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rôle supprimé.');
    }

    private function form(Role $role)
    {
        // Les permissions de toute nouvelle action apparaissent ici automatiquement
        \App\Support\PermissionSync::run();

        $groups = PermissionCatalog::grouped();
        $selected = old('permissions', $role->exists ? $role->permissions()->pluck('name')->all() : []);

        return view('roles.form', compact('role', 'groups', 'selected'));
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => 'nullable|array',
            'permissions.*' => ['string', Rule::in(PermissionCatalog::all()->pluck('name'))],
        ]);
    }

    private function syncPermissions(Role $role, array $names): void
    {
        $role->syncPermissions(Permission::whereIn('name', $names)->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
