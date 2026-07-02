#!/bin/bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
# build-manifest.sh — generate BUILD-MANIFEST.json with sha256 of every input.
# Run BEFORE seal-iso.sh. Manifest is what reproducers verify against.
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

SRC="${SRC:-/home/root/law/alfredlinux-com-source-live}"
OUT="${1:-$SRC/BUILD-MANIFEST.json}"
TS=$(date -u +%Y-%m-%dT%H:%M:%SZ)
GIT_HEAD=$(cd "$SRC" && git rev-parse HEAD 2>/dev/null || echo unknown)

hashes_for() {
    local label="$1" pattern="$2"
    local first=1
    find $pattern -type f 2>/dev/null | sort | while read -r f; do
        h=$(sha256sum "$f" | awk '{print $1}')
        sz=$(stat -c%s "$f")
        rel="${f#$SRC/}"
        [[ $first -eq 0 ]] && echo "    ,"
        echo "    {\"path\":\"$rel\",\"size\":$sz,\"sha256\":\"$h\"}"
        first=0
    done
}

cd "$SRC"
{
  echo "{"
  echo "  \"manifest_version\": \"1\","
  echo "  \"sealed_at\": \"$TS\","
  echo "  \"git_head\": \"$GIT_HEAD\","
  echo "  \"declaration\": \"In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.\","
  echo "  \"scripture\": \"Let your communication be, Yea, yea; Nay, nay: for whatsoever is more than these cometh of evil. — Matt 5:37\","
  echo "  \"inputs\": {"

  echo "    \"hooks\": ["
  hashes_for hooks "$SRC/build/config/hooks/*.hook.chroot"
  echo "    ],"

  echo "    \"kernel_packages\": ["
  hashes_for kernel "$SRC/config/packages.chroot/*.deb"
  echo "    ],"

  echo "    \"scripts\": ["
  hashes_for scripts "$SRC/scripts/*.sh"
  echo "    ],"

  echo "    \"readme\": ["
  if [[ -f "$SRC/README.txt" ]]; then
      h=$(sha256sum "$SRC/README.txt" | awk '{print $1}')
      sz=$(stat -c%s "$SRC/README.txt")
      echo "      {\"path\":\"README.txt\",\"size\":$sz,\"sha256\":\"$h\"}"
  fi
  echo "    ],"

  echo "    \"bible\": ["
  hashes_for bible "$SRC/build-assets/bible/*.tsv"
  echo "    ]"

  echo "  }"
  echo "}"
} > "$OUT"

# Validate JSON
if python3 -c "import json; json.load(open('$OUT'))" 2>/dev/null; then
    echo "[BUILD-MANIFEST] ✓ written: $OUT"
    HOOK_COUNT=$(python3 -c "import json; print(len(json.load(open('$OUT'))['inputs']['hooks']))")
    DEB_COUNT=$(python3 -c "import json; print(len(json.load(open('$OUT'))['inputs']['kernel_packages']))")
    BIBLE_COUNT=$(python3 -c "import json; print(len(json.load(open('$OUT'))['inputs']['bible']))")
    echo "[BUILD-MANIFEST]   hooks=$HOOK_COUNT  kernel_debs=$DEB_COUNT  bible=$BIBLE_COUNT"
    echo "[BUILD-MANIFEST]   git_head=$GIT_HEAD"
else
    echo "[BUILD-MANIFEST] ✗ JSON invalid — manifest broken"
    exit 1
fi
