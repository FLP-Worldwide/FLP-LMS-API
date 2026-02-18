<?php

namespace App\Exports;

use App\Models\UserSalaryTemplate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserSalaryTemplateExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return UserSalaryTemplate::with(['user', 'salaryTemplate'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Template Name',
            'Salary Type',
            'Basic Pay',
            'Hourly Rate',
            'Is Active',
        ];
    }

    public function map($record): array
    {
        $template = $record->salaryTemplate;

        // Handle salary cast safely
        $salaryData = is_array($template?->salary)
            ? $template->salary
            : json_decode($template?->salary ?? '{}', true);

        return [
            $record->user?->name ?? '-',
            $template?->name ?? '-',
            $record->salary_type ?? '-',
            $salaryData['basic'] ?? 0,
            $salaryData['hourly_rate'] ?? 0,
            $record->is_active ? 'Yes' : 'No',
        ];
    }
}
