<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CountryManagerRole extends Seeder
{
    public function run()
    {
        $this->createPermissions();
    }

    private function createPermissions()
    {
        $role = Role::where('name', 'country-manager')->first();

        if (!$role) {
            $this->command->error('Country Manager role not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }

        // Get district manager role and its permissions
        $districtManagerRole = Role::where('name', 'district-manager')->first();

        if (!$districtManagerRole) {
            $this->command->error('District Manager role not found. Please run DistrictManagerRole seeder first.');
            return;
        }

        // Clear existing permissions first
        $role->permissions()->detach();

        $permissionsToAttach = $districtManagerRole->permissions->pluck('name')->toArray();

        // Add additional permissions for country manager
        $additionalResources = [
            'official-holiday' => ['view', 'create', 'update', 'delete'],
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
