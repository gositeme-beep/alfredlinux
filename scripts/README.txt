Alfred Linux — helper scripts (ISO / builder)
==============================================

security-audit.sh
  Static sweep of config/hooks/live/*.hook.chroot (SSH footguns, curl|sh, etc.).
  Optional: ALFRED_SHELLCHECK_ALL=1 to shellcheck every scripts/*.sh.
    bash scripts/security-audit.sh
  Wave checklist: scripts/SECURITY-WAVES.txt
  GoForge Actions (canonical): .gitea/workflows/security-audit.yml — https://alfredlinux.com/forge/
  Mirror on disk: .forgejo/workflows/security-audit.yml (run bash scripts/sync-forgejo-actions-yaml.sh after edits)
  Optional GitHub mirror: .github/workflows/security-audit.yml (keep aligned with .gitea if used)

alfred-repo-health.sh
  Runs `release-integrity.sh check-repo` then `security-audit.sh` (exit non-zero if either fails).
  Optional: `ALFRED_LINUX_REPO=/path/to/checkout` when invoked from elsewhere.
    bash scripts/alfred-repo-health.sh
  Systemd user units (edit WorkingDirectory if your clone path differs):
    contrib/systemd/user/alfred-linux-repo-health.{service,timer}
    systemctl --user enable --now alfred-linux-repo-health.timer

safe-operator-once.sh
  On the **build/web host**, run **once with sudo**: installs `shellcheck`, copies
  canonical `build-assets/forge/docs/index.html` to live Apache path (apache:apache),
  and normalizes `public_html/forge/` permissions. See `docs/SERVER-GIT-REMOTES.txt`.

iso-preflight.sh
  Run before `lb build`. Fails if linux-image-7.0.1*.deb missing from
  config/packages.chroot/ (kernel debs + lists). Runs **`stage-kernel-debs-for-iso.sh`** first (archive under
  **`build-assets/kernel-7.0.1-debs/`**, **`ALFRED_KERNEL_DEBS_ARCHIVE`**, or **`KERNEL_WORK`**).
  When `docker` is in PATH, warns if multiple `alfred-lb-build-*` containers run. Usage:
    bash scripts/iso-preflight.sh
  Run `lb` from `build/` (see ALFRED-LINUX-BUILD-TEST.txt). An empty `build/auto/` is normal
  unless you maintain an executable `build/auto/config` script.

sync-hooks-to-build.sh
  Hooks only: `config/hooks/live/*.hook.chroot` → `build/config/hooks/`.
  Drops flat `*.hook.chroot` not in live/; clears nested `hooks/live/` when permitted.
  If Docker left root-owned `build/config/hooks/live/`, sync-hooks tries
  `docker run … alpine rm -rf` on that bind mount; otherwise `sudo rm -rf build/config/hooks/live`.
    bash scripts/sync-hooks-to-build.sh

sync-canonical-to-build.sh
  **Full staging:** hooks + `config/package-lists/*.list.chroot` + `config/packages.chroot/*`
  + selective or full `build-assets/` → `build/config/includes.chroot/build-assets/`.
  Docker inner runs this with `ALFRED_FULL_BUILD_ASSETS=1` before `lb config`.
  Also syncs repo `local/` → `build/local/` when present (live-build local hooks).
    bash scripts/sync-canonical-to-build.sh
    ALFRED_FULL_BUILD_ASSETS=1 bash scripts/sync-canonical-to-build.sh   # mirror all media
  Ship-gap audit: docs/ISO-STAGING-SHIP-GAPS.txt

release-integrity.sh
  After ISOs exist in one directory: SHA256SUMS + SHA512SUMS, then GPG-detached sign.
    scripts/release-integrity.sh hash *.iso
    scripts/release-integrity.sh sign
  From repo root: `scripts/release-integrity.sh check-repo` (bible_tongues vs languages.conf; hooks==42).
  Verifiers: `scripts/release-integrity.sh verify` or manual gpg + sha256sum -c.
  See README.txt "VERIFICATION — TRUST BUT VERIFY".

kernel-download-7.0.1.sh
  Downloads linux-7.0.1.tar.xz + patch-7.0.1.xz into ../kernel-7.0.1-work/ (or KERNEL_WORK=…).
  Verifies SHA256 against cdn.kernel.org `sha256sums.asc` (skip only with
  `ALFRED_SKIP_KERNEL_SHA256_VERIFY=1` — not for production). Does not compile.
  Then follow config/packages.chroot/README-KERNEL7.txt.
  Kernel security scope + maintainer “best effort”: docs/KERNEL-7.0.1-SECURITY-MANIFEST.txt
  Supply-chain / trojan-path audit: docs/KERNEL-7.0.1-SUPPLY-CHAIN-AUDIT.txt

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
  After .deb exist under KERNEL_WORK: strict copy into `config/packages.chroot/` (delegates to
  `stage-kernel-debs-for-iso.sh --strict`). Then `iso-preflight.sh`.

stage-kernel-debs-for-iso.sh
  Idempotent: unpack **`linux-7.0.1-debs-for-iso.tar.gz`** (or `.tar.zst`) from
  **`build-assets/kernel-7.0.1-debs/`** or **`ALFRED_KERNEL_DEBS_ARCHIVE`**, else copy from
  **`KERNEL_WORK`**. Used by **`iso-preflight.sh`** automatically.

pack-kernel-debs-archive.sh
  On the machine that already built the kernel: create **`linux-7.0.1-debs-for-iso.tar.gz`** under
  **`build-assets/kernel-7.0.1-debs/`** (gitignored) for another host / thin checkout. See
  **`build-assets/kernel-7.0.1-debs/README.txt`**.

lb-docker-build.sh + lb-docker-inner-build.sh
  **No host sudo:** privileged Debian container runs `lb build` with repo at `/work`.
    bash scripts/lb-docker-build.sh detach
  Log: `lb-docker-build.log` (repo root). Name: `lb-docker.containername`.
  Requires Docker; can take many hours; `--privileged` is intentional for mounts/chroot.
  Default `ALFRED_LB_DOCKER_FLOCK_BLOCKING=1` queues overlapping starts on `build/.alfred-lb-docker-build.lock`
  (Reseal / ABCP / Forge hooks must not run two ISO builds on the same bind mount). Use `=0` for fail-fast.

watch-lb-docker-build.sh
  After `detach`, **wait until the container exits**, print summary + ISO paths + log tail.
    bash scripts/watch-lb-docker-build.sh
  Optional: `--status-json /path/state.json` (machine-readable), `--webhook URL` (POST JSON on exit).
  With `--status-json`, a non-blocking **flock** on `.lb-docker-watch.lock` prevents two parallel
  watchers corrupting the same JSON; second instance exits 3. `ALFRED_WATCH_NO_FLOCK=1` bypasses.
  Optional: `ALFRED_DOCKER_WAIT_MAX_SEC=43200` (for example) caps how long `docker wait` blocks; on
  timeout the script records `docker_exit=unknown` (pair with night-shift log fatal detection).
  If `git push` to the forge says `refs/heads/main.lock` / remote rejected, another push or GC
  holds the repo — retry after a few seconds; on the Gitea host remove a **stale** `main.lock` only
  if no real `git` process is using that repository.
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
