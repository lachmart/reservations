<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Server\Tools\Annotations\Title;
use Laravel\Mcp\Server\Tools\ToolInputSchema;
use Laravel\Mcp\Server\Tools\ToolResult;

#[Title('Create reservation')]
class CreateReservation extends BaseTool
{
    public function description(): string
    {
        return 'Create a new customer reservation. Add line items afterwards with '
            .'add-reservation-item.';
    }

    public function schema(ToolInputSchema $schema): ToolInputSchema
    {
        return $schema
            ->string('name')->description('Customer name.')->required()
            ->string('email')->description('Customer email.')->required()
            ->string('phone')->description('Customer phone number.')
            ->string('start_date')->description('Reservation start (ISO-8601).')->required()
            ->string('end_date')->description('Reservation end (ISO-8601), must be after start_date.')->required()
            ->number('price')->description('Total price. Defaults to 0 when omitted.')
            ->string('note')->description('Free-form note.')
            ->boolean('is_active')->description('Whether the reservation is active. Defaults to true.')
            ->boolean('is_paid')->description('Whether the reservation is paid. Defaults to false.');
    }

    protected function run(array $arguments): ToolResult
    {
        $validator = Validator::make($arguments, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ToolResult::error($validator->errors()->first());
        }

        $data = $validator->validated();

        $reservation = Reservation::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'price' => $data['price'] ?? 0,
            // The live `note` column is NOT NULL, so fall back to an empty string.
            'note' => $data['note'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'is_paid' => $data['is_paid'] ?? false,
        ]);

        return ToolResult::json([
            'created' => true,
            'id' => $reservation->id,
            'name' => $reservation->name,
            'email' => $reservation->email,
            'start_date' => optional($reservation->start_date)->toIso8601String(),
            'end_date' => optional($reservation->end_date)->toIso8601String(),
        ]);
    }
}
