<?php
/**
 * GoCodeMe Online Editor - Main Application
 */
header('Location: /middleware/dashboard', true, 302);
exit;

require_once __DIR__ . '/bootstrap_session.php';
require_once __DIR__ . '/config.php';
if (!defined('EDITOR_SESSION_LOADED') || !EDITOR_SESSION_LOADED) {
    session_start();
}

// Simple auth check without including full auth.php
$isLoggedIn = false;
$userName = 'Guest';
$isPremium = false;
$authStatus = null;

// Check session directly
if (isset($_SESSION['uid']) && $_SESSION['uid'] > 0) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT c.id, c.firstname, c.lastname, c.email,
                   (SELECT COUNT(*) FROM services WHERE userid = c.id AND domainstatus = 'Active') as hosting_count
            FROM clients c 
            WHERE c.id = ? AND c.status = 'Active'
        ");
        $stmt->execute([(int)$_SESSION['uid']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $isLoggedIn = true;
            $userName = trim($user['firstname'] . ' ' . $user['lastname']);
            $isPremium = $user['hosting_count'] > 0;
            $authStatus = [
                'id' => $user['id'],
                'firstname' => $user['firstname'],
                'lastname' => $user['lastname'],
                'email' => $user['email'],
                'is_premium' => $isPremium,
                'ai_used' => 0,
                'ai_limit' => $isPremium ? AI_MONTHLY_LIMIT_PAID : AI_MONTHLY_LIMIT_FREE
            ];
            
            // Get AI usage
            $stmt = $pdo->prepare("SELECT ai_used_this_month FROM editor_user_settings WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $settings = $stmt->fetch();
            if ($settings) {
                $authStatus['ai_used'] = $settings['ai_used_this_month'] ?? 0;
            }
        }
    } catch (Exception $e) {
        // Auth check failed, continue as guest
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoCodeMe Online Editor | Build Websites with AI in Your Browser | GoSiteMe</title>
    <meta name="description" content="Free AI website builder in your browser. Create full websites in 60 seconds with no code. No download—use GoCodeMe online editor. Best AI website builder from GoSiteMe hosting.">
    <meta name="keywords" content="AI website builder online, build website in browser, no code editor, GoCodeMe online, free website builder, AI web design, create website free, no download website builder">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://gositeme.com/editor/">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://gositeme.com/editor/">
    <meta property="og:title" content="GoCodeMe Online Editor | AI Website Builder">
    <meta property="og:description" content="Build websites with AI in your browser. No download required. Create sites in 60 seconds.">
    <meta property="og:image" content="https://gositeme.com/assets/hero-banner.png">
    <meta property="og:site_name" content="GoSiteMe">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="GoCodeMe Online Editor | AI Website Builder">
    <meta property="twitter:description" content="Build websites with AI in your browser. No download required.">
    <meta property="twitter:image" content="https://gositeme.com/assets/hero-banner.png">
    <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebApplication","name":"GoCodeMe Online Editor","url":"https://gositeme.com/editor/","applicationCategory":"DeveloperApplication","description":"Build websites with AI in your browser. No download required.","browserRequirements":"Requires JavaScript","offers":{"@type":"Offer","price":"0","priceCurrency":"USD"},"author":{"@type":"Organization","name":"GoSiteMe","url":"https://gositeme.com"}}</script>
    
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    
    <!-- Monaco Editor (Self-Hosted) -->
    <link rel="stylesheet" data-name="vs/editor/editor.main" href="/editor/assets/monaco/vs/editor/editor.main.css">
    
    <style>
        :root {
            --bg-dark: #0d1117;
            --bg-darker: #010409;
            --bg-card: #161b22;
            --bg-input: #0d1117;
            --border: #30363d;
            --border-focus: #58a6ff;
            --text: #e6edf3;
            --text-muted: #8b949e;
            --primary: #58a6ff;
            --purple: #8b5cf6;
            --green: #3fb950;
            --cyan: #00d4ff;
            --orange: #f97316;
            --red: #f85149;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
        }
        
        /* Header */
        .header {
            height: 56px;
            background: var(--bg-darker);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .logo img { height: 32px; }
        
        .project-name {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        .project-name:hover {
            border-color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #4c94e6; }
        .btn-success { background: var(--green); color: #fff; }
        .btn-success:hover { background: #2ea043; }
        .btn-purple { background: var(--purple); color: #fff; }
        .btn-purple:hover { background: #7c3aed; }
        .btn-ghost {
            background: transparent;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            background: var(--bg-card);
            border-color: var(--text-muted);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* Main Layout */
        .main {
            display: flex;
            height: calc(100vh - 56px);
        }
        
        /* AI Panel */
        .ai-panel {
            width: 380px;
            background: var(--bg-darker);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }
        
        .panel-header {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-header h2 {
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .panel-header h2 i { color: var(--purple); }
        
        .ai-usage {
            font-size: 0.75rem;
            color: var(--text-muted);
            background: var(--bg-card);
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .ai-usage.low { color: var(--orange); }
        .ai-usage.empty { color: var(--red); }
        
        /* Templates */
        .templates {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
        }
        
        .templates-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        
        .template-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .template-chip {
            padding: 6px 12px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .template-chip:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        /* Chat Area */
        .ai-chat {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .chat-message {
            margin-bottom: 16px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chat-message.user { text-align: right; }
        
        .chat-message .bubble {
            display: inline-block;
            max-width: 90%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.5;
            text-align: left;
        }
        
        .chat-message.user .bubble {
            background: var(--primary);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        
        .chat-message.ai .bubble {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }
        
        .chat-message .time {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        /* AI Input */
        .ai-input-area {
            padding: 16px;
            border-top: 1px solid var(--border);
        }
        
        .ai-input-wrapper { position: relative; }
        
        .ai-input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 50px 14px 16px;
            color: var(--text);
            font-size: 0.9rem;
            resize: none;
            min-height: 100px;
            font-family: inherit;
        }
        
        .ai-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.15);
        }
        
        .ai-input::placeholder { color: var(--text-muted); }
        
        .ai-send-btn {
            position: absolute;
            right: 12px;
            bottom: 12px;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--primary);
            border: none;
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .ai-send-btn:hover { background: var(--purple); transform: scale(1.05); }
        .ai-send-btn:disabled { background: var(--border); cursor: not-allowed; transform: none; }
        
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Editor Area */
        .editor-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .editor-tabs {
            display: flex;
            background: var(--bg-darker);
            border-bottom: 1px solid var(--border);
            padding: 0 16px;
        }
        
        .editor-tab {
            padding: 12px 20px;
            font-size: 0.85rem;
            color: var(--text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .editor-tab:hover { color: var(--text); }
        .editor-tab.active {
            color: var(--text);
            border-bottom-color: var(--primary);
        }
        
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .dot.html { background: var(--orange); }
        .dot.css { background: var(--primary); }
        .dot.js { background: #f7df1e; }
        
        .editor-content {
            flex: 1;
            display: flex;
        }
        
        .code-editor {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .code-area {
            flex: 1;
            background: var(--bg-input);
            overflow: hidden;
            position: relative;
        }
        
        #monacoEditor {
            width: 100%;
            height: 100%;
        }
        
        /* Preview Panel */
        .preview-panel {
            width: 50%;
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        
        .preview-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            background: var(--bg-darker);
            border-bottom: 1px solid var(--border);
        }
        
        .preview-header span {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-actions { display: flex; gap: 8px; }
        
        .preview-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .preview-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .preview-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        
        .preview-frame {
            flex: 1;
            border: none;
            background: #fff;
        }
        
        /* Status Bar */
        .status-bar {
            height: 28px;
            background: var(--bg-darker);
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
        }
        
        .status-dot.loading {
            background: var(--orange);
            animation: pulse 1s infinite;
        }
        
        .status-dot.error { background: var(--red); }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            transform: scale(0.95);
            transition: all 0.3s;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1);
        }
        
        .modal h3 {
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-input, .modal-select {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            color: var(--text);
            font-size: 0.9rem;
            margin-bottom: 16px;
        }
        
        .modal-input:focus, .modal-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 6px;
        }
        
        /* Login Prompt */
        .login-prompt {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            text-align: center;
        }
        
        .login-prompt p {
            margin-bottom: 12px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        /* Toast notifications */
        .toast {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        
        .toast.success { border-color: var(--green); }
        .toast.success i { color: var(--green); }
        .toast.error { border-color: var(--red); }
        .toast.error i { color: var(--red); }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .ai-panel { width: 320px; }
            .preview-panel { width: 45%; }
        }
        
        @media (max-width: 768px) {
            .main { flex-direction: column; }
            .ai-panel { 
                width: 100%; 
                height: auto;
                min-height: 200px;
                max-height: 40vh;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .editor-panel {
                height: 35vh;
                min-height: 200px;
            }
            .preview-panel { 
                width: 100%; 
                height: 25vh;
                min-height: 150px;
            }
            .header-actions .btn span { display: none; }
            .header-actions .btn { padding: 8px 12px; }
            .logo span { display: none; }
            .project-name span { max-width: 100px; }
            
            .panel-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .file-tabs {
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 8px 12px;
            }
            
            .project-name {
                display: none;
            }
            
            .ai-panel {
                max-height: 35vh;
            }
            
            .ai-input textarea {
                font-size: 16px; /* Prevent iOS zoom */
            }
            
            .header-actions .btn {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .modal-content {
                margin: 10px;
                max-height: 90vh;
                overflow-y: auto;
            }
            
            .modal-header h2 {
                font-size: 1.25rem;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn, .tab, .file-tab {
                min-height: 44px;
                display: flex;
                align-items: center;
            }
            
            textarea, input {
                font-size: 16px; /* Prevent zoom */
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <a href="/" class="logo">
                <img src="/brand/logo_w.png" alt="GoSiteMe">
                <span>GoCodeMe</span>
            </a>
            <div class="project-name" onclick="showProjectsModal()">
                <i class="fas fa-folder"></i>
                <span id="currentProjectName">Untitled Project</span>
                <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-ghost" onclick="showSaveModal()">
                <i class="fas fa-save"></i> <span>Save</span>
            </button>
            <button class="btn btn-ghost" onclick="downloadCode()">
                <i class="fas fa-download"></i> <span>Download</span>
            </button>
            <?php if ($isLoggedIn && $isPremium): ?>
            <button class="btn btn-success" onclick="showPublishModal()">
                <i class="fas fa-rocket"></i> <span>Publish</span>
            </button>
            <?php else: ?>
            <a href="/cart?gid=6" class="btn btn-purple">
                <i class="fas fa-rocket"></i> <span>Get Hosting</span>
            </a>
            <?php endif; ?>
            
            <?php if ($isLoggedIn): ?>
            <div class="user-menu" onclick="toggleUserMenu()">
                <div class="user-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                <span><?= htmlspecialchars($userName) ?></span>
            </div>
            <?php else: ?>
            <a href="/login?redirect=<?= urlencode('https://gositeme.com/editor/') ?>" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <?php endif; ?>
        </div>
    </header>
    
    <!-- Main Layout -->
    <main class="main">
        <!-- AI Panel -->
        <aside class="ai-panel">
            <div class="panel-header">
                <h2><i class="fas fa-wand-magic-sparkles"></i> AI Assistant</h2>
                <?php if ($isLoggedIn): ?>
                <span class="ai-usage <?= ($authStatus['ai_limit'] - $authStatus['ai_used']) < 10 ? 'low' : '' ?>">
                    <?= $authStatus['ai_used'] ?>/<?= $authStatus['ai_limit'] ?> used
                </span>
                <?php endif; ?>
            </div>
            
            <div class="templates">
                <div class="templates-label">Quick Start Templates</div>
                <div class="template-chips">
                    <span class="template-chip" onclick="useTemplate('landing')">Landing Page</span>
                    <span class="template-chip" onclick="useTemplate('portfolio')">Portfolio</span>
                    <span class="template-chip" onclick="useTemplate('restaurant')">Restaurant</span>
                    <span class="template-chip" onclick="useTemplate('business')">Business</span>
                    <span class="template-chip" onclick="useTemplate('ecommerce')">E-commerce</span>
                </div>
            </div>
            
            <?php if (!$isLoggedIn): ?>
            <div class="login-prompt" style="margin: 16px;">
                <p><i class="fas fa-info-circle"></i> Login to save projects and use AI generation</p>
                <a href="/login?redirect=<?= urlencode('https://gositeme.com/editor/') ?>" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login to Continue
                </a>
            </div>
            <?php endif; ?>
            
            <div class="ai-chat" id="aiChat">
                <div class="chat-message ai">
                    <div class="bubble">
                        👋 Hi<?= $isLoggedIn ? ' ' . htmlspecialchars($authStatus['firstname']) : '' ?>! I'm your AI assistant. 
                        Describe the website you want to build, and I'll generate the code for you.
                        <br><br>
                        Try: <em>"Create a modern landing page for a coffee shop with a hero section, menu, and contact form"</em>
                    </div>
                </div>
            </div>
            
            <div class="ai-input-area">
                <div class="ai-input-wrapper">
                    <textarea 
                        class="ai-input" 
                        id="aiInput" 
                        placeholder="Describe what you want to build..."
                        onkeydown="handleInputKeydown(event)"
                        <?= !$isLoggedIn ? 'disabled' : '' ?>
                    ></textarea>
                    <button class="ai-send-btn" id="sendBtn" onclick="sendMessage()" <?= !$isLoggedIn ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Editor Area -->
        <section class="editor-area">
            <div class="editor-tabs">
                <div class="editor-tab active" onclick="switchTab('html')">
                    <span class="dot html"></span> index.html
                </div>
                <div class="editor-tab" onclick="switchTab('css')">
                    <span class="dot css"></span> styles.css
                </div>
                <div class="editor-tab" onclick="switchTab('js')">
                    <span class="dot js"></span> script.js
                </div>
            </div>
            
            <div class="editor-content">
                <div class="code-editor">
                    <div class="code-area">
                        <div id="monacoEditor"></div>
                    </div>
                </div>
                
                <div class="preview-panel">
                    <div class="preview-header">
                        <span><i class="fas fa-eye"></i> Live Preview</span>
                        <div class="preview-actions">
                            <button class="preview-btn active" onclick="setPreviewSize('desktop')" title="Desktop">
                                <i class="fas fa-desktop"></i>
                            </button>
                            <button class="preview-btn" onclick="setPreviewSize('tablet')" title="Tablet">
                                <i class="fas fa-tablet-alt"></i>
                            </button>
                            <button class="preview-btn" onclick="setPreviewSize('mobile')" title="Mobile">
                                <i class="fas fa-mobile-alt"></i>
                            </button>
                            <button class="preview-btn" onclick="refreshPreview()" title="Refresh">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <iframe id="previewFrame" class="preview-frame"></iframe>
                </div>
            </div>
        </section>
    </main>
    
    <!-- Status Bar -->
    <div class="status-bar">
        <div class="status-item">
            <span class="status-dot" id="statusDot"></span>
            <span id="statusText">Ready</span>
        </div>
        <div class="status-item">
            <span id="autoSaveStatus"></span>
            <span style="margin-left: 20px;">GoCodeMe v<?= EDITOR_VERSION ?></span>
        </div>
    </div>
    
    <!-- Save Modal -->
    <div class="modal-overlay" id="saveModal">
        <div class="modal">
            <h3><i class="fas fa-save"></i> Save Project</h3>
            <div class="form-group">
                <label>Project Name</label>
                <input type="text" class="modal-input" id="projectName" placeholder="My Awesome Website">
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <input type="text" class="modal-input" id="projectDescription" placeholder="A brief description...">
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('saveModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveProject()">Save Project</button>
            </div>
        </div>
    </div>
    
    <!-- Projects Modal -->
    <div class="modal-overlay" id="projectsModal">
        <div class="modal">
            <h3><i class="fas fa-folder-open"></i> My Projects</h3>
            <div id="projectsList">
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">Loading projects...</p>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('projectsModal')">Close</button>
                <button class="btn btn-primary" onclick="newProject()"><i class="fas fa-plus"></i> New Project</button>
            </div>
        </div>
    </div>
    
    <!-- Publish Modal -->
    <div class="modal-overlay" id="publishModal">
        <div class="modal" style="max-width: 520px;">
            <h3><i class="fas fa-rocket"></i> Publish to Your Hosting</h3>
            <div class="form-group">
                <label>Select Hosting Account</label>
                <select class="modal-select" id="hostingAccount" onchange="onPublishHostingAccountChange()">
                    <option value="">Loading...</option>
                </select>
                <small style="color: var(--text-muted); display: block; margin-top: 6px;">Choose your hosting to use DirectAdmin/FTP details from hosting—leave password empty to use stored credentials.</small>
            </div>
            <div class="form-group">
                <label>FTP Host</label>
                <input type="text" class="modal-input" id="ftpHost" placeholder="e.g. ftp.yourdomain.com or server IP">
            </div>
            <div class="form-group">
                <label>FTP Username</label>
                <input type="text" class="modal-input" id="ftpUser" placeholder="Your FTP/cPanel username">
            </div>
            <div class="form-group">
                <label>FTP Password</label>
                <input type="password" class="modal-input" id="ftpPass" placeholder="Leave empty to use stored DirectAdmin password" autocomplete="off">
            </div>
            <div class="form-group">
                <label>FTP path (public folder)</label>
                <input type="text" class="modal-input" id="ftpPath" placeholder="public_html" value="public_html">
                <small style="color: var(--text-muted);">Usually <code>public_html</code>. Site will be live at your domain.</small>
            </div>
            <div class="form-group" style="margin-bottom: 8px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" id="ftpSaveDetails" checked>
                    <span>Save FTP details for next time</span>
                </label>
            </div>
            <div class="form-group">
                <label>Subfolder (optional)</label>
                <input type="text" class="modal-input" id="publishPath" placeholder="e.g. my-site">
                <small style="color: var(--text-muted);">Leave empty for site root. Use a folder for yourdomain.com/folder</small>
            </div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal('publishModal')">Cancel</button>
                <button class="btn btn-success" onclick="publishProject()"><i class="fas fa-rocket"></i> Publish</button>
            </div>
        </div>
    </div>
    
    <!-- Monaco Editor Loader (Self-Hosted) -->
    <script src="/editor/assets/monaco/vs/loader.js"></script>
    <script>
        // Configuration
        const API_BASE = '/editor/api';
        const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
        
        // State
        let currentProject = null;
        let code = {
            html: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Welcome to GoCodeMe!</h1>
    <p>Describe what you want in the AI panel, and I'll build it for you.</p>
    <script src="script.js"><\/script>
</body>
</html>`,
            css: `* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: system-ui, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 40px;
}

h1 { font-size: 3rem; margin-bottom: 1rem; }
p { font-size: 1.2rem; opacity: 0.9; }`,
            js: `console.log('GoCodeMe Editor loaded!');`
        };
        
        let currentTab = 'html';
        let hasUnsavedChanges = false;
        let monacoEditor = null;
        const previewFrame = document.getElementById('previewFrame');
        
        // Language mapping
        const langMap = {
            html: 'html',
            css: 'css',
            js: 'javascript'
        };
        
        // Initialize Monaco Editor (Self-Hosted)
        require.config({ paths: { vs: '/editor/assets/monaco/vs' } });
        
        require(['vs/editor/editor.main'], function () {
            // Define custom dark theme
            monaco.editor.defineTheme('gocodeme-dark', {
                base: 'vs-dark',
                inherit: true,
                rules: [
                    { token: 'comment', foreground: '6A9955' },
                    { token: 'keyword', foreground: 'C586C0' },
                    { token: 'string', foreground: 'CE9178' },
                ],
                colors: {
                    'editor.background': '#0d1117',
                    'editor.foreground': '#e6edf3',
                    'editor.lineHighlightBackground': '#161b22',
                    'editorCursor.foreground': '#58a6ff',
                    'editor.selectionBackground': '#264f78',
                    'editorLineNumber.foreground': '#8b949e',
                    'editorLineNumber.activeForeground': '#e6edf3',
                }
            });
            
            // Create editor
            monacoEditor = monaco.editor.create(document.getElementById('monacoEditor'), {
                value: code.html,
                language: 'html',
                theme: 'gocodeme-dark',
                automaticLayout: true,
                fontSize: 14,
                fontFamily: "'JetBrains Mono', 'Fira Code', monospace",
                minimap: { enabled: true, scale: 1 },
                scrollBeyondLastLine: false,
                wordWrap: 'on',
                lineNumbers: 'on',
                renderWhitespace: 'selection',
                bracketPairColorization: { enabled: true },
                autoIndent: 'full',
                formatOnPaste: true,
                formatOnType: true,
                tabSize: 2,
                smoothScrolling: true,
                cursorBlinking: 'smooth',
                cursorSmoothCaretAnimation: 'on',
            });
            
            // Auto-update preview on change
            monacoEditor.onDidChangeModelContent(() => {
                hasUnsavedChanges = true;
                clearTimeout(updateTimeout);
                updateTimeout = setTimeout(updatePreview, 500);
            });
            
            // Initial preview
            updatePreview();
        });
        
        // Tab switching
        function switchTab(tab) {
            if (!monacoEditor) return;
            
            // Save current content
            code[currentTab] = monacoEditor.getValue();
            
            // Switch tab
            currentTab = tab;
            
            // Update editor content and language
            monacoEditor.setValue(code[tab]);
            monaco.editor.setModelLanguage(monacoEditor.getModel(), langMap[tab]);
            
            // Update tab UI
            document.querySelectorAll('.editor-tab').forEach((t, i) => {
                t.classList.toggle('active', ['html', 'css', 'js'][i] === tab);
            });
        }
        
        // Update preview
        let updateTimeout;
        function updatePreview() {
            if (monacoEditor) {
                code[currentTab] = monacoEditor.getValue();
            }
            
            const html = code.html
                .replace('<link rel="stylesheet" href="styles.css">', `<style>${code.css}</style>`)
                .replace(/<script src="script.js"><\/script>/, `<script>${code.js}<\/script>`);
            
            const blob = new Blob([html], { type: 'text/html' });
            previewFrame.src = URL.createObjectURL(blob);
        }
        
        function refreshPreview() {
            updatePreview();
            showToast('Preview refreshed', 'success');
        }
        
        // Preview size
        function setPreviewSize(size) {
            document.querySelectorAll('.preview-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.preview-btn').classList.add('active');
            
            switch(size) {
                case 'mobile':
                    previewFrame.style.maxWidth = '375px';
                    previewFrame.style.margin = '0 auto';
                    break;
                case 'tablet':
                    previewFrame.style.maxWidth = '768px';
                    previewFrame.style.margin = '0 auto';
                    break;
                default:
                    previewFrame.style.maxWidth = '100%';
                    previewFrame.style.margin = '0';
            }
        }
        
        // AI Chat
        function handleInputKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }
        
        async function sendMessage() {
            if (!isLoggedIn) {
                showToast('Please login to use AI generation', 'error');
                return;
            }
            
            const input = document.getElementById('aiInput');
            const message = input.value.trim();
            if (!message) return;
            
            addChatMessage(message, 'user');
            input.value = '';
            
            setStatus('Generating code...', 'loading');
            const sendBtn = document.getElementById('sendBtn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<div class="spinner"></div>';
            
            try {
                const response = await fetch(`${API_BASE}/ai.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        prompt: message,
                        project_id: currentProject?.id,
                        action: 'generate',
                        current_code: code
                    })
                });
                
                const data = await response.json();
                
                if (data.error) {
                    throw new Error(data.error);
                }
                
                addChatMessage(data.message, 'ai');
                
                if (data.code) {
                    if (data.code.html) code.html = data.code.html;
                    if (data.code.css) code.css = data.code.css;
                    if (data.code.js) code.js = data.code.js;
                    
                    if (monacoEditor) monacoEditor.setValue(code[currentTab]);
                    updatePreview();
                    hasUnsavedChanges = true;
                }
                
                // Update AI usage display
                if (data.ai_remaining !== undefined) {
                    const usageEl = document.querySelector('.ai-usage');
                    if (usageEl) {
                        const total = <?= $isLoggedIn ? $authStatus['ai_limit'] : 0 ?>;
                        const used = total - data.ai_remaining;
                        usageEl.textContent = `${used}/${total} used`;
                        usageEl.classList.toggle('low', data.ai_remaining < 10);
                        usageEl.classList.toggle('empty', data.ai_remaining === 0);
                    }
                }
                
                setStatus('Code generated!', 'success');
            } catch (error) {
                addChatMessage('Sorry, something went wrong: ' + error.message, 'ai');
                setStatus('Error', 'error');
            }
            
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
        
        function addChatMessage(text, type) {
            const chat = document.getElementById('aiChat');
            const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            chat.insertAdjacentHTML('beforeend', `
                <div class="chat-message ${type}">
                    <div class="bubble">${text}</div>
                    <div class="time">${time}</div>
                </div>
            `);
            chat.scrollTop = chat.scrollHeight;
        }
        
        // Templates
        function useTemplate(type) {
            const prompts = {
                landing: 'Create a modern SaaS landing page with a hero section featuring a gradient background, features grid, pricing cards, testimonials, and a footer with social links',
                portfolio: 'Create a creative portfolio website with a dark theme, project gallery with hover effects, about section, skills list, and contact form',
                restaurant: 'Create an elegant restaurant website with a hero image, menu section with categories, about us, gallery, and reservation form',
                business: 'Create a professional business consulting website with services section, team members, client logos, case studies, and contact information',
                ecommerce: 'Create a modern e-commerce product landing page with product image gallery, features, reviews, and add to cart button'
            };
            
            document.getElementById('aiInput').value = prompts[type];
            if (isLoggedIn) sendMessage();
        }
        
        // Status
        function setStatus(text, type = 'success') {
            document.getElementById('statusText').textContent = text;
            const dot = document.getElementById('statusDot');
            dot.className = 'status-dot';
            if (type === 'loading') dot.classList.add('loading');
            if (type === 'error') dot.classList.add('error');
        }
        
        // Toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Modal functions
        function showModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function showSaveModal() {
            if (!isLoggedIn) {
                showToast('Please login to save projects', 'error');
                return;
            }
            document.getElementById('projectName').value = currentProject?.name || '';
            document.getElementById('projectDescription').value = currentProject?.description || '';
            showModal('saveModal');
        }
        
        async function showProjectsModal() {
            if (!isLoggedIn) {
                showToast('Please login to view projects', 'error');
                return;
            }
            
            showModal('projectsModal');
            
            try {
                const response = await fetch(`${API_BASE}/projects.php?action=list`);
                const data = await response.json();
                
                const list = document.getElementById('projectsList');
                
                if (data.projects && data.projects.length > 0) {
                    list.innerHTML = data.projects.map(p => `
                        <div style="padding: 12px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="loadProject(${p.id})">
                            <div>
                                <strong>${p.name}</strong>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">${p.description || 'No description'}</div>
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                ${new Date(p.updated_at).toLocaleDateString()}
                            </div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<p style="color: var(--text-muted); text-align: center; padding: 20px;">No projects yet. Create your first one!</p>';
                }
            } catch (error) {
                document.getElementById('projectsList').innerHTML = '<p style="color: var(--red);">Failed to load projects</p>';
            }
        }
        
        let publishHostingAccounts = [];
        
        function onPublishHostingAccountChange() {
            const select = document.getElementById('hostingAccount');
            const id = select.value;
            if (id === '') return;
            const h = publishHostingAccounts.find(x => String(x.id) === String(id));
            if (!h) return;
            document.getElementById('ftpHost').value = h.server_hostname || h.ipaddress || h.domain || '';
            document.getElementById('ftpUser').value = h.username || '';
        }
        
        async function showPublishModal() {
            showModal('publishModal');
            document.getElementById('ftpPass').value = '';
            
            try {
                const response = await fetch(`${API_BASE}/publish.php?action=get_hosting`);
                const data = await response.json();
                
                const select = document.getElementById('hostingAccount');
                publishHostingAccounts = data.hosting_accounts || [];
                
                if (publishHostingAccounts.length > 0) {
                    select.innerHTML = '<option value="">-- Choose account --</option>' + publishHostingAccounts.map(h => 
                        `<option value="${h.id}">${h.domain} (${h.product_name || 'Hosting'})</option>`
                    ).join('');
                    if (data.saved_ftp && data.saved_ftp.host) {
                        document.getElementById('ftpHost').value = data.saved_ftp.host;
                        document.getElementById('ftpUser').value = data.saved_ftp.user || '';
                        document.getElementById('ftpPath').value = data.saved_ftp.path || 'public_html';
                    } else {
                        select.selectedIndex = 1;
                        onPublishHostingAccountChange();
                    }
                } else {
                    select.innerHTML = '<option value="">No hosting accounts found</option>';
                    if (data.saved_ftp && data.saved_ftp.host) {
                        document.getElementById('ftpHost').value = data.saved_ftp.host;
                        document.getElementById('ftpUser').value = data.saved_ftp.user || '';
                        document.getElementById('ftpPath').value = data.saved_ftp.path || 'public_html';
                    }
                }
            } catch (error) {
                document.getElementById('hostingAccount').innerHTML = '<option value="">Error loading accounts</option>';
            }
        }
        
        // Project functions
        async function saveProject() {
            const name = document.getElementById('projectName').value.trim() || 'Untitled Project';
            const description = document.getElementById('projectDescription').value.trim();
            
            if (monacoEditor) code[currentTab] = monacoEditor.getValue();
            
            setStatus('Saving...', 'loading');
            
            try {
                const action = currentProject ? 'update' : 'create';
                const url = currentProject 
                    ? `${API_BASE}/projects.php?action=update&id=${currentProject.id}`
                    : `${API_BASE}/projects.php?action=create`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        description,
                        html: code.html,
                        css: code.css,
                        js: code.js
                    })
                });
                
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                if (data.project) {
                    currentProject = { ...currentProject, ...data.project, name, description };
                    document.getElementById('currentProjectName').textContent = name;
                }
                
                hasUnsavedChanges = false;
                closeModal('saveModal');
                showToast('Project saved!', 'success');
                setStatus('Saved', 'success');
            } catch (error) {
                showToast('Failed to save: ' + error.message, 'error');
                setStatus('Save failed', 'error');
            }
        }
        
        async function loadProject(projectId) {
            setStatus('Loading...', 'loading');
            
            try {
                const response = await fetch(`${API_BASE}/projects.php?action=get&id=${projectId}`);
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                currentProject = data.project;
                code.html = data.project.html_content || code.html;
                code.css = data.project.css_content || code.css;
                code.js = data.project.js_content || code.js;
                
                updatePreview();
                
                if (monacoEditor) monacoEditor.setValue(code[currentTab]);
                document.getElementById('currentProjectName').textContent = data.project.name;
                
                closeModal('projectsModal');
                showToast('Project loaded!', 'success');
                setStatus('Ready', 'success');
            } catch (error) {
                showToast('Failed to load project', 'error');
                setStatus('Error', 'error');
            }
        }
        
        function newProject() {
            currentProject = null;
            code = {
                html: `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>New Project</h1>
    <p>Start building something amazing!</p>
    <script src="script.js"><\/script>
</body>
</html>`,
                css: `* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: system-ui, sans-serif; padding: 40px; }`,
                js: `console.log('Hello!');`
            };
            
            if (monacoEditor) monacoEditor.setValue(code[currentTab]);
            updatePreview();
            document.getElementById('currentProjectName').textContent = 'Untitled Project';
            closeModal('projectsModal');
        }
        
        async function publishProject() {
            if (!currentProject) {
                showToast('Please save the project first', 'error');
                return;
            }
            
            const ftpHost = document.getElementById('ftpHost').value.trim();
            const ftpUser = document.getElementById('ftpUser').value.trim();
            const ftpPass = document.getElementById('ftpPass').value;
            const ftpPath = document.getElementById('ftpPath').value.trim() || 'public_html';
            const saveFtp = document.getElementById('ftpSaveDetails').checked;
            const hostingId = document.getElementById('hostingAccount').value || 0;
            
            if (!ftpHost || !ftpUser) {
                showToast('Please select a hosting account or enter FTP host and username', 'error');
                return;
            }
            if (!ftpPass && !hostingId) {
                showToast('Enter your FTP password or select a hosting account to use stored credentials', 'error');
                return;
            }
            
            setStatus('Publishing...', 'loading');
            
            try {
                if (saveFtp) {
                    await fetch(`${API_BASE}/publish.php?action=save_ftp`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            ftp_host: ftpHost,
                            ftp_user: ftpUser,
                            ftp_pass: ftpPass,
                            ftp_path: ftpPath
                        })
                    });
                }
                
                const response = await fetch(`${API_BASE}/publish.php?action=publish`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        project_id: currentProject.id,
                        subdirectory: document.getElementById('publishPath').value.trim(),
                        hosting_id: hostingId ? parseInt(hostingId, 10) : 0,
                        ftp_host: ftpHost,
                        ftp_user: ftpUser,
                        ftp_pass: ftpPass,
                        ftp_path: ftpPath
                    })
                });
                
                const data = await response.json();
                
                if (data.error) throw new Error(data.error);
                
                closeModal('publishModal');
                showToast('Published successfully!', 'success');
                setStatus('Published', 'success');
                
                if (data.url) {
                    addChatMessage(`🎉 Your site is live at: <a href="${data.url}" target="_blank" style="color: var(--cyan);">${data.url}</a>`, 'ai');
                }
            } catch (error) {
                showToast('Publish failed: ' + error.message, 'error');
                setStatus('Publish failed', 'error');
            }
        }
        
        // Download
        function downloadCode() {
            if (monacoEditor) code[currentTab] = monacoEditor.getValue();
            
            // Download HTML
            const htmlBlob = new Blob([code.html], { type: 'text/html' });
            const htmlUrl = URL.createObjectURL(htmlBlob);
            const a = document.createElement('a');
            a.href = htmlUrl;
            a.download = 'index.html';
            a.click();
            
            // Download CSS
            setTimeout(() => {
                const cssBlob = new Blob([code.css], { type: 'text/css' });
                const cssUrl = URL.createObjectURL(cssBlob);
                a.href = cssUrl;
                a.download = 'styles.css';
                a.click();
            }, 100);
            
            // Download JS
            setTimeout(() => {
                const jsBlob = new Blob([code.js], { type: 'text/javascript' });
                const jsUrl = URL.createObjectURL(jsBlob);
                a.href = jsUrl;
                a.download = 'script.js';
                a.click();
            }, 200);
            
            showToast('Files downloaded!', 'success');
        }
        
        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeModal(overlay.id);
            });
        });
        
        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
    <div style="position:fixed;bottom:0;left:0;right:0;text-align:center;padding:4px;font-size:11px;opacity:0.5;z-index:1">
        <a href="/privacy-policy/" style="color:inherit">Privacy Policy</a> · <a href="/terms-of-service.php" style="color:inherit">Terms of Service</a>
    </div>
</body>
</html>
