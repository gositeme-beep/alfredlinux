#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# BRIDGE v2.0 SYNC — Run as: sudo bash ~/gohostme/SYNC-BRIDGE.sh
# ═══════════════════════════════════════════════════════════════
echo "Syncing Bridge v2.0 (HARDENED) to production..."

# Backup current bridge
cp /opt/gohostme/bridge.sh /opt/gohostme/bridge.sh.bak.$(date +%Y%m%d%H%M%S)
echo "✓ Old bridge backed up"

# Copy new bridge
cp /home/root/gohostme/bridge.sh /opt/gohostme/bridge.sh
chmod 755 /opt/gohostme/bridge.sh
chown root:root /opt/gohostme/bridge.sh
echo "✓ Bridge v2.0 deployed"

# Copy HMAC secret
cp /home/root/.vault/bridge-hmac-secret /opt/gohostme/data/.bridge-hmac-secret
chmod 600 /opt/gohostme/data/.bridge-hmac-secret
chown root:root /opt/gohostme/data/.bridge-hmac-secret
echo "✓ HMAC secret deployed"

echo ""
echo "═══ BRIDGE v2.0 DEPLOYED ═══"
echo "Security layers active:"
echo "  ✓ HMAC token verification (30s expiry)"
echo "  ✓ Command tiers (GREEN/YELLOW/RED)"
echo "  ✓ Dashboard approval for RED commands"
echo "  ✓ Full audit logging to database"
