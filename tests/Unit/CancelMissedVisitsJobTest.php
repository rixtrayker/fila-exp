<?php

namespace Tests\Unit;

use App\Jobs\CancelMissedVisitsJob;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class CancelMissedVisitsJobTest extends TestCase
{
    private Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->database = new Capsule();
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $this->database->schema()->create('visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('status');
            $table->date('visit_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Visit::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_it_cancels_only_the_previous_days_pending_plan_visits(): void
    {
        $this->database->table('visits')->insert([
            ['id' => 1, 'plan_id' => 10, 'status' => 'pending', 'visit_date' => '2026-07-16', 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'plan_id' => 10, 'status' => 'pending', 'visit_date' => '2026-07-17', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'plan_id' => null, 'status' => 'pending', 'visit_date' => '2026-07-16', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'plan_id' => 10, 'status' => 'visited', 'visit_date' => '2026-07-16', 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'plan_id' => 10, 'status' => 'planned', 'visit_date' => '2026-07-16', 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'plan_id' => 10, 'status' => 'pending', 'visit_date' => '2026-07-15', 'created_at' => null, 'updated_at' => null],
        ]);

        (new CancelMissedVisitsJob(
            new CarbonImmutable('2026-07-17'),
        ))->handle();

        $statuses = $this->database->table('visits')
            ->orderBy('id')
            ->pluck('status', 'id')
            ->all();

        self::assertSame([
            1 => 'cancelled',
            2 => 'pending',
            3 => 'pending',
            4 => 'visited',
            5 => 'planned',
            6 => 'pending',
        ], $statuses);
    }
}
