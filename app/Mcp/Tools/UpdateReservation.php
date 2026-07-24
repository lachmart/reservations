<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Update reservation')]
#[IsIdempotent]
class UpdateReservation extends BaseTool
{
    public function description(): string
    {
        return 'Update fields on an existing reservation. Only the fields you provide are '
            .'changed. Useful for marking a reservation paid/active or adjusting dates and price.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->integer('id')->description('The reservation id to update.')->required()
            ->string('name')->description('Customer name.')
            ->string('email')->description('Customer email.')
            ->string('phone')->description('Customer phone number.')
            ->string('start_date')->description('Reservation start (ISO-8601).')
            ->string('end_date')->description('Reservation end (ISO-8601).')
            ->number('price')->description('Total price.')
            ->string('note')->description('Free-form note.')
            ->boolean('is_active')->description('Whether the reservation is active.')
            ->boolean('is_paid')->description('Whether the reservation is paid.');
    }

    protected function run(array $arguments): ToolResult
    {
        $reservation = Reservation::find($arguments['id'] ?? null);

        if (! $reservation) {
            return ToolResult::error('No reservation found with id '.($arguments['id'] ?? 'null').'.');
        }

        $validator = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'note' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_paid' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $data = $validator->validated();

        $start = $data['start_date'] ?? optional($reservation->start_date)->toDateTimeString();
        $end = $data['end_date'] ?? optional($reservation->end_date)->toDateTimeString();

        if ($start && $end && strtotime($end) <= strtotime($start)) {
            return ToolResult::error('end_date must be after start_date.');
        }

        if ($data === []) {
            return ToolResult::error('No fields provided to update.');
        }

        $reservation->fill($data)->save();

        return ToolResult::json([
            'updated' => true,
            'id' => $reservation->id,
            'changed_fields' => array_keys($data),
            'is_active' => $reservation->is_active,
            'is_paid' => $reservation->is_paid,
            'start_date' => optional($reservation->start_date)->toIso8601String(),
            'end_date' => optional($reservation->end_date)->toIso8601String(),
            'price' => $reservation->price,
        ]);
    }
}
