<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{

     public function index(Request $request)
    {
        $students = Student::with('details')
            ->latest()
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'admission_no' => $s->admission_no,
                    'roll_no' => $s->roll_no,
                    'gender' => $s->details?->gender,
                    'dob' => $s->details?->dob,
                    'first_name' => $s->first_name,
                    'last_name' => $s->last_name,
                    'father_name' => $s->details?->father_name,
                    'classes' => $s->classRoom,
                    'section' => $s->section,
                    'status' => $s->status,
                    'phone' => $s->details?->phone,
                    'admission_date' => $s->admission_date,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $students,
        ]);
    }


   public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,passed,left',
            'admission_date' => 'nullable|date',

            'details.dob' => 'nullable|date',
            'details.gender' => 'nullable|in:male,female,other',
            'details.blood_group' => 'nullable|string|max:10',
            'details.email' => 'nullable|email',
            'details.phone' => 'nullable|string|max:15',
            'details.father_name' => 'nullable|string|max:100',
            'details.mother_name' => 'nullable|string|max:100',
            'details.parent_phone' => 'nullable|string|max:15',
            'details.address' => 'nullable|string',
            'details.city' => 'nullable|string|max:100',
            'details.state' => 'nullable|string|max:100',
            'details.medical_info' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Create student without roll_no first
            $student = Student::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'class' => $validated['class'],
                'section' => $validated['section'] ?? null,
                'status' => $validated['status'],
                'admission_date' => $validated['admission_date'] ?? null,
            ]);

            // 2️⃣ Create student details
            StudentDetail::create([
                'student_id' => $student->id,
                ...($validated['details'] ?? [])
            ]);

            // 3️⃣ Recalculate roll numbers alphabetically
            $this->recalculateRollNumbers(
                $student->class,
                $student->section
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Student created successfully',
                'data' => $student->load('details'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔁 Roll number recalculation
     * Alphabetical order by first_name + last_name
     * Class + section wise
     */
    private function recalculateRollNumbers($class, $section)
    {
        $students = Student::where('class', $class)
            ->where('section', $section)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        foreach ($students as $index => $student) {
            $student->update([
                'roll_no' => $index + 1
            ]);
        }
    }

    /* =====================================================
       👁️ SHOW – Student Details
    ===================================================== */
    public function show($id)
    {
        $student = Student::with('details')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $student,
        ]);
    }

    /* =====================================================
       ✏️ UPDATE – Update Student
    ===================================================== */
    public function update(Request $request, $id)
    {
        $student = Student::with('details')->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive,passed,left',
            'admission_date' => 'nullable|date',

            'details.dob' => 'nullable|date',
            'details.gender' => 'nullable|in:male,female,other',
            'details.email' => 'nullable|email',
            'details.phone' => 'nullable|string|max:15',
            'details.father_name' => 'nullable|string|max:100',
            'details.mother_name' => 'nullable|string|max:100',
            'details.parent_phone' => 'nullable|string|max:15',
            'details.address' => 'nullable|string',
            'details.city' => 'nullable|string|max:100',
            'details.state' => 'nullable|string|max:100',
            'details.medical_info' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $student->update($validated);

            if ($student->details) {
                $student->details->update($validated['details'] ?? []);
            } else {
                StudentDetail::create(
                    array_merge(
                        ['student_id' => $student->id],
                        $validated['details'] ?? []
                    )
                );
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Student updated successfully',
                'data' => $student->load('details'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* =====================================================
       🗑️ DELETE – Soft Delete
    ===================================================== */
    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Student deleted successfully',
        ]);
    }
}
