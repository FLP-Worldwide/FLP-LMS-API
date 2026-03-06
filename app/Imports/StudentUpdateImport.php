<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\Batch;
use App\Models\BatchStudent;
use App\Models\StudentImportLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class StudentUpdateImport implements ToCollection
{
    public function collection(Collection $rows)
    {

        if ($rows->count() < 2) {
            return;
        }

        $header = $rows->first()->map(function ($h) {
            return trim($h);
        });

        $rows->shift();

        foreach ($rows as $row) {

            $data = [];

            foreach ($header as $index => $column) {
                $data[$column] = $row[$index] ?? '-';
            }

            /*
            ======================================
            STUDENT ID (MANDATORY)
            ======================================
            */

            if (empty($data['Student ID'])) {
                continue;
            }

            $student = Student::with('details')
                ->where('admission_no', $data['Student ID'])
                ->first();

            if (!$student) {
                continue;
            }

            DB::transaction(function () use ($student, $data) {

                /*
                ======================================
                UPDATE STUDENT NAME
                ======================================
                */

                if (!empty($data['Student Name']) && $data['Student Name'] != '-') {

                    $parts = explode(' ', $data['Student Name']);

                    $student->update([
                        'first_name' => $parts[0] ?? '',
                        'last_name'  => $parts[1] ?? ''
                    ]);
                }

                /*
                ======================================
                UPDATE STUDENT DETAILS
                ======================================
                */

                $details = $student->details ?? new StudentDetail([
                    'student_id' => $student->id
                ]);

                if (isset($data['Student Phone']) && $data['Student Phone'] != '-') {
                    $details->phone = $data['Student Phone'];
                }

                if (isset($data['Gender']) && $data['Gender'] != '-') {
                    $details->gender = $data['Gender'];
                }

                if (isset($data['Mother Name']) && $data['Mother Name'] != '-') {
                    $details->mother_name = $data['Mother Name'];
                }

                if (isset($data['Parent Phone']) && $data['Parent Phone'] != '-') {
                    $details->parent_phone = $data['Parent Phone'];
                }

                if (isset($data['City']) && $data['City'] != '-') {
                    $details->city = $data['City'];
                }

                if (isset($data['State']) && $data['State'] != '-') {
                    $details->state = $data['State'];
                }

                if (isset($data['DOB']) && $data['DOB'] != '-') {
                    $details->dob = $data['DOB'];
                }

                $details->save();

                /*
                ======================================
                UPDATE BATCH (IF PROVIDED)
                ======================================
                */

                if (!empty($data['Batch']) && $data['Batch'] != '-') {

                    $batch = Batch::where('name', $data['Batch'])
                        ->orWhere('batch_uid', $data['Batch'])
                        ->first();

                    if ($batch) {

                        BatchStudent::updateOrCreate(
                            [
                                'student_id' => $student->id
                            ],
                            [
                                'batch_id' => $batch->id,
                                'assigned_date' => now(),
                                'is_active' => 1
                            ]
                        );
                    }
                }

                /*
                ======================================
                SAVE RAW IMPORT JSON
                ======================================
                */

                StudentImportLog::create([
                    'student_id' => $student->id,
                    'raw_data'   => $data
                ]);

            });

        }
    }
}
