<?php

use App\Http\Controllers\Academics\BatchController;
use App\Http\Controllers\Academics\BatchDetailsController;
use App\Http\Controllers\Academics\ClassController;
use App\Http\Controllers\Academics\ClassRoutineController;
use App\Http\Controllers\Academics\RoomController;
use App\Http\Controllers\Academics\SubjectController;
use App\Http\Controllers\Academics\TeacherAttendanceController;
use App\Http\Controllers\Academics\TeacherController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Fees\FeePaymentApprovalController;
use App\Http\Controllers\Assets\AssetAssignmentController;
use App\Http\Controllers\Assets\AssetLocationController;
use App\Http\Controllers\Assets\AssetCategoryController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\PurchaseController;
use App\Http\Controllers\Assets\SupplierController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\Enquiry\EnquiryController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ExamGradeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Fees\FeeConcessionController;
use App\Http\Controllers\Fees\FeesStructureController;
use App\Http\Controllers\Fees\FeesStructureInstallmentController;
use App\Http\Controllers\Fees\FeesTypeController;
use App\Http\Controllers\Fees\FineManagementController;
use App\Http\Controllers\Fees\RefundReasonController;
use App\Http\Controllers\Fees\StudentFeeController;
use App\Http\Controllers\Fees\StudentFeePaymentController;
use App\Http\Controllers\Fees\StudentFinancialSummaryController;
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
use App\Http\Controllers\Lead\CustomFieldController;
use App\Http\Controllers\Lead\ReferredByController;
use App\Http\Controllers\PayeeController;
use App\Http\Controllers\PayerController;
use App\Http\Controllers\Payroll\UserSalaryTemplateController;
use App\Http\Controllers\Reports\StudentExportController;
use App\Http\Controllers\Settings\AssignmentController;
use App\Http\Controllers\Settings\LiveClassSettingController;
use App\Http\Controllers\Settings\SalaryTemplateController;
use App\Http\Controllers\Settings\StudyMaterialController;
use App\Http\Controllers\Settings\StudyResourceController;
use App\Http\Controllers\Settings\ZoomClassController;
use App\Http\Controllers\StaffManage\LeaveController;
use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentFilterController;
use App\Http\Controllers\Transport\StudentTransportController;
use App\Http\Controllers\Transport\VehicleController;
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

    Route::prefix('custom-fields')->group(function () {
        Route::get('/', [CustomFieldController::class, 'index']);
        Route::post('/', [CustomFieldController::class, 'store']);
        Route::get('{customField}', [CustomFieldController::class, 'show']);
        Route::put('{customField}', [CustomFieldController::class, 'update']);
        Route::delete('{customField}', [CustomFieldController::class, 'destroy']);
    });


    Route::prefix('enquiries')
    ->group(function () {
        Route::get('/', [EnquiryController::class, 'index']);
        Route::post('/', [EnquiryController::class, 'store']);
        Route::get('{id}', [EnquiryController::class, 'show']);
        Route::put('{id}', [EnquiryController::class, 'update']);
        Route::post('{id}/convert-to-student', [EnquiryController::class, 'convertToStudent']);
    });

    Route::prefix('classes')
    ->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('{id}', [ClassController::class, 'show']);
        Route::put('{id}', [ClassController::class, 'update']);
    });

    Route::prefix('transport')->group(function () {

        Route::get('routes', [\App\Http\Controllers\Transport\BusRouteController::class, 'index']);
        Route::post('routes', [\App\Http\Controllers\Transport\BusRouteController::class, 'store']);

        Route::get('routes/{id}', [\App\Http\Controllers\Transport\BusRouteController::class, 'show']);
        Route::put('routes/{id}', [\App\Http\Controllers\Transport\BusRouteController::class, 'update']);
        Route::delete('routes/{id}', [\App\Http\Controllers\Transport\BusRouteController::class, 'destroy']);

        Route::get('vehicles', [\App\Http\Controllers\Transport\VehicleController::class, 'index']);
        Route::post('vehicles', [\App\Http\Controllers\Transport\VehicleController::class, 'store']);
        Route::post('/vehicles/{id}/assign-route',[VehicleController::class, 'assignRoute']);
        Route::post('/vehicles/{id}/assign-driver',[VehicleController::class, 'assignDriver']);

        Route::get('vehicles/{id}', [\App\Http\Controllers\Transport\VehicleController::class, 'show']);
        Route::put('vehicles/{id}', [\App\Http\Controllers\Transport\VehicleController::class, 'update']);
        Route::delete('vehicles/{id}', [\App\Http\Controllers\Transport\VehicleController::class, 'destroy']);

        Route::post('/assign-student',[StudentTransportController::class, 'assign']);
        Route::post('/unassign-student',[StudentTransportController::class, 'unassign']);
        Route::get('/assignments',[StudentTransportController::class, 'index']);
        Route::get('/students',[StudentTransportController::class, 'students']);


    });

    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::post('/', [ExamController::class, 'store']);
        Route::get('{id}', [ExamController::class, 'show']);
        Route::put('{id}', [ExamController::class, 'update']);
        Route::delete('{id}', [ExamController::class, 'destroy']);
        Route::post('/mark-attendance', [ExamController::class, 'markAttendance']);
        Route::post('{id}/cancel', [ExamController::class, 'cancel']);

    });

    Route::prefix('live-class')->group(function () {
        Route::get('settings', [LiveClassSettingController::class, 'show']);
        Route::post('settings', [LiveClassSettingController::class, 'store']);

       Route::prefix('zoom-classes')->group(function () {
            Route::get('/', [ZoomClassController::class, 'index']);
            Route::post('/', [ZoomClassController::class, 'store']);
            Route::get('{id}', [ZoomClassController::class, 'show']);
            Route::put('{id}', [ZoomClassController::class, 'update']);
            Route::delete('{id}', [ZoomClassController::class, 'destroy']);
        });
    });

    Route::prefix('admin/study-materials')->group(function () {
        Route::get('/', [StudyMaterialController::class, 'index']);
        Route::post('/', [StudyMaterialController::class, 'store']);
        Route::get('{id}', [StudyMaterialController::class, 'show']);
        Route::post('{id}', [StudyMaterialController::class, 'update']); // multipart-safe
        Route::delete('{id}', [StudyMaterialController::class, 'destroy']);
    });
    Route::prefix('admin/resources')->group(function () {
        Route::get('/', [StudyResourceController::class, 'index']);

        Route::post('folder', [StudyResourceController::class, 'createFolder']);
        Route::post('upload', [StudyResourceController::class, 'uploadFile']);
        Route::post('link', [StudyResourceController::class, 'addLink']);

        Route::delete('{id}', [StudyResourceController::class, 'destroy']);
    });

    Route::prefix('academic-years')->group(function () {

        Route::get('/', [AcademicYearController::class, 'index']);
        Route::post('/', [AcademicYearController::class, 'store']);
        Route::get('{id}', [AcademicYearController::class, 'show']);
        Route::put('{id}', [AcademicYearController::class, 'update']);
        Route::delete('{id}', [AcademicYearController::class, 'destroy']);

    });


    Route::prefix('assignments')->group(function () {
        Route::post('/', [AssignmentController::class, 'store']);
        Route::get('grouped', [AssignmentController::class, 'grouped']);
        Route::get('{id}', [AssignmentController::class, 'show']);
        Route::put('update/{id}', [AssignmentController::class, 'update']);

    });

    Route::prefix('payroll/salary-templates')->group(function () {
        Route::get('/', [SalaryTemplateController::class, 'index']);
        Route::post('/', [SalaryTemplateController::class, 'store']);
        Route::get('{id}', [SalaryTemplateController::class, 'show']);
        Route::put('{id}', [SalaryTemplateController::class, 'update']);
        Route::delete('{id}', [SalaryTemplateController::class, 'destroy']);
    });



    Route::prefix('exam-grades')->group(function () {
        Route::get('/', [ExamGradeController::class, 'index']);
        Route::post('/', [ExamGradeController::class, 'store']);
        Route::put('{id}', [ExamGradeController::class, 'update']);
        Route::delete('{id}', [ExamGradeController::class, 'destroy']);
    });


    Route::prefix('staff')->group(function () {
        Route::get('drivers', [\App\Http\Controllers\Staff\DriverController::class, 'index']);
        Route::post('drivers', [\App\Http\Controllers\Staff\DriverController::class, 'store']);
        Route::get('drivers/{id}', [\App\Http\Controllers\Staff\DriverController::class, 'show']);
        Route::put('drivers/{id}', [\App\Http\Controllers\Staff\DriverController::class, 'update']);
        Route::delete('drivers/{id}', [\App\Http\Controllers\Staff\DriverController::class, 'destroy']);

        Route::get('all', [\App\Http\Controllers\Staff\StaffController::class,'index']);
        Route::get('attendance/{user_id}', [\App\Http\Controllers\Staff\StaffController::class,'showAttendance']);
        Route::put('attendance/{user_id}', [\App\Http\Controllers\Staff\StaffController::class,'updateAttendance']);
        Route::post('create', [\App\Http\Controllers\Staff\StaffOnboardingController::class, 'store']);
        Route::delete('{id}', [\App\Http\Controllers\Staff\StaffOnboardingController::class, 'destroy']);
        Route::get('{id}', [\App\Http\Controllers\Staff\StaffController::class, 'show']);
        Route::get('{id}/attendance', [\App\Http\Controllers\Staff\StaffController::class, 'attendance']);
        Route::put('{id}', [\App\Http\Controllers\Staff\StaffController::class, 'update']);
    });

    Route::prefix('payroll')->group(function () {
        Route::post('assign-salary-template', [UserSalaryTemplateController::class, 'store']);
        Route::get('assign-salary-template/{user_id}', [UserSalaryTemplateController::class, 'show']);

        Route::get('salary-overview/{user}',[UserSalaryTemplateController::class, 'salaryOverview']);
    });


    Route::prefix('settings/roles')->group(function () {
        Route::get('/', [App\Http\Controllers\Settings\RoleController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Settings\RoleController::class, 'store']);
        Route::put('{id}', [App\Http\Controllers\Settings\RoleController::class, 'update']);
        Route::delete('{id}', [App\Http\Controllers\Settings\RoleController::class, 'destroy']);
    });



    Route::prefix('subjects')
    ->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::get('{id}', [SubjectController::class, 'show']);
        Route::put('{id}', [SubjectController::class, 'update']);
    });

    Route::prefix('topics')->group(function () {

        Route::get('/', [\App\Http\Controllers\Academics\TopicController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Academics\TopicController::class, 'store']);

        Route::get('{id}', [\App\Http\Controllers\Academics\TopicController::class, 'show']);
        Route::put('{id}', [\App\Http\Controllers\Academics\TopicController::class, 'update']);
        Route::delete('{id}', [\App\Http\Controllers\Academics\TopicController::class, 'destroy']);

    });


    Route::prefix('class-routines')
    ->group(function () {
        Route::get('/', [ClassRoutineController::class, 'index']);
        Route::post('/', [ClassRoutineController::class, 'store']);
        Route::get('schedule/by-date', [ClassRoutineController::class, 'scheduleByDate']);

        Route::get('schedule/today-classes', [ClassRoutineController::class, 'listClassAttendance']);
        Route::get('/mark-attendance', [ClassRoutineController::class, 'todayClassesAttendance']);
        Route::post('/mark-attendance', [ClassRoutineController::class, 'markAttendance']);

        Route::post('cancel-single', [ClassRoutineController::class, 'cancelSingleClass']);
        Route::post('reschedule-single', [ClassRoutineController::class, 'rescheduleSingleClass']);

        Route::get('{id}', [ClassRoutineController::class, 'show']);
        Route::put('{id}', [ClassRoutineController::class, 'update']);

        Route::delete('{id}', [ClassRoutineController::class, 'deleteSchedule'])
            ->whereNumber('id');

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
    Route::get('/staff-leaves', [TeacherAttendanceController::class, 'staffOnLeave']);

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
        Route::get('/filter', [StudentFilterController::class, 'index']);
        Route::get('/financial-summary', [StudentFilterController::class, 'financialIndex']);
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);

        Route::get('{id}', [StudentController::class, 'show']);
        Route::put('{id}', [StudentController::class, 'update']);
        Route::delete('{id}', [StudentController::class, 'destroy']);
        Route::get('{id}/fees', [StudentController::class, 'fees']);

    });


    Route::prefix('fees')->group(function () {
        Route::get('/types', [FeesTypeController::class, 'index']);
        Route::post('/types', [FeesTypeController::class, 'store']);
        Route::delete('/types/{id}', [FeesTypeController::class, 'destroy']);

        // Fees Structure
        Route::post('/structures', [FeesStructureController::class, 'store']);
        Route::get('/structures/by-class/{classId}', [FeesStructureController::class, 'byClass']);
        Route::get('/structures/by-fees-type/{feesTypeId}', [FeesStructureController::class, 'byFeesType']);

        Route::post('/structure/installments',[FeesStructureInstallmentController::class, 'store']);

        Route::get('/structures/{feesTypeId}', [FeesStructureController::class, 'byFeesType']);


        Route::get('/structure/installments', [FeesStructureInstallmentController::class, 'index']);
        Route::get('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'show']);
        Route::put('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'update']);
        Route::delete('/structure/installments/{id}', [FeesStructureInstallmentController::class, 'destroy']);

        Route::prefix('/fine/manage')->group(function () {
            Route::get('/', [FineManagementController::class, 'index']);
            Route::post('/', [FineManagementController::class, 'store']);
            Route::get('/{id}', [FineManagementController::class, 'show']);
            Route::put('/{id}', [FineManagementController::class, 'update']);
            Route::delete('/{id}', [FineManagementController::class, 'destroy']);
        });

        Route::prefix('/refund-reasons')->group(function () {
            Route::get('/', [RefundReasonController::class, 'index']);
            Route::post('/', [RefundReasonController::class, 'store']);
            Route::put('/{id}', [RefundReasonController::class, 'update']);
            Route::delete('/{id}', [RefundReasonController::class, 'destroy']); // optional
        });

        Route::post(
            '/assign-to-student',
            [StudentFeeController::class, 'assign']
        );
        Route::post('/update/payments', [StudentFeePaymentController::class, 'store']);
        Route::get('/payments', [StudentFeePaymentController::class, 'index']);
        Route::post('/payment/{id}/approve', [FeePaymentApprovalController::class, 'approve']);
        Route::get('/payments/{id}', [StudentFeePaymentController::class, 'studentList']);
        Route::get('/payments-list/{id}', [StudentFeePaymentController::class, 'show']);
        // student/{studentId}/payments
        Route::get('/student/{id}/payments', [StudentFeePaymentController::class, 'studentPayments']);
        // fees/payments/{paymentId}/refund
        Route::post('/payments/{paymentId}/refund', [StudentFeePaymentController::class, 'refund']);


        Route::get('student/{id}/financial-summary', [StudentFinancialSummaryController::class, 'show']);

        Route::get('student/{id}/refund-summary', [StudentFinancialSummaryController::class, 'refundSummary']);
        Route::get('student/{id}/concession-summary', [StudentFinancialSummaryController::class, 'concessionSummary']);

        Route::post('student/{id}/add-concession', [FeePaymentApprovalController::class, 'addConcession']);

        // fees/concessions
        Route::prefix('concessions')->group(function () {
            Route::apiResource('/', FeeConcessionController::class);
            Route::get('students', [FeeConcessionController::class, 'students']);
            Route::post('assign', [FeeConcessionController::class, 'assignToStudent']);
            // concessions/by-batch
            Route::get('by-batch', [FeeConcessionController::class, 'byBatch']);
        });
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

     Route::prefix('batch')->group(function(){
    // {batchId}/details?
        Route::get('{batchId}/details', [BatchDetailsController::class, 'batchDetails']);
     });


      Route::prefix('reports')
        ->group(function () {
            Route::get('/students/export', [StudentExportController::class, 'export']);
            Route::get('/students/import/template', [StudentExportController::class, 'downloadTemplate']);


        });

});
