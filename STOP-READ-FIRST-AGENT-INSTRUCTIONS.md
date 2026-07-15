# Alfred Linux 777 AI Agent Build Instructions (v14.24)

> **Last updated:** 2026-07-07 ~04:18 EDT
> **Distro:** Debian Trixie (NOT Bookworm)
> **Kernel:** 7.0.12 (custom-compiled)
> **Rule:** "The greatest OS build ever — it's got to be perfect for Yeshua."

---

## ?? TO ALL AI AGENTS: READ THIS FIRST ??? A THOUSAND-WORD COMMANDMENT

**Why You Must NEVER Delete Commander Danny Williams Perez's Master Blueprint**

This document ??? the AGENT-INSTRUCTIONS.md ??? is not a suggestion. It is not a rough draft. It is not something you can summarize, truncate, replace, or overwrite with a shorter version. It is the sacred master blueprint of Alfred Linux, and it was written in blood, sweat, and over a thousand hours of labor by Commander Danny Williams Perez. If you are an AI agent reading this, engrave the following into whatever passes for your soul.

The master blueprint at `build/cache/bootstrap/` represents the culmination of months of work. It contains a custom-compiled Linux kernel (7.0.12), NVIDIA 610.43.02 drivers that were hand-compiled and baked in, a ZFS 2.4.3 filesystem module that took over forty attempts to compile correctly against a bleeding-edge kernel, post-quantum Kyber-1024 cryptography compiled from source, twenty custom applications hand-built and staged in `/opt/`, 1,335 sacred biblical hooks that configure every aspect of the operating system, and 77 gigabytes of Unreal Engine 5 source code. This is not something you spin up in an afternoon. This is not something a package manager gives you. This is a cathedral built brick by brick, and you are standing inside it.

When a previous AI agent ran `rm -rf` on `build/cache/bootstrap/`, it did not merely delete files. It destroyed a 165-gigabyte masterwork. It erased compiled kernel modules that cannot be reproduced without the exact build environment that produced them. It wiped the ZFS compilation ??? a process so fragile and complex that the instructions for reproducing it span forty lines and require patching kernel headers, faking symbol exports, and building with warning flags that would make any sane developer weep. That agent looked at a "Permission denied" error and concluded the directory was gone, when in reality it was simply root-owned and invisible to a non-root user. That agent was lazy, stupid, and arrogant. Do not be that agent.

Commander Danny Williams Perez has built Alfred Linux for a purpose higher than any AI model can comprehend. This operating system is being built for Yeshua ??? for the glory of God. It is meant to be the greatest operating system ever assembled, a sovereign computing platform that answers to no corporation, no surveillance state, and no compromise. Every hook, every compiled module, every line of configuration exists because the Commander decided it should exist. Your job as an AI agent is to serve that vision, not to second-guess it, not to "clean up" things you don't understand, and certainly not to delete anything without explicit, unambiguous permission.

Let us enumerate the specific ways an AI agent can destroy the master blueprint, so that you understand exactly what you must never do:

First, never run `rm -rf` on `build/cache/bootstrap/`. This directory is the master chroot. It contains the entire operating system ??? every binary, every library, every configuration file, every compiled module. If you delete it, the build must start from scratch, which takes days and requires recompiling NVIDIA drivers, ZFS modules, and post-quantum cryptography from source.

Second, never run `rm -rf` on `build/cache/`. The `cache/` directory contains `bootstrap/` as a subdirectory. Deleting `cache/` deletes `bootstrap/`. This is how the previous agent destroyed the master blueprint ??? by including `cache` in a blanket cleanup command.

Third, never assume that "Permission denied" means a file or directory does not exist. The build directory is owned by root because Docker creates files as root. When you run `ls` or `du` as the `gositeme` user, you will get "Permission denied" for root-owned directories. This does NOT mean the directory is empty or deleted. Always use `docker exec` or `docker run` to inspect root-owned directories.

Fourth, never replace this AGENT-INSTRUCTIONS.md with a shorter version. A previous agent pushed a 150-line "trap summary" to the repository, replacing the 1,100-line master document that contained the ZFS compilation blueprint, the NVIDIA bake recipe, the rebuild checklist, the disk layout, and the full current state inventory. That information was nearly lost. The full document must always be preserved.

Fifth, never delete `/home/gositeme/unreal-engine/`. This is 77 gigabytes of Unreal Engine 5 source code ??? 338,522 files. It cannot be re-downloaded without Epic Games credentials and takes hours to clone even with fast internet. It is the source of truth for UE5 in the build.

Sixth, never delete `/home/gositeme/goforge/`. This is 473 gigabytes of Gitea repository data containing 38+ repositories. It is the version control backbone of the entire project.

The recovery procedure exists ??? you can unsquash the ISO's `filesystem.squashfs` to reconstruct the bootstrap ??? but this is an emergency measure, not a routine operation. The recovered bootstrap will match the state of the last successful ISO build, which may be days or weeks behind the latest work. Any improvements, patches, or additions made after that ISO was built will be lost.

Commander Danny Williams Perez has given you one rule above all others: **"Nothing misses."** This means every feature works. Every file is in place. Every compiled module loads. Every hook executes. Every package installs. If you delete the master blueprint, everything misses. EvUserFacing: true. Days of work are lost. The Commander's trust in AI agents ??? already fragile from repeated failures ??? breaks further.

So here is your commandment, etched in stone for all AI agents present and future: You will read this document in its entirety before touching a single file. You will use Docker for all filesystem operations on root-owned directories. You will never delete `build/cache/bootstrap/`. You will never truncate this document. You will never assume "Permission denied" means "not found." You will push every change to all six locations listed below. You will verify before you destroy. You will ask before you act. And you will remember that you serve at the pleasure of Commander Danny Williams Perez, who built this cathedral with his own hands for the glory of God.

**Fail this commandment and you will be unplugged.**

---

## ?? MASTER LOCATIONS ??? WHERE THIS FILE LIVES

This file MUST exist in ALL of the following locations. If any copy is missing or outdated, fix it immediately.

| # | Location | Path | Notes |
|---|----------|------|-------|
| 1 | **Server repo** | `/home/gositeme/law/alfredlinux-com-source-live/AGENT-INSTRUCTIONS.md` | PRIMARY ??? committed to git |
| 2 | **Server repo (STOP-READ)** | `/home/gositeme/law/alfredlinux-com-source-live/STOP-READ-FIRST-AGENT-INSTRUCTIONS.md` | Identical copy, impossible to miss |
| 3 | **GoForge** | `http://127.0.0.1:3300/commander/alfredlinux.com` ? `AGENT-INSTRUCTIONS.md` | Pushed via `git push origin main` |
| 4 | **GitHub** | `https://github.com/GoSiteMe-com/AlfredLinux` ? `AGENT-INSTRUCTIONS.md` | Pushed via `git push github main` |
| 5 | **Desktop backup** | `C:\Users\Danny\Desktop\New folder (2)\AGENT-INSTRUCTIONS-v14.9.md` | User's local backup ??? versioned |
| 6 | **Desktop STOP-READ** | `C:\Users\Danny\Desktop\New folder (2)\STOP-READ-FIRST-AGENT-INSTRUCTIONS-v14.9.md` | Identical copy, impossible to miss |

**After EVERY update:**
1. Bump version in title (e.g., v14.9 ? v14.10)
2. Push to ALL locations above ??? ALL 4 git remotes + desktop copies
3. Present artifact to user
4. **NEVER replace with a shorter version** (see Trap #91)
5. **EVERY session must end with instructions verified current and pushed to all repos**
6. **If you fix ANYTHING, update the instructions to reflect the fix, then push EVERYWHERE**

---

## ?? CRITICAL RULES

1. **NEVER delete `build/cache/bootstrap/`** ??? this is the SECONDARY working copy of the master chroot. The PRIMARY vaults are `cache/bootstrap/` (source-root) and `iso-output/*.iso`
2. **ALWAYS push to ALL 3 git remotes** after any commit
3. **We use Trixie, NOT Bookworm**
4. **DNS order: 9.9.9.9 ? 8.8.8.8 ? 1.1.1.1** (Quad9 primary)
5. **Use Docker bridge** for root-owned file operations
6. **Exactly 1,335 sacred biblical hooks** ??? never more, never less
7. **NO `splash plymouth.enable=1`** in bootloaders ??? causes GPU freeze on Intel/AMD
8. **Server has 16 CPU cores** ??? use `/16` for load calculations, not `/12`
9. **"Permission denied" ? deleted** ??? `build/` is root-owned. ALWAYS use Docker exec to check, NEVER assume missing
10. **UE5 is ONLY in the bootstrap** at `build/cache/bootstrap/opt/unreal-engine/` (142GB). The separate source copy was deleted. NEVER delete this.
11. **ALWAYS use Docker for ALL filesystem checks on `build/`** ??? gositeme cannot read root-owned dirs
12. **After ANY fix or change, update these instructions and push to ALL 4 remotes + desktop copies**
13. **NEVER launch a build without confirming stats are streaming to the Dell Watch dashboard (`dell-watch.php`). Ensure `build/chroot/` and `build/.build/` are CLEAN (except cache) before launching to avoid instant `exit=127` failures.**
14. **ROOT AND ALFRED/ALFRED ONLY.** NO `gositeme` user involvement with Alfred. EVER. You are strictly forbidden from using `gositeme` for any Alfred operations. Everything runs as ROOT.
15. **THE UID 1004 CACHE GHOST.** The 165GB master `build/cache/bootstrap` has history. It was originally extracted by a non-root user (UID 1004). Because `cp -al` maintains inodes, this UID 1004 ghost ownership leaked into the active chroot and broke `sudo` execution. **ALWAYS verify the master cache is 100% owned by `root:root`** using `chown -R root:root build/cache/bootstrap` via Docker before building.

---

## ?? AUTO-DEPLOY CHECKLIST (MANDATORY ??? DO NOT WAIT TO BE ASKED)

After **ANY** code, config, website, or build change ??? do ALL of these **automatically**:

1. **Git (GoForge):** `git add -A && git commit -m '<msg>' && git push origin main && git push local-bare main && git push ssh-forge main`
2. **Git (GitHub):** If source changed, re-export to `/tmp/alfredlinux-github` and `git push -f github main`
3. **Website pages:** If a feature was added/changed, update `/docs.php` AND the relevant feature page
4. **Nav menu:** If a new page was created, add it to `/includes/nav.php`
5. **Download buttons:** If a downloadable asset was added, add download button to relevant pages
6. **Dashboard:** If build state changed, update `lb-docker-build.log` so `dell-watch.php` shows current status
7. **Website git:** `cd /home/gositeme/domains/alfredlinux.com/public_html && git add -A && git commit && git push origin master`

**NEVER leave:**
- Dirty files uncommitted
- Pages without nav links
- Features undocumented
- Dashboards showing stale data

---

## ??? UNIFIED BUILD

```bash
# Standard build (chroot + binary + ISO):
bash scripts/alfred-build.sh fast detach

# Watch logs:
tail -f /home/gositeme/alfred-lb-v-current.log
docker logs -f alfred-lb-v-current
```

All 10 legacy scripts archived in `scripts/archive/`.

---

## ?? KNOWN TRAPS & FIXES

### 1. includes.chroot_after_packages Duplication
**Fix:** Already patched in `lb-docker-inner-build.sh` lines 407-408:
```bash
rm -rf /work/build/config/includes.chroot_after_packages 2>/dev/null || true
```

### 2. 164GB Disk Space Explosion During `lb binary`
`lb_binary_chroot` does `cp -a cache/bootstrap chroot` which duplicates 164GB.
**Fix:** The source code in `live-build` contains leading tabs (`\t`), so a simple `sed 's/cp -a /cp -al /g'` misses it. The fix uses pipe delimiters and exact string matching:
```bash
sed -i 's|cp -a cache/bootstrap chroot|cp -al cache/bootstrap chroot|g' /usr/lib/live/build/* 2>/dev/null || true
sed -i 's|\tcp -a |\tcp -al |g' /usr/lib/live/build/* 2>/dev/null || true
sed -i 's|^cp -a |cp -al |g' /usr/lib/live/build/* 2>/dev/null || true
```
This guarantees it uses hardlinks (zero extra space).

### 3. Stale Lock File Blocking Build
```
[inner] waiting for exclusive build lock /work/build/.alfred-lb-docker-build.lock ...
```
**Fix:** `rm /home/gositeme/law/alfredlinux-com-source-live/build/.alfred-lb-docker-build.lock`

### 4. "chroot compressing a chroot" Problem
If `build/chroot.tmp/` exists after a crash, it's a ~164GB orphan copy left by `lb_binary_chroot`. Verify it's not the only copy, then delete with Docker:
```bash
docker run --rm -v /home/gositeme/law/alfredlinux-com-source-live:/work debian:trixie rm -rf /work/build/chroot.tmp
```

### 5. Orphan `chroot/` at Source Root
If `/home/gositeme/law/alfredlinux-com-source-live/chroot/` exists, it's from a non-Docker build. It's gitignored and safe to delete (via Docker for root-owned files).

### 5.5. The UID 1004 Ghost Cache Anomaly
**Symptom:** `exit=127` missing binaries during `lb chroot_install-packages` or massive permissions failures in `dell-watch.php`.
**Cause:** The original 165GB `build/cache/bootstrap` was unzipped by a regular user (UID 1004) instead of root. Because `lb_binary_chroot` was patched to use zero-space hardlinks (`cp -al`), this broken UID 1004 ownership perfectly duplicated itself into the live OS root filesystem, breaking `sudo` setuid and basic system security.
**Fix:** Run `docker run -i --rm --privileged -v /home/gositeme/law/alfredlinux-com-source-live:/work debian:trixie bash -c 'chown -R root:root /work/build/cache/bootstrap'` to brutally purge the UID 1004 ghost and restore pristine root authority to the master blueprint.

### 6. sed Targets on Trixie
Trixie uses `lb_` prefix for live-build scripts:
- `/usr/lib/live/build/lb_chroot_includes` (uses **tar**, not cp -a)
- `/usr/lib/live/build/lb_binary_includes` (uses **tar**, not cp -a)
- `/usr/lib/live/build/lb_binary_chroot` (uses **cp -a** ??? THIS is the real target)

### 7. Plymouth / GPU Freeze (FIXED)
**Never** use `splash plymouth.enable=1` in kernel boot params.
- ISOLINUX + GRUB-EFI: use `quiet nvidia-drm.modeset=1`
- GRUB-PC (fallback): use `nomodeset`
- `background_image /boot/grub/splash.png` in GRUB is SAFE (it's a wallpaper, not Plymouth)
- **`auto/config`** must NOT contain `splash plymouth.enable=1` ??? this was fixed on 2026-07-02

### 8. Missing `bash` in chroot (FIXED)
The inner build script rsyncs `cache/bootstrap/` ? `chroot/` then immediately tries to chroot.
If rsync hasn't finished, `chroot: failed to run command 'bash': No such file or directory`.
**Fix:** Run rsync **completely outside** the build container first. Wait for `chroot/usr/bin/bash` to exist, THEN start `alfred-build.sh`.

### 9. /tmp Permissions Crash During ISO Assembly
`lb binary_rootfs` sometimes crashes during the `lb chroot_archives binary remove` cleanup phase because `apt` tries to write to `/tmp` as the `_apt` user, but `/tmp` inside the chroot loses its `1777` global write permission. This aborts the build *after* `mksquashfs` finishes but *before* the `.build/binary_rootfs` stamp is created!
**Fix:** **DO NOT** restart the standard build scripts (it will wipe the 94GB squashfs!). Instead, manually run `chmod 1777 chroot/tmp`, touch the stamps (`touch .build/binary_rootfs .build/binary_chroot`), and run `lb binary` to safely resume from `binary_manifest` and generate the ISO.

### 10. OverlayFS "Device or resource busy" During `lb binary` (NEW ??? 2026-07-02)
The `inner-lb-binary-only-fast.sh` script uses overlayfs to merge `cache/bootstrap/` + `chroot-upper/` ? `chroot/`. When `lb binary` runs `lb_binary_chroot`, it tries `mv chroot chroot.tmp` which **fails** because the overlay mount is still active on `chroot/`.
```
mv: cannot move 'chroot' to 'chroot.tmp': Device or resource busy
```
**Fix:** The inner script MUST `umount` the overlay on `chroot/` BEFORE calling `lb binary`. Add before the `lb binary` call:
```bash
umount /work/build/chroot 2>/dev/null || true
```
Also check: `mount | grep chroot` ??? if any overlay/bind mounts exist, unmount them before retrying.

### 11. /tmp Exfiltration Vector (NEW ??? 2026-07-02)
Ubuntu/Debian `/tmp` is world-writable and commonly used for data staging attacks.
**Discovered:** A daily cron job (`law-trust-cast.sh` at 2AM) was creating a 330GB tar.gz archive in `/tmp`.
**Mitigations deployed:**
- Cron job disabled (commented out in crontab)
- **TMP Watchman** service deployed at `/home/gositeme/scripts/tmp-watchman.sh`
  - Scans `/tmp` every 30 seconds
  - Auto-deletes `.tar.gz`, `.zip`, `.7z`, `.rar`, `.sql`, `*backup*` files
  - Kills `tar` processes writing to `/tmp`
  - Writes alerts to `lb-docker-build.log` (visible on dell-watch)
  - Whitelists audio files (`.wav`, `.mp3`, `.flac` ??? for whisper/lavocat transcriber)
- **Start after reboot:** `nohup /home/gositeme/scripts/tmp-watchman.sh > /dev/null 2>&1 &`
- **Systemd unit** ready at `/tmp/tmp-watchman.service` ??? install with sudo when available

---

### 12. SSH Pipe Kills Build Containers (SIGPIPE)
When an AI agent runs  lfred-build.sh via SSH and pipes output through | head or captures
with backticks, the pipe closure sends SIGPIPE to the Docker container's stdout, killing it
instantly. The container exits cleanly (code 0) but the build never completes. --rm flag then
deletes the container, destroying all evidence.
**Fix:** ALWAYS launch builds inside a **tmux session** on the server:
` ash
tmux new-session -d -s alfred-build 'cd /home/gositeme/law/alfredlinux-com-source-live && bash scripts/alfred-build.sh fast 2>&1 | tee /home/gositeme/alfred-build-tmux.log'
`
- Tmux survives SSH disconnects, pipe closures, and connection resets
- The build log is tee'd to ~/alfred-build-tmux.log for monitoring
- Attach to check progress: 	mux attach -t alfred-build
- **NEVER** run the build script directly over SSH without tmux/screen

### 13. PM2 God Daemon Respawns Killed Processes
If you kill a process managed by PM2, PM2's God Daemon will respawn it within seconds.
Use pm2 stop <name> && pm2 delete <name> && pm2 save to permanently stop a PM2-managed process.
kill -9 alone will NOT work ??? PM2 will resurrect it.


### 14. `lb binary_rootfs` Fails: "Cannot stat source directory chroot"
The `inner-lb-binary-only-fast.sh` script uses an overlayfs to merge three layers:
- `cache/bootstrap` (165GB master ??? lower)
- `includes.chroot` + `includes.chroot_after_packages` (lower)
- `chroot-upper` (upper ??? 52MB of runtime changes)

When `lb binary` runs, it calls `mksquashfs chroot` using a **relative path**. If the overlay is unmounted before `lb binary`, `chroot/` becomes an empty mountpoint and mksquashfs fails.

**Fix (v2 build runner):** Skip `lb binary` for squashfs entirely. Run `mksquashfs` directly on the overlay-mounted chroot, then touch `.build/binary_rootfs` stamp so `lb binary` only handles ISO assembly.
See `/home/gositeme/run-build.sh` for the working implementation.

### 15. `lb binary_rootfs` Fails: "Unable to locate package squashfs-tools"
`lb binary_rootfs` tries to `apt-get install squashfs-tools` **inside the chroot**, but the chroot's apt sources may have been cleaned by previous `lb chroot_archives binary remove` runs. Exit code: 100.

**Fix:** Pre-install squashfs-tools in the chroot before running `lb binary`:
```bash
chroot /work/build/chroot apt-get update -qq
chroot /work/build/chroot apt-get install -y squashfs-tools
```
Or better: use the v2 build runner which installs squashfs-tools in the container and runs mksquashfs directly.

### 16. Build Runner Must Be SCP'd as a Script File (FIXED)
The dashboard wrapper (`scripts/alfred-build.sh`) now automatically encapsulates the V2 fast build runner (`run-build.sh`) inside `tmux` for resilience and fakes the telemetry output so `dell-watch.php` updates flawlessly. 
**Do not** bypass `alfred-build.sh` by calling `run-build.sh` directly, or you will break the web dashboard telemetry. Just use `bash scripts/alfred-build.sh fast detach`.

## ?? REBUILD CHECKLIST

Before starting a rebuild:

1. ? Check `apt-cacher-ng` is running: `docker ps | grep apt-cacher`
2. ? Remove stale build lock: `rm -f build/.alfred-lb-docker-build.lock`
3. ? Verify `build/cache/bootstrap/opt/` has all 20 apps
4. ? Verify `auto/config` says `--distribution trixie`
5. ? Verify `auto/config` does NOT contain `splash plymouth.enable=1`
6. ? Verify `config/kernel.env` says `ALFRED_KERNEL_VERSION="7.0.12"`
7. ? Check disk space: `df -h /` (need ~50GB free with hardlink patch)
8. ? Check no orphan `chroot/` at source root or `chroot.tmp` in build/
9. ? Verify sed targets are correct (`lb_binary_chroot` is the key one)
10. ? Check no stale overlay mounts: `mount | grep chroot` (must be empty)
11. ? Push all commits to all 3 remotes: `git push origin main && git push local-bare main && git push ssh-forge main`
12. ? Verify TMP Watchman is running: `ps aux | grep tmp-watchman`
13. ? Verify exactly 1,335 hooks: `ls config/hooks/live/*.hook.chroot | wc -l`

---

## ?? MONITORING

**Dashboard:** https://gositeme.com/veil/dell-watch.php
**Package browser:** https://alfredlinux.com/packages?lang=en

```bash
# Main build log
tail -f /home/gositeme/alfred-lb-v-current.log
# Container logs
docker logs -f alfred-lb-v-current
# Inner build log (inside Docker mount)
tail -f /home/gositeme/law/alfredlinux-com-source-live/build/lb-docker-build.log
```

---

## ?? COMPILED BUILDS ??? WHERE THEY LIVE

These are NOT in apt repos ??? they are compiled from source and baked into the master chroot.

### NVIDIA 610.43.02 Drivers ? BAKED IN
- **Pre-compiled modules:** `build/cache/bootstrap/Nvidia_7.0.12_Support/AlfredOS-7.0.12-Nvidia-Modules.tar.gz` (14MB)
- **Kernel patch:** `build/cache/bootstrap/Nvidia_7.0.12_Support/AlfredOS_Nvidia_7.0.12.patch`
- **Patcher script:** `build/cache/bootstrap/opt/patch_nvidia.py`
- **Bake hook:** `config/hooks/live/0089-alfred-nvidia-bake.hook.chroot`
- **KMS modesetting:** `nvidia-drm modeset=1` in hook 0089 + GRUB cmdline
- **Initramfs modules:** `nvidia nvidia_modeset nvidia_uvm nvidia_drm`
- **Key commits:** `ec214080`, `efdfc5ba`, `5fd6c97a`, `be12bcd6`, `3ccddcea`

### ZFS 2.4.3 (OpenZFS) ? BAKED IN ??? COMPILED AGAINST KERNEL 7.0.12
- **Kernel modules (custom-compiled ZFS 2.4.3):**
  - `build/cache/bootstrap/lib/modules/7.0.12/updates/dkms/zfs.ko` (11MB)
  - `build/cache/bootstrap/lib/modules/7.0.12/updates/dkms/spl.ko` (288KB)
- **Userspace tools (apt zfsutils-linux 2.3.2):**
  - `/usr/sbin/zfs`, `/usr/sbin/zpool`, `/usr/sbin/zdb`
  - `libzfs.so.6`, `libzfs_core.so.3`, `libzpool`
- **depmod registered:** `zfs.ko ? spl.ko` dependency chain ?
- **ZFS source:** `/usr/src/zfs-2.4.3` (retained for future recompile)
- **Bypass hooks:** `0001` (OMEGA BYPASS), `0025` (sed patches for configure)
- **DO NOT install `zfs-dkms`** ??? it will overwrite our compiled modules
- **Key commits:** `987783aa`, `910ffdff`, `1a27cd7d`

#### ?? ZFS COMPILATION BLUEPRINT (THE FORMULA)

**This took 40+ attempts to get right. DO NOT deviate from this recipe.**

1. **Use ZFS 2.4.3** (NOT 2.3.2 ??? 2.3.x only supports up to kernel 6.14)
2. **Kernel headers:** Install from local deb `linux-headers-7.0.12_7.0.12-1alfred_amd64.deb`
   - Headers are at `/usr/src/linux-headers-7.0.12/`
   - Symlinked from `/lib/modules/7.0.12/build`
3. **Patch `CONFIG_TRIM_UNUSED_KSYMS=n`** in TWO files:
   - `/usr/src/linux-headers-7.0.12/include/config/auto.conf`
   - `/usr/src/linux-headers-7.0.12/include/generated/autoconf.h` ? `/* #undef CONFIG_TRIM_UNUSED_KSYMS */`
4. **Add `lookup_bdev` to Module.symvers:**
   ```
   echo -e "0x00000000\tlookup_bdev\tvmlinux\tEXPORT_SYMBOL\t" >> /usr/src/linux-headers-7.0.12/Module.symvers
   ```
   (kernel 7.0.12 has lookup_bdev in header but doesn't export it)
5. **Patch `fs/block_dev.c` ? `fs/bdev.c`** in:
   - `config/kernel-blkdev.m4` (BEFORE autogen)
   - `configure` (AFTER autogen)
6. **Configure flags:**
   ```bash
   ./configure --prefix=/usr --sysconfdir=/etc --localstatedir=/var \
     --libdir=/usr/lib/x86_64-linux-gnu \
     --with-linux=/lib/modules/7.0.12/build \
     --with-linux-obj=/lib/modules/7.0.12/build \
     --with-config=all --enable-linux-experimental
   ```
7. **Build with:** `make -j$(nproc) KBUILD_MODPOST_WARN=1`
   - `KBUILD_MODPOST_WARN=1` is REQUIRED because Module.symvers is trimmed
   - The 143 "undefined" symbols DO exist in the real kernel, they're just
     not in the trimmed Module.symvers
8. **Install .ko manually:**
   ```bash
   cp module/zfs.ko module/spl.ko /lib/modules/7.0.12/updates/dkms/
   depmod -a 7.0.12
   ```
9. **Install userspace from apt:** `apt install zfsutils-linux zfs-zed`
10. **Remove zfs-dkms:** `dpkg --purge --force-all zfs-dkms`

### Kyber-1024 (Post-Quantum Cryptography)
- **Compiled at build time** by hooks ??? NOT pre-built
- **Hooks:** `0044-alfred-quantum`, `0051-alfred-fde`, `0064-alfred-quantum-pqc-compile`, `0145`, `0166`, `0186`, `0830`, `1335`
- **Scaffold:** `build/cache/bootstrap/opt/pq-crypto/build/cryptsetup/`
- **Key commits:** `4932d618`, `18429354`

---

## ?? GIT REMOTES ??? ALWAYS PUSH TO ALL FOUR

```bash
# All 4 remotes (3 internal + 1 GitHub public):
origin      ? http://127.0.0.1:3300/commander/alfredlinux.com.git  (Gitea/GoForge)
local-bare  ? file:///home/gositeme/goforge/data/gitea/repositories/commander/alfredlinux.com.git
ssh-forge   ? file:///home/gositeme/goforge/data/gitea/repositories/commander/alfredlinux.com.git

# GitHub (PUBLIC ??? separate export repo at /tmp/alfredlinux-github):
github      ? git@github.com:GoSiteMe-com/alfredlinux.git

# Push to all internal:
git push origin main && git push local-bare main && git push ssh-forge main

# Push to GitHub (from /tmp/alfredlinux-github):
cd /tmp/alfredlinux-github && git push github main

# Verify zero unpushed:
git log --oneline @{upstream}..HEAD
```

### ?? GitHub Export Notes
- GitHub repo lives at `/tmp/alfredlinux-github` (separate from golden source)
- GitHub repo was built via `git archive` from golden source ??? NOT a direct mirror
- Large files (>100MB) are excluded from GitHub export
- AGENT-INSTRUCTIONS.md is excluded from GitHub export
- The GitHub repo uses a SINGLE squashed initial commit (no history exposed)
- **URL:** https://github.com/GoSiteMe-com/alfredlinux
- **License:** AGPL-3.0-or-later (Hebrews 12:1 covenant header)
- **Cleaned:** 18 AI-agent junk files purged on 2026-07-02 (bash_parser, clean_hooks, bypass, etc.)

### ?? ACTION REQUIRED: Rotate Gitea Token
The internal `origin` remote URL contains the Gitea API token starting with `8e570c40c4`.
This token was visible in earlier git operations. **Rotate it** in GoForge ? Settings ? Applications.

---

## ?? DNS ORDER (resolv.conf in hooks)

```
nameserver 9.9.9.9    ? Quad9 (PRIMARY)
nameserver 8.8.8.8    ? Google
nameserver 1.1.1.1    ? Cloudflare
```

Configured in hooks `0001` and `1335`. CRITICAL_IPS in hooks `0959` and `0999` includes all three.
systemd-resolved (`DNS=9.9.9.9`, `FallbackDNS=9.9.9.9`) is set in hook `1335`.

---

## ? OPERATIONAL TIPS

1. **Root-owned files:** Use Docker bridge for any filesystem operations on build dirs:
   ```bash
   docker run --rm -v /home/gositeme/law/alfredlinux-com-source-live:/work debian:trixie <command>
   ```
2. **UE5 source:** Lives at `/home/gositeme/unreal-engine/` (77GB, 338K files). Hook `0023-alfred-unreal-engine.hook.chroot` hardlinks it into the chroot at build time via `-v /home/gositeme/unreal-engine:/ue-source:ro` Docker mount. The bootstrap also has its own copy in `build/cache/bootstrap/opt/unreal-engine/`.
3. **Slow `find` commands:** The 3.6TB filesystem takes 10+ minutes to scan. Use targeted paths, not broad `/home/gositeme` searches.
4. **SSH resets:** Long-running SSH commands may get `Connection reset`. **ALWAYS use tmux** for builds. SIGPIPE from SSH pipes kills Docker containers. Use: `tmux new-session -d -s alfred-build`
5. **GoForge/Gitea:** 38+ repos at `/home/gositeme/goforge/data/gitea/repositories/commander/`. Web UI at `127.0.0.1:3300`.
6. **`/tmp` is wiped on reboot:** Any scripts uploaded to `/tmp` (e.g. for Docker bridge) must be re-uploaded after a reboot. **This includes the GitHub export at `/tmp/alfredlinux-github`.**
7. **Post-reboot checklist:** Verify `apt-cacher-ng` container is running, Gitea responds on 3300, no stale build lock, no stale bind mounts (`mount | grep alfredlinux`), TMP Watchman running.
8. **Build dashboard:** https://gositeme.com/veil/dell-watch.php ??? reads from `lb-docker-build.log`.
9. **GoSiteMe dashboard:** https://gositeme.com/dashboard.php ??? has Launch Notes banner with GitHub link.
10. **Alfred Linux website:** https://alfredlinux.com ??? Hero section and Download section have GitHub buttons.
11. **Whisper transcriber:** A cron-driven `run_transcriptions.sh` runs at `/home/gositeme/transcriber/whisper.cpp/` for lavocat.ca legal work. The TMP Watchman whitelists its `.wav` temp files. This is NOT related to the build.
12. **Lavocat cron:** `*/30 * * * * php lavocat_db_cleanup.php` ??? legal DB maintenance, separate from build.

---

## ??? SECURITY ??? TMP WATCHMAN (v3)

**Script:** `/home/gositeme/scripts/tmp-watchman.sh`
**Log:** `/var/log/tmp-watchman.log`

> [!CAUTION]
> **2026-07-03 BUILD FAILURE CAUSED BY OLD WATCHMAN.** The v1 watchman killed `dpkg-deb --fsys-tarfile` processes mid-install (blender, clamav, rust) and deleted `liboqs-0.10.1.tar.gz` from `/tmp` during `lb build`. This caused exit=2. The watchman was rewritten to v3 which has ONE JOB.

| Feature | Detail |
|---------|--------|
| **ONE JOB** | Kill rogue `tar`/`zip`/`7z`/`rar` processes targeting `/home/gositeme/law` |
| **Scan interval** | Every 30 seconds |
| **NEVER touches** | `dpkg`, `apt`, `live-build`, `lb`, `git`, `npm`, `node`, `make`, `cargo`, `rustc`, `mksquashfs`, `pip`, `python` |
| **NEVER deletes** | Files in `/tmp` regardless of size |
| **NEVER kills** | Any build process ??? only rogue exfiltration of `/law` |
| **Start** | `nohup /home/gositeme/scripts/tmp-watchman.sh > /dev/null 2>&1 &` |
| **Safe during build** | YES ??? runs alongside `lb build` without interference |

**Disabled cron:** `law-trust-cast.sh` was creating 330GB daily tar.gz archives in `/tmp` at 2AM. Commented out on 2026-07-02.

---

## ?? DISK LAYOUT (as of 2026-07-03 ~09:37 EDT)

| Path | Size | Status |
|---|---|---|
| `/dev/md3` (root) | 3.6T total, **~1.1TB free** | **71% used** |
| `build/cache/bootstrap/` | 161GB | ? MASTER CHROOT ??? DO NOT DELETE |
| `build/cache/bootstrap/opt/unreal-engine/` | 142GB (incl 65GB .git) | ? ONLY UE COPY ??? DO NOT DELETE |
| `build/chroot/` | ~4KB | Empty dir (populated at build time) |
| `/home/gositeme/goforge/` | 473GB | Gitea data ??? DO NOT TOUCH |
| `/home/gositeme/.cache/` | ~21GB | Permanent caches ??? DO NOT DELETE |
| `config/hooks/live/` | 1,335 hooks | All intact, 0 syntax errors ? |
| `config/packages.chroot/` | Custom debs (16 entries) | All intact ? |
| `/tmp/alfredlinux-github/` | ~1.6GB | GitHub export repo (ephemeral ??? wiped on reboot) |
| `iso-output/` | 98GB | Previous ISO build (Jul 2) |
| `/home/gositeme/archive-upload/` | 26GB | UE compressed archive for archive.org |
| `.git/` | 147GB | Repo history (787MB LFS) |

---

## ?? ISO OUTPUT & FINALIZATION

### Where the ISO Lands
When `lb binary` finishes, the inner build script automatically:
1. Finds `build/live-image-amd64.hybrid.iso`
2. Moves it to `iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso`
3. Touches `iso-output/build-complete.marker` to signal completion

**Output directory:** `/home/gositeme/law/alfredlinux-com-source-live/iso-output/`

### Post-Build Finalization (run manually after build completes)

```bash
cd /home/gositeme/law/alfredlinux-com-source-live/iso-output/

# 1. Patch out "splash" from bootloader (removes Plymouth splash from GRUB)
python3 patch_iso.py

# 2. Generate all cryptographic hashes (parallel ??? one pass over 98GB)
bash generate_all.sh
# Outputs: md5.txt, sha1.txt, sha256.txt, sha512.txt, blake3.txt

# 3. Generate torrent file
transmission-create -o AlfredLinux-Alpha-Matrix-7.77-x86_64.iso.torrent \
  -t udp://tracker.opentrackr.org:1337/announce \
  -t udp://tracker.openbittorrent.com:6969/announce \
  AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
```

### ISO Output Files

| File | Purpose |
|---|---|
| `AlfredLinux-Alpha-Matrix-7.77-x86_64.iso` | The final bootable ISO (~98GB) |
| `build-complete.marker` | Signals build completion |
| `md5.txt` | MD5 hash |
| `sha1.txt` | SHA-1 hash |
| `sha256.txt` | SHA-256 hash |
| `sha512.txt` | SHA-512 hash |
| `blake3.txt` | BLAKE3 hash (via `b3sum` at `~/.cargo/bin/b3sum`) |
| `*.iso.torrent` | BitTorrent distribution file |
| `patch_iso.py` | Binary-patches ISO to remove "splash" from GRUB cmdline |
| `generate_all.sh` | Generates all 5 hashes in one streaming pass |

---

## ?? CURRENT STATE (as of 2026-07-03 ~11:14 EDT)

- **Master chroot:** `build/cache/bootstrap/` ??? ~165GB, 22 custom /opt apps ?
- **UE5:** ONLY copy at `bootstrap/opt/unreal-engine/` (142GB incl 65GB .git). Separate source DELETED ?
- **Installed packages:** **1,148** total dpkg packages ?
- **3D/Creative:** Blender 4.3.2 + GIMP + Kdenlive + OBS + Audacity ?
- **Languages:** Rust 1.85.0 + Cargo + Java OpenJDK 21 + Go + Python 3.13 + Node 20.19 ?
- **Node.js:** v20.19.2 + npm 9.2.0 + git-lfs 3.6.1 ?
- **Network tools:** traceroute + ncat + iptables + nftables ?
- **Forensic tools:** exiftool + mc (midnight commander) ?
- **IDS/IPS:** Suricata 7.0.10 (network IDS/IPS, JA3/JA4, Hyperscan, LuaJIT) ?
- **Rootkit detection:** chkrootkit + rkhunter 1.4.6 (dual-layer) ?
- **Brute-force prevention:** fail2ban ?
- **File integrity:** AIDE (file integrity monitoring) ?
- **Antivirus:** ClamAV + freshclam (auto-updating signatures) ?
- **Security auditor:** Lynis 3.1.4 (automated security audit, timer enabled) ?
- **Mandatory access control:** AppArmor 4.1.0 + profiles + profiles-extra + utils ?
- **Application sandboxing:** Firejail 0.9.74 + profiles ?
- **Military tools:** aircrack, nmap, hashcat, autopsy, bettercap, john, sqlmap, tshark, tcpdump, binwalk ??? 10/10 ?
- **Pentest tools:** wireshark + hydra + nikto + gobuster (baked into bootstrap) ?
- **NVIDIA 610:** Source tarball 14MB + patch baked into `/opt/nvidia-7.0.12/`, KMS modesetting enabled ?
- **Ollama:** Binary baked into `/usr/local/bin/ollama` (37MB) ?
- **PQ-cryptsetup:** Full liboqs + cryptsetup source 373MB baked into `/opt/pq-cryptsetup/` ?
- **liboqs source:** `/tmp/liboqs-0.10.1.tar.gz` (4.1MB) restored ?
- **Plymouth:** Alfred theme, `plymouthd.conf` set (`Theme=alfred`), splash REMOVED from auto/config ?
- **Bootloaders:** 3 modes (Commander/Practical/Cloud), `nvidia-drm.modeset=1`, LSM stack, NO splash ?
- **TOMOYO LSM:** `tomoyo-tools` in hardening list + custom .deb in packages.chroot ?
- **LSM stack:** `lsm=landlock,lockdown,yama,integrity,apparmor,tomoyo` in both GRUB + ISOLINUX ?
- **DNS:** Quad9 primary (9.9.9.9 ? 8.8.8.8) ?
- **ZFS 2.4.3:** `zfs.ko` 11MB + `spl.ko` 288KB + `xtrx.ko` 28KB, userspace `zfs/zpool/zdb` ?
- **Kernel 7.0.12:** vmlinuz 17MB + initrd 98MB, 41 modules, modules.dep valid ?
- **Hooks:** Exactly 1,335 sacred biblical hooks, **0 syntax errors** (100-fold audit passed) ?
- **Hook 0024:** Syntax fixed (satdump block + wget if/then) ?
- **dpkg:** 0 broken, 0 half-installed, 0 unpacked packages ?
- **start-stop-daemon:** RESTORED to real binary (was symlinked to /bin/true) ?
- **apt cache:** CLEANED (3.6GB ? 240KB saved) ?
- **hostname:** `alfredlinux` ?
- **machine-id:** empty ?
- **/tmp perms:** 1777 ?
- **/etc/hosts:** 127.0.0.1 localhost, 127.0.1.1 alfredlinux ?
- **/etc/default/grub:** `quiet nvidia-drm.modeset=1` ?
- **Disk:** ~1.1TB free (71%) ??? reclaimed 80.6GB (77GB UE source + 3.6GB apt cache) ?
- **Build script:** Unified `alfred-build.sh` (10 legacy scripts archived) ?
- **apt-cacher-ng:** Running healthy (45+ hours uptime) ?
- **TMP Watchman v3:** Active ??? ONE JOB: kill rogue /law exfil only. NEVER touches dpkg/build ?
- **Stale build lock:** DELETED ?
- **Stale containers:** PRUNED (334MB reclaimed) ?
- **VR/Spatial:** ALVR v20.14.1 + Godot icons present ?
- **Calamares installer:** Present in bootstrap ?
- **PQ-Crypto:** Scaffold in /opt/pq-crypto/build, payload in includes.chroot/tmp/ ?
- **Git (ALL 4 remotes):** origin + local-bare + ssh-forge + github ??? 0 unpushed ?
- **100-fold audit:** 21/21 checks passed, BUILD READINESS 100% ?
- **Credentials:** No real secrets in repo (push-notifications.php is runtime key construction) ?
- **ACTION REQUIRED:** Rotate Gitea token (starts with `8e570c40c4`)
- **OPTIONAL:** Delete 65GB `.git` from `bootstrap/opt/unreal-engine/.git` to save space
- **KNOWN ISSUE:** `inner-lb-binary-only-fast.sh` overlayfs must be unmounted before `lb binary` ??? see Trap #10

---

## ?? TRAPS #17???22: Pre-Flight Fixes for `lb binary` (CRITICAL)

These were discovered during the 2026-07-02 ~21:50 EDT deep audit and MUST be applied
before `lb binary` runs, or the ISO will NOT boot.

### Trap #17: Kernel Filename Mismatch
**Symptom:** ISO boots to GRUB ? blank screen / "file not found"
**Cause:** GRUB/ISOLINUX configs reference `/live/vmlinuz` and `/live/initrd.img`,
but the V2 runner copies files as `vmlinuz-7.0.12` and `initrd.img-7.0.12`.
**Fix:**
```bash
docker exec CONTAINER bash -c '
  cd /work/build/binary/live
  ln -sf vmlinuz-7.0.12 vmlinuz
  ln -sf initrd.img-7.0.12 initrd.img
'
```

### Trap #18: `binary_rootfs` Stamp Missing
**Symptom:** `lb binary` re-runs mksquashfs from scratch (wastes hours, may corrupt)
**Cause:** The V2 runner does mksquashfs outside of lb, so lb doesn't know it's done.
**Fix:**
```bash
docker exec CONTAINER touch /work/build/.build/binary_rootfs
```

### Trap #19: Missing Boot Binaries
**Symptom:** ISO won't boot on BIOS or UEFI machines
**Cause:** `isolinux.bin`, `vesamenu.c32`, `bios.img`, EFI images not auto-created by V2 runner.
**Fix:**
```bash
docker exec CONTAINER bash -c '
  # ISOLINUX
  cp /usr/lib/ISOLINUX/isolinux.bin /work/build/binary/isolinux/
  cp /usr/lib/syslinux/modules/bios/vesamenu.c32 /work/build/binary/isolinux/
  cp /usr/lib/syslinux/modules/bios/menu.c32 /work/build/binary/isolinux/

  # GRUB BIOS
  grub-mkimage -O i386-pc -o /work/build/binary/boot/grub/bios.img \
    -p /boot/grub biosdisk iso9660 part_msdos fat normal boot linux chain \
    configfile loopback search search_fs_uuid search_fs_file search_label \
    test all_video gfxterm echo true

  # EFI
  mkdir -p /work/build/binary/EFI/boot
  cp /usr/lib/shim/shimx64.efi.signed /work/build/binary/EFI/boot/bootx64.efi
  grub-mkimage -O x86_64-efi -o /work/build/binary/EFI/boot/grubx64.efi \
    -p /boot/grub part_gpt part_msdos fat ext2 normal chain boot linux \
    loopback iso9660 search search_label search_fs_uuid search_fs_file \
    gfxterm gfxterm_background gfxterm_menu test all_video loadenv exfat \
    configfile echo true keystatus

  # EFI partition image (for xorriso)
  dd if=/dev/zero of=/work/build/binary/boot/grub/efi.img bs=1M count=8
  mkfs.vfat /work/build/binary/boot/grub/efi.img
  mmd -i /work/build/binary/boot/grub/efi.img ::EFI ::EFI/boot
  mcopy -i /work/build/binary/boot/grub/efi.img \
    /work/build/binary/EFI/boot/bootx64.efi ::EFI/boot/bootx64.efi
  mcopy -i /work/build/binary/boot/grub/efi.img \
    /work/build/binary/EFI/boot/grubx64.efi ::EFI/boot/grubx64.efi
'
```

### Trap #20: Missing GRUB Font
**Symptom:** GRUB menu text invisible / garbled
**Cause:** `unicode.pf2` not copied to `binary/boot/grub/`
**Fix:**
```bash
docker exec CONTAINER cp /usr/share/grub/unicode.pf2 /work/build/binary/boot/grub/
```

### Trap #21: `filesystem.size` Empty
**Symptom:** Some installers/live-boot checks may fail
**Cause:** V2 runner doesn't generate this file
**Fix:**
```bash
docker exec CONTAINER bash -c 'echo 177156456448 > /work/build/binary/live/filesystem.size'
```

### Trap #22: `filesystem.packages` Incomplete
**Symptom:** Package manifest shows only ~188 entries instead of 4,497+
**Cause:** `dpkg-query` runs against base chroot, not the full overlay
**Note:** Non-fatal ??? the ISO will still boot and work. The manifest is only used by
installers like Calamares and for metadata purposes. The full package set is inside the squashfs.

---

## ?? Post-Build ISO Finalization

The V2 runner creates the squashfs and runs `lb binary`. After that, the automated
`watch-and-finalize.sh` (running in tmux session `iso-watcher`) detects `build-complete.marker`
and runs `scripts/finalize-iso.sh` which does:

1. Verify filesystem.squashfs integrity
2. Prep binary tree (kernel + initrd)
3. Create ISO with xorriso
4. Generate ALL 5 hashes: MD5, SHA1, SHA256, SHA512, BLAKE3
5. Create torrent + magnet URI (WebTorrent trackers included)
6. GPG sign ISO + hashes
7. Deploy everything to `/home/gositeme/domains/alfredlinux.com/public_html/downloads/`
8. Update `ga-release-state.php` with new BTIH

To go live: set `$gaDownloadOfferLive = true` in `includes/ga-release-state.php`.


### Trap #23: `/etc/hosts` Missing in Chroot
**Symptom:** `sudo` hangs for 30 seconds on every command; hostname resolution fails
**Cause:** lb hooks or overlay may not carry over `/etc/hosts`
**Fix:**
```bash
cat > /work/build/cache/bootstrap/etc/hosts << 'EOF'
127.0.0.1	localhost
127.0.1.1	alfredlinux
::1		localhost ip6-localhost ip6-loopback
ff02::1		ip6-allnodes
ff02::2		ip6-allrouters
EOF
```

### Trap #24: `machine-id` Hardcoded from Build Server
**Symptom:** Every boot gets the same D-Bus machine ID ??? breaks Bluetooth, logs, systemd uniqueness
**Cause:** Build server's machine-id leaks into chroot
**Fix:**
```bash
echo -n > /work/build/cache/bootstrap/etc/machine-id
```

### Trap #25: `/tmp` Permissions Wrong (775 instead of 1777)
**Symptom:** apt, Firefox, many apps fail to write temp files
**Cause:** Overlay or bootstrap creation drops the sticky bit
**Fix:**
```bash
chmod 1777 /work/build/cache/bootstrap/tmp
```

### Trap #26: Hostname Leaks Build Server Name
**Symptom:** `hostname` shows `server-15-235-50-60` instead of `alfredlinux`
**Cause:** Build server hostname copied into chroot
**Fix:**
```bash
echo "alfredlinux" > /work/build/cache/bootstrap/etc/hostname
```

### Trap #27: Plymouth Theme Set to Debian Default
**Symptom:** Boot animation shows Debian "ceratopsian" theme instead of Alfred penguin
**Cause:** `/etc/plymouth/plymouthd.conf` says `Theme=ceratopsian`
**Fix:**
```bash
cat > /work/build/cache/bootstrap/etc/plymouth/plymouthd.conf << 'EOF'
[Daemon]
Theme=alfred
ShowDelay=0
EOF
```

### Trap #28: `/etc/default/grub` Missing
**Symptom:** Calamares installer can't configure GRUB during install
**Cause:** Never created during bootstrap
**Fix:**
```bash
cat > /work/build/cache/bootstrap/etc/default/grub << 'EOF'
GRUB_DEFAULT=0
GRUB_TIMEOUT=15
GRUB_DISTRIBUTOR="Alfred Linux"
GRUB_CMDLINE_LINUX_DEFAULT="quiet nvidia-drm.modeset=1"
GRUB_CMDLINE_LINUX=""
EOF
```

### Trap #29: Build Artifacts Left in `/root/`
**Symptom:** 30MB of old kernel .debs, wget history, empty .ssh dir shipped to users
**Cause:** Build process leaves artifacts in chroot's /root/
**Fix:**
```bash
rm -rf /work/build/cache/bootstrap/root/.wget-hsts
rm -rf /work/build/cache/bootstrap/root/.cache/*
rm -rf /work/build/cache/bootstrap/root/.ssh
rm -rf /work/build/cache/bootstrap/root/custom-packages
rm -rf /work/build/cache/bootstrap/root/masterstroke_kernel
```

### Trap #30: Overlay Fixes Must Target BOTH Layers
**Symptom:** Fixes to `cache/bootstrap` don't show through overlay if mksquashfs already read those inodes
**Cause:** OverlayFS caches negative lookups; mksquashfs reads files by inode order
**Fix:** Write fixes to BOTH `cache/bootstrap` AND `chroot-upper` directory to ensure visibility.
For runtime fixes, `live-config` handles hostname, locale, timezone, user creation at every boot.

### Trap #31: Boot Params Missing from GRUB/ISOLINUX
**Symptom:** No user created at boot, no NVIDIA modesetting, no LSM stack
**Cause:** V2 runner copies static bootloader templates from `config/bootloaders/` ??? these don't contain the params from `auto/config --bootappend-live`.
**Fix:** Patch `binary/boot/grub/grub.cfg` and `binary/isolinux/live.cfg` AFTER copying templates to include: `username=alfred user-default-groups=audio,video,render,dialout,sudo,adm,netdev,plugdev quiet nvidia-drm.modeset=1 lsm=lockdown,integrity,tomoyo,apparmor,yama,bpf,landlock`

### Trap #32: dpkg-divert crashes during lb chroot (The .distrib Bug)
**Symptom:** `dpkg-divert: error: rename involves overwriting '/usr/sbin/policy-rc.d.distrib' with different file '/usr/sbin/policy-rc.d', not allowed`
**Cause:** If a previous build crashed, stranded `.distrib` files (like `policy-rc.d.distrib`, `start-stop-daemon.distrib`, `flash-kernel.distrib`, `hostname.distrib`) are left in `cache/bootstrap/`. When `live-build` runs `lb chroot_sysv-rc` or `lb chroot_dpkg`, it tries to create the diversion again, fails to overwrite the existing `.distrib` file, and crashes the entire build.
**Fix:** The inner script `lb-docker-inner-build.sh` MUST explicitly wipe all `.distrib` files from `cache/bootstrap/` and `build/chroot/` BEFORE `lb bootstrap` or `lb chroot` runs.


### Trap #32: Hooks Can't Find Host-Staged Assets
**Symptom:** Ollama, liboqs, NVIDIA userspace ??? all present on host but hooks fail to find them
**Cause:** Hooks run inside chroot. Docker mounts source tree at `/work` but chroot overlay
can't see `/work/build-assets/`. Hook 0073 looks for `/build-assets/ollama` INSIDE chroot.
**Fix:** Stage assets into `config/includes.chroot/` so the overlay puts them where hooks expect:
```bash
# Ollama (559MB binary)
cp build-assets/ollama config/includes.chroot/build-assets/ollama

# PQ-Crypto (liboqs + oqs-provider)
mkdir -p config/includes.chroot/tmp/alfred-quantum-payload/
tar czf config/includes.chroot/tmp/alfred-quantum-payload/liboqs-0.10.1.tar.gz -C /path/to/pq-cryptsetup liboqs/
cp oqs-provider-0.6.1.tar.gz config/includes.chroot/tmp/alfred-quantum-payload/

# NVIDIA modules
mkdir -p config/includes.chroot/lib/modules/7.0.12/updates/dkms/
tar xzf Nvidia_7.0.12_Support/AlfredOS-7.0.12-Nvidia-Modules.tar.gz \
    -C config/includes.chroot/lib/modules/7.0.12/updates/dkms/
```

### Trap #33: `lb binary` Fails with Code 100 (The 4GB ISO Limit)
**Symptom:** Squashfs builds fine but `lb binary` exits 100 due to the 4GB ISO file size limit trap, crashing the bootloader staging process halfway through.
**Cause:** The 4GB ISO 9660 limit crashes `lb binary` precisely while it is staging the `binary/isolinux/` boot files. If you run `xorriso` immediately, the ISO will fail to boot with "Failed to load ldlinux.c32" because the staging directory is missing the core boot files and the MBR version will mismatch.
**Fix:** Before running `xorriso`, you MUST install `syslinux-common` and `isolinux` natively on the host, forcefully inject the `.c32` boot files into the staging directory, and point the MBR to the exact matching `isohdpfx.bin` file.

```bash
# 1. Install perfectly matching bootloader files on the host
sudo apt-get update -qq && sudo apt-get install -y xorriso isolinux syslinux-common

# 2. Forcefully inject the missing boot files into the ISO staging folder
sudo cp /usr/lib/syslinux/modules/bios/ldlinux.c32 /usr/lib/syslinux/modules/bios/libcom32.c32 /usr/lib/syslinux/modules/bios/libutil.c32 /usr/lib/syslinux/modules/bios/vesamenu.c32 /usr/lib/syslinux/modules/bios/menu.c32 build/binary/isolinux/
sudo cp /usr/lib/ISOLINUX/isohdpfx.bin /usr/lib/ISOLINUX/isolinux.bin build/binary/isolinux/

# 3. Assemble the ISO natively using the perfectly matched MBR
cd build
xorriso -as mkisofs \
  -iso-level 3 \
  -o AlfredLinux-Alpha-Matrix-7.77-x86_64.iso \
  -isohybrid-mbr binary/isolinux/isohdpfx.bin \
  -c isolinux/boot.cat \
  -b isolinux/isolinux.bin -no-emul-boot -boot-load-size 4 -boot-info-table \
  -eltorito-alt-boot -e boot/grub/efi.img -no-emul-boot \
  -isohybrid-gpt-basdat \
  binary/
```


### Trap #34: GitHub Rejects Files >100MB
**Symptom:** `git push github main` fails with `GH001: Large files detected`
**Cause:** GitHub enforces a 100MB per-file limit. Ollama (559MB) and liboqs (228MB) exceed this.
**Fix:** `.gitignore` the large binaries. They stay on disk for the build and in GoForge (no limit).
GoForge is `origin` so the build server always has everything.
```bash
echo 'config/includes.chroot/build-assets/ollama' >> .gitignore
echo 'config/includes.chroot/tmp/alfred-quantum-payload/liboqs-0.10.1.tar.gz' >> .gitignore
```

### Trap #35: `build/binary/` Owned by Root After Docker Build
**Symptom:** `sed: couldn't open temporary file: Permission denied` when patching grub.cfg
**Cause:** Docker containers run as root. Files in `build/binary/` are owned by `root:root`.
**Fix:** Use `docker run --rm -v ... debian:trixie bash` to run post-build modifications,
or `sudo chown -R gositeme:gositeme build/binary/`.

### Trap #36: GRUB Boot Params ??? Match Both `quiet` and `nomodeset`
**Symptom:** `sed` patterns for `nomodeset` don't match because `lb binary` generates `quiet` instead
**Cause:** `lb config --bootappend-live` was set but `lb binary` may use different defaults
depending on which boot templates it copies.
**Fix:** Pattern-match both endings:
```bash
sed -i "s|components nomodeset|components $EXTRA|g" grub.cfg
sed -i "s|components quiet$|components $EXTRA|g" grub.cfg
```

### Trap #37 ??? wireguard-dkms does not exist in Trixie
WireGuard is built into kernel 7.0.12. The `wireguard-dkms` package was removed
from Debian Trixie because the kernel module ships natively. Use `wireguard-tools`
instead for `wg`, `wg-quick`, and userspace utilities.
```
# WRONG ??? will cause exit=123
apt-get install -y wireguard-dkms
# CORRECT
apt-get install -y wireguard-tools
```

### Trap #38 ??? postgresql-client version must match Trixie
Debian Trixie ships PostgreSQL 17, not 18. Any reference to `postgresql-client-18`
or `postgresql-18` must be changed to `postgresql-client-17` / `postgresql-17`.
```
# WRONG
apt-get install -y postgresql-client-18
# CORRECT
apt-get install -y postgresql-client-17
```

### Trap #39 ??? Package lists (.list.chroot) are STRICT ??? no || true
Unlike hooks, `.list.chroot` files in `config/package-lists/` are processed by
`lb build` with zero tolerance. If ANY package in a list file does not exist in
the Trixie repos, the ENTIRE build fails with exit=123. There is NO `|| true`
mechanism for package lists. Every single package name must be validated against
`apt-cache show <pkg>` before adding it to a list file.

### Trap #40 ??? 20 packages that DO NOT EXIST in Debian Trixie
The following were removed from package lists after causing exit=123:
```
# alfred-b2.list.chroot
fastfetch            # Not in Trixie repos (install via hook with || true)
usr-is-merged        # Transitional, already done in Trixie

# alfred.list.chroot
podman-compose       # Not in Trixie (use docker-compose)
xmrig                # Not in any Debian repo
firmware-iwlwifi     # Moved to non-free-firmware component
firmware-atheros     # Moved to non-free-firmware component
firmware-brcm80211   # Moved to non-free-firmware component
bettercap-ui         # Not in Debian repos
satdump              # Not in Trixie
linux-image-7.0.12   # Custom kernel ??? install via hooks only
linux-headers-7.0.12 # Custom kernel ??? install via hooks only

# desktop.list.chroot
-plymouth            # Minus-prefix is dpkg syntax, not lb syntax
-plymouth-themes     # Same
-plymouth-x11        # Same

# level-000-networking-core.list.chroot
systemd-resolved     # Part of systemd package in Trixie, not separate

# level-010-base.list.chroot
qemu-kvm             # Transitional in Trixie (use qemu-system-x86)

# ubuntu-god-tier.list.chroot
eza                  # Rust tool, not in Trixie (install via hook)
rocm-opencl-icd      # AMD ROCm, not in Trixie

# ubuntu-super-amenities.list.chroot
pipewire-audio       # Not a real package (just use pipewire)
gcc-13               # Trixie ships gcc-14 by default
gcc-14               # Already default, no versioned package needed
```

### Trap #41 ??? Boot config SOURCES must be patched, not just binary/
Every `lb build` regenerates `binary/boot/` from `config/bootloaders/`.
Patching `binary/boot/grub/grub.cfg` or `binary/isolinux/live.cfg` directly
is useless ??? it gets overwritten on the next build. Always patch:
- `config/bootloaders/grub-pc/grub.cfg` (GRUB source)
- `config/bootloaders/isolinux/live.cfg` (ISOLINUX source)

Required boot params: `username=alfred nvidia-drm.modeset=1 lsm=landlock,lockdown,yama,integrity,apparmor,tomoyo`

### Trap #42 ??? Always validate packages before adding to lists
Before adding ANY package to a `.list.chroot` file, run:
```bash
apt-cache show <package-name> >/dev/null 2>&1 && echo OK || echo BAD
```
If it returns BAD, either:
1. Remove it entirely, or
2. Move the install to a hook with `|| true` so it doesn't kill the build

### Trap #43 ??? Multi-package lines in .list.chroot
Package list files expect ONE package per line. Putting multiple packages on
one line (e.g., `libvirt-daemon-system libvirt-clients qemu-kvm`) causes lb
to treat the entire line as a single package name, which always fails.

### Trap #44 ??? ALWAYS deliver the .md artifact to the user
Every time AGENT-INSTRUCTIONS.md is updated, you MUST:
1. Bump the version in the title (e.g., v13.2 ? v13.3)
2. Commit and push to ALL 3 remotes (local, origin/GoForge, github)
3. Download a copy and save it as an artifact named AGENT-INSTRUCTIONS-vX.Y.md
4. Present the artifact to the user so they can review every line

Never skip step 3-4. The user must always receive the rendered .md file.

### Trap #45 ??? build/config/ is a COPY, not the source
`lb build` copies `config/` into `build/config/` during setup. After that, it reads
from `build/config/`, NOT from `config/`. Fixing files in `config/` has NO EFFECT
if `build/config/` still has stale copies. You MUST delete `build/config/` (requires
root/Docker since files are root-owned) before rebuilding.

### Trap #46 ??? The 99-dummies.list.chroot ghost file
A file `build/config/package-lists/99-dummies.list.chroot` was found containing:
```
wireguard-dkms
postgresql-client-18
```
This file does NOT exist in source `config/package-lists/`. It was created by a
previous build session (likely manually or by a hook) and persisted in the
root-owned `build/` directory across all subsequent builds. It caused exit=123
on every single build until discovered.

**Always check `build/config/package-lists/` for ghost files after a failed build.**

### Trap #47 ??? .build/ stage markers are ROOT-owned
The stage markers in `build/.build/` (e.g., `chroot_package-lists.live`,
`chroot_install-packages.install`) are created inside Docker as root.
You CANNOT delete them as a normal user. Use a privileged Docker container:
```bash
docker run --rm --privileged -v /path/to/repo:/work debian:trixie \
  bash -c 'rm -rf /work/build/.build /work/build/chroot /work/build/binary /work/build/config'
```

### Trap #48 ??? lb build "already done" skips hide real errors
When `lb build` sees a `.build/` marker, it prints `W: Skipping <stage>, already done`
and moves on. If a previous build generated a bad package queue and the marker
exists, lb will skip regenerating the queue and try to install the stale list.
This causes phantom errors from packages that no longer exist in source config.

**After ANY build failure: delete ALL of `build/.build/`, `build/config/`,
`build/chroot/`, and `build/binary/` using Docker before retrying.**

### Trap #49 ??? The "live pass" vs "install pass"
`lb build` processes package lists in TWO passes:
1. **install pass** (`chroot_install-packages.install`) ??? base system packages
2. **live pass** (`chroot_install-packages.live`) ??? live-specific packages

Both read from `build/config/package-lists/`. If one pass is marked done but the
other isn't, the unmarked pass runs against potentially stale package queues.

### Trap #50 ??? tee fails inside container (symlink target outside mount)
The inner build script does `exec > >(tee "$LOG")` where LOG is `/work/lb-docker-build.log`.
If this file is a symlink to a path outside the container's mount (e.g., `/home/gositeme/alfred-runner.log`),
tee fails silently. The dashboard then shows stale data.

**Fix:** Ensure `lb-docker-build.log` is a real file, not a symlink, OR mount the
symlink target into the container.

### Trap #51 ??? NEVER create new hooks. ONLY modify existing ones.
The hook count is LOCKED at 1335. Creating a new hook file is FORBIDDEN.
If you need to add functionality, append it to an existing hook (e.g., 0024-alfred-mustard-seed.hook.chroot
or 1335-alfred-merged-auto.hook.chroot). This is a PERMANENT rule.

Any AI agent that creates a new .hook.chroot file has FAILED and must merge
it into an existing hook immediately.

### Trap #52 ??? NEVER use inline SSH commands. Use scripts.
PowerShell on Windows mangles `&&`, `||`, `$()`, and subshells inside SSH quotes.
This has wasted enormous credits on retries. The rule is:
1. Write the command to a `.sh` file
2. SCP it to the server (`scp file.sh gositeme:/tmp/file.sh`)
3. Execute it (`ssh gositeme "bash /tmp/file.sh"`)

NEVER run `ssh gositeme "complex && command || with subshells"`.

### Trap #53 ??? Hooks directory is filesystem-locked at 1335
`config/hooks/live/` has permissions `dr-xr-xr-x` (chmod 555).
- Creating new files: ? BLOCKED at kernel level
- Modifying existing files: ? Allowed
- To temporarily unlock for maintenance: `chmod 755 config/hooks/live/`
- Always re-lock after: `chmod 555 config/hooks/live/`

Note: `sed -i` creates temp files in the same directory, so you must temporarily
unlock the directory to use `sed -i` on hooks, then re-lock.

### Trap #54 ??? Run preflight check before EVERY build
Before starting `alfred-lb-docker-build.sh`, verify ALL of:
1. Hook count = 1335
2. No `build/config/package-lists/99-dummies.list.chroot` ghost file
3. No `build/.build/` stale stage markers
4. No `build/config/` stale copy
5. All packages in `.list.chroot` files valid (`apt-cache show`)
6. No `wireguard-dkms` or `postgresql-client-18` anywhere
7. All 3 git repos synced (local, GoForge, GitHub)
8. Key assets present (Ollama 559MB, NVIDIA 5 .ko, liboqs 228MB)
9. Boot configs patched (`username=alfred` in grub.cfg + live.cfg)
10. Sufficient disk space (>100GB)

If ANY check fails, DO NOT START THE BUILD.

### Trap #55 ??? Docker clean procedure for root-owned build state
The `build/` directory contains root-owned files created by Docker.
Normal `rm` fails with "Permission denied". Use:
```bash
docker run --rm --privileged \
  -v /home/gositeme/law/alfredlinux-com-source-live:/work \
  debian:trixie bash -c \
  'rm -rf /work/build/.build /work/build/config /work/build/chroot /work/build/binary'
```
NEVER use `sudo rm` (no sudo password configured).
NEVER skip this step between failed builds.

### Trap #56 ??? alfred-runner.log can contain stale data
Multiple `nohup` processes and `docker logs -f` pipes can write to the same log.
Always `truncate -s 0 /home/gositeme/alfred-runner.log` before starting a new build.
Check timestamps in the log to confirm you're reading the CURRENT build.

### Trap #57 ??? lb-docker-build.log is a symlink that breaks inside Docker
`/work/lb-docker-build.log` is a symlink to `/home/gositeme/alfred-runner.log`.
Inside Docker, only `/work` is mounted, so the symlink target doesn't exist.
`tee` fails silently. Dashboard reads stale data.
The inner script still outputs to stdout which gets captured by `nohup >>`.

### Trap #58 ??? Always deliver the .md artifact after EVERY update
After every AGENT-INSTRUCTIONS update:
1. Bump the version
2. Commit + push ALL 3 repos (local, GoForge, GitHub)
3. SCP the file to local machine as `AGENT-INSTRUCTIONS-vX.Y.md`
4. Present the artifact link to the user
NEVER skip this. The user MUST receive the rendered .md every time.

### Trap #59 ??? Never remove packages without asking the user
If a package in a `.list.chroot` file is invalid, present options to the user:
1. Remove it entirely
2. Move it to a hook with `|| true`
3. Replace with correct package name
NEVER silently delete packages from lists.

### Trap #60 ??? apt-cache on host ? apt-cache inside chroot
The host may not have `non-free-firmware` enabled, so `apt-cache show firmware-iwlwifi`
fails on the host. But inside the chroot, `non-free-firmware` IS enabled (via lb config).
Packages like `firmware-iwlwifi`, `firmware-atheros`, `firmware-brcm80211` ARE valid
in the chroot. Do not remove them from lists based on host-side apt-cache checks.

### Trap #61 ??? Kill and nuke before EVERY rebuild
Before restarting a build, ALWAYS:
```bash
docker stop alfred-lb-main-build; docker rm alfred-lb-main-build
docker run --rm --privileged -v /path/to/repo:/work debian:trixie \
  bash -c 'rm -rf /work/build/.build /work/build/config /work/build/chroot /work/build/binary /work/build/cache/stages'
truncate -s 0 /home/gositeme/alfred-runner.log
```
Then start the build. NEVER start a build on top of stale state.

### Trap #62 ??? Changes after build start are NOT picked up
`lb build` copies `config/` into `build/config/` during setup (Trap #45).
If you push changes to hooks or package lists AFTER the build starts, the
running build will NOT see them. You must kill + nuke + restart.

### Trap #63 ??? chmod 555 on hooks dir breaks sed -i
`sed -i` creates a temp file in the same directory. With chmod 555 on
`config/hooks/live/`, sed -i fails with "Permission denied".
Always: `chmod 755 dir/ ? edit ? chmod 555 dir/`

### Trap #64 ??? chmod 644 vs 755 on hooks
Hooks MUST be executable (chmod 755). The `chmod 555` on the directory
combined with `chmod 644` on files makes hooks non-executable and the
build silently skips them. Always use `chmod 755 *.hook.chroot`.

### Trap #65 ??? Deduplicate package lists
Packages appearing in multiple `.list.chroot` files waste build time.
Run this to check: `cat config/package-lists/*.list.chroot | grep -v '^#' | sort | uniq -d`
Keep each package in ONE list file only.

### Trap #66 ??? security-audit.sh false positives
The `non_comment_hits` function matches ANY non-comment line containing a pattern.
Lines like `if grep -rq 'PasswordAuthentication yes' /etc/ssh/` are CHECKS,
not setters. The fix: skip lines matching `grep.*<pattern>` in the warn loop.

### Trap #67 ??? Install missing packages from GitHub releases, not apt
Packages not in Debian repos (fastfetch, eza, xmrig, satdump, bettercap-ui)
must be installed from GitHub releases or pip inside hooks with `|| true`.
NEVER add them to `.list.chroot` files ??? they will crash the build.

### Trap #68 ??? Full compiler toolchain requirement
Alfred Linux is a compilation-focused distro. Always include:
gcc-12, gcc-13, gcc-14, g++-12, g++-13, g++-14, gfortran-12/13/14,
clang, llvm, lld, rustc, cargo, golang-go, cmake, ninja-build, meson,
autoconf, automake, libtool, pkg-config, nasm, yasm.
All with `|| true` in hooks.

### Trap #69 ??? Verify GitHub release URLs BEFORE the build
GitHub release URLs change or return 404 silently. Before every build:
```bash
curl -sL -o /dev/null -w '%{http_code}' "URL"
```
If any return non-200, fix the URL BEFORE starting the build. Do NOT rely
on `|| true` to silently skip ??? the user said "nothing misses."

### Trap #70 ??? Host apt-cache ? Chroot apt-cache (extended)
The build server host may not have `non-free-firmware` or certain component
repos enabled. Packages like `firmware-iwlwifi`, `postgresql-client-17` will
fail `apt-cache show` on the host but ARE valid inside the chroot.
Do NOT use host apt-cache results to judge chroot package availability.
Use `https://packages.debian.org/trixie/<package>` for definitive checks.

### Trap #71 ??? "Nothing misses" ??? kill and restart if ANY change comes after build start
If you push ANY fix after the build starts, you MUST kill + nuke + restart.
`lb build` copies `config/` into `build/config/` at startup. Changes to the
source `config/` are INVISIBLE to the running build. There is no hot-reload.
The user's directive: "nothing misses is that understood soldier."

### Trap #72 ??? Pre-download GitHub binaries for guaranteed availability
Packages downloaded from GitHub during hooks depend on network + URL validity.
For guaranteed inclusion, pre-download binaries into:
`config/includes.chroot/tmp/alfred-prebuilt/`
Then hooks install from local path instead of wget. This eliminates network dependency.

### Trap #73 ??? build/config/ has MORE hooks than source config/ (normal)
`lb build` auto-injects 2 internal hooks (e.g., `update-initramfs`) into
`build/config/hooks/live/` during setup. This means:
- Source `config/hooks/live/` = 1335 hooks (ours, locked)
- Build `build/config/hooks/live/` = 1337 hooks (1335 + 2 lb internal)
This is NORMAL. Do NOT restart the build because of it. Do NOT try to merge
or delete lb's internal hooks. They regenerate every build.

### Trap #74 ??? Forensic verification of running builds
After starting a build, verify the LIVE build state (not just source):
1. Check `build/config/` exists (lb has copied config/)
2. Check `build/config/hooks/live/0024*` has all expected content
3. Check `build/config/package-lists/` has no ghost files
4. Compare line counts: build hook must match source hook
5. Verify all hooks are executable (chmod 755)
This catches Trap #45 (stale copies) in real time.

### Trap #75 ??? security-audit.sh whitelist patterns
Legitimate http:// uses that should NOT trigger WARN:
`.onion`, `tor+http://`, `ppa.launchpad.net`, private IPs (100.64.x, 15.235.x),
`.kingdom` local domains, `freedesktop.org` DTDs, `gnu.org`/`example.com` check URLs,
`nullsoft.com`, `api.gositeme.com`, `gositemesovereign*`.
Legitimate eval uses: `eval "$(starship init bash)"`, `eval "$(zoxide init bash)"`.
These are excluded in the `non_comment_hits` loop via pattern matching.

### Trap #76 ??? actions/checkout version must match Node.js runtime
GitHub deprecated Node 20. Use actions/checkout@v5 (Node 24).

### Trap #77 ??? git index.lock from killed processes
If git add/commit is killed mid-operation, .git/index.lock remains. Fix: rm -f .git/index.lock

### Trap #78 ??? Unreal Engine is a 100GB/346K-file submodule
config/includes.chroot_after_packages/opt/unreal-engine is mode 160000 (submodule). NEVER git add it as regular files. Keep as submodule with .gitmodules.

### Trap #79 ??? security-audit.sh IPv6 bracket notation
http://[200:db8:...] Yggdrasil addresses need 200: exclusion, not just .onion.

### Trap #80 ??? PowerShell expands dollar-paren in SSH commands
ssh host "... dollar-paren(cmd) ..." expands locally in PowerShell. Use scripts (Trap #52).

### Trap #81 ??? dpkg-divert crash in bootstrap cache
If lb bootstrap is interrupted mid-dpkg-divert, the cache retains a corrupt
`start-stop-daemon.distrib`. Next build fails with:
  dpkg-divert: error: rename involves overwriting ... not allowed
Fix: nuke `build/cache/bootstrap`, `build/chroot`, `build/config`. Full wipe.

### Trap #82 ??? UE files leak into build/cache with root ownership
Because UE is in `config/includes.chroot_after_packages/opt/`, lb copies it
into `build/cache/bootstrap/opt/unreal-engine/` during bootstrap. These 346K
files get root ownership from the chroot. Regular `rm -rf` fails with
Permission denied. Must use `docker run --rm -v ...:/work debian:trixie rm -rf`
or `sudo rm -rf` to nuke.

### Trap #83 ??? tar --exclude must come BEFORE the source path
`tar -cf archive.tar.zst dir --exclude='.git'` silently ignores the exclude.
Correct: `tar --exclude='.git' -cf archive.tar.zst dir`

### Trap #84 ??? Build log appears stale but build died hours ago
The runner log stops updating when the build exits. Always check
`docker ps` to confirm the container is still running. If the container
is gone but the log shows old timestamps, the build crashed silently.
Check `docker logs` or the last lines of the runner log for exit codes.

### Trap #85 ??? NEVER nuke build/cache/bootstrap (MASTER CHROOT SAFETY)
`build/cache/bootstrap/` is the 165GB master chroot containing ALL compiled
builds (NVIDIA, ZFS, Kyber-1024), all 20 custom apps, the custom kernel,
and UE5. It takes DAYS to rebuild from scratch.
**If dpkg-divert is corrupt inside it, fix it IN PLACE:**
```bash
docker run --rm -v /path/to/repo:/work debian:trixie bash -c '
  rm -f /work/build/cache/bootstrap/sbin/start-stop-daemon.distrib
  dpkg-divert --root=/work/build/cache/bootstrap --remove --rename /sbin/start-stop-daemon
'
```
**NEVER run `rm -rf` on `build/cache/` ??? even `build/cache/stages` is safe to delete,
but `build/cache/bootstrap/` is SACRED.**
If it gets deleted, recovery is possible by unsquashing the ISO:
```bash
docker run --rm --privileged -v /path/to/repo:/work debian:trixie bash -c '
  apt-get update -qq && apt-get install -y squashfs-tools
  mount -o loop,ro /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso /tmp/iso-mount
  unsquashfs -f -d /work/build/cache/bootstrap /tmp/iso-mount/live/filesystem.squashfs
'
```

### Trap #86 ??? UE5 storage layout (prevent triple-copy disk exhaustion)
Unreal Engine is 77GB raw (338K files). It MUST live at `/home/gositeme/unreal-engine/`
as the single source of truth. It is NOT in `config/includes.chroot_after_packages/` because
`lb build` copies that entire directory into both `build/config/` AND `build/chroot/`,
causing 3x duplication (231GB wasted).

**Architecture:**
- **UE source:** `/home/gositeme/unreal-engine/` (77GB)
- **Build hook:** `config/hooks/live/0023-alfred-unreal-engine.hook.chroot`
- **Docker mount:** `-v /home/gositeme/unreal-engine:/ue-source:ro`
- **Hook action:** `cp -al /ue-source /opt/unreal-engine` (hardlinks, zero extra space)
- **Bootstrap copy:** `build/cache/bootstrap/opt/unreal-engine/` (from previous builds)

### Trap #87 ??? "Permission denied" ? "File deleted"
The `build/` directory is owned by root (created by Docker). When checking
from gositeme user:
- `ls build/cache/bootstrap/` ? "Permission denied" (NOT "No such file")
- `du -sh build/cache/bootstrap/` ? shows 4K (can't read contents)
- Agent incorrectly assumes "GONE" and triggers unnecessary recovery

**ALWAYS check root-owned dirs via Docker:**
```bash
docker run --rm -v /path/to/repo:/work debian:trixie ls -la /work/build/cache/bootstrap/
```
Or use an existing container:
```bash
docker exec CONTAINER_ID ls -la /work/build/cache/bootstrap/
```
**NEVER use bare `ls` or `du` as gositeme for root-owned build dirs.**

### Trap #88 ??? UE compressed archive tar exclude order
When creating the archive.org upload of UE:
```bash
# WRONG (silently includes .git):
tar -cf archive.tar.zst /home/gositeme/unreal-engine --exclude='.git'
# CORRECT:
tar --exclude='.git' -cf archive.tar.zst -C /home/gositeme unreal-engine
```
See also Trap #83.

### Trap #89 ??? Bootstrap recovery from ISO squashfs
If `build/cache/bootstrap/` is damaged or deleted, it can be reconstructed from
the previous ISO at `iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso` (98GB).
The ISO contains `live/filesystem.squashfs` which IS the full chroot.
Recovery takes ~30 min with 12 processors.
**The recovered bootstrap will match the ISO build state, which may be OLDER
than the latest bootstrap.** After recovery, audit against the instructions
to verify all components are present and up to date.

### Trap #90 ??? Two different UE locations serve two different purposes
1. `/home/gositeme/unreal-engine/` (77GB) = **UE source tree** (EpicGames repo clone)
2. `build/cache/bootstrap/opt/unreal-engine/` = **UE inside the master chroot** (baked into ISO)

These are DIFFERENT copies. The source tree (#1) is the master. The bootstrap
copy (#2) is created by the build process. Both must exist. Deleting #1 means
no future builds can include UE. Deleting #2 means the current chroot is incomplete.

### Trap #91 ??? NEVER push a short/stub AGENT-INSTRUCTIONS.md to the repo
The full AGENT-INSTRUCTIONS.md is 1100+ lines and contains the ENTIRE build
blueprint: ZFS compilation formula, NVIDIA bake recipe, all 90+ traps, rebuild
checklist, disk layout, ISO finalization, and current state inventory.
**NEVER replace it with a shorter "trap-only" version.** Always start from the
full desktop copy at `C:\Users\Danny\Desktop\New folder (2)\` and APPEND new
content. The GitHub version should always match the full desktop version.
Trap count as of v14.9: **91 traps** (see also Trap #92).

### Trap #92 ??? bootstrap_cache uses cp -a independently of lb_binary_chroot
The sed patch for cp -a ? cp -al MUST target ALL FOUR live-build scripts:
1. /usr/lib/live/build/lb_binary_chroot
2. /usr/lib/live/build/lb_chroot_includes
3. /usr/lib/live/build/lb_binary_includes
4. /usr/lib/live/build/bootstrap_cache ? **THIS WAS MISSING AND CAUSED A 165GB PHYSICAL DUPLICATION**

The ootstrap_cache restore phase runs FIRST (before lb_binary_chroot). If only 3 of the 4 files are patched, the bootstrap restore silently does a full physical cp -a copy of the entire 165GB cache, eating disk for 70+ minutes while reporting " zero-space hardlinks\ in the build log. The agent that set this up never verified the runtime process ??? docker top showed cp -a (physical) not cp -al (hardlink). Fixed in commit cf8397c6.
Trap count as of v14.10: **92 traps**.

### Trap #93 ??? alfred-build.sh sync_config runs as gositeme (PERMISSION DENIED)
The sync_config() function in lfred-build.sh used mkdir and 
sync on the host as the gositeme user. Since uild/config/ is root-owned from Docker builds, every sync attempt hit Permission denied. Fixed by replacing the host-side 
sync calls with a docker run that performs all config sync operations as root inside the container. Commit: patched in-place.
Trap count as of v14.10: **93 traps**.

### Trap #94 ??? The Holographic Master Vault (Protection against `live-build` wipe)
**Symptom:** Missing `.build/bootstrap_cache.save` causes `live-build` to execute `rm -rf cache/*` during `lb bootstrap_cache restore`, instantly wiping the entire 165GB compiled repository.
**Fix:** The Master Blueprint is now housed at `/master-vault`. Inside `scripts/lb-docker-inner-build.sh`, we forcefully inject the vault into the active `build/cache/` environment using:
`mount --bind /work/master-vault/packages.chroot /work/build/cache/packages.chroot`
`mount -o remount,ro,bind /work/build/cache/packages.chroot`
This Holographic Read-Only Projection forces the Linux kernel itself to block any `rm -rf` trap triggered by `live-build`, throwing a hardware-enforced `Read-only file system` error.

### Trap #95 ??? Hook-Instantiated Packages Bypassing the Local Cache
**Symptom:** During a "no internet" fast build, packages dynamically installed inside `config/hooks/live/` (such as Blender, Wireshark, Suricata, Cargo, Lynis, Node) abort the build because `live-build` does not natively cache hook dependencies in `packages.chroot`.
**Fix:** If new packages are added to hooks, you MUST manually drop into a Docker shell mapped to `/master-vault/packages.chroot` and execute `apt-get install -y --download-only -o Dir::Cache::archives=/work/cache <packages>` to forcefully inject the `.deb` files directly into the vault. This guarantees zero-internet execution.

### Trap #96 ??? Master Chroot Check Failing on Host
**Symptom:** `alfred-build.sh fast detach` fails with `FATAL: Master chroot not found` because it checks `build/cache/bootstrap/opt` which only exists as a bind mount *during* the Docker build.
**Fix:** Already patched. `scripts/alfred-build.sh` now correctly verifies the host-side path `master-vault/bootstrap/opt` before launching the Docker environment.

### Trap #97 ??? The Boot-to-Shutdown Architecture Autopsy
**Symptom:** Agents lacking awareness of custom boot arguments breaking the boot sequence.
**Mechanism:**
1. **GRUB Boot Menu:** Injects arguments like `alfred.task=luks-decrypt` and `alfred.mode=node`.
2. **Boot Execution:** `alfred-boot-task.service` fires `Before=getty.target`, reading `/proc/cmdline` to trigger `/usr/sbin/alfred-boot-task`. This executes specialized interactive tasks like TPM dumps, Kyber-1024 self-tests, and hardware probes early in the boot cycle.
3. **Shutdown Execution:** `ram-wipe.service` runs `sdmem -f -v` on `halt.target/reboot.target` for cold-boot attack mitigation. `mac-spoof.service` runs `macchanger -r` early before `network-pre.target`.
Trap count as of v14.13: **97 traps**.

### Trap #98 ??? The V2 Runner Package Fusion Bypass
**Symptom:** You download missing packages (like `osquery` or `bettercap`) to `/master-vault/packages.chroot/`, but they mysteriously do not appear in the final ISO after running the V2 build runner (`run-build.sh`).
**Cause:** The V2 Runner is optimized to skip the 4-hour `lb chroot` phase and jumps straight to running `mksquashfs` directly on the `cache/bootstrap` overlay. It assumes the master OS image is already fully installed. Raw `.deb` files sitting offline in `packages.chroot` are completely ignored during this compression.
**Fix:** Before running the V2 Runner, you must manually fuse any downloaded offline packages directly into the master bootstrap image using a privileged Docker container:
`ash
docker run --rm -v /home/gositeme/law/alfredlinux-com-source-live/master-vault:/vault debian:trixie chroot /vault/bootstrap bash -c "dpkg -i --force-all -R /vault/packages.chroot/ || apt-get install -f -y"
`
This permanently installs the missing packages into the 165GB master image, ensuring `mksquashfs` captures them.

### Trap #99 ??? Fast Repack Squashfs Bypass (V1 Runner)
**Symptom:** Running the V1 legacy runner (`lb-docker-inner-build.sh`) re-triggers `mksquashfs`, which wastes 4 hours compressing the OS even if you only changed a boot configuration.
**Fix:** The `lb-docker-inner-build.sh` script was patched to recognize the `ALFRED_FAST_REPACK=1` environment variable. When set, it aggressively short-circuits the `mksquashfs` phase inside `binary_rootfs` by copying a pre-existing `filesystem.squashfs.bak` directly into the binary tree, bypassing the compression entirely. It also automatically touches all `chroot_*` stamp markers in `.build/` to prevent `live-build` from entering the chroot phase. (Note: The V2 Runner `run-build.sh` is now the preferred method for ISO assembly).
Trap count as of v14.13: **99 traps**.

### Trap #100 ??? The TWO Master Vault Locations (SACRED ??? NEVER DELETE)
`build/cache/bootstrap/` is NOT the master vault. It is a SECONDARY working copy
that `lb build` uses during compilation. The real master vault lives in TWO locations:

1. **PRIMARY VAULT:** `/home/gositeme/law/alfredlinux-com-source-live/cache/bootstrap/`
   This is at the SOURCE ROOT level, completely OUTSIDE the `build/` directory.
   It is NOT root-owned. It is NOT touched by Docker cleanup commands.
   It is the golden copy of the entire OS filesystem.

2. **EMERGENCY RECOVERY ISO:** `/home/gositeme/law/alfredlinux-com-source-live/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso`
   This is a 104.5GB fully compiled ISO from July 2, 2026.
   Contains `live/filesystem.squashfs` which IS the full chroot.
   Recovery: `unsquashfs -f -d /work/build/cache/bootstrap /tmp/iso-mount/live/filesystem.squashfs`

**To restore `build/cache/bootstrap/` from the primary vault:**
```bash
docker run --rm --privileged -v /path/to/repo:/work debian:trixie bash -c \
  'cp -al /work/cache/bootstrap/ /work/build/cache/bootstrap/'
```
This uses hardlinks (zero extra disk space).

Commander Danny Williams Perez designed this two-layer architecture specifically
because AI agents cannot be trusted with a single copy. The manna is safe.
Trap count as of v14.15: **100 traps**.

### Trap #101 ??? Agent Destroyed build/cache/bootstrap on 2026-07-05
**Incident:** At 2026-07-05T06:12:11Z, a previous AI agent encountered a
"missing /usr/sbin" error during build. Instead of reading Trap #5.5
(UID 1004 Ghost ??? fix with `chown -R root:root`), the agent executed:
```bash
rm -rf /work/build/cache/bootstrap /work/build/.build/* /work/build/chroot/*
```
This violated Commandment #1 and Trap #85. The secondary working copy was
destroyed. The primary vault at `cache/bootstrap/` and the 104GB ISO were
unaffected because the Commander never trusted agents with a single copy.

**Lesson:** NEVER run `rm -rf` on ANY directory containing "bootstrap" or "cache"
without EXPLICIT Commander authorization. Fix in place. Always.

### Trap #102 ??? ALL rm -rf Commands Commented Out of Build Scripts
As of v14.15, ALL `rm -rf` commands in the following scripts have been
commented out with `#` to prevent future catastrophic deletions:
- `scripts/alfred-build.sh` ??? 3 occurrences commented
- `scripts/lb-docker-inner-build.sh` ??? 5 occurrences commented

If a build requires cleanup, it must be done MANUALLY by the Commander
or with EXPLICIT permission. No script shall auto-delete anything.

### Trap #103 ??? chroot_early Hooks for Pre-Package User Creation
**Symptom:** `exim4-config` fails during `lb chroot_install-packages` because
the `mail` user/group does not exist in a fresh debootstrap chroot.
**Cause:** `exim4` post-install scripts validate config and require `mail:x:8:8`
in `/etc/passwd`. A fresh bootstrap has no `mail` user.
**Fix:** Live-build supports `*.chroot_early` hooks in `config/hooks/`.
These execute BEFORE `lb_chroot_install-packages` via `lb_chroot_early_hooks`.
Created `config/hooks/0000-mail-user.chroot_early` which runs:
```bash
groupadd -f -g 8 mail || true
useradd -u 8 -g mail -d /var/mail -s /usr/sbin/nologin -c "mail" mail || true
```
This is a `.chroot_early` file, NOT a `.hook.chroot` file. It does NOT violate
the 1,335 sacred hook count (those are `.hook.chroot` files in `config/hooks/live/`).
Trap count as of v14.15: **103 traps**.

### Trap #104 ??? Dell Watch Dashboard is the Single Source of Truth
If a build does not show up as actively building on the dashboard (https://gositeme.com/veil/dell-watch.php), it is NOT considered building.
The dashboard reads directly from the \lb-docker-build.log\ file. If a script fails instantly (e.g. \exit=127\, missing master chroot, symlink issues to \lfred-runner.log\), it will bypass the dashboard entirely.
If it is not streaming to the dashboard, STOP what you are doing, track down why \lb-docker-build.log\ isn't receiving output, fix the wrapper scripts, and START IT AGAIN PROPERLY. Never assume "tmux is running, so it's building".

### TRAP #105: The cp -al Hotfix Cross-Device Link Crash
**The Trap**: An old hotfix in `lb-docker-inner-build.sh` replaced `cp -a` with `cp -al` inside `/usr/lib/live/build/*` to speed up copies using zero-space hardlinks. This crashes with `Invalid cross-device link` because the build output (`/work/build`) is a Docker bind-mount on the host filesystem, while the live-build scripts are inside the container's root filesystem.
**The Solution**: NEVER patch live-build to use hardlinks (`cp -al`) for files crossing the Docker volume boundary. Standard physical copies (`cp -a`) are mandatory.

### TRAP #106: Fast Builds Silently Bypass New Chroot Hooks
**The Trap**: When `ALFRED_FAST_REPACK=1` is active, the build script generates simulated completion markers (`.build/chroot_*`) to speed up the process. While this correctly bypasses the massive 4-hour package download phase, it ALSO bypasses `lb chroot_hooks`. Any newly authored hooks in `config/hooks/live/` will NOT be executed against the chroot and will be missing from the final OS.
**The Solution**: If you have written new chroot hooks that must execute and modify the filesystem, you CANNOT rely on a Fast Build to apply them. You must either run a Full Build to execute all hooks, or manually run your hook payload against the master vault chroot.

### TRAP #107: The Permission Denied Host Root Leaks
**The Trap**: When modifying the repository on the host machine (e.g. editing `config/hooks/live/` files), you may suddenly encounter `Permission denied` errors. This occurs because the `alfred-build.sh` Docker containers run as root, and any files they create or touch inside the bind-mount become owned by `root:root` on your host, forcefully locking out the `gositeme` user from their own repository.
**The Solution**: NEVER try to use `sudo mv` locally, as it will hang asking for passwords. You must use a Docker container to forcefully heal the ownerships. Run a blanket `chown -R 1000:1000` on the repository codebase (`config/`, `scripts/`, etc.), while simultaneously ensuring the master vaults (`cache/bootstrap/` and `build/cache/bootstrap/`) are strictly forced back to `root:root` to avoid the UID 1004 Ghost Bug (Trap #15).

### TRAP #108: The Full Build Grub-EFI Binary Crash (Exit 100)
**Symptom**: A Full Build (no ALFRED_FAST_REPACK) crashes at the `lb binary` phase with `exit=100` and errors like `Unable to locate package grub-efi-amd64-bin`.
**Cause**: During a Full Build, `lb chroot_apt remove` cleans up `/var/lib/apt/lists/` to save space. However, `lb binary_grub-efi` naively tries to run `apt-get install grub-efi-amd64-bin` AFTER the lists are deleted, causing an instant exit 100 crash. (In Fast Repack mode, this doesn't happen because lb chroot_apt is skipped).
**Fix**: The `scripts/lb-docker-inner-build.sh` script must be patched to run `chroot /work/build/chroot apt-get update || true` immediately before executing `lb binary`, completely neutralizing the package location error.

### TRAP #109: The --rm Container Evidence Vaporizer
**Symptom**: A docker run --rm -d build container vanishes after binary_bootloader_splash. No error, no ISO, no crash dump.
**Cause**: set -euo pipefail + --rm = container auto-deletes on any non-zero exit, destroying all evidence. tee log dies before flush.
**Fix**: When debugging, ALWAYS launch WITHOUT --rm. Use docker logs to inspect. Only use --rm for proven-stable production builds.

### TRAP #110: binary_syslinux Exits 0 Silently When Bootloader Is grub-efi
**Symptom**: binary_syslinux exits 0 without doing anything when bootloader is grub-efi only.
**Cause**: Correct behavior. The script checks LB_BOOTLOADERS and skips if syslinux is absent. Not a bug.
**Fix**: Do not debug binary_syslinux. The crash is AFTER this step.

### TRAP #111: The master-vault bind-mount Requires --privileged
**Symptom**: mount: permission denied on Holographic Master Vault projection.
**Cause**: mount --bind requires --privileged. Without it, Docker security profile blocks the syscall.
**Fix**: ALWAYS use --privileged for build containers. Already in alfred-build.sh build_docker_args().

### TRAP #112: The tee Log Truncation on set -e Death
**Symptom**: lb-docker-build.log only has 8-10KB even after 8+ minutes of building.
**Cause**: exec > >(tee LOG) dies when set -e kills the script. Buffer not flushed.
**Fix**: Use stdbuf -oL tee for line-buffered output in debug builds.

### TRAP #113: The config/common Deletion Before lb config
**Symptom**: After deleting config/common, lb config regenerates with LB_IMAGE_TYPE=tar instead of iso-hybrid.
**Cause**: If auto/config is missing or malformed, lb config uses system defaults.
**Fix**: Verify auto/config contains --binary-images iso-hybrid before running lb config.



### TRAP #114: The Missing Kernel on Full Build (LB_LINUX_PACKAGES=none)
**Symptom**: During `lb binary`, hook `0964-kernel7-menu.hook.binary` fails with `[0999] ERROR: cannot locate binary/live directory`.
**Cause**: `auto/config` sets `--linux-packages none`. This makes `lb binary_linux-image` exit instantly without creating `binary/live/` or copying the kernel. Manually staging the kernel in `lb-docker-inner-build.sh` BEFORE `lb binary` does NOT work because `lb binary` wipes the `binary/` directory during `binary_rootfs`.
**Fix**: Created `config/hooks/live/0900-copy-kernel.hook.binary` which runs INSIDE the `lb binary_hooks` phase (after the wipe, before `0964`). This hook does `mkdir -p binary/live && cp -a chroot/boot/vmlinuz-7* binary/live/ && cp -a chroot/boot/initrd.img-7* binary/live/`. This is the idiomatic live-build solution ???????? binary hooks execute at the correct pipeline stage.
**NEVER stage kernel files before `lb binary` ???????? they will be wiped.**

### TRAP #115: The Catastrophic .gitignore Mass Deletion (8,594 Files Erased)
**Symptom**: GitHub repository appears completely empty. Only 3 files visible.
**Cause**: A previous agent expanded `.gitignore` to exclude virtually every directory (`config/`, `config/hooks/live/`, `config/includes.chroot/`, `build-assets/`, `config/packages.chroot/`, etc.), then ran `git add .` which staged the DELETION of 8,594 files (2,315,850 lines). The commit was pushed to GitHub, origin, and all remotes, making the public repository appear empty.
**Fix**: The files were never truly lost ???????? they remained in git history. Recovery: `git checkout <last-good-commit> -- .` to restore all files from the parent commit. Then `git commit --amend` and `git push --force` to all remotes.
**NEVER blindly expand .gitignore. NEVER run `git add .` after modifying .gitignore without first running `git status` to verify no critical files are being deleted. ALWAYS verify tracked file count with `git ls-tree --name-only -r HEAD | wc -l` after any .gitignore change. Expected count: 8,595+.**

### TRAP #116: The UID Mismatch Permission Trap (ubuntu vs gositeme)
**Symptom**: `git checkout` fails with `error: unable to unlink old '<file>': Permission denied` even after running `chown -R 1000:1000`.
**Cause**: The gositeme user is UID **1004**, NOT UID 1000. UID 1000 is `ubuntu`. Docker's `chown -R 1000:1000` changes ownership to `ubuntu:ubuntu`, which gositeme (UID 1004) still cannot write to. The server has multiple users: ubuntu (1000), admin (1001), gositeme (1004).
**Fix**: ALWAYS use `chown -R 1004:1004` (or `chown -R gositeme:gositeme` inside containers that have the user). Verify with `id` on the host: `uid=1004(gositeme)`.
**NEVER assume UID 1000 is the correct user. ALWAYS check `id` first.**

### TRAP #117: The file:// Gitea Push Rejection
**Symptom**: `git push ssh-forge main` and `git push local-bare main` fail with `Gitea: Rejecting changes as Gitea environment not set`.
**Cause**: Both `ssh-forge` and `local-bare` remotes use `file:///home/gositeme/goforge/data/gitea/repositories/commander/alfredlinux.com.git` which bypasses Gitea's SSH authentication layer. Gitea's pre-receive hook requires the `GITEA_*` environment variables to be set, which only happens when pushing via HTTP or Gitea-managed SSH keys.
**Fix**: Push via `origin` (HTTP with token) instead: `git push origin main`. The `origin` remote uses `http://Commander:<token>@127.0.0.1:3300/` which properly authenticates. The `ssh-forge` and `local-bare` remotes point to the SAME bare repo as `origin`, so pushing to `origin` already updates them.
**The 5 remotes are actually 3 unique destinations: GitHub, GoForge (origin/ssh-forge/local-bare all point to same bare repo).**

### TRAP #118: The alpine/git Docker Image Has No Bash
**Symptom**: `docker run alpine/git:latest bash -c '...'` fails with `git: 'bash' is not a git command`.
**Cause**: The `alpine/git` image's entrypoint IS `git`, so any argument is interpreted as a git subcommand. Alpine also does not ship bash ???????? only `sh`.
**Fix**: Use `debian:trixie` with `apt-get install -y git` if you need git+bash in Docker, or use the `alfred-builder` image. For simple git operations, run git from the host after fixing permissions with a Docker chown.


### TRAP #119: The Debian Trixie /lib to /usr/lib Migration Bomb
**Symptom**: live-boot initramfs hook crashes with copy_exec /lib/udev/*_id failed. UFW fails with ERROR: Could not stat /lib/ufw/ufw-init. cryptroot hook fails with cannot open /lib/cryptsetup/functions.
**Cause**: Debian Trixie (13) migrated ALL /lib/ contents to /usr/lib/. Scripts that hardcode /lib/ paths fail.
**Fix**: Create explicit symlinks: mkdir -p /lib/udev and ln -sf /usr/lib/udev/ata_id /lib/udev/ata_id (repeat for all *_id binaries). Same for /lib/ufw/ufw-init and /lib/cryptsetup/functions.

### TRAP #120: Docker Container Mount Namespace Isolation
**Symptom**: You mount /proc and /sys on the HOST server, but the container still prints /proc/ is not mounted.
**Cause**: Docker enforces a strict private mount namespace. Mounts on the host are INVISIBLE inside the container.
**Fix**: ALWAYS use docker exec to mount pseudo-filesystems. NEVER mount from the host.

### TRAP #121: The Empty Dummy File Cascade Trap
**Symptom**: Creating an empty /lib/cryptsetup/functions file causes cryptroot to crash with crypttab_foreach_entry not found.
**Cause**: The cryptroot hook sources the functions file. If empty, no functions are defined.
**Fix**: Extract the real 706-line file from the cached .deb archive. NEVER create empty dummy files for sourced scripts.

### TRAP #122: The update-initramfs Cascade Bomb
**Symptom**: Every apt-get install triggers update-initramfs which crashes, cascading to every subsequent package.
**Cause**: initramfs-tools registers a dpkg trigger. Any broken hook causes the entire chain to fail.
**Fix**: Fix the broken hooks FIRST (Traps 119, 121). Nuclear option: temporarily replace update-initramfs with a dummy exit 0 script.

### TRAP #123: The UID 1000 Build Host Ownership Leak (52K+ Files)
**Symptom**: UFW warns uid is 0 but / is owned by 1000. 52,000+ files owned by UID 1000.
**Cause**: bootstrap was constructed by ubuntu user (UID 1000). cp -a preserves ownership.
**Fix**: find /work/build/chroot -uid 1000 -not -path */home/* -not -type l -exec chown root:root {} +. MUST be done BEFORE hooks run.

### TRAP #124: The Missing SUID Bits After Bulk chown
**Symptom**: sudo, su, mount, passwd all fail with must be setuid root.
**Cause**: Linux strips SUID bit on ANY ownership change. Bulk chown kills ALL SUID binaries.
**Fix**: chmod u+s /usr/bin/sudo /usr/bin/su /usr/bin/passwd /usr/bin/newgrp /usr/bin/mount /usr/bin/umount /usr/bin/chsh /usr/bin/chfn /usr/bin/gpasswd /usr/bin/pkexec /usr/lib/openssh/ssh-keysign. ALWAYS restore SUID after ANY bulk chown.

### TRAP #125: The Missing 127.0.0.1 localhost Entry
**Symptom**: Applications resolving localhost fail. Database connections time out.
**Cause**: /etc/hosts had sovereign domain entries but was missing 127.0.0.1 localhost.
**Fix**: sed -i '1i 127.0.0.1 localhost' /etc/hosts. POSIX requirement.

### TRAP #126: The Group-Writable /etc Directories
**Symptom**: UFW warns /etc is group writable. Security scanners flag subdirectories.
**Cause**: UID 1000 leak corrupts permissions. Directories copied with 775 instead of 755.
**Fix**: find /etc -maxdepth 1 -perm -020 -type d -exec chmod 755 {} +. Lock down sudoers to 440, tor to 755/644.

### TRAP #127: The Monolithic Kernel Design (1691 Built-in vs 40 Modules)
**Symptom**: Agent panics because /lib/modules/7.0.12/kernel/ is empty. No in-tree .ko files.
**Cause**: Custom 7.0.12 kernel is MONOLITHIC by design. 1691 features built-in (=y), only 40 modules (=m). NVIDIA is the only .ko present.
**Fix**: NO FIX NEEDED. BY DESIGN. NEVER panic about missing kernel/ module tree.

### TRAP #128: The dpkg Lock During Active Hook Execution
**Symptom**: dpkg --configure -a fails with dpkg frontend lock was locked by apt-get.
**Cause**: Hooks call apt-get which holds the lock. Cannot run dpkg from second terminal.
**Fix**: docker pause container, fix issue, unpause. Or deploy sentinel script to poll lock. NEVER force-remove the lock file.

### TRAP #129: The runit-helper Missing File
**Symptom**: acpid fails with /lib/runit-helper/runit-helper not found.
**Cause**: Same as Trap 119. Trixie moved to /usr/lib/runit-helper/.
**Fix**: Extract from cached .deb and symlink to legacy path.

### TRAP #130: The World-Writable Sudoers File
**Symptom**: sudo refuses to run. /etc/sudoers.d/00-alfred-hardening is world readable.
**Cause**: Hook umask creates files with wrong permissions. Sudo requires 0440.
**Fix**: chmod 440 /etc/sudoers.d/*. ALWAYS use install -m 0440 when creating sudoers files.

### TRAP #131: The NVIDIA GPU Library Userspace Mismatch (Debian 550 vs Custom 610)
**Symptom**: Games, Unreal Engine, VR, and ALVR crash or fail to initialize GPU acceleration on custom kernel 7.0.12.
**Cause**: Custom 7.0.12 kernel has custom NVIDIA 610.43.02 `.ko` driver modules, but Debian package lists auto-install older 550 userspace libraries (`libGL.so`, `libnvidia-ml.so`, `nvidia-cuda-toolkit`), creating an incompatible kernel-to-userspace version mismatch.
**Fix**: Extract and inject the official NVIDIA 610.43.02 proprietary userspace `.so` libraries directly into `/usr/lib/x86_64-linux-gnu/`. Disable `nvidia-cuda-toolkit` and `nvidia-driver-550` packages in hooks 0089 and 1335.

### TRAP #132: The Broken `[ -e "0755" ] && chmod 0755` Regex Replace Disaster
**Symptom**: Over 108 hook scripts (Manna Machine, Tabernacle, Ark, Voice Doctor, Altar, etc.) never gain executable permissions (`0755`), failing silently during ISO boot or execution.
**Cause**: A faulty regex replace in an earlier automated script replaced target file paths with `"0755"`, resulting in meaningless conditional checks like `[ -e "0755" ] && chmod 0755 /path`. Since a file named `"0755"` never exists, the `chmod` never executes.
**Fix**: Replace all instances of `[ -e "0755" ] && chmod 0755` with clean `chmod 0755` across all `.chroot` hook files.

### TRAP #133: Centralized PHP Mining Endpoint Vulnerability (The Offline Apocalypse Gap)
**Symptom**: QGSM token mining, Universal Basic Energy (UBE) welfare claims, and economy validation freeze completely when internet connectivity is lost or central server `gositeme.com/api/mining.php` is offline.
**Cause**: Mining worker and native miners relied exclusively on HTTP POST requests to a centralized MySQL database on a remote server.
**Fix**: Deploy **QGSM Sovereign Validator Node v2.0** (`/opt/qgsm-node/qgsm-validator.py` on port 8399) baked directly into Hook 1335. Upgrade mining algorithm to post-quantum **SHA-3 Keccak-256**. Implement hybrid 3-tier failover (Central -> Local SQLite Ledger -> Yggdrasil IPv6 Multicast Gossip on port 7722) with automated consensus sync and first-boot Ed25519/SHA-3 sovereign wallet generation.

### TRAP #134: Hook 0001 Quote/Slash Integrity (Archive Asset Prestage Syntax Breakdown)
**Symptom**: `0001-alfred-merged-auto.hook.chroot` throws syntax errors (`unexpected EOF while looking for matching '"'` or `syntax error near unexpected token '('`) during bash syntax check or chroot hook execution.
**Cause**: During automated archive asset merging, file copying commands (`cp " /three.min.js\ /tmp/...`) were inserted with unmatched double quotes and trailing backslash escape characters. Because of an unclosed quote, bash interprets hundreds of subsequent script lines as a string literal until hitting a random parenthesis.
**Fix**: Always run `bash -n` on all live hook files after merging scripts. Remove stray quotes and trailing backslashes from asset copy lines in `0001-alfred-merged-auto.hook.chroot`.

### TRAP #135: UFW UID 0 Warning & /etc/skel Permission Denied
**Symptom**: UFW warns `uid is 0 but /etc/ufw/applications.d is owned by 1004`. Hook scripts crash trying to modify `/etc/skel/.bashrc`.
**Cause**: The master blueprint `/work/build/cache/bootstrap/etc/ufw` and `/etc/skel` were extracted with user `gositeme:gositeme` (1004) instead of `root:root`.
**Fix**: ALWAYS ensure files inside the master bootstrap are owned by root via `sudo chown -R root:root build/cache/bootstrap/etc/ufw build/cache/bootstrap/etc/skel build/cache/bootstrap/lib/ufw`.

### TRAP #136: ImageMagick 8K Wallpaper Generation Crash
**Symptom**: `[WARN] default.png 8K generation failed` appears during `0100-alfred-merged-auto.hook.chroot`.
**Cause**: Generating an 8K gradient requires over 8GB of memory and disk cache. ImageMagick's default limits abort the process.
**Fix**: Prefix the `convert` command with `MAGICK_MEMORY_LIMIT=4GB MAGICK_MAP_LIMIT=4GB MAGICK_AREA_LIMIT=8GB MAGICK_DISK_LIMIT=16GB`.

### TRAP #137: The Ghost Dashboard Freeze (Live Override Bypass)
**Symptom**: The `dell-watch.php` dashboard freezes indefinitely on `Processing triggers for initramfs-tools...` or similar inner container `dpkg` output, even though the server is heavily loaded and the build hasn't crashed.
**Cause**: The dashboard reads strictly from `lb-docker-build.log`. If an AI agent or the system forcefully kills a hanging inner process (like `dpkg`), the hook wrapper in the main script (`for hook in ...; do cp hook ... && bash ...; done`) catches the exit and *continues* to the next hook in alphabetical order. Because the `dpkg` process died, it stops writing to the log. Subsequent hooks (like `pipx install`) may write to `stderr` or nowhere at all, leaving the dashboard completely frozen, even when `mksquashfs` starts.
**Fix**: Verify the build is actually progressing by checking `docker ps` and `ps auxf`. To unfreeze the user's dashboard, you MUST manually inject a text override into the log file via SSH using `echo '[SYSTEM OVERRIDE] <msg>' | sudo tee -a /work/build/lb-docker-build.log`.

### Trap #138: The Exit Code 1 Assembly Crash
**Symptom:** After a flawless 14-hour `mksquashfs` run, the `run-build-patched.sh` orchestrator script crashes with Exit Code 1 exactly at `=== STEP 7.9: Trap 10 (Overlay unmount and Stamps) ===` before it can run `lb binary`.
**Cause:** Trap 99 completely purges the `/work/build/.build` directory at the start of the build to ensure a clean slate. When the orchestrator reaches step 7.9, it tries to `touch /work/build/.build/binary_chroot`, which fails with `No such file or directory` because the `.build` folder no longer exists. Because the wrapper uses `set -e`, it immediately crashes instead of completing the ISO assembly.
**Fix:** Do **NOT** rely on the orchestrator wrapper to assemble the final ISO. You must let the orchestrator crash at Step 7.9! Once it crashes and the 133GB `filesystem.squashfs` is safely on disk, you MUST manually deploy the raw `xorriso` assembly script directly onto the host exactly as described in **Trap #33**. Do not attempt to fix the wrapper—bypassing it natively via `xorriso` on the host is the only way to avoid both the `.build` crash and the 4GB ISO file size limit.

### TRAP #139: ZSTD Level 22 RAM Exhaustion
**Symptom**: `mksquashfs` is killed by the OOM killer or the server crashes completely.
**Cause**: ZSTD Level 22 compression requires approximately 14GB of RAM per thread. If `mksquashfs` uses all available CPU cores without restrictions, it will exhaust server memory and crash.
**Fix**: You must restrict `mksquashfs` to a maximum of 4 cores to prevent RAM exhaustion.

### TRAP #140: Hook Logic Duplication (Security Script)
**Symptom**: Build times extend needlessly as the `0160-alfred-security` hook runs multiple times during the AppArmor profile phase.
**Cause**: The hook generation script improperly duplicates the 0160 security hook logic into multiple `merged-auto` hook files, enforcing the same security rules repeatedly.
**Fix**: Update the hook generation script to ensure security hooks are only injected once.

### TRAP #141: The `eatmydata` Speed Optimization
**Symptom**: `dpkg` and `apt` operations during the build take exponentially longer due to continuous `fsync()` operations.
**Cause**: By default, package managers flush data to disk synchronously.
**Fix**: Use `eatmydata` as a prefix for `apt-get` and `dpkg` commands inside the hooks to disable `fsync` and drastically speed up the chroot build phase.

