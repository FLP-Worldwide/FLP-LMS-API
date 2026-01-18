<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * 📌 VEHICLE LIST (TABLE VIEW)
     */
    public function index()
    {
        $vehicles = Vehicle::with([
                'driver:id,name,phone',
                'route:id,route_name,start_point,end_point',
            ])
            ->latest()
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'vehicle_number' => $v->vehicle_number,
                    'type' => $v->type,
                    'capacity' => $v->capacity,

                    'driver' => $v->driver ? [
                        'id' => $v->driver->id,
                        'name' => $v->driver->name,
                        'phone' => $v->driver->phone,
                    ] : null,

                    'route' => $v->route
                        ? $v->route->start_point . ' - ' . $v->route->end_point
                        : null,
                    'route_id'=>$v->bus_route_id,

                    'status' => $v->status,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $vehicles,
        ]);
    }

    /**
     * 📌 CREATE VEHICLE
     */
    public function store(Request $request)
    {
        $request->validate([
            'vehicle_number' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',

            // Optional at creation
            'driver_id' => 'nullable|exists:users,id',
            'bus_route_id' => 'nullable|exists:bus_routes,id',
        ]);

        $vehicle = Vehicle::create([
            'vehicle_number' => $request->vehicle_number,
            'type' => $request->type,
            'capacity' => $request->capacity,

            // Optional fields
            'driver_id' => $request->driver_id,
            'bus_route_id' => $request->bus_route_id, // can be NULL
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle created successfully',
            'data' => [
                'id' => $vehicle->id,
                'vehicle_number' => $vehicle->vehicle_number,
                'status' => $vehicle->status, // available
            ],
        ], 201);
    }


    /**
     * 📌 SHOW VEHICLE
     */
    public function show($id)
    {
        $vehicle = Vehicle::with([
                'driver:id,name,phone',
                'route.stops',
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $vehicle,
        ]);
    }

    /**
     * 📌 UPDATE VEHICLE / ASSIGN ROUTE & DRIVER
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'vehicle_number' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',

            'driver_id' => 'nullable|exists:users,id',
            'bus_route_id' => 'nullable|exists:bus_routes,id',
            'is_active' => 'boolean',
        ]);

        $vehicle->update([
            'vehicle_number' => $request->vehicle_number,
            'type' => $request->type,
            'capacity' => $request->capacity,
            'driver_id' => $request->driver_id,
            'bus_route_id' => $request->bus_route_id,
            'is_active' => $request->is_active ?? $vehicle->is_active,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle,
        ]);
    }

    /**
     * 📌 DELETE VEHICLE
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    public function assignRoute(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'bus_route_id' => 'required|exists:bus_routes,id',
        ]);

        $vehicle->update([
            'bus_route_id' => $request->bus_route_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Route assigned successfully',
            'data' => [
                'vehicle_id' => $vehicle->id,
                'route_id' => $vehicle->bus_route_id,
                'status' => $vehicle->status, // assigned
            ],
        ]);
    }

    public function assignDriver(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $vehicle->update([
            'driver_id' => $request->driver_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver assigned successfully',
        ]);
    }



}
