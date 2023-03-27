<?php

use App\Models\User;
use App\Models\VacationType;
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
        Schema::create('vacation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'rep_id');
            $table->foreignIdFor(User::class, 'manager_id');
            $table->foreignIdFor(VacationType::class);
            $table->boolean('approved')->default(0);
            $table->dateTime('approved_at')->nullable();
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
        Schema::dropIfExists('vacation_requests');
    }
};
