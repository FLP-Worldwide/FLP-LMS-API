<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;

class NewStudentTemplateExport implements WithHeadings
{
    public function headings(): array
    {
        return [
            'Student Name*',
            'Gender',
            'Student Current Address',
            'Student Email',
            'Country Calling Code',
            'Student Phone*',
            'Student Adhar Card',
            'Date of Birth',
            'Date of Admission',
            'Parent Name',
            'Parent Email',
            'Parent Phone',
            'Parent Adhar Card',
            'Parent Profession',
            'Mother Name',
            'Mother Contact',
            'Mother Email',
            'Guardian Name',
            'Guardian Email',
            'Guardian Phone',
            'Assigned Batch Ids',
            'Batch Joining Date',
            'Institute/School Name',
            'Remarks',
            'Standard Id',
            'Register Number',
            'Birth Place',
            'Blood Group',
            'Category',
            'Nationality',
            'Mother Tongue',
            'Pin Code',
            'Educational Group',
            'visit date',
            'centre vist',
            'default*'
        ];
    }
}
