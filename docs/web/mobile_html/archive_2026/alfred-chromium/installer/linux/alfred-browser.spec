Name:     alfred-browser
Version:  1.0.0
Release:  1%{?dist}
Summary:  Alfred Browser — AI-Powered Chromium Browser by GoSiteMe
License:  Proprietary
URL:      https://gositeme.com/alfred-browser
Group:    Applications/Internet

Requires: nss >= 3.26
Requires: gtk3 >= 3.22
Requires: glib2 >= 2.32
Requires: alsa-lib >= 1.0.16
Requires: libXcomposite
Requires: libXdamage
Requires: libXrandr

%description
Alfred is a Chromium-based browser by GoSiteMe with built-in AI assistant,
encrypted messaging (Veil), social network (Pulse), crypto wallet, GSM token
mining, and 1,220+ AI tools. Zero telemetry. Zero tracking.

%install
mkdir -p %{buildroot}/usr/lib/alfred
mkdir -p %{buildroot}/usr/bin
mkdir -p %{buildroot}/usr/share/applications
mkdir -p %{buildroot}/usr/share/icons/hicolor/256x256/apps

# Copy browser files
cp -r %{_sourcedir}/Release/* %{buildroot}/usr/lib/alfred/

# Launcher script
cat > %{buildroot}/usr/bin/alfred-browser << 'EOF'
#!/bin/bash
exec /usr/lib/alfred/chrome "$@"
EOF
chmod +x %{buildroot}/usr/bin/alfred-browser

# Desktop entry
cat > %{buildroot}/usr/share/applications/alfred-browser.desktop << 'EOF'
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
EOF

%files
/usr/lib/alfred/
/usr/bin/alfred-browser
/usr/share/applications/alfred-browser.desktop
/usr/share/icons/hicolor/256x256/apps/alfred-browser.png

%changelog
* Sun Mar 09 2026 GoSiteMe <support@gositeme.com> - 1.0.0-1
- Initial release of Alfred Browser
