<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view samples-report',
        'view-any samples-report',
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

        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name]));

        Role::whereIn('name', self::ROLES)
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = Permission::whereIn('name', self::PERMISSIONS)->get();

        Role::whereIn('name', self::ROLES)
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
