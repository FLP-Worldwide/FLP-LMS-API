<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * List subjects (filter by class)
     */
    public function index(Request $request)
    {
        $query = Subject::with('class:id,name');

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Create subject
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->where('class_id', $request->class_id)
                    ),
            ],
            'short_code' => 'nullable|string|max:20',
            'type' => 'required|in:Scholastic,Co-Scholastic',
            'is_active' => 'boolean',
        ]);

        $subject = Subject::create([
            'class_id'   => $validated['class_id'],
            'name'       => $validated['name'],
            'short_code' => $validated['short_code'] ?? null,
            'type'       => $validated['type'],
            'is_active'  => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Subject created successfully.',
            'data'    => $subject,
        ], 201);
    }

    /**
     * Show single subject
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Subject::with('class:id,name')->findOrFail($id),
        ]);
    }

    /**
     * Update subject
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $validated = $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('subjects')
                    ->ignore($subject->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                          ->where('class_id', $request->class_id)
                    ),
            ],
            'short_code' => 'nullable|string|max:20',
            'type' => 'required|in:Scholastic,Co-Scholastic',
            'is_active' => 'boolean',
        ]);

        $subject->update([
            'class_id'   => $validated['class_id'],
            'name'       => $validated['name'],
            'short_code' => $validated['short_code'] ?? $subject->short_code,
            'type'       => $validated['type'],
            'is_active'  => $validated['is_active'] ?? $subject->is_active,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Subject updated successfully.',
            'data'    => $subject,
        ]);
    }
}
