<?php

namespace App\Exports;

use App\Models\SalaryTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalaryTemplateExport implements FromCollection, WithHeadings, WithMapping
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function collection()
    {
        return SalaryTemplate::where('type', $this->type)->get();
    }

    public function headings(): array
    {
        if ($this->type === 'hourly') {
            return [
                'Template Name',
                'Type',
                'Hourly Rate',
            ];
        }

        return [
            'Template Name',
            'Type',
            'Basic Pay',
        ];
    }

    public function map($template): array
    {
        $salaryData = is_array($template->salary)
            ? $template->salary
            : json_decode($template->salary ?? '{}', true);

        if ($this->type === 'hourly') {
            return [
                $template->name,
                $template->type,
                $salaryData['hourly_rate'] ?? 0,
            ];
        }

        return [
            $template->name,
            $template->type,
            $salaryData['basic'] ?? 0,
        ];
    }

}
