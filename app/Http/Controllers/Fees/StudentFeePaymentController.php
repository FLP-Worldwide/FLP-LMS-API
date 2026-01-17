<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\StudentFee;
use App\Models\StudentFeeLedger;
use App\Models\StudentFeePayment;
use Illuminate\Http\Request;

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
         * 1️⃣ Load payment with student
         */
        $payment = StudentFeePayment::with([
                'student:id,first_name,last_name,class,section,status',
            ])
            ->findOrFail($id);

        /**
         * 2️⃣ Load student fees with installments
         */
        $studentFees = StudentFee::with([
                'installments.feesType',
            ])
            ->where('student_id', $payment->student_id)
            ->get();

        /**
         * 3️⃣ Build installment-wise balance
         */
        $installments = [];

        foreach ($studentFees as $fee) {
            foreach ($fee->installments as $inst) {

                $paid = StudentFeeLedger::where('student_fee_installment_id', $inst->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                $installments[] = [
                    'student_fee_id' => $fee->id,
                    'student_fee_installment_id' => $inst->id,

                    'installment_name' => $fee->structure->name ?? 'N/A',

                    'fee_type' => $inst->feesType->name ?? 'N/A',
                    'assign_type' => $inst->assign_type,
                    'offset' => $inst->offset,

                    'assigned_amount' => $inst->amount,
                    'paid_amount' => $paid,
                    'pending_amount' => max($inst->amount - $paid, 0),

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

}
