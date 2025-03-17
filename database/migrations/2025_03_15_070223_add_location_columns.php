<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // add latitude and longitude columns to visits table
        Schema::table('visits', function (Blueprint $table) {
            // change lat and lng to nullable
            $table->decimal('lat', 10, 8)->nullable()->change();
            $table->decimal('lng', 11, 8)->nullable()->change();
        });

        // add latitude and longitude for clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('address');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('lat');
            $table->dropColumn('lng');
        });
    }
};
