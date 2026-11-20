<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Reservation times are entered and stored as Czech wall-clock time, while the
 * app may run in UTC. When we print them with an offset (MCP), label them with
 * the Prague offset instead of +00:00, so an agent doesn't shift 10:00 to 11:00.
 */
class LocalTime
{
    public const ZONE = 'Europe/Prague';

    public static function iso(?DateTimeInterface $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::instance($value)->shiftTimezone(self::ZONE)->toIso8601String();
    }
}
