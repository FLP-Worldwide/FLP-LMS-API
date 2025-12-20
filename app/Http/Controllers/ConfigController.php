<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;

class ConfigController extends Controller
{
    public function routes()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'methods'    => implode('|', $route->methods()),
                'uri'        => $route->uri(),
                'name'       => $route->getName(),
                'action'     => $route->getActionName(),
                'middleware' => $route->gatherMiddleware(),
            ];
        });

        return response()->json([
            'total_routes' => $routes->count(),
            'routes' => $routes,
        ]);
    }

    public function routesView()
    {
        $routes = collect(Route::getRoutes());

        return view('welcome', compact('routes'));
    }
}
