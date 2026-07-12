# Icon Assets Needed

The following icon files are required for the Chrome Extension:

## Required Files
- `icon16.png` — 16×16px PNG, Alfred "A" logo on gradient background (#6c5ce7 → #0984e3)
- `icon48.png` — 48×48px PNG, same design
- `icon128.png` — 128×128px PNG, same design (used in Chrome Web Store listing)

## Design Spec
- **Background**: Linear gradient 135deg from #6c5ce7 (purple) to #0984e3 (blue)
- **Corner radius**: ~20% of icon size
- **Letter**: White bold "A" centered, using Inter or Space Grotesk font
- **Style**: Consistent with the Alfred AI branding

## Generating Icons
You can use an SVG as a base and convert to PNG at each size:

```svg
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#6c5ce7"/>
      <stop offset="100%" stop-color="#0984e3"/>
    </linearGradient>
  </defs>
  <rect width="128" height="128" rx="28" fill="url(#g)"/>
  <text x="64" y="88" text-anchor="middle" fill="#fff" font-family="Inter,sans-serif" font-size="72" font-weight="800">A</text>
</svg>
```

Convert with ImageMagick:
```bash
convert -background none -resize 16x16 icon.svg icon16.png
convert -background none -resize 48x48 icon.svg icon48.png
convert -background none -resize 128x128 icon.svg icon128.png
```
