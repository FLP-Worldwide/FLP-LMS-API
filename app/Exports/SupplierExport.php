<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Supplier::with('categories')
            ->get()
            ->map(function ($supplier) {

                return [
                    'ID'              => $supplier->id,
                    'Company Name'    => $supplier->company_name,
                    'Email'           => $supplier->email,
                    'Mobile'          => $supplier->mobile,
                    'Contact Person'  => $supplier->contact_person,
                    'Address'         => $supplier->address,
                    'Categories'      => $supplier->categories
                        ? $supplier->categories->pluck('name')->implode(', ')
                        : null,
                    'Status'          => $supplier->is_active ? 'Active' : 'Inactive',
                    'Created At'      => $supplier->created_at?->format('Y-m-d'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Company Name',
            'Email',
            'Mobile',
            'Contact Person',
            'Address',
            'Categories',
            'Status',
            'Created At',
        ];
    }
}
