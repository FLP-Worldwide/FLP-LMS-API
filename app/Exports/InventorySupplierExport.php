<?php

namespace App\Exports;

use App\Models\InventorySupplier;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventorySupplierExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return InventorySupplier::query()
            ->get()
            ->map(function ($supplier) {

                return [
                    'ID'              => $supplier->id,
                    'Company Name'    => $supplier->company,
                    'Email'           => $supplier->email,
                    'Mobile'          => $supplier->mobile,
                    'Contact Person'  => $supplier->supplier,
                    'Address'         => $supplier->address,

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
