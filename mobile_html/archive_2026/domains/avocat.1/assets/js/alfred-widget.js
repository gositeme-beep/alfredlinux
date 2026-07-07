/* ═══════════════════════════════════════════════════════════════════════════════
   ALFRED VOICE WIDGET v2 — Full Inline Chat + Voice Engine
   Features: Chat, Voice, Rich Cards, Shortcuts, Waveform, Agents, Favorites,
             Screen-Awareness, Language Detection, Pipelines, Collab, PWA
   ═══════════════════════════════════════════════════════════════════════════════ */
(function(){
'use strict';

/* ── Prevent double initialization ── */
if (window.__ALFRED_WIDGET_LOADED) return;
window.__ALFRED_WIDGET_LOADED = true;

/* ── Config from PHP (injected via data-attrs or global) ── */
const CFG = {
  wsUrl:      window.AW_WS_URL      || 'wss://gositeme.com/voice-ws',
  authToken:  window.AW_AUTH_TOKEN   || '',
  username:   window.AW_USERNAME     || 'Guest',
  apiBase:    window.AW_API_BASE     || '/api',
  chatApi:    window.AW_CHAT_API     || '/api/alfred-chat.php',
  csrfToken: window.AW_CSRF_TOKEN    || '',
  userId:    window.AW_USER_ID       || '',
  fullVoice:  '/voice.php'
};

/* ── Agent Roster ── */
const AGENTS = {
  'flagship': {
    label: '🏆 Flagship',
    agents: [
      {id:'alfred',   name:'Alfred',   emoji:'🎩', role:'AI Assistant',         engine:'kokoro',  voice:'onyx'},
      {id:'nova',     name:'Nova',     emoji:'✨', role:'Creative Director',        engine:'kokoro',  voice:'nova'},
      {id:'sage',     name:'Sage',     emoji:'🧙', role:'Knowledge Architect',      engine:'kokoro',  voice:'sage'},
      {id:'atlas',    name:'Atlas',    emoji:'🌍', role:'Data Navigator',           engine:'kokoro',  voice:'atlas'},
      {id:'cipher',   name:'Cipher',   emoji:'🔐', role:'Security Analyst',         engine:'kokoro',  voice:'cipher'},
      {id:'pulse',    name:'Pulse',    emoji:'💓', role:'Health & Wellness Guide',  engine:'kokoro',  voice:'pulse'}
    ]
  },
  'expressive': {
    label: '🎭 Expressive',
    agents: [
      {id:'jazz',     name:'Jazz',     emoji:'🎷', role:'Music & Arts Creative',    engine:'orpheus', voice:'tara'},
      {id:'ember',    name:'Ember',    emoji:'🔥', role:'Motivation Coach',         engine:'orpheus', voice:'leah'},
      {id:'frost',    name:'Frost',    emoji:'❄️', role:'Calm Meditation Guide',    engine:'orpheus', voice:'jess'},
      {id:'echo',     name:'Echo',     emoji:'🔊', role:'Sound Designer',           engine:'orpheus', voice:'leo'},
      {id:'pixel',    name:'Pixel',    emoji:'🎮', role:'Gaming Companion',         engine:'orpheus', voice:'mia'},
      {id:'bloom',    name:'Bloom',    emoji:'🌸', role:'Lifestyle Curator',        engine:'orpheus', voice:'zola'},
      {id:'drift',    name:'Drift',    emoji:'🌊', role:'Travel Guide',             engine:'orpheus', voice:'dan'},
      {id:'spark',    name:'Spark',    emoji:'⚡', role:'Quick Problem Solver',     engine:'orpheus', voice:'emma'}
    ]
  },
  'global': {
    label: '🌐 Global',
    agents: [
      {id:'pierre',   name:'Pierre',   emoji:'🇫🇷', role:'French Specialist',      engine:'kokoro',  voice:'pierre'},
      {id:'sofia',    name:'Sofia',    emoji:'🇪🇸', role:'Spanish Specialist',      engine:'kokoro',  voice:'sofia'},
      {id:'hans',     name:'Hans',     emoji:'🇩🇪', role:'German Specialist',       engine:'kokoro',  voice:'hans'},
      {id:'yuki',     name:'Yuki',     emoji:'🇯🇵', role:'Japanese Specialist',     engine:'kokoro',  voice:'yuki'},
      {id:'chen',     name:'Chen',     emoji:'🇨🇳', role:'Chinese Specialist',      engine:'kokoro',  voice:'chen'},
      {id:'priya',    name:'Priya',    emoji:'🇮🇳', role:'Hindi Specialist',        engine:'kokoro',  voice:'priya'}
    ]
  },
  'premium': {
    label: '💎 Premium',
    agents: [
      {id:'aurora',   name:'Aurora',   emoji:'🌌', role:'Executive Assistant',      engine:'cartesia-sonic', voice:'aurora'},
      {id:'titan',    name:'Titan',    emoji:'🏛️', role:'Enterprise Strategist',   engine:'cartesia-sonic', voice:'titan'},
      {id:'luna',     name:'Luna',     emoji:'🌙', role:'Night Owl Developer',      engine:'cartesia-sonic', voice:'luna'},
      {id:'rex',      name:'Rex',      emoji:'👑', role:'CEO Digital Twin',         engine:'cartesia-sonic', voice:'rex'}
    ]
  }
};

/* ── Shortcuts ── */
const SHORTCUTS = [
  {icon:'🌐', label:'Check Domains',   cmd:'check domain availability'},
  {icon:'📦', label:'My Services',      cmd:'show my active services'},
  {icon:'💳', label:'Billing',          cmd:'show my invoices'},
  {icon:'🎫', label:'Support',          cmd:'create support ticket'},
  {icon:'🤖', label:'AI Models',        cmd:'what AI models are available'},
  {icon:'🎙️', label:'Voice Plans',     cmd:'show voice agent pricing'},
  {icon:'🔧', label:'Server Status',    cmd:'check server status'},
  {icon:'📊', label:'Usage Stats',      cmd:'show my usage statistics'}
];

/* ── Language map for auto-detect ── */
const LANG_MAP = {
  'fr': {agent:'pierre', label:'Français',  flag:'🇫🇷'},
  'es': {agent:'sofia',  label:'Español',   flag:'🇪🇸'},
  'de': {agent:'hans',   label:'Deutsch',   flag:'🇩🇪'},
  'ja': {agent:'yuki',   label:'日本語',    flag:'🇯🇵'},
  'zh': {agent:'chen',   label:'中文',      flag:'🇨🇳'},
  'hi': {agent:'priya',  label:'हिन्दी',    flag:'🇮🇳'}
};

/* ── Page Context Patterns ── */
const PAGE_CONTEXTS = [
  // WHMCS client area
  {pattern:/\/whmcs\/clientarea/i,    context:'Client Area — account management',        icon:'fa-user'},
  {pattern:/\/whmcs\/cart/i,          context:'Shopping Cart — product ordering',         icon:'fa-shopping-cart'},
  {pattern:/\/whmcs\/knowledgebase/i, context:'Knowledge Base — help articles',           icon:'fa-book'},
  {pattern:/\/whmcs\/submitticket/i,  context:'Support — ticket submission',              icon:'fa-life-ring'},
  {pattern:/\/whmcs\/invoices/i,      context:'Invoices — billing management',            icon:'fa-file-invoice-dollar'},
  {pattern:/\/whmcs\/affiliates/i,    context:'Affiliate Program — referral earnings',    icon:'fa-handshake'},
  // Product pages
  {pattern:/\/voice-products/i,       context:'Voice Products — 52 AI products catalog',  icon:'fa-phone-volume'},
  {pattern:/\/voice-portal/i,         context:'Voice Portal — agent management',          icon:'fa-headset'},
  {pattern:/\/voice\.php/i,           context:'Voice Interface — AI conversations',       icon:'fa-microphone'},
  {pattern:/\/alfred-tools/i,         context:'Alfred Tools — 1,290+ tool directory',       icon:'fa-toolbox'},
  {pattern:/\/alfred-calls/i,        context:'Alfred Calls — call history & analytics',  icon:'fa-phone'},
  {pattern:/\/alfred-landing/i,       context:'Alfred AI — chief butler introduction',    icon:'fa-robot'},
  {pattern:/\/alfred\.php/i,          context:'Alfred AI — full platform overview',       icon:'fa-robot'},
  {pattern:/\/agent-templates/i,      context:'Agent Templates — pre-built AI agents',    icon:'fa-users-cog'},
  {pattern:/\/gocodeme/i,             context:'GoCodeMe IDE — AI coding platform',        icon:'fa-code'},
  {pattern:/\/ai-servers/i,           context:'AI Servers — GPU server configuration',    icon:'fa-server'},
  // Informational pages
  {pattern:/\/about/i,                context:'About GoSiteMe — company information',     icon:'fa-info-circle'},
  {pattern:/\/affiliate/i,            context:'Affiliate Program — earn commissions',      icon:'fa-handshake'},
  {pattern:/\/analytics/i,            context:'Analytics — platform insights',             icon:'fa-chart-line'},
  {pattern:/\/privacy-policy/i,       context:'Privacy Policy — data protection',          icon:'fa-shield-alt'},
  {pattern:/\/terms-of-service/i,     context:'Terms of Service — legal agreement',        icon:'fa-gavel'},
  {pattern:/\/languages/i,            context:'Languages — 30+ supported languages',       icon:'fa-globe'},
  // Tools
  {pattern:/\/tools\//i,              context:'Tool Directory — browse 1,290+ AI tools',     icon:'fa-toolbox'},
  {pattern:/\/editor/i,               context:'Code Editor — development environment',     icon:'fa-code'},
  {pattern:/\/chess/i,                context:'Chess — game analysis',                     icon:'fa-chess'},
  {pattern:/\/dashboard/i,            context:'Dashboard — overview',                      icon:'fa-tachometer-alt'},
  // Store pages
  {pattern:/\/store/i,                context:'Store — browse products & plans',            icon:'fa-store'},
  {pattern:/\/cart/i,                 context:'Cart — checkout & ordering',                 icon:'fa-shopping-cart'},
  // Homepage (must be last - catch-all for root)
  {pattern:/\/$|\/index/i,            context:'Homepage — main landing page',              icon:'fa-home'}
];

/* ── AI Models Available (all 26 from middleware) ── */
const AI_MODELS = {
  // Smart
  'auto':             {label:'Auto',                provider:'smart',     icon:'🤖', desc:'Routes by complexity',       tier:'free',  cost:0},
  // Anthropic
  'sonnet':           {label:'Claude Sonnet 4.6',   provider:'anthropic', icon:'💜', desc:'Excellent all-rounder',       tier:'pro',   cost:3},
  'opus':             {label:'Claude Opus 4.6',     provider:'anthropic', icon:'👑', desc:'Best for complex tasks',      tier:'elite', cost:15},
  'haiku':            {label:'Claude Haiku 4.5',    provider:'anthropic', icon:'⚡', desc:'Fastest, great quality',      tier:'free',  cost:0.25},
  // OpenAI
  'gpt-4.1':          {label:'GPT-4.1',             provider:'openai',    icon:'🟢', desc:'OpenAI flagship (Jan 2026)',  tier:'pro',   cost:2},
  'gpt-4.1-mini':     {label:'GPT-4.1 Mini',        provider:'openai',    icon:'🟡', desc:'Fast & affordable',           tier:'free',  cost:0.4},
  'gpt-4.1-nano':     {label:'GPT-4.1 Nano',        provider:'openai',    icon:'🟤', desc:'Ultra-light, near-instant',   tier:'free',  cost:0.1},
  'gpt-4o':           {label:'GPT-4o',              provider:'openai',    icon:'🔵', desc:'Multimodal vision+text',      tier:'pro',   cost:2.5},
  'gpt-4o-mini':      {label:'GPT-4o Mini',         provider:'openai',    icon:'🔹', desc:'Multimodal light',            tier:'free',  cost:0.15},
  'o3':               {label:'o3',                  provider:'openai',    icon:'🧠', desc:'Deep reasoning (chain-of-thought)',tier:'elite',cost:10},
  'o3-mini':          {label:'o3 Mini',             provider:'openai',    icon:'💡', desc:'Fast reasoning',              tier:'pro',   cost:1.1},
  'o4-mini':          {label:'o4 Mini',             provider:'openai',    icon:'🔮', desc:'Newest reasoning model',      tier:'pro',   cost:1.1},
  // Google
  'gemini-3.1-pro':   {label:'Gemini 3.1 Pro',      provider:'google',    icon:'🌐', desc:'Google flagship, advanced reasoning',tier:'pro',   cost:2},
  'gemini-3-flash':   {label:'Gemini 3 Flash',       provider:'google',    icon:'⚡', desc:'Frontier-class speed & quality',  tier:'free',  cost:0.5},
  'gemini-3.1-lite':  {label:'Gemini 3.1 Flash Lite', provider:'google',   icon:'🪶', desc:'Cheapest Gemini 3, high-volume',   tier:'free',  cost:0.25},
  'gemini-2.5-pro':   {label:'Gemini 2.5 Pro',       provider:'google',    icon:'💎', desc:'Stable flagship, deep reasoning', tier:'pro',   cost:1.25},
  'gemini-2.5-flash': {label:'Gemini 2.5 Flash',     provider:'google',    icon:'💡', desc:'Stable fast model, free tier',    tier:'free',  cost:0.3},
  'gemini-image':     {label:'Gemini Image Gen',     provider:'google',    icon:'🍌', desc:'Nano Banana 2 — generate images via chat',tier:'free',  cost:0.1},
  // Video Generation (Together.ai)
  'veo-2-video':      {label:'Veo 2 Video',           provider:'together',  icon:'🎬', desc:'Google Veo 2 — generate videos from text',tier:'pro',   cost:25000, isVideo:true},
  // Open-Source (Together.ai)
  'turbo':            {label:'Qwen3 Coder',         provider:'together',  icon:'🔧', desc:'Code specialist, cheapest',   tier:'free',  cost:0.06},
  'qwen3-coder-480b': {label:'Qwen3 Coder 480B',    provider:'together',  icon:'🏗️', desc:'Massive code model',          tier:'pro',   cost:0.9},
  'qwen-3.5':         {label:'Qwen 3.5',            provider:'together',  icon:'🔶', desc:'General purpose open-source', tier:'free',  cost:0.2},
  'deepseek-v3.1':    {label:'DeepSeek V3.1',       provider:'together',  icon:'🌊', desc:'Strong general + code',       tier:'free',  cost:0.27},
  'deepseek-r1':      {label:'DeepSeek R1',         provider:'together',  icon:'🧪', desc:'Reasoning specialist',        tier:'pro',   cost:0.55},
  'glm-5':            {label:'GLM-5',               provider:'together',  icon:'🔬', desc:'Chinese LLM powerhouse',      tier:'free',  cost:0.2},
  'kimi-k2.5':        {label:'Kimi K2.5',           provider:'together',  icon:'🌟', desc:'MoE long-context',            tier:'pro',   cost:0.4},
  'kimi-k2-think':    {label:'Kimi K2 Thinking',    provider:'together',  icon:'🤔', desc:'Extended reasoning',          tier:'pro',   cost:0.6},
  'llama-4-maverick': {label:'Llama 4 Maverick',    provider:'together',  icon:'🦙', desc:'Meta flagship 17Bx128E MoE',  tier:'free',  cost:0.27},
  'llama-4-scout':    {label:'Llama 4 Scout',       provider:'together',  icon:'🔭', desc:'Lightweight Llama 4',         tier:'free',  cost:0.12},
  'mistral-small':    {label:'Mistral Small',       provider:'together',  icon:'🇫🇷', desc:'Fast European model',         tier:'free',  cost:0.1},
  // xAI
  'grok-3':           {label:'Grok 3',              provider:'xai',       icon:'🚀', desc:'xAI flagship, real-time knowledge', tier:'pro',  cost:3},
  'grok-3-mini':      {label:'Grok 3 Mini',         provider:'xai',       icon:'⚡', desc:'xAI fast reasoning, low cost',   tier:'free',  cost:0.3},
  // Groq (free)
  'groq-llama-3.3':   {label:'Llama 3.3 70B (Groq)',provider:'groq',      icon:'🆓', desc:'Free via Groq — fast inference',  tier:'free',  cost:0},
  'groq-llama-3.1':   {label:'Llama 3.1 8B (Groq)', provider:'groq',      icon:'🆓', desc:'Free via Groq — ultra-fast',     tier:'free',  cost:0}
};

/* ── Model provider groups for dropdown UI ── */
const MODEL_GROUPS = [
  {key:'smart',     label:'🤖 Smart Routing',    models:['auto']},
  {key:'anthropic', label:'💜 Anthropic',         models:['sonnet','opus','haiku']},
  {key:'openai',    label:'🟢 OpenAI',            models:['gpt-4.1','gpt-4.1-mini','gpt-4.1-nano','gpt-4o','gpt-4o-mini','o3','o3-mini','o4-mini']},
  {key:'google',    label:'💎 Google',             models:['gemini-3.1-pro','gemini-3-flash','gemini-3.1-lite','gemini-2.5-pro','gemini-2.5-flash','gemini-image']},
  {key:'video',     label:'🎬 Video Gen',           models:['veo-2-video']},
  {key:'xai',       label:'🚀 xAI',                models:['grok-3','grok-3-mini']},
  {key:'together',  label:'🔧 Open Source',        models:['turbo','qwen3-coder-480b','qwen-3.5','deepseek-v3.1','deepseek-r1','glm-5','kimi-k2.5','kimi-k2-think','llama-4-maverick','llama-4-scout','mistral-small']},
  {key:'groq',      label:'🆓 Groq (Free)',        models:['groq-llama-3.3','groq-llama-3.1']}
];

/* ── Cost tier badges ── */
const TIER_BADGES = {
  'free':  {label:'Free',  color:'#22c55e'},
  'pro':   {label:'Pro',   color:'#f59e0b'},
  'elite': {label:'Elite', color:'#ef4444'}
};

/* ── Fleet Roles ── */
const FLEET_ROLES = {
  'researcher':  {label:'Researcher',   emoji:'🔍', desc:'Read-only — finds & gathers info'},
  'analyzer':    {label:'Analyzer',     emoji:'🧪', desc:'Diagnoses issues, audits code'},
  'worker':      {label:'Worker',       emoji:'🔨', desc:'Implements changes (1 at a time)'},
  'reviewer':    {label:'Reviewer',     emoji:'📋', desc:'Reviews code & provides feedback'},
  'tester':      {label:'Tester',       emoji:'�', desc:'Writes & runs tests'},
  'documenter':  {label:'Documenter',   emoji:'📝', desc:'Writes documentation'},
  'architect':   {label:'Architect',    emoji:'🏗️', desc:'Designs systems & data models'},
  'devops':      {label:'DevOps',       emoji:'⚙️', desc:'CI/CD, deployment, infra'},
  'security':    {label:'Security',     emoji:'🛡️', desc:'Vulnerability scanning & hardening'},
  'ux':          {label:'UX Designer',  emoji:'🎨', desc:'UI/UX review & suggestions'},
  'pm':          {label:'Project Mgr',  emoji:'📊', desc:'Planning, timeline, coordination'},
  'qa':          {label:'QA Lead',      emoji:'✅', desc:'Quality assurance & acceptance'},
};

/* ── Fleet Formations (pre-built team templates) ── */
const FLEET_FORMATIONS = {
  'code-review': {
    name: 'Code Review Squad',
    icon: '🔍',
    desc: 'Deep code review with security & quality focus',
    roles: ['reviewer','reviewer','analyzer','security','tester'],
    color: '#7d00ff'
  },
  'full-stack': {
    name: 'Full Stack Build Team',
    icon: '🏗️',
    desc: 'End-to-end feature development',
    roles: ['architect','worker','worker','researcher','tester','reviewer','documenter'],
    color: '#00d4ff'
  },
  'security-audit': {
    name: 'Security Audit Team',
    icon: '🛡️',
    desc: 'Comprehensive security assessment',
    roles: ['security','security','analyzer','researcher','tester'],
    color: '#ef4444'
  },
  'content-pipeline': {
    name: 'Content Pipeline',
    icon: '✍️',
    desc: 'Research, write, review, publish',
    roles: ['researcher','researcher','worker','reviewer','documenter'],
    color: '#22c55e'
  },
  'bug-hunt': {
    name: 'Bug Hunt Party',
    icon: '🐛',
    desc: 'Find and fix bugs systematically',
    roles: ['analyzer','analyzer','tester','tester','worker','reviewer'],
    color: '#f59e0b'
  },
  'launch-prep': {
    name: 'Launch Readiness',
    icon: '🚀',
    desc: 'Pre-launch checklist & hardening',
    roles: ['pm','devops','security','tester','qa','reviewer','documenter'],
    color: '#8b5cf6'
  },
  'rapid-prototype': {
    name: 'Rapid Prototype',
    icon: '⚡',
    desc: 'Fast MVP build with 3 workers',
    roles: ['architect','worker','worker','worker','ux'],
    color: '#06b6d4'
  },
  'migration': {
    name: 'Migration Team',
    icon: '📦',
    desc: 'Database/platform migration',
    roles: ['architect','researcher','worker','worker','devops','tester','qa'],
    color: '#d946ef'
  }
};

/* ── Agent Pipeline Templates ── */
const PIPELINE_TEMPLATES = {
  'waterfall': {
    name: 'Waterfall',
    icon: '🌊',
    stages: ['Research','Design','Implement','Test','Review','Deploy'],
    roles:  ['researcher','architect','worker','tester','reviewer','devops']
  },
  'review-chain': {
    name: 'Review Chain',
    icon: '🔗',
    stages: ['Analyze','Fix','Test','Review','Sign-off'],
    roles:  ['analyzer','worker','tester','reviewer','qa']
  },
  'research-deep': {
    name: 'Deep Research',
    icon: '🔬',
    stages: ['Scout','Deep Dive','Cross-ref','Synthesize','Document'],
    roles:  ['researcher','researcher','analyzer','researcher','documenter']
  },
  'security-scan': {
    name: 'Security Pipeline',
    icon: '🛡️',
    stages: ['Scan','Analyze','Prioritize','Patch','Verify','Report'],
    roles:  ['security','analyzer','pm','worker','tester','documenter']
  }
};

/* ── State ── */
let state = {
  open: false,
  ws: null,
  wsReady: false,
  agent: null,
  recording: false,
  liveMode: false,
  mediaStream: null,
  mediaRec: null,
  audioCtx: null,
  analyser: null,
  waveAnim: null,
  messages: [],
  convId: null,
  favorites: JSON.parse(localStorage.getItem('aw-favorites') || '[]'),
  tab: 'chat',
  agentCat: 'flagship',
  pipelineActive: false,
  collabUsers: [],
  langDetected: null,
  vadActive: false,
  unread: 0,
  // Fleet state
  selectedModel: localStorage.getItem('aw-model') || 'sonnet',
  modelDropdownOpen: false,
  comparisonMode: false,
  comparisonModel: null,
  muted: false,
  agentModelLocks: JSON.parse(localStorage.getItem('aw-agent-model-locks') || '{}'),
  savedWorkflows: JSON.parse(localStorage.getItem('aw-saved-workflows') || '[]'),
  modelUsage: JSON.parse(localStorage.getItem('aw-model-usage') || '{}'),
  fleet: {
    active: false,
    agents: [],        // {id, role, agent, task, status, result, startTime}
    maxAgents: 10,
    pollInterval: null,
    formation: null,   // current formation key
    pipeline: null,    // current pipeline template
    pipelineStage: 0,  // current stage in pipeline
    missions: [],      // {id, title, status, assignee, priority, created}
    missionView: 'board', // 'board' | 'list'
    metrics: {
      totalTasks: 0,
      completed: 0,
      errors: 0,
      totalTokens: 0,
      avgResponseTime: 0,
      history: []     // {timestamp, agents, task, duration, tokens, success}
    },
    trainedAgents: JSON.parse(localStorage.getItem('aw-trained-agents') || '{}'),
    // ^^ { agentId: { instructions, expertise[], personality, trained_at } }
  }
};

/* ── DOM refs (set after inject) ── */
let DOM = {};

/* ══════════════════════════════════════════
   INIT
   ══════════════════════════════════════════ */
function init() {
  injectHTML();
  bindDOM();
  bindEvents();
  setAgent(findAgent('alfred'));
  detectPageContext();
  loadHistory();
  addWelcomeMessage();
  connectWS();

  // Keyboard shortcut: Ctrl+Shift+A
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
      e.preventDefault();
      togglePanel();
    }
  });

  // PWA standalone auto-open
  if (window.matchMedia('(display-mode: standalone)').matches) {
    togglePanel(true);
  }
}

/* ══════════════════════════════════════════
   HTML INJECTION
   ══════════════════════════════════════════ */
function injectHTML() {
  const wrap = document.createElement('div');
  wrap.id = 'alfred-widget';
  wrap.innerHTML = `
    <!-- Trigger Orb -->
    <button class="aw-trigger" id="awTrigger" aria-label="Open Alfred AI">
      <span class="aw-trigger-pulse"></span>
      <span class="aw-trigger-icon">🎩</span>
      <span class="aw-trigger-close">&times;</span>
      <span class="aw-trigger-badge" id="awBadge"></span>
    </button>

    <!-- Panel -->
    <div class="aw-panel" id="awPanel">

      <!-- Header -->
      <div class="aw-header">
        <div class="aw-agent-avatar" id="awAvatar">
          <span id="awAvatarEmoji">🎩</span>
          <span class="aw-agent-crown" id="awCrown">👑</span>
        </div>
        <div class="aw-header-info">
          <div class="aw-agent-name">
            <span id="awAgentName">Alfred</span>
            <span class="aw-conn-dot" id="awConnDot"></span>
            <span class="aw-lang-detected" id="awLangBadge"></span>
          </div>
          <div class="aw-agent-role" id="awAgentRole">AI Assistant</div>
        </div>
        <div class="aw-header-actions">
          <button class="aw-header-btn" id="awFavBtn" title="Favorite Agent"><i class="fa-regular fa-star"></i></button>
          <button class="aw-header-btn" id="awFullBtn" title="Full Voice Mode"><i class="fa-solid fa-expand"></i></button>
          <button class="aw-header-btn" id="awClearBtn" title="Clear Chat"><i class="fa-solid fa-trash-can"></i></button>
        </div>
      </div>

      <!-- Collab bar -->
      <div class="aw-collab-bar" id="awCollabBar"></div>

      <!-- Screen context banner -->
      <div class="aw-context-banner" id="awContextBanner">
        <i class="fa-solid fa-location-dot"></i>
        <span id="awContextText">Homepage</span>
      </div>

      <!-- Pipeline progress bar -->
      <div class="aw-pipeline-bar" id="awPipelineBar">
        <div class="aw-pipeline-bar-title" id="awPipelineTitle">Processing...</div>
        <div class="aw-pipeline-progress"><div class="aw-pipeline-fill" id="awPipelineFill" style="width:0%"></div></div>
      </div>

      <!-- Tabs -->
      <div class="aw-tabs">
        <button class="aw-tab active" data-tab="chat"><i class="fa-solid fa-comments"></i> Chat</button>
        <button class="aw-tab" data-tab="agents"><i class="fa-solid fa-users"></i> Agents</button>
        ${CFG.authToken ? '<button class="aw-tab" data-tab="fleet"><i class="fa-solid fa-people-group"></i> Fleet</button>' : ''}
        <button class="aw-tab" data-tab="history"><i class="fa-solid fa-clock-rotate-left"></i> History</button>
      </div>

      <!-- Chat Tab -->
      <div class="aw-tab-panel active" data-panel="chat">
        <div class="aw-shortcuts" id="awShortcuts"></div>
        <div class="aw-messages" id="awMessages"></div>
        <div class="aw-waveform-wrap" id="awWaveWrap"><canvas class="aw-waveform" id="awWaveform"></canvas></div>
        <div class="aw-input-area">
          <!-- Model selector row -->
          <div class="aw-model-bar">
            <button class="aw-model-selector" id="awModelSelector" title="Select AI Model">
              <span id="awModelIcon">🤖</span>
              <span id="awModelLabel">Auto</span>
              <i class="fa-solid fa-chevron-down aw-model-chevron"></i>
            </button>
            <div class="aw-fleet-indicator" id="awFleetIndicator" style="display:none">
              <span class="aw-fleet-dot"></span>
              <span id="awFleetCount">0</span> agents active
            </div>
          </div>
          <div class="aw-model-dropdown" id="awModelDropdown"></div>
          <div class="aw-input-row">
            <textarea class="aw-input" id="awInput" placeholder="Ask Alfred anything..." rows="1"></textarea>
            <button class="aw-mic-btn" id="awMicBtn" title="Voice input"><i class="fa-solid fa-microphone"></i></button>
            <button class="aw-send-btn" id="awSendBtn" title="Send"><i class="fa-solid fa-paper-plane"></i></button>
          </div>
          <div class="aw-input-hint"><kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>A</kbd> to toggle &bull; Try: "gather 5 agents"</div>
        </div>
      </div>

      <!-- Agents Tab -->
      <div class="aw-tab-panel" data-panel="agents">
        <div class="aw-agents-cats" id="awAgentCats"></div>
        <div class="aw-agents-grid" id="awAgentsGrid"></div>
      </div>

      <!-- Fleet Tab -->
      <div class="aw-tab-panel" data-panel="fleet">
        <!-- Fleet Sub-Navigation -->
        <div class="aw-fleet-subnav">
          <button class="aw-fleet-subnav-btn active" data-subview="roster"><i class="fa-solid fa-users"></i> Roster</button>
          <button class="aw-fleet-subnav-btn" data-subview="formations"><i class="fa-solid fa-chess"></i> Formations</button>
          <button class="aw-fleet-subnav-btn" data-subview="missions"><i class="fa-solid fa-bullseye"></i> Missions</button>
          <button class="aw-fleet-subnav-btn" data-subview="pipeline"><i class="fa-solid fa-diagram-project"></i> Pipeline</button>
          <button class="aw-fleet-subnav-btn" data-subview="training"><i class="fa-solid fa-graduation-cap"></i> Train</button>
          <button class="aw-fleet-subnav-btn" data-subview="metrics"><i class="fa-solid fa-chart-line"></i> Metrics</button>
        </div>

        <!-- ROSTER Subview -->
        <div class="aw-fleet-subview active" data-fleetview="roster">
          <div class="aw-fleet-controls" id="awFleetControls">
            <div class="aw-fleet-header">
              <h3 class="aw-fleet-title"><i class="fa-solid fa-people-group"></i> Agent Fleet</h3>
              <div class="aw-fleet-actions">
                <button class="aw-fleet-btn gather" id="awFleetGather" title="Gather agents"><i class="fa-solid fa-plus"></i> Gather</button>
                <button class="aw-fleet-btn dismiss" id="awFleetDismiss" title="Dismiss all" style="display:none"><i class="fa-solid fa-xmark"></i> Dismiss All</button>
              </div>
            </div>
            <div class="aw-fleet-quick">
              <button class="aw-fleet-preset" data-count="3" data-task="research">🔍 3 Researchers</button>
              <button class="aw-fleet-preset" data-count="5" data-task="mixed">⚡ 5 Mixed</button>
              <button class="aw-fleet-preset" data-count="10" data-task="fullteam">🚀 Full Team (10)</button>
            </div>
          </div>
          <div class="aw-fleet-roster" id="awFleetRoster">
            <div class="aw-fleet-empty" id="awFleetEmpty">
              <div class="aw-fleet-empty-icon">🪖</div>
              <p>No agents deployed yet.</p>
              <p class="aw-muted">Say <strong>"gather 5 agents"</strong> or use presets above.</p>
            </div>
          </div>
        </div>

        <!-- FORMATIONS Subview -->
        <div class="aw-fleet-subview" data-fleetview="formations">
          <div class="aw-fleet-formations-grid" id="awFormationsGrid"></div>
        </div>

        <!-- MISSIONS Subview (Kanban Board) -->
        <div class="aw-fleet-subview" data-fleetview="missions">
          <div class="aw-mission-controls">
            <button class="aw-mission-add-btn" id="awMissionAdd"><i class="fa-solid fa-plus"></i> New Mission</button>
            <div class="aw-mission-view-toggle">
              <button class="aw-mission-view-btn active" data-mview="board"><i class="fa-solid fa-columns"></i></button>
              <button class="aw-mission-view-btn" data-mview="list"><i class="fa-solid fa-list"></i></button>
            </div>
          </div>
          <div class="aw-mission-board" id="awMissionBoard">
            <div class="aw-mission-col" data-col="todo">
              <div class="aw-mission-col-header">📋 To Do <span class="aw-mission-col-count" id="awMcTodo">0</span></div>
              <div class="aw-mission-col-body" id="awMcTodoBody"></div>
            </div>
            <div class="aw-mission-col" data-col="active">
              <div class="aw-mission-col-header">🔄 Active <span class="aw-mission-col-count" id="awMcActive">0</span></div>
              <div class="aw-mission-col-body" id="awMcActiveBody"></div>
            </div>
            <div class="aw-mission-col" data-col="done">
              <div class="aw-mission-col-header">✅ Done <span class="aw-mission-col-count" id="awMcDone">0</span></div>
              <div class="aw-mission-col-body" id="awMcDoneBody"></div>
            </div>
          </div>
        </div>

        <!-- PIPELINE Subview -->
        <div class="aw-fleet-subview" data-fleetview="pipeline">
          <div class="aw-pipeline-templates" id="awPipelineTemplates"></div>
          <div class="aw-pipeline-visual" id="awPipelineVisual"></div>
        </div>

        <!-- TRAINING Subview -->
        <div class="aw-fleet-subview" data-fleetview="training">
          <div class="aw-training-panel" id="awTrainingPanel">
            <div class="aw-training-header">
              <h4><i class="fa-solid fa-graduation-cap"></i> Agent Training Center</h4>
              <p class="aw-muted">Customize any agent with special instructions, expertise, and personality.</p>
            </div>
            <div class="aw-training-agent-select" id="awTrainingAgentSelect"></div>
            <div class="aw-training-form" id="awTrainingForm" style="display:none">
              <div class="aw-training-selected" id="awTrainingSelected"></div>
              <label class="aw-training-label">Custom Instructions</label>
              <textarea class="aw-training-textarea" id="awTrainingInstructions" rows="3" placeholder="e.g. Always respond in bullet points. Focus on PHP and Laravel."></textarea>
              <label class="aw-training-label">Expertise Tags</label>
              <input class="aw-training-input" id="awTrainingExpertise" placeholder="e.g. PHP, Laravel, MySQL, Redis" />
              <label class="aw-training-label">Personality Style</label>
              <select class="aw-training-select" id="awTrainingPersonality">
                <option value="default">Default</option>
                <option value="formal">Formal & Professional</option>
                <option value="casual">Casual & Friendly</option>
                <option value="concise">Ultra Concise</option>
                <option value="detailed">Detailed & Thorough</option>
                <option value="socratic">Socratic (asks questions)</option>
                <option value="mentor">Mentor / Teacher</option>
                <option value="exec">Executive Summary</option>
              </select>
              <div class="aw-training-actions">
                <button class="aw-fleet-btn gather" id="awTrainSave"><i class="fa-solid fa-save"></i> Save Training</button>
                <button class="aw-fleet-btn dismiss" id="awTrainClear"><i class="fa-solid fa-eraser"></i> Reset</button>
              </div>
            </div>
          </div>
        </div>

        <!-- METRICS Subview -->
        <div class="aw-fleet-subview" data-fleetview="metrics">
          <div class="aw-metrics-panel" id="awMetricsPanel">
            <div class="aw-metrics-cards" id="awMetricsCards"></div>
            <div class="aw-metrics-chart" id="awMetricsChart"></div>
            <div class="aw-metrics-history" id="awMetricsHistory"></div>
          </div>
        </div>
      </div>

      <!-- History Tab -->
      <div class="aw-tab-panel" data-panel="history">
        <div class="aw-history-list" id="awHistoryList"></div>
      </div>

    </div>
  `;
  document.body.appendChild(wrap);
}

/* ── Bind DOM refs ── */
function bindDOM() {
  DOM.trigger    = document.getElementById('awTrigger');
  DOM.panel      = document.getElementById('awPanel');
  DOM.badge      = document.getElementById('awBadge');
  DOM.avatar     = document.getElementById('awAvatar');
  DOM.avatarEmoji= document.getElementById('awAvatarEmoji');
  DOM.crown      = document.getElementById('awCrown');
  DOM.agentName  = document.getElementById('awAgentName');
  DOM.agentRole  = document.getElementById('awAgentRole');
  DOM.connDot    = document.getElementById('awConnDot');
  DOM.langBadge  = document.getElementById('awLangBadge');
  DOM.favBtn     = document.getElementById('awFavBtn');
  DOM.fullBtn    = document.getElementById('awFullBtn');
  DOM.clearBtn   = document.getElementById('awClearBtn');
  DOM.collabBar  = document.getElementById('awCollabBar');
  DOM.contextBanner = document.getElementById('awContextBanner');
  DOM.contextText = document.getElementById('awContextText');
  DOM.pipelineBar = document.getElementById('awPipelineBar');
  DOM.pipelineTitle = document.getElementById('awPipelineTitle');
  DOM.pipelineFill = document.getElementById('awPipelineFill');
  DOM.shortcuts  = document.getElementById('awShortcuts');
  DOM.messages   = document.getElementById('awMessages');
  DOM.waveWrap   = document.getElementById('awWaveWrap');
  DOM.waveform   = document.getElementById('awWaveform');
  DOM.input      = document.getElementById('awInput');
  DOM.micBtn     = document.getElementById('awMicBtn');
  DOM.sendBtn    = document.getElementById('awSendBtn');
  DOM.agentCats  = document.getElementById('awAgentCats');
  DOM.agentsGrid = document.getElementById('awAgentsGrid');
  DOM.historyList= document.getElementById('awHistoryList');
  // Model selector
  DOM.modelSelector = document.getElementById('awModelSelector');
  DOM.modelIcon     = document.getElementById('awModelIcon');
  DOM.modelLabel    = document.getElementById('awModelLabel');
  DOM.modelDropdown = document.getElementById('awModelDropdown');
  // Fleet
  DOM.fleetIndicator = document.getElementById('awFleetIndicator');
  DOM.fleetCount     = document.getElementById('awFleetCount');
  DOM.fleetGather    = document.getElementById('awFleetGather');
  DOM.fleetDismiss   = document.getElementById('awFleetDismiss');
  DOM.fleetRoster    = document.getElementById('awFleetRoster');
  DOM.fleetEmpty     = document.getElementById('awFleetEmpty');
  // Fleet sub-views
  DOM.formationsGrid    = document.getElementById('awFormationsGrid');
  DOM.missionBoard      = document.getElementById('awMissionBoard');
  DOM.missionAdd        = document.getElementById('awMissionAdd');
  DOM.pipelineTemplates = document.getElementById('awPipelineTemplates');
  DOM.pipelineVisual    = document.getElementById('awPipelineVisual');
  DOM.trainingPanel     = document.getElementById('awTrainingPanel');
  DOM.trainingAgentSelect = document.getElementById('awTrainingAgentSelect');
  DOM.trainingForm      = document.getElementById('awTrainingForm');
  DOM.trainingSelected  = document.getElementById('awTrainingSelected');
  DOM.trainingInstr     = document.getElementById('awTrainingInstructions');
  DOM.trainingExpertise = document.getElementById('awTrainingExpertise');
  DOM.trainingPersonality = document.getElementById('awTrainingPersonality');
  DOM.trainSave         = document.getElementById('awTrainSave');
  DOM.trainClear        = document.getElementById('awTrainClear');
  DOM.metricsCards      = document.getElementById('awMetricsCards');
  DOM.metricsChart      = document.getElementById('awMetricsChart');
  DOM.metricsHistory    = document.getElementById('awMetricsHistory');
}

/* ═══════════════════════════════════════
   EVENT BINDING
   ═══════════════════════════════════════ */
function bindEvents() {
  DOM.trigger.addEventListener('click', ()=> togglePanel());
  DOM.sendBtn.addEventListener('click', sendMessage);
  DOM.input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
  // Auto-resize textarea
  DOM.input.addEventListener('input', ()=> {
    DOM.input.style.height = 'auto';
    DOM.input.style.height = Math.min(DOM.input.scrollHeight, 100) + 'px';
  });

  // Mic: click = tap record, long press = live mode
  let micTimer = null;
  DOM.micBtn.addEventListener('mousedown', ()=> {
    micTimer = setTimeout(()=> { micTimer = null; toggleLiveMode(); }, 600);
  });
  DOM.micBtn.addEventListener('mouseup', ()=> {
    if (micTimer) { clearTimeout(micTimer); micTimer = null; toggleTapRecord(); }
  });
  DOM.micBtn.addEventListener('touchstart', e => {
    e.preventDefault();
    micTimer = setTimeout(()=> { micTimer = null; toggleLiveMode(); }, 600);
  }, {passive:false});
  DOM.micBtn.addEventListener('touchend', e => {
    e.preventDefault();
    if (micTimer) { clearTimeout(micTimer); micTimer = null; toggleTapRecord(); }
  });

  // Header buttons
  DOM.favBtn.addEventListener('click', toggleFavorite);
  DOM.fullBtn.addEventListener('click', ()=> window.open(CFG.fullVoice, '_blank'));
  DOM.clearBtn.addEventListener('click', clearChat);

  // Tabs
  document.querySelectorAll('.aw-tab').forEach(t => {
    t.addEventListener('click', ()=> switchTab(t.dataset.tab));
  });

  // Build shortcuts
  buildShortcuts();
  buildAgentCats();
  buildAgentsGrid();
  buildModelDropdown();
  bindFleetEvents();
  buildFormationsGrid();
  buildPipelineTemplates();
  buildTrainingAgentSelect();
  renderMetrics();

  // Model selector toggle
  DOM.modelSelector.addEventListener('click', e => {
    e.stopPropagation();
    state.modelDropdownOpen = !state.modelDropdownOpen;
    DOM.modelDropdown.classList.toggle('open', state.modelDropdownOpen);
  });

  // Close model dropdown on outside click
  document.addEventListener('click', e => {
    if (state.modelDropdownOpen && !DOM.modelDropdown.contains(e.target)) {
      state.modelDropdownOpen = false;
      DOM.modelDropdown.classList.remove('open');
    }
  });

  // Close on outside click
  document.addEventListener('click', e => {
    if (state.open && !DOM.panel.contains(e.target) && !DOM.trigger.contains(e.target)) {
      togglePanel(false);
    }
  });

  // Escape to close
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && state.open) togglePanel(false);
  });

  // ── Auto-save conversation on page close / tab switch / hangup ──────────
  // Uses sendBeacon which fires reliably even during page teardown.
  // This ensures work-in-progress (e.g. drafting a habeas corpus) is saved
  // to the server when the user hangs up mid-call or closes the tab.
  window.addEventListener('beforeunload', flushConversation);
  window.addEventListener('pagehide', flushConversation);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') flushConversation();
  });
}

/* ═══════════════════════════════════════
   PANEL TOGGLE
   ═══════════════════════════════════════ */
function togglePanel(force) {
  state.open = (force !== undefined) ? force : !state.open;
  DOM.panel.classList.toggle('open', state.open);
  DOM.trigger.classList.toggle('active', state.open);
  if (state.open) {
    state.unread = 0;
    DOM.badge.classList.remove('visible');
    DOM.input.focus();
    scrollToBottom();
  }
}

/* ═══════════════════════════════════════
   TABS
   ═══════════════════════════════════════ */
function switchTab(tab) {
  state.tab = tab;
  document.querySelectorAll('.aw-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  document.querySelectorAll('.aw-tab-panel').forEach(p => p.classList.toggle('active', p.dataset.panel === tab));
  if (tab === 'history') renderHistory();
  if (tab === 'agents') buildAgentsGrid();
  if (tab === 'fleet') renderFleetRoster();
}

/* ═══════════════════════════════════════
   SHORTCUTS
   ═══════════════════════════════════════ */
function buildShortcuts() {
  DOM.shortcuts.innerHTML = SHORTCUTS.map(s =>
    `<button class="aw-shortcut" data-cmd="${esc(s.cmd)}"><span class="sc-icon">${s.icon}</span>${s.label}</button>`
  ).join('');
  DOM.shortcuts.querySelectorAll('.aw-shortcut').forEach(btn => {
    btn.addEventListener('click', ()=> {
      DOM.input.value = btn.dataset.cmd;
      sendMessage();
    });
  });
}

/* ═══════════════════════════════════════
   AGENTS
   ═══════════════════════════════════════ */
function buildAgentCats() {
  let favBtn = `<button class="aw-agents-cat-btn" data-cat="favorites">⭐ Favorites</button>`;
  let cats = Object.entries(AGENTS).map(([k,v]) =>
    `<button class="aw-agents-cat-btn${k===state.agentCat?' active':''}" data-cat="${k}">${v.label}</button>`
  ).join('');
  DOM.agentCats.innerHTML = favBtn + cats;
  DOM.agentCats.querySelectorAll('.aw-agents-cat-btn').forEach(btn => {
    btn.addEventListener('click', ()=> {
      state.agentCat = btn.dataset.cat;
      DOM.agentCats.querySelectorAll('.aw-agents-cat-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      buildAgentsGrid();
    });
  });
}

function buildAgentsGrid() {
  let agents;
  if (state.agentCat === 'favorites') {
    agents = getAllAgents().filter(a => state.favorites.includes(a.id));
    if (!agents.length) {
      DOM.agentsGrid.innerHTML = '<div class="aw-history-empty"><div class="aw-history-empty-icon">⭐</div><p>No favorites yet.<br>Star agents to add them here.</p></div>';
      return;
    }
  } else {
    agents = AGENTS[state.agentCat]?.agents || [];
  }
  DOM.agentsGrid.innerHTML = agents.map(a => {
    const sel = state.agent?.id === a.id ? ' selected' : '';
    const fav = state.favorites.includes(a.id) ? ' favorited' : '';
    return `<div class="aw-agent-card${sel}" data-agent-id="${a.id}" style="position:relative">
      <div class="aw-agent-card-emoji">${a.emoji}</div>
      <div class="aw-agent-card-name">${a.name}</div>
      <div class="aw-agent-card-role">${a.role}</div>
      <span class="aw-agent-card-fav${fav}" data-fav="${a.id}">${fav ? '★' : '☆'}</span>
    </div>`;
  }).join('');

  DOM.agentsGrid.querySelectorAll('.aw-agent-card').forEach(card => {
    card.addEventListener('click', e => {
      if (e.target.closest('.aw-agent-card-fav')) return;
      const a = findAgent(card.dataset.agentId);
      if (a) { setAgent(a); switchTab('chat'); addSystemMsg(`Switched to ${a.emoji} ${a.name}`); }
    });
  });
  DOM.agentsGrid.querySelectorAll('.aw-agent-card-fav').forEach(star => {
    star.addEventListener('click', e => {
      e.stopPropagation();
      const id = star.dataset.fav;
      toggleFavById(id);
      buildAgentsGrid();
    });
  });
}

function findAgent(id) {
  for (const cat of Object.values(AGENTS)) {
    const a = cat.agents.find(a => a.id === id);
    if (a) return a;
  }
  return null;
}
function getAllAgents() {
  return Object.values(AGENTS).flatMap(c => c.agents);
}
function setAgent(a) {
  state.agent = a;
  DOM.avatarEmoji.textContent = a.emoji;
  DOM.agentName.textContent = a.name;
  DOM.agentRole.textContent = a.role;
  DOM.crown.style.display = (a.id === 'alfred') ? '' : 'none';
  updateFavBtnUI();
}

/* ── Favorites ── */
function toggleFavorite() {
  if (!state.agent) return;
  toggleFavById(state.agent.id);
}
function toggleFavById(id) {
  const idx = state.favorites.indexOf(id);
  if (idx >= 0) state.favorites.splice(idx, 1);
  else state.favorites.push(id);
  localStorage.setItem('aw-favorites', JSON.stringify(state.favorites));
  updateFavBtnUI();
}
function updateFavBtnUI() {
  const fav = state.agent && state.favorites.includes(state.agent.id);
  DOM.favBtn.classList.toggle('fav-active', fav);
  DOM.favBtn.innerHTML = fav ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
}

/* ═══════════════════════════════════════
   SCREEN AWARENESS
   ═══════════════════════════════════════ */
function detectPageContext() {
  const path = window.location.pathname;
  for (const p of PAGE_CONTEXTS) {
    if (p.pattern.test(path)) {
      DOM.contextText.innerHTML = `<i class="fa-solid ${p.icon}"></i> ${p.context}`;
      state.pageContext = p.context;
      return;
    }
  }
  DOM.contextText.textContent = path;
  state.pageContext = 'Browsing ' + path;
}

/* ═══════════════════════════════════════
   WEBSOCKET
   ═══════════════════════════════════════ */
let wsRetries = 0;
const WS_MAX_RETRIES = 10;

function connectWS() {
  if (!CFG.authToken) return;
  if (wsRetries >= WS_MAX_RETRIES) return;
  try {
    state.ws = new WebSocket(CFG.wsUrl);
    state.ws.binaryType = 'arraybuffer';
    state.ws.onopen = () => {
      wsRetries = 0;
      state.wsReady = true;
      DOM.connDot.classList.add('connected');
      state.ws.send(JSON.stringify({
        type: 'auth',
        token: CFG.authToken,
        username: CFG.username
      }));
    };
    state.ws.onmessage = handleWSMessage;
    state.ws.onclose = () => {
      state.wsReady = false;
      DOM.connDot.classList.remove('connected');
      wsRetries++;
      if (wsRetries < WS_MAX_RETRIES) {
        // Exponential backoff with jitter: 1s, 2s, 4s, 8s... capped at 60s
        const delay = Math.min(1000 * Math.pow(2, wsRetries), 60000);
        const jitter = Math.random() * delay * 0.3;
        setTimeout(connectWS, delay + jitter);
      }
    };
    state.ws.onerror = () => { state.ws.close(); };
  } catch(e) { /* silenced */ }
}

// Reconnect when tab becomes visible again
document.addEventListener('visibilitychange', () => {
  if (!document.hidden && !state.wsReady && CFG.authToken) {
    wsRetries = 0;
    connectWS();
  }
});

function handleWSMessage(evt) {
  if (evt.data instanceof ArrayBuffer) {
    playAudioChunk(evt.data);
    return;
  }
  try {
    const msg = JSON.parse(evt.data);
    switch(msg.type) {
      case 'transcript':
        if (msg.text) addAlfred(msg.text);
        break;
      case 'response':
      case 'ai_response':
        if (state._wsTimeout) { clearTimeout(state._wsTimeout); state._wsTimeout = null; }
        showTyping(false);
        addAlfred(msg.text || msg.content || msg.message);
        if (msg.actions && msg.actions.length) executeActions(msg.actions);
        break;
      case 'thinking':
        showTyping(true);
        break;
      case 'done':
        if (state._wsTimeout) { clearTimeout(state._wsTimeout); state._wsTimeout = null; }
        showTyping(false);
        break;
      case 'error':
        if (state._wsTimeout) { clearTimeout(state._wsTimeout); state._wsTimeout = null; }
        showTyping(false);
        addSystemMsg('Error: ' + (msg.message || 'Something went wrong'));
        break;
      case 'pipeline_update':
        updatePipeline(msg);
        break;
      case 'collab_join':
        addCollabUser(msg);
        break;
      case 'collab_leave':
        removeCollabUser(msg);
        break;
      case 'audio_start':
        DOM.trigger.classList.add('live');
        break;
      case 'audio_end':
        DOM.trigger.classList.remove('live');
        break;
    }
  } catch(e) {}
}

function wsSend(obj) {
  if (state.ws && state.wsReady) {
    state.ws.send(JSON.stringify(obj));
    return true;
  }
  return false;
}

/* ═══════════════════════════════════════
   SEND MESSAGE (Text)
   ═══════════════════════════════════════ */
function sendMessage(directText) {
  const text = directText || DOM.input.value.trim();
  if (!text) return;
  DOM.input.value = '';
  DOM.input.style.height = 'auto';
  if (!directText) addUserMsg(text);

  // Detect language
  detectLanguage(text);

  // Detect fleet commands first
  if (detectFleetCommand(text)) return;

  // If fleet is active and agents are on standby, assign task
  if (assignFleetTask(text)) return;

  // A/B comparison mode
  if (state.comparisonModel) {
    sendComparisonRequest(text);
    return;
  }

  // Build context payload
  const effectiveModel = getEffectiveModel();
  let ctx = state.pageContext || '';
  // Enrich context if a page-specific context provider exists (e.g. chess game)
  if (typeof window.getChessContext === 'function') {
    const rich = window.getChessContext();
    if (rich) ctx = rich;
  }
  const payload = {
    type: 'chat',
    message: text,
    agent: state.agent?.id || 'alfred',
    engine: state.agent?.engine || 'kokoro',
    voice: state.agent?.voice || 'onyx',
    context: ctx,
    page_url: window.location.href,
    page_title: document.title,
    conv_id: state.convId,
    model: effectiveModel
  };

  // Try WebSocket first, fall back to REST
  if (!wsSend(payload)) {
    sendViaRest(text);
  } else {
    showTyping(true);
    // Safety timeout — fall back to REST if WS doesn't respond within 30s
    if (state._wsTimeout) clearTimeout(state._wsTimeout);
    state._wsTimeout = setTimeout(() => {
      if (DOM.messages.querySelector('.aw-typing-indicator')) {
        showTyping(false);
        sendViaRest(text);
      }
    }, 30000);
  }

  // Save to history  
  saveConversation(text, 'user');
}

async function sendViaRest(text, _retry) {
  showTyping(true);
  try {
    const effectiveModel = getEffectiveModel();
    let ctx = state.pageContext || '';
    if (typeof window.getChessContext === 'function') {
      const rich = window.getChessContext();
      if (rich) ctx = rich;
    }
    const resp = await fetch(CFG.chatApi, {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-Token': CFG.csrfToken,
        'X-Alfred-Token': CFG.authToken
      },
      body: JSON.stringify({
        message: text,
        agent: state.agent?.id || 'alfred',
        context: ctx,
        page_url: window.location.href,
        conv_id: state.convId,
        model: effectiveModel
      })
    });
    const data = await resp.json();
    // Handle CSRF token refresh — auto-retry once with new token
    if (data.csrf_refresh || (data.error && data.csrf_token && !_retry)) {
      CFG.csrfToken = data.csrf_token;
      showTyping(false);
      return sendViaRest(text, true);
    }
    showTyping(false);
    if (data.response) {
      addAlfred(data.response, data.cards);
      if (data.conv_id) state.convId = data.conv_id;
      if (data.csrf_token) CFG.csrfToken = data.csrf_token;
      if (data.request_id) state.lastRequestId = data.request_id;
      // Execute any navigation/page actions
      if (data.actions && data.actions.length) {
        executeActions(data.actions);
      }
    } else if (data.error) {
      addSystemMsg(data.error);
    }
  } catch(e) {
    showTyping(false);
    addSystemMsg('Connection error. Please try again.');
  }
}

/* ═══════════════════════════════════════
   PAGE ACTION EXECUTOR — Alfred controls the browser
   ═══════════════════════════════════════ */
function executeActions(actions) {
  for (const action of actions) {
    switch (action.type) {
      case 'navigate':
        // Navigate to internal page after a brief delay so user sees the message
        setTimeout(() => {
          window.location.href = action.url;
        }, 1200);
        break;

      case 'open_external':
        // Open external link in new tab — never leave GoSiteMe
        setTimeout(() => {
          window.open(action.url, '_blank', 'noopener,noreferrer');
        }, 800);
        break;

      case 'scroll':
        // Scroll to element on current page
        setTimeout(() => {
          const el = document.querySelector(action.selector);
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 600);
        break;

      case 'highlight':
        // Flash-highlight an element
        setTimeout(() => {
          const el = document.querySelector(action.selector);
          if (el) {
            el.style.transition = 'box-shadow 0.3s, outline 0.3s';
            el.style.outline = '3px solid #7d00ff';
            el.style.boxShadow = '0 0 20px rgba(125,0,255,0.5)';
            setTimeout(() => {
              el.style.outline = '';
              el.style.boxShadow = '';
            }, 3000);
          }
        }, 600);
        break;

      case 'search_domain':
        // Trigger domain search on homepage
        setTimeout(() => {
          const input = document.getElementById('domainInput');
          const form = document.getElementById('domainSearchForm');
          if (input && form) {
            input.value = action.domain;
            input.dispatchEvent(new Event('input'));
            form.dispatchEvent(new Event('submit', { cancelable: true }));
          } else if (window.location.pathname !== '/') {
            // If not on homepage, navigate there with domain param
            window.location.href = '/?search_domain=' + encodeURIComponent(action.domain);
          }
        }, 800);
        break;
    }
  }
}

/* ═══════════════════════════════════════
   VIDEO GENERATION (Veo 2 via Together.ai)
   ═══════════════════════════════════════ */
async function generateVideo(prompt, model = 'veo-2', duration = 5) {
  addUserMsg(`🎬 Generate video: ${prompt}`);
  addSystemMsg('🎬 Submitting video generation... This takes 1-5 minutes.');
  showTyping(true);

  try {
    const resp = await fetch('/middleware/api/extras/generate-video', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CFG.csrfToken,
        'X-Alfred-Token': CFG.authToken
      },
      body: JSON.stringify({ prompt, model, duration })
    });
    const data = await resp.json();

    if (!data.ok) {
      showTyping(false);
      addSystemMsg(`❌ ${data.error || 'Video generation failed'}`);
      return;
    }

    // Start polling for completion
    addSystemMsg(`🎬 Video job submitted (${data.jobId}). Polling for completion...`);
    pollVideoStatus(data.jobId, prompt);
  } catch (e) {
    showTyping(false);
    addSystemMsg('❌ Connection error submitting video.');
  }
}

async function pollVideoStatus(jobId, prompt) {
  const POLL_INTERVAL = 8000;
  const MAX_POLLS = 75;

  for (let i = 0; i < MAX_POLLS; i++) {
    await new Promise(r => setTimeout(r, POLL_INTERVAL));

    try {
      const resp = await fetch(`/middleware/api/extras/video-status/${encodeURIComponent(jobId)}`, {
        headers: {
          'X-CSRF-Token': CFG.csrfToken,
          'X-Alfred-Token': CFG.authToken
        }
      });
      const data = await resp.json();

      if (data.status === 'completed' && data.videoUrl) {
        showTyping(false);
        addVideoMsg(data.videoUrl, prompt);
        return;
      }

      if (data.status === 'failed') {
        showTyping(false);
        addSystemMsg(`❌ Video generation failed: ${data.error || 'Unknown error'}`);
        return;
      }

      // Still processing — update progress every 3rd poll
      if (i > 0 && i % 3 === 0) {
        const elapsed = Math.round((i * POLL_INTERVAL) / 1000);
        addSystemMsg(`⏳ Still generating... (${elapsed}s elapsed, status: ${data.status || 'processing'})`);
      }
    } catch (e) {
      // Network hiccup, keep trying
    }
  }

  showTyping(false);
  addSystemMsg('⏰ Video generation timed out after 10 minutes.');
}

function addVideoMsg(videoUrl, prompt) {
  const msg = {role: 'alfred', text: `🎬 Video generated: ${prompt}`, time: new Date()};
  state.messages.push(msg);
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  el.innerHTML = `
    <div class="aw-msg-sender">${state.agent?.emoji || '🎩'} ${state.agent?.name || 'Alfred'}</div>
    <div class="aw-msg-bubble">
      <div style="margin-bottom:8px"><strong>🎬 Video Generated</strong></div>
      <video controls playsinline preload="metadata"
        style="max-width:100%;border-radius:8px;background:#000"
        src="${esc(videoUrl)}">
        Your browser does not support the video tag.
      </video>
      <div style="margin-top:6px;font-size:0.8em;opacity:0.7">${esc(prompt)}</div>
      <div style="margin-top:4px">
        <a href="${esc(videoUrl)}" download style="color:var(--aw-accent);text-decoration:underline;font-size:0.85em">⬇ Download Video</a>
      </div>
    </div>
    <div class="aw-msg-time">${fmtTime(msg.time)}</div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
  saveConversation(msg.text, 'alfred');
}

/* ═══════════════════════════════════════
   MESSAGES
   ═══════════════════════════════════════ */
function addUserMsg(text) {
  const msg = {role:'user', text, time: new Date()};
  state.messages.push(msg);
  const el = document.createElement('div');
  el.className = 'aw-msg user';
  el.innerHTML = `<div class="aw-msg-bubble">${esc(text)}</div><div class="aw-msg-time">${fmtTime(msg.time)}</div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
}

function addAlfred(text, cards) {
  showTyping(false);
  const msg = {role:'alfred', text, time: new Date()};
  state.messages.push(msg);
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  let html = `<div class="aw-msg-sender">${state.agent?.emoji||'🎩'} ${state.agent?.name||'Alfred'}</div>`;
  html += `<div class="aw-msg-bubble">${renderRichText(text)}</div>`;
  if (cards) html += renderCards(cards);
  html += `<div class="aw-msg-time">${fmtTime(msg.time)}</div>`;
  el.innerHTML = html;
  DOM.messages.appendChild(el);
  scrollToBottom();
  saveConversation(text, 'alfred');

  // Update unread badge if panel closed
  if (!state.open) {
    state.unread++;
    DOM.badge.textContent = state.unread;
    DOM.badge.classList.add('visible');
  }
}

function addSystemMsg(text) {
  const el = document.createElement('div');
  el.className = 'aw-msg system';
  el.innerHTML = `<div class="aw-msg-bubble">${esc(text)}</div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
}

function addWelcomeMessage() {
  const hour = new Date().getHours();
  const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
  const name = CFG.username !== 'Guest' ? ', ' + CFG.username : '';
  addAlfred(`${greeting}${name}! I'm ${state.agent?.name||'Alfred'}, your AI assistant. How can I help you today?\n\nYou can type a message, use voice, or try the quick shortcuts above.`);
}

function showTyping(show) {
  let el = DOM.messages.querySelector('.aw-typing-indicator');
  if (show && !el) {
    el = document.createElement('div');
    el.className = 'aw-msg alfred aw-typing-indicator';
    el.innerHTML = `<div class="aw-msg-sender">${state.agent?.emoji||'🎩'} ${state.agent?.name||'Alfred'}</div>
      <div class="aw-msg-bubble"><div class="aw-typing"><span class="aw-typing-dot"></span><span class="aw-typing-dot"></span><span class="aw-typing-dot"></span></div></div>`;
    DOM.messages.appendChild(el);
    scrollToBottom();
  } else if (!show && el) {
    el.remove();
  }
}

function clearChat() {
  state.messages = [];
  state.convId = null;
  DOM.messages.innerHTML = '';
  addWelcomeMessage();
}

function scrollToBottom() {
  requestAnimationFrame(()=> { DOM.messages.scrollTop = DOM.messages.scrollHeight; });
}

/* ═══════════════════════════════════════
   RICH TEXT RENDERING
   ═══════════════════════════════════════ */
function renderRichText(text) {
  if (!text) return '';
  let html = esc(text);
  // Code blocks ```lang\n...\n```
  html = html.replace(/```(\w*)\n([\s\S]*?)```/g, (_, lang, code) => {
    return `<div class="aw-code-block"><div class="aw-code-header"><span>${lang||'code'}</span><button class="aw-code-copy" onclick="navigator.clipboard.writeText(this.closest('.aw-code-block').querySelector('pre').textContent).then(()=>{this.textContent='Copied!'})">Copy</button></div><pre class="aw-code-pre">${code}</pre></div>`;
  });
  // Inline code
  html = html.replace(/`([^`]+)`/g, '<code style="background:rgba(125,0,255,0.15);padding:1px 5px;border-radius:4px;font-size:0.82em">$1</code>');
  // Bold
  html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  // Italic
  html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
  // Line breaks
  html = html.replace(/\n/g, '<br>');
  // Links — sanitize URL to prevent XSS via crafted href attributes
  html = html.replace(/(https?:\/\/[^\s<"'()]+)/g, (_, url) => {
    const safe = url.replace(/["'<>]/g, '');
    return `<a href="${safe}" target="_blank" rel="noopener noreferrer" style="color:var(--aw-accent);text-decoration:underline">${safe}</a>`;
  });
  return html;
}

/* ── Rich Cards ── */
function renderCards(cards) {
  if (!cards || !Array.isArray(cards)) return '';
  return cards.map(c => {
    switch(c.type) {
      case 'product': return renderProductCard(c);
      case 'domain':  return renderDomainCard(c);
      case 'code':    return renderCodeCard(c);
      case 'pipeline': return renderPipelineCard(c);
      case 'pricing': return renderPricingCard(c);
      default: return '';
    }
  }).join('');
}

function renderProductCard(c) {
  return `<div class="aw-card"><div class="aw-card-header"><i class="fa-solid fa-box"></i> Product</div>
    <div class="aw-card-body"><div class="aw-product-card">
      <div class="aw-product-icon">${c.icon||'📦'}</div>
      <div class="aw-product-info">
        <div class="aw-product-name">${esc(c.name)}</div>
        <div class="aw-product-price">${esc(c.price||'')}</div>
        <div class="aw-product-desc">${esc(c.description||'')}</div>
      </div>
      ${c.url ? `<a href="${esc(c.url)}" class="aw-product-btn">${c.btnText||'View'}</a>` : ''}
    </div></div></div>`;
}

function renderDomainCard(c) {
  return `<div class="aw-card"><div class="aw-card-header"><i class="fa-solid fa-globe"></i> Domain Availability</div>
    <div class="aw-card-body"><div class="aw-domain-card">${(c.domains||[]).map(d =>
      `<div class="aw-domain-row"><span class="aw-domain-name">${esc(d.name)}</span>
       <span class="aw-domain-status ${d.available?'aw-domain-available':'aw-domain-taken'}">${d.available?'Available':'Taken'}</span>
       ${d.price ? `<span class="aw-domain-price">${esc(d.price)}</span>` : ''}
       </div>`
    ).join('')}</div></div></div>`;
}

function renderCodeCard(c) {
  return `<div class="aw-code-block"><div class="aw-code-header"><span>${esc(c.language||'code')}</span>
    <button class="aw-code-copy" onclick="navigator.clipboard.writeText(this.closest('.aw-code-block').querySelector('pre').textContent).then(()=>{this.textContent='Copied!'})">Copy</button></div>
    <pre class="aw-code-pre">${esc(c.code||'')}</pre></div>`;
}

function renderPipelineCard(c) {
  return `<div class="aw-card"><div class="aw-card-header"><i class="fa-solid fa-diagram-project"></i> ${esc(c.title||'Pipeline')}</div>
    <div class="aw-card-body"><div class="aw-pipeline">${(c.steps||[]).map((s,i) =>
      `<div class="aw-pipeline-step ${s.status||'pending'}">
        <div class="aw-pipeline-dot">${s.status==='done'?'✓':s.status==='active'?'⟳':(i+1)}</div>
        <div class="aw-pipeline-label">${esc(s.label)}</div>
      </div>`
    ).join('')}</div></div></div>`;
}

function renderPricingCard(c) {
  return `<div class="aw-card"><div class="aw-card-header"><i class="fa-solid fa-tag"></i> ${esc(c.title||'Pricing')}</div>
    <div class="aw-card-body"><table style="width:100%;font-size:0.78rem;border-collapse:collapse">
    ${(c.rows||[]).map(r => `<tr><td style="padding:4px 0;color:var(--aw-text)">${esc(r.label)}</td><td style="padding:4px 0;text-align:right;color:var(--aw-accent);font-weight:700">${esc(r.value)}</td></tr>`).join('')}
    </table></div></div>`;
}

/* ═══════════════════════════════════════
   MODEL SELECTOR (Grouped + Cost + Comparison)
   ═══════════════════════════════════════ */
function buildModelDropdown() {
  // Check if current agent has a locked model
  const agentId = state.agent?.id;
  const lockedModel = agentId ? state.agentModelLocks[agentId] : null;
  const effectiveModel = lockedModel || state.selectedModel;
  const m = AI_MODELS[effectiveModel] || AI_MODELS['auto'];
  
  DOM.modelIcon.textContent = m.icon;
  DOM.modelLabel.textContent = lockedModel ? `🔒 ${m.label}` : m.label;

  // Build grouped dropdown
  let html = `<div class="aw-model-dropdown-header">
    <span style="font-weight:700;font-size:0.68rem">Select Model</span>
    <div class="aw-model-dropdown-actions">
      <button class="aw-model-action-btn${state.comparisonMode?' active':''}" id="awModelCompareToggle" title="A/B Compare">⚖️</button>
      <button class="aw-model-action-btn" id="awModelLockBtn" title="Lock model for ${state.agent?.name||'this agent'}">🔒</button>
      <button class="aw-model-action-btn" id="awSaveWorkflowBtn" title="Save current setup as workflow">💾</button>
    </div>
  </div>`;

  if (state.comparisonMode) {
    html += `<div class="aw-model-compare-banner">
      <span>⚖️ <strong>A/B Compare Mode</strong></span>
      <span class="aw-muted">Pick Model B — same prompt goes to both</span>
    </div>`;
  }

  html += MODEL_GROUPS.map(group => {
    const models = group.models.filter(k => AI_MODELS[k]);
    if (!models.length) return '';
    return `<div class="aw-model-group">
      <div class="aw-model-group-label">${group.label}</div>
      ${models.map(key => {
        const mdl = AI_MODELS[key];
        const isSelected = key === effectiveModel;
        const isCompareB = state.comparisonMode && key === state.comparisonModel;
        const tier = TIER_BADGES[mdl.tier] || TIER_BADGES['free'];
        const usage = state.modelUsage[key] || 0;
        return `<div class="aw-model-option${isSelected ? ' selected' : ''}${isCompareB ? ' compare-b' : ''}" data-model="${key}">
          <span class="aw-model-option-icon">${mdl.icon}</span>
          <div class="aw-model-option-info">
            <div class="aw-model-option-name">${mdl.label}${isCompareB ? ' (B)' : ''}</div>
            <div class="aw-model-option-desc">${mdl.desc}</div>
          </div>
          <div class="aw-model-option-badges">
            <span class="aw-model-tier-badge" style="background:${tier.color}20;color:${tier.color}">${tier.label}</span>
            ${mdl.cost > 0 ? `<span class="aw-model-cost">$${mdl.cost}/M</span>` : ''}
            ${usage > 0 ? `<span class="aw-model-usage-count">${usage}×</span>` : ''}
          </div>
          ${isSelected ? '<i class="fa-solid fa-check aw-model-check"></i>' : ''}
        </div>`;
      }).join('')}
    </div>`;
  }).join('');

  // Saved workflows section
  if (state.savedWorkflows.length > 0) {
    html += `<div class="aw-model-group">
      <div class="aw-model-group-label">💾 Saved Workflows</div>
      ${state.savedWorkflows.map((w, i) => `<div class="aw-model-workflow" data-wf="${i}">
        <span>${w.name}</span>
        <span class="aw-muted" style="font-size:0.55rem">${AI_MODELS[w.model]?.icon||''} ${w.formation ? '+ ' + FLEET_FORMATIONS[w.formation]?.icon : ''}</span>
        <button class="aw-wf-delete" data-wfdel="${i}" title="Delete">&times;</button>
      </div>`).join('')}
    </div>`;
  }

  DOM.modelDropdown.innerHTML = html;

  // Bind model option clicks
  DOM.modelDropdown.querySelectorAll('.aw-model-option').forEach(opt => {
    opt.addEventListener('click', (e) => {
      e.stopPropagation();
      const key = opt.dataset.model;
      
      if (state.comparisonMode && state.selectedModel !== key) {
        // Set as comparison model B
        state.comparisonModel = key;
        state.comparisonMode = false;
        state.modelDropdownOpen = false;
        DOM.modelDropdown.classList.remove('open');
        buildModelDropdown();
        addSystemMsg(`⚖️ A/B Compare: ${AI_MODELS[state.selectedModel].icon} ${AI_MODELS[state.selectedModel].label} vs ${AI_MODELS[key].icon} ${AI_MODELS[key].label}`);
        return;
      }

      state.selectedModel = key;
      state.comparisonModel = null;
      localStorage.setItem('aw-model', key);
      // Track usage
      state.modelUsage[key] = (state.modelUsage[key] || 0) + 1;
      localStorage.setItem('aw-model-usage', JSON.stringify(state.modelUsage));
      state.modelDropdownOpen = false;
      DOM.modelDropdown.classList.remove('open');
      buildModelDropdown();
      addSystemMsg(`Model switched to ${AI_MODELS[key].icon} ${AI_MODELS[key].label}`);
    });
  });

  // Bind compare toggle
  const compareBtn = document.getElementById('awModelCompareToggle');
  if (compareBtn) {
    compareBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      state.comparisonMode = !state.comparisonMode;
      if (!state.comparisonMode) state.comparisonModel = null;
      buildModelDropdown();
      if (state.comparisonMode) {
        addSystemMsg('⚖️ A/B Compare Mode ON — select Model B from the list');
      } else {
        addSystemMsg('A/B Compare Mode OFF');
      }
    });
  }

  // Bind lock button
  const lockBtn = document.getElementById('awModelLockBtn');
  if (lockBtn) {
    lockBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!agentId) return;
      if (state.agentModelLocks[agentId]) {
        delete state.agentModelLocks[agentId];
        addSystemMsg(`🔓 Model unlocked for ${state.agent?.emoji||''} ${state.agent?.name||agentId}`);
      } else {
        state.agentModelLocks[agentId] = state.selectedModel;
        addSystemMsg(`🔒 ${AI_MODELS[state.selectedModel].icon} ${AI_MODELS[state.selectedModel].label} locked for ${state.agent?.emoji||''} ${state.agent?.name||agentId}`);
      }
      localStorage.setItem('aw-agent-model-locks', JSON.stringify(state.agentModelLocks));
      buildModelDropdown();
    });
  }

  // Bind save workflow
  const saveWfBtn = document.getElementById('awSaveWorkflowBtn');
  if (saveWfBtn) {
    saveWfBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      showSaveWorkflowDialog();
    });
  }

  // Bind workflow loading
  DOM.modelDropdown.querySelectorAll('.aw-model-workflow').forEach(wf => {
    wf.addEventListener('click', (e) => {
      if (e.target.classList.contains('aw-wf-delete')) return;
      const idx = parseInt(wf.dataset.wf);
      loadWorkflow(idx);
      state.modelDropdownOpen = false;
      DOM.modelDropdown.classList.remove('open');
    });
  });

  // Bind workflow delete
  DOM.modelDropdown.querySelectorAll('.aw-wf-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const idx = parseInt(btn.dataset.wfdel);
      state.savedWorkflows.splice(idx, 1);
      localStorage.setItem('aw-saved-workflows', JSON.stringify(state.savedWorkflows));
      buildModelDropdown();
      addSystemMsg('Workflow deleted.');
    });
  });
}

/** Get the effective model for current agent (respects locks) */
function getEffectiveModel() {
  const agentId = state.agent?.id;
  return (agentId && state.agentModelLocks[agentId]) || state.selectedModel;
}

/** Show save workflow dialog */
function showSaveWorkflowDialog() {
  state.modelDropdownOpen = false;
  DOM.modelDropdown.classList.remove('open');
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  el.innerHTML = `<div class="aw-msg-sender">🎩 Alfred</div>
    <div class="aw-msg-bubble">
      <strong>💾 Save Workflow</strong><br>
      <div class="aw-muted" style="margin:4px 0">Saves: Model (${AI_MODELS[state.selectedModel]?.label}) ${state.fleet.formation ? '+ Formation (' + FLEET_FORMATIONS[state.fleet.formation]?.name + ')' : ''} ${state.fleet.agents.length ? '+ Fleet (' + state.fleet.agents.length + ' agents)' : ''}</div>
      <input class="aw-training-input" id="awWfNameInput" placeholder="Workflow name..." style="width:100%;margin:6px 0"/>
      <button class="aw-fleet-gather-opt" id="awWfSaveConfirm" style="background:var(--aw-primary)">Save Workflow</button>
    </div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
  el.querySelector('#awWfNameInput').focus();
  el.querySelector('#awWfSaveConfirm').addEventListener('click', () => {
    const name = el.querySelector('#awWfNameInput').value.trim();
    if (!name) return;
    state.savedWorkflows.push({
      name,
      model: state.selectedModel,
      formation: state.fleet.formation || null,
      agentCount: state.fleet.agents.length,
      roles: state.fleet.agents.map(a => a.role),
      pipeline: state.fleet.pipeline || null,
      created: Date.now()
    });
    localStorage.setItem('aw-saved-workflows', JSON.stringify(state.savedWorkflows));
    el.remove();
    addSystemMsg(`💾 Workflow "${name}" saved!`);
    buildModelDropdown();
  });
}

/** Load a saved workflow */
function loadWorkflow(idx) {
  const wf = state.savedWorkflows[idx];
  if (!wf) return;
  // Restore model
  state.selectedModel = wf.model;
  localStorage.setItem('aw-model', wf.model);
  buildModelDropdown();
  
  // Restore formation if any
  if (wf.formation && FLEET_FORMATIONS[wf.formation]) {
    deployFormation(wf.formation);
  } else if (wf.agentCount > 0) {
    gatherFleet(wf.agentCount);
  }
  addAlfred(`💾 Workflow **"${wf.name}"** loaded!\n\n• Model: ${AI_MODELS[wf.model]?.icon||''} ${AI_MODELS[wf.model]?.label||wf.model}\n${wf.formation ? `• Formation: ${FLEET_FORMATIONS[wf.formation]?.icon||''} ${FLEET_FORMATIONS[wf.formation]?.name||wf.formation}\n` : ''}${wf.agentCount ? `• Agents: ${wf.agentCount}\n` : ''}Ready to go!`);
}

/** A/B comparison send — sends to both models */
async function sendComparisonRequest(text) {
  const modelA = state.selectedModel;
  const modelB = state.comparisonModel;
  if (!modelB) return false;

  addSystemMsg(`⚖️ Sending to ${AI_MODELS[modelA].icon} ${AI_MODELS[modelA].label} and ${AI_MODELS[modelB].icon} ${AI_MODELS[modelB].label}...`);
  showTyping(true);

  try {
    const [respA, respB] = await Promise.all([
      fetch(CFG.chatApi, {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-Token': CFG.csrfToken, 'X-Alfred-Token': CFG.authToken},
        body: JSON.stringify({ message: text, agent: state.agent?.id || 'alfred', context: state.pageContext || '', page_url: window.location.href, conv_id: state.convId, model: modelA })
      }).then(r => r.json()),
      fetch(CFG.chatApi, {
        method: 'POST',
        headers: {'Content-Type':'application/json', 'X-CSRF-Token': CFG.csrfToken, 'X-Alfred-Token': CFG.authToken},
        body: JSON.stringify({ message: text, agent: state.agent?.id || 'alfred', context: state.pageContext || '', page_url: window.location.href, conv_id: state.convId, model: modelB })
      }).then(r => r.json())
    ]);

    showTyping(false);
    
    const combined = `⚖️ **A/B Comparison Results**\n\n` +
      `---\n**${AI_MODELS[modelA].icon} Model A: ${AI_MODELS[modelA].label}**\n\n${respA.response || respA.error || 'No response'}\n\n` +
      `---\n**${AI_MODELS[modelB].icon} Model B: ${AI_MODELS[modelB].label}**\n\n${respB.response || respB.error || 'No response'}\n\n` +
      `---\n*Which response do you prefer? This helps us improve model routing.*`;
    addAlfred(combined);
    if (respA.conv_id) state.convId = respA.conv_id;
    if (respA.csrf_token) CFG.csrfToken = respA.csrf_token;
    
    // Clear comparison after use
    state.comparisonModel = null;
    buildModelDropdown();
  } catch (e) {
    showTyping(false);
    addSystemMsg('Comparison request failed.');
  }
  return true;
}

/* ═══════════════════════════════════════
   FLEET — Agent Fleet Management
   ═══════════════════════════════════════ */
function bindFleetEvents() {
  // Gather button
  DOM.fleetGather.addEventListener('click', () => {
    showFleetGatherDialog();
  });

  // Dismiss all
  DOM.fleetDismiss.addEventListener('click', () => {
    dismissFleet();
  });

  // Preset buttons
  document.querySelectorAll('.aw-fleet-preset').forEach(btn => {
    btn.addEventListener('click', () => {
      const count = parseInt(btn.dataset.count);
      const preset = btn.dataset.task;
      gatherFleet(count, preset);
    });
  });

  // Fleet sub-navigation
  document.querySelectorAll('.aw-fleet-subnav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.aw-fleet-subnav-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const view = btn.dataset.subview;
      document.querySelectorAll('.aw-fleet-subview').forEach(v => v.classList.remove('active'));
      document.querySelector(`[data-fleetview=\"${view}\"]`)?.classList.add('active');
      if (view === 'metrics') renderMetrics();
      if (view === 'missions') renderMissionBoard();
      if (view === 'pipeline') renderPipelineVisual();
      if (view === 'training') buildTrainingAgentSelect();
    });
  });

  // Mission controls
  DOM.missionAdd.addEventListener('click', showAddMissionDialog);
  document.querySelectorAll('.aw-mission-view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.aw-mission-view-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.fleet.missionView = btn.dataset.mview;
      renderMissionBoard();
    });
  });

  // Training save/clear
  DOM.trainSave.addEventListener('click', saveTraining);
  DOM.trainClear.addEventListener('click', clearTraining);
}

/** Detect fleet commands in user input */
function detectFleetCommand(text) {
  const lower = text.toLowerCase().trim();

  // "gather N agents" / "deploy N agents" / "spin up N agents"
  const gatherMatch = lower.match(/(?:gather|deploy|spin\s*up|launch|assemble|recruit|summon)\s+(\d+)\s*(?:agents?|workers?|bots?|helpers?)?/i);
  if (gatherMatch) {
    const count = Math.min(parseInt(gatherMatch[1]), state.fleet.maxAgents);
    gatherFleet(count, 'mixed');
    return true;
  }

  // Formation commands: "deploy code review squad" / "use full stack team"
  for (const [key, f] of Object.entries(FLEET_FORMATIONS)) {
    const nameWords = f.name.toLowerCase().replace(/[^a-z\s]/g,'');
    if (lower.includes(nameWords) || lower.includes(key.replace(/-/g,' '))) {
      deployFormation(key);
      return true;
    }
  }

  // "deploy formation X" generic
  const formationMatch = lower.match(/(?:deploy|use|activate)\s+(?:formation|squad|team)\s+(.+)/i);
  if (formationMatch) {
    const name = formationMatch[1].trim();
    const found = Object.entries(FLEET_FORMATIONS).find(([k,f]) => 
      f.name.toLowerCase().includes(name) || k.includes(name.replace(/\s/g,'-'))
    );
    if (found) { deployFormation(found[0]); return true; }
  }

  // Pipeline commands: "start pipeline waterfall" / "run review chain"
  const pipeMatch = lower.match(/(?:start|run|execute|activate)\s+(?:pipeline\s+)?(\w[\w\s-]*?)(?:\s+pipeline)?$/i);
  if (pipeMatch) {
    const pipeName = pipeMatch[1].trim().replace(/\s+/g,'-');
    if (PIPELINE_TEMPLATES[pipeName]) { startPipeline(pipeName); return true; }
    const found = Object.entries(PIPELINE_TEMPLATES).find(([k,p]) => 
      p.name.toLowerCase().includes(pipeMatch[1].trim().toLowerCase())
    );
    if (found) { startPipeline(found[0]); return true; }
  }

  // Mission commands: "create mission: ..." / "add task: ..."
  const missionMatch = lower.match(/(?:create|add|new)\s+(?:mission|task|objective)[:\s]+(.+)/i);
  if (missionMatch) {
    addMission(missionMatch[1].trim());
    return true;
  }

  // Training commands: "train alfred to ..."
  const trainMatch = lower.match(/train\s+(\w+)\s+(?:to|for|about|on)\s+(.+)/i);
  if (trainMatch) {
    const agentName = trainMatch[1].trim().toLowerCase();
    const instruction = trainMatch[2].trim();
    const agent = findAgent(agentName);
    if (agent) {
      quickTrain(agent.id, instruction);
      return true;
    }
  }

  // "let's begin" / "start the fleet" / "begin work"
  if (/(?:let'?s\s+begin|start\s+(?:the\s+)?fleet|begin\s+work|fleet\s+go)/i.test(lower)) {
    if (state.fleet.agents.length > 0) {
      startFleetWork();
      return true;
    }
  }

  // "dismiss fleet" / "stop agents" / "stand down"
  if (/(?:dismiss|stop|disband|stand\s*down|halt)\s*(?:all\s*)?(?:agents?|fleet|workers?)?/i.test(lower)) {
    if (state.fleet.agents.length > 0) {
      dismissFleet();
      return true;
    }
  }

  // "fleet status" / "show agents" / "fleet metrics"
  if (/(?:fleet|agent|team)\s*(?:status|report|overview)/i.test(lower)) {
    showFleetStatus();
    return true;
  }

  if (/(?:fleet|agent)\s*metrics/i.test(lower)) {
    showFleetMetricsInChat();
    return true;
  }

  // "show formations" / "what formations"
  if (/(?:show|list|what)\s*(?:are\s*)?(?:the\s*)?formations?/i.test(lower)) {
    showFormationsInChat();
    return true;
  }

  return false;
}

/** Gather a fleet of agents */
function gatherFleet(count, preset) {
  count = Math.min(count, state.fleet.maxAgents);
  addSystemMsg(`🪖 Gathering ${count} agents...`);

  const roleDistribution = getFleetDistribution(count, preset);
  const agentPool = getAllAgents();

  state.fleet.agents = [];
  roleDistribution.forEach((role, i) => {
    const agentDef = agentPool[i % agentPool.length];
    const fleetAgent = {
      id: `fleet_${Date.now()}_${i}`,
      role: role,
      agent: agentDef,
      task: null,
      status: 'standby',  // standby | working | completed | error
      result: null,
      startTime: null,
      progress: 0
    };
    state.fleet.agents.push(fleetAgent);
  });

  state.fleet.active = true;
  renderFleetRoster();
  updateFleetIndicator();

  // Show fleet tab
  switchTab('fleet');

  const roleNames = roleDistribution.map(r => FLEET_ROLES[r]?.label || r);
  const roleSummary = {};
  roleNames.forEach(r => roleSummary[r] = (roleSummary[r]||0)+1);
  const summary = Object.entries(roleSummary).map(([k,v]) => `${v}x ${k}`).join(', ');

  addAlfred(`🪖 **Fleet Assembled!** ${count} agents ready for duty.\n\n**Roster:** ${summary}\n\nYou can:\n• **Assign a task** — just type what you need done\n• **"Let's begin"** — start the fleet on your task\n• **"Fleet status"** — check progress\n• **"Dismiss fleet"** — stand down all agents\n\nThink of me as your call center supervisor. I'll coordinate the team. What shall we work on?`);

  DOM.fleetDismiss.style.display = '';
}

function getFleetDistribution(count, preset) {
  switch(preset) {
    case 'research':
      return Array(count).fill('researcher');
    case 'fullteam':
      return [
        'researcher','researcher','researcher',
        'analyzer','analyzer',
        'worker','worker',
        'reviewer',
        'tester',
        'documenter'
      ].slice(0, count);
    case 'mixed':
    default:
      const roles = [];
      for (let i = 0; i < count; i++) {
        if (i < Math.ceil(count * 0.3)) roles.push('researcher');
        else if (i < Math.ceil(count * 0.5)) roles.push('analyzer');
        else if (i < Math.ceil(count * 0.7)) roles.push('worker');
        else if (i < Math.ceil(count * 0.85)) roles.push('reviewer');
        else roles.push('tester');
      }
      return roles;
  }
}

/** Dismiss the entire fleet */
function dismissFleet() {
  state.fleet.agents = [];
  state.fleet.active = false;
  if (state.fleet.pollInterval) {
    clearInterval(state.fleet.pollInterval);
    state.fleet.pollInterval = null;
  }
  renderFleetRoster();
  updateFleetIndicator();
  DOM.fleetDismiss.style.display = 'none';
  addSystemMsg('Fleet dismissed. All agents stood down.');
}

/** Show fleet gather dialog */
function showFleetGatherDialog() {
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  el.innerHTML = `<div class="aw-msg-sender">🎩 Alfred</div>
    <div class="aw-msg-bubble">
      <strong>How many agents do you need?</strong><br><br>
      <div class="aw-fleet-gather-options">
        <button class="aw-fleet-gather-opt" data-n="3">3 agents</button>
        <button class="aw-fleet-gather-opt" data-n="5">5 agents</button>
        <button class="aw-fleet-gather-opt" data-n="8">8 agents</button>
        <button class="aw-fleet-gather-opt" data-n="10">10 agents</button>
      </div>
      <div style="margin-top:8px;font-size:0.75rem;color:var(--aw-muted);">Or type "gather N agents" in the chat.</div>
    </div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();

  el.querySelectorAll('.aw-fleet-gather-opt').forEach(btn => {
    btn.addEventListener('click', () => {
      el.remove();
      gatherFleet(parseInt(btn.dataset.n), 'mixed');
    });
  });
}

/** Start fleet work on a task */
function startFleetWork(task) {
  if (!state.fleet.agents.length) {
    addSystemMsg('No agents in fleet. Gather agents first.');
    return;
  }

  const taskText = task || 'Awaiting task assignment...';
  state.fleet.agents.forEach(a => {
    a.status = 'working';
    a.task = taskText;
    a.startTime = Date.now();
    a.progress = 0;
  });

  renderFleetRoster();
  updateFleetIndicator();
  simulateFleetProgress();

  addSystemMsg(`🚀 Fleet deployed! ${state.fleet.agents.length} agents working on task.`);
}

/** Assign task to fleet via chat */
function assignFleetTask(text) {
  if (state.fleet.active && state.fleet.agents.some(a => a.status === 'standby')) {
    startFleetWork(text);
    // Send fleet request to backend
    sendFleetRequest(text);
    return true;
  }
  return false;
}

/** Send fleet request to backend */
async function sendFleetRequest(task) {
  showTyping(true);
  try {
    // Collect training data for agents in the fleet
    const agentTraining = {};
    state.fleet.agents.forEach(a => {
      const t = state.fleet.trainedAgents[a.agent.id];
      if (t) agentTraining[a.agent.id] = t;
    });

    const resp = await fetch(CFG.chatApi + '?action=fleet', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CFG.csrfToken,
        'X-Alfred-Token': CFG.authToken
      },
      body: JSON.stringify({
        task: task,
        agents: state.fleet.agents.map(a => ({
          id: a.id,
          role: a.role,
          agentId: a.agent.id
        })),
        model: state.selectedModel,
        conv_id: state.convId,
        formation: state.fleet.formation || null,
        agent_training: agentTraining
      })
    });
    const data = await resp.json();
    showTyping(false);

    if (data.results) {
      data.results.forEach((r, i) => {
        if (state.fleet.agents[i]) {
          state.fleet.agents[i].status = r.status || 'completed';
          state.fleet.agents[i].result = r.result || r.text;
          state.fleet.agents[i].progress = 100;
        }
      });
      renderFleetRoster();
      updateFleetIndicator();

      // Track metrics
      const duration = state.fleet.agents.reduce((sum, a) => sum + (a.startTime ? Date.now() - a.startTime : 0), 0) / state.fleet.agents.length;
      const allSuccess = data.results.every(r => r.status === 'completed');
      trackFleetMetric(data.results.length, allSuccess, duration);
      renderMetrics();

      // Show combined fleet response
      const combined = data.results
        .filter(r => r.result || r.text)
        .map((r, i) => `**${FLEET_ROLES[state.fleet.agents[i]?.role]?.emoji || '🤖'} ${FLEET_ROLES[state.fleet.agents[i]?.role]?.label || 'Agent'} Report:**\n${r.result || r.text}`)
        .join('\n\n---\n\n');
      if (combined) addAlfred(combined);
    } else if (data.response) {
      addAlfred(data.response);
    } else if (data.error) {
      addSystemMsg('Fleet error: ' + data.error);
    }
  } catch(e) {
    showTyping(false);
    addSystemMsg('Fleet request failed. Check connection.');
  }
}

/** Simulate fleet progress (visual feedback while waiting) */
function simulateFleetProgress() {
  if (state.fleet.pollInterval) clearInterval(state.fleet.pollInterval);
  state.fleet.pollInterval = setInterval(() => {
    let allDone = true;
    state.fleet.agents.forEach(a => {
      if (a.status === 'working') {
        a.progress = Math.min(a.progress + Math.random() * 8, 95);
        allDone = false;
      }
    });
    renderFleetRoster();
    if (allDone) {
      clearInterval(state.fleet.pollInterval);
      state.fleet.pollInterval = null;
    }
  }, 1500);
}

/** Show fleet status in chat */
function showFleetStatus() {
  const total = state.fleet.agents.length;
  if (!total) {
    addAlfred('No fleet is currently active. Say **"gather 5 agents"** to deploy a team.');
    return;
  }
  const working = state.fleet.agents.filter(a => a.status === 'working').length;
  const done = state.fleet.agents.filter(a => a.status === 'completed').length;
  const standby = state.fleet.agents.filter(a => a.status === 'standby').length;
  const errors = state.fleet.agents.filter(a => a.status === 'error').length;

  let status = `📊 **Fleet Status**\n\n`;
  status += `**Total:** ${total} agents\n`;
  status += `**Standby:** ${standby} | **Working:** ${working} | **Completed:** ${done} | **Errors:** ${errors}\n\n`;
  state.fleet.agents.forEach(a => {
    const statusIcon = {standby:'⏸️', working:'🔄', completed:'✅', error:'❌'}[a.status] || '❓';
    status += `${statusIcon} ${a.agent.emoji} **${a.agent.name}** (${FLEET_ROLES[a.role]?.label||a.role}) — ${a.task || 'No task'}\n`;
  });
  addAlfred(status);
}

/** Render fleet roster in the Fleet tab */
function renderFleetRoster() {
  if (!state.fleet.agents.length) {
    if (DOM.fleetEmpty) DOM.fleetEmpty.style.display = '';
    DOM.fleetRoster.innerHTML = `<div class="aw-fleet-empty" id="awFleetEmpty">
      <div class="aw-fleet-empty-icon">🪖</div>
      <p>No agents deployed yet.</p>
      <p class="aw-muted">Say <strong>"gather 5 agents"</strong> or use the presets above.</p>
    </div>`;
    return;
  }

  DOM.fleetRoster.innerHTML = state.fleet.agents.map(a => {
    const role = FLEET_ROLES[a.role] || {label: a.role, emoji:'🤖'};
    const statusCls = a.status;
    const statusIcon = {standby:'⏸️', working:'🔄', completed:'✅', error:'❌'}[a.status] || '❓';
    const progressBar = a.status === 'working' 
      ? `<div class="aw-fleet-agent-progress"><div class="aw-fleet-agent-progress-fill" style="width:${a.progress}%"></div></div>` 
      : '';
    const elapsed = a.startTime ? `${((Date.now() - a.startTime)/1000).toFixed(0)}s` : '';
    const resultPreview = a.result ? `<div class="aw-fleet-agent-result">${esc(a.result.substring(0, 100))}${a.result.length > 100 ? '...' : ''}</div>` : '';

    return `<div class="aw-fleet-agent ${statusCls}" data-fleet-id="${a.id}">
      <div class="aw-fleet-agent-header">
        <span class="aw-fleet-agent-avatar">${a.agent.emoji}</span>
        <div class="aw-fleet-agent-info">
          <div class="aw-fleet-agent-name">${a.agent.name}</div>
          <div class="aw-fleet-agent-role">${role.emoji} ${role.label}</div>
        </div>
        <div class="aw-fleet-agent-status">
          <span>${statusIcon}</span>
          ${elapsed ? `<span class="aw-fleet-agent-time">${elapsed}</span>` : ''}
        </div>
      </div>
      ${progressBar}
      ${a.task ? `<div class="aw-fleet-agent-task">${esc(a.task.substring(0, 80))}</div>` : ''}
      ${resultPreview}
    </div>`;
  }).join('');

  // Click to expand agent result
  DOM.fleetRoster.querySelectorAll('.aw-fleet-agent').forEach(card => {
    card.addEventListener('click', () => {
      const fa = state.fleet.agents.find(a => a.id === card.dataset.fleetId);
      if (fa && fa.result) {
        addAlfred(`${fa.agent.emoji} **${fa.agent.name}** (${FLEET_ROLES[fa.role]?.label}) report:\n\n${fa.result}`);
        switchTab('chat');
      }
    });
  });
}

/** Update the fleet indicator in the input area */
function updateFleetIndicator() {
  const active = state.fleet.agents.filter(a => a.status === 'working' || a.status === 'standby').length;
  if (active > 0 || state.fleet.agents.length > 0) {
    DOM.fleetIndicator.style.display = '';
    DOM.fleetCount.textContent = state.fleet.agents.length;
    DOM.fleetIndicator.className = 'aw-fleet-indicator' + (state.fleet.agents.some(a => a.status === 'working') ? ' working' : '');
  } else {
    DOM.fleetIndicator.style.display = 'none';
  }
}

/* ═══════════════════════════════════════
   FORMATIONS — Pre-built Team Templates
   ═══════════════════════════════════════ */
function buildFormationsGrid() {
  DOM.formationsGrid.innerHTML = Object.entries(FLEET_FORMATIONS).map(([key, f]) => {
    const roleList = f.roles.map(r => `${FLEET_ROLES[r]?.emoji||'🤖'}`).join(' ');
    return `<div class="aw-formation-card" data-formation="${key}" style="border-color:${f.color}30">
      <div class="aw-formation-icon" style="background:${f.color}20;color:${f.color}">${f.icon}</div>
      <div class="aw-formation-info">
        <div class="aw-formation-name">${f.name}</div>
        <div class="aw-formation-desc">${f.desc}</div>
        <div class="aw-formation-roles">${roleList} <span class="aw-muted">${f.roles.length} agents</span></div>
      </div>
      <button class="aw-formation-deploy" style="background:${f.color}">Deploy</button>
    </div>`;
  }).join('');

  DOM.formationsGrid.querySelectorAll('.aw-formation-card').forEach(card => {
    card.addEventListener('click', () => deployFormation(card.dataset.formation));
  });
}

function deployFormation(key) {
  const f = FLEET_FORMATIONS[key];
  if (!f) return;
  state.fleet.formation = key;
  gatherFleet(f.roles.length, key);
  addAlfred(`${f.icon} **${f.name}** deployed!\n\n${f.desc}\n\n**Team:** ${f.roles.map(r => `${FLEET_ROLES[r]?.emoji||'🤖'} ${FLEET_ROLES[r]?.label||r}`).join(', ')}\n\nGive me a task and I'll coordinate the team.`);
}

function showFormationsInChat() {
  let msg = `🏗️ **Available Formations:**\n\n`;
  Object.entries(FLEET_FORMATIONS).forEach(([key, f]) => {
    msg += `${f.icon} **${f.name}** — ${f.desc} (${f.roles.length} agents)\n`;
  });
  msg += `\nSay **"deploy [formation name]"** to activate one.`;
  addAlfred(msg);
}

/* ═══════════════════════════════════════
   MISSIONS — Kanban Board
   ═══════════════════════════════════════ */
function addMission(title, priority) {
  const mission = {
    id: 'mission_' + Date.now(),
    title: title,
    status: 'todo',    // todo | active | done
    assignee: null,
    priority: priority || 'medium', // low | medium | high | critical
    created: Date.now(),
    completed: null,
    result: null
  };
  state.fleet.missions.push(mission);
  renderMissionBoard();
  addSystemMsg(`📋 Mission added: "${title}"`);

  // Auto-assign if fleet has standby agents
  const standby = state.fleet.agents.find(a => a.status === 'standby');
  if (standby) {
    mission.assignee = standby.id;
    mission.status = 'active';
    standby.task = title;
    standby.status = 'working';
    standby.startTime = Date.now();
    renderFleetRoster();
    renderMissionBoard();
    addSystemMsg(`🤖 Auto-assigned to ${standby.agent.emoji} ${standby.agent.name}`);
    sendFleetRequest(title);
  }
}

function renderMissionBoard() {
  const missions = state.fleet.missions;
  const todo = missions.filter(m => m.status === 'todo');
  const active = missions.filter(m => m.status === 'active');
  const done = missions.filter(m => m.status === 'done');

  const renderCard = (m) => {
    const pColor = {low:'#22c55e',medium:'#f59e0b',high:'#ef4444',critical:'#dc2626'}[m.priority] || '#f59e0b';
    const assignee = m.assignee ? state.fleet.agents.find(a => a.id === m.assignee) : null;
    return `<div class="aw-mission-card" data-mid="${m.id}" draggable="true">
      <div class="aw-mission-card-priority" style="background:${pColor}"></div>
      <div class="aw-mission-card-title">${esc(m.title)}</div>
      ${assignee ? `<div class="aw-mission-card-assignee">${assignee.agent.emoji} ${assignee.agent.name}</div>` : ''}
      <div class="aw-mission-card-meta">${new Date(m.created).toLocaleDateString()}</div>
    </div>`;
  };

  document.getElementById('awMcTodo').textContent = todo.length;
  document.getElementById('awMcActive').textContent = active.length;
  document.getElementById('awMcDone').textContent = done.length;

  document.getElementById('awMcTodoBody').innerHTML = todo.map(renderCard).join('') || '<div class="aw-muted" style="text-align:center;padding:12px;font-size:0.7rem">No tasks</div>';
  document.getElementById('awMcActiveBody').innerHTML = active.map(renderCard).join('') || '<div class="aw-muted" style="text-align:center;padding:12px;font-size:0.7rem">No active</div>';
  document.getElementById('awMcDoneBody').innerHTML = done.map(renderCard).join('') || '<div class="aw-muted" style="text-align:center;padding:12px;font-size:0.7rem">None done</div>';

  // Click to move mission forward
  DOM.missionBoard.querySelectorAll('.aw-mission-card').forEach(card => {
    card.addEventListener('click', () => {
      const m = state.fleet.missions.find(x => x.id === card.dataset.mid);
      if (!m) return;
      if (m.status === 'todo') { m.status = 'active'; }
      else if (m.status === 'active') { m.status = 'done'; m.completed = Date.now(); }
      renderMissionBoard();
    });
  });
}

function showAddMissionDialog() {
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  el.innerHTML = `<div class="aw-msg-sender">🎩 Alfred</div>
    <div class="aw-msg-bubble">
      <strong>New Mission</strong><br><br>
      <input class="aw-training-input" id="awNewMissionInput" placeholder="What needs to be done?" style="width:100%;margin-bottom:8px" />
      <div style="display:flex;gap:6px">
        <button class="aw-fleet-gather-opt" data-p="low" style="background:#22c55e">Low</button>
        <button class="aw-fleet-gather-opt" data-p="medium" style="background:#f59e0b">Medium</button>
        <button class="aw-fleet-gather-opt" data-p="high" style="background:#ef4444">High</button>
        <button class="aw-fleet-gather-opt" data-p="critical" style="background:#dc2626">Critical</button>
      </div>
    </div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
  el.querySelector('#awNewMissionInput').focus();

  el.querySelectorAll('.aw-fleet-gather-opt').forEach(btn => {
    btn.addEventListener('click', () => {
      const title = el.querySelector('#awNewMissionInput').value.trim();
      if (!title) return;
      el.remove();
      addMission(title, btn.dataset.p);
    });
  });
}

/* ═══════════════════════════════════════
   PIPELINES — Sequential Agent Chains
   ═══════════════════════════════════════ */
function buildPipelineTemplates() {
  DOM.pipelineTemplates.innerHTML = `<div class="aw-pipeline-tmpl-header">
    <h4><i class="fa-solid fa-diagram-project"></i> Pipeline Templates</h4>
    <p class="aw-muted">Chain agents in sequence — each stage feeds into the next.</p>
  </div>` + Object.entries(PIPELINE_TEMPLATES).map(([key, p]) => {
    const stageIcons = p.roles.map(r => FLEET_ROLES[r]?.emoji || '🤖');
    return `<div class="aw-pipeline-tmpl-card" data-pipe="${key}">
      <div class="aw-pipeline-tmpl-icon">${p.icon}</div>
      <div class="aw-pipeline-tmpl-info">
        <div class="aw-pipeline-tmpl-name">${p.name}</div>
        <div class="aw-pipeline-tmpl-stages">${p.stages.map((s,i) => `${stageIcons[i]} ${s}`).join(' → ')}</div>
      </div>
      <button class="aw-formation-deploy" style="background:var(--aw-primary)">Start</button>
    </div>`;
  }).join('');

  DOM.pipelineTemplates.querySelectorAll('.aw-pipeline-tmpl-card').forEach(card => {
    card.addEventListener('click', () => {
      const key = card.dataset.pipe;
      showPipelineTaskInput(key);
    });
  });
}

function showPipelineTaskInput(pipeKey) {
  const p = PIPELINE_TEMPLATES[pipeKey];
  if (!p) return;
  const el = document.createElement('div');
  el.className = 'aw-msg alfred';
  el.innerHTML = `<div class="aw-msg-sender">🎩 Alfred</div>
    <div class="aw-msg-bubble">
      <strong>${p.icon} ${p.name} Pipeline</strong><br>
      <div class="aw-muted" style="margin:4px 0">${p.stages.join(' → ')}</div><br>
      <input class="aw-training-input" id="awPipeTaskInput" placeholder="What should this pipeline work on?" style="width:100%;margin-bottom:8px" />
      <button class="aw-fleet-gather-opt" id="awPipeStartBtn">🚀 Start Pipeline</button>
    </div>`;
  DOM.messages.appendChild(el);
  scrollToBottom();
  el.querySelector('#awPipeTaskInput').focus();
  el.querySelector('#awPipeStartBtn').addEventListener('click', () => {
    const task = el.querySelector('#awPipeTaskInput').value.trim();
    if (!task) return;
    el.remove();
    startPipeline(pipeKey, task);
  });
}

function startPipeline(key, task) {
  const p = PIPELINE_TEMPLATES[key];
  if (!p) return;
  
  state.fleet.pipeline = key;
  state.fleet.pipelineStage = 0;

  // Build fleet from pipeline roles
  const agentPool = getAllAgents();
  state.fleet.agents = p.roles.map((role, i) => ({
    id: `pipe_${Date.now()}_${i}`,
    role: role,
    agent: agentPool[i % agentPool.length],
    task: task ? `[Stage ${i+1}: ${p.stages[i]}] ${task}` : `Stage ${i+1}: ${p.stages[i]}`,
    status: i === 0 ? 'working' : 'standby',
    result: null,
    startTime: i === 0 ? Date.now() : null,
    progress: 0,
    pipelineStage: i
  }));
  state.fleet.active = true;

  renderFleetRoster();
  renderPipelineVisual();
  updateFleetIndicator();
  switchTab('fleet');
  // Switch to pipeline subview
  document.querySelectorAll('.aw-fleet-subnav-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('[data-subview="pipeline"]')?.classList.add('active');
  document.querySelectorAll('.aw-fleet-subview').forEach(v => v.classList.remove('active'));
  document.querySelector('[data-fleetview="pipeline"]')?.classList.add('active');

  addAlfred(`${p.icon} **${p.name} Pipeline** started!\n\n**Stages:** ${p.stages.map((s,i) => `${i===0?'▶':'○'} ${s}`).join(' → ')}\n\n${task ? `**Task:** ${task}\n\n` : ''}Stage 1 (${p.stages[0]}) is now running. Each stage's output feeds into the next.`);

  if (task) {
    sendPipelineStage(0, task);
  }
}

async function sendPipelineStage(stageIdx, task) {
  const p = PIPELINE_TEMPLATES[state.fleet.pipeline];
  if (!p || stageIdx >= p.stages.length) {
    addAlfred(`✅ **Pipeline Complete!** All ${p?.stages.length||0} stages finished.`);
    state.fleet.pipeline = null;
    return;
  }

  const agent = state.fleet.agents[stageIdx];
  if (!agent) return;
  agent.status = 'working';
  agent.startTime = Date.now();
  renderPipelineVisual();
  simulateFleetProgress();

  try {
    const previousResults = state.fleet.agents
      .slice(0, stageIdx)
      .filter(a => a.result)
      .map(a => `[${FLEET_ROLES[a.role]?.label}]: ${a.result}`)
      .join('\n\n');

    const resp = await fetch(CFG.chatApi + '?action=fleet', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': CFG.csrfToken,
        'X-Alfred-Token': CFG.authToken
      },
      body: JSON.stringify({
        task: task + (previousResults ? `\n\n--- Previous stage results ---\n${previousResults}` : ''),
        agents: [{ id: agent.id, role: agent.role, agentId: agent.agent.id }],
        model: state.selectedModel,
        conv_id: state.convId,
        pipeline_stage: stageIdx,
        pipeline_name: p.name
      })
    });
    const data = await resp.json();

    if (data.results && data.results[0]) {
      agent.status = 'completed';
      agent.result = data.results[0].result || data.results[0].text;
      agent.progress = 100;
    } else {
      agent.status = 'error';
      agent.result = data.error || 'Stage failed';
    }
  } catch(e) {
    agent.status = 'error';
    agent.result = 'Network error';
  }

  state.fleet.pipelineStage = stageIdx + 1;
  renderPipelineVisual();
  renderFleetRoster();

  addSystemMsg(`${agent.status === 'completed' ? '✅' : '❌'} Stage ${stageIdx+1} (${p.stages[stageIdx]}) ${agent.status}.`);

  // Track metrics
  trackFleetMetric(1, agent.status === 'completed', Date.now() - agent.startTime);

  // Continue to next stage
  if (agent.status === 'completed' && stageIdx + 1 < p.stages.length) {
    setTimeout(() => sendPipelineStage(stageIdx + 1, task), 500);
  } else if (stageIdx + 1 >= p.stages.length) {
    addAlfred(`✅ **${p.name} Pipeline Complete!**\n\nAll ${p.stages.length} stages finished successfully.`);
    state.fleet.pipeline = null;
  }
}

function renderPipelineVisual() {
  const pipe = state.fleet.pipeline ? PIPELINE_TEMPLATES[state.fleet.pipeline] : null;
  if (!pipe) {
    DOM.pipelineVisual.innerHTML = '<div class="aw-muted" style="text-align:center;padding:20px;font-size:0.75rem">No pipeline running. Select a template above to start.</div>';
    return;
  }
  DOM.pipelineVisual.innerHTML = `<div class="aw-pipeline-vis-title">${pipe.icon} ${pipe.name}</div>
    <div class="aw-pipeline-vis-stages">${pipe.stages.map((s, i) => {
      const agent = state.fleet.agents[i];
      const status = agent?.status || 'standby';
      const statusCls = status;
      const dot = {standby:'○', working:'◉', completed:'✓', error:'✗'}[status] || '○';
      return `<div class="aw-pipeline-vis-stage ${statusCls}">
        <div class="aw-pipeline-vis-dot">${dot}</div>
        <div class="aw-pipeline-vis-label">${s}</div>
        <div class="aw-pipeline-vis-agent">${agent?.agent?.emoji||''} ${FLEET_ROLES[agent?.role]?.label||''}</div>
        ${status === 'working' ? '<div class="aw-pipeline-vis-spinner"></div>' : ''}
      </div>${i < pipe.stages.length - 1 ? '<div class="aw-pipeline-vis-arrow">→</div>' : ''}`;
    }).join('')}</div>`;
}

/* ═══════════════════════════════════════
   TRAINING — Agent Customization
   ═══════════════════════════════════════ */
function buildTrainingAgentSelect() {
  const agents = getAllAgents();
  DOM.trainingAgentSelect.innerHTML = agents.map(a => {
    const trained = state.fleet.trainedAgents[a.id];
    return `<div class="aw-training-agent-card${trained ? ' trained' : ''}" data-train-agent="${a.id}">
      <span class="aw-training-agent-emoji">${a.emoji}</span>
      <span class="aw-training-agent-name">${a.name}</span>
      ${trained ? '<span class="aw-training-badge">✓ Trained</span>' : ''}
    </div>`;
  }).join('');

  DOM.trainingAgentSelect.querySelectorAll('.aw-training-agent-card').forEach(card => {
    card.addEventListener('click', () => {
      const id = card.dataset.trainAgent;
      selectAgentForTraining(id);
    });
  });
}

function selectAgentForTraining(agentId) {
  const agent = findAgent(agentId);
  if (!agent) return;
  state._trainingAgent = agentId;
  DOM.trainingForm.style.display = '';
  DOM.trainingSelected.innerHTML = `<span style="font-size:1.5rem">${agent.emoji}</span> <strong>${agent.name}</strong> — ${agent.role}`;

  // Load existing training
  const existing = state.fleet.trainedAgents[agentId];
  if (existing) {
    DOM.trainingInstr.value = existing.instructions || '';
    DOM.trainingExpertise.value = (existing.expertise || []).join(', ');
    DOM.trainingPersonality.value = existing.personality || 'default';
  } else {
    DOM.trainingInstr.value = '';
    DOM.trainingExpertise.value = '';
    DOM.trainingPersonality.value = 'default';
  }
}

function saveTraining() {
  const agentId = state._trainingAgent;
  if (!agentId) return;
  const training = {
    instructions: DOM.trainingInstr.value.trim(),
    expertise: DOM.trainingExpertise.value.split(',').map(s => s.trim()).filter(Boolean),
    personality: DOM.trainingPersonality.value,
    trained_at: Date.now()
  };
  state.fleet.trainedAgents[agentId] = training;
  localStorage.setItem('aw-trained-agents', JSON.stringify(state.fleet.trainedAgents));
  buildTrainingAgentSelect();
  const agent = findAgent(agentId);
  addSystemMsg(`🎓 ${agent?.emoji||''} ${agent?.name||agentId} training saved!`);
}

function clearTraining() {
  const agentId = state._trainingAgent;
  if (!agentId) return;
  delete state.fleet.trainedAgents[agentId];
  localStorage.setItem('aw-trained-agents', JSON.stringify(state.fleet.trainedAgents));
  DOM.trainingInstr.value = '';
  DOM.trainingExpertise.value = '';
  DOM.trainingPersonality.value = 'default';
  buildTrainingAgentSelect();
  addSystemMsg(`Training cleared for ${agentId}.`);
}

function quickTrain(agentId, instruction) {
  const existing = state.fleet.trainedAgents[agentId] || { expertise: [], personality: 'default' };
  existing.instructions = (existing.instructions || '') + (existing.instructions ? '\n' : '') + instruction;
  existing.trained_at = Date.now();
  state.fleet.trainedAgents[agentId] = existing;
  localStorage.setItem('aw-trained-agents', JSON.stringify(state.fleet.trainedAgents));
  const agent = findAgent(agentId);
  addAlfred(`🎓 Training updated for ${agent?.emoji||''} **${agent?.name||agentId}**\n\nNew instruction: "${instruction}"\n\nThis agent will now follow these custom instructions in all future conversations.`);
}

/** Get training prompt for an agent (used when sending to API) */
function getTrainingPrompt(agentId) {
  const t = state.fleet.trainedAgents[agentId];
  if (!t) return '';
  let prompt = '';
  if (t.instructions) prompt += `\n\nCustom Instructions: ${t.instructions}`;
  if (t.expertise?.length) prompt += `\nExpertise areas: ${t.expertise.join(', ')}`;
  if (t.personality && t.personality !== 'default') {
    const styles = {
      formal: 'Respond in a formal, professional tone.',
      casual: 'Be casual and friendly.',
      concise: 'Be extremely concise. Use bullet points.',
      detailed: 'Provide thorough, detailed explanations.',
      socratic: 'Guide through questions rather than giving direct answers.',
      mentor: 'Act as a patient mentor/teacher.',
      exec: 'Provide executive summaries. Lead with the conclusion.'
    };
    prompt += `\nStyle: ${styles[t.personality] || ''}`;
  }
  return prompt;
}

/* ═══════════════════════════════════════
   METRICS — Fleet Performance Dashboard
   ═══════════════════════════════════════ */
function trackFleetMetric(agents, success, duration) {
  state.fleet.metrics.totalTasks++;
  if (success) state.fleet.metrics.completed++;
  else state.fleet.metrics.errors++;
  state.fleet.metrics.avgResponseTime = 
    ((state.fleet.metrics.avgResponseTime * (state.fleet.metrics.totalTasks - 1)) + duration) / state.fleet.metrics.totalTasks;
  
  const effectiveModel = getEffectiveModel();
  state.fleet.metrics.history.push({
    timestamp: Date.now(),
    agents: agents,
    duration: duration,
    success: success,
    model: effectiveModel,
    formation: state.fleet.formation || null,
    pipeline: state.fleet.pipeline || null
  });
  // Keep last 100
  if (state.fleet.metrics.history.length > 100) state.fleet.metrics.history.shift();
  
  // Track model usage
  state.modelUsage[effectiveModel] = (state.modelUsage[effectiveModel] || 0) + 1;
  localStorage.setItem('aw-model-usage', JSON.stringify(state.modelUsage));
}

function renderMetrics() {
  const m = state.fleet.metrics;
  const successRate = m.totalTasks > 0 ? ((m.completed / m.totalTasks) * 100).toFixed(0) : 0;
  const avgTime = m.avgResponseTime > 0 ? (m.avgResponseTime / 1000).toFixed(1) : '0';
  
  // Estimate total cost from history
  const totalCost = m.history.reduce((sum, h) => {
    const mdl = AI_MODELS[h.model];
    return sum + (mdl?.cost || 0) * 0.001; // rough per-request cost estimate
  }, 0);

  DOM.metricsCards.innerHTML = `
    <div class="aw-metric-card">
      <div class="aw-metric-value">${m.totalTasks}</div>
      <div class="aw-metric-label">Total Tasks</div>
    </div>
    <div class="aw-metric-card success">
      <div class="aw-metric-value">${successRate}%</div>
      <div class="aw-metric-label">Success Rate</div>
    </div>
    <div class="aw-metric-card">
      <div class="aw-metric-value">${avgTime}s</div>
      <div class="aw-metric-label">Avg Response</div>
    </div>
    <div class="aw-metric-card${m.errors > 0 ? ' error' : ''}">
      <div class="aw-metric-value">${m.errors}</div>
      <div class="aw-metric-label">Errors</div>
    </div>
    <div class="aw-metric-card">
      <div class="aw-metric-value">$${totalCost.toFixed(3)}</div>
      <div class="aw-metric-label">Est. Cost</div>
    </div>
    <div class="aw-metric-card">
      <div class="aw-metric-value">${Object.keys(state.modelUsage).length}</div>
      <div class="aw-metric-label">Models Used</div>
    </div>
  `;

  // Mini bar chart of recent tasks
  const recent = m.history.slice(-20);
  if (recent.length > 0) {
    const maxDur = Math.max(...recent.map(h => h.duration), 1);
    DOM.metricsChart.innerHTML = `<div class="aw-metrics-chart-title">Recent Tasks</div>
      <div class="aw-metrics-bars">${recent.map(h => {
        const pct = (h.duration / maxDur * 100).toFixed(0);
        const color = h.success ? 'var(--aw-accent)' : '#ef4444';
        const mdlIcon = AI_MODELS[h.model]?.icon || '🤖';
        return `<div class="aw-metrics-bar" style="height:${pct}%;background:${color}" title="${mdlIcon} ${(h.duration/1000).toFixed(1)}s"></div>`;
      }).join('')}</div>`;
  } else {
    DOM.metricsChart.innerHTML = '<div class="aw-muted" style="text-align:center;padding:16px;font-size:0.72rem">No task data yet. Deploy a fleet and run tasks to see metrics.</div>';
  }

  // Model usage breakdown
  const usageEntries = Object.entries(state.modelUsage).sort((a,b) => b[1]-a[1]).slice(0, 8);
  let modelBreakdownHtml = '';
  if (usageEntries.length > 0) {
    const maxUsage = Math.max(...usageEntries.map(e => e[1]), 1);
    modelBreakdownHtml = `<div class="aw-metrics-model-breakdown">
      <div class="aw-metrics-chart-title">Model Usage</div>
      ${usageEntries.map(([key, count]) => {
        const mdl = AI_MODELS[key];
        const pct = (count / maxUsage * 100).toFixed(0);
        return `<div class="aw-metrics-model-row">
          <span class="aw-metrics-model-name">${mdl?.icon||'🤖'} ${mdl?.label||key}</span>
          <div class="aw-metrics-model-bar-wrap">
            <div class="aw-metrics-model-bar" style="width:${pct}%"></div>
          </div>
          <span class="aw-metrics-model-count">${count}</span>
        </div>`;
      }).join('')}
    </div>`;
  }

  // History list + model breakdown
  DOM.metricsHistory.innerHTML = modelBreakdownHtml + (m.history.length > 0 
    ? `<div class="aw-metrics-history-title" style="margin-top:10px">Task Log</div>` + m.history.slice(-10).reverse().map(h => 
      `<div class="aw-metrics-history-item">
        <span>${h.success ? '✅' : '❌'}</span>
        <span>${AI_MODELS[h.model]?.icon || '🤖'}</span>
        <span>${new Date(h.timestamp).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</span>
        <span>${(h.duration/1000).toFixed(1)}s</span>
        ${h.formation ? `<span class="aw-muted">${FLEET_FORMATIONS[h.formation]?.icon||''}</span>` : ''}
      </div>`
    ).join('')
    : '');
}

function showFleetMetricsInChat() {
  const m = state.fleet.metrics;
  const successRate = m.totalTasks > 0 ? ((m.completed / m.totalTasks) * 100).toFixed(0) : 0;
  const topModels = Object.entries(state.modelUsage).sort((a,b) => b[1]-a[1]).slice(0, 3)
    .map(([k,v]) => `${AI_MODELS[k]?.icon||''} ${AI_MODELS[k]?.label||k}: ${v}×`).join(', ');
  addAlfred(`📊 **Fleet Metrics**\n\n• **Total Tasks:** ${m.totalTasks}\n• **Completed:** ${m.completed}\n• **Errors:** ${m.errors}\n• **Success Rate:** ${successRate}%\n• **Avg Response:** ${(m.avgResponseTime/1000).toFixed(1)}s\n${topModels ? `• **Top Models:** ${topModels}\n` : ''}\nOpen the **Fleet → Metrics** tab for the full dashboard.`);
}

/* ═══════════════════════════════════════
   SMART AUTO-ROUTING
   ═══════════════════════════════════════ */
function smartRouteTask(text) {
  const lower = text.toLowerCase();
  // Analyze task complexity and type to suggest formation
  if (/security|vulnerab|exploit|hack|pentest|audit/i.test(lower)) return 'security-audit';
  if (/review|code\s*review|pr\s+review|pull\s*request/i.test(lower)) return 'code-review';
  if (/build|create|implement|develop|feature/i.test(lower)) return 'full-stack';
  if (/bug|fix|debug|error|crash|broken/i.test(lower)) return 'bug-hunt';
  if (/launch|deploy|release|go\s*live|production/i.test(lower)) return 'launch-prep';
  if (/prototype|mvp|quick|fast|rapid/i.test(lower)) return 'rapid-prototype';
  if (/migrat|move|transfer|upgrade|convert/i.test(lower)) return 'migration';
  if (/content|write|blog|docs|article|copy/i.test(lower)) return 'content-pipeline';
  return null;
}

/* ═══════════════════════════════════════
   VOICE COMMANDS
   Intercepts voice transcripts before sending.
   Returns true if the text was a command (consumed).
   ═══════════════════════════════════════ */
const VOICE_COMMAND_HELP = [
  '"clear chat" — start a new conversation',
  '"switch to [model]" — change AI model (e.g. "switch to Gemini")',
  '"talk to [agent]" — switch agent (e.g. "talk to Nova")',
  '"generate image of [X]" — create an image with Nano Banana 2',
  '"generate video of [X]" — create a video with Veo 2 (1-5 min)',
  '"read that back" — read the last response aloud',
  '"copy that" — copy last response to clipboard',
  '"stop" — cancel current generation',
  '"favorite" / "unfavorite" — toggle favorite on current agent',
  '"open full voice" — open the full voice portal',
  '"export chat" — download chat history as text',
  '"bigger" / "smaller" — adjust text size',
  '"mute" / "unmute" — toggle auto TTS on responses',
  '"what model" — show current AI model',
  '"go to [page]" — navigate (billing, support, dashboard, etc.)',
  '"scroll up/down" — scroll the chat',
  '"dark mode" / "light mode" — change theme',
  '"show agents/fleet/history" — switch tabs',
  '"compare mode" / "stop comparing" — A/B model comparison',
  '"lock model" / "unlock model" — lock model to current agent',
  '"save workflow" — save current model+agent as workflow',
  '"load workflow [name]" — load a saved workflow',
  '"live mode" / "stop live" — toggle continuous voice mode',
  '"show history" / "load last chat" — conversation history',
  '"help" / "what can you do" — show voice commands',
  '── Chess (on /vr/chess/ page) ──',
  '"play white/black" — start a game vs AI',
  '"move pawn to e4" / "knight to f3" — make a move by voice',
  '"new game" / "resign" / "offer draw" — game control',
  '"camera orbit/top-down/cinematic" — change view',
  '"theme obsidian/marble/crystal" — board theme',
];

function handleVoiceCommand(text) {
  const t = text.toLowerCase().trim();

  // ── Clear/reset ──────────────────────────────────────────
  if (/^(clear\s*(?:the\s*)?chat|new\s*(?:conversation|chat)|start\s*(?:over|fresh))$/i.test(t)) {
    clearChat();
    addSystemMsg('🗑️ Chat cleared via voice command.');
    return true;
  }

  // ── Help ─────────────────────────────────────────────────
  if (/^(help|what\s*can\s*you\s*do|voice\s*commands?|show\s*commands?)$/i.test(t)) {
    addSystemMsg('🎙️ Voice Commands:\n' + VOICE_COMMAND_HELP.map(c => '• ' + c).join('\n'));
    return true;
  }

  // ── Switch model ─────────────────────────────────────────
  const switchMatch = t.match(/(?:switch|change|use|set)\s+(?:to\s+|model\s+)?(.+)/i);
  if (switchMatch) {
    const wanted = switchMatch[1].replace(/model$/i, '').trim().toLowerCase();
    // Find best match in AI_MODELS
    const match = Object.entries(AI_MODELS).find(([key, m]) => {
      const lbl = m.label.toLowerCase();
      return lbl.includes(wanted) || key.includes(wanted);
    });
    if (match) {
      const [key, m] = match;
      state.selectedModel = key;
      localStorage.setItem('aw-model', key);
      state.modelUsage[key] = (state.modelUsage[key] || 0) + 1;
      localStorage.setItem('aw-model-usage', JSON.stringify(state.modelUsage));
      buildModelDropdown();
      addSystemMsg(`🎙️ Model switched to ${m.icon} ${m.label}`);
      return true;
    }
  }

  // ── Generate image ───────────────────────────────────────
  const imgMatch = t.match(/(?:generate|create|draw|make|paint)\s+(?:an?\s+)?image\s+(?:of\s+)?(.+)/i)
    || t.match(/(?:generate|create|draw|make|paint)\s+(.+)/i);
  if (imgMatch && /image|picture|photo|draw|paint|illustration/i.test(t)) {
    // Switch to image model and send the prompt
    const prompt = imgMatch[1].trim();
    state.selectedModel = 'gemini-image';
    localStorage.setItem('aw-model', 'gemini-image');
    buildModelDropdown();
    addSystemMsg('🎙️ Switched to 🍌 Gemini Image Gen');
    addUserMsg(prompt);
    sendMessage(prompt);
    return true;
  }

  // ── Generate video ───────────────────────────────────────
  const vidMatch = t.match(/(?:generate|create|make)\s+(?:a\s+)?video\s+(?:of\s+)?(.+)/i)
    || t.match(/(?:make|create)\s+(?:a\s+)?(?:clip|animation)\s+(?:of\s+)?(.+)/i);
  if (vidMatch && /video|clip|animation|movie|film/i.test(t)) {
    const prompt = vidMatch[1].trim();
    generateVideo(prompt);
    return true;
  }

  // ── Read last response aloud (Web Speech Synthesis) ──────
  if (/^(read\s*(?:that|it|last)?\s*(?:back|aloud|out\s*loud)?|speak\s*(?:that|it|last)?|say\s*(?:that|it)\s*(?:again|aloud)?)$/i.test(t)) {
    const lastAlfred = [...state.messages].reverse().find(m => m.role === 'alfred');
    if (lastAlfred && window.speechSynthesis) {
      const utt = new SpeechSynthesisUtterance(lastAlfred.text.replace(/<[^>]*>/g, '').substring(0, 4000));
      utt.lang = document.documentElement.lang || 'en-US';
      utt.rate = 1.0;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(utt);
      addSystemMsg('🔊 Reading last response aloud...');
    } else {
      addSystemMsg('No response to read back.');
    }
    return true;
  }

  // ── Stop / cancel ────────────────────────────────────────
  if (/^(stop|cancel|shut\s*up|quiet|silence|enough|stop\s*talking)$/i.test(t)) {
    if (window.speechSynthesis) window.speechSynthesis.cancel();
    showTyping(false);
    addSystemMsg('⏹️ Stopped.');
    return true;
  }

  // ── Scroll ───────────────────────────────────────────────
  if (/^scroll\s*(up|down|top|bottom)$/i.test(t)) {
    const dir = t.match(/scroll\s*(up|down|top|bottom)/i)[1].toLowerCase();
    if (dir === 'up' || dir === 'top') DOM.messages.scrollTop = dir === 'top' ? 0 : DOM.messages.scrollTop - 300;
    else DOM.messages.scrollTop = dir === 'bottom' ? DOM.messages.scrollHeight : DOM.messages.scrollTop + 300;
    return true;
  }

  // ── Theme ────────────────────────────────────────────────
  if (/^(dark\s*mode|light\s*mode|toggle\s*theme)$/i.test(t)) {
    const isDark = t.includes('dark') || (t.includes('toggle') && !document.body.classList.contains('dark'));
    document.body.classList.toggle('dark', isDark);
    addSystemMsg(isDark ? '🌙 Dark mode activated.' : '☀️ Light mode activated.');
    return true;
  }

  // ── Switch agent ─────────────────────────────────────────
  const agentMatch = t.match(/(?:talk\s+to|switch\s+(?:to\s+)?agent|agent)\s+(.+)/i);
  if (agentMatch) {
    const wanted = agentMatch[1].trim().toLowerCase();
    for (const group of Object.values(AGENTS)) {
      const found = group.agents.find(a => a.name.toLowerCase() === wanted || a.id === wanted);
      if (found) {
        setAgent(found);
        addSystemMsg(`🎙️ Now talking to ${found.emoji} ${found.name} — ${found.role}`);
        return true;
      }
    }
    addSystemMsg(`Agent "${agentMatch[1]}" not found. Try: Alfred, Nova, Sage, Atlas, etc.`);
    return true;
  }

  // ── Copy last response ───────────────────────────────────
  if (/^(copy\s*(?:that|it|last|response)?|clipboard)$/i.test(t)) {
    const lastAlfred = [...state.messages].reverse().find(m => m.role === 'alfred');
    if (lastAlfred && navigator.clipboard) {
      navigator.clipboard.writeText(lastAlfred.text.replace(/<[^>]*>/g, '')).then(() => {
        addSystemMsg('📋 Last response copied to clipboard.');
      });
    } else {
      addSystemMsg('Nothing to copy.');
    }
    return true;
  }

  // ── Favorite / unfavorite current agent ──────────────────
  if (/^(favorite|unfavorite|fav|unfav|add\s*(?:to\s*)?fav|remove\s*(?:from\s*)?fav)$/i.test(t)) {
    toggleFavorite();
    const isFav = state.agent && state.favorites.includes(state.agent.id);
    addSystemMsg(isFav ? `⭐ ${state.agent.name} added to favorites.` : `${state.agent.name} removed from favorites.`);
    return true;
  }

  // ── Open full voice portal ───────────────────────────────
  if (/^(open\s*(?:full\s*)?voice|voice\s*portal|full\s*(?:screen|mode))$/i.test(t)) {
    window.open(CFG.fullVoice, '_blank');
    addSystemMsg('🎙️ Opening full voice portal...');
    return true;
  }

  // ── Export chat ──────────────────────────────────────────
  if (/^(export|download|save)\s*(?:the\s*)?(?:chat|conversation|history)?$/i.test(t)) {
    const lines = state.messages.map(m => {
      const role = m.role === 'user' ? 'You' : (m.role === 'alfred' ? 'Alfred' : 'System');
      return `[${fmtTime(m.time)}] ${role}: ${(m.text || '').replace(/<[^>]*>/g, '')}`;
    });
    const blob = new Blob([lines.join('\n')], {type:'text/plain'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `alfred-chat-${new Date().toISOString().slice(0,10)}.txt`;
    a.click();
    URL.revokeObjectURL(a.href);
    addSystemMsg('📥 Chat exported.');
    return true;
  }

  // ── Font size ────────────────────────────────────────────
  if (/^(bigger|larger|increase\s*(?:font|text|size))$/i.test(t)) {
    const cur = parseFloat(getComputedStyle(DOM.messages).fontSize) || 14;
    DOM.messages.style.fontSize = Math.min(cur + 2, 24) + 'px';
    addSystemMsg('🔤 Text size increased.');
    return true;
  }
  if (/^(smaller|decrease\s*(?:font|text|size)|tiny)$/i.test(t)) {
    const cur = parseFloat(getComputedStyle(DOM.messages).fontSize) || 14;
    DOM.messages.style.fontSize = Math.max(cur - 2, 10) + 'px';
    addSystemMsg('🔤 Text size decreased.');
    return true;
  }

  // ── Mute / unmute auto-TTS ──────────────────────────────
  if (/^(mute|unmute|toggle\s*(?:sound|audio|tts))$/i.test(t)) {
    state.muted = !state.muted;
    addSystemMsg(state.muted ? '🔇 Auto-voice responses muted.' : '🔊 Auto-voice responses unmuted.');
    return true;
  }

  // ── What model am I using? ──────────────────────────────
  if (/^(what\s*(?:model|ai)|current\s*model|which\s*model)$/i.test(t)) {
    const m = AI_MODELS[state.selectedModel];
    if (m) addSystemMsg(`🤖 Current model: ${m.icon} ${m.label} (${m.provider})`);
    else addSystemMsg(`🤖 Current model: ${state.selectedModel}`);
    return true;
  }

  // ── Navigation shortcuts ────────────────────────────────
  const navMatch = t.match(/^(?:go\s*(?:to)?|open|navigate\s*(?:to)?)\s+(.+)$/i);
  if (navMatch) {
    const dest = navMatch[1].toLowerCase().trim();
    const NAV = {
      'billing': '/invoices',
      'invoices': '/invoices',
      'support': '/submit-ticket',
      'tickets': '/support-tickets',
      'dashboard': '/middleware/dashboard',
      'usage': '/middleware/usage',
      'services': '/services',
      'domains': '/domains',
      'home': '/',
      'voice': '/voice.php',
      'editor': '/editor/',
      'chess': '/chess/',
      'ai servers': '/ai-servers/',
    };
    const url = NAV[dest] || NAV[Object.keys(NAV).find(k => dest.includes(k))];
    if (url) {
      addSystemMsg(`🧭 Navigating to ${dest}...`);
      setTimeout(() => window.location.href = url, 500);
      return true;
    }
  }

  // ── Repeat last message ─────────────────────────────────
  if (/^(repeat|say\s*(?:that\s*)?again|what\s*(?:did\s*you\s*)?say)$/i.test(t)) {
    const lastAlfred = [...state.messages].reverse().find(m => m.role === 'alfred');
    if (lastAlfred) {
      addAlfred(lastAlfred.text);
    } else {
      addSystemMsg('No previous response to repeat.');
    }
    return true;
  }

  // ── Switch tabs ──────────────────────────────────────────
  const tabMatch = t.match(/^(?:show|open|switch\s*to|go\s*to)?\s*(agents?|fleet|chat|history|conversations?)\s*(?:tab|panel)?$/i);
  if (tabMatch) {
    const w = tabMatch[1].toLowerCase();
    const tab = w.startsWith('agent') ? 'agents' : w.startsWith('fleet') ? 'fleet' : (w.startsWith('hist') || w.startsWith('conv')) ? 'history' : 'chat';
    switchTab(tab);
    addSystemMsg(`📑 Switched to ${tab} tab.`);
    return true;
  }

  // ── A/B Compare mode ────────────────────────────────────
  if (/^(compare\s*(?:mode|models?)?|a\/?b\s*(?:mode|compare|test)?|start\s*compar)$/i.test(t)) {
    state.comparisonMode = true;
    state.comparisonModel = null;
    buildModelDropdown();
    addSystemMsg('⚖️ A/B Compare Mode ON — select Model B from the model list or say "switch to [model]" then ask a question.');
    return true;
  }
  if (/^(stop\s*compar|disable\s*compar|compare\s*off|no\s*compare|cancel\s*compare)$/i.test(t)) {
    state.comparisonMode = false;
    state.comparisonModel = null;
    buildModelDropdown();
    addSystemMsg('A/B Compare Mode OFF.');
    return true;
  }

  // ── Lock / unlock model to agent ────────────────────────
  if (/^(lock\s*(?:this\s*)?model|lock\s*(?:it|current))$/i.test(t)) {
    const agentId = state.agent?.id;
    if (agentId) {
      state.agentModelLocks[agentId] = state.selectedModel;
      localStorage.setItem('aw-agent-model-locks', JSON.stringify(state.agentModelLocks));
      const m = AI_MODELS[state.selectedModel] || {};
      addSystemMsg(`🔒 ${m.icon||''} ${m.label||state.selectedModel} locked for ${state.agent?.emoji||''} ${state.agent?.name||agentId}`);
      buildModelDropdown();
    } else {
      addSystemMsg('No agent selected to lock model to.');
    }
    return true;
  }
  if (/^(unlock\s*(?:this\s*)?model|unlock\s*(?:it|current)|free\s*model)$/i.test(t)) {
    const agentId = state.agent?.id;
    if (agentId && state.agentModelLocks[agentId]) {
      delete state.agentModelLocks[agentId];
      localStorage.setItem('aw-agent-model-locks', JSON.stringify(state.agentModelLocks));
      addSystemMsg(`🔓 Model unlocked for ${state.agent?.emoji||''} ${state.agent?.name||agentId}`);
      buildModelDropdown();
    } else {
      addSystemMsg('No model lock to remove.');
    }
    return true;
  }

  // ── Save workflow ───────────────────────────────────────
  if (/^(save\s*(?:this\s*)?workflow|save\s*(?:current\s*)?setup|bookmark\s*(?:this\s*)?(?:model|setup))$/i.test(t)) {
    showSaveWorkflowDialog();
    return true;
  }

  // ── Load workflow by name ───────────────────────────────
  const wfMatch = t.match(/^(?:load|use|apply|restore)\s+(?:workflow\s+)?(.+)/i);
  if (wfMatch && state.savedWorkflows && state.savedWorkflows.length) {
    const wanted = wfMatch[1].trim().toLowerCase();
    const idx = state.savedWorkflows.findIndex(w => w.name.toLowerCase().includes(wanted));
    if (idx >= 0) {
      loadWorkflow(idx);
      addSystemMsg(`📂 Loaded workflow: ${state.savedWorkflows[idx].name}`);
      return true;
    }
    addSystemMsg(`Workflow "${wfMatch[1]}" not found. Saved workflows: ${state.savedWorkflows.map(w=>w.name).join(', ')||'none'}`);
    return true;
  }

  // ── Toggle live voice mode ──────────────────────────────
  if (/^(live\s*(?:mode|voice)?|start\s*live|continuous\s*(?:mode|voice|listen))$/i.test(t)) {
    if (!state.liveMode) { toggleLiveMode(); addSystemMsg('🎤 Live voice mode started.'); }
    else addSystemMsg('Already in live mode.');
    return true;
  }
  if (/^(stop\s*live|end\s*live|exit\s*live|normal\s*(?:mode|voice))$/i.test(t)) {
    if (state.liveMode) { stopLive(); addSystemMsg('🎤 Live mode stopped.'); }
    else addSystemMsg('Not in live mode.');
    return true;
  }

  // ── History / load conversation ─────────────────────────
  if (/^(show\s*history|chat\s*history|past\s*(?:chats?|conversations?)|conversations?)$/i.test(t)) {
    switchTab('history');
    addSystemMsg('📜 Showing conversation history.');
    return true;
  }
  if (/^(load\s*(?:last|previous|recent)\s*(?:chat|conversation)?|resume\s*(?:last|previous))$/i.test(t)) {
    const hist = JSON.parse(localStorage.getItem('aw-history') || '[]');
    if (hist.length > 0) {
      loadConversation(hist[hist.length - 1]);
      addSystemMsg('📜 Loaded previous conversation.');
    } else {
      addSystemMsg('No previous conversations found.');
    }
    return true;
  }

  // ── Close / minimize widget ─────────────────────────────
  if (/^(close|minimize|hide|go\s*away)$/i.test(t)) {
    togglePanel(false);
    return true;
  }

  // ── Chess voice commands (delegate to ChessVoiceCommander if on chess page) ──
  if (window.chessVoice && window.chessVoice.handleCommand) {
    if (window.chessVoice.handleCommand(t)) {
      return true;
    }
  }

  // Not a voice command — pass through to normal chat
  return false;
}

/* ═══════════════════════════════════════
   VOICE RECORDING (TAP MODE)
   Uses Web Speech API (browser-native STT) as primary.
   Falls back to MediaRecorder + server Whisper if unavailable.
   ═══════════════════════════════════════ */
const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;

async function toggleTapRecord() {
  if (state.liveMode) return;
  if (state.recording) {
    stopRecording();
  } else {
    // Prefer browser-native speech recognition (no server round-trip)
    if (SpeechRec) {
      startSpeechRecognition();
    } else {
      await startRecording();
    }
  }
}

/** Browser-native Speech Recognition — works on Chrome, Edge, Safari, iOS */
function startSpeechRecognition() {
  try {
    const recognition = new SpeechRec();
    recognition.lang = (document.documentElement.lang || 'en-US').replace('_', '-');
    recognition.interimResults = true;
    recognition.continuous = false;
    recognition.maxAlternatives = 1;

    state.recording = true;
    state._speechRec = recognition;
    DOM.micBtn.classList.add('recording');
    addSystemMsg('🎙️ Listening... speak now');

    let finalTranscript = '';
    let gotResults = false;

    // Safety timeout — if no results after 8s, stop gracefully
    const safetyTimer = setTimeout(() => {
      if (state._speechRec === recognition) {
        try { recognition.stop(); } catch(e) {}
      }
    }, 8000);

    recognition.onresult = (event) => {
      gotResults = true;
      let interim = '';
      for (let i = event.resultIndex; i < event.results.length; i++) {
        const t = event.results[i][0].transcript;
        if (event.results[i].isFinal) {
          finalTranscript += t;
        } else {
          interim += t;
        }
      }
      // Show interim results in input
      if (DOM.input) {
        DOM.input.value = finalTranscript || interim;
      }
    };

    recognition.onend = () => {
      clearTimeout(safetyTimer);
      state.recording = false;
      DOM.micBtn.classList.remove('recording');
      state._speechRec = null;

      const text = (finalTranscript || '').trim();
      if (text) {
        if (DOM.input) DOM.input.value = '';
        // Try voice commands first; fall through to normal chat if not a command
        if (!handleVoiceCommand(text)) {
          addUserMsg(text);
          sendMessage(text);
        }
      } else if (!gotResults) {
        addSystemMsg('No speech detected. Tap the mic and speak clearly.');
      }
    };

    recognition.onerror = (event) => {
      clearTimeout(safetyTimer);
      state.recording = false;
      DOM.micBtn.classList.remove('recording');
      state._speechRec = null;
      if (event.error === 'not-allowed') {
        addSystemMsg('Microphone access denied. Please allow microphone permission.');
      } else if (event.error === 'no-speech') {
        addSystemMsg('No speech detected. Tap the mic and speak.');
      } else if (event.error === 'aborted') {
        // User or system cancelled — do nothing
      } else {
        // network, service-not-allowed, etc. — fall back silently to MediaRecorder
        console.warn('SpeechRecognition error:', event.error);
        startRecording();
      }
    };

    recognition.start();
  } catch(e) {
    // SpeechRecognition constructor failed — fall back to MediaRecorder
    console.warn('SpeechRecognition failed to start:', e);
    state.recording = false;
    DOM.micBtn.classList.remove('recording');
    state._speechRec = null;
    startRecording();
  }
}

/** MediaRecorder fallback — records audio and uploads to server for Whisper STT */
async function startRecording() {
  try {
    state.mediaStream = await navigator.mediaDevices.getUserMedia({audio: {
      channelCount: 1, sampleRate: 16000, echoCancellation: true, noiseSuppression: true
    }});
    state.recording = true;
    DOM.micBtn.classList.add('recording');
    showWaveform(true);
    setupAnalyser();

    state.mediaRec = new MediaRecorder(state.mediaStream, {mimeType: getBestMime()});
    const chunks = [];
    state.mediaRec.ondataavailable = e => { if (e.data.size > 0) chunks.push(e.data); };
    state.mediaRec.onstop = async () => {
      const blob = new Blob(chunks, {type: state.mediaRec.mimeType});
      await sendAudioBlob(blob);
    };
    state.mediaRec.start();
    addSystemMsg('🎙️ Recording... tap mic again to stop.');
  } catch(e) {
    addSystemMsg('Microphone access denied. Please allow microphone permission.');
  }
}

function stopRecording() {
  state.recording = false;
  DOM.micBtn.classList.remove('recording');
  showWaveform(false);
  // Stop SpeechRecognition if active
  if (state._speechRec) {
    state._speechRec.stop();
    state._speechRec = null;
    return;
  }
  if (state.mediaRec && state.mediaRec.state !== 'inactive') state.mediaRec.stop();
  cleanupAudio();
}

/* ═══════════════════════════════════════
   VOICE LIVE MODE (HOLD)
   ═══════════════════════════════════════ */
async function toggleLiveMode() {
  if (state.liveMode) {
    stopLive();
  } else {
    await startLive();
  }
}

async function startLive() {
  try {
    state.mediaStream = await navigator.mediaDevices.getUserMedia({audio: {
      channelCount: 1, sampleRate: 16000, echoCancellation: true, noiseSuppression: true
    }});
    state.liveMode = true;
    DOM.micBtn.classList.add('live');
    DOM.trigger.classList.add('live');
    showWaveform(true);
    setupAnalyser();

    // Send audio chunks via WebSocket
    if (!state.audioCtx) state.audioCtx = new (window.AudioContext || window.webkitAudioContext)({sampleRate: 16000});
    const source = state.audioCtx.createMediaStreamSource(state.mediaStream);
    const processor = state.audioCtx.createScriptProcessor(4096, 1, 1);
    processor.onaudioprocess = e => {
      if (!state.liveMode || !state.wsReady) return;
      const pcm = e.inputBuffer.getChannelData(0);
      const int16 = new Int16Array(pcm.length);
      for (let i = 0; i < pcm.length; i++) {
        int16[i] = Math.max(-32768, Math.min(32767, pcm[i] * 32768));
      }
      state.ws.send(int16.buffer);
    };
    source.connect(processor);
    processor.connect(state.audioCtx.destination);
    state._liveSource = source;
    state._liveProcessor = processor;

    wsSend({type: 'voice_start', agent: state.agent?.id || 'alfred', engine: state.agent?.engine, voice: state.agent?.voice, mode: 'live'});
    addSystemMsg('🔴 Live voice mode active — speak freely');
  } catch(e) {
    addSystemMsg('Microphone access denied.');
  }
}

function stopLive() {
  state.liveMode = false;
  DOM.micBtn.classList.remove('live');
  DOM.trigger.classList.remove('live');
  showWaveform(false);
  if (state._liveProcessor) { state._liveProcessor.disconnect(); state._liveProcessor = null; }
  if (state._liveSource) { state._liveSource.disconnect(); state._liveSource = null; }
  wsSend({type: 'voice_stop'});
  cleanupAudio();
  addSystemMsg('Live voice mode ended');
}

/* ── Audio helpers ── */
function getBestMime() {
  const types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
  for (const t of types) if (MediaRecorder.isTypeSupported(t)) return t;
  return 'audio/webm';
}

async function sendAudioBlob(blob) {
  showTyping(true);
  addSystemMsg('🎙️ Processing voice...');
  
  // Send via WebSocket if available
  if (state.wsReady) {
    const reader = new FileReader();
    reader.onload = () => {
      wsSend({type: 'voice_tap', agent: state.agent?.id, engine: state.agent?.engine, voice: state.agent?.voice});
      state.ws.send(reader.result);
      wsSend({type: 'voice_tap_end'});
    };
    reader.readAsArrayBuffer(blob);
  } else {
    // Fallback: upload via REST
    const form = new FormData();
    form.append('audio', blob, 'recording.webm');
    form.append('agent', state.agent?.id || 'alfred');
    try {
      const resp = await fetch(CFG.chatApi + '?action=voice', {method: 'POST', body: form});
      const data = await resp.json();
      showTyping(false);
      if (data.transcript) addUserMsg(data.transcript);
      if (data.response) addAlfred(data.response, data.cards);
    } catch(e) {
      showTyping(false);
      addSystemMsg('Voice processing failed.');
    }
  }
}

function cleanupAudio() {
  if (state.mediaStream) { state.mediaStream.getTracks().forEach(t => t.stop()); state.mediaStream = null; }
}

/* ── Audio playback ── */
const audioQueue = [];
let isPlaying = false;

function playAudioChunk(buffer) {
  audioQueue.push(buffer);
  if (!isPlaying) playNext();
}
async function playNext() {
  if (!audioQueue.length) { isPlaying = false; return; }
  isPlaying = true;
  const buf = audioQueue.shift();
  try {
    if (!state.audioCtx) state.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const decoded = await state.audioCtx.decodeAudioData(buf.slice(0));
    const src = state.audioCtx.createBufferSource();
    src.buffer = decoded;
    src.connect(state.audioCtx.destination);
    src.onended = playNext;
    src.start();
  } catch(e) { playNext(); }
}

/* ═══════════════════════════════════════
   WAVEFORM VISUALIZATION
   ═══════════════════════════════════════ */
function setupAnalyser() {
  if (!state.mediaStream) return;
  if (!state.audioCtx) state.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  state.analyser = state.audioCtx.createAnalyser();
  state.analyser.fftSize = 256;
  const source = state.audioCtx.createMediaStreamSource(state.mediaStream);
  source.connect(state.analyser);
  drawWaveform();
}

function drawWaveform() {
  if (!state.analyser) return;
  const canvas = DOM.waveform;
  const ctx = canvas.getContext('2d');
  const bufLen = state.analyser.frequencyBinCount;
  const dataArr = new Uint8Array(bufLen);
  canvas.width = canvas.offsetWidth * 2;
  canvas.height = canvas.offsetHeight * 2;

  function draw() {
    if (!state.recording && !state.liveMode) { ctx.clearRect(0,0,canvas.width,canvas.height); return; }
    state.waveAnim = requestAnimationFrame(draw);
    state.analyser.getByteFrequencyData(dataArr);

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const barW = (canvas.width / bufLen) * 2.5;
    const centerY = canvas.height / 2;

    for (let i = 0; i < bufLen; i++) {
      const v = dataArr[i] / 255;
      const h = v * centerY * 0.9;
      const x = i * barW;
      const grad = ctx.createLinearGradient(x, centerY - h, x, centerY + h);
      grad.addColorStop(0, 'rgba(125,0,255,0.8)');
      grad.addColorStop(0.5, 'rgba(0,212,255,0.9)');
      grad.addColorStop(1, 'rgba(125,0,255,0.8)');
      ctx.fillStyle = grad;
      ctx.fillRect(x, centerY - h, barW - 1, h * 2);
    }
  }
  draw();
}

function showWaveform(show) {
  DOM.waveWrap.classList.toggle('visible', show);
  if (!show && state.waveAnim) { cancelAnimationFrame(state.waveAnim); state.waveAnim = null; }
}

/* ═══════════════════════════════════════
   LANGUAGE DETECTION
   ═══════════════════════════════════════ */
function detectLanguage(text) {
  // Simple heuristic patterns
  const patterns = {
    'fr': /\b(bonjour|merci|comment|oui|non|je suis|s'il vous|pourquoi|bienvenue|salut|où|ça)\b/i,
    'es': /\b(hola|gracias|cómo|sí|no|por favor|buenos|buenas|donde|qué|quiero)\b/i,
    'de': /\b(hallo|danke|wie|ja|nein|bitte|guten|warum|ich bin|wo ist)\b/i,
    'ja': /[\u3040-\u309f\u30a0-\u30ff]/,
    'zh': /[\u4e00-\u9fff]/,
    'hi': /[\u0900-\u097f]/
  };

  for (const [lang, rx] of Object.entries(patterns)) {
    if (rx.test(text) && LANG_MAP[lang]) {
      if (state.langDetected !== lang) {
        state.langDetected = lang;
        const info = LANG_MAP[lang];
        DOM.langBadge.innerHTML = `${info.flag} ${info.label}`;
        DOM.langBadge.classList.add('visible');
        // Auto-switch agent
        const la = findAgent(info.agent);
        if (la && state.agent?.id !== la.id) {
          setAgent(la);
          addSystemMsg(`${info.flag} Language detected: ${info.label} — switched to ${la.name}`);
        }
      }
      return;
    }
  }
  // English or undetected
  if (state.langDetected) {
    state.langDetected = null;
    DOM.langBadge.classList.remove('visible');
  }
}

/* ═══════════════════════════════════════
   PIPELINES (Voice-to-Action)
   ═══════════════════════════════════════ */
function updatePipeline(msg) {
  state.pipelineActive = true;
  DOM.pipelineBar.classList.add('visible');
  DOM.pipelineTitle.textContent = msg.title || 'Processing...';
  DOM.pipelineFill.style.width = (msg.progress || 0) + '%';
  if (msg.progress >= 100) {
    setTimeout(()=> {
      state.pipelineActive = false;
      DOM.pipelineBar.classList.remove('visible');
    }, 2000);
  }
}

/* ═══════════════════════════════════════
   COLLABORATION
   ═══════════════════════════════════════ */
function addCollabUser(msg) {
  if (!state.collabUsers.find(u => u.id === msg.userId)) {
    state.collabUsers.push({id: msg.userId, name: msg.username, color: msg.color || '#7d00ff'});
    renderCollabBar();
  }
}
function removeCollabUser(msg) {
  state.collabUsers = state.collabUsers.filter(u => u.id !== msg.userId);
  renderCollabBar();
}
function renderCollabBar() {
  if (state.collabUsers.length === 0) {
    DOM.collabBar.classList.remove('visible');
    return;
  }
  DOM.collabBar.classList.add('visible');
  DOM.collabBar.innerHTML = state.collabUsers.map(u =>
    `<div class="aw-collab-avatar" style="background:${u.color}" title="${esc(u.name)}">${u.name.charAt(0).toUpperCase()}</div>`
  ).join('') + `<span class="aw-collab-label">${state.collabUsers.length} user${state.collabUsers.length>1?'s':''} connected</span>`;
}

/* ═══════════════════════════════════════
   HISTORY (localStorage + API)
   ═══════════════════════════════════════ */

/**
 * Flush entire conversation to server on page close / hangup.
 * Uses navigator.sendBeacon for reliability during teardown.
 * This way, if a user is mid-call drafting legal docs (habeas corpus, etc.)
 * and hangs up or closes the browser, the full conversation is persisted.
 */
let _flushed = false;
function flushConversation() {
  if (_flushed || !state.convId || state.messages.length === 0) return;
  _flushed = true; // prevent duplicate flushes from multiple events

  // Save full conversation to localStorage immediately (sync, reliable)
  try {
    const history = JSON.parse(localStorage.getItem('aw-history') || '[]');
    const existing = history.find(h => h.id === state.convId);
    if (existing) {
      existing.messages = state.messages.map(m => ({
        role: m.role, text: (m.text || '').substring(0, 2000), time: m.time ? m.time.getTime() : Date.now()
      }));
      existing.updated = Date.now();
    } else {
      history.unshift({
        id: state.convId,
        agent: state.agent?.id || 'alfred',
        agentName: state.agent?.name || 'Alfred',
        agentEmoji: state.agent?.emoji || '🎩',
        messages: state.messages.map(m => ({
          role: m.role, text: (m.text || '').substring(0, 2000), time: m.time ? m.time.getTime() : Date.now()
        })),
        created: Date.now(),
        updated: Date.now()
      });
    }
    while (history.length > 50) history.pop();
    localStorage.setItem('aw-history', JSON.stringify(history));
  } catch (e) { /* localStorage may be full — non-critical */ }

  // Also persist unsaved messages to server via sendBeacon (works during page teardown)
  if (CFG.authToken || state.messages.length > 0) {
    try {
      const payload = JSON.stringify({
        conv_id: state.convId,
        agent: state.agent?.id || 'alfred',
        messages: state.messages.map(m => ({
          role: m.role === 'alfred' ? 'alfred' : (m.role === 'system' ? 'system' : 'user'),
          text: (m.text || '').substring(0, 5000)
        }))
      });
      navigator.sendBeacon(CFG.chatApi + '?action=flush', payload);
    } catch (e) { /* beacon may fail — non-critical, localStorage has the data */ }
  }

  // Reset flush guard after 2 seconds (in case user returns to page)
  setTimeout(() => { _flushed = false; }, 2000);
}

function saveConversation(text, role) {
  const history = JSON.parse(localStorage.getItem('aw-history') || '[]');
  if (!state.convId) state.convId = 'conv-' + Date.now();
  const existing = history.find(h => h.id === state.convId);
  if (existing) {
    existing.messages.push({role, text: text.substring(0, 200), time: Date.now()});
    existing.updated = Date.now();
  } else {
    history.unshift({
      id: state.convId,
      agent: state.agent?.id || 'alfred',
      agentName: state.agent?.name || 'Alfred',
      agentEmoji: state.agent?.emoji || '🎩',
      messages: [{role, text: text.substring(0, 200), time: Date.now()}],
      created: Date.now(),
      updated: Date.now()
    });
  }
  // Keep last 50 conversations
  while (history.length > 50) history.pop();
  localStorage.setItem('aw-history', JSON.stringify(history));

  // Also persist to server if available
  if (CFG.authToken) {
    fetch(CFG.chatApi + '?action=save', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({conv_id: state.convId, role, text: text.substring(0, 2000), agent: state.agent?.id})
    }).catch(()=>{});
  }
}

function loadHistory() {
  // Try to resume last conversation
  const history = JSON.parse(localStorage.getItem('aw-history') || '[]');
  if (history.length > 0) {
    const last = history[0];
    // Only resume if < 30 min old
    if (Date.now() - last.updated < 30 * 60000) {
      state.convId = last.id;
    }
  }
}

function renderHistory() {
  const history = JSON.parse(localStorage.getItem('aw-history') || '[]');
  if (history.length === 0) {
    DOM.historyList.innerHTML = `<div class="aw-history-empty"><div class="aw-history-empty-icon">💬</div><p>No conversation history yet.<br>Start chatting to build your history.</p></div>`;
    return;
  }
  DOM.historyList.innerHTML = history.map(h => {
    const firstMsg = h.messages[0]?.text || '';
    const date = new Date(h.created);
    return `<div class="aw-history-item" data-conv="${h.id}">
      <div class="aw-history-date">${date.toLocaleDateString()} ${date.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</div>
      <div class="aw-history-preview">${esc(firstMsg)}</div>
      <div class="aw-history-agent">${h.agentEmoji||'🎩'} ${h.agentName||'Alfred'} &bull; ${h.messages.length} messages</div>
    </div>`;
  }).join('');

  DOM.historyList.querySelectorAll('.aw-history-item').forEach(item => {
    item.addEventListener('click', ()=> {
      const conv = history.find(h => h.id === item.dataset.conv);
      if (conv) loadConversation(conv);
    });
  });
}

function loadConversation(conv) {
  state.convId = conv.id;
  state.messages = [];
  DOM.messages.innerHTML = '';
  const a = findAgent(conv.agent);
  if (a) setAgent(a);
  conv.messages.forEach(m => {
    if (m.role === 'user') addUserMsg(m.text);
    else addAlfred(m.text);
  });
  switchTab('chat');
}

/* ═══════════════════════════════════════
   UTILITIES
   ═══════════════════════════════════════ */
function esc(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}
function fmtTime(d) {
  return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}

/* ═══════════════════════════════════════
   PUBLIC API (for page integrations)
   ═══════════════════════════════════════ */
window.AlfredAPI = {
  addSystemMsg: (msg) => { if (typeof addSystemMsg === 'function') addSystemMsg(msg); },
  addUserMsg:   (msg) => { if (typeof addUserMsg === 'function') addUserMsg(msg); },
  togglePanel:  (show) => { if (typeof togglePanel === 'function') togglePanel(show); },
  isOpen:       () => state.open,
};

/* ═══════════════════════════════════════
   START
   ═══════════════════════════════════════ */
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

})();
