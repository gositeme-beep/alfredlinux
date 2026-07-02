# Installing Alfred Linux

## 📥 Download

Alfred Linux is distributed as a **98GB bootable ISO** via BitTorrent.

**Download:** [alfredlinux-latest.iso.torrent](https://alfredlinux.com/downloads/alfredlinux-latest.iso.torrent)

Open the \.torrent\ file in any BitTorrent client (qBittorrent, Transmission, Deluge) to download the ISO.

## 💿 Create Bootable USB

### Requirements
- **USB drive:** 128GB or larger
- **Boot method:** UEFI recommended (Legacy BIOS supported)

### Option 1: Ventoy (Recommended)
1. Install [Ventoy](https://www.ventoy.net/) on your USB drive
2. Copy \AlfredLinux-Alpha-Matrix-7.77-x86_64.iso\ to the Ventoy partition
3. Boot from USB and select Alfred Linux

### Option 2: dd (Linux/macOS)
\\\ash
# ⚠️ DOUBLE-CHECK /dev/sdX — wrong device = data loss
sudo dd if=AlfredLinux-Alpha-Matrix-7.77-x86_64.iso of=/dev/sdX bs=4M status=progress
sync
\\\

### Option 3: Rufus (Windows)
1. Download [Rufus](https://rufus.ie/)
2. Select the ISO, select your USB drive
3. Use GPT partition scheme, UEFI target
4. Click Start

## 🖥️ System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | x86_64, 4 cores | 8+ cores |
| RAM | 8 GB | 16+ GB |
| Storage | 256 GB | 512+ GB (SSD) |
| GPU | Any (Intel/AMD/NVIDIA) | NVIDIA (for AI models) |
| USB | 128 GB | 256 GB |

## 🔐 First Boot

1. Boot from USB (press F12/F2/DEL during POST to select boot device)
2. Select **Alfred Linux** from the boot menu
3. The system boots to a full XFCE desktop — no installation required
4. All 8 AI models, VR stack, and tools are ready to use immediately

## 🛠️ Permanent Installation (Optional)

To install to internal disk with full ZFS encryption:
1. Open a terminal
2. Run \sudo alfred-installer\
3. Follow the guided setup (disk selection, encryption passphrase, user creation)

## ⚠️ Troubleshooting

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues and solutions.

## 🔑 Verify ISO Integrity

\\\ash
# SHA-256
sha256sum -c sha256.txt

# BLAKE3 (if b3sum installed)
b3sum --check blake3.txt
\\\

Hash files are available at [alfredlinux.com/downloads/](https://alfredlinux.com/downloads/)

---

*Built for Yeshua. The Sovereign OS.*
