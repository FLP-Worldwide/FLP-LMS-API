<?php
namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeesStructure;
use Illuminate\Http\Request;

class FeesStructureController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'fees_type_id' => 'required|exists:fees_types,id',
            'class_id' => 'required|exists:class_rooms,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $structure = FeesStructure::updateOrCreate(
            [
                'fees_type_id' => $request->fees_type_id,
                'class_id' => $request->class_id,
            ],
            [
                'amount' => $request->amount,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Fees structure saved',
            'data' => $structure
        ]);
    }

    public function byFeesType($feesTypeId)
    {
        $data = FeesStructure::with('class')
            ->where('fees_type_id', $feesTypeId)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * ✅ NEW API
     * Get all fees structures under a class
     * Also include installments if assigned
     */
    public function byClass(Request $request, $classId)
    {
        $structures = FeesStructure::with([
                'installments.feesType',   // fees_structure_installments
                'batches',          // fees_structure_batches
                'feesType',         // fees_types
            ])
            ->where('class_id', $classId)
            ->get();

        return response()->json([
            'status' => 'success',
            'class_id' => $classId,
            'count' => $structures->count(),
            'data' => $structures
        ]);
    }
}
