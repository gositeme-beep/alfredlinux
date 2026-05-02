#!/usr/bin/env bash
set -euo pipefail
OUT=""
ISO=""
TORRENT=""
SHA256=""
SHA512=""
BLAKE3=""
BTIH=""
HOOKS_RAN=""
BUILD_LOG=""
CONTAINER_NAME=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    --out) OUT="$2"; shift 2 ;;
    --iso) ISO="$2"; shift 2 ;;
    --torrent) TORRENT="$2"; shift 2 ;;
    --sha256) SHA256="$2"; shift 2 ;;
    --sha512) SHA512="$2"; shift 2 ;;
    --blake3) BLAKE3="$2"; shift 2 ;;
    --btih) BTIH="$2"; shift 2 ;;
    --hooks-ran) HOOKS_RAN="$2"; shift 2 ;;
    --build-log) BUILD_LOG="$2"; shift 2 ;;
    --container-name) CONTAINER_NAME="$2"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done
[[ -n "$OUT" && -n "$ISO" ]] || { echo 'missing --out or --iso' >&2; exit 2; }
ISO_MTIME=$(stat -Lc %Y "$ISO")
ISO_SIZE=$(stat -Lc %s "$ISO")
ISO_NAME=$(basename "$ISO")
GIT_REV=$(git -C "$(dirname "$(dirname "$(dirname "$OUT")")")" rev-parse HEAD 2>/dev/null || true)
FINISH_TS=$(python3 - <<'PY' "$BUILD_LOG"
import re, sys
from pathlib import Path
p = Path(sys.argv[1])
text = p.read_text(encoding='utf-8', errors='replace') if p.exists() else ''
matches = re.findall(r'\[inner\] lb build finished at (\S+) exit=0', text)
print(matches[-1] if matches else '')
PY
)
mkdir -p "$(dirname "$OUT")"
python3 - <<'PY' "$OUT" "$ISO" "$ISO_NAME" "$ISO_MTIME" "$ISO_SIZE" "$TORRENT" "$SHA256" "$SHA512" "$BLAKE3" "$BTIH" "$HOOKS_RAN" "$CONTAINER_NAME" "$FINISH_TS" "$GIT_REV"
import json, sys
out, iso, iso_name, iso_mtime, iso_size, torrent, sha256, sha512, blake3, btih, hooks_ran, container_name, finish_ts, git_rev = sys.argv[1:]
data = {
  'iso_path': iso,
  'iso_name': iso_name,
  'iso_mtime_epoch': int(iso_mtime),
  'iso_size': int(iso_size),
  'torrent_path': torrent,
  'sha256': sha256,
  'sha512': sha512,
  'blake3': blake3,
  'btih': btih,
  'hooks_ran': int(hooks_ran or 0),
  'container_name': container_name,
  'build_finished_at': finish_ts,
  'git_revision': git_rev,
}
with open(out, 'w', encoding='utf-8') as f:
    json.dump(data, f, indent=2)
print(f'Wrote {out}')
PY
