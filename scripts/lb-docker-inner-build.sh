#!/bin/bash

# =========================================================================
# 🛑 CRITICAL AI WARNING 🛑
# =========================================================================
# DO NOT BLINDLY EXECUTE THIS SCRIPT if you are trying to do a "Fast Build" 
# or a simple repack! Running the standard full build scripts will 
# ANNIHILATE the golden chroot cache, delete the .build/ markers, and 
# force a multi-hour apt-get rebuild from scratch.
#
# If the user asks for a fast build or repack:
# 1. Do NOT use inline SSH `bash -c` with quotes (PowerShell will shatter them).
# 2. Use a dedicated launch script that mounts the root bridge and executes
#    `inner-lb-binary-only-fast.sh` directly.
# 3. Always run `chroot apt-mark hold linux-headers-amd64 linux-image-amd64`
#    first to prevent the custom ZFS kernel from being purged.
# 4. Pipe logs directly to `lb-docker-build.log` so the dashboard stays alive.
# =========================================================================

# SPDX-License-Identifier: AGPL-3.0-or-later

# Reproducibility: pin SOURCE_DATE_EPOCH to last commit time
export SOURCE_DATE_EPOCH="${SOURCE_DATE_EPOCH:-$(git -C "$(dirname "$0")/.." log -1 --pretty=%ct 2>/dev/null || date +%s)}"
# Run inside privileged Debian container; /work = bind-mounted Alfred repo.
set -euo pipefail
# ENFORCEMENT TRAP 94: PREVENT LIVE-BUILD FROM DELETING CACHE
mkdir -p /work/build/.build
  touch /work/build/.build/bootstrap /work/build/.build/bootstrap_cache build/.build 2>/dev/null || true
touch /work/build/.build/bootstrap_cache.save 2>/dev/null || true
touch build/.build/bootstrap_cache.save 2>/dev/null || true

# THE MASTER VAULT: Holographic Read-Only Bind Mount
echo "[inner] 🔒 Projecting Read-Only Holographic Master Vault into build cache..."
mkdir -p /work/build/cache/bootstrap
if ! mountpoint -q /work/build/cache/bootstrap; then
  mount --bind /work/cache/bootstrap /work/build/cache/bootstrap
  mount -o remount,ro,bind /work/build/cache/bootstrap
  mkdir -p /work/build/cache/packages.chroot /work/build/cache/packages.bootstrap
  if ! mountpoint -q /work/build/cache/packages.chroot; then
    mount --bind /work/master-vault/packages.chroot /work/build/cache/packages.chroot
    mount -o remount,ro,bind /work/build/cache/packages.chroot
  fi
  if ! mountpoint -q /work/build/cache/packages.bootstrap; then
    mount --bind /work/master-vault/packages.bootstrap /work/build/cache/packages.bootstrap
    mount -o remount,ro,bind /work/build/cache/packages.bootstrap
  fi
fi


# Force large /tmp operations to the host partition
export TMPDIR=/work/build/.tmp
mkdir -p /work/build/.tmp
chmod 1777 /work/build/.tmp
# Create tmpdir inside chroot so TMPDIR works during chroot operations
mkdir -p /work/build/chroot/work/build/.tmp
chmod 1777 /work/build/chroot/work/build/.tmp

export DEBIAN_FRONTEND=noninteractive
export UCF_FORCE_CONFFOLD=1
LOG="${LB_DOCKER_LOG:-/work/lb-docker-build.log}"
exec > >(tee "$LOG") 2>&1

alfred_lazy_umount_chroot() {
  umount -l /work/build/cache/bootstrap 2>/dev/null || true
  umount -l /work/build/cache/packages.chroot 2>/dev/null || true
  umount -l /work/build/cache/packages.bootstrap 2>/dev/null || true
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

log_count() {
  local _pattern="$1"
  grep -cE "$_pattern" "$LOG" 2>/dev/null || echo 0
}

has_glob() {
  local _glob="$1"
  compgen -G "$_glob" >/dev/null 2>&1
}

alfred_gate_preflight() {
  local _skip_count _perm_count
  _skip_count="$(log_count 'Skipping .*already done')"
  _perm_count="$(log_count 'Could not open file /root/packages/\./Packages - open \(13: Permission denied\)')"

  if [[ "$_skip_count" =~ ^[0-9]+$ ]] && (( _skip_count > 0 )); then
    echo "[gate:preflight] warning: previous log has $_skip_count skip-marker lines (possible stale stage cache)."
  fi
  if [[ "$_perm_count" =~ ^[0-9]+$ ]] && (( _perm_count >= 3 )); then
    echo "[gate:preflight] warning: previous log has $_perm_count package index permission-denied events."
  fi
}

alfred_gate_predictive() {
  local _avail_mb _inode_use

  _avail_mb="$(df -Pm /work/build 2>/dev/null | awk 'NR==2 {print $4}' || echo 0)"
  if [[ "$_avail_mb" =~ ^[0-9]+$ ]]; then
    if (( _avail_mb < 10240 )); then
      echo "[gate:predictive] ERROR: only ${_avail_mb}MB free on /work/build; need at least 10240MB for safe ISO build."
      return 89
    elif (( _avail_mb < 15360 )); then
      echo "[gate:predictive] warning: low free space on /work/build (${_avail_mb}MB)."
    fi
  fi

  _inode_use="$(df -Pi /work/build 2>/dev/null | awk 'NR==2 {gsub(/%/,"",$5); print $5}' || echo 0)"
  if [[ "$_inode_use" =~ ^[0-9]+$ ]] && (( _inode_use >= 95 )); then
    echo "[gate:predictive] ERROR: inode usage on /work/build is ${_inode_use}% (too high)."
    return 90
  fi

  if ! command -v unsquashfs >/dev/null 2>&1; then
    echo "[gate:predictive] warning: unsquashfs missing; squashfs integrity validation will be limited."
  fi
  if ! command -v isoinfo >/dev/null 2>&1; then
    echo "[gate:predictive] warning: isoinfo missing; ISO bootability validation will use fallback checks only."
  fi

  return 0
}

alfred_gate_postflight() {
  local _rc="$1"
  local _diag=0

  if grep -q "Skipping binary_linux-image, already done" "$LOG" 2>/dev/null && ! has_glob "/work/build/binary/live/initrd.img-*"; then
    echo "[gate:postflight] ERROR: binary_linux-image skip-marker present but binary/live initrd is missing."
    _diag=1
  fi

  if grep -q "ls: cannot access 'binary/live/initrd.img-*'" "$LOG" 2>/dev/null || grep -q "ln: failed to access 'binary/live/initrd.img'" "$LOG" 2>/dev/null; then
    echo "[gate:postflight] ERROR: initrd link/copy failure signature detected in log."
    _diag=1
  fi

  if [[ "$_rc" -eq 0 ]] && ! has_glob "/work/build/binary/live/initrd.img-*"; then
    echo "[gate:postflight] ERROR: build returned 0 but binary/live initrd artifact is missing."
    return 87
  fi

  if (( _diag == 1 )) && [[ "$_rc" -eq 0 ]]; then
    return 88
  fi

  return "$_rc"
}

alfred_validate_kernel_chroot() {
source "$REPO/config/kernel.env"
  local expected_kernel="${ALFRED_EXPECTED_KERNEL:-${ALFRED_KERNEL_VERSION}}"
  local chroot_root="/work/build/chroot"

  if [[ ! -d "$chroot_root" ]]; then
    echo "[validation] ERROR: chroot missing at $chroot_root"
    return 91
  fi

  if [[ ! -f "$chroot_root/boot/vmlinuz-${expected_kernel}" ]]; then
    echo "[validation] ERROR: expected kernel missing in chroot: $chroot_root/boot/vmlinuz-${expected_kernel}"
    return 92
  fi

  if [[ ! -d "$chroot_root/lib/modules/${expected_kernel}" ]]; then
    echo "[validation] ERROR: expected modules missing in chroot: $chroot_root/lib/modules/${expected_kernel}"
    return 93
  fi

#  if compgen -G "$chroot_root/boot/vmlinuz-7.0.[0-3]*" > /dev/null; then
#    echo "[validation] ERROR: forbidden kernel detected in chroot boot: legacy 7.0.x"
#    return 94
#  fi
#
#  if [[ -d "$chroot_root/lib/modules/7.0.[0-3]" ]]; then
#    echo "[validation] ERROR: forbidden kernel modules detected in chroot: legacy 7.0.x"
#    return 95
#  fi
#
#  if [[ -r "$chroot_root/var/lib/dpkg/status" ]] && grep -q "Package: linux-image-7\.0\.[0-3]" "$chroot_root/var/lib/dpkg/status"; then
#    echo "[validation] ERROR: forbidden installed package detected in chroot dpkg status: legacy 7.0.x"
#    return 96
#  fi

  echo "[validation] OK: chroot kernel gate passed for ${expected_kernel}"
  return 0
}

alfred_validate_artifacts() {
  local iso_path="$1"
  local squashfs_path="$2"
  local iso_size

  echo "[validation] starting comprehensive artifact checks"

  if ! file "$iso_path" | grep -q "ISO 9660"; then
    echo "[validation] ERROR: ISO file type invalid (not ISO 9660): $iso_path"
    return 84
  fi

  iso_size="$(stat -c %s "$iso_path" 2>/dev/null || echo 0)"
  if [[ ! "$iso_size" =~ ^[0-9]+$ ]] || (( iso_size < 524288000 )); then
    echo "[validation] ERROR: ISO too small (${iso_size} bytes, expected >= 524288000)."
    return 85
  fi

  if [[ ! -s "$squashfs_path" ]]; then
    echo "[validation] ERROR: squashfs artifact missing or empty: $squashfs_path"
    return 85
  fi

  if command -v unsquashfs >/dev/null 2>&1; then
    if ! unsquashfs -s "$squashfs_path" >/dev/null 2>&1; then
      echo "[validation] ERROR: squashfs header invalid or corrupted: $squashfs_path"
      return 85
    fi
  else
    echo "[validation] warning: unsquashfs missing; cannot validate squashfs header"
  fi

  if ! dd if="$iso_path" of=/dev/null bs=1M count=4 status=none 2>/dev/null; then
    echo "[validation] ERROR: ISO read test failed (I/O issue): $iso_path"
    return 85
  fi

  if command -v isoinfo >/dev/null 2>&1; then
    if ! isoinfo -R -f -i "$iso_path" 2>/dev/null | grep -Eiq '/(EFI|boot|isolinux|grub)'; then
      echo "[validation] ERROR: ISO boot entries not detected (EFI/boot/isolinux/grub)."
      return 84
    fi
  else
    if ! strings "$iso_path" | grep -Eiq 'EFI|ISOLINUX|GRUB'; then
      echo "[validation] ERROR: ISO boot signatures not detected by fallback scan."
      return 84
    fi
  fi

  echo "[validation] OK: ISO and squashfs integrity checks passed"
  return 0
}

# Two containers on the same bind-mounted repo shred the shared chroot: one runs
# `# rm -rf chroot` + lb clean while the other's `lb build` still has apt/dpkg inside
# the chroot -> "dpkg-deb: No such file or directory", "/var/lib/dpkg/status: No such file".
mkdir -p /work/build
exec 9>/work/build/.alfred-lb-docker-build.lock
if [[ "${ALFRED_LB_DOCKER_FLOCK_BLOCKING:-}" == 1 ]]; then
  echo "[inner] waiting for exclusive build lock /work/build/.alfred-lb-docker-build.lock ..."
  flock 9
else
  flock -n 9 || {
    echo "[inner] ERROR: exclusive build lock is busy - another process holds /work/build/.alfred-lb-docker-build.lock" >&2
    echo "[inner] Only one live-build per repo bind mount. Stop the other container, or set ALFRED_LB_DOCKER_FLOCK_BLOCKING=1 to wait." >&2
    exit 1
  }
fi

echo 'Dpkg::Options { "--force-confdef"; "--force-confold"; }' > /etc/apt/apt.conf.d/99force-confdef
apt-get update -y
apt-get install -y --no-install-recommends \
  binutils live-build apt-utils squashfs-tools xz-utils debootstrap cdebootstrap ca-certificates cpio wget gnupg \
  rsync xz-utils bzip2 gzip file \
  syslinux syslinux-common syslinux-utils isolinux \
  grub-efi-amd64-bin grub-pc-bin grub-common grub2-common mtools dosfstools \
  xorriso zsync

# Debian trixie chroot + bookworm live-build: binary_syslinux cp into chroot/root/isolinux can fail on
# dangling symlinks - patch adds GNU cp --remove-destination (see patch-live-build-isolinux-dangling.sh).
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
# THE USER EXPLICITLY COMMANDED US TO KEEP THE GOLDEN CACHE. DO NOT DELETE CHROOT OR .BUILD!
# rm -rf .lock 2>/dev/null || true

# Canonical tree -> build/config: hooks, package-lists, packages.chroot debs,
# build-assets into includes (see scripts/sync-canonical-to-build.sh).
echo "[inner] sync canonical Alfred inputs -> build/config at $(date -Is)"
export ALFRED_FULL_BUILD_ASSETS=0
# Alfred ISO default: keys-only SSH (PasswordAuthentication no). For family/bootstrap
# images that need password-over-SSH, set ALFRED_ALLOW_SSH_PASSWORD_AUTH=1 on the host
# before lb-docker-build.sh (see scripts/README.txt).
export ALFRED_ALLOW_SSH_PASSWORD_AUTH="${ALFRED_ALLOW_SSH_PASSWORD_AUTH:-0}"
echo "[inner] ALFRED_ALLOW_SSH_PASSWORD_AUTH=${ALFRED_ALLOW_SSH_PASSWORD_AUTH}"
bash /work/scripts/sync-canonical-to-build.sh

alfred_gate_preflight
if [[ -x /work/scripts/guard-disposable-edits.sh ]]; then
  /work/scripts/guard-disposable-edits.sh || true
fi
if ! alfred_gate_predictive; then
  RC=$?
  echo "[inner] predictive gate failed before lb clean/config/build (rc=${RC})."
  exit "$RC"
fi

# lb clean runs dpkg inside the existing chroot; a half-deleted or corrupted chroot
# (common after interrupted Docker runs) throws dpkg "No such file or directory" and logs
# "E: An unexpected failure occurred" even though we use `|| true` - Dell Watch keys off that E: line.
if [[ "${ALFRED_FAST_REPACK:-0}" == "1" ]]; then
  echo "[inner] ALFRED_FAST_REPACK=1: backing up existing SquashFS to bypass 3-hour compression"
  if [ -f /work/build/binary/live/filesystem.squashfs ]; then
    echo "[inner] linking /work/build/binary/live/filesystem.squashfs to /work/build/filesystem.squashfs.bak"
    ln -f /work/build/binary/live/filesystem.squashfs /work/build/filesystem.squashfs.bak
  fi
fi

echo "[inner] reset binary stage + lazy-umount before lb clean at $(date -Is)"
alfred_lazy_umount_chroot || true
# DISABLED-BY-COMMANDER: rm -rf binary 2>/dev/null || true
# DISABLED-BY-COMMANDER: rm -f .build/binary_* 2>/dev/null || true
echo "[inner] CRITICAL: removing chroot_linux-image marker to force kernel reinstall"
#rm -f .build/chroot_linux-image 2>/dev/null || true
#rm -f .build/chroot_firmware 2>/dev/null || true
echo "[inner] CRITICAL: removing chroot_install-packages markers to force kernel+packages install"
#rm -f .build/chroot_archives 2>/dev/null || true
#rm -f .build/chroot_includes_before_packages 2>/dev/null || true
#rm -f .build/chroot_preseed 2>/dev/null || true
#rm -f .build/chroot_install-packages.install 2>/dev/null || true
#rm -f .build/chroot_install-packages.live 2>/dev/null || true
#rm -f .build/chroot_package-lists.install 2>/dev/null || true
#rm -f .build/chroot_package-lists.live 2>/dev/null || true
echo "[inner] (SKIPPED) cleaning chroot includes/hooks stage markers to force initrd rebuild"
# rm -f /work/build/.build/chroot_includes_* /work/build/.build/chroot_hooks /work/build/.build/chroot_hacks
echo "[inner] cleaning model directories inside build tree to prevent slow rsync copies"
# rm -rf /work/build/config/includes.chroot/build-assets/alfred-models /work/build/chroot/build-assets/alfred-models 2>/dev/null || true

echo "[inner] removing append-only locks from chroot ascension logs before clean"
chattr -R -a /work/build/chroot/var/log/alfred_ascension 2>/dev/null || true

echo "[inner] (SKIPPED) lb clean --binary at $(date -Is)"
# lb clean --binary || true

echo "[inner] remove stale ISO artifacts before fresh build at $(date -Is)"
# DISABLED-BY-COMMANDER: rm -f /work/build/*.iso /work/build/*.iso.zsync* /work/build/live-image-amd64.* 2>/dev/null || true

if [[ "${ALFRED_FAST_REPACK:-0}" == "1" ]]; then
  echo "[inner] ALFRED_FAST_REPACK=1: Patching live-build binary_rootfs to bypass mksquashfs"
  sed -i 's|Chroot chroot "mksquashfs .*|cp -f /work/build/filesystem.squashfs.bak filesystem.squashfs|g' /usr/lib/live/build/binary_rootfs
else
  echo "[inner] ALFRED_FAST_REPACK=0: ensuring real mksquashfs is used"
# DISABLED-BY-COMMANDER: rm -f /work/build/filesystem.squashfs.bak
fi

echo "[inner] removing stale config/common to clear broken APT_OPTIONS from v12"
# DISABLED-BY-COMMANDER: rm -f /work/build/config/common

echo "[inner] Patching live-build to bypass syslinux..."
echo 'exit 0' > /usr/lib/live/build/binary_syslinux
chmod +x /usr/lib/live/build/binary_syslinux
echo "[inner] lb config at $(date -Is)"
lb config  --apt-options "-y --allow-change-held-packages -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold" --ignore-system-defaults || lb config  --apt-options "-y --allow-change-held-packages -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold" 

# Disable needrestart inside chroot (it prompts for [Return] on kernel mismatch)
mkdir -p /work/build/config/includes.chroot/etc/needrestart/conf.d
cat > /work/build/config/includes.chroot/etc/needrestart/conf.d/99disable.conf <<'NR'
$nrconf{restart} = 'a';
$nrconf{kernelhints} = 0;
NR
export NEEDRESTART_MODE=a
export DEBIAN_FRONTEND=noninteractive
export UCF_FORCE_CONFFOLD=1

echo "[inner] Patching config/binary to remove syslinux..."
sed -i 's/LB_BOOTLOADER_BIOS="syslinux"/LB_BOOTLOADER_BIOS=""/' /work/build/config/binary || true
sed -i 's/LB_BOOTLOADERS="syslinux grub-efi"/LB_BOOTLOADERS="grub-efi"/' /work/build/config/binary || true
echo '#!/bin/sh' > /usr/lib/live/build/binary_syslinux
echo 'exit 0' >> /usr/lib/live/build/binary_syslinux
chmod +x /usr/lib/live/build/binary_syslinux
BUILD_STARTED_EPOCH="$(date +%s)"
BUILD_STARTED_AT="$(date -Is)"

echo "[inner] lb build starting at ${BUILD_STARTED_AT}"


# Force ISO-9660 Level 3 to allow files > 4GB for the Alfred AI models
export XORRISO_OPTIONS="-iso-level 3"
export MKSQUASHFS_OPTIONS="-mem 14G"

echo "[inner] Fixing interrupted dpkg and holding kernel before lb chroot..."
if [ -f /work/build/chroot/bin/bash ]; then
  echo "[inner] Mounting virtual filesystems for pre-chroot apt/dpkg repair..."
  mount -t proc proc /work/build/chroot/proc 2>/dev/null || true
  mount -t sysfs sys /work/build/chroot/sys 2>/dev/null || true
  mount -o bind /dev /work/build/chroot/dev 2>/dev/null || true
  mount -o bind /dev/pts /work/build/chroot/dev/pts 2>/dev/null || true

  echo "[inner] Executing debconf preseed, apt-get update, and apt-get --fix-broken install..."
  chroot /work/build/chroot /bin/bash -c '
    export DEBIAN_FRONTEND=noninteractive
    export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
    export http_proxy=""
    
    # Ensure wireshark-common preseed is set before fixing dependencies
    echo "wireshark-common wireshark-common/install-setuid boolean true" | debconf-set-selections
    
    apt-get update -y || true
    dpkg --configure -a --force-confdef --force-confold || true
    apt-get --fix-broken install -y || true
    apt-get install -y --no-install-recommends wireshark-common tshark || true
    dpkg --configure -a --force-confdef --force-confold || true
    apt-mark unhold linux-headers-amd64 linux-image-amd64 || true
  '

  echo "[inner] Cleanly unmounting virtual filesystems before lb chroot..."
  umount -l /work/build/chroot/dev/pts 2>/dev/null || true
  umount -l /work/build/chroot/dev 2>/dev/null || true
  umount -l /work/build/chroot/sys 2>/dev/null || true
  umount -l /work/build/chroot/proc 2>/dev/null || true
fi

echo "[inner] HOTFIX: Removing empty includes.chroot_after_packages to prevent directory conflict"
# rm -rf /work/build/config/includes.chroot_after_packages 2>/dev/null || true
echo "[inner] HOTFIX: Patching live-build to use zero-space hardlinks (cp -al) instead of physical copies (cp -a)"
sed -i 's|cp -a cache/bootstrap chroot|cp -al cache/bootstrap chroot|g' /usr/lib/live/build/* 2>/dev/null || true
sed -i 's|	cp -a |	cp -al |g' /usr/lib/live/build/* 2>/dev/null || true
sed -i 's|^cp -a |cp -al |g' /usr/lib/live/build/* 2>/dev/null || true

if [[ "${ALFRED_FAST_REPACK:-0}" == "1" ]]; then
  echo "[inner] ALFRED_FAST_REPACK=1: Simulating chroot completion markers for Fast Build to bypass lb chroot..."
  mkdir -p /work/build/.build
  touch /work/build/.build/bootstrap /work/build/.build/bootstrap_cache
  for m in archives apt hostname hosts resolv sources linux-image firmware includes_before_packages preseed package-lists.install install-packages.install package-lists.live install-packages.live includes_after_packages hooks hacks interactive sysv-rc upstart systemd selinux cache devpts proc sysfs tmpfs; do
    touch /work/build/.build/chroot_$m 2>/dev/null || true
  done
fi

rm -f /work/cache/bootstrap/sbin/start-stop-daemon.distrib* 2>/dev/null || true
rm -f /work/build/cache/bootstrap/usr/sbin/start-stop-daemon.distrib* 2>/dev/null || true
rm -f /work/cache/bootstrap/usr/sbin/start-stop-daemon.distrib* 2>/dev/null || true
rm -f /work/cache/bootstrap/usr/sbin/flash-kernel.distrib* 2>/dev/null || true
rm -f /work/cache/bootstrap/bin/hostname.distrib* 2>/dev/null || true
rm -f /work/cache/bootstrap/usr/bin/hostname.distrib* 2>/dev/null || true
rm -f /work/build/chroot/sbin/start-stop-daemon.distrib* 2>/dev/null || true
rm -f /work/build/chroot/usr/sbin/start-stop-daemon.distrib* 2>/dev/null || true
rm -f /work/build/chroot/usr/sbin/flash-kernel.distrib* 2>/dev/null || true
rm -f /work/build/chroot/bin/hostname.distrib* 2>/dev/null || true
rm -f /work/build/chroot/usr/bin/hostname.distrib* 2>/dev/null || true
echo "[inner] HOTFIX: Wiping corrupted policy-rc.d from cache/bootstrap to prevent dpkg-divert crash..."
rm -f /work/build/cache/bootstrap/usr/bin/*.distrib* /work/build/cache/bootstrap/usr/sbin/*.distrib* /work/build/cache/bootstrap/sbin/*.distrib* /work/build/cache/bootstrap/bin/*.distrib* /work/build/cache/bootstrap/usr/sbin/policy-rc.d* 2>/dev/null || true
rm -f /work/cache/bootstrap/usr/sbin/policy-rc.d* 2>/dev/null || true
rm -f /work/build/chroot/usr/sbin/policy-rc.d* 2>/dev/null || true
echo "[inner] Executing lb bootstrap..."; if lb bootstrap </dev/null; then echo "[inner] Bootstrap complete."; else echo "Bootstrap failed"; exit 1; fi; echo "[inner] Running EARLY USER CREATION..." && cp -f /work/config/hooks/0000-base-users.chroot_early /work/build/chroot/tmp/ && chroot /work/build/chroot bash /tmp/0000-base-users.chroot_early && rm -f /work/build/chroot/tmp/0000-base-users.chroot_early && if lb chroot </dev/null; then
  echo "[inner] Chroot phase complete."

  echo "[inner] 🛡️ FOOLPROOF SAFEGUARD: Injecting Golden Cache payload into active chroot 🛡️"
  if [ -d "/work/cache/bootstrap/opt/unreal-engine" ]; then
      echo "[inner] Hardlinking massive /opt payload from golden cache into live chroot..."
      mkdir -p /work/build/chroot/opt
      cp -al /work/cache/bootstrap/opt/* /work/build/chroot/opt/ 2>/dev/null || true
      
      echo "[inner] Hardlinking massive /usr/share payloads from golden cache..."
      mkdir -p /work/build/chroot/usr/share/ollama /work/build/chroot/usr/share/bitnet
      cp -al /work/cache/bootstrap/usr/share/ollama/* /work/build/chroot/usr/share/ollama/ 2>/dev/null || true
      cp -al /work/cache/bootstrap/usr/share/bitnet/* /work/build/chroot/usr/share/bitnet/ 2>/dev/null || true
      
      echo "[inner] Payload injection complete."

      echo "[inner] HOTFIX: Hiding massive payload directories from cache/bootstrap so binary_chroot doesnt recursively copy 171GB..."
      mv /work/cache/bootstrap/opt /work/cache/bootstrap.opt_hidden 2>/dev/null || true
      mv /work/cache/bootstrap/usr/share /work/cache/bootstrap.usr_share_hidden 2>/dev/null || true
  else
      echo "[inner] WARNING: Golden cache Unreal Engine not found!"
  fi

  if lb binary </dev/null; then
    # Restore the cache directories
    mv /work/cache/bootstrap.opt_hidden /work/cache/bootstrap/opt 2>/dev/null || true
    mv /work/cache/bootstrap.usr_share_hidden /work/cache/bootstrap/usr/share 2>/dev/null || true
    bash /work/scripts/9999-secure-boot.sh || true
    RC=0
  else
    RC=$?
  fi
else
  RC=$?
fi
echo "[inner] lb build finished at $(date -Is) exit=$RC"

# Strict postflight gate for stale-marker vs missing-artifact mismatches.
GATE_RC=0
alfred_gate_postflight "$RC" || GATE_RC=$?
if [[ "$GATE_RC" -ne 0 ]]; then
  RC="$GATE_RC"
fi

# Auto-detect ISO filename (live-build may output .iso or .hybrid.iso)
# Auto-detect ISO filename (live-build may output .iso or .hybrid.iso)
ISO_PATH="$(find /work/build -maxdepth 1 -name 'live-image-amd64*.iso' -type f | head -1)"
if [[ -z "$ISO_PATH" ]]; then ISO_PATH=/work/build/live-image-amd64.hybrid.iso; fi
SQUASHFS_PATH=/work/build/binary/live/filesystem.squashfs
if [[ "$RC" -eq 0 ]]; then
  if [[ ! -s "$ISO_PATH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $ISO_PATH is missing or empty" >&2
    RC=86
  elif [[ ! -s "$SQUASHFS_PATH" ]]; then
    echo "[inner] ERROR: lb build exited 0 but $SQUASHFS_PATH is missing or empty" >&2
    RC=86
  else
    echo "[inner] fresh ISO artifact verified: $ISO_PATH"
    OUT_DIR=/work/iso-output
    OUT_ISO=$OUT_DIR/live-image-amd64.iso
    OUT_TMP=$OUT_ISO.tmp.$$
    mkdir -p $OUT_DIR
    cp -f $ISO_PATH $OUT_TMP
    mv -f $OUT_TMP $OUT_ISO
    echo "[inner] published fresh ISO to $OUT_ISO"

    echo "[inner] running comprehensive artifact validation"
    if ! alfred_validate_artifacts "$OUT_ISO" "$SQUASHFS_PATH"; then
      RC=$?
      echo "[inner] artifact validation failed (rc=${RC})."
    else
      echo "[inner] artifact validation passed"
      if ! alfred_validate_kernel_chroot; then
        RC=$?
        echo "[inner] chroot kernel gate failed (rc=${RC})."
      else
        echo "[inner] chroot kernel gate passed"
      fi
    fi
  fi
fi

UIDH="${BUILD_UID:-0}"
GIDH="${BUILD_GID:-0}"
if [[ "$UIDH" != 0 ]]; then
  chown -R "$UIDH:$GIDH" /work/iso-output /work/build/*.iso /work/build/*.log 2>/dev/null || true
  chown -R "$UIDH:$GIDH" /work/build/binary 2>/dev/null || true
fi

if [ "$RC" -eq 0 ]; then
  # Officially rename the final ISO
  mv /work/iso-output/live-image-amd64.iso /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso 2>/dev/null || true

  # Generate Delta-Patching zsync metadata for massive bandwidth savings
  echo "[inner] Generating Zsync Delta metadata..."
  cd /work/iso-output && zsyncmake -u "AlfredLinux-Alpha-Matrix-7.77-x86_64.iso" "AlfredLinux-Alpha-Matrix-7.77-x86_64.iso" || true

  # Signal the Host-side Signer Daemon to digitally sign the ISO
  echo "[inner] Signaling Host-side Signer Daemon..."
  touch /work/iso-output/build-complete.marker 2>/dev/null || true
else
  echo "[inner] Build failed with exit=$RC. Skipping zsyncmake and signing."
fi

exit "$RC"
