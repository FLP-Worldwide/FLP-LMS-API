<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\Payer;
use App\Models\Payee;
use Illuminate\Http\Request;

class FinanceAccountController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_for' => 'required|in:payer,payee',
            'accountable_id' => 'required|integer',

            'account_type' => 'required|in:Cash,Bank,UPI,Cheque',
            'account_name' => 'nullable|string|max:100',

            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'ifsc' => 'nullable|string|max:20',

            'upi_id' => 'nullable|string|max:100',
        ]);

        // 🔎 Resolve morph model
        $accountableType = match ($validated['account_for']) {
            'payer' => \App\Models\Payer::class,
            'payee' => \App\Models\Payee::class,
        };

        $account = FinanceAccount::create([
            'accountable_type' => $accountableType,
            'accountable_id'   => $validated['accountable_id'],

            'account_type' => $validated['account_type'],
            'account_name' => $validated['account_name'] ?? null,

            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'ifsc' => $validated['ifsc'] ?? null,

            'upi_id' => $validated['upi_id'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Account saved successfully.',
            'data' => $account,
        ], 201);
    }

    /**
     * Fetch accounts (filterable)
     */
    public function index(Request $request)
    {
        $query = FinanceAccount::query()->with('accountable');

        if ($request->account_for) {
            $query->where('accountable_type', match ($request->account_for) {
                'payer' => \App\Models\Payer::class,
                'payee' => \App\Models\Payee::class,
            });
        }

        if ($request->accountable_id) {
            $query->where('accountable_id', $request->accountable_id);
        }

        if ($request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    public function show(string $type, int $id)
    {
        // ✅ Resolve morph class safely
        $accountableType = match ($type) {
            'payee' => \App\Models\Payee::class,
            'payer' => \App\Models\Payer::class,
            default => null,
        };

        if (!$accountableType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid account type. Allowed: payee, payer',
            ], 422);
        }

        $accounts = FinanceAccount::where('accountable_type', $accountableType)
            ->where('accountable_id', $id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'type' => $type,
            'accountable_id' => $id,
            'data' => $accounts,
        ]);
    }
}
