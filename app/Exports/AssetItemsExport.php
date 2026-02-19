<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetItemsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Asset::with(['location', 'category'])
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Asset Name',
            'Code',
            'Category',
            'Location',
            'Quantity',
            'Condition',
            'Description',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->name,
            $asset->code ?? '-',
            $asset->category?->name ?? '-',
            $asset->location?->name ?? '-',
            $asset->quantity,
            $asset->condition,
            $asset->description ?? '-',
        ];
    }
}
