<?php

namespace App\Http\Controllers;

use App\Mcp\Servers\PublicServer;
use Illuminate\Http\JsonResponse;
use Laravel\Mcp\Server\Tool;

/**
 * Server card describing the public MCP server, so agents can discover it.
 * Served under /.well-known/... and, because the hosting blocks dot-directories,
 * also under /mcp/server-card.json.
 */
class McpDiscoveryController extends Controller
{
    public function card(): JsonResponse
    {
        $server = new PublicServer;

        $tools = collect($server->tools)->map(function (string $class) {
            /** @var Tool $tool */
            $tool = app($class);

            return ['name' => $tool->name(), 'description' => $tool->description()];
        })->values()->all();

        return response()->json([
            'name' => 'cz.marakarental/reservations',
            'title' => 'Maraka rental: rezervace ozvučení a světel (Olomouc)',
            'description' => 'Browse sound, light and stage gear for rent in Olomouc, check availability for a term and create a reservation with items. Prices in CZK.',
            'version' => $server->serverVersion,
            'protocolVersions' => $server->supportedProtocolVersion,
            'websiteUrl' => url('/'),
            'documentationUrl' => route('ai.index'),
            'remotes' => [
                ['type' => 'streamable-http', 'url' => url('/mcp/reservations')],
            ],
            'authentication' => ['required' => false],
            'tools' => $tools,
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            // Explicit lifetime so agents and proxies don't keep a stale card.
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
