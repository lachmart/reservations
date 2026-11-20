<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Reservation;
use Laravel\Mcp\Server\Tools\Annotations\Title;

/**
 * Admin flavour of create-reservation: items are optional (add more later with
 * add-reservation-item) and price / active / paid may be overridden.
 */
#[Title('Create reservation')]
class AdminCreateReservation extends CreateReservation
{
    public function name(): string
    {
        return 'create-reservation';
    }

    public function description(): string
    {
        return 'Create a reservation, optionally with items (availability is checked and the '
            .'price computed from them). Add more items later with add-reservation-item. '
            .'price, is_active and is_paid override the computed values. Sends no e-mails.';
    }

    protected function isAdmin(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'items' => ['nullable', 'array'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
        ]);
    }

    protected function afterCreate(Reservation $reservation, array $data): void
    {
        $overrides = array_filter(
            array_intersect_key($data, array_flip(self::ADMIN_FIELDS)),
            fn ($v) => ! is_null($v),
        );

        if ($overrides) {
            $reservation->update($overrides);
        }
    }
}
