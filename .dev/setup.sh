#!/usr/bin/env bash
#
# Stand up a local WordPress with the plugin mounted, and seed fixtures.
# Idempotent: safe to re-run.
#
# Usage:
#   ./dev/setup.sh          # start stack + install WP + activate + seed
#   ./dev/setup.sh reset    # destroy volumes first (fresh DB)
#
set -euo pipefail

cd "$(dirname "$0")/.."

DC="docker compose"
WP="$DC run --rm wpcli"
SITE_URL="http://localhost:8080"

if [[ "${1:-}" == "reset" ]]; then
  echo ">> Destroying volumes (fresh database)..."
  $DC down -v
fi

echo ">> Starting containers..."
$DC up -d db wordpress
echo ">> Waiting for WordPress to be reachable..."
for _ in $(seq 1 30); do
  if curl -s -o /dev/null "$SITE_URL/"; then break; fi
  sleep 2
done

echo ">> Installing WordPress (skipped if already installed)..."
if ! $WP core is-installed 2>/dev/null; then
  $WP core install \
    --url="$SITE_URL" \
    --title="Footnotes Dev" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.com \
    --skip-email
else
  echo "   already installed."
fi

echo ">> Activating plugin..."
$WP plugin activate footnotes

echo ">> Seeding fixtures..."
bash "$(dirname "$0")/seed.sh"

echo
echo "Done."
echo "  Site:  $SITE_URL"
echo "  Admin: $SITE_URL/wp-admin/  (admin / admin)"
