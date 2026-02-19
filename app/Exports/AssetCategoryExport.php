<?php

namespace App\Exports;

use App\Models\AssetCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetCategoryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return AssetCategory::
            orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Category Name',
            'Code',

            'Description',
        ];
    }

    public function map($category): array
    {
        return [
            $category->name,
            $category->code ?? '-',
            $category->description ?? '-',
        ];
    }
}
