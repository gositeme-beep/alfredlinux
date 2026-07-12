#!/bin/bash
echo "=== ALFRED LINUX MASTER VAULT — FINAL INVENTORY ==="
echo ""
echo "## PACKAGE COUNT"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -c ^ii
echo ""
echo "## DPKG HEALTH"
chroot /work/master-vault/bootstrap dpkg --audit 2>&1 | head -5
echo "(empty above = clean)"
echo ""
echo "## KERNEL"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep linux-image
echo ""
echo "## SECURITY TOOLS"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -iE "^ii.*(osquery|bettercap|wireshark|suricata|clamav|rkhunter|fail2ban|aide|apparmor|firejail|nmap|hashcat|sleuthkit|qtox|john )" | head -30
echo ""
echo "## DESKTOP ENVIRONMENT"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -iE "^ii.*(plasma-desktop|kde-full|sddm|kwin )" | head -10
echo ""
echo "## OFFICE/PRODUCTIVITY"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -iE "^ii.*(libreoffice-core|gimp |blender |kdenlive |audacity |obs-studio)" | head -10
echo ""
echo "## DRIVERS"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -iE "^ii.*(nvidia-driver|amd64-microcode|intel-microcode|firmware-amd)" | head -10
echo ""
echo "## RADIO/SDR"
chroot /work/master-vault/bootstrap dpkg -l 2>/dev/null | grep -iE "^ii.*(gnuradio|gr-|gqrx|soapy)" | head -15
echo ""
echo "## INJECTED TOOLS"
for t in eza xmrig; do
    test -f /work/master-vault/bootstrap/usr/local/bin/$t && echo "  OK: /usr/local/bin/$t"
done
test -f /work/master-vault/bootstrap/usr/bin/fastfetch && echo "  OK: /usr/bin/fastfetch"
test -d /work/master-vault/bootstrap/usr/local/share/bettercap/ui && echo "  OK: bettercap-ui"
test -d /work/master-vault/bootstrap/opt/nexmon_csi && echo "  OK: nexmon_csi"
test -d /work/master-vault/bootstrap/opt/espectre && echo "  OK: espectre"
test -f /work/master-vault/bootstrap/tmp/satdump.deb && echo "  OK: satdump.deb (staged)"
echo ""
echo "## VAULT SIZE"
du -sh /work/master-vault/bootstrap/ 2>/dev/null
echo "=== END ==="
