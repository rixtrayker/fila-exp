<?php

use Database\Seeders\RoleVisitTargetsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Invoke the idempotent data seeder directly so production migrations
        // do not depend on Artisan's interactive --force protection.
        app(RoleVisitTargetsSeeder::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
