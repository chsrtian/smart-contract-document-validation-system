@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Create New User</h1>
                <p class="admin-page-subtitle">Add a new user to the system</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                Back to Users
            </a>
        </div>

        <div class="admin-content">
            <div class="admin-card">
                <div class="admin-card-body">
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <!-- Name -->
                        <div class="admin-form-group">
                            <label for="name" class="admin-form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="admin-form-input @error('name') admin-form-input-error @enderror">
                            @error('name')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="admin-form-group">
                            <label for="email" class="admin-form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                class="admin-form-input @error('email') admin-form-input-error @enderror">
                            @error('email')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="admin-form-group">
                            <label for="password" class="admin-form-label">Password</label>
                            <input type="password" name="password" id="password" required
                                class="admin-form-input @error('password') admin-form-input-error @enderror">
                            @error('password')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="admin-form-group">
                            <label for="password_confirmation" class="admin-form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="admin-form-input">
                        </div>

                        <!-- Role -->
                        <div class="admin-form-group" style="margin-bottom: 1.5rem;">
                            <label for="role" class="admin-form-label">Role</label>
                            <select name="role" id="role" required
                                class="admin-form-select @error('role') admin-form-input-error @enderror">
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="admin-form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                            <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-plus"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection