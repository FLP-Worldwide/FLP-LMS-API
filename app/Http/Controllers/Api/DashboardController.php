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
    ClassRoom,
    ClassRoutine,
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
        $currentTime = now()->format('H:i:s');

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

        /* ===================== PAYMENT MODES ===================== */
        $paymentModes = StudentFeePayment::where('status', 'APPROVED')
            ->selectRaw('payment_mode, SUM(amount) as total')
            ->groupBy('payment_mode')
            ->pluck('total', 'payment_mode')
            ->toArray();

        /* ===================== LEAD STATS ===================== */
        $leadStats = Enquiry::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        /* ===================== BIRTHDAYS ===================== */
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
            ])
            ->values();

        /* ===================== RUNNING BATCHES ===================== */
        $runningBatches = Batch::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('course:id,name,standard_id')
            ->get()
            ->map(function ($batch) use ($today) {

                $dayName = $today->format('l');
                $currentTime = now()->format('H:i:s');

                /*
                ===============================
                ✅ FIXED STUDENT COUNT
                ===============================
                */

                $studentsCount = 0;

                if ($batch->course && $batch->course->standard_id) {
                    $studentsCount = Student::where('class', $batch->course->standard_id)
                        ->count();
                }

                /*
                ===============================
                TODAY CLASSES
                ===============================
                */

                $routines = ClassRoutine::with([
                        'teacher:id,first_name,last_name',
                        'subject:id,name',
                        'days'
                    ])
                    ->where('batch_id', $batch->id)
                    ->get();

                $todayClasses = collect();

                foreach ($routines as $routine) {

                    $show = false;

                    if ($routine->repeat_type === 'Does Not Repeat') {
                        $show = $routine->base_date == $today->toDateString();
                    }

                    elseif ($routine->repeat_type === 'Weekly') {
                        $days = $routine->days
                            ->whereNull('specific_date')
                            ->pluck('day')
                            ->toArray();

                        if (in_array($dayName, $days) && $today->toDateString() >= $routine->base_date) {
                            $show = true;
                        }
                    }

                    elseif ($routine->repeat_type === 'Daily') {
                        if ($today->toDateString() >= $routine->base_date) {
                            $show = true;
                        }
                    }

                    elseif ($routine->repeat_type === 'Select Dates') {
                        $dates = $routine->days
                            ->whereNotNull('specific_date')
                            ->pluck('specific_date')
                            ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                            ->toArray();

                        $show = in_array($today->toDateString(), $dates);
                    }

                    if (!$show) continue;

                    $status = 'upcoming';

                    if ($currentTime >= $routine->start_time && $currentTime <= $routine->end_time) {
                        $status = 'ongoing';
                    }

                    if ($currentTime > $routine->end_time) {
                        $status = 'completed';
                    }

                    $todayClasses->push([
                        'routine_id' => $routine->id,
                        'subject' => $routine->subject?->name,
                        'teacher' => $routine->teacher
                            ? $routine->teacher->first_name.' '.$routine->teacher->last_name
                            : null,
                        'start_time' => date('h:i A', strtotime($routine->start_time)),
                        'end_time'   => date('h:i A', strtotime($routine->end_time)),
                        'class_type' => $routine->class_type,
                        'status' => $status,
                    ]);
                }

                return [
                    'batch_id' => $batch->id,
                    'batch_name' => $batch->name,
                    'course_name' => $batch->course?->name,
                    'start_date' => $batch->start_date,
                    'end_date' => $batch->end_date,
                    'students_count' => $studentsCount,
                    'today_classes' => $todayClasses->sortBy('start_time')->values(),
                ];
            });


        /* ===================== FINAL RESPONSE ===================== */
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->uid,
                    'name' => $user->name,
                    'role' => $user->role,
                ],

                'institute' => $institute,
                'subscription' => $subscription,

                'dashboard' => [

                    'students' => [
                        'total' => $totalStudents,
                        'added_today' => $studentsAddedToday,
                        'gender' => [
                            'male' => $genderStats['male'] ?? 0,
                            'female' => $genderStats['female'] ?? 0,
                            'others' => $genderStats['other'] ?? 0,
                            'na' => $genderStats['NA'] ?? 0,
                        ],
                    ],

                    'today_fee_stats' => [
                        'collection' => $todayCollection ?? 0,
                        'dues' => $todayDues ?? 0,
                    ],

                    'overall_fee_stats' => [
                        'fees' => $totalFees ?? 0,
                        'concession' => $totalConcession ?? 0,
                        'total_fees' => $totalPayable ?? 0,
                        'collected' => $totalCollected ?? 0,
                        'total_dues' => $totalDues ?? 0,
                    ],

                    'payment_modes' => $paymentModes ?? [],

                    'lead_stats' => [
                        'total' => $leadStats->sum() ?? 0,
                        'open' => $leadStats['OPEN'] ?? 0,
                        'in_progress' => $leadStats['IN_PROGRESS'] ?? 0,
                        'admitted' => $leadStats['ADMITTED'] ?? 0,
                        'closed' => $leadStats['CLOSED'] ?? 0,
                    ],

                    'birthdays_today' => $birthdays ?? [],

                    'running_batches' => $runningBatches ?? [],
                ],

                'modules' => $permissions->pluck('key')->values(),
            ],
        ]);
    }


}
