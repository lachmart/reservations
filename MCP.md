# MCP servers

This app ships two [Model Context Protocol](https://modelcontextprotocol.io) servers
(built on [`laravel/mcp`](https://github.com/laravel/mcp)) so AI agents can drive the
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

**Admin** — everything above plus:
- Reservations: `list-reservations`, `get-reservation`, `create-reservation`,
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
