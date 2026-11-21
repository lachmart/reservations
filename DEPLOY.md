# Deployment

Production: **www.marakarental.cz on Váš Hosting, FTP only** (no SSH, no Composer on
the server, PHP 8.5). Deploy with [`deploy-ftp.sh`](./deploy-ftp.sh).

## How it works

- The whole project is uploaded into the web root (`/www`). The root `.htaccess` sends
  every request into `public/`, so `.env`, `vendor/` and the source are never served.
- `vendor/` is built locally (`composer install --no-dev`, platform pinned to PHP 8.3 in
  `composer.json`, so it runs on 8.3–8.5) and uploaded.
- Everything that used to be `php artisan …` runs through the app itself:
  `POST /_maintenance/{task}` with header `X-Maintenance-Token` (value =
  `MAINTENANCE_TOKEN` in the server `.env`; the endpoint is 404 without it), or the
  admin page **Údržba**. Tasks: `deploy` (migrate + caches + storage link), `migrate`,
  `optimize`, `storage-link`, and `import-database` (moving hosts only, refuses a
  non-empty database).

## Everyday deploy

```bash
./deploy-ftp.sh            # build, upload, migrate + caches, check the home page
./deploy-ftp.sh --dry-run  # show what would be uploaded
./deploy-ftp.sh --build-only
```

Needs locally: `lftp`, `rsync`, `npm`, `php` 8.3+ and `composer`. Config lives in the
git-ignored `.deploy-ftp.env` (template: `.deploy-ftp.env.example`). The upload deletes
stale files only inside the app folders; `storage/` (photos, logs), `public/storage`,
`.env` and anything else in the web root are never deleted.

If a deploy fails halfway, rerun it; or open Admin → Údržba → "Po nahrání".

## Server .env

Kept locally as `.env.production` (git-ignored). With `UPLOAD_ENV=1` the script uploads
it as the server `.env` on every deploy, so change settings there, not on the server.
Must contain `MAINTENANCE_TOKEN` (same value as in `.deploy-ftp.env`).

If the host ever refuses the `public/storage` symlink, set `PUBLIC_STORAGE_IN_PUBLIC=true`
and keep product photos in `public/storage` instead (Váš Hosting allows the symlink).

## Moving to another host (done once, 2026-11-20)

1. Create the database and FTP account; put them into `.env.production` / `.deploy-ftp.env`.
2. Dump the old database (gzip SQL, no `LOCK TABLES`) and copy the old
   `storage/app/public` locally.
3. In `.deploy-ftp.env` set `SITE_IP` (new server), `MOVE_DUMP` and `MOVE_MEDIA`, then run
   `./deploy-ftp.sh`. It imports the dump into the empty database, uploads the photos and
   runs the deploy tasks; checks go to the new server by IP over HTTP.
4. Test the new server by IP, then switch DNS. After the switch: clear `MOVE_*` and
   `SITE_IP`, make sure HTTPS is issued for the domain, and copy over any reservations
   made on the old server in the meantime.
