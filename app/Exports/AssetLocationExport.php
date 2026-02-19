<?php

namespace App\Exports;

use App\Models\AssetLocation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetLocationExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return AssetLocation::latest()->get();
    }

    public function headings(): array
    {
        return [
            'Location Name',
            'Code',
            'Description',
            'Status',
            'Created At',
        ];
    }

    public function map($location): array
    {
        return [
            $location->name,
            $location->code ?? '-',
            $location->description ?? '-',
            $location->is_active ? 'Active' : 'Inactive',
            $location->created_at?->format('Y-m-d'),
        ];
    }
}
