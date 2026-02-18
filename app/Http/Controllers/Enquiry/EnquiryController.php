<?php

namespace App\Http\Controllers\Enquiry;

use App\Http\Controllers\Controller;
use App\Models\CustomFieldValue;
use App\Models\Enquiry;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class EnquiryController extends Controller
{
    /**
     * List enquiries (basic only – fast)
     */
    public function index(Request $request)
    {
        $query = Enquiry::query()
            ->with([
                'details:id,enquiry_id,email,gender,state,city,category,parent_name',
                'leadSource:id,name',
                'followUps' => function ($q) {
                    $q->latest()->limit(1);
                }
            ])
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

        /* ================= BASIC FILTERS ================= */

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('lead_temperature', $request->priority);
        }

        if ($request->filled('source')) {
            $query->where('lead_source_type_id', $request->source);
        }

        if ($request->filled('referred_by')) {
            $query->where('referred_by_id', $request->referred_by);
        }

        /* ================= ENQUIRY DATE FILTER ================= */

        if ($request->filled('enquiry_from_date') && $request->filled('enquiry_to_date')) {
            $query->whereBetween('enquiry_date', [
                $request->enquiry_from_date,
                $request->enquiry_to_date,
            ]);
        }

        /* ================= DETAILS FILTER ================= */

        $query->when($request->state, function ($q) use ($request) {
            $q->whereHas('details', fn ($d) =>
                $d->where('state', $request->state)
            );
        });

        $query->when($request->city, function ($q) use ($request) {
            $q->whereHas('details', fn ($d) =>
                $d->where('city', $request->city)
            );
        });

        $query->when($request->category, function ($q) use ($request) {
            $q->whereHas('details', fn ($d) =>
                $d->where('category', $request->category)
            );
        });

        /* ================= FOLLOW UP FILTER ================= */

        $query->when($request->follow_up_type, function ($q) use ($request) {
            $q->whereHas('followUps', fn ($f) =>
                $f->where('follow_up_type', $request->follow_up_type)
            );
        });

        $query->when($request->follow_up_date, function ($q) use ($request) {
            $q->whereHas('followUps', fn ($f) =>
                $f->whereDate('followup_date', $request->follow_up_date)
            );
        });

        return response()->json([
            'status' => 'success',
            'data'   => $query->latest()->paginate(20),
        ]);
    }


    /**
     * Store enquiry (basic + details + follow-ups + custom fields)
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            /* ================= VALIDATION ================= */
            $validated = $request->validate([
                'student_name' => 'required|string|max:150',
                'phone'        => 'required|string|max:20',

                'source'       => 'nullable|integer',
                'referred_by'  => 'nullable|integer',

                'status'           => 'nullable|string',
                'lead_temperature' => 'nullable|string',
                'enquiry_date'     => 'nullable|date',

                // follow up
                'follow_up_type' => 'nullable|string',
                'followup_date'  => 'nullable|date',
                'followup_hh'    => 'nullable|string',
                'followup_mm'    => 'nullable|string',
            ]);

            /* ================= CREATE ENQUIRY ================= */
            $enquiry = Enquiry::create([
                'enquiry_code' => 'ENQ-' . rand(1000, 9999),
                'student_name' => $request->student_name,
                'phone'        => $request->phone,

                'lead_source_type_id' => $request->source,
                'referred_by_id'      => $request->referred_by,

                'status'           => $request->status ?? 'Open',
                'lead_temperature' => $request->lead_temperature,
                'enquiry_date'     => $request->enquiry_date ?? now()->toDateString(),
            ]);

            /* ================= ENQUIRY DETAILS ================= */
            $sameAddress = filter_var($request->same_address, FILTER_VALIDATE_BOOLEAN);

            $detailData = [
                // BASIC
                'email'   => $request->email,
                'gender'  => $request->gender,
                'dob'     => $request->dob,

                // LOCATION
                'country' => 'India',
                'state'   => $request->state,
                'city'    => $request->city,
                'area'    => $request->area,
                'pincode' => $request->pincode,

                'current_address'     => $request->current_address,
                'residential_address' => $sameAddress
                    ? $request->current_address
                    : $request->residential_address,

                'same_address' => $sameAddress,

                // OTHER
                'alternate_contact' => $request->alternate_contact,
                'alternate_email'   => $request->alternate_email,
                'nationality'       => $request->nationality,
                'birth_place'       => $request->birth_place,
                'mother_tongue'     => $request->mother_tongue,
                'category'          => $request->category,
                'religion'          => $request->religion,
                'blood_group'       => $request->blood_group,
                'aadhar_no'         => $request->aadhar_no,

                // PARENT
                'parent_name'        => $request->parent_name,
                'parent_contact'     => $request->parent_contact,
                'parent_email'       => $request->parent_email,
                'parent_profession'  => $request->parent_profession,
                'parent_aadhar_no'   => $request->parent_aadhar_no,

                // GUARDIAN
                'guardian_name'    => $request->guardian_name,
                'guardian_contact' => $request->guardian_contact,
                'guardian_email'   => $request->guardian_email,

                'comment' => $request->comment,
            ];

            $enquiry->details()->create($detailData);

            /* ================= FOLLOW UP ================= */
            if ($request->follow_up_type) {
                $enquiry->followUps()->create([
                    'follow_up_type' => $request->follow_up_type,
                    'followup_date'  => $request->followup_date,
                    'followup_time'  => $request->followup_hh . ':' . $request->followup_mm,
                    'comment'        => $request->comment,
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Enquiry created successfully',
                'data'    => $enquiry->load(['details', 'followUps']),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create enquiry',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show enquiry
     */
public function show($id)
{
    $enquiry = Enquiry::with([
        'details',
        'followUps' => function ($q) {
            $q->orderBy('created_at', 'asc');
        },
        'customFieldValues.field',
    ])->findOrFail($id);

    // ✅ history banaya (followUps se hi)
    $history = $enquiry->followUps->map(function ($followUp) {
        return [
            'id' => $followUp->id,
            'follow_up_type' => $followUp->follow_up_type,
            'followup_date' => $followUp->followup_date,
            'followup_time' => $followUp->followup_time,
            'comment' => $followUp->comment,
            'created_at' => $followUp->created_at,
        ];
    });

    // ✅ data ke andar hi merge
    $enquiry->history = $history;

    return response()->json([
        'status' => 'success',
        'data'   => $enquiry,
    ]);
}


    /**
     * Update enquiry (FULL payload support)
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $enquiry = Enquiry::findOrFail($id);

            // ================= VALIDATION =================
            $validated = $request->validate([
                'student_name' => 'required|string|max:150',
                'phone'        => 'required|string|max:20',

                'lead_source_type_id' => 'nullable|integer',
                'referred_by_id'      => 'nullable|integer',

                'status'           => 'nullable|string',
                'lead_temperature' => 'nullable|string',
                'enquiry_date'     => 'nullable|date',

                'details' => 'nullable|array',
                'follow_ups' => 'nullable|array',

                'custom_fields' => 'nullable|array',
                'custom_fields.*.custom_field_id' => 'required|integer|exists:custom_fields,id',
                'custom_fields.*.value' => 'nullable|string',
            ]);

            // ================= BASIC =================
            $enquiry->update([
                'student_name' => $validated['student_name'],
                'phone'        => $validated['phone'],
                'lead_source_type_id' => $request->lead_source_type_id,
                'referred_by_id'      => $request->referred_by_id,
                'status'              => $request->status ?? $enquiry->status,
                'lead_temperature'    => $request->lead_temperature,
                'enquiry_date'        => $request->enquiry_date,
            ]);

            // ================= DETAILS =================
            $detailData = $request->input('details', []);

            if (!empty($detailData)) {
                $sameAddress = filter_var(
                    $detailData['same_address'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                );

                $detailData['same_address'] = $sameAddress ? 1 : 0;

                if ($sameAddress && isset($detailData['current_address'])) {
                    $detailData['residential_address'] = $detailData['current_address'];
                }

                if ($enquiry->details) {
                    $enquiry->details->update($detailData);
                } else {
                    $enquiry->details()->create($detailData);
                }
            }

            // ================= FOLLOW UPS =================
            // ================= FOLLOW UPS (APPEND ONLY – HISTORY SAFE) =================
            if ($request->filled('follow_ups')) {
                foreach ($request->follow_ups as $followUp) {

                    // Skip empty comments (optional safety)
                    if (
                        empty($followUp['comment']) &&
                        empty($followUp['follow_up_type']) &&
                        empty($followUp['followup_date'])
                    ) {
                        continue;
                    }

                    $enquiry->followUps()->create([
                        'follow_up_type' => $followUp['follow_up_type'] ?? null,
                        'followup_date'  => $followUp['followup_date'] ?? null,
                        'followup_time'  => $followUp['followup_time'] ?? null,
                        'comment'        => $followUp['comment'] ?? null,
                    ]);
                }
            }


            // ================= CUSTOM FIELDS =================
            if ($request->filled('custom_fields')) {
                $incomingIds = collect($request->custom_fields)
                    ->pluck('custom_field_id')
                    ->toArray();

                CustomFieldValue::where('enquiry_id', $enquiry->id)
                    ->whereNotIn('custom_field_id', $incomingIds)
                    ->delete();

                foreach ($request->custom_fields as $field) {
                    CustomFieldValue::updateOrCreate(
                        [
                            'enquiry_id' => $enquiry->id,
                            'custom_field_id' => $field['custom_field_id'],
                        ],
                        [
                            'value' => $field['value'],
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Enquiry updated successfully.',
                'data'    => $enquiry->load([
                    'details',
                    'followUps',
                    'customFieldValues.field',
                ]),
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



    public function convertToStudent($id)
    {
        DB::beginTransaction();

        try {
            $enquiry = Enquiry::with([
                'details',
                'customFieldValues',
            ])->findOrFail($id);

            // ❌ Already converted check
            if ($enquiry->status === 'converted') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Enquiry already converted to student.'
                ], 400);
            }

            // ================= CREATE USER =================
            $password = Str::random(8);

            $user = User::create([
                'uid' => Str::uuid(),
                'name' => $enquiry->student_name,
                'email' => $enquiry->details->email ?? 'student_' . time() . '@demo.com',
                'password' => Hash::make($password),
                'temp_password' => Crypt::encryptString($password),
            ]);

            // ================= CREATE STUDENT =================
            $nameParts = explode(' ', $enquiry->student_name, 2);

            $student = Student::create([
                'stuid' => Str::uuid(),
                'institute_id' => $enquiry->institute_id,

                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? null,

                'status' => 'active',
                'admission_date' => now()->toDateString(),
            ]);

            // ================= STUDENT DETAILS =================
            $detail = $enquiry->details;

            StudentDetail::create([
                'student_id' => $student->id,

                'dob' => $detail->dob ?? null,
                'gender' => $detail->gender ?? null,
                'blood_group' => $detail->blood_group ?? null,

                'email' => $detail->email ?? null,
                'phone' => $detail->phone ?? $enquiry->phone,

                'father_name' => $detail->parent_name ?? null,
                'mother_name' => null,
                'parent_phone' => $detail->parent_contact ?? null,

                'address' => $detail->residential_address ?? null,
                'city' => $detail->city ?? null,
                'state' => $detail->state ?? null,
                'country' => 'India',
            ]);

            // ================= UPDATE ENQUIRY =================
            $enquiry->update([
                'status' => 'converted',
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Enquiry converted to student successfully.',
                'data' => [
                    'student_id' => $student->id,
                    'user_id' => $user->id,
                    'login' => [
                        'email' => $user->email,
                        'password' => $password, // show once to admin
                    ]
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert enquiry to student.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
