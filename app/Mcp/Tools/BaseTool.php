<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Generator;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolResult;
use Throwable;

/**
 * Base class for this application's MCP tools.
 *
 * It funnels every tool through run() and turns any unexpected exception into a
 * clean JSON-RPC tool error, so a database or runtime failure returns a usable
 * message to the agent instead of crashing the transport with an HTTP 500.
 */
abstract class BaseTool extends Tool
{
    final public function handle(array $arguments): ToolResult|Generator
    {
        try {
            return $this->run($arguments);
        } catch (ValidationException $e) {
            // Let the package format validation errors consistently.
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return ToolResult::error('The operation failed: '.$e->getMessage());
        }
    }

    /**
     * Execute the tool call.
     *
     * @return ToolResult|Generator<mixed>
     */
    abstract protected function run(array $arguments): ToolResult|Generator;
}
