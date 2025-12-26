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
        // ✅ Always fetch by unique name (safer than type)
        $school = Institute::where('name', 'DPS Jaipur')->first();

        if (!$school) {
            $this->command->warn('School institute not found. Skipping SchoolAdminSeeder.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'school@dps.com'],
            [
                'uid' => 'US'.rand(10000,99999),
                'name' => 'DPS School Admin',
                'password' => Hash::make('School@123'),
                'role' => 'school_admin',
                'account_type' => 'school',
                'email_verified_at' => now(),
            ]
        );

        $user->institutes()->syncWithoutDetaching([
            $school->id => ['role' => 'admin'],
        ]);
    }
}
