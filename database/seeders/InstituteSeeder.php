<?php

namespace Database\Seeders;

use App\Models\Institute;
use Illuminate\Database\Seeder;

class InstituteSeeder extends Seeder
{
    public function run(): void
    {
        // SCHOOL
        Institute::updateOrCreate(
            ['name' => 'DPS Jaipur'],
            [
                'sid' => 'SCH'.rand(10000,99999),
                'type' => 'school',
                'email' => 'contact@dpsjaipur.com',
                'phone' => '9876543210',
                'address' => 'Ajmer Road',
                'city' => 'Jaipur',
                'state' => 'Rajasthan',
                'country' => 'India',
            ]
        );

        // COACHING
        Institute::updateOrCreate(
            ['name' => 'Alpha Coaching Institute'],
            [
                'sid' => 'INST'.rand(10000,99999),
                'type' => 'coaching',
                'email' => 'info@alphacoaching.com',
                'phone' => '9123456789',
                'address' => 'Malviya Nagar',
                'city' => 'Jaipur',
                'state' => 'Rajasthan',
                'country' => 'India',
            ]
        );
    }
}
