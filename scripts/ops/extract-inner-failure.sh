#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
LOG=""
OUT=""
REASON="unknown"
CONTEXT_LINES="220"
while [[ $# -gt 0 ]]; do
  case "$1" in
    --log) LOG="$2"; shift 2 ;;
    --out) OUT="$2"; shift 2 ;;
    --reason) REASON="$2"; shift 2 ;;
    --context-lines) CONTEXT_LINES="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done
[[ -n "$LOG" && -n "$OUT" ]] || { echo "missing --log/--out" >&2; exit 2; }
if [[ ! -f "$LOG" ]]; then
  {
    echo "=== inner failure snapshot ==="
    echo "reason: $REASON"
    echo "log: $LOG"
    echo "status: missing log file"
  } > "$OUT"
  exit 0
fi
mkdir -p "$(dirname "$OUT")"
python3 - <<'PY' "$LOG" "$OUT" "$REASON" "$CONTEXT_LINES"
import re
import sys
from pathlib import Path
log_path, out_path, reason, context_lines = sys.argv[1:5]
context_n = int(context_lines) if context_lines.isdigit() else 220
text = Path(log_path).read_text(encoding='utf-8', errors='replace')
lines = text.splitlines()
start = 0
for i, line in enumerate(lines):
    if '[inner] lb build starting' in line:
        start = i
slice_lines = lines[start:]
patterns = [
    r'E: An unexpected failure occurred',
    r'^E:\s',
    r'\bErr:\d+\b',
    r'\bFAILED\b',
    r'\bTraceback\b',
    r'\bNo space left on device\b',
    r'\bUnable to locate package\b',
]
rx = re.compile('|'.join(patterns), re.IGNORECASE)
hits = []
for idx, line in enumerate(slice_lines, start=start + 1):
    if rx.search(line):
        hits.append((idx, line))
out = []
out.append('=== inner failure snapshot ===')
out.append(f'reason: {reason}')
out.append(f'log: {log_path}')
out.append(f'last_inner_start_line: {start + 1}')
out.append(f'slice_lines: {len(slice_lines)}')
out.append(f'error_hits: {len(hits)}')
out.append('')
out.append('=== matched error lines (first 80) ===')
if hits:
    for ln, txt in hits[:80]:
        out.append(f'{ln}: {txt}')
else:
    out.append('(none)')
out.append('')
out.append(f'=== last {context_n} lines from current inner slice ===')
tail = slice_lines[-context_n:] if context_n > 0 else slice_lines
for line in tail:
    out.append(line)
Path(out_path).write_text('\n'.join(out) + '\n', encoding='utf-8')
PY
