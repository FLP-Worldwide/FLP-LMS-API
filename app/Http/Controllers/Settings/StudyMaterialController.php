<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\StudyMaterial;
use Illuminate\Http\Request;

class StudyMaterialController extends Controller
{
    public function store(Request $request)
    {
        $isVideo = in_array($request->type, ['YouTube', 'Vimeo']);

        $rules = [
            'class_id' => 'required|exists:class_rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'topic_id' => 'nullable|exists:topics,id',
            'type' => 'required|string',
        ];

        if ($isVideo) {
            $rules['title'] = 'required|string';
            $rules['video_url'] = 'required|url';
        } else {
            $rules['file'] = 'required|file|max:102400'; // 100MB
        }

        $data = $request->validate($rules);

        // 🔹 Handle file upload
        if (!$isVideo && $request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('study-materials', 'public');

            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
        }

        $material = StudyMaterial::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Study material added successfully',
            'data' => $material,
        ], 201);
    }

    public function index(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_rooms,id',
            'topic_id' => 'nullable|exists:topics,id',
            'type' => 'nullable|string',
        ]);

        $materials = StudyMaterial::with([
            'subject:id,name,short_code',
            'topic:id,name',
        ])
            ->where('class_id', $request->class_id)
            ->when($request->topic_id, fn ($q) =>
                $q->where('topic_id', $request->topic_id)
            )
            ->when($request->type, fn ($q) =>
                $q->where('type', $request->type)
            )
            ->latest()
            ->get();

        // 🔥 GROUP BY SUBJECT
        $grouped = $materials
            ->groupBy('subject_id')
            ->map(function ($items) {
                $subject = $items->first()->subject;

                return [
                    'subject' => [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'short_code' => $subject->short_code,
                    ],
                    'materials' => $items->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'type' => $m->type,
                            'title' => $m->title,
                            'video_url' => $m->video_url,
                            'file_path' => $m->file_path,
                            'file_name' => $m->file_name,
                            'topic' => $m->topic ? [
                                'id' => $m->topic->id,
                                'name' => $m->topic->name,
                            ] : null,
                            'created_at' => $m->created_at,
                        ];
                    }),
                ];
            })
            ->values(); // reset keys

        return response()->json([
            'status' => 'success',
            'data' => $grouped,
        ]);
    }

    public function show($id)
    {
        $material = StudyMaterial::with([
            'classRoom:id,name',
            'subject:id,name,short_code',
            'topic:id,name',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $material->id,
                'type' => $material->type,
                'title' => $material->title,
                'video_url' => $material->video_url,
                'file_path' => $material->file_path,
                'file_name' => $material->file_name,
                'class' => $material->classRoom,
                'subject' => $material->subject,
                'topic' => $material->topic,
                'created_at' => $material->created_at,
            ],
        ]);
    }




    public function update(Request $request, $id)
    {
        $material = StudyMaterial::findOrFail($id);

        $material->update($request->only([
            'title',
            'video_url',
            'topic_id',
        ]));

        // Optional file replace
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('study-materials', 'public');

            $material->update([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Study material updated successfully',
        ]);
    }

    public function destroy($id)
    {
        StudyMaterial::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Study material deleted successfully',
        ]);
    }


}
