Alfred Linux — Linux 7.0.3 kernel .deb files (required for ISO)
===============================================================

Upstream fact (do not guess): kernel.org v7.x **stable is 7.0.3**, not “7.1”.
- Index: https://cdn.kernel.org/pub/linux/kernel/v7.x/
- Tarball: https://cdn.kernel.org/pub/linux/kernel/v7.x/linux-7.0.3.tar.xz
- Small step from 7.0:     https://cdn.kernel.org/pub/linux/kernel/v7.x/patch-7.0.3.xz

This live-build tree does **not** install `linux-image-amd64`. The ISO must
include **your** `bindeb-pkg` output built from **linux-7.0.3** with Alfred
config and patches carried forward from any earlier 7.0-rc / 7.0.0 work.

Workflow
--------
1. Fetch and verify checksum of `linux-7.0.3.tar.xz` from cdn.kernel.org.
2. Merge your Alfred `.config` / patches from the older 7.0-rc tree; run
   `make olddefconfig` (or equivalent) and resolve new symbols.
3. `make bindeb-pkg LOCALVERSION= KDEB_PKGVERSION=7.0.3-1alfred`  
   (revision string is yours; **binary package name** must still be
   `linux-image-7.0.3...` as emitted by the kernel build.)
4. Copy into **this directory** next to `alfred-browser_*.deb`:
     linux-image-*_amd64.deb
     linux-headers-*_amd64.deb
   plus `linux-libc-dev_*.deb` from the same build if apt demands it.

Pack / unpack (second host, Forge runner, thin checkout)
----------------------------------------------------------
On a machine that already has the `.deb` files under `KERNEL_WORK` or in this directory:

  bash scripts/pack-kernel-debs-archive.sh

Copy the resulting **`build-assets/kernel-7.0.3-debs/linux-7.0.3-debs-for-iso.tar.gz`**
onto the build host (that tree is gitignored except `README.txt`). Then:

  bash scripts/stage-kernel-debs-for-iso.sh
  bash scripts/iso-preflight.sh

`iso-preflight.sh` runs **`stage-kernel-debs-for-iso.sh`** for you (archive →
`config/packages.chroot/`, then `KERNEL_WORK` copy). Override archive path with
**`ALFRED_KERNEL_DEBS_ARCHIVE`**. **`.tar.zst`** is supported if **`zstd`** is installed.

Hook gate (must pass before ISO is “good”)
------------------------------------------
  dpkg-query -W -f '${binary:Package}\n' | grep -E '^linux-image-7\.0\.1'

Large `linux-*.deb` files are gitignored — keep them on the build host.

Runtime policy (on the ISO)
---------------------------
- `unattended-upgrades` is limited to Debian + Debian-Security origins; it does **not** remove
  “unused” kernel packages (`Remove-Unused-Kernel-Packages` is false) so `linux-image-7.0.3*`
  is never auto-pruned.
- `/etc/apt/preferences.d/99alfred-no-debian-default-kernel` (from hook **0710**) blocks Debian
  meta-packages such as `linux-image-amd64` so `apt` / upgrades do not replace the Alfred kernel.
  Publish a new `linux-image-7.0.3*.deb` (and headers) when you ship kernel updates.

Security manifest (scope + “do our best” — not a CVE-by-CVE claim)
-----------------------------------------------------------------
  See: `docs/KERNEL-7.0.3-SECURITY-MANIFEST.txt`
