<?php

namespace App\Exports;

use App\Models\Teacher;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeacherTemplateExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Teacher::with(['detail','user'])
            ->get()
            ->map(function ($teacher) {

                return [
                    'Teacher Name' => $teacher->first_name.' '.$teacher->last_name,
                    'Contact No.' => $teacher->detail->phone ?? null,
                    'Email ID' => $teacher->detail->email ?? $teacher->user->email,
                    'Date of Joining' => $teacher->joining_date,
                    'DOB' => $teacher->detail->dob ?? null,
                    'Alternate Contact No.' => null,
                    'Department' => $teacher->department,
                    'Designation' => $teacher->designation,
                    'Address' => $teacher->detail->address ?? null,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Teacher Name',
            'Contact No.',
            'Email ID',
            'Date of Joining',
            'DOB',
            'Alternate Contact No.',
            'Department',
            'Designation',
            'Address',
        ];
    }
}
