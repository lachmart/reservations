<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Port;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Attach product port')]
class AttachProductPort extends BaseTool
{
    public function description(): string
    {
        return 'Attach a port to a product (or update the amount if already attached), '
            .'storing the pivot amount.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('product_id')->description('The product.')->required()
            ->integer('port_id')->description('The port to attach.')->required()
            ->integer('amount')->description('Pivot amount for this port (default 1).');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'product_id' => ['required', 'integer'],
            'port_id' => ['required', 'integer'],
            'amount' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $product = Product::find($arguments['product_id']);

        if (! $product) {
            return ToolResult::error("No product found with id {$arguments['product_id']}.");
        }

        if (! Port::whereKey($arguments['port_id'])->exists()) {
            return ToolResult::error("No port found with id {$arguments['port_id']}.");
        }

        $amount = (int) ($arguments['amount'] ?? 1);

        $product->ports()->syncWithoutDetaching([
            $arguments['port_id'] => ['amount' => $amount],
        ]);

        return ToolResult::json([
            'attached' => true,
            'product_id' => $product->id,
            'port_id' => (int) $arguments['port_id'],
            'amount' => $amount,
        ]);
    }
}
