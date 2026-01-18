<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\BusRoute;
use App\Models\BusRouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusRouteController extends Controller
{
    /**
     * 📌 LIST ROUTES
     */
    public function index()
    {
        $routes = BusRoute::with('stops')
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $routes,
        ]);
    }

    /**
     * 📌 CREATE ROUTE WITH STOPS
     */
    public function store(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string|max:255',
            'vehicle_number' => 'nullable|string|max:255',
            'start_point' => 'required|string|max:255',
            'end_point' => 'required|string|max:255',

            'stops' => 'required|array|min:1',
            'stops.*.stop_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $route = BusRoute::create([
                'route_name' => $request->route_name,
                'vehicle_number' => $request->vehicle_number,
                'start_point' => $request->start_point,
                'end_point' => $request->end_point,
            ]);

            foreach ($request->stops as $index => $stop) {
                BusRouteStop::create([
                    'bus_route_id' => $route->id,
                    'stop_name' => $stop['stop_name'],
                    'stop_order' => $index + 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bus route created successfully',
                'data' => $route->load('stops'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📌 SHOW ROUTE
     */
    public function show($id)
    {
        $route = BusRoute::with('stops')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $route,
        ]);
    }

    /**
     * 📌 UPDATE ROUTE + REPLACE STOPS
     */
    public function update(Request $request, $id)
    {
        $route = BusRoute::findOrFail($id);

        $request->validate([
            'route_name' => 'required|string|max:255',
            'start_point' => 'required|string|max:255',
            'end_point' => 'required|string|max:255',
            'stops' => 'required|array|min:1',
            'stops.*.stop_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Update route
            $route->update([
                'route_name' => $request->route_name,
                'start_point' => $request->start_point,
                'end_point' => $request->end_point,
            ]);

            // 2️⃣ HARD delete old stops (IMPORTANT)
            $route->stops()->forceDelete();

            // 3️⃣ Recreate stops
            foreach ($request->stops as $index => $stop) {
                BusRouteStop::create([
                    'bus_route_id' => $route->id,
                    'stop_name' => $stop['stop_name'],
                    'stop_order' => $index + 1,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Bus route updated successfully',
                'data' => $route->load('stops'),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * 📌 DELETE ROUTE
     */
    public function destroy($id)
    {
        $route = BusRoute::findOrFail($id);
        $route->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bus route deleted successfully',
        ]);
    }
}
