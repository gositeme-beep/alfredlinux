#!/data/data/com.termux/files/usr/bin/bash
# ═══════════════════════════════════════════════════════════════════
# Alfred Linux 4.0 Mobile Installer — "Sovereign"
# Version: 2.0.0 — April 10, 2026
# Compatible: Samsung Galaxy S26 Ultra (1TB), Pixel, any Android 12+
# Method: Termux + proot-distro (NO ROOT REQUIRED)
# Base: Debian Trixie (same as Alfred Linux 4.0 Desktop ISO)
# ═══════════════════════════════════════════════════════════════════
#
# PREREQUISITES:
#   1. Install Termux from F-Droid (NOT Google Play — that version is outdated)
#      https://f-droid.org/en/packages/com.termux/
#   2. Open Termux and run:
#      curl -fsSL https://alfredlinux.com/downloads/install-alfred-mobile.sh | bash
#
# WHAT THIS DOES:
#   - Installs proot-distro in Termux
#   - Creates a Debian Trixie environment (same as Alfred Linux 4.0 Desktop)
#   - Installs Alfred IDE (code-server) accessible via browser
#   - Installs Alfred Voice (Kokoro TTS)
#   - Installs Alfred Search (Meilisearch)
#   - Installs Alfred Commander extension for IDE
#   - Connects to GoSiteMe ecosystem
#   - Creates launcher commands
#   - Samsung DeX desktop mode support
#
# STORAGE: ~5 GB after full install
# ═══════════════════════════════════════════════════════════════════

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
AMBER='\033[0;33m'
CYAN='\033[0;36m'
GOLD='\033[38;5;220m'
BOLD='\033[1m'
NC='\033[0m'

VERSION="4.0-mobile"
BUILD_DATE="2026-04-10"

banner() {
    echo ""
    echo -e "${GOLD}${BOLD}"
    echo "  ╔════════════════════════════════════════════╗"
    echo "  ║       ALFRED LINUX 4.0 — MOBILE            ║"
    echo "  ║    The AI-Native OS · Sovereign Edition     ║"
    echo "  ║                                             ║"
    echo "  ║    \"Your phone is a sovereign computer\"     ║"
    echo "  ╚════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo -e "  ${CYAN}Version $VERSION · $BUILD_DATE · Debian Trixie${NC}"
    echo ""
}

step() { echo -e "\n${GREEN}${BOLD}[$(date +%H:%M:%S)] ▸ $1${NC}"; }
warn() { echo -e "${AMBER}  ⚠ $1${NC}"; }
fail() { echo -e "${RED}${BOLD}  ✗ $1${NC}"; exit 1; }
ok()   { echo -e "${GREEN}  ✓ $1${NC}"; }

banner

if [[ ! -d /data/data/com.termux ]]; then
    fail "This script must be run inside Termux on Android."
fi

step "Checking Android environment..."
ARCH=$(uname -m)
DEVICE=$(getprop ro.product.model 2>/dev/null || echo 'unknown')
API_LEVEL=$(getprop ro.build.version.sdk 2>/dev/null || echo 'unknown')
ANDROID_VER=$(getprop ro.build.version.release 2>/dev/null || echo 'unknown')
TOTAL_STORAGE=$(df -h /data | tail -1 | awk '{print $2}')
FREE_STORAGE=$(df -h /data | tail -1 | awk '{print $4}')

echo "  Device:       $DEVICE"
echo "  Android:      $ANDROID_VER (API $API_LEVEL)"
echo "  Architecture: $ARCH"
echo "  Storage:      $FREE_STORAGE free of $TOTAL_STORAGE"

if echo "$DEVICE" | grep -qi "samsung\|SM-S"; then
    echo -e "  ${GOLD}Samsung detected — DeX desktop mode will be optimized${NC}"
fi

if [[ "$ARCH" != "aarch64" && "$ARCH" != "arm64" && "$ARCH" != "x86_64" ]]; then
    fail "Unsupported architecture: $ARCH. Need arm64 or x86_64."
fi

FREE_MB=$(df -m /data | tail -1 | awk '{print $4}')
if [[ "$FREE_MB" -lt 3000 ]]; then
    warn "Low storage: ${FREE_MB}MB free. Alfred needs ~5GB."
fi
if [[ "$FREE_MB" -gt 500000 ]]; then
    echo -e "  ${GOLD}${BOLD}  ROYAL STORAGE: ${FREE_STORAGE} — This phone was made for a King.${NC}"
fi

# ── Phase 1: Update Termux ──
step "Phase 1/7: Updating Termux packages..."
pkg update -y 2>&1 | tail -3
pkg upgrade -y 2>&1 | tail -3
ok "Termux updated"

# ── Phase 2: Install proot-distro ──
step "Phase 2/7: Installing proot-distro + audio..."
pkg install -y proot-distro pulseaudio termux-api 2>&1 | tail -3
ok "proot-distro + PulseAudio + Termux:API installed"

# ── Phase 3: Install Debian Trixie ──
step "Phase 3/7: Installing Debian Trixie (Alfred Linux 4.0 base)..."
if proot-distro list | grep -q "debian.*installed"; then
    warn "Debian already installed — upgrading to Trixie..."
    proot-distro login debian -- bash -c '
        if grep -q "bookworm" /etc/apt/sources.list 2>/dev/null; then
            sed -i "s/bookworm/trixie/g" /etc/apt/sources.list
            apt-get update -qq
            apt-get dist-upgrade -y -qq 2>&1 | tail -5
        fi
    '
else
    proot-distro install debian 2>&1 | tail -5
    proot-distro login debian -- bash -c '
        sed -i "s/bookworm/trixie/g" /etc/apt/sources.list 2>/dev/null || true
        apt-get update -qq
        apt-get dist-upgrade -y -qq 2>&1 | tail -5
    '
fi
ok "Debian Trixie ready"

# ── Phase 4: Alfred Layer ──
step "Phase 4/7: Installing Alfred Linux components..."
cat > /tmp/alfred-phase4.sh << 'PHASE4SCRIPT'
set -e

apt-get update -qq
apt-get upgrade -y -qq

apt-get install -y -qq \
    curl wget git sudo nano htop neofetch \
    python3 python3-pip python3-venv \
    nodejs npm build-essential \
    ca-certificates locales jq \
    2>&1 | tail -5

sed -i "s/# en_US.UTF-8/en_US.UTF-8/" /etc/locale.gen
locale-gen en_US.UTF-8 2>/dev/null || true

# Alfred IDE
if ! command -v code-server &>/dev/null; then
    curl -fsSL https://code-server.dev/install.sh | sh -s -- --method=standalone 2>&1 | tail -3
fi

mkdir -p ~/.config/code-server
cat > ~/.config/code-server/config.yaml << IDECONF
bind-addr: 0.0.0.0:8080
auth: none
cert: false
app-name: Alfred IDE Mobile
IDECONF

# Commander Extension
EXT_URL="https://gositeme.com/downloads/alfred-ide/alfred-commander-latest.vsix"
curl -fsSL "$EXT_URL" -o /tmp/alfred-commander.vsix 2>/dev/null || true
if [[ -f /tmp/alfred-commander.vsix && -s /tmp/alfred-commander.vsix ]]; then
    code-server --install-extension /tmp/alfred-commander.vsix 2>/dev/null || true
    rm -f /tmp/alfred-commander.vsix
fi

# Meilisearch
if ! command -v meilisearch &>/dev/null; then
    curl -fsSL https://install.meilisearch.com | sh 2>&1 | tail -3
    mv ./meilisearch /usr/local/bin/ 2>/dev/null || true
fi

# Kokoro TTS
python3 -m venv /opt/alfred-voice 2>/dev/null || true
/opt/alfred-voice/bin/pip install --quiet kokoro 2>&1 | tail -3 || true

# PM2
npm install -g pm2 2>&1 | tail -2 || true

# Branding
mkdir -p /etc/alfred/ecosystem
cat > /etc/alfred/release << RELEASE
ALFRED_LINUX_VERSION="4.0-mobile"
ALFRED_LINUX_CODENAME="Sovereign"
ALFRED_LINUX_BUILD="ga-mobile"
ALFRED_LINUX_DATE="2026-04-10"
ALFRED_LINUX_BASE="Debian Trixie"
ALFRED_LINUX_EDITION="Mobile"
RELEASE

# Override os-release so neofetch/screenfetch show Alfred Linux
cat > /etc/os-release << OSRELEASE
PRETTY_NAME="Alfred Linux 4.0 Mobile (Sovereign)"
NAME="Alfred Linux"
VERSION_ID="4.0"
VERSION="4.0 Mobile (Sovereign)"
VERSION_CODENAME=sovereign
ID=alfred
ID_LIKE=debian
HOME_URL="https://alfredlinux.com"
SUPPORT_URL="https://gositeme.com"
BUG_REPORT_URL="https://gositeme.com/support"
OSRELEASE

cat > /etc/alfred/ecosystem/config.json << ECOCONF
{
    "version": "4.0-mobile",
    "ecosystem": {
        "hub": "https://gositeme.com",
        "ide": "https://gositeme.com/alfred-ide/",
        "pulse": "https://gositeme.com/pulse",
        "metadome": "https://meta-dome.com",
        "music": "https://soundstudiopro.com",
        "linux": "https://alfredlinux.com"
    },
    "api": {
        "calendar": "https://gositeme.com/api/daniel-calendar.php",
        "wisdom": "https://gositeme.com/api/daily-wisdom.php",
        "chat": "https://gositeme.com/api/alfred-chat.php"
    }
}
ECOCONF

cat > /etc/motd << MOTD

  ╔════════════════════════════════════════════════════╗
  ║        ALFRED LINUX 4.0 — MOBILE EDITION           ║
  ║     The AI-Native OS · Sovereign · In Your Pocket   ║
  ╚════════════════════════════════════════════════════╝

  SERVICES:
    Alfred IDE      → code-server (port 8080)
    Alfred Search   → meilisearch (port 7700)
    Alfred Voice    → kokoro-tts

  COMMANDS:
    alfred-ide      → Launch Alfred IDE
    alfred-search   → Start Search engine
    alfred-voice    → Text to speech
    alfred-music    → Open SoundStudioPro
    alfred-shabbat  → God's Clock & calendar
    alfred-pray     → Kingdom Prayers
    alfred-dex      → Samsung DeX mode
    alfred-info     → System info
    alfred-update   → Check for updates

  ECOSYSTEM:
    gositeme.com       → Kingdom Hub
    soundstudiopro.com → AI Music Studio
    meta-dome.com      → VR Worlds
    alfredlinux.com    → Desktop OS

MOTD

mkdir -p ~/.config/neofetch
cat > ~/.config/neofetch/config.conf << NEOFETCH
print_info() {
    info title
    info underline
    info "OS" distro
    info "Host" model
    info "Kernel" kernel
    info "Shell" shell
    info "Packages" packages
    info "Memory" memory
    info "Disk" disk
}
NEOFETCH
PHASE4SCRIPT
proot-distro login debian -- bash /tmp/alfred-phase4.sh
rm -f /tmp/alfred-phase4.sh
ok "Alfred Linux 4.0 components installed"

# ── Phase 5: Launcher Commands ──
step "Phase 5/7: Creating launcher commands..."

cat > "$PREFIX/bin/alfred" << 'LAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m  ALFRED LINUX 4.0 — MOBILE · Sovereign · Omahon\033[0m\n"
proot-distro login debian
LAUNCHER
chmod +x "$PREFIX/bin/alfred"

cat > "$PREFIX/bin/alfred-ide" << 'IDELAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[0;32m\033[1m  Starting Alfred IDE on port 8080...\033[0m"
echo "  Open browser → http://localhost:8080"
pulseaudio --start 2>/dev/null || true
proot-distro login debian -- bash -c 'code-server --bind-addr 0.0.0.0:8080 2>&1' &
sleep 2
am start -a android.intent.action.VIEW -d "http://localhost:8080" 2>/dev/null || true
echo -e "\n  Alfred IDE running. Ctrl+C to stop."
wait
IDELAUNCHER
chmod +x "$PREFIX/bin/alfred-ide"

cat > "$PREFIX/bin/alfred-search" << 'SEARCHLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[0;32m\033[1m  Starting Alfred Search on port 7700...\033[0m"
proot-distro login debian -- bash -c 'meilisearch --http-addr 0.0.0.0:7700 2>&1' &
sleep 2
echo "  Alfred Search at http://localhost:7700  Ctrl+C to stop."
wait
SEARCHLAUNCHER
chmod +x "$PREFIX/bin/alfred-search"

cat > "$PREFIX/bin/alfred-music" << 'MUSICLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m  Opening SoundStudioPro — AI Music Studio\033[0m"
echo "  From the heart of David to the Kingdom of God"
am start -a android.intent.action.VIEW -d "https://soundstudiopro.com" 2>/dev/null || echo "  Open https://soundstudiopro.com"
MUSICLAUNCHER
chmod +x "$PREFIX/bin/alfred-music"

cat > "$PREFIX/bin/alfred-dex" << 'DEXLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m"
echo "  ╔════════════════════════════════════════════╗"
echo "  ║   ALFRED LINUX 4.0 — SAMSUNG DEX MODE      ║"
echo "  ║   Full Desktop Experience on Your Phone     ║"
echo "  ╚════════════════════════════════════════════╝"
echo -e "\033[0m"
pulseaudio --start 2>/dev/null || true
proot-distro login debian -- bash -c 'code-server --bind-addr 0.0.0.0:8080 2>&1' &
sleep 1
proot-distro login debian -- bash -c 'meilisearch --http-addr 0.0.0.0:7700 2>&1' &
sleep 1
echo -e "  \033[0;32m✓ Alfred IDE:    http://localhost:8080\033[0m"
echo -e "  \033[0;32m✓ Alfred Search: http://localhost:7700\033[0m"
echo ""
echo "  Connect monitor via DeX → open http://localhost:8080"
am start -a android.intent.action.VIEW -d "http://localhost:8080" 2>/dev/null || true
echo "  All services running. Ctrl+C to stop."
wait
DEXLAUNCHER
chmod +x "$PREFIX/bin/alfred-dex"

cat > "$PREFIX/bin/alfred-info" << 'INFOLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m  ALFRED LINUX 4.0 — MOBILE STATUS\033[0m\n"
proot-distro login debian -- bash -c '
cat /etc/alfred/release 2>/dev/null
echo ""
echo "Installed:"
command -v code-server &>/dev/null && echo "  ✓ Alfred IDE ($(code-server --version 2>/dev/null | head -1))"
command -v meilisearch &>/dev/null && echo "  ✓ Alfred Search"
[[ -d /opt/alfred-voice ]] && echo "  ✓ Alfred Voice"
command -v pm2 &>/dev/null && echo "  ✓ PM2"
command -v node &>/dev/null && echo "  ✓ Node.js $(node --version 2>/dev/null)"
command -v python3 &>/dev/null && echo "  ✓ Python $(python3 --version 2>/dev/null | cut -d" " -f2)"
echo ""
'
INFOLAUNCHER
chmod +x "$PREFIX/bin/alfred-info"

cat > "$PREFIX/bin/alfred-update" << 'UPDLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[0;36m\033[1m  Checking for Alfred Linux updates...\033[0m"
LATEST=$(curl -fsSL "https://alfredlinux.com/api/version.json" 2>/dev/null | grep -o '"mobile":"[^"]*"' | cut -d'"' -f4)
if [[ -n "$LATEST" && "$LATEST" != "4.0-mobile" ]]; then
    echo -e "  \033[0;33mUpdate available: 4.0-mobile → $LATEST\033[0m"
    echo "  Run: curl -fsSL https://alfredlinux.com/downloads/install-alfred-mobile.sh | bash"
else
    echo -e "  \033[0;32mYou're on the latest version (4.0-mobile)\033[0m"
fi
echo ""
UPDLAUNCHER
chmod +x "$PREFIX/bin/alfred-update"

cat > "$PREFIX/bin/alfred-voice" << 'VOICELAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
if [[ -z "$1" ]]; then
    echo -e "\n\033[38;5;220m\033[1m  Alfred Voice — Text to Speech\033[0m"
    echo "  Usage: alfred-voice \"Your message here\""
    echo "  From the heart of David, God gave us a voice."
    exit 0
fi
proot-distro login debian -- bash -c "
source /opt/alfred-voice/bin/activate 2>/dev/null
python3 -c \"
from kokoro import KPipeline
pipe = KPipeline(lang_code='a')
for _, _, audio in pipe('$1'):
    import soundfile as sf
    sf.write('/tmp/alfred-speech.wav', audio, 24000)
\" 2>/dev/null && pulseaudio --start 2>/dev/null && play /tmp/alfred-speech.wav 2>/dev/null || echo '  Audio saved to /tmp/alfred-speech.wav'
"
VOICELAUNCHER
chmod +x "$PREFIX/bin/alfred-voice"

cat > "$PREFIX/bin/alfred-shabbat" << 'SHABBATLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m  ✡ Alfred Shabbat — God's Clock\033[0m\n"
CALENDAR=$(curl -fsSL "https://gositeme.com/api/daniel-calendar.php?city=montreal" 2>/dev/null)
if [[ -n "$CALENDAR" ]]; then
    echo "$CALENDAR" | python3 -c "
import sys,json
try:
    d=json.load(sys.stdin)
    print(f\"  Date:     {d.get('gregorian','')} ({d.get('dayOfWeek','')})\" )
    print(f\"  Hebrew:   {d.get('hebrew',{}).get('display','')}\" )
    print(f\"  Enochian: {d.get('enochian',{}).get('display','')}\" )
    print(f\"  Sunset:   {d.get('sunset',{}).get('time','')}\")
    sb=d.get('shabbat',{})
    if sb.get('isShabbat'): print(f\"  🕯️ SHABBAT SHALOM — Rest in the Lord\")
    elif sb.get('isErev'): print(f\"  🕯️ EREV SHABBAT — Prepare your heart\")
    print(f\"  Torah:    {d.get('torahPortion',{}).get('name','')}\")
    print(f\"  Verse:    {d.get('dailyVerse',{}).get('text','')}\")
except: print('  Could not parse calendar data')
" 2>/dev/null || echo "  Calendar service unavailable"
else
    echo "  Could not reach calendar API"
fi
echo ""
SHABBATLAUNCHER
chmod +x "$PREFIX/bin/alfred-shabbat"

cat > "$PREFIX/bin/alfred-pray" << 'PRAYLAUNCHER'
#!/data/data/com.termux/files/usr/bin/bash
echo -e "\n\033[38;5;220m\033[1m  ✡ Kingdom Prayers\033[0m"
echo "  Opening the prayer library..."
am start -a android.intent.action.VIEW -d "https://gositeme.com/downloads/kingdom-prayers/" 2>/dev/null || echo "  Open https://gositeme.com/downloads/kingdom-prayers/"
PRAYLAUNCHER
chmod +x "$PREFIX/bin/alfred-pray"

ok "11 launcher commands created"

# ── Phase 6: Shortcuts ──
step "Phase 6/7: Creating widget shortcuts..."
mkdir -p ~/.shortcuts
for cmd in alfred-ide alfred alfred-music alfred-dex alfred-shabbat; do
    echo "#!/data/data/com.termux/files/usr/bin/bash
$cmd" > ~/.shortcuts/$(echo "$cmd" | sed 's/alfred-/Alfred-/;s/^alfred$/Alfred-Shell/')
    chmod +x ~/.shortcuts/* 2>/dev/null
done
ok "Shortcuts created"

# ── Phase 7: Done ──
step "Phase 7/7: Finalizing..."
ok "Alfred Linux 4.0 Mobile — INSTALLED"

echo ""
echo -e "${GOLD}${BOLD}"
echo "  ╔═══════════════════════════════════════════════════════╗"
echo "  ║         ALFRED LINUX 4.0 MOBILE — INSTALLED           ║"
echo "  ║                     OMAHON!                            ║"
echo "  ╚═══════════════════════════════════════════════════════╝"
echo -e "${NC}"
echo -e "  ${BOLD}Commands:${NC}"
echo -e "    ${CYAN}alfred${NC}          → Linux shell"
echo -e "    ${CYAN}alfred-ide${NC}      → IDE in browser"
echo -e "    ${CYAN}alfred-search${NC}   → Search engine"
echo -e "    ${CYAN}alfred-music${NC}    → SoundStudioPro"
echo -e "    ${CYAN}alfred-voice${NC}    → Text-to-speech"
echo -e "    ${CYAN}alfred-dex${NC}      → Samsung DeX mode"
echo -e "    ${CYAN}alfred-shabbat${NC}  → God's Clock & calendar"
echo -e "    ${CYAN}alfred-pray${NC}     → Kingdom Prayers"
echo -e "    ${CYAN}alfred-info${NC}     → System info"
echo -e "    ${CYAN}alfred-update${NC}   → Check updates"
echo ""
echo -e "  ${BOLD}What you have:${NC}"
echo -e "    • Debian Trixie Linux (same as desktop)"
echo -e "    • Alfred IDE with Commander extension"
echo -e "    • Alfred Search (Meilisearch)"
echo -e "    • Alfred Voice (Kokoro TTS)"
echo -e "    • Python 3, Node.js, Git, PM2"
echo -e "    • Connected to GoSiteMe ecosystem"
echo ""
echo -e "  ${GOLD}${BOLD}Your phone is a sovereign computer. — Omahon${NC}"
echo ""
