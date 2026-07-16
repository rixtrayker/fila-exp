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
            $table->unsignedBigInteger('client_id')->nullable();
            $table->date('visit_date');
            $table->softDeletes();
        });

        $this->database->schema()->create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('brick_id');
            $table->unsignedBigInteger('client_type_id');
            $table->boolean('active')->default(true);
        });

        $this->database->schema()->create('user_bricks_view', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('brick_id');
        });

        $this->database->schema()->create('client_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('client_id');
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

    public function test_accountable_pool_uses_personal_lists_per_type_and_current_territory(): void
    {
        $this->database->table('user_bricks_view')->insert([
            ['user_id' => 10, 'brick_id' => 1],
        ]);
        $this->database->table('clients')->insert([
            ['id' => 1, 'brick_id' => 1, 'client_type_id' => 1, 'active' => true],
            ['id' => 2, 'brick_id' => 1, 'client_type_id' => 1, 'active' => true],
            ['id' => 3, 'brick_id' => 1, 'client_type_id' => 2, 'active' => true],
            ['id' => 4, 'brick_id' => 2, 'client_type_id' => 2, 'active' => true],
        ]);
        $this->database->table('client_user')->insert([
            ['user_id' => 10, 'client_id' => 1],
            ['user_id' => 10, 'client_id' => 4],
        ]);
        $this->database->table('visits')->insert([
            ['id' => 10, 'user_id' => 10, 'client_id' => 1, 'visit_date' => '2026-07-17'],
            ['id' => 11, 'user_id' => 10, 'client_id' => 2, 'visit_date' => '2026-07-17'],
            ['id' => 12, 'user_id' => 10, 'client_id' => 3, 'visit_date' => '2026-07-17'],
            ['id' => 13, 'user_id' => 10, 'client_id' => 4, 'visit_date' => '2026-07-17'],
        ]);

        $ids = Visit::withoutGlobalScopes()
            ->withinAccountablePool()
            ->orderBy('id')
            ->pluck('id')
            ->all();

        self::assertSame([10, 12], $ids);
    }
}
