<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\VacationType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VacationRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {

        return [
            'user_id' => User::factory(),
            'vacation_type_id' => VacationType::factory(),
            'approved_at' => $this->faker->dateTimeBetween('2024-01-01', '2024-12-31'),
            'approved' => $this->faker->randomElement([5,3,2,1,0,-1,-2,-3,-4,-5]),
        ];
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return ['approved' => 5];
        });
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return ['approved' => 0];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return ['approved' => -1];
        });
    }

}
