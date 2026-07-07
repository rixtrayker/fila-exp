<?php

use Database\Seeders\RoleVisitTargetsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', ['--class' => RoleVisitTargetsSeeder::class]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
