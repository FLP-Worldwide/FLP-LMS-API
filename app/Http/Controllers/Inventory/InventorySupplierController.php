<?php
namespace App\Http\Controllers\Inventory;

use App\Exports\InventorySupplierExport;
use App\Http\Controllers\Controller;
use App\Models\InventorySupplier;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventorySupplierController extends Controller
{
    // LIST
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => InventorySupplier::latest()->get(),
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company'  => 'required|string|max:150',
            'supplier' => 'required|string|max:150',
            'email'         => 'required|email|max:150',
            'mobile'        => 'required|string|max:20',
            'address'       => 'nullable|string',
        ]);

        $supplier = InventorySupplier::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier added successfully.',
            'data'    => $supplier,
        ], 201);
    }

    // SHOW
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => InventorySupplier::findOrFail($id),
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $supplier = InventorySupplier::findOrFail($id);

        $validated = $request->validate([
            'company'  => 'required|string|max:150',
            'supplier' => 'required|string|max:150',
            'email'         => 'required|email|max:150',
            'mobile'        => 'required|string|max:20',
            'address'       => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier updated successfully.',
            'data'    => $supplier,
        ]);
    }

    // DELETE (SOFT)
    public function destroy($id)
    {
        InventorySupplier::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier deleted successfully.',
        ]);
    }


    public function export()
    {
        return Excel::download(
            new InventorySupplierExport(),
            'suppliers_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }
}
