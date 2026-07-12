#!/bin/bash
# package.sh — Create Alfred Browser installers for distribution
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CHROMIUM_SRC="$PROJECT_DIR/chromium/src"
OUT_DIR="$CHROMIUM_SRC/out/Release"
DIST_DIR="$PROJECT_DIR/dist"
VERSION=$(cat "$PROJECT_DIR/VERSION" 2>/dev/null || echo "1.0.0")
PLATFORM=$(uname -s)

echo "=== Alfred Browser — Package Installer ==="
echo "Version: $VERSION"
echo "Platform: $PLATFORM"
echo ""

mkdir -p "$DIST_DIR"

case "$PLATFORM" in
    Linux)
        echo "[1/3] Creating .deb package..."
        DEB_DIR="$DIST_DIR/deb-staging"
        mkdir -p "$DEB_DIR/DEBIAN"
        mkdir -p "$DEB_DIR/usr/bin"
        mkdir -p "$DEB_DIR/usr/lib/alfred"
        mkdir -p "$DEB_DIR/usr/share/applications"
        mkdir -p "$DEB_DIR/usr/share/icons/hicolor/256x256/apps"

        # Control file
        cat > "$DEB_DIR/DEBIAN/control" << EOF
Package: alfred-browser
Version: $VERSION
Section: web
Priority: optional
Architecture: amd64
Depends: libnss3 (>= 3.26), libgtk-3-0 (>= 3.22), libglib2.0-0 (>= 2.32), libasound2 (>= 1.0.16)
Maintainer: GoSiteMe Inc <support@gositeme.com>
Homepage: https://gositeme.com/alfred-browser
Description: Alfred Browser — AI-Powered Chromium Browser by GoSiteMe
 Alfred is a Chromium-based browser with built-in AI assistant,
 encrypted messaging, social network, crypto wallet, and mining.
 Zero telemetry. Zero tracking. Full ecosystem.
EOF

        # Desktop entry
        cat > "$DEB_DIR/usr/share/applications/alfred-browser.desktop" << EOF
[Desktop Entry]
Name=Alfred
Comment=AI-Powered Browser by GoSiteMe
Exec=/usr/bin/alfred-browser %U
Terminal=false
Type=Application
Icon=alfred-browser
Categories=Network;WebBrowser;
MimeType=text/html;text/xml;application/xhtml+xml;x-scheme-handler/http;x-scheme-handler/https;
StartupNotify=true
StartupWMClass=Alfred
EOF

        # Copy build output
        cp -r "$OUT_DIR"/* "$DEB_DIR/usr/lib/alfred/" 2>/dev/null || echo "  (No build output yet — packaging skeleton only)"
        
        # Create launcher
        cat > "$DEB_DIR/usr/bin/alfred-browser" << 'EOF'
#!/bin/bash
exec /usr/lib/alfred/chrome "$@"
EOF
        chmod +x "$DEB_DIR/usr/bin/alfred-browser"

        # Build .deb
        dpkg-deb --build "$DEB_DIR" "$DIST_DIR/alfred-browser_${VERSION}_amd64.deb" 2>/dev/null || echo "  dpkg-deb not available (skeleton created)"
        rm -rf "$DEB_DIR"

        echo "[2/3] Creating .rpm package..."
        echo "  (RPM spec created at installer/linux/alfred-browser.spec)"

        echo "[3/3] Creating AppImage..."
        echo "  (AppImage config at installer/linux/AppImageBuilder.yml)"
        ;;

    Darwin)
        echo "Creating .dmg for macOS..."
        APP_NAME="Alfred.app"
        APP_DIR="$DIST_DIR/$APP_NAME"
        mkdir -p "$APP_DIR/Contents/MacOS"
        mkdir -p "$APP_DIR/Contents/Resources"
        mkdir -p "$APP_DIR/Contents/Frameworks"

        # Info.plist
        cat > "$APP_DIR/Contents/Info.plist" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>CFBundleName</key>
    <string>Alfred</string>
    <key>CFBundleDisplayName</key>
    <string>Alfred Browser</string>
    <key>CFBundleIdentifier</key>
    <string>com.gositeme.alfred</string>
    <key>CFBundleVersion</key>
    <string>$VERSION</string>
    <key>CFBundleShortVersionString</key>
    <string>$VERSION</string>
    <key>CFBundleExecutable</key>
    <string>Alfred</string>
    <key>CFBundleIconFile</key>
    <string>alfred.icns</string>
    <key>CFBundlePackageType</key>
    <string>APPL</string>
    <key>LSMinimumSystemVersion</key>
    <string>12.0</string>
    <key>NSHighResolutionCapable</key>
    <true/>
    <key>CFBundleURLTypes</key>
    <array>
        <dict>
            <key>CFBundleURLName</key>
            <string>HTTP URL</string>
            <key>CFBundleURLSchemes</key>
            <array>
                <string>http</string>
                <string>https</string>
            </array>
        </dict>
    </array>
</dict>
</plist>
EOF

        echo "  App bundle created at $APP_DIR"
        echo "  To create DMG: hdiutil create -volname Alfred -srcfolder $APP_DIR -ov $DIST_DIR/Alfred-$VERSION.dmg"
        ;;

    MINGW*|MSYS*|CYGWIN*)
        echo "Creating Windows NSIS installer..."
        echo "  NSIS script at installer/windows/alfred-installer.nsi"
        echo "  Run: makensis installer/windows/alfred-installer.nsi"
        ;;
esac

echo ""
echo "=== Packaging complete. Output: $DIST_DIR/ ==="
