<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sqlFile = database_path('sql/procedures/GetSOPsAndCallRateData.sql');

        if (! file_exists($sqlFile)) {
            throw new \Exception("SQL file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);

        if ($sql === false) {
            throw new \Exception("Failed to read SQL file: {$sqlFile}");
        }

        // Do not remove an existing procedure until the replacement file has
        // at least been read successfully.
        DB::statement('DROP PROCEDURE IF EXISTS GetSOPsAndCallRateData');
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
