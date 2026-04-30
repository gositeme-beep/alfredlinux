#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Alfred Linux — static security sweep for hooks, repo scripts, and build-assets shells.
# Wave 2: hook CI gate; Wave 3: scripts/*.sh + ops + shlib + build-assets (same grep rules).
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

log "=== Alfred security-audit (hooks + scripts + build-assets) ==="

# shellcheck source=shlib/bible_tongues_counts.sh
. "$(dirname "$0")/shlib/bible_tongues_counts.sh"

# --- CRITICAL: api/version.json bible_tongues vs 0292 languages.conf rows ---
if [ -f api/version.json ] && [ -f config/hooks/live/0292-alfred-bible-tongues.hook.chroot ]; then
  want=$(bible_tongues_version_field)
  got=$(bible_tongues_conf_rows)
  if [ -z "$want" ] || [ "$want" = "None" ]; then
    warn "api/version.json missing bible_tongues (expected integer)"
  elif [ "$got" != "$want" ]; then
    crit "bible_tongues mismatch: api/version.json says $want but 0292 languages.conf has $got data rows"
  fi
fi

# --- CRITICAL: api/version.json hooks == Kingdom lineage (42) ---
if [ -f api/version.json ]; then
  hj=$(version_json_hooks_field)
  if [ -z "$hj" ] || [ "$hj" = "None" ]; then
    warn "api/version.json missing hooks (expected $ALFRED_KINGDOM_HOOKS_EXPECTED)"
  elif [ "$hj" != "$ALFRED_KINGDOM_HOOKS_EXPECTED" ]; then
    crit "hooks mismatch: api/version.json says $hj but Kingdom lineage is $ALFRED_KINGDOM_HOOKS_EXPECTED (README / LICENSING.md)"
  fi
fi

# --- WARN: SPDX on Kingdom hooks (LICENSING.md §1; REUSE-friendly) ---
for f in "${HOOK_GLOB[@]}"; do
  [ -f "$f" ] || continue
  if ! head -n 12 "$f" | grep -q '^# SPDX-License-Identifier: AGPL-3.0-or-later'; then
    warn "hook missing SPDX AGPL-3.0-or-later near top: $f"
  fi
done

# --- CRITICAL: known SSH / auth footguns in live hooks ---
while IFS= read -r hit; do
  [ -z "$hit" ] && continue
  crit "StrictHostKeyChecking=no on executable line: $hit"
done < <(non_comment_hits "StrictHostKeyChecking=no")

# Password SSH is a policy choice (e.g. bootstrap before keys); do not CRITICAL
# without maintainer sign-off — WARN so CI can still surface it.
while IFS= read -r hit; do
  [ -z "$hit" ] && continue
  warn "PasswordAuthentication yes on executable line (review policy): $hit"
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

# --- Wave 3: scripts/*.sh + scripts/ops/*.sh (not hooks) — same SSH + supply-chain rules ---
# Skip this script: it contains the grep patterns and example strings on real code lines.
for f in scripts/*.sh scripts/ops/*.sh scripts/shlib/*.sh; do
  [ -f "$f" ] || continue
  [[ "$f" == scripts/security-audit.sh ]] && continue
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    if [[ "$line" == *"StrictHostKeyChecking=no"* ]]; then
      crit "StrictHostKeyChecking=no on executable line: $f: ${line:0:140}"
    fi
  done < "$f"
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    grep -qE 'curl[^|]*\|[[:space:]]*(ba)?sh|wget[^|]*\|[[:space:]]*(ba)?sh' <<<"$line" || continue
    crit "curl|sh or wget|sh in $f — ${line:0:140}"
  done < "$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*eval[[:space:]] ]] || continue
    warn "eval statement in $f: ${line:0:120}"
  done < "$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" != *"http://"* ]] && continue
    [[ "$line" == *"http://127."* || "$line" == *"http://localhost"* || "$line" == *'xmlns="http://'* ]] && continue
    [[ "$line" == *"http://deb.debian.org"* || "$line" == *"http://security.debian.org"* ]] && continue
    warn "http:// in $f: ${line:0:140}"
  done < "$f"
done

# --- Wave 3b: build-assets shell (canonical media helpers; synced into ISO includes) ---
for f in build-assets/*.sh build-assets/wallpapers/scripts/*.sh; do
  [ -f "$f" ] || continue
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    if [[ "$line" == *"StrictHostKeyChecking=no"* ]]; then
      crit "StrictHostKeyChecking=no on executable line: $f: ${line:0:140}"
    fi
  done < "$f"
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    grep -qE 'curl[^|]*\|[[:space:]]*(ba)?sh|wget[^|]*\|[[:space:]]*(ba)?sh' <<<"$line" || continue
    crit "curl|sh or wget|sh in $f — ${line:0:140}"
  done < "$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*eval[[:space:]] ]] || continue
    warn "eval statement in $f: ${line:0:120}"
  done < "$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" != *"http://"* ]] && continue
    [[ "$line" == *"http://127."* || "$line" == *"http://localhost"* || "$line" == *'xmlns="http://'* ]] && continue
    [[ "$line" == *"http://deb.debian.org"* || "$line" == *"http://security.debian.org"* ]] && continue
    warn "http:// in $f: ${line:0:140}"
  done < "$f"
done

# --- WARN: SPDX on build-assets shell helpers ---
for f in build-assets/*.sh build-assets/wallpapers/scripts/*.sh; do
  [ -f "$f" ] || continue
  if ! head -n 12 "$f" | grep -q '^# SPDX-License-Identifier: AGPL-3.0-or-later'; then
    warn "build-assets script missing SPDX AGPL-3.0-or-later near top: $f"
  fi
done

# --- shellcheck: this script always; all scripts/*.sh if ALFRED_SHELLCHECK_ALL=1 ---
if command -v shellcheck >/dev/null 2>&1; then
  log "=== shellcheck (scripts/security-audit.sh) ==="
  shellcheck -P SCRIPTDIR -x scripts/security-audit.sh
  if [ -f scripts/audit-law-wrappers.sh ]; then
    log "=== shellcheck (scripts/audit-law-wrappers.sh) ==="
    shellcheck -P SCRIPTDIR -x scripts/audit-law-wrappers.sh
  fi
  if [ "${ALFRED_SHELLCHECK_ALL:-0}" = 1 ]; then
    log "=== shellcheck (ALFRED_SHELLCHECK_ALL=1 — scripts + ops + shlib + build-assets) ==="
    shopt -s nullglob
    for s in scripts/*.sh scripts/ops/*.sh scripts/shlib/*.sh build-assets/*.sh build-assets/wallpapers/scripts/*.sh; do
      [ -f "$s" ] || continue
      [[ "$s" == scripts/security-audit.sh ]] && continue
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
