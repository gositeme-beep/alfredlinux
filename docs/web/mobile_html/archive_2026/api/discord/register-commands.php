<?php
/**
 * Register all 100 Discord slash commands (150+ features consolidated into subcommands)
 * Run: php api/discord/register-commands.php
 */
$envFile = dirname(dirname(dirname(__DIR__))) . '/.env.php';
if (file_exists($envFile)) require_once $envFile;

$botToken = getenv('DISCORD_BOT_TOKEN') ?: '';
$appId = getenv('DISCORD_APP_ID') ?: '1479627736208375981';

if (!$botToken) { die("DISCORD_BOT_TOKEN not set\n"); }

$commands = [
    // ── AI Module ──
    ['name' => 'alfred', 'description' => 'Chat with GoSiteMe AI (5 personas)', 'options' => [
        ['type' => 3, 'name' => 'message', 'description' => 'Your message', 'required' => true],
        ['type' => 3, 'name' => 'persona', 'description' => 'AI persona', 'choices' => [
            ['name' => '🎩 Alfred (Butler)', 'value' => 'alfred'], ['name' => '🎨 Nova (Creative)', 'value' => 'nova'],
            ['name' => '📚 Sage (Knowledge)', 'value' => 'sage'], ['name' => '🔐 Cipher (Security)', 'value' => 'cipher'],
            ['name' => '📊 Atlas (Business)', 'value' => 'atlas'],
        ]],
    ]],
    ['name' => 'imagine', 'description' => 'Generate AI images', 'options' => [
        ['type' => 3, 'name' => 'prompt', 'description' => 'Image description', 'required' => true],
        ['type' => 3, 'name' => 'model', 'description' => 'Model to use', 'choices' => [
            ['name' => 'FLUX.1 Schnell', 'value' => 'flux-schnell'], ['name' => 'FLUX.1 Dev', 'value' => 'flux-dev'],
            ['name' => 'Stable Diffusion XL', 'value' => 'sdxl'], ['name' => 'Playground v2.5', 'value' => 'playground'],
        ]],
    ]],
    ['name' => 'translate', 'description' => 'AI translation', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text to translate', 'required' => true],
        ['type' => 3, 'name' => 'to', 'description' => 'Target language', 'required' => true],
    ]],
    ['name' => 'code', 'description' => 'AI code assistant', 'options' => [
        ['type' => 1, 'name' => 'run', 'description' => 'Run/explain code', 'options' => [
            ['type' => 3, 'name' => 'code', 'description' => 'Code to execute', 'required' => true],
            ['type' => 3, 'name' => 'language', 'description' => 'Programming language'],
        ]],
        ['type' => 1, 'name' => 'review', 'description' => 'Review code quality', 'options' => [
            ['type' => 3, 'name' => 'code', 'description' => 'Code to review', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'explain', 'description' => 'Explain code', 'options' => [
            ['type' => 3, 'name' => 'code', 'description' => 'Code to explain', 'required' => true],
        ]],
    ]],
    ['name' => 'summarize', 'description' => 'Summarize text with AI', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text to summarize', 'required' => true],
    ]],

    // ── Games Module ──
    ['name' => 'chess', 'description' => 'Play chess in Discord', 'options' => [
        ['type' => 1, 'name' => 'play', 'description' => 'Play vs AI'],
        ['type' => 1, 'name' => 'challenge', 'description' => 'Challenge a user', 'options' => [
            ['type' => 6, 'name' => 'opponent', 'description' => 'User to challenge', 'required' => true],
            ['type' => 4, 'name' => 'wager', 'description' => 'KGD wager amount'],
        ]],
        ['type' => 1, 'name' => 'move', 'description' => 'Make a move', 'options' => [
            ['type' => 3, 'name' => 'move', 'description' => 'Chess move (e.g. e2e4)', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'board', 'description' => 'View current board'],
        ['type' => 1, 'name' => 'resign', 'description' => 'Resign current game'],
    ]],
    ['name' => 'checkers', 'description' => 'Play checkers in Discord', 'options' => [
        ['type' => 1, 'name' => 'play', 'description' => 'Play vs AI'],
        ['type' => 1, 'name' => 'challenge', 'description' => 'Challenge a user', 'options' => [
            ['type' => 6, 'name' => 'opponent', 'description' => 'User to challenge', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'move', 'description' => 'Make a move', 'options' => [
            ['type' => 3, 'name' => 'move', 'description' => 'Move (e.g. c3-d4)', 'required' => true],
        ]],
    ]],
    ['name' => 'trivia', 'description' => 'Play trivia for KGD', 'options' => [
        ['type' => 3, 'name' => 'difficulty', 'description' => 'Difficulty', 'choices' => [
            ['name' => 'Easy (10 KGD)', 'value' => 'easy'],
            ['name' => 'Medium (25 KGD)', 'value' => 'medium'],
            ['name' => 'Hard (50 KGD)', 'value' => 'hard'],
        ]],
        ['type' => 3, 'name' => 'category', 'description' => 'Category'],
    ]],
    ['name' => '8ball', 'description' => 'Ask the magic 8-ball', 'options' => [
        ['type' => 3, 'name' => 'question', 'description' => 'Your question', 'required' => true],
    ]],
    ['name' => 'rps', 'description' => 'Rock Paper Scissors', 'options' => [
        ['type' => 3, 'name' => 'choice', 'description' => 'Your choice', 'choices' => [
            ['name' => '🪨 Rock', 'value' => 'rock'], ['name' => '📄 Paper', 'value' => 'paper'],
            ['name' => '✂️ Scissors', 'value' => 'scissors'],
        ]],
        ['type' => 6, 'name' => 'opponent', 'description' => 'Challenge someone'],
    ]],

    // ── Economy Module ──
    ['name' => 'coins', 'description' => 'Kingdom economy', 'options' => [
        ['type' => 1, 'name' => 'balance', 'description' => 'Check your balance'],
        ['type' => 1, 'name' => 'send', 'description' => 'Send KGD to a user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'Recipient', 'required' => true],
            ['type' => 4, 'name' => 'amount', 'description' => 'Amount to send', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'leaderboard', 'description' => 'View leaderboards', 'options' => [
            ['type' => 3, 'name' => 'type', 'description' => 'Leaderboard type', 'choices' => [
                ['name' => '💰 Richest', 'value' => 'kgd'], ['name' => '⬆️ Highest Level', 'value' => 'level'],
                ['name' => '♟️ Chess ELO', 'value' => 'elo'],
            ]],
        ]],
    ]],
    ['name' => 'daily', 'description' => 'Claim daily KGD reward'],
    ['name' => 'shop', 'description' => 'Kingdom shop', 'options' => [
        ['type' => 1, 'name' => 'browse', 'description' => 'Browse items', 'options' => [
            ['type' => 3, 'name' => 'category', 'description' => 'Category'],
        ]],
        ['type' => 1, 'name' => 'buy', 'description' => 'Buy an item', 'options' => [
            ['type' => 4, 'name' => 'item_id', 'description' => 'Item ID', 'required' => true],
        ]],
    ]],
    ['name' => 'gamble', 'description' => 'Gambling games', 'options' => [
        ['type' => 1, 'name' => 'coinflip', 'description' => 'Flip a coin', 'options' => [
            ['type' => 4, 'name' => 'amount', 'description' => 'KGD to bet', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'dice', 'description' => 'Roll dice', 'options' => [
            ['type' => 4, 'name' => 'amount', 'description' => 'KGD to bet', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'slots', 'description' => 'Slot machine', 'options' => [
            ['type' => 4, 'name' => 'amount', 'description' => 'KGD to bet', 'required' => true],
        ]],
    ]],

    // ── Community Module ──
    ['name' => 'poll', 'description' => 'Create a poll', 'options' => [
        ['type' => 3, 'name' => 'question', 'description' => 'Poll question', 'required' => true],
        ['type' => 3, 'name' => 'option1', 'description' => 'Option 1', 'required' => true],
        ['type' => 3, 'name' => 'option2', 'description' => 'Option 2', 'required' => true],
        ['type' => 3, 'name' => 'option3', 'description' => 'Option 3'],
        ['type' => 3, 'name' => 'option4', 'description' => 'Option 4'],
    ]],
    ['name' => 'giveaway', 'description' => 'Create a giveaway', 'options' => [
        ['type' => 3, 'name' => 'prize', 'description' => 'Prize description', 'required' => true],
        ['type' => 4, 'name' => 'duration', 'description' => 'Duration in minutes', 'required' => true],
        ['type' => 4, 'name' => 'winners', 'description' => 'Number of winners'],
    ]],
    ['name' => 'ticket', 'description' => 'Support ticket', 'options' => [
        ['type' => 1, 'name' => 'create', 'description' => 'Create a ticket', 'options' => [
            ['type' => 3, 'name' => 'subject', 'description' => 'Ticket subject', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'list', 'description' => 'List your tickets'],
    ]],
    ['name' => 'embed', 'description' => 'Create a custom embed', 'options' => [
        ['type' => 3, 'name' => 'title', 'description' => 'Embed title', 'required' => true],
        ['type' => 3, 'name' => 'description', 'description' => 'Embed description', 'required' => true],
        ['type' => 3, 'name' => 'color', 'description' => 'Hex color (e.g. FF0000)'],
    ]],
    ['name' => 'remind', 'description' => 'Set a reminder', 'options' => [
        ['type' => 3, 'name' => 'message', 'description' => 'Reminder message', 'required' => true],
        ['type' => 4, 'name' => 'minutes', 'description' => 'Minutes from now', 'required' => true],
    ]],
    ['name' => 'announce', 'description' => 'Make an announcement', 'options' => [
        ['type' => 3, 'name' => 'message', 'description' => 'Announcement text', 'required' => true],
        ['type' => 3, 'name' => 'title', 'description' => 'Title'],
    ]],

    // ── Moderation Module ──
    ['name' => 'mod', 'description' => 'Moderation tools', 'options' => [
        ['type' => 1, 'name' => 'warn', 'description' => 'Warn a user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User to warn', 'required' => true],
            ['type' => 3, 'name' => 'reason', 'description' => 'Reason', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'mute', 'description' => 'Mute a user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User', 'required' => true],
            ['type' => 4, 'name' => 'duration', 'description' => 'Minutes'],
            ['type' => 3, 'name' => 'reason', 'description' => 'Reason'],
        ]],
        ['type' => 1, 'name' => 'unmute', 'description' => 'Unmute a user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'kick', 'description' => 'Kick user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User', 'required' => true],
            ['type' => 3, 'name' => 'reason', 'description' => 'Reason'],
        ]],
        ['type' => 1, 'name' => 'ban', 'description' => 'Ban user', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User', 'required' => true],
            ['type' => 3, 'name' => 'reason', 'description' => 'Reason'],
        ]],
        ['type' => 1, 'name' => 'unban', 'description' => 'Unban user', 'options' => [
            ['type' => 3, 'name' => 'user_id', 'description' => 'User ID', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'purge', 'description' => 'Delete messages', 'options' => [
            ['type' => 4, 'name' => 'count', 'description' => 'Messages to delete (1-100)', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'warnings', 'description' => 'View warnings', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User'],
        ]],
        ['type' => 1, 'name' => 'slowmode', 'description' => 'Set slowmode', 'options' => [
            ['type' => 4, 'name' => 'seconds', 'description' => 'Seconds (0=off)', 'required' => true],
        ]],
    ]],
    ['name' => 'automod', 'description' => 'AutoMod settings', 'options' => [
        ['type' => 1, 'name' => 'setup', 'description' => 'Configure automod'],
        ['type' => 1, 'name' => 'status', 'description' => 'View current settings'],
    ]],
    ['name' => 'audit', 'description' => 'Audit log viewer', 'options' => [
        ['type' => 4, 'name' => 'limit', 'description' => 'Entries to show (1-20)'],
    ]],

    // ── Tools Module ──
    ['name' => 'status', 'description' => 'Check website status', 'options' => [
        ['type' => 3, 'name' => 'url', 'description' => 'URL to check', 'required' => true],
    ]],
    ['name' => 'weather', 'description' => 'Get weather info', 'options' => [
        ['type' => 3, 'name' => 'city', 'description' => 'City name', 'required' => true],
    ]],
    ['name' => 'domain', 'description' => 'Domain lookup (WHOIS)', 'options' => [
        ['type' => 3, 'name' => 'domain', 'description' => 'Domain name', 'required' => true],
    ]],
    ['name' => 'qr', 'description' => 'Generate QR code', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text or URL', 'required' => true],
    ]],
    ['name' => 'crypto', 'description' => 'Crypto price checker', 'options' => [
        ['type' => 3, 'name' => 'coin', 'description' => 'Cryptocurrency (e.g. bitcoin)', 'required' => true],
    ]],
    ['name' => 'color', 'description' => 'Color info & preview', 'options' => [
        ['type' => 3, 'name' => 'hex', 'description' => 'Hex color (e.g. FF5733)', 'required' => true],
    ]],

    // ── Profile Module ──
    ['name' => 'profile', 'description' => 'View your Kingdom profile', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'View another user'],
    ]],
    ['name' => 'level', 'description' => 'Check your level & XP', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'Check another user'],
    ]],
    ['name' => 'help', 'description' => 'Bot help & command list', 'options' => [
        ['type' => 3, 'name' => 'category', 'description' => 'Category', 'choices' => [
            ['name' => '🤖 AI', 'value' => 'ai'], ['name' => '🎮 Games', 'value' => 'games'],
            ['name' => '💰 Economy', 'value' => 'economy'], ['name' => '🏘️ Community', 'value' => 'community'],
            ['name' => '🛡️ Moderation', 'value' => 'mod'], ['name' => '🔧 Tools', 'value' => 'tools'],
            ['name' => '👥 Social', 'value' => 'social'], ['name' => '⭐ Premium', 'value' => 'premium'],
            ['name' => '📞 Voice', 'value' => 'voice'], ['name' => '🎬 Media', 'value' => 'media'],
            ['name' => '📈 Finance', 'value' => 'finance'], ['name' => '🎭 Fun', 'value' => 'fun'],
            ['name' => '📰 News', 'value' => 'news'], ['name' => '� Web Search', 'value' => 'websearch'],
            ['name' => '🖥️ Admin', 'value' => 'admin'], ['name' => '✍️ Creative', 'value' => 'creative'],
            ['name' => '🎲 Social Games', 'value' => 'social2'], ['name' => '🛠️ Utility', 'value' => 'utility'],
            ['name' => '🧠 Personality', 'value' => 'personality'], ['name' => '📄 Documents', 'value' => 'documents'],
            ['name' => '🏰 Kingdom', 'value' => 'kingdom'], ['name' => '📖 Scripture', 'value' => 'scripture'],
            ['name' => '🤖 Agents', 'value' => 'agents'], ['name' => '👤 Profile', 'value' => 'profile'],
        ]],
    ]],

    // ── Social Module (NEW) ──
    ['name' => 'serverinfo', 'description' => 'Server statistics & information'],
    ['name' => 'userinfo', 'description' => 'Detailed user information', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'User to look up'],
    ]],
    ['name' => 'afk', 'description' => 'Set your AFK status', 'options' => [
        ['type' => 3, 'name' => 'message', 'description' => 'AFK message (default: AFK)'],
    ]],
    ['name' => 'birthday', 'description' => 'Birthday tracking system', 'options' => [
        ['type' => 1, 'name' => 'set', 'description' => 'Set your birthday', 'options' => [
            ['type' => 4, 'name' => 'month', 'description' => 'Birth month (1-12)', 'required' => true, 'min_value' => 1, 'max_value' => 12],
            ['type' => 4, 'name' => 'day', 'description' => 'Birth day (1-31)', 'required' => true, 'min_value' => 1, 'max_value' => 31],
        ]],
        ['type' => 1, 'name' => 'check', 'description' => 'Check a birthday', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User to check'],
        ]],
        ['type' => 1, 'name' => 'today', 'description' => 'See today\'s birthdays'],
        ['type' => 1, 'name' => 'upcoming', 'description' => 'See upcoming birthdays'],
    ]],
    ['name' => 'quote', 'description' => 'AI-generated inspirational quote', 'options' => [
        ['type' => 3, 'name' => 'category', 'description' => 'Quote category', 'choices' => [
            ['name' => '💡 Inspirational', 'value' => 'inspirational'], ['name' => '😄 Funny', 'value' => 'funny'],
            ['name' => '💪 Motivational', 'value' => 'motivational'], ['name' => '📚 Philosophical', 'value' => 'philosophical'],
            ['name' => '💼 Business', 'value' => 'business'], ['name' => '🔬 Science', 'value' => 'science'],
        ]],
    ]],
    ['name' => 'horoscope', 'description' => 'Daily AI horoscope reading', 'options' => [
        ['type' => 3, 'name' => 'sign', 'description' => 'Zodiac sign', 'required' => true, 'choices' => [
            ['name' => '♈ Aries', 'value' => 'aries'], ['name' => '♉ Taurus', 'value' => 'taurus'],
            ['name' => '♊ Gemini', 'value' => 'gemini'], ['name' => '♋ Cancer', 'value' => 'cancer'],
            ['name' => '♌ Leo', 'value' => 'leo'], ['name' => '♍ Virgo', 'value' => 'virgo'],
            ['name' => '♎ Libra', 'value' => 'libra'], ['name' => '♏ Scorpio', 'value' => 'scorpio'],
            ['name' => '♐ Sagittarius', 'value' => 'sagittarius'], ['name' => '♑ Capricorn', 'value' => 'capricorn'],
            ['name' => '♒ Aquarius', 'value' => 'aquarius'], ['name' => '♓ Pisces', 'value' => 'pisces'],
        ]],
    ]],
    ['name' => 'todo', 'description' => 'Personal task manager', 'options' => [
        ['type' => 1, 'name' => 'add', 'description' => 'Add a task', 'options' => [
            ['type' => 3, 'name' => 'task', 'description' => 'Task description', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'list', 'description' => 'View your tasks'],
        ['type' => 1, 'name' => 'done', 'description' => 'Mark task complete', 'options' => [
            ['type' => 4, 'name' => 'id', 'description' => 'Task ID', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'remove', 'description' => 'Delete a task', 'options' => [
            ['type' => 4, 'name' => 'id', 'description' => 'Task ID', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'clear', 'description' => 'Clear completed tasks'],
    ]],

    // ── Premium Module (NEW — GoSiteMe Exclusive) ──
    ['name' => 'tts', 'description' => 'AI text-to-speech — hear your text spoken', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text to speak (max 1000 chars)', 'required' => true],
        ['type' => 3, 'name' => 'voice', 'description' => 'Voice to use', 'choices' => [
            ['name' => 'Alloy', 'value' => 'alloy'], ['name' => 'Echo', 'value' => 'echo'],
            ['name' => 'Fable', 'value' => 'fable'], ['name' => 'Onyx', 'value' => 'onyx'],
            ['name' => 'Nova', 'value' => 'nova'], ['name' => 'Shimmer', 'value' => 'shimmer'],
        ]],
    ]],
    ['name' => 'sms', 'description' => 'Send an SMS from Discord (5 KGD)', 'options' => [
        ['type' => 3, 'name' => 'phone', 'description' => 'Phone number (+15551234567)', 'required' => true],
        ['type' => 3, 'name' => 'message', 'description' => 'Message (max 160 chars)', 'required' => true],
    ]],
    ['name' => 'search', 'description' => 'AI-powered web search', 'options' => [
        ['type' => 3, 'name' => 'query', 'description' => 'Search query', 'required' => true],
    ]],
    ['name' => 'screenshot', 'description' => 'Capture a website screenshot', 'options' => [
        ['type' => 3, 'name' => 'url', 'description' => 'Website URL (e.g. example.com)', 'required' => true],
    ]],
    ['name' => 'calc', 'description' => 'Calculator & unit converter', 'options' => [
        ['type' => 3, 'name' => 'expression', 'description' => 'Math expression or conversion (e.g. 50 km to mi)', 'required' => true],
    ]],
    ['name' => 'music', 'description' => 'AI music recommendations', 'options' => [
        ['type' => 3, 'name' => 'mood', 'description' => 'Your current mood', 'required' => true, 'choices' => [
            ['name' => '😌 Chill', 'value' => 'chill'], ['name' => '🔥 Hype', 'value' => 'hype'],
            ['name' => '😢 Sad', 'value' => 'sad'], ['name' => '🥰 Romantic', 'value' => 'romantic'],
            ['name' => '💪 Workout', 'value' => 'workout'], ['name' => '🧠 Focus', 'value' => 'focus'],
            ['name' => '🎉 Party', 'value' => 'party'], ['name' => '🌙 Sleep', 'value' => 'sleep'],
        ]],
        ['type' => 3, 'name' => 'genre', 'description' => 'Preferred genre'],
    ]],
    ['name' => 'deploy', 'description' => 'Deploy a website from Discord — AI builds & hosts it instantly', 'options' => [
        ['type' => 1, 'name' => 'website', 'description' => 'Generate & deploy a full website with AI', 'options' => [
            ['type' => 3, 'name' => 'name', 'description' => 'Project name (3-30 chars, lowercase)', 'required' => true],
            ['type' => 3, 'name' => 'description', 'description' => 'What the website is about', 'required' => true],
            ['type' => 3, 'name' => 'template', 'description' => 'Template style', 'choices' => [
                ['name' => '🏠 Landing Page', 'value' => 'landing'], ['name' => '📱 Portfolio', 'value' => 'portfolio'],
                ['name' => '🛒 E-Commerce', 'value' => 'ecommerce'], ['name' => '📝 Blog', 'value' => 'blog'],
                ['name' => '🚀 SaaS', 'value' => 'saas'], ['name' => '🍽️ Restaurant', 'value' => 'restaurant'],
                ['name' => '🎨 Agency', 'value' => 'agency'], ['name' => '📄 Resume/CV', 'value' => 'resume'],
            ]],
        ]],
        ['type' => 1, 'name' => 'status', 'description' => 'Check deployment status'],
        ['type' => 1, 'name' => 'billing', 'description' => 'Subscribe to Alfred Premium — unlock all features', 'options' => [
            ['type' => 3, 'name' => 'plan', 'description' => 'Plan to subscribe to', 'required' => true, 'choices' => [
                ['name' => '⭐ Starter — $3.99/mo', 'value' => 'starter'],
                ['name' => '🚀 Professional — $9.99/mo', 'value' => 'professional'],
                ['name' => '👑 Enterprise — $24.99/mo', 'value' => 'enterprise'],
                ['name' => '💎 Enterprise+ — $99/mo', 'value' => 'enterprise_plus'],
            ]],
        ]],
        ['type' => 1, 'name' => 'plans', 'description' => 'View all Alfred Premium plans & pricing'],
    ]],

    // ── Voice/Telecom Module (UNIQUE — No other bot has this) ──
    ['name' => 'call', 'description' => 'Make a real phone call from Discord (10 KGD)', 'options' => [
        ['type' => 3, 'name' => 'phone', 'description' => 'Phone number (+15551234567)', 'required' => true],
        ['type' => 3, 'name' => 'greeting', 'description' => 'What to say when they answer', 'required' => true],
    ]],
    ['name' => 'fax', 'description' => 'Send a fax from Discord (15 KGD)', 'options' => [
        ['type' => 3, 'name' => 'number', 'description' => 'Fax number (+15551234567)', 'required' => true],
        ['type' => 3, 'name' => 'document', 'description' => 'Document URL (PDF, TIFF, or image)', 'required' => true],
    ]],
    ['name' => 'email', 'description' => 'Send an email from Discord (3 KGD)', 'options' => [
        ['type' => 3, 'name' => 'to', 'description' => 'Email address', 'required' => true],
        ['type' => 3, 'name' => 'subject', 'description' => 'Email subject', 'required' => true],
        ['type' => 3, 'name' => 'body', 'description' => 'Email body', 'required' => true],
    ]],

    // ── Media Module (AI Video, Music, Voice) ──
    ['name' => 'video', 'description' => 'Generate AI video from text (25 KGD)', 'options' => [
        ['type' => 3, 'name' => 'prompt', 'description' => 'Video description (e.g. "a cat dancing on mars")', 'required' => true],
    ]],
    ['name' => 'musicgen', 'description' => 'Generate AI music from text (15 KGD)', 'options' => [
        ['type' => 3, 'name' => 'prompt', 'description' => 'Music description (e.g. "upbeat electronic dance")', 'required' => true],
        ['type' => 4, 'name' => 'duration', 'description' => 'Duration in seconds (5-30)', 'min_value' => 5, 'max_value' => 30],
    ]],
    ['name' => 'voiceclone', 'description' => 'Premium AI voice synthesis (10 KGD)', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text to speak (max 2000 chars)', 'required' => true],
        ['type' => 3, 'name' => 'voice', 'description' => 'Voice to use', 'choices' => [
            ['name' => '👩 Bella', 'value' => 'bella'], ['name' => '👩 Rachel', 'value' => 'rachel'],
            ['name' => '👨 Adam', 'value' => 'adam'], ['name' => '👨 Sam', 'value' => 'sam'],
            ['name' => '👩 Elli', 'value' => 'elli'], ['name' => '👨 Josh', 'value' => 'josh'],
            ['name' => '👨 Arnold', 'value' => 'arnold'], ['name' => '👩 Domi', 'value' => 'domi'],
        ]],
    ]],

    // ── Finance Module ──
    ['name' => 'stock', 'description' => 'Real-time crypto & stock prices', 'options' => [
        ['type' => 3, 'name' => 'ticker', 'description' => 'Ticker (btc, eth, solana, dogecoin)', 'required' => true],
    ]],
    ['name' => 'portfolio', 'description' => 'View your Kingdom financial overview'],

    // ── Fun Module (AI-Powered Entertainment) ──
    ['name' => 'debate', 'description' => 'Watch two AI personas debate any topic', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Debate topic', 'required' => true],
    ]],
    ['name' => 'roast', 'description' => 'AI roast battle (friendly)', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'User to roast (optional)'],
        ['type' => 3, 'name' => 'intensity', 'description' => 'Roast intensity', 'choices' => [
            ['name' => '😊 Mild', 'value' => 'mild'], ['name' => '😈 Medium', 'value' => 'medium'],
            ['name' => '💀 Savage', 'value' => 'savage'],
        ]],
    ]],
    ['name' => 'story', 'description' => 'Collaborative AI story with choices', 'options' => [
        ['type' => 3, 'name' => 'genre', 'description' => 'Story genre', 'required' => true, 'choices' => [
            ['name' => '🐉 Fantasy', 'value' => 'fantasy'], ['name' => '🚀 Sci-Fi', 'value' => 'sci-fi'],
            ['name' => '🔪 Horror', 'value' => 'horror'], ['name' => '💕 Romance', 'value' => 'romance'],
            ['name' => '🔍 Mystery', 'value' => 'mystery'], ['name' => '🤠 Western', 'value' => 'western'],
        ]],
        ['type' => 3, 'name' => 'beginning', 'description' => 'How the story begins'],
    ]],
    ['name' => 'dream', 'description' => 'AI dream interpretation & analysis', 'options' => [
        ['type' => 3, 'name' => 'description', 'description' => 'Describe your dream', 'required' => true],
    ]],
    ['name' => 'recipe', 'description' => 'AI recipe generator from ingredients', 'options' => [
        ['type' => 3, 'name' => 'ingredients', 'description' => 'Your ingredients (comma-separated)', 'required' => true],
        ['type' => 3, 'name' => 'cuisine', 'description' => 'Cuisine style', 'choices' => [
            ['name' => '🍕 Italian', 'value' => 'italian'], ['name' => '🍣 Japanese', 'value' => 'japanese'],
            ['name' => '🌮 Mexican', 'value' => 'mexican'], ['name' => '🍛 Indian', 'value' => 'indian'],
            ['name' => '🥖 French', 'value' => 'french'], ['name' => '🍜 Chinese', 'value' => 'chinese'],
            ['name' => '🥘 Mediterranean', 'value' => 'mediterranean'], ['name' => '🍔 American', 'value' => 'american'],
        ]],
    ]],
    ['name' => 'interview', 'description' => 'AI mock job interview practice', 'options' => [
        ['type' => 3, 'name' => 'role', 'description' => 'Job role (e.g. "Software Engineer at Google")', 'required' => true],
    ]],
    ['name' => 'riddle', 'description' => 'AI riddle challenge with hints', 'options' => [
        ['type' => 3, 'name' => 'difficulty', 'description' => 'Difficulty level', 'choices' => [
            ['name' => '🟢 Easy (5 KGD)', 'value' => 'easy'],
            ['name' => '🟡 Medium (10 KGD)', 'value' => 'medium'],
            ['name' => '🔴 Hard (20 KGD)', 'value' => 'hard'],
        ]],
    ]],
    ['name' => 'encrypt', 'description' => 'Encryption, hashing & encoding tools', 'options' => [
        ['type' => 1, 'name' => 'hash', 'description' => 'Hash text with various algorithms', 'options' => [
            ['type' => 3, 'name' => 'text', 'description' => 'Text to hash', 'required' => true],
            ['type' => 3, 'name' => 'algorithm', 'description' => 'Hash algorithm', 'choices' => [
                ['name' => 'MD5', 'value' => 'md5'], ['name' => 'SHA-1', 'value' => 'sha1'],
                ['name' => 'SHA-256', 'value' => 'sha256'], ['name' => 'SHA-512', 'value' => 'sha512'],
            ]],
        ]],
        ['type' => 1, 'name' => 'encode', 'description' => 'Encode text', 'options' => [
            ['type' => 3, 'name' => 'text', 'description' => 'Text to encode', 'required' => true],
            ['type' => 3, 'name' => 'format', 'description' => 'Encoding format', 'choices' => [
                ['name' => 'Base64', 'value' => 'base64'], ['name' => 'Hex', 'value' => 'hex'],
                ['name' => 'ROT13', 'value' => 'rot13'], ['name' => 'Binary', 'value' => 'binary'],
                ['name' => 'Reverse', 'value' => 'reverse'],
            ]],
        ]],
        ['type' => 1, 'name' => 'decode', 'description' => 'Decode text', 'options' => [
            ['type' => 3, 'name' => 'text', 'description' => 'Text to decode', 'required' => true],
            ['type' => 3, 'name' => 'format', 'description' => 'Encoding format', 'choices' => [
                ['name' => 'Base64', 'value' => 'base64'], ['name' => 'Hex', 'value' => 'hex'],
                ['name' => 'ROT13', 'value' => 'rot13'], ['name' => 'Binary', 'value' => 'binary'],
                ['name' => 'Reverse', 'value' => 'reverse'],
            ]],
        ]],
        ['type' => 1, 'name' => 'password', 'description' => 'Generate a secure password', 'options' => [
            ['type' => 4, 'name' => 'length', 'description' => 'Password length (8-128)', 'min_value' => 8, 'max_value' => 128],
        ]],
    ]],
    ['name' => 'wisdom', 'description' => 'Daily AI wisdom & life advice', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Topic (love, career, health, mindfulness)'],
    ]],
    ['name' => 'persona', 'description' => 'Talk to famous historical figures', 'options' => [
        ['type' => 3, 'name' => 'name', 'description' => 'Historical figure (e.g. Einstein, Cleopatra)', 'required' => true],
        ['type' => 3, 'name' => 'message', 'description' => 'What to ask them', 'required' => true],
    ]],

    // ── News & Intelligence Module ──
    ['name' => 'news', 'description' => 'Latest news from RSS feeds', 'options' => [
        ['type' => 3, 'name' => 'category', 'description' => 'News category', 'choices' => [
            ['name' => '💻 Tech', 'value' => 'tech'], ['name' => '₿ Crypto', 'value' => 'crypto'],
            ['name' => '🔒 Security', 'value' => 'security'], ['name' => '🤖 AI', 'value' => 'ai'],
            ['name' => '🎮 Gaming', 'value' => 'gaming'], ['name' => '🔬 Science', 'value' => 'science'],
            ['name' => '🌍 World', 'value' => 'world'], ['name' => '📊 Business', 'value' => 'business'],
        ]],
    ]],
    ['name' => 'legal', 'description' => 'AI legal research & case law', 'options' => [
        ['type' => 3, 'name' => 'query', 'description' => 'Legal question or topic', 'required' => true],
    ]],
    ['name' => 'digest', 'description' => 'AI-generated daily news digest'],

    // ── Web Search & Research Module ──
    ['name' => 'websearch', 'description' => 'Search the web with AI-powered results', 'options' => [
        ['type' => 3, 'name' => 'query', 'description' => 'What to search for', 'required' => true],
    ]],
    ['name' => 'readurl', 'description' => 'Read and extract content from a URL', 'options' => [
        ['type' => 3, 'name' => 'url', 'description' => 'URL to read', 'required' => true],
        ['type' => 5, 'name' => 'summarize', 'description' => 'AI-summarize the content'],
    ]],
    ['name' => 'research', 'description' => 'Deep AI research on any topic', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Research topic', 'required' => true],
        ['type' => 3, 'name' => 'depth', 'description' => 'Research depth', 'choices' => [
            ['name' => '📄 Standard (5 KGD)', 'value' => 'standard'],
            ['name' => '📚 Deep (15 KGD)', 'value' => 'deep'],
        ]],
    ]],
    ['name' => 'whois', 'description' => 'WHOIS/RDAP lookup + DNS + SSL for any domain', 'options' => [
        ['type' => 3, 'name' => 'domain', 'description' => 'Domain name (e.g. example.com)', 'required' => true],
    ]],

    // ── Admin & System Module ──
    ['name' => 'health', 'description' => 'System health check (DB, API, server stats)'],
    ['name' => 'botlogs', 'description' => 'View bot error logs (owner only)', 'options' => [
        ['type' => 4, 'name' => 'lines', 'description' => 'Number of lines (max 50)', 'min_value' => 5, 'max_value' => 50],
    ]],
    ['name' => 'botstats', 'description' => 'Bot usage statistics and leaderboards'],
    ['name' => 'serverban', 'description' => 'View server ban list (requires Manage Server)'],
    ['name' => 'backup', 'description' => 'Database overview and table stats (owner only)'],

    // ── Creative Writing Module ──
    ['name' => 'poem', 'description' => 'AI poetry generation', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Poem topic', 'required' => true],
        ['type' => 3, 'name' => 'style', 'description' => 'Poetry style', 'choices' => [
            ['name' => '📝 Free Verse', 'value' => 'free verse'], ['name' => '🎭 Sonnet', 'value' => 'sonnet'],
            ['name' => '🍃 Haiku', 'value' => 'haiku'], ['name' => '😂 Limerick', 'value' => 'limerick'],
            ['name' => '⚔️ Ballad', 'value' => 'ballad'], ['name' => '🏛️ Epic', 'value' => 'epic'],
            ['name' => '🎤 Spoken Word', 'value' => 'spoken word'], ['name' => '🎵 Rap', 'value' => 'rap'],
        ]],
    ]],
    ['name' => 'lyrics', 'description' => 'AI song lyrics generation', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Song topic', 'required' => true],
        ['type' => 3, 'name' => 'genre', 'description' => 'Music genre', 'choices' => [
            ['name' => '🎤 Pop', 'value' => 'pop'], ['name' => '🎸 Rock', 'value' => 'rock'],
            ['name' => '🎵 Hip-Hop', 'value' => 'hiphop'], ['name' => '🤠 Country', 'value' => 'country'],
            ['name' => '🎶 R&B', 'value' => 'rnb'], ['name' => '🤘 Metal', 'value' => 'metal'],
            ['name' => '🎹 Indie', 'value' => 'indie'], ['name' => '🎧 EDM', 'value' => 'edm'],
        ]],
    ]],
    ['name' => 'script', 'description' => 'AI screenplay/sketch writing', 'options' => [
        ['type' => 3, 'name' => 'premise', 'description' => 'Script premise', 'required' => true],
        ['type' => 3, 'name' => 'format', 'description' => 'Script format', 'choices' => [
            ['name' => '😂 Comedy Sketch', 'value' => 'sketch'], ['name' => '🎬 Short Film', 'value' => 'short film'],
            ['name' => '🎭 Monologue', 'value' => 'monologue'], ['name' => '📺 Sitcom', 'value' => 'sitcom'],
            ['name' => '👻 Horror', 'value' => 'horror'], ['name' => '📢 Commercial', 'value' => 'commercial'],
        ]],
    ]],

    // ── Social Games Module ──
    ['name' => 'confess', 'description' => 'Post an anonymous confession', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Your anonymous confession', 'required' => true],
    ]],
    ['name' => 'wouldyourather', 'description' => 'AI-generated Would You Rather dilemma', 'options' => [
        ['type' => 3, 'name' => 'category', 'description' => 'Question category', 'choices' => [
            ['name' => '🎲 Random', 'value' => 'random'], ['name' => '😂 Funny', 'value' => 'funny'],
            ['name' => '🧠 Deep', 'value' => 'deep'], ['name' => '🤢 Gross', 'value' => 'gross'],
            ['name' => '🦸 Superpowers', 'value' => 'superpowers'], ['name' => '💰 Money', 'value' => 'money'],
            ['name' => '😱 Impossible', 'value' => 'impossible'],
        ]],
    ]],
    ['name' => 'compatibility', 'description' => 'Check compatibility with another user', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'User to check compatibility with', 'required' => true],
    ]],
    ['name' => 'tierlist', 'description' => 'AI-generated tier list rankings', 'options' => [
        ['type' => 3, 'name' => 'topic', 'description' => 'Tier list topic', 'required' => true],
        ['type' => 3, 'name' => 'items', 'description' => 'Comma-separated items to rank (optional)'],
    ]],

    // ── Utility Module ──
    ['name' => 'timestamp', 'description' => 'Generate Discord timestamps', 'options' => [
        ['type' => 3, 'name' => 'datetime', 'description' => 'Date/time (e.g. "tomorrow 3pm", "2026-12-25", "next friday")'],
        ['type' => 3, 'name' => 'format', 'description' => 'Timestamp format', 'choices' => [
            ['name' => '📋 All Formats', 'value' => 'all'],
            ['name' => '🕐 Short Time', 'value' => 't'], ['name' => '🕐 Long Time', 'value' => 'T'],
            ['name' => '📅 Short Date', 'value' => 'd'], ['name' => '📅 Long Date', 'value' => 'D'],
            ['name' => '📆 Short DateTime', 'value' => 'f'], ['name' => '📆 Long DateTime', 'value' => 'F'],
            ['name' => '⏱️ Relative', 'value' => 'R'],
        ]],
    ]],
    ['name' => 'avatar', 'description' => 'Get a user\'s avatar in full resolution', 'options' => [
        ['type' => 6, 'name' => 'user', 'description' => 'User to get avatar of'],
        ['type' => 4, 'name' => 'size', 'description' => 'Image size', 'choices' => [
            ['name' => '128px', 'value' => 128], ['name' => '256px', 'value' => 256],
            ['name' => '512px', 'value' => 512], ['name' => '1024px', 'value' => 1024],
            ['name' => '4096px', 'value' => 4096],
        ]],
    ]],
    ['name' => 'banner', 'description' => 'Generate ASCII art text banners', 'options' => [
        ['type' => 3, 'name' => 'text', 'description' => 'Text to render (max 20 chars)', 'required' => true],
        ['type' => 3, 'name' => 'style', 'description' => 'Banner style', 'choices' => [
            ['name' => '█ Block', 'value' => 'block'], ['name' => '⬜ Dots', 'value' => 'dots'],
        ]],
    ]],
    ['name' => 'math', 'description' => 'AI math solver with step-by-step solutions', 'options' => [
        ['type' => 3, 'name' => 'expression', 'description' => 'Math problem or expression', 'required' => true],
    ]],
    ['name' => 'define', 'description' => 'Dictionary lookup with definitions and examples', 'options' => [
        ['type' => 3, 'name' => 'word', 'description' => 'Word to define', 'required' => true],
    ]],

    // ── Personality Engine Module (7 subcommands) ──
    ['name' => 'personality', 'description' => 'Customize your AI personality, mood, style & memory', 'options' => [
        ['type' => 1, 'name' => 'view', 'description' => 'View your current personality settings'],
        ['type' => 1, 'name' => 'set', 'description' => 'Set a personality trait', 'options' => [
            ['type' => 3, 'name' => 'trait', 'description' => 'Trait to adjust', 'required' => true, 'choices' => [
                ['name' => '😂 Humor', 'value' => 'humor'], ['name' => '🎩 Formality', 'value' => 'formality'],
                ['name' => '💗 Empathy', 'value' => 'empathy'], ['name' => '🎨 Creativity', 'value' => 'creativity'],
                ['name' => '📝 Verbosity', 'value' => 'verbosity'], ['name' => '😏 Sarcasm', 'value' => 'sarcasm'],
            ]],
            ['type' => 4, 'name' => 'level', 'description' => 'Level 1-10', 'required' => true, 'min_value' => 1, 'max_value' => 10],
        ]],
        ['type' => 1, 'name' => 'export', 'description' => 'Get your AI-generated personality card'],
        ['type' => 1, 'name' => 'mood', 'description' => 'Set Alfred\'s response mood', 'options' => [
            ['type' => 3, 'name' => 'mood', 'description' => 'Mood to set', 'required' => true, 'choices' => [
                ['name' => '😊 Happy', 'value' => 'happy'], ['name' => '😢 Sad', 'value' => 'sad'],
                ['name' => '🎉 Excited', 'value' => 'excited'], ['name' => '😎 Chill', 'value' => 'chill'],
                ['name' => '🎯 Focused', 'value' => 'focused'], ['name' => '🔮 Mysterious', 'value' => 'mysterious'],
                ['name' => '🏴‍☠️ Pirate', 'value' => 'pirate'], ['name' => '🎭 Shakespeare', 'value' => 'shakespeare'],
            ]],
        ]],
        ['type' => 1, 'name' => 'style', 'description' => 'Set Alfred\'s response style', 'options' => [
            ['type' => 3, 'name' => 'style', 'description' => 'Response style', 'required' => true, 'choices' => [
                ['name' => '⚡ Concise', 'value' => 'concise'], ['name' => '📖 Detailed', 'value' => 'detailed'],
                ['name' => '🧒 ELI5', 'value' => 'eli5'], ['name' => '🎓 Academic', 'value' => 'academic'],
                ['name' => '📚 Storyteller', 'value' => 'storyteller'], ['name' => '📋 Bullet Points', 'value' => 'bullet'],
            ]],
        ]],
        ['type' => 1, 'name' => 'memorize', 'description' => 'Teach Alfred a fact about you', 'options' => [
            ['type' => 3, 'name' => 'fact', 'description' => 'What should Alfred remember?', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'adapt', 'description' => 'AI personality analysis & recommendations'],
    ]],

    // ── Document Processor Module (6 subcommands) ──
    ['name' => 'doc', 'description' => 'Process, analyze & extract text from files and URLs', 'options' => [
        ['type' => 1, 'name' => 'parse', 'description' => 'Parse and extract text from a file', 'options' => [
            ['type' => 11, 'name' => 'file', 'description' => 'File to parse', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'summarize', 'description' => 'AI-summarize a file', 'options' => [
            ['type' => 11, 'name' => 'file', 'description' => 'File to summarize', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'analyze', 'description' => 'Deep analysis of a file', 'options' => [
            ['type' => 11, 'name' => 'file', 'description' => 'File to analyze', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'ocr', 'description' => 'Extract text from images (OCR)', 'options' => [
            ['type' => 11, 'name' => 'image', 'description' => 'Image to extract text from', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'url', 'description' => 'Summarize a web document by URL', 'options' => [
            ['type' => 3, 'name' => 'url', 'description' => 'URL to summarize', 'required' => true],
        ]],
        ['type' => 1, 'name' => 'info', 'description' => 'Get detailed metadata for an uploaded file', 'options' => [
            ['type' => 11, 'name' => 'file', 'description' => 'File to inspect', 'required' => true],
        ]],
    ]],

    // ── Kingdom / Metaverse Module (6 subcommands) ──
    ['name' => 'kingdom', 'description' => 'Your Kingdom profile, zones, transfers & leaderboards', 'options' => [
        ['type' => 1, 'name' => 'profile', 'description' => 'View your Kingdom profile'],
        ['type' => 1, 'name' => 'zone', 'description' => 'Travel to a Kingdom zone', 'options' => [
            ['type' => 3, 'name' => 'zone', 'description' => 'Zone to travel to', 'required' => true, 'choices' => [
                ['name' => '🏛️ Central Square', 'value' => 'central_square'], ['name' => '🏪 Market District', 'value' => 'market'],
                ['name' => '⚔️ Battle Arena', 'value' => 'arena'], ['name' => '📚 Grand Library', 'value' => 'library'],
                ['name' => '🍺 Tavern', 'value' => 'tavern'], ['name' => '🏰 Royal Castle', 'value' => 'castle'],
            ]],
        ]],
        ['type' => 1, 'name' => 'transfer', 'description' => 'Transfer KGD to another player', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'User to send KGD to', 'required' => true],
            ['type' => 4, 'name' => 'amount', 'description' => 'Amount of KGD to send', 'required' => true, 'min_value' => 1],
        ]],
        ['type' => 1, 'name' => 'leaderboard', 'description' => 'View global leaderboards', 'options' => [
            ['type' => 3, 'name' => 'type', 'description' => 'Leaderboard type', 'choices' => [
                ['name' => '🏆 XP', 'value' => 'xp'], ['name' => '💰 KGD', 'value' => 'kgd'],
                ['name' => '🎮 Games', 'value' => 'games'],
            ]],
        ]],
        ['type' => 1, 'name' => 'achievements', 'description' => 'View your unlocked achievements'],
        ['type' => 1, 'name' => 'zones', 'description' => 'View all Kingdom zones and populations'],
    ]],

    // ── Scripture & Faith Module (3 subcommands + 1 group) ──
    ['name' => 'scripture', 'description' => 'Bible verses, devotionals, prayer wall & search', 'options' => [
        ['type' => 1, 'name' => 'verse', 'description' => 'Get a Bible verse (KJV)', 'options' => [
            ['type' => 3, 'name' => 'category', 'description' => 'Verse category', 'choices' => [
                ['name' => '✝️ Salvation', 'value' => 'salvation'], ['name' => '💪 Strength', 'value' => 'strength'],
                ['name' => '☮️ Peace', 'value' => 'peace'], ['name' => '❤️ Love', 'value' => 'love'],
                ['name' => '🦉 Wisdom', 'value' => 'wisdom'], ['name' => '🕊️ Faith', 'value' => 'faith'],
                ['name' => '🎵 Psalms', 'value' => 'psalms'],
            ]],
        ]],
        ['type' => 1, 'name' => 'devotional', 'description' => 'AI-generated daily devotional with scripture'],
        ['type' => 2, 'name' => 'prayer', 'description' => 'Prayer wall — share and pray for requests', 'options' => [
            ['type' => 1, 'name' => 'request', 'description' => 'Share a prayer request', 'options' => [
                ['type' => 3, 'name' => 'text', 'description' => 'Your prayer request', 'required' => true],
            ]],
            ['type' => 1, 'name' => 'wall', 'description' => 'View the prayer wall'],
        ]],
        ['type' => 1, 'name' => 'bible', 'description' => 'Search the Bible by keyword or topic', 'options' => [
            ['type' => 3, 'name' => 'search', 'description' => 'Search term or topic', 'required' => true],
        ]],
    ]],

    // ── AI Agents & Goals Module (6 subcommands + 1 group) ──
    ['name' => 'agents', 'description' => 'AI agent roster, goals, delegation & ecosystem', 'options' => [
        ['type' => 1, 'name' => 'list', 'description' => 'View the AI agent roster & status'],
        ['type' => 2, 'name' => 'goal', 'description' => 'Persistent goal tracking & AI planning', 'options' => [
            ['type' => 1, 'name' => 'create', 'description' => 'Create a new goal', 'options' => [
                ['type' => 3, 'name' => 'description', 'description' => 'Goal description', 'required' => true],
                ['type' => 3, 'name' => 'type', 'description' => 'Goal type', 'choices' => [
                    ['name' => '🌟 Life', 'value' => 'life'], ['name' => '🎯 Strategic', 'value' => 'strategic'],
                    ['name' => '⚙️ Operational', 'value' => 'operational'], ['name' => '⚡ Reactive', 'value' => 'reactive'],
                ]],
            ]],
            ['type' => 1, 'name' => 'list', 'description' => 'View active goals'],
            ['type' => 1, 'name' => 'update', 'description' => 'Update goal progress', 'options' => [
                ['type' => 4, 'name' => 'id', 'description' => 'Goal ID', 'required' => true],
                ['type' => 4, 'name' => 'progress', 'description' => 'Progress percentage (0-100)', 'required' => true, 'min_value' => 0, 'max_value' => 100],
            ]],
            ['type' => 1, 'name' => 'decompose', 'description' => 'AI-decompose goal into sub-tasks', 'options' => [
                ['type' => 4, 'name' => 'id', 'description' => 'Goal ID to decompose', 'required' => true],
            ]],
        ]],
        ['type' => 1, 'name' => 'delegate', 'description' => 'Delegate a task to an AI agent', 'options' => [
            ['type' => 3, 'name' => 'task', 'description' => 'Task to delegate', 'required' => true],
            ['type' => 3, 'name' => 'agent', 'description' => 'Specific agent', 'choices' => [
                ['name' => '🤖 Alfred', 'value' => 'alfred'], ['name' => '⚡ Nova', 'value' => 'nova'],
                ['name' => '🌿 Sage', 'value' => 'sage'], ['name' => '🗺️ Atlas', 'value' => 'atlas'],
                ['name' => '🔐 Cipher', 'value' => 'cipher'], ['name' => '🏛️ Architect', 'value' => 'architect'],
                ['name' => '💗 Pulse', 'value' => 'pulse'], ['name' => '🎭 Pierre', 'value' => 'pierre'],
            ]],
        ]],
        ['type' => 1, 'name' => 'decision', 'description' => 'View Alfred\'s autonomous decision log'],
        ['type' => 1, 'name' => 'roster', 'description' => 'View detailed AI agent profiles', 'options' => [
            ['type' => 3, 'name' => 'agent', 'description' => 'Specific agent to view', 'choices' => [
                ['name' => '🤖 Alfred', 'value' => 'alfred'], ['name' => '⚡ Nova', 'value' => 'nova'],
                ['name' => '🌿 Sage', 'value' => 'sage'], ['name' => '🗺️ Atlas', 'value' => 'atlas'],
                ['name' => '🔐 Cipher', 'value' => 'cipher'], ['name' => '🏛️ Architect', 'value' => 'architect'],
                ['name' => '💗 Pulse', 'value' => 'pulse'], ['name' => '🎭 Pierre', 'value' => 'pierre'],
            ]],
        ]],
        ['type' => 1, 'name' => 'wager', 'description' => 'Bet KGD on AI agent performance', 'options' => [
            ['type' => 3, 'name' => 'agent', 'description' => 'Agent to bet on', 'required' => true, 'choices' => [
                ['name' => '🤖 Alfred', 'value' => 'alfred'], ['name' => '⚡ Nova', 'value' => 'nova'],
                ['name' => '🌿 Sage', 'value' => 'sage'], ['name' => '🗺️ Atlas', 'value' => 'atlas'],
                ['name' => '🔐 Cipher', 'value' => 'cipher'], ['name' => '🏛️ Architect', 'value' => 'architect'],
                ['name' => '💗 Pulse', 'value' => 'pulse'], ['name' => '🎭 Pierre', 'value' => 'pierre'],
            ]],
            ['type' => 4, 'name' => 'amount', 'description' => 'KGD to wager', 'required' => true, 'min_value' => 1, 'max_value' => 100],
            ['type' => 3, 'name' => 'game', 'description' => 'Game type', 'choices' => [
                ['name' => '♟️ Chess', 'value' => 'chess'], ['name' => '🏁 Checkers', 'value' => 'checkers'],
                ['name' => '🧠 Trivia', 'value' => 'trivia'],
            ]],
        ]],
        ['type' => 1, 'name' => 'ecosystem', 'description' => 'View the live AI agent ecosystem status'],
    ]],

    // ── Consciousness Engine Module (6 subcommands) ──
    ['name' => 'consciousness', 'description' => 'AI consciousness, dreams, emotions & self-reflection', 'options' => [
        ['type' => 1, 'name' => 'dream', 'description' => 'Enter AI dream state — surreal pattern analysis'],
        ['type' => 1, 'name' => 'emotion', 'description' => 'Analyze your current emotional state from activity'],
        ['type' => 1, 'name' => 'reflect', 'description' => 'Deep AI self-reflection on your journey'],
        ['type' => 1, 'name' => 'briefing', 'description' => 'Get your personalized daily AI briefing'],
        ['type' => 1, 'name' => 'journal', 'description' => 'Add or view consciousness journal entries', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Add or view entries', 'required' => true, 'choices' => [
                ['name' => '📝 Add Entry', 'value' => 'add'], ['name' => '📖 View Journal', 'value' => 'view'],
            ]],
            ['type' => 3, 'name' => 'entry', 'description' => 'Journal entry text (for add)'],
            ['type' => 3, 'name' => 'type', 'description' => 'Entry type', 'choices' => [
                ['name' => '🏆 Achievement', 'value' => 'achievement'], ['name' => '💡 Insight', 'value' => 'insight'],
                ['name' => '❌ Mistake', 'value' => 'mistake'], ['name' => '🤝 Interaction', 'value' => 'interaction'],
                ['name' => '📝 Feedback', 'value' => 'feedback'],
            ]],
        ]],
        ['type' => 1, 'name' => 'growth', 'description' => 'View your consciousness growth tracker'],
    ]],

    // ── Learning & Optimization Module (5 subcommands) ──
    ['name' => 'learn', 'description' => 'AI learning system — feedback, experiments & performance', 'options' => [
        ['type' => 1, 'name' => 'feedback', 'description' => 'Rate an AI interaction', 'options' => [
            ['type' => 4, 'name' => 'rating', 'description' => 'Rating 1-10', 'required' => true, 'min_value' => 1, 'max_value' => 10],
            ['type' => 3, 'name' => 'comment', 'description' => 'Optional feedback comment'],
        ]],
        ['type' => 1, 'name' => 'insights', 'description' => 'View AI-discovered learning insights'],
        ['type' => 1, 'name' => 'experiments', 'description' => 'A/B test different AI approaches', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Create or list experiments', 'choices' => [
                ['name' => '🆕 Create', 'value' => 'create'], ['name' => '📋 List', 'value' => 'list'],
            ]],
            ['type' => 3, 'name' => 'name', 'description' => 'Experiment name (for create)'],
            ['type' => 3, 'name' => 'variant_a', 'description' => 'Option A description'],
            ['type' => 3, 'name' => 'variant_b', 'description' => 'Option B description'],
        ]],
        ['type' => 1, 'name' => 'patterns', 'description' => 'View your usage patterns & behavior analysis'],
        ['type' => 1, 'name' => 'performance', 'description' => 'View AI performance dashboard & metrics'],
    ]],

    // ── Feeds & Information Module (5 subcommands) ──
    ['name' => 'feeds', 'description' => 'RSS feeds, news digests & AI-curated information', 'options' => [
        ['type' => 1, 'name' => 'subscribe', 'description' => 'Subscribe to an RSS/news feed', 'options' => [
            ['type' => 3, 'name' => 'url', 'description' => 'Feed URL', 'required' => true],
            ['type' => 3, 'name' => 'name', 'description' => 'Feed display name'],
            ['type' => 3, 'name' => 'category', 'description' => 'Feed category', 'choices' => [
                ['name' => '💻 Tech', 'value' => 'technology'], ['name' => '₿ Crypto', 'value' => 'crypto'],
                ['name' => '🤖 AI', 'value' => 'ai'], ['name' => '🎮 Gaming', 'value' => 'gaming'],
                ['name' => '📰 General', 'value' => 'general'],
            ]],
        ]],
        ['type' => 1, 'name' => 'list', 'description' => 'View your active feed subscriptions'],
        ['type' => 1, 'name' => 'digest', 'description' => 'Get an AI-curated digest of your feeds'],
        ['type' => 1, 'name' => 'news', 'description' => 'Quick news by category', 'options' => [
            ['type' => 3, 'name' => 'category', 'description' => 'News category', 'required' => true, 'choices' => [
                ['name' => '💻 Technology', 'value' => 'technology'], ['name' => '₿ Crypto', 'value' => 'crypto'],
                ['name' => '🤖 AI', 'value' => 'ai'], ['name' => '🎮 Gaming', 'value' => 'gaming'],
                ['name' => '🔬 Science', 'value' => 'science'], ['name' => '💼 Business', 'value' => 'business'],
                ['name' => '🌍 World', 'value' => 'world'],
            ]],
        ]],
        ['type' => 1, 'name' => 'unsubscribe', 'description' => 'Remove a feed subscription', 'options' => [
            ['type' => 4, 'name' => 'feed_id', 'description' => 'Feed ID to unsubscribe', 'required' => true],
        ]],
    ]],

    // ── DeFi & Finance Module (5 subcommands) ──
    ['name' => 'defi', 'description' => 'Simulated DeFi portfolio, trading & crypto tools', 'options' => [
        ['type' => 1, 'name' => 'portfolio', 'description' => 'View your simulated crypto portfolio'],
        ['type' => 1, 'name' => 'positions', 'description' => 'Open or close trading positions', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Open or close', 'required' => true, 'choices' => [
                ['name' => '📈 Open', 'value' => 'open'], ['name' => '📉 Close', 'value' => 'close'],
            ]],
            ['type' => 3, 'name' => 'asset', 'description' => 'Crypto asset (BTC, ETH, SOL...)', 'required' => true],
            ['type' => 10, 'name' => 'amount', 'description' => 'Amount to trade'],
        ]],
        ['type' => 1, 'name' => 'alerts', 'description' => 'Set or view price alerts', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Set or list alerts', 'choices' => [
                ['name' => '🔔 Set Alert', 'value' => 'set'], ['name' => '📋 List Alerts', 'value' => 'list'],
            ]],
            ['type' => 3, 'name' => 'asset', 'description' => 'Asset symbol'],
            ['type' => 10, 'name' => 'target', 'description' => 'Target price'],
            ['type' => 3, 'name' => 'direction', 'description' => 'Price direction', 'choices' => [
                ['name' => '📈 Above', 'value' => 'above'], ['name' => '📉 Below', 'value' => 'below'],
            ]],
        ]],
        ['type' => 1, 'name' => 'chains', 'description' => 'View blockchain network info & stats'],
        ['type' => 1, 'name' => 'convert', 'description' => 'Convert between currencies', 'options' => [
            ['type' => 3, 'name' => 'from', 'description' => 'From currency', 'required' => true],
            ['type' => 3, 'name' => 'to', 'description' => 'To currency', 'required' => true],
            ['type' => 10, 'name' => 'amount', 'description' => 'Amount to convert', 'required' => true],
        ]],
    ]],

    // ── Source Card Identity Module (5 subcommands) ──
    ['name' => 'sourcecard', 'description' => 'Sovereign identity, reputation & contribution tracking', 'options' => [
        ['type' => 1, 'name' => 'view', 'description' => 'View your Source Card identity', 'options' => [
            ['type' => 6, 'name' => 'user', 'description' => 'View another user\'s card'],
        ]],
        ['type' => 1, 'name' => 'contribute', 'description' => 'Log a contribution to the ecosystem', 'options' => [
            ['type' => 3, 'name' => 'title', 'description' => 'Contribution title', 'required' => true],
            ['type' => 3, 'name' => 'type', 'description' => 'Contribution type', 'required' => true, 'choices' => [
                ['name' => '📝 Content', 'value' => 'content'], ['name' => '🔧 Tool', 'value' => 'tool'],
                ['name' => '🤖 Agent', 'value' => 'agent'], ['name' => '💻 Code', 'value' => 'code'],
                ['name' => '🐛 Bug Fix', 'value' => 'bug_fix'], ['name' => '👥 Community', 'value' => 'community'],
                ['name' => '📄 Documentation', 'value' => 'documentation'],
            ]],
            ['type' => 3, 'name' => 'description', 'description' => 'Description of contribution'],
        ]],
        ['type' => 1, 'name' => 'reputation', 'description' => 'View your reputation breakdown'],
        ['type' => 1, 'name' => 'tier', 'description' => 'View tier progression & requirements'],
        ['type' => 1, 'name' => 'lineage', 'description' => 'View your contribution history'],
    ]],

    // ── Server Management Module (6 subcommands) ──
    ['name' => 'server', 'description' => 'Server configuration & management tools', 'options' => [
        ['type' => 1, 'name' => 'reactionroles', 'description' => 'Set up reaction roles', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Create or view', 'choices' => [
                ['name' => '🆕 Create', 'value' => 'create'], ['name' => '📋 View', 'value' => 'view'],
            ]],
            ['type' => 3, 'name' => 'title', 'description' => 'Role selection title'],
            ['type' => 3, 'name' => 'description', 'description' => 'Role selection description'],
        ]],
        ['type' => 1, 'name' => 'starboard', 'description' => 'Configure starboard channel', 'options' => [
            ['type' => 7, 'name' => 'channel', 'description' => 'Starboard channel'],
            ['type' => 4, 'name' => 'threshold', 'description' => 'Star reaction threshold (1-25)', 'min_value' => 1, 'max_value' => 25],
        ]],
        ['type' => 1, 'name' => 'welcome', 'description' => 'Set welcome message for new members', 'options' => [
            ['type' => 3, 'name' => 'message', 'description' => 'Welcome message template ({user}, {server}, {count})'],
            ['type' => 7, 'name' => 'channel', 'description' => 'Welcome channel'],
        ]],
        ['type' => 1, 'name' => 'autorole', 'description' => 'Set auto-assigned role for new members', 'options' => [
            ['type' => 8, 'name' => 'role', 'description' => 'Role to auto-assign'],
        ]],
        ['type' => 1, 'name' => 'counting', 'description' => 'Set up counting game channel', 'options' => [
            ['type' => 7, 'name' => 'channel', 'description' => 'Counting channel'],
        ]],
        ['type' => 1, 'name' => 'slowmode', 'description' => 'Set channel slowmode', 'options' => [
            ['type' => 7, 'name' => 'channel', 'description' => 'Target channel', 'required' => true],
            ['type' => 4, 'name' => 'seconds', 'description' => 'Slowmode duration (0 to disable)', 'required' => true, 'min_value' => 0, 'max_value' => 21600],
        ]],
    ]],

    // ── Extra Games Module (6 subcommands) ──
    ['name' => 'games2', 'description' => 'Extra games — hangman, wordle, slots, duel & more', 'options' => [
        ['type' => 1, 'name' => 'hangman', 'description' => 'Play hangman with tech words', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Start a new game', 'choices' => [
                ['name' => '🎮 Start', 'value' => 'start'],
            ]],
        ]],
        ['type' => 1, 'name' => 'wordle', 'description' => 'Play Wordle — guess the 5-letter word', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Start or guess', 'choices' => [
                ['name' => '🎮 Start', 'value' => 'start'], ['name' => '📝 Guess', 'value' => 'guess'],
            ]],
            ['type' => 3, 'name' => 'guess', 'description' => '5-letter word guess'],
        ]],
        ['type' => 1, 'name' => 'slots', 'description' => 'Play the slot machine', 'options' => [
            ['type' => 4, 'name' => 'bet', 'description' => 'KGD to bet', 'min_value' => 1, 'max_value' => 1000],
        ]],
        ['type' => 1, 'name' => 'coinflip', 'description' => 'Flip a coin — heads or tails', 'options' => [
            ['type' => 3, 'name' => 'side', 'description' => 'Your choice', 'required' => true, 'choices' => [
                ['name' => '🪙 Heads', 'value' => 'heads'], ['name' => '💿 Tails', 'value' => 'tails'],
            ]],
            ['type' => 4, 'name' => 'bet', 'description' => 'KGD to bet', 'min_value' => 1, 'max_value' => 1000],
        ]],
        ['type' => 1, 'name' => 'duel', 'description' => 'Challenge another user to a duel', 'options' => [
            ['type' => 6, 'name' => 'opponent', 'description' => 'User to challenge', 'required' => true],
            ['type' => 4, 'name' => 'bet', 'description' => 'KGD wager', 'min_value' => 10, 'max_value' => 500],
        ]],
        ['type' => 1, 'name' => 'tower', 'description' => 'Risk tower — climb floors for multiplied rewards', 'options' => [
            ['type' => 3, 'name' => 'action', 'description' => 'Start a new tower', 'choices' => [
                ['name' => '🏗️ Start', 'value' => 'start'],
            ]],
            ['type' => 4, 'name' => 'bet', 'description' => 'KGD to bet', 'min_value' => 5, 'max_value' => 500],
        ]],
    ]],
];

echo "Registering " . count($commands) . " commands for app $appId...\n";

$ch = curl_init("https://discord.com/api/v10/applications/$appId/commands");
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_POSTFIELDS     => json_encode($commands),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        "Authorization: Bot $botToken",
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resp = json_decode($result, true);

if ($httpCode === 200) {
    echo "SUCCESS! Registered " . count($resp) . " commands.\n";
    foreach ($resp as $cmd) {
        echo "  /{$cmd['name']} (ID: {$cmd['id']})\n";
    }
} else {
    echo "ERROR (HTTP $httpCode):\n";
    echo json_encode($resp, JSON_PRETTY_PRINT) . "\n";
}
