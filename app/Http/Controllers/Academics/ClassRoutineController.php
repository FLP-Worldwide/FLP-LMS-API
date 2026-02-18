<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\ClassRoutine;
use App\Models\ClassRoutineException;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassRoutineController extends Controller
{
    public function index(Request $request)
    {
        $query = ClassRoutine::with([
            'days',
            'classRoom:id,name',
            'subject:id,name,short_code',
            'teacher:id,first_name,last_name',
            'room:id,name,code,floor,number',
            'course:id,name',
            'batch:id,name'
        ]);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->section) {
            $query->where('section', $request->section);
        }

        $routines = $query->orderBy('start_time')->get();

        $data = $routines->map(function ($routine) {

            $weeklyDays = $routine->days
                ->whereNull('specific_date')
                ->pluck('day')
                ->values();

            $specificDates = $routine->days
                ->whereNotNull('specific_date')
                ->pluck('specific_date')
                ->map(fn($date) => date('Y-m-d', strtotime($date)))
                ->values();

            return [
                'id' => $routine->id,

                // ✅ Course
                'course' => $routine->course ? [
                    'id'   => $routine->course->id,
                    'name' => $routine->course->name,
                ] : null,

                // ✅ Batch
                'batch' => $routine->batch ? [
                    'id'   => $routine->batch->id,
                    'name' => $routine->batch->name,
                ] : null,

                // ✅ Class
                'class' => $routine->classRoom ? [
                    'id'   => $routine->classRoom->id,
                    'name' => $routine->classRoom->name,
                ] : null,

                'section' => $routine->section,

                // ✅ Subject
                'subject' => $routine->subject ? [
                    'id'   => $routine->subject->id,
                    'name' => $routine->subject->name,
                    'code' => $routine->subject->short_code,
                ] : null,

                // ✅ Teacher
                'teacher' => $routine->teacher ? [
                    'id'   => $routine->teacher->id,
                    'name' => $routine->teacher->first_name . ' ' . $routine->teacher->last_name,
                ] : null,

                // ✅ Room
                'room' => $routine->room ? [
                    'id'     => $routine->room->id,
                    'name'   => $routine->room->name ?? null,
                    'code'   => $routine->room->code ?? null,
                    'floor'  => $routine->room->floor ?? null,
                    'number' => $routine->room->number ?? null,
                ] : null,

                // ✅ Timing
                'start_time' => date('h:i A', strtotime($routine->start_time)),
                'end_time'   => date('h:i A', strtotime($routine->end_time)),

                // ✅ Type
                'class_type'  => $routine->class_type,
                'repeat_type' => $routine->repeat_type,
                'base_date'   => $routine->base_date,

                // ✅ Repeat Data
                'weekly_days'    => $weeklyDays,
                'specific_dates' => $specificDates,

                'is_active' => (bool) $routine->is_active,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }




    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id'  => 'required|exists:batches,id',
            'base_date' => 'required|date',

            'classes' => 'required|array|min:1',

            'classes.*.class_id' => 'required|exists:class_rooms,id',
            'classes.*.subject_id' => 'required|exists:subjects,id',
            'classes.*.topic' => 'nullable|string|max:255',
            'classes.*.start_time' => 'required|string',
            'classes.*.end_time' => 'required|string',
            'classes.*.teacher_id' => 'nullable|exists:teachers,id',
            'classes.*.description' => 'nullable|string',
            'classes.*.room_no' => 'nullable|string|max:50',
            'classes.*.class_type' => 'required|string',

            'classes.*.repeat_type' => 'required|in:Does Not Repeat,Weekly,Select Dates,Daily',
            'classes.*.weekly_days' => 'nullable|array',
            'classes.*.specific_dates' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $routines = [];

            foreach ($validated['classes'] as $class) {

                $startTime = date('H:i:s', strtotime($class['start_time']));
                $endTime   = date('H:i:s', strtotime($class['end_time']));


                $conflict = ClassRoutine::where('class_id', $class['class_id'])
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->exists();

                if ($conflict) {
                    throw new \Exception(
                        "Time slot already exists for this class between $startTime - $endTime"
                    );
                }

                $routine = ClassRoutine::create([
                    'course_id'  => $validated['course_id'],
                    'batch_id'   => $validated['batch_id'],
                    'class_id'   => $class['class_id'], // 🔥 IMPORTANT FIX
                    'base_date'  => $validated['base_date'],

                    'subject_id' => $class['subject_id'],
                    'topic'      => $class['topic'] ?? null,
                    'teacher_id' => $class['teacher_id'] ?? null,
                    'description'=> $class['description'] ?? null,
                    'room_id'    => $class['room_no'] ?? null,
                    'class_type' => $class['class_type'],
                    'repeat_type'=> $class['repeat_type'],

                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ]);

                /*
                ========================================
                HANDLE REPEAT TYPES
                ========================================
                */

                // 🔹 1. Does Not Repeat
                if ($class['repeat_type'] === 'Does Not Repeat') {

                    $routine->days()->create([
                        'day' => date('l', strtotime($validated['base_date'])),
                        'specific_date' => $validated['base_date'],
                    ]);
                }

                elseif ($class['repeat_type'] === 'Daily') {

                    // store base_date as first occurrence
                    $routine->days()->create([
                        'day' => date('l', strtotime($validated['base_date'])),
                        'specific_date' => null,
                    ]);
                }

                // 🔹 2. Weekly
                elseif ($class['repeat_type'] === 'Weekly') {

                    foreach ($class['weekly_days'] ?? [] as $day) {

                        $routine->days()->create([
                            'day' => $day,
                            'specific_date' => null,
                        ]);
                    }
                }

                // 🔹 3. Select Dates
                elseif ($class['repeat_type'] === 'Select Dates') {

                    foreach ($class['specific_dates'] ?? [] as $date) {

                        $routine->days()->create([
                            'day' => date('l', strtotime($date)),
                            'specific_date' => $date, // 🔥 THIS IS NOW SAVING
                        ]);
                    }
                }

                $routines[] = $routine->load('days');
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Schedule created successfully.',
                'data'    => $routines,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => ClassRoutine::with('days')->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $routine = ClassRoutine::with('days')->findOrFail($id);

        $validated = $request->validate([
            'section'    => 'required|string|max:50',
            'subject_id' => 'required|exists:subjects,id',
            'teacher'    => 'nullable|string|max:100',
            'room_id'    => 'required|exists:rooms,id',

            'day'        => 'required|array|min:1',
            'day.*'      => 'required|string',

            'start_time' => 'required|string',
            'end_time'   => 'required|string',
        ]);

        $startTime = date('H:i:s', strtotime($validated['start_time']));
        $endTime   = date('H:i:s', strtotime($validated['end_time']));

        DB::transaction(function () use ($routine, $validated, $startTime, $endTime) {

            // ✅ Update routine core
            $routine->update([
                'section'    => $validated['section'],
                'subject_id' => $validated['subject_id'],
                'teacher'    => $validated['teacher'] ?: null,
                'room_id'    => $validated['room_id'],
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ]);

            // ================= DAY SYNC LOGIC =================

            $existingDays = $routine->days->pluck('day')->toArray();
            $incomingDays = $validated['day'];

            // ➕ Add new days (skip duplicates)
            $daysToAdd = array_diff($incomingDays, $existingDays);

            foreach ($daysToAdd as $day) {
                $routine->days()->create([
                    'day' => $day,
                ]);
            }

            // ➖ Remove days that are no longer present
            $daysToRemove = array_diff($existingDays, $incomingDays);

            if (!empty($daysToRemove)) {
                $routine->days()
                    ->whereIn('day', $daysToRemove)
                    ->delete();
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Class routine updated successfully.',
            'data'    => $routine->fresh('days'),
        ]);
    }
public function scheduleByDate(Request $request)
{
    $validated = $request->validate([
        'course_id' => 'required|exists:courses,id',
        'batch_id'  => 'required|exists:batches,id',
        'date'      => 'nullable|date',
    ]);

    $selectedDate = $validated['date'] ?? null;
    $today = now()->toDateString();
    $currentTime = now()->format('H:i:s');

    $weekStart = now()->startOfWeek();
    $weekEnd   = now()->endOfWeek();

    $routines = ClassRoutine::with([
        'days',
        'exceptions',
        'course:id,name',
        'batch:id,name',
        'classRoom:id,name',
        'subject:id,name,short_code',
        'teacher:id,first_name,last_name',
        'room:id,name,code'
    ])
    ->where('course_id', $validated['course_id'])
    ->where('batch_id', $validated['batch_id'])
    ->get();

    $result = collect();

    foreach ($routines as $routine) {

        /*
        =====================================
        DETERMINE DATES TO CHECK
        =====================================
        */

        $dates = [];

        if ($selectedDate) {
            $dates = [$selectedDate];
        } else {
            $cursor = $weekStart->copy();
            while ($cursor <= $weekEnd) {
                $dates[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }
        }

        foreach ($dates as $date) {

            /*
            =====================================
            CHECK IF ROUTINE SHOULD RUN ON THIS DATE
            =====================================
            */

            $shouldRun = false;

            if ($routine->repeat_type === 'Does Not Repeat') {
                $shouldRun = $routine->base_date == $date;
            }

            elseif ($routine->repeat_type === 'Weekly') {
                $dayName = \Carbon\Carbon::parse($date)->format('l');
                $days = $routine->days->pluck('day')->toArray();

                if (in_array($dayName, $days) && $date >= $routine->base_date) {
                    $shouldRun = true;
                }
            }

            elseif ($routine->repeat_type === 'Daily') {
                if ($date >= $routine->base_date) {
                    $shouldRun = true;
                }
            }

            elseif ($routine->repeat_type === 'Select Dates') {
                $specificDates = $routine->days
                    ->whereNotNull('specific_date')
                    ->pluck('specific_date')
                    ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                    ->toArray();

                $shouldRun = in_array($date, $specificDates);
            }

            if (!$shouldRun) {
                continue;
            }

            /*
            =====================================
            CHECK EXCEPTIONS
            =====================================
            */

            $exception = $routine->exceptions
                ->first(function ($ex) use ($date) {
                    return $ex->exception_date == $date
                        || $ex->new_date == $date;
                });

            // 🔴 CANCELLED
            if ($exception && $exception->type === 'cancelled'
                && $exception->exception_date == $date) {

                $result->push($this->formatRoutineResponse(
                    $routine,
                    $date,
                    null,
                    null,
                    'cancelled'
                ));

                continue;
            }

            // 🔁 RESCHEDULED
            if ($exception && $exception->type === 'rescheduled') {

                // If old date → skip
                if ($exception->exception_date == $date) {
                    continue;
                }

                // If new date → show rescheduled
                if ($exception->new_date == $date) {

                    $result->push($this->formatRoutineResponse(
                        $routine,
                        $date,
                        $exception->new_start_time ?? $routine->start_time,
                        $exception->new_end_time ?? $routine->end_time,
                        'rescheduled'
                    ));

                    continue;
                }
            }

            /*
            =====================================
            NORMAL STATUS
            =====================================
            */

            $status = 'upcoming';

            if ($date < $today) {
                $status = 'completed';
            }

            if ($date == $today) {
                if ($currentTime >= $routine->start_time
                    && $currentTime <= $routine->end_time) {
                    $status = 'ongoing';
                }

                if ($currentTime > $routine->end_time) {
                    $status = 'completed';
                }
            }

            $result->push($this->formatRoutineResponse(
                $routine,
                $date,
                $routine->start_time,
                $routine->end_time,
                $status
            ));
        }
    }

    return response()->json([
        'status' => 'success',
        'data'   => $result->sortBy([
            ['date', 'asc'],
            ['start_time', 'asc']
        ])->values(),
    ]);
}

public function listClassAttendance(Request $request)
{
    $today = now()->toDateString();
    $currentTime = now()->format('H:i:s');
    $dayName = now()->format('l');

    $routines = ClassRoutine::with([
        'days',
        'exceptions',
        'course:id,name',
        'batch:id,name',
        'classRoom:id,name',
        'subject:id,name,short_code',
        'teacher:id,first_name,last_name',
    ])->get();

    $result = collect();

    foreach ($routines as $routine) {

        $show = false;
        $finalStart = $routine->start_time;
        $finalEnd   = $routine->end_time;

        /*
        =====================================
        CHECK IF CLASS IS SCHEDULED TODAY
        =====================================
        */

        if ($routine->repeat_type === 'Does Not Repeat') {
            $show = $routine->base_date == $today;
        }

        elseif ($routine->repeat_type === 'Weekly') {

            $days = $routine->days
                ->whereNull('specific_date')
                ->pluck('day')
                ->toArray();

            if (in_array($dayName, $days) && $today >= $routine->base_date) {
                $show = true;
            }
        }

        elseif ($routine->repeat_type === 'Daily') {

            if ($today >= $routine->base_date) {
                $show = true;
            }
        }

        elseif ($routine->repeat_type === 'Select Dates') {

            $dates = $routine->days
                ->whereNotNull('specific_date')
                ->pluck('specific_date')
                ->map(fn($d) => date('Y-m-d', strtotime($d)))
                ->toArray();

            $show = in_array($today, $dates);
        }

        if (!$show) continue;

        /*
        =====================================
        CHECK EXCEPTION (Cancel / Reschedule)
        =====================================
        */

        $exception = $routine->exceptions
            ->where('exception_date', $today)
            ->first();

        if ($exception) {

            // 🔴 Cancelled
            if ($exception->type === 'cancelled') {

                $result->push([
                    'routine_id' => $routine->id,
                    'course' => $routine->course,
                    'batch'  => $routine->batch,
                    'class'  => $routine->classRoom,
                    'subject'=> $routine->subject,
                    'teacher'=> $routine->teacher,
                    'start_time' => null,
                    'end_time'   => null,
                    'class_status' => 'cancelled',
                    'attendance_status' => 'not_applicable',
                    'total_students' => 0,
                    'attendance_marked' => 0,
                ]);

                continue;
            }

            // 🔁 Rescheduled
            if ($exception->type === 'rescheduled') {

                if ($exception->new_date != $today) {
                    continue; // old date should not show
                }

                $finalStart = $exception->new_start_time ?? $routine->start_time;
                $finalEnd   = $exception->new_end_time ?? $routine->end_time;
            }
        }

        /*
        =====================================
        ATTENDANCE CHECK (FIXED)
        =====================================
        */

        $totalStudents = Student::where('class', $routine->class_id)
            ->whereNull('deleted_at')
            ->count();

        $markedCount = StudentAttendance::where('attendance_date', $today)
            ->where('class_routine_id', $routine->id) // ✅ FIXED
            ->count();

        $attendanceStatus = 'not_marked';

        if ($markedCount > 0 && $markedCount < $totalStudents) {
            $attendanceStatus = 'partial';
        }

        if ($markedCount == $totalStudents && $totalStudents > 0) {
            $attendanceStatus = 'marked';
        }

        /*
        =====================================
        CLASS TIME STATUS
        =====================================
        */

        $classStatus = 'upcoming';

        if ($currentTime >= $finalStart && $currentTime <= $finalEnd) {
            $classStatus = 'ongoing';
        }

        if ($currentTime > $finalEnd) {
            $classStatus = 'completed';
        }

        /*
        =====================================
        PUSH RESULT
        =====================================
        */

        $result->push([
            'routine_id' => $routine->id,

            'course' => $routine->course,
            'batch'  => $routine->batch,
            'class'  => $routine->classRoom,
            'subject'=> $routine->subject,
            'teacher'=> $routine->teacher,

            'repeat_type' => $routine->repeat_type, // ✅ Added
            'class_type'  => $routine->class_type,  // ✅ Added

            'start_time' => date('h:i A', strtotime($finalStart)),
            'end_time'   => date('h:i A', strtotime($finalEnd)),

            'class_status' => $classStatus,
            'attendance_status' => $attendanceStatus,
            'total_students' => $totalStudents,
            'attendance_marked' => $markedCount,
        ]);
    }

    return response()->json([
        'status' => 'success',
        'date'   => $today,
        'data'   => $result->sortBy('start_time')->values(),
    ]);
}




public function todayClassesAttendance(Request $request)
{
    $validated = $request->validate([
        'routine_id' => 'required|exists:class_routines,id',
    ]);

    $today = now()->toDateString();
    $currentTime = now()->format('H:i:s');

    $routine = ClassRoutine::with([
        'days',
        'course:id,name',
        'batch:id,name',
        'classRoom:id,name',
        'subject:id,name,short_code',
        'teacher:id,first_name,last_name',
    ])->findOrFail($validated['routine_id']);

    $dayName = now()->format('l');
    $show = false;

    /*
    =====================================
    CHECK IF CLASS IS SCHEDULED TODAY
    =====================================
    */

    if ($routine->repeat_type === 'Does Not Repeat') {
        $show = $routine->base_date == $today;
    }

    elseif ($routine->repeat_type === 'Weekly') {

        $days = $routine->days
            ->whereNull('specific_date')
            ->pluck('day')
            ->toArray();

        if (in_array($dayName, $days) && $today >= $routine->base_date) {
            $show = true;
        }
    }

    elseif ($routine->repeat_type === 'Select Dates') {

        $dates = $routine->days
            ->whereNotNull('specific_date')
            ->pluck('specific_date')
            ->map(fn($d) => date('Y-m-d', strtotime($d)))
            ->toArray();

        $show = in_array($today, $dates);
    }

    if (!$show) {
        return response()->json([
            'status' => 'success',
            'message' => 'No class scheduled today for this routine.',
            'data' => null,
        ]);
    }

    /*
    =====================================
    GET STUDENTS
    =====================================
    */

    $students = Student::where('class', $routine->class_id)
        ->whereNull('deleted_at')
        ->get();

    $totalStudents = $students->count();

    /*
    =====================================
    GET TODAY ATTENDANCE
    =====================================
    */

    $attendances = StudentAttendance::where('attendance_date', $today)
            ->where('class_routine_id', $routine->id)
            ->get();


    $markedCount = $attendances->count();

    /*
    =====================================
    OVERALL ATTENDANCE STATUS
    =====================================
    */

    $attendanceStatus = 'not_marked';

    if ($markedCount > 0 && $markedCount < $totalStudents) {
        $attendanceStatus = 'partial';
    }

    if ($markedCount == $totalStudents && $totalStudents > 0) {
        $attendanceStatus = 'marked';
    }

    /*
    =====================================
    CLASS STATUS
    =====================================
    */

    $classStatus = 'upcoming';

    if ($currentTime >= $routine->start_time && $currentTime <= $routine->end_time) {
        $classStatus = 'ongoing';
    }

    if ($currentTime > $routine->end_time) {
        $classStatus = 'completed';
    }

    /*
    =====================================
    STUDENT LIST
    =====================================
    */

    $studentList = $students->map(function ($student) use ($attendances) {

        $attendance = $attendances
            ->where('student_id', $student->id)
            ->first();

        return [
            'id' => $student->id,
            'name' => $student->first_name . ' ' . $student->last_name,
            'roll_no' => $student->roll_no,
            'attendance_status' => $attendance->status ?? null,
        ];
    });

    /*
    =====================================
    FINAL RESPONSE
    =====================================
    */

    return response()->json([
        'status' => 'success',
        'date'   => $today,
        'data'   => [

            'routine_id' => $routine->id,

            'course' => [
                'id' => $routine->course->id,
                'name' => $routine->course->name,
            ],

            'batch' => [
                'id' => $routine->batch->id,
                'name' => $routine->batch->name,
            ],

            'class' => [
                'id' => $routine->classRoom->id,
                'name' => $routine->classRoom->name,
            ],

            'subject' => [
                'id' => $routine->subject->id,
                'name' => $routine->subject->name,
                'code' => $routine->subject->short_code,
            ],

            'teacher' => $routine->teacher ? [
                'id' => $routine->teacher->id,
                'name' => $routine->teacher->first_name.' '.$routine->teacher->last_name,
            ] : null,

            'start_time' => date('h:i A', strtotime($routine->start_time)),
            'end_time'   => date('h:i A', strtotime($routine->end_time)),

            'class_status' => $classStatus,

            'total_students' => $totalStudents,
            'attendance_marked' => $markedCount,
            'attendance_status' => $attendanceStatus,

            'students' => $studentList,
        ],
    ]);
}


    public function markAttendance(Request $request)
    {
        $validated = $request->validate([
            'routine_id' => 'required|exists:class_routines,id',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.status' => 'required|in:P,A,L,H',
        ]);

        DB::beginTransaction();

        try {

            $today = now()->toDateString();
            $markedBy = auth()->id() ?? null;

            $routine = ClassRoutine::with('days')
                ->findOrFail($validated['routine_id']);

            foreach ($validated['students'] as $studentData) {

                StudentAttendance::updateOrCreate(
                    [
                        'student_id' => $studentData['student_id'],
                        'attendance_date' => $today,
                        'class_routine_id' => $routine->id, // ✅ KEY FIX
                    ],
                    [
                        'class_id' => $routine->class_id,
                        'class' => $routine->class_id,
                        'section' => $routine->section ?? null,
                        'status' => $studentData['status'],
                        'marked_at' => now(),
                        'marked_by' => $markedBy,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Attendance marked successfully.',
                'routine_id' => $routine->id,
                'date' => $today,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function deleteSchedule($id)
    {
        $routine = ClassRoutine::findOrFail($id);

        $routine->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Entire schedule deleted successfully.'
        ]);
    }

    public function cancelSingleClass(Request $request)
    {
        $validated = $request->validate([
            'routine_id' => 'required|exists:class_routines,id',
            'date' => 'required|date',
        ]);

        ClassRoutineException::updateOrCreate(
            [
                'class_routine_id' => $validated['routine_id'],
                'exception_date' => $validated['date'],
            ],
            [
                'type' => 'cancelled',
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Class cancelled for selected date.',
        ]);
    }

    public function rescheduleSingleClass(Request $request)
    {
        $validated = $request->validate([
            'routine_id' => 'required|exists:class_routines,id',
            'date' => 'required|date',
            'new_date' => 'required|date',
            'new_start_time' => 'required',
            'new_end_time' => 'required',
        ]);

        ClassRoutineException::updateOrCreate(
            [
                'class_routine_id' => $validated['routine_id'],
                'exception_date' => $validated['date'],
            ],
            [
                'type' => 'rescheduled',
                'new_date' => $validated['new_date'],
                'new_start_time' => date('H:i:s', strtotime($validated['new_start_time'])),
                'new_end_time' => date('H:i:s', strtotime($validated['new_end_time'])),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Class rescheduled successfully.',
        ]);
    }


    private function formatRoutineResponse($routine, $date, $start, $end, $status)
    {
        return [
            'id' => $routine->id,

            'course' => [
                'id' => $routine->course->id,
                'name' => $routine->course->name,
            ],

            'batch' => [
                'id' => $routine->batch->id,
                'name' => $routine->batch->name,
            ],

            'class' => [
                'id' => $routine->classRoom->id,
                'name' => $routine->classRoom->name,
            ],

            'subject' => [
                'id' => $routine->subject->id,
                'name' => $routine->subject->name,
                'code' => $routine->subject->short_code,
            ],

            'teacher' => $routine->teacher ? [
                'id' => $routine->teacher->id,
                'name' => $routine->teacher->first_name.' '.$routine->teacher->last_name,
            ] : null,

            'room' => $routine->room ? [
                'id' => $routine->room->id,
                'name' => $routine->room->name,
                'code' => $routine->room->code,
            ] : null,

            'class_type' => $routine->class_type,
            'repeat_type' => $routine->repeat_type,

            'date' => $date,
            'start_time' => $start ? date('h:i A', strtotime($start)) : null,
            'end_time'   => $end ? date('h:i A', strtotime($end)) : null,

            'status' => $status,
        ];
    }

}
