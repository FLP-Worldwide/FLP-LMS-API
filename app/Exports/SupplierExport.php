<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Supplier::with(['categories', 'assetItems'])
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Company Name',
            'Contact Person',
            'Mobile',
            'Email',
            'Address',
            'Categories',
            'Total Asset Items',
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->company_name,
            $supplier->contact_person,
            $supplier->mobile,
            $supplier->email ?? '-',
            $supplier->address,
            $supplier->categories->pluck('name')->implode(', '),
            $supplier->assetItems->count(),
        ];
    }
}
