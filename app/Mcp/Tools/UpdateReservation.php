<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Attributes\Title;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;

#[Title('Update reservation')]
#[IsIdempotent]
class UpdateReservation extends BaseTool
{
    public function description(): string
    {
        return 'Update fields on an existing reservation. Only the fields you provide are '
            .'changed. Useful for marking a reservation paid/active or adjusting dates and price.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The reservation id to update.')
                ->required(),
            'name' => $schema->string()
                ->description('Customer name.'),
            'email' => $schema->string()
                ->description('Customer email.'),
            'phone' => $schema->string()
                ->description('Customer phone number.'),
            'start_date' => $schema->string()
                ->description('Reservation start (ISO-8601).'),
            'end_date' => $schema->string()
                ->description('Reservation end (ISO-8601).'),
            'price' => $schema->number()
                ->description('Total price.'),
            'note' => $schema->string()
                ->description('Free-form note.'),
            'is_active' => $schema->boolean()
                ->description('Whether the reservation is active.'),
            'is_paid' => $schema->boolean()
                ->description('Whether the reservation is paid.'),
        ];
    }

    protected function run(array $arguments): Response
    {
        $reservation = Reservation::find($arguments['id'] ?? null);

        if (! $reservation) {
            return Response::error('No reservation found with id '.($arguments['id'] ?? 'null').'.');
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
            return Response::error($validator->errors()->first());
        }

        $data = $validator->validated();

        $start = $data['start_date'] ?? optional($reservation->start_date)->toDateTimeString();
        $end = $data['end_date'] ?? optional($reservation->end_date)->toDateTimeString();

        if ($start && $end && strtotime($end) <= strtotime($start)) {
            return Response::error('end_date must be after start_date.');
        }

        if ($data === []) {
            return Response::error('No fields provided to update.');
        }

        $reservation->fill($data)->save();

        return Response::json([
            'updated' => true,
            'id' => $reservation->id,
            'changed_fields' => array_keys($data),
            'is_active' => $reservation->is_active,
            'is_paid' => $reservation->is_paid,
            'start_date' => \App\Support\LocalTime::iso($reservation->start_date),
            'end_date' => \App\Support\LocalTime::iso($reservation->end_date),
            'price' => $reservation->price,
        ]);
    }
}
