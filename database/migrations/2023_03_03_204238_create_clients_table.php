<?php

use App\Models\City;
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
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('email')->nullable();
            $table->string('phone');
            $table->foreignIdFor(City::class);
            $table->string('address');
            $table->enum('grade',['A+','A','B+','B','C']);
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
