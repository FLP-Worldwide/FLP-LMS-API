<?php
namespace App\Http\Controllers\StaffManage;

use App\Http\Controllers\Controller;
use App\Models\LeaveCategory;
use Illuminate\Http\Request;

class LeaveController extends Controller
{

    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => LeaveCategory::latest()->get(),
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
        ]);

        $category = LeaveCategory::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave category created successfully.',
            'data' => $category,
        ], 201);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $category = LeaveCategory::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string',
        ]);

        $category->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Leave category updated successfully.',
            'data' => $category,
        ]);
    }

    // DELETE (SOFT)
    public function destroy($id)
    {
        LeaveCategory::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leave category deleted successfully.',
        ]);
    }

}
