<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoachingAdminSeeder extends Seeder
{
    public function run(): void
    {
        $coaching = Institute::where('name', 'Alpha Coaching Institute')->first();

        if (!$coaching) {
            $this->command->warn('Coaching institute not found. Skipping CoachingAdminSeeder.');
            return;
        }

        $user = User::updateOrCreate(
            ['email' => 'coaching@alpha.com'],
            [
                'name' => 'Coaching Admin',
                'password' => Hash::make('Coaching@123'),
                'role' => 'coaching_admin',
                'account_type' => 'school',
                'email_verified_at' => now(),
            ]
        );

        $user->institutes()->syncWithoutDetaching([
            $coaching->id => ['role' => 'admin'],
        ]);
    }
}
