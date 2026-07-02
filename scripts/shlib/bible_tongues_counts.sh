#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# bible_tongues_counts.sh — repo metadata drift helpers (source from repo root).
# Expects cwd = repository root.
#
# Kingdom hook lineage (Matthew 1:17): public `hooks` in api/version.json must
# stay 42 — extra .hook.chroot files on disk are merge shards, not lineage.

# Read by release-integrity.sh / security-audit.sh after sourcing this file.
# shellcheck disable=SC2034
ALFRED_KINGDOM_HOOKS_EXPECTED=42

bible_tongues_conf_rows() {
  sed -n '/cat > .*languages\.conf/,/^CONF$/p' \
    config/hooks/live/0094-alfred-bible-tongues.hook.chroot \
    | grep '|' | grep -cvE '^[[:space:]]*#'
}

bible_tongues_version_field() {
  python3 -c "import json; print(json.load(open('api/version.json')).get('bible_tongues', ''))" 2>/dev/null || echo ""
}

version_json_hooks_field() {
  python3 -c "import json; print(json.load(open('api/version.json')).get('hooks', ''))" 2>/dev/null || echo ""
}
