# GoSiteMe Veil — Android App

Native Android application for GoSiteMe Veil, built as a **Trusted Web Activity (TWA)**.

## What is TWA?

TWA is Google's recommended approach for packaging a PWA as a native Android app. It:

- Runs the web app in **full-screen Chrome** (no URL bar, no browser chrome)
- Uses **Digital Asset Links** to verify domain ownership
- Gets a **real APK** that can be published to Google Play Store
- Supports **push notifications**, deep links, and Android shortcuts
- Auto-updates content without Play Store updates (the web app IS the content)

## Architecture

```
com.gositeme.veil
├── LauncherActivity.java    # Starts TWA → gositeme.com/veil/
├── AndroidManifest.xml      # Permissions, deep links, asset statements
└── res/
    ├── values/              # Theme, colors, strings
    ├── drawable/            # Splash screen
    ├── mipmap-*/            # App icons (48-192px)
    └── xml/                 # Network security, shortcuts
```

## Build Instructions

### Prerequisites

1. **JDK 17+**
   ```bash
   sudo apt install openjdk-17-jdk
   ```

2. **Android SDK** (via Android Studio or command-line tools)
   ```bash
   # Download command-line tools from developer.android.com
   export ANDROID_HOME=$HOME/Android/Sdk
   sdkmanager "platforms;android-34" "build-tools;34.0.0"
   ```

### Build Debug APK

```bash
chmod +x build.sh
./build.sh
```

Output: `../downloads/GoSiteMe-Veil.apk`

### Build Signed Release APK

```bash
./build.sh release
```

This will:
1. Create a signing keystore (if none exists)
2. Print the SHA-256 fingerprint (needed for `assetlinks.json`)
3. Build and sign the APK
4. Copy to `../downloads/GoSiteMe-Veil.apk`

### Post-Build: Update Asset Links

After building the release APK, update `/.well-known/assetlinks.json` with the SHA-256 fingerprint:

```json
[{
    "relation": ["delegate_permission/common.handle_all_urls"],
    "target": {
        "namespace": "android_app",
        "package_name": "com.gositeme.veil",
        "sha256_cert_fingerprints": ["YOUR_SHA256_FINGERPRINT_HERE"]
    }
}]
```

### Install on Device

```bash
adb install ../downloads/GoSiteMe-Veil.apk
```

## Security

- **HTTPS only** — `network_security_config.xml` blocks cleartext traffic
- **No WebView** — Uses Chrome's Trusted Web Activity (inherits Chrome's security)
- **Post-quantum encryption** — All crypto runs in the web layer (Kyber-768 + ECDH)
- **No data stored natively** — All data lives in the web app's IndexedDB/localStorage
