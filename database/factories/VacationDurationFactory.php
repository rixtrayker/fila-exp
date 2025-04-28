<?php

namespace Database\Factories;

use App\Models\VacationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VacationDurationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $vacationRequest = VacationRequest::factory()->create();
        // random date between 2024-01-01 and 2024-12-31
        $start = $this->faker->dateTimeBetween('2024-01-01', '2024-12-31');
        $end = $this->faker->dateTimeBetween($start, '2024-12-31');
        // the difference in days between start and end
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);
        $duration = $start->diffInDays($end);
        $startShift = $this->faker->randomElement(['AM', 'PM']);
        $endShift = $this->faker->randomElement(['AM', 'PM']);
        // if start am and end pm that is full day
        // if start am and end am we will minus half day
        // if start pm and end pm and not the same day - half from the duration
        // if start pm and end am that is full day and minus 1 day from the duration

        if (!($startShift === 'AM' && $endShift === 'PM')) {
            if ($startShift === 'AM' && $endShift === 'AM') {
                $duration = $duration - 0.5;
            }
            if ($startShift === 'PM' && $endShift === 'PM') {
                $duration = $duration - 0.5;
            }
            if ($startShift === 'PM' && $endShift === 'AM') {
                $duration = $duration - 1;
            }
        }

        return [
            'vacation_request_id' => $vacationRequest->id,
            'start' => $start,
            'end' => $end,
            'duration' => $duration,
            'start_shift' => $startShift,
            'end_shift' => $endShift,
        ];
    }
}
