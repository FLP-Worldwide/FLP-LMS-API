<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherDetail;
use App\Models\StaffDetail;
use App\Models\InstituteUser;
use App\Models\Role;
use App\Models\UserAttendance;
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

            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',

            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',

            'designation' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',

            'dob' => 'nullable|date',
            'address' => 'nullable|string',

            'class_room_ids' => 'nullable|array',
            'class_room_ids.*' => 'exists:class_rooms,id',

            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',

            'id_number' => 'nullable|string|max:100',

            'document1' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'document2' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        return DB::transaction(function () use ($data, $request) {

            $role = Role::findOrFail($data['role_id']);
            $roleSlug = $role->slug;
            $password = Str::random(10);

            /*
            =========================
            NORMALIZE NAME
            =========================
            */

            $fullName = $data['name']
                ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            /*
            =========================
            CREATE USER
            =========================
            */

            $user = User::create([
                'uid' => strtoupper(substr($roleSlug, 0, 3)) . rand(10000, 99999),
                'name' => $fullName,
                'email' => $data['email'],
                'temp_password' => Crypt::encryptString($password),
                'password' => Hash::make($password),
                'role' => $roleSlug,
            ]);

            /*
            =========================
            MAP USER TO INSTITUTE
            =========================
            */

            InstituteUser::create([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role' => $roleSlug,
            ]);

            /*
            =========================
            UPLOAD DOCUMENTS
            =========================
            */

            $document1Path = null;
            $document2Path = null;

            if ($request->hasFile('document1')) {
                $document1Path = $request->file('document1')
                    ->store('staff_documents', 'public');
            }

            if ($request->hasFile('document2')) {
                $document2Path = $request->file('document2')
                    ->store('staff_documents', 'public');
            }

            /*
            =========================
            TEACHER FLOW
            =========================
            */

            if ($roleSlug === 'teacher') {

                $teacher = Teacher::create([
                    'user_id' => $user->id,
                    'first_name' => $data['first_name'] ?? $fullName,
                    'last_name' => $data['last_name'] ?? null,
                    'employee_id' => $user->uid,
                    'designation' => $data['designation'] ?? 'Teacher',
                    'department' => $data['department'] ?? null,
                    'joining_date' => $data['joining_date'] ?? null,
                ]);

                TeacherDetail::create([
                    'teacher_id' => $teacher->id,
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'dob' => $data['dob'] ?? null,
                    'address' => $data['address'] ?? null,
                    'document1' => $document1Path,
                    'document2' => $document2Path,
                ]);

                if (!empty($data['class_room_ids'])) {
                    $teacher->classRooms()->sync($data['class_room_ids']);
                }

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

            /*
            =========================
            STAFF FLOW
            =========================
            */

            StaffDetail::create([
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'designation' => $data['designation'] ?? ucfirst($roleSlug),
                'joining_date' => $data['joining_date'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'document1' => $document1Path,
                'document2' => $document2Path,
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
        DB::transaction(function () use ($id) {

            $user = User::findOrFail($id);

            /**
             * ------------------------------------
             * 1️⃣ IF TEACHER
             * ------------------------------------
             */
            if ($user->role === 'teacher') {

                $teacher = Teacher::whereHas('detail', function ($q) use ($user) {
                    $q->where('email', $user->email);
                })->first();

                if ($teacher) {

                    // Detach pivot relations
                    $teacher->classRooms()->detach();
                    $teacher->subjects()->detach();

                    // Delete teacher detail
                    $teacher->detail()?->delete();

                    // Delete teacher
                    $teacher->delete();
                }
            }

            /**
             * ------------------------------------
             * 2️⃣ IF OTHER STAFF
             * ------------------------------------
             */
            if ($user->staffDetail) {
                $user->staffDetail->delete();
            }

            /**
             * ------------------------------------
             * 3️⃣ DELETE INSTITUTE MAPPING
             * ------------------------------------
             */
            InstituteUser::where('user_id', $user->id)->delete();

            /**
             * ------------------------------------
             * 4️⃣ DELETE ATTENDANCE
             * ------------------------------------
             */
            UserAttendance::where('user_id', $user->id)->delete();

            /**
             * ------------------------------------
             * 5️⃣ DELETE USER
             * ------------------------------------
             */
            $user->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Staff member deleted successfully',
        ]);
    }

}
