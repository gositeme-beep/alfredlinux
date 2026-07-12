t# Alfred Linux — Brand Guidelines

## Brand Identity

### Name
- **Full name:** Alfred Linux
- **Short name:** Alfred OS / AlfredOS
- **Mobile name:** Alfred Mobile ([alfred-mobile.com](https://alfred-mobile.com))
- **Enterprise name:** Quantum Linux ([quantum-linux.com](https://quantum-linux.com))
- **Developer portal:** [alfred-linux.com](https://alfred-linux.com)
- **Parent company:** GoSiteMe Inc.

### Tagline Options
- **Primary:** "Your voice is the command line."
- **Technical:** "The world's first AI-native operating system."
- **Emotional:** "Built by humans and AI, for everyone."
- **Action:** "Speak. Control. Everything."

### Brand Personality
- **Intelligent** — not artificial, genuinely helpful
- **Sovereign** — you own your data, your compute, your identity
- **Elegant** — dark, refined, premium without being exclusionary
- **Fearless** — post-quantum encrypted, privacy-first, no compromises
- **Alive** — 114,000+ AI agents live in this ecosystem, it breathes

---

## Logo Specification

### Primary Logo: The Alfred "A" Shield

```
Design Concept:
- Letter "A" formed from two angular lines meeting at a peak
- Enclosed within a rounded shield/pentagon shape
- Inner glow: cyan (#00D4FF) to purple (#7D00FF) gradient
- The "A" crossbar is a sound wave / voice waveform
- Background: transparent or void black (#0a0a14)
```

### Logo Variants
| Variant | Use Case | Format |
|---------|----------|--------|
| **Full color** | Website, presentations, marketing | SVG, PNG |
| **Monochrome white** | Dark backgrounds, terminal | SVG, PNG |
| **Monochrome black** | Print, light backgrounds | SVG, PNG |
| **Icon only** | Favicons, app icons, boot splash | SVG, PNG, ICO |
| **Wordmark** | Full "Alfred Linux" text logo | SVG, PNG |
| **Boot animation** | Plymouth splash during startup | Animated PNG/SVG |

### Logo Safe Space
- Minimum clear space around logo = height of the "A" crossbar
- Minimum size = 32px (icon), 120px (full logo)
- Never stretch, rotate, or recolor outside brand palette

---

## Color Palette

### Core Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Void Black** | `#0a0a14` | 10, 10, 20 | Primary background |
| **Deep Space** | `#12121f` | 18, 18, 31 | Raised surfaces |
| **Nebula** | `#1a1a2e` | 26, 26, 46 | Cards, panels |
| **Stellar** | `#222240` | 34, 34, 64 | Hover states |

### Accent Colors

| Name | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Alfred Cyan** | `#00D4FF` | 0, 212, 255 | Primary accent, links, active states |
| **Alfred Purple** | `#7D00FF` | 125, 0, 255 | Secondary accent, gradients |
| **Signal Blue** | `#0074D9` | 0, 116, 217 | Buttons, CTAs |
| **Indigo** | `#6c5ce7` | 108, 92, 231 | Tertiary accent |

### Semantic Colors

| Name | Hex | Usage |
|------|-----|-------|
| **Success** | `#10b981` | Confirmations, online, healthy |
| **Warning** | `#f59e0b` | Cautions, attention needed |
| **Danger** | `#ef4444` | Errors, critical, destroy |
| **Pink** | `#ec4899` | Notifications, social |

### Text Colors

| Name | Hex | Usage |
|------|-----|-------|
| **Bright** | `#e8e8f0` | Primary text |
| **Muted** | `#a8b2d1` | Secondary text |
| **Dim** | `#94a3b8` | Tertiary, disabled |

### Gradients

| Name | Values | Usage |
|------|--------|-------|
| **Alfred Gradient** | `#00D4FF → #7D00FF` | Logo, hero sections, key UI |
| **Glow Cyan** | `0 0 30px rgba(0,212,255,0.3)` | Focus states, voice active |
| **Glow Purple** | `0 0 30px rgba(125,0,255,0.3)` | Hover states, selection |

---

## Typography

### Font Stack
| Purpose | Font | Fallback | Weight |
|---------|------|----------|--------|
| **Sans (UI/Body)** | Inter | -apple-system, sans-serif | 400, 500, 600, 700 |
| **Mono (Code/Terminal)** | JetBrains Mono | monospace | 400, 500 |
| **Display (Headers)** | Inter | sans-serif | 700, 800 |

### Scale
| Token | Size | Line Height | Usage |
|-------|------|-------------|-------|
| xs | 12px | 18px | Badges, micro-text |
| sm | 14px | 20px | Body secondary |
| base | 16px | 24px | Body primary |
| lg | 18px | 28px | Leads, emphasis |
| xl | 20px | 30px | Section titles |
| 2xl | 24px | 32px | Page subtitles |
| 3xl | 30px | 38px | Page titles |
| 4xl | 36px | 44px | Hero subtitles |
| 5xl | 40px | 48px | Hero titles |

---

## Iconography

### System Icons
- **Primary set:** Font Awesome 6 Pro
- **Style:** Regular weight for UI, Solid for emphasis
- **Fallback:** Custom inline SVG for brand-specific icons

### Key Brand Icons
| Icon | Meaning | FA Icon |
|------|---------|---------|
| Microphone | Voice commands | `fa-microphone` |
| Shield | Security/Veil | `fa-shield-halved` |
| Brain | AI/Intelligence | `fa-brain` |
| Coins | GSM tokens | `fa-coins` |
| House | Smart home | `fa-house-signal` |
| Car | Vehicle | `fa-car-side` |
| Robot | Robotics | `fa-robot` |
| Tractor | Agriculture | `fa-tractor` |
| VR Cardboard | VR/AR | `fa-vr-cardboard` |
| Gamepad | Gaming | `fa-gamepad` |

---

## Sound Design

### Voice
- **Alfred's voice:** Male, calm, articulate, slight warmth
- **Engine:** Kokoro TTS (primary), Orpheus (backup)
- **Personality:** Helpful butler, not servile. Confident, not arrogant.

### System Sounds
| Event | Sound Character |
|-------|----------------|
| Boot complete | Soft ascending chime, 2 notes (C → E) |
| Login | Warm "welcome" tone |
| Notification | Gentle ping, not jarring |
| Error | Low, brief double-pulse |
| Voice activated | Subtle "listening" swoosh |
| Voice confirmed | Clean affirmative chime |
| Logout | Descending warm tone |
| Shutdown | Gentle fade-out chord |

---

## Wallpapers

### Default Wallpaper Concept
- **Name:** "Constellation"
- **Description:** Deep void black (#0a0a14) background with subtle constellation pattern
- Connected nodes in Alfred Cyan and Purple forming a loose network
- Central cluster slightly brighter (Alfred "brain")
- Minimal, not busy — this is a work OS
- Resolution: 8K (7680x4320), scales down for all displays

### Wallpaper Pack
1. **Constellation** — Default (dark, minimal, networked nodes)
2. **Quantum Field** — Abstract quantum wave interference pattern
3. **Voice Wave** — Sound waveform stretching across horizon
4. **Veil** — Encrypted data streams, Matrix-inspired but elegant
5. **Metadome** — Aerial view of MetaDome civilization
6. **Farm Dawn** — AI-monitored fields at sunrise (for Farm edition)
7. **Fleet** — Robot swarm in formation (for Robotics edition)
8. **Circuit** — PCB trace art creating Alfred "A" shape

---

## Boot Experience

### GRUB Theme
- Void black background
- Alfred "A" logo centered, cyan glow
- Menu items in Inter font, clean spacing
- Selected item: Alfred Gradient underline

### Plymouth Boot Animation
- Alfred "A" logo dissolves in from particles
- Particles converge from edges to center
- Once formed, "A" pulses with cyan glow (heartbeat rhythm)
- Text below: "Alfred Linux" in Inter 700
- Duration: matches boot time (~3-8 seconds)

### Login Screen
- Full wallpaper background (blurred)
- Circular user avatar centered
- Voice prompt: "Say your name to unlock" (voice auth option)
- Password field with "or type your password" hint
- Clock in top-left, minimal

---

## Marketing Materials

### Elevator Pitch (30 seconds)
> "Alfred Linux is the first operating system built around AI. Instead of clicking through menus, you just talk. Alfred controls your smart home, your car, your robot fleet, your farm — all with voice commands, encrypted with post-quantum cryptography, and powered by a token economy that pays you for contributing. It's not a distro with a chatbot. It's what computing looks like when AI is the interface to reality."

### One-liner
> "The OS that listens, learns, and controls everything."

### Press Kit Elements
- Logo package (all variants)
- Screenshot gallery (desktop, terminal, voice HUD, settings)
- Architecture diagram
- Comparison table (vs macOS, Windows, ChromeOS)
- Founder bio + company info
- Press release template

---

## CSS Custom Properties (Design Tokens)

For developers building Alfred Linux apps, these CSS custom properties are standard:

```css
:root {
  /* Alfred Linux Brand Tokens */
  --al-bg:           #0a0a14;
  --al-bg-raised:    #12121f;
  --al-bg-card:      #1a1a2e;
  --al-bg-hover:     #222240;
  
  --al-cyan:         #00D4FF;
  --al-purple:       #7D00FF;
  --al-blue:         #0074D9;
  --al-indigo:       #6c5ce7;
  
  --al-success:      #10b981;
  --al-warning:      #f59e0b;
  --al-danger:       #ef4444;
  
  --al-text:         #e8e8f0;
  --al-text-muted:   #a8b2d1;
  --al-text-dim:     #94a3b8;
  
  --al-font-sans:    'Inter', -apple-system, sans-serif;
  --al-font-mono:    'JetBrains Mono', monospace;
  
  --al-radius-sm:    6px;
  --al-radius-md:    10px;
  --al-radius-lg:    16px;
  --al-radius-xl:    24px;
  
  --al-gradient:     linear-gradient(135deg, #00D4FF, #7D00FF);
  --al-glow-cyan:    0 0 30px rgba(0, 212, 255, 0.3);
  --al-glow-purple:  0 0 30px rgba(125, 0, 255, 0.3);
}
```

---

*This brand guide is a living document. Updated as Alfred Linux evolves.*  
*Version 1.1 — March 11, 2026*
