<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Student;
use App\Models\Batch;
use App\Models\StudentDetail;
use App\Models\BatchStudent;
use App\Models\StudentImportLog;
use App\Models\StudentImportFile;
use App\Models\ClassRoom;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;

class NewStudentImport implements ToCollection
{

    public $fileId;

    public $totalRows = 0;
    public $successRows = 0;
    public $failedRows = 0;

    public function __construct($fileId)
    {
        $this->fileId = $fileId;
    }

    public function collection(Collection $rows)
    {

        $header = $rows->first()->map(fn($h) => trim($h));
        $rows->shift();

        $this->totalRows = $rows->count();

        $rowNumber = 1;

        foreach ($rows as $row) {

            $rowNumber++;

            $data = [];

            foreach ($header as $index => $column) {
                $data[$column] = $row[$index] ?? null;
            }

            $validator = Validator::make($data, [

                'Student Name*' => 'required|string|max:255',
                'Gender' => 'nullable|in:Male,Female,Other',
                'Student Current Address' => 'nullable|string|max:500',
                'Student Email' => 'nullable|email|max:255',
                'Country Calling Code' => 'required|string|max:5',
                'Student Phone*' => 'required|digits_between:8,15',
                'Student Adhar Card' => 'nullable|digits:12',
                'Date of Birth' => 'nullable|date',
                'Date of Admission' => 'nullable|date',
                'Parent Name' => 'nullable|string|max:255',
                'Parent Email' => 'nullable|email',
                'Parent Phone' => 'nullable|digits_between:8,15',
                'Parent Adhar Card' => 'nullable|digits:12',
                'Parent Profession' => 'nullable|string|max:255',
                'Mother Name' => 'nullable|string|max:255',
                'Mother Contact' => 'nullable|digits_between:8,15',
                'Mother Email' => 'nullable|email',
                'Guardian Name' => 'nullable|string|max:255',
                'Guardian Email' => 'nullable|email',
                'Guardian Phone' => 'nullable|digits_between:8,15',
                'Assigned Batch Ids' => 'nullable|string',
                'Batch Joining Date' => 'nullable|date',
                'Institute/School Name' => 'nullable|string|max:255',
                'Remarks' => 'nullable|string|max:500',
                'Standard Id' => 'nullable|string|max:50',
                'Register Number' => 'nullable|string|max:50',
                'Birth Place' => 'nullable|string|max:255',
                'Blood Group' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
                'Category' => 'nullable|string|max:100',
                'Nationality' => 'nullable|string|max:100',
                'Mother Tongue' => 'nullable|string|max:100',
                'Pin Code' => 'nullable|digits:6',
                'Educational Group' => 'nullable|string|max:100',
                'visit date' => 'nullable|date',
                'centre vist' => 'nullable|string|max:100',
                'default*' => 'nullable|string|max:50',

            ], [

                'Student Name*.required' => 'Student Name is required',

                'Gender.in' => 'Invalid Gender. Allowed values: Male, Female, Other',

                'Student Email.email' => 'Invalid Student Email format',

                'Country Calling Code.required' => 'Country Calling Code is required (Example: +91)',

                'Student Phone*.required' => 'Student Phone is required',
                'Student Phone*.digits_between' => 'Student Phone must be between 8 to 15 digits',

                'Student Adhar Card.digits' => 'Student Adhar Card must be 12 digits',

                'Date of Birth.date' => 'Date of Birth must be a valid date (YYYY-MM-DD)',

                'Date of Admission.date' => 'Date of Admission must be a valid date (YYYY-MM-DD)',

                'Parent Email.email' => 'Invalid Parent Email format',

                'Parent Phone.digits_between' => 'Parent Phone must be between 8 to 15 digits',

                'Parent Adhar Card.digits' => 'Parent Adhar Card must be 12 digits',

                'Mother Contact.digits_between' => 'Mother Contact must be between 8 to 15 digits',

                'Mother Email.email' => 'Invalid Mother Email format',

                'Guardian Email.email' => 'Invalid Guardian Email format',

                'Guardian Phone.digits_between' => 'Guardian Phone must be between 8 to 15 digits',

                'Batch Joining Date.date' => 'Batch Joining Date must be a valid date',

                'Blood Group.in' => 'Invalid Blood Group. Allowed values: A+, A-, B+, B-, O+, O-, AB+, AB-',

                'Pin Code.digits' => 'Pin Code must be exactly 6 digits',

                'visit date.date' => 'Visit Date must be a valid date',

            ]);

            if ($validator->fails()) {

                $this->failedRows++;

                $this->logError($data, $validator->errors()->first());

                continue;
            }

            try {

                if (
                    empty($data['Student Name*']) ||
                    empty($data['Student Phone*']) ||
                    empty($data['Country Calling Code']) ||
                    (empty($data['Standard Id']) && empty($data['Assigned Batch Ids']))
                ) {

                    $this->failedRows++;

                    $this->logError($data, "Mandatory fields missing");

                    continue;
                }

                /*
                ============================
                CLASS RESOLVE
                ============================
                */

                $classId = null;

                if (!empty($data['Standard Id'])) {

                    $class = ClassRoom::where('class_code', trim($data['Standard Id']))->first();

                    if (!$class) {

                        $this->failedRows++;

                        $this->logError($data, "Standard Id not exist");

                        continue;
                    }

                    $classId = $class->id;
                }

                /*
                ============================
                BATCH RESOLVE
                ============================
                */

                $batchIds = [];

                if (!empty($data['Assigned Batch Ids'])) {

                    $batchUids = explode(',', $data['Assigned Batch Ids']);

                    foreach ($batchUids as $uid) {

                        $batch = Batch::where('batch_uid', trim($uid))->first();

                        if (!$batch) {

                            $this->failedRows++;

                            $this->logError($data, "Batch UID {$uid} not exist");

                            continue 2;
                        }

                        $batchIds[] = $batch->id;
                    }
                }

                DB::transaction(function () use ($data, $classId, $batchIds) {

                    $nameParts = explode(' ', $data['Student Name*']);

                    $firstName = $nameParts[0] ?? '';
                    $lastName = $nameParts[1] ?? '';

                    $password = Str::random(8);

                    $user = User::create([
                        'uid' => 'ST' . rand(10000, 99999),
                        'name' => $data['Student Name*'],
                        'email' => $data['Student Email'] ?? Str::random(6) . '@temp.com',
                        'password' => Hash::make($password),
                        'role' => 'student'
                    ]);

                    $student = Student::create([
                        'user_id' => $user->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'class' => $classId,
                        'admission_no' => $data['Register Number'] ?? 'ADM' . rand(10000, 99999),
                        'admission_date' => $data['Date of Admission'] ?? now()
                    ]);

                    StudentDetail::create([
                        'student_id' => $student->id,
                        'phone' => $data['Student Phone*'],
                        'email' => $data['Student Email'] ?? null,
                        'gender' => $data['Gender'] ?? null,
                        'dob' => $data['Date of Birth'] ?? null,
                        'address' => $data['Student Current Address'] ?? null
                    ]);

                    foreach ($batchIds as $batchId) {

                        BatchStudent::create([
                            'student_id' => $student->id,
                            'batch_id' => $batchId,
                            'assigned_date' => $data['Batch Joining Date'] ?? now(),
                            'is_active' => 1
                        ]);
                    }

                });

                $this->successRows++;

            } catch (\Throwable $e) {

                $this->failedRows++;

                $this->logError($data, $e->getMessage());
            }
        }

        /*
        ============================
        UPDATE FILE SUMMARY
        ============================
        */

        StudentImportFile::where('id', $this->fileId)->update([
            'total_rows' => $this->totalRows,
            'success_rows' => $this->successRows,
            'failed_rows' => $this->failedRows
        ]);
    }

    private function logError($data, $message)
    {
        StudentImportLog::create([
            'file_id' => $this->fileId,
            'raw_data' => [
                'data' => $data,
                'error' => $message
            ]
        ]);
    }


}
