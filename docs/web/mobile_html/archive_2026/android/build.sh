#!/bin/bash
#
# Veil Messenger — Android APK Build Script
# Builds the encrypted messenger TWA APK for distribution
#
# Prerequisites:
#   - JDK 17+ (apt install openjdk-17-jdk)
#   - Android SDK (sdkmanager or Android Studio)
#   - ANDROID_HOME environment variable set
#
# Usage:
#   chmod +x build.sh
#   ./build.sh          # Build debug APK
#   ./build.sh release  # Build signed release APK
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# ── Check prerequisites ──────────────────────────────────
command -v java >/dev/null 2>&1 || {
    echo "❌ JDK not found. Install with: sudo apt install openjdk-17-jdk"
    exit 1
}

if [ -z "${ANDROID_HOME:-}" ]; then
    echo "❌ ANDROID_HOME not set. Install Android SDK and set the path."
    echo "   export ANDROID_HOME=\$HOME/Android/Sdk"
    exit 1
fi

# ── Build ──────────────────────────────────────────────────
BUILD_TYPE="${1:-debug}"

echo "🔨 Building Veil Messenger APK ($BUILD_TYPE)..."

if [ "$BUILD_TYPE" = "release" ]; then
    # For release builds, you need a keystore
    if [ ! -f "keystore.jks" ]; then
        echo ""
        echo "📋 No keystore found. Creating one..."
        echo "   You'll need this to sign the APK and for .well-known/assetlinks.json"
        echo ""
        keytool -genkeypair \
            -alias gositeme-veil \
            -keyalg RSA \
            -keysize 2048 \
            -validity 10000 \
            -keystore keystore.jks \
            -storepass gositeme \
            -dname "CN=GoSiteMe, OU=Engineering, O=GoSiteMe Inc, L=Montreal, ST=Quebec, C=CA"

        echo ""
        echo "📋 Keystore SHA-256 fingerprint (put this in .well-known/assetlinks.json):"
        keytool -list -v -keystore keystore.jks -alias gositeme-veil -storepass gositeme \
            | grep SHA256 | head -1
        echo ""
    fi

    ./gradlew assembleRelease

    APK_PATH="app/build/outputs/apk/release/app-release.apk"
    if [ -f "$APK_PATH" ]; then
        DEST_DIR="$(dirname "$SCRIPT_DIR")/downloads"
        cp "$APK_PATH" "$DEST_DIR/GoSiteMe-Veil.apk"
        echo ""
        echo "✅ Release APK built: $DEST_DIR/GoSiteMe-Veil.apk"
        echo ""
        echo "📋 Next steps:"
        echo "   1. Update .well-known/assetlinks.json with the SHA-256 fingerprint above"
        echo "   2. Upload to Google Play Console"
        echo "   3. Test: adb install $DEST_DIR/GoSiteMe-Veil.apk"
    fi
else
    ./gradlew assembleDebug

    APK_PATH="app/build/outputs/apk/debug/app-debug.apk"
    if [ -f "$APK_PATH" ]; then
        DEST_DIR="$(dirname "$SCRIPT_DIR")/downloads"
        cp "$APK_PATH" "$DEST_DIR/GoSiteMe-Veil.apk"
        echo ""
        echo "✅ Debug APK built: $DEST_DIR/GoSiteMe-Veil.apk"
        echo "   Install: adb install $DEST_DIR/GoSiteMe-Veil.apk"
    fi
fi
