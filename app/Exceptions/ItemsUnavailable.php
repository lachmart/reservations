<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a booking asks for more units than are free in the requested term.
 * Nothing has been written when this is thrown.
 */
class ItemsUnavailable extends RuntimeException
{
    /**
     * @param  array<int, array{product_id:int, name:string, requested:int, available:int}>  $shortages
     */
    public function __construct(public readonly array $shortages)
    {
        $parts = array_map(
            fn (array $s) => "{$s['name']} (product_id {$s['product_id']}): requested {$s['requested']}, available {$s['available']}",
            $shortages,
        );

        parent::__construct('Not enough units available for the requested term: '.implode('; ', $parts).'.');
    }
}
