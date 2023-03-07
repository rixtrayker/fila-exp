<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edit_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('editable');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('attribute')->nullable();
            $table->string('batch');
            $table->unsignedBigInteger('added_by_id');
            $table->enum('status',['pending','refused','approved'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edit_requests');
    }
};
