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
        $coaching = Institute::where('type', 'coaching')->first();

        $user = User::updateOrCreate(
            ['email' => 'coaching@alpha.com'],
            [
                'name' => 'Coaching Admin',
                'password' => Hash::make('Coaching@123'),
                'role' => 'coaching_admin',
                'email_verified_at' => now(),
            ]
        );

        $user->institutes()->syncWithoutDetaching([
            $coaching->id => ['role' => 'admin']
        ]);
    }
}
