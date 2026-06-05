<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use App\Services\AuditLogService;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(25);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',
        ]);

        $user->assignRole($request->role);

        
        AuditLogService::log('user.created', $user, null, $user->toArray(), 'info', "User created with role: {$request->role}");

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$user->name}' created successfully with role '{$request->role}'.");
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $oldData = $user->only(['name', 'email']);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $newData = $user->only(['name', 'email']);

        
        AuditLogService::log('user.updated', $user, $oldData, $newData, 'info');

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        $oldRoles = $user->getRoleNames()->toArray();
        
        $user->syncRoles([$request->role]);
        
        $newRoles = $user->getRoleNames()->toArray();

        
        AuditLogService::log('user.role_assigned', $user, ['roles' => $oldRoles], ['roles' => $newRoles], 'info', "Role changed from " . implode(', ', $oldRoles) . " to {$request->role}");

        return redirect()
            ->back()
            ->with('success', "Role '{$request->role}' assigned to '{$user->name}' successfully.");
    }

    public function deactivate(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->isInactive()) {
            return redirect()
                ->back()
                ->with('error', 'User is already inactive.');
        }

        $user->deactivate();

        
        AuditLogService::log('user.deactivated', $user, ['status' => 'active'], ['status' => 'inactive'], 'warning', "User deactivated by admin");

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been deactivated.");
    }

    public function reactivate(User $user)
    {
        if ($user->isActive()) {
            return redirect()
                ->back()
                ->with('error', 'User is already active.');
        }

        $user->reactivate();

        
        AuditLogService::log('user.reactivated', $user, ['status' => 'inactive'], ['status' => 'active'], 'info', "User reactivated by admin");

        return redirect()
            ->back()
            ->with('success', "User '{$user->name}' has been reactivated.");
    }
}