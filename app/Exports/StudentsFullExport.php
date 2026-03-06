<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsFullExport implements FromCollection, WithHeadings
{

    public function collection()
    {

        $students = Student::with([
            'details',
            'user',
            'batches',
            'course'
        ])->get();

        return $students->map(function ($student) {

            return [

                'Student ID' => $student->admission_no,

                'Student Name' => $student->first_name.' '.$student->last_name,

                'Student Email' => $student->details->email ?? '-',

                'Student Phone' => $student->details->phone ?? '-',

                'Gender' => $student->details->gender ?? '-',

                'DOB' => $student->details->dob ?? '-',

                'Admission Date' => $student->admission_date ?? '-',

                'Address' => $student->details->address ?? '-',

                'City' => $student->details->city ?? '-',

                'State' => $student->details->state ?? '-',

                'Country' => $student->details->country ?? '-',

                'Pin Code' => $student->details->pin_code ?? '-',

                'Blood Group' => $student->details->blood_group ?? '-',

                'Father Name' => $student->details->father_name ?? '-',

                'Parent Phone' => $student->details->parent_phone ?? '-',

                'Mother Name' => $student->details->mother_name ?? '-',

                'Mother Phone' => $student->details->mother_contact ?? '-',

                'Guardian Name' => $student->details->guardian_name ?? '-',

                'Guardian Phone' => $student->details->guardian_phone ?? '-',

                'Batch' => optional($student->batches->first())->name ?? '-',

                'Course' => $student->course->name ?? '-',

                'Register Number' => $student->roll_no ?? '-',

                'Birth Place' => $student->details->birth_place ?? '-',

                'Nationality' => $student->details->nationality ?? '-',

                'Mother Tongue' => $student->details->mother_tongue ?? '-',

                'Category' => $student->details->category ?? '-',

                'Educational Group' => $student->details->educational_group ?? '-',

                'Remarks' => $student->details->remarks ?? '-',

                'Created At' => $student->created_at

            ];

        });

    }


    public function headings(): array
    {

        return [

            'Student ID',
            'Student Name',
            'Student Email',
            'Student Phone',
            'Gender',
            'DOB',
            'Admission Date',
            'Address',
            'City',
            'State',
            'Country',
            'Pin Code',
            'Blood Group',
            'Father Name',
            'Parent Phone',
            'Mother Name',
            'Mother Phone',
            'Guardian Name',
            'Guardian Phone',
            'Batch',
            'Course',
            'Register Number',
            'Birth Place',
            'Nationality',
            'Mother Tongue',
            'Category',
            'Educational Group',
            'Remarks',
            'Created At'

        ];

    }

}
