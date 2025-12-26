<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\LeadClosingReason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadClosingReasonController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => LeadClosingReason::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_closing_reasons')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->whereNull('deleted_at')
                    ),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $reason = LeadClosingReason::create([
            'name'        => $validated['name'],
            'slug'        => str()->slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead closing reason created successfully.',
            'data'    => $reason,
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => LeadClosingReason::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $reason = LeadClosingReason::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('lead_closing_reasons')
                    ->ignore($reason->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->whereNull('deleted_at')
                    ),
            ],
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $reason->update([
            'name'        => $validated['name'],
            'slug'        => str()->slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? $reason->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead closing reason updated successfully.',
            'data'    => $reason,
        ]);
    }

    public function destroy($id)
    {
        $reason = LeadClosingReason::findOrFail($id);

        // Optional safety (recommended when leads table exists)
        // if ($reason->leads()->exists()) { ... }

        $reason->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead closing reason deleted successfully.',
        ]);
    }
}
