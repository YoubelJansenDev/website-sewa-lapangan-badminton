<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
        ['email' => env('ADMIN_EMAIL')],
        ['name' => 'Admin', 'password' => env('ADMIN_PASSWORD'), 'role' => 'admin']
    );
    }
}
