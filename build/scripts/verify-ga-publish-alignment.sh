#!/usr/bin/env bash
# Level-4 lite: compare on-disk GA artifacts with includes/ga-release-state.php.
# Default: script lives in <site>/build/scripts → site root is ../..
# From a Forge-only clone, set: ALFRED_SITE_ROOT=/path/to/alfredlinux.com/public_html
#
# Stat(1)s files under $ROOT/downloads/ — not HTTP. Live site denies plain GET *.iso
# (downloads/.htaccess); bytes via /download (P2P) or downloads/iso.php?t=… after /covenant.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -n "${ALFRED_SITE_ROOT:-}" ]]; then
  ROOT="$(cd "$ALFRED_SITE_ROOT" && pwd)"
else
  ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
fi
STATE="$ROOT/includes/ga-release-state.php"
SUMS="$ROOT/downloads/SHA256SUMS-7.77.txt"

die() { echo "verify-ga-publish-alignment: $*" >&2; exit 1; }
[[ -f "$STATE" ]] || die "missing $STATE — export ALFRED_SITE_ROOT=/path/to/public_html if this script is not inside the site tree"

ISO_BASE="$(grep '^\$gaIsoBasename' "$STATE" | head -1 | sed "s/.*'\([^']*\)'.*/\1/")"
BTIH_WANT="$(grep '^\$gaTorrentBtihHex' "$STATE" | head -1 | sed "s/.*'\([^']*\)'.*/\1/")"
[[ -n "$ISO_BASE" ]] || die "could not parse \$gaIsoBasename from ga-release-state.php"
[[ -n "$BTIH_WANT" ]] || die "could not parse \$gaTorrentBtihHex from ga-release-state.php"

ISO="$ROOT/downloads/${ISO_BASE}.iso"
TORRENT="$ROOT/downloads/${ISO_BASE}.iso.torrent"

echo "Expected basename: $ISO_BASE"
echo "Expected btih:     $BTIH_WANT"
echo "Site root:        $ROOT"

[[ -f "$ISO" ]] || die "missing ISO (place symlink or file): $ISO"
[[ -f "$TORRENT" ]] || die "missing torrent: $TORRENT"

if [[ -f "$SUMS" ]]; then
  if ! grep -qF "$ISO_BASE" "$SUMS"; then
    die "SHA256SUMS-7.77.txt has no line for $ISO_BASE"
  fi
  # sha256sum lines: "<hash>  <filename>" (GNU uses two spaces before path)
  want_hash="$(awk -v f="$ISO_BASE" 'NF >= 2 && ($NF == f || index($0, f)) { print $1; exit }' "$SUMS")"
  [[ -n "$want_hash" ]] || die "could not read hash from SUMS for $ISO_BASE"
  got_hash="$(sha256sum "$ISO" | awk '{print $1}')"
  if [[ "$want_hash" != "$got_hash" ]]; then
    die "SHA256 mismatch: SUMS has $want_hash, disk has $got_hash"
  fi
  echo "SHA256: OK (matches $SUMS)"
else
  echo "SHA256: SKIP (no $SUMS)"
fi

# Optional: verify .torrent info-hash (transmission-cli from transmission-common)
if command -v transmission-show >/dev/null 2>&1; then
  got_btih="$(transmission-show "$TORRENT" 2>/dev/null | awk -F': ' '/^  Hash:/{print tolower($2); exit}')"
  if [[ -z "$got_btih" ]]; then
    echo "Torrent btih: SKIP (transmission-show produced no Hash line)"
  elif [[ "$got_btih" != "$BTIH_WANT" ]]; then
    die "torrent info-hash $got_btih != ga-release-state \$gaTorrentBtihHex $BTIH_WANT"
  else
    echo "Torrent btih: OK (matches ga-release-state.php)"
  fi
else
  echo "Torrent btih: SKIP (install transmission-cli for transmission-show)"
fi

echo "verify-ga-publish-alignment: all checks passed."
