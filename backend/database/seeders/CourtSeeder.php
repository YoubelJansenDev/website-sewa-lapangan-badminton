<?php

namespace Database\Seeders;

use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $courts = [
            ['name' => 'Lapangan 1', 'floor_type' => 'Karpet sintetis', 'price_per_hour' => 50000],
            ['name' => 'Lapangan 2', 'floor_type' => 'Karpet sintetis', 'price_per_hour' => 50000],
            ['name' => 'Lapangan 3', 'floor_type' => 'Kayu parquet', 'price_per_hour' => 65000],
        ];

        foreach ($courts as $court) {
            Court::updateOrCreate(['name' => $court['name']], $court + ['is_active' => true]);
        }
    }
}