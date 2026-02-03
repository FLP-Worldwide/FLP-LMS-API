<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\StudentApproveConcession;
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
            /**
             * 1️⃣ Lock payment row
             */
            $payment = StudentFeePayment::lockForUpdate()->findOrFail($paymentId);

            if ($payment->status !== 'PENDING') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending payments can be approved',
                ], 422);
            }

            /**
             * 2️⃣ Validate allocation total
             */
            $totalAllocated = collect($request->allocations)->sum('amount');

            if ((float) $totalAllocated !== (float) $payment->amount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Allocated amount does not match payment amount',
                ], 422);
            }

            /**
             * 3️⃣ Process each installment allocation
             */
            foreach ($request->allocations as $allocation) {

                $installment = StudentFeeInstallment::with('studentFee')
                    ->lockForUpdate()
                    ->findOrFail($allocation['student_fee_installment_id']);

                /**
                 * 🔒 Prevent over-payment of installment
                 */
                $alreadyPaid = StudentFeeLedger::where('student_fee_installment_id', $installment->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                $remaining = $installment->amount - $alreadyPaid;

                if ($allocation['amount'] > $remaining) {
                    throw new \Exception(
                        "Payment exceeds pending balance for installment ID {$installment->id}"
                    );
                }

                /**
                 * 4️⃣ Create ledger CREDIT (INSTALLMENT LINKED 🔥)
                 */
                StudentFeeLedger::create([
                    'student_id'                 => $payment->student_id,
                    'student_fee_id'             => $installment->student_fee_id,
                    'student_fee_installment_id' => $installment->id,
                    'payment_id'                 => $payment->id,

                    'type'        => 'CREDIT',
                    'amount'      => $allocation['amount'],
                    'description' => $request->remarks ?? 'Fee payment approved',
                ]);
            }

            /**
             * 5️⃣ Approve payment
             */
            $payment->update([
                'status'       => 'APPROVED',
                'remarks'      => $request->remarks,
                'approved_at'  => now(),
            ]);

            /**
             * 6️⃣ Optional async actions
             */
            if ($request->boolean('generate_receipt')) {
                // dispatch(new GenerateFeeReceiptJob($payment->id));
            }

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


    // addConcession
    public function addConcession(Request $request)
    {
        $request->validate([
            'concessions' => 'required|array|min:1',
            'concessions.*.student_fee_installment_id' => 'required|exists:student_fee_installments,id',
            'concessions.*.amount' => 'required|numeric|min:1',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            foreach ($request->concessions as $item) {

                $installment = StudentFeeInstallment::with('studentFee')
                    ->lockForUpdate()
                    ->findOrFail($item['student_fee_installment_id']);

                /**
                 * 1️⃣ Calculate already adjusted amount
                 */
                $paidAmount = StudentFeeLedger::where('student_fee_installment_id', $installment->id)
                    ->where('type', 'CREDIT')
                    ->sum('amount');

                $remaining = $installment->amount - $paidAmount;

                if ($item['amount'] > $remaining) {
                    throw new \Exception(
                        "Concession exceeds pending balance for installment ID {$installment->id}"
                    );
                }

                /**
                 * 2️⃣ Save concession record
                 */
                StudentApproveConcession::create([
                    'student_id'                 => $installment->studentFee->student_id,
                    'student_fee_id'             => $installment->student_fee_id,
                    'student_fee_installment_id' => $installment->id,
                    'amount'                     => $item['amount'],
                    'remarks'                    => $request->remarks,
                ]);

                /**
                 * 3️⃣ Ledger entry (CREDIT as CONCESSION)
                 */
                StudentFeeLedger::create([
                    'student_id'                 => $installment->studentFee->student_id,
                    'student_fee_id'             => $installment->student_fee_id,
                    'student_fee_installment_id' => $installment->id,
                    'payment_id'                 => null,

                    'type'        => 'CREDIT',
                    'amount'      => $item['amount'],
                    'description' => 'Fee concession applied',
                    'is_extra'    => 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Concession applied successfully',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to apply concession',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


}
