<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FeeConcession;
use App\Models\Student;
use App\Models\StudentFeeConcession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeConcessionController extends Controller
{
    /* ================= LIST ================= */
    public function index()
    {
        $data = FeeConcession::with(['batches', 'feeTypes', 'course'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /* ================= STORE ================= */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'type' => 'required|in:AMOUNT,PERCENT',
                'amount' => 'required|numeric|min:0',

                'course_id' => 'nullable|exists:courses,id',
                'batch_ids' => 'nullable|array',
                'batch_ids.*' => 'exists:batches,id',

                'fee_type_ids' => 'required|array',
                'fee_type_ids.*' => 'exists:fees_types,id',
            ]);

            $concession = FeeConcession::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'course_id' => $validated['course_id'] ?? null,
            ]);

            if (!empty($validated['batch_ids'])) {
                $concession->batches()->sync($validated['batch_ids']);
            }

            $concession->feeTypes()->sync($validated['fee_type_ids']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Concession created successfully',
                'data' => $concession->load(['batches','feeTypes','course'])
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /* ================= SHOW ================= */
    public function show($id)
    {
        $concession = FeeConcession::with(['batches','feeTypes','course'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $concession
        ]);
    }

    /* ================= UPDATE ================= */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $concession = FeeConcession::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'type' => 'required|in:AMOUNT,PERCENT',
                'amount' => 'required|numeric|min:0',

                'course_id' => 'nullable|exists:courses,id',
                'batch_ids' => 'nullable|array',
                'batch_ids.*' => 'exists:batches,id',

                'fee_type_ids' => 'required|array',
                'fee_type_ids.*' => 'exists:fees_types,id',
            ]);

            $concession->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'course_id' => $validated['course_id'] ?? null,
            ]);

            $concession->batches()->sync($validated['batch_ids'] ?? []);
            $concession->feeTypes()->sync($validated['fee_type_ids']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Concession updated successfully',
                'data' => $concession->load(['batches','feeTypes','course'])
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /* ================= DELETE ================= */
    public function destroy($id)
    {
        $concession = FeeConcession::findOrFail($id);
        $concession->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Concession deleted successfully'
        ]);
    }


    public function students(Request $request)
    {
        $courseId     = $request->course_id;
        $batchId      = $request->batch_id;
        $concessionId = $request->concession_id; // OPTIONAL
        $search       = $request->search;

        // 1️⃣ Course → Class
        $course  = Course::findOrFail($courseId);
        $classId = $course->standard_id;

        // 2️⃣ Get ALL students of this class
        $students = Student::where('class', $classId)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_no', 'like', "%{$search}%");
                });
            })
            ->get();

        // 3️⃣ Get assigned student IDs (dynamic)
        $assignedStudentIds = StudentFeeConcession::when($concessionId, function ($q) use ($concessionId) {
                $q->where('fee_concession_id', $concessionId);
            })
            ->pluck('student_id')
            ->toArray();

        // 4️⃣ Response
        $data = $students->map(function ($student) use ($assignedStudentIds) {
            return [
                'id'           => $student->id,
                'name'         => trim($student->first_name . ' ' . $student->last_name),
                'admission_no' => $student->admission_no,
                'is_assigned'  => in_array($student->id, $assignedStudentIds),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }



    public function assignToStudent(Request $request)
    {
        $validated = $request->validate([
            'student_id'    => 'required|exists:students,id',
            'concession_id' => 'required|exists:fee_concessions,id',
        ]);

        $record = StudentFeeConcession::where('student_id', $validated['student_id'])
            ->where('fee_concession_id', $validated['concession_id'])
            ->first();

        if ($record) {
            $record->delete();
            $action = 'removed';
        } else {
            StudentFeeConcession::create([
                'student_id'        => $validated['student_id'],
                'fee_concession_id' => $validated['concession_id'],
            ]);

            $action = 'assigned';
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Concession {$action} successfully",
        ]);
    }


    public function byBatch(Request $request)
    {
        $courseId = $request->course_id;
        $batchId  = $request->batch_id;

        $concessions = FeeConcession::where(function ($q) use ($courseId, $batchId) {
            $q->where('course_id', $courseId)
              ->orWhereHas('batches', function ($qq) use ($batchId) {
                  $qq->where('batches.id', $batchId);
              });
        })
        ->with(['batches','feeTypes','course'])
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $concessions
        ]);
    }

}

