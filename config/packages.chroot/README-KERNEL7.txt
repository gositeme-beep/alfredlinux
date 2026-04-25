Alfred Kernel 7.1 — required .deb files for ISO build
=====================================================

This live-build tree does NOT use the Debian metapackage linux-image-amd64.
The ISO must ship **Linux 7.1** with your **Alfred kernel work carried forward
from 7.0** (config, LOCALVERSION, patches, out-of-tree fixes)—not a bare
upstream tarball alone, and not a 7.0-only image.

Layering (what “7.1 on top of 7.0” means here)
----------------------------------------------
1. **7.0 Alfred baseline** — the .config, Alfred-specific options, and any
   patches you already proved on `linux-7.0-rc7` (or equivalent).
2. **7.1 upstream base** — move the build to **linux-7.1.y** (stable line or
   the exact 7.1 tag you choose from kernel.org).
3. **Merge / replay** — `make olddefconfig` (or `merge_config.sh`) so 7.0
   intent survives; resolve new Kconfig symbols; add **7.1-specific fixes**
   you care about (stable commits, security, hardware).
4. **Package** — same `bindeb-pkg` flow; bump **KDEB_PKGVERSION** so the
   installed package name is **linux-image-7.1*** (required by hook 0050).

Before running `lb build` on the build host
--------------------------------------------
1. Produce debs from the **7.1** tree, e.g.:
     make bindeb-pkg LOCALVERSION= KDEB_PKGVERSION=7.1.0-1alfred
   (Your revision string may differ; package **name** must still match
   `linux-image-7.1*` as produced by the kernel Makefile.)

2. Copy into THIS directory (same folder as alfred-browser_*.deb):
     linux-image-*_amd64.deb
     linux-headers-*_amd64.deb
   Add linux-libc-dev_*.deb from the same build if apt complains.

3. Hook check (must pass):
     dpkg-query -W -f '${binary:Package}\n' | grep -E '^linux-image-7\.1\.'

4. Large *.deb files are gitignored — keep them on the build host or artifact store.

If these debs are missing or still 7.0-only names, hook 0050 fails the build on purpose.
