<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\RefundReason;
use Illuminate\Http\Request;

class RefundReasonController extends Controller
{
    /**
     * 📌 GET ALL REFUND REASONS
     * GET /fees/refund-reasons
     */
    public function index()
    {
        $data = RefundReason::latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * 📌 CREATE REFUND REASON
     * POST /fees/refund-reasons
     */
    public function store(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $reason = RefundReason::create([
            'reason' => $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Refund reason created successfully',
            'data' => $reason
        ]);
    }

    /**
     * 📌 UPDATE REFUND REASON
     * PUT /fees/refund-reasons/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $reason = RefundReason::findOrFail($id);

        $reason->update([
            'reason' => $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Refund reason updated successfully',
            'data' => $reason
        ]);
    }

    /**
     * 📌 DELETE REFUND REASON (OPTIONAL BUT RECOMMENDED)
     * DELETE /fees/refund-reasons/{id}
     */
    public function destroy($id)
    {
        $reason = RefundReason::findOrFail($id);
        $reason->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Refund reason deleted successfully'
        ]);
    }
}
