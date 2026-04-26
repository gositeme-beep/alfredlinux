Alfred Linux — helper scripts (ISO / builder)
==============================================

iso-preflight.sh
  Run before `lb build`. Fails if linux-image-7.0.1*.deb missing from
  config/packages.chroot/ (hook 0050). Usage:
    bash scripts/iso-preflight.sh

kernel-download-7.0.1.sh
  Downloads linux-7.0.1.tar.xz + patch-7.0.1.xz into ../kernel-7.0.1-work/ (or KERNEL_WORK=…).
  Does not compile. Then follow config/packages.chroot/README-KERNEL7.txt.

remote-apt-live-build.sh
  Run on Ubuntu builder as root (`sudo su -`) to apt install live-build stack.

build-on-ubuntu.sh
  From gositeme (or laptop): rsync repo to ubuntu@BUILD_HOST, optional ssh.
  On the builder: interactive `sudo su -` (password if required), then `lb` from …/build.
  See /home/gositeme/law/ALFRED-LINUX-BUILD-TEST.txt for full flow.

kernel-install-build-deps.sh
  sudo once on build host: debhelper, libdw-dev, etc. (required for bindeb-pkg).

kernel-bindeb-pkg-nohup.sh
  After host `sudo` deps: background `fakeroot make bindeb-pkg` on gositeme.

kernel-docker-bindeb.sh + kernel-docker-inner-bindeb.sh
  **No host sudo:** build inside `debian:bookworm` Docker. Usage:
    bash scripts/kernel-docker-bindeb.sh detach
  Then `docker logs -f <name>` (name in ../kernel-7.0.1-work/docker-bindeb.containername).

copy-kernel-debs-to-chroot.sh
  After .deb exist under KERNEL_WORK: copy into `config/packages.chroot/`, then `iso-preflight.sh`.

Law copies (same ideas, paths fixed for gositeme home):
  /home/gositeme/law/alfred-build-preflight.sh
  /home/gositeme/law/alfred-kernel-download-sources.sh
  /home/gositeme/law/alfred-remote-apt-live-build.sh
  /home/gositeme/law/alfred-build-on-ubuntu.sh
