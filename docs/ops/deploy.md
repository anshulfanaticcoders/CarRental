# Production Deploy Checklist

## One-time setup (per server)

1. PHP 8.1+, MySQL 8, Redis (optional), Node 20+, Supervisor.
2. Clone repo + `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build`
4. Copy `.env.example` → `.env`, fill every key marked with no default value. See **Required env** below.
5. `php artisan key:generate`
6. `php artisan storage:link`
7. `php artisan migrate --force`
8. Install supervisor config — see `docs/ops/queue-worker.md`
9. Install cron — see `docs/ops/queue-worker.md` (`schedule:run` every minute)
10. Web server: point document root at `public/`. Nginx config in `docs/ops/nginx.conf` (TODO).
11. Stripe dashboard → Webhooks → add endpoint `https://your-domain/api/stripe/webhook`, copy signing secret into `STRIPE_WEBHOOK_SECRET`.

## Required env (must be set, no usable default)

| Key | What |
|-----|------|
| `APP_KEY` | `php artisan key:generate` once |
| `APP_URL` | Full https URL, no trailing slash |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `DB_*` | MySQL credentials |
| `QUEUE_CONNECTION` | `database` (see queue-worker.md) |
| `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Stripe live keys |
| `VITE_ADMIN_EMAIL` | Must match a real `users.email` row, else admin notifications drop silently |
| `MAILTRAP_API_KEY` | Or fall back to SMTP via `MAIL_HOST/PORT/USERNAME/PASSWORD` |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | From-line for all transactional email |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` | If using S3 for uploads |
| `SENTRY_LARAVEL_DSN` | Error tracking |
| `VITE_STADIA_MAPS_API_KEY` | Reverse geocoding in admin maps |
| `EXCHANGERATE_API_KEY` | `RefreshCurrencyRates` command |
| `PROVIDER_MARKUP_PERCENT` | Platform commission, default 0.15 (15%) |
| `VROOEM_GATEWAY_BASE_URL`, `VROOEM_GATEWAY_API_KEY` | Internal FastAPI gateway |
| `PUSHER_*` | Real-time chat broadcasting |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | Contact form CAPTCHA |
| `GOOGLE_CLIENT_*`, `FACEBOOK_CLIENT_*`, `APPLE_CLIENT_ID` | Social auth (if enabled) |

Provider-specific (only required if that provider is enabled):
- `GREENMOTION_USERNAME/PASSWORD`, `USAVE_API_URL/USERNAME`, `SURPRICE_API_KEY/RATE_CODE`
- `FAVRICA_*`, `XDRIVE_*`, `OK_MOBILITY_*`, `RECORDRENTACAR_*`

## Deploy script (run on every push)

```bash
#!/usr/bin/env bash
set -euo pipefail
cd /var/www/CarRental

# Pull
git pull --ff-only origin master

# PHP deps
composer install --no-dev --optimize-autoloader --no-interaction

# JS build (production)
npm ci
npm run build

# DB migrations
php artisan migrate --force

# Caches — config/route/view cache for performance.
# Safe because every hot env() call has been moved into config files
# (see docs/ops/deploy.md "Env audit" + config/admin.php, config/services.php).
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Bust the queue workers so they pick up new code
php artisan queue:restart

# Storage symlink (idempotent)
php artisan storage:link || true

# OPcache reset (if using PHP-FPM)
sudo systemctl reload php8.3-fpm
```

`config:cache` returns `null` for any `env()` called outside of `config/*.php` — the env audit (2026-05-15) verified zero such calls remain in `app/`. New code must read from `config()` only, never `env()` directly outside config files. Add a CI grep guard:

```bash
if grep -rn "env('" app/; then
  echo "ERROR: env() found outside config files — config:cache will break"
  exit 1
fi
```

## Rollback

```bash
git revert --no-edit <commit>
git push origin master   # triggers same deploy script
```

If migration is at fault, also run:

```bash
php artisan migrate:rollback --step=1 --force
```

## Health checks after deploy

1. `curl -fsS https://your-domain/up` — Laravel health endpoint
2. Tail logs: `tail -f storage/logs/laravel.log`
3. Stripe Test webhook: dashboard → Send test event → checkout.session.completed → verify 200 in webhook delivery log
4. Make one real test booking with a live card in incognito; verify success page + DB row + admin email
5. Confirm worker is processing: `php artisan queue:work --once --timeout=10` should pull one job (or sit idle if queue empty)
6. Confirm scheduler runs: `tail storage/logs/laravel.log | grep schedule:run`

## Monitoring

- **Sentry** for PHP errors + JS errors (Vite plugin already wired)
- **Microsoft Clarity** for frontend session replays (`CLARITY_ENABLED=true`)
- **Awin** for affiliate conversion tracking
- **Mailtrap** dashboard for transactional email delivery
- Stripe dashboard → Logs for webhook deliveries
- Supervisor: `sudo supervisorctl status carrental-worker:*`
- MySQL slow query log

## Common gotchas

| Symptom | Likely cause |
|---------|--------------|
| Admin notifications silently missing | `VITE_ADMIN_EMAIL` doesn't match a user row |
| Provider reservation never happens after payment | Queue worker not running, or `QUEUE_CONNECTION=sync` in prod env |
| `Stripe API Error: idempotency key conflict` | Two checkout submissions reusing the same `extras_payload_id` — should not happen with current code; if it does, inspect `stripe_checkout_payloads` for stale rows |
| `Pricing has changed. Please refresh and try again.` | Customer's `search_session_id` cache expired; web app should regenerate it on the search-results page |
| Webhook delivery shows 500 | Real failure inside booking creation. Stripe will retry; check `storage/logs/laravel.log` for the underlying exception |
| Search returns 0 vehicles | Vrooem gateway down. `curl http://gateway-host/health` |
| Cron jobs never run | `* * * * * php artisan schedule:run` cron missing |
