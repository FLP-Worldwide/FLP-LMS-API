<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\{
    Batch,
    BatchStudent,
    BatchSubject,
    ClassRoutine,
    Exam,
    Student
};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BatchDetailsController extends Controller
{
    public function batchDetails(Request $request, $batchId)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year ?? now()->year);
        $today = now()->toDateString();

        /* ===================== BATCH ===================== */
        $batch = Batch::with('course.classRoom')->findOrFail($batchId);
        $course = $batch->course;
        $class  = $course->classRoom;

        /* ===================== STUDENTS (ONLY ASSIGNED IN BATCH) ===================== */
        $students = $batch->students()
            ->with('details')
            ->wherePivot('is_active', true)
            ->wherePivot('assigned_date', '<=', $today)
            ->get();

        /* ---------- Gender Stats ---------- */
        $genderStats = ['male'=>0,'female'=>0,'other'=>0,'na'=>0];

        foreach ($students as $s) {
            $gender = $s->details?->gender ?? 'na';
            $genderStats[$gender] = ($genderStats[$gender] ?? 0) + 1;
        }

        /* ---------- Expired Students ---------- */
        $expiredStudents = $students
            ->whereIn('status', ['left','passed','inactive'])
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->first_name.' '.$s->last_name,
                    'dob' => $s->details?->dob,
                    'assigned_date' => $s->pivot->assigned_date,
                ];
            })
            ->values();

        /* ===================== EXAMS ===================== */
        $exams = Exam::with(['subjects.subject','subjects.topic'])
            ->where('batch_id', $batch->id)
            ->orderBy('exam_date')
            ->get()
            ->map(function ($exam) use ($today) {

                if ($exam->exam_date < $today) {
                    $status = 'COMPLETED';
                } elseif ($exam->exam_date == $today) {
                    $status = 'ONGOING';
                } else {
                    $status = 'UPCOMING';
                }

                return [
                    'id' => $exam->id,
                    'date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'status' => $status,
                    'subjects' => $exam->subjects->map(fn ($s) => [
                        'subject' => $s->subject?->name,
                        'marks'   => $s->marks,
                        'topic'   => $s->topic?->name,
                        'room'    => $s->room_no,
                    ]),
                ];
            });

        /* ===================== WEEKLY SCHEDULE ===================== */

        $batchSubjects = BatchSubject::with(['subject','teacher','extraTeacher'])
            ->where('batch_id', $batch->id)
            ->get()
            ->keyBy('subject_id');

        $routines = ClassRoutine::with(['days','subject'])
            ->where('class_id', $course->standard_id)
            ->whereIn('subject_id', $batchSubjects->keys())
            ->where('is_active', true)
            ->get();

        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $start = Carbon::parse($batch->start_date)->greaterThan($weekStart)
            ? Carbon::parse($batch->start_date)
            : $weekStart;

        $end = Carbon::parse($batch->end_date)->lessThan($weekEnd)
            ? Carbon::parse($batch->end_date)
            : $weekEnd;

        $schedule = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

            foreach ($routines as $routine) {

                $days = $routine->days
                    ->pluck('day')
                    ->map(fn ($d) => strtolower($d));

                if (!$days->contains(strtolower($date->format('l')))) {
                    continue;
                }

                if ($date->toDateString() < $today) {
                    $status = 'COMPLETED';
                } elseif ($date->toDateString() == $today) {
                    $status = 'ONGOING';
                } else {
                    $status = 'UPCOMING';
                }

                $bs = $batchSubjects[$routine->subject_id] ?? null;


                $schedule[] = [
                    'date'        => $date->toDateString(),
                    'day'         => $date->format('l'),
                    'batch'       => $batch->name,
                    'subject'     => $routine->subject?->name,
                    'topic'       => null,
                    'teacher'     => $bs?->teacher?->name,
                    'start_time'  => Carbon::parse($routine->start_time)->format('H:i'),
                    'end_time'    => Carbon::parse($routine->end_time)->format('H:i'),
                    'status'      => $status,
                ];
            }
        }

        /* ===================== RESPONSE ===================== */
        return response()->json([
            'status' => 'success',
            'data' => [
                'filters' => compact('month','year'),

                'batch' => [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'academic_year' => $batch->academic_year,
                    'start_date' => $batch->start_date,
                    'end_date' => $batch->end_date,
                    'course' => [
                        'id' => $course->id,
                        'name' => $course->name,
                    ],
                    'class' => [
                        'id' => $class->id,
                        'name' => $class->name,
                    ],
                ],

                'students' => [
                    'total' => $students->count(),
                    'gender' => $genderStats,
                    'expired_students' => $expiredStudents,
                ],
                'subjects' => $batchSubjects->values()->map(function ($s) {

                    return [
                        'subject' => $s->subject?->name,

                        // existing UI compatible field
                        'teacher' => $s->teacher?->user?->name,

                        // full object
                        'teacher_obj' => $s->teacher ? [
                            'id' => $s->teacher->id,
                            'user_id' => $s->teacher->user_id,
                            'name' => $s->teacher->user?->name,
                            'email' => $s->teacher->user?->email ?? null,
                        ] : null,

                        'extra_teacher' => $s->extraTeacher?->user?->name,

                        'extra_teacher_obj' => $s->extraTeacher ? [
                            'id' => $s->extraTeacher->id,
                            'user_id' => $s->extraTeacher->user_id,
                            'name' => $s->extraTeacher->user?->name,
                        ] : null,
                    ];
                }),

                'exams' => $exams,

                'weekly_schedule' => $schedule,
            ]
        ]);
    }


    public function batchStudents($batchId)
    {
        $batch = Batch::with([
            'students.details'
        ])->findOrFail($batchId);

        $students = $batch->students->map(function ($student) {

            return [
                'id' => $student->id,
                'name' => $student->first_name.' '.$student->last_name,
                'status' => $student->status,
                'assigned_date' => $student->pivot->assigned_date,
                'batch_status' => $student->pivot->is_active,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $students
        ]);
    }
    public function updateAssignedDate(Request $request, $batchId, $studentId)
    {
        $request->validate([
            'assigned_date' => 'required|date'
        ]);

        $batchStudent = BatchStudent::where('batch_id', $batchId)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $batchStudent->update([
            'assigned_date' => $request->assigned_date
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Assigned date updated successfully'
        ]);
    }

    public function assignStudent(Request $request, $batchId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'assigned_date' => 'nullable|date'
        ]);

        $batch = Batch::findOrFail($batchId);
        $student = Student::findOrFail($request->student_id);

        // 🔥 Smart logic
        if ($request->assigned_date) {
            $assignedDate = $request->assigned_date;
        } else {
            if ($student->admission_date <= $batch->start_date) {
                $assignedDate = $batch->start_date;
            } else {
                $assignedDate = $student->admission_date;
            }
        }

        BatchStudent::updateOrCreate(
            [
                'batch_id' => $batch->id,
                'student_id' => $student->id,
            ],
            [
                'assigned_date' => $assignedDate,
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Student assigned successfully',
            'assigned_date' => $assignedDate
        ]);
    }

}
