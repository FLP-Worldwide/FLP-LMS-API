<?php


Namespace App\Http\Controllers\Reports;

use App\Exports\StudentsExport;
use App\Exports\StudentTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentExportController extends Controller
{
    public function downloadTemplate()
    {
        return Excel::download(
            new StudentTemplateExport(),
            'student_import_template.xlsx'
        );
    }

    public function export(Request $request)
    {
        return Excel::download(
            new StudentsExport(
                $request->course_id,
                $request->batch_id,
                $request->include_batch ?? false,
                $request->include_course ?? false,
                $request->include_fees ?? false,
                $request->include_attendance ?? false
            ),
            'students-report.xlsx'
        );
    }
}
