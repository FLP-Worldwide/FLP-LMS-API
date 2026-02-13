<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherDetail;
use App\Models\StaffDetail;
use App\Models\InstituteUser;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class StaffOnboardingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
             'role_id' => 'required|exists:roles,id',

            // Common
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',

            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',

            'designation' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',

            // Teacher-only
            'dob' => 'nullable|date',
            'address' => 'nullable|string',

            'class_room_ids' => 'nullable|array',
            'class_room_ids.*' => 'exists:class_rooms,id',

            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',

            // Staff-only
            'id_number' => 'nullable|string|max:100',
        ]);

        return DB::transaction(function () use ($data) {

            $role = Role::findOrFail($data['role_id']);
            $roleSlug = $role->slug; // teacher, driver, accountant
            $password = Str::random(10);

            /**
             * =========================
             * 1️⃣ NORMALIZE NAME
             * =========================
             */
            $fullName = $data['name']
                ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            /**
             * =========================
             * 2️⃣ USER (AUTH)
             * =========================
             */
            $user = User::create([
                'uid' => strtoupper(substr($roleSlug, 0, 3)) . rand(10000, 99999),
                'name' => $fullName,
                'email' => $data['email'],
                'temp_password' => Crypt::encryptString($password),
                'password' => Hash::make($password),
                'role' => $roleSlug,
            ]);

            /**
             * =========================
             * 3️⃣ MAP TO INSTITUTE
             * =========================
             */
            InstituteUser::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role' => $roleSlug,
            ]);

            /**
             * =========================
             * 4️⃣ TEACHER FLOW
             * =========================
             */
            if ($roleSlug === 'teacher') {

                $teacher = Teacher::create([
                    'first_name'   => $data['first_name'] ?? $fullName,
                    'last_name'    => $data['last_name'] ?? null,
                    'designation'  => $data['designation'] ?? 'Teacher',
                    'department'   => $data['department'] ?? null,
                    'joining_date' => $data['joining_date'] ?? null,
                ]);

                TeacherDetail::create([
                    'teacher_id' => $teacher->id,
                    'phone'      => $data['phone'],
                    'email'      => $data['email'],
                    'dob'        => $data['dob'] ?? null,
                    'address'    => $data['address'] ?? null,
                ]);

                // 🔗 Attach classes
                if (!empty($data['class_room_ids'])) {
                    $teacher->classRooms()->sync($data['class_room_ids']);
                }

                // 🔗 Attach subjects
                if (!empty($data['subject_ids'])) {
                    $teacher->subjects()->sync($data['subject_ids']);
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Teacher created successfully',
                    'data' => $teacher->load([
                        'detail',
                        'classRooms:id,name',
                        'subjects:id,name'
                    ]),
                ], 201);
            }

            /**
             * =========================
             * 5️⃣ STAFF FLOW
             * =========================
             */
            StaffDetail::create([
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'designation' => $data['designation'] ?? ucfirst($roleSlug),
                'joining_date' => $data['joining_date'] ?? null,
                'id_number' => $data['id_number'] ?? null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => ucfirst($roleSlug) . ' created successfully',
                'data' => $user,
            ], 201);
        });
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'teacher') {
            $teacher = Teacher::where('first_name', $user->name)->first();
            if ($teacher) {
                $teacher->classRooms()->detach();
                $teacher->subjects()->detach();
                $teacher->delete();
            }
        }

        InstituteUser::where('user_id', $user->id)->delete();
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member deleted successfully',
        ]);
    }
}
