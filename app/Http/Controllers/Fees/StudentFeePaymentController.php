<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\StudentFee;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentFeePaymentController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:PENDING,APPROVED,REJECTED,ALL',
            'student_id' => 'nullable|exists:students,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $payments = StudentFeePayment::with([
                'student:id,first_name,last_name,class,section,status',
            ])
            ->when(
                $request->filled('status') && $request->status !== 'ALL',
                fn ($q) => $q->where('status', $request->status),
                fn ($q) => $q->where('status', 'PENDING') // default
            )
            ->when($request->filled('student_id'), fn ($q) =>
                $q->where('student_id', $request->student_id)
            )
            ->when($request->filled('from_date'), fn ($q) =>
                $q->whereDate('payment_date', '>=', $request->from_date)
            )
            ->when($request->filled('to_date'), fn ($q) =>
                $q->whereDate('payment_date', '<=', $request->to_date)
            )
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'filters' => [
                'status' => $request->status ?? 'PENDING',
                'student_id' => $request->student_id,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
            ],
            'count' => $payments->count(),
            'data' => $payments,
        ]);
    }

    public function studentList($id)
    {
        $payment = StudentFeePayment::with([ 'student:id,first_name,last_name,class,section,status', ])
         ->where('student_id', $id)->get();
         return response()->json([ 'status' => 'success', 'data' => $payment, ]);
    }

    public function show($id)
    {
        /**
         * 1️⃣ Load payment + student
         */
        $payment = StudentFeePayment::with([
                'student:id,first_name,last_name,class,section,status',
            ])
            ->findOrFail($id);

        /**
         * 2️⃣ Load student fees + installments
         */
        $studentFees = StudentFee::with([
                'installments.feesType',
                'structure',
            ])
            ->where('student_id', $payment->student_id)
            ->get();

        $installments = [];

        /**
         * 3️⃣ Build ONLY pending installments
         */
        foreach ($studentFees as $fee) {
            foreach ($fee->installments as $inst) {

                $paid = StudentFeeLedger::where('student_fee_installment_id', $inst->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                $pending = max($inst->amount - $paid, 0);

                // 🔥 Hide fully paid installments
                if ($pending <= 0) {
                    continue;
                }

                $installments[] = [
                    'student_fee_id' => $fee->id,
                    'student_fee_installment_id' => $inst->id,

                    'installment_name' => $fee->structure->name ?? 'N/A',
                    'fee_type' => $inst->feesType->name ?? 'N/A',

                    'assign_type' => $inst->assign_type,
                    'offset' => $inst->offset,

                    'assigned_amount' => $inst->amount,
                    'paid_amount' => $paid,
                    'pending_amount' => $pending,

                    'is_extra' => $inst->is_extra,
                ];
            }
        }

    return response()->json([
        'status' => 'success',
        'data' => [
            'payment' => $payment,
            'installments' => $installments,
        ],
    ]);
}




    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',

            'payment_mode' => 'required|in:CASH,UPI,BANK_TRANSFER,CHEQUE',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',

            // 🔹 Conditional fields (based on mode)
            'bank_name' => 'required_if:payment_mode,CHEQUE,BANK_TRANSFER',
            'account_number' => 'required_if:payment_mode,BANK_TRANSFER',
            'transaction_reference' => 'required_if:payment_mode,UPI,BANK_TRANSFER,CHEQUE',

            'country' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            $payment = StudentFeePayment::create([
                'student_id' => $request->student_id,

                'payment_mode' => $request->payment_mode,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,

                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'transaction_reference' => $request->transaction_reference,
                'country' => $request->country,
                'remarks' => $request->remarks,

                // 🔴 IMPORTANT: no ledger adjustment here
                'status' => 'PENDING',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment recorded successfully (Pending approval)',
                'data' => $payment,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



    public function studentPayments($studentId)
    {
        $payments = StudentFeePayment::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {

                $refundedAmount = StudentFeeLedger::where('payment_id', $p->id)
                    ->where('type', 'DEBIT')
                    ->sum('amount');

                // refund may NOT exist
                $refund = StudentFeeRefund::where('payment_id', $p->id)->first();

                return [
                    'id' => $p->id,
                    'payment_mode' => $p->payment_mode,
                    'amount' => (float) $p->amount,

                    'refunded_amount' => (float) $refundedAmount,

                    // ✅ null-safe access
                    'reference_number' => $refund?->reference_no,

                    'net_amount' => max($p->amount - $refundedAmount, 0),

                    'payment_date' => $p->payment_date,
                    'status' => $p->status,
                    'remarks' => $p->remarks,
                    'created_at' => $p->created_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $payments,
        ]);
    }




    public function refund(Request $request, $paymentId)
    {
        $request->validate([
            'student_fee_installment_id' => 'nullable|exists:student_fee_installments,id',
            'refund_amount' => 'required|numeric|min:1',
            'refund_date' => 'required|date',
            'payment_mode' => 'required|string',
            // 'reason' => 'required|string',
            'reference_no' => 'nullable|string',

            'download_receipt' => 'boolean',

            'notify.email.parents' => 'boolean',
            'notify.email.students' => 'boolean',
            'notify.sms.parents' => 'boolean',
            'notify.sms.students' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $payment = StudentFeePayment::lockForUpdate()->findOrFail($paymentId);

            if ($payment->status !== 'APPROVED') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only approved payments can be refunded',
                ], 422);
            }

            // 🔒 Prevent over-refund
            $alreadyRefunded = StudentFeeLedger::where('payment_id', $payment->id)
                ->where('type', 'DEBIT')
                ->sum('amount');

            $availableRefund = $payment->amount - $alreadyRefunded;

            if ($request->refund_amount > $availableRefund) {
                throw new \Exception('Refund amount exceeds available paid amount');
            }

            /**
             * 1️⃣ Save refund record
             */
            $refund = StudentFeeRefund::create([
                'student_id' => $payment->student_id,
                'payment_id' => $payment->id,
                'student_fee_installment_id' => $request->student_fee_installment_id,

                'refund_amount' => $request->refund_amount,
                'refund_date' => $request->refund_date,
                'payment_mode' => $request->payment_mode,
                'reference_no' => $request->reference_no,

                'reason' => $request->refund_reason_id ?? null,
                'remarks' => $request->reason,

                'download_receipt' => $request->boolean('download_receipt'),

                'notify_email_parents' => data_get($request, 'notify.email.parents', false),
                'notify_email_students' => data_get($request, 'notify.email.students', false),
                'notify_sms_parents' => data_get($request, 'notify.sms.parents', false),
                'notify_sms_students' => data_get($request, 'notify.sms.students', false),
            ]);

            /**
             * 2️⃣ Ledger entry (REFUND = DEBIT)
             */
            $studentFeeId = StudentFeeLedger::where('payment_id', $payment->id)
                ->where('type', 'CREDIT')
                ->value('student_fee_id');

            if (!$studentFeeId) {
                throw new \Exception('Unable to determine student_fee_id for refund');
            }

            StudentFeeLedger::create([
                'student_id' => $payment->student_id,
                'student_fee_id' => $studentFeeId,
                'student_fee_installment_id' => $request->student_fee_installment_id,
                'payment_id' => $payment->id,

                'type' => 'DEBIT',
                'amount' => $request->refund_amount,
                'description' => 'Payment refund: ' . $request->reason,
            ]);

            /**
             * 3️⃣ Update payment status
             */
            if ($request->refund_amount == $availableRefund) {
                $payment->update(['status' => 'REFUNDED']);
            }

            DB::commit();

            /**
             * 4️⃣ Optional async actions
             */
            if ($refund->download_receipt) {
                // dispatch(new GenerateRefundReceiptJob($refund->id));
            }

            if ($refund->notify_email_parents || $refund->notify_email_students) {
                // dispatch(new SendRefundEmailJob($refund->id));
            }

            if ($refund->notify_sms_parents || $refund->notify_sms_students) {
                // dispatch(new SendRefundSmsJob($refund->id));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Refund processed successfully',
                'refund_id' => $refund->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Refund failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}
