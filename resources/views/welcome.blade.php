<!DOCTYPE html>
<html>
<head>
    <title>API Routes</title>
    <style>
        body { font-family: Arial; font-size: 14px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>

<h2>API Routes (Laravel 12)</h2>

@php
    $apiRoutes = collect(\Route::getRoutes())->filter(function ($route) {
        return in_array('api', $route->gatherMiddleware());
    });
@endphp

<p>Total API Routes: {{ $apiRoutes->count() }}</p>

<table>
    <thead>
        <tr>
            <th>Method</th>
            <th>URI</th>
            <th>Action</th>
            <th>Middleware</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($apiRoutes as $route)
            <tr>
                <td>{{ implode('|', $route->methods()) }}</td>
                <td>{{ $route->uri() }}</td>
                <td>{{ $route->getActionName() }}</td>
                <td>{{ implode(', ', $route->gatherMiddleware()) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
