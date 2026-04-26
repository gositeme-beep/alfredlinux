#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# release-integrity.sh — checksum + GPG for Alfred Linux release artifacts
#
# Why: No vendor can be stopped from *creating* a modified .iso on their own
# disks. What protects users is *detection*: published hashes and a detached
# signature with a key only the project controls. Verify before every install.
#
# Usage (from a directory containing your .iso files):
#   ./scripts/release-integrity.sh hash *.iso
#   ./scripts/release-integrity.sh sign
#   ./scripts/release-integrity.sh verify
# From repository root (metadata drift gate):
#   ./scripts/release-integrity.sh check-repo
#
set -euo pipefail

SUM256="SHA256SUMS"
SUM512="SHA512SUMS"

usage() {
    echo "release-integrity.sh — Alfred Linux release checksums + GPG"
    echo ""
    echo "Commands:"
    echo "  hash <files...>   Write ${SUM256} and ${SUM512} for given artifacts."
    echo "  sign              Detached-sign ${SUM256} -> ${SUM256}.asc (needs GPG secret key)."
    echo "  verify            gpg --verify ${SUM256}.asc && sha256sum -c ${SUM256}"
    echo "  check-repo        From repo root: bible_tongues vs 0292 languages.conf (exit 1 on mismatch)."
}

cmd_check_repo() {
    ROOT=$(cd "$(dirname "$0")/.." && pwd)
    cd "$ROOT"
    # shellcheck source=shlib/bible_tongues_counts.sh
    . "$ROOT/scripts/shlib/bible_tongues_counts.sh"
    if [ ! -f api/version.json ] || [ ! -f config/hooks/live/0292-alfred-bible-tongues.hook.chroot ]; then
        echo "error: need api/version.json and 0292 hook at repo root ($ROOT)" >&2
        exit 1
    fi
    want=$(bible_tongues_version_field)
    got=$(bible_tongues_conf_rows)
    if [ -z "$want" ] || [ "$want" = "None" ]; then
        echo "error: api/version.json missing numeric bible_tongues" >&2
        exit 1
    fi
    if [ "$got" != "$want" ]; then
        echo "error: bible_tongues mismatch — version.json=$want languages.conf rows=$got" >&2
        exit 1
    fi
    echo "OK: bible_tongues ($want) matches languages.conf row count."
}

cmd_hash() {
    if [ "$#" -lt 1 ]; then
        echo "error: hash needs at least one file" >&2
        usage >&2
        exit 1
    fi
    for f in "$@"; do
        if [ ! -f "$f" ]; then
            echo "error: not a file: $f" >&2
            exit 1
        fi
    done
    sha256sum "$@" >"$SUM256"
    sha512sum "$@" >"$SUM512"
    echo "Wrote $PWD/$SUM256 and $PWD/$SUM512"
    echo "Next: ./scripts/release-integrity.sh sign"
}

cmd_sign() {
    if [ ! -f "$SUM256" ]; then
        echo "error: $SUM256 not found — run hash first" >&2
        exit 1
    fi
    gpg --armor --detach-sign --output "${SUM256}.asc" "$SUM256"
    echo "Wrote $PWD/${SUM256}.asc"
    echo "Publish: $SUM256 $SUM512 ${SUM256}.asc alongside the .iso files."
}

cmd_verify() {
    if [ ! -f "${SUM256}.asc" ] || [ ! -f "$SUM256" ]; then
        echo "error: need ${SUM256} and ${SUM256}.asc in $PWD" >&2
        exit 1
    fi
    gpg --verify "${SUM256}.asc" "$SUM256"
    sha256sum -c "$SUM256"
    echo "OK: signature and SHA-256 checksums match."
}

case "${1:-}" in
    hash) shift; cmd_hash "$@" ;;
    sign) cmd_sign ;;
    verify) cmd_verify ;;
    check-repo) cmd_check_repo ;;
    -h|--help|help) usage ;;
    *)
        usage >&2
        exit 1
        ;;
esac
