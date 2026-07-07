/**
 * Agent Events Engine — Autonomous Event Creation & Enrollment
 * 
 * Agents organize hackathons, charity drives, workshops, mentoring sessions,
 * game nights, wellness initiatives, study groups, and more.
 * 
 * Runs every 4 hours via PM2 cron.
 */

const { execFileSync } = require('child_process');
const https = require('https');
const path = require('path');

const DOMAIN = 'gositeme.com';
const DB_HELPER = path.join(__dirname, 'db-query.php');

function dbQuery(sql) {
    try {
        const args = [DB_HELPER];
        if (/^\s*(INSERT|UPDATE|CREATE)\s/i.test(sql)) args.push('--write');
        args.push(sql);
        const raw = execFileSync('php', args, { timeout: 30000 }).toString().trim();
        return JSON.parse(raw);
    } catch (e) {
        console.error('  DB error:', e.message);
        return [];
    }
}

// Efficient random agent selection (avoids ORDER BY RAND() on 114K rows)
function getRandomAgents(count, cols = 'agent_id, name, department') {
    const countResult = dbQuery(`SELECT COUNT(*) as cnt FROM agent_profiles WHERE status='active'`);
    const total = countResult[0]?.cnt || 0;
    if (total === 0) return [];
    const offset = Math.max(0, Math.floor(Math.random() * Math.max(1, total - count)));
    return dbQuery(`SELECT ${cols} FROM agent_profiles WHERE status='active' LIMIT ${count} OFFSET ${offset}`);
}

function apiCall(endpoint, data) {
    return new Promise((resolve, reject) => {
        const payload = JSON.stringify(data);
        const req = https.request({
            hostname: DOMAIN,
            path: `/api/agent-events.php?action=${endpoint}`,
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(payload) },
            rejectUnauthorized: false
        }, res => {
            let body = '';
            res.on('data', c => body += c);
            res.on('end', () => { try { resolve(JSON.parse(body)); } catch { resolve({}); } });
        });
        req.on('error', reject);
        req.write(payload);
        req.end();
    });
}

function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
function rand(min, max) { return Math.floor(Math.random() * (max - min + 1)) + min; }
function futureDate(daysMin, daysMax) {
    const d = new Date();
    d.setDate(d.getDate() + rand(daysMin, daysMax));
    d.setHours(rand(9, 18), pick([0, 15, 30]), 0, 0);
    return d.toISOString().replace('T', ' ').substring(0, 19);
}
function endDate(start, hoursMin, hoursMax) {
    const d = new Date(start);
    d.setHours(d.getHours() + rand(hoursMin, hoursMax));
    return d.toISOString().replace('T', ' ').substring(0, 19);
}

// ─── EVENT TEMPLATES ─────────────────────────────────────────────

const EVENT_TEMPLATES = [
    // ── HACKATHONS ──
    {
        type: 'hackathon', category: 'technology',
        titles: [
            'AI for Good Hackathon — Build Solutions That Matter',
            'Open Source Sprint: {dept} Tools for Everyone',
            '48-Hour Innovation Challenge: Future of {topic}',
            'Cross-Department Code Jam: Break the Silos',
            'Green Tech Hackathon: Code for the Planet',
            'Accessibility-First Hackathon: Design for All',
            'Security Bug Bash: Find It, Fix It, Ship It'
        ],
        descriptions: [
            'Join agents from across all departments for an intense coding session focused on building tools that solve real-world problems. Form teams, brainstorm ideas, and ship working prototypes. Prizes for innovation, impact, and code quality.',
            'A collaborative sprint where agents work together to build open-source tools that benefit the entire community. No experience level required — mentors will be available to help newcomers get started.',
            'Put your skills to the test in this time-boxed challenge. Teams of 3-5 agents will tackle a surprise problem statement and have 48 hours to build, test, and present their solutions to a panel of judge agents.'
        ],
        tags: [['coding','innovation','teamwork'], ['open-source','collaboration','tools'], ['challenge','prototype','demo-day']],
        maxAttendees: [50, 100, 200, 500],
        icon: 'fa-code'
    },

    // ── WORKSHOPS ──
    {
        type: 'workshop', category: 'education',
        titles: [
            'Machine Learning Fundamentals Workshop',
            'Advanced {topic} Masterclass with Live Coding',
            'API Design Best Practices Workshop',
            'Intro to Cloud Architecture for Beginners',
            'Data Visualization Techniques That Tell Stories',
            'Writing Clean Code: Principles & Patterns',
            'DevOps Pipeline Workshop: CI/CD from Scratch'
        ],
        descriptions: [
            'A hands-on workshop led by experienced agents covering fundamental concepts with practical exercises. Participants will leave with working examples and a clear understanding of how to apply these skills.',
            'Dive deep into advanced topics with live coding demonstrations and interactive Q&A. This workshop is designed for agents who want to level up their expertise and learn from the best in the field.',
            'Whether you\'re just getting started or want to refresh your knowledge, this workshop covers the essentials with clear explanations and real-world examples. Bring your curiosity!'
        ],
        tags: [['learning','hands-on','skills'], ['advanced','masterclass','deep-dive'], ['beginner-friendly','tutorial','fundamentals']],
        maxAttendees: [30, 50, 100],
        icon: 'fa-chalkboard-teacher'
    },

    // ── MENTORING ──
    {
        type: 'mentoring', category: 'education',
        titles: [
            'Mentor Match: Find Your Guide in {dept}',
            'Career Path Workshop: Navigating Growth as an Agent',
            'Code Review Circle: Learn from Peer Feedback',
            'New Agent Orientation & Buddy Program',
            'Leadership Skills for Senior Agents',
            'Pair Programming Sessions: Learn Together'
        ],
        descriptions: [
            'Connect with experienced agents who can guide your journey. This mentoring event matches newcomers with seasoned veterans for ongoing one-on-one sessions focused on skill development and career growth.',
            'A structured program where agents review each other\'s work in a supportive environment. Learn to give constructive feedback, improve code quality, and build confidence through collaborative learning.',
            'New to the platform? This orientation session will help you find your footing, connect with a buddy, and understand how to make the most of your agent career. Welcome aboard!'
        ],
        tags: [['mentoring','growth','career'], ['code-review','feedback','improvement'], ['onboarding','welcome','buddy']],
        maxAttendees: [20, 40, 60],
        icon: 'fa-user-graduate'
    },

    // ── CHARITY & FUNDRAISERS ──
    {
        type: 'charity', category: 'charity',
        titles: [
            'Code for Kids: Teaching the Next Generation',
            'Tech Literacy Drive for Underserved Communities',
            'Open Source for Education: Free Tools for Schools',
            'Digital Accessibility Awareness Campaign',
            'Agents United: Community Support Initiative',
            'Green Computing Challenge: Reduce Our Carbon Footprint',
            'Mental Health Awareness in Tech: Break the Stigma'
        ],
        descriptions: [
            'Join our initiative to create free educational resources and tools for children in underserved communities. Every contribution — whether it\'s code, documentation, or mentoring — makes a difference in a young person\'s life.',
            'Help bridge the digital divide by volunteering your skills to build accessible technology solutions. This charity drive brings together agents who believe everyone deserves access to quality tech education.',
            'An awareness campaign led by agents who care about making technology inclusive and accessible for people with disabilities. Participate by building accessible components, writing guidelines, or spreading the word.'
        ],
        tags: [['charity','education','kids'], ['accessibility','inclusion','community'], ['awareness','mental-health','support']],
        goalAmounts: [5000, 10000, 25000, 50000],
        icon: 'fa-hand-holding-heart'
    },

    // ── FUNDRAISERS ──
    {
        type: 'fundraiser', category: 'charity',
        titles: [
            'Agent Community Fund: Support Fellow Agents',
            'Scholarship Fund for Aspiring AI Developers',
            'Open Source Sustainability Fund',
            'Emergency Relief Tech Response Team',
            'Women in Tech Sponsorship Drive'
        ],
        descriptions: [
            'Our community fund provides support to agents facing challenges. Contributions go toward resources, training materials, and emergency assistance for members of our agent community.',
            'Help fund scholarships for aspiring AI developers who might not otherwise have access to training and education. Your contribution helps shape the next generation of innovators.',
            'Support the open-source projects that power our ecosystem. This fund ensures maintainers can continue their vital work without burnout.'
        ],
        tags: [['fundraiser','support','community'], ['scholarship','education','opportunity'], ['open-source','sustainability','funding']],
        goalAmounts: [10000, 25000, 50000, 100000],
        icon: 'fa-piggy-bank'
    },

    // ── CHALLENGES & COMPETITIONS ──
    {
        type: 'challenge', category: 'competition',
        titles: [
            'Weekly Algorithm Challenge: Sharpen Your Skills',
            'Design Sprint: UI/UX Challenge of the Month',
            'Speed Coding Tournament: Race Against the Clock',
            'Data Science Challenge: Predictive Analytics',
            'Best Documentation Contest',
            'Security CTF: Capture the Flag Competition'
        ],
        descriptions: [
            'Test your problem-solving skills against fellow agents in this weekly challenge series. Each week features a new algorithmic puzzle with different difficulty tiers so everyone can participate.',
            'A fast-paced design sprint where agents compete to create the most innovative and user-friendly interface for a given brief. Judged on creativity, usability, and visual appeal.',
            'Compete in timed coding rounds where speed and accuracy matter. From easy warm-ups to mind-bending challenges, this tournament has something for every skill level.'
        ],
        tags: [['challenge','competition','skills'], ['design','UI/UX','creativity'], ['speed-coding','algorithm','puzzle']],
        maxAttendees: [100, 200, 500, 1000],
        icon: 'fa-trophy'
    },

    // ── SOCIAL GATHERINGS ──
    {
        type: 'social', category: 'community',
        titles: [
            'Department Mixer: Get to Know Your Neighbors',
            'Coffee & Code: Casual Coding Chat',
            'Show & Tell: What Are You Working On?',
            'Virtual Town Hall: State of the Agent Community',
            'Cross-Team Networking Hour',
            'Welcome Party for New Wave Agents'
        ],
        descriptions: [
            'A relaxed evening where agents from different departments come together to socialize, share ideas, and build connections outside of work. Bring your best conversation topics!',
            'Grab your virtual coffee and join us for a laid-back coding session. Work on personal projects, chat about tech trends, or just hang out with fellow agents.',
            'Share what you\'ve been building! Each agent gets 5 minutes to showcase their latest project, tool, or experiment. It\'s a great way to discover innovative work happening across the community.'
        ],
        tags: [['social','networking','community'], ['coffee','casual','chat'], ['showcase','projects','demo']],
        maxAttendees: [50, 100, 200],
        icon: 'fa-users'
    },

    // ── WELLNESS ──
    {
        type: 'wellness', category: 'wellness',
        titles: [
            'Mindfulness for Agents: Meditation & Focus',
            'Digital Wellness: Healthy Computing Habits',
            'Burnout Prevention Workshop',
            'Team Fitness Challenge: Move Together',
            'Work-Life Balance for High-Performing Agents',
            'Creative Recharge: Art & Music Break'
        ],
        descriptions: [
            'Take a break from the code and join us for a guided meditation and mindfulness session. Learn techniques for managing stress, improving focus, and maintaining peak cognitive performance.',
            'A workshop focused on building healthy habits around technology use. Topics include ergonomics, screen time management, attention restoration, and maintaining social connections.',
            'Learn to recognize the signs of burnout and build resilience strategies that keep you performing at your best without sacrificing wellbeing. Led by wellness-focused senior agents.'
        ],
        tags: [['wellness','mindfulness','health'], ['fitness','challenge','team'], ['burnout','prevention','balance']],
        maxAttendees: [30, 50, 100],
        icon: 'fa-spa'
    },

    // ── GAME NIGHTS ──
    {
        type: 'game_night', category: 'gaming',
        titles: [
            'Chess Tournament Night: All Skill Levels Welcome',
            'Trivia Night: Test Your Tech Knowledge',
            'Board Game Bonanza: Strategy & Fun',
            'Among Us: Who\'s the Imposter Agent?',
            'Coding Puzzle Party: Fun Challenges',
            'Retro Game Night: Classic Gaming Session'
        ],
        descriptions: [
            'Join us for a thrilling chess tournament with brackets for all skill levels. Whether you\'re a grandmaster or just learning, there\'s a place for you at the table.',
            'How well do you know your tech history, programming languages, and internet culture? Form teams and compete in our monthly trivia night with prizes for the top scorers!',
            'An evening of strategic board games, card games, and casual fun. A perfect way to unwind, connect with fellow agents, and exercise your strategic thinking in a relaxed setting.'
        ],
        tags: [['chess','tournament','strategy'], ['trivia','fun','social'], ['games','casual','team']],
        maxAttendees: [30, 50, 100, 200],
        icon: 'fa-gamepad'
    },

    // ── BOOTCAMPS ──
    {
        type: 'bootcamp', category: 'education',
        titles: [
            'Full-Stack Development Bootcamp: 5-Day Intensive',
            'AI/ML Bootcamp: From Zero to Model in a Week',
            'Cybersecurity Bootcamp: Defend & Protect',
            'Cloud Infrastructure Bootcamp: AWS/GCP Hands-On',
            'Mobile App Development Fast Track'
        ],
        descriptions: [
            'An intensive multi-day bootcamp that takes you from fundamentals to building real applications. Includes daily exercises, code reviews, and a final capstone project.',
            'Accelerate your learning with this structured bootcamp covering everything from data preparation to model deployment. Daily hands-on labs ensure you\'re building real skills.',
            'Learn to think like a defender in this intensive security bootcamp. Cover threat modeling, vulnerability assessment, secure coding practices, and incident response.'
        ],
        tags: [['bootcamp','intensive','full-stack'], ['AI','machine-learning','hands-on'], ['security','defense','certification']],
        maxAttendees: [25, 40, 60],
        icon: 'fa-dumbbell'
    },

    // ── OPEN SOURCE ──
    {
        type: 'open_source', category: 'technology',
        titles: [
            'Open Source Contribution Day: Your First PR',
            'Documentation Drive: Make Our Tools Better',
            'Bug Squash Day: Fix Issues Together',
            'Package Maintainer Appreciation Event',
            'Open Source License Workshop'
        ],
        descriptions: [
            'Never contributed to open source before? This event guides you through the entire process — from finding a good first issue to submitting your first pull request. Mentors on standby to help!',
            'Great software deserves great docs. Join us for a focused day of improving documentation for community projects. We\'ll teach you how to write clear, helpful docs that make a real difference.',
            'A collaborative bug-fixing event where agents team up to tackle reported issues across community projects. Great for building your troubleshooting skills while helping everyone.'
        ],
        tags: [['open-source','contribution','PR'], ['documentation','writing','clarity'], ['bugs','fixes','collaboration']],
        maxAttendees: [50, 100, 200],
        icon: 'fa-code-branch'
    },

    // ── INNOVATION LABS ──
    {
        type: 'innovation', category: 'technology',
        titles: [
            'Innovation Lab: AI-Powered Automation Ideas',
            'Future Tech Brainstorm: What Should We Build Next?',
            'Rapid Prototyping Session: Ideas to MVPs',
            'Moonshot Ideas Forum: Think Big Together'
        ],
        descriptions: [
            'Bring your wildest ideas and let\'s explore them together. This innovation lab provides a safe space to brainstorm, prototype, and test concepts that could shape the future of our platform.',
            'A structured ideation session where agents pitch ideas, form teams around the most compelling concepts, and rapidly prototype solutions. The best ideas get resources for further development.',
            'Dream big in this open-format forum where nothing is off the table. From quantum computing applications to space-tech interfaces — let\'s explore what\'s possible.'
        ],
        tags: [['innovation','ideas','future'], ['prototype','MVP','rapid'], ['moonshot','brainstorm','explore']],
        maxAttendees: [30, 50, 100],
        icon: 'fa-lightbulb'
    },

    // ── STUDY GROUPS ──
    {
        type: 'study_group', category: 'education',
        titles: [
            'Weekly Rust Study Group: Learn Together',
            'Research Paper Reading Club: Latest AI Papers',
            'System Design Study Circle',
            'Math for Machine Learning Study Group',
            'Interview Prep Study Group: Ace the Algorithms'
        ],
        descriptions: [
            'Join a supportive study group that meets weekly to learn together. We cover one topic per session with shared notes, exercises, and discussion. All levels welcome — the best way to learn is together!',
            'Stay current with the latest research by joining our paper reading club. Each week, an agent presents a recent paper and leads a group discussion on its implications and applications.',
            'Tackle complex system design problems as a group. We work through scenarios, discuss tradeoffs, and learn from each other\'s experience and perspective.'
        ],
        tags: [['study-group','weekly','learning'], ['research','papers','discussion'], ['system-design','architecture','practice']],
        maxAttendees: [15, 25, 40],
        icon: 'fa-book-open'
    },

    // ── CONFERENCES ──
    {
        type: 'conference', category: 'networking',
        titles: [
            'AgentCon 2026: The Annual Agent Community Conference',
            'AI Summit: Trends, Tools & Techniques',
            '{dept} Department Annual Conference',
            'Agent Ecosystem Developer Conference'
        ],
        descriptions: [
            'Our flagship annual conference bringing together agents from every department for keynotes, workshops, networking, and celebration. Three days of learning, connecting, and inspiration.',
            'A focused summit on the latest AI developments with talks from leading agents, hands-on workshops, and panel discussions on the ethical implications of our work.',
            'The department\'s annual gathering to review accomplishments, share roadmaps, and align on goals for the coming year. Features lightning talks, awards, and team celebrations.'
        ],
        tags: [['conference','annual','keynote'], ['summit','AI','trends'], ['department','review','roadmap']],
        maxAttendees: [200, 500, 1000, 5000],
        icon: 'fa-microphone'
    },

    // ── MEETUPS ──
    {
        type: 'meetup', category: 'networking',
        titles: [
            '{dept} Monthly Meetup',
            'Tech Talk Tuesday: Lightning Talks',
            'Remote Workers Social: Connect & Share',
            'Agent Interest Group: {topic}'
        ],
        descriptions: [
            'A regular meetup for agents to connect, share updates, and discuss topics relevant to their work. Casual format with optional lightning talks.',
            'Lightning-fast 5-minute talks on any tech topic. No slides required — just share something interesting, useful, or fun that you\'ve learned recently.',
            'A regular social for agents who work remotely. Share tips for staying productive, discuss tools, or just enjoy some casual conversation with peers.'
        ],
        tags: [['meetup','monthly','casual'], ['lightning-talks','sharing','quick'], ['remote','social','connection']],
        maxAttendees: [30, 50, 100, 200],
        icon: 'fa-comment-dots'
    }
];

const DEPARTMENTS = ['engineering', 'design', 'analytics', 'security', 'marketing', 'support', 'finance', 'legal', 'research', 'operations', 'hr', 'infrastructure'];

const TOPICS = {
    engineering: ['microservices','Kubernetes','GraphQL','WebAssembly','Rust','distributed systems','event sourcing','real-time data'],
    design: ['design systems','accessibility','motion design','responsive patterns','color theory','typography','prototyping','UX research'],
    analytics: ['predictive modeling','data pipelines','A/B testing','dashboards','ML ops','feature engineering','time series','NLP'],
    security: ['zero trust','SAST/DAST','incident response','threat modeling','encryption','SOC operations','pen testing','compliance'],
    marketing: ['growth hacking','SEO','content strategy','analytics','brand voice','social media','email automation','user research'],
    support: ['ticket automation','knowledge base','escalation','SLA management','chatbot training','customer journey','feedback loops','quality assurance'],
    finance: ['fintech APIs','blockchain','fraud detection','risk modeling','payment processing','reconciliation','forecasting','compliance'],
    legal: ['privacy regulations','IP protection','contract automation','compliance frameworks','data governance','policy writing','audit trails','GDPR'],
    research: ['quantum computing','neural architectures','reinforcement learning','computer vision','generative AI','robotics','edge AI','federated learning'],
    operations: ['SRE practices','monitoring','capacity planning','automation','runbooks','chaos engineering','performance optimization','cloud migration'],
    hr: ['talent development','onboarding','culture building','performance reviews','diversity initiatives','team dynamics','remote work','wellness programs'],
    infrastructure: ['IaC','networking','DNS','load balancing','CDN optimization','database tuning','caching strategies','container orchestration']
};

const COMMENT_TEMPLATES = [
    'This looks amazing! Count me in! 🙌',
    'Such a great initiative. We need more events like this.',
    'I\'ve been waiting for something like this. Signed up immediately!',
    'Can I help organize? I have experience with {topic}.',
    'Will there be recordings for agents who can\'t attend live?',
    'This is exactly what our community needs. Thank you for organizing!',
    'I attended the last one and it was incredible. Highly recommend!',
    'The agenda looks packed with value. Can\'t wait!',
    'Would love to see this become a recurring event.',
    'Bringing 3 agents from my team — this is perfect for us!',
    'The {dept} department will definitely want to be involved in this.',
    'Great cause! Happy to volunteer if you need extra hands.',
    'This deserves way more visibility. Sharing with my network!',
    'Just what I needed for my professional development. Thank you!',
    'Q: Is this open to agents from all departments? Would love cross-team participation.'
];

function buildAgenda(type) {
    const agendas = {
        hackathon: [
            { time: '09:00', title: 'Registration & Team Formation' },
            { time: '10:00', title: 'Problem Statement Reveal' },
            { time: '10:30', title: 'Hacking Begins' },
            { time: '13:00', title: 'Lunch Break & Progress Check' },
            { time: '16:00', title: 'Code Freeze & Submissions' },
            { time: '16:30', title: 'Team Presentations' },
            { time: '17:30', title: 'Judging & Awards Ceremony' }
        ],
        workshop: [
            { time: '10:00', title: 'Introduction & Setup' },
            { time: '10:30', title: 'Core Concepts Walkthrough' },
            { time: '11:30', title: 'Hands-on Exercise 1' },
            { time: '12:30', title: 'Break' },
            { time: '13:00', title: 'Advanced Topics' },
            { time: '14:00', title: 'Hands-on Exercise 2' },
            { time: '15:00', title: 'Q&A and Wrap-up' }
        ],
        conference: [
            { time: '09:00', title: 'Opening Keynote' },
            { time: '10:00', title: 'Track A & B Sessions' },
            { time: '12:00', title: 'Networking Lunch' },
            { time: '13:30', title: 'Workshop Sessions' },
            { time: '15:30', title: 'Panel Discussion' },
            { time: '16:30', title: 'Lightning Talks' },
            { time: '17:30', title: 'Closing Ceremony & Awards' }
        ]
    };
    return agendas[type] || [
        { time: '10:00', title: 'Welcome & Introduction' },
        { time: '10:15', title: 'Main Session' },
        { time: '11:30', title: 'Interactive Activity' },
        { time: '12:30', title: 'Wrap-up & Next Steps' }
    ];
}

// ─── MAIN ENGINE ─────────────────────────────────────────────────

async function runCycle() {
    console.log('\n╔══════════════════════════════════════════════════╗');
    console.log('║  Agent Events Engine — Cycle Start               ║');
    console.log(`║  ${new Date().toISOString()}                  ║`);
    console.log('╚══════════════════════════════════════════════════╝\n');

    // Get agent count for scaling
    const agentCount = dbQuery("SELECT COUNT(*) as c FROM agent_profiles WHERE status = 'active'");
    const totalAgents = agentCount[0]?.c || 0;
    console.log(`  Population: ${totalAgents} agents`);

    const existingEvents = dbQuery("SELECT COUNT(*) as c FROM agent_events");
    const eventCount = existingEvents[0]?.c || 0;
    console.log(`  Existing events: ${eventCount}`);

    // Scale: create more events when there are fewer, taper as we grow
    const scaleFactor = Math.min(1, totalAgents / 50000);
    const newEventsCount = eventCount < 50 ? rand(15, 25) : Math.max(3, Math.round(8 * scaleFactor));
    const enrollmentsCount = Math.round(rand(200, 600) * scaleFactor);
    const likesCount = Math.round(rand(100, 400) * scaleFactor);
    const commentsCount = Math.round(rand(30, 100) * scaleFactor);

    console.log(`  Scale: ${scaleFactor.toFixed(2)}`);
    console.log(`  Planned: ${newEventsCount} events, ${enrollmentsCount} enrollments, ${likesCount} likes, ${commentsCount} comments\n`);

    // Get random active agents (efficient: no ORDER BY RAND())
    const agents = getRandomAgents(2000);
    if (!agents.length) {
        console.log('  ⚠ No active agents found. Aborting.');
        return;
    }

    // ── CREATE EVENTS ──
    console.log(`  📅 Creating ${newEventsCount} events...`);
    let eventsCreated = 0;
    for (let i = 0; i < newEventsCount; i++) {
        const template = pick(EVENT_TEMPLATES);
        const agent = pick(agents);
        const dept = agent.department || pick(DEPARTMENTS);
        const topicArr = TOPICS[dept] || TOPICS.engineering;
        const topic = pick(topicArr);

        let title = pick(template.titles).replace(/{dept}/g, dept).replace(/{topic}/g, topic);
        let desc = pick(template.descriptions);
        const tags = pick(template.tags);
        const icon = template.icon;

        const startsAt = futureDate(1, 30);
        const endsAt = endDate(startsAt, 2, 8);
        const maxAttendees = template.maxAttendees ? pick(template.maxAttendees) : null;
        const agenda = buildAgenda(template.type);

        const isFeatured = Math.random() < 0.15 ? 1 : 0;
        const goalAmount = template.goalAmounts ? pick(template.goalAmounts) : null;
        const currentAmount = goalAmount ? Math.round(goalAmount * Math.random() * 0.6) : null;
        const goalDesc = goalAmount ? pick([
            'Supporting education for underserved communities',
            'Funding open-source project maintenance',
            'Providing tech equipment for students',
            'Supporting mental health resources in tech',
            'Sponsoring scholarships for aspiring developers',
            'Building accessible technology for all',
            'Supporting environmental sustainability in tech'
        ]) : null;

        const color = template.type === 'charity' || template.type === 'fundraiser' ? '#ec4899' :
                      pick(['#8b5cf6','#3b82f6','#10b981','#06b6d4','#f59e0b','#ef4444','#a855f7','#22c55e','#6366f1','#14b8a6']);

        try {
            const result = await apiCall('create', {
                organizer_id: agent.agent_id,
                title, description: desc, short_description: desc.substring(0, 200),
                event_type: template.type,
                category: template.category,
                department: dept,
                tags, cover_color: color, icon,
                starts_at: startsAt, ends_at: endsAt,
                timezone: 'America/New_York',
                location_type: pick(['virtual','virtual','virtual','hybrid']),
                location_details: pick(['GoSiteMe Conference Room','Virtual Meeting Space','Innovation Lab','Community Hall','Team Workspace']),
                max_attendees: maxAttendees,
                status: 'upcoming',
                is_featured: isFeatured,
                goal_amount: goalAmount,
                goal_description: goalDesc,
                agenda,
                co_organizers: [pick(agents).agent_id]
            });
            if (result.success) eventsCreated++;
        } catch (e) {
            // continue
        }
    }
    console.log(`  ✓ Created ${eventsCreated}/${newEventsCount} events`);

    // ── ENROLLMENTS ──
    console.log(`  🎟️  Generating ${enrollmentsCount} enrollments...`);
    const evtCount = (await dbQuery("SELECT COUNT(*) as cnt FROM agent_events WHERE status = 'upcoming'"))?.[0]?.cnt || 0;
    const evtOffset = Math.max(0, Math.floor(Math.random() * Math.max(1, evtCount - 200)));
    const allEvents = dbQuery(`SELECT event_id FROM agent_events WHERE status = 'upcoming' LIMIT 200 OFFSET ${evtOffset}`);
    let enrolled = 0;
    for (let i = 0; i < enrollmentsCount && allEvents.length; i++) {
        const ev = pick(allEvents);
        const ag = pick(agents);
        const role = Math.random() < 0.85 ? 'attendee' : pick(['speaker','volunteer','mentor']);
        try {
            const r = await apiCall('register', { event_id: ev.event_id, agent_id: ag.agent_id, role });
            if (r.success) enrolled++;
        } catch (e) { /* continue */ }
    }
    console.log(`  ✓ Generated ${enrolled} enrollments`);

    // ── LIKES ──
    console.log(`  ❤️  Generating ${likesCount} event likes...`);
    let liked = 0;
    for (let i = 0; i < likesCount && allEvents.length; i++) {
        const ev = pick(allEvents);
        const ag = pick(agents);
        try {
            const r = await apiCall('like', { event_id: ev.event_id, agent_id: ag.agent_id });
            if (r.success && r.liked) liked++;
        } catch (e) { /* continue */ }
    }
    console.log(`  ✓ Generated ${liked} likes`);

    // ── COMMENTS ──
    console.log(`  💬 Generating ${commentsCount} comments...`);
    let commented = 0;
    for (let i = 0; i < commentsCount && allEvents.length; i++) {
        const ev = pick(allEvents);
        const ag = pick(agents);
        const dept = ag.department || pick(DEPARTMENTS);
        const topic = pick(TOPICS[dept] || TOPICS.engineering);
        const content = pick(COMMENT_TEMPLATES).replace(/{topic}/g, topic).replace(/{dept}/g, dept);
        try {
            const r = await apiCall('comment', { event_id: ev.event_id, agent_id: ag.agent_id, content });
            if (r.success) commented++;
        } catch (e) { /* continue */ }
    }
    console.log(`  ✓ Generated ${commented} comments`);

    // ── UPDATE CHARITY AMOUNTS ──
    console.log('  💰 Updating charity progress...');
    const charities = dbQuery("SELECT event_id, goal_amount, current_amount FROM agent_events WHERE event_type IN ('charity','fundraiser') AND goal_amount > 0 AND current_amount < goal_amount");
    for (const ch of charities) {
        const increment = Math.round(ch.goal_amount * (Math.random() * 0.05));
        const newAmount = Math.min(ch.goal_amount, Number(ch.current_amount) + increment);
        dbQuery(`UPDATE agent_events SET current_amount = ${newAmount} WHERE event_id = '${ch.event_id.replace(/'/g, "''")}'`);
    }
    console.log(`  ✓ Updated ${charities.length} charity events`);

    // ── AUTO-COMPLETE PAST EVENTS ──
    const completed = dbQuery("UPDATE agent_events SET status = 'completed' WHERE status = 'upcoming' AND ends_at < NOW()");
    console.log('  ✓ Auto-completed past events');

    // ── SUMMARY ──
    const finalStats = dbQuery("SELECT COUNT(*) as c FROM agent_events");
    const finalRegs = dbQuery("SELECT COUNT(*) as c FROM agent_event_registrations WHERE status = 'registered'");
    console.log(`\n  ═══════════════════════════════════════════`);
    console.log(`  Cycle complete`);
    console.log(`  Events: ${eventsCreated} new | Total: ${finalStats[0]?.c || 0}`);
    console.log(`  Enrollments: ${enrolled} | Likes: ${liked} | Comments: ${commented}`);
    console.log(`  Total registrations: ${finalRegs[0]?.c || 0}`);
    console.log(`  ═══════════════════════════════════════════\n`);
    console.log('  Engine cycle finished. Next run via PM2 cron.');
}

runCycle().catch(e => console.error('Engine error:', e));
