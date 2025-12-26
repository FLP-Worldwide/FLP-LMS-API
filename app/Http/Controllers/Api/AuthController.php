<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = auth()->user();

        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => config('jwt.ttl') * 60 * 7,
                'role'         => $user->role,
                'account_type' => $user->account_type,
            ]
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'data' => [
                'access_token' => JWTAuth::refresh(),
                'token_type'   => 'bearer',
                'expires_in'   => config('jwt.ttl') * 60,
            ]
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
