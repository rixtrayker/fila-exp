<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ignore foriegn keys
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $seeders = [
            'RolesAndPermissionsSeeder',
            'MedicalRepRole',
            'DistrictManagerRole',
            'AreaManagerRole',
            'CountryManagerRole',
        ];

        foreach ($seeders as $seeder) {
            Artisan::call('db:seed', ['--class' => $seeder]);
        }

    }
};
