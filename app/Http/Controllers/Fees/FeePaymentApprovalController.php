<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\StudentFeePayment;
use App\Models\StudentFeeInstallment;
use App\Models\StudentFeeLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeePaymentApprovalController extends Controller
{
    public function approve(Request $request, $paymentId)
    {
        $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.student_fee_installment_id' => 'required|exists:student_fee_installments,id',
            'allocations.*.amount' => 'required|numeric|min:1',
            'remarks' => 'nullable|string',
            'generate_receipt' => 'boolean',
            'send_email' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            /** @var StudentFeePayment $payment */
            $payment = StudentFeePayment::lockForUpdate()->findOrFail($paymentId);

            if ($payment->status !== 'PENDING') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending payments can be approved',
                ], 422);
            }

            $totalAllocated = collect($request->allocations)->sum('amount');

            if ((float) $totalAllocated !== (float) $payment->amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Allocated amount does not match payment amount',
                ], 422);
            }

            foreach ($request->allocations as $allocation) {

                /** @var StudentFeeInstallment $installment */
                $installment = StudentFeeInstallment::with('studentFee')
                    ->lockForUpdate()
                    ->findOrFail($allocation['student_fee_installment_id']);

                StudentFeeLedger::create([
                    'student_id'     => $payment->student_id,
                    'student_fee_id' => $installment->student_fee_id,
                    'payment_id'     => $payment->id,
                    'type'           => 'CREDIT',
                    'amount'         => $allocation['amount'],
                    'description'    => $request->remarks ?? 'Fee payment approved',
                ]);
            }

            $payment->update([
                'status' => 'APPROVED',
                'remarks' => $request->remarks,
                'approved_at' => now(),
            ]);

            // OPTIONAL: Receipt generation
            if ($request->boolean('generate_receipt')) {
                // dispatch(new GenerateFeeReceiptJob($payment->id));
            }

            // OPTIONAL: Email
            if ($request->boolean('send_email')) {
                // dispatch(new SendFeeReceiptMailJob($payment->id));
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment approved successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Payment approval failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
