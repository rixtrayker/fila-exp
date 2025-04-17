<?php

use App\Models\Client;
use App\Models\User;
use App\Models\CallType;
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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(User::class,'second_user_id')->nullable();
            $table->foreignIdFor(Plan::class);
            $table->foreignIdFor(Client::class);
            $table->date('visit_date');
            $table->date('next_visit')->nullable();
            $table->enum('status',['pending','verified','visited','cancelled','planned'])->default('pending');
            $table->foreignIdFor(CallType::class);
            $table->string('place')->nullable();
            $table->integer('atendees_number')->nullable();
            $table->text('comment')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
            $table->index('status');
            $table->softDeletes();
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
        Schema::dropIfExists('visits');
    }
};
