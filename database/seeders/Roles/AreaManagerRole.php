<?php

namespace Database\Seeders\Roles;

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
        $medicalRepPermissions = Permission::where('name', 'medical-rep')->get();
        $permissions = [];
        foreach ($medicalRepPermissions as $permission) {
            $permissions[] = $permission->name;
        }

        $role = Role::where('name', 'area-manager')->first();
        $role->permissions()->attach($permissions);
    }
}

