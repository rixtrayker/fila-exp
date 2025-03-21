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
            $table->integer('order')->default(0)->after('id');
            $table->string('key')->unique()->after('order');
            $table->string('type')->nullable()->before('name');
            $table->string('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('key');
            $table->dropColumn('order');
            $table->dropColumn('name');
            $table->dropColumn('description');
        });
    }
};
