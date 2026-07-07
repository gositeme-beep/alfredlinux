#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Emit a MANIFEST-v1-shaped JSON skeleton (unsigned) to stdout or OUT file.
# See docs/MANIFEST-v1.txt. Does not compute CIDs or signatures — CI/publisher fills those.
#
# Usage:
#   bash scripts/emit-manifest-stub.sh [product] [release_id] [outfile]
#   bash scripts/emit-manifest-stub.sh alfred-linux-iso 2026.05.01 /tmp/manifest.json
set -euo pipefail
PRODUCT=${1:-example-bundle}
RELEASE_ID=${2:-$(date -u +%Y%m%d%H%M%S)}
OUT=${3:-}

payload=$(python3 - "$PRODUCT" "$RELEASE_ID" <<'PY'
import json, sys
from datetime import datetime, timezone

product = sys.argv[1]
release_id = sys.argv[2]
manifest = {
    "manifest_version": "1",
    "product": product,
    "release_id": release_id,
    "created_utc": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
    "root_cid": "",
    "files": [],
    "signatures": [],
}
print(json.dumps(manifest, indent=2))
PY
)

if [[ -n "$OUT" ]]; then
  printf '%s\n' "$payload" >"$OUT"
  echo "Wrote $OUT" >&2
else
  printf '%s\n' "$payload"
fi
