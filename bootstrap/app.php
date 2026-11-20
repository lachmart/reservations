<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Outside the "web" group: no session, cookies, CSRF or cache-backed
        // throttling, all of which need database tables that an empty database
        // (first deploy, moving hosts) doesn't have yet.
        then: function () {
            \Illuminate\Support\Facades\Route::post('/_maintenance/{task}', \App\Http\Controllers\MaintenanceController::class)
                ->whereIn('task', array_keys(\App\Support\Maintenance::TASKS + \App\Support\Maintenance::SCRIPT_TASKS));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Redirect old/non-canonical hosts (api.*) to the canonical APP_URL host.
        $middleware->prepend(\App\Http\Middleware\EnforceCanonicalDomain::class);

        // Point agents at the MCP server card and llms.txt from every page.
        $middleware->web(append: [
            \App\Http\Middleware\AddDiscoveryLinks::class,
        ]);

        $middleware->api(append: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
