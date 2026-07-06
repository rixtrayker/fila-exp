<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reinstall the stored procedure so the daily target CASE matches
     * App\Models\ClientType (PM = 1, PH = 2, AM = 3). The previous version
     * applied the AM target to PM and the PH target to AM.
     */
    public function up(): void
    {
        // Drop the procedure if it exists
        DB::statement('DROP PROCEDURE IF EXISTS GetSOPsAndCallRateData');

        // Create the stored procedure from SQL file
        $sqlFile = database_path('sql/procedures/GetSOPsAndCallRateData.sql');

        if (!file_exists($sqlFile)) {
            throw new \Exception("SQL file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);

        if ($sql === false) {
            throw new \Exception("Failed to read SQL file: {$sqlFile}");
        }

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP PROCEDURE IF EXISTS GetSOPsAndCallRateData');
    }
};
