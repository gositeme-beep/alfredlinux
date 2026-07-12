#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
MODE="${1:-enforce}"
SL=/home/gositeme/law/alfredlinux-com-source-live
STATUS_JSON=/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json
EVENT_WRITER=$SL/scripts/ops/write-ops-event.sh
ISO=$SL/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
LOG=$SL/lb-docker-build.log
SUMS=/home/gositeme/domains/alfredlinux.com/public_html/downloads/SHA256SUMS-7.77.txt
MANIFEST=/home/gositeme/domains/alfredlinux.com/public_html/releases/7.77/build-manifest.json
ATTEMPT=$(cat /home/gositeme/law/night-shift-attempt.txt 2>/dev/null || echo unknown)
PHASE=$(jq -r '.phase // "unknown"' "$STATUS_JSON" 2>/dev/null || echo unknown)
CNAME=$(cat "$SL/lb-docker.containername" 2>/dev/null || echo unknown)
reasons=""

append_reason() {
  local reason="${1:-}"
  [[ -z "$reason" ]] && return 0
  if [[ -z "$reasons" ]]; then
    reasons="$reason"
  else
    reasons="$reasons,$reason"
  fi
}

[[ -f "$ISO" ]] || append_reason "iso_missing"
[[ -f "$SUMS" ]] || append_reason "sha256sums_missing"
[[ -f "$MANIFEST" ]] || append_reason "manifest_missing"

if [[ -f "$ISO" ]]; then
  size=$(stat -Lc %s "$ISO" 2>/dev/null || echo 0)
  (( size >= 1073741824 )) || append_reason "iso_too_small"
fi

sline=$(grep -n '\[inner\] lb build starting' "$LOG" 2>/dev/null | tail -n1 | cut -d: -f1 || true)
fline=$(grep -n '\[inner\] lb build finished .* exit=0' "$LOG" 2>/dev/null | tail -n1 | cut -d: -f1 || true)
if [[ -z "$sline" || -z "$fline" ]]; then
  append_reason "inner_markers_missing"
elif (( fline < sline )); then
  append_reason "inner_finish_missing_after_start"
fi
if [[ -n "$sline" ]] && tail -n +"$sline" "$LOG" 2>/dev/null | grep -q 'E: An unexpected failure occurred'; then
  append_reason "fatal_after_start"
fi

if [[ -n "$sline" ]] && [[ -f "$LOG" ]]; then
  APT_FAIL_RE='E: Unable to locate package|E: Package .* has no installation candidate|E: Unable to correct problems|The following packages have unmet dependencies|dpkg: error|E: Sub-process /usr/bin/dpkg returned an error code'
  OPTIONAL_APT_RE="${ALFRED_OPTIONAL_APT_FAILURE_REGEX:-\\b(ollama|code-server|k9s|helm|kubectl|rocm|meilisearch|syncthing|open-quantum-safe|liboqs|oqs-provider)\\b}"
  apt_hits=$(tail -n +"$sline" "$LOG" 2>/dev/null | grep -En "$APT_FAIL_RE" || true)
  if [[ -n "$apt_hits" ]]; then
    req_fail=0
    opt_fail=0
    while IFS= read -r line; do
      [[ -z "$line" ]] && continue
      if echo "$line" | grep -Eiq "$OPTIONAL_APT_RE"; then
        ((opt_fail+=1))
      else
        ((req_fail+=1))
      fi
    done <<< "$apt_hits"
    if (( opt_fail > 0 )); then
      echo "build-gates: optional package failure lines=$opt_fail (allowed by regex)."
    fi
    if (( req_fail > 0 )); then
      append_reason "required_pkg_failures"
    fi
  fi
fi

smoke=$(ls -1t /home/gositeme/law/night-shift-logs/smoke-a*.log 2>/dev/null | head -n1 || true)
if [[ -z "$smoke" ]]; then
  append_reason "smoke_log_missing"
elif ! grep -q 'PASS: ISO contains /etc/alfred' "$smoke" 2>/dev/null; then
  append_reason "smoke_not_green"
fi

if [[ -f "$ISO" ]]; then
  MNT=$(mktemp -d)
  SQLIST=$(mktemp)
  mounted=0
  if sudo mount -o loop,ro "$ISO" "$MNT" 2>/dev/null; then
    mounted=1
    for rel in \
      "boot/grub/grub.cfg" \
      "boot/extras/FD13LIVE.iso" \
      "boot/extras/systemrescue.iso" \
      "boot/extras/clonezilla.iso" \
      "boot/extras/plpbt.iso" \
      "boot/extras/ipxe/ipxe.efi" \
      "boot/extras/ipxe/ipxe.lkrn" \
      "boot/extras/ipxe/undionly.kpxe"
    do
      if [[ ! -s "$MNT/$rel" ]]; then
        append_reason "iso_missing_$(echo "$rel" | tr '/.-' '_')"
      fi
    done

    if [[ -s "$MNT/live/filesystem.squashfs" ]]; then
      if sudo unsquashfs -ll "$MNT/live/filesystem.squashfs" >"$SQLIST" 2>/dev/null; then
        grep -Eq '/lib/modules/7\.0\.10(/|$)' "$SQLIST" || append_reason "kernel7010_missing_in_squashfs"
        grep -Eq '/(usr/bin/calamares|usr/share/calamares)(/|$)' "$SQLIST" || append_reason "calamares_missing_in_squashfs"
        grep -Eq '/etc/alfred(/|$)' "$SQLIST" || append_reason "etc_alfred_missing_in_squashfs"
      else
        append_reason "squashfs_list_failed"
      fi
    else
      append_reason "filesystem_squashfs_missing"
    fi
  else
    append_reason "iso_mount_failed"
  fi

  if (( mounted == 1 )); then
    sudo umount "$MNT" 2>/dev/null || true
  fi
# DISABLED-BY-COMMANDER: rm -f "$SQLIST" 2>/dev/null || true
  rmdir "$MNT" 2>/dev/null || true
fi

if [[ -z "$reasons" ]]; then
  "$EVENT_WRITER" --source build-gates --event gate_passed --reason "attempt=$ATTEMPT mode=$MODE" --attempt "$ATTEMPT" --container "$CNAME" --phase "$PHASE" || true
  echo '{"passed":true,"reasons":[]}'
  exit 0
fi

"$EVENT_WRITER" --source build-gates --event gate_failed --reason "attempt=$ATTEMPT mode=$MODE reasons=$reasons" --attempt "$ATTEMPT" --container "$CNAME" --phase "$PHASE" || true
echo "{\"passed\":false,\"reasons\":[\"${reasons//,/\",\"}\"]}"
[[ "$MODE" == "report-only" ]] && exit 0
exit 1