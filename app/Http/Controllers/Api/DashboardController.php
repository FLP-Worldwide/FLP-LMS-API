<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use App\Models\RolePermission;

use App\Models\InstituteSubscription;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // ===================== PERMISSIONS =====================
        $userPermissions = $user->permissions;

        $permissions = $userPermissions->isNotEmpty()
            ? $userPermissions
            : Permission::whereIn(
                'id',
                RolePermission::where('role', $user->role)
                    ->pluck('permission_id')
            )->get();

        // ===================== INSTITUTE =====================
        $institute = $user->role === 'super_admin'
            ? null
            : $user->institutes()->first();

        // ===================== SUBSCRIPTION =====================
        $subscription = $institute
            ? InstituteSubscription::with('plan')
                ->where('institute_id', $institute->id)
                ->first()
            : null;

        return response()->json([
            "data" => [

                // ===================== USER =====================
                'user' => [
                    'userId'           => $user->uid,
                    'name'         => $user->name,
                    'email'        => $user->email,
                    'role'         => $user->role,
                    'account_type' => $user->account_type,
                ],

                // ===================== INSTITUTE =====================
                'institute' => $institute ? [
                    'schoolId'      => $institute->sid,
                    'name'    => $institute->name,
                    'type'    => $institute->type,
                    'email'   => $institute->email,
                    'phone'   => $institute->phone,
                    'address' => $institute->address,
                    'city'    => $institute->city,
                    'state'   => $institute->state,
                    'country' => $institute->country,
                ] : null,

                // ===================== SUBSCRIPTION / PLAN =====================
                'subscription' => $subscription ? [
                    'status' => $subscription->status,
                    'starts_at' => $subscription->starts_at,
                    'ends_at'   => $subscription->ends_at,
                    'is_active' => $subscription->status === 'active',

                    'plan' => $subscription->plan ? [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'storage_limit_mb' => $subscription->plan->storage_limit_mb,
                        'price' => $subscription->plan->price,
                    ] : null,
                ] : null,

                // ===================== STATS =====================
                'stats' => [
                    'students' => Student::count(),
                    'teachers' => Teacher::count(),
                ],

                // ===================== MODULES (SIDEBAR CONTROL) =====================
                'modules' => $permissions->pluck('key')->values(),
            ]
        ]);
    }


}
