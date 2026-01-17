<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

class DevOpsController extends Controller
{
    public function migrate(Request $request)
    {

        Artisan::call('migrate', ['--force' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Migration completed'
        ]);
    }

    public function seed(Request $request)
    {
        Artisan::call('db:seed', ['--force' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Seeding completed'
        ]);
    }
}
