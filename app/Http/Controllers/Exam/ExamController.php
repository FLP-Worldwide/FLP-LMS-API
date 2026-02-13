<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttendance;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',

            'subjects' => 'required|array|min:1',
            'subjects.*.subject_id' => 'required|exists:subjects,id',
            'subjects.*.marks' => 'required|integer|min:1',
            'subjects.*.room_no' => 'nullable|string',
            'subjects.*.topic_id' => 'nullable|string',
            'subjects.*.description' => 'nullable|string',
        ]);

        $exam = Exam::create($request->only([
            'course_id',
            'batch_id',
            'exam_date',
            'start_time',
            'end_time',
            'title',
            'description',
        ]));

        foreach ($request->subjects as $subject) {
            $exam->subjects()->create($subject);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Exam created successfully',
            'data' => $exam->load('subjects'),
        ], 201);
    }


  public function index(Request $request)
    {
        $today = now()->toDateString();

        $query = Exam::with([
            'course:id,name,standard_id',
            'course.classRoom:id,name',
            'course.classRoom.subjects:id,class_id,name',
            'batch:id,name,course_id',
            'subjects.subject:id,name',
        ])
        ->withCount('attendances');

        /* ================= DATE FILTER (NEW) ================= */
        if ($request->filled('date')) {
            $query->whereDate('exam_date', $request->date);
        }

        /* ================= EXISTING FILTERS ================= */
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('exam_date')) { // optional backward support
            $query->whereDate('exam_date', $request->exam_date);
        }

        if ($request->filled('class_id')) {
            $query->whereHas('course', function ($q) use ($request) {
                $q->where('standard_id', $request->class_id);
            });
        }

        $exams = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $exams->map(function ($exam) use ($today) {

                $isToday = $exam->exam_date === $today;

                return [
                    'id' => $exam->id,
                    'exam_date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,

                    'room' => $exam->subjects->first()?->room_no,
                    'topic' => $exam->topic ? $exam->topic->name : null,
                    'status' => $exam->status,

                    // 🔑 Attendance flags
                    'is_today' => $isToday,
                    'is_attendance_marked' => $isToday
                        ? $exam->attendances_count > 0
                        : null,

                    'course' => $exam->course ? [
                        'id' => $exam->course->id,
                        'name' => $exam->course->name,
                    ] : null,

                    'batch' => $exam->batch ? [
                        'id' => $exam->batch->id,
                        'name' => $exam->batch->name,
                        'course_id' => $exam->batch->course_id,
                    ] : null,



                    'class' => $exam->course?->classRoom ? [
                        'id' => $exam->course->classRoom->id,
                        'name' => $exam->course->classRoom->name,
                    ] : null,


                    'subjects' => $exam->subjects->map(function ($s) {
                        return [
                            'id' => $s->subject?->id,
                            'name' => $s->subject?->name,
                            'marks' => $s->marks,
                        ];
                    }),

                ];
            }),
        ]);
    }




    public function show($id)
    {
        $exam = Exam::with([
            'subjects.subject',
            'batch.course.classRoom',
            'attendances'
        ])->findOrFail($id);

        $classId = $exam->batch->course->standard_id;

        // All students of that class
        $students = Student::with('details')
            ->where('class', $classId)
            ->get()
            ->map(function ($student) use ($exam) {

                $attendance = $exam->attendances
                    ->firstWhere('student_id', $student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'roll_no' => $student->roll_no ?? null,
                    'gender' => $student->details?->gender,
                    'attendance' => $attendance?->attendance ?? 0, // 0 = not marked
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'exam' => [
                    'id' => $exam->id,
                    'date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'batch' => $exam->batch->name,
                    'course' => $exam->batch->course->name,
                    'class' => $exam->batch->course->classRoom->name,
                ],
                'subjects' => $exam->subjects,
                'students' => $students,
            ],
        ]);
    }


    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        if ($exam->attendances()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel exam. Attendance already marked.',
            ], 422);
        }


        $request->validate([
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'subjects' => 'sometimes|array|min:1',
            'subjects.*.subject_id' => 'required_with:subjects|exists:subjects,id',
            'subjects.*.marks' => 'required_with:subjects|numeric|min:0',
        ]);

        // ✅ Update only basic fields
        $exam->update($request->only([
            'exam_date',
            'start_time',
            'end_time',
            'title',
            'description',
        ]));

        // ✅ Only touch subjects IF provided
        if ($request->has('subjects')) {

            // Option 1: Clean replace (safe)
            $exam->subjects()->delete();

            foreach ($request->subjects as $subject) {
                $exam->subjects()->create($subject);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Exam updated successfully',
        ]);
    }


    public function destroy($id)
    {
        Exam::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Exam deleted successfully',
        ]);
    }


    public function markAttendance(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'students' => 'required|array',
            'students.*.student_id' => 'required|exists:students,id',
            'students.*.attendance' => 'required|in:0,1,2,3',
        ]);

        DB::transaction(function () use ($request) {

            foreach ($request->students as $row) {
                ExamAttendance::updateOrCreate(
                    [
                        'exam_id' => $request->exam_id,
                        'student_id' => $row['student_id'],
                    ],
                    [
                        'attendance' => $row['attendance'],
                    ]
                );
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Exam attendance saved successfully.',
        ]);
    }

    public function cancel($id)
    {
        $exam = Exam::findOrFail($id);

        // Prevent cancelling completed exam
        if ($exam->exam_date < now()->toDateString()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel a completed exam.',
            ], 422);
        }

        // Already cancelled
        if ($exam->status === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => 'Exam is already cancelled.',
            ], 422);
        }

        $exam->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Exam cancelled successfully.',
        ]);
    }


}
