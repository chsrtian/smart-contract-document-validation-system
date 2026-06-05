@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">User Management</h1>
                <p class="admin-page-subtitle">Manage system users and their roles</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i>
                Create New User
            </a>
        </div>

        <div class="admin-content">
            <!-- Filter Panel -->
            <div class="admin-filter-panel">
                <form method="GET" action="{{ route('admin.users.index') }}">
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                        <!-- Search -->
                        <div>
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                placeholder="Name or email..." class="admin-form-input">
                        </div>

                        <!-- Role Filter -->
                        <div>
                            <label for="role">Role</label>
                            <select name="role" id="role" class="admin-form-select">
                                <option value="">All Roles</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status">Status</label>
                            <select name="status" id="status" class="admin-form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div style="display: flex; align-items: flex-end; gap: 0.5rem;">
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-table-card">
                <div class="admin-table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td class="admin-font-semibold">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="admin-role-badge admin-role-{{ $role->name }}">
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($user->isActive())
                                            <span class="admin-badge admin-badge-success">
                                                <i class="fas fa-check-circle" style="margin-right:4px"></i> Active
                                            </span>
                                        @else
                                            <span class="admin-badge admin-badge-danger">
                                                <i class="fas fa-times-circle" style="margin-right:4px"></i> Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="admin-btn-group">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="admin-link admin-link-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            
                                            @if($user->id !== auth()->id())
                                                @if($user->isActive())
                                                    <form action="{{ route('admin.users.deactivate', $user) }}" method="POST" style="display: inline;" 
                                                        onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                                                        @csrf
                                                        <button type="submit" class="admin-link admin-link-danger" style="background: none; border: none; cursor: pointer;">
                                                            <i class="fas fa-ban"></i> Deactivate
                                                        </button>
                                                    </form>
                                                @else
                                                    <form action="{{ route('admin.users.reactivate', $user) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="admin-link admin-link-success" style="background: none; border: none; cursor: pointer;">
                                                            <i class="fas fa-check"></i> Reactivate
                                                        </button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem;">
                                        <div class="admin-empty-state">
                                            <i class="fas fa-users" style="font-size: 2rem; color: var(--admin-text-light); margin-bottom: 0.5rem;"></i>
                                            <p>No users found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="admin-pagination" style="padding: 1rem 1.5rem;">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection