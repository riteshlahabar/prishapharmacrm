#!/usr/bin/env bash
# Phase 6 smoke: the /api/v1 contract the two Flutter apps build against —
# the envelope, pagination, scoping, 403-vs-404, stale tokens, and the Phase
# 3-5 endpoints still answering. See docs/api/v1-common.md.
#
#   php artisan serve --port=8099 &
#   bash tests/smoke/phase6-api.sh [base-url]
#
# Dev-only: it signs in as the local dev users below and toggles one of them
# inactive and back, so point it at a dev database, never a deployment.
# Override the fixtures with SMOKE_PASS / SMOKE_MR / SMOKE_TC / SMOKE_TC2 /
# SMOKE_MR2 if your seed differs.
set -uo pipefail
AP="${APP_PATH:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
B="${1:-http://127.0.0.1:8099}/api/v1"
PW="${SMOKE_PASS:-Smoke#12345}"
U_MR="${SMOKE_MR:-p3.mr2@x.local}"
U_TC="${SMOKE_TC:-p3.tc@x.local}"
U_TC2="${SMOKE_TC2:-smoke.tc@prishapharma.local}"
U_MR2="${SMOKE_MR2:-region.mr@prishapharma.local}"
PASS=0; FAIL=0

# check <name> <expected> <actual>
ck() {
  if [ "$2" = "$3" ]; then PASS=$((PASS+1)); printf '  ok   %-52s %s\n' "$1" "$3"
  else FAIL=$((FAIL+1)); printf '  FAIL %-52s expected[%s] got[%s]\n' "$1" "$2" "$3"; fi
}
# status <method> <path> <token> [data]
status() { local m=$1 p=$2 t=$3 d=${4:-}
  if [ -n "$d" ]; then curl -s -o /dev/null -w '%{http_code}' -X "$m" -H "Authorization: Bearer $t" -H 'Accept: application/json' -d "$d" "$B$p"
  else curl -s -o /dev/null -w '%{http_code}' -X "$m" -H "Authorization: Bearer $t" -H 'Accept: application/json' "$B$p"; fi; }
# jq-free field read
fld() { php -r '$j=json_decode(stream_get_contents(STDIN),true); $k=explode(".",$argv[1]); $v=$j; foreach($k as $s){ if(!is_array($v)||!array_key_exists($s,$v)){echo "MISSING";exit;} $v=$v[$s]; } echo is_bool($v)?var_export($v,true):(is_array($v)?json_encode($v):(string)($v??"null"));' "$1"; }
body() { local m=$1 p=$2 t=$3 d=${4:-}
  if [ -n "$d" ]; then curl -s -X "$m" -H "Authorization: Bearer $t" -H 'Accept: application/json' -d "$d" "$B$p"
  else curl -s -X "$m" -H "Authorization: Bearer $t" -H 'Accept: application/json' "$B$p"; fi; }
login() { curl -s -X POST "$B/auth/login" -H 'Accept: application/json' \
  -d "email=$1&password=$2&role=$3&device_name=smoke" | fld data.token; }

echo "=== 1. envelope on every failure path ==="
r=$(body GET /visits "")
ck "401 unauthenticated: success"   "false"           "$(echo "$r" | fld success)"
ck "401 unauthenticated: reason"    "unauthenticated" "$(echo "$r" | fld errors.reason)"
ck "401 unauthenticated: status"    "401"             "$(status GET /visits '')"
r=$(curl -s -X POST "$B/auth/login" -H 'Accept: application/json' -d '')
ck "422 validation: success"        "false"      "$(echo "$r" | fld success)"
ck "422 validation: reason"         "validation" "$(echo "$r" | fld errors.reason)"
ck "422 validation: field errors"   '["The email field is required."]' "$(echo "$r" | fld errors.email)"
r=$(body GET /nope-not-a-route "")
ck "404 unknown route: success"     "false"     "$(echo "$r" | fld success)"
ck "404 unknown route: reason"      "not_found" "$(echo "$r" | fld errors.reason)"
ck "404 unknown route: no trace"    "MISSING"   "$(echo "$r" | fld trace)"
ck "404 unknown route: no path leak" "Not found." "$(echo "$r" | fld message)"
r=$(body POST /auth/me "")
ck "405 wrong method: reason"       "method_not_allowed" "$(echo "$r" | fld errors.reason)"
ck "405 wrong method: status"       "405"                "$(status POST /auth/me '')"

echo "=== 2. login + the regions field (empty from Phase 2 until now) ==="
MR=$(login "$U_MR" "$PW" "mr")
TC=$(login "$U_TC" "$PW" "telecaller")
MR_OTHER=$(login "$U_MR2" "$PW" "mr")
ck "mr login issues a token"        "yes" "$([ ${#MR} -gt 10 ] && echo yes || echo no)"
ck "tc login issues a token"        "yes" "$([ ${#TC} -gt 10 ] && echo yes || echo no)"
r=$(body GET /auth/me "$MR")
ck "me: role"                       "mr"   "$(echo "$r" | fld data.role)"
ck "me: region_id kept"             "1"    "$(echo "$r" | fld data.region_id)"
ck "me: regions populated"          "1"    "$(echo "$r" | fld data.regions.0.id)"
ck "me: regions carry code"         "P3R"  "$(echo "$r" | fld data.regions.0.code)"
r=$(body GET /auth/me "$TC")
ck "me(tc): regions populated"      "1"    "$(echo "$r" | fld data.regions.0.id)"
ck "me(tc): region_id null"         "null" "$(echo "$r" | fld data.region_id)"

echo "=== 3. pagination is the same on every list ==="
for ep in "/visits:$MR" "/attendance/history:$MR" "/messages:$MR" "/persons:$MR" \
          "/call-logs:$TC" "/call-logs/follow-ups:$TC" "/messages/sent:$TC"; do
  p=${ep%:*}; t=${ep##*:}
  r=$(body GET "$p?per_page=99999" "$t")
  ck "clamp to 100  $p"   "100"   "$(echo "$r" | fld meta.per_page)"
  ck "has_more      $p"   "false" "$(echo "$r" | fld meta.has_more)"
  ck "default 20    $p"   "20"    "$(body GET "$p" "$t" | fld meta.per_page)"
  ck "per_page=-5 no 500 $p" "200" "$(status GET "$p?per_page=-5" "$t")"
  ck "per_page=-5 -> 20  $p" "20"  "$(body GET "$p?per_page=-5" "$t" | fld meta.per_page)"
  ck "per_page=abc -> 20 $p" "20"  "$(body GET "$p?per_page=abc" "$t" | fld meta.per_page)"
done

echo "=== 4. 403 = your account/app may not; 404 = not your row ==="
ck "mr -> telecaller endpoint 403"  "403" "$(status GET /call-logs "$MR")"
ck "  reason forbidden"             "forbidden" "$(body GET /call-logs "$MR" | fld errors.reason)"
ck "tc -> mr endpoint 403"          "403" "$(status GET /visits "$TC")"
ck "tc -> attendance 403"           "403" "$(status GET /attendance/today "$TC")"
ck "mr -> send message 403"         "403" "$(status POST /messages "$MR" 'to_user_id=2&subject=x&body=y')"
ck "mr -> recipient picker 403"     "403" "$(status GET /mrs "$MR")"
ck "tc -> mark read 403"            "403" "$(status POST /messages/1/read "$TC")"
# another telecaller's call log reads as missing, not forbidden, so the
# response cannot confirm the row exists. TC2 owns no calls.
TC2=$(login "$U_TC2" "$PW" "telecaller")
ck "own call patch: 200"            "200" "$(status PATCH /call-logs/1 "$TC" 'notes=own')"
ck "other telecaller patch: 404"    "404" "$(status PATCH /call-logs/1 "$TC2" 'notes=x')"
ck "  reason not_found (not 403)"   "not_found" "$(body PATCH /call-logs/1 "$TC2" 'notes=x' | fld errors.reason)"
ck "other telecaller follow-up:404" "404" "$(status POST /call-logs/1/follow-up-done "$TC2")"
ck "other MR's visit not listed"    "0"   "$(body GET /visits "$MR_OTHER" | fld meta.total)"

echo "=== 5. Phase 3-5 endpoints still answer ==="
ck "attendance today  (ph3)"        "200" "$(status GET /attendance/today "$MR")"
ck "attendance history(ph3)"        "200" "$(status GET /attendance/history "$MR")"
ck "visits index      (ph3)"        "200" "$(status GET /visits "$MR")"
ck "persons (mr)      (ph4)"        "200" "$(status GET /persons "$MR")"
ck "persons (tc)      (ph4)"        "200" "$(status GET /persons "$TC")"
ck "call-logs index   (ph4)"        "200" "$(status GET /call-logs "$TC")"
ck "follow-ups        (ph4)"        "200" "$(status GET /call-logs/follow-ups "$TC")"
ck "messages inbox    (ph5)"        "200" "$(status GET /messages "$MR")"
ck "unread-count      (ph5)"        "200" "$(status GET /messages/unread-count "$MR")"
ck "unread is an int  (ph5)"        "yes" "$(body GET /messages/unread-count "$MR" | fld data.unread | grep -qE '^[0-9]+$' && echo yes || echo no)"
ck "messages sent     (ph5)"        "200" "$(status GET /messages/sent "$TC")"
ck "recipient picker  (ph5)"        "200" "$(status GET /mrs "$TC")"
ck "read-all          (ph5)"        "200" "$(status POST /messages/read-all "$MR")"
ck "legacy /api/auth/me still up"   "200" "$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $MR" -H 'Accept: application/json' "${B%/v1}/auth/me")"

echo "=== 6. token stops when the account does ==="
ck "active token works"             "200" "$(status GET /visits "$MR")"
(cd $AP && U="$U_MR" php artisan tinker --execute='App\Modules\Users\Models\User::where("email",getenv("U")?:"p3.mr2@x.local")->first()->forceFill(["is_active"=>false])->save();' >/dev/null) || { echo "  !! could not toggle $U_MR in $AP"; FAIL=$((FAIL+1)); }
ck "deactivated -> 403"             "403" "$(status GET /visits "$MR")"
ck "  reason account_inactive"      "account_inactive" "$(body GET /visits "$MR" | fld errors.reason)"
ck "deactivated cannot file visit"  "403" "$(status POST /visits "$MR" 'person_id=1&purpose=product_detailing')"
(cd $AP && U="$U_MR" php artisan tinker --execute='App\Modules\Users\Models\User::where("email",getenv("U")?:"p3.mr2@x.local")->first()->forceFill(["is_active"=>true,"role"=>"telecaller"])->save();' >/dev/null) || { echo "  !! could not toggle $U_MR in $AP"; FAIL=$((FAIL+1)); }
ck "role changed -> 403"            "403" "$(status GET /visits "$MR")"
ck "  reason role_changed"          "role_changed" "$(body GET /visits "$MR" | fld errors.reason)"
(cd $AP && U="$U_MR" php artisan tinker --execute='App\Modules\Users\Models\User::where("email",getenv("U")?:"p3.mr2@x.local")->first()->forceFill(["is_active"=>true,"role"=>"mr"])->save();' >/dev/null) || { echo "  !! could not toggle $U_MR in $AP"; FAIL=$((FAIL+1)); }
ck "restored -> 200"                "200" "$(status GET /visits "$MR")"

echo "=== 7. write paths still work end to end ==="
r=$(body POST /visits "$MR" 'person_id=1&purpose=product_detailing&notes=phase6 smoke&latitude=19.1&longitude=72.8')
ck "file a visit: 201"              "201" "$(status POST /visits "$MR" 'person_id=1&purpose=product_detailing&notes=phase6 smoke')"
ck "  visit carries person"         "1"   "$(echo "$r" | fld data.person.id)"
ck "  recorded_at server-set"       "yes" "$([ "$(echo "$r" | fld data.recorded_at)" != "MISSING" ] && echo yes || echo no)"
r=$(body POST /call-logs "$TC" 'person_id=1&status=interested&notes=phase6 smoke')
ck "log a call: 201"                "201" "$(status POST /call-logs "$TC" 'person_id=1&status=interested')"
ck "  called_at server-set"         "yes" "$([ "$(echo "$r" | fld data.called_at)" != "MISSING" ] && echo yes || echo no)"
ck "visit validation 422"           "422" "$(status POST /visits "$MR" 'purpose=product_detailing')"
ck "  reason validation"            "validation" "$(body POST /visits "$MR" 'purpose=product_detailing' | fld errors.reason)"

echo
echo "================  pass: $PASS   fail: $FAIL  ================"
[ "$FAIL" -eq 0 ]
