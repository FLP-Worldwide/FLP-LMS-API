<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Models\LeadInstituteName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class InstituteController extends Controller
{
    public function index(Request $request)
    {
        $query = LeadInstituteName::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $institutes = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $institutes
        ]);
    }


    /*
    =========================================
    CREATE INSTITUTE
    =========================================
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lead_institute_names,name',
        ]);

        DB::beginTransaction();

        try {

            $institute = LeadInstituteName::create([
                'name' => $validated['name'],
                'code' => 'INS-' . strtoupper(Str::random(5)),
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Institute created successfully.',
                'data' => $institute,
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /*
    =========================================
    SHOW SINGLE INSTITUTE
    =========================================
    */
    public function show($id)
    {
        $institute = LeadInstituteName::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $institute,
        ]);
    }


    /*
    =========================================
    UPDATE INSTITUTE
    =========================================
    */
    public function update(Request $request, $id)
    {
        $institute = LeadInstituteName::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lead_institute_names,name,' . $id,
            'is_active' => 'nullable|boolean',
        ]);

        $institute->update([
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $institute->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Institute updated successfully.',
            'data' => $institute,
        ]);
    }


    /*
    =========================================
    DELETE (SOFT DELETE)
    =========================================
    */
    public function destroy($id)
    {
        $institute = LeadInstituteName::findOrFail($id);

        $institute->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Institute deleted successfully.',
        ]);
    }


    /*
    =========================================
    TOGGLE ACTIVE STATUS
    =========================================
    */
    public function toggleStatus($id)
    {
        $institute = LeadInstituteName::findOrFail($id);

        $institute->update([
            'is_active' => !$institute->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Institute status updated.',
            'data' => $institute,
        ]);
    }


    public function downloadTemplate()
    {
        $data = collect([
            ['name' => 'Institute Name'] // header
        ]);

        return Excel::download(
            new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection {
                protected $data;
                public function __construct(Collection $data)
                {
                    $this->data = $data;
                }
                public function collection()
                {
                    return $this->data;
                }
            },
            'institute_template.xlsx'
        );
    }


    /*
    ========================================
    EXPORT ALL INSTITUTE NAMES
    ========================================
    */
    public function exportAll()
    {
        $institutes = LeadInstituteName::select('name')->get();

        return Excel::download(
            new class($institutes) implements \Maatwebsite\Excel\Concerns\FromCollection,
                                              \Maatwebsite\Excel\Concerns\WithHeadings {

                protected $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function collection()
                {
                    return $this->data;
                }

                public function headings(): array
                {
                    return ['Institute Name'];
                }
            },
            'institute_list.xlsx'
        );
    }


    /*
    ========================================
    BULK UPLOAD
    ========================================
    */
    public function bulkUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120'
        ]);

        DB::beginTransaction();

        try {

            $rows = Excel::toCollection(null, $request->file('file'))->first();

            $inserted = 0;
            $skipped = 0;

            foreach ($rows as $index => $row) {

                // Skip header row
                if ($index == 0) continue;

                $name = trim($row[0] ?? null);

                if (!$name) {
                    $skipped++;
                    continue;
                }

                // Skip duplicates
                if (LeadInstituteName::where('name', $name)->exists()) {
                    $skipped++;
                    continue;
                }

                LeadInstituteName::create([
                    'name' => $name
                ]);

                $inserted++;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk upload completed.',
                'data' => [
                    'inserted' => $inserted,
                    'skipped' => $skipped
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
