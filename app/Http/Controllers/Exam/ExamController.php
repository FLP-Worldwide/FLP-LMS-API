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
            'batch:id,name,course_id',
            'subjects.subject:id,name,short_code',
            'subjects.topic:id,name',
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
            'data' => $exams,
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
