<?php

namespace App\Exports;

use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TopicTemplateExport implements FromCollection, WithHeadings
{
    protected $classId;
    protected $subjectId;

    public function __construct($classId, $subjectId)
    {
        $this->classId   = $classId;
        $this->subjectId = $subjectId;
    }

    public function headings(): array
    {
        return [
            'Class*',
            'Subject*',
            'Topic*',
            'SubTopic',
            'SubTopic 1',
            'SubTopic 2',
            'SubTopic 3',
            'SubTopic 4',
            'SubTopic 5',
            'SubTopic 6',
            'SubTopic 7',
            'SubTopic 8',
            'SubTopic 9',
            'SubTopic 10',
        ];
    }

    public function collection()
    {
        $class   = ClassRoom::findOrFail($this->classId);
        $subject = Subject::findOrFail($this->subjectId);

        $rows = [];

        for ($i = 0; $i < 10; $i++) {
            $rows[] = [
                'Class*'      => $class->name,      // ✅ class name
                'Subject*'    => $subject->name,    // ✅ subject name
                'Topic*'      => '',
                'SubTopic'    => '',
                'SubTopic 1'  => '',
                'SubTopic 2'  => '',
                'SubTopic 3'  => '',
                'SubTopic 4'  => '',
                'SubTopic 5'  => '',
                'SubTopic 6'  => '',
                'SubTopic 7'  => '',
                'SubTopic 8'  => '',
                'SubTopic 9'  => '',
                'SubTopic 10' => '',
            ];
        }

        return new Collection($rows);
    }
}
