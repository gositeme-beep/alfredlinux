Alfred Linux — helper scripts (ISO / builder)
==============================================

security-audit.sh
  Static sweep of config/hooks/live/*.hook.chroot (SSH footguns, curl|sh, etc.).
  Optional: ALFRED_SHELLCHECK_ALL=1 to shellcheck every scripts/*.sh.
    bash scripts/security-audit.sh
  Wave checklist: scripts/SECURITY-WAVES.txt
  CI: .github/workflows/security-audit.yml

iso-preflight.sh
  Run before `lb build`. Fails if linux-image-7.0.1*.deb missing from
  config/packages.chroot/ (hook 0050). Usage:
    bash scripts/iso-preflight.sh
  Run `lb` from `build/` (see ALFRED-LINUX-BUILD-TEST.txt). An empty `build/auto/` is normal
  unless you maintain an executable `build/auto/config` script.

release-integrity.sh
  After ISOs exist in one directory: SHA256SUMS + SHA512SUMS, then GPG-detached sign.
    scripts/release-integrity.sh hash *.iso
    scripts/release-integrity.sh sign
  Verifiers: `scripts/release-integrity.sh verify` or manual gpg + sha256sum -c.
  See README.txt "VERIFICATION — TRUST BUT VERIFY".

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

lb-docker-build.sh + lb-docker-inner-build.sh
  **No host sudo:** privileged Debian container runs `lb build` with repo at `/work`.
    bash scripts/lb-docker-build.sh detach
  Log: `lb-docker-build.log` (repo root). Name: `lb-docker.containername`.
  Requires Docker; can take many hours; `--privileged` is intentional for mounts/chroot.

watch-lb-docker-build.sh
  After `detach`, **wait until the container exits**, print summary + ISO paths + log tail.
    bash scripts/watch-lb-docker-build.sh
  Optional: `--status-json /path/state.json` (machine-readable), `--webhook URL` (POST JSON on exit).
  With `--status-json`, a non-blocking **flock** on `.lb-docker-watch.lock` prevents two parallel
  watchers corrupting the same JSON; second instance exits 3. `ALFRED_WATCH_NO_FLOCK=1` bypasses.
  Law wrapper: `/home/gositeme/law/alfred-watch-lb-docker.sh`

check-lb-docker-status.sh
  One-shot triage: container name, `docker ps`, `last-lb-docker.json`, ISO `find`, log grep/tail.
    bash scripts/check-lb-docker-status.sh
    bash scripts/check-lb-docker-status.sh /path/to/last-lb-docker.json

supervise-lb-docker-nap.sh
  **Before sleep:** `detach` + `docker wait` + ISO check + `last-lb-docker.json` + optional `NAP_WEBHOOK`.
    bash scripts/supervise-lb-docker-nap.sh
  Canonical script: `/home/gositeme/law/alfred-build-control-plane/supervise-lb-docker-nap.sh`

Law copies (same ideas, paths fixed for gositeme home):
  /home/gositeme/law/alfred-build-preflight.sh
  /home/gositeme/law/alfred-kernel-download-sources.sh
  /home/gositeme/law/alfred-remote-apt-live-build.sh
  /home/gositeme/law/alfred-build-on-ubuntu.sh
