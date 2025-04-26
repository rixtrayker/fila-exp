<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->foreignId('country_id')->nullable();
            $table->foreign('country_id')->references('id')->on('countries');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_files');
    }
};
