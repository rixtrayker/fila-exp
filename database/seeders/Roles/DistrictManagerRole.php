<?php

namespace Database\Seeders\Roles;

use Althinect\FilamentSpatieRolesPermissions\Commands\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DistrictManagerRole extends Seeder
{
    public function run()
    {
        $this->createPermissions();
    }

    private function createPermissions()
    {
        // same as medical rep Permissions
        // in addition to the following resources
        // get medical rep permissions
        $medicalRepPermissions = Permission::where('name', 'medical-rep')->get();
        $permissions = [];
        foreach ($medicalRepPermissions as $permission) {
            $permissions[] = $permission->name;
        }

        $resources = [
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
            'visit-type' => ['view', 'create', 'update', 'delete'],
        ];


        $role = Role::where('name', 'district-manager')->first();
        $role->permissions()->attach($permissions);

        foreach ($resources as $resource => $permissions) {
            $permissions = [];
            foreach ($permissions as $permission) {
                $permissions[] = $resource . ' ' . $permission;
            }

            $role->permissions()->attach($permissions);
        }
    }
}
