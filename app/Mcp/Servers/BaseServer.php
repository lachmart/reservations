<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Methods\CallTool;
use Laravel\Mcp\Server;

/**
 * Fixes shared by both MCP servers on top of laravel/mcp v0.1.
 */
abstract class BaseServer extends Server
{
    public function boot()
    {
        $this->addMethod('tools/call', CallTool::class);
    }

    /**
     * Version negotiation per the MCP spec: when a client asks for a protocol version
     * we don't know (e.g. a newer one), answer with our latest instead of failing the
     * handshake. The package rejects it with "Unsupported protocol version".
     */
    public function handle(string $rawMessage)
    {
        $data = json_decode($rawMessage, true);

        if (is_array($data)
            && ($data['method'] ?? null) === 'initialize'
            && isset($data['params']['protocolVersion'])
            && ! in_array($data['params']['protocolVersion'], $this->supportedProtocolVersion, true)
        ) {
            $data['params']['protocolVersion'] = $this->supportedProtocolVersion[0];
            $rawMessage = json_encode($data);
        }

        return parent::handle($rawMessage);
    }
}
