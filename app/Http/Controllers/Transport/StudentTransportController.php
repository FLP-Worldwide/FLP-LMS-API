<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDetail;
use App\Models\Vehicle;
use App\Models\StudentTransportAssignment;
use Illuminate\Http\Request;

class StudentTransportController extends Controller
{
    public function students(Request $request)
    {
        $students = Student::with('details')
            ->when($request->class, function ($q) use ($request) {
                $q->where('class', $request->class);
            })
            ->when($request->section, function ($q) use ($request) {
                $q->where('section', $request->section);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->get()
            ->map(function ($student) {

                $assignment = StudentTransportAssignment::where('student_id', $student->id)
                    ->where('is_active', true)
                    ->first();

                return [
                    'student_id' => $student->id,
                    'admission_no' => $student->admission_no,
                    'name' => trim($student->first_name . ' ' . $student->last_name),
                    'class' => $student->class,
                    'section' => $student->section,

                    // 🔹 pickup info
                    'address' => $student->details?->address,
                    'city' => $student->details?->city,
                    'parent_phone' => $student->details?->parent_phone,

                    // 🔹 transport status
                    'transport_assigned' => $assignment ? true : false,
                    'vehicle_id' => $assignment?->vehicle_id,
                    'route_id' => $assignment?->bus_route_id,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $students,
        ]);
    }

    public function index(Request $request)
    {
        $query = StudentTransportAssignment::with([
            'student:id,first_name,last_name,admission_no',
            'vehicle:id,vehicle_number,capacity',
            'route:id,route_name,start_point,end_point',
        ])->where('is_active', true);

        if ($request->vehicle_id) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->bus_route_id) {
            $query->where('bus_route_id', $request->bus_route_id);
        }

        $assignments = $query->get();

        $vehicle = null;
        $availableSeats = null;

        if ($request->vehicle_id) {
            $vehicle = Vehicle::find($request->vehicle_id);
            $assigned = $assignments->count();
            $availableSeats = $vehicle
                ? max($vehicle->capacity - $assigned, 0)
                : null;
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'assignments' => $assignments,
                'vehicle' => $vehicle,
                'available_seats' => $availableSeats,
            ],
        ]);
    }


    /**
     * 📌 ASSIGN STUDENT TO VEHICLE & ROUTE
     */
    public function assign(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'bus_route_id' => 'required|exists:bus_routes,id',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // 🔹 Seat availability check
        $assignedCount = StudentTransportAssignment::where('vehicle_id', $vehicle->id)
            ->where('is_active', true)
            ->count();

        if ($assignedCount >= $vehicle->capacity) {
            return response()->json([
                'status' => 'error',
                'message' => 'No seats available in this vehicle',
            ], 422);
        }

        // 🔹 Get pickup point from student_details
        $studentDetail = StudentDetail::where('student_id', $request->student_id)->first();

        $pickupPoint = $studentDetail?->address ?? 'N/A';

        $assignment = StudentTransportAssignment::updateOrCreate(
            [
                'student_id' => $request->student_id,
            ],
            [
                'vehicle_id' => $request->vehicle_id,
                'bus_route_id' => $request->bus_route_id,
                'pickup_point' => $pickupPoint,
                'is_active' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Student assigned to transport successfully',
            'data' => $assignment,
        ]);
    }

    /**
     * 📌 UNASSIGN STUDENT FROM VEHICLE & ROUTE
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $assignment = StudentTransportAssignment::where('student_id', $request->student_id)
            ->where('is_active', true)
            ->first();

        if (!$assignment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student is not assigned to any transport',
            ], 404);
        }

        // 🔹 Soft unassign (recommended – keeps history)
        $assignment->update([
            'is_active' => false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Student unassigned from transport successfully',
        ]);
    }


}
