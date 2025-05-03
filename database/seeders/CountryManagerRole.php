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
        // just like district manager
        $districtManagerPermissions = Permission::where('name', 'district-manager')->get();
        $permissions = [];
        foreach ($districtManagerPermissions as $permission) {
            $permissions[] = $permission->name;
        }

        $role = Role::where('name', 'country-manager')->first();
        $role->permissions()->attach($permissions);

        $resources = [
            'official-holiday' => ['view', 'create', 'update', 'delete'],
        ];

        foreach ($resources as $resource => $permissions) {
            $permissions = [];
            foreach ($permissions as $permission) {
                $permissions[] = $resource . ' ' . $permission;
            }

            $role->permissions()->attach($permissions);
        }
    }
}
