<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Port;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Attach product port')]
class AttachProductPort extends BaseTool
{
    public function description(): string
    {
        return 'Attach a port to a product (or update the amount if already attached), '
            .'storing the pivot amount.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'product_id' => $schema->integer()
                ->description('The product.')
                ->required(),
            'port_id' => $schema->integer()
                ->description('The port to attach.')
                ->required(),
            'amount' => $schema->integer()
                ->description('Pivot amount for this port (default 1).'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'port_id' => ['required', 'integer'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return Response::error("No product found with id {$arguments['product_id']}.");
        }

        if (! Port::whereKey($arguments['port_id'])->exists()) {
            return Response::error("No port found with id {$arguments['port_id']}.");
        }

        $amount = (int) ($arguments['amount'] ?? 1);

        $product->ports()->syncWithoutDetaching([
            $arguments['port_id'] => ['amount' => $amount],
        ]);

        return Response::json([
            'attached' => true,
            'product_id' => $product->id,
            'port_id' => (int) $arguments['port_id'],
            'amount' => $amount,
        ]);
    }
}
