<?php

namespace Tests\Unit;

use App\Models\Visit;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class VisitParticipantScopeTest extends TestCase
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
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('second_user_id')->nullable();
            $table->date('visit_date');
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Visit::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_it_filters_grouped_owner_and_accompanying_participation(): void
    {
        $this->database->table('visits')->insert([
            ['id' => 1, 'user_id' => 10, 'second_user_id' => null, 'visit_date' => '2026-07-17'],
            ['id' => 2, 'user_id' => 20, 'second_user_id' => 10, 'visit_date' => '2026-07-17'],
            ['id' => 3, 'user_id' => 20, 'second_user_id' => 10, 'visit_date' => '2026-06-17'],
            ['id' => 4, 'user_id' => 20, 'second_user_id' => 30, 'visit_date' => '2026-07-17'],
        ]);

        $ids = Visit::withoutGlobalScopes()
            ->whereDate('visit_date', '2026-07-17')
            ->participatedBy(10)
            ->pluck('id')
            ->all();

        self::assertSame([1, 2], $ids);
    }
}
