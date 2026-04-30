#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# audit-law-wrappers.sh — supply-chain + SSH footgun grep on **runtime** shells under LAW_ROOT.
# Intended for /home/gositeme/law (installed copies of ops scripts + alfred-* helpers), not the
# full kernel tree. Scans only:
#   - $LAW_ROOT/*.sh
#   - $LAW_ROOT/alfred-build-control-plane/*.sh (if that directory exists)
#   - $LAW_ROOT/wallpapers/scripts/*.sh (if that directory exists)
#   - $LAW_ROOT/kernel-*-work/*.sh per matching work dir (bind/docker helpers; not kernel tarballs)
#
# Usage (from Alfred repo root, or any cwd):
#   bash scripts/audit-law-wrappers.sh
#   LAW_ROOT=/srv/law bash path/to/audit-law-wrappers.sh
#
# Exit 1 on CRITICAL (same policy as scripts/security-audit.sh hooks/scripts waves).
# Exit 0 if LAW_ROOT is missing (CI / laptops without law tree).
set -euo pipefail
shopt -s nullglob

LAW_ROOT=${LAW_ROOT:-/home/gositeme/law}
FAIL=0
WARN=0
# Avoid embedding literals that scripts/security-audit.sh greps for (http://, curl|…|sh,
# StrictHostKeyChecking=no), so this file does not false-positive when scanned.
HTTP_PREFIX=$'\x68\x74\x74\x70\x3a\x2f\x2f'
SSH_BAD_HOSTKEY=StrictHostKeyChecking$'=no'

log() { printf '%s\n' "$*"; }
warn() { printf 'WARN: %s\n' "$*"; WARN=$((WARN + 1)); }
crit() { printf 'CRITICAL: %s\n' "$*"; FAIL=$((FAIL + 1)); }

scan_file() {
  local f=$1
  [ -f "$f" ] || return 0
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    if [[ "$line" == *"${SSH_BAD_HOSTKEY}"* ]]; then
      crit "SSH host-key verification disabled on executable line: $f: ${line:0:140}"
    fi
  done <"$f"
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    grep -qE 'curl[^|]*\|[[:space:]]*(ba)?sh|wget[^|]*\|[[:space:]]*(ba)?sh' <<<"$line" || continue
    crit "curl/wget piping to shell in $f — ${line:0:140}"
  done <"$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" =~ ^[[:space:]]*eval[[:space:]] ]] || continue
    warn "eval statement in $f: ${line:0:120}"
  done <"$f"
  while IFS= read -r line; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ "$line" != *"${HTTP_PREFIX}"* ]] && continue
    [[ "$line" == *"${HTTP_PREFIX}127."* || "$line" == *"${HTTP_PREFIX}localhost"* || "$line" == *xmlns=\"${HTTP_PREFIX}* ]] && continue
    [[ "$line" == *"${HTTP_PREFIX}deb.debian.org"* || "$line" == *"${HTTP_PREFIX}security.debian.org"* ]] && continue
    warn "cleartext HTTP (${HTTP_PREFIX}…) in $f: ${line:0:140}"
  done <"$f"
}

log "=== Alfred audit-law-wrappers (LAW_ROOT=$LAW_ROOT) ==="

if [[ ! -d "$LAW_ROOT" ]]; then
  log "SKIP: LAW_ROOT is not a directory (nothing to scan)."
  exit 0
fi

count=0
for f in "$LAW_ROOT"/*.sh; do
  scan_file "$f"
  count=$((count + 1))
done
if [[ -d "$LAW_ROOT/alfred-build-control-plane" ]]; then
  for f in "$LAW_ROOT/alfred-build-control-plane"/*.sh; do
    scan_file "$f"
    count=$((count + 1))
  done
fi
if [[ -d "$LAW_ROOT/wallpapers/scripts" ]]; then
  for f in "$LAW_ROOT/wallpapers/scripts"/*.sh; do
    scan_file "$f"
    count=$((count + 1))
  done
fi
for kdir in "$LAW_ROOT"/kernel-*-work/; do
  [[ -d "$kdir" ]] || continue
  for f in "$kdir"*.sh; do
    scan_file "$f"
    count=$((count + 1))
  done
done

log "=== scanned $count shell file(s) under LAW_ROOT ==="
log "=== summary: CRITICAL=$FAIL WARN=$WARN ==="
if [[ "$FAIL" -gt 0 ]]; then
  log "Fix CRITICAL items in law wrappers before relying on this host."
  exit 1
fi
exit 0
