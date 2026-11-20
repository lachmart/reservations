<?php

use App\Http\Middleware\VerifyMcpToken;
use App\Mcp\Servers\AdminServer;
use App\Mcp\Servers\PublicServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Servers
|--------------------------------------------------------------------------
|
|   POST /mcp/reservations  Public booking server (no auth).
|   POST /mcp/admin         Full admin server (requires MCP_TOKEN bearer).
|
| Both are also runnable locally over STDIO:
|
|   php artisan mcp:start reservations
|   php artisan mcp:start admin
|
*/

Mcp::web('/mcp/reservations', PublicServer::class)
    ->middleware('throttle:60,1');

Mcp::web('/mcp/admin', AdminServer::class)
    ->middleware(VerifyMcpToken::class);

Mcp::local('reservations', PublicServer::class);
Mcp::local('admin', AdminServer::class);
