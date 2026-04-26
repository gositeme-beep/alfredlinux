#!/usr/bin/env bash
# bible_tongues_counts.sh — row count helpers (source from repo root).
# Expects cwd = repository root.

bible_tongues_conf_rows() {
  sed -n '/cat > .*languages\.conf/,/^CONF$/p' \
    config/hooks/live/0292-alfred-bible-tongues.hook.chroot \
    | grep '|' | grep -cvE '^[[:space:]]*#'
}

bible_tongues_version_field() {
  python3 -c "import json; print(json.load(open('api/version.json')).get('bible_tongues', ''))" 2>/dev/null || echo ""
}
