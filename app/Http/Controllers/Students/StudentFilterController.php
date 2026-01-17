<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\StudentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFilterController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'batch_id' => 'nullable|exists:batches,id',
            'academic_year' => 'nullable|string',
            'status' => 'nullable|string', // ACTIVE | INACTIVE | ALL
        ]);

        /**
         * STEP 1️⃣: Resolve Class IDs from course + batch
         */
        $classIds = ClassRoom::query()
            ->when($request->filled('course_id'), function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            })
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->where('batch_id', $request->batch_id);
            })
            ->pluck('id');

        /**
         * STEP 2️⃣: Fetch students from resolved classes
         */
        $students = Student::with(['details', 'classRoom'])
            ->when($classIds->isNotEmpty(), function ($q) use ($classIds) {
                $q->whereIn('class_room_id', $classIds);
            })

            // Academic year filter
            ->when($request->filled('academic_year'), function ($q) use ($request) {
                $q->where('academic_year', $request->academic_year);
            })

            // Status filter
            ->when(
                $request->filled('status') && $request->status !== 'ALL',
                function ($q) use ($request) {
                    $q->where('status', strtolower($request->status));
                }
            )

            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'filters' => [
                'course_id' => $request->course_id,
                'batch_id' => $request->batch_id,
                'academic_year' => $request->academic_year,
                'status' => $request->status ?? 'ALL',
            ],
            'count' => $students->count(),
            'data' => $students,
        ]);
    }
}
