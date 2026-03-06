<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\BatchStudent;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;

class NewStudentImport implements ToCollection
{
    public function collection(Collection $rows)
    {

        $header = $rows->first()->map(fn($h) => trim($h));
        $rows->shift();

        foreach ($rows as $row) {

            $data = [];

            foreach ($header as $index => $column) {
                $data[$column] = $row[$index] ?? null;
            }

            /*
            ============================
            REQUIRED VALIDATION
            ============================
            */

            if(empty($data['Student Name*']) || empty($data['Student Phone*'])){
                continue;
            }

            DB::transaction(function() use ($data){

                /*
                ============================
                NAME SPLIT
                ============================
                */

                $nameParts = explode(' ', $data['Student Name*']);

                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';

                /*
                ============================
                CREATE USER
                ============================
                */

                $password = Str::random(8);

                $user = User::create([
                    'uid' => 'ST'.rand(10000,99999),
                    'name' => $data['Student Name*'],
                    'email' => $data['Student Email'] ?? null,
                    'password' => Hash::make($password),
                    'role' => 'student'
                ]);

                /*
                ============================
                CREATE STUDENT
                ============================
                */

                $student = Student::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'class' => $data['Standard Id'] ?? null,
                    'admission_no' => $data['Register Number'] ?? 'ADM'.rand(10000,99999),
                    'admission_date' => $data['Date of Admission'] ?? now()
                ]);

                /*
                ============================
                STUDENT DETAILS
                ============================
                */

                StudentDetail::create([
                    'student_id' => $student->id,
                    'phone' => $data['Student Phone*'],
                    'email' => $data['Student Email'] ?? null,
                    'gender' => $data['Gender'] ?? null,
                    'dob' => $data['Date of Birth'] ?? null,
                    'address' => $data['Student Current Address'] ?? null,
                    'blood_group' => $data['Blood Group'] ?? null,
                    'father_name' => $data['Parent Name'] ?? null,
                    'parent_phone' => $data['Parent Phone'] ?? null,
                    'mother_name' => $data['Mother Name'] ?? null,
                    'city' => $data['Birth Place'] ?? null,
                    'state' => $data['Nationality'] ?? null,
                    'pin_code' => $data['Pin Code'] ?? null
                ]);

                /*
                ============================
                BATCH ASSIGN
                ============================
                */

                if(!empty($data['Assigned Batch Ids'])){

                    $batchIds = explode(',', $data['Assigned Batch Ids']);

                    foreach($batchIds as $batchId){

                        BatchStudent::create([
                            'student_id' => $student->id,
                            'batch_id' => $batchId,
                            'assigned_date' => $data['Batch Joining Date'] ?? now(),
                            'is_active' => 1
                        ]);
                    }
                }

            });

        }
    }
}
