<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $courses = Course::with('classRoom')
            ->when($request->standard_id, fn ($q) =>
                $q->where('standard_id', $request->standard_id)
            )
            ->when($request->active_only, fn ($q) =>
                $q->where('is_active', true)
            )
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $courses
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:150',
            'standard_id'          => 'required|exists:class_rooms,id',
            'short_description'    => 'nullable|string',
            'show_on_registration' => 'boolean',
        ]);

        $course = Course::create([
            'name'                 => $validated['name'],
            'standard_id'          => $validated['standard_id'],
            'short_description'    => $validated['short_description'] ?? null,
            'show_on_registration' => $validated['show_on_registration'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Course created successfully',
            'data'    => $course
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $course = Course::with('classRoom')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $course
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(course $course)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name'                 => 'required|string|max:150',
            'standard_id'          => 'required|exists:class_rooms,id',
            'short_description'    => 'nullable|string',
            'show_on_registration' => 'boolean',
            'is_active'            => 'boolean',
        ]);

        $course->update([
            'name'                 => $validated['name'],
            'standard_id'          => $validated['standard_id'],
            'short_description'    => $validated['short_description'] ?? null,
            'show_on_registration' => $validated['show_on_registration'] ?? true,
            'is_active'            => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Course updated successfully',
            'data'    => $course
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Course::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Course deleted successfully'
        ]);
    }

}
