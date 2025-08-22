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