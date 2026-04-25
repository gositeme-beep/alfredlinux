Alfred Kernel 7 — required .deb files for ISO build
===================================================

This live-build tree does NOT use the Debian metapackage linux-image-amd64.
The ISO must include your custom-built Alfred kernel (Linux 7.x) packages.

Before running `lb build` on the build host:

1. Build kernel debs (example from Alfred pipeline):
     make bindeb-pkg LOCALVERSION= KDEB_PKGVERSION=7.0.0~rc7-1alfred

2. Copy into THIS directory (same folder as alfred-browser_*.deb):
     linux-image-*_amd64.deb
     linux-headers-*_amd64.deb
   Add linux-libc-dev_*.deb from the same build if apt complains about dependencies.

3. Package names must satisfy the chroot hook check:
     dpkg -l | grep linux-image-7

4. Large *.deb files are gitignored — keep them on the build machine or artifact store.

If these debs are missing, hook 0050-alfred-kernel7.hook.chroot fails the build on purpose.
