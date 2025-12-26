<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetInstituteContext
{
public function handle(Request $request, Closure $next)
{
    $user = auth()->user();

    // If token invalid, auth.jwt will already handle it
    if (!$user) {
        return response()->json([
            'status'  => 'error',
            'code'    => 'UNAUTHENTICATED',
            'message' => 'Unauthenticated.',
        ], 401);
    }

    // Super admin can access all
    if ($user->role === 'super_admin') {
        return $next($request);
    }

    $institute = $user->institutes()->first();

    if (!$institute) {
        return response()->json([
            'status'  => 'error',
            'code'    => 'INSTITUTE_NOT_ASSIGNED',
            'message' => 'Institute not assigned to user.',
        ], 403);
    }

    // Attach institute_id
    $request->merge([
        'institute_id' => $institute->id,
    ]);

    app()->instance('institute_id', $institute->id);

    return $next($request);
}

}
