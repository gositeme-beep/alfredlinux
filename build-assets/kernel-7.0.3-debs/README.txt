Linux 7.0.3 kernel .deb archive (optional — gitignored blobs)
=============================================================
Large `linux-*.deb` files stay out of git (see repo `.gitignore`). For a second machine
or a thin checkout, pack on the host that already built the kernel:

  bash scripts/pack-kernel-debs-archive.sh

That writes **`linux-7.0.3-debs-for-iso.tar.gz`** in this directory (or set **`OUT=`**).

On the build host (or before `iso-preflight` / `lb-docker-build`):

  • Drop the `.tar.gz` (or `.tar.zst`) here with that name, **or**
  • Set **`ALFRED_KERNEL_DEBS_ARCHIVE=/path/to/archive.tar.gz`**, **or**
  • Keep debs only under **`KERNEL_WORK`** (default `../kernel-7.0.3-work`) and run:

      bash scripts/stage-kernel-debs-for-iso.sh

`iso-preflight.sh` runs staging automatically. Canonical doc: `config/packages.chroot/README-KERNEL7.txt`.
