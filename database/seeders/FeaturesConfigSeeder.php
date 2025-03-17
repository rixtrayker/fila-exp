<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeaturesConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name' => 'location',
                'enabled' => true,
                'description' => 'Location feature',
                'icon' => 'location',
                'color' => 'blue',
            ]
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate($feature);
        }
    }
}
