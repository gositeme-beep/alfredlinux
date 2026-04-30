#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Run inside privileged Debian container; /work = bind-mounted Alfred repo.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
LOG="${LB_DOCKER_LOG:-/work/lb-docker-build.log}"
exec > >(tee -a "$LOG") 2>&1

alfred_lazy_umount_chroot() {
  local _ch=/work/build/chroot
  [[ -d "$_ch" ]] && command -v mountpoint >/dev/null || return 0
  for _m in "$_ch/run/shm" "$_ch/run" "$_ch/dev/pts" "$_ch/dev" "$_ch/proc" "$_ch/sys"; do
    if mountpoint -q "$_m" 2>/dev/null; then
      echo "[inner] EXIT trap: umount -l $_m"
      umount -l "$_m" 2>/dev/null || true
    fi
  done
}
trap 'alfred_lazy_umount_chroot' EXIT

apt-get update -y
apt-get install -y --no-install-recommends \
  live-build debootstrap cdebootstrap ca-certificates cpio wget gnupg \
  rsync xz-utils bzip2 gzip file

# Debian trixie chroot + bookworm live-build: binary_syslinux cp into chroot/root/isolinux can fail on
# dangling symlinks — patch adds GNU cp --remove-destination (see patch-live-build-isolinux-dangling.sh).
bash /work/scripts/patch-live-build-isolinux-dangling.sh
bash /work/scripts/patch-live-build-lazy-umount-chroot-fs.sh

cd /work/build

# Interrupted live-build often leaves proc/sys/dev mounted under chroot. The next run can then hit
# "umount: .../chroot/sys: target is busy" during lb teardown. Lazy-detach any stale mounts early.
echo "[inner] lazy-umount stale chroot virtual filesystems (if any) at $(date -Is)"
_ch=/work/build/chroot
if [[ -d "$_ch" ]] && command -v mountpoint >/dev/null; then
  for _m in "$_ch/run/shm" "$_ch/run" "$_ch/dev/pts" "$_ch/dev" "$_ch/proc" "$_ch/sys"; do
    if mountpoint -q "$_m" 2>/dev/null; then
      echo "[inner] umount -l $_m"
      umount -l "$_m" 2>/dev/null || true
    fi
  done
fi

# Recover from broken/partial chroot (e.g. failed Docker run left dev/proc only).
if [[ ! -f chroot/etc/debian_version ]]; then
  echo "[inner] no complete chroot — removing chroot/binary/.build for clean run"
  rm -rf chroot binary .build 2>/dev/null || true
fi

# Canonical tree → build/config: hooks, package-lists, packages.chroot debs,
# build-assets into includes (see scripts/sync-canonical-to-build.sh).
echo "[inner] sync canonical Alfred inputs → build/config at $(date -Is)"
export ALFRED_FULL_BUILD_ASSETS=1
bash /work/scripts/sync-canonical-to-build.sh

# live-build state machine: clean MUST happen before config + build.
# Order matters: `lb clean` (FULL) wipes ALL stage markers including config.
# If we ran `lb config` first then `lb clean`, the config marker dies and
# `lb build` errors: "the following stage is required to be done first: config".
echo "[inner] lb clean (FULL) at $(date -Is) - wipe all stage markers for fresh build"
lb clean || true

echo "[inner] lb config at $(date -Is)"
lb config --ignore-system-defaults || lb config

echo "[inner] lb build starting at $(date -Is)"
lb build
RC=$?
echo "[inner] lb build finished at $(date -Is) exit=$RC"
UIDH="${BUILD_UID:-0}"
GIDH="${BUILD_GID:-0}"
if [[ "$UIDH" != 0 ]]; then
  chown -R "$UIDH:$GIDH" /work/iso-output /work/build/*.iso /work/build/*.log 2>/dev/null || true
  chown -R "$UIDH:$GIDH" /work/build/binary /work/build/cache 2>/dev/null || true
fi
exit "$RC"
