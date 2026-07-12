/**
 * prismEngine.js — PRISM: Visual Intelligence Engine
 *
 * Design analysis, color theory, typography, layout evaluation,
 * accessibility contrast checking, responsive design validation,
 * and visual design scoring — Alfred's eye for aesthetics.
 *
 * Intelligence Type: Visual / Spatial
 * Tools: 9
 */

import { promises as fs } from 'node:fs';
import path from 'node:path';

const designSystems = new Map(); // user → {tokens}

// ── Color utilities ─────────────────────────────────────────────────────
function hexToRgb(hex) {
  const h = hex.replace('#', '');
  return { r: parseInt(h.substring(0, 2), 16), g: parseInt(h.substring(2, 4), 16), b: parseInt(h.substring(4, 6), 16) };
}
function relativeLuminance(rgb) {
  const [rs, gs, bs] = [rgb.r, rgb.g, rgb.b].map(c => {
    const s = c / 255;
    return s <= 0.03928 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
  });
  return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}
function contrastRatio(hex1, hex2) {
  const l1 = relativeLuminance(hexToRgb(hex1));
  const l2 = relativeLuminance(hexToRgb(hex2));
  const lighter = Math.max(l1, l2), darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}
function hexToHsl(hex) {
  const { r, g, b } = hexToRgb(hex);
  const rn = r / 255, gn = g / 255, bn = b / 255;
  const max = Math.max(rn, gn, bn), min = Math.min(rn, gn, bn);
  let h = 0, s = 0, l = (max + min) / 2;
  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    if (max === rn) h = ((gn - bn) / d + (gn < bn ? 6 : 0)) / 6;
    else if (max === gn) h = ((bn - rn) / d + 2) / 6;
    else h = ((rn - gn) / d + 4) / 6;
  }
  return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
}

/**
 * Analyze color palette — harmony, balance, accessibility
 */
export async function analyzeColors(colors) {
  const analysis = colors.map(hex => {
    const hsl = hexToHsl(hex);
    return {
      hex, hsl,
      warmth: (hsl.h >= 0 && hsl.h <= 60) || hsl.h >= 300 ? 'warm' : 'cool',
      brightness: hsl.l > 70 ? 'light' : hsl.l > 30 ? 'medium' : 'dark',
      saturation: hsl.s > 70 ? 'vivid' : hsl.s > 30 ? 'moderate' : 'muted'
    };
  });
  const hues = analysis.map(a => a.hsl.h);
  const uniqueHues = new Set(hues.map(h => Math.round(h / 30) * 30));
  let harmony = 'custom';
  if (uniqueHues.size === 1) harmony = 'monochromatic';
  else if (hues.length === 2 && Math.abs(hues[0] - hues[1]) > 150) harmony = 'complementary';
  else if (uniqueHues.size <= 3) harmony = 'analogous';
  else harmony = 'diverse';
  return {
    palette: analysis,
    harmony,
    warmth_balance: {
      warm: analysis.filter(a => a.warmth === 'warm').length,
      cool: analysis.filter(a => a.warmth === 'cool').length
    },
    accessibility_note: 'Use prism_check_contrast to verify text/background pairs',
    recommendations: [
      harmony === 'diverse' && 'Consider reducing to 3-5 core colors for cohesion',
      analysis.every(a => a.brightness === 'dark') && 'All dark — add a light accent for contrast',
      analysis.every(a => a.brightness === 'light') && 'All light — add a dark anchor color'
    ].filter(Boolean)
  };
}

/**
 * Suggest color palettes based on mood/theme
 */
export async function suggestPalette(mood, count = 5) {
  const palettes = {
    professional: { colors: ['#1a1a2e', '#16213e', '#0f3460', '#e94560', '#f5f5f5'], mood: 'Trust, authority, competence' },
    playful: { colors: ['#ff6b6b', '#feca57', '#48dbfb', '#ff9ff3', '#54a0ff'], mood: 'Fun, creative, youthful' },
    nature: { colors: ['#2d6a4f', '#40916c', '#52b788', '#74c69d', '#b7e4c7'], mood: 'Growth, calm, organic' },
    luxury: { colors: ['#0d0d0d', '#1a1a1a', '#b8860b', '#d4af37', '#f5f0e1'], mood: 'Exclusive, premium, sophisticated' },
    tech: { colors: ['#0a0a23', '#1b1b32', '#2a2a4a', '#6b5ce7', '#00d4ff'], mood: 'Innovation, futurism, intelligence' },
    health: { colors: ['#e0fbfc', '#98c1d9', '#3d5a80', '#293241', '#ee6c4d'], mood: 'Wellness, trust, vitality' },
    energy: { colors: ['#ff0054', '#ffd600', '#ff6600', '#c70039', '#900c3f'], mood: 'Power, urgency, passion' },
    calm: { colors: ['#f0f4f8', '#d9e2ec', '#bcccdc', '#9fb3c8', '#627d98'], mood: 'Serenity, trust, clarity' },
    dark_mode: { colors: ['#0d1117', '#161b22', '#21262d', '#30363d', '#58a6ff'], mood: 'Modern, developer-friendly' },
    sunset: { colors: ['#2b2d42', '#8d99ae', '#edf2f4', '#ef233c', '#d90429'], mood: 'Dramatic, emotional, warm' }
  };
  const p = palettes[mood] || palettes.professional;
  return {
    mood, palette: p.colors.slice(0, count).map((c, i) => ({
      hex: c, role: ['primary', 'secondary', 'accent', 'highlight', 'background'][i], hsl: hexToHsl(c)
    })),
    emotional_tone: p.mood,
    css_variables: p.colors.slice(0, count).reduce((acc, c, i) => { acc[`--color-${['primary','secondary','accent','highlight','bg'][i]}`] = c; return acc; }, {}),
    available_moods: Object.keys(palettes)
  };
}

/**
 * Check color contrast for WCAG accessibility
 */
export async function checkContrast(foreground, background) {
  const ratio = contrastRatio(foreground, background);
  const rounded = Math.round(ratio * 100) / 100;
  return {
    foreground, background,
    contrast_ratio: `${rounded}:1`,
    wcag_aa_normal: rounded >= 4.5 ? 'PASS' : 'FAIL',
    wcag_aa_large: rounded >= 3 ? 'PASS' : 'FAIL',
    wcag_aaa_normal: rounded >= 7 ? 'PASS' : 'FAIL',
    wcag_aaa_large: rounded >= 4.5 ? 'PASS' : 'FAIL',
    rating: rounded >= 7 ? 'Excellent' : rounded >= 4.5 ? 'Good' : rounded >= 3 ? 'Acceptable for large text only' : 'Poor — needs improvement',
    fix_suggestion: rounded < 4.5 ? `Increase contrast: darken the foreground or lighten the background` : null
  };
}

/**
 * Analyze page layout / visual hierarchy
 */
export async function analyzeLayout(htmlOrUrl, options = {}) {
  // Heuristic-based layout analysis for HTML content
  const html = htmlOrUrl;
  const elements = {
    h1: (html.match(/<h1/gi) || []).length,
    h2: (html.match(/<h2/gi) || []).length,
    h3: (html.match(/<h3/gi) || []).length,
    images: (html.match(/<img/gi) || []).length,
    links: (html.match(/<a /gi) || []).length,
    buttons: (html.match(/<button/gi) || []).length,
    forms: (html.match(/<form/gi) || []).length,
    sections: (html.match(/<section/gi) || []).length,
    nav: (html.match(/<nav/gi) || []).length,
    footer: (html.match(/<footer/gi) || []).length,
    videos: (html.match(/<video/gi) || []).length
  };
  const issues = [];
  if (elements.h1 === 0) issues.push({ severity: 'high', issue: 'Missing H1 — critical for hierarchy and SEO' });
  if (elements.h1 > 1) issues.push({ severity: 'medium', issue: `Multiple H1 tags (${elements.h1}) — use only one per page` });
  if (elements.images > 0 && !(/<img[^>]+alt=/i.test(html))) issues.push({ severity: 'high', issue: 'Images missing alt attributes' });
  if (elements.nav === 0) issues.push({ severity: 'medium', issue: 'No <nav> element — add for accessibility' });
  if (elements.buttons === 0 && elements.links > 5) issues.push({ severity: 'low', issue: 'Many links but no buttons — consider primary CTAs' });
  return {
    element_count: elements,
    hierarchy_score: Math.max(0, 100 - issues.length * 15),
    issues,
    structure_summary: {
      has_clear_hierarchy: elements.h1 === 1 && elements.h2 >= 1,
      has_navigation: elements.nav >= 1,
      has_footer: elements.footer >= 1,
      cta_count: elements.buttons + (html.match(/class="[^"]*cta[^"]*"/gi) || []).length,
      media_rich: elements.images + elements.videos > 3
    }
  };
}

/**
 * Generate/manage design system tokens
 */
export async function designSystem(user, action, tokens = {}) {
  if (action === 'set') {
    const system = {
      colors: tokens.colors || { primary: '#6b5ce7', secondary: '#00d4ff', accent: '#ff6b6b', bg: '#0a0a23', text: '#f5f5f5' },
      spacing: tokens.spacing || { xs: '4px', sm: '8px', md: '16px', lg: '24px', xl: '32px', xxl: '48px' },
      typography: tokens.typography || {
        fontFamily: { heading: 'Inter, sans-serif', body: 'system-ui, sans-serif', mono: 'JetBrains Mono, monospace' },
        fontSize: { xs: '12px', sm: '14px', base: '16px', lg: '18px', xl: '24px', xxl: '32px', hero: '48px' },
        fontWeight: { normal: 400, medium: 500, semibold: 600, bold: 700 }
      },
      borders: tokens.borders || { radius: { sm: '4px', md: '8px', lg: '16px', full: '9999px' }, width: '1px' },
      shadows: tokens.shadows || { sm: '0 1px 2px rgba(0,0,0,0.1)', md: '0 4px 8px rgba(0,0,0,0.15)', lg: '0 8px 24px rgba(0,0,0,0.2)' },
      updatedAt: new Date().toISOString()
    };
    designSystems.set(user, system);
    return { action: 'set', system };
  }
  if (action === 'get') return { system: designSystems.get(user) || null };
  if (action === 'export_css') {
    const sys = designSystems.get(user);
    if (!sys) return { error: 'No design system defined. Use action "set" first.' };
    const vars = [
      ...Object.entries(sys.colors).map(([k, v]) => `  --color-${k}: ${v};`),
      ...Object.entries(sys.spacing).map(([k, v]) => `  --space-${k}: ${v};`),
      ...Object.entries(sys.typography.fontSize).map(([k, v]) => `  --font-${k}: ${v};`),
      ...Object.entries(sys.borders.radius).map(([k, v]) => `  --radius-${k}: ${v};`),
    ];
    return { css: `:root {\n${vars.join('\n')}\n}` };
  }
  return { hint: 'Actions: set, get, export_css' };
}

/**
 * Check responsive design breakpoints
 */
export async function responsiveCheck(cssOrHtml) {
  const content = cssOrHtml;
  const mediaQueries = content.match(/@media[^{]+/g) || [];
  const breakpoints = mediaQueries.map(mq => {
    const width = mq.match(/(\d+)px/);
    return { query: mq.trim(), pixel: width ? parseInt(width[1]) : null };
  }).filter(b => b.pixel);
  const standard = { mobile: 480, tablet: 768, desktop: 1024, wide: 1440 };
  const covered = {};
  for (const [name, px] of Object.entries(standard)) {
    covered[name] = breakpoints.some(b => Math.abs(b.pixel - px) <= 100);
  }
  return {
    breakpoints_found: breakpoints.length,
    breakpoints: breakpoints,
    standard_coverage: covered,
    has_mobile: covered.mobile || breakpoints.some(b => b.pixel <= 640),
    has_tablet: covered.tablet,
    has_desktop: covered.desktop,
    viewport_meta: content.includes('viewport') ? 'present' : 'MISSING — add <meta name="viewport">',
    score: Object.values(covered).filter(Boolean).length * 25,
    recommendations: [
      !covered.mobile && 'Add mobile breakpoint (≤480px)',
      !covered.tablet && 'Add tablet breakpoint (≤768px)',
      !content.includes('viewport') && 'Add viewport meta tag for mobile rendering'
    ].filter(Boolean)
  };
}

/**
 * Suggest/analyze typography pairings
 */
export async function typography(primary = '', style = 'modern') {
  const pairings = {
    modern: [
      { heading: 'Inter', body: 'Inter', mono: 'JetBrains Mono', vibe: 'Clean, versatile, tech' },
      { heading: 'Space Grotesk', body: 'DM Sans', mono: 'Fira Code', vibe: 'Geometric, contemporary' },
      { heading: 'Outfit', body: 'Plus Jakarta Sans', mono: 'Source Code Pro', vibe: 'Friendly modern' }
    ],
    classic: [
      { heading: 'Playfair Display', body: 'Source Serif Pro', mono: 'Courier Prime', vibe: 'Elegant, editorial' },
      { heading: 'Merriweather', body: 'Open Sans', mono: 'IBM Plex Mono', vibe: 'Readable, trustworthy' },
      { heading: 'Cormorant Garamond', body: 'Lora', mono: 'DM Mono', vibe: 'Luxury, refined' }
    ],
    bold: [
      { heading: 'Bebas Neue', body: 'Roboto', mono: 'Ubuntu Mono', vibe: 'Impactful, attention-grabbing' },
      { heading: 'Oswald', body: 'Lato', mono: 'Cascadia Code', vibe: 'Strong, athletic' },
      { heading: 'Anton', body: 'Nunito', mono: 'Hack', vibe: 'Bold, energetic' }
    ],
    minimal: [
      { heading: 'Helvetica Neue', body: 'system-ui', mono: 'SF Mono', vibe: 'Invisible, functional' },
      { heading: 'Archivo', body: 'Work Sans', mono: 'Roboto Mono', vibe: 'Understated, precise' }
    ]
  };
  const pairs = pairings[style] || pairings.modern;
  return {
    style,
    recommended_pairings: pairs,
    type_scale: { base: '16px', ratio: 1.25, sizes: ['12px', '14px', '16px', '20px', '25px', '31px', '39px'] },
    rules: ['Max 2-3 fonts per project', 'Heading + body should contrast but not clash', 'Use weight (bold/light) for hierarchy within a family'],
    available_styles: Object.keys(pairings)
  };
}

/**
 * Score visual design quality (1-100)
 */
export async function visualScore(htmlOrCss) {
  const content = htmlOrCss;
  let score = 50; // Start at 50
  const checks = [];
  // Color usage
  const colors = content.match(/#[0-9a-fA-F]{3,6}/g) || [];
  const uniqueColors = new Set(colors);
  if (uniqueColors.size <= 5) { score += 10; checks.push('Good: Limited color palette'); }
  else if (uniqueColors.size > 15) { score -= 10; checks.push('Issue: Too many colors (>15) — simplify palette'); }
  // Typography
  if (content.includes('font-family')) { score += 5; checks.push('Good: Custom typography defined'); }
  // Spacing consistency
  const margins = content.match(/margin:\s*\d+px/g) || [];
  if (margins.length > 0) { score += 5; checks.push('Good: Margin/spacing defined'); }
  // Responsive
  if (content.includes('@media')) { score += 10; checks.push('Good: Responsive breakpoints present'); }
  if (content.includes('viewport')) { score += 5; checks.push('Good: Viewport meta present'); }
  // Accessibility
  if (content.includes('alt=')) { score += 5; checks.push('Good: Image alt attributes found'); }
  if (content.includes('aria-')) { score += 5; checks.push('Good: ARIA attributes found'); }
  // Animations
  if (content.includes('transition') || content.includes('animation')) { score += 5; checks.push('Good: Transitions/animations present'); }
  // Shadows
  if (content.includes('box-shadow')) { score += 5; checks.push('Good: Depth via shadows'); }
  score = Math.max(0, Math.min(100, score));
  return {
    score,
    grade: score >= 90 ? 'A+' : score >= 80 ? 'A' : score >= 70 ? 'B' : score >= 60 ? 'C' : score >= 50 ? 'D' : 'F',
    checks,
    unique_colors: uniqueColors.size,
    has_responsive: content.includes('@media'),
    has_accessibility: content.includes('aria-') || content.includes('alt='),
    improvements: [
      score < 70 && 'Reduce color count to 3-5 core colors',
      !content.includes('@media') && 'Add responsive breakpoints',
      !content.includes('aria-') && 'Add ARIA attributes for accessibility',
      !content.includes('transition') && 'Add subtle transitions for polish'
    ].filter(Boolean)
  };
}

/**
 * Suggest icons for features/concepts
 */
export async function iconSuggest(concepts) {
  const iconMap = {
    security: { emoji: '🔒', lucide: 'shield-check', heroicon: 'ShieldCheckIcon', fa: 'fa-shield-halved' },
    speed: { emoji: '⚡', lucide: 'zap', heroicon: 'BoltIcon', fa: 'fa-bolt' },
    analytics: { emoji: '📊', lucide: 'bar-chart-3', heroicon: 'ChartBarIcon', fa: 'fa-chart-bar' },
    user: { emoji: '👤', lucide: 'user', heroicon: 'UserIcon', fa: 'fa-user' },
    settings: { emoji: '⚙️', lucide: 'settings', heroicon: 'CogIcon', fa: 'fa-gear' },
    search: { emoji: '🔍', lucide: 'search', heroicon: 'MagnifyingGlassIcon', fa: 'fa-search' },
    email: { emoji: '📧', lucide: 'mail', heroicon: 'EnvelopeIcon', fa: 'fa-envelope' },
    database: { emoji: '🗄️', lucide: 'database', heroicon: 'CircleStackIcon', fa: 'fa-database' },
    ai: { emoji: '🤖', lucide: 'brain', heroicon: 'SparklesIcon', fa: 'fa-robot' },
    code: { emoji: '💻', lucide: 'code', heroicon: 'CodeBracketIcon', fa: 'fa-code' },
    deploy: { emoji: '🚀', lucide: 'rocket', heroicon: 'RocketLaunchIcon', fa: 'fa-rocket' },
    money: { emoji: '💰', lucide: 'dollar-sign', heroicon: 'CurrencyDollarIcon', fa: 'fa-dollar-sign' },
    time: { emoji: '⏰', lucide: 'clock', heroicon: 'ClockIcon', fa: 'fa-clock' },
    cloud: { emoji: '☁️', lucide: 'cloud', heroicon: 'CloudIcon', fa: 'fa-cloud' },
    file: { emoji: '📁', lucide: 'folder', heroicon: 'FolderIcon', fa: 'fa-folder' },
    heart: { emoji: '❤️', lucide: 'heart', heroicon: 'HeartIcon', fa: 'fa-heart' },
    star: { emoji: '⭐', lucide: 'star', heroicon: 'StarIcon', fa: 'fa-star' },
    globe: { emoji: '🌐', lucide: 'globe', heroicon: 'GlobeAltIcon', fa: 'fa-globe' },
    lock: { emoji: '🔐', lucide: 'lock', heroicon: 'LockClosedIcon', fa: 'fa-lock' },
    check: { emoji: '✅', lucide: 'check-circle', heroicon: 'CheckCircleIcon', fa: 'fa-check-circle' }
  };
  const results = (Array.isArray(concepts) ? concepts : [concepts]).map(concept => {
    const lc = concept.toLowerCase();
    const match = Object.entries(iconMap).find(([key]) => lc.includes(key));
    return {
      concept,
      suggestion: match ? match[1] : { emoji: '🔧', lucide: 'tool', heroicon: 'WrenchIcon', fa: 'fa-wrench' },
      matched_keyword: match ? match[0] : 'default'
    };
  });
  return { suggestions: results, icon_libraries: ['Lucide React', 'Heroicons', 'Font Awesome 6', 'Phosphor Icons'] };
}
