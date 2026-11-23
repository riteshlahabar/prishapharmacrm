#!/usr/bin/env bash
# Phase 7 smoke: the panel dashboard — what each role sees, how it is scoped,
# and that the rest of the panel still answers. See docs/modules/dashboard.md.
#
#   php artisan serve --port=8099 &
#   bash tests/smoke/phase7-dashboard.sh [base-url]
#
# Dev-only: it signs in as the local dev users below, so point it at a dev
# database. Override with SMOKE_PASS / SMOKE_ADMIN / SMOKE_TC / SMOKE_TC_FAR /
# SMOKE_MR if your seed differs.
set -uo pipefail
B="${1:-http://127.0.0.1:8099}"
PW="${SMOKE_PASS:-Smoke#12345}"
U_ADMIN="${SMOKE_ADMIN:-admin@gmail.com}"
U_TC="${SMOKE_TC:-p3.tc@x.local}"
U_FAR="${SMOKE_TC_FAR:-region.tc@prishapharma.local}"
U_MR="${SMOKE_MR:-smoke.mr@prishapharma.local}"
AP="${APP_PATH:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
PASS=0; FAIL=0

# Panel login is throttle:6,1 and this script signs in four times. Clear the
# limiter first, or a run straight after another one fails on 429s that say
# nothing about the dashboard.
reset_throttle() { (cd "$AP" && php artisan cache:clear >/dev/null 2>&1) || true; }
reset_throttle

ck() {
  if [ "$2" = "$3" ]; then PASS=$((PASS+1)); printf '  ok   %-50s %s\n' "$1" "$3"
  else FAIL=$((FAIL+1)); printf '  FAIL %-50s expected[%s] got[%s]\n' "$1" "$2" "$3"; fi
}
# login <jar> <email> -> prints the POST status. Retries once past the
# throttle, so a rate limit never reads as a dashboard failure.
login() {
  local out
  out=$(_login "$@")

  if [ "$out" = "429" ]; then
    reset_throttle
    out=$(_login "$@")
  fi

  echo "$out"
}
_login() {
  rm -f "$1"
  local t
  t=$(curl -s -c "$1" "$B/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$1" -c "$1" -o /dev/null -w '%{http_code}' -X POST "$B/login" \
    -d "_token=$t&email=$2&password=$PW"
}
code()  { curl -s -b "$1" -o /dev/null -w '%{http_code}' "$B$2"; }
page()  { curl -s -b "$1" "$B$2"; }
tiles() { page "$1" "$2" | grep -c 'f-light f-12 d-block">'; }
charts(){ page "$1" "$2" | grep -o 'id="chart-' | wc -l | tr -d ' '; }

echo "=== 1. the dashboard moved into the module ==="
ck "route 'dashboard' -> module controller" "1" \
   "$(php artisan route:list --name=dashboard 2>/dev/null | grep -c 'Modules.Dashboard')"
ck "no placeholder controller left"         "0" \
   "$([ -f app/Http/Controllers/DashboardController.php ] && echo 1 || echo 0)"
ck "no legacy dashboard view left"          "0" \
   "$([ -d resources/views/dashboard ] && echo 1 || echo 0)"

echo "=== 2. admin sees the whole board ==="
ck "admin signs in"         "302" "$(login /tmp/p7a.jar "$U_ADMIN")"
ck "dashboard renders"      "200" "$(code /tmp/p7a.jar /)"
ck "six tiles"              "6"   "$(tiles /tmp/p7a.jar /)"
ck "four charts"            "4"   "$(charts /tmp/p7a.jar /)"
for want in chart-activity chart-calls-status chart-attendance chart-visits-mr; do
  ck "  $want present" "1" "$(page /tmp/p7a.jar / | grep -c "id=\"$want\"")"
done
ck "apexcharts loaded once" "1" "$(page /tmp/p7a.jar / | grep -c 'apex-chart.js')"
ck "cuba demo data NOT loaded" "0" "$(page /tmp/p7a.jar / | grep -c 'chart-custom.js')"

echo "=== 3. telecaller sees only their own cards ==="
ck "telecaller signs in"    "302" "$(login /tmp/p7t.jar "$U_TC")"
ck "dashboard renders"      "200" "$(code /tmp/p7t.jar /)"
ck "four tiles"             "4"   "$(tiles /tmp/p7t.jar /)"
ck "two charts"             "2"   "$(charts /tmp/p7t.jar /)"
ck "no attendance card"     "0"   "$(page /tmp/p7t.jar / | grep -c 'id="chart-attendance"')"
ck "no visits-by-rep card"  "0"   "$(page /tmp/p7t.jar / | grep -c 'id="chart-visits-mr"')"
ck "names their region"     "1"   "$(page /tmp/p7t.jar / | grep -c 'Phase3 Region')"

echo "=== 4. scope: a telecaller elsewhere sees none of it ==="
ck "far telecaller signs in" "302" "$(login /tmp/p7f.jar "$U_FAR")"
ck "dashboard renders"       "200" "$(code /tmp/p7f.jar /)"
ck "empty state, not axes"   "1"   "$(page /tmp/p7f.jar / | grep -c 'Nothing to chart yet')"
ck "no charts drawn"         "0"   "$(charts /tmp/p7f.jar /)"
ck "does not leak the other region" "0" "$(page /tmp/p7f.jar / | grep -c 'Phase3 Region')"

echo "=== 5. period selector, and junk in ?days ==="
ck "default is 30"      "1" "$(page /tmp/p7a.jar / | grep -c 'last 30 days')"
ck "?days=7 honoured"   "1" "$(page /tmp/p7a.jar '/?days=7' | grep -c 'last 7 days')"
ck "?days=90 honoured"  "1" "$(page /tmp/p7a.jar '/?days=90' | grep -c 'last 90 days')"
for bad in 1 9999 abc -5 'days[]=7'; do
  q=$([ "$bad" = 'days[]=7' ] && echo "/?days[]=7" || echo "/?days=$bad")
  ck "  junk '$bad' falls back to 30" "1" "$(page /tmp/p7a.jar "$q" | grep -c 'last 30 days')"
  ck "  junk '$bad' still 200"        "200" "$(code /tmp/p7a.jar "$q")"
done

echo "=== 6. an MR still cannot reach the panel at all ==="
ck "mr login refused"        "302" "$(login /tmp/p7m.jar "$U_MR")"
ck "mr has no session"       "302" "$(code /tmp/p7m.jar /)"
ck "guest bounced from /"    "302" "$(curl -s -o /dev/null -w '%{http_code}' "$B/")"

echo "=== 7. the rest of the panel is untouched ==="
for p in /clients /client-types /regions /visits /calls /messages /attendance /mrs /telecallers /access/permissions; do
  ck "admin  $p" "200" "$(code /tmp/p7a.jar $p)"
done
for p in /clients /calls /messages; do
  ck "tc     $p" "200" "$(code /tmp/p7t.jar $p)"
done
for p in /visits /attendance /mrs; do
  ck "tc     $p refused" "403" "$(code /tmp/p7t.jar $p)"
done

echo
echo "================  pass: $PASS   fail: $FAIL  ================"
[ "$FAIL" -eq 0 ]
