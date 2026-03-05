<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TeacherTemplateExport;
use App\Imports\TeacherBulkImport;

class TeacherBulkController extends Controller
{
    /*
    ===============================
    DOWNLOAD TEMPLATE
    ===============================
    */

    public function downloadTeachers()
    {
        return Excel::download(
            new \App\Exports\TeacherTemplateExport(),
            'teachers.xlsx'
        );
    }
    /*
    ===============================
    BULK IMPORT
    ===============================
    */

    public function uploadTeachers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(
            new TeacherBulkImport(),
            $request->file('file')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Teachers imported successfully'
        ]);
    }
}
