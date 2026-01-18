<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InstituteUser;
use App\Models\StaffDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    /**
     * 📌 LIST DRIVERS
     */
    public function index()
    {
        $drivers = User::where('role', 'driver')
            ->with('staffDetail')
            ->select('id', 'uid', 'name', 'email')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'uid' => $u->uid,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->staffDetail?->phone,
                    'id_number' => $u->staffDetail?->id_number,
                    'status' => $u->is_active ? 'active' : 'inactive',
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $drivers,
        ]);
    }

    /**
     * 📌 CREATE DRIVER
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
        ]);

        // 1️⃣ Create user
        $driver = User::create([
            'uid' => 'DRV' . rand(10000, 99999),
            'name' => $request->name,
            'email' => $request->email,
            'password' => null,
            'role' => 'driver',
            'is_active' => true,
        ]);

        StaffDetail::create([
            'user_id' => $driver->id,
            'phone' => $request->phone,
            'designation' => 'Driver',
            'id_number' => $request->id_number,
        ]);


        // 2️⃣ Map to institute
        InstituteUser::create([
            'user_id' => $driver->id,
            'role' => 'driver',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver created successfully',
            'data' => $driver,
        ], 201);
    }

    /**
     * 📌 SHOW DRIVER
     */
    public function show($id)
    {
        $driver = User::where('role', 'driver')
            ->with('instituteUsers')
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $driver,
        ]);
    }

    /**
     * 📌 UPDATE DRIVER
     */
    public function update(Request $request, $id)
    {
        $driver = User::where('role', 'driver')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'is_active' => 'boolean',
        ]);

        $driver->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'is_active' => $request->is_active ?? $driver->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver updated successfully',
        ]);
    }

    /**
     * 📌 DELETE DRIVER
     */
    public function destroy($id)
    {
        $driver = User::where('role', 'driver')->findOrFail($id);

        $driver->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Driver deleted successfully',
        ]);
    }
}
