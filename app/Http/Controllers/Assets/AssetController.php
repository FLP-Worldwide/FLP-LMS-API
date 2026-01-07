<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends Controller
{

    public function index(Request $request)
    {
        $query = Asset::query()
            ->where('is_active', 1);

        // 🔹 Single category
        if ($request->filled('category_id')) {
            $query->where('asset_category_id', $request->category_id);
        }

        // 🔹 Multiple categories
        if ($request->filled('category_ids')) {
            $query->whereIn('asset_category_id', $request->category_ids);
        }

        // 🔹 Optional search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $assets = $query
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $assets
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_location_id' => 'required|exists:asset_locations,id',
            'asset_category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:1',
            'condition' => 'required|in:New,Good,Damaged,Repair',
            'description' => 'nullable|string'
        ]);

        $asset = Asset::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $asset->load('location', 'category')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $asset->update($request->only([
            'asset_location_id',
            'asset_category_id',
            'name',
            'code',
            'quantity',
            'condition',
            'description',
            'is_active'
        ]));

        return response()->json([
            'status' => 'success',
            'data' => $asset->load('location', 'category')
        ]);
    }

    public function destroy($id)
    {
        Asset::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }
}

