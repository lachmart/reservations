<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Delete reservation')]
#[IsDestructive]
class DeleteReservation extends BaseTool
{
    public function description(): string
    {
        return 'Permanently delete a reservation and all of its line items. This cannot be undone.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The reservation id to delete.')
                ->required(),
        ];
    }

    protected function run(array $arguments): Response
    {
        $reservation = Reservation::find($arguments['id'] ?? null);

        if (! $reservation) {
            return Response::error('No reservation found with id '.($arguments['id'] ?? 'null').'.');
        }

        $reservation->reservationItems()->delete();
        $reservation->delete();

        return Response::json([
            'deleted' => true,
            'id' => $arguments['id'],
        ]);
    }
}
