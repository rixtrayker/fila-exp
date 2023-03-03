<?php

use App\Models\ClientType;
use App\Models\Speciality;
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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('email');
            $table->string('phone');
            $table->string('address');
            $table->enum('grade',['A','B','C','N','PH']);
            $table->enum('shift',['AM','PM']);
            $table->foreignIdFor(ClientType::class);
            $table->foreignIdFor(Speciality::class);
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
        Schema::dropIfExists('clients');
    }
};
