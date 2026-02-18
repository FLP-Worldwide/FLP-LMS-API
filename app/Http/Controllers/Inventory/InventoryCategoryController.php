<?php
namespace App\Http\Controllers\Inventory;

use App\Exports\InventoryCategoryExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryCategoryController extends Controller
{
    // 📄 LIST
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryCategory::latest()->get(),
        ]);
    }

    // ➕ CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string',
            'description'   => 'nullable|string|max:255',
        ]);

        $category = InventoryCategory::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory category created successfully.',
            'data'    => $category,
        ], 201);
    }

    // 👁️ SHOW
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryCategory::findOrFail($id),
        ]);
    }

    // ✏️ UPDATE
    public function update(Request $request, $id)
    {
        $category = InventoryCategory::findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'required|string',
            'description'   => 'nullable|string|max:255',
        ]);

        $category->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory category updated successfully.',
            'data'    => $category,
        ]);
    }

    // 🗑️ DELETE (SOFT)
    public function destroy($id)
    {
        InventoryCategory::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory category deleted successfully.',
        ]);
    }


    public function export()
    {
        return Excel::download(
            new InventoryCategoryExport(),
            'inventory_categories_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }



}
