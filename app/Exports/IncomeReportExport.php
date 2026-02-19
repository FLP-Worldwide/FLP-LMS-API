<?php

namespace App\Exports;

use App\Models\Income;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class IncomeReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Income::with(['payer', 'account']);

        // 🔹 Date Filters
        if ($this->request->filter === 'current_month') {
            $query->whereBetween('payment_date', [
                now()->startOfMonth(),
                now()
            ]);
        }

        if ($this->request->filter === 'till_date') {
            $query->whereDate('payment_date', '<=', now());
        }

        if ($this->request->from && $this->request->to) {
            $query->whereBetween('payment_date', [
                $this->request->from,
                $this->request->to
            ]);
        }

        // 🔹 Payer Filter
        if ($this->request->payer_id) {
            $query->where('payer_id', $this->request->payer_id);
        }

        // 🔹 Account Filter
        if ($this->request->finance_account_id) {
            $query->where('finance_account_id', $this->request->finance_account_id);
        }

        // 🔹 Payment Mode
        if ($this->request->payment_mode) {
            $query->where('payment_mode', $this->request->payment_mode);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Total Amount',
            'Account Name',
            'Payer Name',
            'Transaction ID',
            'Payment Mode',
        ];
    }

    public function map($income): array
    {
        return [
            $income->payment_date,
            $income->total_amount,
            $income->account?->account_name ?? '-',
            $income->payer?->name ?? '-',
            $income->transaction_id ?? '-',
            $income->payment_mode,
        ];
    }
}
