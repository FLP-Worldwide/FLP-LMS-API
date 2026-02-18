<?php

namespace App\Exports;

use App\Models\InventorySale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventorySaleExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return InventorySale::with(['staff', 'payment'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'Reference No',
            'User Name',
            'Role',
            'Sale Date',
            'Total Amount',
            'Paid Amount',
            'Due Amount',
        ];
    }

    public function map($sale): array
    {
        $paidAmount = $sale->payment?->amount ?? 0;
        $dueAmount = $sale->total_amount - $paidAmount;

        return [
            $sale->reference_no,
            $sale->staff?->first_name . ' ' . $sale->staff?->last_name,
            $sale->role,
            $sale->date,
            $sale->total_amount,
            $paidAmount,
            $dueAmount,
        ];
    }
}
