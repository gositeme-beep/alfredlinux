# GoSiteMe / GoCodeMe — Comprehensive Content Audit

**Audit Date:** July 2025  
**Audited By:** Automated Content Analysis  
**Scope:** All marketing, landing, product, legal, and utility pages across gositeme.com and gocodeme.com

---

## Table of Contents

1. [Domain Mapping](#1-domain-mapping)
2. [Page-by-Page Audit](#2-page-by-page-audit)
3. [Content Gap Analysis](#3-content-gap-analysis)
4. [HTML/Code Bugs](#4-htmlcode-bugs)
5. [Pricing Inconsistencies](#5-pricing-inconsistencies)
6. [Design & Branding Inconsistencies](#6-design--branding-inconsistencies)
7. [SEO & Structured Data](#7-seo--structured-data)
8. [Content Duplication](#8-content-duplication)
9. [Recommendations](#9-recommendations)

---

## 1. Domain Mapping

### Pages served on **gositeme.com** (primary domain)

| File | Purpose | Indexed? |
|------|---------|----------|
| `index.php` | Main homepage | ✅ Yes |
| `alfred.php` | Alfred AI product page | ✅ Yes |
| `gocodeme.php` | GoCodeMe landing page (canonical: `gocodeme.com`) | ✅ Yes |
| `voice.php` | Alfred Voice client (interactive) | ✅ Yes |
| `voice-products.php` | Voice & AI product catalog (52 products) | ✅ Yes |
| `voice-portal.php` | Voice command center (dashboard) | ❌ noindex |
| `dashboard.php` | User dashboard | ❌ noindex |
| `languages.php` | 300+ languages & technologies page | ✅ Yes |
| `privacy-policy.php` | Privacy policy | ✅ Yes |
| `terms-of-service.php` | Terms of service | ✅ Yes |
| `404.php` | Custom 404 page | ❌ noindex |
| `weather-test.php` | Weather test page (unknown) | Unknown |
| `uc.php` | Unknown utility page | Unknown |

### Pages intended for **gocodeme.com** domain

| File | Evidence |
|------|----------|
| `gocodeme.php` | `<link rel="canonical" href="https://gocodeme.com/">` — serves as GoCodeMe homepage |

### Static demo pages (`/demo/`)

| File | Purpose | Status |
|------|---------|--------|
| `demo/index.html` | Alternative/demo homepage | Static HTML, no PHP |
| `demo/hosting.html` | Plans comparison page | Static HTML |
| `demo/domains.html` | Domain registration page | Static HTML |
| `demo/pricing.html` | Detailed pricing page | Static HTML |
| `demo/features.html` | Features & engines detail | Static HTML |
| `demo/contact.html` | Contact page | Static HTML |
| `demo/styles.css` | Shared demo styles | CSS |
| `demo/script.js` | Shared demo JS | JS |

### Other product areas

| Path | Purpose |
|------|---------|
| `/ai-servers/` | AI Server Configurator (custom GPU workstation builder) |
| `/editor/` | GoCodeMe Editor (web-based) |
| `/chess/` | Chess game (recreational?) |
| `/quickqr/` | QR code generator |
| `/presser/` | Unknown |
| `/articles/` | Articles section |

---

## 2. Page-by-Page Audit

---

### 2.1 `index.php` — GoSiteMe Homepage (1,613 lines)

**Content & Features:**
- Hero with 18 floating feature cards showing platform capabilities
- Stats section: 50K websites, 99.9% uptime, 24/7 support, 60s AI creation, 457 AI tools, 73 categories, 332+ features
- Trusted-by logos: Shopify, WordPress, Stripe, Cloudflare, AWS
- Capability ticker marquee
- 3-step "How It Works" flow
- AI Editor demo section with live examples
- Alfred AI teaser with mock conversation cards
- Voice & AI Products showcase (phone $3/mo, agents $29/mo, fax $15/mo, SMS $19/mo, industry $59/mo)
- Business Programs (reseller $299/mo, affiliate 20%, agency partner)
- Features grid (12 cards)
- Head-to-Head comparison with animated progress rings (vs Cursor, GoDaddy, Bluehost, Hostinger, Cloudways, cPanel, DigitalOcean, Replit)
- Named Competitor Scoreboard (32/32 vs competitors scoring 4-9)
- Enterprise Showcase (8 industry cards: SaaS, e-com, health, fintech, real estate, restaurant, law, booking)
- Pricing section (4 plans: Builder $15, Professional $29, Studio $59, Business $99)
- Token top-up packs and power-up add-ons
- Domain search with TLD pricing (.com $13.13, .co.uk $9.59, .org $16.20, .net $16.80, .xyz $15.60)
- Services section (Token packs from $5, Dedicated AI servers from $2,499, Voice & AI from $3)
- Comparison table vs Bluehost, GoDaddy, Hostinger
- Guarantee section (30-day money-back)
- 3 testimonials (Sarah K., Marcus R., James T.) with 4.9/5 review summary
- 6 FAQ items
- Final CTA
- Rich structured data: FAQPage, ItemList (plans), HowTo, Review, Service, BreadcrumbList, TechArticle, ImageObject, Offer (warranty), WebPage

**Internationalization:** ✅ Full EN/FR via `L()` function

**What's Good:**
- Extremely comprehensive page with strong SEO schema markup
- Good social proof with competitor comparison
- Strong pricing transparency
- Multiple CTAs throughout
- Bilingual support

**What's Missing/Needs Updating:**
- ⚠️ **HTML bug on line ~870**: `<span class="hp-rs-num" style="color:#00D4FF;"457</span>` — missing `>` before "457"
- ⚠️ Stats say "457 AI tools" but alfred.php hero says "400+"  — inconsistent tool count
- No actual images/screenshots — all placeholder emoji/icons
- No video content or demo walkthrough
- Testimonials appear generic/fabricated — no photos, no company links, no verifiable details
- Review platforms (Google, Trustpilot, G2) referenced but likely no actual listings exist
- "50K websites" stat may be aspirational rather than verified
- Missing: live chat widget, pricing toggle (monthly/annual), comparison calculator
- Should add a Creator plan ($22/mo) to match gocodeme.php
- `dateModified: '2026-03-03'` in FAQ schema — future date (likely a typo)

---

### 2.2 `gocodeme.php` — GoCodeMe Landing Page (727 lines)

**Content & Features:**
- Hero: "World's First AI Development Platform" with 457 MCP tools, 16 AI engines, voice commands
- Promo code: LAUNCH50 (50% off first year)
- Live Demo timeline: 8 demo scenarios (WordPress, WooCommerce, image gen, email, code review, browser agent, code interpreter, charts)
- Alfred Autopilot section: 15 action types, 19 human features, 29 API endpoints, 42 UI components
- 6 autopilot capability cards
- 3-step how-it-works
- 16 Intelligence Engines listed by codename (ELEPHANT, ORACLE, PLAYBOOK, CLOCKWORK, HIVEMIND, SENTINEL, NEXUS, FORGE, CORTEX + 7 more)
- All Features: 12 feature cards (voice, messaging, image gen, video, vision, charts, private AI, A2A, MCP, voice rooms, WordPress, security)
- 457 Tools by Category: 28 category cards with tool counts
- Comparison table vs Cursor ($20/mo), Lovable ($20/mo), cPanel ($15+), Replit
- Download section: Windows x64, macOS Apple Silicon, macOS Intel, Linux AppImage — Version 1.99
- Pricing: Builder $15, **Creator $22**, Professional $29, Studio $59, Business $99
- Token top-ups: 100K/$5, 500K/$19, 1M/$35, 5M/$149
- Voice & AI add-ons section and business programs
- 15 FAQ items
- Final CTA

**Canonical URL:** `https://gocodeme.com/` — cross-domain canonical

**Internationalization:** ❌ English only — no `L()` function

**What's Good:**
- Excellent product positioning with detailed feature showcase
- Strong competitor comparison
- Desktop download links for all platforms
- Comprehensive FAQ

**What's Missing/Needs Updating:**
- ⚠️ **HTML bug**: `<span class="accent"457</span>` — missing `>` before "457" (appears multiple times)
- ⚠️ **HTML bug in comparison table**: `style="color:var(--cyan); font-weight:800;"457` — broken attribute
- ❌ **No i18n** — should use `L()` like other pages, especially since it targets Canadian market (FR required)
- Uses its own standalone header/footer instead of `site-header.inc.php`
- No navigation shared with main site
- Missing: structured data/schema markup (unlike index.php which has extensive schema)
- Missing: testimonials, social proof
- Missing: actual screenshots or video demos
- Download links may point to outdated files
- "Creator" plan at $22/mo exists here but NOT on index.php pricing — major inconsistency
- No mention of Enterprise plan that exists on demo/hosting.html

---

### 2.3 `alfred.php` — Alfred AI Product Page (1,090 lines)

**Content & Features:**
- Hero: "The World's First AI Hosting Assistant" with 400+ tools, 16+ engines, 36 categories
- Promo code in hero
- Full commercial/demo timeline: 17 demo scenarios with tool tags (WordPress install, plugins, image gen, email, security scan, backup, code review, memory, scheduling, browser agent, code interpreter, RAG knowledge, proactive monitoring, charts, MCP gateway, private AI)
- Intelligence Engines section: 9 engine cards (ELEPHANT, ORACLE, PLAYBOOK, CLOCKWORK, HIVEMIND, SENTINEL, NEXUS, FORGE, CORTEX)
- v6.0 Superpowers: 7 cards (browser agent, code interpreter, RAG, proactive monitoring, workflow automation, live artifacts, command steering)
- Private On-Server AI section: 4 capability cards
- Live Charts & Artifacts section: 4 artifact type cards
- A2A Protocol section: demo conversation + agent network diagram (GitHub, Analytics, Finance, Design agents)
- Voice Rooms section: 3 capability cards
- Image Generation section: 4 demo prompts
- Voice Commands & Messaging section: WhatsApp, Signal, Discord, Telegram, SMS, Voice browser
- Head-to-Head comparison (identical ring chart system to index.php)
- Token pricing: Builder $15, Creator $22, Professional $29, Studio $59, Business $99
- Token top-ups
- 14 power-up add-ons with prices
- All 400+ tools listed by category with names, descriptions, and example commands
- Safety section: 6 safety cards
- Professional services: Quick Start $149, Workshop $499, Custom Integration $1,499, Server Support from $49/mo
- Voice & AI Products showcase (52 products)
- Business Programs
- 25 FAQ items (20 i18n + 5 hardcoded English)
- Final CTA
- SoftwareApplication schema

**Internationalization:** ✅ Mostly EN/FR via `L()` + `lang_alfred.php`, but 5 newer FAQ items are hardcoded in English only

**What's Good:**
- Most comprehensive product page on the site
- Excellent tool-by-tool breakdowns with "try saying" examples
- Strong safety/trust section
- Professional services upsell
- Good structured data

**What's Missing/Needs Updating:**
- Hero says "400+" tools but other pages say "457" — should standardize
- 5 newest FAQ items not translated to French
- New sections (Private AI, A2A, Voice Rooms, Charts/Artifacts) have hardcoded English text — not i18n
- Page is extremely long (1,090 lines) — may benefit from lazy loading or tabbed sections
- No actual images — all emoji placeholders
- Competition ring chart is duplicated from index.php (code duplication)
- Stats on this page say "9 engines" in ring section but "16+ engines" in hero — inconsistent

---

### 2.4 `voice.php` — Alfred Voice Client (1,242 lines)

**Content & Features:**
- Full interactive voice AI client with real-time audio
- WHMCS auth detection → JWT token generation for authenticated users
- Standalone page (own header, not site-header.inc.php)
- Voice UI with animated orb, tap/live modes
- 24 AI agents across 4 categories:
  - Flagship (6): Alfred, Aria, Luna, Marcus, Sage, Rex — Kokoro engine
  - Expressive (8): Tara, Leah, Jess, Leo, Dan, Mia, Zac, Zoe — Orpheus engine
  - Global/Multilingual (6): Nova, Ethan, Sophie, Lyra, Finn, Clara — Cartesia Sonic engine
  - Premium (4): Max, Elena, Victor, Grace — Kokoro premium voices
- 3 TTS engines: Kokoro, Orpheus, Cartesia Sonic
- Help panel with 35 example commands across 9 categories (web, e-commerce, SEO, design, DevOps, security, content, steering)
- Voice Activity Detection (VAD) for live mode
- Text input bar for typing commands
- Command steering queue system
- Transcript display
- Reconnection logic (up to 3 attempts)
- Uses HTTP relay bridge (`/middleware/api/voice-relay`)

**SEO:** Has meta tags and Schema.org WebApplication markup

**Internationalization:** ❌ English only (minimal — status messages are hardcoded)

**What's Good:**
- Extremely polished interactive UI
- Comprehensive agent selection system
- Dual input modes (voice + text)
- Smart reconnection handling
- Help panel with categorized examples

**What's Missing/Needs Updating:**
- Not using site-header.inc.php — no shared navigation
- No French translation
- No fallback for browsers without MediaRecorder/WebAudio support
- No accessibility considerations (screen reader labels, keyboard nav)
- No visible pricing or CTA to upgrade — pure utility page
- Phone number in footer note area could link to actual support
- Should show "Sign in for full access" link more prominently for unauthenticated users

---

### 2.5 `voice-products.php` — Voice & AI Product Catalog (765 lines)

**Content & Features:**
- Hero: "AI Phone Agents for Every Business" with stats (52 products, $3 starting price, 12 industries, 30+ languages, 24/7)
- Jump links navigation (Agents, À La Carte, Call Centers, Industry, Add-Ons, Business)
- **AI Voice Agent Packages** (4 tiers):
  - Starter $29/mo (1 agent, 100 min, 1 number)
  - Business $79/mo (3 agents, 500 min, 3 numbers) — Most Popular
  - Professional $199/mo (10 agents, 2,000 min, 10 numbers)
  - Enterprise $499/mo (unlimited agents, 10,000 min, unlimited numbers)
- **À La Carte** (12 products): Local $3/mo, Toll-Free $5/mo, Vanity $15/mo, International $10/mo, SIP Trunk $10/mo, 10-Number Bundle $25/mo, AI Fax Standard $14.99/mo, AI Fax Pro $39.99/mo, AI SMS Starter $29/mo, AI SMS Business $79/mo, AI Live Chat $19/mo, AI Document Generator $19/mo
- **Call Center Solutions** (6 products): Outbound Starter $99/mo, Outbound Pro $299/mo, Outbound Enterprise $999/mo, AI Inbound $499/mo, AI Appointment Setter $149/mo, AI Collections Agent $249/mo
- **AI Office Suite** (6 products): Virtual Receptionist $49/mo, Executive Assistant $39/mo, Customer Service Desk $149/mo, Bookkeeper $59/mo, Sales Agent $199/mo, E-Signature $19.99/mo
- **Industry Solutions** (12 industries): Restaurant $99, Real Estate $149, Medical/Dental $249, Legal $199, Home Services $79, Insurance $179, Automotive $149, Salon/Spa $59, Property Mgmt $129, E-Commerce $99, Accounting $129, Fitness $59
- **Add-Ons** (10): Extra minutes, recording, voice cloning $149, extra agents, concurrent lines, HIPAA $99, SMS bundle, multi-language pack $29
- **Business Programs**: White-Label Reseller ($299/mo), Affiliate (20% recurring), Agency Partner, Upsells/Cross-Sells
- Final CTA with phone number: +1 (807) 798-2850
- Product schema with AggregateOffer

**Internationalization:** ❌ English only

**What's Good:**
- Most comprehensive product catalog on the site
- Clear pricing for every product
- Good category organization with jump links
- Strong business programs section
- Real phone number for live demo

**What's Missing/Needs Updating:**
- Hero says "52 products" but if you count everything listed, it's closer to ~50 items — verify exact count
- No French translation
- No customer testimonials or case studies specific to voice products
- No comparison with competitors (Dialpad, Aircall, etc.)
- No demo audio samples of AI agents
- Industry solutions lack detail — each is just a card with one-line description
- Missing: ROI calculator, setup time estimates, integration guides
- WHMCS product IDs go up to pid=100 — verify all exist in WHMCS backend

---

### 2.6 `voice-portal.php` — Voice & AI Command Center (1,134 lines)

**Purpose:** Authenticated user dashboard for managing voice & AI services  
**Access:** Requires auth-gate.inc.php  
**Indexed:** ❌ noindex, nofollow

**Content:** Dashboard layout with sidebar navigation, stats cards, tables, modals, forms, badges system, usage bars, Chart.js.

**Assessment:** Internal tool, not marketing content. No action needed for content audit.

---

### 2.7 `dashboard.php` — User Dashboard (954 lines)

**Purpose:** Authenticated user dashboard  
**Access:** Requires auth-gate.inc.php  
**Indexed:** ❌ noindex, nofollow

**Assessment:** Internal tool, not marketing content. No action needed for content audit.

---

### 2.8 `languages.php` — 300+ Languages & Technologies (739 lines)

**Content & Features:**
- Header with "Full-Stack Developer" badge
- 300+ languages listed across 35 categories
- Search/filter with live count
- Categories: Web Dev, Backend, Mobile, Systems, Database, Scripting, Data Science, Infrastructure, Game Dev, Scientific, Markup, Hardware, Build Tools, AI/ML, GPU, DSLs, Parsing, Formal Verification, Semantic Web, Functional, Stack-Based, Array Languages, Audio, Math, Bioinformatics, Finance, Shaders, Legacy, Robotics, Security, Networking, Blockchain, Non-English, Esoteric, Newest/Emerging
- CTA section with "Get in Touch" email link

**Internationalization:** ❌ English only

**What's Good:**
- Impressive breadth of language coverage
- Clean search/filter UX
- Good visual design with tag-style chips

**What's Missing/Needs Updating:**
- ⚠️ **Completely different visual design** — uses Playfair Display + Montserrat + Fira Code fonts and gold (#FFD700) accent color. Every other page uses Inter + Space Grotesk with cyan/purple accents.
- ❌ **No shared navigation** — uses standalone config.php, not site-header.inc.php. No way to navigate to other pages.
- CTA email links to `hello@example.com` — **placeholder email never updated!**
- "Back to Home" link goes to `/` but page has no relationship to the rest of the site visually
- No mention of Alfred AI or GoCodeMe — feels like a detached portfolio page
- Missing: connection to product pages, "these languages work with Alfred" messaging
- No structured data

---

### 2.9 `demo/index.html` — Demo Homepage (296 lines)

**Content:** Static HTML alternative homepage. Hero, 7 feature cards, 3-step How It Works, plan cards.

**What's Missing:**
- Static HTML — no PHP, no i18n, no dynamic WHMCS links
- Appears to be a prototype/mockup that may be outdated
- Plan names differ from main pages
- Links reference other demo/ pages rather than main PHP pages

**Assessment:** Likely a design prototype. Should be removed from production or redirected.

---

### 2.10 `demo/hosting.html` — Plans Comparison (434 lines)

**Content:** Full plan comparison table with an **Enterprise plan at $199/mo** that doesn't appear on the main pricing pages.

**What's Missing:**
- Enterprise plan pricing ($199/mo) not reflected anywhere else
- Static HTML — no WHMCS integration

---

### 2.11 `demo/pricing.html` — Pricing Page (359 lines)

**Content:** 4 plan cards (Builder $15, Professional $29, Studio $59, Business $99). Token packs and add-ons.

**What's Missing:**
- No Creator plan ($22/mo) that exists on gocodeme.php and alfred.php
- Professional marked as "MOST POPULAR" — matches index.php

---

### 2.12 `demo/features.html` — Features Page (669 lines)

**Content:** Extensive CSS for engine cards, section labels, card grids. Feature detail page.

**Assessment:** Mostly CSS with feature card layouts. Content appears to be a prototype.

---

### 2.13 `demo/contact.html` — Contact Page (312 lines)

**Content:** Contact info (Phone: 1-833-GOSITEME, Email: support@gositeme.com), contact methods grid, FAQ with accordion, CTA.

**What's Good:** Real phone number and email.

**What's Missing:** Static HTML only — no form submission capability.

---

### 2.14 `privacy-policy.php` — Privacy Policy (223 lines)

**Content:** Standard privacy policy covering PII collection, data storage (Quebec, Canada), encryption (SSL/TLS, AES-256), access control, cookies.

**Last Updated:** February 3, 2025

**What's Good:**
- Uses site-header.inc.php (consistent navigation)
- Mentions server location (Quebec, Canada)
- Covers key privacy topics

**What's Missing/Needs Updating:**
- No mention of AI data processing/retention practices (critical for AI platform)
- No mention of voice recording storage/deletion policies
- No mention of PIPEDA compliance (Canadian privacy law)
- Date may need updating after recent product additions
- Not translated to French (important for Quebec)

---

### 2.15 `terms-of-service.php` — Terms of Service (293 lines)

**Content:** Comprehensive ToS covering definitions (Platform, Tokens, Plan, Add-on, Token Pack, AI Server), account registration, eligibility (18+), account security, AI platform plans, token usage, token packs, add-ons.

**Last Updated:** July 14, 2025

**What's Good:**
- Very well-structured with highlight boxes
- Clear definitions section
- Token policies spelled out (no rollover, pack tokens don't expire, packs non-refundable after partial use)
- Uses site-header.inc.php

**What's Missing/Needs Updating:**
- Not translated to French (legally required in Quebec)
- Should cover voice product terms specifically (call recording consent, AI agent disclaimers)
- Should address A2A protocol data sharing
- Should mention HIPAA BAA availability for medical customers

---

### 2.16 `404.php` — Custom 404 Page (100 lines)

**Content:** Clean 404 page with helpful navigation, 3 upsell cards (AI Hosting, GoCodeMe IDE, AI Phone Agents), sitemap links.

**What's Good:**
- i18n support (EN/FR)
- Proper `noindex, follow` robots directive
- Smart upsell cards
- Links to key product pages
- Schema.org WebPage markup

**What's Missing:**
- "AI Web Hosting starting at $2.99/mo" — this price doesn't match any plan ($15/mo is the cheapest). Either old pricing or misleading.

---

### 2.17 `ai-servers/` — AI Server Configurator

**Content:** Custom AI-ready workstation build configurator. Users pick GPU, CPU, motherboard, RAM, storage, PSU, case. Compatibility validation and price summation. Presets: Starter AI, Pro AI, Studio, Max. Quote request system.

**What's Good:**
- Well-documented README
- API-driven architecture
- Supplier cost kept server-side (good margin protection)

**What's Missing:**
- No link from main navigation to this section
- Only referenced in index.php services section ($2,499 starting price)
- No marketing content — pure configurator
- Missing: landing page with value proposition, specs comparison, customer testimonials

---

## 3. Content Gap Analysis

### Critical Gaps

| Gap | Impact | Priority |
|-----|--------|----------|
| **No About Us page** | No company story, team, or trust signals beyond generic testimonials | 🔴 High |
| **No Blog/Content Marketing** | `/articles/` directory exists but no visible blog; zero inbound SEO content | 🔴 High |
| **No Case Studies** | No customer success stories, no verifiable social proof | 🔴 High |
| **No Documentation/Help Center** | No user guides, tutorials, or API documentation linked from marketing pages | 🔴 High |
| **No French translations** for gocodeme.php, voice.php, voice-products.php, languages.php | Quebec legal requirement; ~50% of content is English-only | 🔴 High |
| **No dedicated Pricing page** | Pricing scattered across 4+ places; no single canonical pricing URL | 🔴 High |
| **No Changelog/What's New** | No product updates page to show momentum | 🟡 Medium |
| **No Status Page** | No uptime/status page despite claiming 99.9% uptime | 🟡 Medium |
| **No comparison landing pages** | E.g., "GoSiteMe vs GoDaddy", "GoCodeMe vs Cursor" — high-intent SEO pages | 🟡 Medium |
| **No partner/reseller landing page** | Business programs mentioned everywhere but no dedicated signup flow | 🟡 Medium |
| **No onboarding/getting-started guide** | No visible first-run experience documentation | 🟡 Medium |
| **No video demos or screenshots** | Zero actual product imagery across all pages | 🟡 Medium |
| **No AI Server landing page** | $2,499+ product buried with no marketing page | 🟡 Medium |
| **No contact page in main site** | Only exists as static `demo/contact.html` | 🟡 Medium |
| **No sitemap page (HTML)** | XML sitemap exists but no user-facing sitemap | 🟢 Low |
| **No cookie consent banner** | Privacy policy mentions cookies but no consent mechanism visible | 🟢 Low |
| **No accessibility statement** | Despite promoting accessibility auditing as a feature | 🟢 Low |

### Missing Product Pages

| Product/Feature | Current State | Recommended |
|----------------|---------------|-------------|
| GoCodeMe Desktop App | Download links on gocodeme.php | Needs dedicated download page with system requirements, release notes, changelog |
| AI Image Generation | Mentioned on alfred.php | Needs standalone product page with gallery examples |
| AI Video Generation | Mentioned on alfred.php | Needs standalone product page or section |
| MCP Server | Technical docs in gocodeme/README.md | Needs developer-facing docs page |
| OpenClaw Messaging | Mentioned in architecture | No user-facing documentation |
| SSL Certificates | Mentioned in schema markup ($29/year) | No dedicated SSL product page |
| Email Hosting | Part of plans | No dedicated email hosting page |
| Token Packs | Available in WHMCS store | Could use a dedicated marketing page explaining ROI |

---

## 4. HTML/Code Bugs

| File | Line | Bug | Severity |
|------|------|-----|----------|
| `index.php` | ~249 | `<h3457</h3>` — missing `>` in stats h3 tag | 🔴 Visible rendering bug |
| `index.php` | ~870 | `style="color:#00D4FF;"457</span>` — missing `>` before "457" | 🔴 Visible rendering bug |
| `gocodeme.php` | Multiple | `<span class="accent"457</span>` — missing `>` | 🔴 Visible rendering bug |
| `gocodeme.php` | Comparison table | `style="color:var(--cyan); font-weight:800;"457` — broken attribute | 🔴 Visible rendering bug |
| `languages.php` | CTA | `mailto:hello@example.com` — placeholder email | 🔴 Broken link |
| `index.php` | Schema | `dateModified: '2026-03-03'` — future date | 🟡 SEO issue |

---

## 5. Pricing Inconsistencies

| Discrepancy | Where It Appears |
|-------------|-----------------|
| **Creator plan ($22/mo)** exists on `gocodeme.php` and `alfred.php` but is **absent** from `index.php` and `demo/pricing.html` | Cross-page |
| **Enterprise plan ($199/mo)** exists on `demo/hosting.html` but nowhere else | demo only |
| **404.php says "$2.99/mo"** but cheapest plan is Builder at $15/mo | 404.php |
| **alfred.php says "400+ tools"** but index.php & gocodeme.php say "457 tools" | Cross-page |
| **alfred.php ring section says "9 engines"** but hero says "16+ engines" | alfred.php |
| **voice-products.php hero says "52 products"** but page title says "29 Voice & AI products" (in meta) | voice-products.php |
| **index.php comparison shows "332+ features"** but other pages don't reference this number | index.php |
| **Promo codes differ**: LAUNCH50 (50% off first year) on gocodeme.php, LAUNCH50 (50% off first month) on index.php, TRAIN15 on alfred.php | Cross-page |

### Recommended Canonical Pricing

Based on frequency of appearance and WHMCS product IDs:

| Plan | Price | Tokens | WHMCS PID |
|------|-------|--------|-----------|
| Builder | $15/mo | 300K | 18 |
| Creator | $22/mo | 450K | 32 |
| Professional | $29/mo | 600K | 19 |
| Studio | $59/mo | 1.5M | 20 |
| Business | $99/mo | 3M | 21 |
| Enterprise | Custom | Custom | 22 |

---

## 6. Design & Branding Inconsistencies

| Issue | Pages Affected |
|-------|---------------|
| **languages.php uses completely different fonts** (Playfair Display, Montserrat, Fira Code) and gold accent (#FFD700) | languages.php |
| **languages.php has no shared navigation** — standalone page with only "Back to Home" link | languages.php |
| **gocodeme.php has its own header/footer** — no shared site navigation | gocodeme.php |
| **voice.php has its own header** — "GoSiteMe.com" text logo only | voice.php |
| **demo/ pages are static HTML** with their own stylesheet — different from main site | All demo/ pages |
| **Font loading**: Main site uses Inter + Space Grotesk; languages.php uses Playfair Display + Montserrat + Fira Code | languages.php |

### Consistent Elements (good)
- Dark theme (#0a0a14 background) across all PHP pages
- Cyan (#00D4FF) and purple (#7D00FF) accent colors on most pages
- Font Awesome 6 icons used consistently
- AOS scroll animations on main pages
- Glass-morphism card style consistent

---

## 7. SEO & Structured Data

### Pages WITH structured data
| Page | Schema Types |
|------|-------------|
| `index.php` | FAQPage, ItemList (plans), HowTo, Review, Service, BreadcrumbList, TechArticle, ImageObject, Offer, WebPage |
| `alfred.php` | SoftwareApplication |
| `voice.php` | WebApplication |
| `voice-products.php` | Product with AggregateOffer |
| `404.php` | WebPage |

### Pages WITHOUT structured data (should have)
| Page | Recommended Schema |
|------|-------------------|
| `gocodeme.php` | SoftwareApplication, FAQPage, Product |
| `languages.php` | ItemList (languages) |
| `privacy-policy.php` | WebPage |
| `terms-of-service.php` | WebPage |

### SEO Issues
- `gocodeme.php` has canonical pointing to `gocodeme.com` — if that domain doesn't resolve, this creates an SEO problem
- No `hreflang` tags for EN/FR language variants
- No XML sitemap entries verified for voice-products.php, alfred.php pages
- Missing Open Graph images — `hero-banner.png` referenced but may not exist as an actual screenshot

---

## 8. Content Duplication

| Duplicated Content | Pages |
|-------------------|-------|
| **Head-to-Head comparison ring charts** — exact same PHP code, same competitor data | `index.php`, `alfred.php`, `gocodeme.php` |
| **Competitor scoreboard** (Cursor 9/32, GoDaddy 6/32, etc.) | `index.php`, `alfred.php` |
| **Pricing grid** (Builder $15, Pro $29, Studio $59, Business $99) | `index.php`, `alfred.php`, `gocodeme.php`, `demo/pricing.html`, `demo/hosting.html` |
| **Token top-up packs** ($5/$19/$35/$149) | `index.php`, `alfred.php`, `gocodeme.php` |
| **Business programs** (reseller $299, affiliate 20%, agency) | `index.php`, `alfred.php`, `voice-products.php`, `gocodeme.php` |
| **Voice & AI products showcase** | `index.php`, `alfred.php` |
| **Enterprise industry cards** (SaaS, e-com, health, etc.) | `index.php` |
| **FAQ items** (overlapping topics) | `index.php` (6), `alfred.php` (25), `gocodeme.php` (15) |

### Recommendation
Extract shared sections (pricing, comparison, business programs, token packs) into PHP includes. This ensures consistency and makes updates one-change-everywhere.

---

## 9. Recommendations

### Immediate Fixes (Week 1)

1. **Fix HTML bugs** — The "457" rendering bugs on index.php and gocodeme.php are visible to users
2. **Fix languages.php email** — Change `hello@example.com` to `support@gositeme.com`
3. **Standardize tool count** — Pick either "400+" or "457" and use everywhere
4. **Fix 404.php pricing** — Change "$2.99/mo" to "$15/mo" or remove
5. **Fix schema date** — Change `2026-03-03` to actual date
6. **Add Creator plan to index.php** — Major pricing gap

### Short-Term (Weeks 2-4)

7. **Add French translations** to gocodeme.php, voice-products.php, and at minimum complete alfred.php's missing FR strings
8. **Integrate languages.php** into main site template (site-header.inc.php, correct fonts/colors)
9. **Add shared navigation** to gocodeme.php and voice.php
10. **Create a dedicated /pricing page** that's the single source of truth for all plans
11. **Create a /contact page** (PHP, not static HTML) with actual form submission
12. **Add actual product screenshots/videos** — the entire site has zero real imagery
13. **Extract shared sections** (pricing, comparison, business programs) into PHP includes

### Medium-Term (Months 1-2)

14. **Create an About Us page** with company story, team, mission
15. **Launch blog/articles section** with 5-10 SEO-targeted articles
16. **Create 2-3 case studies** with real customer stories
17. **Build comparison pages** (GoSiteMe vs GoDaddy, GoCodeMe vs Cursor, etc.)
18. **Create AI Server landing page** with use cases and ROI calculator
19. **Add hreflang tags** for EN/FR language alternatives
20. **Create a status page** (even a simple one at status.gositeme.com)
21. **Add structured data** to gocodeme.php, languages.php, legal pages
22. **Remove or redirect demo/ directory** — prototype content that may confuse users/search engines

### Long-Term (Quarter)

23. **Build documentation/help center** linked from main navigation
24. **Add live chat widget** to all marketing pages
25. **Create partner/reseller portal** with dedicated signup flow
26. **Build ROI/pricing calculators** for voice products
27. **Add cookie consent mechanism** with privacy policy link
28. **Create accessibility statement** page
29. **Set up Google Reviews, Trustpilot, G2 profiles** to back up the review claims

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Total marketing pages audited | 16 |
| Pages with i18n (EN/FR) | 4 of 16 (25%) |
| Pages using site-header.inc.php | 8 of 16 (50%) |
| Pages with structured data | 5 of 16 (31%) |
| HTML bugs found | 6 |
| Pricing inconsistencies | 8 |
| Critical content gaps | 7 |
| Total products listed (voice) | ~50 |
| Total plans offered | 5 (+Enterprise custom) |
| Total promo codes in use | 3 (LAUNCH50, ANNUAL20, TRAIN15) |
