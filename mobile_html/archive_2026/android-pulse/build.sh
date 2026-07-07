#!/bin/bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

command -v java >/dev/null 2>&1 || {
    echo "❌ JDK not found. Install with: sudo apt install openjdk-17-jdk"
    exit 1
}

if [ -z "${ANDROID_HOME:-}" ]; then
    echo "❌ ANDROID_HOME not set."
    echo "   export ANDROID_HOME=\$HOME/Android/Sdk"
    exit 1
fi

BUILD_TYPE="${1:-debug}"

echo "🔨 Building Pulse Social APK ($BUILD_TYPE)..."

if [ "$BUILD_TYPE" = "release" ]; then
    if [ ! -f "keystore.jks" ]; then
        echo "📋 Creating keystore..."
        keytool -genkeypair \
            -alias gositeme-pulse \
            -keyalg RSA \
            -keysize 2048 \
            -validity 10000 \
            -keystore keystore.jks \
            -storepass gositeme \
            -dname "CN=GoSiteMe, OU=Engineering, O=GoSiteMe Inc, L=Montreal, ST=Quebec, C=CA"

        echo "📋 Keystore SHA-256 fingerprint:"
        keytool -list -v -keystore keystore.jks -alias gositeme-pulse -storepass gositeme \
            | grep SHA256 | head -1
    fi

    ./gradlew assembleRelease

    APK_PATH="app/build/outputs/apk/release/app-release.apk"
    if [ -f "$APK_PATH" ]; then
        DEST_DIR="$(dirname "$SCRIPT_DIR")/downloads"
        cp "$APK_PATH" "$DEST_DIR/Pulse-Social.apk"
        echo "✅ Release APK: $DEST_DIR/Pulse-Social.apk"
    fi
else
    ./gradlew assembleDebug

    APK_PATH="app/build/outputs/apk/debug/app-debug.apk"
    if [ -f "$APK_PATH" ]; then
        DEST_DIR="$(dirname "$SCRIPT_DIR")/downloads"
        cp "$APK_PATH" "$DEST_DIR/Pulse-Social.apk"
        echo "✅ Debug APK: $DEST_DIR/Pulse-Social.apk"
    fi
fi
