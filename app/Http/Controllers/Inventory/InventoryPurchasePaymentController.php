<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryPurchase;
use App\Models\InventoryPurchasePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryPurchasePaymentController extends Controller
{
    /* =========================================================
       ➕ ADD / UPDATE PAYMENT
    ========================================================= */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'inventory_purchase_id' => 'required|exists:inventory_purchases,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:cash,upi,bank,cheque,card',
            'reference_no' => 'nullable|string|max:150',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $purchase = InventoryPurchase::findOrFail(
                $validated['inventory_purchase_id']
            );


            $due = $purchase->total_amount - $purchase->paid_amount;

            if ($validated['amount'] > $due) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment amount exceeds due amount.',
                ], 422);
            }


            InventoryPurchasePayment::create($validated);

            /* 🔄 Update Purchase Totals */
            $purchase->increment('paid_amount', $validated['amount']);
            $purchase->update([
                'due_amount' => $purchase->total_amount - $purchase->paid_amount,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment added successfully.',
                'data' => $purchase->load('payments'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* =========================================================
       📃 LIST PAYMENTS BY PURCHASE
    ========================================================= */
    public function index($purchaseId)
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryPurchasePayment::where(
                'inventory_purchase_id',
                $purchaseId
            )->latest()->get(),
        ]);
    }

    /* =========================================================
       ✏️ UPDATE PAYMENT
    ========================================================= */
    public function update(Request $request, $id)
    {
        $payment = InventoryPurchasePayment::findOrFail($id);
        $purchase = $payment->purchase;

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:cash,upi,bank,cheque,card',
            'reference_no' => 'nullable|string|max:150',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            /* 🔄 Revert old payment */
            $purchase->decrement('paid_amount', $payment->amount);

            $newDue = $purchase->total_amount - $purchase->paid_amount;

            if ($validated['amount'] > $newDue) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Updated amount exceeds due.',
                ], 422);
            }

            /* ✏️ Update payment */
            $payment->update($validated);

            /* 🔄 Apply new payment */
            $purchase->increment('paid_amount', $validated['amount']);
            $purchase->update([
                'due_amount' => $purchase->total_amount - $purchase->paid_amount,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment updated successfully.',
                'data' => $purchase->load('payments'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* =========================================================
       🗑️ DELETE PAYMENT
    ========================================================= */
    public function destroy($id)
    {
        $payment = InventoryPurchasePayment::findOrFail($id);
        $purchase = $payment->purchase;

        DB::beginTransaction();

        try {
            $purchase->decrement('paid_amount', $payment->amount);
            $purchase->update([
                'due_amount' => $purchase->total_amount - $purchase->paid_amount,
            ]);

            $payment->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
