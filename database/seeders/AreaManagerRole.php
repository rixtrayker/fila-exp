<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AreaManagerRole extends Seeder
{
    public function run()
    {
        $this->createPermissions();
    }

    private function createPermissions()
    {
        $role = Role::where('name', 'area-manager')->first();

        if (!$role) {
            $this->command->error('Area Manager role not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }

        // Get medical rep role and its permissions
        $medicalRepRole = Role::where('name', 'medical-rep')->first();

        if (!$medicalRepRole) {
            $this->command->error('Medical Rep role not found. Please run MedicalRepRole seeder first.');
            return;
        }

        // Clear existing permissions first
        $role->permissions()->detach();

        $medicalRepPermissions = $medicalRepRole->permissions->pluck('name')->toArray();

        // Add additional permissions for area manager
        $additionalPermissions = [
            'item' => ['view', 'create', 'update', 'delete'],
            'bundle' => ['view', 'create', 'update', 'delete'],
            'campaign' => ['view', 'create', 'update', 'delete'],
        ];

        $allPermissions = array_merge($medicalRepPermissions, $additionalPermissions);
        $role->syncPermissions($allPermissions);
    }
}

