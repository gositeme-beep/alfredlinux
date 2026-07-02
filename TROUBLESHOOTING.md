# Troubleshooting Alfred Linux

## 🖥️ Boot Issues

### Black screen after boot menu
- **Cause:** GPU driver conflict with Plymouth splash
- **Fix:** At GRUB menu, press \e\, find the \linux\ line, add \
omodeset\ before \quiet\
- Press \Ctrl+X\ to boot

### Stuck at " Loading initial ramdisk\
- **Cause:** Slow USB read speeds on the 98GB ISO
- **Fix:** Wait 2-5 minutes. Use a USB 3.0 port and a fast drive

### UEFI boot not showing
- **Fix:** Disable Secure Boot in BIOS/UEFI settings. Alfred Linux uses a custom kernel that is not signed with Microsoft keys

## 🎮 GPU Issues

### NVIDIA GPU not detected
- **Cause:** NVIDIA 610.43.02 drivers are pre-compiled for specific GPU families
- **Fix:** Run \
vidia-smi\ to check. If no output, try \sudo modprobe nvidia\
- For unsupported GPUs, the system falls back to \
ouveau\ automatically

### Screen tearing
- **Fix:** Run \
vidia-settings\ → X Server Display Configuration → enable \Force Composition Pipeline\

## 💾 ZFS Issues

### \zpool: command not found\
- **Cause:** ZFS modules not loaded
- **Fix:**
\\\ash
sudo modprobe zfs
sudo modprobe spl
zpool status
\\\

### ZFS pool import fails
- **Fix:** \sudo zpool import -f <poolname>\

## 🤖 AI Model Issues

### Models not loading / out of memory
- **Cause:** Models require GPU VRAM or sufficient RAM
- **Fix:** Use smaller models first. XTTS-v2 and Whisper work on CPU. FLUX.2 needs 8GB+ VRAM

### \ orch.cuda.is_available()\ returns False
- **Fix:** Verify NVIDIA drivers: \
vidia-smi\. If working, run \pip install torch --index-url https://download.pytorch.org/whl/cu121\

## 🌐 Network Issues

### No internet connection
- **Cause:** Alfred Linux randomizes MAC address on every boot
- **Fix:** Some networks (corporate/hotel) may block randomized MACs. Disable with:
\\\ash
sudo macchanger -p eth0 # Restore permanent MAC
\\\

### DNS not resolving
- **Fix:** Alfred uses Quad9 (9.9.9.9) by default. Verify:
\\\ash
cat /etc/resolv.conf
# Should show: nameserver 9.9.9.9
\\\

## 🔒 Security Issues

### Locked out of root
- **Fix:** Boot with \init=/bin/bash\ kernel parameter, then \passwd\

### AppArmor blocking an app
- **Fix:** \sudo aa-complain /path/to/program\ to switch to complain mode

## 🥽 VR Issues

### ALVR not connecting to Quest 3
- **Cause:** Firewall blocking ALVR ports
- **Fix:**
\\\ash
sudo nft add rule inet filter input udp dport 9943-9944 accept
sudo nft add rule inet filter input tcp dport 8082 accept
\\\

### OpenXR runtime not found
- **Fix:** \export XR_RUNTIME_JSON=/usr/share/openxr/1/openxr_monado.json\

## 📞 Getting Help

- **Website:** [alfredlinux.com](https://alfredlinux.com)
- **GitHub Issues:** [github.com/GoSiteMe-com/alfredlinux/issues](https://github.com/GoSiteMe-com/alfredlinux/issues)

---

*\Fear not for I am with you.\ — Isaiah 41:10*
