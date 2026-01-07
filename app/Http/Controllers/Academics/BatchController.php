<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    /**
     * LIST BATCHES
     */
    public function index(Request $request)
    {
        $batches = Batch::with([
                'course',
                'subjects.subject',
                'subjects.teacher',
                'subjects.extraTeacher'
            ])
            ->when($request->course_id, fn ($q) =>
                $q->where('course_id', $request->course_id)
            )
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $batches
        ]);
    }

    /**
     * STORE BATCH
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id'      => 'required|exists:courses,id',
            'name'           => 'required|string|max:100',
            'academic_year'  => 'required|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',

            'subjects'                     => 'required|array|min:1',
            'subjects.*.subject_id'        => 'required|exists:subjects,id',
            'subjects.*.teacher_id'        => 'required|exists:teachers,id',
            'subjects.*.extra_teacher_id'  => 'nullable|exists:teachers,id',
        ]);

        DB::beginTransaction();

        try {
            $batch = Batch::create([
                'course_id'     => $validated['course_id'],
                'name'          => $validated['name'],
                'batch_uid'     => "BATCH-" . strtoupper(rand(100000, 999999)),
                'academic_year' => $validated['academic_year'],
                'start_date'    => $validated['start_date'],
                'end_date'      => $validated['end_date'],
            ]);

            foreach ($validated['subjects'] as $sub) {
                BatchSubject::create([
                    'batch_id'         => $batch->id,
                    'subject_id'       => $sub['subject_id'],
                    'teacher_id'       => $sub['teacher_id'],
                    'extra_teacher_id' => $sub['extra_teacher_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Batch created successfully',
                'data'    => $batch->load([
                    'course',
                    'subjects.subject',
                    'subjects.teacher',
                    'subjects.extraTeacher'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * SHOW SINGLE BATCH
     */
    public function show($id)
    {
        $batch = Batch::with([
            'course',
            'subjects.subject',
            'subjects.teacher',
            'subjects.extraTeacher'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $batch
        ]);
    }

    /**
     * UPDATE BATCH
     */
    public function update(Request $request, $id)
    {
        $batch = Batch::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'academic_year'  => 'required|string',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after:start_date',

            'subjects'                     => 'required|array|min:1',
            'subjects.*.subject_id'        => 'required|exists:subjects,id',
            'subjects.*.teacher_id'        => 'required|exists:teachers,id',
            'subjects.*.extra_teacher_id'  => 'nullable|exists:teachers,id',
        ]);

        DB::beginTransaction();

        try {
            $batch->update([
                'name'          => $validated['name'],
                'academic_year' => $validated['academic_year'],
                'start_date'    => $validated['start_date'],
                'end_date'      => $validated['end_date'],
            ]);

            BatchSubject::where('batch_id', $batch->id)->delete();

            foreach ($validated['subjects'] as $sub) {
                BatchSubject::create([
                    'batch_id'         => $batch->id,
                    'subject_id'       => $sub['subject_id'],
                    'teacher_id'       => $sub['teacher_id'],
                    'extra_teacher_id' => $sub['extra_teacher_id'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Batch updated successfully',
                'data'    => $batch->load([
                    'course',
                    'subjects.subject',
                    'subjects.teacher',
                    'subjects.extraTeacher'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * DELETE BATCH
     */
    public function destroy($id)
    {
        Batch::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Batch deleted successfully'
        ]);
    }
}
