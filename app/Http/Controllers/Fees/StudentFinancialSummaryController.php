<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentApproveConcession;
use App\Models\StudentFee;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeRefund;
use Illuminate\Http\Request;

class StudentFinancialSummaryController extends Controller
{
    public function show($studentId)
    {
        /**
         * 1️⃣ Student + personal details
         */
        $student = Student::with(['details'])->findOrFail($studentId);

        /**
         * 2️⃣ Student fees + structure + installments
         */
        $studentFees = StudentFee::with([
            'structure.feesType',
            'structure.batches.course.classRoom',
            'installments.feesType',
        ])
            ->where('student_id', $student->id)
            ->get();

        /**
         * 3️⃣ Payments list
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
         * 4️⃣ Installment-wise breakdown + calculations
         */
        $installments = [];

        $totalFeesAssigned = 0;
        $overdueFees = 0;

        foreach ($studentFees as $fee) {
            foreach ($fee->installments as $inst) {

                $assigned = $inst->amount;
                $totalFeesAssigned += $assigned;

                // Total CREDIT (payment + concession)
                $paid = StudentFeeLedger::where('student_fee_installment_id', $inst->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                // Concession only (no payment_id)
                $concession = StudentFeeLedger::where('student_fee_installment_id', $inst->id)
                    ->where('type', 'CREDIT')
                    ->whereNull('payment_id')
                    ->sum('amount');

                // Actual paid by student
                $actualPaid = $paid - $concession;

                $pending = max($assigned - $paid, 0);

                /**
                 * 🔥 Overdue logic (matches UI)
                 */
                if ($pending > 0) {
                    if (
                        in_array($inst->assign_type, ['BAD', 'DAYS_AFTER_BAD']) ||
                        ($inst->assign_type === 'TRIGGER' && $inst->offset > 0)
                    ) {
                        $overdueFees += $pending;
                    }
                }

                $installments[] = [
                    'student_fee_id' => $fee->id,
                    'installment_id' => $inst->id,

                    'fee_structure' => $fee->structure->name,
                    'fee_type' => $inst->feesType->name,

                    'assign_type' => $inst->assign_type,
                    'offset' => $inst->offset,

                    'assigned_amount' => $assigned,

                    // existing key (DO NOT CHANGE)
                    'paid_amount' => $paid,

                    // ✅ new keys
                    'concession_amount' => $concession,
                    'paid_amount_excluding_concession' => $actualPaid,

                    'pending_amount' => $pending,
                    'status' => $pending <= 0 ? 'PAID' : 'PENDING',

                    'is_extra' => (bool) $inst->is_extra,
                ];
            }
        }

        /**
         * 5️⃣ Totals (ledger-based adjustments)
         */
        $totalConcession = StudentFeeLedger::where('student_id', $student->id)
            ->where('type', 'CREDIT')
            ->whereNull('payment_id')
            ->sum('amount');

        $totalPaidExcludingConcession = StudentFeeLedger::where('student_id', $student->id)
            ->where('type', 'CREDIT')
            ->whereNotNull('payment_id')
            ->sum('amount');

        $totalPayable = max($totalFeesAssigned - $totalConcession, 0);
        $totalDues = max($totalPayable - $totalPaidExcludingConcession, 0);

        /**
         * 6️⃣ Final response (frontend safe)
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
                    // UI: Fees (F)
                    'total_assigned' => $totalFeesAssigned,

                    // UI: Concession (C)
                    'total_concession' => $totalConcession,

                    // UI: Amount Paid
                    'total_paid_excluding_concession' => $totalPaidExcludingConcession,

                    // existing key (keep)
                    'total_paid' => $totalPaidExcludingConcession + $totalConcession,

                    // UI: Total Dues
                    'total_pending' => $totalDues,

                    // UI: Overdue Fees
                    'overdue_fees' => $overdueFees,

                    // UI: Tax (future)
                    'tax' => 0,
                ],
            ],
        ]);
    }


    // refundSummary to get all refudnded amount for a student
   public function refundSummary($studentId)
    {
        $refunds = StudentFeeRefund::with('reasonMaster')
            ->where('student_id', $studentId)
            ->orderByDesc('refund_date')
            ->get();

        $refundList = $refunds->map(function ($r) {

            return [
                'refund_id' => $r->id,

                'payment_id' => $r->payment_id,
                'student_fee_id' => $r->student_fee_id,
                'student_fee_installment_id' => $r->student_fee_installment_id,

                'refund_amount' => (float) $r->refund_amount,
                'refund_date' => $r->refund_date,

                'payment_mode' => $r->payment_mode,
                'reference_no' => $r->reference_no,

                // ✅ HERE IS THE FIX
                'reason_id' => $r->reason,
                'reason' => $r->reasonMaster?->reason, // 👈 text from table

                'download_receipt' => (bool) $r->download_receipt,

                'notify' => [
                    'email' => [
                        'parents' => (bool) $r->notify_email_parents,
                        'students' => (bool) $r->notify_email_students,
                    ],
                    'sms' => [
                        'parents' => (bool) $r->notify_sms_parents,
                        'students' => (bool) $r->notify_sms_students,
                    ],
                ],

                'created_at' => $r->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_refunded_amount' => (float) $refunds->sum('refund_amount'),
                'refunds' => $refundList,
            ],
        ]);
    }


    public function concessionSummary($studentId)
    {
        // 1️⃣ Fetch all concessions with reason text
        $concessions = StudentApproveConcession::with([

                'installment',
            ])
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        // 2️⃣ Prepare list
        $concessionList = $concessions->map(function ($c) {
            return [
                'concession_id' => $c->id,

                'student_fee_id' => $c->student_fee_id,
                'student_fee_installment_id' => $c->student_fee_installment_id,

                'installment_name' => $c->installment?->feesType?->name,
                'installment_amount' => $c->installment?->amount,

                'concession_amount' => (float) $c->amount,

                'remarks' => $c->remarks,

                'created_at' => $c->created_at,
            ];
        });

        // 3️⃣ Total concession amount
        $totalConcession = $concessions->sum('amount');

        // 4️⃣ Response
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_concession_amount' => (float) $totalConcession,
                'concessions' => $concessionList,
            ],
        ]);
    }




}
