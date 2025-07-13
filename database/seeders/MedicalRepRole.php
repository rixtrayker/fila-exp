<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MedicalRepRole extends Seeder
{
    public function run()
    {
        $this->createPermissions();
    }

    private function createPermissions()
    {
        $resources = [
            'governorate' => ['view', 'create', 'update', 'delete'],
            'office-work' => ['view', 'create', 'update', 'delete'],
            'product-category' => ['view', 'create', 'update', 'delete'],
            'user' => ['view', 'create', 'update', 'delete'],
            'vacation' => ['view', 'create', 'update', 'delete'],
            'visit' => ['view', 'create', 'update', 'delete'],
            'edit-request' => ['view', 'create', 'update', 'delete'],
            'expenses' => ['view', 'create', 'update', 'delete'],
            'frequency-report' => ['view-any', 'view'],
            'message' => ['view', 'create', 'update', 'delete'],
            'official-holiday' => ['view'],
            'order-report' => ['view'],
            'order' => ['view', 'create', 'update', 'delete'],
            'plan' => ['view', 'create', 'update', 'delete'],
            'product' => ['view', 'create', 'update', 'delete'],
            'sales-report' => ['view'],
            'vacations-report' => ['view'],
            'visit-report' => ['view'],
            'coverage-report' => ['view-any', 'view'],
            'activity' => ['view', 'create', 'update', 'delete'],
            'template-file' => ['view', 'view-any'],
        ];

        $role = Role::where('name', 'medical-rep')->first();

        if (!$role) {
            $this->command->error('Medical Rep role not found. Please run RolesAndPermissionsSeeder first.');
            return;
        }

        $permissionsToAttach = [];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                $permissionName = $action . ' ' . $resource;
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $permissionsToAttach[] = $permission;
            }
        }

        $role->syncPermissions($permissionsToAttach);
    }
}
