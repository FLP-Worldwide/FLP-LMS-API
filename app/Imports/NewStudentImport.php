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
