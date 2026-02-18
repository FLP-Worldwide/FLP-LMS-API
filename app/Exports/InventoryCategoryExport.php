<?php

namespace App\Exports;

use App\Models\InventoryCategory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryCategoryExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventoryCategory::query()
            ->select(
                'id',
                'category_name',
                'description',
                'created_at'
            )
            ->get()
            ->map(function ($category) {
                return [
                    'ID'          => $category->id,
                    'Category Name' => $category->category_name,
                    'Description' => $category->description,
                    'Created At'  => $category->created_at->format('Y-m-d'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Category Name',
            'Description',
            'Created At',
        ];
    }
}
