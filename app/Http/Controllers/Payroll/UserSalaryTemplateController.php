<?php

namespace App\Http\Controllers\Payroll;

use App\Exports\SalaryTemplateExport;
use App\Exports\UserSalaryTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAttendance;
use App\Models\UserSalaryTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UserSalaryTemplateController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'salary_template_id' => 'required|exists:salary_templates,id',
            'salary_type' => 'required|in:monthly,hourly',
        ]);

        return DB::transaction(function () use ($data) {

            // 🔥 STEP 1: Soft delete ALL previous records for this user
            UserSalaryTemplate::where('user_id', $data['user_id'])
                ->whereNull('deleted_at')
                ->delete();

            // 🔥 STEP 2: Insert ONLY ONE fresh record
            $mapping = UserSalaryTemplate::create([
                'user_id' => $data['user_id'],
                'salary_template_id' => $data['salary_template_id'],
                'salary_type' => $data['salary_type'],
                'is_active' => true,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Salary template updated successfully',
                'data' => $mapping->load('template'),
            ], 201);
        });
    }

public function salaryOverview(User $user)
{
    // 1️⃣ Load required relations (SAFE)
    $user->load([
        'instituteUser.role',
        'staffDetail',
    ]);

    // 2️⃣ Resolve role-based profile
    $isTeacher = optional($user->instituteUser?->role)->slug === 'teacher';

    $profile = $isTeacher
        ? [
            'type' => 'teacher',
            'designation' => 'Teacher',
            'department' => null,
        ]
        : [
            'type' => 'staff',
            'designation' => optional($user->staffDetail)->designation,
            'department' => optional($user->staffDetail)->department,
        ];

    // 3️⃣ Get latest ACTIVE salary mapping (IMPORTANT)
    $salaryMapping = UserSalaryTemplate::with('salaryTemplate')
        ->where('user_id', $user->id)
        ->where('is_active', 1)
        ->whereNull('deleted_at')
        ->latest('id')
        ->first();

    $salaryTemplate = $salaryMapping?->salaryTemplate;

    // 4️⃣ Attendance summary (current month)
    $start = now()->startOfMonth();
    $end   = now()->endOfMonth();

    $attendance = UserAttendance::where('user_id', $user->id)
        ->whereBetween('attendance_date', [$start, $end])
        ->get();

    $attendanceSummary = [
        'working_days' => $attendance->count(),
        'present' => $attendance->where('status', 'P')->count(),
        'absent' => $attendance->where('status', 'A')->count(),
        'half_day' => $attendance->where('status', 'H')->count(),
    ];

    // 5️⃣ Salary preview (optional – simple version)
    $salaryPreview = null;

    if ($salaryTemplate && $salaryTemplate->type === 'monthly') {
        $basic = $salaryTemplate->salary['basic'] ?? 0;

        if ($basic > 0) {
            $perDay = $basic / max($attendanceSummary['working_days'], 1);
            $deduction = $attendanceSummary['absent'] * $perDay;

            $salaryPreview = [
                'basic' => $basic,
                'attendance_deduction' => round($deduction, 2),
                'net_payable' => max(round($basic - $deduction, 2), 0),
            ];
        }
    }

    // 6️⃣ FINAL RESPONSE (FRONTEND READY)
    return response()->json([
        'status' => 'success',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => optional($user->instituteUser?->role)->name,
                'last_login' => null,
                ...$profile
            ],

            'salary_template' => $salaryTemplate ? [
                'id' => $salaryTemplate->id,
                'name' => $salaryTemplate->name,
                'type' => $salaryTemplate->type,
                'salary' => $salaryTemplate->salary,
                'allowances' => $salaryTemplate->allowances,
                'deductions' => $salaryTemplate->deductions,
                'summary' => $salaryTemplate->summary,
            ] : null,

            'attendance_summary' => $attendanceSummary,

            'salary_preview' => $salaryPreview,
        ]
    ]);
}



    public function show($userId)
    {
        $mapping = UserSalaryTemplate::with('template')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => $mapping,
        ]);
    }


    public function export()
    {
        return Excel::download(
            new SalaryTemplateExport('monthly'),
            'monthly_salary_templates.xlsx'
        );
    }

    public function hourlyexport()
    {
        return Excel::download(
            new SalaryTemplateExport('hourly'),
            'hourly_salary_templates.xlsx'
        );
    }

    // manageSalaryExport
    public function manageSalaryExport()
    {
        return Excel::download(
            new UserSalaryTemplateExport,
            'manage_salary_templates.xlsx'
        );
    }

}
