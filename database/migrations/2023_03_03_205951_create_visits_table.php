<?php

use App\Models\Client;
use App\Models\User;
use App\Models\CallType;
use App\Models\VisitType;
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
            $table->foreignIdFor(Client::class);
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(User::class,'second_user_id')->nullable();
            $table->date('visit_date');
            $table->date('next_visit')->nullable();
            $table->enum('status',['pending','verified','visited','cancelled'])->default('pending');
            $table->date('next_visit')->nullable();
            $table->foreignIdFor(CallType::class)->nullable();
            $table->foreignIdFor(VisitType::class)->nullable();
            $table->text('comment')->nullable();
            $table->double('lat')->nullable();
            $table->double('lng')->nullable();
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
