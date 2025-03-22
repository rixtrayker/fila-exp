<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AllRolesSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seeders = [
            RolesAndPermissionsSeeder::class,
            MedicalRepRole::class,
            DistrictManagerRole::class,
            AreaManagerRole::class,
            CountryManagerRole::class,
        ];

        foreach ($seeders as $seeder) {
            $this->call($seeder);
        }

    }
}
