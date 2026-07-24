<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CheckProductAvailability;
use App\Mcp\Tools\CreateReservation;
use App\Mcp\Tools\ListProducts;
use Laravel\Mcp\Server;

class PublicServer extends Server
{
    /**
     * The name of the MCP server.
     */
    public string $serverName = 'Reservations (Public Booking)';

    /**
     * The version of the MCP server.
     */
    public string $serverVersion = '1.0.0';

    /**
     * Instructions given to the AI agent connecting to this server.
     */
    public string $instructions = <<<'TXT'
    This public server lets a customer (or an agent acting for one) book a rental
    reservation. You can browse available products, check how much of a product is free
    for a date range, and create a reservation.

    Guidance:
    - Dates accept ISO-8601 (e.g. "2026-08-01" or "2026-08-01 14:00"); end_date must be
      after start_date.
    - Check availability with check-product-availability before promising a booking.
    - This endpoint cannot modify or delete existing reservations, nor manage the catalog;
      those actions require the authenticated admin server.
    TXT;

    /**
     * The tools exposed by this server.
     *
     * @var array<int, class-string>
     */
    public array $tools = [
        ListProducts::class,
        CheckProductAvailability::class,
        CreateReservation::class,
    ];
}
