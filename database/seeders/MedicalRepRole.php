<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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
            'product-category' => ['view', 'create', 'update', 'delete'],
            'user' => ['view', 'create', 'update', 'delete'],
            'vacation' => ['view', 'create', 'update', 'delete'],
            'visit' => ['view', 'create', 'update', 'delete'],
            'edit-request' => ['view', 'create', 'update', 'delete'],
            'expenses' => ['view', 'create', 'update', 'delete'],
            'frequency-report' => ['view'],
            'message' => ['view', 'create', 'update', 'delete'],
            'order-report' => ['view'],
            'order' => ['view', 'create', 'update', 'delete'],
            'plan' => ['view', 'create', 'update', 'delete'],
            'product' => ['view', 'create', 'update', 'delete'],
            'sales-report' => ['view'],
            'vacations-report' => ['view'],
            'visit-report' => ['view'],
            'coverage-report' => ['view'],
        ];

        $role = Role::where('name', 'medical-rep')->first();

        foreach ($resources as $resource => $permissions)
        {
            $permissions = [];
            foreach ($permissions as $permission) {
                $permissions[] = $resource . ' ' . $permission;
            }

            $role->permissions()->attach($permissions);
        }
    }
}
