# Alfred Linux — Brand Guidelines

## Brand Identity

### Core Tagline
**"Your Computer. Your Rules. Your AI."**

### Brand Promise
Alfred Linux is the first operating system built for a world where AI agents are first-class citizens. It delivers privacy, intelligence, and sovereignty to every device it runs on.

### Personality
- Intelligent but approachable
- Powerful but not intimidating
- Futuristic but grounded
- Protective but not paranoid

---

## Logo Specification

### Primary Logo
- **Mark**: Stylized "A" with circuit-board trace motif, forming a shield silhouette
- **Wordmark**: "Alfred Linux" in custom-weighted Inter typeface
- **Lockup**: Mark + Wordmark, horizontal layout (primary), stacked layout (secondary)

### Logo Variations
| Variant | Use Case |
|---------|----------|
| Full color on dark | Default, hero sections, boot screen |
| Full color on light | Documentation, print materials |
| Monochrome white | Overlays, watermarks |
| Monochrome black | Print, fax, stamps |
| Icon only | Favicons, app icons, taskbar |

### Clear Space
- Minimum clear space: 1× the height of the "A" mark on all sides
- Minimum size: 24px height for digital, 10mm for print

---

## Color Palette

### Primary Colors
| Name | Hex | RGB | Use |
|------|-----|-----|-----|
| Void Black | `#0a0a14` | 10, 10, 20 | Primary background |
| Alfred Cyan | `#00D4FF` | 0, 212, 255 | Primary accent, links, active states |
| Alfred Purple | `#7D00FF` | 125, 0, 255 | Secondary accent, AI indicators |

### Secondary Colors
| Name | Hex | RGB | Use |
|------|-----|-----|-----|
| Nebula Blue | `#1a1a2e` | 26, 26, 46 | Card backgrounds, panels |
| Starfield Gray | `#2a2a3e` | 42, 42, 62 | Borders, dividers |
| Ghost White | `#e0e0e8` | 224, 224, 232 | Primary text |
| Dim Silver | `#8888aa` | 136, 136, 170 | Secondary text |

### Semantic Colors
| Name | Hex | Use |
|------|-----|-----|
| Success Green | `#00FF88` | Confirmations, healthy status |
| Warning Amber | `#FFB800` | Warnings, attention needed |
| Error Red | `#FF3366` | Errors, critical alerts |
| Info Blue | `#4488FF` | Informational messages |

### Gradient
- **Primary Gradient**: `linear-gradient(135deg, #00D4FF, #7D00FF)`
- Use for: Hero elements, progress bars, feature highlights
- Never use for: Body text, small UI elements

---

## Typography

### Font Stack
| Role | Font | Weight | Size Range |
|------|------|--------|------------|
| Display / H1 | Inter | 800 (ExtraBold) | 48–72px |
| Headings / H2–H4 | Inter | 600 (SemiBold) | 24–36px |
| Body | Inter | 400 (Regular) | 14–16px |
| Code / Terminal | JetBrains Mono | 400 | 13–14px |
| UI Labels | Inter | 500 (Medium) | 12–14px |

### Line Heights
- Display: 1.1
- Headings: 1.3
- Body: 1.6
- Code: 1.5

---

## Iconography

### Style
- Line icons, 1.5px stroke weight
- Rounded caps and joins
- 24×24px base grid
- Consistent optical sizing

### Icon Categories
- **System**: power, settings, network, battery, volume, display
- **AI**: brain, sparkle, waveform, neural-net, agent-badge
- **Security**: shield, lock, key, fingerprint, eye-off
- **Files**: folder, document, image, code, archive

---

## Sound Design

### System Sounds
| Event | Description | Duration |
|-------|-------------|----------|
| Boot | Low synth chord rising to bright tone | 2.5s |
| Login | Soft chime with subtle reverb | 0.8s |
| Notification | Gentle two-tone ping | 0.4s |
| Error | Muted low buzz | 0.3s |
| AI Listening | Soft ambient pulse (looping) | Variable |
| AI Response | Quick ascending sparkle | 0.5s |

### Voice
- Default TTS: Kokoro (local, offline-capable)
- Voice character: Warm, clear, slightly formal British-English cadence
- Speed: 1.0× default, user-adjustable 0.5×–2.0×

---

## Wallpapers

### Default Set (5 included)
1. **Void** — Deep black with faint star particles and a single cyan nebula wisp
2. **Circuit** — Abstract circuit-board traces in purple on dark background
3. **Horizon** — Gradient horizon line, deep purple to cyan, minimal
4. **Mesh** — Wireframe globe with glowing nodes, Alfred Cyan on Void Black
5. **Calm** — Soft gradient, Nebula Blue to Void Black, no elements — focus wallpaper

### Community Wallpapers
- Encouraged through Alfred Art Program
- Must meet minimum resolution: 3840×2160
- Must include dark variant
- Reviewed for brand alignment before featuring

---

## Writing Style

### Voice & Tone
- **Clear over clever**: No jargon unless necessary, explain when used
- **Confident but not cocky**: "Alfred handles this" not "Alfred dominates this"
- **Inclusive**: "Your system" not "The system"
- **Active voice**: "Alfred encrypts your files" not "Your files are encrypted by Alfred"

### Naming Conventions
| Component | Name Format | Example |
|-----------|-------------|---------|
| Desktop Environment | ADE (Alfred Desktop Environment) | "Open ADE Settings" |
| Security Layer | Veil | "Veil is active" |
| Economy Layer | GSM (GoSiteMe Token) | "Earn GSM" |
| Voice Assistant | Alfred | "Hey Alfred" |
| Package Manager | apkg | `apkg install firefox` |

---

## Motion & Animation

### Principles
- Purposeful: Every animation communicates state change
- Quick: Default duration 200ms, max 400ms for complex transitions
- Smooth: Use ease-out curves for entrances, ease-in for exits
- Reduced motion: All animations respect `prefers-reduced-motion`

### Standard Animations
| Action | Type | Duration | Easing |
|--------|------|----------|--------|
| Panel open | Slide + fade | 200ms | ease-out |
| Notification enter | Slide from right | 250ms | ease-out |
| Menu appear | Scale + fade | 150ms | ease-out |
| Window minimize | Scale down + fade | 200ms | ease-in |
| AI thinking | Pulse glow | 1000ms loop | ease-in-out |

---

*Brand guidelines v1.0 — March 2026*
*Alfred Linux is a product of GoSiteMe Inc.*
