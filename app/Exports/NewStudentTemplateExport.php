<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class NewStudentTemplateExport implements FromArray
{
    public function array(): array
    {
        return [

            [
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
            ],

            // Example Row
            [
                'Rahul Sharma',
                'Male',
                'Jaipur Rajasthan',
                'rahul@example.com',
                '+91',
                '9876543210',
                '',
                '2008-05-10',
                '2024-04-01',
                'Mahesh Sharma',
                '',
                '9876543211',
                '',
                '',
                'Sita Sharma',
                '',
                '',
                '',
                '',
                '',
                '1',
                '2024-04-01',
                'ABC School',
                '',
                '3',
                'ADM1001',
                'Jaipur',
                'O+',
                '',
                'Indian',
                'Hindi',
                '302001',
                '',
                '',
                '',
                'Yes'
            ]
        ];
    }
}
