<?php

use App\Models\ClientRequestType;
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
            $table->integer('request_cost');
            $table->integer('expected_revenue');
            $table->date('response_date');
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
