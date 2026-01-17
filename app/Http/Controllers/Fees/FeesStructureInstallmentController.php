<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeesStructure;
use App\Models\FeesStructureInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeesStructureInstallmentController extends Controller
{

    public function index(Request $request)
    {
        $query = FeesStructure::with(['installments', 'batches'])
            ->latest();

        // 🔹 Filter by class
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        // 🔹 Filter by fees type
        if ($request->filled('fees_type_id')) {
            $query->where('fees_type_id', $request->fees_type_id);
        }

        $data = $query->get();
            return response()->json([
                'status' => 'success',
                'filters' => [
                    'class_id' => $request->class_id,
                    'fees_type_id' => $request->fees_type_id,
                ],
                'data' => $data
            ]);
    }

    /**
     * 📌 SHOW SINGLE FEES STRUCTURE
     */
    public function show($id)
    {
        $structure = FeesStructure::with(['installments', 'batches'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $structure
        ]);
    }

    /**
     * 📌 CREATE FEES STRUCTURE INSTALLMENTS
     */
    public function store(Request $request)
    {
        $request->validate([
            'fees_type_id' => 'required|exists:fees_types,id',
            'class_id' => 'required|integer',
            'fee_structure_name' => 'required|string|max:255',

            'batch_ids' => 'required|array|min:1',
            'batch_ids.*' => 'exists:batches,id',

            'total_amount' => 'required|numeric|min:0',

            'installments' => 'required|array|min:1',
            'installments.*.fee_type_id' => 'required|exists:fees_types,id',
            'installments.*.assign_type' => 'required|in:TRIGGER,BAD,DAYS_AFTER_BAD,MONTH_AFTER_BAD',
            'installments.*.offset' => 'required|integer|min:0',
            'installments.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            /**
             * 1️⃣ Create or Update Parent Fees Structure
             */
            $structure = FeesStructure::updateOrCreate(
                [
                    'fees_type_id' => $request->fees_type_id,
                    'class_id' => $request->class_id,
                ],
                [
                    'name' => $request->fee_structure_name, // ✅ GROUP NAME
                    'amount' => $request->total_amount,
                ]
            );

            /**
             * 2️⃣ Attach batches
             */
            $structure->batches()->sync($request->batch_ids);

            /**
             * 3️⃣ Replace installments
             */
            $structure->installments()->delete();

            foreach ($request->installments as $item) {
                FeesStructureInstallment::create([
                    'fees_structure_id' => $structure->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'assign_type' => $item['assign_type'],
                    'offset' => $item['offset'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fees installment structure saved successfully',
                'data' => $structure->load('installments', 'batches')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📌 UPDATE FEES STRUCTURE INSTALLMENTS
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'batch_ids' => 'required|array|min:1',
            'batch_ids.*' => 'exists:batches,id',
            'total_amount' => 'required|numeric|min:0',

            'installments' => 'required|array|min:1',
            'installments.*.fee_type_id' => 'required|exists:fees_types,id',
            'installments.*.assign_type' => 'required|in:TRIGGER,BAD,DAYS_AFTER_BAD,MONTH_AFTER_BAD',
            'installments.*.offset' => 'required|integer|min:0',
            'installments.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $structure = FeesStructure::findOrFail($id);

            // Update parent
            $structure->update([
                'amount' => $request->total_amount,
            ]);

            // Update batches
            $structure->batches()->sync($request->batch_ids);

            // Replace installments
            $structure->installments()->delete();

            foreach ($request->installments as $item) {
                FeesStructureInstallment::create([
                    'fees_structure_id' => $structure->id,
                    'fee_type_id' => $item['fee_type_id'],
                    'assign_type' => $item['assign_type'],
                    'offset' => $item['offset'],
                    'amount' => $item['amount'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Fees structure updated successfully',
                'data' => $structure->load('installments', 'batches')
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
     * 📌 DELETE FEES STRUCTURE (SOFT DELETE)
     */
    public function destroy($id)
    {
        $structure = FeesStructure::findOrFail($id);

        $structure->installments()->delete();
        $structure->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Fees structure deleted successfully'
        ]);
    }
}
