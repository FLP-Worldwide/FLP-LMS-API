<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use Illuminate\Http\Request;


class AssetAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    $assignments = AssetAssignment::with([
            'asset',
            'category',
            'checkoutBy'
        ])
        ->when($request->role, fn ($q) =>
            $q->where('role', $request->role)
        )
        ->when($request->asset_id, fn ($q) =>
            $q->where('asset_id', $request->asset_id)
        )
        ->latest()
        ->get();

    return response()->json([
        'status' => 'success',
        'data'   => $assignments
    ]);
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
public function store(Request $request)
{
    $validated = $request->validate([
        'asset_category_id' => 'required|exists:asset_categories,id',
        'asset_id'          => 'required|exists:assets,id',
        'role'              => 'required|string',
        'checkout_by'       => 'nullable|exists:teachers,id',
        'quantity'          => 'required|integer|min:1',
        'assign_date'       => 'required|date',
        'due_date'          => 'nullable|date',
        'return_date'       => 'nullable|date',
        'note'              => 'nullable|string',
    ]);

    $assignment = AssetAssignment::create($validated);

    return response()->json([
        'status'  => 'success',
        'message' => 'Asset assigned successfully',
        'data'    => $assignment
    ], 201);
}


    /**
     * Display the specified resource.
     */
public function show($id)
{
    $assignment = AssetAssignment::with([
        'asset',
        'category',
        'checkoutBy'
    ])->findOrFail($id);

    return response()->json([
        'status' => 'success',
        'data'   => $assignment
    ]);
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AssetAssignmentController $assetAssignmentController)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
public function update(Request $request, $id)
{
    $assignment = AssetAssignment::findOrFail($id);

    $validated = $request->validate([
        'asset_category_id' => 'required|exists:asset_categories,id',
        'asset_id'          => 'required|exists:assets,id',
        'role'              => 'required|string',
        'checkout_by'       => 'nullable|exists:teachers,id',
        'quantity'          => 'required|integer|min:1',
        'assign_date'       => 'required|date',
        'due_date'          => 'nullable|date',
        'return_date'       => 'nullable|date',
        'note'              => 'nullable|string',
    ]);

    $assignment->update($validated);

    return response()->json([
        'status'  => 'success',
        'message' => 'Assignment updated successfully',
        'data'    => $assignment
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
public function destroy($id)
{
    AssetAssignment::findOrFail($id)->delete();

    return response()->json([
        'status'  => 'success',
        'message' => 'Assignment deleted successfully'
    ]);
}

}
