<?php

namespace App\Imports;

use App\Models\Topic;
use App\Models\SubTopic;
use App\Models\ClassRoom;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TopicImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {

            // ✅ Skip fully empty rows
            if (empty($row['topic'])) {
                continue;
            }

            // ✅ Get class & subject by name
            $class = ClassRoom::where('name', $row['class'])->first();
            $subject = Subject::where('name', $row['subject'])->first();

            if (!$class || !$subject) {
                continue;
            }

            // ✅ Avoid duplicate topic
            $topic = Topic::firstOrCreate([
                'class_id'   => $class->id,
                'subject_id' => $subject->id,
                'name'       => $row['topic'],
            ], [
                'is_active' => 1
            ]);

            // 🔥 Collect all subtopic columns
            $subtopicColumns = [
                'subtopic',
                'subtopic_1',
                'subtopic_2',
                'subtopic_3',
                'subtopic_4',
                'subtopic_5',
                'subtopic_6',
                'subtopic_7',
                'subtopic_8',
                'subtopic_9',
                'subtopic_10',
            ];

            foreach ($subtopicColumns as $column) {

                if (!empty($row[$column])) {

                    // ✅ Avoid duplicate subtopic
                    SubTopic::firstOrCreate([
                        'topic_id' => $topic->id,
                        'name'     => $row[$column],
                    ], [
                        'is_active' => 1
                    ]);
                }
            }
        }
    }
}
