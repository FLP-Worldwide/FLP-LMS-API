<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ClassRoom;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    /**
     * List class_rooms
     */

    public function index(Request $request)
    {
        $batchId  = $request->batch_id;
        $courseId = $request->course_id;

        $query = ClassRoom::query();

        /**
         * ------------------------------
         * 1️⃣ FILTER BY BATCH
         * ------------------------------
         * batch → course → class
         */
        if ($batchId) {

            $batch = Batch::with('course')->find($batchId);

            if ($batch && $batch->course) {
                $query->where('id', $batch->course->standard_id);
            }
        }

        /**
         * ------------------------------
         * 2️⃣ FILTER BY COURSE
         * ------------------------------
         * course → class
         */
        elseif ($courseId) {

            $course = Course::find($courseId);

            if ($course) {
                $query->where('id', $course->standard_id);
            }
        }

        /**
         * ------------------------------
         * RESULT
         * ------------------------------
         */
        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('created_on')->get(),
        ]);
    }

    /**
     * Create class
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('class_rooms')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                    ),
            ],
        ]);

        $class = ClassRoom::create([
            'name'       => $validated['name'],
            'class_code' => 'CLS-' . strtoupper(Str::random(4)),
            'created_on' => now()->toDateString(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Class created successfully.',
            'data'    => $class,
        ], 201);
    }

    /**
     * Show single class
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => ClassRoom::findOrFail($id),
        ]);
    }

    /**
     * Update class name / status
     */
    public function update(Request $request, $id)
    {
        $class = ClassRoom::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('class_rooms')
                    ->ignore($class->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                    ),
            ],
            'is_active' => 'boolean',
        ]);

        $class->update([
            'name'      => $validated['name'],
            'is_active' => $validated['is_active'] ?? $class->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Class updated successfully.',
            'data'    => $class,
        ]);
    }
}
