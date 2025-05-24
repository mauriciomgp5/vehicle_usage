<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicles')->insert([
            [
                'plate' => 'ABC2525',
                'model' => 'Uno',
                'brand' => 'Fiat',
                'year' => 2015,
                'km' => 120000,
                'status' => 'active',
                'licensing_due_date' => Carbon::now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plate' => 'XYZ2525',
                'model' => 'Gol',
                'brand' => 'Volkswagen',
                'year' => 2018,
                'km' => 90000,
                'status' => 'active',
                'licensing_due_date' => Carbon::now()->addMonths(8),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plate' => 'DEF1234',
                'model' => 'Onix',
                'brand' => 'Chevrolet',
                'year' => 2020,
                'km' => 45000,
                'status' => 'active',
                'licensing_due_date' => Carbon::now()->addMonths(12),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plate' => 'GHI5678',
                'model' => 'HB20',
                'brand' => 'Hyundai',
                'year' => 2019,
                'km' => 60000,
                'status' => 'active',
                'licensing_due_date' => Carbon::now()->addMonths(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
