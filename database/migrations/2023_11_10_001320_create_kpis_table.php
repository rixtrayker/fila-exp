<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();

            $table->string('key')->index(); // A key to identify the kpi and group them
            $table->foreignIdFor(User::class);
            // store any possible kind of value
            $table->decimal('number_value', 12, 2)->nullable();

            $table->string('string_value')->nullable();

            $table->bigInteger('money_value')->default(0);
            $table->string('money_currency')->nullable();

            $table->json('json_value')->nullable();

            $table->text('description')->nullable(); // store any comment
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }
};
