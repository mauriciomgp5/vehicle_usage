<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Test User',
            'phone' => '5517996165851',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_supervisor' => true,
        ]);

        $this->call([
            VehicleSeeder::class,
        ]);
    }
}
