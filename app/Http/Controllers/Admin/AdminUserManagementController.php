<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AdminUserManagementController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index(Request $request): View
    {
        $query = Admin::with('roles')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->query('is_active') !== '') {
            $query->where('is_active', (bool) $request->query('is_active'));
        }

        $admins = $query->paginate(15)->withQueryString();

        return view('admin.access.users.index', compact('admins'));
    }

    /**
     * Show form for creating a new administrator.
     */
    public function create(): View
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.access.users.create', compact('roles'));
    }

    /**
     * Store a newly created administrator.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $admin->syncRoles($validated['roles']);

        return redirect()->route('admin.admins.index')
            ->with('success', "Administrator {$admin->name} created successfully.");
    }

    /**
     * Show form for editing an administrator.
     */
    public function edit(Admin $admin): View
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.access.users.edit', compact('admin', 'roles'));
    }

    /**
     * Update an administrator's details.
     */
    public function update(Request $request, Admin $admin): RedirectResponse
    {
        $currentAdmin = auth('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admins,email,'.$admin->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        // Guard against self-deactivation
        if ($currentAdmin && (int) $currentAdmin->id === (int) $admin->id && ! $request->boolean('is_active', true)) {
            return back()->with('error', 'You cannot deactivate your own administrator account.');
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active', true),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
            // Invalidate other sessions on password change
            $data['auth_token_version'] = $admin->auth_token_version + 1;
        }

        $admin->update($data);
        $admin->syncRoles($validated['roles']);

        return redirect()->route('admin.admins.index')
            ->with('success', "Administrator {$admin->name} updated successfully.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Admin $admin): RedirectResponse
    {
        $currentAdmin = auth('admin')->user();

        if ($currentAdmin && (int) $currentAdmin->id === (int) $admin->id) {
            return back()->with('error', 'You cannot modify your own active status.');
        }

        $admin->update([
            'is_active' => ! $admin->is_active,
            'auth_token_version' => $admin->is_active ? $admin->auth_token_version + 1 : $admin->auth_token_version,
        ]);

        $status = $admin->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Administrator {$admin->name} has been {$status}.");
    }
}
