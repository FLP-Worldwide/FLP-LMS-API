<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ExamGrade;
use Illuminate\Http\Request;

class ExamGradeController extends Controller
{
    /**
     * 📌 LIST EXAM GRADES
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => ExamGrade::latest()->get(),
        ]);
    }

    /**
     * 📌 CREATE EXAM GRADE
     */
    public function store(Request $request)
    {
        $request->validate([
            'grade_name' => 'required|string|max:100|unique:exam_grades,grade_name',
            'description' => 'nullable|string',
        ]);

        $grade = ExamGrade::create([
            'grade_name' => $request->grade_name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Exam grade created successfully',
            'data' => $grade,
        ], 201);
    }

    /**
     * 📌 UPDATE EXAM GRADE
     */
    public function update(Request $request, $id)
    {
        $grade = ExamGrade::findOrFail($id);

        $request->validate([
            'grade_name' => 'required|string|max:100|unique:exam_grades,grade_name,' . $id,
            'description' => 'nullable|string',
        ]);

        $grade->update($request->only('grade_name', 'description'));

        return response()->json([
            'status' => 'success',
            'message' => 'Exam grade updated successfully',
        ]);
    }

    /**
     * 📌 DELETE EXAM GRADE
     */
    public function destroy($id)
    {
        ExamGrade::findOrFail($id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Exam grade deleted successfully',
        ]);
    }
}
