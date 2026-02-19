<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PurchaseReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return Purchase::with([
                'supplier',
                'items.asset',
                'items.category'
            ])
            ->when($this->request->supplier_id, fn ($q) =>
                $q->where('supplier_id', $this->request->supplier_id)
            )
            ->when($this->request->from_date, fn ($q) =>
                $q->whereDate('purchase_date', '>=', $this->request->from_date)
            )
            ->when($this->request->to_date, fn ($q) =>
                $q->whereDate('purchase_date', '<=', $this->request->to_date)
            )
            ->latest()
            ->get()
            ->flatMap(function ($purchase) {
                return $purchase->items->map(function ($item) use ($purchase) {
                    return [
                        'purchase' => $purchase,
                        'item' => $item
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Purchase Date',
            'Invoice No',
            'Supplier',
            'Service Date',
            'Expiry Date',
            'Unit',
            'Purchased By',
            'Asset',
            'Category',
            'Quantity',
            'Price',
            'Line Total',
            'Purchase Total Amount',
        ];
    }

    public function map($row): array
    {
        $purchase = $row['purchase'];
        $item = $row['item'];

        return [
            $purchase->purchase_date,
            $purchase->invoice_no ?? '-',
            $purchase->supplier?->company_name ?? '-',
            $purchase->service_date ?? '-',
            $purchase->expiry_date ?? '-',
            $purchase->unit ?? '-',
            $purchase->purchased_by ?? '-',
            $item->asset?->name ?? '-',
            $item->category?->name ?? '-',
            $item->quantity,
            $item->price,
            $item->total,
            $purchase->total_amount,
        ];
    }
}
