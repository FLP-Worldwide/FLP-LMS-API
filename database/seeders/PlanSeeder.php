<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['name' => 'Free'],
            [
                'storage_limit_mb' => 500,
                'price' => 0,
            ]
        );

        Plan::updateOrCreate(
            ['name' => 'Basic'],
            [
                'storage_limit_mb' => 2000,
                'price' => 999,
            ]
        );

        Plan::updateOrCreate(
            ['name' => 'Pro'],
            [
                'storage_limit_mb' => 10000,
                'price' => 2999,
            ]
        );
    }
}
