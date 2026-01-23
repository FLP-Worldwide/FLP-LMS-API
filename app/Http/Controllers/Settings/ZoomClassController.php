<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LiveClassSetting;
use App\Models\ZoomClass;
use Illuminate\Http\Request;

class ZoomClassController extends Controller
{

public function store(Request $request)
{
    $data = $request->validate([
        'topic' => 'required|string',
        'date' => 'required|date',
        'from_time' => 'required',
        'to_time' => 'required',

        'teacher_ids' => 'required|array',
        'teacher_ids.*' => 'exists:users,id',

        'course_ids' => 'nullable|array',
        'course_ids.*' => 'exists:courses,id',

        'batch_ids' => 'nullable|array',
        'batch_ids.*' => 'exists:batches,id',

        'student_ids' => 'nullable|array',
        'student_ids.*' => 'exists:students,id',

        'settings' => 'nullable|array',
    ]);

    $zoomClass = ZoomClass::create($data);

    $zoomClass->teachers()->sync($request->teacher_ids);
    $zoomClass->courses()->sync($request->course_ids ?? []);
    $zoomClass->batches()->sync($request->batch_ids ?? []);
    $zoomClass->students()->sync($request->student_ids ?? []);

    return response()->json([
        'status' => 'success',
        'message' => 'Zoom class created successfully',
        'data' => $zoomClass->load('teachers', 'courses', 'batches', 'students'),
    ], 201);
}


public function index(Request $request)
{
    $classes = ZoomClass::with([
        'teachers',
        'courses:id,name',
        'batches:id,name',
        'students:id,first_name,last_name',
    ])->latest()->get();

    return response()->json([
        'status' => 'success',
        'data' => $classes,
    ]);
}

public function show($id)
{
    $class = ZoomClass::with([
        'teachers',
        'courses',
        'batches',
        'students',
    ])->findOrFail($id);

    return response()->json([
        'status' => 'success',
        'data' => $class,
    ]);
}


public function update(Request $request, $id)
{
    $zoomClass = ZoomClass::findOrFail($id);

    $zoomClass->update($request->only([
        'topic',
        'date',
        'from_time',
        'to_time',
        'settings',
    ]));

    if ($request->has('teacher_ids')) {
        $zoomClass->teachers()->sync($request->teacher_ids);
    }

    if ($request->has('course_ids')) {
        $zoomClass->courses()->sync($request->course_ids);
    }

    if ($request->has('batch_ids')) {
        $zoomClass->batches()->sync($request->batch_ids);
    }

    if ($request->has('student_ids')) {
        $zoomClass->students()->sync($request->student_ids);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Zoom class updated successfully',
    ]);
}


public function destroy($id)
{
    ZoomClass::findOrFail($id)->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Zoom class deleted successfully',
    ]);
}


}
