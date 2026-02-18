<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [

            // 🔴 REQUIRED FIELDS
            'first_name*',
            'class*',
            'status* (active,inactive,passed,left)',

            // 🟢 OPTIONAL FIELDS
            'last_name',
            'section',
            'admission_date (YYYY-MM-DD)',

            'dob (YYYY-MM-DD)',
            'gender (male,female,other)',
            'blood_group',
            'email',
            'phone',
            'father_name',
            'mother_name',
            'parent_phone',
            'address',
            'city',
            'state',
            'medical_info',
        ];
    }
}
