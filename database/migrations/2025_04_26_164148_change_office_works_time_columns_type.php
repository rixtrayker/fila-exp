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
        Schema::table('office_works', function (Blueprint $table) {
            $table->time('time_from')->nullable()->change();
            $table->time('time_to')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('office_works', function (Blueprint $table) {
            $table->dateTime('time_from')->nullable()->change();
            $table->dateTime('time_to')->nullable()->change();
        });
    }
};
