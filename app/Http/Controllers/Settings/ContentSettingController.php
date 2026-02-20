<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

use App\Models\Setting;
use Illuminate\Http\Request;

class ContentSettingController extends Controller
{
    public function save(Request $request)
    {
        $validated = $request->validate([
            'key'   => 'required|string|max:100',
            'value' => 'required|array',
        ]);

        $setting = Setting::updateOrCreate(
            ['key' => $validated['key']],
            ['value' => $validated['value']]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Setting saved successfully',
            'data'    => $setting
        ]);
    }

    /**
     * 🔹 Get Setting By Key
     */
    public function get($key)
    {
        $setting = Setting::where('key', $key)->first();

        return response()->json([
            'status' => 'success',
            'data'   => $setting?->value ?? []
        ]);
    }

    /**
     * 🔹 Get All Settings (Optional)
     */
    public function all()
    {
        return response()->json([
            'status' => 'success',
            'data'   => Setting::pluck('value', 'key')
        ]);
    }
}
