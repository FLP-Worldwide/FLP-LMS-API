<?php

namespace App\Exports;

use App\Models\AssetAssignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetAssignmentExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return AssetAssignment::with([
                'asset',
                'category',
                'checkoutBy'
            ])
            ->when($this->request->role, fn ($q) =>
                $q->where('role', $this->request->role)
            )
            ->when($this->request->asset_id, fn ($q) =>
                $q->where('asset_id', $this->request->asset_id)
            )
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'Asset Name',
            'Category',
            'Role',
            'Assigned To',
            'Quantity',
            'Assign Date',
            'Due Date',
            'Return Date',
            'Status',
            'Note',
        ];
    }

    public function map($assignment): array
    {
        $status = $assignment->return_date ? 'Returned' : 'Pending';

        return [
            $assignment->asset?->name ?? '-',
            $assignment->category?->name ?? '-',
            $assignment->role,
            $assignment->checkoutBy?->name ?? '-',
            $assignment->quantity,
            $assignment->assign_date,
            $assignment->due_date ?? '-',
            $assignment->return_date ?? '-',
            $status,
            $assignment->note ?? '-',
        ];
    }
}
