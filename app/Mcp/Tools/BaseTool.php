<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Throwable;

/**
 * Base class for this application's MCP tools.
 *
 * Tools implement run() with the plain argument array. Any unexpected exception
 * becomes a tool error result with a usable message, instead of a JSON-RPC
 * internal error, so the agent can react to it.
 */
abstract class BaseTool extends Tool
{
    final public function handle(Request $request): Response
    {
        try {
            return $this->run($request->all());
        } catch (ValidationException $e) {
            // Let the package format validation errors consistently.
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return Response::error('The operation failed: '.$e->getMessage());
        }
    }

    /**
     * Execute the tool call with the raw arguments.
     */
    abstract protected function run(array $arguments): Response;
}
