<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\InstituteUser;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class StudentController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string',
            'class_id' => 'nullable|exists:class_rooms,id',
            'course_id' => 'nullable|exists:courses,id',
            'batch_id' => 'nullable|exists:batches,id',
            'status' => 'nullable|string',
        ]);

        $search = trim($request->get('q'));

        $classIds = collect();

        /**
         * ✅ 1. Direct class filter
         */
        if ($request->filled('class_id')) {
            $classIds->push($request->class_id);
        }

        /**
         * ✅ 2. Course → class
         */
        if ($request->filled('course_id')) {
            $course = \App\Models\Course::find($request->course_id);
            if ($course) {
                $classIds->push($course->standard_id);
            }
        }

        /**
         * ✅ 3. Batch → course → class
         */
        if ($request->filled('batch_id')) {
            $batch = \App\Models\Batch::find($request->batch_id);

            if ($batch) {
                $course = \App\Models\Course::find($batch->course_id);
                if ($course) {
                    $classIds->push($course->standard_id);
                }
            }
        }

        $classIds = $classIds->unique()->values();

        /**
         * 🔥 MAIN QUERY
         */
        $students = Student::with(['details', 'classRoom'])

            // ✅ SEARCH
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {

                    $q->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('admission_no', 'LIKE', "%{$search}%")
                        ->orWhere('roll_no', 'LIKE', "%{$search}%")
                        ->orWhereHas('details', function ($dq) use ($search) {
                            $dq->where('phone', 'LIKE', "%{$search}%");
                        });
                });
            })

            // ✅ CLASS FILTER
            ->when($classIds->isNotEmpty(), function ($q) use ($classIds) {
                $q->whereIn('class', $classIds);
            })

            // ✅ STATUS FILTER
            ->when(
                $request->filled('status') && strtolower($request->status) !== 'all',
                function ($q) use ($request) {
                    $q->where('status', strtolower($request->status));
                }
            )

            ->latest()
            ->get()

            // ✅ RESPONSE FORMAT
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'admission_no' => $s->admission_no,
                    'roll_no' => $s->roll_no,

                    'first_name' => $s->first_name,
                    'last_name' => $s->last_name,

                    'gender' => $s->details?->gender,
                    'dob' => $s->details?->dob,
                    'father_name' => $s->details?->father_name,
                    'phone' => $s->details?->phone,

                    'classes' => $s->classRoom,
                    'section' => $s->section,
                    'status' => $s->status,
                    'admission_date' => $s->admission_date,
                ];
            });

        return response()->json([
            'status' => 'success',
            'filters' => [
                'q' => $search,
                'class_id' => $request->class_id,
                'course_id' => $request->course_id,
                'batch_id' => $request->batch_id,
                'status' => $request->status ?? 'ALL',
            ],
            'count' => $students->count(),
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

            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:10',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'father_name' => 'nullable|string|max:100',
            'mother_name' => 'nullable|string|max:100',
            'parent_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'medical_info' => 'nullable|string',

            // file validation
            'aadhaar' => 'nullable|file|mimes:pdf,jpg,jpeg,png,csv,docx,doc,xlsx,xls|max:5120',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png,csv,docx,doc,xlsx,xls|max:5120',
            'document_other' => 'nullable|file|mimes:pdf,jpg,jpeg,png,csv,docx,doc,xlsx,xls|max:5120',
        ]);

        DB::beginTransaction();

        try {

            // 🔹 1️⃣ Create Login User
            $password = Str::random(10);

            $user = User::create([
                'uid' => 'ST'.Str::random(5),
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $validated['email'] ?? null,
                'temp_password' => Crypt::encryptString($password),
                'password' => Hash::make($password),
                'role' => 'student',
            ]);

            InstituteUser::create([
                'user_id' => $user->id,
                'role' => 'student',
            ]);

            // 🔹 2️⃣ Create Student
            $student = Student::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'class' => $validated['class'],
                'section' => $validated['section'] ?? null,
                'status' => $validated['status'],
                'user_id' => $user->id,
                'admission_date' => $validated['admission_date'] ?? null,
            ]);

            // 🔹 3️⃣ Handle File Uploads
            $aadhaarPath = null;
            $docPath = null;
            $docOtherPath = null;

            if ($request->hasFile('aadhaar')) {
                $aadhaarPath = $request->file('aadhaar')
                    ->store('students/aadhaar', 'public');
            }

            if ($request->hasFile('document')) {
                $docPath = $request->file('document')
                    ->store('students/documents', 'public');
            }

            if ($request->hasFile('document_other')) {
                $docOtherPath = $request->file('document_other')
                    ->store('students/other_documents', 'public');
            }

            // 🔹 4️⃣ Create Student Details
            StudentDetail::create([
                'student_id' => $student->id,
                'dob' => $validated['dob'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'blood_group' => $validated['blood_group'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'father_name' => $validated['father_name'] ?? null,
                'mother_name' => $validated['mother_name'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'medical_info' => $validated['medical_info'] ?? null,

                'aadhaar_doc' => $aadhaarPath,
                'document' => $docPath,
                'document_other' => $docOtherPath,
            ]);

            // 🔹 5️⃣ Recalculate Roll Numbers
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
        $student = Student::with([
            'details',
            'classRoom',
            'examAttendances',
        ])->findOrFail($id);

        // 🔹 Get exams for student class
       $exams = Exam::whereHas('course', function ($q) use ($student) {
                $q->where('standard_id', $student->class);
            })
            ->with(['course', 'batch'])
            ->get()
            ->map(function ($exam) use ($student) {

                $attendance = $exam->attendances()
                    ->where('student_id', $student->id)
                    ->first();

                return [
                    'exam_id' => $exam->id,
                    'title' => $exam->title,
                    'exam_date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'end_time' => $exam->end_time,
                    'status' => $exam->status,
                    'attended' => $attendance ? true : false,
                    'attendance_status' => $attendance->attendance ?? null,
                ];
            });


        return response()->json([
            'status' => 'success',
            'data' => array_merge($student->toArray(), ['exams' => $exams]),
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


    public function fees($id)
    {
        $student = Student::with([
            'fees.structure.feesType',
            'fees.structure.batches',
            'fees.installments',
            'fees.ledgers',
        ])->findOrFail($id);

        /* ================= SUMMARY ================= */
        $totalFees = $student->fees->sum('total_amount');

        $totalPaid = $student->feeLedgers
            ->where('type', 'CREDIT')
            ->sum('amount');

        $totalFine = $student->feeLedgers
            ->where('type', 'DEBIT')
            ->sum('amount');

        $totalDue = max(($totalFees + $totalFine) - $totalPaid, 0);

        /* ================= PAYMENT HISTORY ================= */
        $payments = $student->feeLedgers->map(function ($l) {
            return [
                'paid_on' => optional($l->created_at)->format('Y-m-d'),
                'installment_no' => $l->student_fee_installment_id,
                'fee_type' => optional($l->feesType)->name ?? '-',
                'batch' => optional($l->studentFee?->structure?->batches->first())->name,
                'amount_paid' => $l->amount,
                'payment_mode' => 'Online', // if column exists replace
                'remark' => $l->description,
            ];
        });

        /* ================= STRUCTURE VIEW ================= */
        $feeStructures = $student->fees->map(function ($fee) {
            return [
                'fees_structure_id' => $fee->fees_structure_id,
                'fee_type' => $fee->structure->feesType->name ?? null,
                'batches' => $fee->structure->batches->pluck('name'),
                'total_amount' => $fee->total_amount,
                'installments' => $fee->installments->map(function ($i) {
                    return [
                        'installment_id' => $i->id,
                        'assign_type' => $i->assign_type,
                        'amount' => $i->amount,
                        'offset' => $i->offset,
                        'is_extra' => $i->is_extra,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'fees' => $totalFees,
                    'concession' => 0,
                    'tax' => 0,
                    'total_payable' => $totalFees,
                    'amount_paid' => $totalPaid,
                    'bad_debt' => 0,
                    'overdue_fees' => $totalDue,
                    'upcoming_dues' => 0,
                    'total_dues' => $totalDue,
                    'total_fine' => $totalFine,
                    'paid_fine' => 0,
                    'balance_fine' => $totalFine,
                ],
                'payment_history' => $payments,
                'fee_structures' => $feeStructures,
            ]
        ]);
    }




}
