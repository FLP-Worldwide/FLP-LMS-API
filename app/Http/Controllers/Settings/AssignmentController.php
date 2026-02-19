<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'topic' => 'required|string',
            'description' => 'nullable|string',

            'status' => 'required|in:draft,published',

            'publish_at' => 'nullable|date',
            'due_at' => 'nullable|date|after:publish_at',

            'allow_late_submission' => 'boolean',
            'evaluation_required' => 'boolean',

            'course_id' => 'required|exists:courses,id',
            'batch_id' => 'required|exists:batches,id',
            'teacher_id' => 'required|exists:users,id',

            'subject_id' => 'nullable|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'sub_topic_id' => 'nullable|exists:topics,id',

            'file' => 'nullable|file|max:153600',

            'links' => 'array|max:5',
            'links.*.name' => 'required|string',
            'links.*.url' => 'required|url',
        ]);

        $assignment = Assignment::create($data);

        // Files
        if ($request->hasFile('file')) {

            $file = $request->file('file');
            $path = $file->store('assignments', 'public');

            $assignment->resources()->create([
                'type' => 'file',
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }


        // Links
        foreach ($request->links ?? [] as $link) {
            $assignment->resources()->create([
                'type' => 'link',
                'name' => $link['name'],
                'url' => $link['url'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assignment saved successfully',
            'data' => $assignment,
        ]);
    }


public function grouped(Request $request)
{
    $now = now()->utc();

    $assignments = Assignment::with(['course', 'batch', 'subject'])
        ->where('batch_id', $request->batch_id)
        ->get();

    $data = [
        // 📝 Draft assignments
        'draft' => $assignments
            ->where('status', 'draft')
            ->values(),

        // 🟢 Active = Published + Not past due
        // (includes upcoming + currently active)
        'active' => $assignments->filter(fn ($a) =>
            $a->status === 'published' &&
            $a->due_at &&
            $a->due_at->gte($now)
        )->values(),

        // 🔴 Past = Due date crossed
        'past' => $assignments->filter(fn ($a) =>
            $a->status === 'published' &&
            $a->due_at &&
            $a->due_at->lt($now)
        )->values(),
    ];

    return response()->json([
        'status' => 'success',
        'data' => $data,
    ]);
}




    public function show($id)
    {
        $assignment = Assignment::with([
            'course',
            'batch',
            'teacher',
            'subject',
            'resources',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $assignment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);

        $data = $request->validate([
            'topic' => 'sometimes|required|string',
            'description' => 'nullable|string',

            // 🔥 draft → published allowed
            'status' => 'sometimes|required|in:draft,published',

            'publish_at' => 'nullable|date',
            'due_at' => 'nullable|date|after:publish_at',

            'allow_late_submission' => 'boolean',
            'evaluation_required' => 'boolean',

            'course_id' => 'sometimes|required|exists:courses,id',
            'batch_id' => 'sometimes|required|exists:batches,id',
            'teacher_id' => 'sometimes|required|exists:users,id',

            'subject_id' => 'nullable|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'sub_topic_id' => 'nullable|exists:topics,id',

            // Resources
            'files.*' => 'file|max:153600',
            'links' => 'array|max:5',
            'links.*.name' => 'required|string',
            'links.*.url' => 'required|url',

            // Optional flag
            'replace_resources' => 'boolean',
        ]);

        /**
         * 🔒 SAFETY: If publishing, ensure publish_at & due_at exist
         */
        if (
            ($data['status'] ?? $assignment->status) === 'published'
        ) {
            if (
                empty($data['publish_at'] ?? $assignment->publish_at) ||
                empty($data['due_at'] ?? $assignment->due_at)
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Publish date and Due date are required to publish assignment',
                ], 422);
            }
        }

        // 🔹 Update assignment core data
        $assignment->update($data);

        /**
         * 🔹 Replace resources if requested
         */
        if ($request->boolean('replace_resources')) {
            $assignment->resources()->delete();
        }

        // 🔹 Add new files
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('assignments', 'public');

                $assignment->resources()->create([
                    'type' => 'file',
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                ]);
            }
        }

        // 🔹 Add new links
        foreach ($request->links ?? [] as $link) {
            $assignment->resources()->create([
                'type' => 'link',
                'name' => $link['name'],
                'url' => $link['url'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Assignment updated successfully',
            'data' => $assignment->fresh(['course', 'batch', 'subject', 'resources']),
        ]);
    }



}
