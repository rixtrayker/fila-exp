<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\ClientType;
use App\Models\Speciality;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // Ensure we have at least one client type
        if (ClientType::count() === 0) {
            ClientType::factory()->count(3)->create();
        }

        // Ensure we have at least one specialty
        if (Speciality::count() === 0) {
            Speciality::factory()->count(5)->create();
        }

        $arr =  [
            'name_en' => $this->faker->name(),
        ];

        $this->faker->locale('ar_JO');

        $arr['name_ar'] = $this->faker->name();

        $arr2 = [
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'client_type_id' => ClientType::inRandomOrder()->first()->id,
            'speciality_id' => Speciality::inRandomOrder()->first()->id,
            'shift' => $this->faker->randomElement(['AM','PM']),
            'grade' => $this->faker->randomElement(['A','B','C','N','PH']),
        ];

        return array_merge($arr,$arr2);
    }
}
