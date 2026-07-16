<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleVisitTarget;
use Illuminate\Database\Seeder;

class RoleVisitTargetsSeeder extends Seeder
{
    /**
     * Seed the medical-rep role with the current global settings values
     * (daily_am_target / daily_pm_target / daily_ph_target) so report numbers
     * do not change when the per-role matrix is introduced.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'medical-rep'],
            ['display_name' => 'Medical Rep']
        );

        foreach (array_keys(RoleVisitTarget::SETTING_KEYS) as $clientTypeId) {
            RoleVisitTarget::firstOrCreate(
                [
                    'role_id' => $role->id,
                    'client_type_id' => $clientTypeId,
                ],
                [
                    'daily_target' => RoleVisitTarget::getGlobalDailyTarget($clientTypeId),
                ]
            );
        }

        RoleVisitTarget::flushMatrixCache();
    }
}
