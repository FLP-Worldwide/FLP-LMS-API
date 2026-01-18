<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * 📌 INDEX (FILTERABLE)
     * GET /api/topics?class_id=1&subject_id=2
     */
    public function index(Request $request)
    {
        $topics = Topic::with([
                'classRoom:id,name',
                'subject:id,name',
            ])
            ->when($request->class_id, function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            })
            ->when($request->subject_id, function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $topics,
        ]);
    }

    /**
     * 📌 STORE
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $topic = Topic::create([
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $request->duration,
            'priority' => $request->priority,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Topic created successfully',
            'data' => $topic,
        ], 201);
    }

    /**
     * 📌 SHOW
     */
    public function show($id)
    {
        $topic = Topic::with([
                'classRoom:id,name',
                'subject:id,name',
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $topic,
        ]);
    }

    /**
     * 📌 UPDATE
     */
    public function update(Request $request, $id)
    {
        $topic = Topic::findOrFail($id);

        $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'is_active' => 'boolean',
        ]);

        $topic->update([
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'name' => $request->name,
            'description' => $request->description,
            'duration' => $request->duration,
            'priority' => $request->priority,
            'is_active' => $request->is_active ?? $topic->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Topic updated successfully',
            'data' => $topic,
        ]);
    }

    /**
     * 📌 DELETE (SOFT)
     */
    public function destroy($id)
    {
        $topic = Topic::findOrFail($id);
        $topic->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Topic deleted successfully',
        ]);
    }
}
