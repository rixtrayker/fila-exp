<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Models that require standard CRUD permissions
     */
    private array $standardModels = [
        'activity',
        'area',
        'brick',
        'bundle',
        'business-order',
        'call-type',
        'campaign',
        'city',
        'client',
        'client-request',
        'client-request-type',
        'client-type',
        'company',
        'company-branch',
        'country',
        'daily-visit',
        'edit-request',
        'expenses',
        'feature',
        'governorate',
        'hospital',
        'item',
        'message',
        'office-work',
        'official-holiday',
        'order',
        'order-product',
        'permission',
        'plan',
        'plan-shift',
        'product',
        'product-category',
        'product-visit',
        'region',
        'role',
        'setting',
        'speciality',
        'template-file',
        'user',
        'vacation',
        'vacation-duration',
        'vacation-request',
        'vacation-type',
        'visit',
    ];

     /**
     * Report models that only need view permission
     */
    private array $reportModels = [
        'coverage-report',
        'expenses-report',
        'frequency-report',
        'order-report',
        'sales-report',
        'vacations-report',
        'visit-report',
    ];

    /**
     * Models that require approval permissions
     */
    private array $approvalModels = [
        'client-request',
        'order',
        'plan',
        'vacation-request',
        'expenses',
    ];

    /**
     * Standard CRUD actions for models
     */
    private array $standardActions = [
        'view-any', 'view', 'create', 'update', 'delete', 'restore', 'force-delete'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $allPermissions = $this->createPermissions();

        // Create misc permission
        $miscPermission = $this->createMiscPermission();

        // Create roles
        $this->createRoles($allPermissions, $miscPermission);

        // Create users
        $this->createUsers();
    }

    /**
     * Create all required permissions
     *
     * @return Collection
     */
    private function createPermissions(): Collection
    {
        $permissions = collect();

        // Create standard CRUD permissions for regular models
        foreach ($this->standardModels as $model) {
            foreach ($this->standardActions as $action) {
                $permissionName = "{$action} {$model}";
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $permissions->push($permission);
            }
        }

        // Create view-only permissions for report models
        foreach ($this->reportModels as $reportModel) {
            $permissionName = "view-any {$reportModel}";
            $permissionNameView = "view {$reportModel}";
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissionView = Permission::firstOrCreate(['name' => $permissionNameView]);
            $permissions->push($permission);
            $permissions->push($permissionView);
        }

        // Create approve permissions for models that need approval
        foreach ($this->approvalModels as $model) {
            $permissionName = "approve {$model}";
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $permissions->push($permission);
        }

        return $permissions;
    }

    /**
     * Create the miscellaneous permission
     *
     * @return Permission
     */
    private function createMiscPermission(): Permission
    {
        return Permission::firstOrCreate(['name' => 'N/A']);
    }

    /**
     * Create roles with their permissions
     *
     * @param Collection $allPermissions
     * @param Permission $miscPermission
     * @return void
     */
    private function createRoles(Collection $allPermissions, Permission $miscPermission): void
    {
        // User role - basic access only
        Role::firstOrCreate(['name' => 'user', 'display_name' => 'User'])
            ->syncPermissions([$miscPermission]);

        // Super Admin role - all permissions
        $superAdminPermissions = $allPermissions->merge([$miscPermission]);
        Role::firstOrCreate(['name' => 'super-admin'], ['display_name' => 'Super Admin'])
            ->syncPermissions($superAdminPermissions);

        // Moderator role - limited permissions
        $moderatorPermissions = $this->getModeratorPermissions($allPermissions);
        Role::firstOrCreate(['name' => 'moderator', 'display_name' => 'Moderator'])
            ->syncPermissions($moderatorPermissions);

        // Developer role - specific permissions
        $developerPermissions = $this->getDeveloperPermissions($allPermissions);
        Role::firstOrCreate(['name' => 'developer', 'display_name' => 'Developer'])
            ->syncPermissions($developerPermissions);

        Role::firstOrCreate(['name' => 'medical-rep', 'display_name' => 'Medical Rep']);
        Role::firstOrCreate(['name' => 'district-manager', 'display_name' => 'District Manager']);
        Role::firstOrCreate(['name' => 'area-manager', 'display_name' => 'Area Manager']);
        Role::firstOrCreate(['name' => 'country-manager', 'display_name' => 'Country Manager']);
        Role::firstOrCreate(['name' => 'account-manager', 'display_name' => 'Account Manager']);
        Role::firstOrCreate(['name' => 'account', 'display_name' => 'Account']);
        Role::firstOrCreate(['name' => 'accountant', 'display_name' => 'Accountant']);
    }

    /**
     * Get permissions for moderator role
     *
     * @param Collection $allPermissions
     * @return Collection
     */
    private function getModeratorPermissions(Collection $allPermissions): Collection
    {
        return $allPermissions->filter(function ($permission) {
            // Allow viewing any model, viewing clients, updating orders, and viewing reports
            return str_starts_with($permission->name, 'view-any') ||
                   $permission->name === 'view Client' ||
                   $permission->name === 'update Order' ||
                   (str_starts_with($permission->name, 'view') &&
                    str_contains($permission->name, 'Report'));
        });
    }

    /**
     * Get permissions for developer role
     *
     * @param Collection $allPermissions
     * @return Collection
     */
    private function getDeveloperPermissions(Collection $allPermissions): Collection
    {
        return $allPermissions->filter(function ($permission) {
            // Allow updating orders and viewing reports
            return $permission->name === 'update Order' ||
                   (str_starts_with($permission->name, 'view') &&
                    str_contains($permission->name, 'Report'));
        });
    }

    /**
     * Create default users for each role
     *
     * @return void
     */
    private function createUsers(): void
    {
        // Create admin user
        $this->createUserWithRole(
            'super admin',
            'admin@admin.com',
            'super-admin'
        );


        // Create developer user
        $this->createUserWithRole(
            'developer',
            'developer@admin.com',
            'developer'
        );

        // Create medical rep user
        $this->createUserWithRole(
            'medical-rep',
            'medical-rep@admin.com',
            'medical-rep'
        );

        // Create accountant user
        $this->createUserWithRole(
            'accountant',
            'accountant@admin.com',
            'accountant'
        );

        // Create test users
        for ($i = 0; $i < 50; $i++) {
            $this->createUserWithRole(
                'test' . $i,
                'test' . $i . '@user.com',
                'user'
            );
        }
    }

    /**
     * Create a user with the specified role
     *
     * @param string $name
     * @param string $email
     * @param string $roleName
     * @param bool $isAdmin
     * @return void
     */
    private function createUserWithRole(
        string $name,
        string $email,
        string $roleName,
    ): void {
        $userData = [
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt('1234'),
        ];


        $user = User::firstOrCreate(
            ['email' => $email],
            $userData
        );

        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->syncRoles([$role]);
        }
    }
}
