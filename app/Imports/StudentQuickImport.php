<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\Batch;
use App\Models\BatchStudent;
use App\Models\StudentImportLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;

class StudentQuickImport implements ToCollection
{
    public function collection(Collection $rows)
    {

        $header = $rows->first();
        $rows->shift();

        foreach ($rows as $row) {

            $data = [];

            foreach ($header as $index => $column) {
                $data[$column] = $row[$index] ?? '-';
            }

            DB::transaction(function () use ($data) {

                /*
                ======================
                STUDENT NAME
                ======================
                */

                $fullName = $data['Student Name'] ?? 'Unknown';

                $parts = explode(' ', $fullName);
                $firstName = $parts[0];
                $lastName = $parts[1] ?? '';

                /*
                ======================
                CREATE USER
                ======================
                */

                $password = Str::random(8);

                $user = User::create([
                    'uid' => 'ST'.rand(10000,99999),
                    'name' => $fullName,
                    'email' => $data['Student Email'] ?? Str::random(6).'@temp.com',
                    'password' => Hash::make($password),
                    'temp_password' => Crypt::encryptString($password),
                    'role' => 'student'
                ]);

                /*
                ======================
                CREATE STUDENT
                ======================
                */

                $student = Student::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'class' => $data['Standard'] ?? null,
                    'section' => $data['Section'] ?? null,
                    'status' => 'active',
                    'admission_date' => now()
                ]);

                /*
                ======================
                STUDENT DETAILS
                ======================
                */

                StudentDetail::create([
                    'student_id' => $student->id,
                    'phone' => $data['Student Phone'] ?? null,
                    'mother_name' => $data['Mother Name'] ?? null,
                    'father_name' => $data['Parent Name'] ?? null,
                    'parent_phone' => $data['Parent Phone'] ?? null,
                    'city' => $data['City'] ?? null,
                    'state' => $data['State'] ?? null,
                    'address' => $data['Student Current Address'] ?? null,
                    'dob' => $data['Date of Birth'] ?? null
                ]);

                /*
                ======================
                BATCH ASSIGNMENT
                ======================
                */

                if (!empty($data['Batch'])) {

                    $batch = Batch::where('name',$data['Batch'])
                        ->orWhere('batch_uid',$data['Batch'])
                        ->first();

                    if ($batch) {

                        BatchStudent::create([
                            'batch_id' => $batch->id,
                            'student_id' => $student->id,
                            'assigned_date' => now(),
                            'is_active' => 1
                        ]);
                    }
                }

                /*
                ======================
                SAVE RAW JSON
                ======================
                */

                StudentImportLog::create([
                    'student_id' => $student->id,
                    'raw_data' => $data
                ]);

            });
        }
    }
}
