#!/usr/bin/env bash
# Alfred Linux — static security sweep for hooks + helper scripts.
# Wave 2: grep-based CI gate; extend patterns here each security wave.
# Usage: bash scripts/security-audit.sh   (from repo root; exit 1 on CRITICAL)

set -euo pipefail
shopt -s nullglob
ROOT=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT"

HOOK_GLOB=(config/hooks/live/*.hook.chroot)
FAIL=0
WARN=0

log() { printf '%s\n' "$*"; }
warn() { printf 'WARN: %s\n' "$*"; WARN=$((WARN + 1)); }
crit() { printf 'CRITICAL: %s\n' "$*"; FAIL=$((FAIL + 1)); }

# Lines matching PAT in hook files where the line is not shell-comment-only.
non_comment_hits() {
  local pat=$1 f line nl
  for f in "${HOOK_GLOB[@]}"; do
    [ -f "$f" ] || continue
    nl=0
    while IFS= read -r line || [ -n "$line" ]; do
      nl=$((nl + 1))
      [[ "$line" =~ ^[[:space:]]*# ]] && continue
      [[ "$line" == *"$pat"* ]] && printf '%s:%s:%s\n' "$f" "$nl" "$line"
    done < "$f"
  done
}

log "=== Alfred security-audit (wave 2) ==="

# --- CRITICAL: api/version.json bible_tongues vs 0292 languages.conf rows ---
if [ -f api/version.json ] && [ -f config/hooks/live/0292-alfred-bible-tongues.hook.chroot ]; then
  want=$(python3 -c "import json; print(json.load(open('api/version.json')).get('bible_tongues', ''))" 2>/dev/null || echo "")
  got=$(sed -n '/cat > .*languages\.conf/,/^CONF$/p' config/hooks/live/0292-alfred-bible-tongues.hook.chroot | grep '|' | grep -cvE '^[[:space:]]*#')
  if [ -z "$want" ] || [ "$want" = "None" ]; then
    warn "api/version.json missing bible_tongues (expected integer)"
  elif [ "$got" != "$want" ]; then
    crit "bible_tongues mismatch: api/version.json says $want but 0292 languages.conf has $got data rows"
  fi
fi

# --- CRITICAL: known SSH / auth footguns in live hooks ---
while IFS= read -r hit; do
  [ -z "$hit" ] && continue
  crit "StrictHostKeyChecking=no on executable line: $hit"
done < <(non_comment_hits "StrictHostKeyChecking=no")

while IFS= read -r hit; do
  [ -z "$hit" ] && continue
  crit "PasswordAuthentication yes on executable line: $hit"
done < <(non_comment_hits "PasswordAuthentication yes")

# --- CRITICAL: curl|sh / wget|sh on non-comment lines (supply chain) ---
for f in "${HOOK_GLOB[@]}"; do
  [ -f "$f" ] || continue
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    grep -qE 'curl[^|]*\|[[:space:]]*(ba)?sh|wget[^|]*\|[[:space:]]*(ba)?sh' <<<"$line" || continue
    crit "curl|sh or wget|sh in $f — ${line:0:140}"
  done < "$f"
done

# --- WARN: eval at statement start (exclude 0260 zoxide/starship init) ---
for f in "${HOOK_GLOB[@]}"; do
  [ -f "$f" ] || continue
  [[ "$f" == *0260-alfred-terminal-power* ]] && continue
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*eval[[:space:]] ]] || continue
    warn "eval statement in $f: ${line:0:120}"
  done < "$f"
done

# --- WARN: plain http:// fetch in hooks (excluding obvious local SVG xmlns) ---
for f in "${HOOK_GLOB[@]}"; do
  [ -f "$f" ] || continue
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" != *"http://"* ]] && continue
    [[ "$line" == *"http://127."* || "$line" == *"http://localhost"* || "$line" == *'xmlns="http://'* ]] && continue
    [[ "$line" == *"http://deb.debian.org"* || "$line" == *"http://security.debian.org"* ]] && continue
    warn "http:// in $f: ${line:0:140}"
  done < "$f"
done

# --- shellcheck: this script always; all scripts/*.sh if ALFRED_SHELLCHECK_ALL=1 ---
if command -v shellcheck >/dev/null 2>&1; then
  log "=== shellcheck (scripts/security-audit.sh) ==="
  shellcheck -x scripts/security-audit.sh
  if [ "${ALFRED_SHELLCHECK_ALL:-0}" = 1 ]; then
    log "=== shellcheck (all scripts/*.sh — ALFRED_SHELLCHECK_ALL=1) ==="
    shopt -s nullglob
    for s in scripts/*.sh; do
      [ -f "$s" ] || continue
      shellcheck -x "$s" || WARN=$((WARN + 1))
    done
    shopt -u nullglob
  fi
else
  warn "shellcheck not installed — skipping (apt install shellcheck)"
fi

log "=== summary: CRITICAL=$FAIL WARN=$WARN ==="
if [ "$FAIL" -gt 0 ]; then
  log "Fix CRITICAL items before release."
  exit 1
fi
exit 0
