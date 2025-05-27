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
        Schema::create('frequency_report_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->date('report_date');
            $table->integer('done_visits_count')->default(0);
            $table->integer('pending_visits_count')->default(0);
            $table->integer('missed_visits_count')->default(0);
            $table->integer('total_visits_count')->default(0);
            $table->decimal('achievement_percentage', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['client_id', 'report_date']);
            $table->index(['report_date']);
            $table->index(['client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frequency_report_data');
    }
};
