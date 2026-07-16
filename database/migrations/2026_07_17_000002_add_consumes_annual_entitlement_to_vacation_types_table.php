<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacation_types', function (Blueprint $table) {
            $table->boolean('consumes_annual_entitlement')
                ->default(true)
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('vacation_types', function (Blueprint $table) {
            $table->dropColumn('consumes_annual_entitlement');
        });
    }
};
