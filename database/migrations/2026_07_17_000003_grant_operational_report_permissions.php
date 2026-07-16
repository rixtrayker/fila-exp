<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const RESOURCES = [
        'visit-performance-report',
        'client-coverage-report',
    ];

    private const ROLES = [
        'medical-rep',
        'district-manager',
        'area-manager',
        'country-manager',
        'super-admin',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::RESOURCES)
            ->flatMap(fn (string $resource) => [
                Permission::firstOrCreate(['name' => "view {$resource}"]),
                Permission::firstOrCreate(['name' => "view-any {$resource}"]),
            ]);

        Role::whereIn('name', self::ROLES)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = collect(self::RESOURCES)
            ->flatMap(fn (string $resource) => [
                "view {$resource}",
                "view-any {$resource}",
            ]);
        $permissions = Permission::whereIn('name', $permissionNames)->get();

        Role::whereIn('name', self::ROLES)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
