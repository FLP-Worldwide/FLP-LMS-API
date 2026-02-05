<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;

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
        $query = Exam::with([
            'course:id,name,standard_id',
            'course.classRoom:id,name',
            'course.classRoom.subjects:id,class_id,name',
            'batch:id,name,course_id',
            'subjects.subject:id,name',
        ]);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->filled('exam_date')) {
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
            'data' => $exams->map(function ($exam) {

                return [
                    'id' => $exam->id,
                    'exam_date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'room' => $exam->subjects->first()?->room_no,
                    'topic' => $exam->topic ? $exam->topic->name : null,

                    'course' => [
                        'id' => $exam->course->id,
                        'name' => $exam->course->name,
                    ],

                     'batch' => $exam->batch ? [
                        'id' => $exam->batch->id,
                        'name' => $exam->batch->name,
                        'course_id' => $exam->batch->course_id,
                    ] : null,

                    'class' => [
                        'id' => $exam->course->classRoom->id,
                        'name' => $exam->course->classRoom->name,
                    ],

                    'subjects' => $exam->subjects->map(fn ($s) => [
                        'id' => $s->subject->id,
                        'name' => $s->subject->name,
                        'marks' => $s->marks,
                    ]),
                ];
            }),
        ]);
    }




    public function show($id)
    {
        $exam = Exam::with(['subjects.subject'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $exam,
        ]);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);

        $request->validate([
            'exam_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'subjects' => 'required|array|min:1',
        ]);

        $exam->update($request->only([
            'exam_date',
            'start_time',
            'end_time',
            'title',
            'description',
        ]));

        // 🔥 Remove old subjects
        $exam->subjects()->delete();

        // 🔥 Insert new subjects
        foreach ($request->subjects as $subject) {
            $exam->subjects()->create($subject);
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

}
