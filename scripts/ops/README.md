# scripts/ops — Build host operator pipeline

Tracked copies of the three scripts the build host actually runs out of
`/home/gositeme/law/` (referenced by absolute path in
`/etc/systemd/system/alfred-night-shift.service`).

| script | what it does |
|---|---|
| `alfred-night-shift.sh` | systemd entrypoint: waits on the build container, runs smoke + restage, auto-requeues up to 2 retries via ABCP, and writes per-attempt diagnostics (`attempt-diagnostics-a<attempt>.log`, `smoke-a<attempt>.log`, `restage-a<attempt>.log`) on failures. |
| `smoke-test-iso.sh` | Mounts a fresh ISO, validates `/etc/alfred` is in the squashfs and the ISO mtime is fresher than `THRESHOLD`. Listing uses **`unsquashfs -ll`** paths (`squashfs-root/etc/...`), not fixed column regexes. |
| `post-build-restage.sh` | Hardlinks fresh ISO into `public_html/downloads/`, computes SHA-512 + BLAKE3 + SHA-256, regenerates `.torrent`, computes new btih, patches `$gaTorrentBtihHex` in `ga-release-state.php`, GPG-signs the ISO + every SUMS file, and bumps `$gaFrozenIsoHookCount` based on hook stamps in the squashfs. |
| `night-shift-watchdog-email.sh` | Cron-friendly watchdog: emails `commander@gositeme.com` once per **new** `night-shift-DONE.txt` or `night-shift-FAIL.txt` mtime. First run records current mtimes without sending (no backlog burst). Requires a working local MTA (`mail(1)`); see `night-shift-watchdog.log`. |
| `alfred-clear-stale-pipeline-markers.sh` | When you start a **new** `lb-docker` container but `night-shift-FAIL.txt` still exists from an old exhausted-retry run, this removes the stale FAIL (backup under **`/home/gositeme/law/night-shift-marker-backups/`** or `ALFRED_CLEAR_BACKUP_DIR`), resets `night-shift-state.txt`, and refreshes `last-lb-docker.json` via `alfred_status_json_waiting`. Requires `--yes` or `ALFRED_PIPELINE_CLEAR_FORCE=1` and a **Running** container named in `lb-docker.containername`. |
| `alfred-finalize-nap-json.sh` | When **`[inner] lb build finished … exit=0`** is in the log and at least one **`*.iso`** exists under **`build/`** or **`iso-output/`**, but **`last-lb-docker.json`** is still **`waiting_for_container` / `pending`** (Docker or **`watch-lb-docker-build.sh`** wedged), this script **writes `phase=done`** + **`nap_ok`** so **night-shift** can continue to smoke/restage. Refuses if the named container still exists in Docker (override with **`ALFRED_FINALIZE_NAP_IGNORE_RUNNING=1`** only if you know what you are doing). |
| `recover-night-shift.sh` | One-command rescue helper: stops **`alfred-night-shift`**, kills stale watcher processes, optionally restarts Docker / removes the active build container / clears stale `build/*.iso`, finalizes `last-lb-docker.json` when inner build already succeeded, then restarts the service and prints status. |
| `extract-inner-failure.sh` | Writes a focused incident artifact from the current inner run slice in `lb-docker-build.log`: matched fatal/error lines plus the latest context tail. Night-shift stores this as `night-shift-logs/inner-failure-a<attempt>.log` on failed attempts. |
| `night-shift-heartbeat-guard.sh` | Guard for watcher stalls: if `alfred-night-shift` is active, heartbeat is stale, and status phase is still `waiting_for_container`, it auto-runs `recover-night-shift.sh` (with cooldown + lock) to self-heal wedged watch states. |
| `write-ops-event.sh` | Appends structured JSONL ops events at `/home/gositeme/law/alfredlinux-com-source-live/night-shift-logs/ops-events.jsonl` and updates compact latest-event JSON at `/home/gositeme/law/alfred-build-control-plane/last-ops-event.json` for dashboards. |

## One-command recovery

```bash
sudo bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/recover-night-shift.sh --clear-build-iso
```

Useful flags:

- `--restart-docker` — restart Docker before recovery
- `--kill-container` — remove the active `alfred-lb-build-*` container
- `--clear-build-iso` — delete stale `build/*.iso` before the next run
- `--no-restart` — stop/finalize only, do not restart `alfred-night-shift`

## Unstick `last-lb-docker.json` (Dell Watch **nap_watch_json** BAD)

If **`last-lb-docker.json`** never left **`waiting_for_container`** after inner success, **`alfred-night-shift.sh`** (current `main`) will run **`alfred-finalize-nap-json.sh` automatically** after `watch-lb-docker-build.sh` when JSON is still not **`phase=done`** — **`sudo systemctl restart alfred-night-shift`** is enough once the updated script is deployed. You can still run finalize by hand:

```bash
sudo systemctl restart docker
sleep 8
sudo -u gositeme bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/alfred-finalize-nap-json.sh
sudo systemctl restart alfred-night-shift
```

## Cron: watcher heartbeat auto-recovery

After installing the script under `/home/gositeme/law/`:

```bash
chmod +x /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/night-shift-heartbeat-guard.sh
(crontab -l 2>/dev/null; echo '*/5 * * * * ALFRED_WATCH_HEARTBEAT_MAX_AGE_SEC=900 ALFRED_WATCH_RECOVERY_MIN_INTERVAL_SEC=1800 /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/night-shift-heartbeat-guard.sh >/dev/null 2>&1') | crontab -
```

State files: `/home/gositeme/law/.night-shift-heartbeat-guard.state`, `/home/gositeme/law/night-shift-heartbeat-guard.log`.

Guard-triggered recoveries stamp `last-lb-docker.json` with `recovery_reason_code`, `recovery_actor`, `recovery_ts`, and `last_recovery` so dashboards can explain self-heal actions.

Dell Watch compact feed: `/home/gositeme/law/alfred-build-control-plane/last-ops-event.json` always contains the latest orchestration event (`source`, `event`, `reason`, `container`, `phase`, `ts`).

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
sudo install -o gositeme -g gositeme -m 755 \
  scripts/ops/alfred-finalize-nap-json.sh /home/gositeme/law/alfred-finalize-nap-json.sh
sudo systemctl restart alfred-night-shift
```

**Recommended on the build host:** keep **thin `exec` wrappers** under `/home/gositeme/law/` for **`alfred-night-shift.sh`**, **`smoke-test-iso.sh`**, and **`post-build-restage.sh`** so systemd and cron always run the repo versions (rolling ISO window, token file, requeue safety). After `lb-docker-build.sh detach`, start the waiter with **`sudo systemctl start alfred-night-shift`** (enable the unit if you want it easy to start after boot).

## ABCP token (auto-requeue)

`alfred-night-shift.sh` reads **`ABCP_TOKEN`** from the environment or from **`/home/gositeme/law/.alfred-abcp-token`** (mode **600**). The token is not stored in git.

**ABCP must be listening** on **`http://127.0.0.1:18787`** (see `alfred-build-control-plane`). If smoke/restage fails and night-shift tries to requeue, **`ctl.py`** will get **`Connection refused`** and you will see **`could not requeue build via ABCP`** — bring ABCP up, then clear **`night-shift-FAIL.txt`** and **`sudo systemctl restart alfred-night-shift`**:

```bash
bash /home/gositeme/law/alfred-build-control-plane/start-abcp.sh
curl -sf http://127.0.0.1:18787/healthz && echo OK
```

## Stale FAIL after a new detach build

If `night-shift-FAIL.txt` still says exhausted retries but **`docker ps`** shows a **new** `alfred-lb-build-*` container running:

```bash
bash /home/gositeme/law/alfredlinux-com-source-live/scripts/ops/alfred-clear-stale-pipeline-markers.sh --yes
sudo systemctl restart alfred-night-shift   # optional; picks up fresh state
```

If **`night-shift-state.txt`** / **`night-shift-FAIL.txt`** are **root-owned**, the script tries **`sudo -n`** (passwordless sudo) to update/remove them; otherwise it prints a **`sudo tee` / `chown`** hint. **`last-lb-docker.json`** is refreshed regardless when the repo dir is writable.

## Tunables

- **Rolling ISO freshness:** `ALFRED_ISO_MAX_AGE_DAYS` (default **14**) and optional `ALFRED_ISO_MIN_MTIME_EPOCH` in night-shift, smoke, and restage — ISO must be newer than the rolling window (no manual `THRESHOLD` bump each cycle).
- **Smoke vs inner build:** `smoke-test-iso.sh` compares **`live-image-amd64.hybrid.iso` mtime** to the last **`[inner] lb build finished … exit=0`** timestamp in **`lb-docker-build.log`** (default slack **`ALFRED_SMOKE_INNER_MTIME_SLACK_SEC=600`**). If the hybrid on disk is still an **older GA** while the log shows a **new** inner finish, smoke fails fast instead of MISS **`etc/alfred`**. Set **`ALFRED_SMOKE_SKIP_INNER_LOG_MTIME_CHECK=1`** to skip that gate (not recommended).
- `MAX_RETRIES` (in night-shift) — extra ABCP requeues beyond the in-flight build.
- **`ALFRED_ABCP_QUEUE_RETRIES`** (default **8**) / **`ALFRED_ABCP_QUEUE_RETRY_SLEEP_SEC`** (default **4**) — when `ctl.py queue-build` hits **Connection refused** / **URLError** / timeouts to **`ABCP_BASE`**, night-shift retries before giving up (covers ABCP waking slowly after smoke failure).
- **`ALFRED_DOCKER_INSPECT_TIMEOUT_SEC`** (default **30**) — every `docker inspect` in **`watch-lb-docker-build.sh`** (and helpers used by night-shift / triage scripts) runs under **`timeout(1)`** so a wedged Docker daemon cannot leave **`last-lb-docker.json`** stuck at **`waiting_for_container`** while **`lb-docker-build.log`** already shows **`[inner] lb build finished … exit=0`**. Set to **0** to use bare **`docker inspect`** (legacy).
- Night-shift now prunes stale `watch-lb-docker-build.sh` PIDs before launching a new watch pass, reducing duplicate watcher races around `last-lb-docker.json`.
- On failed attempts, night-shift writes `night-shift-logs/inner-failure-a<attempt>.log` via `extract-inner-failure.sh` for postmortem triage.
- `GPG_KEY` (in post-build-restage) — release signing key. Currently `41E166075B0F95205839E41B32BCEDE8C8DD8B00`.
- `B3SUM_BIN` fallback chain in restage: `/usr/local/bin`, `/usr/bin`, `/home/gositeme/.cargo/bin`.

## Truth invariants

- `$gaIsoBasename` in the public site **only changes** when bytes change *and* a new
  date is intended. The restage rewrites SUMS / `.torrent` / btih to match the new
  bytes but keeps the basename stable until you intentionally rename.
- All four SUMS files (downloads/SHA256SUMS-7.77.txt + releases/7.77/SHA{256,512}SUMS + BLAKE3SUMS) end up signed.
- `.iso.asc` exists beside the ISO so `download.php` and `dell-partner.php` can show "GPG-signed" honestly.

## `$gaFrozenIsoHookCount` (Forge “Hooks Shipping”)

`post-build-restage.sh` bumps **`$gaFrozenIsoHookCount`** using **`unsquashfs -ll`** and a **grep** for legacy paths (`/var/lib/alfred/hook-stamps/…`, `/etc/alfred/hooks/…`, `/var/log/alfred-hook-*`). Most current **`0100–0725`** hooks **do not create those paths**, so the proxy often returns a **small number (e.g. 2)** even when all **1335** chroot hooks ran. If the proxy is **fewer than 30**, the script **does not** auto-patch the PHP var (manual review).

**Canonical truth:** `bash scripts/release-integrity.sh check-repo` and **`ls config/hooks/live/*.hook.chroot | wc -l`** (expect **1335**). After you confirm **`lb build` / chroot logs** show the full hook sequence, set **`$gaFrozenIsoHookCount = 1335`** in `includes/ga-release-state.php` (or extend hooks to emit consistent stamps and tighten the grep — follow-up work).

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

Hook progress still comes from live-build’s `Executing hook config/hooks/…` lines in the same log. Dell Watch also shows the last few of those lines verbatim and a **1335-segment bar** ordered like `config/hooks/live/*.hook.chroot`; the tail window size is the PHP constant **`DW_BUILD_LOG_TAIL_LINES`** in `dell-watch.php` (currently **1200** lines). The first line of **`night-shift-state.txt`** is echoed in the Finish line panel when set.
