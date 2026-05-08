# 2026-05-07 live-build local package repo note

## Repo change committed
- Added `--apt-secure false` to `auto/config`.

## Host-level patch applied outside repo
- Patched `/usr/lib/live/build/lb_chroot_archives`.
- Changed generated chroot source entry from:
  - `deb file:/root/packages ./`
- To:
  - `deb [trusted=yes] file:/root/packages ./`

## Why
- The build failed in chroot APT stage with:
  - `E: The repository 'file:/root/packages ./ Release' is not signed.`
- `config/packages.chroot` causes live-build to generate a local APT repo under `/root/packages`.
- On this host/live-build version, `--apt-secure false` alone did not suppress that failure for the generated local repo source entry.

## Persistence warning
- The `/usr/lib/live/build/lb_chroot_archives` patch is machine-local and not tracked by git.
- Reinstalling or upgrading `live-build` may remove it and require reapplying the patch.
