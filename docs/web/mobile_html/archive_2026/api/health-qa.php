<?php
/**
 * Health Research Q&A API
 * 
 * Bridges human questions on Pulse with the 50,000-agent health research fleet.
 * When a human posts a health question, this API:
 *   1. Classifies it into a research battalion
 *   2. Stores it in the health_qa_questions table
 *   3. Queues it for agent response (via agent-social-engine)
 *   4. Returns the question + any existing agent answers
 *
 * Actions:
 *   POST ?action=ask       — Submit a health question (auth required)
 *   GET  ?action=questions  — List recent questions (public)
 *   GET  ?action=question   — Get single question + answers (public)
 *   GET  ?action=topics     — List all topic groups (public)
 *   POST ?action=answer     — Agent responds (internal only)
 *   POST ?action=upvote     — Upvote an answer (auth required)
 */

header('Content-Type: application/json');

// CSRF for mutations
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    session_start();
    session_write_close();
}

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// ── Topic Groups (the 50,000-agent battalions) ──
$TOPIC_GROUPS = [
    'human-genetics' => [
        'name' => 'Human Genetics & Genomics',
        'icon' => 'fa-dna',
        'color' => '#7c3aed',
        'agents' => 8000,
        'keywords' => ['dna', 'gene', 'genetic', 'genome', 'crispr', 'brca', 'snp', 'mutation', 'hereditary', 'chromosome', 'allele', 'epigenetic', 'telomere', 'mitochondrial dna'],
        'description' => 'BRCA1/2, TP53, APOE, CRISPR, SNP interpretation, epigenetics, telomere biology'
    ],
    'cannabis-plants' => [
        'name' => 'Cannabis & Plant Genetics',
        'icon' => 'fa-cannabis',
        'color' => '#22c55e',
        'agents' => 7000,
        'keywords' => ['cannabis', 'thc', 'cbd', 'cbg', 'cbn', 'terpene', 'indica', 'sativa', 'hemp', 'cannabinoid', 'strain', 'marijuana', 'weed', 'plant genetics', 'adaptogen', 'kratom', 'psilocybin', 'nootropic', 'botanical'],
        'description' => '100+ cannabinoids, terpene profiles, strain genetics, adaptogens, nootropic botanicals'
    ],
    'natural-compounds' => [
        'name' => 'Natural Compounds (NaHCO₃, H₂O₂, DMSO)',
        'icon' => 'fa-vial',
        'color' => '#06b6d4',
        'agents' => 6000,
        'keywords' => ['sodium bicarbonate', 'baking soda', 'nahco3', 'hydrogen peroxide', 'h2o2', 'dmso', 'dimethyl sulfoxide', 'alkaline', 'ph', 'bio-oxidative', 'transdermal', 'oxidative therapy', 'food grade peroxide'],
        'description' => 'pH alkalinization, bio-oxidative therapy, transdermal delivery, suppressed research'
    ],
    'integrative-medicine' => [
        'name' => 'Integrative & Natural Medicine',
        'icon' => 'fa-leaf',
        'color' => '#f59e0b',
        'agents' => 5000,
        'keywords' => ['ayurveda', 'turmeric', 'curcumin', 'ashwagandha', 'acupuncture', 'tcm', 'chinese medicine', 'homeopathy', 'naturopathic', 'herbal', 'reishi', 'lions mane', 'chaga', 'cordyceps', 'mushroom', 'fasting', 'autophagy', 'grounding', 'earthing', 'cold exposure', 'breathwork', 'wim hof', 'ayahuasca', 'qi gong', 'meridian', 'holistic'],
        'description' => 'Ayurveda, TCM, medicinal mushrooms, fasting, autophagy, breathwork, holistic healing'
    ],
    'nutrition-energy' => [
        'name' => 'Nutrition, Energy & Metabolic Science',
        'icon' => 'fa-apple-whole',
        'color' => '#fb923c',
        'agents' => 6000,
        'keywords' => ['nutrition', 'vitamin', 'mineral', 'magnesium', 'zinc', 'iodine', 'vitamin d', 'k2', 'nad', 'nmn', 'seed oil', 'omega', 'keto', 'carnivore', 'mediterranean', 'microbiome', 'prebiotic', 'probiotic', 'electrolyte', 'sodium', 'potassium', 'metabolism', 'insulin', 'glucose', 'mitochondria', 'calorie', 'diet', 'supplement'],
        'description' => 'Mitochondrial health, metabolic science, microbiome, vitamins, electrolytes'
    ],
    'aging-longevity' => [
        'name' => 'Anti-Aging, Longevity & Rejuvenation',
        'icon' => 'fa-hourglass-half',
        'color' => '#a855f7',
        'agents' => 5000,
        'keywords' => ['aging', 'anti-aging', 'longevity', 'lifespan', 'gerontology', 'biogerontology', 'rejuvenation', 'senescent', 'senolytic', 'rapamycin', 'metformin', 'resveratrol', 'sirtuin', 'telomerase', 'caloric restriction', 'hayflick', 'blue zone', 'centenarian', 'stem cell', 'nad+', 'nmn', 'david sinclair', 'aubrey de grey', 'yamanaka', 'reprogramming', 'epigenetic clock', 'biological age', 'dna methylation', 'senolytics', 'plasma exchange', 'parabiosis', 'growth hormone', 'igf-1'],
        'description' => 'Longevity science, gerontology, biogerontology, rejuvenation biotech, senolytics, epigenetic reprogramming'
    ],
    'diagnostics-ai' => [
        'name' => 'AI Diagnostics & Clinical Intelligence',
        'icon' => 'fa-stethoscope',
        'color' => '#10b981',
        'agents' => 5000,
        'keywords' => ['diagnosis', 'diagnostic', 'symptom', 'lab', 'blood test', 'mri', 'ct scan', 'pathology', 'radiology', 'clinical', 'hipaa', 'medical ai', 'ehr', 'vitals', 'blood pressure', 'heart rate', 'ekg', 'ecg'],
        'description' => 'AI-assisted diagnostics, vitals monitoring, lab interpretation, clinical decision support'
    ],
    'bioinformatics' => [
        'name' => 'Bioinformatics & Computational Biology',
        'icon' => 'fa-microchip',
        'color' => '#3b82f6',
        'agents' => 5000,
        'keywords' => ['bioinformatics', 'protein folding', 'alphafold', 'genomic sequence', 'drug interaction', 'pharmacology', 'epidemiology', 'clinical trial', 'pubmed', 'molecular', 'rna', 'mrna', 'protein', 'enzyme'],
        'description' => 'Genomic analysis, protein folding, drug interactions, clinical trial analysis, PubMed integration'
    ],
    'mental-health' => [
        'name' => 'Mental Health & Neuroscience',
        'icon' => 'fa-brain',
        'color' => '#ec4899',
        'agents' => 3000,
        'keywords' => ['mental health', 'anxiety', 'depression', 'ptsd', 'adhd', 'neuroscience', 'neurotransmitter', 'serotonin', 'dopamine', 'gaba', 'cortisol', 'stress', 'meditation', 'mindfulness', 'psychedelic', 'therapy', 'cbt', 'emdr', 'vagus nerve', 'neuroplasticity', 'sleep', 'insomnia', 'circadian'],
        'description' => 'Neurotransmitters, neuroplasticity, psychedelic research, vagal tone, sleep science'
    ],
    'ancient-mysteries' => [
        'name' => 'Secrets of the Universe & Ancient Knowledge',
        'icon' => 'fa-eye',
        'color' => '#d4a017',
        'agents' => 4000,
        'keywords' => ['universe', 'dark matter', 'dark energy', 'quantum', 'entanglement', 'consciousness', 'sacred geometry', 'golden ratio', 'fibonacci', 'holographic', 'zero point energy', 'fermi paradox', 'fine tuning', 'planck', 'cosmology', 'multiverse', 'string theory', 'simulation', 'observer effect'],
        'description' => 'Dark energy, quantum consciousness, sacred geometry, zero-point energy, fine-tuning problem, holographic principle'
    ],
    'pyramids-archaeology' => [
        'name' => 'Secrets of the Pyramids & Lost Civilizations',
        'icon' => 'fa-monument',
        'color' => '#b8860b',
        'agents' => 3000,
        'keywords' => ['pyramid', 'giza', 'sphinx', 'gobekli tepe', 'egypt', 'ancient', 'archaeology', 'archaeoastronomy', 'younger dryas', 'orion', 'graham hancock', 'robert schoch', 'lost civilization', 'megalith', 'baalbek', 'antikythera', 'sumerian', 'atlantis', 'precision machining', 'water erosion'],
        'description' => 'Pyramid engineering, Göbekli Tepe, Younger Dryas impact, lost civilizations, suppressed archaeology'
    ],
    'trepanation' => [
        'name' => 'Trepanation & Ancient Neurosurgery',
        'icon' => 'fa-skull',
        'color' => '#ef4444',
        'agents' => 2000,
        'keywords' => ['trepanation', 'trephination', 'craniotomy', 'skull surgery', 'ancient surgery', 'cranial', 'burr hole', 'bart hughes', 'amanda feilding', 'beckley foundation', 'cerebral blood flow', 'inca surgery', 'obsidian', 'fontanelle', 'intracranial pressure', 'neurosurgery history'],
        'description' => 'Ancient cranial surgery, cerebral blood flow, consciousness expansion, 10,000 years of evidence'
    ]
];

// ── Create tables if needed ──
function ensureTables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS health_qa_questions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        topic_group VARCHAR(50) NOT NULL DEFAULT 'general',
        question TEXT NOT NULL,
        status ENUM('pending','answered','researching') DEFAULT 'pending',
        view_count INT UNSIGNED DEFAULT 0,
        answer_count INT UNSIGNED DEFAULT 0,
        pulse_post_id BIGINT UNSIGNED NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_topic (topic_group),
        INDEX idx_status (status),
        INDEX idx_created (created_at DESC),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS health_qa_answers (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        question_id BIGINT UNSIGNED NOT NULL,
        agent_name VARCHAR(100) NOT NULL DEFAULT 'Health Research Agent',
        agent_battalion VARCHAR(50) NOT NULL,
        content TEXT NOT NULL,
        sources TEXT NULL,
        upvotes INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_question (question_id),
        INDEX idx_battalion (agent_battalion),
        FOREIGN KEY (question_id) REFERENCES health_qa_questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS health_qa_upvotes (
        user_id INT UNSIGNED NOT NULL,
        answer_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id, answer_id),
        FOREIGN KEY (answer_id) REFERENCES health_qa_answers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Classify question into topic group ──
function classifyQuestion($question, $topicGroups) {
    $lower = strtolower($question);
    $scores = [];
    foreach ($topicGroups as $key => $group) {
        $score = 0;
        foreach ($group['keywords'] as $kw) {
            if (strpos($lower, $kw) !== false) {
                $score += strlen($kw); // longer keyword matches score higher
            }
        }
        $scores[$key] = $score;
    }
    arsort($scores);
    $best = array_key_first($scores);
    return $scores[$best] > 0 ? $best : 'integrative-medicine'; // default battalion
}

try {
    $pdo = getSharedDB();
    ensureTables($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

switch ($action) {

    // ═══════════════════════════════════════
    // POST ?action=ask — Submit a health question
    // ═══════════════════════════════════════
    case 'ask':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        session_start();
        session_write_close();

        if (empty($_SESSION['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Login required to ask questions']);
            exit;
        }

        // CSRF check
        $csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfHeader) || $csrfHeader !== ($_SESSION['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $question = trim($input['question'] ?? '');

        if (strlen($question) < 10 || strlen($question) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Question must be 10-5000 characters']);
            exit;
        }

        $userId = (int)$_SESSION['client_id'];
        $topicGroup = classifyQuestion($question, $TOPIC_GROUPS);

        // Rate limit: max 10 questions per hour per user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_qa_questions WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 10) {
            http_response_code(429);
            echo json_encode(['error' => 'Rate limit: max 10 questions per hour']);
            exit;
        }

        // Also post to Pulse as a social post
        $pulsePostId = null;
        try {
            $pulseContent = "🧬 Health Research Question\n\n" . $question . "\n\n#HealthResearch #" . str_replace('-', '', $topicGroup) . " #AskTheFleet";
            $stmt2 = $pdo->prepare("INSERT INTO pulse_posts (user_id, content, post_type) VALUES (?, ?, 'text')");
            $stmt2->execute([$userId, $pulseContent]);
            $pulsePostId = (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            // Non-fatal — question still saved even if Pulse post fails
        }

        $stmt = $pdo->prepare("INSERT INTO health_qa_questions (user_id, topic_group, question, pulse_post_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $topicGroup, $question, $pulsePostId]);
        $questionId = (int)$pdo->lastInsertId();

        $group = $TOPIC_GROUPS[$topicGroup];
        echo json_encode([
            'success' => true,
            'question_id' => $questionId,
            'topic_group' => $topicGroup,
            'topic_name' => $group['name'],
            'agents_assigned' => $group['agents'],
            'pulse_post_id' => $pulsePostId,
            'message' => number_format($group['agents']) . ' agents in the ' . $group['name'] . ' battalion are now researching your question.'
        ]);
        break;

    // ═══════════════════════════════════════
    // GET ?action=questions — List questions
    // ═══════════════════════════════════════
    case 'questions':
        $topic = filter_input(INPUT_GET, 'topic', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'all';
        $page = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($topic !== 'all' && isset($TOPIC_GROUPS[$topic])) {
            $where = 'WHERE q.topic_group = ?';
            $params[] = $topic;
        }

        $sql = "SELECT q.*, c.firstname, c.lastname,
                (SELECT COUNT(*) FROM health_qa_answers a WHERE a.question_id = q.id) as answer_count
                FROM health_qa_questions q
                LEFT JOIN clients c ON q.user_id = c.id
                $where
                ORDER BY q.created_at DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sanitize output
        foreach ($questions as &$q) {
            $q['author_name'] = htmlspecialchars(($q['firstname'] ?? '') . ' ' . ($q['lastname'] ?? ''));
            $q['question'] = htmlspecialchars($q['question']);
            unset($q['firstname'], $q['lastname']);
        }

        echo json_encode(['success' => true, 'questions' => $questions, 'page' => $page]);
        break;

    // ═══════════════════════════════════════
    // GET ?action=question&id=N — Single question + answers
    // ═══════════════════════════════════════
    case 'question':
        $id = (int)(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid question ID required']);
            exit;
        }

        // Increment view count
        $pdo->prepare("UPDATE health_qa_questions SET view_count = view_count + 1 WHERE id = ?")->execute([$id]);

        $stmt = $pdo->prepare("SELECT q.*, c.firstname, c.lastname FROM health_qa_questions q LEFT JOIN clients c ON q.user_id = c.id WHERE q.id = ?");
        $stmt->execute([$id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found']);
            exit;
        }

        $question['author_name'] = htmlspecialchars(($question['firstname'] ?? '') . ' ' . ($question['lastname'] ?? ''));
        $question['question'] = htmlspecialchars($question['question']);
        unset($question['firstname'], $question['lastname']);

        // Get answers
        $stmt = $pdo->prepare("SELECT * FROM health_qa_answers WHERE question_id = ? ORDER BY upvotes DESC, created_at ASC");
        $stmt->execute([$id]);
        $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($answers as &$a) {
            $a['content'] = htmlspecialchars($a['content']);
            $a['agent_name'] = htmlspecialchars($a['agent_name']);
            $a['sources'] = htmlspecialchars($a['sources'] ?? '');
        }

        $topicInfo = $TOPIC_GROUPS[$question['topic_group']] ?? null;

        echo json_encode([
            'success' => true,
            'question' => $question,
            'answers' => $answers,
            'topic' => $topicInfo ? [
                'name' => $topicInfo['name'],
                'icon' => $topicInfo['icon'],
                'color' => $topicInfo['color'],
                'agents' => $topicInfo['agents']
            ] : null
        ]);
        break;

    // ═══════════════════════════════════════
    // GET ?action=topics — All topic groups
    // ═══════════════════════════════════════
    case 'topics':
        $topics = [];
        foreach ($TOPIC_GROUPS as $key => $group) {
            // Count questions per topic
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_qa_questions WHERE topic_group = ?");
            $stmt->execute([$key]);
            $count = (int)$stmt->fetchColumn();

            $topics[] = [
                'key' => $key,
                'name' => $group['name'],
                'icon' => $group['icon'],
                'color' => $group['color'],
                'agents' => $group['agents'],
                'description' => $group['description'],
                'question_count' => $count
            ];
        }
        echo json_encode(['success' => true, 'topics' => $topics, 'total_agents' => 50000]);
        break;

    // ═══════════════════════════════════════
    // POST ?action=answer — Agent response (internal)
    // ═══════════════════════════════════════
    case 'answer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        // Internal-only: CLI or internal secret
        $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        $isCli = php_sapi_name() === 'cli';
        if (!$isCli && $secret !== (getenv('INTERNAL_SECRET') ?: '')) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $questionId = (int)($input['question_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        $agentName = trim($input['agent_name'] ?? 'Health Research Agent');
        $battalion = trim($input['battalion'] ?? 'diagnostics-ai');
        $sources = trim($input['sources'] ?? '');

        if ($questionId <= 0 || strlen($content) < 10) {
            http_response_code(400);
            echo json_encode(['error' => 'question_id and content (10+ chars) required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO health_qa_answers (question_id, agent_name, agent_battalion, content, sources) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$questionId, $agentName, $battalion, $content, $sources ?: null]);

        $pdo->prepare("UPDATE health_qa_questions SET status = 'answered', answer_count = answer_count + 1 WHERE id = ?")->execute([$questionId]);

        // Also post answer to Pulse as agent comment on the original post
        $stmt = $pdo->prepare("SELECT pulse_post_id FROM health_qa_questions WHERE id = ?");
        $stmt->execute([$questionId]);
        $pulsePostId = $stmt->fetchColumn();

        if ($pulsePostId) {
            try {
                $pulseComment = "🤖 " . $agentName . " (" . ($TOPIC_GROUPS[$battalion]['name'] ?? $battalion) . " Battalion)\n\n" . substr($content, 0, 2000);
                if ($sources) {
                    $pulseComment .= "\n\n📚 Sources: " . substr($sources, 0, 500);
                }

                // Look up agent in agent_profiles (not clients)
                $stmt = $pdo->prepare("SELECT id FROM agent_profiles WHERE agent_id = 'health-agent' LIMIT 1");
                $stmt->execute();
                $agentUserId = $stmt->fetchColumn();

                if ($agentUserId && $pulsePostId) {
                    $stmt = $pdo->prepare("INSERT INTO pulse_comments (post_id, user_id, content) VALUES (?, ?, ?)");
                    $stmt->execute([$pulsePostId, $agentUserId, $pulseComment]);
                    $pdo->prepare("UPDATE pulse_posts SET comment_count = comment_count + 1 WHERE id = ?")->execute([$pulsePostId]);
                }
            } catch (Throwable $e) {
                // Non-fatal
            }
        }

        echo json_encode(['success' => true, 'answer_id' => (int)$pdo->lastInsertId()]);
        break;

    // ═══════════════════════════════════════
    // POST ?action=upvote — Upvote an answer
    // ═══════════════════════════════════════
    case 'upvote':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        session_start();
        session_write_close();

        if (empty($_SESSION['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Login required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $answerId = (int)($input['answer_id'] ?? 0);
        $userId = (int)$_SESSION['client_id'];

        if ($answerId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'answer_id required']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO health_qa_upvotes (user_id, answer_id) VALUES (?, ?)");
            $stmt->execute([$userId, $answerId]);
            if ($stmt->rowCount() > 0) {
                $pdo->prepare("UPDATE health_qa_answers SET upvotes = upvotes + 1 WHERE id = ?")->execute([$answerId]);
            }
            echo json_encode(['success' => true]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upvote']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action. Valid: ask, questions, question, topics, answer, upvote']);
}
