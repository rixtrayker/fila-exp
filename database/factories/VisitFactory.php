<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => 50,
            'plan_id' => 4,
            'client_id' => 72,
            'status' => ['verified','visited'][random_int(0,1)],
            'call_type_id' => 1,
            'visit_type_id' => 1,
            'comment' => '11',
        ];
    }
}
