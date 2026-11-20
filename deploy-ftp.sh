#!/usr/bin/env bash
#
# Deploy to FTP-only hosting (no SSH, no Composer on the server).
#
#   ./deploy-ftp.sh              build, upload, run migrations + caches
#   ./deploy-ftp.sh --build-only build into .build/ and stop (inspect it)
#   ./deploy-ftp.sh --dry-run    build and show what would be uploaded
#
# Config: .deploy-ftp.env (git-ignored), see .deploy-ftp.env.example.
# Needs locally: lftp, rsync, npm, php 8.3+ and composer (run on the host).
#
# What it does:
#   1. npm run build (assets) and a clean copy of the app into .build/
#   2. composer install --no-dev inside .build/ (vendor/ is uploaded)
#   3. lftp mirror per app folder. It deletes only inside the app folders, never
#      storage/ (photos, logs, sessions), public/storage, .env or anything the
#      hosting keeps in the web root.
#   4. POST $SITE_URL/_maintenance/deploy (migrations, caches, storage link)
#
# The whole project goes into FTP_PATH (the web root); the root .htaccess sends
# every request into public/, so the hosting's web root doesn't have to be public/.

set -euo pipefail
cd "$(dirname "$0")"

MODE=deploy
[[ "${1:-}" == "--build-only" ]] && MODE=build
[[ "${1:-}" == "--dry-run" ]] && MODE=dry

info() { printf '\033[1;34m→\033[0m %s\n' "$*"; }
ok()   { printf '\033[0;32m✓\033[0m %s\n' "$*"; }
die()  { printf '\033[0;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

# --- Config ---------------------------------------------------------------------
if [[ "$MODE" != build ]]; then
  [[ -f .deploy-ftp.env ]] || die "Missing .deploy-ftp.env (copy .deploy-ftp.env.example)."
  # shellcheck disable=SC1091
  source .deploy-ftp.env
  : "${FTP_HOST:?FTP_HOST missing}" "${FTP_USER:?FTP_USER missing}" "${FTP_PATH:?FTP_PATH missing}"
  : "${SITE_URL:?SITE_URL missing}" "${MAINTENANCE_TOKEN:?MAINTENANCE_TOKEN missing}"
  FTP_PROTOCOL="${FTP_PROTOCOL:-ftp}"    # ftp (with TLS if offered) | ftps | sftp
  UPLOAD_ENV="${UPLOAD_ENV:-0}"          # 1 = upload .env.production as .env
  [[ -n "${FTP_PASSWORD:-}" ]] || die "FTP_PASSWORD missing in .deploy-ftp.env."
  command -v lftp >/dev/null || die "lftp not found (brew install lftp)."
fi
for bin in rsync npm composer php; do command -v "$bin" >/dev/null || die "'$bin' not found."; done

BUILD=.build

# --- 1. Assets + clean copy ---------------------------------------------------------
info "Building assets…"
npm run build >/dev/null
ok "Assets built."

info "Copying the app into ${BUILD}/…"
rsync -a --delete --delete-excluded \
  --exclude '/.git' --exclude '/.github' --exclude '/.build' --exclude '/.ddev' \
  --exclude '/.claude' --exclude '/.idea' --exclude '/.vscode' --exclude '/.fleet' \
  --exclude '/node_modules' --exclude '/vendor' --exclude '/tests' \
  --exclude '.env' --exclude '.env.*' --exclude '*.env' --exclude '.deploy*' \
  --exclude '/deploy.sh' --exclude '/deploy-ftp.sh' --exclude '/scripts' \
  --exclude '/public/hot' --exclude '/public/storage' \
  --exclude '/storage/**' --exclude '/bootstrap/cache/*' \
  --exclude '.DS_Store' --exclude '*.log' --exclude '/*.xlsx' --exclude '/auth.json' \
  --exclude '/.phpunit*' --exclude '/phpunit.xml' --exclude '/.mcp.json' --exclude '/.blueprint' \
  --exclude '/draft.yaml' --exclude '/.impeccable.md' \
  ./ "${BUILD}/"

# Empty runtime folders the app needs on the server (uploaded only if missing).
for d in app/public framework/cache/data framework/sessions framework/views logs; do
  mkdir -p "${BUILD}/storage/$d"
done
mkdir -p "${BUILD}/bootstrap/cache"

info "Installing production dependencies (composer --no-dev)…"
# Runs on the host, not in DDEV: DDEV's file sync (mutagen) lags behind on macOS,
# so a build made in the container isn't reliably complete on disk yet.
# (An older host Composer may call the lock "not up to date" because it hashes it
# differently; the locked versions are installed either way, so that line is hidden.)
(cd "${BUILD}" && composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet 2>&1 \
  | grep -v 'lock file is not up to date' || true)
[[ -f "${BUILD}/vendor/autoload.php" && -f "${BUILD}/bootstrap/cache/packages.php" ]] \
  || die "Build incomplete: vendor/ or bootstrap/cache/packages.php missing."

# Only Composer's package manifests may ship; config/route caches are built on the server.
find "${BUILD}/bootstrap/cache" -mindepth 1 ! -name 'packages.php' ! -name 'services.php' ! -name '.gitignore' -exec rm -rf {} +
ok "Build ready in ${BUILD}/ ($(du -sh "$BUILD" | cut -f1))."

[[ "$MODE" == build ]] && exit 0

# --- 2. Upload ------------------------------------------------------------------------
DRY=""
[[ "$MODE" == dry ]] && DRY="--dry-run"

case "$FTP_PROTOCOL" in
  ftps) URL="ftps://${FTP_HOST}" ;;
  sftp) URL="sftp://${FTP_HOST}" ;;
  *)    URL="ftp://${FTP_HOST}" ;;
esac

# Folders mirrored with --delete (stale code must go). public/storage is kept.
MIRRORS=""
for d in app bootstrap config database resources routes vendor; do
  MIRRORS+="mirror -R --delete --parallel=${FTP_PARALLEL:-2} --no-perms --verbose=1 ${DRY} ${BUILD}/$d $d
"
done
MIRRORS+="mirror -R --delete --parallel=${FTP_PARALLEL:-2} --no-perms --verbose=1 ${DRY} --exclude-glob storage/ ${BUILD}/public public
"
# Runtime folders: create what's missing, never overwrite or delete.
MIRRORS+="mirror -R --only-missing --no-perms ${DRY} ${BUILD}/storage storage
"

ROOT_FILES="mput -O . ${BUILD}/.htaccess ${BUILD}/artisan ${BUILD}/composer.json"
[[ -n "$DRY" ]] && ROOT_FILES="!echo '(dry run) would upload .htaccess artisan composer.json'"

# One-off when moving hosts: MOVE_DUMP (gzip SQL) is uploaded for the guarded
# import step, MOVE_MEDIA (a copy of the old storage/app/public) is uploaded
# without deleting anything.
MOVE=""
if [[ -n "${MOVE_DUMP:-}" ]]; then
  [[ -f "$MOVE_DUMP" ]] || die "MOVE_DUMP not found: $MOVE_DUMP"
  MOVE+="put -O storage/app ${DRY:+--dry-run }\"$MOVE_DUMP\" -o import.sql.gz
"
  [[ -n "$DRY" ]] && MOVE="!echo '(dry run) would upload the database dump'
"
fi
if [[ -n "${MOVE_MEDIA:-}" ]]; then
  [[ -d "$MOVE_MEDIA" ]] || die "MOVE_MEDIA not found: $MOVE_MEDIA"
  MOVE+="mirror -R --only-newer --parallel=${FTP_PARALLEL:-2} --no-perms ${DRY} \"${MOVE_MEDIA%/}\" storage/app/public
"
fi

ENV_UPLOAD=""
if [[ "$UPLOAD_ENV" == "1" ]]; then
  [[ -f .env.production ]] || die "UPLOAD_ENV=1 but .env.production is missing."
  ENV_UPLOAD="put -O . .env.production -o .env"
  [[ -n "$DRY" ]] && ENV_UPLOAD="!echo '(dry run) would upload .env.production as .env'"
fi

info "Uploading to ${FTP_PROTOCOL}://${FTP_HOST}${FTP_PATH} ${DRY:+(dry run)}…"
# The password goes through the environment, not the command line.
export LFTP_PASSWORD="$FTP_PASSWORD"
lftp <<LFTP
set cmd:fail-exit yes
set net:max-retries 8
set net:reconnect-interval-base 5
set net:reconnect-interval-max 30
set net:timeout 30
set ftp:ssl-allow yes
set ssl:verify-certificate ${FTP_VERIFY_CERT:-yes}
open --env-password -u "${FTP_USER}" "${URL}"
mkdir -p -f "${FTP_PATH}"
cd "${FTP_PATH}"
${MIRRORS}
${ROOT_FILES}
${ENV_UPLOAD}
${MOVE}
bye
LFTP
ok "Upload finished."

[[ "$MODE" == dry ]] && exit 0

# --- 3. Migrations + caches on the server --------------------------------------------
# SITE_IP: talk to the new server before DNS points at it (plain HTTP, because
# the domain's certificate is only issued after the DNS switch).
CHECK_URL="${SITE_URL%/}"
CURL_EXTRA=()
if [[ -n "${SITE_IP:-}" ]]; then
  host="${SITE_URL#*://}"; host="${host%%/*}"
  CURL_EXTRA=(--resolve "${host}:80:${SITE_IP}" --resolve "${host}:443:${SITE_IP}")
  CHECK_URL="http://${host}"
fi
maint() {
  info "Server task: $1…"
  curl -fsS "${CURL_EXTRA[@]}" -X POST -H "X-Maintenance-Token: ${MAINTENANCE_TOKEN}" "${CHECK_URL}/_maintenance/$1" \
    || die "Server task '$1' failed (see output above). The new code is uploaded; fix and rerun, or use Admin → Údržba."
}

[[ -n "${MOVE_DUMP:-}" ]] && maint import-database
maint deploy

code=$(curl -s "${CURL_EXTRA[@]}" -o /dev/null -w '%{http_code}' "${CHECK_URL}/")
[[ "$code" == 200 ]] || die "Home page answers ${code}."
ok "Deploy complete → ${CHECK_URL}${SITE_IP:+ (via ${SITE_IP})}"
