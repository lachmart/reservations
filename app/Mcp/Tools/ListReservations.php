<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('List reservations')]
#[IsReadOnly]
class ListReservations extends BaseTool
{
    public function description(): string
    {
        return 'List reservations, optionally filtered by paid/active status, customer '
            .'email, or an overlapping date range. Returns the most recent matches first.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->boolean('is_active')->description('Only reservations with this active flag.')
            ->boolean('is_paid')->description('Only reservations with this paid flag.')
            ->string('email')->description('Match reservations for this customer email (partial match).')
            ->string('from')->description('Include reservations overlapping on/after this date (ISO-8601).')
            ->string('to')->description('Include reservations overlapping on/before this date (ISO-8601).')
            ->integer('limit')->description('Maximum number of reservations to return (default 25, max 100).');
    }

    protected function run(array $arguments): ToolResult
    {
        $query = Reservation::query()->withCount('reservationItems');

        if (array_key_exists('is_active', $arguments)) {
            $query->where('is_active', (bool) $arguments['is_active']);
        }

        if (array_key_exists('is_paid', $arguments)) {
            $query->where('is_paid', (bool) $arguments['is_paid']);
        }

        if (! empty($arguments['email'])) {
            $query->where('email', 'like', '%'.$arguments['email'].'%');
        }

        if (! empty($arguments['from'])) {
            $query->where('end_date', '>=', $arguments['from']);
        }

        if (! empty($arguments['to'])) {
            $query->where('start_date', '<=', $arguments['to']);
        }

        $limit = (int) ($arguments['limit'] ?? 25);
        $limit = max(1, min($limit, 100));

        $reservations = $query->orderByDesc('id')->limit($limit)->get();

        return ToolResult::json([
            'count' => $reservations->count(),
            'reservations' => $reservations->map(fn (Reservation $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'phone' => $r->phone,
                'start_date' => optional($r->start_date)->toIso8601String(),
                'end_date' => optional($r->end_date)->toIso8601String(),
                'price' => $r->price,
                'is_active' => $r->is_active,
                'is_paid' => $r->is_paid,
                'items_count' => $r->reservation_items_count,
            ])->all(),
        ]);
    }
}
