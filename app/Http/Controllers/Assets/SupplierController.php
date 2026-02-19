<?php

namespace App\Http\Controllers\Assets;

use App\Exports\SupplierExport;
use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Supplier;
use App\Models\SupplierAssetItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    /**
     * LIST SUPPLIERS
     */
    public function index(Request $request)
    {
        $suppliers = Supplier::with(['categories', 'assetItems'])

            ->when($request->search, function ($q) use ($request) {
                $q->where('company_name', 'like', "%{$request->search}%")
                  ->orWhere('mobile', 'like', "%{$request->search}%");
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $suppliers
        ]);
    }

    /**
     * STORE SUPPLIER
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'   => 'required|string|max:150',
            'email'          => 'nullable|email',
            'mobile'         => 'required|string|max:15',
            'contact_person' => 'required|string|max:100',
            'address'        => 'required|string',

            'category_ids'   => 'required|array|min:1',
            'category_ids.*' => 'exists:asset_categories,id',

            'asset_item_ids'   => 'nullable|array',
            'asset_item_ids.*' => 'exists:assets,id',
        ]);

        DB::beginTransaction();

        try {
            $supplier = Supplier::create([
                // 'institute_id'   => auth()->user()->institute_id,
                'company_name'   => $validated['company_name'],
                'email'          => $validated['email'] ?? null,
                'mobile'         => $validated['mobile'],
                'contact_person' => $validated['contact_person'],
                'address'        => $validated['address'],
            ]);

            // Categories
            $supplier->categories()->sync($validated['category_ids']);

            // 🔥 Asset Items (MANUAL INSERT)
            if (!empty($validated['asset_item_ids'])) {
                foreach ($validated['asset_item_ids'] as $assetId) {
                    SupplierAssetItem::create([
                        'supplier_id'   => $supplier->id,
                        'asset_item_id' => $assetId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Supplier created successfully',
                'data'    => $supplier->load(['categories', 'assetItems'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create supplier',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * SHOW SINGLE SUPPLIER
     */
    public function show($id)
    {
        $supplier = Supplier::with(['categories', 'assetItems'])

            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $supplier
        ]);
    }

    /**
     * UPDATE SUPPLIER
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::
            findOrFail($id);

        $validated = $request->validate([
            'company_name'   => 'required|string|max:150',
            'email'          => 'nullable|email',
            'mobile'         => 'required|string|max:15',
            'contact_person' => 'required|string|max:100',
            'address'        => 'required|string',

            'category_ids'   => 'required|array|min:1',
            'category_ids.*' => 'exists:asset_categories,id',

            'asset_item_ids'   => 'nullable|array',
            'asset_item_ids.*' => 'exists:assets,id',


            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();

        try {
            $supplier->update([
                'company_name'   => $validated['company_name'],
                'email'          => $validated['email'] ?? null,
                'mobile'         => $validated['mobile'],
                'contact_person' => $validated['contact_person'],
                'address'        => $validated['address'],
                'is_active'      => $validated['is_active'] ?? true,
            ]);

            $supplier->categories()->sync($validated['category_ids']);

            $supplier->assetItems()->sync($validated['asset_item_ids'] ?? []);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Supplier updated successfully',
                'data'    => $supplier->load(['categories', 'assetItems'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update supplier',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE SUPPLIER (SOFT DELETE)
     */
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        $supplier->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Supplier deleted successfully'
        ]);
    }

    public function supplierCategories($supplierId)
    {
        $supplier = Supplier::with('categories')->findOrFail($supplierId);

        return response()->json([
            'status' => 'success',
            'data'   => $supplier->categories
        ]);
    }

    public function supplierAssets(Request $request, $supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);

        $assets = $supplier->assetItems()
            ->when($request->category_id, function ($q) use ($request) {
                $q->where('asset_category_id', $request->category_id);
            })
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $assets
        ]);
    }

    public function export()
    {
        return Excel::download(
            new SupplierExport(),
            'suppliers_report.xlsx'
        );
    }


}
