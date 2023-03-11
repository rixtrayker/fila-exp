<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use \Staudenmeir\LaravelMergedRelations\Facades\Schema;


return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::createMergeViewWithoutDuplicates(
        //     'all_messages',
        //     [(new User)->userMessages(), (new User())->roleMassges()]
        // );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropView('all_messages');
    }
};
