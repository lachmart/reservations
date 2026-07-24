<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Delete reservation')]
#[IsDestructive]
class DeleteReservation extends BaseTool
{
    public function description(): string
    {
        return 'Permanently delete a reservation and all of its line items. This cannot be undone.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The reservation id to delete.')->required();
    }

    protected function run(array $arguments): ToolResult
    {
        $reservation = Reservation::find($arguments['id'] ?? null);

        if (! $reservation) {
            return ToolResult::error('No reservation found with id '.($arguments['id'] ?? 'null').'.');
        }

        $reservation->reservationItems()->delete();
        $reservation->delete();

        return ToolResult::json([
            'deleted' => true,
            'id' => $arguments['id'],
        ]);
    }
}
