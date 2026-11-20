<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Advertises the MCP server card and llms.txt in an HTTP Link header on every
 * page, so agents find them with a plain GET or HEAD. Stands in for
 * /.well-known/mcp*, which the hosting blocks (403 on every dot path).
 */
class AddDiscoveryLinks
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $links = [
            '<'.url('/mcp/server-card.json').'>; rel="service-desc"; type="application/json"',
            '<'.url('/llms.txt').'>; rel="describedby"; type="text/plain"',
        ];

        $existing = $response->headers->get('Link');
        $response->headers->set('Link', implode(', ', array_filter([$existing, ...$links])));

        return $response;
    }
}
