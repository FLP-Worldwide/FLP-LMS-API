<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\StudyResource;
use Illuminate\Http\Request;

class StudyResourceController extends Controller
{
    public function index(Request $request)
    {
        $resources = StudyResource::where('parent_id', $request->parent_id)
            ->orderByRaw("type = 'folder' DESC") // folders first
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $resources,
        ]);
    }

    public function destroy($id)
    {
        $resource = StudyResource::findOrFail($id);

        // optional: prevent delete if folder not empty
        if ($resource->type === 'folder' && $resource->children()->count()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Folder is not empty',
            ], 422);
        }

        $resource->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Deleted successfully',
        ]);
    }


    public function createFolder(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:study_resources,id',
        ]);

        $folder = StudyResource::create([
            'type' => 'folder',
            'name' => $data['name'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Folder created successfully',
            'data' => $folder,
        ]);
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:study_resources,id',
            'file' => 'required|file|max:204800', // 200MB
        ]);

        $file = $request->file('file');
        $path = $file->store('resources', 'public');

        $resource = StudyResource::create([
            'type' => 'file',
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
            'parent_id' => $request->parent_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'File uploaded successfully',
            'data' => $resource,
        ]);
    }


    public function addLink(Request $request)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:study_resources,id',
            'name' => 'required|string',
            'url' => 'required|url',
        ]);

        $link = StudyResource::create([
            'type' => 'link',
            'name' => $data['name'],
            'url' => $data['url'],
            'parent_id' => $data['parent_id'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Link added successfully',
            'data' => $link,
        ]);
    }


}
