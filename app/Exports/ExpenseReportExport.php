<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpenseReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Expense::with(['payee', 'account']);

        // 🔹 Apply same filters as index()

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

        if ($this->request->payee_id) {
            $query->where('payee_id', $this->request->payee_id);
        }

        if ($this->request->finance_account_id) {
            $query->where('finance_account_id', $this->request->finance_account_id);
        }

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
            'Payee Name',
            'Transaction ID',
            'Payment Mode',
        ];
    }

    public function map($expense): array
    {
        return [
            $expense->payment_date,
            $expense->total_amount,
            $expense->account?->account_name ?? '-',
            $expense->payee?->name ?? '-',
            $expense->transaction_id ?? '-',
            $expense->payment_mode,
        ];
    }
}
