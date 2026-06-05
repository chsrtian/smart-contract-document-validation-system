<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SupervisorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure supervisor role exists
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

        // Create a default supervisor account
        $supervisor = User::firstOrCreate(
            ['email' => 'supervisor@lgu-magallanes.gov.ph'],
            [
                'name' => 'LCRO Supervisor',
                'password' => Hash::make('supervisor123'), // Change in production!
            ]
        );

        // Assign supervisor role
        $supervisor->assignRole('supervisor');

        $this->command->info('Supervisor account created:');
        $this->command->info('Email: supervisor@lgu-magallanes.gov.ph');
        $this->command->info('Password: supervisor123');
        $this->command->warn('⚠️  Please change the password immediately in production!');
    }
}