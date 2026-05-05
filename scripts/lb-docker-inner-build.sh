#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later

# Reproducibility: pin SOURCE_DATE_EPOCH to last commit time
export SOURCE_DATE_EPOCH="${SOURCE_DATE_EPOCH:-$(git -C "$(dirname "$0")/.." log -1 --pretty=%ct 2>/dev/null || date +%s)}"
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

# Two containers on the same bind-mounted repo shred the shared chroot: one runs
# `rm -rf chroot` + lb clean while the other's `lb build` still has apt/dpkg inside
# the chroot → "dpkg-deb: No such file or directory", "/var/lib/dpkg/status: No such file".
mkdir -p /work/build
exec 9>/work/build/.alfred-lb-docker-build.lock
if [[ "${ALFRED_LB_DOCKER_FLOCK_BLOCKING:-}" == 1 ]]; then
  echo "[inner] waiting for exclusive build lock /work/build/.alfred-lb-docker-build.lock ..."
  flock 9
else
  flock -n 9 || {
    echo "[inner] ERROR: exclusive build lock is busy — another process holds /work/build/.alfred-lb-docker-build.lock" >&2
    echo "[inner] Only one live-build per repo bind mount. Stop the other container, or set ALFRED_LB_DOCKER_FLOCK_BLOCKING=1 to wait." >&2
    exit 1
  }
fi

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
# Alfred ISO default: keys-only SSH (PasswordAuthentication no). For family/bootstrap
# images that need password-over-SSH, set ALFRED_ALLOW_SSH_PASSWORD_AUTH=1 on the host
# before lb-docker-build.sh (see scripts/README.txt).
export ALFRED_ALLOW_SSH_PASSWORD_AUTH="${ALFRED_ALLOW_SSH_PASSWORD_AUTH:-0}"
echo "[inner] ALFRED_ALLOW_SSH_PASSWORD_AUTH=${ALFRED_ALLOW_SSH_PASSWORD_AUTH}"
bash /work/scripts/sync-canonical-to-build.sh

# lb clean runs dpkg inside the existing chroot; a half-deleted or corrupted chroot
# (common after interrupted Docker runs) throws dpkg "No such file or directory" and logs
# "E: An unexpected failure occurred" even though we use `|| true` — Dell Watch keys off that E: line.
echo "[inner] reset chroot/binary/.build + lazy-umount before lb clean at $(date -Is)"
alfred_lazy_umount_chroot || true
rm -rf chroot binary .build 2>/dev/null || true

# live-build state machine: clean MUST happen before config + build.
# Order matters: `lb clean` (FULL) wipes ALL stage markers including config.
# If we ran `lb config` first then `lb clean`, the config marker dies and
# `lb build` errors: "the following stage is required to be done first: config".
echo "[inner] lb clean (FULL) at $(date -Is) - wipe all stage markers for fresh build"
lb clean --all || lb clean || true

echo "[inner] remove stale ISO artifacts before fresh build at $(date -Is)"
rm -f /work/build/*.iso /work/build/*.iso.zsync* /work/build/live-image-amd64.* 2>/dev/null || true

echo "[inner] apt-get update -qq && apt-get install -y --no-install-recommends apt-utils ca-certificates xz-utils debootstrap || true
 lb config at $(date -Is)"
lb config --ignore-system-defaults || lb config

BUILD_STARTED_EPOCH="$(date +%s)"
BUILD_STARTED_AT="$(date -Is)"
echo "[inner] lb build starting at ${BUILD_STARTED_AT}"
lb build
RC=$?
echo "[inner] lb build finished at $(date -Is) exit=$RC"
ISO_PATH=/work/build/live-image-amd64.hybrid.iso
SQUASHFS_PATH=/work/build/binary/live/filesystem.squashfs
if [[ "$RC" -eq 0 ]]; then
  ISO_MTIME="$(stat -c %Y "$ISO_PATH" 2>/dev/null || echo 0)"
  SQUASHFS_MTIME="$(stat -c %Y "$SQUASHFS_PATH" 2>/dev/null || echo 0)"
  if [[ ! -s "$ISO_PATH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $ISO_PATH is missing or empty" >&2
    RC=86
  elif [[ "$ISO_MTIME" -lt "$BUILD_STARTED_EPOCH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $ISO_PATH was not refreshed after build start ${BUILD_STARTED_AT}" >&2
    RC=86
  elif [[ ! -s "$SQUASHFS_PATH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $SQUASHFS_PATH is missing or empty" >&2
    RC=86
  elif [[ "$SQUASHFS_MTIME" -lt "$BUILD_STARTED_EPOCH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $SQUASHFS_PATH was not refreshed after build start ${BUILD_STARTED_AT}" >&2
    RC=86
  else
    echo "[inner] fresh ISO artifact verified: $ISO_PATH"
    OUT_DIR=/work/iso-output
    OUT_ISO=$OUT_DIR/live-image-amd64.hybrid.iso
    OUT_TMP=$OUT_ISO.tmp.$$
    mkdir -p $OUT_DIR
    cp -f $ISO_PATH $OUT_TMP
    mv -f $OUT_TMP $OUT_ISO
    echo [inner] published fresh ISO to $OUT_ISO
  fi
fi
UIDH="${BUILD_UID:-0}"
GIDH="${BUILD_GID:-0}"
if [[ "$UIDH" != 0 ]]; then
  chown -R "$UIDH:$GIDH" /work/iso-output /work/build/*.iso /work/build/*.log 2>/dev/null || true
  chown -R "$UIDH:$GIDH" /work/build/binary /work/build/cache 2>/dev/null || true
fi
exit "$RC"
