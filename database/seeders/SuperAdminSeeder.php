<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
