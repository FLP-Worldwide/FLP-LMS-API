<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    /**
     * List areas (filter by state / city)
     */
    public function index(Request $request)
    {
        $query = Area::query();

        if ($request->state) {
            $query->where('state', $request->state);
        }

        if ($request->city) {
            $query->where('city', $request->city);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('state')
                            ->orderBy('city')
                            ->orderBy('area')
                            ->get(),
        ]);
    }

    /**
     * Create area
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'state' => 'required|string|max:100',
            'city'  => 'required|string|max:100',
            'area'  => [
                'required',
                'string',
                'max:150',
                Rule::unique('areas')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->where('state', $request->state)
                          ->where('city', $request->city)
                          ->whereNull('deleted_at')
                    ),
            ],
            'country' => 'nullable|string|max:100',
        ]);

        $area = Area::create([
            'country' => $validated['country'] ?? 'India',
            'state'   => $validated['state'],
            'city'    => $validated['city'],
            'area'    => $validated['area'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Area saved successfully.',
            'data'    => $area,
        ], 201);
    }

    /**
     * Show single area
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Area::findOrFail($id),
        ]);
    }

    /**
     * Update area
     */
    public function update(Request $request, $id)
    {
        $area = Area::findOrFail($id);

        $validated = $request->validate([
            'state' => 'required|string|max:100',
            'city'  => 'required|string|max:100',
            'area'  => [
                'required',
                'string',
                'max:150',
                Rule::unique('areas')
                    ->ignore($area->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->where('state', $request->state)
                          ->where('city', $request->city)
                          ->whereNull('deleted_at')
                    ),
            ],
            'country'  => 'nullable|string|max:100',
            'is_active'=> 'boolean',
        ]);

        $area->update([
            'country'   => $validated['country'] ?? $area->country,
            'state'     => $validated['state'],
            'city'      => $validated['city'],
            'area'      => $validated['area'],
            'is_active' => $validated['is_active'] ?? $area->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Area updated successfully.',
            'data'    => $area,
        ]);
    }

    /**
     * Soft delete area
     */
    public function destroy($id)
    {
        $area = Area::findOrFail($id);
        $area->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Area deleted successfully.',
        ]);
    }
}
