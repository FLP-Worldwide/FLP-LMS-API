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
