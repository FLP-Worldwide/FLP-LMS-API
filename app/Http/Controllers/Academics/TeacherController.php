<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\TeacherDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{

    public function index(Request $request)
    {
        $query = Teacher::with([
            'detail',
            'classRooms:id,name',
            'subjects:id,name'
        ]);

        // 🔍 Filters
        if ($request->department) {
            $query->where('department', $request->department);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->class_room_id) {
            $query->whereHas('classRooms', function ($q) use ($request) {
                $q->where('class_room_id', $request->class_room_id);
            });
        }

        if ($request->subject_id) {
            $query->whereHas('subjects', function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
        }

        $teachers = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'tuid' => $teacher->tuid,

                    'name' => trim($teacher->first_name . ' ' . $teacher->last_name),
                    'department' => $teacher->department,
                    'designation' => $teacher->designation,
                    'status' => $teacher->status,
                    'joining_date' => $teacher->joining_date,

                    'contact' => [
                        'phone' => $teacher->detail->phone ?? null,
                        'email' => $teacher->detail->email ?? null,
                    ],

                    'classes' => $teacher->classRooms->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ]),

                    'subjects' => $teacher->subjects->map(fn ($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                    ]),
                ];
            }),
        ]);
    }

    public function show($id)
    {
        $teacher = Teacher::with([
            'detail',
            'classRooms:id,name',
            'subjects:id,name'
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $teacher->id,
                'tuid' => $teacher->tuid,

                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'department' => $teacher->department,
                'designation' => $teacher->designation,
                'status' => $teacher->status,
                'joining_date' => $teacher->joining_date,

                'detail' => [
                    'phone' => $teacher->detail->phone ?? null,
                    'email' => $teacher->detail->email ?? null,
                    'dob' => $teacher->detail->dob ?? null,
                    'address' => $teacher->detail->address ?? null,
                ],

                'class_rooms' => $teacher->classRooms->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                ]),

                'subjects' => $teacher->subjects->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ]),
            ],
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            // Teacher core
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'designation'=> 'nullable|string|max:100',
            'joining_date' => 'nullable|date',

            // Contact
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',

            // Personal
            'dob'     => 'nullable|date',
            'address' => 'nullable|string',

            // Class mapping
            'class_room_ids'   => 'nullable|array',
            'class_room_ids.*' => 'exists:class_rooms,id',

            // Subject mapping
            'subject_ids'   => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        DB::transaction(function () use ($validated, &$teacher) {

            // 1️⃣ Teacher
            $teacher = Teacher::create([
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'] ?? null,
                'department'   => $validated['department'] ?? null,
                'designation'  => $validated['designation'] ?? null,
                'joining_date' => $validated['joining_date'] ?? null,
            ]);

            // 2️⃣ Teacher details
            TeacherDetail::create([
                'teacher_id' => $teacher->id,
                'phone'      => $validated['phone'],
                'email'      => $validated['email'] ?? null,
                'dob'        => $validated['dob'] ?? null,
                'address'    => $validated['address'] ?? null,
            ]);

            // 3️⃣ Attach classes
            if (!empty($validated['class_room_ids'])) {
                $teacher->classRooms()->sync($validated['class_room_ids']);
            }

            // 4️⃣ Attach subjects
            if (!empty($validated['subject_ids'])) {
                $teacher->subjects()->sync($validated['subject_ids']);
            }
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Teacher onboarded successfully.',
            'data'    => $teacher->load([
                'detail',
                'classRooms:id,name',
                'subjects:id,name'
            ]),
        ], 201);
    }
}
