<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\ClassRoutine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassRoutineController extends Controller
{
    public function index(Request $request)
{
    $query = ClassRoutine::with([
        'days',
        'classRoom:id,name',
        'subject:id,name,short_code',
        'room:id,name,code,floor,number'
    ]);

    if ($request->class_id) {
        $query->where('class_id', $request->class_id);
    }

    if ($request->section) {
        $query->where('section', $request->section);
    }

    $routines = $query->orderBy('start_time')->get();

    $data = $routines->map(function ($routine) {
        return [
            'id' => $routine->id,

            // ✅ class relation exists
            'class' => $routine->classRoom ? [
                'id' => $routine->classRoom->id,
                'name' => $routine->classRoom->name,
            ] : null,

            'section' => $routine->section,

            // ✅ subject relation exists
            'subject' => $routine->subject ? [
                'id' => $routine->subject->id,
                'name' => $routine->subject->name,
                'code' => $routine->subject->short_code,
            ] : null,

            // ✅ teacher is STRING
            'teacher' => $routine->teacher,

            // ✅ room is ID (not relation)
            'room' => $routine->room,

            'start_time' => date('h:i A', strtotime($routine->start_time)),
            'end_time'   => date('h:i A', strtotime($routine->end_time)),

            'days' => $routine->days->pluck('day')->values(),

            'is_active' => $routine->is_active,
        ];
    });

    return response()->json([
        'status' => 'success',
        'data' => $data,
    ]);
}



    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id'   => 'required|exists:class_rooms,id',
            'section'    => 'required|string|max:50',
            'subject_id' => 'required|exists:subjects,id',

            // teacher can be empty
            'teacher'    => 'nullable|string|max:100',

            'room_id'    => 'required|exists:rooms,id',

            'day'        => 'required|array|min:1',
            'day.*'      => 'required|string',

            'start_time' => 'required|string',
            'end_time'   => 'required|string',
        ]);



        $startTime = date('H:i:s', strtotime($validated['start_time']));
        $endTime   = date('H:i:s', strtotime($validated['end_time']));



        DB::transaction(function () use ($validated, $startTime, $endTime, &$routine) {

            $routine = ClassRoutine::create([
                'class_id'   => $validated['class_id'],
                'section'    => $validated['section'],
                'subject_id' => $validated['subject_id'],
                'teacher'    => $validated['teacher'],
                'room_id'    => $validated['room_id'],
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ]);

            foreach ($validated['day'] as $day) {
                $routine->days()->create(['day' => $day]);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Class routine created successfully.',
            'data'    => $routine->load('days'),
        ], 201);
    }


    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => ClassRoutine::with('days')->findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $routine = ClassRoutine::with('days')->findOrFail($id);

        $validated = $request->validate([
            'section'    => 'required|string|max:50',
            'subject_id' => 'required|exists:subjects,id',
            'teacher'    => 'nullable|string|max:100',
            'room_id'    => 'required|exists:rooms,id',

            'day'        => 'required|array|min:1',
            'day.*'      => 'required|string',

            'start_time' => 'required|string',
            'end_time'   => 'required|string',
        ]);

        $startTime = date('H:i:s', strtotime($validated['start_time']));
        $endTime   = date('H:i:s', strtotime($validated['end_time']));

        DB::transaction(function () use ($routine, $validated, $startTime, $endTime) {

            // ✅ Update routine core
            $routine->update([
                'section'    => $validated['section'],
                'subject_id' => $validated['subject_id'],
                'teacher'    => $validated['teacher'] ?: null,
                'room_id'    => $validated['room_id'],
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ]);

            // ================= DAY SYNC LOGIC =================

            $existingDays = $routine->days->pluck('day')->toArray();
            $incomingDays = $validated['day'];

            // ➕ Add new days (skip duplicates)
            $daysToAdd = array_diff($incomingDays, $existingDays);

            foreach ($daysToAdd as $day) {
                $routine->days()->create([
                    'day' => $day,
                ]);
            }

            // ➖ Remove days that are no longer present
            $daysToRemove = array_diff($existingDays, $incomingDays);

            if (!empty($daysToRemove)) {
                $routine->days()
                    ->whereIn('day', $daysToRemove)
                    ->delete();
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Class routine updated successfully.',
            'data'    => $routine->fresh('days'),
        ]);
    }


}
