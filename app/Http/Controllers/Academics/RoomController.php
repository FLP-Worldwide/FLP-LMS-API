<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    /**
     * Fetch rooms
     */
    public function index(Request $request)
    {
        $query = Room::query();

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    /**
     * Create room
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms')
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                    ),
            ],
            'code'     => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'floor' => 'nullable',
            'number' => 'nullable',
            'is_active'=> 'boolean',
        ]);

        $room = Room::create([
            'name'     => $validated['name'],
            'code'     => $validated['code'] ?? 'RM-'.rand(1000,9999),
            'capacity' => $validated['capacity'] ?? null,
            'number' => $validated['number'] ?? null,
            'floor' => $validated['floor'] ?? null,
            'is_active'=> $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Room created successfully.',
            'data'    => $room,
        ], 201);
    }

    /**
     * Show single room
     */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => Room::findOrFail($id),
        ]);
    }

    /**
     * Update room
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('rooms')
                    ->ignore($room->id)
                    ->where(fn ($q) =>
                        $q->where('institute_id', app('institute_id'))
                    ),
            ],
            'code'     => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'floor' => 'nullable',
            'number' => 'nullable',
            'is_active'=> 'boolean',
        ]);

        $room->update([
            'name'     => $validated['name'],
            'code'     => $validated['code'] ?? $room->code,
            'capacity' => $validated['capacity'] ?? $room->capacity,
            'is_active'=> $validated['is_active'] ?? $room->is_active,
            'number' => $validated['number'] ?? null,
            'floor' => $validated['floor'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Room updated successfully.',
            'data'    => $room,
        ]);
    }
}
