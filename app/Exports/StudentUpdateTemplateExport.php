<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\BatchStudent;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentUpdateTemplateExport implements FromCollection, WithHeadings
{

    protected $fields;
    protected $courseIds;
    protected $batchIds;
    protected $withoutBatch;

    public function __construct($fields,$courseIds,$batchIds,$withoutBatch)
    {
        $this->fields = $fields;
        $this->courseIds = $courseIds;
        $this->batchIds = $batchIds;
        $this->withoutBatch = $withoutBatch;
    }

    /*
    ===============================
    HEADINGS
    ===============================
    */

    public function headings(): array
    {
        return array_merge(['Student ID'], $this->fields);
    }

    /*
    ===============================
    DATA
    ===============================
    */

    public function collection()
    {

        $query = Student::with(['details','batches','course']);

        /*
        ===============================
        FILTER BY COURSE
        ===============================
        */

        if(!empty($this->courseIds)){
            $query->whereIn('class',$this->courseIds);
        }

        /*
        ===============================
        FILTER BY BATCH
        ===============================
        */

        if(!empty($this->batchIds)){

            $studentIds = BatchStudent::whereIn('batch_id',$this->batchIds)
                ->pluck('student_id');

            $query->whereIn('id',$studentIds);
        }

        /*
        ===============================
        WITHOUT BATCH
        ===============================
        */

        // if($this->withoutBatch){

        //     $studentIds = BatchStudent::pluck('student_id');

        //     $query->whereNotIn('id',$studentIds);
        // }

        if($this->withoutBatch){
            // Do nothing — export all students
        }

        $students = $query->get();

        return $students->map(function($student){

            $row = [];

            $row['Student ID'] = $student->admission_no;

            foreach($this->fields as $field){

                switch($field){

                    case 'Student Name':
                        $row[$field] = $student->first_name.' '.$student->last_name;
                        break;

                    case 'Student Phone':
                        $row[$field] = $student->detail->phone ?? '-';
                        break;

                    case 'Gender':
                        $row[$field] = $student->detail->gender ?? '-';
                        break;

                    case 'Mother Name':
                        $row[$field] = $student->detail->mother_name ?? '-';
                        break;

                    case 'Parent Phone':
                        $row[$field] = $student->detail->parent_phone ?? '-';
                        break;

                    case 'Batch':
                        $row[$field] = optional($student->batches->first())->name ?? '-';
                        break;

                    case 'Course':
                        $row[$field] = $student->course->name ?? '-';
                        break;

                    default:
                        $row[$field] = '-';
                }

            }

            return $row;

        });

    }

}
