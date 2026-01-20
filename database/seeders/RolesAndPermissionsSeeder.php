<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Student permissions
            'view students',
            'manage students',
            'view own students',  // For parents - view their children only
            
            // Attendance permissions
            'view attendance',
            'mark attendance',
            'manage attendance',
            'view own attendance',  // For parents/students
            
            // Report permissions
            'view reports',
            'view all reports',    // Admin - all classes
            'view own reports',    // Teachers - only their classes
            
            // Billing permissions
            'view billing',
            'manage billing',
            
            // User/system management
            'manage users',
            'manage classes',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Super Admin - has all permissions via Gate::before check
        Role::firstOrCreate(['name' => 'Super Admin']);

        // Admin - full access to everything
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions([
            'view students', 'manage students',
            'view attendance', 'mark attendance', 'manage attendance',
            'view reports', 'view all reports',
            'view billing', 'manage billing',
            'manage users', 'manage classes', 'manage settings',
        ]);

        // Billing Admin - billing + read-only students
        $billingAdminRole = Role::firstOrCreate(['name' => 'Billing Admin']);
        $billingAdminRole->syncPermissions([
            'view students',
            'view attendance',
            'view billing', 'manage billing',
        ]);

        // Teacher - students, attendance for any class, reports for own classes
        $teacherRole = Role::firstOrCreate(['name' => 'Teacher']);
        $teacherRole->syncPermissions([
            'view students',
            'view attendance', 'mark attendance',
            'view reports', 'view own reports',
            'manage classes',
        ]);

        // Parent - view own children only (read-only)
        $parentRole = Role::firstOrCreate(['name' => 'Parent']);
        $parentRole->syncPermissions([
            'view own students',
            'view own attendance',
        ]);

        // Student - view own profile only
        $studentRole = Role::firstOrCreate(['name' => 'Student']);
        $studentRole->syncPermissions([
            'view own students',  // For viewing own profile
            'view own attendance',
        ]);
    }
}
