<?php

use App\Models\User;
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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->double('trasporation')->nullable();
            $table->double('lodging')->nullable();
            $table->double('mileage')->nullable();
            $table->double('meal')->nullable();
            $table->double('telephone_postage')->nullable();
            $table->double('daily_allowance')->nullable();
            $table->double('medical_expenses')->nullable();
            $table->double('others')->nullable();
            $table->string('others_description')->nullable();
            $table->date('date');
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('expenses');
    }
};
