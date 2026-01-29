<?php

use App\Http\Controllers\ConfigController;
use App\Http\Controllers\DevOpsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/deploy/migrate', [DevOpsController::class, 'migrate']);
Route::get('/deploy/migrate-fresh', [DevOpsController::class, 'migrateFresh']);
Route::get('/deploy/seed', [DevOpsController::class, 'seed']);


// Route:: get('/', [ConfigController::class, 'routesView']);
