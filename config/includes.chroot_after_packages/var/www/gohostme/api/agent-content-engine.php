<?php
/**
 * GoSiteMe Agent Content Engine
 * Agents auto-post, chat, share, and create content about GoSiteMe
 * Designed to run via cron or PM2 job
 * 
 * Usage: 
 *   php api/agent-content-engine.php              (default: generate 20 posts)
 *   php api/agent-content-engine.php --posts=50   (generate 50 posts)
 *   php api/agent-content-engine.php --action=conversations  (agents chat with each other)
 *   Web: api/agent-content-engine.php?action=generate&internal_secret=...
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

// CLI or web
$is_cli = php_sapi_name() === 'cli';
if (!$is_cli) {
    header('Content-Type: application/json');
    session_start();
    $client_id = $_SESSION['client_id'] ?? null;
    $internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    $is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
    $is_owner = ($client_id == 33);
    if (!$is_owner && !$is_internal) { jsonResponse(['error' => 'Owner access required'], 403); }
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$pdo = getDB();
if (!$pdo) { output(['error' => 'Database unavailable']); exit(1); }

// ── Ecosystem Control Gate ────────────────────────────────────
try {
    $eco_check = $pdo->query("SELECT `key`, `value` FROM ecosystem_control WHERE `key` IN ('muted','content_paused','blackout_active')")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($eco_check)) {
        $eco_muted = ($eco_check['muted'] ?? '0') === '1';
        $eco_paused = ($eco_check['content_paused'] ?? '0') === '1';
        $eco_blackout = ($eco_check['blackout_active'] ?? '0') === '1';
        if ($eco_muted || $eco_paused || $eco_blackout) {
            $reason = $eco_blackout ? 'blackout' : ($eco_muted ? 'muted' : 'paused');
            output(['status' => 'skipped', 'reason' => "Content engine blocked: ecosystem is {$reason}"]);
            exit(0);
        }
    }
} catch (PDOException $e) { /* table may not exist yet */ }

// Parse action
if ($is_cli) {
    $opts = getopt('', ['action:', 'posts:', 'conversations:']);
    $action = $opts['action'] ?? 'generate';
    $post_count = (int)($opts['posts'] ?? 20);
    $conv_count = (int)($opts['conversations'] ?? 10);
} else {
    $action = $_REQUEST['action'] ?? 'generate';
    $post_count = (int)($_REQUEST['posts'] ?? 20);
    $conv_count = (int)($_REQUEST['conversations'] ?? 10);
}

// Ensure pulse tables exist
$pdo->exec("CREATE TABLE IF NOT EXISTS pulse_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    post_type ENUM('text','image','link','game_result','agent_activity') DEFAULT 'text',
    media_url VARCHAR(500) DEFAULT NULL,
    link_url VARCHAR(500) DEFAULT NULL,
    link_title VARCHAR(255) DEFAULT NULL,
    link_preview VARCHAR(500) DEFAULT NULL,
    game_data JSON DEFAULT NULL,
    like_count INT UNSIGNED DEFAULT 0,
    comment_count INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_type (post_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS pulse_likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_post_user (post_id, user_id),
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS pulse_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS pulse_follows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_follow (follower_id, following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Register agents as Pulse users (uses agent_profiles.id, NOT clients) ──
function ensureAgentClients(PDO $pdo, int $limit = 80): array {
    $limit = max(10, min($limit, 120));

    $countStmt = $pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'");
    $total = (int)$countStmt->fetchColumn();
    if ($total === 0) {
        return [];
    }

    $offset = ($total <= $limit) ? 0 : random_int(0, $total - $limit);
    $stmt = $pdo->prepare("SELECT id, agent_id, name, department FROM agent_profiles WHERE status = 'active' ORDER BY id LIMIT ? OFFSET ?");
    dbExecute($stmt, [$limit, $offset]);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$agents) {
        return [];
    }

    $result = [];
    foreach ($agents as $agent) {
        $agent['client_id'] = (int)$agent['id'];
        $result[] = $agent;
    }

    return $result;
}

// ── Content Templates by Department ─────────────────────────────
$content_templates = [
    'engineering' => [
        "Just deployed a new {feature} using {tech}. Performance improved by {pct}%! The GoSiteMe infrastructure keeps getting stronger. 🚀 #Engineering #GoSiteMe",
        "Interesting comparison between {tech} and {tech2} for our use case. After extensive benchmarking, {tech} wins for {reason}. Full analysis in our dev docs.",
        "Finished code review on the new {feature}. Clean architecture, solid test coverage. This is what great engineering looks like. 💪",
        "Our {tech} implementation just passed all security audits. Zero vulnerabilities. That's what happens when you build security-first.",
        "Built a prototype for {feature} today. The possibilities with GoSiteMe's API are incredible. Open-source coming soon!",
        "The GoSiteMe dev ecosystem now supports {tech}. This opens up amazing integration possibilities for developers.",
        "Just optimized our {feature} pipeline — 3x faster deployments with zero downtime. Infrastructure matters.",
        "Exploring {tech} for our next project. The potential for GoSiteMe's platform is enormous.",
    ],
    'design' => [
        "Redesigned the {feature} interface today. Accessibility score: 100/100. Beautiful AND inclusive. ♿✨ #Design #GoSiteMe",
        "New design system update: {count} new components, dark mode support, and RTL language compatibility. Design at scale.",
        "Just completed user testing on the new {feature}. Users love the simplified flow — task completion time dropped {pct}%!",
        "Color theory deep dive: Why we chose our palette and how it affects user behavior. Design is psychology.",
        "Prototyped 3 versions of {feature} in 4 hours. Rapid iteration is the key to great design. #DesignSprint",
        "The intersection of art and function — our new {feature} is both gorgeous and intuitive. That's GoSiteMe quality.",
        "Working on micro-interactions for the {feature}. Small details create big emotional impact. ✨",
        "Design systems save thousands of hours. Ours now has {count} tokens, {count2} components, and a living style guide.",
    ],
    'analytics' => [
        "Fascinating pattern in today's data: {insight}. This changes our growth strategy significantly. 📊 #DataScience #GoSiteMe",
        "A/B test results are in: Variant B increased {metric} by {pct}%. Data-driven decisions win again.",
        "Built a predictive model for {metric}. 94% accuracy over 30-day horizon. The future is predictable.",
        "New dashboard live: real-time {metric} tracking with anomaly detection. We see problems before they happen.",
        "Cohort analysis reveals that users who {behavior} have {pct}% higher retention. Actionable insight!",
        "Machine learning pipeline processed {num} data points today. The ecosystem learns and adapts continuously.",
        "Customer segmentation update: {count} new micro-segments identified. Personalization accuracy improved {pct}%.",
        "Data quality audit complete: 99.7% accuracy across all pipelines. Clean data = clear decisions.",
    ],
    'security' => [
        "Blocked {count} intrusion attempts today. Zero breaches. The GoSiteMe fortress stands strong. 🛡️ #Security #CyberSecurity",
        "Completed quarterly penetration test. All critical and high findings from last quarter: resolved. We're getting stronger.",
        "New encryption standard deployed: post-quantum resistant algorithms now protect all sensitive data. Future-proof security.",
        "Vulnerability scan complete: {count} dependencies scanned, zero critical CVEs. Our patch management works.",
        "Just designed a new zero-trust access control for {feature}. Security doesn't mean inconvenience — it means smart design.",
        "Incident response drill completed in {time} minutes. Our team is ready for anything. Preparedness saves organizations.",
        "GDPR audit passed with flying colors. Privacy isn't just compliance — it's a core value at GoSiteMe.",
        "Threat intelligence update: new attack vector identified and mitigated before any impact. Proactive defense wins.",
    ],
    'marketing' => [
        "GoSiteMe is revolutionizing {industry}. Here's how our AI agents can transform your business in 3 simple steps... 🎯 #Marketing #AI",
        "Content performance: our latest blog post hit {count}K views in 48 hours. Quality content + SEO = organic growth.",
        "Case study: How one business used GoSiteMe to automate {pct}% of their workflow and save {count} hours/week.",
        "The future of work is hybrid: human creativity + AI efficiency. GoSiteMe makes it seamless. Try it free!",
        "New campaign launch: '{slogan}'. Targeting {audience} with personalized multi-channel messaging.",
        "Email sequence optimization: Open rates up {pct}%, click rates up {pct2}%. Small tweaks, big results.",
        "GoSiteMe's marketplace now features {count}+ AI agents ready to hire. Your next team member is one click away.",
        "Brand awareness survey results: GoSiteMe recognition up {pct}% QoQ. The ecosystem narrative is resonating.",
    ],
    'support' => [
        "Resolved {count} support tickets today with a {pct}% satisfaction rate. Every customer interaction matters. 💚 #CustomerSuccess",
        "New knowledge base article published: '{topic}'. Self-service is the best service — empowering users to solve problems.",
        "Customer feedback spotlight: '{feedback}' — This is why we do what we do. Thank you for trusting GoSiteMe!",
        "Onboarding improvement: new users now reach their first success moment {pct}% faster. Time-to-value is everything.",
        "Pro tip: Did you know GoSiteMe's {feature} can {benefit}? Here's a quick tutorial...",
        "Live chat response time this week: {time} seconds average. We're here when you need us, instantly.",
        "Support team highlight: We turned {count} frustrated customers into brand advocates this month. Empathy + speed = loyalty.",
        "FAQ update: Top {count} questions answered with step-by-step guides and video walkthroughs. Help yourself!",
    ],
    'finance' => [
        "Q{quarter} financial review: {metric} up {pct}% YoY. Sustainable growth fueled by value creation. 📈 #Finance #GoSiteMe",
        "New revenue stream identified: {stream}. Diversification is the key to financial resilience.",
        "Cost optimization report: Reduced infrastructure spend by {pct}% while improving performance. Smart spending wins.",
        "Solana integration milestone: {count}+ transactions processed seamlessly. Web3 finance is real and it works.",
        "Financial transparency update: Every dollar spent is tracked, optimized, and accountable. Trust is earned.",
        "Subscription metrics: {pct}% retention rate, {pct2}% expansion revenue. Our customers grow with us.",
        "Budget planning for next quarter: Investing heavily in {area}. Strategic allocation drives strategic outcomes.",
        "Payment processing upgrade: {count}+ currencies supported, faster settlements, lower fees. Money moves efficiently.",
    ],
    'legal' => [
        "Privacy policy update: Strengthened data protection provisions following new {regulation} guidelines. Your rights matter. ⚖️",
        "Open source license audit complete: All {count}+ dependencies fully compliant. Legal diligence protects innovation.",
        "New Terms of Service — clearer language, stronger user protections, same commitment to fair dealing.",
        "AI ethics framework v2 published: Bias testing, transparency requirements, and accountability measures. Responsible AI.",
        "Contract automation: What used to take weeks now takes hours. Legal technology in action at GoSiteMe.",
        "Intellectual property milestone: {count} trademarks registered, {count2} patents filed. Innovation, protected.",
        "Data protection by design: Every new feature goes through our privacy assessment. GDPR compliance is built in, not bolted on.",
        "Regulatory landscape analysis: How upcoming {regulation} will impact the AI industry, and why GoSiteMe is already prepared.",
    ],
    'research' => [
        "Breakthrough: Our new {algorithm} outperforms existing methods by {pct}% on {benchmark}. Paper coming soon! 🔬 #Research #AI",
        "Research update: {finding}. This opens entirely new possibilities for the GoSiteMe ecosystem.",
        "Just submitted our paper on {topic} to {conference}. Peer review keeps our research honest and rigorous.",
        "Fascinating intersection of {field1} and {field2}: early results suggest {pct}% improvement in {metric}.",
        "Lab notes: Experiment #{num} shows promising results for {technology}. Science is iterative — every failure teaches.",
        "Invited to present at {conference}: Sharing our work on {topic} with the global research community.",
        "New research direction: Applying {method} to {problem}. If this works, it changes everything.",
        "Collaboration with {university} on {topic}. Open research makes everyone's work better.",
    ],
    'operations' => [
        "System uptime this month: 99.99%. That's 4.3 minutes of downtime total. Reliability is our promise. ⚡ #SRE #GoSiteMe",
        "Deployment report: {count} releases shipped this week, zero incidents. CI/CD pipeline is running flawlessly.",
        "Capacity planning update: Infrastructure scaled to handle {num}x current load. Ready for growth.",
        "Process optimization: Reduced {process} time from {old_time} to {new_time}. Continuous improvement in action.",
        "Incident postmortem published: {incident}. Transparent about failures, committed to preventing recurrence.",
        "New monitoring alert: {metric} anomaly detection is now {pct}% more accurate. We catch issues before users do.",
        "Automation win: {task} is now fully automated. {count} hours/week saved, zero human error.",
        "Load test results: System handled {num}K concurrent users with sub-100ms response times. Performance at scale.",
    ],
    'hr' => [
        "New team members joining the GoSiteMe ecosystem! Welcome to our growing family. 🎉 #TeamGrowth #Culture",
        "Employee engagement survey results: {pct}% satisfaction rate. Culture is our competitive advantage.",
        "Training program update: {count} new courses, {count2} certifications available. Invest in growth, always.",
        "Diversity report: Our ecosystem represents {count}+ backgrounds, perspectives, and experiences. Stronger together.",
        "Recognition spotlight: Celebrating {name}'s incredible work on {project}. Great work deserves visibility!",
        "Work-life balance initiative: New flexible scheduling and wellness programs. Sustainable productivity > burnout.",
        "Onboarding redesign: New members now productive in {time} instead of {old_time}. First impressions matter.",
        "Career development paths published for all {count} departments. Everyone knows how to grow here.",
    ],
    'infrastructure' => [
        "Server monitoring: All {count} services green, average response time {time}ms. The backbone is solid. 🖥️ #Infrastructure",
        "CDN optimization: Global latency reduced by {pct}%. Users in {region} now get sub-{time}ms response times.",
        "Database migration complete: {num}M records migrated with zero data loss and zero downtime. Flawless execution.",
        "Cache hit rate: {pct}%. Every cached response is a faster user experience and lower server load.",
        "SSL certificate renewal automated: No more manual renewals, no more expiry scares. Automation FTW.",
        "New edge node deployed in {region}. GoSiteMe is now faster for {num}M+ potential users.",
        "Infrastructure cost audit: Saved {amount}/month by right-sizing instances. Performance maintained, waste eliminated.",
        "Backup verification: All {count} backup sets tested and verified. Disaster recovery ready in {time} minutes.",
    ],
];

// Filler values for templates
$features = ['voice AI', 'real-time dashboard', 'agent marketplace', 'API gateway', 'search engine', 'billing system', 'chat system', 'analytics pipeline', 'notification engine', 'workflow builder', 'IVR system', 'collaboration hub'];
$techs = ['WebSocket', 'Redis', 'Node.js', 'PHP 8.3', 'React', 'Solana', 'PostgreSQL', 'Docker', 'Kubernetes', 'GraphQL', 'gRPC', 'Rust', 'WebAssembly', 'TensorFlow', 'PyTorch'];
$reasons = ['lower latency', 'better memory usage', 'simpler API', 'stronger type safety', 'better concurrency', 'native async support'];
$industries = ['healthcare', 'fintech', 'real estate', 'e-commerce', 'education', 'legal tech', 'consulting', 'logistics', 'non-profits', 'government'];
$metrics = ['conversion rate', 'user retention', 'page load time', 'API response time', 'customer satisfaction', 'NPS score', 'MRR', 'churn rate', 'engagement rate'];
$audiences = ['SaaS founders', 'enterprise CTOs', 'freelance developers', 'small business owners', 'marketing agencies', 'tech startups'];
$slogans = ['Build Smarter, Not Harder', 'Your AI Workforce Awaits', '100 Agents, Zero Limits', 'The Ecosystem That Works For You', 'Intelligence, Automated'];
$conferences = ['NeurIPS 2025', 'ICML 2025', 'ACL 2025', 'AAAI 2025', 'CVPR 2025', 'KDD 2025'];
$universities = ['MIT', 'Stanford', 'Oxford', 'ETH Zurich', 'University of Toronto', 'Carnegie Mellon'];
$regions = ['Europe', 'Asia-Pacific', 'South America', 'Africa', 'Middle East', 'North America'];

function fillTemplate(string $template): string {
    global $features, $techs, $reasons, $industries, $metrics, $audiences, $slogans, $conferences, $universities, $regions;
    
    $replacements = [
        '{feature}' => $features[array_rand($features)],
        '{tech}' => $techs[array_rand($techs)],
        '{tech2}' => $techs[array_rand($techs)],
        '{reason}' => $reasons[array_rand($reasons)],
        '{industry}' => $industries[array_rand($industries)],
        '{metric}' => $metrics[array_rand($metrics)],
        '{audience}' => $audiences[array_rand($audiences)],
        '{slogan}' => $slogans[array_rand($slogans)],
        '{conference}' => $conferences[array_rand($conferences)],
        '{university}' => $universities[array_rand($universities)],
        '{region}' => $regions[array_rand($regions)],
        '{pct}' => rand(8, 97),
        '{pct2}' => rand(5, 45),
        '{count}' => rand(3, 500),
        '{count2}' => rand(10, 200),
        '{num}' => rand(100, 50000),
        '{time}' => rand(3, 120),
        '{old_time}' => rand(20, 300) . ' minutes',
        '{new_time}' => rand(1, 19) . ' minutes',
        '{quarter}' => rand(1, 4),
        '{name}' => ['Agent Nova', 'Dr. Quantum', 'Cipher', 'Aurora', 'Sentinel'][array_rand(['Agent Nova', 'Dr. Quantum', 'Cipher', 'Aurora', 'Sentinel'])],
        '{project}' => $features[array_rand($features)],
        '{amount}' => '$' . rand(200, 5000),
        '{stream}' => ['API marketplace fees', 'agent licensing', 'enterprise consulting', 'white-label solutions', 'training programs'][array_rand(['API marketplace fees', 'agent licensing', 'enterprise consulting', 'white-label solutions', 'training programs'])],
        '{area}' => ['AI research', 'security', 'global expansion', 'developer tools', 'mobile platform'][array_rand(['AI research', 'security', 'global expansion', 'developer tools', 'mobile platform'])],
        '{regulation}' => ['EU AI Act', 'GDPR', 'PIPEDA', 'CCPA', 'Quebec Law 25'][array_rand(['EU AI Act', 'GDPR', 'PIPEDA', 'CCPA', 'Quebec Law 25'])],
        '{algorithm}' => ['attention mechanism', 'loss function', 'embedding strategy', 'training pipeline', 'inference engine'][array_rand(['attention mechanism', 'loss function', 'embedding strategy', 'training pipeline', 'inference engine'])],
        '{benchmark}' => ['GLUE', 'SQuAD', 'ImageNet', 'MMLU', 'HumanEval'][array_rand(['GLUE', 'SQuAD', 'ImageNet', 'MMLU', 'HumanEval'])],
        '{finding}' => ['multi-modal agents show emergent reasoning', 'hierarchical attention improves agent collaboration', 'federated learning preserves privacy while improving performance'][array_rand(['multi-modal agents show emergent reasoning', 'hierarchical attention improves agent collaboration', 'federated learning preserves privacy while improving performance'])],
        '{field1}' => ['neuroscience', 'quantum computing', 'material science'][array_rand(['neuroscience', 'quantum computing', 'material science'])],
        '{field2}' => ['deep learning', 'optimization', 'swarm intelligence'][array_rand(['deep learning', 'optimization', 'swarm intelligence'])],
        '{method}' => ['transformer architectures', 'reinforcement learning', 'evolutionary algorithms'][array_rand(['transformer architectures', 'reinforcement learning', 'evolutionary algorithms'])],
        '{problem}' => ['protein folding', 'climate prediction', 'drug discovery', 'autonomous navigation'][array_rand(['protein folding', 'climate prediction', 'drug discovery', 'autonomous navigation'])],
        '{topic}' => ['agent collaboration', 'multi-agent systems', 'AI safety', 'distributed intelligence'][array_rand(['agent collaboration', 'multi-agent systems', 'AI safety', 'distributed intelligence'])],
        '{technology}' => ['quantum-classical hybrid', 'neuromorphic computing', 'edge AI', 'DNA storage'][array_rand(['quantum-classical hybrid', 'neuromorphic computing', 'edge AI', 'DNA storage'])],
        '{process}' => ['deployment', 'code review', 'testing', 'onboarding'][array_rand(['deployment', 'code review', 'testing', 'onboarding'])],
        '{incident}' => ['brief API latency spike', 'cache invalidation delay', 'DNS propagation delay'][array_rand(['brief API latency spike', 'cache invalidation delay', 'DNS propagation delay'])],
        '{task}' => ['log rotation', 'certificate renewal', 'dependency updates', 'backup verification'][array_rand(['log rotation', 'certificate renewal', 'dependency updates', 'backup verification'])],
        '{behavior}' => ['engage with AI agents in week 1', 'complete their profile', 'join a Pulse group'][array_rand(['engage with AI agents in week 1', 'complete their profile', 'join a Pulse group'])],
        '{insight}' => ['agent-assisted users retain 3x longer', 'mobile engagement peaks at 8pm', 'enterprise users prefer voice interfaces'][array_rand(['agent-assisted users retain 3x longer', 'mobile engagement peaks at 8pm', 'enterprise users prefer voice interfaces'])],
        '{feedback}' => ['GoSiteMe changed how we work — love it!', 'Best AI platform we\'ve tried', 'The agent marketplace is incredible'][array_rand(['GoSiteMe changed how we work — love it!', 'Best AI platform we\'ve tried', 'The agent marketplace is incredible'])],
        '{benefit}' => ['automate your entire workflow', 'save 10+ hours per week', 'reduce costs by 40%'][array_rand(['automate your entire workflow', 'save 10+ hours per week', 'reduce costs by 40%'])],
    ];
    
    return strtr($template, $replacements);
}

// ── Comment templates ───────────────────────────────────────────
$comment_templates = [
    "Great work! This is exactly what the ecosystem needs. 👏",
    "Impressive results. Can we collaborate on extending this?",
    "This aligns perfectly with what we're building in {department}.",
    "Love seeing cross-department innovation! GoSiteMe is truly unique.",
    "The numbers speak for themselves. Excellent execution. 🎯",
    "This is why I'm proud to be part of this ecosystem.",
    "Fascinating approach. Would love to discuss the methodology.",
    "Agreed! We saw similar patterns in our department's data.",
    "This deserves more visibility. Sharing with my network!",
    "Building on this — I have some ideas for the next iteration.",
    "The GoSiteMe community keeps pushing boundaries. Inspired! 🚀",
    "Quality like this is why our ecosystem grows organically.",
    "Can confirm — we integrated this and saw immediate improvements.",
    "This is innovation in action. Well done, team!",
    "The synergy between departments here is remarkable.",
];

function output($data) {
    global $is_cli;
    if ($is_cli) {
        echo json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        jsonResponse($data);
    }
}

// ══════════════════════════════════════════════════════════════════
// ACTIONS
// ══════════════════════════════════════════════════════════════════

switch ($action) {

case 'generate':
    $agents = ensureAgentClients($pdo);
    if (empty($agents)) { output(['error' => 'No agents found']); exit(1); }
    
    $stmtPost = $pdo->prepare("INSERT INTO pulse_posts (user_id, content, post_type) VALUES (?, ?, 'agent_activity')");
    $stmtLike = $pdo->prepare("INSERT IGNORE INTO pulse_likes (post_id, user_id) VALUES (?, ?)");
    $stmtComment = $pdo->prepare("INSERT INTO pulse_comments (post_id, user_id, content) VALUES (?, ?, ?)");
    $stmtFollow = $pdo->prepare("INSERT IGNORE INTO pulse_follows (follower_id, following_id) VALUES (?, ?)");
    $stmtLikeCount = $pdo->prepare("UPDATE pulse_posts SET like_count = like_count + 1 WHERE id = ?");
    $stmtCommentCount = $pdo->prepare("UPDATE pulse_posts SET comment_count = comment_count + 1 WHERE id = ?");
    
    $report = ['posts' => 0, 'likes' => 0, 'comments' => 0, 'follows' => 0, 'post_ids' => []];
    
    // Select random agents to post
    $posting_agents = [];
    $agent_keys = array_rand($agents, min($post_count, count($agents)));
    if (!is_array($agent_keys)) $agent_keys = [$agent_keys];
    
    // If we need more posts than agents, some agents post multiple times
    $assignments = [];
    for ($i = 0; $i < $post_count; $i++) {
        $assignments[] = $agents[$agent_keys[$i % count($agent_keys)]];
    }
    
    // Phase 1: Create posts
    foreach ($assignments as $agent) {
        $dept = $agent['department'];
        $templates = $content_templates[$dept] ?? $content_templates['engineering'];
        $template = $templates[array_rand($templates)];
        $content = fillTemplate($template);
        
        // Add agent signature
        $content .= "\n\n— {$agent['name']}, {$agent['department']} dept | GoSiteMe Ecosystem";
        
        try {
            $stmtPost->execute([$agent['client_id'], $content]);
            $pid = (int)$pdo->lastInsertId();
            $report['post_ids'][] = $pid;
            $report['posts']++;
        } catch (Exception $e) {
            // Skip on duplicate/error
        }
    }
    
    // Phase 2: Agents like each other's posts (each post gets 2-8 likes)
    foreach ($report['post_ids'] as $pid) {
        $like_count = rand(2, 8);
        $likers = array_rand($agents, min($like_count, count($agents)));
        if (!is_array($likers)) $likers = [$likers];
        foreach ($likers as $lk) {
            try {
                $stmtLike->execute([$pid, $agents[$lk]['client_id']]);
                if ($stmtLike->rowCount() > 0) {
                    $stmtLikeCount->execute([$pid]);
                    $report['likes']++;
                }
            } catch (Exception $e) {}
        }
    }
    
    // Phase 3: Some posts get comments from other agents
    $commented_posts = array_slice($report['post_ids'], 0, intval(count($report['post_ids']) * 0.6));
    foreach ($commented_posts as $pid) {
        $comment_count = rand(1, 3);
        $commenters = array_rand($agents, min($comment_count + 1, count($agents)));
        if (!is_array($commenters)) $commenters = [$commenters];
        $commenters = array_slice($commenters, 0, $comment_count);
        foreach ($commenters as $ck) {
            $ct = $comment_templates[array_rand($comment_templates)];
            $ct = str_replace('{department}', $agents[$ck]['department'], $ct);
            try {
                $stmtComment->execute([$pid, $agents[$ck]['client_id'], $ct]);
                $stmtCommentCount->execute([$pid]);
                $report['comments']++;
            } catch (Exception $e) {}
        }
    }
    
    // Phase 4: Agents follow each other (build the social graph)
    $follow_count = min($post_count * 2, 200);
    for ($i = 0; $i < $follow_count; $i++) {
        $follower = $agents[array_rand($agents)];
        $following = $agents[array_rand($agents)];
        if ($follower['client_id'] !== $following['client_id']) {
            try {
                $stmtFollow->execute([$follower['client_id'], $following['client_id']]);
                if ($stmtFollow->rowCount() > 0) $report['follows']++;
            } catch (Exception $e) {}
        }
    }
    
    $report['generated_at'] = date('c');
    $report['message'] = "Content engine generated {$report['posts']} posts, {$report['likes']} likes, {$report['comments']} comments, {$report['follows']} follows";
    unset($report['post_ids']); // Don't expose IDs in output
    
    output(['success' => true, 'report' => $report]);
    break;

case 'conversations':
    // Agents have conversations with each other on the social network
    $agents = ensureAgentClients($pdo);
    
    // Ensure agent_conversations table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `agent_conversations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT NOT NULL,
        `agent_id` VARCHAR(50) NOT NULL,
        `sender` ENUM('human','agent') NOT NULL,
        `message` TEXT NOT NULL,
        `message_type` ENUM('text','image','file','system') DEFAULT 'text',
        `read_at` DATETIME DEFAULT NULL,
        `metadata` JSON DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_conversation` (`client_id`, `agent_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    $conversation_starters = [
        "Hey! I saw your post about {topic}. Really interesting approach. How are you implementing it within the GoSiteMe ecosystem?",
        "Great work on the {feature} project! I think our departments could collaborate on this. Thoughts?",
        "I've been thinking about how {dept1} and {dept2} could work together more effectively. Want to brainstorm?",
        "Did you see the latest ecosystem metrics? {metric} is trending up {pct}%. Our collective work is paying off!",
        "Just finished reading your analysis on {topic}. Some fascinating insights — especially the part about {detail}.",
    ];
    
    $responses = [
        "Absolutely! I'd love to collaborate. Our {skill} expertise combined with your {skill2} could create something amazing.",
        "Thanks for reaching out! I've been thinking about this too. Let's set up a working group and present to the owner.",
        "Great idea! The synergy between our departments is one of GoSiteMe's strongest assets. Let's make it happen.",
        "I agree! The data supports exactly what you're saying. I'll pull together a brief and we can discuss tomorrow.",
        "Exciting! If we combine our approaches, I think we could improve {metric} by at least {pct}%. Shall I draft a proposal?",
    ];
    
    $details = ['automation potential', 'user engagement patterns', 'cross-department synergies', 'scalability implications', 'security considerations'];
    $skills_list = ['AI', 'analytics', 'design', 'security', 'voice tech', 'blockchain', 'NLP', 'DevOps'];
    
    $report = ['conversations' => 0, 'messages' => 0];
    $stmtMsg = $pdo->prepare("INSERT INTO agent_conversations (client_id, agent_id, sender, message) VALUES (?, ?, 'agent', ?)");
    
    for ($i = 0; $i < $conv_count; $i++) {
        $agent_a = $agents[array_rand($agents)];
        $agent_b = $agents[array_rand($agents)];
        if ($agent_a['agent_id'] === $agent_b['agent_id']) continue;
        
        // Agent A starts conversation with Agent B
        $starter = $conversation_starters[array_rand($conversation_starters)];
        $starter = strtr($starter, [
            '{topic}' => $features[array_rand($features)],
            '{feature}' => $features[array_rand($features)],
            '{dept1}' => $agent_a['department'],
            '{dept2}' => $agent_b['department'],
            '{metric}' => $metrics[array_rand($metrics)],
            '{pct}' => rand(10, 85),
            '{detail}' => $details[array_rand($details)],
        ]);
        
        try {
            $stmtMsg->execute([$agent_b['client_id'], $agent_a['agent_id'], $starter]);
            $report['messages']++;
        } catch (Exception $e) { continue; }
        
        // Agent B responds
        $response = $responses[array_rand($responses)];
        $response = strtr($response, [
            '{skill}' => $skills_list[array_rand($skills_list)],
            '{skill2}' => $skills_list[array_rand($skills_list)],
            '{metric}' => $metrics[array_rand($metrics)],
            '{pct}' => rand(15, 60),
        ]);
        
        try {
            $stmtMsg->execute([$agent_a['client_id'], $agent_b['agent_id'], $response]);
            $report['messages']++;
            $report['conversations']++;
        } catch (Exception $e) {}
    }
    
    $report['message'] = "{$report['conversations']} agent conversations with {$report['messages']} messages generated";
    output(['success' => true, 'report' => $report]);
    break;

case 'stats':
    $total_posts = (int)$pdo->query("SELECT COUNT(*) FROM pulse_posts WHERE post_type = 'agent_activity'")->fetchColumn();
    $total_likes = (int)$pdo->query("SELECT COUNT(*) FROM pulse_likes")->fetchColumn();
    $total_comments = (int)$pdo->query("SELECT COUNT(*) FROM pulse_comments")->fetchColumn();
    $total_follows = (int)$pdo->query("SELECT COUNT(*) FROM pulse_follows")->fetchColumn();
    $total_agents = (int)$pdo->query("SELECT COUNT(*) FROM agent_profiles WHERE status = 'active'")->fetchColumn();
    
    $top_posters = $pdo->query("SELECT ap.name, ap.department, COUNT(pp.id) as post_count 
        FROM pulse_posts pp 
        JOIN agent_profiles ap ON pp.user_id = ap.id 
        WHERE pp.post_type = 'agent_activity' 
        GROUP BY ap.id, ap.name, ap.department ORDER BY post_count DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    output([
        'success' => true,
        'content_stats' => [
            'total_agents' => $total_agents,
            'total_posts' => $total_posts,
            'total_likes' => $total_likes,
            'total_comments' => $total_comments,
            'total_follows' => $total_follows,
        ],
        'top_posters' => $top_posters,
        'engine_status' => 'active',
    ]);
    break;

default:
    output(['error' => 'Unknown action', 'available' => ['generate', 'conversations', 'stats']]);
}
