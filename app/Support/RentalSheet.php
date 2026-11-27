<?php

namespace App\Support;

use App\Models\Reservation;

/**
 * What goes on a reservation's rental sheet (výpůjční list), the paper that is
 * checked when the gear is handed out and again when it comes back. Sets are
 * broken down into the pieces that actually travel; prices are left out.
 */
class RentalSheet
{
    /** Empty rows at the end of the table for things written in by hand. */
    public const BLANK_ROWS = 5;

    public function __construct(public readonly Reservation $reservation) {}

    /**
     * Booked items in booking order. A set is a 'set' heading followed by its
     * 'part' rows, each amount multiplied by how many sets were booked.
     *
     * @return list<array{kind: 'item'|'set'|'part', name: string, amount: int}>
     */
    public function rows(): array
    {
        $items = $this->reservation->reservationItems()
            ->with('product.bundleItems')
            ->orderBy('id')
            ->get()
            ->filter(fn ($item) => $item->product); // product deleted since

        $rows = [];
        foreach ($items as $item) {
            $amount = (int) $item->amount;
            $parts = $item->product->bundleItems;

            $rows[] = ['kind' => $parts->isEmpty() ? 'item' : 'set', 'name' => $item->product->name, 'amount' => $amount];

            foreach ($parts as $part) {
                $rows[] = ['kind' => 'part', 'name' => $part->name, 'amount' => (int) $part->pivot->amount * $amount];
            }
        }

        return $rows;
    }

    /**
     * Things added in the warehouse (not catalog products); empty form rows are
     * dropped and a missing amount means one.
     *
     * @return list<array{name: string, amount: int}>
     */
    public function extras(): array
    {
        return collect($this->reservation->extra_items ?? [])
            ->map(fn ($extra) => ['name' => trim((string) ($extra['name'] ?? '')), 'amount' => max(1, (int) ($extra['amount'] ?? 1))])
            ->filter(fn ($extra) => $extra['name'] !== '')
            ->values()
            ->all();
    }

    public function filename(): string
    {
        return "vypujcni-list-{$this->reservation->id}.pdf";
    }
}
