<?php

namespace Database\Factories;

use App\Models\ClientType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientTypeFactory extends Factory
{
    protected $model = ClientType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Hospital',
                'Clinic',
                'Pharmacy',
                'Medical Center',
                'Laboratory',
                'Medical Store'
            ]),
        ];
    }
}
