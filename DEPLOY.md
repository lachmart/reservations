# Deployment

Production host: **SSH-enabled shared hosting** (`api.marakarental.cz`).
Approach: build assets locally, `rsync` the code up, run Composer + Artisan on the
server. Automated by [`deploy.sh`](./deploy.sh).

## One-time server setup

Do these once, by hand, before the first `./deploy.sh`.

1. **Point the subdomain's document root at `.../public`.**
   Laravel must be served from its `public/` folder, never the app root. Set this in your
   hosting panel (subdomain / web root = `<REMOTE_PATH>/public`). If the panel forces a
   fixed docroot you can't change, tell me and I'll give you the `public`-relocation
   workaround.

2. **Create the app directory** on the server, e.g. `~/www/reservations`
   (this becomes `REMOTE_PATH`).

3. **Upload the environment file** (the script never uploads it — it holds secrets):
   ```bash
   scp .env.production <user>@api.marakarental.cz:~/www/reservations/.env
   ```
   Confirm on the server that `APP_ENV=production`, `APP_DEBUG=false`, the `DB_*` values,
   and `MCP_TOKEN` are correct. `APP_KEY` is already set in `.env.production`.

4. **Database**: the `DB_*` in `.env.production` point at `rezervace_27`. Ensure that DB
   exists and the user has access. First deploy will run migrations (`RUN_MIGRATIONS=1`),
   or import a dump manually via phpMyAdmin/Adminer and set `RUN_MIGRATIONS=0` initially.

5. **Ownership / writable dirs**: handled automatically on every deploy. You connect as
   **root**, but the web server needs files owned by `www-data`. After syncing and running
   Composer/Artisan (which, as root, create root-owned `vendor/` and caches), the script
   runs `chown -R www-data:www-data` over the app and makes `storage/` and
   `bootstrap/cache/` writable. Set `WEB_USER`/`WEB_GROUP` in `.deploy.env` if your host
   uses a different web user.

## Routine deploy

```bash
cp .deploy.env.example .deploy.env   # first time only — fill in host + path
./deploy.sh --dry-run                # preview what rsync will change
./deploy.sh                          # deploy for real
```

What `deploy.sh` does each run:
1. `npm ci && npm run build` locally (the host has no Node, so assets are built here).
2. `rsync` the code up — **excludes** `.env`, `vendor/`, `node_modules/`, `.git`, tests,
   and server-owned runtime dirs (uploaded media, logs, sessions, caches). `public/build`
   **is** shipped.
3. Over SSH (as root): `composer install --no-dev -o`, optional `migrate --force`,
   `storage:link`, and rebuilds config/route/view/Filament caches — wrapped in
   `artisan down`/`up` maintenance mode — then `chown -R www-data:www-data` so the web
   server owns everything.

> **rsync note:** macOS ships `openrsync`, which lacks `--chown`. The script detects this
> and falls back to not stamping your local UID; the server-side `chown` fixes ownership
> either way. Nothing to configure.

## MCP servers in production

The MCP endpoints (`/mcp/reservations`, `/mcp/admin` — see [MCP.md](./MCP.md)) ship with
the app, so they'll be live at `https://api.marakarental.cz/mcp/...` after deploy. Make
sure a **strong** `MCP_TOKEN` is set in the server `.env` (different from local). With
`APP_DEBUG=false` (now fixed), errors no longer leak that token.

## Rollback

`deploy.sh` doesn't keep releases. If a deploy breaks things, re-run it from a known-good
local commit (`git checkout <good-sha>` → `./deploy.sh`). If you want zero-downtime
timestamped releases with an instant symlink rollback, say so and I'll extend the script.
