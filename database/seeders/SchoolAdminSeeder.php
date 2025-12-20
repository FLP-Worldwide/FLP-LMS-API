<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolAdminSeeder extends Seeder
{
    public function run(): void
    {
        $school = Institute::where('type', 'school')->first();

        $user = User::updateOrCreate(
            ['email' => 'school@dps.com'],
            [
                'name' => 'DPS School Admin',
                'password' => Hash::make('School@123'),
                'role' => 'school_admin',
                'email_verified_at' => now(),
            ]
        );

        // Attach user to school
        $user->institutes()->syncWithoutDetaching([
            $school->id => ['role' => 'admin']
        ]);
    }
}
