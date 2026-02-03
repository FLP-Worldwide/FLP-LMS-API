<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\CustomField;
use Illuminate\Http\Request;

class CustomFieldController extends Controller
{
    // 📌 LIST
    public function index()
    {
        $fields = CustomField::orderBy('sequence')->get();

        return response()->json([
            'status' => 'success',
            'data' => $fields
        ]);
    }

    // 📌 CREATE
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $field = CustomField::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Custom field created successfully',
            'data' => $field
        ]);
    }

    // 📌 SHOW (single)
    public function show(CustomField $customField)
    {
        return response()->json([
            'status' => 'success',
            'data' => $customField
        ]);
    }

    // 📌 UPDATE
    public function update(Request $request, CustomField $customField)
    {
        $validated = $request->validate($this->rules($customField->id));

        $customField->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Custom field updated successfully',
            'data' => $customField
        ]);
    }

    // 📌 DELETE (soft delete)
    public function destroy(CustomField $customField)
    {
        $customField->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Custom field deleted successfully'
        ]);
    }

    // 🔁 Validation rules
    private function rules($id = null)
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:textbox,textarea,dropdown,checkbox,radio,date',
            'description' => 'nullable|string',

            'show_on_student' => 'required|in:Y,N',
            'is_required' => 'required|in:Y,N',
            'is_searchable' => 'required|in:Y,N',
            'is_external' => 'required|in:Y,N',

            'sequence' => 'nullable|integer|min:1',
            'max_length' => 'nullable|integer|min:1',
            'default_value' => 'nullable|string|max:255',
            'prefilled_data' => 'nullable|array',
        ];
    }
}
