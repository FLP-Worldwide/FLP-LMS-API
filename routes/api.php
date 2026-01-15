<?php

use App\Http\Controllers\Academics\BatchController;
use App\Http\Controllers\Academics\ClassController;
use App\Http\Controllers\Academics\ClassRoutineController;
use App\Http\Controllers\Academics\RoomController;
use App\Http\Controllers\Academics\SubjectController;
use App\Http\Controllers\Academics\TeacherAttendanceController;
use App\Http\Controllers\Academics\TeacherController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Assets\AssetAssignmentController;
use App\Http\Controllers\Assets\AssetLocationController;
use App\Http\Controllers\Assets\AssetCategoryController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\PurchaseController;
use App\Http\Controllers\Assets\SupplierController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Enquiry\EnquiryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Fees\FeesStructureController;
use App\Http\Controllers\Fees\FeesStructureInstallmentController;
use App\Http\Controllers\Fees\FeesTypeController;
use App\Http\Controllers\FinanceAccountController;
use App\Http\Controllers\FinanceCategoryController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\Inventory\InventoryCategoryController;
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\InventoryPurchaseController;
use App\Http\Controllers\Inventory\InventoryPurchasePaymentController;
use App\Http\Controllers\Inventory\InventorySaleController;
use App\Http\Controllers\Inventory\InventorySupplierController;
use App\Http\Controllers\Lead\LeadClosingReasonController;
use App\Http\Controllers\Lead\LeadSetup;
use App\Http\Controllers\Lead\AreaController;
use App\Http\Controllers\Lead\ReferredByController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PayerController;
use App\Http\Controllers\StaffManage\LeaveController;
use App\Http\Controllers\Students\StudentController;
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

    Route::prefix('leave')->group(function () {
        Route::apiResource('categories', LeaveController::class)
            ->only(['index', 'store', 'update', 'destroy']);
    });

    Route::prefix('inventory')->group(function () {
        Route::apiResource('suppliers', InventorySupplierController::class);
        Route::apiResource('category', InventoryCategoryController::class);
        Route::apiResource('item', InventoryItemController::class);

        Route::get('purchase', [InventoryPurchaseController::class, 'index']);
        Route::post('purchase', [InventoryPurchaseController::class, 'store']);
        Route::post('purchase/payment', [InventoryPurchasePaymentController::class, 'store']);
        Route::get('purchase/{id}/payments', [InventoryPurchasePaymentController::class, 'index']);
        Route::post('purchase/payment/{id}', [InventoryPurchasePaymentController::class, 'update']);
        Route::delete('purchase/payment/{id}', [InventoryPurchasePaymentController::class, 'destroy']);

        Route::get('purchase/{id}', [InventoryPurchaseController::class, 'show']);
        Route::post('purchase/{id}', [InventoryPurchaseController::class, 'update']);
        Route::delete('purchase/{id}', [InventoryPurchaseController::class, 'destroy']);

        Route::post('/sale',[InventorySaleController::class, 'store']);
        Route::get('/sale', [InventorySaleController::class, 'index']);
        Route::get('/sale/{id}', [InventorySaleController::class, 'show']);
    });

    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('{id}', [StudentController::class, 'show']);
        Route::post('{id}', [StudentController::class, 'update']);
        Route::delete('{id}', [StudentController::class, 'destroy']);
    });


    Route::prefix('fees')->group(function () {
        Route::get('/types', [FeesTypeController::class, 'index']);
        Route::post('/types', [FeesTypeController::class, 'store']);
        Route::delete('/types/{id}', [FeesTypeController::class, 'destroy']);

        // Fees Structure
        Route::post('/structures', [FeesStructureController::class, 'store']);

        Route::post('/structure/installments',[FeesStructureInstallmentController::class, 'store']);

        Route::get('/structures/{feesTypeId}', [FeesStructureController::class, 'byFeesType']);


        Route::get('/structure/installments', [FeesStructureInstallmentController::class, 'index']);
        Route::get('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'show']);
        Route::put('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'update']);
        Route::delete('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'destroy']);

    });



    Route::prefix('finance')->group(function(){
        Route::apiResource('category', FinanceCategoryController::class);
        Route::apiResource('payee', PayeeController::class);
        Route::apiResource('payer', PayerController::class);

        Route::post('/accounts', [FinanceAccountController::class, 'store']);
        Route::get('/accounts', [FinanceAccountController::class, 'index']);
        Route::get('/accounts/{type}/{id}', [FinanceAccountController::class, 'show']);


        Route::post('/expenses', [ExpenseController::class, 'store']);
        Route::get('/expenses', [ExpenseController::class, 'index']);
        Route::get('/expenses/{id}', [ExpenseController::class, 'show']);

        Route::post('/incomes', [IncomeController::class, 'store']);
        Route::get('/incomes', [IncomeController::class, 'index']);

    });

    Route::apiResource('asset-locations', AssetLocationController::class);
    Route::apiResource('asset-categories', AssetCategoryController::class);
    Route::apiResource('assets', AssetController::class);
    Route::apiResource('supplier-master', SupplierController::class);
    Route::get('supplier-master/{id}/categories', [SupplierController::class, 'supplierCategories']);
    Route::apiResource('purchase-assets', PurchaseController::class);

    Route::apiResource('asset-assignments', AssetAssignmentController::class);

    Route::apiResource('courses', CourseController::class);
    Route::apiResource('batches', BatchController::class);



});
