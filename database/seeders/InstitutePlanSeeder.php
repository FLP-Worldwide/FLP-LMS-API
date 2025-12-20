<?php

namespace Database\Seeders;

use App\Models\Institute;
use App\Models\Plan;
use App\Models\InstituteSubscription;
use Illuminate\Database\Seeder;

class InstitutePlanSeeder extends Seeder
{
    public function run(): void
    {
        $freePlan = Plan::where('name', 'Free')->first();

        if (!$freePlan) {
            $this->command->warn('Free plan not found.');
            return;
        }

        Institute::all()->each(function ($institute) use ($freePlan) {

            InstituteSubscription::updateOrCreate(
                ['institute_id' => $institute->id],
                [
                    'plan_id'   => $freePlan->id,
                    'starts_at' => now(),
                    'status'    => 'active',
                ]
            );
        });
    }
}
