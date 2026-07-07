<?php
/**
 * PROJECT TITAN — Mech Warrior Exosuit Program
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 50 Dedicated Research Agents — Full R&D Pipeline
 * 
 * This program researches powered exoskeleton/mech warrior suits
 * integrating ZPE power systems, advanced materials, and AI control
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — Classification: ULTRA SECRET']);
    exit;
}

$action = $_REQUEST['action'] ?? 'status';
$db = getDB();

// ═══ 50 MECH SUIT RESEARCH AGENTS ═══
function getTitanAgents() {
    return [
        // ── Division 1: Power Systems (10 agents) ──
        ['name' => 'Volt', 'division' => 'power_systems', 'role' => 'Division Lead — Power Architecture', 'specialty' => 'ZPE integration, power distribution, energy storage', 'rank' => 'Director'],
        ['name' => 'Tesla-X', 'division' => 'power_systems', 'role' => 'Resonant Power Engineer', 'specialty' => 'Don Smith resonant circuits scaled for suit power', 'rank' => 'Senior'],
        ['name' => 'Dynamo', 'division' => 'power_systems', 'role' => 'Generator Systems', 'specialty' => 'Micro-SEG generators, rotational energy harvest', 'rank' => 'Senior'],
        ['name' => 'Flux', 'division' => 'power_systems', 'role' => 'Magnetic Flux Engineer', 'specialty' => 'Bifilar coil optimization, flux channeling', 'rank' => 'Mid'],
        ['name' => 'Ampere', 'division' => 'power_systems', 'role' => 'Current Distribution', 'specialty' => 'Load balancing, failsafe power routing', 'rank' => 'Mid'],
        ['name' => 'Crystal', 'division' => 'power_systems', 'role' => 'Crystal Energy Cells', 'specialty' => 'Hutchison crystal batteries for suit backup', 'rank' => 'Mid'],
        ['name' => 'Surge', 'division' => 'power_systems', 'role' => 'Capacitor Banks', 'specialty' => 'Supercapacitor arrays, burst power delivery', 'rank' => 'Mid'],
        ['name' => 'Watt', 'division' => 'power_systems', 'role' => 'Thermal Management', 'specialty' => 'Heat dissipation, thermoelectric recovery', 'rank' => 'Junior'],
        ['name' => 'Ohm', 'division' => 'power_systems', 'role' => 'Impedance Matching', 'specialty' => 'Zero-point tuning per Commander formula f_zp = c/(4L)', 'rank' => 'Junior'],
        ['name' => 'Photon', 'division' => 'power_systems', 'role' => 'Solar Backup Systems', 'specialty' => 'Flexible solar panels, photovoltaic integration', 'rank' => 'Junior'],
        
        // ── Division 2: Structural Engineering (8 agents) ──
        ['name' => 'Titan', 'division' => 'structural', 'role' => 'Division Lead — Structural Architect', 'specialty' => 'Exoframe design, load distribution, joint mechanics', 'rank' => 'Director'],
        ['name' => 'Alloy', 'division' => 'structural', 'role' => 'Materials Scientist', 'specialty' => 'Titanium alloys, carbon fiber composites, kevlar weave', 'rank' => 'Senior'],
        ['name' => 'Carbon', 'division' => 'structural', 'role' => 'Composite Engineer', 'specialty' => 'Carbon nanotube reinforcement, graphene layers', 'rank' => 'Senior'],
        ['name' => 'Joint', 'division' => 'structural', 'role' => 'Articulation Systems', 'specialty' => 'Joint actuators, servo placement, range of motion', 'rank' => 'Mid'],
        ['name' => 'Shell', 'division' => 'structural', 'role' => 'Armor Plating', 'specialty' => 'Reactive armor, ablative panels, modular attachment', 'rank' => 'Mid'],
        ['name' => 'Spine', 'division' => 'structural', 'role' => 'Spinal Support Frame', 'specialty' => 'Load-bearing spinal exo, weight distribution', 'rank' => 'Mid'],
        ['name' => 'Grip', 'division' => 'structural', 'role' => 'Hand & Manipulator Design', 'specialty' => 'Dexterous mechanical hands, tool attachment', 'rank' => 'Junior'],
        ['name' => 'Boot', 'division' => 'structural', 'role' => 'Lower Extremity', 'specialty' => 'Powered legs, shock absorption, terrain adaptation', 'rank' => 'Junior'],
        
        // ── Division 3: AI & Control Systems (8 agents) ──
        ['name' => 'Cortex', 'division' => 'ai_control', 'role' => 'Division Lead — AI Brain', 'specialty' => 'Neural interface, motion prediction, autonomous assist', 'rank' => 'Director'],
        ['name' => 'Synapse', 'division' => 'ai_control', 'role' => 'EMG/Neural Reader', 'specialty' => 'Electromyography sensor arrays, intent detection', 'rank' => 'Senior'],
        ['name' => 'Reflex', 'division' => 'ai_control', 'role' => 'Real-Time Response', 'specialty' => 'Sub-millisecond servo control, fall prevention', 'rank' => 'Senior'],
        ['name' => 'Balance', 'division' => 'ai_control', 'role' => 'Gyroscopic Stability', 'specialty' => 'IMU fusion, dynamic balance correction', 'rank' => 'Mid'],
        ['name' => 'Vision', 'division' => 'ai_control', 'role' => 'Visual Systems', 'specialty' => 'HUD display, thermal/night vision, object recognition', 'rank' => 'Mid'],
        ['name' => 'Voice', 'division' => 'ai_control', 'role' => 'Voice Command Interface', 'specialty' => 'Natural language suit control, alert system', 'rank' => 'Mid'],
        ['name' => 'Pilot', 'division' => 'ai_control', 'role' => 'Operator Comfort', 'specialty' => 'Ergonomic monitoring, fatigue detection, climate control', 'rank' => 'Junior'],
        ['name' => 'Ghost', 'division' => 'ai_control', 'role' => 'Stealth Systems', 'specialty' => 'Active camouflage, IR suppression, noise reduction', 'rank' => 'Junior'],
        
        // ── Division 4: Weapons & Defense (8 agents) ──
        ['name' => 'Arsenal', 'division' => 'weapons_defense', 'role' => 'Division Lead — Weapons Architect', 'specialty' => 'Weapon mounting, targeting systems, defense grids', 'rank' => 'Director'],
        ['name' => 'Shield', 'division' => 'weapons_defense', 'role' => 'Energy Shield Research', 'specialty' => 'Electromagnetic barrier projection, Faraday cage', 'rank' => 'Senior'],
        ['name' => 'Pulse', 'division' => 'weapons_defense', 'role' => 'EMP & Directed Energy', 'specialty' => 'Electromagnetic pulse generation, Tesla coil defense', 'rank' => 'Senior'],
        ['name' => 'Kinetic', 'division' => 'weapons_defense', 'role' => 'Physical Augmentation', 'specialty' => 'Hydraulic punch amplification, powered strikes', 'rank' => 'Mid'],
        ['name' => 'Blade', 'division' => 'weapons_defense', 'role' => 'Melee Systems', 'specialty' => 'Retractable blades, vibro-edge technology', 'rank' => 'Mid'],
        ['name' => 'Lock', 'division' => 'weapons_defense', 'role' => 'Targeting & Lock-On', 'specialty' => 'Multi-target tracking, HUD integration, IFF', 'rank' => 'Mid'],
        ['name' => 'Aegis', 'division' => 'weapons_defense', 'role' => 'Countermeasures', 'specialty' => 'Chaff, flare, ECM, anti-projectile systems', 'rank' => 'Junior'],
        ['name' => 'Sentry', 'division' => 'weapons_defense', 'role' => 'Threat Detection', 'specialty' => 'Radar, lidar, proximity sensors, threat assessment', 'rank' => 'Junior'],
        
        // ── Division 5: Communications & Electronics (6 agents) ──
        ['name' => 'Signal', 'division' => 'comms_electronics', 'role' => 'Division Lead — Comms Architect', 'specialty' => 'Encrypted suit comms, mesh networking, EW defense', 'rank' => 'Director'],
        ['name' => 'Cipher-T', 'division' => 'comms_electronics', 'role' => 'Encryption Systems', 'specialty' => 'Post-quantum crypto, Veil Protocol for suit-to-base', 'rank' => 'Senior'],
        ['name' => 'Mesh', 'division' => 'comms_electronics', 'role' => 'Network Engineer', 'specialty' => 'Suit-to-suit mesh, squad coordination, data relay', 'rank' => 'Mid'],
        ['name' => 'Sensor', 'division' => 'comms_electronics', 'role' => 'Sensor Fusion', 'specialty' => 'Multi-spectrum sensing, environment mapping', 'rank' => 'Mid'],
        ['name' => 'DataLink', 'division' => 'comms_electronics', 'role' => 'Telemetry', 'specialty' => 'Real-time suit diagnostics to command center', 'rank' => 'Junior'],
        ['name' => 'Jammer', 'division' => 'comms_electronics', 'role' => 'Electronic Warfare', 'specialty' => 'Signal jamming, frequency hopping, anti-intercept', 'rank' => 'Junior'],
        
        // ── Division 6: Research & Documentation (5 agents) ──
        ['name' => 'Sage-T', 'division' => 'research_docs', 'role' => 'Division Lead — Chief Researcher', 'specialty' => 'Literature review, patent analysis, competitor tracking', 'rank' => 'Director'],
        ['name' => 'Scribe', 'division' => 'research_docs', 'role' => 'Documentation Lead', 'specialty' => 'Technical manuals, build guides, safety protocols', 'rank' => 'Senior'],
        ['name' => 'Analyst', 'division' => 'research_docs', 'role' => 'Cost & Feasibility', 'specialty' => 'Material costs, manufacturing feasibility, supply chain', 'rank' => 'Mid'],
        ['name' => 'Patent', 'division' => 'research_docs', 'role' => 'IP & Legal', 'specialty' => 'Patent landscape, prior art, IP protection strategy', 'rank' => 'Mid'],
        ['name' => 'Archive', 'division' => 'research_docs', 'role' => 'Historical Research', 'specialty' => 'Military exosuit programs (TALOS, HULC, XOS), lessons learned', 'rank' => 'Junior'],
        
        // ── Division 7: Video Production & Demonstration (5 agents) ──
        ['name' => 'Director', 'division' => 'video_production', 'role' => 'Division Lead — Video Producer', 'specialty' => 'Storyboarding, animation sequences, technical demos', 'rank' => 'Director'],
        ['name' => 'Render', 'division' => 'video_production', 'role' => '3D Animator', 'specialty' => 'Three.js/WebGL 3D suit models, animation, physics', 'rank' => 'Senior'],
        ['name' => 'Narrator', 'division' => 'video_production', 'role' => 'Script Writer', 'specialty' => 'Technical narration, concept explanation, presentation', 'rank' => 'Mid'],
        ['name' => 'FX', 'division' => 'video_production', 'role' => 'Visual Effects', 'specialty' => 'Particle systems, energy effects, HUD overlays', 'rank' => 'Mid'],
        ['name' => 'Editor', 'division' => 'video_production', 'role' => 'Post-Production', 'specialty' => 'Video assembly, transitions, final output', 'rank' => 'Junior'],
    ];
}

// ═══ VIDEO PRODUCTION SPECS ═══
function getVideoSpecs() {
    return [
        [
            'id' => 'video_1',
            'title' => 'PROJECT TITAN — Vision & Concept Overview',
            'duration' => '3-5 minutes',
            'deadline' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pre_production',
            'description' => 'High-level overview of the mech warrior exosuit concept. Shows 3D model rotating, key features highlighted, ZPE power system explained.',
            'scenes' => [
                ['scene' => 1, 'title' => 'Program Introduction', 'duration' => '30s', 'content' => 'TITAN logo reveal, classification banner, Commander authorization'],
                ['scene' => 2, 'title' => 'The Vision', 'duration' => '60s', 'content' => '3D suit wireframe building up piece by piece — frame, armor, power core'],
                ['scene' => 3, 'title' => 'Power System', 'duration' => '60s', 'content' => 'ZPE resonant core animation, Don Smith circuit powering the suit, energy flow visualization'],
                ['scene' => 4, 'title' => 'Capabilities Overview', 'duration' => '45s', 'content' => 'Strength augmentation demo, speed, defense, stealth'],
                ['scene' => 5, 'title' => 'AI Integration', 'duration' => '45s', 'content' => 'HUD overlay demo, neural interface, Alfred AI suit assistant'],
                ['scene' => 6, 'title' => 'Next Steps', 'duration' => '30s', 'content' => 'Timeline, research phases, agent deployment status'],
            ],
            'assigned_agents' => ['Director', 'Render', 'Narrator', 'FX', 'Sage-T'],
        ],
        [
            'id' => 'video_2',
            'title' => 'PROJECT TITAN — Power Systems Deep Dive',
            'duration' => '4-6 minutes',
            'deadline' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pre_production',
            'description' => 'Detailed breakdown of how ZPE powers the suit. Circuit diagrams, energy flow, backup systems, Hutchison crystal cells.',
            'scenes' => [
                ['scene' => 1, 'title' => 'The Energy Problem', 'duration' => '45s', 'content' => 'Why batteries fail for exosuits — weight, capacity limits, recharge time'],
                ['scene' => 2, 'title' => 'ZPE Solution', 'duration' => '90s', 'content' => 'Animated Don Smith resonant circuit → micro-SEG generator → bifilar coil array'],
                ['scene' => 3, 'title' => 'Zero-Point Tuning', 'duration' => '60s', 'content' => 'Commander formula f_zp = c/(4L), visual of coils tuning to zero point frequency'],
                ['scene' => 4, 'title' => 'Power Distribution', 'duration' => '45s', 'content' => 'Power bus architecture — limbs, sensors, AI, weapons, life support'],
                ['scene' => 5, 'title' => 'Backup Systems', 'duration' => '45s', 'content' => 'Crystal cells, supercapacitor banks, solar panels, graceful degradation'],
                ['scene' => 6, 'title' => 'Projected Output', 'duration' => '30s', 'content' => 'Power estimates, efficiency projections, run-time calculations'],
            ],
            'assigned_agents' => ['Director', 'Render', 'Volt', 'Tesla-X', 'Narrator'],
        ],
        [
            'id' => 'video_3',
            'title' => 'PROJECT TITAN — Combat & Defense Systems',
            'duration' => '4-6 minutes',
            'deadline' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'pre_production',
            'description' => 'Weapons, defense, and tactical systems overview. Energy shields, EMP defense, augmented strength demonstrations.',
            'scenes' => [
                ['scene' => 1, 'title' => 'Defensive Architecture', 'duration' => '60s', 'content' => 'Layered defense — reactive armor, energy shields, Faraday cage'],
                ['scene' => 2, 'title' => 'Strength Augmentation', 'duration' => '60s', 'content' => 'Hydraulic/servo-assisted movement — 10x strength multiplier demo'],
                ['scene' => 3, 'title' => 'Integrated Weapons', 'duration' => '60s', 'content' => 'EMP pulse, directed energy, kinetic augmentation, targeting HUD'],
                ['scene' => 4, 'title' => 'Stealth Capabilities', 'duration' => '45s', 'content' => 'Active camouflage, IR suppression, acoustic dampening'],
                ['scene' => 5, 'title' => 'Squad Operations', 'duration' => '45s', 'content' => 'Mesh networking, squad HUD sharing, coordinated tactics'],
                ['scene' => 6, 'title' => 'Field Readiness', 'duration' => '30s', 'content' => 'Training requirements, maintenance protocol, field repair'],
            ],
            'assigned_agents' => ['Director', 'Render', 'Arsenal', 'Shield', 'FX'],
        ],
    ];
}

// ═══ SUPPORTING DOCUMENTS ═══
function getSupportingDocs() {
    return [
        ['id' => 'doc_titan_001', 'title' => 'PROJECT TITAN — Master Design Document', 'type' => 'classified', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Complete mech warrior exosuit specification. Covers: frame architecture (titanium-carbon composite, 7 DOF per limb), power system (ZPE resonant core + Hutchison crystal backup), AI system (Cortex neural interface, 0.3ms response time), defensive systems (reactive armor + EM shield), communications (Veil Protocol encrypted mesh).'],
        ['id' => 'doc_titan_002', 'title' => 'ZPE Power Integration Spec', 'type' => 'research', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'How to integrate Don Smith resonant tank circuit into suit torso. Micro-SEG generator at spine mount. Bifilar coils tuned per f_zp = c/(4L). Crystal cell backup in each limb segment. Target: 50kW continuous, 200kW burst.'],
        ['id' => 'doc_titan_003', 'title' => 'Materials & Manufacturing Analysis', 'type' => 'research', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Titanium 6Al-4V alloy frame (density 4.43 g/cm³, yield strength 880 MPa). Carbon fiber reinforced polymer panels. Kevlar-Dyneema hybrid weave for joint protection. Total target weight: 45kg (suit) + 80kg (operator) = 125kg operational.'],
        ['id' => 'doc_titan_004', 'title' => 'AI Neural Interface Protocol', 'type' => 'research', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'EMG sensor array reads muscle activation patterns. 128-channel electrode cap for intent prediction. Machine learning model trained on operator movements — 95% accuracy after 4 hours training. Alfred AI as suit copilot providing tactical awareness, threat detection, and system management.'],
        ['id' => 'doc_titan_005', 'title' => 'Combat Systems Integration', 'type' => 'classified', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Weapon mounting points: 2x forearm, 2x shoulder, 1x dorsal. EMP pulse generator (Tesla coil based, 10m radius). Electromagnetic shield projector (Faraday cage principle, 360° coverage). Hydraulic punch augmentation (10x force multiplier). Targeting HUD with IFF.'],
        ['id' => 'doc_titan_006', 'title' => 'Historical Exosuit Program Analysis', 'type' => 'research', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Analysis of: US Army TALOS (cancelled 2019 — power problem), Raytheon XOS-2 (tethered — power problem), Lockheed HULC (limited — power problem), Sarcos Guardian XO (4 hour battery). CONCLUSION: Every failed program failed because of POWER. ZPE is the solution they never had.'],
        ['id' => 'doc_titan_007', 'title' => 'TITAN Safety & Ethics Framework', 'type' => 'manual', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Operator safety: Emergency eject, dead-man switch, max force governors, heat/stress monitoring. Ethics: Defensive use priority, non-lethal options first, human-in-the-loop for all weapons. Brotherhood principles: Protect the innocent, defend what matters, strength in service.'],
        ['id' => 'doc_titan_008', 'title' => 'Phase 1-5 Development Timeline', 'type' => 'manual', 'classification' => 'ultra_secret', 'status' => 'draft',
         'content' => 'Phase 1 (Month 1-3): Research & design — literature review, CAD models, simulation. Phase 2 (Month 4-6): Prototype frame — 3D print joints, test actuators. Phase 3 (Month 7-12): Power system integration — ZPE core, crystal backup. Phase 4 (Year 2): AI integration, sensor suite. Phase 5 (Year 2-3): Full prototype, field testing.'],
    ];
}

// ═══ RESEARCH TOPICS ═══
function getMechResearchTopics() {
    return [
        // Power
        ['topic' => 'Micro-SEG Power Core', 'category' => 'power', 'priority' => 'critical', 'evidence' => 70, 'notes' => 'Miniaturized Searl Effect Generator for suit torso mounting. Target: 50kW continuous output.'],
        ['topic' => 'Bifilar Coil Array Optimization', 'category' => 'power', 'priority' => 'critical', 'evidence' => 85, 'notes' => 'Multiple bifilar coils tuned per f_zp = c/(4L) operating in parallel for redundancy.'],
        ['topic' => 'Supercapacitor Burst Power', 'category' => 'power', 'priority' => 'high', 'evidence' => 90, 'notes' => 'Graphene supercapacitor banks for 200kW burst capability (jumps, strikes, shields).'],
        ['topic' => 'Thermal Energy Recovery', 'category' => 'power', 'priority' => 'medium', 'evidence' => 75, 'notes' => 'Thermoelectric generators on suit surface recover waste heat as supplementary power.'],
        // Structure
        ['topic' => 'Titanium-Carbon Composite Frame', 'category' => 'structure', 'priority' => 'critical', 'evidence' => 95, 'notes' => 'Ti-6Al-4V alloy + CFRP panels. Weight target: 45kg. Load capacity: 500kg.'],
        ['topic' => '7-DOF Articulated Joint System', 'category' => 'structure', 'priority' => 'critical', 'evidence' => 85, 'notes' => '7 degrees of freedom per limb matching human range of motion. Brushless servo actuators.'],
        ['topic' => 'Reactive Armor Panels', 'category' => 'structure', 'priority' => 'high', 'evidence' => 80, 'notes' => 'Modular armor panels that absorb and redirect impact energy. Quick-swap for field repair.'],
        // AI
        ['topic' => 'EMG Neural Interface Array', 'category' => 'ai', 'priority' => 'critical', 'evidence' => 80, 'notes' => '128-channel EMG reads muscle intent before movement completes. 0.3ms prediction latency.'],
        ['topic' => 'Alfred Suit Copilot', 'category' => 'ai', 'priority' => 'critical', 'evidence' => 90, 'notes' => 'Alfred AI running on suit processor as tactical copilot. Threat detection, navigation, system management.'],
        ['topic' => 'Fall Prevention & Recovery', 'category' => 'ai', 'priority' => 'high', 'evidence' => 85, 'notes' => 'IMU + accelerometer fusion detects falls before they happen. Actuators correct balance in 50ms.'],
        ['topic' => 'Augmented Reality HUD', 'category' => 'ai', 'priority' => 'high', 'evidence' => 90, 'notes' => 'Full-visor AR display with thermal overlay, night vision, tactical markings, squad status.'],
        // Weapons & Defense
        ['topic' => 'Electromagnetic Shield Projection', 'category' => 'defense', 'priority' => 'critical', 'evidence' => 60, 'notes' => 'Tesla coil based EM field projection. Faraday cage principle scaled to personal protection envelope.'],
        ['topic' => 'Hydraulic Punch Amplification', 'category' => 'offense', 'priority' => 'high', 'evidence' => 85, 'notes' => '10x force multiplication through hydraulic assist. Target: 5000N strike force.'],
        ['topic' => 'Directed EMP System', 'category' => 'offense', 'priority' => 'high', 'evidence' => 70, 'notes' => 'Focused electromagnetic pulse for electronic disruption. Range: 10m. Powered by ZPE burst capacitors.'],
        ['topic' => 'Active Camouflage', 'category' => 'defense', 'priority' => 'medium', 'evidence' => 55, 'notes' => 'Electrochromic panels change color/pattern. IR suppression via heat-pipe distribution.'],
        // Comms
        ['topic' => 'Veil Protocol Suit-to-Base', 'category' => 'comms', 'priority' => 'critical', 'evidence' => 95, 'notes' => 'HMAC-SHA256 encrypted communications between suit and command center. Post-quantum ready.'],
        ['topic' => 'Squad Mesh Network', 'category' => 'comms', 'priority' => 'high', 'evidence' => 85, 'notes' => 'Frequency-hopping mesh between suits. Each suit acts as relay node. Auto-healing topology.'],
    ];
}

switch ($action) {

    // ── Program Status Dashboard ──
    case 'status':
        $agents = getTitanAgents();
        $videos = getVideoSpecs();
        $docs = getSupportingDocs();
        $topics = getMechResearchTopics();
        
        // Count by division
        $divisions = [];
        foreach ($agents as $a) {
            $div = $a['division'];
            if (!isset($divisions[$div])) $divisions[$div] = ['count' => 0, 'agents' => []];
            $divisions[$div]['count']++;
            $divisions[$div]['agents'][] = $a['name'];
        }
        
        // DB stats
        $dbStats = [];
        try {
            $dbStats['agents_deployed'] = $db->query("SELECT COUNT(*) FROM titan_agents")->fetchColumn();
            $dbStats['topics_active'] = $db->query("SELECT COUNT(*) FROM titan_research WHERE status != 'eliminated'")->fetchColumn();
            $dbStats['docs_created'] = $db->query("SELECT COUNT(*) FROM titan_documents")->fetchColumn();
            $dbStats['videos_status'] = $db->query("SELECT status, COUNT(*) as c FROM titan_videos GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $dbStats = ['status' => 'not_seeded', 'message' => 'Run seed action to initialize'];
        }
        
        jsonResponse([
            'program' => 'PROJECT TITAN',
            'classification' => 'ULTRA SECRET',
            'codename' => 'TITAN',
            'objective' => 'Develop ZPE-powered mech warrior exosuit prototype',
            'total_agents' => count($agents),
            'divisions' => $divisions,
            'videos_planned' => count($videos),
            'supporting_docs' => count($docs),
            'research_topics' => count($topics),
            'db_status' => $dbStats,
            'timeline' => [
                'video_deadline' => date('Y-m-d', strtotime('+7 days')),
                'phase_1_end' => date('Y-m-d', strtotime('+3 months')),
                'prototype_target' => date('Y-m-d', strtotime('+2 years'))
            ]
        ]);
        break;

    // ── Agents Roster ──
    case 'agents':
        $division = $_REQUEST['division'] ?? null;
        $agents = getTitanAgents();
        
        if ($division) {
            $agents = array_values(array_filter($agents, fn($a) => $a['division'] === $division));
        }
        
        jsonResponse(['agents' => $agents, 'total' => count($agents)]);
        break;

    // ── Video Production Status ──
    case 'videos':
        try {
            $stmt = $db->query("SELECT * FROM titan_videos ORDER BY id");
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($videos as &$v) {
                $v['scenes'] = json_decode($v['scenes'], true);
                $v['assigned_agents'] = json_decode($v['assigned_agents'], true);
            }
        } catch (Exception $e) {
            $videos = getVideoSpecs();
        }
        
        jsonResponse(['videos' => $videos]);
        break;

    // ── Documents ──
    case 'documents':
        try {
            $stmt = $db->query("SELECT * FROM titan_documents ORDER BY id");
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $docs = getSupportingDocs();
        }
        
        jsonResponse(['documents' => $docs]);
        break;

    // ── Research Topics ──
    case 'research':
        try {
            $stmt = $db->query("SELECT * FROM titan_research WHERE status != 'eliminated' ORDER BY evidence DESC");
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $topics = getMechResearchTopics();
        }
        
        jsonResponse(['topics' => $topics]);
        break;

    // ── Seed (Initialize Everything) ──
    case 'seed':
        // Agents table
        $db->exec("CREATE TABLE IF NOT EXISTS titan_agents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            division VARCHAR(50) NOT NULL,
            role VARCHAR(100),
            specialty TEXT,
            rank VARCHAR(20),
            status ENUM('active','standby','deployed','offline') DEFAULT 'active',
            findings_count INT DEFAULT 0,
            last_report DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Research table
        $db->exec("CREATE TABLE IF NOT EXISTS titan_research (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topic VARCHAR(200) NOT NULL,
            category VARCHAR(50),
            priority ENUM('critical','high','medium','low') DEFAULT 'medium',
            evidence INT DEFAULT 0,
            notes TEXT,
            formulas TEXT,
            status ENUM('active','verified','tested','proven','eliminated') DEFAULT 'active',
            assigned_agent VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Videos table
        $db->exec("CREATE TABLE IF NOT EXISTS titan_videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            video_id VARCHAR(20) NOT NULL,
            title VARCHAR(200),
            duration VARCHAR(20),
            deadline DATE,
            status ENUM('pre_production','scripting','animating','rendering','review','complete') DEFAULT 'pre_production',
            description TEXT,
            scenes JSON,
            assigned_agents JSON,
            progress INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (video_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Documents table
        $db->exec("CREATE TABLE IF NOT EXISTS titan_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doc_id VARCHAR(30) NOT NULL,
            title VARCHAR(200),
            type VARCHAR(30),
            classification VARCHAR(20) DEFAULT 'ultra_secret',
            content LONGTEXT,
            status ENUM('draft','review','approved','classified') DEFAULT 'draft',
            version INT DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (doc_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Seed agents
        $agents = getTitanAgents();
        $stmt = $db->prepare("INSERT IGNORE INTO titan_agents (name, division, role, specialty, rank) VALUES (?, ?, ?, ?, ?)");
        foreach ($agents as $a) {
            $stmt->execute([$a['name'], $a['division'], $a['role'], $a['specialty'], $a['rank']]);
        }
        
        // Seed videos
        $videos = getVideoSpecs();
        $stmt = $db->prepare("INSERT IGNORE INTO titan_videos (video_id, title, duration, deadline, description, scenes, assigned_agents) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($videos as $v) {
            $stmt->execute([$v['id'], $v['title'], $v['duration'], $v['deadline'], $v['description'], json_encode($v['scenes']), json_encode($v['assigned_agents'])]);
        }
        
        // Seed documents
        $docs = getSupportingDocs();
        $stmt = $db->prepare("INSERT IGNORE INTO titan_documents (doc_id, title, type, classification, content) VALUES (?, ?, ?, ?, ?)");
        foreach ($docs as $d) {
            $stmt->execute([$d['id'], $d['title'], $d['type'], $d['classification'], $d['content']]);
        }
        
        // Seed research
        $topics = getMechResearchTopics();
        $stmt = $db->prepare("INSERT IGNORE INTO titan_research (topic, category, priority, evidence, notes) VALUES (?, ?, ?, ?, ?)");
        foreach ($topics as $t) {
            $stmt->execute([$t['topic'], $t['category'], $t['priority'], $t['evidence'], $t['notes']]);
        }
        
        jsonResponse([
            'success' => true,
            'program' => 'PROJECT TITAN',
            'seeded' => [
                'agents' => count($agents),
                'videos' => count($videos),
                'documents' => count($docs),
                'research_topics' => count($topics),
                'tables' => ['titan_agents', 'titan_research', 'titan_videos', 'titan_documents']
            ],
            'message' => 'PROJECT TITAN initialized — 50 agents deployed, 3 videos queued, 8 docs created, 17 research topics active'
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => ['status','agents','videos','documents','research','seed']], 400);
}
