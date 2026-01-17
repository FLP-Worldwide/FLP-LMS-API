<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeeFine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FineManagementController extends Controller
{
    /**
     * 📌 LIST FINES
     * GET /fees/fine/manage
     */
    public function index()
    {
        $data = FeeFine::with('feeTypes')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * 📌 CREATE FINE
     * POST /fees/fine/manage
     */
    public function store(Request $request)
    {
        $request->validate([
            'fine_name' => 'required|string|max:255',
            'fine_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'fees_type_ids' => 'required|array|min:1',
            'fees_type_ids.*' => 'exists:fees_types,id',
        ]);

        DB::beginTransaction();

        try {
            $fine = FeeFine::create([
                'fine_name' => $request->fine_name,
                'fine_type' => $request->fine_type,
                'amount' => $request->amount,

            ]);

            $fine->feeTypes()->sync($request->fees_type_ids);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fine created successfully',
                'data' => $fine->load('feeTypes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📌 SHOW SINGLE FINE
     * GET /fees/fine/manage/{id}
     */
    public function show($id)
    {
        $fine = FeeFine::with('feeTypes')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $fine
        ]);
    }

    /**
     * 📌 UPDATE FINE
     * PUT /fees/fine/manage/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'fine_name' => 'required|string|max:255',
            'fine_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'fees_type_ids' => 'required|array|min:1',
            'fees_type_ids.*' => 'exists:fees_types,id',
        ]);

        DB::beginTransaction();

        try {
            $fine = FeeFine::findOrFail($id);

            $fine->update([
                'fine_name' => $request->fine_name,
                'fine_type' => $request->fine_type,
                'amount' => $request->amount,
            ]);

            $fine->feeTypes()->sync($request->fees_type_ids);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fine updated successfully',
                'data' => $fine->load('feeTypes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📌 DELETE FINE (SOFT DELETE)
     * DELETE /fees/fine/manage/{id}
     */
    public function destroy($id)
    {
        $fine = FeeFine::findOrFail($id);
        $fine->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Fine deleted successfully'
        ]);
    }
}
