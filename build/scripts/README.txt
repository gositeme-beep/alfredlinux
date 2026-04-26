Alfred Linux — GA build helper scripts (Forge mirror)
=====================================================

The canonical `build-unified.sh` and full live-build tree for the public site live in the
**alfredlinux.com** repository under `build/scripts/`.

This folder mirrors the **small, copy-safe** operator files so Forge clones stay aligned:

  • `ga-iso-release.conf` — basename + btih; must match `includes/ga-release-state.php` on site.
  • `verify-ga-publish-alignment.sh` — run on the web host after ISO + `.torrent` + SUMS land.
  • `check-iso-777gib.sh` — size gate (~7.77 GiB binary); same as site `build/scripts/` copy.
  • `smoke-test-iso.sh` — runs check-iso + optional `VERIFY_SITE=1` verify (mirrors `/home/ubuntu/smoke-test-iso.sh` pattern in /reseal).

Verify scripts inspect **on-disk** `downloads/` (symlink to iso-output is fine). They do not
curl plain `/downloads/*.iso` — that must stay **denied** on the live vhost (`downloads/.htaccess`).

After editing the canonical copies on the site repo, refresh these files here (or vice versa)
before tagging a GA release.

Run verify on the live web host (paths point at public_html):

  ALFRED_SITE_ROOT=/home/gositeme/domains/alfredlinux.com/public_html \\
    bash build/scripts/verify-ga-publish-alignment.sh
