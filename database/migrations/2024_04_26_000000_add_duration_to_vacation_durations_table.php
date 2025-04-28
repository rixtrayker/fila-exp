<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\VacationDuration;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vacation_durations', function (Blueprint $table) {
            $table->decimal('duration', 8, 2)->nullable()->after('end');
        });

        // calculate duration for all records
        $vacationDurations = VacationDuration::all();
        foreach ($vacationDurations as $vacationDuration) {
            $duration = $vacationDuration->calculateDuration();
            $vacationDuration->duration = $duration;
            $vacationDuration->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vacation_durations', function (Blueprint $table) {
            $table->dropColumn('duration');
        });
    }
};
