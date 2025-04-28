<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\VacationDuration;
use App\Models\VacationRequest;
use App\Services\VacationCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class VacationCalculatorTest extends TestCase
{
    private VacationCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new VacationCalculator();
    }

    /**
     * @test
     * @dataProvider shiftAdjustmentProvider
     */
    public function it_calculates_shift_adjustment_correctly($startShift, $endShift, $expectedAdjustment)
    {
        $method = new \ReflectionMethod(VacationCalculator::class, 'calculateShiftAdjustment');
        $method->setAccessible(true);

        $result = $method->invoke($this->calculator, $startShift, $endShift);
        $this->assertEquals($expectedAdjustment, $result);
    }

    public function shiftAdjustmentProvider()
    {
        return [
            'AM shift only' => ['AM', 'AM', 0.5],
            'PM shift only' => ['PM', 'PM', 0.5],
            'Full day (AM and PM)' => ['AM', 'PM', 1.0],
            'Invalid shift combination' => ['PM', 'AM', 0.0],
        ];
    }

    /**
     * @test
     * @dataProvider daysDifferenceProvider
     */
    public function it_calculates_days_difference_correctly($start, $end, $expectedDays)
    {
        $method = new \ReflectionMethod(VacationCalculator::class, 'calculateDaysDifference');
        $method->setAccessible(true);

        $result = $method->invoke($this->calculator, $start, $end);
        $this->assertEquals($expectedDays, $result);
    }

    public function daysDifferenceProvider()
    {
        return [
            'Same day' => ['2024-01-01', '2024-01-01', 0],
            'One day difference' => ['2024-01-01', '2024-01-02', 1],
            'Multiple day difference' => ['2024-01-01', '2024-01-05', 4],
            'Month boundary' => ['2024-01-30', '2024-02-02', 3],
            'Year boundary' => ['2023-12-30', '2024-01-02', 3],
        ];
    }

    /** @test */
    public function it_calculates_total_duration_correctly()
    {
        // Test with AM shift only (half day)
        $duration1 = $this->calculator->calculateTotalDuration('2024-01-01', '2024-01-01', 'AM', 'AM');
        $this->assertEquals(0.5, $duration1);

        // Test with PM shift only (half day)
        $duration2 = $this->calculator->calculateTotalDuration('2024-01-01', '2024-01-01', 'PM', 'PM');
        $this->assertEquals(0.5, $duration2);

        // Test with both shifts (full day)
        $duration3 = $this->calculator->calculateTotalDuration('2024-01-01', '2024-01-01', 'AM', 'PM');
        $this->assertEquals(1.0, $duration3);

        // Test with multiple days plus full day shift
        $duration4 = $this->calculator->calculateTotalDuration('2024-01-01', '2024-01-03', 'AM', 'PM');
        $this->assertEquals(3.0, $duration4);

        // Test with multiple days plus half day shift
        $duration5 = $this->calculator->calculateTotalDuration('2024-01-01', '2024-01-03', 'AM', 'AM');
        $this->assertEquals(2.5, $duration5);
    }

    /**
     * @test
     * @dataProvider durationOverlapProvider
     */
    public function it_calculates_duration_overlap_correctly($durationData, $fromDate, $toDate, $expected)
    {
        $duration = new VacationDuration($durationData);

        $result = $this->calculator->calculateOverlappingVacationDays(
            $duration,
            Carbon::parse($fromDate),
            Carbon::parse($toDate)
        );

        $this->assertEquals($expected, $result);
    }

    public function durationOverlapProvider()
    {
        return [
            'Complete overlap with AM shift only' => [
                [
                    'start' => '2024-01-01',
                    'end' => '2024-01-01',
                    'start_shift' => 'AM',
                    'end_shift' => 'AM'
                ],
                '2024-01-01',
                '2024-01-01',
                0.5
            ],
            'Complete overlap with PM shift only' => [
                [
                    'start' => '2024-01-01',
                    'end' => '2024-01-01',
                    'start_shift' => 'PM',
                    'end_shift' => 'PM'
                ],
                '2024-01-01',
                '2024-01-01',
                0.5
            ],
            'Complete overlap with both shifts' => [
                [
                    'start' => '2024-01-01',
                    'end' => '2024-01-01',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM'
                ],
                '2024-01-01',
                '2024-01-01',
                1.0
            ],
            'Partial overlap at start' => [
                [
                    'start' => '2023-12-30',
                    'end' => '2024-01-02',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM'
                ],
                '2024-01-01',
                '2024-01-05',
                2  // 2 days (Jan 1-2)
            ],
            'Partial overlap at end' => [
                [
                    'start' => '2024-01-03',
                    'end' => '2024-01-07',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM'
                ],
                '2024-01-01',
                '2024-01-05',
                3  // 3 days (Jan 3-5)
            ],
            'Duration spans range' => [
                [
                    'start' => '2023-12-30',
                    'end' => '2024-01-07',
                    'start_shift' => 'AM',
                    'end_shift' => 'PM'
                ],
                '2024-01-01',
                '2024-01-05',
                5  // 5 days (Jan 1-5)
            ],
        ];
    }


    /** @test */
    public function it_calculates_duration_days_in_range_when_no_overlap()
    {
        $duration = $this->createMock(VacationDuration::class);
        $duration->end = Carbon::parse('2023-12-25');
        $duration->start = Carbon::parse('2023-12-20');

        $method = new \ReflectionMethod(VacationCalculator::class, 'calculateDurationDaysInRange');
        $method->setAccessible(true);

        $fromDate = Carbon::parse('2024-01-01');
        $toDate = Carbon::parse('2024-01-05');

        $result = $method->invoke($this->calculator, $duration, $fromDate, $toDate);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function it_calculates_duration_days_in_range_when_completely_within_range()
    {
        // Use a real instance instead of a mock
        $duration = new VacationDuration();
        $duration->start = Carbon::parse('2024-01-02'); // Use Carbon instances directly for clarity
        $duration->end = Carbon::parse('2024-01-03');
        $duration->start_shift = 'AM';
        $duration->end_shift = 'PM';
        // Explicitly set the duration value that the method should return in this case
        $duration->duration = 2.0;

        $method = new \ReflectionMethod(VacationCalculator::class, 'calculateDurationDaysInRange');
        $method->setAccessible(true);

        $fromDate = Carbon::parse('2024-01-01');
        $toDate = Carbon::parse('2024-01-05');

        // First Test Case: Full days
        $result = $method->invoke($this->calculator, $duration, $fromDate, $toDate);
        $this->assertEquals(2.0, $result);

        // Test with half day shifts
        // Reconfigure the same object for the next test case
        $duration->start = Carbon::parse('2024-01-02'); // Keep dates the same for this specific sub-test
        $duration->end = Carbon::parse('2024-01-03');
        $duration->start_shift = 'AM';
        $duration->end_shift = 'AM';
        // Explicitly set the expected duration for this scenario
        $duration->duration = 1.5; // Duration for 2nd AM to 3rd AM

        $result = $method->invoke($this->calculator, $duration, $fromDate, $toDate);
        $this->assertEquals(1.5, $result); // <-- Make sure expected value matches duration set

        // Test with single day
        // Reconfigure the same object
        $duration->start = Carbon::parse('2024-01-02');
        $duration->end = Carbon::parse('2024-01-02');
        $duration->start_shift = 'AM';
        $duration->end_shift = 'PM';
        // Explicitly set the expected duration for this scenario
        $duration->duration = 1.0; // Duration for 2nd AM to 2nd PM

        $result = $method->invoke($this->calculator, $duration, $fromDate, $toDate);
        $this->assertEquals(1.0, $result); // <-- Make sure expected value matches duration set
    }
}
