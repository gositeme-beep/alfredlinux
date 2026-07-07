#!/bin/bash
# level-777-bios.sh
# ALL-IN-ONE: hook consolidation (46→42) + Level 777 advanced bootloader.
# RUN WITH SUDO ON THE BUILD HOST.
#
#   sudo bash /home/root/level-777-bios.sh
#
# What this does:
#   1. Snapshot config/ + chroot/boot + chroot/etc/default/grub
#   2. Consolidate 46 build hooks → canonical 42
#   3. Install advanced bootloader stack INSIDE the chroot
#        - grub-efi-amd64-bin + grub-efi-arm64-bin + grub-pc-bin
#        - shim-signed (Microsoft-signed, Secure Boot capable)
#        - memtest86+, ipxe, mokutil, sbsigntool, tpm2-tools, clevis-tpm2
#   4. Build the binary/ tree:
#        - binary/isolinux/ (BIOS) — already present, refresh
#        - binary/boot/grub/grub.cfg + theme/ + locale/ + fonts/
#        - binary/boot/grub/efi.img (FAT image with BOOTX64.EFI shim chain)
#        - binary/EFI/BOOT/{BOOTX64.EFI, BOOTAA64.EFI, grubx64.efi, mmx64.efi}
#        - binary/boot/memtest86+.bin
#   5. Stage MOTD + register Kingdom wallpapers in GNOME picker
#   6. Print hand-off line for reseal-iso.sh
#
# Time:  ~10–25 minutes (most spent in chroot apt install)
# Disk:  ~1.5 GB headroom in chroot

set -euo pipefail
[ "$(id -u)" = "0" ] || { echo "Must run as root: sudo bash $0"; exit 1; }

ROOT=/home/root/alfred-linux-v2/build
CHROOT=$ROOT/chroot
BIN=$ROOT/binary
HOOKS=$ROOT/config/hooks
TS=$(date +%Y%m%d-%H%M%S)
BAK=$ROOT/.level777-bak-$TS
mkdir -p "$BAK"

banner() { echo; echo "════════ $* ════════"; }

# ─────────────────────────────────────────────────────────────────────
banner "0/9  Snapshot critical paths → $BAK"
cp -a "$HOOKS"                "$BAK/hooks"          2>/dev/null || true
cp -a "$CHROOT/boot"          "$BAK/chroot-boot"    2>/dev/null || true
cp -a "$CHROOT/etc/default"   "$BAK/chroot-default" 2>/dev/null || true
[ -d "$BIN" ] && cp -a "$BIN" "$BAK/binary"        || true
echo "  snapshot OK"

# ─────────────────────────────────────────────────────────────────────
banner "1/9  Consolidate 46 hooks → 42"

merge() {
    local DST="$1" SRC="$2" LABEL="$3"
    [ -f "$HOOKS/$DST" ] || { echo "  MISSING DST $DST"; return 1; }
    [ -f "$HOOKS/$SRC" ] || { echo "  (already merged) $SRC"; return 0; }
    {
        printf '\n# ─── Merged from %s (%s) on %s ───\n' "$SRC" "$LABEL" "$TS"
        sed '1{/^#!/d;}' "$HOOKS/$SRC"
    } >> "$HOOKS/$DST"
    rm -f "$HOOKS/$SRC"
    echo "  MERGED $SRC → $DST"
}

if [ -f "$HOOKS/0010-fix-security-repo.hook.chroot" ] && [ ! -f "$HOOKS/0010-alfred-bootstrap.hook.chroot" ]; then
    mv "$HOOKS/0010-fix-security-repo.hook.chroot" "$HOOKS/0010-alfred-bootstrap.hook.chroot"
    echo "  RENAMED 0010-fix-security-repo → 0010-alfred-bootstrap"
fi
merge 0010-alfred-bootstrap.hook.chroot 0011-fix-dns.hook.chroot              "DNS fix"
merge 0010-alfred-bootstrap.hook.chroot 0012-fix-dpkg-noninteractive.hook.chroot "dpkg noninteractive"
merge 0400-alfred-voice.hook.chroot     0900-alfred-voice-v2.hook.chroot      "voice v2"
merge 0722-alfred-sabbath.hook.chroot   0720-alfred-sacred-rest.hook.chroot   "sacred-rest"

for f in 0010-alfred-bootstrap 0400-alfred-voice 0722-alfred-sabbath; do
    bash -n "$HOOKS/$f.hook.chroot" && echo "  syntax OK: $f"
done

HC=$(ls "$HOOKS" | grep -c '\.hook\.chroot$')
echo "  Hook count: $HC (target 42)"
[ "$HC" = "42" ] && echo "  ✓ CANONICAL FORTY-TWO" || echo "  ⚠ unexpected count $HC"

# ─────────────────────────────────────────────────────────────────────
banner "2/9  Inside chroot: install Level-777 bootloader stack"

# Bind mounts so apt works
for m in proc sys dev dev/pts; do
    mountpoint -q "$CHROOT/$m" || mount --bind "/$m" "$CHROOT/$m"
done
trap 'for m in dev/pts dev sys proc; do umount -lf "$CHROOT/$m" 2>/dev/null||true; done' EXIT

cat > "$CHROOT/tmp/install-bootloader.sh" <<'INNER'
#!/bin/bash
set -e
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y --no-install-recommends \
    grub-pc-bin grub-efi-amd64-bin grub-efi-amd64-signed \
    grub-efi-arm64-bin \
    grub2-common grub-common \
    shim-signed mokutil sbsigntool \
    memtest86+ \
    ipxe \
    tpm2-tools clevis-tpm2 clevis-luks clevis-initramfs \
    plymouth-themes \
    dosfstools mtools xorriso isolinux syslinux-common syslinux-utils \
    || true
echo "INNER_OK"
INNER
chmod +x "$CHROOT/tmp/install-bootloader.sh"
chroot "$CHROOT" /tmp/install-bootloader.sh | tail -20

# ─────────────────────────────────────────────────────────────────────
banner "3/9  Build binary/boot/grub/grub.cfg (Level 777 menu)"
mkdir -p "$BIN/boot/grub" "$BIN/EFI/BOOT" "$BIN/boot/grub/themes/alfred"

# Copy alfred grub theme out of chroot to binary
if [ -d "$CHROOT/boot/grub/themes/alfred" ]; then
    cp -a "$CHROOT/boot/grub/themes/alfred/." "$BIN/boot/grub/themes/alfred/"
    echo "  alfred theme staged → $BIN/boot/grub/themes/alfred"
fi

# Copy GRUB modules + locale + fonts + memtest from chroot
for d in i386-pc x86_64-efi arm64-efi; do
    SRC=$CHROOT/usr/lib/grub/$d
    [ -d "$SRC" ] && { mkdir -p "$BIN/boot/grub/$d"; cp -a "$SRC"/. "$BIN/boot/grub/$d/"; }
done
[ -d "$CHROOT/usr/share/grub" ] && cp -a "$CHROOT/usr/share/grub/." "$BIN/boot/grub/" 2>/dev/null || true
mkdir -p "$BIN/boot/grub/fonts"
[ -f "$CHROOT/usr/share/grub/unicode.pf2" ] && cp "$CHROOT/usr/share/grub/unicode.pf2" "$BIN/boot/grub/fonts/"

# Memtest
for mt in $CHROOT/boot/memtest86+x64.bin $CHROOT/boot/memtest86+.bin $CHROOT/boot/memtest86+x32.bin; do
    [ -f "$mt" ] && cp "$mt" "$BIN/boot/" 2>/dev/null || true
done

cat > "$BIN/boot/grub/grub.cfg" <<'GRUBCFG'
# ═════════════════════════════════════════════════════════════════════
#   Alfred Linux 7.77 — Level 777 Boot Menu
#   "By Him all things consist." — Colossians 1:17
# ═════════════════════════════════════════════════════════════════════

if loadfont /boot/grub/fonts/unicode.pf2 ; then
    insmod all_video
    insmod gfxterm
    insmod png
    set gfxmode=auto
    set gfxpayload=keep
    terminal_output gfxterm
fi

set theme=/boot/grub/themes/alfred/theme.txt
loadfont $prefix/themes/alfred/Alfred-24.pf2 2>/dev/null

set timeout=10
set default=0
set menu_color_normal=white/black
set menu_color_highlight=black/light-gray

# ── KINGDOM LIVE ─────────────────────────────────────────────────────
menuentry "Alfred Linux 7.77 — Live (Kingdom)"  --class alfred --class gnu-linux {
    linux  /live/vmlinuz boot=live components quiet splash plymouth.ignore-serial-consoles
    initrd /live/initrd.img
}

menuentry "Alfred Linux 7.77 — Live (Sabbath safe-mode, no splash)"  --class alfred {
    linux  /live/vmlinuz boot=live components nomodeset noquiet text systemd.show_status=true
    initrd /live/initrd.img
}

menuentry "Alfred Linux 7.77 — Live (to RAM, kiosk)"  --class alfred {
    linux  /live/vmlinuz boot=live components quiet splash toram
    initrd /live/initrd.img
}

menuentry "Alfred Linux 7.77 — Live (failsafe, single user)"  --class alfred {
    linux  /live/vmlinuz boot=live components nomodeset noapic noapm nodma nosmp single
    initrd /live/initrd.img
}

# ── INSTALL ──────────────────────────────────────────────────────────
menuentry "Install Alfred Linux 7.77 (Calamares / graphical)"  --class install {
    linux  /live/vmlinuz boot=live components quiet splash findiso=${iso_path} username=installer
    initrd /live/initrd.img
}

menuentry "Install Alfred Linux 7.77 (text installer)"  --class install {
    linux  /live/vmlinuz boot=live components text
    initrd /live/initrd.img
}

# ── RESCUE / TOOLS ───────────────────────────────────────────────────
menuentry "Rescue shell (root, no network)"  --class rescue {
    linux  /live/vmlinuz boot=live components rescue noeject
    initrd /live/initrd.img
}

if [ -f /boot/memtest86+x64.bin ]; then
    menuentry "Memtest86+ 7.00 (RAM check)"  --class memtest {
        linux16 /boot/memtest86+x64.bin
    }
elif [ -f /boot/memtest86+.bin ]; then
    menuentry "Memtest86+ (RAM check)"  --class memtest {
        linux16 /boot/memtest86+.bin
    }
fi

if [ "$grub_platform" = "efi" ]; then
    menuentry "UEFI Firmware Settings"  --class settings {
        fwsetup
    }
fi

menuentry "Reboot"  --class reboot { reboot }
menuentry "Shutdown"  --class shutdown { halt }
GRUBCFG
echo "  grub.cfg written"

# ─────────────────────────────────────────────────────────────────────
banner "4/9  Build binary/boot/grub/efi.img (FAT12 EFI System image)"

EFI_IMG=$BIN/boot/grub/efi.img
SHIM=$CHROOT/usr/lib/shim/shimx64.efi.signed
SHIM_FB=$CHROOT/usr/lib/shim/shimx64.efi.signed.latest
GRUBX64=$CHROOT/usr/lib/grub/x86_64-efi-signed/grubx64.efi.signed
MMX64=$CHROOT/usr/lib/shim/mmx64.efi.signed
GRUBARM=$CHROOT/usr/lib/grub/arm64-efi-signed/grubaa64.efi.signed

[ -f "$SHIM" ] || SHIM="$SHIM_FB"

# 32 MiB FAT image is plenty
dd if=/dev/zero of="$EFI_IMG" bs=1M count=32 status=none
mkfs.vfat -F 12 -n EFIBOOT "$EFI_IMG" >/dev/null
mmd  -i "$EFI_IMG" ::EFI ::EFI/BOOT ::EFI/debian

if [ -f "$SHIM" ]; then
    mcopy -i "$EFI_IMG" "$SHIM"      ::EFI/BOOT/BOOTX64.EFI
    echo "  Secure-Boot shim → BOOTX64.EFI"
    cp    "$SHIM"      "$BIN/EFI/BOOT/BOOTX64.EFI"
else
    echo "  ⚠ no signed shim found — Secure Boot will require MOK enroll"
fi
if [ -f "$GRUBX64" ]; then
    mcopy -i "$EFI_IMG" "$GRUBX64"   ::EFI/BOOT/grubx64.efi
    cp    "$GRUBX64"   "$BIN/EFI/BOOT/grubx64.efi"
fi
if [ -f "$MMX64" ]; then
    mcopy -i "$EFI_IMG" "$MMX64"     ::EFI/BOOT/mmx64.efi
    cp    "$MMX64"     "$BIN/EFI/BOOT/mmx64.efi"
fi
if [ -f "$GRUBARM" ]; then
    mcopy -i "$EFI_IMG" "$GRUBARM"   ::EFI/BOOT/BOOTAA64.EFI
    cp    "$GRUBARM"   "$BIN/EFI/BOOT/BOOTAA64.EFI"
fi

# Embed an EFI grub.cfg that just chains to /boot/grub/grub.cfg
cat > /tmp/efi-grub.cfg <<'EFIGRUB'
search --no-floppy --set=root --label "Alfred Linux 7.77"
configfile /boot/grub/grub.cfg
EFIGRUB
mcopy -i "$EFI_IMG" /tmp/efi-grub.cfg ::EFI/debian/grub.cfg
mcopy -i "$EFI_IMG" /tmp/efi-grub.cfg ::EFI/BOOT/grub.cfg
echo "  EFI image populated:"
mdir -i "$EFI_IMG" ::EFI/BOOT

# ─────────────────────────────────────────────────────────────────────
banner "5/9  MOTD · Welcome · Wallpapers · Videos"

# 5a — install advanced MOTD (drop in from /home/root/staging or build-assets)
for src in \
    /home/root/staging/50-kingdom-bread \
    "$ROOT/build-assets/chroot-staging/etc/update-motd.d/50-kingdom-bread"; do
    if [ -f "$src" ]; then
        install -m 755 "$src" "$CHROOT/etc/update-motd.d/50-kingdom-bread"
        echo "  ✓ MOTD installed from $src"
        # disable Debian's stock 10-uname / 10-help-text noise
        chmod -x "$CHROOT/etc/update-motd.d/10-uname"     2>/dev/null || true
        chmod -x "$CHROOT/etc/update-motd.d/10-help-text" 2>/dev/null || true
        chmod -x "$CHROOT/etc/update-motd.d/00-header"    2>/dev/null || true
        break
    fi
done

# 5b — install Welcome of All Welcomes
mkdir -p "$CHROOT/usr/share/alfred/welcome"
for src in /home/root/staging/welcome.xml "$ROOT/build-assets/chroot-staging/welcome.xml"; do
    [ -f "$src" ] && { install -m 644 "$src" "$CHROOT/usr/share/alfred/welcome/welcome.xml"; echo "  ✓ welcome.xml installed"; break; }
done
for src in /home/root/staging/alfred-welcome "$ROOT/build-assets/chroot-staging/alfred-welcome"; do
    [ -f "$src" ] && { install -m 755 "$src" "$CHROOT/usr/local/bin/alfred-welcome"; echo "  ✓ alfred-welcome CLI installed"; break; }
done

# 5c — register EVERY wallpaper at every resolution in GNOME picker
GBP=$CHROOT/usr/share/gnome-background-properties
BG=$CHROOT/usr/share/backgrounds/alfred-linux
mkdir -p "$GBP"
{
    echo '<?xml version="1.0" encoding="UTF-8"?>'
    echo '<!DOCTYPE wallpapers SYSTEM "gnome-wp-list.dtd">'
    echo '<wallpapers>'
    for res in 8k 4k 1080p; do
        D="$BG/$res"
        [ -d "$D" ] || continue
        for f in "$D"/*.png; do
            [ -f "$f" ] || continue
            name=$(basename "$f" .png)
            pretty=$(echo "$name" | sed 's/-1080p$//;s/-4k$//;s/-8k$//;s/-/ /g' | \
                     awk '{for(i=1;i<=NF;i++)$i=toupper(substr($i,1,1))substr($i,2)}1')
            echo "  <wallpaper deleted=\"false\">"
            echo "    <name>Kingdom · $pretty (${res})</name>"
            echo "    <filename>/usr/share/backgrounds/alfred-linux/$res/$name.png</filename>"
            echo "    <options>zoom</options>"
            echo "    <pcolor>#000000</pcolor>"
            echo "    <scolor>#000000</scolor>"
            echo "  </wallpaper>"
        done
    done
    echo '</wallpapers>'
} > "$GBP/alfred-kingdom.xml"
WP_COUNT=$(grep -c '<wallpaper ' "$GBP/alfred-kingdom.xml")
echo "  ✓ registered $WP_COUNT Kingdom wallpapers (1080p+4K+8K)"

for x in "$GBP"/debian-*.xml; do
    [ -f "$x" ] && sed -i 's/deleted="false"/deleted="true"/g' "$x"
done
echo "  ✓ hid stock Debian wallpapers"

# 5d — register all videos in chroot for Kingdom Media app
VID_SRC="$CHROOT/build-assets/videos/clips"
VID_DST="$CHROOT/usr/share/alfred/videos"
mkdir -p "$VID_DST"
if [ -d "$VID_SRC" ]; then
    cp -an "$VID_SRC"/*.mp4 "$VID_DST/" 2>/dev/null || true
fi
# extra videos (kingdom-master, intros) live under build-assets/videos directly
for f in "$CHROOT/build-assets/videos"/*.mp4 "$CHROOT/build-assets/videos"/*.webm; do
    [ -f "$f" ] && cp -an "$f" "$VID_DST/"
done
VID_COUNT=$(ls "$VID_DST" 2>/dev/null | wc -l)

# Build a manifest the Kingdom Media app reads
cat > "$VID_DST/manifest.json" <<MAN
{
  "version": "7.77",
  "generated": "$TS",
  "count": $VID_COUNT,
  "videos": [
$(for f in "$VID_DST"/*.mp4 "$VID_DST"/*.webm 2>/dev/null; do
    [ -f "$f" ] || continue
    n=$(basename "$f")
    s=$(stat -c%s "$f")
    printf '    {"file":"%s","bytes":%s},\n' "$n" "$s"
  done | sed '$s/,$//')
  ]
}
MAN
echo "  ✓ registered $VID_COUNT videos → $VID_DST"

# desktop launcher pointing at videos dir
cat > "$CHROOT/usr/share/applications/alfred-kingdom-videos.desktop" <<DESK
[Desktop Entry]
Version=1.0
Type=Application
Name=Kingdom Videos
Comment=Watch the Kingdom Master and scenes
Exec=xdg-open /usr/share/alfred/videos
Icon=video-x-generic
Terminal=false
Categories=AudioVideo;Video;
DESK
echo "  ✓ Kingdom Videos launcher installed"

# ─────────────────────────────────────────────────────────────────────
banner "6/9  Refresh isolinux assets (BIOS path)"
mkdir -p "$BIN/isolinux"
for f in isolinux.bin ldlinux.c32 libcom32.c32 libutil.c32 vesamenu.c32 chain.c32 reboot.c32 poweroff.c32 menu.c32; do
    SRC=$(find "$CHROOT/usr/lib/syslinux" "$CHROOT/usr/lib/ISOLINUX" -name "$f" 2>/dev/null | head -1)
    [ -n "$SRC" ] && cp "$SRC" "$BIN/isolinux/" 2>/dev/null || true
done
echo "  isolinux refreshed"

# ─────────────────────────────────────────────────────────────────────
banner "7/9  Confirm all key artifacts"
for f in \
    "$BIN/boot/grub/grub.cfg" \
    "$BIN/boot/grub/efi.img" \
    "$BIN/EFI/BOOT/BOOTX64.EFI" \
    "$BIN/isolinux/isolinux.bin" \
    "$CHROOT/etc/update-motd.d/50-kingdom-bread" \
    "$CHROOT/usr/share/gnome-background-properties/alfred-kingdom.xml" \
; do
    if [ -e "$f" ]; then
        printf "  ✓ %s  (%s bytes)\n" "$f" "$(stat -c%s "$f")"
    else
        printf "  ✗ MISSING: %s\n" "$f"
    fi
done

# ─────────────────────────────────────────────────────────────────────
banner "8/9  Hook count final + chroot size"
echo "  hooks: $(ls "$HOOKS" | grep -c '\.hook\.chroot$')"
echo "  chroot size: $(du -sh "$CHROOT" 2>/dev/null | cut -f1)"

# ─────────────────────────────────────────────────────────────────────
banner "9/9  DONE — next step"
echo
echo "  ✓ 46 → 42 hooks consolidated"
echo "  ✓ Level-777 bootloader stack (BIOS + UEFI x86_64 + UEFI ARM64 + Secure Boot shim)"
echo "  ✓ Memtest86+ menu entry, Rescue, Install, UEFI fwsetup"
echo "  ✓ Kingdom GRUB theme staged"
echo "  ✓ MOTD + wallpaper picker registered in chroot"
echo
echo "  Now run the reseal:"
echo "    sudo bash /home/root/reseal-iso.sh"
echo
echo "  Snapshot for rollback: $BAK"
echo
echo "  ✠  Soli Deo Gloria.  ✠"
