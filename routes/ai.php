<?php

use App\Http\Middleware\VerifyMcpToken;
use App\Mcp\Servers\AdminServer;
use App\Mcp\Servers\PublicServer;
use Laravel\Mcp\Server\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP Servers
|--------------------------------------------------------------------------
|
| Web routes here are automatically prefixed with "/mcp" by the package.
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

Mcp::web('reservations', PublicServer::class);

Mcp::web('admin', AdminServer::class)
    ->middleware(VerifyMcpToken::class);

Mcp::local('reservations', PublicServer::class);
Mcp::local('admin', AdminServer::class);
