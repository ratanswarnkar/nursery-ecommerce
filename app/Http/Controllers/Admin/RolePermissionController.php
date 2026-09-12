<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    /**
     * Display a listing of roles and permission matrix overview.
     */
    public function index(): View
    {
        $roles = Role::where('guard_name', 'admin')
            ->withCount(['permissions', 'users'])
            ->get();

        $permissions = Permission::where('guard_name', 'admin')->get();

        // Categorize permissions by domain prefix (e.g., 'products.view' -> 'products')
        $permissionGroups = $permissions->groupBy(function ($perm) {
            $parts = explode('.', $perm->name);

            return $parts[0] ?? 'general';
        });

        return view('admin.access.roles.index', compact('roles', 'permissionGroups'));
    }

    /**
     * Display the specified role with its granted permissions and assigned administrators.
     */
    public function show(Role $role): View
    {
        $role->load('permissions');
        $admins = Admin::role($role->name, 'admin')->get();

        $allPermissions = Permission::where('guard_name', 'admin')->get();
        $permissionGroups = $allPermissions->groupBy(function ($perm) {
            $parts = explode('.', $perm->name);

            return $parts[0] ?? 'general';
        });

        $rolePermissionNames = $role->permissions->pluck('name')->flip()->toArray();

        return view('admin.access.roles.show', compact('role', 'admins', 'permissionGroups', 'rolePermissionNames'));
    }
}
