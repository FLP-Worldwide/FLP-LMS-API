<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\FinanceAccount;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{

    public function index(Request $request)
{
    $query = Income::with([
        'payer:id,name',
        'account:id,account_type,account_name',
        'items:id,income_id,category,description,quantity,amount'
    ]);

    // 🔹 Date filters
    if ($request->from && $request->to) {
        $query->whereBetween('payment_date', [$request->from, $request->to]);
    } elseif ($request->payment_date) {
        $query->whereDate('payment_date', $request->payment_date);
    }

    // 🔹 Payer filter
    if ($request->payer_id) {
        $query->where('payer_id', $request->payer_id);
    }

    // 🔹 Finance account filter
    if ($request->finance_account_id) {
        $query->where('finance_account_id', $request->finance_account_id);
    }

    // 🔹 Payment mode filter
    if ($request->payment_mode) {
        $query->where('payment_mode', $request->payment_mode);
    }

    // 🔹 Category filter (from income_items)
    if ($request->category) {
        $query->whereHas('items', function ($q) use ($request) {
            $q->where('category', $request->category);
        });
    }

    $incomes = $query->latest()->get();

    return response()->json([
        'status' => 'success',
        'data' => $incomes->map(function ($income) {
            return [
                'id' => $income->id,
                'payment_date' => $income->payment_date,
                'payment_mode' => $income->payment_mode,
                'total_amount' => $income->total_amount,
                'remark' => $income->remark,

                'payer' => [
                    'id' => $income->payer->id,
                    'name' => $income->payer->name,
                ],

                'account' => [
                    'id' => $income->account->id,
                    'type' => $income->account->account_type,
                    'name' => $income->account->account_name,
                ],

                'items' => $income->items->map(function ($item) {
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


public function store(Request $request)
{
    $validated = $request->validate([
        'payer_id' => 'required|exists:payers,id',
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

    DB::transaction(function () use ($validated, $account, &$income) {

        $total = collect($validated['items'])
            ->sum(fn ($i) => $i['quantity'] * $i['amount']);

        $income = Income::create([
            'payer_id' => $validated['payer_id'],
            'finance_account_id' => $validated['finance_account_id'],
            'payment_date' => $validated['payment_date'],
            'payment_mode' => $account->account_type,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'cheque_no' => $validated['cheque_no'] ?? null,
            'total_amount' => $total,
            'remark' => $validated['remark'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $income->items()->create($item);
        }
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Income added successfully.',
        'data' => $income->load('items', 'payer', 'account'),
    ], 201);
}

}
