<?php

namespace Tests\Unit;

use App\Models\AllActivitySOPsReport;
use App\Models\ClientType;
use App\Models\RoleVisitTarget;
use App\Models\Setting;
use App\Services\AllActivitySOPsReportService;
use Database\Seeders\RoleVisitTargetsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class AllActivitySOPsReportServiceTest extends TestCase
{
    private const CONNECTION = 'all_activity_sops_test';

    // 2026-06-01 is a Monday; 2026-06-01..2026-06-05 is a full Mon-Fri week.
    private const FROM_DATE = '2026-06-01';

    private const TO_DATE = '2026-06-05';

    private const MEDICAL_REP_ROLE_ID = 10;

    private const MANAGER_ROLE_ID = 11;

    private const UNSUPPORTED_ROLE_ID = 12;

    private const REP_ID = 1;

    private const MANAGER_ID = 2;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        config(['database.default' => self::CONNECTION]);
        DB::purge(self::CONNECTION);

        Cache::flush();
        RoleVisitTarget::flushMatrixCache();

        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        RoleVisitTarget::flushMatrixCache();
        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_report_splits_visit_counts_per_client_type_and_credits_double_visits_to_both_users(): void
    {
        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];
        $manager = $this->getReportRows(self::MANAGER_ROLE_ID)[self::MANAGER_ID];

        // Rep: 1 AM + 3 PM + 2 PH visited visits; pending, soft-deleted and
        // out-of-range visits are excluded.
        $this->assertSame(1, $rep['am_visits']);
        $this->assertSame(3, $rep['pm_visits']);
        $this->assertSame(2, $rep['ph_visits']);
        $this->assertSame(6, $rep['total_visits']);

        // The double visit (rep + accompanying manager) is credited to BOTH:
        // it is one of the rep's 3 PM visits and the manager's only PM visit.
        $this->assertSame(1, $manager['pm_visits']);
        $this->assertSame(0, $manager['am_visits']);
        $this->assertSame(0, $manager['ph_visits']);
        $this->assertSame(1, $manager['total_visits']);
    }

    public function test_visits_outside_the_users_territory_are_not_counted(): void
    {
        // A client in a brick nobody covers: visits to it are logged but the
        // rep is not accountable for it, so it must not inflate SOPs.
        DB::table('clients')->insert([
            'id' => 4, 'name' => 'Foreign Client', 'client_type_id' => ClientType::PM, 'brick_id' => 99,
        ]);
        DB::table('visits')->insert([
            'user_id' => self::REP_ID, 'client_id' => 4,
            'status' => 'visited', 'visit_date' => '2026-06-01',
        ]);

        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];

        $this->assertSame(3, $rep['pm_visits']);
        $this->assertSame(6, $rep['total_visits']);
    }

    public function test_personal_list_replaces_the_area_pool_for_that_client_type_only(): void
    {
        // The rep curates a PM list holding only client 1... but not the
        // second PM client they also visited.
        DB::table('clients')->insert([
            'id' => 4, 'name' => 'Unlisted Doctor', 'client_type_id' => ClientType::PM, 'brick_id' => 1,
        ]);
        DB::table('visits')->insert([
            'user_id' => self::REP_ID, 'client_id' => 4,
            'status' => 'visited', 'visit_date' => '2026-06-01',
        ]);
        DB::table('client_user')->insert([
            'client_id' => 1, 'user_id' => self::REP_ID,
        ]);

        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];

        // PM is now evaluated against the list: the 3 visits to the listed
        // client count, the visit to the unlisted one does not.
        $this->assertSame(3, $rep['pm_visits']);

        // PH and AM have no personal list, so they keep the area-derived pool.
        $this->assertSame(2, $rep['ph_visits']);
        $this->assertSame(1, $rep['am_visits']);
        $this->assertSame(6, $rep['total_visits']);
    }

    public function test_stale_list_entries_do_not_switch_a_user_off_the_area_pool(): void
    {
        // The rep's only list entry is a client that left their territory.
        // That is not a maintained list, so the area pool must still apply.
        DB::table('clients')->insert([
            'id' => 4, 'name' => 'Moved Away', 'client_type_id' => ClientType::PM, 'brick_id' => 99,
        ]);
        DB::table('client_user')->insert([
            'client_id' => 4, 'user_id' => self::REP_ID,
        ]);

        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];

        $this->assertSame(3, $rep['pm_visits']);
        $this->assertSame(6, $rep['total_visits']);
    }

    public function test_double_visits_respect_the_accompanying_managers_own_list(): void
    {
        // The manager curates a PM list that excludes the client visited on
        // the double visit; the rep has no list and keeps the area pool.
        DB::table('client_user')->insert([
            'client_id' => 4, 'user_id' => self::MANAGER_ID,
        ]);
        DB::table('clients')->insert([
            'id' => 4, 'name' => 'Manager Only Doctor', 'client_type_id' => ClientType::PM, 'brick_id' => 1,
        ]);

        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];
        $manager = $this->getReportRows(self::MANAGER_ROLE_ID)[self::MANAGER_ID];

        // The manager is credited only for clients on their own list.
        $this->assertSame(0, $manager['pm_visits']);
        $this->assertSame(0, $manager['total_visits']);

        // The rep is unaffected by the manager's list.
        $this->assertSame(3, $rep['pm_visits']);
    }

    public function test_inactive_clients_are_excluded_from_visit_counts(): void
    {
        DB::table('clients')->where('id', 2)->update(['active' => false]);

        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];

        $this->assertSame(0, $rep['ph_visits']);
        $this->assertSame(4, $rep['total_visits']);
    }

    public function test_working_days_exclude_weekends_holidays_and_busy_days(): void
    {
        // Mon-Fri week minus the official holiday on Thursday => 4 working days.
        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];
        $manager = $this->getReportRows(self::MANAGER_ROLE_ID)[self::MANAGER_ID];
        $this->assertSame(4, $rep['working_days']);
        $this->assertSame(4, $manager['working_days']);

        // Rep has an approved vacation on Tuesday => 3 actual working days.
        $this->assertSame(3, $rep['actual_working_days']);

        // Manager has approved office work on Wednesday and an activity on
        // Friday => 2 actual working days.
        $this->assertSame(2, $manager['actual_working_days']);
    }

    public function test_targets_resolve_role_matrix_first_then_settings_and_percentages_are_correct(): void
    {
        $rep = $this->getReportRows(self::MEDICAL_REP_ROLE_ID)[self::REP_ID];
        $manager = $this->getReportRows(self::MANAGER_ROLE_ID)[self::MANAGER_ID];

        // Rep role has matrix rows (AM 2 / PM 8 / PH 5), distinct from settings.
        $this->assertSame(2.0, $rep['am_daily_target']);
        $this->assertSame(8.0, $rep['pm_daily_target']);
        $this->assertSame(5.0, $rep['ph_daily_target']);

        // Monthly target = actual working days (3) x daily target.
        $this->assertSame(6.0, $rep['am_monthly_target']);
        $this->assertSame(24.0, $rep['pm_monthly_target']);
        $this->assertSame(15.0, $rep['ph_monthly_target']);

        // SOPs % = visits / monthly target * 100.
        $this->assertSame(round(1 / 6 * 100, 2), $rep['am_sops']);
        $this->assertSame(round(3 / 24 * 100, 2), $rep['pm_sops']);
        $this->assertSame(round(2 / 15 * 100, 2), $rep['ph_sops']);

        // Call rate = total visits / actual working days.
        $this->assertSame(2.0, $rep['call_rate']);

        // Manager role has NO matrix rows => falls back to global settings
        // (AM 4 / PM 6 / PH 9), with 2 actual working days.
        $this->assertSame(4.0, $manager['am_daily_target']);
        $this->assertSame(6.0, $manager['pm_daily_target']);
        $this->assertSame(9.0, $manager['ph_daily_target']);
        $this->assertSame(8.0, $manager['am_monthly_target']);
        $this->assertSame(12.0, $manager['pm_monthly_target']);
        $this->assertSame(18.0, $manager['ph_monthly_target']);
        $this->assertSame(round(1 / 12 * 100, 2), $manager['pm_sops']);
        $this->assertSame(0.0, $manager['am_sops']);
        $this->assertSame(0.5, $manager['call_rate']);
    }

    public function test_resolve_daily_target_uses_role_row_first_then_settings_fallback(): void
    {
        // Role with a matrix row.
        $this->assertSame(5.0, RoleVisitTarget::resolveDailyTarget(self::MEDICAL_REP_ROLE_ID, ClientType::PH));

        // Role without a matrix row falls back to settings.
        $this->assertSame(9.0, RoleVisitTarget::resolveDailyTarget(self::MANAGER_ROLE_ID, ClientType::PH));

        // No role at all falls back to settings.
        $this->assertSame(6.0, RoleVisitTarget::resolveDailyTarget(null, ClientType::PM));
    }

    public function test_user_filter_limits_report_rows(): void
    {
        $rows = (new AllActivitySOPsReportService())->getReportData([
            'from_date' => self::FROM_DATE,
            'to_date' => self::TO_DATE,
            'user_id' => [self::MANAGER_ID],
            'evaluation_role_id' => self::MANAGER_ROLE_ID,
        ]);

        $this->assertCount(1, $rows);
        $this->assertSame(self::MANAGER_ID, $rows->first()['id']);
    }

    public function test_filament_filter_state_preserves_the_role_for_report_and_export(): void
    {
        $filters = AllActivitySOPsReport::normalizeFilters([
            'evaluation_role_id' => ['value' => (string) self::MANAGER_ROLE_ID],
            'user_id' => ['values' => [(string) self::MANAGER_ID]],
            'date_range' => [
                'from_date' => self::FROM_DATE,
                'to_date' => self::TO_DATE,
            ],
        ]);

        $this->assertSame(self::MANAGER_ROLE_ID, $filters['evaluation_role_id']);
        $this->assertSame([self::MANAGER_ID], $filters['user_id']);
    }

    public function test_evaluation_role_is_required(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('An evaluation role must be selected.');

        (new AllActivitySOPsReportService())->getReportData([
            'from_date' => self::FROM_DATE,
            'to_date' => self::TO_DATE,
        ]);
    }

    public function test_selected_role_must_be_supported_and_belong_to_the_evaluated_user(): void
    {
        $service = new AllActivitySOPsReportService();

        try {
            $service->getReportData([
                'from_date' => self::FROM_DATE,
                'to_date' => self::TO_DATE,
                'evaluation_role_id' => self::UNSUPPORTED_ROLE_ID,
            ]);
            $this->fail('Unsupported evaluation role was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('The selected evaluation role is not supported.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The selected evaluation role must belong to every evaluated user.');

        $service->getReportData([
            'from_date' => self::FROM_DATE,
            'to_date' => self::TO_DATE,
            'user_id' => [self::MANAGER_ID],
            'evaluation_role_id' => self::MEDICAL_REP_ROLE_ID,
        ]);
    }

    public function test_explicit_role_selection_controls_targets_for_a_multi_role_user(): void
    {
        DB::table('model_has_roles')->insert([
            'role_id' => self::MANAGER_ROLE_ID,
            'model_type' => 'App\\Models\\User',
            'model_id' => self::REP_ID,
        ]);
        RoleVisitTarget::create([
            'role_id' => self::MANAGER_ROLE_ID,
            'client_type_id' => ClientType::AM,
            'daily_target' => 12,
        ]);

        $medicalRepRow = $this->getReportRows(self::MEDICAL_REP_ROLE_ID, [self::REP_ID])[self::REP_ID];
        $managerRow = $this->getReportRows(self::MANAGER_ROLE_ID, [self::REP_ID])[self::REP_ID];

        $this->assertSame('medical-rep', $medicalRepRow['role_name']);
        $this->assertSame(2.0, $medicalRepRow['am_daily_target']);
        $this->assertSame('district-manager', $managerRow['role_name']);
        $this->assertSame(12.0, $managerRow['am_daily_target']);
    }

    public function test_role_target_seeder_only_creates_missing_defaults_and_is_idempotent(): void
    {
        RoleVisitTarget::query()
            ->where('role_id', self::MEDICAL_REP_ROLE_ID)
            ->where('client_type_id', ClientType::AM)
            ->update(['daily_target' => 99]);
        RoleVisitTarget::query()
            ->where('role_id', self::MEDICAL_REP_ROLE_ID)
            ->where('client_type_id', ClientType::PH)
            ->delete();

        $seeder = app(RoleVisitTargetsSeeder::class);
        $seeder->run();
        $seeder->run();

        $targets = RoleVisitTarget::query()
            ->where('role_id', self::MEDICAL_REP_ROLE_ID)
            ->pluck('daily_target', 'client_type_id');

        $this->assertCount(3, $targets);
        $this->assertSame(99.0, (float) $targets[ClientType::AM]);
        $this->assertSame(9.0, (float) $targets[ClientType::PH]);
    }

    /**
     * @return array<int, array> report rows keyed by user id
     */
    private function getReportRows(int $evaluationRoleId, array $userIds = []): array
    {
        $rows = (new AllActivitySOPsReportService())->getReportData([
            'from_date' => self::FROM_DATE,
            'to_date' => self::TO_DATE,
            'evaluation_role_id' => $evaluationRoleId,
            'user_id' => $userIds,
        ]);

        return $rows->keyBy('id')->all();
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('_lft')->default(0);
            $table->unsignedInteger('_rgt')->default(0);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('client_types', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('settings', function ($table) {
            $table->id();
            $table->integer('order')->nullable();
            $table->string('name')->nullable();
            $table->string('key');
            $table->string('value')->nullable();
            $table->string('type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('official_holidays', function ($table) {
            $table->id();
            $table->date('date');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('office_works', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('activities', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vacation_requests', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('approved')->default(0);
            $table->timestamps();
        });

        Schema::create('vacation_durations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('vacation_request_id');
            $table->date('start');
            $table->date('end');
            $table->string('start_shift')->nullable();
            $table->string('end_shift')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('client_type_id')->nullable();
            $table->unsignedBigInteger('brick_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('client_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Mirrors the user_bricks_view database view: which bricks each user
        // covers, via direct assignment or area membership.
        Schema::create('user_bricks_view', function ($table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('brick_id');
        });

        Schema::create('visits', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('second_user_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('status')->default('pending');
            $table->date('visit_date');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        // Exercise the real migration for the new table.
        $migration = include base_path('database/migrations/2026_07_07_000001_create_role_visit_targets_table.php');
        $migration->up();
    }

    private function seedFixtures(): void
    {
        DB::table('users')->insert([
            ['id' => self::REP_ID, 'name' => 'Alice Rep'],
            ['id' => self::MANAGER_ID, 'name' => 'Bob Manager'],
        ]);

        DB::table('roles')->insert([
            ['id' => self::MEDICAL_REP_ROLE_ID, 'name' => 'medical-rep', 'guard_name' => 'web'],
            ['id' => self::MANAGER_ROLE_ID, 'name' => 'district-manager', 'guard_name' => 'web'],
            ['id' => self::UNSUPPORTED_ROLE_ID, 'name' => 'accountant', 'guard_name' => 'web'],
        ]);

        DB::table('model_has_roles')->insert([
            ['role_id' => self::MEDICAL_REP_ROLE_ID, 'model_type' => 'App\\Models\\User', 'model_id' => self::REP_ID],
            ['role_id' => self::MANAGER_ROLE_ID, 'model_type' => 'App\\Models\\User', 'model_id' => self::MANAGER_ID],
        ]);

        DB::table('client_types')->insert([
            ['id' => ClientType::PM, 'name' => 'PM'],
            ['id' => ClientType::PH, 'name' => 'PH'],
            ['id' => ClientType::AM, 'name' => 'AM'],
        ]);

        // Global settings targets, intentionally distinct from the matrix.
        foreach ([
            ['key' => 'daily_am_target', 'value' => '4'],
            ['key' => 'daily_pm_target', 'value' => '6'],
            ['key' => 'daily_ph_target', 'value' => '9'],
        ] as $index => $setting) {
            Setting::create([
                'order' => $index + 1,
                'name' => $setting['key'],
                'key' => $setting['key'],
                'value' => $setting['value'],
                'type' => 'number',
            ]);
        }

        // Matrix targets for the medical-rep role only.
        foreach ([
            [ClientType::AM, 2],
            [ClientType::PM, 8],
            [ClientType::PH, 5],
        ] as [$clientTypeId, $target]) {
            RoleVisitTarget::create([
                'role_id' => self::MEDICAL_REP_ROLE_ID,
                'client_type_id' => $clientTypeId,
                'daily_target' => $target,
            ]);
        }

        // Official holiday on Thursday 2026-06-04.
        DB::table('official_holidays')->insert([
            ['date' => '2026-06-04', 'name' => 'Holiday'],
        ]);

        // Rep vacation on Tuesday 2026-06-02 (approved).
        DB::table('vacation_requests')->insert([
            ['id' => 1, 'user_id' => self::REP_ID, 'approved' => 1],
        ]);
        DB::table('vacation_durations')->insert([
            ['vacation_request_id' => 1, 'start' => '2026-06-02', 'end' => '2026-06-02'],
        ]);

        // Manager office work on Wednesday 2026-06-03 (approved) and an
        // activity on Friday 2026-06-05.
        DB::table('office_works')->insert([
            ['user_id' => self::MANAGER_ID, 'status' => 'approved', 'created_at' => '2026-06-03 09:00:00'],
        ]);
        DB::table('activities')->insert([
            ['user_id' => self::MANAGER_ID, 'date' => '2026-06-05'],
        ]);

        // Clients: one per type, all inside brick 1.
        DB::table('clients')->insert([
            ['id' => 1, 'name' => 'Doctor Client', 'client_type_id' => ClientType::PM, 'brick_id' => 1],
            ['id' => 2, 'name' => 'Pharmacy Client', 'client_type_id' => ClientType::PH, 'brick_id' => 1],
            ['id' => 3, 'name' => 'Account Client', 'client_type_id' => ClientType::AM, 'brick_id' => 1],
        ]);

        // Both users cover brick 1. Neither maintains a personal client list
        // yet, so every visit falls back to this area-derived pool.
        DB::table('user_bricks_view')->insert([
            ['user_id' => self::REP_ID, 'brick_id' => 1],
            ['user_id' => self::MANAGER_ID, 'brick_id' => 1],
        ]);

        $visit = fn (array $attributes) => array_merge([
            'user_id' => self::REP_ID,
            'second_user_id' => null,
            'client_id' => null,
            'status' => 'visited',
            'visit_date' => '2026-06-01',
            'deleted_at' => null,
        ], $attributes);

        DB::table('visits')->insert([
            // Rep: 2 solo PM visits + 1 PM double visit with the manager.
            $visit(['client_id' => 1, 'visit_date' => '2026-06-01']),
            $visit(['client_id' => 1, 'visit_date' => '2026-06-03']),
            $visit(['second_user_id' => self::MANAGER_ID, 'client_id' => 1, 'visit_date' => '2026-06-05']),
            // Rep: 2 PH visits.
            $visit(['client_id' => 2, 'visit_date' => '2026-06-01']),
            $visit(['client_id' => 2, 'visit_date' => '2026-06-05']),
            // Rep: 1 AM visit.
            $visit(['client_id' => 3, 'visit_date' => '2026-06-03']),
            // Excluded: not visited.
            $visit(['client_id' => 1, 'status' => 'pending', 'visit_date' => '2026-06-03']),
            // Excluded: soft deleted.
            $visit(['client_id' => 1, 'visit_date' => '2026-06-03', 'deleted_at' => '2026-06-03 10:00:00']),
            // Excluded: outside date range.
            $visit(['client_id' => 1, 'visit_date' => '2026-06-10']),
        ]);
    }
}
