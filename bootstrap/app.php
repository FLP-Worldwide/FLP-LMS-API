<?php

use App\Exceptions\Handler;
use App\Http\Middleware\SetInstituteContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'set.institute' => SetInstituteContext::class,
            'auth.jwt' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {

            $previous = $e->getPrevious();

            if ($previous instanceof TokenExpiredException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_EXPIRED',
                    'message' => 'Your session has expired. Please login again.',
                ], 401);
            }

            if ($previous instanceof TokenInvalidException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_INVALID',
                    'message' => 'Invalid token.',
                ], 401);
            }

            if ($previous instanceof JWTException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOKEN_MISSING',
                    'message' => 'Authorization token not found.',
                ], 401);
            }

            return response()->json([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED',
                'message' => 'Unauthorized.',
            ], 401);
        });

    })->create();
