#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Force-write last-lb-docker.json to phase=done when inner lb already succeeded but
# watch-lb-docker-build.sh never finished (Docker wedged, hung docker wait, etc.).
#
# Safe only when:
#   • lb-docker-build.log (tail window) shows [inner] lb build finished … exit=0 after the last
#     [inner] lb build starting in that window (same rule as Dell Watch / night-shift).
#   • At least one *.iso exists under build/ or iso-output/.
#   • The lb-docker container is NOT still running (docker inspect), unless
#     ALFRED_FINALIZE_NAP_IGNORE_RUNNING=1 (dangerous if a new build just started).
#
# Usage:
#   bash scripts/ops/alfred-finalize-nap-json.sh
#   STATUS_JSON=/path/to/last-lb-docker.json bash scripts/ops/alfred-finalize-nap-json.sh
#
# Typical recovery (build host, as root or gositeme + docker):
#   sudo systemctl restart docker
#   sudo -u gositeme bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/alfred-finalize-nap-json.sh
#   sudo systemctl restart alfred-night-shift
set -euo pipefail

REPO="$(cd "$(dirname "$0")/../.." && pwd)"
# shellcheck disable=SC1091
source "$REPO/scripts/lb-nap-helpers.sh"

LOG="$REPO/lb-docker-build.log"
NAME_FILE="$REPO/lb-docker.containername"
STATUS_JSON="${STATUS_JSON:-/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json}"

[[ -f "$NAME_FILE" ]] || { echo "Missing $NAME_FILE" >&2; exit 1; }
NAME="$(tr -d '\n\r' <"$NAME_FILE")"
[[ -n "$NAME" ]] || { echo "Empty container name in $NAME_FILE" >&2; exit 1; }
[[ -f "$LOG" ]] || { echo "Missing $LOG" >&2; exit 1; }

if [[ -f "$STATUS_JSON" ]]; then
  if python3 -c "import json,sys;d=json.load(open(sys.argv[1]));sys.exit(0 if d.get('phase')=='done' else 1)" "$STATUS_JSON" 2>/dev/null; then
    echo "last-lb-docker.json already has phase=done — nothing to do."
    exit 0
  fi
fi

LOG_PATH="$LOG" python3 <<'PY' || { echo "Refusing: inner lb success not confirmed in recent log tail (see stderr above)." >&2; exit 2; }
import os, sys
path = os.environ["LOG_PATH"]
with open(path, "r", encoding="utf-8", errors="replace") as f:
    lines = f.readlines()
window = 12000
tail = lines[-window:] if len(lines) > window else lines
last_start = last_fin = -1
for i, L in enumerate(tail):
    if "[inner] lb build starting" in L:
        last_start = i
    if "[inner] lb build finished" in L and "exit=0" in L:
        last_fin = i
if last_fin < 0:
    print("no [inner] lb build finished … exit=0 in last", len(tail), "lines", file=sys.stderr)
    sys.exit(2)
if last_start > last_fin:
    print("last [inner] lb build starting is after last success line — new inner run may be in progress", file=sys.stderr)
    sys.exit(2)
sys.exit(0)
PY

if alfred_docker_inspect_ok "$NAME"; then
  if [[ "${ALFRED_FINALIZE_NAP_IGNORE_RUNNING:-}" == "1" ]]; then
    echo "WARN: container $NAME still exists in Docker but ALFRED_FINALIZE_NAP_IGNORE_RUNNING=1 — continuing" >&2
  else
    echo "Refusing: container $NAME still exists in Docker (build not finished or new run). Stop it or set ALFRED_FINALIZE_NAP_IGNORE_RUNNING=1 if you are sure." >&2
    exit 3
  fi
fi

ISO_LIST="$(mktemp)"
cleanup() { rm -f "$ISO_LIST"; }
trap cleanup EXIT
find "$REPO/build" "$REPO/iso-output" -maxdepth 5 -name '*.iso' -type f 2>/dev/null | head -50 >"$ISO_LIST" || true
ISO_COUNT="$(wc -l <"$ISO_LIST" | tr -d ' ')"
if [[ "$ISO_COUNT" == "0" ]]; then
  echo "Refusing: no .iso under $REPO/build or $REPO/iso-output — not claiming nap_ok." >&2
  exit 4
fi

nap_ok_num=1
EXIT="unknown"

python3 - "$STATUS_JSON" "$NAME" "$EXIT" "$ISO_LIST" "$nap_ok_num" <<'PY'
import json, sys, time
path, name, exit_s, iso_list, nap_s = sys.argv[1:6]
with open(iso_list) as f:
    iso_paths = [ln.strip() for ln in f if ln.strip()]
nap_ok = nap_s == "1"
data = {
    "phase": "done",
    "ts": time.time(),
    "container": name,
    "docker_exit": exit_s,
    "iso_paths": iso_paths,
    "iso_count": len(iso_paths),
    "nap_ok": nap_ok,
    "note": "Written by alfred-finalize-nap-json.sh (watch did not finalize JSON).",
}
with open(path, "w") as out:
    json.dump(data, out, indent=2)
print("Wrote", path, "nap_ok=", nap_ok, "iso_count=", len(iso_paths))
PY

echo "OK: $STATUS_JSON finalized — restart alfred-night-shift: sudo systemctl restart alfred-night-shift"
