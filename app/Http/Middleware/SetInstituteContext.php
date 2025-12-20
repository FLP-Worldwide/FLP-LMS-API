<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetInstituteContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // Super admin can access all
        if ($user && $user->role === 'super_admin') {
            return $next($request);
        }

        if ($user) {
            $institute = $user->institutes()->first();

            if (!$institute) {
                return response()->json([
                    'message' => 'Institute not assigned'
                ], 403);
            }

            // Attach institute_id globally
            $request->merge([
                'institute_id' => $institute->id,
            ]);

            // Optional helper
            app()->instance('institute_id', $institute->id);
        }

        return $next($request);
    }
}
