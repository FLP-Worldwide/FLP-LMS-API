<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\{
    Batch,
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

        /* ===================== STUDENTS ===================== */
        $students = Student::with('details')
            ->where('class', $class->id)
            ->get();

        $genderStats = ['male'=>0,'female'=>0,'other'=>0,'na'=>0];

        foreach ($students as $s) {
            $g = $s->details?->gender ?? 'na';
            $genderStats[$g]++;
        }

        $expiredStudents = $students
            ->whereIn('status', ['left','passed','inactive'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->first_name.' '.$s->last_name,
                'dob' => $s->details?->dob,
            ])
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
                        'marks' => $s->marks,
                        'topic' => $s->topic?->name,
                        'room' => $s->room_no,
                    ]),
                ];
            });

        /* ===================== MONTHLY DATE-WISE SCHEDULE ===================== */

        $batchSubjects = BatchSubject::with(['subject','teacher','extraTeacher'])
            ->where('batch_id', $batch->id)
            ->get()
            ->keyBy('subject_id');

        $routines = ClassRoutine::with(['days','subject'])
            ->where('class_id', $course->standard_id)
            ->whereIn('subject_id', $batchSubjects->keys())
            ->where('is_active', true)
            ->get();

        $start = Carbon::parse($batch->start_date);
        $end   = Carbon::parse($batch->end_date);

        $schedule = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

            if ($date->month !== $month || $date->year !== $year) {
                continue;
            }

            foreach ($routines as $routine) {

                $days = $routine->days->pluck('day')->map(fn ($d) => strtolower($d));

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

                $bs = $batchSubjects[$routine->subject_id];

                $schedule[] = [
                    'date' => $date->toDateString(),
                    'day' => $date->format('l'),
                    'batch' => $batch->name,
                    'subject' => $routine->subject?->name,
                    'topic' => null,
                    'teacher' => $bs->teacher?->name,
                    'start_time' => Carbon::parse($routine->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($routine->end_time)->format('H:i'),
                    'status' => $status,
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

                'exams' => $exams,

                'monthly_schedule' => $schedule,
            ]
        ]);
    }
}
