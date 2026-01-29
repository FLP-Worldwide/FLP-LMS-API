<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SalaryTemplate;
use Illuminate\Http\Request;

class SalaryTemplateController extends Controller
{
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'type' => 'required|in:monthly,hourly',
            'salary' => 'required|array',
        ];

        // Monthly specific rules
        if ($request->type === 'monthly') {
            $rules['salary.basic'] = 'required|numeric|min:0';
            $rules['allowances'] = 'nullable|array';
            $rules['deductions'] = 'nullable|array';
            $rules['summary'] = 'nullable|array';
        }

        // Hourly specific rules
        if ($request->type === 'hourly') {
            $rules['salary.hourly_rate'] = 'required|numeric|min:0';
        }

        $data = $request->validate($rules);

        $template = SalaryTemplate::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Salary template created successfully',
            'data' => $template,
        ], 201);
    }

    public function index(Request $request)
    {
        $query = SalaryTemplate::query();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => SalaryTemplate::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = SalaryTemplate::findOrFail($id);

        $template->update($request->only([
            'name',
            'salary',
            'allowances',
            'deductions',
            'summary',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Salary template updated successfully',
            'data' => $template,
        ]);
    }

    public function destroy($id)
    {
        SalaryTemplate::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Salary template deleted successfully',
        ]);
    }




}
