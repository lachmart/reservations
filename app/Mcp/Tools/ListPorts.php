<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Port;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Response;

#[Title('List ports')]
#[IsReadOnly]
class ListPorts extends BaseTool
{
    public function description(): string
    {
        return 'List all ports with their gender and product counts.';
    }

    protected function run(array $arguments): Response
    {
        $ports = Port::withCount('products')->orderBy('name')->get();

        return Response::json([
            'count' => $ports->count(),
            'ports' => $ports->map(fn (Port $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'gender' => $p->gender?->value,
                'products_count' => $p->products_count,
            ])->all(),
        ]);
    }
}
