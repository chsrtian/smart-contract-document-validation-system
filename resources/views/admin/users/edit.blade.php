@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Edit User: {{ $user->name }}</h1>
                <p class="admin-page-subtitle">Manage user information and roles</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                Back to Users
            </a>
        </div>

        <div class="admin-content">
            <!-- Edit Basic Info -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body">
                    <h3 class="admin-section-title"><i class="fas fa-user"></i> Basic Information</h3>
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')

                        <!-- Name -->
                        <div class="admin-form-group">
                            <label for="name" class="admin-form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                                class="admin-form-input @error('name') admin-form-input-error @enderror">
                            @error('name')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="admin-form-group">
                            <label for="email" class="admin-form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                                class="admin-form-input @error('email') admin-form-input-error @enderror">
                            @error('email')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i> Update Basic Info
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Role -->
            <div class="admin-card">
                <div class="admin-card-body">
                    <h3 class="admin-section-title"><i class="fas fa-shield-alt"></i> Role Management</h3>
                    <form method="POST" action="{{ route('admin.users.assign-role', $user) }}">
                        @csrf

                        <div class="admin-form-group">
                            <label class="admin-form-label">Current Role</label>
                            <div style="margin-top: 0.5rem; margin-bottom: 1rem;">
                                @foreach($user->roles as $role)
                                    <span class="admin-role-badge admin-role-{{ $role->name }}">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @endforeach
                            </div>

                            <label for="role" class="admin-form-label">Assign New Role</label>
                            <select name="role" id="role" required class="admin-form-select">
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="admin-btn admin-btn-warning"
                                onclick="return confirm('Are you sure you want to change this user\'s role?');">
                                <i class="fas fa-exchange-alt"></i> Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection