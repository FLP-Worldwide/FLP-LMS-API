<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Institute;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolAdminSeeder extends Seeder
{
    public function run(): void
    {
        /* ===================== 1️⃣ GET INSTITUTE ===================== */

        $school = Institute::where('name', 'DPS Jaipur')->first();

        if (!$school) {
            $this->command->warn('School institute not found.');
            return;
        }

        /* ===================== 2️⃣ GET ROLE ID (ADMIN) ===================== */

        $role = Role::where('institute_id', $school->id)
            ->where('slug', 'admin')
            ->first();

        if (!$role) {
            $this->command->warn('Admin role not found for school.');
            return;
        }

        /* ===================== 3️⃣ CREATE / UPDATE USER ===================== */

        $user = User::updateOrCreate(
            ['email' => 'school@dps.com'],
            [
                'uid'               => 'US' . rand(10000, 99999),
                'name'              => 'DPS School Admin',
                'password'          => Hash::make('School@123'),
                'role'              => 'school_admin', // optional (users table)
                'account_type'      => 'school',
                'email_verified_at' => now(),
            ]
        );

        /* ===================== 4️⃣ ATTACH WITH ROLE + ROLE_ID ===================== */

        $user->institutes()->syncWithoutDetaching([
            $school->id => [
                'role'    => 'admin',
                'role_id' => $role->id, // ✅ THIS WAS MISSING
            ],
        ]);
    }
}
