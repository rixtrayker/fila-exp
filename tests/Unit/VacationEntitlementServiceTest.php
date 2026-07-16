<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VacationDuration;
use App\Services\VacationCalculator;
use App\Services\VacationEntitlementService;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\TestCase;

class VacationEntitlementServiceTest extends TestCase
{
    private Capsule $database;

    private VacationEntitlementService $service;

    private User $user;

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

        $this->createSchema();

        $this->service = new VacationEntitlementService(new VacationCalculator());
        $this->user = new User();
        $this->user->forceFill([
            'id' => 1,
            'annual_vacation_days' => 21,
        ]);
    }

    protected function tearDown(): void
    {
        VacationDuration::unsetConnectionResolver();

        parent::tearDown();
    }

    public function test_cross_year_leave_is_split_between_calendar_years(): void
    {
        $this->insertType(1, true);
        $this->insertRequest(10, 1, true);
        $this->insertDuration(10, '2025-12-30', '2026-01-03', 'AM', 'PM', 5);

        self::assertSame(2.0, $this->service->spentAnnualDays($this->user, 2025));
        self::assertSame(3.0, $this->service->spentAnnualDays($this->user, 2026));
    }

    public function test_half_days_are_split_correctly_at_year_boundary(): void
    {
        $this->insertType(1, true);
        $this->insertRequest(10, 1, true);
        $this->insertDuration(10, '2025-12-31', '2026-01-01', 'PM', 'AM', 1);

        self::assertSame(0.5, $this->service->spentAnnualDays($this->user, 2025));
        self::assertSame(0.5, $this->service->spentAnnualDays($this->user, 2026));
    }

    public function test_non_annual_leave_does_not_reduce_annual_balance(): void
    {
        $this->insertType(1, false);
        $this->insertRequest(10, 1, true);
        $this->insertDuration(10, '2026-02-01', '2026-02-03', 'AM', 'PM', 3);

        self::assertSame(21.0, $this->service->remainingAnnualDays($this->user, 2026));
        self::assertSame(
            3.0,
            $this->service->spentDaysInRange(
                $this->user,
                Carbon::parse('2026-01-01'),
                Carbon::parse('2026-12-31'),
            ),
        );
    }

    public function test_editing_can_exclude_the_current_approved_request(): void
    {
        $this->insertType(1, true);
        $this->insertRequest(10, 1, true);
        $this->insertDuration(10, '2026-03-01', '2026-03-05', 'AM', 'PM', 5);

        self::assertSame(16.0, $this->service->remainingAnnualDays($this->user, 2026));
        self::assertSame(21.0, $this->service->remainingAnnualDays($this->user, 2026, 10));
    }

    private function createSchema(): void
    {
        $this->database->schema()->create('vacation_types', function (Blueprint $table) {
            $table->id();
            $table->boolean('consumes_annual_entitlement')->default(true);
        });

        $this->database->schema()->create('vacation_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('vacation_type_id');
            $table->integer('approved')->default(0);
        });

        $this->database->schema()->create('vacation_durations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vacation_request_id');
            $table->date('start');
            $table->date('end');
            $table->string('start_shift');
            $table->string('end_shift');
            $table->decimal('duration', 8, 2);
        });
    }

    private function insertType(int $id, bool $consumesAnnualEntitlement): void
    {
        $this->database->table('vacation_types')->insert([
            'id' => $id,
            'consumes_annual_entitlement' => $consumesAnnualEntitlement,
        ]);
    }

    private function insertRequest(int $id, int $typeId, bool $approved): void
    {
        $this->database->table('vacation_requests')->insert([
            'id' => $id,
            'user_id' => $this->user->id,
            'vacation_type_id' => $typeId,
            'approved' => $approved ? 1 : 0,
        ]);
    }

    private function insertDuration(
        int $requestId,
        string $start,
        string $end,
        string $startShift,
        string $endShift,
        float $duration,
    ): void {
        $this->database->table('vacation_durations')->insert([
            'vacation_request_id' => $requestId,
            'start' => $start,
            'end' => $end,
            'start_shift' => $startShift,
            'end_shift' => $endShift,
            'duration' => $duration,
        ]);
    }
}
