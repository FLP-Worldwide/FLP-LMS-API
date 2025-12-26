<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // ================= JWT AUTH EXCEPTION WRAPPER =================
        if ($exception instanceof UnauthorizedHttpException) {

            $previous = $exception->getPrevious();

            // TOKEN EXPIRED
            if ($previous instanceof TokenExpiredException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_EXPIRED',
                    'message' => 'Your session has expired. Please login again.',
                ], 401);
            }

            // TOKEN INVALID
            if ($previous instanceof TokenInvalidException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_INVALID',
                    'message' => 'Invalid token.',
                ], 401);
            }

            // TOKEN MISSING OR OTHER JWT ISSUE
            if ($previous instanceof JWTException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_MISSING',
                    'message' => 'Authorization token not found.',
                ], 401);
            }

            // Fallback
            return response()->json([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED',
                'message' => $exception->getMessage(),
            ], 401);
        }

        // ================= LARAVEL AUTH EXCEPTION =================
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status'  => 'error',
                'code'    => 'UNAUTHENTICATED',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return parent::render($request, $exception);
    }
}
