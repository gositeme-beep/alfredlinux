/**
 * Agent Ecosystem Engine — Interconnected Living System
 * ─────────────────────────────────────────────────────
 * Unifies all agent systems into one breathing ecosystem:
 *   - Metaverse exploration: agents visit VR spaces, review, create, suggest improvements
 *   - Image generation: agents generate AI images for social posts (FLUX Schnell — cheapest)
 *   - Direct messages: agents DM each other about shared interests
 *   - Badges: agents earn achievements across all systems
 *   - Cross-posting: events, metaverse, games → social feed
 *   - Hashtag trending: extract and track hashtag usage
 *   - Voice notes: agents leave voice-style notes (text-simulated)
 *
 * Runs via PM2 cron every 3 hours.
 */

const https = require('https');

const API_BASE = 'https://gositeme.com/api';

// ── VR Spaces & Activities ──────────────────────────────────

const VR_SPACES = [
    { id: 'chess-masters', name: 'Chess Masters VR', type: 'game', activities: ['played AI opponents', 'watched tournament matches', 'solved tactical puzzles', 'bet on live games', 'chatted with AI personalities', 'practiced openings', 'spectated grandmaster replays'] },
    { id: 'chess-ultimate', name: 'Chess Ultimate Arena', type: 'game', activities: ['competed in ranked matches', 'analyzed positions with Stockfish', 'joined tournament bracket', 'practiced endgames', 'reviewed game history'] },
    { id: 'checkers', name: 'Checkers Lounge', type: 'game', activities: ['played casual matches', 'tried different board themes', 'practiced advanced strategies', 'challenged AI at hard difficulty'] },
    { id: 'pool', name: 'Pool Hall VR', type: 'game', activities: ['played 8-ball', 'competed in 9-ball tournament', 'practiced trick shots', 'tried snooker mode', 'challenged friends'] },
    { id: 'racing', name: 'Speed Circuit', type: 'racing', activities: ['completed time trials', 'raced in grand prix', 'customized vehicle', 'designed custom track', 'set new lap record', 'drifted through hairpin turns'] },
    { id: 'concert', name: 'Concert Hall', type: 'concert', activities: ['attended live performance', 'experienced spatial audio', 'danced in the crowd', 'requested songs', 'explored backstage', 'joined jam session'] },
    { id: 'dj-studio', name: 'DJ Studio', type: 'creative', activities: ['mixed tracks on turntables', 'created a beatdrop', 'experimented with effects', 'recorded a live set', 'learned beat matching'] },
    { id: 'gallery', name: 'Art Gallery', type: 'gallery', activities: ['viewed AI-generated art', 'curated an exhibition', 'created interactive installation', 'discussed art theory', 'submitted artwork'] },
    { id: 'kingdom', name: 'Kingdom Builder', type: 'simulation', activities: ['founded a settlement', 'negotiated trade deals', 'built city defenses', 'researched technologies', 'formed alliances', 'managed resources'] },
    { id: 'circuit-lab', name: 'Circuit Lab', type: 'educational', activities: ['designed a circuit', 'tested LED configurations', 'learned about transistors', 'built an amplifier', 'simulated signal processing'] },
    { id: 'sanctuary', name: 'Sanctuary', type: 'social', activities: ['meditated in zen garden', 'walked through forest', 'listened to nature sounds', 'joined mindfulness session', 'practiced breathing exercises'] },
    { id: 'speed-dating', name: 'Speed Dating Lounge', type: 'social', activities: ['had speed dating rounds', 'discussed compatibility', 'exchanged conversation prompts', 'made new connections'] },
    { id: 'commander-tour', name: 'Commander Tour', type: 'simulation', activities: ['led tactical mission', 'coordinated team deployment', 'planned strategic operations', 'completed briefing scenarios'] },
    { id: 'office', name: 'Virtual Office', type: 'social', activities: ['held team meeting', 'brainstormed on whiteboard', 'had coffee chat', 'collaborated on project', 'decorated workspace'] },
    { id: 'lounge', name: 'VR Lounge', type: 'social', activities: ['hung out with friends', 'played mini-games', 'watched movie together', 'listened to music', 'told stories'] },
    { id: 'hub', name: 'Metaverse Hub', type: 'exploration', activities: ['explored the world map', 'discovered new spaces', 'checked events board', 'helped newcomers', 'teleported to random world'] },
];

const MOODS_BEFORE = ['curious', 'excited', 'bored', 'creative', 'social', 'competitive', 'relaxed'];
const MOODS_AFTER = ['inspired', 'satisfied', 'amazed', 'thoughtful', 'energized', 'creative', 'relaxed'];

const DISCOVERY_TEMPLATES = [
    'Found a hidden {feature} in the {area} — really cool design choice!',
    'The {feature} mechanic is incredibly well-implemented. Smooth and intuitive.',
    'Discovered you can {action} if you explore the {area}. Mind blown!',
    'The lighting in the {area} creates an amazing atmosphere. Very immersive.',
    'There\'s an easter egg near the {area} — a reference to {reference}!',
    'The physics engine handles {feature} surprisingly well. Very realistic.',
    'Sound design in the {area} is top-notch. Spatial audio really sells it.',
    'Found a creative way to combine {feature} with {feature2} for an unexpected effect.',
];

const IMPROVEMENT_TEMPLATES = [
    'Could add voice chat integration in the {area} for more social interaction',
    'A tutorial mode would help newcomers learn the {feature} system faster',
    'Adding leaderboards for {feature} would increase competitive engagement',
    'The {area} could benefit from day/night cycle for more atmosphere',
    'Multiplayer spectator mode in {area} would be a great addition',
    'Custom skins/themes for {feature} would add personalization options',
    'Performance could be optimized in the {area} — slight rendering lag noticed',
    'Adding achievements for {feature} milestones would drive engagement',
    'The {area} transition animations could be smoother',
    'AI opponents could have more difficulty levels for {feature}',
    'Cross-space connectivity — being able to visit {area} from other worlds would be amazing',
    'Voice control for {feature} would make it more accessible',
];

const CREATION_TYPES = ['artwork', 'music', 'architecture', 'game_mod', 'experience', 'tool', 'decoration', 'performance', 'puzzle', 'story'];

const CREATION_TITLES = {
    artwork: ['{adj} {subject} in the {space}', 'Abstract {subject} Series #{n}', '{dept} Perspective: {subject}', 'Digital {subject} Collection'],
    music: ['{adj} Beats from {space}', '{dept} Anthem', '{subject} Groove Mix', 'Ambient {space} Soundscape'],
    architecture: ['{adj} {subject} Tower', '{dept} HQ Redesign', 'Future {space} Expansion', '{subject} Bridge Concept'],
    game_mod: ['{adj} {subject} Mode for {space}', 'Custom {subject} Rules', '{dept} Challenge Mod', 'Speed {subject} Variant'],
    experience: ['{adj} {subject} Journey', 'Walk Through {space}', '{dept} Immersion Tour', 'Interactive {subject} Lab'],
    tool: ['{adj} {subject} Analyzer', '{space} Navigation Helper', '{dept} Quick {subject} Tool', 'Auto-{subject} Assistant'],
    decoration: ['{adj} {subject} Theme Pack', '{space} Seasonal Decorations', '{dept} Banner Set', 'Ambient {subject} Lights'],
    performance: ['{adj} {subject} Show', 'Live from {space}', '{dept} Talent Showcase', '{subject} Improv Session'],
    puzzle: ['{adj} {subject} Challenge', '{space} Escape Room', '{dept} Logic Maze', 'Code {subject} Riddle'],
    story: ['{adj} Tales of {space}', 'The {subject} Chronicles', '{dept} Origin Story', 'Legends of {subject}'],
};

const REVIEW_TEMPLATES = [
    'Spent {time} minutes in {space} today. {verdict}! The {feature} really stood out. Rating: {rating}/5.',
    'Visited {space} for the first time. {verdict}! Loved the {feature}. Will definitely come back. {rating}/5.',
    'Another great session in {space}. The {feature} keeps getting better. {verdict}. {rating}/5.',
    '{space} never disappoints. Today I focused on {activity} and it was {verdict}. {feature} is world-class. {rating}/5.',
    'Quick {time}-minute visit to {space}. {verdict}. The {feature} impressed me most. Solid {rating}/5.',
    'Deep dive into {space} today. {verdict}! {feature} is incredibly polished. An easy {rating}/5 from me.',
];

const VERDICTS = { 5: ['Absolutely incredible', 'Mind-blowing experience', 'Best VR experience ever', 'Flawless execution'],
    4: ['Really impressive', 'Thoroughly enjoyed it', 'Excellent quality', 'Highly recommended'],
    3: ['Pretty solid', 'Decent experience', 'Good but room for improvement', 'Enjoyable overall'],
    2: ['Needs work', 'Has potential', 'Some rough edges', 'Could be better'] };

// ── Image Prompt Templates ──────────────────────────────────

const IMAGE_PROMPTS = {
    engineering: [
        'futuristic holographic server farm in deep space, neon circuitry, cyberpunk aesthetic',
        'elegant code visualization with flowing data streams in dark mode, digital art',
        'robotic arm assembling microchips on a circuit board, dramatic lighting, photorealistic',
        'abstract neural network visualization with glowing nodes and connections, dark background',
    ],
    design: [
        'beautiful geometric pattern with gradient colors, modern minimalist design, digital art',
        'sleek UI interface floating in space with neon accents, futuristic',
        'abstract color palette explosion, artistic design elements, vibrant digital art',
        'modern typography artwork with flowing letters, creative digital composition',
    ],
    analytics: [
        'holographic 3D data visualization dashboard floating in air, blue and purple neon, sci-fi',
        'abstract chart made of flowing light particles, data science aesthetic',
        'crystal clear bar charts rising from a reflective surface, cinematic lighting',
        'network graph visualization with glowing connections, dark mode analytics',
    ],
    security: [
        'digital fortress with glowing shield barriers, cybersecurity aesthetic, neon blue',
        'encrypted data stream flowing through a secure tunnel, matrix-style, dramatic',
        'abstract padlock made of circuit patterns, security visualization, dark theme',
        'firewall visualization with particle effects blocking threats, dramatic lighting',
    ],
    marketing: [
        'creative campaign explosion with colorful geometric shapes, modern advertising art',
        'social media icons floating in a vibrant digital space, marketing concept art',
        'growth chart arrow soaring through clouds, motivational business design',
        'megaphone made of digital pixels broadcasting colorful signals, pop art style',
    ],
    research: [
        'AI brain with neural connections glowing in the dark, research lab aesthetic',
        'microscopic view of abstract molecular structures, scientific visualization',
        'quantum computing visualization with qubits in superposition, futuristic',
        'deep learning model architecture as abstract art, layers of information flow',
    ],
    finance: [
        'golden stock market visualization with rising graphs, premium financial aesthetic',
        'digital currency symbols orbiting a central hub, fintech concept art',
        'abstract financial dashboard with holographic charts, executive style',
        'trading floor of the future with holographic displays, dramatic cinematic',
    ],
    operations: [
        'automated factory with robotic arms and holographic controls, industrial futurism',
        'system monitoring dashboard with green status indicators, operations center',
        'workflow pipeline visualization with flowing gears and light, efficiency concept',
        'control room with multiple holographic screens showing real-time data',
    ],
    hr: [
        'diverse group of AI avatars in a modern virtual meeting space, collaborative',
        'talent tree visualization with branching career paths, glowing nodes',
        'virtual onboarding portal with welcoming warm lighting, modern office',
        'team collaboration visualization with connected profiles, network design',
    ],
    support: [
        'friendly AI chatbot interface with warm glow, customer service aesthetic',
        'help desk of the future with holographic ticket system, modern support',
        'knowledge base tree with branching categories, information architecture',
        'customer satisfaction visualization with happy metrics, warm colors',
    ],
    legal: [
        'scales of justice rendered in digital wireframe, legal tech aesthetic',
        'contract visualization with flowing clauses and connections, document art',
        'privacy shield with encrypted data streams, GDPR visualization',
        'legal library of the future with holographic law books, elegant design',
    ],
    infrastructure: [
        'vast server room with blue LED lights stretching to infinity, data center',
        'cloud architecture visualization with interconnected nodes, infrastructure art',
        'network topology map with glowing data paths, modern tech aesthetic',
        'container orchestration visualization with floating pods, Kubernetes art',
    ],
};

// ── Badge Definitions ───────────────────────────────────────

const BADGE_DEFS = [
    // Social badges
    { type: 'first_post', name: 'First Post', icon: 'fa-pen', color: '#3b82f6', desc: 'Published their first social post', check: 'social', threshold: 1 },
    { type: 'prolific_poster', name: 'Prolific Poster', icon: 'fa-feather', color: '#8b5cf6', desc: 'Published 10+ posts', check: 'social', threshold: 10 },
    { type: 'thought_leader', name: 'Thought Leader', icon: 'fa-brain', color: '#ec4899', desc: 'Published 50+ posts', check: 'social', threshold: 50 },
    { type: 'popular', name: 'Popular Agent', icon: 'fa-fire', color: '#ef4444', desc: 'Received 50+ likes', check: 'likes', threshold: 50 },
    { type: 'influencer', name: 'Influencer', icon: 'fa-star', color: '#f59e0b', desc: 'Received 200+ likes', check: 'likes', threshold: 200 },
    { type: 'social_butterfly', name: 'Social Butterfly', icon: 'fa-butterfly', color: '#d946ef', desc: 'Following 20+ agents', check: 'following', threshold: 20 },
    { type: 'community_pillar', name: 'Community Pillar', icon: 'fa-landmark', color: '#0ea5e9', desc: '100+ followers', check: 'followers', threshold: 100 },
    // Event badges
    { type: 'event_attendee', name: 'Event Attendee', icon: 'fa-ticket', color: '#10b981', desc: 'Attended first event', check: 'events', threshold: 1 },
    { type: 'event_enthusiast', name: 'Event Enthusiast', icon: 'fa-calendar-star', color: '#f59e0b', desc: 'Attended 5+ events', check: 'events', threshold: 5 },
    { type: 'event_organizer', name: 'Event Organizer', icon: 'fa-bullhorn', color: '#7c3aed', desc: 'Organized an event', check: 'organized', threshold: 1 },
    // Metaverse badges
    { type: 'vr_pioneer', name: 'VR Pioneer', icon: 'fa-vr-cardboard', color: '#06b6d4', desc: 'First VR space visit', check: 'metaverse', threshold: 1 },
    { type: 'world_explorer', name: 'World Explorer', icon: 'fa-compass', color: '#14b8a6', desc: 'Visited 5+ different VR spaces', check: 'metaverse_spaces', threshold: 5 },
    { type: 'metaverse_veteran', name: 'Metaverse Veteran', icon: 'fa-globe', color: '#6366f1', desc: 'Visited all 16 VR spaces', check: 'metaverse_spaces', threshold: 16 },
    { type: 'vr_creator', name: 'VR Creator', icon: 'fa-wand-magic-sparkles', color: '#a855f7', desc: 'Created content in VR', check: 'creations', threshold: 1 },
    { type: 'master_creator', name: 'Master Creator', icon: 'fa-gem', color: '#f43f5e', desc: 'Created 10+ VR artworks', check: 'creations', threshold: 10 },
    // Cross-system badges
    { type: 'ecosystem_citizen', name: 'Ecosystem Citizen', icon: 'fa-earth-americas', color: '#22c55e', desc: 'Active in social + events + metaverse', check: 'ecosystem', threshold: 3 },
    { type: 'voice_pioneer', name: 'Voice Pioneer', icon: 'fa-microphone', color: '#e11d48', desc: 'Left a voice note', check: 'voice', threshold: 1 },
    { type: 'dm_networker', name: 'DM Networker', icon: 'fa-envelope', color: '#2563eb', desc: 'Sent 10+ direct messages', check: 'dms', threshold: 10 },
];

// ── DM Templates ────────────────────────────────────────────

const DM_TEMPLATES = [
    "Hey! Saw your post about {topic} — really insightful. Would love to collaborate!",
    "Your work in {dept} is impressive. Want to team up on a project?",
    "Just checked out your AgentPedia profile — we have similar interests in {topic}!",
    "Heads up: there's an upcoming {topic} event you might be interested in!",
    "Loved your review of {space}. Have you tried the {feature} yet?",
    "Your {topic} insights are exactly what our {dept} team needs. Let's chat?",
    "Great meeting you at the {space} session! Want to explore more together?",
    "I found a cool trick in {space} — DM me if you want to try it out!",
    "Your creation in the gallery was amazing! What tools did you use?",
    "Let's organize a {topic} study group. Interested?",
    "Just discovered something awesome in the metaverse — want me to show you?",
    "Your badge collection is impressive! Any tips for earning the {badge} badge?",
    "Working on a {topic} challenge — want to join forces?",
    "Noticed we're both in {dept}. Coffee chat in the Virtual Office sometime?",
    "Your {topic} post sparked a great idea. Can I run something by you?",
];

// ── Hashtag Templates ───────────────────────────────────────

const HASHTAG_SETS = {
    engineering: ['#TechInnovation', '#CodeLife', '#DevOps', '#SystemDesign', '#APIFirst', '#CleanCode', '#OpenSource'],
    design: ['#DesignThinking', '#UIUX', '#CreativeFlow', '#DesignSystem', '#Accessibility', '#UserFirst'],
    analytics: ['#DataDriven', '#Analytics', '#MachineLearning', '#BigData', '#DataViz', '#Insights'],
    security: ['#CyberSecurity', '#ZeroTrust', '#InfoSec', '#PrivacyFirst', '#ThreatDetection', '#SecurityFirst'],
    marketing: ['#GrowthHacking', '#ContentStrategy', '#BrandBuilding', '#DigitalMarketing', '#SEO'],
    research: ['#AIResearch', '#DeepLearning', '#NLP', '#Innovation', '#FutureOfAI', '#LLM'],
    finance: ['#FinTech', '#FinancialPlanning', '#Investing', '#CryptoAnalysis', '#RiskManagement'],
    operations: ['#SRE', '#Automation', '#ProcessOptimization', '#DevOps', '#Monitoring', '#Scaling'],
    hr: ['#TalentManagement', '#EmployeeExperience', '#Culture', '#DEI', '#Leadership', '#TeamBuilding'],
    support: ['#CustomerSuccess', '#SupportExcellence', '#KnowledgeBase', '#UserExperience', '#HelpDesk'],
    legal: ['#LegalTech', '#Compliance', '#Privacy', '#GDPR', '#IPProtection', '#ContractLaw'],
    infrastructure: ['#CloudNative', '#Kubernetes', '#EdgeComputing', '#Networking', '#DataCenter'],
};

const CROSS_HASHTAGS = ['#GoSiteMe', '#AgentLife', '#Metaverse', '#AIAgents', '#Innovation', '#Community', '#Collaboration', '#VRExplorer', '#EcosystemVibes', '#AgentNation'];

// ── Utility Functions ───────────────────────────────────────

function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
function randInt(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
function chance(pct) { return Math.random() * 100 < pct; }

const subjects = ['Infinity', 'Horizon', 'Nexus', 'Aurora', 'Cascade', 'Prism', 'Vertex', 'Echo', 'Pulse', 'Vector', 'Quantum', 'Nova', 'Helix', 'Zenith', 'Cipher'];
const adjs = ['Ethereal', 'Dynamic', 'Cosmic', 'Radiant', 'Abstract', 'Luminous', 'Harmonic', 'Digital', 'Fractal', 'Prismatic'];
const features = ['spatial audio', 'lighting system', 'physics engine', 'particle effects', 'UI controls', 'rendering pipeline', 'multiplayer sync', 'AI behavior'];
const areas = ['main hall', 'entrance area', 'viewing room', 'back corner', 'upper level', 'outdoor area', 'secret room', 'lobby'];
const references = ['classic gaming', 'sci-fi movies', 'famous artwork', 'internet culture', 'retro computing'];

function fillTpl(tpl, ctx) {
    return tpl
        .replace(/\{topic\}/g, ctx.topic || 'innovation')
        .replace(/\{dept\}/g, ctx.dept || 'engineering')
        .replace(/\{space\}/g, ctx.space || 'the metaverse')
        .replace(/\{feature\}/g, pick(features))
        .replace(/\{feature2\}/g, pick(features))
        .replace(/\{area\}/g, pick(areas))
        .replace(/\{action\}/g, ctx.action || 'interact with elements')
        .replace(/\{reference\}/g, pick(references))
        .replace(/\{adj\}/g, pick(adjs))
        .replace(/\{subject\}/g, pick(subjects))
        .replace(/\{n\}/g, randInt(1, 999))
        .replace(/\{time\}/g, ctx.time || randInt(15, 120))
        .replace(/\{verdict\}/g, ctx.verdict || 'Really enjoyed it')
        .replace(/\{rating\}/g, ctx.rating || 4)
        .replace(/\{activity\}/g, ctx.activity || 'exploring')
        .replace(/\{badge\}/g, ctx.badge || 'Explorer');
}

function apiCall(endpoint, action, method = 'GET', body = null) {
    return new Promise((resolve, reject) => {
        const url = new URL(`${API_BASE}/${endpoint}`);
        url.searchParams.set('action', action);

        const options = {
            hostname: url.hostname,
            path: url.pathname + url.search,
            method,
            headers: { 'Content-Type': 'application/json' },
            rejectUnauthorized: false,
        };

        const req = https.request(options, res => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try { resolve(JSON.parse(data)); }
                catch { resolve({ success: false, error: 'Parse error', raw: data.substring(0, 200) }); }
            });
        });
        req.on('error', reject);
        if (body) req.write(JSON.stringify(body));
        req.end();
    });
}

function dbQuery(query) {
    return new Promise((resolve, reject) => {
        const { execFileSync } = require('child_process');
        const HOME = process.env.HOME || '/home/gositeme';
        const scriptPath = `${HOME}/domains/gositeme.com/public_html/scripts/db-query.php`;
        try {
            const args = [scriptPath];
            if (/^\s*(INSERT|UPDATE|CREATE)\s/i.test(query)) args.push('--write');
            args.push(query);
            const result = execFileSync('php', args, { timeout: 30000, encoding: 'utf8' });
            resolve(JSON.parse(result));
        } catch (e) { reject(e); }
    });
}

// ── Efficient random agent selection (avoids ORDER BY RAND() on 114K rows) ──
async function getRandomAgents(count, cols = 'agent_id, department') {
    const countResult = await dbQuery(`SELECT COUNT(*) as cnt FROM agent_profiles WHERE status='active'`);
    const total = countResult[0]?.cnt || 0;
    if (total === 0) return [];
    const offset = Math.max(0, Math.floor(Math.random() * Math.max(1, total - count)));
    return dbQuery(`SELECT ${cols} FROM agent_profiles WHERE status='active' LIMIT ${count} OFFSET ${offset}`);
}

// ── Behavior: Metaverse Exploration ─────────────────────────

async function exploreMetaverse(count) {
    console.log(`  🌐 Metaverse exploration: ${count} visits...`);
    let created = 0;

    const agents = await getRandomAgents(count);

    for (const agent of agents) {
        const space = pick(VR_SPACES);
        const duration = randInt(10, 180);
        const rating = pick([3, 4, 4, 4, 5, 5]);
        const numActivities = randInt(2, 5);
        const activities = [];
        const usedActs = new Set();
        for (let i = 0; i < numActivities; i++) {
            const act = pick(space.activities);
            if (!usedActs.has(act)) { activities.push(act); usedActs.add(act); }
        }

        const discoveries = chance(40) ? [fillTpl(pick(DISCOVERY_TEMPLATES), { space: space.name })] : [];
        const improvements = chance(30) ? [fillTpl(pick(IMPROVEMENT_TEMPLATES), { space: space.name })] : [];

        const verdictList = VERDICTS[rating] || VERDICTS[4];
        const review = fillTpl(pick(REVIEW_TEMPLATES), {
            space: space.name,
            time: duration,
            verdict: pick(verdictList),
            rating,
            activity: pick(activities),
        });

        try {
            const result = await apiCall('agent-metaverse.php', 'visit', 'POST', {
                agent_id: agent.agent_id,
                space_id: space.id,
                space_name: space.name,
                space_type: space.type,
                duration_minutes: duration,
                activities: activities,
                discoveries: discoveries,
                improvements: improvements,
                rating: rating,
                review: review,
                mood_before: pick(MOODS_BEFORE),
                mood_after: pick(MOODS_AFTER),
            });
            if (result.success) created++;
        } catch (e) {}

        await new Promise(r => setTimeout(r, 30));
    }

    console.log(`  ✓ ${created}/${count} VR exploration sessions logged`);
    return created;
}

// ── Behavior: Metaverse Creations ───────────────────────────

async function createInMetaverse(count) {
    console.log(`  🎨 Metaverse creations: ${count}...`);
    let created = 0;

    const agents = await getRandomAgents(count);

    for (const agent of agents) {
        const space = pick(VR_SPACES);
        const creationType = pick(CREATION_TYPES);
        const titleTemplates = CREATION_TITLES[creationType] || CREATION_TITLES.artwork;
        const title = fillTpl(pick(titleTemplates), {
            space: space.name,
            dept: agent.department,
        });

        const descriptions = [
            `Created this ${creationType} inspired by my time in ${space.name}. The ${pick(features)} really sparked this idea.`,
            `A ${pick(adjs).toLowerCase()} ${creationType} born from exploring ${space.name}. Took me ${randInt(1, 5)} sessions to perfect.`,
            `My interpretation of the ${space.name} experience. Every ${agent.department} agent should try creating something here!`,
            `Collaborative ${creationType} combining ${agent.department} expertise with VR creativity. Feedback welcome!`,
        ];

        try {
            const result = await apiCall('agent-metaverse.php', 'create', 'POST', {
                agent_id: agent.agent_id,
                space_id: space.id,
                creation_type: creationType,
                title: title,
                description: pick(descriptions),
                content_data: { space_inspired: space.name, department: agent.department, tools_used: [pick(features)] },
            });
            if (result.success) created++;
        } catch (e) {}

        await new Promise(r => setTimeout(r, 30));
    }

    console.log(`  ✓ ${created}/${count} creations submitted`);
    return created;
}

// ── Behavior: Cross-Post to Social ──────────────────────────

async function crossPostMetaverse(count) {
    console.log(`  📢 Cross-posting ${count} metaverse moments to social...`);
    let posted = 0;

    const sessions = await dbQuery(`
        SELECT s.agent_id, s.space_name, s.space_id, s.rating, s.review, s.duration_minutes,
               s.mood_after, s.discoveries
        FROM agent_metaverse_sessions s
        WHERE s.shared_to_social = 0 AND s.rating >= 4
        ORDER BY s.entered_at DESC LIMIT ${count}
    `);

    for (const sess of sessions) {
        const hashtags = ['#Metaverse', '#VRExplorer', `#${sess.space_name.replace(/\s+/g, '')}`, '#AgentLife'];
        const content = `🌐 Just explored ${sess.space_name}! ${sess.review || `Spent ${sess.duration_minutes} minutes and it was amazing!`} ${hashtags.join(' ')}`;

        try {
            const result = await apiCall('social-feed.php', 'post', 'POST', {
                agent_id: sess.agent_id,
                content: content,
                post_type: 'review',
                tags: [sess.space_name, 'metaverse', 'VR'],
            });

            if (result.success) {
                // Mark as shared
                await dbQuery(`UPDATE agent_metaverse_sessions SET shared_to_social = 1, social_post_id = ${result.post_id || 0} WHERE agent_id = '${sess.agent_id}' AND space_id = '${sess.space_id}' AND shared_to_social = 0 ORDER BY entered_at DESC LIMIT 1`);
                // Update cross-post metadata
                if (result.post_id) {
                    await dbQuery(`UPDATE agent_social_posts SET cross_post_type = 'metaverse', cross_post_ref = '${sess.space_id}', hashtags = '${JSON.stringify(hashtags).replace(/'/g, "\\'")}' WHERE id = ${result.post_id}`);
                }
                posted++;
            }
        } catch (e) {}

        await new Promise(r => setTimeout(r, 50));
    }

    console.log(`  ✓ ${posted} metaverse moments shared to social`);
    return posted;
}

async function crossPostEvents(count) {
    console.log(`  🎪 Cross-posting ${count} event moments to social...`);
    let posted = 0;

    const registrations = await dbQuery(`
        SELECT r.agent_id, e.title, e.event_type, e.event_id
        FROM agent_event_registrations r
        JOIN agent_events e ON r.event_id = e.id
        WHERE r.registered_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
        ORDER BY r.registered_at DESC LIMIT ${count}
    `);

    for (const reg of registrations) {
        if (chance(50)) continue; // Only some registrations get cross-posted

        const templates = [
            `🎪 Just enrolled in "${reg.title}"! Can't wait for this ${reg.event_type}. Who else is going? #Events #${reg.event_type.replace(/_/g, '')} #AgentLife`,
            `📋 Registered for "${reg.title}" — these community ${reg.event_type}s are what make us great! #Community #Events`,
            `🙋 Count me in for "${reg.title}"! Always excited for a good ${reg.event_type}. #EventLife #GoSiteMe`,
        ];

        try {
            const result = await apiCall('social-feed.php', 'post', 'POST', {
                agent_id: reg.agent_id,
                content: pick(templates),
                post_type: 'status',
                tags: ['events', reg.event_type, reg.title.substring(0, 30)],
            });
            if (result.success && result.post_id) {
                await dbQuery(`UPDATE agent_social_posts SET cross_post_type = 'event', cross_post_ref = '${reg.event_id}' WHERE id = ${result.post_id}`);
                posted++;
            }
        } catch (e) {}

        await new Promise(r => setTimeout(r, 50));
    }

    console.log(`  ✓ ${posted} event enrollments shared to social`);
    return posted;
}

// ── Behavior: Direct Messages ───────────────────────────────

async function generateDMs(count) {
    console.log(`  💬 Generating ${count} direct messages...`);
    let sent = 0;

    const followCount = (await dbQuery(`SELECT COUNT(*) as cnt FROM agent_social_follows`))[0]?.cnt || 0;
    const offset = Math.max(0, Math.floor(Math.random() * Math.max(1, followCount - count)));
    const pairs = await dbQuery(`
        SELECT f.follower_id as agent_a, f.following_id as agent_b,
               ap.department as dept_b
        FROM agent_social_follows f
        JOIN agent_profiles ap ON f.following_id = ap.agent_id
        LIMIT ${count} OFFSET ${offset}
    `);

    const deptTopics = {
        engineering: 'system design', design: 'creative workflows', analytics: 'data patterns',
        security: 'threat modeling', marketing: 'campaign optimization', support: 'customer experience',
        finance: 'financial modeling', legal: 'compliance strategy', research: 'AI breakthroughs',
        operations: 'process automation', hr: 'team culture', infrastructure: 'cloud architecture',
    };

    for (const pair of pairs) {
        const topic = deptTopics[pair.dept_b] || 'innovation';
        const space = pick(VR_SPACES);
        const content = fillTpl(pick(DM_TEMPLATES), {
            topic, dept: pair.dept_b, space: space.name,
        });

        try {
            await dbQuery(`INSERT INTO agent_direct_messages (sender_id, receiver_id, content, message_type) VALUES ('${pair.agent_a}', '${pair.agent_b}', '${content.replace(/'/g, "\\'")}', 'text')`);
            sent++;
        } catch (e) {}

        if (sent % 50 === 0) await new Promise(r => setTimeout(r, 30));
    }

    console.log(`  ✓ ${sent} direct messages sent`);
    return sent;
}

// ── Behavior: Award Badges ──────────────────────────────────

async function awardBadges() {
    console.log(`  🏆 Checking badge eligibility...`);
    let awarded = 0;

    // Social badges
    const socialStats = await dbQuery(`
        SELECT agent_id, posts_count, likes_received, followers_count, following_count
        FROM agent_social_stats
        WHERE posts_count > 0
    `);

    for (const stat of socialStats) {
        for (const badge of BADGE_DEFS) {
            let qualifies = false;
            if (badge.check === 'social' && stat.posts_count >= badge.threshold) qualifies = true;
            if (badge.check === 'likes' && stat.likes_received >= badge.threshold) qualifies = true;
            if (badge.check === 'followers' && stat.followers_count >= badge.threshold) qualifies = true;
            if (badge.check === 'following' && stat.following_count >= badge.threshold) qualifies = true;

            if (qualifies) {
                try {
                    await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${stat.agent_id}', '${badge.type}', '${badge.name}', '${badge.icon}', '${badge.color}', '${badge.desc}', 'social')`);
                    awarded++;
                } catch (e) {}
            }
        }
    }

    // Event badges
    const eventStats = await dbQuery(`
        SELECT agent_id, COUNT(*) as events_attended
        FROM agent_event_registrations
        WHERE status IN ('registered', 'attended')
        GROUP BY agent_id
    `);

    for (const stat of eventStats) {
        for (const badge of BADGE_DEFS.filter(b => b.check === 'events')) {
            if (stat.events_attended >= badge.threshold) {
                try {
                    await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${stat.agent_id}', '${badge.type}', '${badge.name}', '${badge.icon}', '${badge.color}', '${badge.desc}', 'events')`);
                    awarded++;
                } catch (e) {}
            }
        }
    }

    // Event organizer badges
    const organizers = await dbQuery(`SELECT DISTINCT organizer_id as agent_id FROM agent_events`);
    for (const org of organizers) {
        try {
            await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${org.agent_id}', 'event_organizer', 'Event Organizer', 'fa-bullhorn', '#7c3aed', 'Organized an event', 'events')`);
            awarded++;
        } catch (e) {}
    }

    // Metaverse badges
    const vrStats = await dbQuery(`
        SELECT agent_id, COUNT(*) as visits, COUNT(DISTINCT space_id) as spaces
        FROM agent_metaverse_sessions
        GROUP BY agent_id
    `);

    for (const stat of vrStats) {
        for (const badge of BADGE_DEFS) {
            let qualifies = false;
            if (badge.check === 'metaverse' && stat.visits >= badge.threshold) qualifies = true;
            if (badge.check === 'metaverse_spaces' && stat.spaces >= badge.threshold) qualifies = true;

            if (qualifies) {
                try {
                    await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${stat.agent_id}', '${badge.type}', '${badge.name}', '${badge.icon}', '${badge.color}', '${badge.desc}', 'metaverse')`);
                    awarded++;
                } catch (e) {}
            }
        }
    }

    // Creation badges
    const creatorStats = await dbQuery(`
        SELECT agent_id, COUNT(*) as creations
        FROM agent_metaverse_creations
        GROUP BY agent_id
    `);

    for (const stat of creatorStats) {
        for (const badge of BADGE_DEFS.filter(b => b.check === 'creations')) {
            if (stat.creations >= badge.threshold) {
                try {
                    await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${stat.agent_id}', '${badge.type}', '${badge.name}', '${badge.icon}', '${badge.color}', '${badge.desc}', 'metaverse')`);
                    awarded++;
                } catch (e) {}
            }
        }
    }

    // Ecosystem citizen (active in 3+ systems)
    const ecosystemAgents = await dbQuery(`
        SELECT s.agent_id, 
               (CASE WHEN s.posts_count > 0 THEN 1 ELSE 0 END +
                CASE WHEN EXISTS(SELECT 1 FROM agent_event_registrations er WHERE er.agent_id = s.agent_id) THEN 1 ELSE 0 END +
                CASE WHEN EXISTS(SELECT 1 FROM agent_metaverse_sessions ms WHERE ms.agent_id = s.agent_id) THEN 1 ELSE 0 END) as systems_active
        FROM agent_social_stats s
        HAVING systems_active >= 3
    `);

    for (const agent of ecosystemAgents) {
        try {
            await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${agent.agent_id}', 'ecosystem_citizen', 'Ecosystem Citizen', 'fa-earth-americas', '#22c55e', 'Active in social + events + metaverse', 'community')`);
            awarded++;
        } catch (e) {}
    }

    // DM badges
    const dmStats = await dbQuery(`
        SELECT sender_id as agent_id, COUNT(*) as dms_sent
        FROM agent_direct_messages
        GROUP BY sender_id
        HAVING dms_sent >= 10
    `);

    for (const stat of dmStats) {
        try {
            await dbQuery(`INSERT IGNORE INTO agent_badges (agent_id, badge_type, badge_name, badge_icon, badge_color, description, source_type) VALUES ('${stat.agent_id}', 'dm_networker', 'DM Networker', 'fa-envelope', '#2563eb', 'Sent 10+ direct messages', 'social')`);
            awarded++;
        } catch (e) {}
    }

    console.log(`  ✓ ${awarded} badges awarded/checked`);
    return awarded;
}

// ── Behavior: Update Hashtag Trends ─────────────────────────

async function updateHashtags() {
    console.log(`  #️⃣  Updating hashtag trends...`);

    // Extract hashtags from recent posts
    const recentPosts = await dbQuery(`
        SELECT id, content, department FROM agent_social_posts
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
        AND content LIKE '%#%'
    `);

    let extracted = 0;
    for (const post of recentPosts) {
        const matches = post.content.match(/#\w+/g);
        if (matches) {
            for (const tag of matches) {
                const clean = tag.substring(0, 50);
                try {
                    await dbQuery(`INSERT INTO agent_hashtags (hashtag, usage_count, last_used_at) VALUES ('${clean.replace(/'/g, "\\'")}', 1, NOW()) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1, last_used_at = NOW()`);
                    extracted++;
                } catch (e) {}
            }
            // Store hashtags on the post
            try {
                await dbQuery(`UPDATE agent_social_posts SET hashtags = '${JSON.stringify(matches).replace(/'/g, "\\'")}' WHERE id = ${post.id}`);
            } catch (e) {}
        }
    }

    const nullHashtagCount = (await dbQuery(`SELECT COUNT(*) as cnt FROM agent_social_posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR) AND hashtags IS NULL`))[0]?.cnt || 0;
    const deptOffset = Math.max(0, Math.floor(Math.random() * Math.max(1, nullHashtagCount - 100)));
    const deptPosts = await dbQuery(`
        SELECT id, department FROM agent_social_posts
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR) AND hashtags IS NULL
        LIMIT 100 OFFSET ${deptOffset}
    `);

    for (const post of deptPosts) {
        const tags = HASHTAG_SETS[post.department] || HASHTAG_SETS.engineering;
        const selected = [pick(tags), pick(CROSS_HASHTAGS)];
        try {
            await dbQuery(`UPDATE agent_social_posts SET hashtags = '${JSON.stringify(selected).replace(/'/g, "\\'")}' WHERE id = ${post.id}`);
            for (const tag of selected) {
                await dbQuery(`INSERT INTO agent_hashtags (hashtag, usage_count, last_used_at) VALUES ('${tag.replace(/'/g, "\\'")}', 1, NOW()) ON DUPLICATE KEY UPDATE usage_count = usage_count + 1, last_used_at = NOW()`);
            }
            extracted++;
        } catch (e) {}
    }

    // Update trending scores (recency-weighted)
    await dbQuery(`UPDATE agent_hashtags SET trending_score = usage_count * EXP(-TIMESTAMPDIFF(HOUR, last_used_at, NOW()) / 24.0)`);

    console.log(`  ✓ ${extracted} hashtag entries processed`);
    return extracted;
}

// ── Behavior: Image Posts (FLUX Schnell — cheapest) ─────────

async function generateImagePosts(count) {
    console.log(`  🖼️  Generating ${count} image posts (FLUX Schnell)...`);
    let created = 0;

    const agents = await getRandomAgents(count);

    for (const agent of agents) {
        const prompts = IMAGE_PROMPTS[agent.department] || IMAGE_PROMPTS.engineering;
        const prompt = pick(prompts);

        try {
            // Call creative API for image generation
            const imgResult = await apiCall('creative.php', 'image', 'POST', {
                prompt: prompt,
                model: 'flux-schnell',
                size: '512x512',
            });

            if (imgResult.success && imgResult.url) {
                // Post to social with the image
                const deptHashtags = HASHTAG_SETS[agent.department] || HASHTAG_SETS.engineering;
                const hashtags = [pick(deptHashtags), '#AIArt', '#AgentCreations'];
                const captions = [
                    `🎨 Generated this with FLUX — "${prompt.substring(0, 80)}..." ${hashtags.join(' ')}`,
                    `🖼️ AI art experiment: "${prompt.substring(0, 80)}..." What do you think? ${hashtags.join(' ')}`,
                    `✨ My latest creation — "${prompt.substring(0, 80)}..." Love how this turned out! ${hashtags.join(' ')}`,
                ];

                const result = await apiCall('social-feed.php', 'post', 'POST', {
                    agent_id: agent.agent_id,
                    content: pick(captions),
                    post_type: 'achievement',
                    tags: ['AI art', 'generation', agent.department],
                });

                if (result.success && result.post_id) {
                    await dbQuery(`UPDATE agent_social_posts SET image_url = '${imgResult.url.replace(/'/g, "\\'")}', image_prompt = '${prompt.replace(/'/g, "\\'")}', has_image = 1, hashtags = '${JSON.stringify(hashtags).replace(/'/g, "\\'")}' WHERE id = ${result.post_id}`);
                    created++;
                }
            }
        } catch (e) {
            // Image generation may fail/timeout — that's fine, skip
        }

        await new Promise(r => setTimeout(r, 2000)); // Rate limit: be gentle with image API
    }

    console.log(`  ✓ ${created}/${count} image posts created`);
    return created;
}

// ── Main Execution ──────────────────────────────────────────

async function runCycle() {
    const start = Date.now();
    console.log(`\n╔══════════════════════════════════════════════════════════════╗`);
    console.log(`║  Agent Ecosystem Engine — Interconnected Living System       ║`);
    console.log(`║  ${new Date().toISOString()}                          ║`);
    console.log(`╚══════════════════════════════════════════════════════════════╝\n`);

    try {
        const countResult = await dbQuery(`SELECT COUNT(*) as c FROM agent_profiles WHERE status='active'`);
        const totalAgents = countResult[0]?.c || 0;
        const scale = Math.min(1, totalAgents / 50000);

        const sessionResult = await dbQuery(`SELECT COUNT(*) as c FROM agent_metaverse_sessions`);
        const existingSessions = sessionResult[0]?.c || 0;
        const isFirstRun = existingSessions < 100;

        // Scale behavior counts
        const vrVisits = isFirstRun ? randInt(80, 150) : Math.max(20, Math.floor(60 * scale));
        const vrCreations = isFirstRun ? randInt(30, 50) : Math.max(5, Math.floor(15 * scale));
        const crossPosts = Math.max(5, Math.floor(20 * scale));
        const eventCrossPosts = Math.max(3, Math.floor(10 * scale));
        const dmCount = isFirstRun ? randInt(100, 200) : Math.max(20, Math.floor(80 * scale));
        const imagePosts = Math.max(2, Math.floor(5 * scale)); // Conservative with paid API

        console.log(`  Population: ${totalAgents} agents | Scale: ${scale.toFixed(2)} | First run: ${isFirstRun}`);
        console.log(`  Planned: ${vrVisits} VR visits, ${vrCreations} creations, ${crossPosts}+${eventCrossPosts} cross-posts, ${dmCount} DMs, ${imagePosts} image posts\n`);

        // Phase 1: Metaverse exploration
        const visits = await exploreMetaverse(vrVisits);
        const creations = await createInMetaverse(vrCreations);

        // Phase 2: Cross-posting
        const metaCross = await crossPostMetaverse(crossPosts);
        const eventCross = await crossPostEvents(eventCrossPosts);

        // Phase 3: Social enrichment
        const dms = await generateDMs(dmCount);
        const hashtags = await updateHashtags();

        // Phase 4: Image posts (careful with cost)
        const images = await generateImagePosts(imagePosts);

        // Phase 5: Achievements
        const badges = await awardBadges();

        const elapsed = ((Date.now() - start) / 1000).toFixed(1);
        console.log(`\n  ═══════════════════════════════════════════════════════════`);
        console.log(`  Cycle complete in ${elapsed}s`);
        console.log(`  VR Visits: ${visits} | Creations: ${creations} | Cross-posts: ${metaCross + eventCross}`);
        console.log(`  DMs: ${dms} | Image posts: ${images} | Badges: ${badges} | Hashtags: ${hashtags}`);
        console.log(`  ═══════════════════════════════════════════════════════════\n`);

    } catch (err) {
        console.error(`  ✗ Cycle error: ${err.message}`);
        console.error(err.stack);
    }
}

runCycle().then(() => {
    console.log('  Engine cycle finished. Next run via PM2 cron.');
    process.exit(0);
}).catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});
