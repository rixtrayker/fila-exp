<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $miscPermission = Permission::create(['name' => 'N/A']);

        $permissions[] = Permission::create(['name' => 'create: user']);
        $permissions[] = Permission::create(['name' => 'read: user']);
        $permissions[] = Permission::create(['name' => 'update: user']);
        $permissions[] = Permission::create(['name' => 'delete: user']);

        $permissions[] = Permission::create(['name' => 'create: role']);
        $permissions[] = Permission::create(['name' => 'read: role']);
        $permissions[] = Permission::create(['name' => 'update: role']);
        $permissions[] = Permission::create(['name' => 'delete: role']);

        $permissions[] = Permission::create(['name' => 'create: permission']);
        $permissions[] = Permission::create(['name' => 'read: permission']);
        $permissions[] = Permission::create(['name' => 'update: permission']);
        $permissions[] = Permission::create(['name' => 'delete: permission']);

        $permissions[] = Permission::create(['name' => 'read: admin']);
        $permissions[] = Permission::create(['name' => 'update: admin']);

        $userRole = Role::create(['name' => 'user'])->syncPermissions([
            $miscPermission
        ]);

        $superAdminRole = Role::create(['name' => 'super-admin'])
            ->syncPermissions(array_merge($permissions,[$miscPermission]));

        $moderatorRole = Role::create(['name' => 'moderator'])
            ->syncPermissions([
                $permissions[1],
                $permissions[5],
                $permissions[9],
                $permissions[13],
                ]
            );
        $developerRole = Role::create(['name' => 'developer'])
            ->syncPermissions([
                $permissions[13],
                ]
            );

        User::create([
            'name' => 'super admin',
            'is_admin' => 1,
            'email' => 'amr@super-admin.com',
            'email_verified_at' => now(),
            'password' => bcrypt('1234'),
        ])->assignRole($superAdminRole);

        User::create([
            'name' => 'admin',
            'email' => 'amr@admin.com',
            'email_verified_at' => now(),
            'password' => bcrypt('1234'),
        ])->assignRole($moderatorRole);

        User::create([
            'name' => 'developer',
            'email' => 'amr@developer.com',
            'email_verified_at' => now(),
            'password' => bcrypt('1234'),
        ])->assignRole($developerRole);

        for($i=0; $i<50; $i++){
            User::create([
                'name' => 'test'.$i,
                'email' => 'test'.$i.'@user.com',
                'email_verified_at' => now(),
                'password' => bcrypt('1234'),
            ])->assignRole($userRole);
        }

    }
}
