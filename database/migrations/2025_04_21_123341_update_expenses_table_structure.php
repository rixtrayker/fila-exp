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
        Schema::table('expenses', function (Blueprint $table) {
            // Add new columns
            $table->string('from')->nullable()->after('date');
            $table->string('to')->nullable()->after('from');
            $table->text('description')->nullable()->after('to');
            $table->double('distance')->nullable()->after('description');

            // Rename columns
            $table->renameColumn('lodging', 'accommodation');

            // Drop columns
            $table->dropColumn('mileage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['from', 'to', 'description', 'distance']);

            // Rename back
            $table->renameColumn('accommodation', 'lodging');

            // Add back dropped column
            $table->double('mileage')->nullable();
        });
    }
};
