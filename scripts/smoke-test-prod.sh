#!/usr/bin/env bash
# Post-deploy production smoke test. Run from the server's CarRental directory.
# Usage: bash scripts/smoke-test-prod.sh https://vrooem.com

set -u

DOMAIN="${1:-https://vrooem.com}"
PASS=0
FAIL=0
RED=$'\e[31m'
GREEN=$'\e[32m'
YELLOW=$'\e[33m'
RESET=$'\e[0m'

check() {
  local label="$1"
  local cmd="$2"
  printf "  %-48s " "$label"
  if eval "$cmd" >/dev/null 2>&1; then
    echo "${GREEN}PASS${RESET}"
    PASS=$((PASS + 1))
  else
    echo "${RED}FAIL${RESET}"
    FAIL=$((FAIL + 1))
  fi
}

echo ""
echo "Running production smoke tests against $DOMAIN"
echo "================================================"

echo ""
echo "Network / SSL"
check "Domain resolves"                "host vrooem.com"
check "HTTPS reachable"                "curl -fsS -o /dev/null $DOMAIN/up"
check "SSL cert valid"                 "curl -fsS -o /dev/null --ssl-reqd $DOMAIN/up"

echo ""
echo "Laravel app"
check "/up health endpoint returns 200" "curl -fsS -o /dev/null -w '%{http_code}' $DOMAIN/up | grep -q 200"
check "Homepage renders (200)"         "curl -fsS -o /dev/null -w '%{http_code}' $DOMAIN | grep -q 200"
check "Mobile API auth endpoint up"    "curl -fsS -o /dev/null -X POST -H 'Content-Type: application/json' -d '{\"email\":\"none@test.com\"}' $DOMAIN/api/mobile/auth/check-availability"
check "Currencies endpoint returns JSON" "curl -fsS $DOMAIN/api/mobile/currencies | grep -q supported"

echo ""
echo "Config / DB"
if [ -d /var/www/CarRental ]; then
  cd /var/www/CarRental
  check "APP_KEY set"                  "[ -n \"\$(php artisan tinker --execute='echo config(\"app.key\");' 2>/dev/null)\" ]"
  check "APP_ENV is production"        "php artisan tinker --execute='echo config(\"app.env\");' 2>/dev/null | grep -q production"
  check "APP_DEBUG is false"           "php artisan tinker --execute='echo config(\"app.debug\") ? \"true\" : \"false\";' 2>/dev/null | grep -q false"
  check "DB connection works"          "php artisan db:show >/dev/null 2>&1"
  check "Queue driver is database"     "php artisan tinker --execute='echo config(\"queue.default\");' 2>/dev/null | grep -q database"
  check "Stripe secret set"            "php artisan tinker --execute='echo config(\"services.stripe.secret\") ? \"set\" : \"\";' 2>/dev/null | grep -q set"
  check "Stripe webhook secret set"    "php artisan tinker --execute='echo config(\"services.stripe.webhook_secret\") ? \"set\" : \"\";' 2>/dev/null | grep -q set"
  check "VITE_ADMIN_EMAIL set"         "php artisan tinker --execute='echo config(\"admin.email\");' 2>/dev/null | grep -q '@'"
  check "Admin user row exists"        "php artisan tinker --execute='echo App\\Models\\User::where(\"email\", config(\"admin.email\"))->exists() ? \"yes\" : \"no\";' 2>/dev/null | grep -q yes"
  check "Migrations are current"       "php artisan migrate:status 2>/dev/null | grep -v 'Pending'"
fi

echo ""
echo "Queue + scheduler"
check "Supervisor running"             "systemctl is-active --quiet supervisor"
check "vrooem-worker registered"       "supervisorctl status 2>/dev/null | grep -q vrooem-worker"
check "Scheduler cron registered"      "crontab -l -u www-data 2>/dev/null | grep -q schedule:run"

echo ""
echo "================================================"
TOTAL=$((PASS + FAIL))
if [ $FAIL -eq 0 ]; then
  echo "${GREEN}All $TOTAL checks PASSED.${RESET}"
  exit 0
else
  echo "${YELLOW}$PASS/$TOTAL passed, ${RED}$FAIL FAILED${RESET}."
  echo "Fix the failed items above before going live."
  exit 1
fi
