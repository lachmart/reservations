<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\CheckProductAvailability;
use App\Mcp\Tools\CreateReservation;
use App\Mcp\Tools\ListProducts;

class PublicServer extends BaseServer
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
    This public server lets a customer (or an agent acting for one) book sound, light
    and stage gear from Maraka rental (Olomouc, Czech Republic). Prices are in CZK.

    Typical flow:
    1. list-products: find product ids, daily prices and bundles (is_bundle = ready-made sets).
    2. check-product-availability for each product and the term.
    3. create-reservation with all items at once: [{product_id, quantity}].
       The server computes the price (every started 24 hours is one day) and saves
       nothing if anything is short.

    Guidance:
    - Dates accept ISO-8601 (e.g. "2027-07-06" or "2027-07-06 14:00"); end_date must be
      after start_date. A one-day event is e.g. 2027-07-06 10:00 to 2027-07-07 10:00.
    - Give the customer's phone or e-mail (at least one), and the event place and type so
      transport can be priced. Pick a delivery_zone for transport (delivery and pick-up);
      installation, wiring or an operator on site are extra (service = true), quoted later.
    - New reservations are unconfirmed and unpaid until the rental company confirms them.
    - This endpoint cannot modify or delete existing reservations, nor manage the catalog.
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
