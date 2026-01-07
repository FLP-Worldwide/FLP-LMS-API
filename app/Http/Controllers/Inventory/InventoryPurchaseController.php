<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventoryPurchase;
use App\Models\InventoryPurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryPurchaseController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryPurchase::with('supplier')
                ->latest()
                ->get(),
        ]);
    }
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => InventoryPurchase::with([
                'supplier',
                'items.item',
                'items.category'
            ])->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchase = InventoryPurchase::with('items')->findOrFail($id);

        $validated = $request->validate([
            'supplier_id' => 'required|exists:inventory_suppliers,id',
            'date' => 'required|date',
            'reference_no' => 'required|string|max:150',
            'description' => 'nullable|string',
            'bill_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.inventory_category_id' => 'required|exists:inventory_categories,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.units' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            /* 🔄 REVERSE OLD STOCK */
            foreach ($purchase->items as $oldItem) {
                InventoryItem::where('id', $oldItem->inventory_item_id)
                    ->decrement('quantity', $oldItem->units);
            }

            /* 🗑️ DELETE OLD ITEMS */
            InventoryPurchaseItem::where('inventory_purchase_id', $purchase->id)->delete();

            /* 📂 UPDATE BILL */
            if ($request->hasFile('bill_copy')) {
                Storage::disk('public')->delete($purchase->bill_copy);

                $purchase->bill_copy = $request->file('bill_copy')
                    ->store('inventory/bills', 'public');
            }

            $purchase->update([
                'supplier_id' => $validated['supplier_id'],
                'date' => $validated['date'],
                'reference_no' => $validated['reference_no'],
                'description' => $validated['description'] ?? null,
            ]);

            $total = 0;

            foreach ($validated['items'] as $row) {
                $subTotal = $row['unit_price'] * $row['units'];

                InventoryPurchaseItem::create([
                    'inventory_purchase_id' => $purchase->id,
                    'inventory_category_id' => $row['inventory_category_id'],
                    'inventory_item_id' => $row['inventory_item_id'],
                    'unit_price' => $row['unit_price'],
                    'units' => $row['units'],
                    'sub_total' => $subTotal,
                ]);

                InventoryItem::where('id', $row['inventory_item_id'])
                    ->increment('quantity', $row['units']);

                $total += $subTotal;
            }

            $purchase->update(['total_amount' => $total]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase updated successfully.',
                'data' => $purchase->load('items'),
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
       🗑️ DELETE – Soft Delete + Reverse Stock
    ========================================================= */
    public function destroy($id)
    {
        $purchase = InventoryPurchase::with('items')->findOrFail($id);

        DB::beginTransaction();

        try {
            foreach ($purchase->items as $item) {
                InventoryItem::where('id', $item->inventory_item_id)
                    ->decrement('quantity', $item->units);
            }

            $purchase->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // ➕ CREATE PURCHASE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'               => 'required|exists:inventory_suppliers,id',
            'date'                      => 'required|date',
            'reference_no'              => 'required|string|max:150',
            'description'               => 'nullable|string',
            'bill_copy'                 => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',

            'items'                     => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.inventory_category_id' => 'required|exists:inventory_categories,id',
            'items.*.unit_price'        => 'required|numeric|min:0',
            'items.*.units'             => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            /* 📂 Upload Bill */
            $billPath = $request->file('bill_copy')->store('inventory/bills', 'public');

            /* 🧾 Purchase Master */
            $purchase = InventoryPurchase::create([
                'supplier_id'  => $validated['supplier_id'],
                'date'         => $validated['date'],
                'reference_no' => $validated['reference_no'],
                'description'  => $validated['description'] ?? null,
                'bill_copy'    => $billPath,
                'total_amount' => 0,
            ]);

            $total = 0;

            /* 📦 Purchase Items */
            foreach ($validated['items'] as $row) {
                $subTotal = $row['unit_price'] * $row['units'];

                InventoryPurchaseItem::create([
                    'inventory_purchase_id' => $purchase->id,
                    'inventory_category_id' => $row['inventory_category_id'],
                    'inventory_item_id'     => $row['inventory_item_id'],
                    'unit_price'            => $row['unit_price'],
                    'units'                 => $row['units'],
                    'sub_total'             => $subTotal,
                ]);

                /* 🔄 UPDATE INVENTORY ITEM QTY */
                InventoryItem::where('id', $row['inventory_item_id'])
                    ->increment('quantity', $row['units']);

                $total += $subTotal;
            }

            /* 💰 Update Total */
            $purchase->update(['total_amount' => $total]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Purchase added successfully',
                'data'    => $purchase->load('items'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
