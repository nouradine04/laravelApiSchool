<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Créer les permissions
        $permissions = [
            'manage-students',
            'manage-teachers',
            'manage-classes',
            'manage-subjects',
            'manage-grades',
            'generate-reports',
            'view-dashboard',
            'add-grades',
            'view-own-grades',
            'view-children-grades',
            'download-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'manage-students',
            'manage-teachers',
            'manage-classes',
            'manage-subjects',
            'manage-grades',
            'generate-reports',
            'view-dashboard',
            'download-reports',
        ]);

        $teacherRole = Role::create(['name' => 'teacher']);
        $teacherRole->givePermissionTo([
            'add-grades',
            'view-dashboard',
        ]);

        $studentRole = Role::create(['name' => 'student']);
        $studentRole->givePermissionTo([
            'view-own-grades',
            'download-reports',
        ]);

        $parentRole = Role::create(['name' => 'parent']);
        $parentRole->givePermissionTo([
            'view-children-grades',
            'download-reports',
        ]);
    }
}
