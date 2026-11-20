# MCP servers

This app ships two [Model Context Protocol](https://modelcontextprotocol.io) servers
(built on [`laravel/mcp`](https://github.com/laravel/mcp) 1.x) so AI agents can drive the
reservation system. Servers and tools live in `app/Mcp`, and are registered in
`routes/ai.php`.

## Endpoints

| Server   | HTTP endpoint (web transport)              | Auth                        | Purpose |
|----------|--------------------------------------------|-----------------------------|---------|
| Public   | `POST /mcp/reservations`                   | none                        | Customer booking: browse products, check availability, create a reservation. |
| Admin    | `POST /mcp/admin`                          | `Authorization: Bearer …`   | Full administration of reservations, products, and linked entities. |

Both are also runnable locally over STDIO:

```bash
ddev artisan mcp:start reservations   # public
ddev artisan mcp:start admin          # admin
```

## Authentication

The admin endpoint is guarded by `App\Http\Middleware\VerifyMcpToken`, which requires a
bearer token equal to `MCP_TOKEN` (see `config/mcp.php`). Set it in `.env`:

```
MCP_TOKEN=<a long random secret>
```

If `MCP_TOKEN` is empty the admin endpoint refuses all requests (503). The public
endpoint needs no token.

## Tools

**Public** — `list-products`, `check-product-availability`, `create-reservation`.

Public `create-reservation` takes all items at once (`items: [{product_id, quantity}]`,
bundles are expanded) plus contact (phone or e-mail), term, `event_place`, `event_type`,
`delivery_zone` (zones and prices in `config/contact.php`; the zone price is added to the
total, `dal` is quoted later) and `note`. `list-products` returns each product's `url` and,
for sets, their `components`. It goes through `App\Services\ReservationBooking`, the same code
as the web cart: availability is checked and the reservation and items are saved in one
transaction, the price is computed on the server, the reservation starts active and
unpaid, and the customer and owner are e-mailed. `price`, `is_paid` and `is_active` are
rejected. It is rate limited to 5 reservations per IP per 10 minutes, and the whole
endpoint to 60 requests per minute.

Protocol: built on `laravel/mcp` 1.x, which negotiates the protocol version (latest
2025-11-25) and answers an unknown tool with JSON-RPC error `-32602` (HTTP 400) itself.
Tools extend `App\Mcp\Tools\BaseTool` (implement `run(array $arguments): Response`
and `schema(JsonSchema $schema): array`).

Discovery: the server card is at `/mcp/server-card.json` (and `/.well-known/mcp*`, which
the hosting currently blocks).

**Admin** — everything above plus:
- Reservations: `list-reservations`, `get-reservation`, `create-reservation` (items
  optional, may override `price`/`is_active`/`is_paid`, sends no e-mail),
  `update-reservation`, `delete-reservation`, `add-reservation-item`
- Catalog: `create-product`, `update-product`
- Linked entities: `list-categories`, `create-category`, `list-tags`, `create-tag`,
  `list-ports`, `create-port`, `sync-product-tags`, `attach-product-port`

## Connecting Claude Code

`.mcp.json` in the project root wires both servers up. The admin server reads the token
from the `MCP_TOKEN` environment variable, so export it before launching Claude Code:

```bash
export MCP_TOKEN=$(grep '^MCP_TOKEN=' .env | cut -d= -f2)
```

(DDEV serves the app over a locally-trusted HTTPS certificate. If you connect from an
environment that doesn't trust the DDEV/mkcert CA, install it with `mkcert -install`.)
