<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => AcademicYear::orderByDesc('start_year')->get()
        ]);
    }

    /*
    ==============================
    CREATE
    ==============================
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_year'  => 'required|digits:4',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean'
        ]);

        $startYear = (int) $validated['start_year'];
        $endYear   = $startYear + 1;

        $name = $startYear . '-' . $endYear;

        if (AcademicYear::where('name', $name)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Academic year already exists.'
            ], 422);
        }

        DB::beginTransaction();

        try {

            if (!empty($validated['is_active'])) {
                AcademicYear::where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $year = AcademicYear::create([
                'name'        => $name,
                'start_year'  => $startYear,
                'end_year'    => $endYear,
                'start_date'  => $validated['start_date'],
                'end_date'    => $validated['end_date'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? false,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Academic year created successfully',
                'data' => $year
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    ==============================
    SHOW
    ==============================
    */
    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => AcademicYear::findOrFail($id)
        ]);
    }

    /*
    ==============================
    UPDATE
    ==============================
    */
    public function update(Request $request, $id)
    {
        $year = AcademicYear::findOrFail($id);

        $validated = $request->validate([
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after:start_date',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean'
        ]);

        DB::beginTransaction();

        try {

            if (!empty($validated['is_active'])) {
                AcademicYear::where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $year->update($validated);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Academic year updated successfully',
                'data' => $year
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    ==============================
    DELETE (Soft)
    ==============================
    */
    public function destroy($id)
    {
        $year = AcademicYear::findOrFail($id);
        $year->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Academic year deleted successfully'
        ]);
    }
}
