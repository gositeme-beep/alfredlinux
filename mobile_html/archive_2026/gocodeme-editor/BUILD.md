# Building GoCodeMe

## Prerequisites

- Node.js 18+ (recommended: use nvm)
- Python 3.x
- Git
- Build tools:
  - **Windows**: Visual Studio Build Tools
  - **macOS**: Xcode Command Line Tools
  - **Linux**: `build-essential`, `libx11-dev`, `libxkbfile-dev`, `libsecret-1-dev`

## Quick Build

```bash
# Install dependencies
npm install

# Build React components
npm run buildreact

# Compile TypeScript
npm run compile

# Build for your platform
npm run gulp vscode-linux-x64      # Linux
npm run gulp vscode-darwin-x64     # macOS Intel
npm run gulp vscode-darwin-arm64   # macOS Apple Silicon
npm run gulp vscode-win32-x64      # Windows
```

## Creating Release Packages

```bash
# Create distributable packages
npm run gulp vscode-linux-x64-min      # Linux .tar.gz
npm run gulp vscode-darwin-x64-min     # macOS .zip
npm run gulp vscode-win32-x64-min      # Windows .zip
```

Output will be in `../VSCode-linux-x64/`, `../VSCode-darwin-x64/`, or `../VSCode-win32-x64/`

## GitHub Actions (Recommended)

For production builds, use GitHub Actions:

1. Fork this repo to your GitHub
2. Go to Actions tab
3. Run the build workflow
4. Download artifacts

## Troubleshooting

### "Cannot find module" errors
```bash
rm -rf node_modules
npm cache clean --force
npm install
```

### Python errors
Make sure Python 3 is in your PATH:
```bash
python3 --version
```

### Native module errors
Rebuild native modules:
```bash
npm run electron -- npm rebuild
```

## Custom Branding

Icons and logos are in:
- `void_icons/` - Main application icons
- `resources/linux/` - Linux icons
- `resources/darwin/` - macOS icons  
- `resources/win32/` - Windows icons

Product info is in:
- `product.json` - Application name, IDs, URLs

---

Need help? Contact support@gositeme.com
