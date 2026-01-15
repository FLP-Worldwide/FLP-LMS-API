<?php

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeesType;
use Illuminate\Http\Request;

class FeesTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => FeesType::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $feesType = FeesType::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Fees type created successfully',
            'data' => $feesType
        ]);
    }

    public function destroy($id)
    {
        FeesType::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Fees type deleted'
        ]);
    }
}
