<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use Illuminate\Http\Request;

class StudentFinancialSummaryController extends Controller
{
    public function show($studentId)
    {
        /**
         * 1️⃣ Student + personal details
         */
        $student = Student::with([
                'details',
            ])
            ->findOrFail($studentId);

        /**
         * 2️⃣ Course & Batch (derived via student_fees → fees_structures → batches)
         */
        $studentFees = StudentFee::with([
                'structure.feesType',
                'structure.batches.course.classRoom',
                'installments.feesType',
            ])
            ->where('student_id', $student->id)
            ->get();

        /**
         * 3️⃣ Payments (pending + approved)
         */
        $payments = StudentFeePayment::where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'payment_mode' => $p->payment_mode,
                    'amount' => $p->amount,
                    'payment_date' => $p->payment_date,
                    'status' => $p->status,
                    'remarks' => $p->remarks,
                ];
            });

        /**
         * 4️⃣ Installment-wise breakdown
         */
        $installments = [];
        $totalAssigned = 0;
        $totalPaid = 0;

        foreach ($studentFees as $fee) {
            foreach ($fee->installments as $inst) {

                $assigned = $inst->amount;

                $paid = StudentFeeLedger::where('student_fee_installment_id', $inst->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                $pending = max($assigned - $paid, 0);

                $totalAssigned += $assigned;
                $totalPaid += $paid;

                $installments[] = [
                    'student_fee_id' => $fee->id,
                    'installment_id' => $inst->id,

                    'fee_structure' => $fee->structure->name,
                    'fee_type' => $inst->feesType->name,

                    'assign_type' => $inst->assign_type,
                    'offset' => $inst->offset,

                    'assigned_amount' => $assigned,
                    'paid_amount' => $paid,
                    'pending_amount' => $pending,

                    'status' => $pending <= 0 ? 'PAID' : 'PENDING',
                    'is_extra' => (bool) $inst->is_extra,
                ];
            }
        }

        /**
         * 5️⃣ Ledger totals (final authority)
         */
        $ledgerDebit = StudentFeeLedger::where('student_id', $student->id)
            ->where('type', 'DEBIT')
            ->sum('amount');

        $ledgerCredit = StudentFeeLedger::where('student_id', $student->id)
            ->where('type', 'CREDIT')
            ->sum('amount');

        /**
         * 6️⃣ Final response
         */
        return response()->json([
            'status' => 'success',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'roll_no' => $student->roll_no,
                    'name' => trim($student->first_name . ' ' . $student->last_name),
                    'class' => $student->class,
                    'section' => $student->section,
                    'status' => $student->status,
                    'details' => $student->details,
                ],

                'course_batch' => $studentFees->flatMap(function ($sf) {
                    return $sf->structure->batches->map(function ($b) {
                        return [
                            'batch_id' => $b->id,
                            'batch_name' => $b->name,
                            'academic_year' => $b->academic_year,
                            'course' => $b->course->name,
                            'class' => $b->course->classRoom->name,
                            'start_date' => $b->start_date,
                            'end_date' => $b->end_date,
                        ];
                    });
                })->unique('batch_id')->values(),

                'installments' => $installments,

                'payments' => $payments,

                'summary' => [
                    'total_assigned' => $ledgerDebit,
                    'total_paid' => $ledgerCredit,
                    'total_pending' => max($ledgerDebit - $ledgerCredit, 0),
                ],
            ],
        ]);
    }
}
