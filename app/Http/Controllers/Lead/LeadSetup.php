<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\LeadSourceType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadSetup extends Controller
{
    public function index()
    {
        $sources = LeadSourceType::latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $sources,
        ]);
    }

    /**
     * Create lead source
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_source_types')
                    ->where(function ($q) {
                        $q->where('institute_id', app('institute_id'))
                        ->whereNull('deleted_at');
                    }),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $source = LeadSourceType::create([
            'name'        => $validated['name'],
            'slug'        => str()->slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead source created successfully.',
            'data'    => $source,
        ], 201);
    }

    /**
     * Show single lead source
     */
    public function show($id)
    {
        $source = LeadSourceType::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $source,
        ]);
    }

    /**
     * Update lead source
     */
    public function update(Request $request, $id)
    {
        $source = LeadSourceType::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_source_types')
                    ->ignore($source->id)
                    ->where(function ($q) {
                        $q->where('institute_id', app('institute_id'))
                        ->whereNull('deleted_at'); // ✅ IGNORE SOFT DELETED
                    }),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $source->update([
            'name'        => $validated['name'],
            'slug'        => str()->slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? $source->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead source updated successfully.',
            'data'    => $source,
        ]);
    }

    /**
     * Delete lead source
     */
    public function destroy($id)
    {
        $source = LeadSourceType::findOrFail($id);
        $source->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead source deleted successfully.',
        ]);
    }
}
