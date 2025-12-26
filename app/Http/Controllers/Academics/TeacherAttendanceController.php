<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherAttendanceController extends Controller
{
    /**
     * 📅 Get attendance (date / month wise)
     */
    public function index(Request $request)
    {
        // -----------------------------
        // DATE FILTER LOGIC
        // -----------------------------

        // Default → today
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

        // -----------------------------
        // TEACHER QUERY
        // -----------------------------

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

        // -----------------------------
        // RESPONSE TRANSFORMATION
        // -----------------------------

        return response()->json([
            'status' => 'success',
            'filter' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'data' => $teachers->map(function ($teacher) use ($from, $to) {

                // Single day view
                if ($from->equalTo($to)) {
                    $attendance = $teacher->attendances->first();

                    return [
                        'teacher' => [
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
                        ] : null
                    ];
                }

                // Multi-day (month / range / year)
                return [
                    'teacher' => [
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
        ]);
    }


    /**
     * 📝 Mark attendance (bulk)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',

            'records' => 'required|array|min:1',
            'records.*.teacher_id' => 'required|exists:teachers,id',
            'records.*.status' => 'required|in:P,A,LP,HP,L',
        ]);

        DB::transaction(function () use ($validated) {

            foreach ($validated['records'] as $row) {
                TeacherAttendance::updateOrCreate(
                    [
                        'teacher_id' => $row['teacher_id'],
                        'attendance_date' => $validated['date'],
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
     * 🔁 Update attendance for a specific date (row-wise update button)
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
            'records.*.teacher_id' => 'required|exists:teachers,id',
            'records.*.status' => 'required|in:P,A,LP,HP,L',
        ]);

        DB::transaction(function () use ($validated, $date) {

            foreach ($validated['records'] as $row) {
                TeacherAttendance::updateOrCreate(
                    [
                        'teacher_id' => $row['teacher_id'],
                        'attendance_date' => $date,
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
}

