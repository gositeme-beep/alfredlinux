# scripts/ops — Build host operator pipeline

Tracked copies of the three scripts the build host actually runs out of
`/home/gositeme/law/` (referenced by absolute path in
`/etc/systemd/system/alfred-night-shift.service`).

| script | what it does |
|---|---|
| `alfred-night-shift.sh` | systemd entrypoint: waits on the build container, runs smoke + restage, auto-requeues up to 2 retries via ABCP. |
| `smoke-test-iso.sh` | Mounts a fresh ISO, validates `/etc/alfred` is in the squashfs and the ISO mtime is fresher than `THRESHOLD`. |
| `post-build-restage.sh` | Hardlinks fresh ISO into `public_html/downloads/`, computes SHA-512 + BLAKE3 + SHA-256, regenerates `.torrent`, computes new btih, patches `$gaTorrentBtihHex` in `ga-release-state.php`, GPG-signs the ISO + every SUMS file, and bumps `$gaFrozenIsoHookCount` based on hook stamps in the squashfs. |
| `night-shift-watchdog-email.sh` | Cron-friendly watchdog: emails `commander@gositeme.com` once per **new** `night-shift-DONE.txt` or `night-shift-FAIL.txt` mtime. First run records current mtimes without sending (no backlog burst). Requires a working local MTA (`mail(1)`); see `night-shift-watchdog.log`. |
| `alfred-clear-stale-pipeline-markers.sh` | When you start a **new** `lb-docker` container but `night-shift-FAIL.txt` still exists from an old exhausted-retry run, this removes the stale FAIL (backup under **`/home/gositeme/law/night-shift-marker-backups/`** or `ALFRED_CLEAR_BACKUP_DIR`), resets `night-shift-state.txt`, and refreshes `last-lb-docker.json` via `alfred_status_json_waiting`. Requires `--yes` or `ALFRED_PIPELINE_CLEAR_FORCE=1` and a **Running** container named in `lb-docker.containername`. |

## Cron: build completion email

After installing the script under `/home/gositeme/law/`:

```bash
chmod +x /home/gositeme/law/night-shift-watchdog-email.sh
(crontab -l 2>/dev/null; echo '*/5 * * * * /home/gositeme/law/night-shift-watchdog-email.sh >/dev/null 2>&1') | crontab -
```

State file: `/home/gositeme/law/.night-shift-watchdog.state` (mode 600). Optional env: `NIGHT_SHIFT_WATCHDOG_TO`, `NIGHT_SHIFT_WATCHDOG_LOG`.

## Sync to the runtime location

These files in the repo are the source of truth. To deploy:

```bash
sudo install -o gositeme -g gositeme -m 755 \
  scripts/ops/alfred-night-shift.sh   /home/gositeme/law/alfred-night-shift.sh
sudo install -o gositeme -g gositeme -m 755 \
  scripts/ops/smoke-test-iso.sh       /home/gositeme/law/smoke-test-iso.sh
sudo install -o gositeme -g gositeme -m 755 \
  scripts/ops/post-build-restage.sh   /home/gositeme/law/post-build-restage.sh
sudo systemctl restart alfred-night-shift
```

**Recommended on the build host:** keep **thin `exec` wrappers** under `/home/gositeme/law/` for **`alfred-night-shift.sh`**, **`smoke-test-iso.sh`**, and **`post-build-restage.sh`** so systemd and cron always run the repo versions (rolling ISO window, token file, requeue safety). After `lb-docker-build.sh detach`, start the waiter with **`sudo systemctl start alfred-night-shift`** (enable the unit if you want it easy to start after boot).

## ABCP token (auto-requeue)

`alfred-night-shift.sh` reads **`ABCP_TOKEN`** from the environment or from **`/home/gositeme/law/.alfred-abcp-token`** (mode **600**). The token is not stored in git.

## Stale FAIL after a new detach build

If `night-shift-FAIL.txt` still says exhausted retries but **`docker ps`** shows a **new** `alfred-lb-build-*` container running:

```bash
bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/alfred-clear-stale-pipeline-markers.sh --yes
sudo systemctl restart alfred-night-shift   # optional; picks up fresh state
```

If **`night-shift-state.txt`** was written by systemd as **root**, the script refreshes **`last-lb-docker.json`** anyway but may skip rewriting state until you run **`sudo chown gositeme:gositeme`** on the marker files once, or use the `sudo tee` hint printed by the script.

## Tunables

- **Rolling ISO freshness:** `ALFRED_ISO_MAX_AGE_DAYS` (default **14**) and optional `ALFRED_ISO_MIN_MTIME_EPOCH` in night-shift, smoke, and restage — ISO must be newer than the rolling window (no manual `THRESHOLD` bump each cycle).
- `MAX_RETRIES` (in night-shift) — extra ABCP requeues beyond the in-flight build.
- **`ALFRED_SMOKE_SCRIPT` / `ALFRED_RESTAGE_SCRIPT`** — override paths to smoke and restage (defaults: repo `scripts/ops/*.sh`).
- `GPG_KEY` (in post-build-restage) — release signing key. Currently `41E166075B0F95205839E41B32BCEDE8C8DD8B00`.
- `B3SUM_BIN` fallback chain in restage: `/usr/local/bin`, `/usr/bin`, `/home/gositeme/.cargo/bin`.

## Truth invariants

- `$gaIsoBasename` in the public site **only changes** when bytes change *and* a new
  date is intended. The restage rewrites SUMS / `.torrent` / btih to match the new
  bytes but keeps the basename stable until you intentionally rename.
- All four SUMS files (downloads/SHA256SUMS-7.77.txt + releases/7.77/SHA{256,512}SUMS + BLAKE3SUMS) end up signed.
- `.iso.asc` exists beside the ISO so `download.php` and `dell-partner.php` can show "GPG-signed" honestly.

## `$gaFrozenIsoHookCount` (Forge “Hooks Shipping”)

`post-build-restage.sh` bumps **`$gaFrozenIsoHookCount`** using **`unsquashfs -ll`** and a **grep** for legacy paths (`/var/lib/alfred/hook-stamps/…`, `/etc/alfred/hooks/…`, `/var/log/alfred-hook-*`). Most current **`0100–0725`** hooks **do not create those paths**, so the proxy often returns a **small number (e.g. 2)** even when all **42** chroot hooks ran. If the proxy is **fewer than 30**, the script **does not** auto-patch the PHP var (manual review).

**Canonical truth:** `bash scripts/release-integrity.sh check-repo` and **`ls config/hooks/live/*.hook.chroot | wc -l`** (expect **42**). After you confirm **`lb build` / chroot logs** show the full hook sequence, set **`$gaFrozenIsoHookCount = 42`** in `includes/ga-release-state.php` (or extend hooks to emit consistent stamps and tighten the grep — follow-up work).

Until signing runs, **“GPG unsigned”** on the dashboard is expected: complete **`post-build-restage.sh`** GPG steps (or your release checklist) so `.asc` + SUMS signatures match the published ISO.

## Dell Watch ↔ inner build log markers

Commander **Dell Watch** (`veil/dell-watch.php` on GoSiteMe) tails **`lb-docker-build.log`** and greps stable substrings emitted by **`scripts/lb-docker-inner-build.sh`** (container `/work` = bind-mounted repo). Source of truth for wording is that script; do not rename `[inner] …` lines without updating Dell Watch.

| Marker (substring) | Meaning |
|---|---|
| `[inner] sync canonical` | Canonical tree rsynced into `build/config`. |
| `[inner] lb clean` | Full `lb clean` after chroot reset. |
| `[inner] lb config` | `lb config` completed. |
| `[inner] lb build starting` | `lb build` entered (night-shift also keys off this vs stale `E:` lines). |
| `[inner] lb build finished` + `exit=0` | Inner live-build succeeded. |
| `[inner] ALFRED_ALLOW_SSH_PASSWORD_AUTH=` | Logged SSH policy for that ISO build (`0` keys-only default). |

Hook progress still comes from live-build’s `Executing hook config/hooks/…` lines in the same log. Dell Watch also shows the last few of those lines verbatim and a **42-segment bar** ordered like `config/hooks/live/*.hook.chroot`; the tail window size is the PHP constant **`DW_BUILD_LOG_TAIL_LINES`** in `dell-watch.php` (currently **1200** lines). The first line of **`night-shift-state.txt`** is echoed in the Finish line panel when set.
