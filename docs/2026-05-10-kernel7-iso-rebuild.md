# 2026-05-10 kernel 7.0.4 ISO rebuild note

## What this commit fixes
- Fixes the kernel fallback loop in `config/hooks/0999-kernel7-menu.hook.binary`.
- Adds required runtime packages to `config/package-lists/alfred-b2.list.chroot`:
  - `initramfs-tools`
  - `kmod`
  - `bibletime`
  - `xiphos`
  - `diatheke`
  - `sword-text-kjv`
  - `sword-comm-mhcc`

## Root cause of the boot failure
The previous binary hook created fallback symlinks like this:
- `vmlinuz-7-fallback -> vmlinuz`
- `initrd.img-7-fallback -> initrd.img`

Later in the same hook it repointed the top-level names back to the fallback names:
- `vmlinuz -> vmlinuz-7-fallback`
- `initrd.img -> initrd.img-7-fallback`

That created a circular loop. When the ISO was packed, the kernel and initrd landed as zero-byte files in `binary/live/`, which produced the boot failure reported as `bad file number`.

## Permanent fix
The hook now copies the readable top-level kernel artifacts into real fallback files with `cp -fL` before it repoints the top-level names. The resulting layout is:
- `binary/live/vmlinuz -> vmlinuz-7-fallback`
- `binary/live/vmlinuz-7-fallback` = real file
- `binary/live/initrd.img -> initrd.img-7-fallback`
- `binary/live/initrd.img-7-fallback` = real file

## Authoritative forge path
Run the build through the repo entrypoint:

```bash
bash scripts/lb-docker-inner-build.sh
```

That script already:
- pins `SOURCE_DATE_EPOCH`
- syncs canonical config into `build/config`
- resets stale `chroot`, `binary`, and `.build`
- runs `lb clean --all`, `lb config`, and `lb build`
- publishes the fresh ISO to `iso-output/live-image-amd64.hybrid.iso`

## Recommended rebuild flow
From the repo root:

```bash
bash scripts/lb-docker-inner-build.sh
```

After the build finishes, verify:

```bash
ls -lh build/binary/live/vmlinuz* build/binary/live/initrd*
file build/binary/live/vmlinuz build/binary/live/initrd.img
unsquashfs -s build/binary/live/filesystem.squashfs | head
```

Expected:
- `vmlinuz` and `initrd.img` are not empty
- fallback targets are real files, not loops
- `filesystem.squashfs` has a valid header

## Emergency recovery if the build tree is already good
If `chroot/boot/vmlinuz-7.0.4` and `chroot/boot/initrd.img-7.0.4` are correct but an already-packed ISO is bad, repair the live tree before repacking:

```bash
BUILD=/home/gositeme/law/alfredlinux-com-source-live
sudo rm -f $BUILD/binary/live/vmlinuz $BUILD/binary/live/vmlinuz-7-fallback
sudo rm -f $BUILD/binary/live/initrd.img $BUILD/binary/live/initrd.img-7-fallback
sudo cp $BUILD/chroot/boot/vmlinuz-7.0.4 $BUILD/binary/live/vmlinuz
sudo cp $BUILD/chroot/boot/vmlinuz-7.0.4 $BUILD/binary/live/vmlinuz-7-fallback
sudo cp $BUILD/chroot/boot/initrd.img-7.0.4 $BUILD/binary/live/initrd.img
sudo cp $BUILD/chroot/boot/initrd.img-7.0.4 $BUILD/binary/live/initrd.img-7-fallback
```

Then repack with xorriso. The successful BIOS-only repack used:

```bash
sudo xorriso -as mkisofs \
  -iso-level 3 \
  -full-iso9660-filenames \
  -volid ALFRED7 \
  -appid Alfred-Linux \
  -publisher alfredlinux.com \
  -preparer live-build \
  -eltorito-boot isolinux/isolinux.bin \
  -eltorito-catalog isolinux/boot.cat \
  -no-emul-boot \
  -boot-load-size 4 \
  -boot-info-table \
  -output /desired/output.iso \
  binary/
```

## Important note about UEFI
This emergency repack was BIOS-only because the packed tree being recovered did not contain `boot/grub/efi.img`. A clean rebuild through `scripts/lb-docker-inner-build.sh` is the authoritative path for a full release artifact.

## Related host-level prerequisite
There is still a machine-local live-build patch documented in `docs/2026-05-07-live-build-local-repo-note.md`. If that host patch is lost, rebuilds may fail even when this repo is correct.
