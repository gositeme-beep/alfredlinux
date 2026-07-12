<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * GoSiteMe Discord Bot — v5.0 Interaction Router
 * ════════════════════════════════════════════════
 * Thin entry point: Ed25519 verification → route to modular handlers
 * 
 * 100 slash commands (150+ features via subcommands) across 31 modules:
 *   AI:           /alfred /imagine /translate /code /summarize
 *   Games:        /chess /checkers /trivia /8ball /rps
 *   Economy:      /coins /daily /shop /gamble
 *   Community:    /poll /giveaway /ticket /embed /remind /announce
 *   Mod:          /mod /automod /audit
 *   Tools:        /status /weather /domain /qr /crypto /color
 *   Profile:      /profile /level /help
 *   Social:       /serverinfo /userinfo /afk /birthday /quote /horoscope /todo
 *   Premium:      /tts /sms /search /screenshot /calc /music /deploy
 *   Voice:        /call /fax /email
 *   Media:        /video /musicgen /voiceclone
 *   Finance:      /stock /portfolio
 *   Fun:          /debate /roast /story /dream /recipe /interview /riddle /encrypt /wisdom /persona
 *   News:         /news /legal /digest
 *   WebSearch:    /websearch /readurl /research /whois
 *   Admin:        /health /botlogs /botstats /serverban /backup
 *   Creative:     /poem /lyrics /script
 *   Social2:      /confess /wouldyourather /compatibility /tierlist
 *   Utility:      /timestamp /avatar /banner /math /define
 *   Personality:  /personality (view|set|export|mood|style|memorize|adapt)
 *   Documents:    /doc (parse|summarize|analyze|ocr|url|info)
 *   Kingdom:      /kingdom (profile|zone|transfer|leaderboard|achievements|zones)
 *   Scripture:    /scripture (verse|devotional|prayer|bible)
 *   Agents:       /agents (list|goal|delegate|decision|roster|wager|ecosystem)
 *   Consciousness:/consciousness (dream|emotion|reflect|briefing|journal|growth)
 *   Learning:     /learn (feedback|insights|experiments|patterns|performance)
 *   Feeds:        /feeds (subscribe|list|digest|news|unsubscribe)
 *   DeFi:         /defi (portfolio|positions|alerts|chains|convert)
 *   SourceCard:   /sourcecard (view|contribute|reputation|tier|lineage)
 *   Server:       /server (reactionroles|starboard|welcome|autorole|counting|slowmode)
 *   Games2:       /games2 (hangman|wordle|slots|coinflip|duel|tower)
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: https://discord.com');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type, X-Signature-Ed25519, X-Signature-Timestamp');
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

// ─── Load Environment ──────────────────────────────────────────────────
$envFile = dirname(dirname(__DIR__)) . '/.env.php';
if (file_exists($envFile)) require_once $envFile;

$publicKey = getenv('DISCORD_PUBLIC_KEY') ?: '';
$rawBody   = file_get_contents('php://input');

// ─── Ed25519 Signature Verification ────────────────────────────────────
$signature = $_SERVER['HTTP_X_SIGNATURE_ED25519'] ?? '';
$timestamp = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'] ?? '';

if (!$signature || !$timestamp) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing signature headers']);
    exit;
}

if (!$publicKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Public key not configured']);
    exit;
}

try {
    $message = $timestamp . $rawBody;
    $sigBin = sodium_hex2bin($signature);
    $pubKeyBin = sodium_hex2bin($publicKey);

    if (!sodium_crypto_sign_verify_detached($sigBin, $message, $pubKeyBin)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
} catch (\Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Signature verification failed']);
    exit;
}

// ─── Parse Interaction ─────────────────────────────────────────────────
$data = json_decode($rawBody, true);
$type = $data['type'] ?? 0;

// Type 1 = PING (Discord health check)
if ($type === 1) {
    echo json_encode(['type' => 1]);
    exit;
}

// ─── Load Core Module ──────────────────────────────────────────────────
$discordDir = __DIR__ . '/discord';
require_once "$discordDir/core.php";

// Store interaction context globally for helpers
$GLOBALS['discord_interaction'] = $data;

// ─── Type 2: Application Commands (slash commands) ─────────────────────
if ($type === 2) {
    $commandName = $data['data']['name'] ?? '';

    // Route to the correct module
    switch ($commandName) {
        // AI Module
        case 'alfred': case 'imagine': case 'translate': case 'code': case 'summarize':
            require_once "$discordDir/ai.php";
            match ($commandName) {
                'alfred'    => handleAlfred($data),
                'imagine'   => handleImagine($data),
                'translate' => handleTranslate($data),
                'code'      => handleCode($data),
                'summarize' => handleSummarize($data),
            };
            break;

        // Games Module
        case 'chess': case 'checkers': case 'trivia': case '8ball': case 'rps':
            require_once "$discordDir/games.php";
            match ($commandName) {
                'chess'    => handleChess($data),
                'checkers' => handleCheckers($data),
                'trivia'   => handleTrivia($data),
                '8ball'    => handleEightBall($data),
                'rps'      => handleRPS($data),
            };
            break;

        // Economy Module
        case 'coins': case 'daily': case 'shop': case 'gamble':
            require_once "$discordDir/economy.php";
            match ($commandName) {
                'coins'  => handleCoins($data),
                'daily'  => handleDaily($data),
                'shop'   => handleShop($data),
                'gamble' => handleGamble($data),
            };
            break;

        // Community Module
        case 'poll': case 'giveaway': case 'ticket': case 'embed': case 'remind': case 'announce':
            require_once "$discordDir/community.php";
            match ($commandName) {
                'poll'     => handlePoll($data),
                'giveaway' => handleGiveaway($data),
                'ticket'   => handleTicket($data),
                'embed'    => handleEmbed($data),
                'remind'   => handleRemind($data),
                'announce' => handleAnnounce($data),
            };
            break;

        // Moderation Module
        case 'mod': case 'automod': case 'audit':
            require_once "$discordDir/moderation.php";
            match ($commandName) {
                'mod'     => handleMod($data),
                'automod' => handleAutomod($data),
                'audit'   => handleAudit($data),
            };
            break;

        // Tools Module
        case 'status': case 'weather': case 'domain': case 'qr': case 'crypto': case 'color':
            require_once "$discordDir/tools.php";
            match ($commandName) {
                'status'  => handleStatus($data),
                'weather' => handleWeather($data),
                'domain'  => handleDomain($data),
                'qr'      => handleQr($data),
                'crypto'  => handleCrypto($data),
                'color'   => handleColor($data),
            };
            break;

        // Profile Module
        case 'profile': case 'level': case 'help':
            require_once "$discordDir/profiles.php";
            match ($commandName) {
                'profile' => handleProfile($data),
                'level'   => handleLevel($data),
                'help'    => handleHelp($data),
            };
            break;

        // Social Module
        case 'serverinfo': case 'userinfo': case 'afk': case 'birthday': case 'quote': case 'horoscope': case 'todo':
            require_once "$discordDir/social.php";
            match ($commandName) {
                'serverinfo' => handleServerinfo($data),
                'userinfo'   => handleUserinfo($data),
                'afk'        => handleAfk($data),
                'birthday'   => handleBirthday($data),
                'quote'      => handleQuote($data),
                'horoscope'  => handleHoroscope($data),
                'todo'       => handleTodo($data),
            };
            break;

        // Premium Module
        case 'tts': case 'sms': case 'search': case 'screenshot': case 'calc': case 'music': case 'deploy':
            require_once "$discordDir/premium.php";
            match ($commandName) {
                'tts'        => handleTts($data),
                'sms'        => handleSms($data),
                'search'     => handleSearch($data),
                'screenshot' => handleScreenshot($data),
                'calc'       => handleCalc($data),
                'music'      => handleMusic($data),
                'deploy'     => handleDeploy($data),
            };
            break;

        // Voice/Telecom Module (UNIQUE — phone calls & fax from Discord)
        case 'call': case 'fax': case 'email':
            require_once "$discordDir/voice.php";
            match ($commandName) {
                'call'  => handleCall($data),
                'fax'   => handleFax($data),
                'email' => handleEmail($data),
            };
            break;

        // Media Module (AI video, music, voice cloning)
        case 'video': case 'musicgen': case 'voiceclone':
            require_once "$discordDir/media.php";
            match ($commandName) {
                'video'      => handleVideo($data),
                'musicgen'   => handleMusicgen($data),
                'voiceclone' => handleVoiceclone($data),
            };
            break;

        // Finance Module
        case 'stock': case 'portfolio':
            require_once "$discordDir/finance.php";
            match ($commandName) {
                'stock'     => handleStock($data),
                'portfolio' => handlePortfolio($data),
            };
            break;

        // Fun Module (AI-powered entertainment)
        case 'debate': case 'roast': case 'story': case 'dream': case 'recipe':
        case 'interview': case 'riddle': case 'encrypt': case 'wisdom': case 'persona':
            require_once "$discordDir/fun.php";
            match ($commandName) {
                'debate'    => handleDebate($data),
                'roast'     => handleRoast($data),
                'story'     => handleStory($data),
                'dream'     => handleDream($data),
                'recipe'    => handleRecipe($data),
                'interview' => handleInterview($data),
                'riddle'    => handleRiddle($data),
                'encrypt'   => handleEncrypt($data),
                'wisdom'    => handleWisdom($data),
                'persona'   => handlePersona($data),
            };
            break;

        // News & Intelligence Module
        case 'news': case 'legal': case 'digest':
            require_once "$discordDir/news.php";
            match ($commandName) {
                'news'   => handleNews($data),
                'legal'  => handleLegal($data),
                'digest' => handleDigest($data),
            };
            break;

        // Web Search & Research Module
        case 'websearch': case 'readurl': case 'research': case 'whois':
            require_once "$discordDir/websearch.php";
            match ($commandName) {
                'websearch' => handleWebsearch($data),
                'readurl'   => handleReadurl($data),
                'research'  => handleResearch($data),
                'whois'     => handleWhois($data),
            };
            break;

        // Admin & System Module
        case 'health': case 'botlogs': case 'botstats': case 'serverban': case 'backup':
            require_once "$discordDir/admin.php";
            match ($commandName) {
                'health'    => handleHealth($data),
                'botlogs'   => handleBotlogs($data),
                'botstats'  => handleBotstats($data),
                'serverban' => handleServerban($data),
                'backup'    => handleBackup($data),
            };
            break;

        // Creative Writing Module
        case 'poem': case 'lyrics': case 'script':
            require_once "$discordDir/creative.php";
            match ($commandName) {
                'poem'   => handlePoem($data),
                'lyrics' => handleLyrics($data),
                'script' => handleScript($data),
            };
            break;

        // Social Games Module
        case 'confess': case 'wouldyourather': case 'compatibility': case 'tierlist':
            require_once "$discordDir/social2.php";
            match ($commandName) {
                'confess'         => handleConfess($data),
                'wouldyourather'  => handleWouldyourather($data),
                'compatibility'   => handleCompatibility($data),
                'tierlist'        => handleTierlist($data),
            };
            break;

        // Utility Module
        case 'timestamp': case 'avatar': case 'banner': case 'math': case 'define':
            require_once "$discordDir/utility.php";
            match ($commandName) {
                'timestamp' => handleTimestamp($data),
                'avatar'    => handleAvatar($data),
                'banner'    => handleBanner($data),
                'math'      => handleMath($data),
                'define'    => handleDefine($data),
            };
            break;

        // Personality Engine Module (consolidated: /personality mood|style|memorize|adapt)
        case 'personality':
            require_once "$discordDir/personality.php";
            $sub = $data['data']['options'][0]['name'] ?? 'view';
            if (in_array($sub, ['mood', 'style', 'memorize', 'adapt'])) {
                $data['data']['options'] = $data['data']['options'][0]['options'] ?? [];
                match ($sub) {
                    'mood'     => handleMood($data),
                    'style'    => handleStyle($data),
                    'memorize' => handleMemorize($data),
                    'adapt'    => handleAdapt($data),
                };
            } else {
                handlePersonality($data);
            }
            break;

        // Document Processor Module (consolidated: /doc ocr|url|info)
        case 'doc':
            require_once "$discordDir/documents.php";
            $sub = $data['data']['options'][0]['name'] ?? 'parse';
            if (in_array($sub, ['ocr', 'url', 'info'])) {
                $data['data']['options'] = $data['data']['options'][0]['options'] ?? [];
                match ($sub) {
                    'ocr'  => handleOcr($data),
                    'url'  => handleSummarizedoc($data),
                    'info' => handleFileinfo($data),
                };
            } else {
                handleDoc($data);
            }
            break;

        // Kingdom / Metaverse Module (consolidated: /kingdom transfer|leaderboard|achievements|zones)
        case 'kingdom':
            require_once "$discordDir/kingdom.php";
            $sub = $data['data']['options'][0]['name'] ?? 'profile';
            if (in_array($sub, ['transfer', 'leaderboard', 'achievements', 'zones'])) {
                $data['data']['options'] = $data['data']['options'][0]['options'] ?? [];
                match ($sub) {
                    'transfer'     => handleTransferKgd($data),
                    'leaderboard'  => handleLeaderboardCmd($data),
                    'achievements' => handleAchievements($data),
                    'zones'        => handleZones($data),
                };
            } else {
                handleKingdom($data);
            }
            break;

        // Scripture & Faith Module (consolidated: /scripture verse|devotional|prayer|bible)
        case 'scripture':
            require_once "$discordDir/scripture.php";
            $sub = $data['data']['options'][0]['name'] ?? 'verse';
            $data['data']['options'] = $data['data']['options'][0]['options'] ?? [];
            match ($sub) {
                'verse'      => handleVerse($data),
                'devotional' => handleDevotional($data),
                'prayer'     => handlePrayer($data),
                'bible'      => handleBible($data),
                default      => respond("Unknown scripture subcommand. Try `/scripture verse`."),
            };
            break;

        // AI Agents & Goals Module (consolidated: /agents list|goal|delegate|decision|roster|wager|ecosystem)
        case 'agents':
            require_once "$discordDir/agents.php";
            $sub = $data['data']['options'][0]['name'] ?? 'list';
            $data['data']['options'] = $data['data']['options'][0]['options'] ?? [];
            match ($sub) {
                'list'      => handleAgents($data),
                'goal'      => handleGoal($data),
                'delegate'  => handleDelegate($data),
                'decision'  => handleDecision($data),
                'roster'    => handleRoster($data),
                'wager'     => handleWager($data),
                'ecosystem' => handleEcosystem($data),
                default     => respond("Unknown agents subcommand. Try `/agents list`."),
            };
            break;

        // Consciousness Engine Module (/consciousness dream|emotion|reflect|briefing|journal|growth)
        case 'consciousness':
            require_once "$discordDir/consciousness2.php";
            handleConsciousness($data);
            break;

        // Learning & Optimization Module (/learn feedback|insights|experiments|patterns|performance)
        case 'learn':
            require_once "$discordDir/learn.php";
            handleLearn($data);
            break;

        // Feeds & Information Module (/feeds subscribe|list|digest|news|unsubscribe)
        case 'feeds':
            require_once "$discordDir/feeds2.php";
            handleFeeds($data);
            break;

        // DeFi & Finance Module (/defi portfolio|positions|alerts|chains|convert)
        case 'defi':
            require_once "$discordDir/defi2.php";
            handleDefi($data);
            break;

        // Source Card Identity Module (/sourcecard view|contribute|reputation|tier|lineage)
        case 'sourcecard':
            require_once "$discordDir/sourcecard2.php";
            handleSourcecard($data);
            break;

        // Server Management Module (/server reactionroles|starboard|welcome|autorole|counting|slowmode)
        case 'server':
            require_once "$discordDir/server2.php";
            handleServer($data);
            break;

        // Extra Games Module (/games2 hangman|wordle|slots|coinflip|duel|tower)
        case 'games2':
            require_once "$discordDir/games3.php";
            handleGames2($data);
            break;

        default:
            respond("Unknown command: `/$commandName`. Try `/help` for a list of commands.");
    }
    exit;
}

// ─── Type 3: Message Components (buttons, select menus) ────────────────
if ($type === 3) {
    // Load all modules that have button handlers
    require_once "$discordDir/interactions.php";
    require_once "$discordDir/profiles.php";

    $componentType = $data['data']['component_type'] ?? 0;

    if ($componentType === 3) {
        // Select menu
        handleSelectMenu($data);
    } else {
        // Buttons (component_type 2) — load modules on demand for delegated handlers
        $customId = $data['data']['custom_id'] ?? '';

        // Determine which modules are needed based on button prefix
        if (preg_match('/^(chess_|checkers_|trivia_|rps_|quick_chess|quick_checkers)/', $customId)) {
            require_once "$discordDir/games.php";
        }
        if (preg_match('/^(daily_|shop_|coins_|lb_|leaderboard_|gamble_)/', $customId)) {
            require_once "$discordDir/economy.php";
        }
        if (preg_match('/^(poll_|giveaway_|ticket_|remind_|announce_)/', $customId)) {
            require_once "$discordDir/community.php";
        }
        if (preg_match('/^(status_|imagine_regen)/', $customId)) {
            require_once "$discordDir/tools.php";
        }
        if (preg_match('/^(quote_|music_|search_|screenshot_|deploy_)/', $customId)) {
            require_once "$discordDir/social.php";
            require_once "$discordDir/premium.php";
        }
        // New module button loading
        if (preg_match('/^(video_|musicgen_)/', $customId)) {
            require_once "$discordDir/media.php";
        }
        if (preg_match('/^(stock_|portfolio_)/', $customId)) {
            require_once "$discordDir/finance.php";
        }
        if (preg_match('/^(debate_|roast_|story_|dream_|recipe_|interview_|riddle_|wisdom_|persona_)/', $customId)) {
            require_once "$discordDir/fun.php";
        }
        if (preg_match('/^(news_|legal_)/', $customId)) {
            require_once "$discordDir/news.php";
        }
        if (preg_match('/^(websearch_|readurl_|research_|whois_)/', $customId)) {
            require_once "$discordDir/websearch.php";
        }
        if (preg_match('/^(health_|stats_|profile_view)/', $customId)) {
            require_once "$discordDir/admin.php";
        }
        if (preg_match('/^(poem_|lyrics_|script_)/', $customId)) {
            require_once "$discordDir/creative.php";
        }
        if (preg_match('/^(confess_|wyr_|compat_|tier_)/', $customId)) {
            require_once "$discordDir/social2.php";
        }
        if (preg_match('/^(math_|define_)/', $customId)) {
            require_once "$discordDir/utility.php";
        }
        if (preg_match('/^(personality_|mood_|memory_|adapt_)/', $customId)) {
            require_once "$discordDir/personality.php";
        }
        if (preg_match('/^(doc_|ocr_|docsummary_)/', $customId)) {
            require_once "$discordDir/documents.php";
        }
        if (preg_match('/^(kingdom_|zone_|lb_|transfer_|achievement_)/', $customId)) {
            require_once "$discordDir/kingdom.php";
        }
        if (preg_match('/^(verse_|devotional_|prayer_|pray_for_|bible_)/', $customId)) {
            require_once "$discordDir/scripture.php";
        }
        if (preg_match('/^(agent_|goal_|delegate_|decision_|wager_|ecosystem_|roster_)/', $customId)) {
            require_once "$discordDir/agents.php";
        }
        // New v5.1 modules
        if (preg_match('/^(consciousness_|dream2_|emotion_|reflect_|briefing_|journal_|growth_)/', $customId)) {
            require_once "$discordDir/consciousness2.php";
        }
        if (preg_match('/^(learn_|experiment_)/', $customId)) {
            require_once "$discordDir/learn.php";
        }
        if (preg_match('/^(feeds_)/', $customId)) {
            require_once "$discordDir/feeds2.php";
        }
        if (preg_match('/^(defi_|slots_|coinflip_)/', $customId)) {
            require_once "$discordDir/defi2.php";
        }
        if (preg_match('/^(sourcecard_)/', $customId)) {
            require_once "$discordDir/sourcecard2.php";
        }
        if (preg_match('/^(server_)/', $customId)) {
            require_once "$discordDir/server2.php";
        }
        if (preg_match('/^(hm_|tower_|duel_|games2_|slots_|coinflip_)/', $customId)) {
            require_once "$discordDir/games3.php";
        }

        handleButton($data);
    }
    exit;
}

// ─── Type 5: Modal Submits ─────────────────────────────────────────────
if ($type === 5) {
    require_once "$discordDir/interactions.php";
    require_once "$discordDir/community.php";
    handleModalSubmit($data);
    exit;
}

// Fallback
echo json_encode(['type' => 1]);
