<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DistrictManagerRole extends Seeder
{
    public function run()
    {
        $this->createPermissions();
    }

    private function createPermissions()
    {
        $role = Role::where('name', 'district-manager')->first();

        if (!$role) {
            $this->command->error('District Manager role not found. Please run RolesAndPermissionsSeeder first.');
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

        $permissionsToAttach = $medicalRepRole->permissions->pluck('name')->toArray();

        // Add additional permissions for district manager
        $additionalResources = [
            'call-type' => ['view', 'create', 'update', 'delete'],
            'city' => ['view', 'create', 'update', 'delete'],
            'client-request-type' => ['view', 'create', 'update', 'delete'],
            'client-type' => ['view', 'create', 'update', 'delete'],
            'country' => ['view', 'create', 'update', 'delete'],
            'edit-request' => ['view', 'create', 'update', 'delete'],
            'expenses' => ['view', 'create', 'update', 'delete'],
            'message' => ['view', 'create', 'update', 'delete'],
            'product-category' => ['view', 'create', 'update', 'delete'],
            'region' => ['view', 'create', 'update', 'delete'],
            'user' => ['view', 'create', 'update', 'delete'],
            'vacation-type' => ['view', 'create', 'update', 'delete'],
        ];

        foreach ($additionalResources as $resource => $actions) {
            foreach ($actions as $action) {
                $permissionName = $action . ' ' . $resource;
                Permission::firstOrCreate(['name' => $permissionName]);
                $permissionsToAttach[] = $permissionName;
            }
        }

        $role->syncPermissions(array_unique($permissionsToAttach));
    }
}
