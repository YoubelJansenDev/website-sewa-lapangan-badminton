<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Raket', 'price' => 15000, 'stock' => 10],
            ['name' => 'Kok (1 buah)', 'price' => 5000, 'stock' => 50],
            ['name' => 'Sepatu badminton', 'price' => 20000, 'stock' => 6],
        ];

        foreach ($items as $item) {
            Equipment::updateOrCreate(['name' => $item['name']], $item + ['is_active' => true]);
        }
    }
}