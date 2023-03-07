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
        $arr =  [
            'name_en' => $this->faker->name(),
        ];

        $this->faker->locale('ar_JO');

        $arr['name_ar'] = $this->faker->name();

        $arr2 = [
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->unique()->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city_id' => $this->faker->randomElement(City::pluck('id')),
            'client_type_id' => $this->faker->randomElement(ClientType::pluck('id')),
            'speciality_id' => $this->faker->randomElement(Speciality::pluck('id')),
            'shift' => $this->faker->randomElement(['AM','PM']),
            'grade' => $this->faker->randomElement(['A+','A','B+','B','C']),
        ];

        return array_merge($arr,$arr2);
    }
}
