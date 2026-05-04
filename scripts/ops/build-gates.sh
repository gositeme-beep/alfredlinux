#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
MODE="${1:-enforce}"
SL=/home/gositeme/law/alfredlinux-com-source-live
STATUS_JSON=/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json
EVENT_WRITER=$SL/scripts/ops/write-ops-event.sh
ISO=$SL/iso-output/live-image-amd64.hybrid.iso
LOG=$SL/lb-docker-build.log
SUMS=/home/gositeme/domains/alfredlinux.com/public_html/downloads/SHA256SUMS-7.77.txt
MANIFEST=/home/gositeme/domains/alfredlinux.com/public_html/releases/7.77/build-manifest.json
ATTEMPT=$(cat /home/gositeme/law/night-shift-attempt.txt 2>/dev/null || echo unknown)
PHASE=$(jq -r '.phase // "unknown"' "$STATUS_JSON" 2>/dev/null || echo unknown)
CNAME=$(cat $SL/lb-docker.containername 2>/dev/null || echo unknown)
reasons=""

[[ -f "$ISO" ]] || reasons="$reasons,iso_missing"
[[ -f "$SUMS" ]] || reasons="$reasons,sha256sums_missing"
[[ -f "$MANIFEST" ]] || reasons="$reasons,manifest_missing"

if [[ -f "$ISO" ]]; then
  size=$(stat -Lc %s "$ISO" 2>/dev/null || echo 0)
  (( size >= 1073741824 )) || reasons="$reasons,iso_too_small"
fi

sline=$(grep -n '\[inner\] lb build starting' "$LOG" 2>/dev/null | tail -n1 | cut -d: -f1 || true)
fline=$(grep -n '\[inner\] lb build finished .* exit=0' "$LOG" 2>/dev/null | tail -n1 | cut -d: -f1 || true)
if [[ -z "$sline" || -z "$fline" ]]; then
  reasons="$reasons,inner_markers_missing"
elif (( fline < sline )); then
  reasons="$reasons,inner_finish_missing_after_start"
fi
if [[ -n "$sline" ]] && tail -n +"$sline" "$LOG" 2>/dev/null | grep -q 'E: An unexpected failure occurred'; then
  reasons="$reasons,fatal_after_start"
fi

smoke=$(ls -1t /home/gositeme/law/night-shift-logs/smoke-a*.log 2>/dev/null | head -n1 || true)
if [[ -z "$smoke" ]]; then
  reasons="$reasons,smoke_log_missing"
elif ! grep -q 'PASS: ISO contains /etc/alfred' "$smoke" 2>/dev/null; then
  reasons="$reasons,smoke_not_green"
fi

reasons=${reasons#,}
if [[ -z "$reasons" ]]; then
  "$EVENT_WRITER" --source build-gates --event gate_passed --reason "attempt=$ATTEMPT mode=$MODE" --attempt "$ATTEMPT" --container "$CNAME" --phase "$PHASE" || true
  echo '{"passed":true,"reasons":[]}'
  exit 0
fi

"$EVENT_WRITER" --source build-gates --event gate_failed --reason "attempt=$ATTEMPT mode=$MODE reasons=$reasons" --attempt "$ATTEMPT" --container "$CNAME" --phase "$PHASE" || true
echo "{\"passed\":false,\"reasons\":[\"${reasons//,/\",\"}\"]}"
[[ "$MODE" == "report-only" ]] && exit 0
exit 1
