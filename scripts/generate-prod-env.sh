#!/usr/bin/env bash
# Generate .env.production from your local .env, pre-filling everything
# that's safe to copy and clearly marking what you MUST change for production.
#
# Run locally:  bash scripts/generate-prod-env.sh
# Output:       .env.production (gitignored, scp this up to the server)

set -euo pipefail

SRC=".env"
DEST=".env.production"

if [ ! -f "$SRC" ]; then
  echo "Source .env not found at $SRC. Run this from the repo root."
  exit 1
fi

if [ -f "$DEST" ]; then
  echo "$DEST already exists. Remove it or rename it first."
  exit 1
fi

# Helper to swap a key=value pair (only first match).
set_kv() {
  local key="$1"
  local val="$2"
  if grep -qE "^${key}=" "$DEST"; then
    sed -i "s|^${key}=.*|${key}=${val}|" "$DEST"
  else
    echo "${key}=${val}" >> "$DEST"
  fi
}

cp "$SRC" "$DEST"

# Production overrides — values that must change from local
set_kv "APP_ENV" "production"
set_kv "APP_DEBUG" "false"
set_kv "APP_URL" "https://vrooem.com"
set_kv "ASSET_URL" "https://vrooem.com"
set_kv "SESSION_DOMAIN" "vrooem.com"
set_kv "SANCTUM_STATEFUL_DOMAINS" "vrooem.com,www.vrooem.com"
set_kv "QUEUE_CONNECTION" "database"
set_kv "CACHE_STORE" "file"
set_kv "SESSION_DRIVER" "database"

# DB — placeholder, you'll fill on the server
set_kv "DB_HOST" "127.0.0.1"
set_kv "DB_PORT" "3306"
set_kv "DB_DATABASE" "vrooem"
set_kv "DB_USERNAME" "vrooem"
set_kv "DB_PASSWORD" "CHANGE_ME_ON_SERVER"

# Clear local-dev APP_KEY so artisan generates a fresh one on the server
set_kv "APP_KEY" ""

# Annotate keys that need swapping live ↔ test
cat >> "$DEST" <<'EOF'

# ============================================================================
# REVIEW THESE BEFORE GO-LIVE
# ============================================================================
# - APP_KEY: run `php artisan key:generate` on the server after first deploy
# - DB_PASSWORD: set to the password you created during mysql_secure_installation
# - STRIPE_KEY / STRIPE_SECRET: currently TEST keys (pk_test/sk_test).
#     Swap to pk_live/sk_live when ready for real money.
# - STRIPE_WEBHOOK_SECRET: re-generate after creating the live webhook in
#     Stripe dashboard → Developers → Webhooks → Add endpoint
#     URL: https://vrooem.com/api/stripe/webhook
# - MAILTRAP_API_KEY: upgrade Mailtrap to a paid tier so email isn't rate-limited
# - VITE_ADMIN_EMAIL: make sure a users.email row exists matching this value
# - AWS_*: production S3 bucket for vehicle uploads
# - SENTRY_LARAVEL_DSN: production Sentry project DSN
# - Provider keys (GREENMOTION_*, SURPRICE_*, etc.) only if you enable those
# ============================================================================
EOF

echo ""
echo "Generated $DEST."
echo "Next steps:"
echo "  1. Review $DEST line-by-line and fix anything marked CHANGE_ME"
echo "  2. scp $DEST root@<server-ip>:/var/www/CarRental/.env"
echo "  3. On the server: php artisan key:generate && php artisan config:cache"
echo ""
echo "Reminder: $DEST is gitignored. Never commit it."
