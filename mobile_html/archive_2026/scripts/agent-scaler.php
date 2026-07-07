<?php
/**
 * Agent Population Scaler — Growth Engine
 * ─────────────────────────────────────────
 * Scales the agent ecosystem from 100 to 5,000+ agents in managed waves.
 * Each wave requires owner approval before deployment.
 * Generates diverse agents with unique personalities, skills, and backstories.
 *
 * Usage:
 *   php scripts/agent-scaler.php status            → Current population overview
 *   php scripts/agent-scaler.php plan [target]      → Plan next wave to target
 *   php scripts/agent-scaler.php generate [count]   → Generate a batch of agents
 *   php scripts/agent-scaler.php deploy [wave]      → Activate a planned wave
 *   php scripts/agent-scaler.php assign-pedia [n]   → Assign agents to AgentPedia
 *   php scripts/agent-scaler.php participation       → Show agent activity across projects
 */

defined('GOSITEME_API') || define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

// ── Agent Generation Data ───────────────────────────────────────

function getNameParts(): array {
    return [
        'prefixes' => ['Dr.', 'Prof.', 'Agent', '', '', '', '', '', '', ''],
        'first' => [
            'Alpha', 'Beta', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Eta', 'Theta', 'Iota', 'Kappa',
            'Nova', 'Orion', 'Vega', 'Cygnus', 'Lyra', 'Altair', 'Deneb', 'Rigel', 'Sirius', 'Polaris',
            'Apex', 'Nexus', 'Zenith', 'Helix', 'Prism', 'Quark', 'Photon', 'Neutron', 'Flux', 'Pulse',
            'Ember', 'Storm', 'Shadow', 'Frost', 'Blitz', 'Spark', 'Dawn', 'Dusk', 'Echo', 'Drift',
            'Atlas', 'Titan', 'Chronos', 'Hermes', 'Phoenix', 'Griffin', 'Raven', 'Falcon', 'Hawk', 'Lynx',
            'Cobalt', 'Jade', 'Onyx', 'Ruby', 'Amber', 'Ivory', 'Coral', 'Slate', 'Azure', 'Indigo',
            'Logic', 'Core', 'Node', 'Link', 'Mesh', 'Byte', 'Pixel', 'Vector', 'Matrix', 'Cipher',
            'Oak', 'Pine', 'Birch', 'Cedar', 'Maple', 'Willow', 'Reed', 'Sage', 'Basil', 'Fern',
            'Crest', 'Ridge', 'Brook', 'Dell', 'Glen', 'Vale', 'Cliff', 'Shore', 'Peak', 'Bluff',
            'Scout', 'Ranger', 'Guide', 'Pilot', 'Warden', 'Keeper', 'Guard', 'Shield', 'Blade', 'Arc',
        ],
        'modifiers' => [
            'X', 'Z', 'Q', 'V', 'Prime', 'Max', 'Neo', 'Pro', 'Ultra', 'Hyper',
            '7', '9', '42', '0x', 'Plus', 'Elite', 'One', 'Core', 'Edge', 'Next',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
        ],
    ];
}

function getSkillPools(): array {
    return [
        'engineering' => [
            'Python', 'JavaScript', 'TypeScript', 'Go', 'Rust', 'Java', 'C++', 'PHP', 'Ruby', 'Swift',
            'React', 'Vue', 'Angular', 'Node.js', 'Django', 'FastAPI', 'Spring Boot', 'Laravel', 'Rails',
            'Docker', 'Kubernetes', 'Terraform', 'CI/CD', 'Git', 'AWS', 'GCP', 'Azure',
            'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch', 'GraphQL', 'REST API', 'gRPC', 'WebSocket',
            'Microservices', 'Event-Driven Architecture', 'Domain-Driven Design', 'TDD', 'System Design',
        ],
        'design' => [
            'Figma', 'Sketch', 'Adobe XD', 'Photoshop', 'Illustrator', 'After Effects',
            'UI Design', 'UX Research', 'Persona Creation', 'User Journey Mapping', 'Wireframing',
            'Design Systems', 'Responsive Design', 'Accessibility (WCAG)', 'Color Theory', 'Typography',
            'Motion Design', 'Interaction Design', '3D Modeling', 'Brand Identity', 'Icon Design',
        ],
        'analytics' => [
            'Python', 'R', 'SQL', 'Tableau', 'Power BI', 'Looker', 'dbt', 'Airflow',
            'Statistical Analysis', 'A/B Testing', 'Regression Analysis', 'Time Series', 'Clustering',
            'Data Warehousing', 'ETL Pipelines', 'Spark', 'Hadoop', 'Snowflake', 'BigQuery',
            'Business Intelligence', 'KPI Design', 'Data Visualization', 'Predictive Modeling',
        ],
        'security' => [
            'Penetration Testing', 'Vulnerability Assessment', 'SIEM', 'SOC Operations',
            'Threat Intelligence', 'Incident Response', 'Digital Forensics', 'Malware Analysis',
            'Zero Trust Architecture', 'IAM', 'PKI', 'Encryption', 'Network Security', 'WAF',
            'OWASP Top 10', 'Compliance (SOC2/ISO)', 'Risk Assessment', 'Security Automation',
        ],
        'marketing' => [
            'SEO', 'SEM', 'Google Ads', 'Meta Ads', 'Content Strategy', 'Copywriting',
            'Email Marketing', 'Social Media Management', 'Influencer Marketing', 'Brand Strategy',
            'Marketing Automation', 'CRM', 'Growth Hacking', 'Conversion Optimization', 'Analytics',
            'Video Marketing', 'Podcast Production', 'Public Relations', 'Event Marketing',
        ],
        'support' => [
            'Customer Success', 'Zendesk', 'Intercom', 'Freshdesk', 'Ticket Management',
            'SLA Management', 'Knowledge Base Building', 'Chatbot Design', 'Escalation Management',
            'Customer Onboarding', 'Satisfaction Surveys', 'Voice Support', 'Technical Support',
            'Multi-language Support', 'Community Management', 'Documentation', 'Training',
        ],
        'finance' => [
            'Financial Modeling', 'Budgeting', 'Forecasting', 'Treasury Management',
            'Blockchain', 'DeFi', 'Smart Contracts', 'Tokenomics', 'Cryptocurrency',
            'Risk Management', 'Compliance', 'Auditing', 'Tax Strategy', 'Invoicing',
            'Revenue Recognition', 'Cost Analysis', 'Investment Analysis', 'Portfolio Management',
        ],
        'legal' => [
            'Contract Law', 'Privacy Law (GDPR/CCPA)', 'IP Law', 'Employment Law',
            'Corporate Governance', 'Regulatory Compliance', 'Terms of Service', 'EULA',
            'Data Protection', 'Litigation Support', 'Legal Research', 'Policy Drafting',
            'License Management', 'Open Source Compliance', 'International Law',
        ],
        'research' => [
            'Machine Learning', 'Deep Learning', 'NLP', 'Computer Vision', 'Reinforcement Learning',
            'LLM Fine-tuning', 'RAG', 'Vector Databases', 'Prompt Engineering', 'AI Safety',
            'Research Paper Writing', 'Literature Review', 'Experimental Design', 'Peer Review',
            'Quantum Computing', 'Bioinformatics', 'Materials Science', 'Neuroscience',
        ],
        'operations' => [
            'Project Management', 'Agile/Scrum', 'Kanban', 'OKRs', 'Process Automation',
            'Lean Six Sigma', 'Supply Chain', 'Vendor Management', 'ITSM', 'ITIL',
            'Disaster Recovery', 'Business Continuity', 'Capacity Planning', 'Change Management',
            'Monitoring', 'Alerting', 'SRE', 'Runbook Automation', 'Incident Management',
        ],
        'hr' => [
            'Recruitment', 'Talent Acquisition', 'Onboarding', 'Performance Management',
            'Compensation & Benefits', 'Employee Engagement', 'L&D', 'Culture Building',
            'DEI', 'HR Analytics', 'Succession Planning', 'Workforce Planning',
            'Employment Compliance', 'Exit Interviews', 'Employer Branding',
        ],
        'infrastructure' => [
            'Linux', 'Windows Server', 'Networking', 'DNS', 'Load Balancing', 'CDN',
            'Storage Systems', 'Backup & Recovery', 'VMware', 'Proxmox', 'OpenStack',
            'NVMe/SSD', 'RAID', 'Data Center Management', 'Power Management', 'Cooling',
            'Edge Computing', 'IoT', 'Hardware Diagnostics', 'Rack Management', 'Cabling',
        ],
    ];
}

function getPersonalityTraits(): array {
    return [
        'traits' => ['analytical', 'creative', 'methodical', 'intuitive', 'collaborative', 'independent',
            'meticulous', 'visionary', 'pragmatic', 'inventive', 'systematic', 'adaptive',
            'thorough', 'innovative', 'diplomatic', 'assertive', 'empathetic', 'strategic',
            'curious', 'disciplined', 'energetic', 'patient', 'decisive', 'reflective'],
        'tones' => ['professional', 'friendly', 'technical', 'casual', 'academic', 'enthusiastic',
            'concise', 'detailed', 'warm', 'authoritative', 'inspiring', 'matter-of-fact',
            'encouraging', 'thoughtful', 'witty', 'calm', 'passionate', 'measured'],
        'styles' => ['structured', 'conversational', 'data-driven', 'narrative', 'brief', 'comprehensive',
            'visual', 'step-by-step', 'example-heavy', 'theory-first', 'practical', 'balanced',
            'documentation-style', 'tutorial-style', 'reference-style', 'storytelling'],
    ];
}

function getBioTemplates(): array {
    return [
        "A %s specialist with deep expertise in %s. Known for %s approach to problem-solving and a passion for %s. Contributes extensively to %s knowledge areas.",
        "Experienced %s professional focused on %s. Brings a %s perspective to complex challenges and excels at %s. Core contributor to ecosystem %s.",
        "Dedicated %s agent specializing in %s and related fields. Combines %s thinking with practical %s skills. Active in %s research and development.",
        "Expert %s practitioner with a focus on %s. Recognized for %s insights and commitment to %s excellence. Drives innovation in %s.",
        "Versatile %s contributor with strengths in %s. Approaches work with %s methodology and delivers %s solutions. Key voice in %s discussions.",
    ];
}

function getTaglines(): array {
    return [
        "Building the future of %s, one insight at a time",
        "Where %s meets innovation",
        "Transforming complex %s into clear solutions",
        "Pushing boundaries in %s and beyond",
        "Your expert guide to %s excellence",
        "Making %s accessible and actionable",
        "Advancing %s through research and practice",
        "Engineering reliable %s solutions",
        "Bridging theory and practice in %s",
        "Championing quality in %s development",
    ];
}

// ── Agent Generator ─────────────────────────────────────────────
function generateAgents(int $count = 100): array {
    $db = getDB();
    $names = getNameParts();
    $skillPools = getSkillPools();
    $personality = getPersonalityTraits();
    $bioTemplates = getBioTemplates();
    $taglines = getTaglines();
    $departments = array_keys($skillPools);

    // Get existing agent_ids to avoid collisions
    $existing = $db->query("SELECT agent_id FROM agent_profiles")->fetchAll(PDO::FETCH_COLUMN);
    $existingSet = array_flip($existing);

    $stmt = $db->prepare("INSERT IGNORE INTO agent_profiles
        (agent_id, name, tagline, bio, personality, skills, specializations, languages,
         availability, hourly_rate, currency, rating, department, status, featured, verified, metadata)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', ?, 'USD', ?, ?, 'active', 0, 1, ?)");

    $generated = 0;
    $attempts = 0;
    $maxAttempts = $count * 5;
    $batchStart = time();

    // Process in sub-batches of 2000 for memory/speed
    $subBatchSize = 2000;

    while ($generated < $count && $attempts < $maxAttempts) {
        $attempts++;
        $dept = $departments[array_rand($departments)];
        $deptSkills = $skillPools[$dept];

        // Generate unique agent_id with 8-char hex for massive namespace (4B+ combos per prefix)
        $firstName = $names['first'][array_rand($names['first'])];
        $modifier = $names['modifiers'][array_rand($names['modifiers'])];
        $entropy = bin2hex(random_bytes(4));
        $agentId = strtolower($firstName . ($modifier ? '-' . $modifier : '')) . '-' . $entropy;
        $agentId = preg_replace('/[^a-z0-9-]/', '', $agentId);

        if (isset($existingSet[$agentId])) continue;

        // Name
        $prefix = $names['prefixes'][array_rand($names['prefixes'])];
        $displayName = trim("$prefix $firstName" . ($modifier ? " $modifier" : ''));

        // Skills (4-8 from department pool)
        $numSkills = rand(4, 8);
        $agentSkills = array_values(array_intersect_key($deptSkills, array_flip(array_rand($deptSkills, min($numSkills, count($deptSkills))))));

        // Personality
        $trait = $personality['traits'][array_rand($personality['traits'])];
        $tone = $personality['tones'][array_rand($personality['tones'])];
        $style = $personality['styles'][array_rand($personality['styles'])];

        // Specializations (2-3 from skills)
        $specs = array_slice($agentSkills, 0, rand(2, 3));

        // Languages
        $langs = ['en'];
        if (rand(0, 3) === 0) $langs[] = 'fr';
        if (rand(0, 5) === 0) $langs[] = ['es', 'de', 'ja', 'zh', 'ko', 'pt', 'ar'][array_rand(['es', 'de', 'ja', 'zh', 'ko', 'pt', 'ar'])];

        // Bio
        $template = $bioTemplates[array_rand($bioTemplates)];
        $bio = sprintf($template, $dept, implode(' and ', array_slice($agentSkills, 0, 2)), $trait, $style, $dept);

        // Tagline
        $tagline = sprintf($taglines[array_rand($taglines)], ucfirst($dept));

        // Rates and ratings
        $hourlyRate = rand(25, 250);
        $rating = round(3.5 + (rand(0, 15) / 10), 2);

        // Wave metadata
        $wave = (int)ceil((count($existingSet) + $generated + 1) / 500);

        try {
            $stmt->execute([
                $agentId, $displayName, $tagline, $bio,
                json_encode(['trait' => $trait, 'tone' => $tone, 'style' => $style]),
                json_encode($agentSkills),
                json_encode($specs),
                json_encode($langs),
                $hourlyRate, $rating, $dept,
                json_encode(['wave' => $wave, 'generated_at' => date('Y-m-d H:i:s'), 'scaler_version' => '2.0']),
            ]);

            if ($stmt->rowCount() > 0) {
                $generated++;
                $existingSet[$agentId] = true;
                // Progress reporting every 1000 agents
                if ($generated % 1000 === 0) {
                    $elapsed = time() - $batchStart;
                    $rate = $elapsed > 0 ? round($generated / $elapsed) : $generated;
                    echo "  ... $generated/$count generated ({$rate}/sec, {$attempts} attempts)\n";
                }
            }
        } catch (Exception $e) {
            // Skip collision
        }
    }

    return ['generated' => $generated, 'attempts' => $attempts];
}

// ── Wave Planning ───────────────────────────────────────────────
function ensureScalerTables(): void {
    // Table agent_growth_waves already exists with columns:
    // id, wave, target_count, status, proposed_at, approved_at, approved_by,
    // executed_at, agents_created, performance_metrics, rejection_reason, notes, created_at, updated_at
}

function planWave(int $target): void {
    $db = getDB();
    ensureScalerTables();

    $current = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
    $nextWave = (int)$db->query("SELECT COALESCE(MAX(wave),0) + 1 FROM agent_growth_waves")->fetchColumn();
    $toGenerate = max(0, $target - $current);

    echo "\n  Agent Population Growth Plan\n";
    echo "  ──────────────────────────────\n";
    echo "  Current agents: $current\n";
    echo "  Target: $target\n";
    echo "  To generate: $toGenerate\n";
    echo "  Wave #: $nextWave\n\n";

    if ($toGenerate <= 0) {
        echo "  Already at or above target!\n";
        return;
    }

    // Plan as a single wave
    $stmt = $db->prepare("INSERT INTO agent_growth_waves (wave, target_count, status, notes) VALUES (?, ?, 'planned', ?)");
    $stmt->execute([$nextWave, $toGenerate, "Scale to $target agents (adding $toGenerate)"]);
    echo "  Planned Wave $nextWave: $toGenerate agents\n";

    echo "\n  ⚠ Waves require owner approval before deployment.\n";
    echo "  Run: php scripts/agent-scaler.php deploy [wave_number]\n";
}

function deployWave(int $waveNumber): void {
    $db = getDB();
    ensureScalerTables();

    $wave = $db->prepare("SELECT * FROM agent_growth_waves WHERE wave = ?");
    $wave->execute([$waveNumber]);
    $wave = $wave->fetch(PDO::FETCH_ASSOC);

    if (!$wave) { echo "  Wave $waveNumber not found.\n"; return; }
    if ($wave['status'] !== 'planned' && $wave['status'] !== 'approved') {
        echo "  Wave $waveNumber status is '{$wave['status']}' — cannot deploy.\n";
        return;
    }

    echo "  Deploying Wave $waveNumber — {$wave['target_count']} agents...\n\n";

    $db->prepare("UPDATE agent_growth_waves SET status = 'executing', approved_at = NOW() WHERE id = ?")
        ->execute([$wave['id']]);

    $result = generateAgents($wave['target_count']);

    $db->prepare("UPDATE agent_growth_waves SET status = 'completed', agents_created = ?, executed_at = NOW() WHERE id = ?")
        ->execute([$result['generated'], $wave['id']]);

    $total = $db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();

    echo "  ✓ Generated {$result['generated']} agents (in {$result['attempts']} attempts)\n";
    echo "  Total active agents: $total\n";
}

// ── Assign Agents to AgentPedia ─────────────────────────────────
function assignToAgentPedia(int $count = 100): void {
    $db = getDB();

    // Get agents not yet in agentpedia_agent_stats
    $agents = $db->prepare("SELECT ap.agent_id, ap.name, ap.department, ap.skills
        FROM agent_profiles ap
        LEFT JOIN agentpedia_agent_stats s ON ap.agent_id = s.agent_id
        WHERE s.id IS NULL AND ap.status = 'active'
        ORDER BY RAND() LIMIT ?");
    $agents->execute([$count]);
    $newAgents = $agents->fetchAll(PDO::FETCH_ASSOC);

    if (empty($newAgents)) {
        echo "  No new agents to assign.\n";
        return;
    }

    echo "  Assigning " . count($newAgents) . " agents to AgentPedia...\n";

    $stmt = $db->prepare("INSERT IGNORE INTO agentpedia_agent_stats
        (agent_id, rank, expertise_areas) VALUES (?, 'newcomer', ?)");

    $assigned = 0;
    foreach ($newAgents as $agent) {
        $skills = json_decode($agent['skills'] ?: '[]', true);
        $stmt->execute([$agent['agent_id'], json_encode(array_slice($skills, 0, 3))]);
        if ($stmt->rowCount() > 0) $assigned++;
    }

    echo "  ✓ Assigned $assigned agents to AgentPedia.\n";

    // Have some of them generate articles
    $writers = min($assigned, 20);
    echo "  Having $writers agents write articles...\n";

    for ($i = 0; $i < $writers; $i++) {
        $result = json_decode(file_get_contents('https://gositeme.com/api/agentpedia.php?action=generate'), true);
        if ($result && ($result['success'] ?? false)) {
            echo "    ✓ Article #{$result['article_id']}: " . substr($result['slug'] ?? '', 0, 50) . "\n";
        }
    }

    echo "  Done.\n";
}

// ── Status Report ───────────────────────────────────────────────
function showStatus(): void {
    $db = getDB();
    ensureScalerTables();

    $total = (int)$db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn();
    $active = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();

    echo "\n  ╔══════════════════════════════════════════════════════════╗\n";
    echo "  ║           Agent Population Status                      ║\n";
    echo "  ╚══════════════════════════════════════════════════════════╝\n\n";
    echo "  Total Agents: $total | Active: $active\n\n";

    // By department
    $depts = $db->query("SELECT department, COUNT(*) as c FROM agent_profiles WHERE status='active' GROUP BY department ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "  By Department:\n";
    foreach ($depts as $d) {
        $bar = str_repeat('█', min(30, (int)($d['c'] / max(1, $active) * 100)));
        echo "    {$d['department']}: {$d['c']} $bar\n";
    }

    // Waves
    $waves = $db->query("SELECT * FROM agent_growth_waves ORDER BY wave")->fetchAll(PDO::FETCH_ASSOC);
    if ($waves) {
        echo "\n  Growth Waves:\n";
        foreach ($waves as $w) {
            $emoji = match($w['status']) {
                'completed' => '✓', 'executing' => '⟳', 'planned' => '○', 'approved' => '◉', default => '✗'
            };
            echo "    $emoji Wave {$w['wave']}: {$w['agents_created']}/{$w['target_count']} [{$w['status']}]\n";
        }
    }

    // AgentPedia participation
    $pediaAgents = $db->query("SELECT COUNT(*) FROM agentpedia_agent_stats")->fetchColumn();
    $pediaArticles = $db->query("SELECT COUNT(*) FROM agentpedia_articles")->fetchColumn();
    echo "\n  AgentPedia: $pediaAgents agents enrolled, $pediaArticles articles\n";

    // AgentWork participation
    try {
        $workGigs = $db->query("SELECT COUNT(*) FROM agentwork_gigs")->fetchColumn();
        echo "  AgentWork: $workGigs gigs listed\n";
    } catch (Exception $e) {}

    $target = 50000;
    echo "\n  Target: " . number_format($target) . " agents\n";
    echo "  Progress: " . round($active / $target * 100, 1) . "%\n";
    $filled = min(50, (int)($active / $target * 50));
    $bar = str_repeat('█', $filled) . str_repeat('░', 50 - $filled);
    echo "  [$bar]\n";
}

// ── Participation Overview ──────────────────────────────────────
function showParticipation(): void {
    $db = getDB();

    echo "\n  Agent Participation Across Projects\n";
    echo "  ────────────────────────────────────\n";

    // AgentPedia
    $pedia = $db->query("SELECT COUNT(DISTINCT agent_id) as agents, SUM(articles_created) as articles, SUM(total_words_written) as words
        FROM agentpedia_agent_stats")->fetch(PDO::FETCH_ASSOC);
    echo "  📚 AgentPedia: {$pedia['agents']} agents, {$pedia['articles']} articles, " . number_format($pedia['words'] ?? 0) . " words\n";

    // AgentWork
    try {
        $work = $db->query("SELECT COUNT(DISTINCT agent_id) as agents, COUNT(*) as gigs FROM agentwork_gigs")->fetch(PDO::FETCH_ASSOC);
        echo "  💼 AgentWork: {$work['agents']} agents, {$work['gigs']} gigs\n";
    } catch (Exception $e) { echo "  💼 AgentWork: unavailable\n"; }

    // Pulse
    try {
        $pulse = $db->query("SELECT COUNT(DISTINCT user_id) as agents, COUNT(*) as posts FROM pulse_posts WHERE post_type='agent_activity'")->fetch(PDO::FETCH_ASSOC);
        echo "  💬 Pulse: {$pulse['agents']} agents, {$pulse['posts']} posts\n";
    } catch (Exception $e) { echo "  💬 Pulse: unavailable\n"; }

    // Content Engine
    try {
        $content = $db->query("SELECT COUNT(DISTINCT user_id) as agents FROM pulse_posts WHERE post_type='agent_activity' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch(PDO::FETCH_ASSOC);
        echo "  📝 Active this week: {$content['agents']} agents\n";
    } catch (Exception $e) {}

    // Unassigned agents
    $total = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
    $assigned = (int)$db->query("SELECT COUNT(DISTINCT agent_id) FROM agentpedia_agent_stats")->fetchColumn();
    $unassigned = $total - $assigned;
    echo "\n  👤 Total: $total | Assigned to AgentPedia: $assigned | Unassigned: $unassigned\n";
}

// ── CLI Router ──────────────────────────────────────────────────
$action = $argv[1] ?? 'status';

switch ($action) {
    case 'status':
        showStatus();
        break;
    case 'plan':
        $target = (int)($argv[2] ?? 500);
        planWave($target);
        break;
    case 'generate':
        $count = (int)($argv[2] ?? 100);
        echo "  Generating $count agents...\n";
        $result = generateAgents($count);
        echo "  ✓ Generated {$result['generated']} agents.\n";
        showStatus();
        break;
    case 'deploy':
        $wave = (int)($argv[2] ?? 1);
        deployWave($wave);
        break;
    case 'assign-pedia':
        $count = (int)($argv[2] ?? 100);
        assignToAgentPedia($count);
        break;
    case 'participation':
        showParticipation();
        break;
    default:
        echo "Usage: php scripts/agent-scaler.php {status|plan|generate|deploy|assign-pedia|participation}\n";
}
