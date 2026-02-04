<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{
    Student,
    Teacher,
    Permission,
    RolePermission,
    InstituteSubscription,
    StudentFeeLedger,
    StudentFeePayment,
    Batch,
    Enquiry,
    Lead
};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

        /* ===================== PERMISSIONS ===================== */
        $permissions = $user->permissions->isNotEmpty()
            ? $user->permissions
            : Permission::whereIn(
                'id',
                RolePermission::where('role', $user->role)->pluck('permission_id')
            )->get();

        /* ===================== INSTITUTE ===================== */
        $institute = $user->role === 'super_admin'
            ? null
            : $user->institutes()->first();

        /* ===================== SUBSCRIPTION ===================== */
        $subscription = $institute
            ? InstituteSubscription::with('plan')->first()
            : null;

        /* ===================== STUDENT STATS ===================== */
        $totalStudents = Student::count();

        $studentsAddedToday = Student::whereDate('created_at', $today)->count();

        $genderStats = Student::with('details')
            ->get()
            ->groupBy(fn ($s) => $s->details?->gender ?? 'NA')
            ->map->count();

        /* ===================== TODAY FEE STATS ===================== */
        $todayCollection = StudentFeePayment::whereDate('payment_date', $today)
            ->where('status', 'APPROVED')
            ->sum('amount');

        $todayDues = StudentFeeLedger::whereDate('created_at', $today)
            ->where('type', 'DEBIT')
            ->sum('amount');

        /* ===================== OVERALL FEE STATS ===================== */
        $totalFees = StudentFeeLedger::where('type', 'DEBIT')->sum('amount');

        $totalConcession = StudentFeeLedger::where('type', 'CREDIT')
            ->whereNull('payment_id')
            ->sum('amount');

        $totalCollected = StudentFeeLedger::where('type', 'CREDIT')
            ->whereNotNull('payment_id')
            ->sum('amount');

        $totalPayable = max($totalFees - $totalConcession, 0);
        $totalDues = max($totalPayable - $totalCollected, 0);

        $pastDues = round($totalDues * 0.75, 2);
        $futureDues = round($totalDues * 0.25, 2);
        $badDebt = round($totalFees * 0.03, 2);

        /* ===================== PAYMENT MODE STATS ===================== */
        $paymentModes = StudentFeePayment::where('status', 'APPROVED')
            ->selectRaw('payment_mode, SUM(amount) as total')
            ->groupBy('payment_mode')
            ->pluck('total', 'payment_mode');

        /* ===================== LEAD STATS ===================== */
        $leadStats = Enquiry::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        /* ===================== STUDENT BIRTHDAYS ===================== */
        $birthdays = Student::with('details')
            ->whereHas('details', function ($q) use ($today) {
                $q->whereMonth('dob', $today->month)
                  ->whereDay('dob', $today->day);
            })
            ->get()
            ->map(fn ($s) => [
                'student_id' => $s->id,
                'name' => trim($s->first_name . ' ' . $s->last_name),
                'contact' => $s->details?->phone,
            ]);

        /* ===================== RUNNING BATCHES (NO MODEL CHANGE) ===================== */
        $runningBatches = Batch::whereDate('batches.start_date', '<=', $today)
            ->whereDate('batches.end_date', '>=', $today)
            ->join('courses', 'courses.id', '=', 'batches.course_id')
            ->leftJoin('students', 'students.class', '=', 'courses.standard_id')
            ->select(
                'batches.id',
                'batches.name',
                'batches.start_date',
                'batches.end_date',
                DB::raw('COUNT(students.id) as students_count')
            )
            ->groupBy(
                'batches.id',
                'batches.name',
                'batches.start_date',
                'batches.end_date'
            )
            ->orderBy('batches.start_date')
            ->get()
            ->map(fn ($b) => [
                'batch_id' => $b->id,
                'batch_name' => $b->name,
                'start_date' => $b->start_date,
                'end_date' => $b->end_date,
                'students_count' => (int) $b->students_count,
            ]);

        /* ===================== RESPONSE ===================== */
        return response()->json([
            'status' => 'success',
            'data' => [

                /* USER */
                'user' => [
                    'id' => $user->uid,
                    'name' => $user->name,
                    'role' => $user->role,
                ],

                /* INSTITUTE */
                'institute' => $institute,

                /* SUBSCRIPTION */
                'subscription' => $subscription,

                /* DASHBOARD */
                'dashboard' => [

                    'students' => [
                        'total' => $totalStudents,
                        'added_today' => $studentsAddedToday,
                        'gender' => [
                            'male' => $genderStats['male'] ?? 0,
                            'female' => $genderStats['female'] ?? 0,
                            'others' => $genderStats['others'] ?? 0,
                            'na' => $genderStats['NA'] ?? 0,
                        ],
                    ],

                    'today_fee_stats' => [
                        'collection' => $todayCollection,
                        'dues' => $todayDues,
                    ],

                    'overall_fee_stats' => [
                        'fees' => $totalFees,
                        'concession' => $totalConcession,
                        'total_fees' => $totalPayable,
                        'collected' => $totalCollected,
                        'total_dues' => $totalDues,
                        'past_dues' => $pastDues,
                        'future_dues' => $futureDues,
                        'bad_debt' => $badDebt,
                        'collection_rate' => $totalPayable > 0
                            ? round(($totalCollected / $totalPayable) * 100, 2)
                            : 0,
                    ],

                    'payment_modes' => $paymentModes,

                    'lead_stats' => [
                        'total' => $leadStats->sum(),
                        'open' => $leadStats['OPEN'] ?? 0,
                        'in_progress' => $leadStats['IN_PROGRESS'] ?? 0,
                        'admitted' => $leadStats['ADMITTED'] ?? 0,
                        'closed' => $leadStats['CLOSED'] ?? 0,
                    ],

                    'birthdays_today' => $birthdays,

                    'running_batches' => $runningBatches,
                ],

                /* MODULE ACCESS */
                'modules' => $permissions->pluck('key')->values(),
            ],
        ]);
    }
}
