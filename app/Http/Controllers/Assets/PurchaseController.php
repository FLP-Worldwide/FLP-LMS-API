<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * LIST PURCHASES
     */
  public function index(Request $request)
{
    $purchases = Purchase::with([
            'supplier',
            'items.asset',
            'items.category'
        ])
        ->when($request->supplier_id, fn ($q) =>
            $q->where('supplier_id', $request->supplier_id)
        )
        ->when($request->from_date, fn ($q) =>
            $q->whereDate('purchase_date', '>=', $request->from_date)
        )
        ->when($request->to_date, fn ($q) =>
            $q->whereDate('purchase_date', '<=', $request->to_date)
        )
        ->latest()
        ->get();

    return response()->json([
        'status' => 'success',
        'data'   => $purchases
    ]);
}

    /**
     * SHOW SINGLE PURCHASE
     */
    public function show($id)
    {
        $purchase = Purchase::with([
            'supplier',
            'items.asset',
            'items.category'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $purchase
        ]);
    }

    /**
     * STORE PURCHASE
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'invoice_no'    => 'nullable|string',
            'remarks'       => 'nullable|string',

            // OPTIONAL
            'service_date' => 'nullable|date',
            'expiry_date'  => 'nullable|date',
            'unit'         => 'nullable|string',
            'purchased_by' => 'nullable|string',
            'file'         => 'nullable|file|max:2048',

            'items'                     => 'required|array|min:1',
            'items.*.asset_id'          => 'required|exists:assets,id',
            'items.*.asset_category_id' => 'required|exists:asset_categories,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.price'             => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $supplier = Supplier::with('assetItems')->findOrFail($validated['supplier_id']);
            $supplierAssetIds = $supplier->assetItems->pluck('id')->toArray();

            // FILE UPLOAD
            $filePath = null;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('purchase_files', 'public');
            }

            $purchase = Purchase::create([

                'supplier_id'   => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'invoice_no'    => $validated['invoice_no'] ?? null,
                'remarks'       => $validated['remarks'] ?? null,

                // OPTIONAL
                'service_date'  => $validated['service_date'] ?? null,
                'expiry_date'   => $validated['expiry_date'] ?? null,
                'unit'          => $validated['unit'] ?? null,
                'purchased_by'  => $validated['purchased_by'] ?? null,
                'file_path'     => $filePath,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                if (!in_array($item['asset_id'], $supplierAssetIds)) {
                    throw new \Exception('Asset not assigned to selected supplier');
                }

                $lineTotal = $item['quantity'] * $item['price'];
                $totalAmount += $lineTotal;

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'asset_category_id' => $item['asset_category_id'],
                    'asset_id'          => $item['asset_id'],
                    'quantity'          => $item['quantity'],
                    'price'             => $item['price'],
                    'total'             => $lineTotal,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Purchase stored successfully',
                'data'    => $purchase->load(['supplier', 'items.asset'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * UPDATE PURCHASE
     */
    public function update(Request $request, $id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'invoice_no'    => 'nullable|string',
            'remarks'       => 'nullable|string',

            // OPTIONAL
            'service_date' => 'nullable|date',
            'expiry_date'  => 'nullable|date',
            'unit'         => 'nullable|string',
            'purchased_by' => 'nullable|string',
            'file'         => 'nullable|file|max:2048',

            'items'                     => 'required|array|min:1',
            'items.*.asset_id'          => 'required|exists:assets,id',
            'items.*.asset_category_id' => 'required|exists:asset_categories,id',
            'items.*.quantity'          => 'required|integer|min:1',
            'items.*.price'             => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $supplier = Supplier::with('assetItems')->findOrFail($purchase->supplier_id);
            $supplierAssetIds = $supplier->assetItems->pluck('id')->toArray();

            // FILE UPDATE (optional replace)
            $filePath = $purchase->file_path;
            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('purchase_files', 'public');
            }

            // UPDATE MASTER
            $purchase->update([
                'purchase_date' => $validated['purchase_date'],
                'invoice_no'    => $validated['invoice_no'] ?? null,
                'remarks'       => $validated['remarks'] ?? null,

                'service_date'  => $validated['service_date'] ?? null,
                'expiry_date'   => $validated['expiry_date'] ?? null,
                'unit'          => $validated['unit'] ?? null,
                'purchased_by'  => $validated['purchased_by'] ?? null,
                'file_path'     => $filePath,
            ]);

            // RESET ITEMS
            PurchaseItem::where('purchase_id', $purchase->id)->delete();

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                if (!in_array($item['asset_id'], $supplierAssetIds)) {
                    throw new \Exception('Asset not assigned to selected supplier');
                }

                $lineTotal = $item['quantity'] * $item['price'];
                $totalAmount += $lineTotal;

                PurchaseItem::create([
                    'purchase_id'       => $purchase->id,
                    'asset_category_id' => $item['asset_category_id'],
                    'asset_id'          => $item['asset_id'],
                    'quantity'          => $item['quantity'],
                    'price'             => $item['price'],
                    'total'             => $lineTotal,
                ]);
            }

            $purchase->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Purchase updated successfully',
                'data'    => $purchase->load(['supplier', 'items.asset'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * DELETE PURCHASE
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            PurchaseItem::where('purchase_id', $id)->delete();
            Purchase::findOrFail($id)->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Purchase deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete purchase'
            ], 422);
        }
    }
}
