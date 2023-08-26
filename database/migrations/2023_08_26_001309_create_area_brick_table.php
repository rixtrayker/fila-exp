<?php

use App\Models\Area;
use App\Models\Brick;
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
        Schema::create('area_brick', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Area::class);
            $table->foreignIdFor(Brick::class);
            $table->unique(['area_id', 'brick_id']);
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
        Schema::dropIfExists('area_brick');
    }
};
