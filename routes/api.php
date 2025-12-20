<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.jwt', 'set.institute'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

});
