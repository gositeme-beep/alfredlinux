#!/bin/bash
# Run inside privileged Debian container; /work = bind-mounted Alfred repo.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
LOG="${LB_DOCKER_LOG:-/work/lb-docker-build.log}"
exec > >(tee -a "$LOG") 2>&1

apt-get update -y
apt-get install -y --no-install-recommends \
  live-build debootstrap cdebootstrap ca-certificates cpio wget gnupg \
  rsync xz-utils bzip2 gzip file

# Debian trixie chroot + bookworm live-build: binary_syslinux can hit GNU cp refusing to
# overwrite dangling symlinks under chroot/root/isolinux (see scripts/patch-live-build-isolinux-dangling.sh).
bash /work/scripts/patch-live-build-isolinux-dangling.sh

cd /work/build
# Recover from broken/partial chroot (e.g. failed Docker run left dev/proc only).
if [[ ! -f chroot/etc/debian_version ]]; then
  echo "[inner] no complete chroot — removing chroot/binary/.build for clean run"
  rm -rf chroot binary .build 2>/dev/null || true
fi

# live-build requires the `config` stage before chroot stages (error otherwise:
# "E: the following stage is required to be done first: config").
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
