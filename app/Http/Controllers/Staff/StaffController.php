<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\StaffDetail;
use App\Models\InstituteUser;
use App\Models\User;
use App\Models\UserAttendance;
use App\Models\UserSalaryTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        /**
         * 🔹 Role filter (optional)
         * teacher | driver | accountant | admin | staff
         */
        $role = $request->role;

        /**
         * ------------------------------------
         * 1️⃣ TEACHERS
         * ------------------------------------
         */
        $teachers = collect();

        if (!$role || $role === 'teacher') {
            $teachers = Teacher::with('detail')
                ->get()
                ->map(function ($t) {

                    // 🔗 Resolve user_id via institute_users + email
                    $userId = InstituteUser::where('role', 'teacher')
                        ->whereHas('user', function ($q) use ($t) {
                            $q->where('email', $t->detail?->email);
                        })
                        ->value('user_id');

                    $user = $userId ? User::find($userId) : null;

                    $lastLoginTs = $user
                        ? $this->getLastLoginForUser($user->id)
                        : null;

                    return [
                        'type' => 'teacher',
                        'id' => $t->id,
                        'user_id' => $userId,

                        'name' => trim($t->first_name . ' ' . $t->last_name),
                        'designation' => $t->designation,
                        'department' => $t->department,
                        'status' => $t->status,
                        'joining_date' => $t->joining_date,

                        'phone' => $t->detail?->phone,
                        'email' => $t->detail?->email,

                        // 💰 Salary Template
                        'salary_template' => $userId
                            ? $this->salaryTemplateForUser($userId)
                            : ['assigned' => false],

                        // 🔐 Login Credentials (ADMIN ONLY)
                        'credentials' => [
                            'username' => $t->detail?->phone ?? $t->detail?->email,
                            'password' => $user?->temp_password,
                            'is_visible' => true,
                        ],

                        // 🕒 Last Login
                        'last_login' => $lastLoginTs
                            ? Carbon::createFromTimestamp($lastLoginTs)->toDateTimeString()
                            : null,
                    ];
                });
        }

        /**
         * ------------------------------------
         * 2️⃣ OTHER STAFF (Driver, Accountant, Admin, etc.)
         * ------------------------------------
         */
        $staff = collect();

        if (!$role || $role !== 'teacher') {

            $staffQuery = StaffDetail::with('user');



            if ($role) {
                $staffQuery->whereHas('user', function ($q) use ($role) {
                    $q->where('role', $role);
                });
            }


            $staff = $staffQuery->get()->map(function ($s) {

                $user = $s->user;

                $lastLoginTs = $user
                    ? $this->getLastLoginForUser($user->id)
                    : null;

                return [
                    'type' => 'staff',
                    'id' => $s->user_id,
                    'user_id' => $s->user_id,

                    'name' => $user?->name,
                    'designation' => $s->designation,
                    'joining_date' => $s->joining_date,

                    'phone' => $s->phone,
                    'email' => $user?->email,
                    'is_active' => $s->is_active,
                    'address' => $s->address,

                    // 💰 Salary Template
                    'salary_template' => $this->salaryTemplateForUser($s->user_id),

                    // 🔐 Login Credentials (ADMIN ONLY)
                    'credentials' => [
                        'username' => $user->email ?? $user?->phone,
                        'password' => $user?->temp_password,
                        'is_visible' => true,
                    ],

                    // 🕒 Last Login
                    'last_login' => $lastLoginTs
                        ? Carbon::createFromTimestamp($lastLoginTs)->toDateTimeString()
                        : null,
                ];
            });
        }

        /**
         * ------------------------------------
         * 3️⃣ MERGE & SORT
         * ------------------------------------
         */
        $data = $teachers
            ->merge($staff)
            ->sortBy('name')
            ->values();

        return response()->json([
            'status' => 'success',
            'count' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * ------------------------------------
     * 💰 Salary Template Resolver
     * ------------------------------------
     */
    private function salaryTemplateForUser($userId)
    {
        $mapping = UserSalaryTemplate::with('template')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$mapping) {
            return [
                'assigned' => false,
            ];
        }

        return [
            'assigned' => true,
            'salary_type' => $mapping->salary_type,
            'template_id' => $mapping->salary_template_id,
            'template_name' => $mapping->template?->name,
        ];
    }

    public function showAttendance(Request $request, $userId){
            $from = $to = Carbon::today();

        if ($request->date) {
            $from = $to = Carbon::parse($request->date);
        }

        if ($request->month) {
            $from = Carbon::parse($request->month . '-01')->startOfMonth();
            $to   = $from->copy()->endOfMonth();
        }

        if ($request->year) {
            $from = Carbon::create($request->year, 1, 1)->startOfYear();
            $to   = $from->copy()->endOfYear();
        }

        if ($request->from && $request->to) {
            $from = Carbon::parse($request->from);
            $to   = Carbon::parse($request->to);
        }

        /**
         * -----------------------------
         * RESOLVE USER
         * -----------------------------
         */
        $user = User::with('staffDetail')->findOrFail($userId);

        /**
         * -----------------------------
         * CHECK IF TEACHER
         * -----------------------------
         */
        $teacher = Teacher::whereHas('detail', function ($q) use ($user) {
            $q->where('email', $user->email);
        })->first();

        /**
         * -----------------------------
         * FETCH ATTENDANCE
         * -----------------------------
         */
        $attendanceQuery = UserAttendance::whereBetween(
            'attendance_date',
            [$from, $to]
        );

        if ($teacher) {
            $attendanceQuery->where('teacher_id', $teacher->id);
        } else {
            $attendanceQuery->where('user_id', $user->id);
        }

        $attendances = $attendanceQuery
            ->orderBy('attendance_date')
            ->get();

        /**
         * -----------------------------
         * FORMAT RESPONSE
         * -----------------------------
         */
        return response()->json([
            'status' => 'success',

            'filter' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],

            'staff' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'designation' => $teacher
                    ? $teacher->designation
                    : $user->staffDetail?->designation,
                'department' => $teacher?->department,
                'phone' => $teacher
                    ? $teacher->detail?->phone
                    : $user->staffDetail?->phone,
                'email' => $user->email,
            ],

            'attendance' => $attendances->map(fn ($a) => [
                'date' => Carbon::parse($a->attendance_date)->toDateString(),
                'status' => $a->status,
            ]),
        ]);
    }


    public function updateAttendance(Request $request, $userId)
    {
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'status' => 'required|in:P,A,LP,HP,L,H',
        ]);

        // 🔎 Find user
        $user = User::findOrFail($userId);

        // 🔎 Check if user is a teacher
        $teacher = Teacher::whereHas('detail', function ($q) use ($user) {
            $q->where('email', $user->email);
        })->first();

        // 🛑 Prevent future update
        if (Carbon::parse($validated['date'])->isFuture()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Future attendance cannot be updated',
            ], 422);
        }

        // ✅ Save attendance
        $attendance = UserAttendance::updateOrCreate(
            [
                'attendance_date' => $validated['date'],
                'teacher_id' => $teacher?->id,
                'user_id' => $teacher ? null : $user->id,
            ],
            [
                'status' => $validated['status'],
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated successfully',
            'data' => [
                'user_id' => $user->id,
                'date' => $attendance->attendance_date->toDateString(),
                'status' => $attendance->status,
            ],
        ]);
    }
    /**
     * ------------------------------------
     * 🕒 LAST LOGIN (DATABASE SESSIONS)
     * ------------------------------------
     */



    private function getLastLoginForUser($userId)
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderByDesc('last_activity')
            ->value('last_activity');
    }
}
