<?php

namespace App\Exports;

use App\Models\InventoryItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InventoryItemExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $categoryId;

    public function __construct($categoryId = null)
    {
        $this->categoryId = $categoryId;
    }

    public function collection()
    {
        $query = InventoryItem::with('category:id,category_name');

        if ($this->categoryId) {
            $query->where('inventory_category_id', $this->categoryId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Item ID',
            'Category',
            'Item Name',
            'Buying Price',
            'Sale Price',
            'Tax (%)',
            'Low Stock Indicator',
            'Quantity',
            'Status',
            'Description',
        ];
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->category?->category_name,
            $item->item_name,
            $item->buying_price,
            $item->sale_price,
            $item->tax_percentage,
            $item->low_stock_indicator,
            $item->quantity,
            $item->is_active ? 'Active' : 'Inactive',
            $item->description,
        ];
    }
}
