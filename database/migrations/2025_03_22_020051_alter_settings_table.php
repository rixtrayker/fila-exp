<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->truncate();

        Schema::table('settings', function (Blueprint $table) {
            $table->integer('order')->default(0);
            $table->string('name')->nullable();
            $table->string('key')->unique()->change();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('key')->unique()->change();
            $table->dropColumn('order');
            $table->dropColumn('name');
            $table->dropColumn('description');
        });
    }
};
