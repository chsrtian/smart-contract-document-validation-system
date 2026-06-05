<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create supervisor role
        $supervisor = Role::firstOrCreate(
            ['name' => 'supervisor', 'guard_name' => 'web']
        );

        // Create permissions for correction workflow
        $permissions = [
            'view_correction_requests',
            'approve_correction_requests',
            'reject_correction_requests',
            'view_correction_audit_log',
            'view_documents',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Assign permissions to supervisor role
        $supervisor->syncPermissions($permissions);

        // Also ensure staff has permission to create correction requests
        $staffPermissions = [
            'create_correction_requests',
            'view_own_correction_requests',
            'view_documents',
        ];

        foreach ($staffPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->givePermissionTo($staffPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        $permissions = [
            'view_correction_requests',
            'approve_correction_requests',
            'reject_correction_requests',
            'view_correction_audit_log',
            'create_correction_requests',
            'view_own_correction_requests',
        ];

        foreach ($permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }

        // Remove supervisor role
        Role::where('name', 'supervisor')->delete();
    }
};