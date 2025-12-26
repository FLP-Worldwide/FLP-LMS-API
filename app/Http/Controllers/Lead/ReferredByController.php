<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\ReferredBy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferredByController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => ReferredBy::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('referred_bies')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->whereNull('deleted_at')
                    ),
            ],
            'phone' => 'nullable|string|max:15',
        ]);

        $ref = ReferredBy::create([
            'name'  => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Referral member added successfully.',
            'data'    => $ref,
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => ReferredBy::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $ref = ReferredBy::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('referred_bies')
                    ->ignore($ref->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->whereNull('deleted_at')
                    ),
            ],
            'phone'     => 'nullable|string|max:15',
            'is_active' => 'boolean',
        ]);

        $ref->update([
            'name'      => $validated['name'],
            'phone'     => $validated['phone'] ?? $ref->phone,
            'is_active' => $validated['is_active'] ?? $ref->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Referral member updated successfully.',
            'data'    => $ref,
        ]);
    }

    public function destroy($id)
    {
        $ref = ReferredBy::findOrFail($id);
        $ref->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Referral member deleted successfully.',
        ]);
    }
}
