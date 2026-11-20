<?php

declare(strict_types=1);

namespace App\Mcp\Methods;

use Laravel\Mcp\Server\Exceptions\JsonRpcException;
use Laravel\Mcp\Server\Methods\CallTool as BaseCallTool;
use Laravel\Mcp\Server\ServerContext;
use Laravel\Mcp\Server\Transport\JsonRpcRequest;

/**
 * tools/call that reports protocol-level problems as JSON-RPC errors, as the MCP
 * spec requires: an unknown tool or a malformed request is error -32602, not a
 * tool result with isError. Errors raised by the tool itself stay tool results.
 */
class CallTool extends BaseCallTool
{
    public function handle(JsonRpcRequest $request, ServerContext $context)
    {
        $name = $request->params['name'] ?? null;

        if (! is_string($name) || $name === '') {
            throw new JsonRpcException('Invalid params: "name" is required.', -32602, $request->id);
        }

        if (! $context->tools()->contains(fn ($tool) => $tool->name() === $name)) {
            throw new JsonRpcException("Unknown tool: {$name}", -32602, $request->id);
        }

        $arguments = $request->params['arguments'] ?? [];

        if (! is_array($arguments)) {
            throw new JsonRpcException('Invalid params: "arguments" must be an object.', -32602, $request->id);
        }

        return parent::handle(
            new JsonRpcRequest($request->id, $request->method, ['arguments' => $arguments] + $request->params),
            $context,
        );
    }
}
