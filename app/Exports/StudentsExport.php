<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentFee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $courseId;
    protected $batchId;
    protected $includeBatch;
    protected $includeCourse;
    protected $includeFees;
    protected $includeAttendance;

    public function __construct(
        $courseId = null,
        $batchId = null,
        $includeBatch = false,
        $includeCourse = false,
        $includeFees = false,
        $includeAttendance = false
    ) {
        $this->courseId = $courseId;
        $this->batchId = $batchId;
        $this->includeBatch = $includeBatch;
        $this->includeCourse = $includeCourse;
        $this->includeFees = $includeFees;
        $this->includeAttendance = $includeAttendance;
    }

    public function collection()
    {
        $query = Student::with(['course', 'batch']);

        if ($this->courseId) {
            $query->where('course_id', $this->courseId);
        }

        if ($this->batchId) {
            $query->where('batch_id', $this->batchId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'ID',
            'Roll No',
            'First Name',
            'Last Name',
            'Class',
            'Section',
            'Phone',
            'Email',
        ];

        if ($this->includeCourse) {
            $headings[] = 'Course Name';
        }

        if ($this->includeBatch) {
            $headings[] = 'Batch Name';
        }

        if ($this->includeFees) {
            $headings[] = 'Total Fees';
            $headings[] = 'Paid Fees';
            $headings[] = 'Due Fees';
        }

        if ($this->includeAttendance) {
            $headings[] = 'Attendance %';
        }

        return $headings;
    }

    public function map($student): array
    {
        $row = [
            $student->id,
            $student->roll_no,
            $student->first_name,
            $student->last_name,
            $student->class,
            $student->section,
            $student->phone,
            $student->email,
        ];

        if ($this->includeCourse) {
            $row[] = optional($student->course)->name;
        }

        if ($this->includeBatch) {
            $row[] = optional($student->batch)->name;
        }

        if ($this->includeFees) {

            $totalFees = StudentFee::where('student_id', $student->id)->sum('total_amount');
            $paidFees  = StudentFee::where('student_id', $student->id)->sum('paid_amount');
            $dueFees   = $totalFees - $paidFees;

            $row[] = $totalFees;
            $row[] = $paidFees;
            $row[] = $dueFees;
        }

        if ($this->includeAttendance) {

            $totalDays = StudentAttendance::where('student_id', $student->id)->count();
            $presentDays = StudentAttendance::where('student_id', $student->id)
                ->where('status', 'P')
                ->count();

            $percentage = $totalDays > 0
                ? round(($presentDays / $totalDays) * 100, 2)
                : 0;

            $row[] = $percentage . '%';
        }

        return $row;
    }
}
