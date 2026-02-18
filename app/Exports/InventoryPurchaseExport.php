<?php

namespace App\Exports;

use App\Models\InventoryPurchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryPurchaseExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return InventoryPurchase::with(['supplier', 'payments'])
            ->get();
    }

    public function headings(): array
    {
        return [
            'Reference No',
            'Supplier Name',
            'Company Name',
            'Purchase Date',
            'Total Amount',
            'Paid Amount',
            'Balance Amount',
        ];
    }

    public function map($purchase): array
    {
        $paidAmount = $purchase->payments->sum('amount');
        $balance = $purchase->total_amount - $paidAmount;

        return [
            $purchase->reference_no,
            $purchase->supplier?->supplier ?? '-',
            $purchase->supplier?->company ?? '-',
            $purchase->date,
            $purchase->total_amount,
            $paidAmount,
            $balance,
        ];
    }
}
