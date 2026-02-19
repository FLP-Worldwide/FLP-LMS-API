<?php
namespace App\Http\Controllers\Assets;

use App\Exports\AssetLocationExport;
use App\Http\Controllers\Controller;
use App\Models\AssetLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AssetLocationController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => AssetLocation::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_locations')
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('asset_locations')
                    ->whereNull('deleted_at'),
            ],
            'description' => 'nullable|string',
        ]);

        $location = AssetLocation::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $location
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $location = AssetLocation::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_locations')
                    ->whereNull('deleted_at')
                    ->ignore($location->id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('asset_locations')
                    ->whereNull('deleted_at')
                    ->ignore($location->id),
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $location->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $location
        ]);
    }
    public function destroy($id)
    {
        AssetLocation::findOrFail($id)->delete();

        return response()->json(['status' => 'success']);
    }


    public function export()
    {
        return Excel::download(
            new AssetLocationExport(),
            'asset_locations.xlsx'
        );
    }

}

