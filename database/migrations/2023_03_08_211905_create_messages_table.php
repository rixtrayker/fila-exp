<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->string('title')->nullable();
            $table->text('message');
            $table->text('files')->nullable();
            $table->timestamps();
        });

        Schema::create('message_user', function (Blueprint $table) {
            $table->id();
            $table->boolean('read')->default(0);
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Message::class);
            $table->timestamps();
        });

        Schema::create('message_role', function (Blueprint $table) {
            $table->id();
            $table->boolean('read')->default(0);
            $table->foreignIdFor(Role::class);
            $table->foreignIdFor(Message::class);
            $table->timestamps();
        });

        // Schema::create('messagables', function (Blueprint $table) {
        //     $table->id();
        //     $table->morphs('messagable');
        //     $table->foreignIdFor(Message::class);
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
};
