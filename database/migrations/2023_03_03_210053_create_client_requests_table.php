<?php

use App\Models\Client;
use App\Models\ClientRequestType;
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
        Schema::create('client_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Client::class);
            $table->foreignIdFor(ClientRequestType::class);
            $table->integer('request_cost')->default(0);
            $table->integer('expected_revenue')->default(0);
            $table->date('response_date')->nullable();
            $table->enum('rx_rate',['yes','no']);
            $table->enum('ordered_before',['yes','no']);
            $table->text('description')->nullable();
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
        Schema::dropIfExists('client_requests');
    }
};
