<?php

namespace App\Http\Controllers\Enquiry;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\EnquiryDetail;
use App\Models\EnquiryFollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class EnquiryController extends Controller
{
    /**
     * List enquiries (basic only – fast)
     */
    public function index(Request $request)
    {
        $query = Enquiry::query()
            ->select([
                'id',
                'enquiry_code',
                'student_name',
                'phone',
                'lead_source_type_id',
                'referred_by_id',
                'status',
                'lead_temperature',
                'enquiry_date',
                'created_at',
            ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->lead_temperature) {
            $query->where('lead_temperature', $request->lead_temperature);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('enquiry_date', [
                $request->from_date,
                $request->to_date,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->latest()->get(),
        ]);
    }

    /**
     * Store enquiry (basic + details + optional follow-up)
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // ================= BASIC VALIDATION =================
            $validated = $request->validate([
                'student_name' => 'required|string|max:150',
                'phone'        => 'required|string|max:20',

                'lead_source_type_id' => 'nullable|integer',
                'referred_by_id'      => 'nullable|integer',

                'status'           => 'nullable|string',
                'lead_temperature' => 'nullable|string',
                'enquiry_date'     => 'nullable|date',
            ]);

            // ================= CREATE ENQUIRY =================
            $enquiry = Enquiry::create([
                'enquiry_code' => 'ENQ-' . strtoupper(Str::random(6)),
                'student_name' => $validated['student_name'],
                'phone'        => $validated['phone'],
                'lead_source_type_id' => $request->lead_source_type_id,
                'referred_by_id'      => $request->referred_by_id,
                'status'              => $request->status ?? 'new',
                'lead_temperature'    => $request->lead_temperature,
                'enquiry_date'        => $request->enquiry_date ?? now()->toDateString(),
            ]);

            $detailData = $request->except([
                'student_name',
                'phone',
                'lead_source_type_id',
                'referred_by_id',
                'status',
                'lead_temperature',
                'enquiry_date',
                'follow_up_type',
                'followup_date',
                'followup_time',
                'comment',
            ]);

            // ✅ Normalize boolean
            $sameAddress = filter_var(
                $request->same_address,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            // Force safe value
            $detailData['same_address'] = $sameAddress ? 1 : 0;

            // Copy address if same
            if ($sameAddress) {
                $detailData['residential_address'] = $request->current_address;
            }

            $enquiry->details()->create($detailData);


            // ================= OPTIONAL FOLLOW-UP =================
            if ($request->follow_up_type || $request->followup_date) {
                $enquiry->followUps()->create([
                    'follow_up_type' => $request->follow_up_type,
                    'followup_date'  => $request->followup_date,
                    'followup_time'  => $request->followup_time,
                    'comment'        => $request->comment,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Enquiry created successfully.',
                'data'    => $enquiry->load('details', 'followUps'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create enquiry.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show enquiry (basic + details + follow-ups)
     */
    public function show($id)
    {
        $enquiry = Enquiry::with(['details', 'followUps'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $enquiry,
        ]);
    }

    /**
     * Update enquiry (basic + details)
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $enquiry = Enquiry::findOrFail($id);

            // ================= UPDATE BASIC =================
            $validated = $request->validate([
                'student_name' => 'required|string|max:150',
                'phone'        => 'required|string|max:20',

                'lead_source_type_id' => 'nullable|integer',
                'referred_by_id'      => 'nullable|integer',

                'status'           => 'nullable|string',
                'lead_temperature' => 'nullable|string',
                'enquiry_date'     => 'nullable|date',
            ]);

            $enquiry->update([
                'student_name' => $validated['student_name'],
                'phone'        => $validated['phone'],
                'lead_source_type_id' => $request->lead_source_type_id,
                'referred_by_id'      => $request->referred_by_id,
                'status'              => $request->status ?? $enquiry->status,
                'lead_temperature'    => $request->lead_temperature,
                'enquiry_date'        => $request->enquiry_date,
            ]);

            // ================= UPDATE DETAILS =================
            $detailData = $request->except([
                'student_name',
                'phone',
                'lead_source_type_id',
                'referred_by_id',
                'status',
                'lead_temperature',
                'enquiry_date',
                'follow_up_type',
                'followup_date',
                'followup_time',
                'comment',
            ]);

            if ($request->same_address) {
                $detailData['residential_address'] = $request->current_address;
            }

            if ($enquiry->details) {
                $enquiry->details->update($detailData);
            } else {
                $enquiry->details()->create($detailData);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Enquiry updated successfully.',
                'data'    => $enquiry->load('details', 'followUps'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update enquiry.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
