<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\FeesStructure;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\StudentFee;
use App\Models\StudentFeeLedger;
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
            'status' => 'nullable|string', // active | inactive | ALL
        ]);

        $classIds = collect();

        // 1️⃣ Resolve class from course
        if ($request->filled('course_id')) {
            $course = Course::find($request->course_id);
            if ($course) {
                $classIds->push($course->standard_id);
            }
        }

        // 2️⃣ Resolve class from batch → course
        if ($request->filled('batch_id')) {
            $batch = Batch::find($request->batch_id);

            if ($batch) {
                if (
                    $request->filled('academic_year') &&
                    $batch->academic_year !== $request->academic_year
                ) {
                    return response()->json([
                        'status' => 'success',
                        'count' => 0,
                        'data' => [],
                    ]);
                }

                $course = Course::find($batch->course_id);
                if ($course) {
                    $classIds->push($course->standard_id);
                }
            }
        }

        $classIds = $classIds->unique()->values();

        /**
         * 3️⃣ Fees structures (class-level total)
         */
        $feesStructures = FeesStructure::query()
            ->when($classIds->isNotEmpty(), fn ($q) =>
                $q->whereIn('class_id', $classIds)
            )
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->whereHas('batches', fn ($b) =>
                    $b->where('batch_id', $request->batch_id)
                );
            })
            ->get()
            ->groupBy('class_id');

        /**
         * 4️⃣ Students
         */
        $students = Student::query()
            ->when($classIds->isNotEmpty(), fn ($q) =>
                $q->whereIn('class', $classIds)
            )
            ->when(
                $request->filled('status') && $request->status !== 'ALL',
                fn ($q) => $q->where('status', strtolower($request->status))
            )
            ->latest()
            ->get();

        /**
         * 5️⃣ Assigned fees (student-level)
         */
        $assignedFees = StudentFee::whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id'); // one assignment per student

        /**
         * 6️⃣ Attach totals
         */
        $students = $students->map(function ($student) use ($feesStructures, $assignedFees) {
            $classId = $student->class;

            // Class-level total
            $student->total_fees = $feesStructures->has($classId)
                ? $feesStructures[$classId]->sum('amount')
                : '-';

            // Student-level assigned total
            if ($assignedFees->has($student->id)) {
                    $student->assigned_fees = $assignedFees[$student->id]->sum('total_amount');
                } else {
                    $student->assigned_fees = '-';
                }


            return $student;
        });

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



    public function financialIndex(Request $request)
    {
        $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'batch_id' => 'nullable|exists:batches,id',
            'academic_year' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        /* -------------------------------------------------------
        * STEP 1️⃣ Resolve class IDs from filters
        * -----------------------------------------------------*/
        $classIds = collect();

        if ($request->filled('course_id')) {
            $course = Course::find($request->course_id);
            if ($course) {
                $classIds->push($course->standard_id);
            }
        }

        if ($request->filled('batch_id')) {
            $batch = Batch::find($request->batch_id);

            if ($batch) {
                if (
                    $request->filled('academic_year') &&
                    $batch->academic_year !== $request->academic_year
                ) {
                    return response()->json([
                        'status' => 'success',
                        'count' => 0,
                        'data' => [],
                    ]);
                }

                $course = Course::find($batch->course_id);
                if ($course) {
                    $classIds->push($course->standard_id);
                }
            }
        }

        $classIds = $classIds->unique()->values();

        /* -------------------------------------------------------
        * STEP 2️⃣ Load masters
        * -----------------------------------------------------*/
        $classes = ClassRoom::pluck('name', 'id');
        $courses = Course::all()->groupBy('standard_id');
        $batches = Batch::all()->groupBy('course_id');

        /* -------------------------------------------------------
        * STEP 3️⃣ Class-level fees
        * -----------------------------------------------------*/
        $classFees = FeesStructure::query()
            ->when($classIds->isNotEmpty(), fn ($q) =>
                $q->whereIn('class_id', $classIds)
            )
            ->get()
            ->groupBy('class_id');

        /* -------------------------------------------------------
        * STEP 4️⃣ Students
        * -----------------------------------------------------*/
        $students = Student::query()
            ->when($classIds->isNotEmpty(), fn ($q) =>
                $q->whereIn('class', $classIds)
            )
            ->when(
                $request->filled('status') && $request->status !== 'ALL',
                fn ($q) => $q->where('status', strtolower($request->status))
            )
            ->latest()
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'count' => 0,
                'data' => [],
            ]);
        }

        /* -------------------------------------------------------
        * STEP 5️⃣ Student fees & ledger
        * -----------------------------------------------------*/
        $studentFees = StudentFee::whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        $ledgers = StudentFeeLedger::whereIn('student_id', $students->pluck('id'))
            ->get()
            ->groupBy('student_id');

        /* -------------------------------------------------------
        * STEP 6️⃣ Final mapping
        * -----------------------------------------------------*/
        $students = $students->map(function ($student) use (
            $classes,
            $courses,
            $batches,
            $classFees,
            $studentFees,
            $ledgers
        ) {

            $classId = $student->class;

            // Resolve course via class
            $course = $courses->get($classId)?->first();

            // Resolve batch via course
            $batch = $course
                ? $batches->get($course->id)?->first()
                : null;

            $totalFees = $classFees->has($classId)
                ? $classFees[$classId]->sum('amount')
                : 0;

            $assigned = $studentFees->has($student->id)
                ? $studentFees[$student->id]->sum('total_amount')
                : 0;

            $paid = $ledgers->has($student->id)
                ? $ledgers[$student->id]
                    ->where('type', 'CREDIT')
                    ->sum('amount')
                : 0;

            $pending = max($assigned - $paid, 0);

            return [
                'student_id' => $student->id,
                'admission_no' => $student->admission_no,
                'student_name' => trim($student->first_name . ' ' . $student->last_name),

                'class' => $classes[$classId] ?? null,
                'course' => $course?->name,
                'batch' => $batch?->name,
                'section' => $student->section,

                'status' => $student->status,

                'total_fees' => $totalFees,
                'assigned_fees' => $assigned,
                'paid_amount' => $paid,
                'pending_amount' => $pending,

                'is_fully_paid' => $pending == 0,
            ];
        });

        return response()->json([
            'status' => 'success',
            'count' => $students->count(),
            'data' => $students,
        ]);
    }


}
