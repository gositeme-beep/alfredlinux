# Veil Desktop App

Windows/macOS/Linux desktop application for the Veil encrypted AI platform.

## Prerequisites

- Node.js 18+ (for building)
- npm or yarn

## Setup

```bash
cd desktop-app
npm install
```

## Development

```bash
npm start
```

## Build for Windows

```bash
npm run build:win
```

This creates a `.exe` installer in the `dist/` folder.

## Build for All Platforms

```bash
npm run build:all
```

## What This App Does

The Veil Desktop App is an Electron wrapper around the GoSiteMe.com Veil platform:

- **Native window** with system tray integration
- **Splash screen** with animated Veil branding
- **Auto-updates** (when configured)
- **Menu bar** with quick links to Command Center, Fleet Dashboard, Black Vault
- **Single instance** — only one window at a time
- **Close to tray** — stays running in background
- **External links** open in system browser (not in-app)

## Icon Generation

Place these icon files in the `build/` directory:
- `icon.png` — 512x512 PNG (Linux/Mac)
- `icon.ico` — Windows icon (256x256)
- `icon.icns` — macOS icon

You can generate these from the SVG in `splash.html`.

## Architecture

```
desktop-app/
├── main.js          # Electron main process
├── preload.js       # Secure bridge to renderer
├── splash.html      # Loading screen
├── package.json     # Dependencies & build config
├── build/           # Icon assets
│   ├── icon.png
│   ├── icon.ico
│   └── icon.icns
└── dist/            # Built installers (gitignored)
```
