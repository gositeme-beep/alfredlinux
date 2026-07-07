#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
OUT="/home/root/law/alfredlinux-com-source-live/night-shift-logs/ops-events.jsonl"
SUMMARY_OUT="/home/root/law/alfred-build-control-plane/last-ops-event.json"
SOURCE=""
EVENT=""
REASON=""
ATTEMPT=""
RC=""
CONTAINER=""
PHASE=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --out) OUT="$2"; shift 2 ;;
    --summary-out) SUMMARY_OUT="$2"; shift 2 ;;
    --source) SOURCE="$2"; shift 2 ;;
    --event) EVENT="$2"; shift 2 ;;
    --reason) REASON="$2"; shift 2 ;;
    --attempt) ATTEMPT="$2"; shift 2 ;;
    --rc) RC="$2"; shift 2 ;;
    --container) CONTAINER="$2"; shift 2 ;;
    --phase) PHASE="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done
[[ -n "$SOURCE" && -n "$EVENT" ]] || { echo "missing --source/--event" >&2; exit 2; }
mkdir -p "$(dirname "$OUT")"
python3 - <<'PY' "$OUT" "$SUMMARY_OUT" "$SOURCE" "$EVENT" "$REASON" "$ATTEMPT" "$RC" "$CONTAINER" "$PHASE"
import json, os, sys, time
out, summary_out, source, event, reason, attempt, rc, container, phase = sys.argv[1:10]
obj = {
  "ts": time.time(),
  "iso_ts": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
  "source": source,
  "event": event,
  "reason": reason,
  "attempt": attempt,
  "rc": rc,
  "container": container,
  "phase": phase,
  "host": os.uname().nodename,
}
with open(out, "a", encoding="utf-8") as f:
  f.write(json.dumps(obj, separators=(",", ":")) + "\n")
if summary_out:
  with open(summary_out, "w", encoding="utf-8") as f:
    json.dump(obj, f, indent=2)
PY
