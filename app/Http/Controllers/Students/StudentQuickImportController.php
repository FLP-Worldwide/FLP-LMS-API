<?php

namespace App\Http\Controllers\Students;

use App\Exports\NewStudentTemplateExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentQuickImportTemplateExport;
use App\Exports\StudentsFullExport;
use App\Exports\StudentUpdateTemplateExport;
use App\Imports\NewStudentImport;
use App\Imports\StudentQuickImport;

class StudentQuickImportController extends Controller
{
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'fields' => 'required|array|min:1'
        ]);

        return Excel::download(
            new StudentQuickImportTemplateExport($request->fields),
            'student_import_template.xlsx'
        );
    }

    public function importStudents(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new StudentQuickImport, $request->file('file'));

        return response()->json([
            'status' => 'success',
            'message' => 'Students imported successfully'
        ]);
    }



    public function exportUpdateTemplate(Request $request)
    {
        $request->validate([
            'fields' => 'required|array'
        ]);

        return Excel::download(
            new StudentUpdateTemplateExport(
                $request->fields,
                $request->course_ids ?? [],
                $request->batch_ids ?? [],
                $request->without_batch ?? false
            ),
            'students_update_template.xlsx'
        );
    }


    public function importUpdateStudents(Request $request)
    {
        $request->validate([
            'file'=>'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new \App\Imports\StudentUpdateImport,$request->file('file'));

        return response()->json([
            'status'=>'success',
            'message'=>'Students updated successfully'
        ]);
    }


    public function downloadNewStudentTemplate()
    {
        return Excel::download(
            new NewStudentTemplateExport,
            'new_student_template.xlsx'
        );
    }

   public function importNewStudents(Request $request)
    {

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        $fileName = $request->file('file')->getClientOriginalName();

        $file = \App\Models\StudentImportFile::create([
            'file_name' => $fileName
        ]);

        $import = new NewStudentImport($file->id);

        Excel::import($import, $request->file('file'));

        return response()->json([
            'status' => true,
            'message' => 'Import Completed'
        ]);
    }

    public function exportAllStudents()
    {

        return Excel::download(
            new StudentsFullExport,
            'all_students.xlsx'
        );

    }

    public function studentImportLogs()
    {
        $logs = \App\Models\StudentImportLog::latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }


    public function studentUploadReport()
    {

        $reports = \App\Models\StudentImportFile::latest()
            ->select(
                'id',
                'file_name',
                'total_rows',
                'success_rows',
                'failed_rows',
                'created_at'
            )
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $reports
        ]);
    }

    public function studentUploadErrors($fileId)
    {

        $errors = \App\Models\StudentImportLog::where('file_id', $fileId)
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $errors
        ]);
    }



}
