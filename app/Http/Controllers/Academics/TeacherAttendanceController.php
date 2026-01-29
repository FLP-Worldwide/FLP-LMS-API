<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\StaffDetail;
use App\Models\UserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    /**
     * 📅 Get attendance (date / month / range / year)
     */
    public function index(Request $request)
    {
        /**
         * -----------------------------
         * DATE FILTER LOGIC
         * -----------------------------
         */
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
         * TEACHERS QUERY
         * -----------------------------
         */
        $teachers = Teacher::with([
            'detail',
            'attendances' => fn ($q) =>
                $q->whereBetween('attendance_date', [$from, $to])
        ])
        ->when($request->teacher_id, fn ($q) =>
            $q->where('id', $request->teacher_id)
        )
        ->when($request->department, fn ($q) =>
            $q->where('department', $request->department)
        )
        ->get();

        /**
         * -----------------------------
         * STAFF QUERY (EXCEPT ADMIN)
         * -----------------------------
         */
        $staff = StaffDetail::with('user')
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'admin'))
            ->get()
            ->map(function ($s) use ($from, $to) {

                $attendances = $this->getUserAttendances(
                    $s->user_id,
                    $from,
                    $to
                );

                // 🔹 Single day
                if ($from->equalTo($to)) {
                    $attendance = $attendances->first();

                    return [
                        'type' => 'staff',
                        'profile' => [
                            'id' => $s->user_id,
                            'name' => $s->user?->name,
                            'designation' => $s->designation,
                            'phone' => $s->phone,
                            'email' => $s->user?->email,
                        ],
                        'attendance' => $attendance ? [
                            'date' => $attendance->attendance_date->toDateString(),
                            'status' => $attendance->status,
                        ] : null,
                    ];
                }

                // 🔹 Multi-day
                return [
                    'type' => 'staff',
                    'profile' => [
                        'id' => $s->user_id,
                        'name' => $s->user?->name,
                        'designation' => $s->designation,
                    ],
                    'attendance' => $attendances
                        ->groupBy(fn ($a) => $a->attendance_date->toDateString())
                        ->map(fn ($rows) => $rows->first()->status),
                ];
            });

        /**
         * -----------------------------
         * RESPONSE
         * -----------------------------
         */
        return response()->json([
            'status' => 'success',
            'filter' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'data' => [

                /**
                 * =============================
                 * TEACHERS
                 * =============================
                 */
                'teachers' => $teachers->map(function ($teacher) use ($from, $to) {

                    if ($from->equalTo($to)) {
                        $attendance = $teacher->attendances->first();

                        return [
                            'type' => 'teacher',
                            'profile' => [
                                'id' => $teacher->id,
                                'name' => trim($teacher->first_name . ' ' . $teacher->last_name),
                                'department' => $teacher->department,
                                'designation'=> $teacher->designation,
                                'phone' => $teacher->detail->phone ?? null,
                                'email' => $teacher->detail->email ?? null,
                            ],
                            'attendance' => $attendance ? [
                                'date' => $attendance->attendance_date->toDateString(),
                                'status' => $attendance->status,
                            ] : null,
                        ];
                    }

                    return [
                        'type' => 'teacher',
                        'profile' => [
                            'id' => $teacher->id,
                            'name' => trim($teacher->first_name . ' ' . $teacher->last_name),
                            'department' => $teacher->department,
                            'designation'=> $teacher->designation,
                        ],
                        'attendance' => $teacher->attendances
                            ->groupBy(fn ($a) => $a->attendance_date->toDateString())
                            ->map(fn ($rows) => $rows->first()->status),
                    ];
                }),

                /**
                 * =============================
                 * STAFF
                 * =============================
                 */
                'staff' => $staff,
            ],
        ]);
    }

    /**
     * 📝 Mark attendance (TEACHER + STAFF)
     */
   public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',

            'records' => 'required|array|min:1',

            // ✅ Either teacher_id OR user_id (never both required)
            'records.*.teacher_id' => 'nullable|required_without:records.*.user_id|exists:teachers,id',
            'records.*.user_id'    => 'nullable|required_without:records.*.teacher_id|exists:users,id',

            'records.*.status' => 'required|in:P,A,LP,HP,L',
        ]);

        DB::transaction(function () use ($validated) {

            foreach ($validated['records'] as $row) {

                UserAttendance::updateOrCreate(
                    [
                        'attendance_date' => $validated['date'],
                        'teacher_id' => $row['teacher_id'] ?? null,
                        'user_id'    => $row['user_id'] ?? null,
                    ],
                    [
                        'status' => $row['status'],
                    ]
                );
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Attendance updated successfully.',
        ]);
    }

    /**
     * 🔁 Update attendance (row-wise)
     */
    public function bulkUpdate(Request $request, $date)
    {
        if (Carbon::parse($date)->isFuture()) {
            return response()->json([
                'message' => 'Future attendance cannot be updated.'
            ], 422);
        }

        $validated = $request->validate([
            'records' => 'required|array|min:1',

            // ✅ Either teacher_id OR user_id
            'records.*.teacher_id' => 'nullable|required_without:records.*.user_id|exists:teachers,id',
            'records.*.user_id'    => 'nullable|required_without:records.*.teacher_id|exists:users,id',

            'records.*.status' => 'required|in:P,A,LP,HP,L',
        ]);

        DB::transaction(function () use ($validated, $date) {

            foreach ($validated['records'] as $row) {

                UserAttendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'teacher_id' => $row['teacher_id'] ?? null,
                        'user_id'    => $row['user_id'] ?? null,
                    ],
                    [
                        'status' => $row['status'],
                    ]
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance updated.',
        ]);
    }


    /**
     * 🔎 Fetch user attendance (STAFF)
     */
    private function getUserAttendances($userId, $from, $to)
    {
        return UserAttendance::where('user_id', $userId)
            ->whereBetween('attendance_date', [$from, $to])
            ->get();
    }
}
