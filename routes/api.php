<?php

use App\Http\Controllers\Academics\ClassController;
use App\Http\Controllers\Academics\ClassRoutineController;
use App\Http\Controllers\Academics\RoomController;
use App\Http\Controllers\Academics\SubjectController;
use App\Http\Controllers\Academics\TeacherAttendanceController;
use App\Http\Controllers\Academics\TeacherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Enquiry\EnquiryController;
use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\Lead\LeadClosingReasonController;
use App\Http\Controllers\Lead\LeadSetup;
use App\Http\Controllers\Lead\AreaController;
use App\Http\Controllers\Lead\ReferredByController;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth.jwt', 'set.institute'])->group(function () {

    // Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('lead-setup')->group(function () {

        Route::get('/', [LeadSetup::class, 'index']);        // List
        Route::post('/', [LeadSetup::class, 'store']);       // Create
        Route::get('{id}', [LeadSetup::class, 'show']);      // Single
        Route::put('{id}', [LeadSetup::class, 'update']);    // Update
        Route::delete('{id}', [LeadSetup::class, 'destroy']); // Delete
    });

    Route::prefix('lead-closing-reasons')
    ->group(function () {

        Route::get('/', [LeadClosingReasonController::class, 'index']);
        Route::post('/', [LeadClosingReasonController::class, 'store']);
        Route::get('{id}', [LeadClosingReasonController::class, 'show']);
        Route::put('{id}', [LeadClosingReasonController::class, 'update']);
        Route::delete('{id}', [LeadClosingReasonController::class, 'destroy']);
    });

    Route::prefix('areas')
    ->group(function () {
        Route::get('/', [AreaController::class, 'index']);
        Route::post('/', [AreaController::class, 'store']);
        Route::get('{id}', [AreaController::class, 'show']);
        Route::put('{id}', [AreaController::class, 'update']);
        Route::delete('{id}', [AreaController::class, 'destroy']);
    });


    Route::prefix('lead-referredby')
    ->group(function () {
        Route::get('/', [ReferredByController::class, 'index']);
        Route::post('/', [ReferredByController::class, 'store']);
        Route::get('{id}', [ReferredByController::class, 'show']);
        Route::put('{id}', [ReferredByController::class, 'update']);
        Route::delete('{id}', [ReferredByController::class, 'destroy']);
    });

    Route::prefix('enquiries')
    ->group(function () {
        Route::get('/', [EnquiryController::class, 'index']);
        Route::post('/', [EnquiryController::class, 'store']);
        Route::get('{id}', [EnquiryController::class, 'show']);
        Route::put('{id}', [EnquiryController::class, 'update']);
    });

    Route::prefix('classes')
    ->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('{id}', [ClassController::class, 'show']);
        Route::put('{id}', [ClassController::class, 'update']);
    });

    Route::prefix('subjects')
    ->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::get('{id}', [SubjectController::class, 'show']);
        Route::put('{id}', [SubjectController::class, 'update']);
    });

    Route::prefix('class-routines')
    ->group(function () {
        Route::get('/', [ClassRoutineController::class, 'index']);
        Route::post('/', [ClassRoutineController::class, 'store']);
        Route::get('{id}', [ClassRoutineController::class, 'show']);
        Route::put('{id}', [ClassRoutineController::class, 'update']);
    });

    Route::prefix('rooms')
    ->group(function () {
        Route::get('/', [RoomController::class, 'index']);
        Route::post('/', [RoomController::class, 'store']);
        Route::get('{id}', [RoomController::class, 'show']);
        Route::put('{id}', [RoomController::class, 'update']);
    });

    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/teachers/{id}', [TeacherController::class, 'show']);

    Route::get('/teacher-attendance', [TeacherAttendanceController::class, 'index']);
    Route::post('/teacher-attendance', [TeacherAttendanceController::class, 'store']);
    Route::put('/teacher-attendance/{date}', [TeacherAttendanceController::class, 'bulkUpdate']);

    Route::prefix('finance')->group(function(){
        Route::apiResource('category', FinanceCategoryController::class);
    });

});
