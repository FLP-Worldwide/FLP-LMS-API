<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Teacher;
use App\Models\TeacherDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class TeacherBulkImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $rows->shift(); // remove heading

        foreach ($rows as $row) {

            if (!$row[0] || !$row[2]) {
                continue;
            }

            $name = $row[0];
            $phone = $row[1];
            $email = $row[2];
            $joiningDate = $row[3];
            $dob = $row[4];
            $altPhone = $row[5];
            $department = $row[6];
            $designation = $row[7];
            $address = $row[8];

            if (User::where('email',$email)->exists()) {
                continue;
            }

            $firstName = explode(' ', $name)[0];
            $lastName = explode(' ', $name)[1] ?? null;

            $password = Str::random(10);

            /*
            USER
            */

            $user = User::create([
                'uid' => 'TEA'.rand(10000,99999),
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'temp_password' => Crypt::encryptString($password),
                'role' => 'teacher'
            ]);

            /*
            TEACHER
            */

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'employee_id' => $user->uid,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'designation' => $designation,
                'department' => $department,
                'joining_date' => $joiningDate
            ]);

            /*
            DETAILS
            */

            TeacherDetail::create([
                'teacher_id' => $teacher->id,
                'phone' => $phone,
                'email' => $email,
                'dob' => $dob,
                'address' => $address
            ]);
        }
    }
}
