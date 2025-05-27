<?php

namespace Database\Factories;

use App\Models\Speciality;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpecialityFactory extends Factory
{
    protected $model = Speciality::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Cardiology',
                'Dermatology',
                'Endocrinology',
                'Gastroenterology',
                'Neurology',
                'Obstetrics and Gynecology',
                'Ophthalmology',
                'Orthopedics',
                'Pediatrics',
                'Psychiatry',
                'Rheumatology',
                'Urology',
                'General Medicine',
                'Internal Medicine',
                'Family Medicine'
            ]),
        ];
    }
}
