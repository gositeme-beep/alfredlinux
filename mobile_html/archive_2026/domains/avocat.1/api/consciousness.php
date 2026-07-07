<?php
/**
 * Alfred AI — Consciousness Layer API
 * 
 * Personality Engine, Memory & Learning, Relationship & Growth,
 * Proactive Intelligence, Emotional Intelligence
 * 
 * Routes via $_GET['action']
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['status' => 'ok']);
}

requireCSRF();
apiRateLimit(30, 60, 'consciousness');

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

$clientId = (int)$_SESSION['client_id'];
$action   = $_GET['action'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];
$db       = getDB();

if (!$db) {
    jsonResponse(['error' => 'Database unavailable'], 500);
}

// ─── Default personality traits ──────────────────────────────────────────────
$defaultTraits = [
    'humor_level'        => 7,
    'formality'          => 5,
    'empathy'            => 8,
    'creativity'         => 7,
    'verbosity'          => 5,
    'cultural_awareness' => 'neutral',
];

// ─── Route ───────────────────────────────────────────────────────────────────
switch ($action) {

    // =====================================================================
    //  PERSONALITY ENGINE
    // =====================================================================

    case 'set_personality':
        requireMethod('POST');
        $input = getJsonInput();

        $allowedTraits = ['humor_level', 'formality', 'empathy', 'creativity', 'verbosity', 'cultural_awareness'];
        $updated = 0;

        foreach ($allowedTraits as $trait) {
            if (!isset($input[$trait])) continue;

            $value = $input[$trait];

            // Validate numeric traits 1-10
            if ($trait !== 'cultural_awareness') {
                $value = (int)$value;
                if ($value < 1 || $value > 10) {
                    jsonResponse(['error' => "$trait must be between 1 and 10"], 400);
                }
                $value = (string)$value;
            } else {
                $value = sanitize($value, 500);
            }

            $confidence = isset($input['confidence']) ? min(1.0, max(0.0, (float)$input['confidence'])) : 1.0;

            // Upsert
            $stmt = $db->prepare("
                INSERT INTO alfred_personality (client_id, trait_name, trait_value, confidence, active, last_triggered, created_at, updated_at)
                VALUES (:cid, :trait, :val, :conf, 1, NOW(), NOW(), NOW())
                ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value),
                                        confidence  = VALUES(confidence),
                                        last_triggered = NOW(),
                                        updated_at  = NOW()
            ");
            $stmt->execute([
                ':cid'   => $clientId,
                ':trait'  => $trait,
                ':val'    => $value,
                ':conf'   => $confidence,
            ]);
            $updated++;
        }

        jsonResponse(['success' => true, 'traits_updated' => $updated]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'get_personality':
        requireMethod('GET');

        $stmt = $db->prepare("
            SELECT trait_name, trait_value, confidence, active, last_triggered, updated_at
            FROM alfred_personality
            WHERE client_id = :cid AND active = 1
        ");
        $stmt->execute([':cid' => $clientId]);
        $rows = $stmt->fetchAll();

        // Build map, fill defaults
        $traits = $defaultTraits;
        foreach ($rows as $r) {
            $traits[$r['trait_name']] = is_numeric($r['trait_value']) ? (int)$r['trait_value'] : $r['trait_value'];
        }

        // Build detailed array
        $detailed = [];
        foreach ($rows as $r) {
            $detailed[$r['trait_name']] = [
                'value'          => is_numeric($r['trait_value']) ? (int)$r['trait_value'] : $r['trait_value'],
                'confidence'     => (float)$r['confidence'],
                'last_triggered' => $r['last_triggered'],
                'updated_at'     => $r['updated_at'],
            ];
        }

        jsonResponse([
            'success'  => true,
            'traits'   => $traits,
            'detailed' => $detailed,
            'is_default' => empty($rows),
        ]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'adapt_style':
        requireMethod('POST');

        // Fetch last 20 journal entries
        $stmt = $db->prepare("
            SELECT entry_type, content, metadata, confidence
            FROM alfred_learning_journal
            WHERE client_id = :cid
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':cid' => $clientId]);
        $entries = $stmt->fetchAll();

        if (empty($entries)) {
            jsonResponse(['success' => true, 'message' => 'No interactions to analyze yet', 'adjustments' => []]);
            break;
        }

        // Simple heuristic analysis
        $adjustments   = [];
        $formalWords    = ['please', 'kindly', 'sincerely', 'respectfully', 'regarding', 'hereby', 'pursuant'];
        $casualWords    = ['hey', 'lol', 'gonna', 'wanna', 'cool', 'awesome', 'btw', 'nah'];
        $emotionalWords = ['feel', 'frustrated', 'happy', 'confused', 'worried', 'excited', 'love', 'hate'];
        $creativeWords  = ['idea', 'imagine', 'creative', 'brainstorm', 'innovate', 'design', 'inspiration'];

        $formalScore   = 0;
        $casualScore   = 0;
        $emotionalScore = 0;
        $creativeScore = 0;
        $totalLength   = 0;
        $feedbackCount = 0;
        $preferenceCount = 0;

        foreach ($entries as $e) {
            $text = strtolower($e['content']);
            $totalLength += strlen($e['content']);

            foreach ($formalWords as $w)    if (strpos($text, $w) !== false) $formalScore++;
            foreach ($casualWords as $w)    if (strpos($text, $w) !== false) $casualScore++;
            foreach ($emotionalWords as $w) if (strpos($text, $w) !== false) $emotionalScore++;
            foreach ($creativeWords as $w)  if (strpos($text, $w) !== false) $creativeScore++;

            if ($e['entry_type'] === 'feedback')   $feedbackCount++;
            if ($e['entry_type'] === 'preference') $preferenceCount++;
        }

        $entryCount = count($entries);
        $avgLength  = $totalLength / $entryCount;

        // Formality adjustment
        if ($formalScore > $casualScore + 2) {
            $adjustments[] = ['trait' => 'formality', 'direction' => 'increase', 'reason' => 'User uses formal language'];
            upsertTrait($db, $clientId, 'formality', min(10, 7), 0.7);
        } elseif ($casualScore > $formalScore + 2) {
            $adjustments[] = ['trait' => 'formality', 'direction' => 'decrease', 'reason' => 'User uses casual language'];
            upsertTrait($db, $clientId, 'formality', max(1, 3), 0.7);
        }

        // Empathy adjustment
        if ($emotionalScore > 3) {
            $adjustments[] = ['trait' => 'empathy', 'direction' => 'increase', 'reason' => 'User expresses emotions frequently'];
            upsertTrait($db, $clientId, 'empathy', min(10, 9), 0.6);
        }

        // Creativity adjustment
        if ($creativeScore > 3) {
            $adjustments[] = ['trait' => 'creativity', 'direction' => 'increase', 'reason' => 'User engages in creative topics'];
            upsertTrait($db, $clientId, 'creativity', min(10, 9), 0.6);
        }

        // Verbosity adjustment based on avg message length
        if ($avgLength > 300) {
            $adjustments[] = ['trait' => 'verbosity', 'direction' => 'increase', 'reason' => 'User writes detailed messages'];
            upsertTrait($db, $clientId, 'verbosity', min(10, 7), 0.5);
        } elseif ($avgLength < 50) {
            $adjustments[] = ['trait' => 'verbosity', 'direction' => 'decrease', 'reason' => 'User prefers brief messages'];
            upsertTrait($db, $clientId, 'verbosity', max(1, 3), 0.5);
        }

        // Humor — increase if casual and creative
        if ($casualScore > 3 && $creativeScore > 1) {
            $adjustments[] = ['trait' => 'humor_level', 'direction' => 'increase', 'reason' => 'User is casual and creative'];
            upsertTrait($db, $clientId, 'humor_level', min(10, 8), 0.5);
        }

        jsonResponse([
            'success'     => true,
            'entries_analyzed' => $entryCount,
            'adjustments' => $adjustments,
            'signals'     => [
                'formal_score'   => $formalScore,
                'casual_score'   => $casualScore,
                'emotional_score' => $emotionalScore,
                'creative_score' => $creativeScore,
                'avg_message_length' => round($avgLength),
            ],
        ]);
        break;

    // =====================================================================
    //  MEMORY & LEARNING
    // =====================================================================

    case 'add_journal':
        requireMethod('POST');
        $input = getJsonInput();

        $validTypes = ['preference', 'pattern', 'insight', 'mistake', 'achievement', 'interaction', 'feedback'];
        $entryType  = $input['entry_type'] ?? '';
        if (!in_array($entryType, $validTypes, true)) {
            jsonResponse(['error' => 'Invalid entry_type. Must be one of: ' . implode(', ', $validTypes)], 400);
        }

        $content = trim($input['content'] ?? '');
        if ($content === '') {
            jsonResponse(['error' => 'Content is required'], 400);
        }
        $content = sanitize($content, 5000);

        $metadata   = isset($input['metadata']) ? json_encode($input['metadata']) : null;
        $confidence = isset($input['confidence']) ? min(1.0, max(0.0, (float)$input['confidence'])) : 0.8;
        $source     = sanitize($input['source'] ?? 'api', 100);

        $stmt = $db->prepare("
            INSERT INTO alfred_learning_journal (client_id, entry_type, content, metadata, confidence, source, created_at)
            VALUES (:cid, :type, :content, :meta, :conf, :src, NOW())
        ");
        $stmt->execute([
            ':cid'     => $clientId,
            ':type'    => $entryType,
            ':content' => $content,
            ':meta'    => $metadata,
            ':conf'    => $confidence,
            ':src'     => $source,
        ]);

        jsonResponse(['success' => true, 'journal_id' => (int)$db->lastInsertId()]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'get_journal':
        requireMethod('GET');

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;
        $typeFilter = $_GET['type'] ?? '';

        $where  = "WHERE client_id = :cid";
        $params = [':cid' => $clientId];

        $validTypes = ['preference', 'pattern', 'insight', 'mistake', 'achievement', 'interaction', 'feedback'];
        if ($typeFilter && in_array($typeFilter, $validTypes, true)) {
            $where .= " AND entry_type = :type";
            $params[':type'] = $typeFilter;
        }

        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Fetch page
        $stmt = $db->prepare("
            SELECT id, entry_type, content, metadata, confidence, source, created_at
            FROM alfred_learning_journal
            $where
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $entries = $stmt->fetchAll();

        // Decode JSON metadata
        foreach ($entries as &$e) {
            $e['metadata'] = $e['metadata'] ? json_decode($e['metadata'], true) : null;
        }
        unset($e);

        jsonResponse([
            'success'    => true,
            'entries'    => $entries,
            'pagination' => [
                'page'       => $page,
                'per_page'   => $perPage,
                'total'      => $total,
                'total_pages' => (int)ceil($total / $perPage),
            ],
        ]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'get_profile':
        requireMethod('GET');

        $stmt = $db->prepare("
            SELECT id, client_id, display_name, avatar_url, bio, skills, preferences, goals,
                   communication_style, timezone, language, onboarding_completed, created_at, updated_at
            FROM alfred_user_profiles
            WHERE client_id = :cid
            LIMIT 1
        ");
        $stmt->execute([':cid' => $clientId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            jsonResponse([
                'success'   => true,
                'profile'   => null,
                'is_new'    => true,
                'message'   => 'No profile found. Create one with update_profile.',
            ]);
            break;
        }

        // Decode JSON fields
        foreach (['skills', 'preferences', 'goals', 'communication_style'] as $jsonField) {
            $profile[$jsonField] = $profile[$jsonField] ? json_decode($profile[$jsonField], true) : null;
        }

        jsonResponse(['success' => true, 'profile' => $profile]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'update_profile':
        requireMethod('POST');
        $input = getJsonInput();

        // Check if profile exists
        $stmt = $db->prepare("SELECT id FROM alfred_user_profiles WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $exists = $stmt->fetch();

        $fields = [];
        $params = [':cid' => $clientId];

        $textFields = ['display_name', 'avatar_url', 'bio', 'timezone', 'language'];
        foreach ($textFields as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[":$f"] = sanitize($input[$f], $f === 'bio' ? 2000 : 255);
            }
        }

        $jsonFields = ['skills', 'preferences', 'goals', 'communication_style'];
        foreach ($jsonFields as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = :$f";
                $params[":$f"] = json_encode($input[$f]);
            }
        }

        if (isset($input['onboarding_completed'])) {
            $fields[] = "onboarding_completed = :onb";
            $params[':onb'] = $input['onboarding_completed'] ? 1 : 0;
        }

        if (empty($fields) && $exists) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        if ($exists) {
            $fields[] = "updated_at = NOW()";
            $sql = "UPDATE alfred_user_profiles SET " . implode(', ', $fields) . " WHERE client_id = :cid";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['success' => true, 'action' => 'updated']);
        } else {
            // Insert new profile
            $insertFields = ['client_id'];
            $insertPlaceholders = [':cid'];
            foreach ($params as $k => $v) {
                if ($k === ':cid') continue;
                $col = ltrim($k, ':');
                if ($col === 'onb') $col = 'onboarding_completed';
                $insertFields[] = $col;
                $insertPlaceholders[] = $k;
            }
            $insertFields[] = 'created_at';
            $insertPlaceholders[] = 'NOW()';
            $insertFields[] = 'updated_at';
            $insertPlaceholders[] = 'NOW()';

            $sql = "INSERT INTO alfred_user_profiles (" . implode(', ', $insertFields) . ") VALUES (" . implode(', ', $insertPlaceholders) . ")";
            $stmt = $db->prepare($sql);
            // Bind only parameter placeholders (not NOW())
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            jsonResponse(['success' => true, 'action' => 'created', 'profile_id' => (int)$db->lastInsertId()]);
        }
        break;

    // =====================================================================
    //  RELATIONSHIP & GROWTH
    // =====================================================================

    case 'relationship_score':
        requireMethod('GET');

        // Total interactions
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $totalInteractions = (int)$stmt->fetchColumn();

        // Days since first interaction
        $stmt = $db->prepare("SELECT MIN(created_at) FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $firstDate = $stmt->fetchColumn();
        $daysTogether = $firstDate ? max(1, (int)((time() - strtotime($firstDate)) / 86400)) : 0;

        // Personality customization level (how many traits set)
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_personality WHERE client_id = :cid AND active = 1");
        $stmt->execute([':cid' => $clientId]);
        $traitsSet = (int)$stmt->fetchColumn();

        // XP data
        $stmt = $db->prepare("SELECT tools_used, problems_solved, level FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch() ?: ['tools_used' => 0, 'problems_solved' => 0, 'level' => 1];

        // Achievements count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_achievements WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $achievementCount = (int)$stmt->fetchColumn();

        // Calculate score (0-100)
        $score = 0;
        $score += min(25, $totalInteractions * 0.5);          // Up to 25 pts from interactions
        $score += min(20, $daysTogether * 0.2);                // Up to 20 pts from longevity
        $score += min(15, $traitsSet * 2.5);                   // Up to 15 pts from personalization
        $score += min(20, (int)$xp['tools_used'] * 2);        // Up to 20 pts from tool usage
        $score += min(10, (int)$xp['problems_solved'] * 0.5); // Up to 10 pts from problems solved
        $score += min(10, $achievementCount * 1);              // Up to 10 pts from achievements
        $score = min(100, round($score));

        // Trust level
        if      ($score >= 80) $trustLevel = 'partner';
        elseif ($score >= 60) $trustLevel = 'friend';
        elseif ($score >= 40) $trustLevel = 'colleague';
        elseif ($score >= 20) $trustLevel = 'acquaintance';
        else                   $trustLevel = 'stranger';

        jsonResponse([
            'success'      => true,
            'score'        => $score,
            'trust_level'  => $trustLevel,
            'rapport_summary' => generateRapportSummary($trustLevel, $totalInteractions, $daysTogether),
            'history_depth' => [
                'total_interactions' => $totalInteractions,
                'days_together'      => $daysTogether,
                'traits_customized'  => $traitsSet,
                'tools_used'         => (int)$xp['tools_used'],
                'problems_solved'    => (int)$xp['problems_solved'],
                'achievements'       => $achievementCount,
                'level'              => (int)$xp['level'],
            ],
        ]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'growth_tracker':
        requireMethod('GET');

        // Total interactions
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $totalInteractions = (int)$stmt->fetchColumn();

        // Insights learned
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid AND entry_type = 'insight'");
        $stmt->execute([':cid' => $clientId]);
        $insightsLearned = (int)$stmt->fetchColumn();

        // Mistakes caught
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid AND entry_type = 'mistake'");
        $stmt->execute([':cid' => $clientId]);
        $mistakesCaught = (int)$stmt->fetchColumn();

        // Days together
        $stmt = $db->prepare("SELECT MIN(created_at) FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $firstDate = $stmt->fetchColumn();
        $daysTogether = $firstDate ? max(1, (int)((time() - strtotime($firstDate)) / 86400)) : 0;

        // XP & level
        $stmt = $db->prepare("SELECT total_xp, level, streak_days, tools_used, problems_solved FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch() ?: ['total_xp' => 0, 'level' => 1, 'streak_days' => 0, 'tools_used' => 0, 'problems_solved' => 0];

        jsonResponse([
            'success'            => true,
            'total_interactions'  => $totalInteractions,
            'tools_mastered'     => (int)$xp['tools_used'],
            'insights_learned'   => $insightsLearned,
            'mistakes_caught'    => $mistakesCaught,
            'days_together'      => $daysTogether,
            'level'              => (int)$xp['level'],
            'total_xp'           => (int)$xp['total_xp'],
            'streak_days'        => (int)$xp['streak_days'],
            'problems_solved'    => (int)$xp['problems_solved'],
        ]);
        break;

    // =====================================================================
    //  PROACTIVE INTELLIGENCE
    // =====================================================================

    case 'daily_briefing':
        requireMethod('GET');

        // Get personality for greeting style
        $traits = getTraits($db, $clientId, $defaultTraits);

        // Recent journal entries (last 7 days)
        $stmt = $db->prepare("
            SELECT entry_type, content, metadata, confidence, created_at
            FROM alfred_learning_journal
            WHERE client_id = :cid AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
        ");
        $stmt->execute([':cid' => $clientId]);
        $recentEntries = $stmt->fetchAll();

        // User profile
        $stmt = $db->prepare("SELECT display_name, timezone FROM alfred_user_profiles WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $profile = $stmt->fetch();
        $displayName = $profile['display_name'] ?? 'there';

        // XP / streak
        $stmt = $db->prepare("SELECT streak_days, level, total_xp, last_active FROM alfred_user_xp_summary WHERE client_id = :cid LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
        $xp = $stmt->fetch() ?: ['streak_days' => 0, 'level' => 1, 'total_xp' => 0, 'last_active' => null];

        // Build greeting
        $greeting = buildGreeting($displayName, $traits);

        // Categorize recent entries
        $updates     = [];
        $suggestions = [];
        $typeCounts  = [];
        foreach ($recentEntries as $e) {
            $typeCounts[$e['entry_type']] = ($typeCounts[$e['entry_type']] ?? 0) + 1;
            if ($e['entry_type'] === 'achievement') {
                $updates[] = ['type' => 'achievement', 'text' => $e['content'], 'date' => $e['created_at']];
            }
            if ($e['entry_type'] === 'insight') {
                $updates[] = ['type' => 'insight', 'text' => $e['content'], 'date' => $e['created_at']];
            }
        }

        // Generate suggestions from patterns
        if (($typeCounts['mistake'] ?? 0) > 2) {
            $suggestions[] = ['text' => 'You\'ve had a few stumbles recently — want to review common pitfalls?', 'priority' => 'medium'];
        }
        if (($typeCounts['interaction'] ?? 0) > 10) {
            $suggestions[] = ['text' => 'You\'ve been very active! Consider documenting your workflow for future reference.', 'priority' => 'low'];
        }
        if (empty($recentEntries)) {
            $suggestions[] = ['text' => 'It\'s been a while! Let\'s pick up where we left off.', 'priority' => 'high'];
        }
        if ((int)$xp['streak_days'] > 5) {
            $suggestions[] = ['text' => "Impressive {$xp['streak_days']}-day streak! Keep the momentum going.", 'priority' => 'low'];
        }

        jsonResponse([
            'success'     => true,
            'greeting'    => $greeting,
            'updates'     => array_slice($updates, 0, 10),
            'suggestions' => $suggestions,
            'streak'      => [
                'days'    => (int)$xp['streak_days'],
                'level'   => (int)$xp['level'],
                'xp'      => (int)$xp['total_xp'],
            ],
            'activity_summary' => $typeCounts,
        ]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'proactive_suggest':
        requireMethod('GET');

        // Analyze journal for recurring patterns
        $stmt = $db->prepare("
            SELECT entry_type, content, metadata, confidence, created_at
            FROM alfred_learning_journal
            WHERE client_id = :cid
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $stmt->execute([':cid' => $clientId]);
        $entries = $stmt->fetchAll();

        $suggestions = [];

        if (empty($entries)) {
            jsonResponse(['success' => true, 'suggestions' => [], 'message' => 'No data to analyze yet']);
            break;
        }

        // Frequency analysis of entry types
        $typeCounts = [];
        $topicWords = [];
        foreach ($entries as $e) {
            $typeCounts[$e['entry_type']] = ($typeCounts[$e['entry_type']] ?? 0) + 1;
            // Extract keywords (simple word frequency)
            $words = array_filter(str_word_count(strtolower($e['content']), 1), function ($w) {
                return strlen($w) > 4 && !in_array($w, ['about', 'their', 'which', 'would', 'could', 'should', 'there', 'where', 'these', 'those', 'being']);
            });
            foreach ($words as $w) {
                $topicWords[$w] = ($topicWords[$w] ?? 0) + 1;
            }
        }

        // Sort topics by frequency
        arsort($topicWords);
        $topTopics = array_slice($topicWords, 0, 5, true);

        // Generate suggestions
        if (($typeCounts['preference'] ?? 0) > 3) {
            $suggestions[] = [
                'type'       => 'optimization',
                'text'       => 'You have several saved preferences — I can auto-apply them to streamline your workflow.',
                'confidence' => 0.8,
            ];
        }
        if (($typeCounts['mistake'] ?? 0) > ($typeCounts['achievement'] ?? 0)) {
            $suggestions[] = [
                'type'       => 'learning',
                'text'       => 'I notice more challenges than wins lately. Want me to create a focused improvement plan?',
                'confidence' => 0.7,
            ];
        }
        if (($typeCounts['pattern'] ?? 0) > 2) {
            $suggestions[] = [
                'type'       => 'automation',
                'text'       => 'I\'ve detected recurring patterns in your work. These might be automatable.',
                'confidence' => 0.75,
            ];
        }
        if (!empty($topTopics)) {
            $topTopic = array_key_first($topTopics);
            $suggestions[] = [
                'type'       => 'focus',
                'text'       => "You frequently work on topics related to \"$topTopic\". Want me to curate resources?",
                'confidence' => 0.6,
            ];
        }

        // Time-based patterns
        $recentCount = 0;
        $olderCount  = 0;
        foreach ($entries as $e) {
            if (strtotime($e['created_at']) > strtotime('-3 days')) $recentCount++;
            else $olderCount++;
        }
        if ($recentCount > $olderCount && count($entries) > 10) {
            $suggestions[] = [
                'type'       => 'pacing',
                'text'       => 'Activity is spiking recently. Remember to take breaks for sustained productivity.',
                'confidence' => 0.5,
            ];
        }

        jsonResponse([
            'success'     => true,
            'suggestions' => $suggestions,
            'analysis'    => [
                'entries_analyzed' => count($entries),
                'type_distribution' => $typeCounts,
                'top_topics'       => $topTopics,
            ],
        ]);
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'dream_state':
        requireMethod('POST');

        // Analyze all journal entries for patterns
        $stmt = $db->prepare("
            SELECT id, entry_type, content, metadata, confidence, created_at
            FROM alfred_learning_journal
            WHERE client_id = :cid
            ORDER BY created_at ASC
        ");
        $stmt->execute([':cid' => $clientId]);
        $allEntries = $stmt->fetchAll();

        if (count($allEntries) < 5) {
            jsonResponse(['success' => true, 'message' => 'Not enough data for dream-state analysis (need at least 5 entries)', 'insights' => []]);
            break;
        }

        $insights = [];

        // Pattern: recurring topics
        $topicFreq = [];
        foreach ($allEntries as $e) {
            $words = str_word_count(strtolower($e['content']), 1);
            $words = array_filter($words, fn($w) => strlen($w) > 5);
            foreach ($words as $w) {
                $topicFreq[$w] = ($topicFreq[$w] ?? 0) + 1;
            }
        }
        arsort($topicFreq);
        $recurringTopics = array_filter($topicFreq, fn($c) => $c >= 3);
        $recurringTopics = array_slice($recurringTopics, 0, 10, true);

        if (!empty($recurringTopics)) {
            $topList = implode(', ', array_keys(array_slice($recurringTopics, 0, 5, true)));
            $insights[] = [
                'type'       => 'recurring_theme',
                'content'    => "Recurring themes detected: $topList",
                'confidence' => 0.8,
            ];
        }

        // Pattern: mistake clusters
        $mistakes = array_filter($allEntries, fn($e) => $e['entry_type'] === 'mistake');
        if (count($mistakes) >= 3) {
            $insights[] = [
                'type'       => 'mistake_pattern',
                'content'    => count($mistakes) . ' mistakes logged. Consider reviewing error patterns to prevent recurrence.',
                'confidence' => 0.7,
            ];
        }

        // Pattern: growth trajectory
        $achievements = array_filter($allEntries, fn($e) => $e['entry_type'] === 'achievement');
        if (count($achievements) > 0) {
            $ratio = count($achievements) / max(1, count($mistakes));
            if ($ratio > 2) {
                $insights[] = [
                    'type'       => 'growth_positive',
                    'content'    => 'Strong growth trajectory: achievements outpace mistakes by ' . round($ratio, 1) . 'x.',
                    'confidence' => 0.85,
                ];
            } elseif ($ratio < 0.5) {
                $insights[] = [
                    'type'       => 'growth_concern',
                    'content'    => 'More mistakes than achievements recently. A focused review session could help.',
                    'confidence' => 0.7,
                ];
            }
        }

        // Pattern: activity gaps
        $dates = array_map(fn($e) => date('Y-m-d', strtotime($e['created_at'])), $allEntries);
        $uniqueDates = array_unique($dates);
        sort($uniqueDates);
        $maxGap = 0;
        for ($i = 1; $i < count($uniqueDates); $i++) {
            $gap = (strtotime($uniqueDates[$i]) - strtotime($uniqueDates[$i - 1])) / 86400;
            if ($gap > $maxGap) $maxGap = $gap;
        }
        if ($maxGap > 7) {
            $insights[] = [
                'type'       => 'engagement_gap',
                'content'    => "Longest gap between interactions: {$maxGap} days. Consistency improves outcomes.",
                'confidence' => 0.6,
            ];
        }

        // Store insights back in journal
        $storedCount = 0;
        foreach ($insights as $insight) {
            // Avoid duplicating very recent identical insights
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM alfred_learning_journal
                WHERE client_id = :cid AND entry_type = 'insight'
                  AND content = :content AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([':cid' => $clientId, ':content' => $insight['content']]);
            if ((int)$stmt->fetchColumn() === 0) {
                $stmt = $db->prepare("
                    INSERT INTO alfred_learning_journal (client_id, entry_type, content, metadata, confidence, source, created_at)
                    VALUES (:cid, 'insight', :content, :meta, :conf, 'dream_state', NOW())
                ");
                $stmt->execute([
                    ':cid'     => $clientId,
                    ':content' => $insight['content'],
                    ':meta'    => json_encode(['type' => $insight['type']]),
                    ':conf'    => $insight['confidence'],
                ]);
                $storedCount++;
            }
        }

        jsonResponse([
            'success'         => true,
            'insights'        => $insights,
            'entries_analyzed' => count($allEntries),
            'insights_stored' => $storedCount,
            'recurring_topics' => $recurringTopics,
        ]);
        break;

    // =====================================================================
    //  EMOTIONAL INTELLIGENCE
    // =====================================================================

    case 'emotional_state':
        if ($method === 'GET') {
            // Derive emotional state from recent interactions
            $stmt = $db->prepare("
                SELECT entry_type, content, confidence, created_at
                FROM alfred_learning_journal
                WHERE client_id = :cid
                ORDER BY created_at DESC
                LIMIT 15
            ");
            $stmt->execute([':cid' => $clientId]);
            $recent = $stmt->fetchAll();

            $energy     = 5;
            $mood       = 5;
            $engagement = 5;

            if (!empty($recent)) {
                $achievementCount = 0;
                $mistakeCount     = 0;
                $interactionCount = 0;
                $avgConfidence    = 0;
                $recentHours      = 0;

                foreach ($recent as $r) {
                    if ($r['entry_type'] === 'achievement') $achievementCount++;
                    if ($r['entry_type'] === 'mistake')     $mistakeCount++;
                    if ($r['entry_type'] === 'interaction') $interactionCount++;
                    $avgConfidence += (float)$r['confidence'];
                }
                $avgConfidence /= count($recent);

                // How recent is the activity (hours since last entry)
                $lastEntry   = strtotime($recent[0]['created_at']);
                $recentHours = (time() - $lastEntry) / 3600;

                // Energy: high if lots of recent activity
                $energy = min(10, max(1, 5 + $interactionCount - (int)($recentHours / 6)));

                // Mood: influenced by success ratio
                $mood = min(10, max(1, 5 + $achievementCount - $mistakeCount));

                // Engagement: based on frequency and confidence
                $engagement = min(10, max(1, round($avgConfidence * 10)));
            }

            jsonResponse([
                'success'    => true,
                'emotional_state' => [
                    'energy'     => $energy,
                    'mood'       => $mood,
                    'engagement' => $engagement,
                    'summary'    => describeEmotionalState($energy, $mood, $engagement),
                ],
                'based_on'   => count($recent) . ' recent entries',
            ]);

        } elseif ($method === 'POST') {
            // Log an emotional state observation
            $input = getJsonInput();

            $observation = sanitize($input['observation'] ?? '', 2000);
            if ($observation === '') {
                jsonResponse(['error' => 'Observation text required'], 400);
            }

            $emotionData = [
                'energy'     => isset($input['energy']) ? min(10, max(1, (int)$input['energy'])) : null,
                'mood'       => isset($input['mood']) ? min(10, max(1, (int)$input['mood'])) : null,
                'engagement' => isset($input['engagement']) ? min(10, max(1, (int)$input['engagement'])) : null,
            ];

            $stmt = $db->prepare("
                INSERT INTO alfred_learning_journal (client_id, entry_type, content, metadata, confidence, source, created_at)
                VALUES (:cid, 'feedback', :content, :meta, :conf, 'emotional_observation', NOW())
            ");
            $stmt->execute([
                ':cid'     => $clientId,
                ':content' => $observation,
                ':meta'    => json_encode($emotionData),
                ':conf'    => 0.9,
            ]);

            jsonResponse(['success' => true, 'logged' => true, 'journal_id' => (int)$db->lastInsertId()]);

        } else {
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        break;

    // ─────────────────────────────────────────────────────────────────────
    case 'self_reflect':
        requireMethod('GET');

        // Interaction count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid");
        $stmt->execute([':cid' => $clientId]);
        $totalInteractions = (int)$stmt->fetchColumn();

        // Achievements count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid AND entry_type = 'achievement'");
        $stmt->execute([':cid' => $clientId]);
        $achievementCount = (int)$stmt->fetchColumn();

        // Mistakes count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid AND entry_type = 'mistake'");
        $stmt->execute([':cid' => $clientId]);
        $mistakeCount = (int)$stmt->fetchColumn();

        // Insights count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_learning_journal WHERE client_id = :cid AND entry_type = 'insight'");
        $stmt->execute([':cid' => $clientId]);
        $insightCount = (int)$stmt->fetchColumn();

        // Recent wins (last 5 achievements)
        $stmt = $db->prepare("
            SELECT content, created_at FROM alfred_learning_journal
            WHERE client_id = :cid AND entry_type = 'achievement'
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([':cid' => $clientId]);
        $recentWins = $stmt->fetchAll();

        // Recent lessons (last 5 mistakes)
        $stmt = $db->prepare("
            SELECT content, created_at FROM alfred_learning_journal
            WHERE client_id = :cid AND entry_type = 'mistake'
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([':cid' => $clientId]);
        $recentLessons = $stmt->fetchAll();

        // Performance score
        $total = $achievementCount + $mistakeCount;
        $performanceScore = $total > 0 ? round(($achievementCount / $total) * 100) : 50;

        // Strengths & weaknesses
        $strengths  = [];
        $weaknesses = [];

        if ($achievementCount > $mistakeCount * 2) $strengths[]  = 'High success rate';
        if ($insightCount > 5)                     $strengths[]  = 'Strong pattern recognition';
        if ($totalInteractions > 50)               $strengths[]  = 'Deep engagement';
        if ($mistakeCount > $achievementCount)      $weaknesses[] = 'Error rate needs attention';
        if ($totalInteractions < 10)                $weaknesses[] = 'Limited interaction history';
        if ($insightCount < 2)                      $weaknesses[] = 'Few insights generated';

        if (empty($strengths))  $strengths[]  = 'Growing — keep interacting to reveal strengths';
        if (empty($weaknesses)) $weaknesses[] = 'No significant weaknesses detected';

        // Improvement plan
        $plan = [];
        if ($mistakeCount > $achievementCount) {
            $plan[] = 'Focus on reviewing past mistakes before taking on new tasks';
        }
        if ($insightCount < 3) {
            $plan[] = 'Run dream_state analysis to extract deeper insights';
        }
        if ($totalInteractions < 20) {
            $plan[] = 'Increase interaction frequency for better personalization';
        }
        if (empty($plan)) {
            $plan[] = 'Continue current trajectory — performance is on track';
        }

        jsonResponse([
            'success'           => true,
            'performance_score' => $performanceScore,
            'total_interactions' => $totalInteractions,
            'strengths'         => $strengths,
            'weaknesses'        => $weaknesses,
            'improvement_plan'  => $plan,
            'recent_wins'       => $recentWins,
            'recent_lessons'    => $recentLessons,
            'stats'             => [
                'achievements' => $achievementCount,
                'mistakes'     => $mistakeCount,
                'insights'     => $insightCount,
            ],
        ]);
        break;

    // =====================================================================
    //  UNKNOWN ACTION
    // =====================================================================

    default:
        $available = [
            'set_personality', 'get_personality', 'adapt_style',
            'add_journal', 'get_journal',
            'get_profile', 'update_profile',
            'relationship_score', 'growth_tracker',
            'daily_briefing', 'proactive_suggest', 'dream_state',
            'emotional_state', 'self_reflect',
        ];
        jsonResponse([
            'error'   => 'Unknown action: ' . sanitize($action, 50),
            'available_actions' => $available,
        ], 400);
        break;
}

// ═════════════════════════════════════════════════════════════════════════════
//  HELPER FUNCTIONS
// ═════════════════════════════════════════════════════════════════════════════

/**
 * Require a specific HTTP method or return 405.
 */
function requireMethod(string $required): void {
    if ($_SERVER['REQUEST_METHOD'] !== $required) {
        jsonResponse(['error' => "Method not allowed. Use $required."], 405);
    }
}

/**
 * Parse JSON request body.
 */
function getJsonInput(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }
    return $data;
}

/**
 * Upsert a personality trait.
 */
function upsertTrait(PDO $db, int $clientId, string $trait, $value, float $confidence): void {
    $stmt = $db->prepare("
        INSERT INTO alfred_personality (client_id, trait_name, trait_value, confidence, active, last_triggered, created_at, updated_at)
        VALUES (:cid, :trait, :val, :conf, 1, NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value),
                                confidence  = VALUES(confidence),
                                last_triggered = NOW(),
                                updated_at  = NOW()
    ");
    $stmt->execute([
        ':cid'   => $clientId,
        ':trait'  => $trait,
        ':val'    => (string)$value,
        ':conf'   => $confidence,
    ]);
}

/**
 * Get current personality traits for a user.
 */
function getTraits(PDO $db, int $clientId, array $defaults): array {
    $stmt = $db->prepare("SELECT trait_name, trait_value FROM alfred_personality WHERE client_id = :cid AND active = 1");
    $stmt->execute([':cid' => $clientId]);
    $rows = $stmt->fetchAll();

    $traits = $defaults;
    foreach ($rows as $r) {
        $traits[$r['trait_name']] = is_numeric($r['trait_value']) ? (int)$r['trait_value'] : $r['trait_value'];
    }
    return $traits;
}

/**
 * Build a personalized greeting based on personality traits.
 */
function buildGreeting(string $name, array $traits): string {
    $formality = (int)($traits['formality'] ?? 5);
    $humor     = (int)($traits['humor_level'] ?? 7);
    $empathy   = (int)($traits['empathy'] ?? 8);

    $hour = (int)date('G');

    if ($hour < 12) $timeWord = 'morning';
    elseif ($hour < 17) $timeWord = 'afternoon';
    else $timeWord = 'evening';

    if ($formality >= 7) {
        $greeting = "Good $timeWord, $name. I hope you're doing well.";
    } elseif ($formality <= 3) {
        $greetings = ["Hey $name! 👋", "Yo $name! What's up?", "Hey hey, $name!"];
        $greeting = $greetings[array_rand($greetings)];
    } else {
        $greeting = "Good $timeWord, $name!";
    }

    if ($humor >= 8) {
        $quips = [
            " I've been crunching numbers while you were away — they fought back.",
            " I organized your data alphabetically. You're welcome.",
            " Ready to be productive? Me too. Mostly.",
        ];
        $greeting .= $quips[array_rand($quips)];
    }

    return $greeting;
}

/**
 * Generate rapport summary text.
 */
function generateRapportSummary(string $trustLevel, int $interactions, int $days): string {
    switch ($trustLevel) {
        case 'partner':
            return "We've been through $interactions interactions over $days days. I know your style, preferences, and goals deeply. We're a great team.";
        case 'friend':
            return "With $interactions interactions over $days days, I've got a strong understanding of how you work. We've built solid rapport.";
        case 'colleague':
            return "We've had $interactions interactions over $days days. I'm learning your patterns and getting better at anticipating your needs.";
        case 'acquaintance':
            return "We're still getting to know each other ($interactions interactions over $days days). The more we interact, the better I can help.";
        default:
            return "We're just getting started! Let me learn about you so I can personalize your experience.";
    }
}

/**
 * Describe emotional state in words.
 */
function describeEmotionalState(int $energy, int $mood, int $engagement): string {
    $parts = [];

    if ($energy >= 8) $parts[] = 'highly energized';
    elseif ($energy >= 5) $parts[] = 'steady energy';
    else $parts[] = 'low energy';

    if ($mood >= 8) $parts[] = 'very positive mood';
    elseif ($mood >= 5) $parts[] = 'neutral mood';
    else $parts[] = 'subdued mood';

    if ($engagement >= 8) $parts[] = 'deeply engaged';
    elseif ($engagement >= 5) $parts[] = 'moderately engaged';
    else $parts[] = 'lightly engaged';

    return 'Alfred is currently ' . implode(', ', $parts) . '.';
}
