<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\InventorySale;
use App\Models\InventorySaleItem;
use App\Models\InventorySalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventorySaleController extends Controller
{
    /* =====================================================
       ➕ STORE SALE / ALLOCATION
    ===================================================== */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:student,staff,teacher,Teacher,Student,Staff',
            'user_id' => 'required|integer',
            'reference_no' => 'nullable|string|max:150',
            'date' => 'required|date',
            'payment_status' => 'required|in:paid,unpaid',
            'description' => 'nullable|string',
            'bill_copy' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            'items' => 'required|array|min:1',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.sale_type' => 'required|in:free,paid',
            'items.*.sale_price' => 'required|numeric|min:0',
            'items.*.units' => 'required|integer|min:1',
            'items.*.tax_percentage' => 'required|numeric|min:0|max:100',

            'payment.amount' => 'required_if:payment_status,paid|numeric|min:1',
            'payment.payment_method' => 'required_if:payment_status,paid|string',
            'payment.date' => 'required_if:payment_status,paid|date',
            'payment.reference_no' => 'nullable|string',
            'payment.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        DB::beginTransaction();

        try {
            /* 📂 Upload bill */
            $billPath = null;
            if ($request->hasFile('bill_copy')) {
                $billPath = $request->file('bill_copy')
                    ->store('inventory/sales/bills', 'public');
            }

            $sale = InventorySale::create([
                'role' => $validated['role'],
                'user_id' => $validated['user_id'],
                'reference_no' => $validated['reference_no'] ?? null,
                'date' => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'description' => $validated['description'] ?? null,
                'bill_copy' => $billPath,
                'total_amount' => 0,
            ]);

            $total = 0;

            /* 📦 Items */
            foreach ($validated['items'] as $row) {

                $item = InventoryItem::findOrFail($row['inventory_item_id']);

                if ($item->quantity < $row['units']) {
                    throw new \Exception(
                        "Insufficient stock for item ID {$item->id}"
                    );
                }

                $subTotal = ($row['sale_type'] === 'free')
                    ? 0
                    : ($row['sale_price'] * $row['units']);

                InventorySaleItem::create([
                    'inventory_sale_id' => $sale->id,
                    'inventory_item_id' => $row['inventory_item_id'],
                    'sale_type' => $row['sale_type'],
                    'sale_price' => $row['sale_price'],
                    'units' => $row['units'],
                    'tax_percentage' => $row['tax_percentage'],
                    'sub_total' => $subTotal,
                ]);

                /* 🔻 Reduce Inventory */
                $item->decrement('quantity', $row['units']);

                $total += $subTotal;
            }

            $sale->update(['total_amount' => $total]);

            /* 💰 Payment (Only If Paid) */
            if ($validated['payment_status'] === 'paid') {

                $receiptPath = null;
                if ($request->hasFile('payment.receipt')) {
                    $receiptPath = $request->file('payment.receipt')
                        ->store('inventory/sales/receipts', 'public');
                }

                InventorySalePayment::create([
                    'inventory_sale_id' => $sale->id,
                    'amount' => $validated['payment']['amount'],
                    'payment_method' => $validated['payment']['payment_method'],
                    'date' => $validated['payment']['date'],
                    'reference_no' => $validated['payment']['reference_no'] ?? null,
                    'receipt' => $receiptPath,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory allocated successfully.',
                'data' => $sale->load('items','payment'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $sales = InventorySale::with([
                'staff:id,first_name,last_name,designation',
            ])
            ->latest()
            ->get()
            ->map(function ($sale) {
                return [
                    'id'             => $sale->id,
                    'reference_no'   => $sale->reference_no,
                    'date'           => $sale->date,
                    'designation'    => $sale->role,
                    'staff'          => $sale->staff
                                        ? trim($sale->staff->first_name.' '.$sale->staff->last_name)
                                        : null,
                    'payment_status' => $sale->payment_status,
                    'total_amount'   => $sale->total_amount,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data'   => $sales,
        ]);
    }

    public function show($id)
    {
        $sale = InventorySale::with([
                'staff',
                'items.item:id,item_name',
                'payment',
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $sale->id,
                'reference_no'   => $sale->reference_no,
                'date'           => $sale->date,
                'payment_status' => $sale->payment_status,
                'description'    => $sale->description,
                'bill_copy'      => $sale->bill_copy,
                'total_amount'   => $sale->total_amount,

                'staff' => [
                    'id'          => $sale->staff?->id,
                    'name'        => $sale->staff
                                        ? trim($sale->staff->first_name.' '.$sale->staff->last_name)
                                        : null,
                    'designation' => $sale->staff?->designation,
                    'phone'       => $sale->staff?->contact['phone'] ?? null,
                    'email'       => $sale->staff?->contact['email'] ?? null,
                ],

                'items' => $sale->items->map(function ($i) {
                    return [
                        'id'             => $i->id,
                        'item_name'      => $i->item?->item_name,
                        'sale_type'      => $i->sale_type,
                        'sale_price'     => $i->sale_price,
                        'units'          => $i->units,
                        'tax_percentage' => $i->tax_percentage,
                        'sub_total'      => $i->sub_total,
                    ];
                }),

                'payments' => $sale->payments,
            ],
        ]);
    }


}
