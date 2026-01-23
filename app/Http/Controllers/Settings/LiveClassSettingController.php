<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LiveClassSetting;
use Illuminate\Http\Request;

class LiveClassSettingController extends Controller
{
    public function show()
    {
        $settings = LiveClassSetting::first();

        return response()->json([
            'status' => 'success',
            'data' => $settings,
        ]);
    }

    /**
     * 📌 CREATE / UPDATE SETTINGS (UPSERT)
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'recording_enabled' => 'required|boolean',

            'recorded_view_visibility' => 'required|array',
            'recorded_download_visibility' => 'required|array',

            'attendance' => 'required|array',
            'attendance.attendance_threshold' => 'required|integer|min:0',
            'attendance.attendance_notification_mode' => 'required|string',

            'vdocipher' => 'nullable|array',
            'vdocipher.watch_multiplier' => 'nullable|integer|min:1',

            'zoom_account_selection' => 'required|boolean',
        ]);

        $settings = LiveClassSetting::first();
        if($settings){
            $settings->delete();
        }

        $settings = LiveClassSetting::create(
            $data
            );

        return response()->json([
            'status' => 'success',
            'message' => 'Live class settings saved successfully',
            'data' => $settings,
        ]);
    }

}
