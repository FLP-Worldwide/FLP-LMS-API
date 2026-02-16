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
            'course_id' => 'required|exists:courses,id',
            'batch_id'  => 'required|exists:batches,id',
            'base_date' => 'required|date',
            'classes'   => 'required|array|min:1',

            'classes.*.subject_id' => 'required|exists:subjects,id',
            'classes.*.topic' => 'nullable|string|max:255',
            'classes.*.start_time' => 'required|string',
            'classes.*.end_time' => 'required|string',
            'classes.*.teacher_id' => 'nullable|exists:teachers,id',
            'classes.*.description' => 'nullable|string',
            'classes.*.room_no' => 'nullable|string|max:50',
            'classes.*.class_type' => 'required|string',

            'classes.*.repeat' => 'required|in:Does Not Repeat,Weekly,Select Dates',
            'classes.*.repeat_days' => 'nullable|array',
            'classes.*.repeat_dates' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {

            $createdRoutines = [];

            foreach ($validated['classes'] as $class) {

                $startTime = date('H:i:s', strtotime($class['start_time']));
                $endTime   = date('H:i:s', strtotime($class['end_time']));

                $routine = ClassRoutine::create([
                    'course_id'  => $validated['course_id'],
                    'batch_id'   => $validated['batch_id'],
                    'base_date'  => $validated['base_date'],

                    'subject_id' => $class['subject_id'],
                    'topic'      => $class['topic'] ?? null,
                    'teacher_id' => $class['teacher_id'] ?? null,
                    'description'=> $class['description'] ?? null,
                    'room_id'    => $class['room_no'] ?? null,
                    'class_type' => $class['class_type'],
                    'repeat_type'=> $class['repeat'],

                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                ]);

                /*
                ----------------------------------------
                HANDLE REPEAT LOGIC
                ----------------------------------------
                */

                if ($class['repeat'] === 'Does Not Repeat') {

                    $routine->days()->create([
                        'day' => date('l', strtotime($validated['base_date']))
                    ]);

                } elseif ($class['repeat'] === 'Weekly') {

                    foreach ($class['repeat_days'] ?? [] as $day) {
                        $routine->days()->create(['day' => $day]);
                    }

                } elseif ($class['repeat'] === 'Select Dates') {

                    foreach ($class['repeat_dates'] ?? [] as $date) {
                        $routine->days()->create([
                            'day' => date('l', strtotime($date))
                        ]);
                    }
                }

                $createdRoutines[] = $routine->load('days');
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Class routine created successfully.',
                'data' => $createdRoutines,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
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
