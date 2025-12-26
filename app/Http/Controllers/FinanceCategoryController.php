<?php

namespace App\Http\Controllers;

use App\Models\FinanceCategory;
use Illuminate\Http\Request;

class FinanceCategoryController extends Controller
{
    // All Categories
    public function index() {
        return response()->json(FinanceCategory::all(),200);
    }

    // Create
    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|unique:finance_categories,name',
            'type' => 'required|in:Income,Expense'
        ]);

        $category = FinanceCategory::create($request->all());
        return response()->json(['message'=>'Category created','data'=>$category],201);
    }

    // Show
    public function show($id) {
        return FinanceCategory::findOrFail($id);
    }

    // Update
    public function update(Request $request,$id) {
        $category = FinanceCategory::findOrFail($id);
        $category->update($request->all());

        return response()->json(['message'=>'Category updated','data'=>$category],200);
    }

    // Delete
    public function destroy($id) {
        FinanceCategory::findOrFail($id)->delete();
        return response()->json(['message'=>'Category deleted'],200);
    }
}
