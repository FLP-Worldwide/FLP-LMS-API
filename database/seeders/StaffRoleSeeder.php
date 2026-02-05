<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Institute;
use App\Models\Role;

class StaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        $institutes = Institute::all();

        foreach ($institutes as $institute) {

            // Common roles for all institutes
            $roles = [
                [
                    'name' => 'Admin',
                    'slug' => 'admin',
                ],
                [
                    'name' => 'Teacher',
                    'slug' => 'teacher',
                ],
                [
                    'name' => 'Accountant',
                    'slug' => 'accountant',
                ],
            ];

            // Extra roles for coaching institutes
            if ($institute->type === 'coaching') {
                $roles[] = [
                    'name' => 'Counsellor',
                    'slug' => 'counsellor',
                ];
            }

            foreach ($roles as $role) {
                Role::updateOrCreate(
                    [
                        'institute_id' => $institute->id,
                        'slug' => $role['slug'],
                    ],
                    [
                        'name' => $role['name'],
                    ]
                );
            }
        }
    }
}
