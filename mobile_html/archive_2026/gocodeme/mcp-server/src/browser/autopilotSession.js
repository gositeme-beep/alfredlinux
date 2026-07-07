/**
 * autopilotSession.js — Persistent Browser Sessions for Alfred Autopilot
 * ═══════════════════════════════════════════════════════════════════════
 * v9.0.0 — Ultimate Human-Like Agentic Browser
 *
 *   P0  Max step / duration guardrails
 *   P1  Human-in-the-loop (pause & confirm before acting)
 *   P1  Cursor overlay coordinates for visual feedback
 *   P1  Multi-tab support (track & switch tabs)
 *   P2  Screenshot history / replay timeline / DVR
 *   P2  Cookie persistence across sessions
 *   P2  Download / upload file handling
 *   P3  Network request interception & exposure
 *   P3  Mobile / tablet / desktop viewport presets
 *
 *   HUMAN-CENTRIC:
 *   H1  Sensitive field masking (password/CC/SSN auto-masked in screenshots)
 *   H2  Action preview cards (pre-action element crop)
 *   H3  Confidence indicator (selector match quality scoring)
 *   H4  Undo / rollback (navigate back, undo last action)
 *   H5  Session replay / DVR (full timeline with scrubber)
 *   H6  Stuck / sentiment detection (detects loops, failures, progress)
 *   H7  Celebration moments (micro-events on task success)
 *   H8  Frustration detection (rapid reject/close patterns)
 *   H9  Geo-fence domain restriction
 *   H10 Data retention controls (session/24h/permanent/never-screenshot)
 *   H11 Shared sessions (spectator count tracking)
 *   H12 Annotation mode (markers on viewport)
 *   H13 Task templates (save/load reusable session scripts)
 *   H14 Batch operations (queued multi-task execution)
 *   H15 Scheduled autopilot (cron-like, checked externally)
 *   H16 Smart wait (spinner/skeleton/CAPTCHA detection)
 *   H17 Screen reader narration (ARIA action descriptions)
 *   H18 High-contrast mode support
 *   H19 Keyboard-only operation
 *   H20 Human-like mouse movement (Bézier curves, jitter, overshoot)
 *   H21 Realistic typing (per-keystroke delays, typos, corrections)
 *   H22 User-Agent rotation (randomized real browser fingerprints)
 *   H23 Browser stealth (navigator.webdriver, canvas, WebGL, plugin masking)
 *   H24 Random action delays (human-paced timing between actions)
 *   H25 Smooth scrolling (inertia, acceleration, variable speed)
 *   H26 Dialog auto-handling (alert/confirm/prompt interception)
 *   H27 iframe traversal (cross-frame element interaction)
 *   H28 Drag-and-drop (full mouse-based drag gestures)
 *   H29 Right-click / context menu interaction
 *   H30 Touch event simulation (mobile tap, swipe, pinch)
 *   H31 Geolocation spoofing (GPS coordinate override)
 *   H32 Proxy support (HTTP/SOCKS/rotating proxy chains)
 *   H33 localStorage/sessionStorage persistence
 *   H34 PDF generation from pages
 *   H35 Session video recording (continuous)
 *   H36 CAPTCHA solving integration (2Captcha/Anti-Captcha)
 *
 *   Security: SSRF blocking, script sandboxing, concurrency lock,
 *             browser leak protection, bounded history
 * ═══════════════════════════════════════════════════════════════════════
 */

import { chromium } from 'playwright';
import { EventEmitter } from 'events';
import { readFile, writeFile, mkdir, readdir, unlink, stat } from 'fs/promises';
import { join } from 'path';
import os from 'os';

if (!process.env.PLAYWRIGHT_BROWSERS_PATH) {
  process.env.PLAYWRIGHT_BROWSERS_PATH = `${os.homedir()}/.playwright-browsers`;
}

// ── Constants ──────────────────────────────────────────────────────────────

const sessions = new Map();            // daUsername → AutopilotSession
const SCREENSHOT_INTERVAL = 500;       // 2 FPS live stream
const MAX_HISTORY = 200;               // Cap action history array
const MAX_SCREENSHOT_HISTORY = 100;    // Cap screenshot timeline
const MAX_UNDO = 20;                   // Undo stack depth
const MAX_NETWORK_LOG = 200;
const MAX_ANNOTATIONS = 50;
const STUCK_THRESHOLD = 4;             // Same URL N times = stuck
const COOKIE_DIR = join(os.homedir(), '.gocodeme', 'autopilot-cookies');
const DOWNLOAD_DIR = join(os.tmpdir(), 'gocodeme-autopilot-downloads');
const TEMPLATE_DIR = join(os.homedir(), '.gocodeme', 'autopilot-templates');
const SCHEDULE_DIR = join(os.homedir(), '.gocodeme', 'autopilot-schedules');
const STORAGE_DIR  = join(os.homedir(), '.gocodeme', 'autopilot-storage');
const PDF_DIR      = join(os.tmpdir(), 'gocodeme-autopilot-pdfs');
const VIDEO_DIR    = join(os.tmpdir(), 'gocodeme-autopilot-videos');

// ── H22: User-Agent pool — real-world browsers, rotated per session ──────
const USER_AGENTS = [
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Safari/605.1.15',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
  'Mozilla/5.0 (X11; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36 OPR/114.0.0.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36',
];

// ── H22: Matching screen resolutions for each UA profile ─────────────────
const SCREEN_PROFILES = [
  { width: 1920, height: 1080, colorDepth: 24 },
  { width: 1440, height: 900,  colorDepth: 24 },
  { width: 2560, height: 1440, colorDepth: 30 },
  { width: 1366, height: 768,  colorDepth: 24 },
  { width: 1536, height: 864,  colorDepth: 24 },
  { width: 1680, height: 1050, colorDepth: 24 },
];

// ── H24: Human-paced timing ranges (milliseconds) ───────────────────────
const HUMAN_DELAYS = {
  beforeClick:   { min: 120, max: 450 },
  beforeType:    { min: 200, max: 600 },
  betweenKeys:   { min: 35,  max: 180 },
  beforeScroll:  { min: 100, max: 300 },
  afterNavigate: { min: 500, max: 1500 },
  afterClick:    { min: 150, max: 400 },
  beforeHover:   { min: 80,  max: 250 },
  pagePause:     { min: 800, max: 2500 },
};

// ── H31: Geolocation presets ─────────────────────────────────────────────
const GEO_PRESETS = {
  montreal:    { latitude: 45.5017, longitude: -73.5673, accuracy: 50 },
  toronto:     { latitude: 43.6532, longitude: -79.3832, accuracy: 50 },
  newyork:     { latitude: 40.7128, longitude: -74.0060, accuracy: 50 },
  london:      { latitude: 51.5074, longitude: -0.1278,  accuracy: 50 },
  paris:       { latitude: 48.8566, longitude: 2.3522,   accuracy: 50 },
  tokyo:       { latitude: 35.6762, longitude: 139.6503, accuracy: 50 },
  sydney:      { latitude: -33.8688, longitude: 151.2093, accuracy: 50 },
  sanfrancisco:{ latitude: 37.7749, longitude: -122.4194, accuracy: 50 },
};

const VIEWPORT_PRESETS = {
  desktop:     { width: 1280, height: 800 },
  laptop:      { width: 1366, height: 768 },
  tablet:      { width: 768, height: 1024 },
  tablet_land: { width: 1024, height: 768 },
  mobile:      { width: 375, height: 812 },
  mobile_land: { width: 812, height: 375 },
  '4k':        { width: 1920, height: 1080 },
};

const DEFAULT_OPTS = {
  maxSteps: 50,           // P0: Guardrail — auto-stop after N actions
  maxDuration: 600_000,   // P0: 10 minutes max session time
  idleTimeout: 300_000,   // 5 min idle timeout
  viewport: 'desktop',
  humanApproval: false,   // P1: When true, actions pause for user confirmation
  persistCookies: false,  // P2: Save/restore cookies between sessions
  allowedDomains: [],     // H9: Geo-fence — empty = all public domains allowed
  sensitiveFieldMasking: true, // H1: Auto-mask passwords/CC/SSN in screenshots
  retentionPolicy: 'session',  // H10: 'session' | '24h' | 'permanent' | 'no-screenshots'
  smartWait: true,        // H16: Auto-wait for spinners to disappear before acting
  highContrast: false,    // H18: Inject high-contrast CSS overlay
  // H20-H36: Ultimate human-like capabilities
  humanMouse: true,       // H20: Bézier curve mouse paths with jitter/overshoot
  humanTyping: true,      // H21: Per-keystroke delays, occasional typos + corrections
  rotateUserAgent: true,  // H22: Random UA per session from real browser pool
  stealthMode: true,      // H23: Patch navigator.webdriver, canvas, WebGL, plugins
  humanDelays: true,      // H24: Random pauses between actions (120-2500ms)
  smoothScroll: true,     // H25: Scroll with inertia/acceleration like a real human
  autoDialogs: true,      // H26: Auto-handle alert/confirm/prompt dialogs
  iframeAccess: true,     // H27: Enable cross-frame element interactions
  geolocation: null,      // H31: {latitude, longitude, accuracy} or preset name
  proxy: null,            // H32: {server, username?, password?} or 'http://host:port'
  persistStorage: false,  // H33: Save/restore localStorage/sessionStorage
  videoRecording: false,  // H35: Record continuous session video
  captchaApiKey: null,    // H36: 2Captcha/Anti-Captcha API key
  captchaService: null,   // H36: '2captcha' | 'anticaptcha'
};

const BLOCKED_URL_PATTERNS = [
  /^file:/i, /^data:/i, /^ftp:/i,
  /169\.254\.169\.254/, /metadata\.google\.internal/,
  /localhost/i, /127\.0\.0\./, /0\.0\.0\.0/, /\[::1\]/,
  /10\.(\d+\.){2}\d+/, /172\.(1[6-9]|2\d|3[01])\./, /192\.168\./,
];

// H1: Patterns for sensitive fields → auto-masking
const SENSITIVE_FIELD_SELECTORS = [
  'input[type="password"]',
  'input[autocomplete*="cc-"]',
  'input[autocomplete="credit-card"]',
  'input[name*="card-number"]', 'input[name*="cardnumber"]', 'input[name*="cardNumber"]',
  'input[name*="cvv"]', 'input[name*="cvc"]', 'input[name*="csv"]',
  'input[name*="ssn"]', 'input[name*="social-security"]',
  'input[name*="routing"]', 'input[name*="account-number"]',
  'input[autocomplete="one-time-code"]', 'input[autocomplete="otp"]',
];

// H16: Spinner/loading patterns
const SPINNER_SELECTORS = [
  '.loading', '.spinner', '.loader', '[aria-busy="true"]',
  '.skeleton', '.shimmer', '[data-loading]', '[data-loading="true"]',
  '.MuiCircularProgress-root', '.MuiLinearProgress-root',
  '.ant-spin', '.ant-skeleton', '.chakra-spinner',
  '.v-progress-circular', '.el-loading-mask',
  'progress:not([value])',
  '[class*="loading"]', '[class*="spinner"]',
];

export const autopilotEvents = new EventEmitter();
autopilotEvents.setMaxListeners(50);

// ── AutopilotSession class ─────────────────────────────────────────────────

class AutopilotSession {
  constructor(daUsername, opts = {}) {
    this.daUsername = daUsername;
    this.opts = { ...DEFAULT_OPTS, ...opts };
    this.browser = null;
    this.context = null;
    this.page = null;           // "active" page (current tab)
    this.pages = [];            // P1: all open tabs
    this.activeTabIndex = 0;
    this.lastActivity = Date.now();
    this.startTime = Date.now();
    this.screenshotTimer = null;
    this.idleTimer = null;
    this.durationTimer = null;  // P0: max-duration hard stop
    this.stepCount = 0;
    this.history = [];          // [{step, action, selector, value, url, timestamp}]
    this.screenshotHistory = []; // P2: [{step, url, timestamp, buffer}]
    this.networkLog = [];       // P3: captured XHR/fetch responses
    this.downloads = [];        // P2: [{filename, path, size, timestamp}]
    this.alive = false;
    this.lastScreenshot = null;      // raw Buffer
    this.lastScreenshotB64 = null;   // cached base64
    this.currentUrl = '';
    this.taskDescription = '';
    this._actionLock = false;
    this._paused = false;       // P1: human-in-the-loop pause state
    this._pendingAction = null; // P1: action waiting for human approval
    this._approvalResolve = null;
    this._lastClickCoords = null; // P1: {x, y} for cursor overlay
    this._currentViewport = VIEWPORT_PRESETS[this.opts.viewport] || VIEWPORT_PRESETS.desktop;

    // H4: Undo / rollback
    this._undoStack = [];         // [{url, scrollY, step, timestamp}]

    // H6: Sentiment / stuck detection
    this._sentiment = 'neutral';  // 'neutral' | 'progressing' | 'stuck' | 'failing'
    this._stuckCounter = 0;
    this._failCount = 0;
    this._successCount = 0;
    this._lastUrls = [];          // rolling window
    this._sentimentHistory = [];  // [{sentiment, step, timestamp}]

    // H8: Frustration detection
    this._rapidRejectCount = 0;
    this._lastRejectTime = 0;
    this._frustrationLevel = 0;  // 0-100

    // H3: Confidence scoring
    this._confidenceScore = 1.0;  // 0.0 - 1.0

    // H12: Annotations
    this._annotations = [];       // [{id, x, y, text, color, timestamp}]
    this._annotationIdCounter = 0;

    // H11: Shared sessions
    this._spectatorCount = 0;

    // H7: Celebrations
    this._celebrationPending = null; // { type, message }

    // H14: Batch queue
    this._batchQueue = [];
    this._batchIndex = 0;
    this._batchActive = false;

    // H2: Preview
    this._lastPreview = null; // { imageB64, selector, action }

    // H17: Narration
    this._narrationQueue = [];  // [{text, priority}]

    // H20: Human mouse state
    this._mouseX = 0;
    this._mouseY = 0;

    // H26: Dialog log
    this._dialogLog = [];       // [{type, message, response, timestamp}]

    // H33: Storage persistence paths
    this._storageDir = STORAGE_DIR;

    // H34: PDF generation
    this._pdfDir = PDF_DIR;

    // H35: Video recording
    this._videoPath = null;

    // H36: CAPTCHA tracking
    this._captchasSolved = 0;
  }

  // ── Lifecycle ────────────────────────────────────────────────────────────

  async start(taskDescription = '') {
    this.taskDescription = taskDescription;
    try {
      // H32: Proxy support
      const launchOpts = {
        headless: true,
        args: [
          '--no-sandbox', '--disable-setuid-sandbox',
          '--disable-dev-shm-usage', '--disable-gpu', '--disable-extensions',
          '--disable-blink-features=AutomationControlled', // H23: Hide automation flag
        ],
      };
      if (this.opts.proxy) {
        const proxyConfig = typeof this.opts.proxy === 'string'
          ? { server: this.opts.proxy }
          : this.opts.proxy;
        launchOpts.proxy = proxyConfig;
      }

      this.browser = await chromium.launch(launchOpts);

      await mkdir(DOWNLOAD_DIR, { recursive: true }).catch(() => {});
      await mkdir(TEMPLATE_DIR, { recursive: true }).catch(() => {});
      await mkdir(STORAGE_DIR, { recursive: true }).catch(() => {});
      await mkdir(PDF_DIR, { recursive: true }).catch(() => {});
      await mkdir(VIDEO_DIR, { recursive: true }).catch(() => {});

      // H22: User-Agent rotation
      const ua = this.opts.rotateUserAgent
        ? USER_AGENTS[Math.floor(Math.random() * USER_AGENTS.length)]
        : 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36';
      const screenProfile = SCREEN_PROFILES[Math.floor(Math.random() * SCREEN_PROFILES.length)];

      // H31: Geolocation spoofing
      let geoOverride = null;
      if (this.opts.geolocation) {
        if (typeof this.opts.geolocation === 'string') {
          geoOverride = GEO_PRESETS[this.opts.geolocation.toLowerCase()] || null;
        } else {
          geoOverride = this.opts.geolocation;
        }
      }

      const contextOpts = {
        userAgent: ua,
        viewport: this._currentViewport,
        ignoreHTTPSErrors: true,
        locale: 'en-US',
        timezoneId: 'America/New_York',
        acceptDownloads: true,
        screen: { width: screenProfile.width, height: screenProfile.height },
        colorScheme: 'light',
        javaScriptEnabled: true,
        bypassCSP: false,
      };

      if (geoOverride) {
        contextOpts.geolocation = geoOverride;
        contextOpts.permissions = ['geolocation'];
      }

      // H35: Video recording
      if (this.opts.videoRecording) {
        contextOpts.recordVideo = {
          dir: VIDEO_DIR,
          size: { width: this._currentViewport.width, height: this._currentViewport.height },
        };
      }

      this.context = await this.browser.newContext(contextOpts);

      // P2: Restore cookies if persistence is enabled
      if (this.opts.persistCookies) {
        await this._restoreCookies();
      }

      this.page = await this.context.newPage();
      this.pages = [this.page];
      this.activeTabIndex = 0;

      // H23: Browser stealth — patch navigator.webdriver, plugins, languages, canvas
      if (this.opts.stealthMode) {
        await this._applyStealthPatches(this.page);
      }

      // H26: Dialog auto-handling (alert, confirm, prompt)
      if (this.opts.autoDialogs) {
        this._setupDialogHandler(this.page);
      }

      // Block heavy resources
      await this.page.route(/\.(woff2?|ttf|eot|mp4|webm|ogg)(\?|$)/, r => r.abort());

      // P3: Network request interception
      this._setupNetworkInterceptor(this.page);

      // P2: Download handling
      this._setupDownloadHandler(this.page);

      // P1: Multi-tab — listen for new pages (popup/target)
      this.context.on('page', (newPage) => {
        this.pages.push(newPage);
        this._setupNetworkInterceptor(newPage);
        this._setupDownloadHandler(newPage);
        if (this.opts.stealthMode) this._applyStealthPatches(newPage).catch(() => {});
        if (this.opts.autoDialogs) this._setupDialogHandler(newPage);
        newPage.route(/\.(woff2?|ttf|eot|mp4|webm|ogg)(\?|$)/, r => r.abort());
        this._emit('tab_opened', {
          tabIndex: this.pages.length - 1,
          url: newPage.url(),
          totalTabs: this.pages.length,
        });
        this._narrate(`New tab opened: ${newPage.url()}`);
      });

    } catch (err) {
      try { await this.page?.close(); } catch {}
      try { await this.context?.close(); } catch {}
      try { await this.browser?.close(); } catch {}
      this.page = null; this.context = null; this.browser = null;
      sessions.delete(this.daUsername);
      throw new Error(`Autopilot browser launch failed: ${err.message}`);
    }

    this.alive = true;
    this.startTime = Date.now();
    this.lastActivity = Date.now();
    this._startScreenshotStream();
    this._startIdleTimer();
    this._startDurationTimer();

    this._emit('started', { task: taskDescription, viewport: this._currentViewport });
    this._log('start', { task: taskDescription });
    this._narrate(`Session started. Task: ${taskDescription}`);

    return {
      status: 'started',
      viewport: this._currentViewport,
      task: taskDescription,
      guardrails: {
        maxSteps: this.opts.maxSteps,
        maxDuration: `${Math.round(this.opts.maxDuration / 60_000)} min`,
        humanApproval: this.opts.humanApproval,
      },
    };
  }

  async stop(reason = 'user') {
    if (!this.alive) return { status: 'already stopped' };
    this.alive = false;

    // P2: Save cookies before shutdown
    if (this.opts.persistCookies && this.context) {
      await this._saveCookies();
    }

    // H33: Save localStorage/sessionStorage before shutdown
    if (this.opts.persistStorage && this.page) {
      await this._saveStorage();
    }

    // H35: Finalize video recording
    if (this.opts.videoRecording && this.page) {
      try {
        this._videoPath = await this.page.video()?.path();
      } catch {}
    }

    // H10: Data retention cleanup
    if (this.opts.retentionPolicy === 'session' || this.opts.retentionPolicy === 'no-screenshots') {
      this.screenshotHistory = [];
    }

    clearInterval(this.screenshotTimer);
    clearInterval(this.idleTimer);
    clearTimeout(this.durationTimer);
    this.screenshotTimer = null;
    this.idleTimer = null;
    this.durationTimer = null;

    // H7: Final celebration if task completed successfully
    if (reason === 'user' || reason === 'task_complete') {
      this._celebrationPending = { type: 'completed', message: '🎉 Task completed successfully!' };
    }

    try { await this.page?.close(); } catch {}
    try { await this.context?.close(); } catch {}
    try { await this.browser?.close(); } catch {}
    this.page = null; this.context = null; this.browser = null;
    this.pages = [];

    sessions.delete(this.daUsername);

    const result = {
      status: 'stopped',
      reason,
      totalSteps: this.stepCount,
      duration: `${Math.round((Date.now() - this.startTime) / 1000)}s`,
      history: this.history,
      downloads: this.downloads.map(d => ({ filename: d.filename, size: d.size })),
      sentiment: this._sentiment,
      celebration: this._celebrationPending,
      videoPath: this._videoPath || undefined,
      captchasSolved: this._captchasSolved || undefined,
      dialogsHandled: this._dialogLog.length || undefined,
    };

    this._emit('stopped', result);
    this._narrate('Session ended.');
    return result;
  }

  // ── Guardrail checks ────────────────────────────────────────────────────

  _checkGuardrails() {
    if (this.stepCount >= this.opts.maxSteps) {
      this.stop('max_steps_reached').catch(() => {});
      return { error: `Session stopped: reached maximum ${this.opts.maxSteps} steps. Start a new session if needed.` };
    }
    const elapsed = Date.now() - this.startTime;
    if (elapsed >= this.opts.maxDuration) {
      this.stop('max_duration_reached').catch(() => {});
      return { error: `Session stopped: exceeded maximum duration of ${Math.round(this.opts.maxDuration / 60_000)} minutes.` };
    }
    return null;
  }

  // ── P1: Human-in-the-loop ───────────────────────────────────────────────

  async _maybeAwaitApproval(actionName, details) {
    if (!this.opts.humanApproval) return null; // Auto-approve

    this._paused = true;
    this._pendingAction = { action: actionName, ...details, step: this.stepCount + 1 };

    // H2: Generate preview card
    const preview = await this._generatePreview(actionName, details);

    this._emit('approval_required', {
      action: actionName,
      details,
      step: this.stepCount + 1,
      preview,
      confidence: this._confidenceScore,
      message: `Alfred wants to: ${actionName}${details.selector ? ` on "${details.selector}"` : ''}${details.url ? ` → ${details.url}` : ''}${details.text ? ` "${(details.text || '').slice(0, 60)}"` : ''}`,
    });

    this._narrate(`Waiting for approval: ${actionName}`);

    // Wait up to 60 seconds for human approval
    return new Promise((resolve) => {
      const timeout = setTimeout(() => {
        this._paused = false;
        this._pendingAction = null;
        this._approvalResolve = null;
        resolve({ error: 'Action timed out waiting for user approval (60s).' });
      }, 60_000);

      this._approvalResolve = (approved, editedAction) => {
        clearTimeout(timeout);
        this._paused = false;
        this._pendingAction = null;
        this._approvalResolve = null;
        if (approved) {
          this._rapidRejectCount = 0; // Reset frustration
          resolve(editedAction || null);
        } else {
          // H8: Track rapid rejections
          const now = Date.now();
          if (now - this._lastRejectTime < 5000) {
            this._rapidRejectCount++;
            if (this._rapidRejectCount >= 3) {
              this._frustrationLevel = Math.min(100, this._frustrationLevel + 30);
              this._emit('frustration_detected', {
                level: this._frustrationLevel,
                message: 'Multiple rapid rejections detected. Would you like to adjust settings or describe what you want differently?',
              });
            }
          } else {
            this._rapidRejectCount = 1;
          }
          this._lastRejectTime = now;
          resolve({ error: 'Action rejected by user.' });
        }
      };
    });
  }

  /** Called by proxy when user approves/rejects. */
  resolveApproval(approved, editedAction = null) {
    if (this._approvalResolve) {
      this._approvalResolve(approved, editedAction);
    }
  }

  getPendingAction() {
    return this._paused ? this._pendingAction : null;
  }

  // ── URL validation ──────────────────────────────────────────────────────

  _validateUrl(url) {
    if (!url || typeof url !== 'string') return false;
    try {
      const parsed = new URL(url);
      if (!['http:', 'https:'].includes(parsed.protocol)) return false;
      for (const pattern of BLOCKED_URL_PATTERNS) {
        if (pattern.test(url) || pattern.test(parsed.hostname)) return false;
      }
      return true;
    } catch { return false; }
  }

  // H9: Geo-fence check
  _checkGeofence(url) {
    if (!this.opts.allowedDomains || this.opts.allowedDomains.length === 0) return true;
    try {
      const hostname = new URL(url).hostname;
      return this.opts.allowedDomains.some(d => {
        if (d.startsWith('*.')) return hostname.endsWith(d.slice(1)) || hostname === d.slice(2);
        return hostname === d;
      });
    } catch { return false; }
  }

  // ── Concurrency lock ────────────────────────────────────────────────────

  async _withLock(fn) {
    if (this._actionLock) {
      return { error: 'Another action is in progress. Wait for it to complete.' };
    }
    this._actionLock = true;
    try { return await fn(); } finally { this._actionLock = false; }
  }

  // ── H16: Smart wait ────────────────────────────────────────────────────

  async _smartWaitForPage() {
    if (!this.opts.smartWait || !this.page) return;
    try {
      // Wait up to 3s for spinners/loaders to disappear
      const hasSpinner = await this.page.evaluate((selectors) => {
        for (const sel of selectors) {
          const el = document.querySelector(sel);
          if (el && el.offsetParent !== null) return true;
        }
        return false;
      }, SPINNER_SELECTORS).catch(() => false);

      if (hasSpinner) {
        // Wait for ALL spinners to be gone (max 5s)
        await this.page.waitForFunction((selectors) => {
          for (const sel of selectors) {
            const el = document.querySelector(sel);
            if (el && el.offsetParent !== null) return false;
          }
          return true;
        }, SPINNER_SELECTORS, { timeout: 5000 }).catch(() => {});
      }
    } catch {}
  }

  // ── H1: Sensitive field masking ─────────────────────────────────────────

  async _maskSensitiveFields() {
    if (!this.opts.sensitiveFieldMasking || !this.page) return;
    try {
      await this.page.evaluate((selectors) => {
        const style = document.createElement('style');
        style.id = '__gcm_mask_style';
        style.textContent = selectors.map(s =>
          `${s} { -webkit-text-security: disc !important; color: transparent !important; text-shadow: 0 0 8px rgba(0,0,0,0.5) !important; }`
        ).join('\n');
        if (!document.getElementById('__gcm_mask_style')) {
          document.head.appendChild(style);
        }
      }, SENSITIVE_FIELD_SELECTORS).catch(() => {});
    } catch {}
  }

  async _unmaskSensitiveFields() {
    if (!this.page) return;
    try {
      await this.page.evaluate(() => {
        const style = document.getElementById('__gcm_mask_style');
        if (style) style.remove();
      }).catch(() => {});
    } catch {}
  }

  // ── H2: Action preview card ────────────────────────────────────────────

  async _generatePreview(actionName, details) {
    if (!this.page) return null;
    try {
      if (details.selector && ['click', 'type', 'select', 'hover'].includes(actionName)) {
        const el = this.page.locator(details.selector).first();
        const box = await el.boundingBox().catch(() => null);
        if (box) {
          // Crop area around the element (±40px padding)
          const pad = 40;
          const clip = {
            x: Math.max(0, box.x - pad),
            y: Math.max(0, box.y - pad),
            width: box.width + pad * 2,
            height: box.height + pad * 2,
          };
          const crop = await this.page.screenshot({ type: 'jpeg', quality: 60, clip }).catch(() => null);
          if (crop) {
            this._lastPreview = {
              imageB64: crop.toString('base64'),
              selector: details.selector,
              action: actionName,
              box,
            };
            return { imageB64: crop.toString('base64'), box, selector: details.selector };
          }
        }
      }
    } catch {}
    return null;
  }

  // ── H3: Confidence scoring ──────────────────────────────────────────────

  async _scoreConfidence(selector) {
    if (!this.page || !selector) { this._confidenceScore = 0.5; return; }
    try {
      const score = await this.page.evaluate((sel) => {
        try {
          const matches = document.querySelectorAll(sel);
          if (matches.length === 0) return 0.0;
          if (matches.length === 1) {
            // Unique match = high confidence
            const el = matches[0];
            const visible = el.offsetParent !== null || getComputedStyle(el).display !== 'none';
            return visible ? 1.0 : 0.4;
          }
          // Multiple matches = lower confidence
          return Math.max(0.2, 1.0 / matches.length);
        } catch { return 0.3; }
      }, selector);
      this._confidenceScore = Math.round(score * 100) / 100;
    } catch {
      this._confidenceScore = 0.5;
    }
  }

  // ── H4: Undo / rollback ────────────────────────────────────────────────

  async _saveUndoPoint() {
    if (!this.page) return;
    try {
      const scrollY = await this.page.evaluate(() => window.scrollY).catch(() => 0);
      this._undoStack.push({
        url: this.currentUrl,
        scrollY,
        step: this.stepCount,
        timestamp: Date.now(),
      });
      if (this._undoStack.length > MAX_UNDO) this._undoStack.shift();
    } catch {}
  }

  async undo() {
    return this._withLock(async () => {
      if (this._undoStack.length === 0) {
        return { error: 'Nothing to undo. Undo stack is empty.' };
      }
      const prev = this._undoStack.pop();
      try {
        await this.page.goBack({ waitUntil: 'domcontentloaded', timeout: 10_000 }).catch(async () => {
          // If goBack fails, navigate directly
          if (prev.url) await this.page.goto(prev.url, { waitUntil: 'domcontentloaded', timeout: 10_000 });
        });
        if (prev.scrollY) {
          await this.page.evaluate((y) => window.scrollTo(0, y), prev.scrollY).catch(() => {});
        }
        this.currentUrl = this.page.url();
        this._log('undo', { returnedTo: this.currentUrl, undoneStep: prev.step });
        this._narrate(`Undone. Back to ${this.currentUrl}`);
        return await this.observe();
      } catch (err) {
        return { error: `Undo failed: ${err.message}` };
      }
    });
  }

  // ── H6: Sentiment / stuck detection ─────────────────────────────────────

  _updateSentiment(actionResult) {
    const url = this.currentUrl;

    // Track URL history
    this._lastUrls.push(url);
    if (this._lastUrls.length > 10) this._lastUrls.shift();

    if (actionResult?.error) {
      this._failCount++;
      this._successCount = 0;
    } else {
      this._successCount++;
      this._failCount = 0;
    }

    // Check for stuck: same URL repeated
    const recentSameUrl = this._lastUrls.filter(u => u === url).length;
    if (recentSameUrl >= STUCK_THRESHOLD && this._lastUrls.length >= STUCK_THRESHOLD) {
      this._stuckCounter++;
    } else {
      this._stuckCounter = Math.max(0, this._stuckCounter - 1);
    }

    // Determine sentiment
    let newSentiment = 'neutral';
    if (this._failCount >= 3) {
      newSentiment = 'failing';
    } else if (this._stuckCounter >= 2) {
      newSentiment = 'stuck';
    } else if (this._successCount >= 3) {
      newSentiment = 'progressing';
    }

    if (newSentiment !== this._sentiment) {
      this._sentiment = newSentiment;
      this._sentimentHistory.push({ sentiment: newSentiment, step: this.stepCount, timestamp: Date.now() });

      this._emit('sentiment_changed', {
        sentiment: newSentiment,
        step: this.stepCount,
        failCount: this._failCount,
        stuckCounter: this._stuckCounter,
        message: newSentiment === 'stuck'
          ? '🔄 Alfred seems stuck on the same page. Try a different approach?'
          : newSentiment === 'failing'
          ? '⚠️ Multiple actions failing. Check selectors or page state.'
          : newSentiment === 'progressing'
          ? '✅ Making good progress!'
          : '',
      });
    }
  }

  // ── H12: Annotations ───────────────────────────────────────────────────

  addAnnotation(x, y, text, color = '#fbbf24') {
    const id = ++this._annotationIdCounter;
    const ann = { id, x, y, text, color, timestamp: Date.now() };
    this._annotations.push(ann);
    if (this._annotations.length > MAX_ANNOTATIONS) this._annotations.shift();
    this._emit('annotation_added', ann);
    return ann;
  }

  removeAnnotation(id) {
    this._annotations = this._annotations.filter(a => a.id !== id);
    this._emit('annotation_removed', { id });
    return { removed: id };
  }

  clearAnnotations() {
    this._annotations = [];
    this._emit('annotations_cleared', {});
    return { cleared: true };
  }

  getAnnotations() {
    return this._annotations;
  }

  // ── H13: Task templates ────────────────────────────────────────────────

  async saveTemplate(name) {
    try {
      await mkdir(TEMPLATE_DIR, { recursive: true });
      const template = {
        name,
        task: this.taskDescription,
        steps: this.history.map(h => ({
          action: h.action,
          selector: h.selector,
          value: h.value,
          text: h.text,
          url: h.url,
          key: h.key,
          direction: h.direction,
          amount: h.amount,
          preset: h.preset,
          description: h.description,
        })).filter(s => s.action !== 'start'),
        viewport: this.opts.viewport,
        createdAt: new Date().toISOString(),
        createdBy: this.daUsername,
      };
      const path = join(TEMPLATE_DIR, `${name.replace(/[^a-zA-Z0-9_-]/g, '_')}.json`);
      await writeFile(path, JSON.stringify(template, null, 2));
      this._narrate(`Template saved: ${name}`);
      return { status: 'saved', name, steps: template.steps.length };
    } catch (err) {
      return { error: `Template save failed: ${err.message}` };
    }
  }

  // ── H14: Batch operations ──────────────────────────────────────────────

  setBatchQueue(tasks) {
    this._batchQueue = tasks; // [{task, url}]
    this._batchIndex = 0;
    this._batchActive = true;
    return { queued: tasks.length };
  }

  getBatchStatus() {
    return {
      active: this._batchActive,
      total: this._batchQueue.length,
      current: this._batchIndex,
      remaining: this._batchQueue.length - this._batchIndex,
    };
  }

  nextBatchItem() {
    if (!this._batchActive || this._batchIndex >= this._batchQueue.length) {
      this._batchActive = false;
      return null;
    }
    const item = this._batchQueue[this._batchIndex++];
    return item;
  }

  // ── H17: Narration queue ───────────────────────────────────────────────

  _narrate(text, priority = 'normal') {
    this._narrationQueue.push({ text, priority, timestamp: Date.now() });
    if (this._narrationQueue.length > 20) this._narrationQueue.shift();
    this._emit('narration', { text, priority });
  }

  getNarration() {
    const items = [...this._narrationQueue];
    this._narrationQueue = [];
    return items;
  }

  // ── Actions ─────────────────────────────────────────────────────────────

  async navigate(url) {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      if (!this._validateUrl(url)) {
        return { error: `Blocked or invalid URL: ${url}. Only http/https to public hosts allowed.` };
      }
      // H9: Geo-fence
      if (!this._checkGeofence(url)) {
        return { error: `Domain not allowed by geo-fence. Allowed: ${this.opts.allowedDomains.join(', ')}` };
      }

      const approval = await this._maybeAwaitApproval('navigate', { url });
      if (approval?.error) return approval;

      await this._saveUndoPoint(); // H4
      this._touchAction();
      this._narrate(`Navigating to ${url}`);
      try {
        await this.page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30_000 });
        await this.page.waitForLoadState('networkidle', { timeout: 5000 }).catch(() => {});
        await this._smartWaitForPage(); // H16
        // H24: Human-like pause after page load
        if (this.opts.humanDelays) await this._humanDelay('afterNavigate');
        this.currentUrl = this.page.url();
        this._log('navigate', { url: this.currentUrl });
        const result = await this.observe();
        this._updateSentiment(result); // H6
        return result;
      } catch (err) {
        const result = { error: `Navigation failed: ${err.message}`, url };
        this._updateSentiment(result);
        return result;
      }
    });
  }

  async click(selector, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      await this._scoreConfidence(selector); // H3
      const approval = await this._maybeAwaitApproval('click', { selector, description, confidence: this._confidenceScore });
      if (approval?.error) return approval;

      await this._saveUndoPoint(); // H4
      // H24: Human-like delay before clicking
      if (this.opts.humanDelays) await this._humanDelay('beforeClick');
      this._touchAction();
      this._narrate(`Clicking ${description || selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });

        // P1: Capture click coordinates for cursor overlay
        const box = await this.page.locator(selector).first().boundingBox();
        if (box) {
          this._lastClickCoords = {
            x: Math.round(box.x + box.width / 2),
            y: Math.round(box.y + box.height / 2),
          };

          // H20: Human-like mouse movement to target
          if (this.opts.humanMouse) {
            await this._humanMouseMove(this._lastClickCoords.x, this._lastClickCoords.y);
            await this.page.mouse.click(this._lastClickCoords.x, this._lastClickCoords.y);
          } else {
            await this.page.click(selector);
          }
        } else {
          await this.page.click(selector);
        }

        await this.page.waitForLoadState('networkidle', { timeout: 3000 }).catch(() => {});
        await this._smartWaitForPage(); // H16
        // H24: Post-click delay
        if (this.opts.humanDelays) await this._humanDelay('afterClick');
        this.currentUrl = this.page.url();
        this._log('click', { selector, description, coords: this._lastClickCoords });
        const result = await this.observe();
        this._updateSentiment(result); // H6

        // H7: Celebration on form submit
        if (description?.toLowerCase().includes('submit') || description?.toLowerCase().includes('complete') || description?.toLowerCase().includes('confirm')) {
          this._celebrationPending = { type: 'action_success', message: '🎉 Action completed!' };
          this._emit('celebration', this._celebrationPending);
        }

        return result;
      } catch (err) {
        const result = { error: `Click failed on "${selector}": ${err.message}` };
        this._updateSentiment(result);
        return result;
      }
    });
  }

  async type(selector, text, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      await this._scoreConfidence(selector); // H3
      const approval = await this._maybeAwaitApproval('type', { selector, text: (text || '').slice(0, 80), description, confidence: this._confidenceScore });
      if (approval?.error) return approval;

      await this._saveUndoPoint(); // H4
      // H24: Human-like delay before typing
      if (this.opts.humanDelays) await this._humanDelay('beforeType');
      this._touchAction();
      this._narrate(`Typing into ${description || selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });
        const box = await this.page.locator(selector).first().boundingBox();
        if (box) this._lastClickCoords = { x: Math.round(box.x + box.width / 2), y: Math.round(box.y + box.height / 2) };

        // H20: Move mouse to field first
        if (this.opts.humanMouse && box) {
          await this._humanMouseMove(this._lastClickCoords.x, this._lastClickCoords.y);
          await this.page.mouse.click(this._lastClickCoords.x, this._lastClickCoords.y);
        } else {
          await this.page.click(selector);
        }
        // Clear existing content
        await this.page.locator(selector).first().fill('');

        // H21: Realistic per-keystroke typing with occasional typos
        if (this.opts.humanTyping && text.length > 0 && text.length <= 500) {
          await this._humanType(text);
        } else {
          await this.page.fill(selector, text);
        }
        this._log('type', { selector, text: (text || '').slice(0, 100), description });
        const result = await this.observe();
        this._updateSentiment(result);
        return result;
      } catch (err) {
        const result = { error: `Type failed on "${selector}": ${err.message}` };
        this._updateSentiment(result);
        return result;
      }
    });
  }

  async press(key, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      const approval = await this._maybeAwaitApproval('press', { key, description });
      if (approval?.error) return approval;

      this._touchAction();
      this._narrate(`Pressing ${key}`);
      try {
        await this.page.keyboard.press(key);
        await this.page.waitForLoadState('networkidle', { timeout: 2000 }).catch(() => {});
        await this._smartWaitForPage(); // H16
        this._log('press', { key, description });
        const result = await this.observe();
        this._updateSentiment(result);
        return result;
      } catch (err) {
        const result = { error: `Key press "${key}" failed: ${err.message}` };
        this._updateSentiment(result);
        return result;
      }
    });
  }

  async scroll(direction = 'down', amount = 400) {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      // H24: Delay before scroll
      if (this.opts.humanDelays) await this._humanDelay('beforeScroll');
      this._touchAction();
      try {
        // H25: Smooth scrolling with inertia
        if (this.opts.smoothScroll) {
          await this._smoothScroll(direction, amount);
        } else {
          const delta = direction === 'up' ? -amount : amount;
          await this.page.mouse.wheel(0, delta);
        }
        this._log('scroll', { direction, amount });
        return await this.observe();
      } catch (err) {
        return { error: `Scroll failed: ${err.message}` };
      }
    });
  }

  async select(selector, value, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      await this._scoreConfidence(selector);
      const approval = await this._maybeAwaitApproval('select', { selector, value, description, confidence: this._confidenceScore });
      if (approval?.error) return approval;

      await this._saveUndoPoint();
      this._touchAction();
      this._narrate(`Selecting "${value}" in ${description || selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });
        await this.page.selectOption(selector, value);
        this._log('select', { selector, value, description });
        const result = await this.observe();
        this._updateSentiment(result);
        return result;
      } catch (err) {
        const result = { error: `Select failed on "${selector}": ${err.message}` };
        this._updateSentiment(result);
        return result;
      }
    });
  }

  async hover(selector, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      this._touchAction();
      this._narrate(`Hovering over ${description || selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });
        const box = await this.page.locator(selector).first().boundingBox();
        if (box) this._lastClickCoords = { x: Math.round(box.x + box.width / 2), y: Math.round(box.y + box.height / 2) };
        await this.page.hover(selector);
        this._log('hover', { selector, description, coords: this._lastClickCoords });
        return await this.observe();
      } catch (err) {
        return { error: `Hover failed on "${selector}": ${err.message}` };
      }
    });
  }

  async waitFor(selector, timeout = 10000) {
    return this._withLock(async () => {
      this._touchAction();
      this._narrate(`Waiting for ${selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout });
        this._log('waitFor', { selector });
        return await this.observe();
      } catch (err) {
        return { error: `Wait for "${selector}" timed out after ${timeout}ms` };
      }
    });
  }

  async executeScript(script) {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      const approval = await this._maybeAwaitApproval('script', { script: (script || '').slice(0, 120) });
      if (approval?.error) return approval;

      this._touchAction();
      const blocked = ['require(', 'process.', 'child_process', '__dirname', '__filename', 'fs.'];
      if (blocked.some(b => script.includes(b))) {
        return { error: 'Script contains blocked patterns.' };
      }
      try {
        const result = await this.page.evaluate(script);
        this._log('executeScript', { script: script.slice(0, 200) });
        const obs = await this.observe();
        obs.scriptResult = result;
        return obs;
      } catch (err) {
        return { error: `Script execution failed: ${err.message}` };
      }
    });
  }

  // ── P1: Multi-tab support ───────────────────────────────────────────────

  async switchTab(tabIndex) {
    return this._withLock(async () => {
      if (tabIndex < 0 || tabIndex >= this.pages.length) {
        return { error: `Tab ${tabIndex} does not exist. Open tabs: ${this.pages.length}` };
      }
      const target = this.pages[tabIndex];
      if (target.isClosed()) {
        this.pages.splice(tabIndex, 1);
        return { error: 'That tab was closed. Removed from tab list.' };
      }
      this.activeTabIndex = tabIndex;
      this.page = target;
      await target.bringToFront();
      this.currentUrl = target.url();
      this._log('switch_tab', { tabIndex, url: this.currentUrl });
      this._emit('tab_switched', { tabIndex, url: this.currentUrl, totalTabs: this.pages.length });
      this._narrate(`Switched to tab ${tabIndex + 1}`);
      return await this.observe();
    });
  }

  listTabs() {
    return this.pages.map((p, i) => ({
      index: i,
      url: p.isClosed() ? '(closed)' : p.url(),
      active: i === this.activeTabIndex,
    }));
  }

  // ── P3: Viewport presets ────────────────────────────────────────────────

  async setViewport(preset) {
    return this._withLock(async () => {
      const vp = VIEWPORT_PRESETS[preset];
      if (!vp) {
        return { error: `Unknown viewport: ${preset}. Available: ${Object.keys(VIEWPORT_PRESETS).join(', ')}` };
      }
      this._currentViewport = vp;
      await this.page.setViewportSize(vp);
      this._log('set_viewport', { preset, ...vp });
      this._emit('viewport_changed', { preset, ...vp });
      this._narrate(`Viewport changed to ${preset}`);
      return await this.observe();
    });
  }

  // ── P2: Download access ─────────────────────────────────────────────────

  getDownloads() {
    return this.downloads.map(d => ({
      filename: d.filename,
      size: d.size,
      timestamp: d.timestamp,
      path: d.path,
    }));
  }

  // ── P2: Upload file to <input type="file"> ─────────────────────────────

  async uploadFile(selector, filePath) {
    return this._withLock(async () => {
      this._touchAction();
      this._narrate(`Uploading file to ${selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });
        await this.page.setInputFiles(selector, filePath);
        this._log('upload_file', { selector, file: filePath });
        return await this.observe();
      } catch (err) {
        return { error: `Upload failed: ${err.message}` };
      }
    });
  }

  // ── P3: Get captured network requests ───────────────────────────────────

  getNetworkLog(options = {}) {
    let log = this.networkLog;
    if (options.urlFilter) {
      log = log.filter(e => e.url.includes(options.urlFilter));
    }
    if (options.method) {
      log = log.filter(e => e.method === options.method.toUpperCase());
    }
    return log.slice(-(options.limit || 50));
  }

  // ── P2: Screenshot history / replay ─────────────────────────────────────

  getScreenshotHistory() {
    return this.screenshotHistory.map(s => ({
      step: s.step,
      url: s.url,
      timestamp: s.timestamp,
    }));
  }

  getScreenshotAtStep(step) {
    const entry = this.screenshotHistory.find(s => s.step === step);
    return entry ? entry.buffer : null;
  }

  // ── P2: Cookie persistence ──────────────────────────────────────────────

  async saveCookies() {
    return this._saveCookies();
  }

  async loadCookies() {
    return this._restoreCookies();
  }

  // ── Observation ─────────────────────────────────────────────────────────

  async observe() {
    this.lastActivity = Date.now();
    if (!this.alive || !this.page) {
      return { error: 'Session not active' };
    }

    try {
      // H1: Mask sensitive fields before screenshot
      await this._maskSensitiveFields();

      const [screenshot, a11yTree, url, title] = await Promise.all([
        this.page.screenshot({ type: 'jpeg', quality: 50 }).catch(() => null),
        this._getAccessibilityTree().catch(() => ''),
        Promise.resolve(this.page.url()),
        this.page.title().catch(() => ''),
      ]);

      // H1: Unmask after screenshot (so user can still interact)
      await this._unmaskSensitiveFields();

      this.currentUrl = url;
      if (screenshot) {
        this.lastScreenshot = screenshot;
        this.lastScreenshotB64 = screenshot.toString('base64');
      }

      const elapsed = Date.now() - this.startTime;

      return {
        url,
        title,
        screenshot: this.lastScreenshot ? `[screenshot: ${screenshot?.length || 0} bytes]` : null,
        accessibilityTree: a11yTree,
        step: this.stepCount,
        totalHistory: this.history.length,
        history: this.history.slice(-5),
        guardrails: {
          stepsRemaining: this.opts.maxSteps - this.stepCount,
          timeRemaining: `${Math.max(0, Math.round((this.opts.maxDuration - elapsed) / 1000))}s`,
        },
        cursor: this._lastClickCoords,
        tabs: this.pages.length > 1 ? this.listTabs() : undefined,
        paused: this._paused,
        pendingAction: this._pendingAction,
        viewport: this._currentViewport,
        // Human-centric
        confidence: this._confidenceScore,        // H3
        sentiment: this._sentiment,                // H6
        frustrationLevel: this._frustrationLevel,  // H8
        annotations: this._annotations.length > 0 ? this._annotations : undefined, // H12
        celebration: this._celebrationPending,     // H7
        undoAvailable: this._undoStack.length > 0, // H4
        batchStatus: this._batchActive ? this.getBatchStatus() : undefined, // H14
        spectators: this._spectatorCount,          // H11
        dialogsHandled: this._dialogLog.length || undefined, // H26
        captchasSolved: this._captchasSolved || undefined,   // H36
      };
    } catch (err) {
      return { error: `Observe failed: ${err.message}` };
    } finally {
      // Clear one-time celebration
      this._celebrationPending = null;
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H27: IFRAME TRAVERSAL — interact with elements inside iframes
  // ═══════════════════════════════════════════════════════════════════════

  async iframeAction(iframeSelector, action, targetSelector, value = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      if (!this.opts.iframeAccess) return { error: 'iframe access disabled. Enable with iframeAccess: true.' };

      this._touchAction();
      this._narrate(`Acting inside iframe: ${action} on ${targetSelector}`);
      try {
        const frame = this.page.frameLocator(iframeSelector);
        switch (action) {
          case 'click':
            await frame.locator(targetSelector).click();
            break;
          case 'type':
            await frame.locator(targetSelector).fill(value);
            break;
          case 'text': {
            const text = await frame.locator(targetSelector).textContent();
            this._log('iframe_text', { iframeSelector, targetSelector, text: (text || '').slice(0, 500) });
            return { text, iframe: iframeSelector, selector: targetSelector };
          }
          case 'count': {
            const count = await frame.locator(targetSelector).count();
            return { count, iframe: iframeSelector, selector: targetSelector };
          }
          default:
            return { error: `Unknown iframe action: ${action}. Use: click, type, text, count` };
        }
        this._log('iframe_action', { iframe: iframeSelector, action, selector: targetSelector });
        return await this.observe();
      } catch (err) {
        return { error: `iframe action failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H28: DRAG AND DROP — full mouse-based drag gestures
  // ═══════════════════════════════════════════════════════════════════════

  async dragAndDrop(sourceSelector, targetSelector, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;

      const approval = await this._maybeAwaitApproval('drag', { source: sourceSelector, target: targetSelector, description });
      if (approval?.error) return approval;

      this._touchAction();
      this._narrate(`Dragging ${description || sourceSelector} to ${targetSelector}`);
      try {
        await this.page.waitForSelector(sourceSelector, { timeout: 5000 });
        await this.page.waitForSelector(targetSelector, { timeout: 5000 });

        if (this.opts.humanMouse) {
          const srcBox = await this.page.locator(sourceSelector).first().boundingBox();
          const tgtBox = await this.page.locator(targetSelector).first().boundingBox();
          if (srcBox && tgtBox) {
            const sx = srcBox.x + srcBox.width / 2, sy = srcBox.y + srcBox.height / 2;
            const tx = tgtBox.x + tgtBox.width / 2, ty = tgtBox.y + tgtBox.height / 2;
            await this._humanMouseMove(sx, sy);
            await this.page.mouse.down();
            await this._sleep(80 + Math.random() * 120);
            const steps = 8 + Math.floor(Math.random() * 8);
            for (let i = 1; i <= steps; i++) {
              const t = i / steps;
              const cx = sx + (tx - sx) * t + (Math.random() - 0.5) * 4;
              const cy = sy + (ty - sy) * t + (Math.random() - 0.5) * 4;
              await this.page.mouse.move(cx, cy);
              await this._sleep(15 + Math.random() * 30);
            }
            await this.page.mouse.move(tx, ty);
            await this._sleep(50 + Math.random() * 100);
            await this.page.mouse.up();
          } else {
            await this.page.dragAndDrop(sourceSelector, targetSelector);
          }
        } else {
          await this.page.dragAndDrop(sourceSelector, targetSelector);
        }
        this._log('drag_and_drop', { source: sourceSelector, target: targetSelector, description });
        return await this.observe();
      } catch (err) {
        return { error: `Drag failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H29: RIGHT-CLICK / CONTEXT MENU
  // ═══════════════════════════════════════════════════════════════════════

  async rightClick(selector, description = '') {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      if (this.opts.humanDelays) await this._humanDelay('beforeClick');
      this._touchAction();
      this._narrate(`Right-clicking ${description || selector}`);
      try {
        await this.page.waitForSelector(selector, { timeout: 5000 });
        const box = await this.page.locator(selector).first().boundingBox();
        if (box) {
          this._lastClickCoords = { x: Math.round(box.x + box.width / 2), y: Math.round(box.y + box.height / 2) };
          if (this.opts.humanMouse) await this._humanMouseMove(this._lastClickCoords.x, this._lastClickCoords.y);
        }
        await this.page.click(selector, { button: 'right' });
        this._log('right_click', { selector, description, coords: this._lastClickCoords });
        return await this.observe();
      } catch (err) {
        return { error: `Right-click failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H30: TOUCH EVENT SIMULATION — mobile tap, swipe, long-press
  // ═══════════════════════════════════════════════════════════════════════

  async touch(action, options = {}) {
    return this._withLock(async () => {
      const guard = this._checkGuardrails();
      if (guard) return guard;
      this._touchAction();
      try {
        switch (action) {
          case 'tap': {
            const { selector, x, y } = options;
            if (selector) {
              await this.page.waitForSelector(selector, { timeout: 5000 });
              await this.page.tap(selector);
            } else if (x !== undefined && y !== undefined) {
              await this.page.touchscreen.tap(x, y);
            } else {
              return { error: 'Tap requires selector or {x, y} coordinates.' };
            }
            this._narrate(`Tapped ${selector || `(${x}, ${y})`}`);
            this._log('touch_tap', options);
            break;
          }
          case 'swipe': {
            const { startX = 200, startY = 400, endX = 200, endY = 100, steps = 10 } = options;
            await this.page.evaluate(({ sx, sy, ex, ey, steps }) => {
              const el = document.elementFromPoint(sx, sy);
              if (!el) return;
              const touch = new Touch({ identifier: 1, target: el, clientX: sx, clientY: sy });
              el.dispatchEvent(new TouchEvent('touchstart', { touches: [touch], changedTouches: [touch], bubbles: true }));
              for (let i = 1; i <= steps; i++) {
                const t = i / steps;
                const mx = sx + (ex - sx) * t, my = sy + (ey - sy) * t;
                const mt = new Touch({ identifier: 1, target: el, clientX: mx, clientY: my });
                el.dispatchEvent(new TouchEvent('touchmove', { touches: [mt], changedTouches: [mt], bubbles: true }));
              }
              const et = new Touch({ identifier: 1, target: el, clientX: ex, clientY: ey });
              el.dispatchEvent(new TouchEvent('touchend', { touches: [], changedTouches: [et], bubbles: true }));
            }, { sx: startX, sy: startY, ex: endX, ey: endY, steps });
            this._narrate(`Swiped from (${startX},${startY}) to (${endX},${endY})`);
            this._log('touch_swipe', options);
            break;
          }
          case 'long_press': {
            const { selector, duration = 1000 } = options;
            if (!selector) return { error: 'long_press requires a selector.' };
            await this.page.waitForSelector(selector, { timeout: 5000 });
            const box = await this.page.locator(selector).first().boundingBox();
            if (box) {
              const cx = box.x + box.width / 2, cy = box.y + box.height / 2;
              await this.page.touchscreen.tap(cx, cy);
              await this._sleep(duration);
            }
            this._narrate(`Long-pressed ${selector} for ${duration}ms`);
            this._log('touch_long_press', { selector, duration });
            break;
          }
          default:
            return { error: `Unknown touch action: ${action}. Use: tap, swipe, long_press` };
        }
        return await this.observe();
      } catch (err) {
        return { error: `Touch ${action} failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H34: PDF GENERATION from current page
  // ═══════════════════════════════════════════════════════════════════════

  async generatePdf(options = {}) {
    return this._withLock(async () => {
      if (!this.page) return { error: 'No active page.' };
      this._narrate('Generating PDF of current page');
      try {
        const filename = `page_${Date.now()}.pdf`;
        const pdfPath = join(PDF_DIR, filename);
        await this.page.pdf({
          path: pdfPath,
          format: options.format || 'A4',
          landscape: options.landscape || false,
          printBackground: options.printBackground !== false,
          margin: options.margin || { top: '0.5in', right: '0.5in', bottom: '0.5in', left: '0.5in' },
          scale: options.scale || 1,
        });
        const info = await stat(pdfPath).catch(() => null);
        this._log('generate_pdf', { filename, size: info?.size || 0, url: this.currentUrl });
        this._narrate(`PDF saved: ${filename}`);
        return { success: true, filename, path: pdfPath, size: info?.size || 0, url: this.currentUrl };
      } catch (err) {
        return { error: `PDF generation failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H36: CAPTCHA SOLVING — 2Captcha / Anti-Captcha integration
  // ═══════════════════════════════════════════════════════════════════════

  async solveCaptcha(type = 'recaptcha') {
    return this._withLock(async () => {
      if (!this.opts.captchaApiKey) return { error: 'No CAPTCHA API key configured. Set captchaApiKey in session options.' };
      if (!this.page) return { error: 'No active page.' };
      this._touchAction();
      this._narrate(`Solving ${type} CAPTCHA`);
      try {
        const service = this.opts.captchaService || '2captcha';
        const pageUrl = this.page.url();

        // Find sitekey
        const sitekey = await this.page.evaluate((captchaType) => {
          if (captchaType === 'hcaptcha') {
            const el = document.querySelector('.h-captcha, [data-sitekey]');
            return el?.getAttribute('data-sitekey') || null;
          }
          const el = document.querySelector('.g-recaptcha, [data-sitekey]');
          return el?.getAttribute('data-sitekey') || null;
        }, type);
        if (!sitekey) return { error: `No ${type} found on page.` };

        const method = type === 'hcaptcha' ? 'hcaptcha' : 'userrecaptcha';
        const submitUrl = `https://2captcha.com/in.php?key=${this.opts.captchaApiKey}&method=${method}&${type === 'hcaptcha' ? 'sitekey' : 'googlekey'}=${sitekey}&pageurl=${encodeURIComponent(pageUrl)}&json=1`;

        const submitRes = await fetch(submitUrl);
        const submitData = await submitRes.json();
        if (submitData.status !== 1) return { error: `CAPTCHA submit failed: ${submitData.request}` };
        const taskId = submitData.request;

        // Poll solution (max 120s)
        let solution = null;
        for (let i = 0; i < 24; i++) {
          await this._sleep(5000);
          const check = await fetch(`https://2captcha.com/res.php?key=${this.opts.captchaApiKey}&action=get&id=${taskId}&json=1`);
          const result = await check.json();
          if (result.status === 1) { solution = result.request; break; }
          if (result.request !== 'CAPCHA_NOT_READY') return { error: `CAPTCHA error: ${result.request}` };
        }
        if (!solution) return { error: 'CAPTCHA solving timed out (120s).' };

        // Inject solution token
        const responseField = type === 'hcaptcha' ? 'h-captcha-response' : 'g-recaptcha-response';
        await this.page.evaluate(({ token, field }) => {
          const ta = document.getElementById(field) || document.querySelector(`[name="${field}"]`);
          if (ta) { ta.style.display = 'block'; ta.value = token; }
          // Trigger callbacks
          if (typeof ___grecaptcha_cfg !== 'undefined') {
            Object.values(___grecaptcha_cfg.clients || {}).forEach(client => {
              Object.values(client).forEach(val => { if (val?.callback) val.callback(token); });
            });
          }
        }, { token: solution, field: responseField });

        this._captchasSolved++;
        this._log('captcha_solved', { type, sitekey, service });
        this._narrate(`${type} solved!`);
        this._celebrationPending = { type: 'captcha_solved', message: `🔓 ${type} solved!` };
        this._emit('celebration', this._celebrationPending);
        return { solved: true, type, service, sitekey };
      } catch (err) {
        return { error: `CAPTCHA solving failed: ${err.message}` };
      }
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H31: SET GEOLOCATION dynamically during session
  // ═══════════════════════════════════════════════════════════════════════

  async setGeolocation(location) {
    if (!this.context) return { error: 'No active session.' };
    try {
      let geo = location;
      if (typeof location === 'string') {
        geo = GEO_PRESETS[location.toLowerCase()];
        if (!geo) return { error: `Unknown preset. Available: ${Object.keys(GEO_PRESETS).join(', ')}` };
      }
      await this.context.setGeolocation(geo);
      await this.context.grantPermissions(['geolocation']);
      this._log('set_geolocation', geo);
      this._narrate(`Geolocation set to ${geo.latitude}, ${geo.longitude}`);
      return { success: true, ...geo };
    } catch (err) {
      return { error: `Geolocation failed: ${err.message}` };
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H26: GET DIALOG LOG
  // ═══════════════════════════════════════════════════════════════════════

  getDialogLog() { return this._dialogLog; }

  // ── Internal helpers ───────────────────────────────────────────────────

  _touchAction() {
    this.lastActivity = Date.now();
    this.stepCount++;
  }

  _log(action, details) {
    const entry = {
      step: this.stepCount,
      action,
      ...details,
      url: this.currentUrl,
      timestamp: new Date().toISOString(),
    };

    this.history.push(entry);
    if (this.history.length > MAX_HISTORY) {
      this.history = this.history.slice(-MAX_HISTORY);
    }

    // P2: Save screenshot with each action for history/replay
    if (this.lastScreenshot && action !== 'start' && this.opts.retentionPolicy !== 'no-screenshots') {
      this.screenshotHistory.push({
        step: this.stepCount,
        url: this.currentUrl,
        timestamp: Date.now(),
        buffer: this.lastScreenshot,
      });
      if (this.screenshotHistory.length > MAX_SCREENSHOT_HISTORY) {
        this.screenshotHistory.shift();
      }
    }

    this._emit('action', {
      step: this.stepCount,
      action,
      description: details.description || '',
      url: this.currentUrl,
      coords: this._lastClickCoords,
      confidence: this._confidenceScore,
      sentiment: this._sentiment,
    });
  }

  _emit(event, data) {
    autopilotEvents.emit('autopilot', {
      daUsername: this.daUsername,
      event,
      ...data,
      timestamp: Date.now(),
    });
  }

  _startScreenshotStream() {
    this.screenshotTimer = setInterval(async () => {
      if (!this.alive || !this.page || this._actionLock) return;
      try {
        // H1: Mask before screenshot
        await this._maskSensitiveFields();
        const buf = await this.page.screenshot({ type: 'jpeg', quality: 45 });
        await this._unmaskSensitiveFields();

        this.lastScreenshot = buf;
        this.lastScreenshotB64 = buf.toString('base64');
        this._emit('frame', {
          screenshotBuffer: buf,
          url: this.currentUrl,
          step: this.stepCount,
          cursor: this._lastClickCoords,
        });
      } catch {
        // page navigating — skip frame
      }
    }, SCREENSHOT_INTERVAL);
    if (this.screenshotTimer.unref) this.screenshotTimer.unref();
  }

  _startIdleTimer() {
    this.idleTimer = setInterval(() => {
      if (!this.alive) { clearInterval(this.idleTimer); this.idleTimer = null; return; }
      if (Date.now() - this.lastActivity > this.opts.idleTimeout) {
        this.stop('idle_timeout').catch(() => {});
      }
    }, 30_000);
    if (this.idleTimer.unref) this.idleTimer.unref();
  }

  _startDurationTimer() {
    this.durationTimer = setTimeout(() => {
      if (this.alive) {
        this.stop('max_duration_reached').catch(() => {});
      }
    }, this.opts.maxDuration);
    if (this.durationTimer.unref) this.durationTimer.unref();
  }

  // ── P3: Network interceptor ─────────────────────────────────────────────

  _setupNetworkInterceptor(page) {
    page.on('response', async (response) => {
      try {
        const url = response.url();
        const ct = response.headers()['content-type'] || '';
        if (ct.includes('json') || ct.includes('xml') || ct.includes('text/plain')) {
          const body = await response.text().catch(() => '');
          this.networkLog.push({
            url,
            method: response.request().method(),
            status: response.status(),
            contentType: ct,
            body: body.slice(0, 5000),
            timestamp: Date.now(),
          });
          if (this.networkLog.length > MAX_NETWORK_LOG) {
            this.networkLog = this.networkLog.slice(-150);
          }
        }
      } catch {}
    });
  }

  // ── P2: Download handler ────────────────────────────────────────────────

  _setupDownloadHandler(page) {
    page.on('download', async (download) => {
      try {
        const filename = download.suggestedFilename();
        const savePath = join(DOWNLOAD_DIR, `${this.daUsername}_${Date.now()}_${filename}`);
        await download.saveAs(savePath);
        const entry = {
          filename,
          path: savePath,
          size: 0,
          timestamp: new Date().toISOString(),
        };
        try {
          const info = await stat(savePath);
          entry.size = info.size;
        } catch {}
        this.downloads.push(entry);
        this._emit('download', entry);
        this._log('download', { filename, size: entry.size });
        this._narrate(`Downloaded: ${filename}`);
        // H7: Celebration on download
        this._celebrationPending = { type: 'download', message: `📥 Downloaded ${filename}` };
        this._emit('celebration', this._celebrationPending);
      } catch {}
    });
  }

  // ── P2: Cookie persistence ──────────────────────────────────────────────

  async _saveCookies() {
    try {
      await mkdir(COOKIE_DIR, { recursive: true });
      const cookies = await this.context.cookies();
      const path = join(COOKIE_DIR, `${this.daUsername}.json`);
      await writeFile(path, JSON.stringify(cookies, null, 2));
      return { status: 'saved', count: cookies.length };
    } catch (err) {
      return { error: `Cookie save failed: ${err.message}` };
    }
  }

  async _restoreCookies() {
    try {
      const path = join(COOKIE_DIR, `${this.daUsername}.json`);
      const data = await readFile(path, 'utf-8');
      const cookies = JSON.parse(data);
      if (Array.isArray(cookies) && cookies.length > 0) {
        await this.context.addCookies(cookies);
        return { status: 'restored', count: cookies.length };
      }
      return { status: 'no cookies to restore' };
    } catch {
      return { status: 'no saved cookies found' };
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H20: HUMAN MOUSE MOVEMENT — Bézier curves with jitter & overshoot
  // ═══════════════════════════════════════════════════════════════════════

  async _humanMouseMove(targetX, targetY) {
    if (!this.page) return;
    const fromX = this._mouseX, fromY = this._mouseY;
    const dist = Math.sqrt((targetX - fromX) ** 2 + (targetY - fromY) ** 2);
    const steps = Math.max(12, Math.min(35, Math.floor(dist / 15)));

    // Two random control points for a cubic Bézier
    const cp1x = fromX + (targetX - fromX) * 0.25 + (Math.random() - 0.5) * dist * 0.3;
    const cp1y = fromY + (targetY - fromY) * 0.25 + (Math.random() - 0.5) * dist * 0.3;
    const cp2x = fromX + (targetX - fromX) * 0.75 + (Math.random() - 0.5) * dist * 0.2;
    const cp2y = fromY + (targetY - fromY) * 0.75 + (Math.random() - 0.5) * dist * 0.2;

    for (let i = 1; i <= steps; i++) {
      const t = i / steps;
      const inv = 1 - t;
      // Cubic Bézier formula
      const x = inv ** 3 * fromX + 3 * inv ** 2 * t * cp1x + 3 * inv * t ** 2 * cp2x + t ** 3 * targetX;
      const y = inv ** 3 * fromY + 3 * inv ** 2 * t * cp1y + 3 * inv * t ** 2 * cp2y + t ** 3 * targetY;
      // Add micro-jitter
      const jitterX = x + (Math.random() - 0.5) * 2;
      const jitterY = y + (Math.random() - 0.5) * 2;
      await this.page.mouse.move(jitterX, jitterY);
      await this._sleep(8 + Math.random() * 18);
    }

    // Overshoot + settle (30% chance)
    if (Math.random() < 0.3 && dist > 50) {
      const ovX = targetX + (Math.random() - 0.5) * 8;
      const ovY = targetY + (Math.random() - 0.5) * 8;
      await this.page.mouse.move(ovX, ovY);
      await this._sleep(30 + Math.random() * 50);
      await this.page.mouse.move(targetX, targetY);
      await this._sleep(15 + Math.random() * 25);
    }

    this._mouseX = targetX;
    this._mouseY = targetY;
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H21: HUMAN TYPING — per-keystroke with realistic delays & typos
  // ═══════════════════════════════════════════════════════════════════════

  async _humanType(text) {
    if (!this.page) return;
    const chars = text.split('');
    for (let i = 0; i < chars.length; i++) {
      // 3% chance of a typo
      if (Math.random() < 0.03 && chars[i].match(/[a-zA-Z]/)) {
        const typo = String.fromCharCode(chars[i].charCodeAt(0) + (Math.random() < 0.5 ? 1 : -1));
        await this.page.keyboard.press(typo);
        await this._sleep(50 + Math.random() * 80);
        await this.page.keyboard.press('Backspace');
        await this._sleep(40 + Math.random() * 60);
      }
      await this.page.keyboard.press(chars[i]);
      // Variable delay: faster in mid-word, slower at word boundaries
      let delay;
      if (chars[i] === ' ' || chars[i] === '.' || chars[i] === ',') {
        delay = HUMAN_DELAYS.betweenKeys.max * 0.8 + Math.random() * HUMAN_DELAYS.betweenKeys.max * 0.6;
      } else {
        delay = HUMAN_DELAYS.betweenKeys.min + Math.random() * (HUMAN_DELAYS.betweenKeys.max - HUMAN_DELAYS.betweenKeys.min) * 0.6;
      }
      await this._sleep(delay);
      // 5% chance of a thinking pause mid-word
      if (Math.random() < 0.05) {
        await this._sleep(200 + Math.random() * 400);
      }
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H24: HUMAN DELAY — random pauses between actions
  // ═══════════════════════════════════════════════════════════════════════

  async _humanDelay(type) {
    const range = HUMAN_DELAYS[type];
    if (!range) return;
    const ms = range.min + Math.random() * (range.max - range.min);
    await this._sleep(ms);
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H25: SMOOTH SCROLLING — inertia-based multi-step scroll
  // ═══════════════════════════════════════════════════════════════════════

  async _smoothScroll(direction, totalAmount) {
    if (!this.page) return;
    const steps = 6 + Math.floor(Math.random() * 6);
    let remaining = totalAmount;
    for (let i = 0; i < steps; i++) {
      // Ease-out: larger steps at start, smaller at end
      const fraction = (1 - (i / steps) ** 1.5) / steps * 2;
      const stepAmount = Math.max(10, Math.floor(remaining * Math.min(fraction + 0.05, 0.4)));
      const dx = direction === 'left' ? -stepAmount : direction === 'right' ? stepAmount : 0;
      const dy = direction === 'up' ? -stepAmount : direction === 'down' ? stepAmount : 0;
      await this.page.mouse.wheel(dx, dy);
      remaining -= stepAmount;
      if (remaining <= 0) break;
      await this._sleep(25 + Math.random() * 40);
    }
    // Final micro-scroll to land exactly
    if (remaining > 0) {
      const fdx = direction === 'left' ? -remaining : direction === 'right' ? remaining : 0;
      const fdy = direction === 'up' ? -remaining : direction === 'down' ? remaining : 0;
      await this.page.mouse.wheel(fdx, fdy);
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H23: STEALTH PATCHES — evade bot detection
  // ═══════════════════════════════════════════════════════════════════════

  async _applyStealthPatches(page) {
    await page.addInitScript(() => {
      // 1. navigator.webdriver = false
      Object.defineProperty(navigator, 'webdriver', { get: () => false });

      // 2. Fake navigator.plugins (non-empty to look real)
      Object.defineProperty(navigator, 'plugins', {
        get: () => {
          const plugins = [
            { name: 'Chrome PDF Plugin', description: 'Portable Document Format', filename: 'internal-pdf-viewer' },
            { name: 'Chrome PDF Viewer', description: '', filename: 'mhjfbmdgcfjbbpaeojofohoefgiehjai' },
            { name: 'Native Client', description: '', filename: 'internal-nacl-plugin' },
          ];
          plugins.length = 3;
          return plugins;
        },
      });

      // 3. Fake navigator.languages
      Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en', 'fr'] });

      // 4. Permissions query override
      const origQuery = window.Permissions?.prototype?.query;
      if (origQuery) {
        window.Permissions.prototype.query = async (params) => {
          if (params.name === 'notifications') return { state: 'prompt', onchange: null };
          return origQuery.call(window.navigator.permissions, params);
        };
      }

      // 5. Canvas fingerprint noise
      const origToDataURL = HTMLCanvasElement.prototype.toDataURL;
      HTMLCanvasElement.prototype.toDataURL = function (...args) {
        const ctx = this.getContext('2d');
        if (ctx) {
          const noise = Math.floor(Math.random() * 10);
          ctx.fillStyle = `rgba(${noise},${noise},${noise},0.01)`;
          ctx.fillRect(0, 0, 1, 1);
        }
        return origToDataURL.apply(this, args);
      };

      // 6. WebGL fingerprint noise
      const origGetParameter = WebGLRenderingContext?.prototype?.getParameter;
      if (origGetParameter) {
        WebGLRenderingContext.prototype.getParameter = function (param) {
          // UNMASKED_VENDOR_WEBGL
          if (param === 0x9245) return 'Intel Inc.';
          // UNMASKED_RENDERER_WEBGL
          if (param === 0x9246) return 'Intel Iris OpenGL Engine';
          return origGetParameter.call(this, param);
        };
      }

      // 7. Chrome object presence
      if (!window.chrome) window.chrome = {};
      if (!window.chrome.runtime) window.chrome.runtime = { connect: () => {}, sendMessage: () => {} };

      // 8. iframe contentWindow spoofing
      const origContentWindow = Object.getOwnPropertyDescriptor(HTMLIFrameElement.prototype, 'contentWindow');
      if (origContentWindow) {
        Object.defineProperty(HTMLIFrameElement.prototype, 'contentWindow', {
          get: function () { return origContentWindow.get.call(this); },
        });
      }

      // 9. Connection type
      if (navigator.connection) {
        Object.defineProperty(navigator.connection, 'rtt', { get: () => 50 });
      }

      // 10. Hardware concurrency (realistic values)
      Object.defineProperty(navigator, 'hardwareConcurrency', { get: () => 8 });
      Object.defineProperty(navigator, 'deviceMemory', { get: () => 8 });
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H26: DIALOG HANDLER — auto-accept alerts, confirms, prompts
  // ═══════════════════════════════════════════════════════════════════════

  _setupDialogHandler(page) {
    page.on('dialog', async (dialog) => {
      const entry = {
        type: dialog.type(),
        message: dialog.message(),
        timestamp: new Date().toISOString(),
        action: this.opts.autoDialogs ? 'accepted' : 'dismissed',
      };
      this._dialogLog.push(entry);
      if (this._dialogLog.length > 50) this._dialogLog.shift();
      this._emit('dialog', entry);
      this._narrate(`Dialog (${dialog.type()}): "${dialog.message().slice(0, 100)}"`);
      try {
        if (this.opts.autoDialogs) {
          if (dialog.type() === 'prompt') {
            await dialog.accept(dialog.defaultValue() || '');
          } else {
            await dialog.accept();
          }
        } else {
          await dialog.dismiss();
        }
      } catch {}
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // H33: LOCALSTORAGE / SESSIONSTORAGE PERSISTENCE
  // ═══════════════════════════════════════════════════════════════════════

  async _saveStorage() {
    if (!this.page || !this.opts.persistStorage) return;
    try {
      await mkdir(STORAGE_DIR, { recursive: true });
      const data = await this.page.evaluate(() => ({
        localStorage: { ...localStorage },
        sessionStorage: { ...sessionStorage },
        url: location.origin,
      }));
      const path = join(STORAGE_DIR, `${this.daUsername}_storage.json`);
      await writeFile(path, JSON.stringify(data, null, 2));
    } catch {}
  }

  async _restoreStorage() {
    if (!this.page || !this.opts.persistStorage) return;
    try {
      const path = join(STORAGE_DIR, `${this.daUsername}_storage.json`);
      const raw = await readFile(path, 'utf-8');
      const data = JSON.parse(raw);
      await this.page.evaluate((d) => {
        Object.entries(d.localStorage || {}).forEach(([k, v]) => localStorage.setItem(k, v));
        Object.entries(d.sessionStorage || {}).forEach(([k, v]) => sessionStorage.setItem(k, v));
      }, data);
    } catch {}
  }

  // ═══════════════════════════════════════════════════════════════════════
  // UTILITY: sleep
  // ═══════════════════════════════════════════════════════════════════════

  _sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

  // ── Accessibility tree ──────────────────────────────────────────────────

  async _getAccessibilityTree() {
    return await this.page.evaluate(() => {
      let interactiveIndex = 0;

      function buildSelector(el) {
        if (el.id) {
          const escaped = CSS.escape ? CSS.escape(el.id) : el.id.replace(/([^\w-])/g, '\\$1');
          return '#' + escaped;
        }
        if (el.getAttribute?.('data-testid')) {
          return '[data-testid="' + el.getAttribute('data-testid') + '"]';
        }
        if (el.name && ['input','select','textarea'].includes(el.tagName?.toLowerCase())) {
          return el.tagName.toLowerCase() + '[name="' + el.name + '"]';
        }
        const parts = [];
        let cur = el;
        for (let i = 0; i < 4 && cur && cur !== document.body; i++) {
          const tag = cur.tagName?.toLowerCase();
          if (!tag) break;
          if (cur.id) {
            const esc = CSS.escape ? CSS.escape(cur.id) : cur.id.replace(/([^\w-])/g, '\\$1');
            parts.unshift('#' + esc);
            break;
          }
          let idx = 1;
          let sib = cur.previousElementSibling;
          while (sib) { if (sib.tagName === cur.tagName) idx++; sib = sib.previousElementSibling; }
          parts.unshift(tag + ':nth-of-type(' + idx + ')');
          cur = cur.parentElement;
        }
        return parts.join(' > ') || el.tagName?.toLowerCase() || '*';
      }

      const walk = (el, depth = 0) => {
        if (depth > 8) return '';
        const tag = el.tagName?.toLowerCase() || '';
        const skip = ['script','style','noscript','svg','path','meta','link','head','br','hr'];
        if (skip.includes(tag)) return '';
        if (el.hidden || el.getAttribute?.('aria-hidden') === 'true') return '';

        const role = el.getAttribute?.('role') || '';
        const ariaLabel = el.getAttribute?.('aria-label') || '';
        const text = el.childNodes.length === 1 && el.childNodes[0].nodeType === 3
          ? el.childNodes[0].textContent.trim().slice(0, 80) : '';

        const indent = '  '.repeat(depth);
        let line = '';

        if (['a', 'button', 'input', 'select', 'textarea'].includes(tag)) {
          interactiveIndex++;
          const href = tag === 'a' ? ' href="' + (el.href || '').slice(0, 100) + '"' : '';
          const type = el.type ? ' type="' + el.type + '"' : '';
          const name = el.name ? ' name="' + el.name + '"' : '';
          const value = el.value ? ' value="' + el.value.slice(0, 50) + '"' : '';
          const placeholder = el.placeholder ? ' placeholder="' + el.placeholder + '"' : '';
          const label = ariaLabel ? ' aria-label="' + ariaLabel + '"' : '';
          const disabled = el.disabled ? ' disabled' : '';
          const selector = buildSelector(el);
          line = indent + '[' + interactiveIndex + '] <' + tag + type + name + href + value + placeholder + label + disabled + '> selector="' + selector + '" ' + text + '\n';
        } else if (role) {
          line = indent + '[' + role + '] ' + (ariaLabel || text) + '\n';
        } else if (text && ['p','h1','h2','h3','h4','h5','h6','li','td','th','span','label','div','figcaption','caption','legend'].includes(tag)) {
          line = indent + tag + ': ' + text + '\n';
        }

        let children = '';
        for (const child of el.children || []) { children += walk(child, depth + 1); }
        return line + children;
      };
      return walk(document.body).slice(0, 20000);
    });
  }
}

// ── Public API ─────────────────────────────────────────────────────────────

export function getSession(daUsername) {
  return sessions.get(daUsername) || null;
}

export async function startSession(daUsername, taskDescription, opts = {}) {
  const existing = sessions.get(daUsername);
  if (existing?.alive) {
    await existing.stop('new_session');
  }
  const session = new AutopilotSession(daUsername, opts);
  sessions.set(daUsername, session);
  await session.start(taskDescription);
  return session;
}

export async function stopSession(daUsername) {
  const session = sessions.get(daUsername);
  if (!session) return { status: 'no session' };
  return session.stop('user');
}

export function getActiveSessions() {
  const active = [];
  for (const [username, session] of sessions) {
    if (session.alive) {
      active.push({
        username,
        url: session.currentUrl,
        steps: session.stepCount,
        task: session.taskDescription,
        lastActivity: session.lastActivity,
        tabs: session.pages.length,
        paused: session._paused,
        viewport: session._currentViewport,
        sentiment: session._sentiment,
        confidence: session._confidenceScore,
        spectators: session._spectatorCount,
        frustrationLevel: session._frustrationLevel,
        guardrails: {
          maxSteps: session.opts.maxSteps,
          stepsRemaining: session.opts.maxSteps - session.stepCount,
          elapsed: `${Math.round((Date.now() - session.startTime) / 1000)}s`,
        },
      });
    }
  }
  return active;
}

// ── H13: Template API ─────────────────────────────────────────────────────

export async function listTemplates() {
  try {
    await mkdir(TEMPLATE_DIR, { recursive: true });
    const files = await readdir(TEMPLATE_DIR);
    const templates = [];
    for (const f of files) {
      if (!f.endsWith('.json')) continue;
      try {
        const data = JSON.parse(await readFile(join(TEMPLATE_DIR, f), 'utf-8'));
        templates.push({ name: data.name, task: data.task, steps: data.steps?.length || 0, createdAt: data.createdAt });
      } catch {}
    }
    return templates;
  } catch { return []; }
}

export async function getTemplate(name) {
  try {
    const path = join(TEMPLATE_DIR, `${name.replace(/[^a-zA-Z0-9_-]/g, '_')}.json`);
    return JSON.parse(await readFile(path, 'utf-8'));
  } catch { return null; }
}

export async function deleteTemplate(name) {
  try {
    const path = join(TEMPLATE_DIR, `${name.replace(/[^a-zA-Z0-9_-]/g, '_')}.json`);
    await unlink(path);
    return { deleted: name };
  } catch (err) {
    return { error: err.message };
  }
}

// ── H15: Schedule API ─────────────────────────────────────────────────────

export async function saveSchedule(schedule) {
  try {
    await mkdir(SCHEDULE_DIR, { recursive: true });
    const id = `${schedule.name || 'task'}_${Date.now()}`.replace(/[^a-zA-Z0-9_-]/g, '_');
    const entry = {
      id,
      name: schedule.name,
      task: schedule.task,
      url: schedule.url,
      cron: schedule.cron || '0 9 * * *', // Default: daily at 9am
      viewport: schedule.viewport || 'desktop',
      enabled: true,
      createdAt: new Date().toISOString(),
      lastRun: null,
      runCount: 0,
    };
    await writeFile(join(SCHEDULE_DIR, `${id}.json`), JSON.stringify(entry, null, 2));
    return entry;
  } catch (err) {
    return { error: err.message };
  }
}

export async function listSchedules() {
  try {
    await mkdir(SCHEDULE_DIR, { recursive: true });
    const files = await readdir(SCHEDULE_DIR);
    const schedules = [];
    for (const f of files) {
      if (!f.endsWith('.json')) continue;
      try {
        schedules.push(JSON.parse(await readFile(join(SCHEDULE_DIR, f), 'utf-8')));
      } catch {}
    }
    return schedules;
  } catch { return []; }
}

export async function deleteSchedule(id) {
  try {
    await unlink(join(SCHEDULE_DIR, `${id}.json`));
    return { deleted: id };
  } catch (err) {
    return { error: err.message };
  }
}

// ── H10: Retention cleanup ────────────────────────────────────────────────

export async function cleanupRetention() {
  try {
    const cutoff = Date.now() - 24 * 60 * 60 * 1000; // 24 hours ago
    // Clean old cookies
    const cookieFiles = await readdir(COOKIE_DIR).catch(() => []);
    for (const f of cookieFiles) {
      const path = join(COOKIE_DIR, f);
      const info = await stat(path).catch(() => null);
      if (info && info.mtimeMs < cutoff) {
        await unlink(path).catch(() => {});
      }
    }
    // Clean old downloads
    const dlFiles = await readdir(DOWNLOAD_DIR).catch(() => []);
    for (const f of dlFiles) {
      const path = join(DOWNLOAD_DIR, f);
      const info = await stat(path).catch(() => null);
      if (info && info.mtimeMs < cutoff) {
        await unlink(path).catch(() => {});
      }
    }
    return { cleaned: true };
  } catch (err) {
    return { error: err.message };
  }
}

export { VIEWPORT_PRESETS };
