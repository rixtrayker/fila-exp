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

        $permissions = [];
        $models = ['CallType','City','Client','ClientRequest','ClientRequestType','ClientType','Company','CompanyBranch','EditRequest','Expenses','Governorate','Hospital','Message','Order','OrderProduct','Permission','Plan','PlanShift','Product','ProductCategory','ProductVisit','Region','Role','Speciality','User','VacationRequest','VacationType','Visit','VisitType'];

        //admin
        foreach($models as $model){
            $permissions[] = Permission::create(['name' => 'view-any ' . $model]);
            $permissions[] = Permission::create(['name' => 'view ' . $model]);
            $permissions[] = Permission::create(['name' => 'create ' . $model]);
            $permissions[] = Permission::create(['name' => 'update ' . $model]);
            $permissions[] = Permission::create(['name' => 'delete ' . $model]);
            $permissions[] = Permission::create(['name' => 'restore ' . $model]);
            $permissions[] = Permission::create(['name' => 'force-delete ' . $model]);
        }

        $superAdminRole = Role::create(['name' => 'super-admin'])
            ->syncPermissions(array_merge($permissions,[$miscPermission]));


        // $permissions[] = Permission::create(['name' => 'replicate User']);
        // $permissions[] = Permission::create(['name' => 'reorder user']);


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

    // public function getModels($path = app_path() . '/Models'){
    //     $out = [];
    //     $results = scandir($path);
    //     foreach ($results as $result) {
    //         if ($result === '.' or $result === '..') continue;
    //         $filename = $path . '/' . $result;
    //         if (is_dir($filename)) {
    //             $out = array_merge($out, getModels($filename));
    //         }else{
    //             $out[] = substr($filename,0,-4);
    //         }
    //     }
    //     return $out;
    // }
}
