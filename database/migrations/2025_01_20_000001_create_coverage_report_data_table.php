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
        Schema::create('coverage_report_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('report_date');
            // $table->string('area_name')->nullable();
            $table->integer('working_days')->default(0);
            $table->integer('daily_visit_target')->default(0);
            $table->integer('office_work_count')->default(0);
            $table->integer('activities_count')->default(0);
            $table->integer('actual_working_days')->default(0);
            $table->decimal('sops', 5, 2)->default(0);
            $table->integer('actual_visits')->default(0);
            $table->decimal('call_rate', 5, 2)->default(0);
            $table->integer('total_visits')->default(0);
            $table->json('metadata')->nullable(); // For additional data
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'report_date']);
            $table->index(['report_date']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coverage_report_data');
    }
};
