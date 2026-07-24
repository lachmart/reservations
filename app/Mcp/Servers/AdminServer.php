<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddReservationItem;
use App\Mcp\Tools\AttachProductPhoto;
use App\Mcp\Tools\AttachProductPort;
use App\Mcp\Tools\CheckProductAvailability;
use App\Mcp\Tools\CreateCategory;
use App\Mcp\Tools\CreatePort;
use App\Mcp\Tools\CreateProduct;
use App\Mcp\Tools\CreateReservation;
use App\Mcp\Tools\CreateTag;
use App\Mcp\Tools\DeleteCategory;
use App\Mcp\Tools\DeleteProduct;
use App\Mcp\Tools\DeleteReservation;
use App\Mcp\Tools\DeleteTag;
use App\Mcp\Tools\GetReservation;
use App\Mcp\Tools\ListCategories;
use App\Mcp\Tools\ListPorts;
use App\Mcp\Tools\ListProducts;
use App\Mcp\Tools\ListReservations;
use App\Mcp\Tools\ListTags;
use App\Mcp\Tools\SyncProductTags;
use App\Mcp\Tools\UpdateProduct;
use App\Mcp\Tools\UpdateReservation;
use App\Mcp\Tools\UploadProductPhoto;
use Laravel\Mcp\Server;

class AdminServer extends Server
{
    /**
     * The name of the MCP server.
     */
    public string $serverName = 'Reservations Admin';

    /**
     * The version of the MCP server.
     */
    public string $serverVersion = '1.0.0';

    /**
     * Instructions given to the AI agent connecting to this server.
     */
    public string $instructions = <<<'TXT'
    This authenticated server administers the whole rental reservations system: customer
    reservations and their line items, the product catalog, and the linked entities
    (categories, tags and ports).

    Guidance:
    - Dates are full timestamps and accept ISO-8601; end_date must be after start_date.
    - Before adding a line item, use check-product-availability to confirm enough stock
      exists for the reservation's date range (add-reservation-item enforces this too).
    - Price fields are decimals in the store's currency.
    - Deleting a reservation is permanent and also removes its line items.
    - Products link to tags via set-product-tags and to ports via attach-product-port.
    TXT;

    /**
     * The tools exposed by this server.
     *
     * @var array<int, class-string>
     */
    public array $tools = [
        // Reservations
        ListReservations::class,
        GetReservation::class,
        CreateReservation::class,
        UpdateReservation::class,
        DeleteReservation::class,
        AddReservationItem::class,

        // Catalog
        ListProducts::class,
        CreateProduct::class,
        UpdateProduct::class,
        DeleteProduct::class,
        CheckProductAvailability::class,
        AttachProductPhoto::class,
        UploadProductPhoto::class,

        // Linked entities
        ListCategories::class,
        CreateCategory::class,
        DeleteCategory::class,
        ListTags::class,
        CreateTag::class,
        DeleteTag::class,
        ListPorts::class,
        CreatePort::class,
        SyncProductTags::class,
        AttachProductPort::class,
    ];
}
