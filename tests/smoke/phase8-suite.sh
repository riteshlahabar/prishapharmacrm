#!/usr/bin/env bash
# Phase 8 smoke: the hardening gate. Runs everything that must be green before
# a commit — style, the feature suite, the demo seeders and the docs rules.
#
#   bash tests/smoke/phase8-suite.sh
#
# Needs no running server and no MySQL: the suite uses the sqlite :memory:
# connection from phpunit.xml, and the seeder check builds a throwaway file
# database. Safe to run anywhere, including CI.
#
# The phase6/phase7 scripts are the other half of the picture: they drive a
# live server over HTTP. This one proves the code itself.
set -uo pipefail
AP="${APP_PATH:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
cd "$AP" || exit 1
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
PASS=0; FAIL=0

step() {
  local name=$1; shift
  if "$@" >"$TMP/out" 2>&1; then
    PASS=$((PASS+1)); printf '  ok   %s\n' "$name"
  else
    FAIL=$((FAIL+1)); printf '  FAIL %s\n' "$name"
    sed 's/^/         /' "$TMP/out" | tail -25
  fi
}

echo "=== 1. style (Pint) ==="
step "pint --test, no violations" vendor/bin/pint --test

echo "=== 2. feature + unit suite ==="
# Runs tests/Feature and tests/Unit per phpunit.xml. Covers every module's
# API and panel surface, the envelope and the 403-vs-404 rules.
step "php artisan test, all green" php artisan test

echo "=== 3. seeders build a usable database ==="
DB="$TMP/smoke.sqlite"; : >"$DB"
export DB_CONNECTION=sqlite DB_DATABASE="$DB"
step "migrations run on a fresh database" php artisan migrate --force
step "core seeders (permissions, roles, admin)" php artisan db:seed --force
step "demo seeder builds a demo world" php artisan db:seed --class=Database\\Seeders\\DemoSeeder --force
# A second run must recognise its own rows rather than doubling them.
step "demo seeder is safe to re-run" php artisan db:seed --class=Database\\Seeders\\DemoSeeder --force
step "rollback leaves no table behind" php artisan migrate:rollback --force
unset DB_CONNECTION DB_DATABASE

echo "=== 4. docs rules ==="
step "docs/check-docs.sh (sizes, 100-line CLAUDE.md)" bash docs/check-docs.sh

echo
echo "================  pass: $PASS   fail: $FAIL  ================"
[ "$FAIL" -eq 0 ]
