<?php

namespace App\Http\Controllers\Assets;

use App\Exports\AssetCategoryExport;
use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AssetCategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => AssetCategory::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        $category = AssetCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = AssetCategory::findOrFail($id);
        $category->update($request->only('name', 'code', 'description', 'is_active'));

        return response()->json(['status' => 'success', 'data' => $category]);
    }

    public function destroy($id)
    {
        AssetCategory::findOrFail($id)->delete();
        return response()->json(['status' => 'success']);
    }


    public function exportCategories()
    {
        return Excel::download(
            new AssetCategoryExport(),
            'asset_category_report.xlsx'
        );
    }
}

