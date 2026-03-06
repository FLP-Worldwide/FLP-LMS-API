<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromArray;

class StudentQuickImportTemplateExport implements FromArray, WithHeadings
{
    protected $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    /*
    =====================================
    HEADERS (DYNAMIC)
    =====================================
    */

    public function headings(): array
    {
        return $this->fields;
    }

    /*
    =====================================
    EMPTY ROW (OPTIONAL SAMPLE)
    =====================================
    */

    public function array(): array
    {
        return [
            array_fill(0, count($this->fields), '')
        ];
    }

}
