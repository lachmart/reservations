<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMcpToken
{
    /**
     * Guard the MCP web transport with a static bearer token.
     *
     * The expected token is read from the MCP_TOKEN env value. If no token is
     * configured the endpoint is refused entirely rather than left wide open.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('mcp.token');

        if (empty($expected)) {
            abort(503, 'MCP server is not configured (missing MCP_TOKEN).');
        }

        $provided = $request->bearerToken() ?? $request->header('X-MCP-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            abort(401, 'Invalid or missing MCP token.');
        }

        return $next($request);
    }
}
