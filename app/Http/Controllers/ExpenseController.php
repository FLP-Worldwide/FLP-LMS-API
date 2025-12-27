<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\FinanceAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payee_id' => 'required|exists:payees,id',
            'finance_account_id' => 'required|exists:finance_accounts,id',
            'payment_date' => 'required|date',

            'transaction_id' => 'nullable|string|max:100',
            'cheque_no' => 'nullable|string|max:50',
            'remark' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string|max:100',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        $account = FinanceAccount::findOrFail($validated['finance_account_id']);

        DB::transaction(function () use ($validated, $account, &$expense) {

            $total = collect($validated['items'])
                ->sum(fn ($i) => $i['quantity'] * $i['amount']);

            $expense = Expense::create([
                'payee_id' => $validated['payee_id'],
                'finance_account_id' => $validated['finance_account_id'],
                'payment_date' => $validated['payment_date'],
                'payment_mode' => $account->account_type,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'cheque_no' => $validated['cheque_no'] ?? null,
                'total_amount' => $total,
                'remark' => $validated['remark'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $expense->items()->create($item);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Expense added successfully.',
            'data' => $expense->load('items', 'payee', 'account'),
        ], 201);
    }


    public function index(Request $request)
    {
            $query = Expense::with([
                'payee:id,name',
                'account:id,account_type,account_name',
                'items:id,expense_id,category,description,quantity,amount'
            ]);

            // 🔹 Date filters
            if ($request->from && $request->to) {
                $query->whereBetween('payment_date', [$request->from, $request->to]);
            } elseif ($request->payment_date) {
                $query->whereDate('payment_date', $request->payment_date);
            }

            // 🔹 Payee filter
            if ($request->payee_id) {
                $query->where('payee_id', $request->payee_id);
            }

            // 🔹 Finance account filter
            if ($request->finance_account_id) {
                $query->where('finance_account_id', $request->finance_account_id);
            }

            // 🔹 Payment mode filter (cash / bank / upi)
            if ($request->payment_mode) {
                $query->where('payment_mode', $request->payment_mode);
            }

            // 🔹 Category filter (from expense_items)
            if ($request->category) {
                $query->whereHas('items', function ($q) use ($request) {
                    $q->where('category', $request->category);
                });
            }

            $expenses = $query->latest()->get();

            return response()->json([
                'status' => 'success',
                'data' => $expenses->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'payment_date' => $expense->payment_date,
                        'payment_mode' => $expense->payment_mode,
                        'total_amount' => $expense->total_amount,
                        'remark' => $expense->remark,

                        'payee' => [
                            'id' => $expense->payee->id,
                            'name' => $expense->payee->name,
                        ],

                        'account' => [
                            'id' => $expense->account->id,
                            'type' => $expense->account->account_type,
                            'name' => $expense->account->account_name,
                        ],

                        'items' => $expense->items->map(function ($item) {
                            return [
                                'category' => $item->category,
                                'description' => $item->description,
                                'quantity' => $item->quantity,
                                'amount' => $item->amount,
                                'total' => $item->quantity * $item->amount,
                            ];
                        }),
                    ];
                }),
            ]);
    }


}
