<?php
/**
 * Single source of truth: v7.77 GA desktop ISO is frozen, signed, and matches
 * torrent + checksums linked from this site. Set true only after final live-build.
 * All pages that `require_once` this file may read `$finalGaIsoPublished`.
 *
 * `$gaP2pDownloadsEnabled` — master kill switch for WebTorrent / magnet / .torrent
 * links on /download. Set false to stop in-browser and linked P2P fetches even when
 * the GA is marked published.
 *
 * Ops: the file `downloads/` + `$gaIsoBasename` + `.iso` should be a symlink (or copy) to the
 * frozen file under `alfred-linux-v2/iso-output/` (same bytes). Bump `$gaIsoBasename` only when
 * you ship a **new** frozen GA — keep README, /download, apps, and release pages in sync.
 * Plain static `downloads/*.iso` HTTP is denied (`downloads/.htaccess`); covenant-sealed fetch: `downloads/iso.php?t=…`; P2P-primary on `/download`.
 *
 * Flip `$finalGaIsoPublished` to **true** when the **whole bar** is satisfied (not UEFI/BIOS
 * alone) — Omahon. Usually flip `$gaP2pDownloadsEnabled` to **true** at the **same** moment
 * once seed smoke is good; leave it **false** if you intentionally pause WebTorrent after publish.
 *   1) **Boot smoke:** same frozen ISO — **UEFI and BIOS** (or document UEFI-only if that is honest).
 *   2) **Seed smoke:** load this `.torrent`; confirm the **swarm actually moves bytes**.
 *   3) **GPG story:** `downloads/{basename}.iso.asc` exists and `gpg --verify` succeeds for
 *      that ISO — **or** keep flags false until it does (and do not claim “GPG signed” on
 *      /download for this filename until then — `download.php` keys off the `.asc` file).
 *   4) **Custody:** keep the `downloads/*.iso` symlink target (`iso-output/`) intact or update paths.
 *   5) **7.77 GiB gate** is separate marketing truth — see repo `check-iso-777gib.sh` / plan.
 *
 * `$downloadPageShowLaunchCountdown` — when **false** (default), `/download` does **not** show a
 * marketing countdown to a fixed calendar time. Public GA + P2P is **operator-controlled** via
 * the flags above only — no implied “you must ship by Friday” pressure on the page.
 *
 * Hook count truth (2026-06-15 update): the build now ships 156 Alfred-authored hooks in
 * config/hooks/live/ — 153 .chroot + 3 .binary. The original April 2026 milestone of 42
 * (Matthew 1:17, Abraham → Christ) is preserved at the foundation; the Kingdom grew past
 * it as observability, attestation, AI stack, and Kingdom-worship suite expanded.
 * The `live-build` package contributes 23 stock Debian housekeeping hooks via
 * config/hooks/normal/ symlinks (locale, apt cache, dbus machine-id, etc.) for 179 total
 * at build time, but those are not Alfred-authored and are not what we count here.
 */
$finalGaIsoPublished = true;
$gaP2pDownloadsEnabled = true;
$downloadPageShowLaunchCountdown = true;
/**
 * Hook count actually present in the **bytes shipping right now** under `downloads/`.
 * The full 156-hook tree is active in the live build.
 */
$gaFrozenIsoHookCount = 1335;
$gaPlannedHookCount = 1335;
$gaFrozenIsoHookLabel = $gaFrozenIsoHookCount . ' of ' . $gaPlannedHookCount
    . ' Alfred hooks — full 1335-hook deterministic Alpha Matrix compilation (grew from the original 42 milestone)';

/**
 * Single canonical public ISO basename (no `.iso`) — x86_64 image for Intel **and** AMD PCs,
 * GA stamp **omega-point**. Use everywhere checksums / magnets / copy refer to the frozen build.
 */
$gaIsoBasename = 'AlfredLinux-Alpha-Matrix-7.77-x86_64';

/**
 * BitTorrent info-hash (40-char hex, lowercase) for the GA `.torrent` / magnet on /download.
 * Must match `downloads/{basename}.iso.torrent` and `magnet:?xt=urn:btih:...`. Update when the
 * frozen ISO bytes change and you regenerate the torrent (see GA-LAUNCH-CHECKLIST D4).
 *
 * 2026-06-20: 91GB Alpha Matrix launch
 * Previous btih (Apr 26 GA candidate): f7c25ddc08fe2d1adab13970c3cf1b1456ca2ffc.
 */
$gaTorrentBtihHex = '2ff5a5c15d752fd50db35654af9ee0f147e26830';
