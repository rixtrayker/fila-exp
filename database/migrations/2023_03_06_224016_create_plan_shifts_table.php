<?php

use App\Models\Client;
use App\Models\Day;
use App\Models\Plan;
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
        Schema::create('plan_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Plan::class);
            $table->enum('day',[1,2,3,4,5,6,7]);
            $table->unique(['plan_id', 'day']);
            $table->foreignIdFor(Client::class,'am_shift');
            $table->foreignIdFor(Client::class,'pm_shift');
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
        Schema::dropIfExists('plan_shifts');
    }
};
