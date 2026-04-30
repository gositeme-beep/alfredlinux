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

## Tunables

- `THRESHOLD` (in night-shift + smoke + restage) — bump after each cycle to reject stale prior builds.
- `MAX_RETRIES` (in night-shift) — extra ABCP requeues beyond the in-flight build.
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
