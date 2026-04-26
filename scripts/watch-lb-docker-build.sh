#!/usr/bin/env bash
# Wait for detached `lb-docker-build.sh` container to finish; summarize + optional webhook/status JSON.
#
#   bash scripts/watch-lb-docker-build.sh
#   bash scripts/watch-lb-docker-build.sh --webhook https://example.com/hook
#   bash scripts/watch-lb-docker-build.sh --status-json /path/to/last-lb-docker.json
#
# Reads container name from lb-docker.containername in repo root (written by lb-docker-build.sh detach).
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
NAME_FILE="$REPO/lb-docker.containername"
LOG="$REPO/lb-docker-build.log"
WEBHOOK=""
STATUS_JSON=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --webhook) WEBHOOK="${2:?}"; shift 2 ;;
    --status-json) STATUS_JSON="${2:?}"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

# Same env as supervise-lb-docker-nap.sh so one export works for watch-after-detach.
[[ -z "$WEBHOOK" && -n "${NAP_WEBHOOK:-}" ]] && WEBHOOK="$NAP_WEBHOOK"

[[ -f "$NAME_FILE" ]] || { echo "Missing $NAME_FILE — run lb-docker-build.sh detach first." >&2; exit 1; }
NAME="$(tr -d '\n' <"$NAME_FILE")"
[[ -n "$NAME" ]] || { echo "Empty container name in $NAME_FILE" >&2; exit 1; }

EXIT="unknown"
if docker inspect "$NAME" &>/dev/null; then
  echo "Waiting for Docker container: $NAME (docker wait)…"
  set +e
  EXIT="$(docker wait "$NAME" 2>/dev/null | tr -d '\n')"
  [[ -n "$EXIT" ]] || EXIT="unknown"
  set -e
else
  echo "Container $NAME not found (already exited and removed?). Summarizing from disk…"
fi

ISO_LIST="$(mktemp)"
find "$REPO/build" "$REPO/iso-output" -maxdepth 5 -name '*.iso' -type f 2>/dev/null | head -50 >"$ISO_LIST" || true
ISO_COUNT="$(wc -l <"$ISO_LIST" | tr -d ' ')"
LOG_TAIL="$(tail -60 "$LOG" 2>/dev/null || echo '(no log)')"

{
  echo "=== watch-lb-docker-build $(date -Is) ==="
  echo "container: $NAME"
  echo "docker_exit: $EXIT"
  echo "iso_count: $ISO_COUNT"
  if [[ "$ISO_COUNT" != "0" ]]; then echo "iso_paths:"; cat "$ISO_LIST"; fi
  echo "--- last 60 lines: $LOG ---"
  echo "$LOG_TAIL"
}

if [[ -n "$STATUS_JSON" ]]; then
  python3 - "$STATUS_JSON" "$NAME" "$EXIT" "$ISO_LIST" <<'PY'
import json, sys, time
path, name, exit_s, iso_list = sys.argv[1:5]
with open(iso_list) as f:
    iso_paths = [ln.strip() for ln in f if ln.strip()]
nap_ok = exit_s == "0" and len(iso_paths) > 0
data = {
    "ts": time.time(),
    "container": name,
    "docker_exit": exit_s,
    "iso_paths": iso_paths,
    "iso_count": len(iso_paths),
    "nap_ok": nap_ok,
}
with open(path, "w") as out:
    json.dump(data, out, indent=2)
print("Wrote", path, "nap_ok=", nap_ok)
PY
fi

if [[ -n "$WEBHOOK" ]]; then
  BODY=$(python3 -c "import json,time; print(json.dumps({'ts':time.time(),'container':'${NAME}','docker_exit':'${EXIT}','iso_count':int(${ISO_COUNT}),'nap_ok':('${EXIT}'=='0' and int(${ISO_COUNT})>0)}))")
  curl -sS -X POST -H 'Content-Type: application/json' -d "$BODY" "$WEBHOOK" -o /dev/null -w 'webhook_http:%{http_code}\n' || echo "webhook: curl failed"
fi

rm -f "$ISO_LIST"

# Success: clean container exit, or we found an ISO even if `docker wait` missed (--rm race).
if [[ "$EXIT" == "0" ]] || [[ "$ISO_COUNT" != "0" ]]; then
  exit 0
fi
exit 1
