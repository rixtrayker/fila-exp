<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VacationDuration;
use App\Models\VacationRequest;
use App\Services\VacationCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VacationCalculatorFeatureTest extends TestCase
{
    use RefreshDatabase;

    private VacationCalculator $calculator;
    private User $user;
    private Carbon $fromDate;
    private Carbon $toDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VacationCalculator();
        $this->user = User::factory()->create();
        $this->fromDate = Carbon::parse('2024-01-01');
        $this->toDate = Carbon::parse('2024-01-31');
    }

    /** @test */
    public function it_returns_zero_when_no_vacation_requests_exist()
    {
        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_returns_zero_when_only_non_approved_requests_exist()
    {
        // Create a pending vacation request
        $pendingRequest = VacationRequest::factory()->pending()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $pendingRequest->id,
            'start' => '2024-01-10',
            'end' => '2024-01-15',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 6
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_returns_zero_when_all_vacations_are_outside_range()
    {
        // Create an approved vacation request outside the date range
        $request = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => '2023-12-10',
            'end' => '2023-12-15',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 6
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_calculates_vacations_that_are_completely_within_range()
    {
        // Create an approved vacation request completely within range
        $request = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => '2024-01-10',
            'end' => '2024-01-15',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 6
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        $this->assertEquals(6, $result);
    }

    /** @test */
    public function it_calculates_vacations_that_partially_overlap_with_range()
    {
        // Create approved vacation requests that partially overlap with range
        $request = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        // Before range, extending into range
        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => '2023-12-29',
            'end' => '2024-01-05',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 8
        ]);

        // Within range, extending beyond
        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => '2024-01-28',
            'end' => '2024-02-03',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 7
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should include: Jan 1-5 (5 days) + Jan 28-31 (4 days) = 9 days
        $this->assertEquals(9, $result);
    }

    /** @test */
    public function it_calculates_vacations_that_span_the_entire_range()
    {
        // Create approved vacation request that spans the entire range
        $request = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => '2023-12-15',
            'end' => '2024-02-15',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 63
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should include all days in January = 31 days
        $this->assertEquals(31, $result);
    }

    /** @test */
    public function it_handles_single_day_vacations_with_different_shift_combinations()
    {
        // Create vacation requests for a single day with different shift combinations
        $request1 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        $request2 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        $request3 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        // Full day (both AM and PM shifts)
        VacationDuration::factory()->create([
            'vacation_request_id' => $request1->id,
            'start' => '2024-01-10',
            'end' => '2024-01-10',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 1
        ]);

        // Half day (AM shift only)
        VacationDuration::factory()->create([
            'vacation_request_id' => $request2->id,
            'start' => '2024-01-15',
            'end' => '2024-01-15',
            'start_shift' => 'AM',
            'end_shift' => 'AM',
            'duration' => 0.5
        ]);

        // Half day (PM shift only)
        VacationDuration::factory()->create([
            'vacation_request_id' => $request3->id,
            'start' => '2024-01-20',
            'end' => '2024-01-20',
            'start_shift' => 'PM',
            'end_shift' => 'PM',
            'duration' => 0.5
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should include: 1 full day + 0.5 AM half day + 0.5 PM half day = 2 days
        $this->assertEquals(2, $result);
    }

    /** @test */
    public function it_handles_complex_vacation_combinations()
    {
        // Create multiple vacation requests with various overlap patterns
        $request1 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        $request2 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        $request3 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        // Completely within range
        VacationDuration::factory()->create([
            'vacation_request_id' => $request1->id,
            'start' => '2024-01-10',
            'end' => '2024-01-12',
            'start_shift' => 'AM',
            'end_shift' => 'PM',
            'duration' => 3
        ]);

        // Partially overlapping at the start
        VacationDuration::factory()->create([
            'vacation_request_id' => $request2->id,
            'start' => '2023-12-29',
            'end' => '2024-01-03',
            'start_shift' => 'AM',
            'end_shift' => 'AM',
            'duration' => 5.5
        ]);

        // Single day within range - half day (PM shift only)
        VacationDuration::factory()->create([
            'vacation_request_id' => $request3->id,
            'start' => '2024-01-20',
            'end' => '2024-01-20',
            'start_shift' => 'PM',
            'end_shift' => 'PM',
            'duration' => 0.5
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should include:
        // - Jan 10-12: 3 days (Full days)
        // - Jan 1-3: 3 days (with Jan 3 being AM only = 2.5 + 0.5)
        // - Jan 20: 0.5 day (PM only)
        // Total: 6.5 days
        $this->assertEquals(6.5, $result);
    }

    /**
     * @test
     * @dataProvider vacationRangeProvider
     */
    public function it_calculates_various_vacation_scenarios($vacationData, $fromDate, $toDate, $expected)
    {
        // Reset database for each data set
        $this->refreshDatabase();
        $user = User::factory()->create();

        $request = VacationRequest::factory()->approved()->create([
            'user_id' => $user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request->id,
            'start' => $vacationData['start'],
            'end' => $vacationData['end'],
            'start_shift' => $vacationData['start_shift'],
            'end_shift' => $vacationData['end_shift'],
            'duration' => $vacationData['duration'],
        ]);

        $result = $this->calculator->calculateTotalVacationDaysInRange(
            $user,
            Carbon::parse($fromDate),
            Carbon::parse($toDate)
        );

        $this->assertEquals($expected, $result);
    }

    public function vacationRangeProvider()
    {
        return [
            'Complete overlap - full days' => [
                [
                    'start' => '2024-01-10',
                    'end' => '2024-01-12',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 3
                ],
                '2024-01-01',
                '2024-01-31',
                3
            ],
            'No overlap - before range' => [
                [
                    'start' => '2023-12-10',
                    'end' => '2023-12-15',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 6
                ],
                '2024-01-01',
                '2024-01-31',
                0
            ],
            'No overlap - after range' => [
                [
                    'start' => '2024-02-10',
                    'end' => '2024-02-15',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 6
                ],
                '2024-01-01',
                '2024-01-31',
                0
            ],
            'Partial overlap - start' => [
                [
                    'start' => '2023-12-29',
                    'end' => '2024-01-05',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 8
                ],
                '2024-01-01',
                '2024-01-31',
                5
            ],
            'Partial overlap - end' => [
                [
                    'start' => '2024-01-29',
                    'end' => '2024-02-05',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 8
                ],
                '2024-01-01',
                '2024-01-31',
                3
            ],
            'Single day - full day (both shifts)' => [
                [
                    'start' => '2024-01-15',
                    'end' => '2024-01-15',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM',
                    'duration' => 1
                ],
                '2024-01-01',
                '2024-01-31',
                1
            ],
            'Single day - half day (AM shift only)' => [
                [
                    'start' => '2024-01-15',
                    'end' => '2024-01-15',
                    'start_shift' => 'AM',
                    'end_shift' => 'AM',
                    'duration' => 0.5
                ],
                '2024-01-01',
                '2024-01-31',
                0.5
            ],
            'Single day - half day (PM shift only)' => [
                [
                    'start' => '2024-01-15',
                    'end' => '2024-01-15',
                    'start_shift' => 'PM',
                    'end_shift' => 'PM',
                    'duration' => 0.5
                ],
                '2024-01-01',
                '2024-01-31',
                0.5
            ],
        ];
    }

    /** @test */
    public function it_accounts_for_shift_correctly_in_partial_overlaps()
    {
        // Test partial overlaps with different shift combinations

        // Test 1: Start overlap with AM shift only
        $request1 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request1->id,
            'start' => '2023-12-30',
            'end' => '2024-01-02',
            'start_shift' => 'AM',
            'end_shift' => 'AM',
            'duration' => 3.5  // 3 days + AM shift
        ]);

        $result1 = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should count Jan 1-2 with Jan 2 being AM only = 1.5 days + partial adjustment
        $this->assertEquals(2.5, $result1);

        // Reset database
        $this->refreshDatabase();
        $this->user = User::factory()->create();

        // Test 2: End overlap with PM shift only
        $request2 = VacationRequest::factory()->approved()->create([
            'user_id' => $this->user->id,
        ]);

        VacationDuration::factory()->create([
            'vacation_request_id' => $request2->id,
            'start' => '2024-01-30',
            'end' => '2024-02-02',
            'start_shift' => 'PM',
            'end_shift' => 'PM',
            'duration' => 3.5  // 3 days + PM shift
        ]);

        $result2 = $this->calculator->calculateTotalVacationDaysInRange(
            $this->user,
            $this->fromDate,
            $this->toDate
        );

        // Should count Jan 30-31 with Jan 30 being PM only = 1.5 days + partial adjustment
        $this->assertEquals(2.5, $result2);
    }
}