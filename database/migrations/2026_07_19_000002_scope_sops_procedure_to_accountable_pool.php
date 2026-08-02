<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reinstall the stored procedure so visit counts respect each user's
     * accountable client pool: users who maintain a personal client list for
     * a client type are evaluated against that list, users without one keep
     * the area-derived pool from user_bricks_view. Previously the report
     * counted every logged visit, so clients outside the rep's own list (and
     * outside their territory entirely) still inflated SOPs and call rate.
     *
     * Mirrors AccountsCoverageReport and Client::scopeAccountablePool.
     */
    public function up(): void
    {
        $sqlFile = database_path('sql/procedures/GetSOPsAndCallRateData.sql');

        if (! file_exists($sqlFile)) {
            throw new RuntimeException("SQL file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);

        if ($sql === false) {
            throw new RuntimeException("Failed to read SQL file: {$sqlFile}");
        }

        $validationName = 'GetSOPsAndCallRateData_validation';
        $validationSql = str_replace(
            'CREATE PROCEDURE GetSOPsAndCallRateData(',
            "CREATE PROCEDURE {$validationName}(",
            $sql,
        );

        if ($validationSql === $sql) {
            throw new RuntimeException('Unable to create validation SQL for GetSOPsAndCallRateData');
        }

        DB::statement("DROP PROCEDURE IF EXISTS {$validationName}");

        try {
            // Compile the replacement under a temporary name first. A syntax
            // failure leaves the currently installed report procedure intact.
            DB::statement($validationSql);
        } finally {
            DB::statement("DROP PROCEDURE IF EXISTS {$validationName}");
        }

        DB::statement('DROP PROCEDURE IF EXISTS GetSOPsAndCallRateData');
        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'This migration is irreversible because dropping the active report procedure would break reporting.',
        );
    }
};
