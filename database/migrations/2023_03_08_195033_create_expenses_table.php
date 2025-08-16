<?php

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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->double('transportation')->nullable();
            $table->double('accommodation')->nullable();
            $table->double('meal')->nullable();
            $table->double('telephone_postage')->nullable();
            $table->double('daily_allowance')->nullable();
            $table->double('medical_expenses')->nullable();
            $table->double('others')->nullable();
            $table->double('total')->nullable();
            $table->string('others_description')->nullable();
            $table->date('date');
            $table->text('comment')->nullable();

            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->text('description')->nullable();
            $table->double('distance')->nullable();

            // Add approval and payment columns (0: pending, 1: approved, 2: rejected)  (0: not paid, 1: paid)
            $table->integer('approved')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users');

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
        Schema::dropIfExists('expenses');
    }
};
