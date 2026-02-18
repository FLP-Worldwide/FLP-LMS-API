<?php
namespace App\Http\Controllers\Inventory;

use App\Exports\InventoryItemExport;
use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with('category');

        if ($request->category_id) {
            $query->where('inventory_category_id', $request->category_id);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    // ➕ CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name'              => 'required|string|max:150',
            'inventory_category_id'  => 'required|exists:inventory_categories,id',
            'buying_price'           => 'required|numeric|min:0',
            'sale_price'             => 'required|numeric|min:0',
            'tax_percentage'         => 'required|numeric|min:0|max:100',
            'low_stock_indicator'    => 'required|integer|min:1',
            'description'            => 'nullable|string',
        ]);

        $item = InventoryItem::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory item added successfully.',
            'data'    => $item->load('category'),
        ], 201);
    }

    // 👁️ SHOW
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryItem::with('category')->findOrFail($id),
        ]);
    }

    // ✏️ UPDATE
    public function update(Request $request, $id)
    {
        $item = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'item_name'              => 'required|string|max:150',
            'inventory_category_id'  => 'required|exists:inventory_categories,id',
            'buying_price'           => 'required|numeric|min:0',
            'sale_price'             => 'required|numeric|min:0',
            'tax_percentage'         => 'required|numeric|min:0|max:100',
            'low_stock_indicator'    => 'required|integer|min:1',
            'description'            => 'nullable|string',
            'is_active'              => 'boolean',
        ]);

        $item->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory item updated successfully.',
            'data'    => $item->load('category'),
        ]);
    }

    // 🗑️ DELETE (SOFT)
    public function destroy($id)
    {
        InventoryItem::findOrFail($id)->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Inventory item deleted successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $categoryId = $request->category_id ?? null;

        return Excel::download(
            new InventoryItemExport($categoryId),
            'inventory_items_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }
}
