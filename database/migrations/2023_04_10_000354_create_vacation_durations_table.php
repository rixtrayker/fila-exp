<?php

use App\Models\VacationRequest;
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
        Schema::create('vacation_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(VacationRequest::class);
            $table->enum('start_shift',['AM','PM']);
            $table->enum('end_shift',['AM','PM']);
            $table->date('start');
            $table->date('end');
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
        Schema::dropIfExists('vacation_durations');
    }
};
