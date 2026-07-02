<?php
// ── Auth FIRST — before ANY output ──
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
if ($clientId !== 33) {
    header('Location: /login.php');
    exit;
}

// Alfred IDE — standalone editor with chat panel
// Alfred IDE available at /alfred-ide/ for the full VS Code experience

$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// ── Allowed base directories (security: restrict file access) ──
$ALLOWED_ROOTS = [
    '/var/www',
    '/home/root/.vault',
    '/home/root/domains/root.com',
];

function isPathAllowed(string $path, array $roots): bool {
    $real = realpath($path);
    if ($real === false) {
        $parent = realpath(dirname($path));
        if ($parent === false) return false;
        foreach ($roots as $root) {
            if (str_starts_with($parent, $root)) return true;
        }
        return false;
    }
    foreach ($roots as $root) {
        if (str_starts_with($real, $root)) return true;
    }
    return false;
}

// ── API Handler ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!hash_equals($csrfToken, $_POST['csrf'] ?? '')) {
        echo json_encode(['error' => 'Invalid CSRF token']); exit;
    }
    $action = $_POST['action'];
    switch ($action) {
        case 'list_dir':
            $dir = $_POST['path'] ?? '/var/www';
            if (!isPathAllowed($dir, $ALLOWED_ROOTS)) { echo json_encode(['error' => 'Access denied']); break; }
            $real = realpath($dir);
            if (!$real || !is_dir($real)) { echo json_encode(['error' => 'Directory not found']); break; }
            $items = [];
            foreach (scandir($real) as $entry) {
                if ($entry === '.') continue;
                $fullPath = "$real/$entry";
                $isDir = is_dir($fullPath);
                $items[] = ['name' => $entry, 'path' => $fullPath, 'isDir' => $isDir,
                    'size' => $isDir ? null : filesize($fullPath),
                    'modified' => date('Y-m-d H:i:s', filemtime($fullPath))];
            }
            usort($items, function($a, $b) {
                if ($a['isDir'] !== $b['isDir']) return $b['isDir'] - $a['isDir'];
                return strcasecmp($a['name'], $b['name']);
            });
            echo json_encode(['path' => $real, 'items' => $items]);
            break;

        case 'read_file':
            $file = $_POST['path'] ?? '';
            if (!isPathAllowed($file, $ALLOWED_ROOTS)) { echo json_encode(['error' => 'Access denied']); break; }
            $real = realpath($file);
            if (!$real || !is_file($real)) { echo json_encode(['error' => 'File not found']); break; }
            if (filesize($real) > 5 * 1024 * 1024) { echo json_encode(['error' => 'File too large (>5MB)']); break; }
            $content = file_get_contents($real);
            $ext = pathinfo($real, PATHINFO_EXTENSION);
            $langMap = ['php'=>'php','js'=>'javascript','json'=>'json','html'=>'html','css'=>'css','md'=>'markdown',
                'py'=>'python','sh'=>'shell','sql'=>'sql','xml'=>'xml','yml'=>'yaml','yaml'=>'yaml',
                'conf'=>'ini','cnf'=>'ini','txt'=>'plaintext','env'=>'shell','htaccess'=>'apache','inc'=>'php'];
            $lang = $langMap[strtolower($ext)] ?? 'plaintext';
            echo json_encode(['path'=>$real,'content'=>$content,'language'=>$lang,'size'=>strlen($content),
                'modified'=>date('Y-m-d H:i:s', filemtime($real))]);
            break;

        case 'save_file':
            $file = $_POST['path'] ?? '';
            $content = $_POST['content'] ?? '';
            if (!isPathAllowed($file, $ALLOWED_ROOTS)) { echo json_encode(['error' => 'Access denied']); break; }
            if (str_ends_with($file, '.enc')) { echo json_encode(['error' => 'Cannot edit encrypted vault files']); break; }
            $backup = null;
            if (file_exists($file)) {
                $backupDir = '/home/root/.vault/ide-backups';
                if (!is_dir($backupDir)) mkdir($backupDir, 0700, true);
                $backup = "$backupDir/" . basename($file) . "." . date('Ymd-His') . '.bak';
                copy($file, $backup);
            }
            $bytes = file_put_contents($file, $content);
            if ($bytes === false) { echo json_encode(['error' => 'Write failed']); break; }
            $result = ['success' => true, 'bytes' => $bytes, 'path' => $file];
            if ($backup) $result['backup'] = $backup;
            if (str_ends_with($file, '.php')) {
                exec("php -l " . escapeshellarg($file) . " 2>&1", $out, $rc);
                $result['syntax'] = $rc === 0 ? 'ok' : implode("\n", $out);
            }
            echo json_encode($result);
            break;

        case 'search_files':
            $query = $_POST['query'] ?? '';
            $dir = $_POST['path'] ?? '/var/www';
            if (!$query || strlen($query) < 2) { echo json_encode(['error' => 'Query too short']); break; }
            if (!isPathAllowed($dir, $ALLOWED_ROOTS)) { echo json_encode(['error' => 'Access denied']); break; }
            $escapedQuery = escapeshellarg($query);
            $escapedDir = escapeshellarg($dir);
            $files = [];
            exec("grep -rl --include='*.php' --include='*.js' --include='*.json' --include='*.html' --include='*.css' --include='*.md' --include='*.py' --include='*.sh' -m 20 $escapedQuery $escapedDir 2>/dev/null | head -30", $files);
            $results = [];
            foreach ($files as $f) { $results[] = ['path' => $f, 'name' => basename($f)]; }
            echo json_encode(['results' => $results, 'query' => $query]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

// ── Now include header for the HTML page ──
$page_title = "Alfred IDE — Web Editor";
$page_description = "Browser-based code editor for the GoSiteMe ecosystem";
$page_robots = "noindex, nofollow";
include 'includes/site-header.inc.php';
?>
<style>
/* ── Alfred IDE — scoped styles ── */
.gcm-wrap{position:fixed;top:0;left:0;right:0;bottom:0;z-index:500;display:flex;flex-direction:column;background:#1e1e2e;font-family:'Inter',system-ui,sans-serif}
.gcm-topbar{height:40px;background:#11111b;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:center;padding:0 1rem;gap:1rem;flex-shrink:0;z-index:501}
.gcm-topbar .brand{display:flex;align-items:center;gap:.5rem;font-size:.85rem;font-weight:700;color:#cba6f7}
.gcm-topbar .brand span{background:linear-gradient(135deg,#89b4fa,#cba6f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.gcm-topbar .back-link{color:#a6adc8;text-decoration:none;font-size:.75rem;padding:.25rem .6rem;border-radius:4px;border:1px solid rgba(255,255,255,.08)}
.gcm-topbar .back-link:hover{background:rgba(255,255,255,.05);color:#cdd6f4}
.gcm-main{flex:1;display:flex;overflow:hidden}

/* Sidebar */
.gcm-sidebar{width:280px;background:#181825;border-right:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;flex-shrink:0}
.gcm-sidebar-hdr{padding:.6rem .75rem;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:.5rem}
.gcm-sidebar-hdr h3{font-size:.8rem;font-weight:600;color:#cdd6f4;flex:1;margin:0}
.gcm-sidebar-hdr button{background:none;border:none;color:#cdd6f4;cursor:pointer;padding:.2rem;opacity:.5;font-size:.9rem}
.gcm-sidebar-hdr button:hover{opacity:1}

.gcm-roots{padding:.4rem .6rem;border-bottom:1px solid rgba(255,255,255,.04);display:flex;flex-wrap:wrap;gap:.25rem}
.gcm-root-btn{padding:.25rem .5rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:4px;color:#a6adc8;font-size:.68rem;cursor:pointer;white-space:nowrap}
.gcm-root-btn:hover{background:rgba(255,255,255,.06);color:#cdd6f4}

.gcm-search{padding:.4rem .6rem;border-bottom:1px solid rgba(255,255,255,.04)}
.gcm-search input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:5px;padding:.35rem .5rem;color:#cdd6f4;font-size:.75rem;outline:none}
.gcm-search input::placeholder{color:rgba(255,255,255,.2)}
.gcm-search input:focus{border-color:rgba(137,180,250,.4)}

.gcm-tree{flex:1;overflow-y:auto;padding:.2rem 0}
.gcm-tree-item{display:flex;align-items:center;gap:.35rem;padding:.2rem .6rem;cursor:pointer;font-size:.78rem;color:#cdd6f4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-left:2px solid transparent;user-select:none}
.gcm-tree-item:hover{background:rgba(255,255,255,.04)}
.gcm-tree-item.active{background:rgba(137,180,250,.1);border-left-color:#89b4fa;color:#89b4fa}
.gcm-tree-item.dir{color:#f9e2af}
.gcm-tree-item .ico{width:14px;text-align:center;flex-shrink:0;font-size:.7rem}
.gcm-tree-item .sz{opacity:.3;font-size:.62rem;flex-shrink:0;margin-left:auto}

/* Editor area */
.gcm-editor{flex:1;display:flex;flex-direction:column;min-width:0}

/* Tab bar */
.gcm-tabs{display:flex;align-items:center;background:#11111b;border-bottom:1px solid rgba(255,255,255,.06);height:34px;overflow-x:auto;flex-shrink:0}
.gcm-tab{display:flex;align-items:center;gap:.3rem;padding:0 .8rem;height:100%;font-size:.75rem;cursor:pointer;border-right:1px solid rgba(255,255,255,.04);color:#a6adc8;white-space:nowrap}
.gcm-tab:hover{background:rgba(255,255,255,.03)}
.gcm-tab.active{background:#1e1e2e;color:#89b4fa;box-shadow:inset 0 -2px 0 #89b4fa}
.gcm-tab .x{opacity:.3;font-size:.6rem;padding:.15rem .2rem;border-radius:2px;margin-left:.3rem}
.gcm-tab .x:hover{opacity:1;background:rgba(255,255,255,.1)}
.gcm-tab .dot{color:#f9e2af;margin-right:.2rem}

/* Monaco container */
.gcm-editor-wrap{flex:1;position:relative}
#gcm-monaco{width:100%;height:100%;position:absolute;top:0;left:0}

/* Welcome screen */
.gcm-welcome{display:flex;align-items:center;justify-content:center;height:100%;text-align:center;flex-direction:column;gap:1.2rem;padding:2rem}
.gcm-welcome h2{font-size:1.6rem;font-weight:800;background:linear-gradient(135deg,#89b4fa,#cba6f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0}
.gcm-welcome p{font-size:.85rem;color:rgba(255,255,255,.35);max-width:380px;line-height:1.6;margin:0}
.gcm-welcome kbd{background:rgba(255,255,255,.08);padding:.15rem .4rem;border-radius:3px;font-size:.72rem;border:1px solid rgba(255,255,255,.12);font-family:monospace}
.gcm-welcome .loading-msg{color:#f9e2af;font-size:.8rem}

/* Status bar */
.gcm-status{display:flex;align-items:center;justify-content:space-between;padding:0 .75rem;height:24px;background:#181825;border-top:1px solid rgba(255,255,255,.06);font-size:.68rem;color:#a6adc8;flex-shrink:0}
.gcm-status .l,.gcm-status .r{display:flex;align-items:center;gap:.8rem}
.gcm-badge{padding:.1rem .35rem;border-radius:3px;font-size:.6rem;font-weight:700}
.gcm-badge.saved{background:rgba(34,197,94,.15);color:#22c55e}
.gcm-badge.unsaved{background:rgba(249,115,22,.15);color:#f97316}

/* Notification toast */
.gcm-toast{position:fixed;top:50px;right:20px;padding:.5rem 1rem;border-radius:7px;font-size:.78rem;font-weight:600;z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none}
.gcm-toast.show{opacity:1}
.gcm-toast.ok{background:rgba(34,197,94,.92);color:#fff}
.gcm-toast.err{background:rgba(239,68,68,.92);color:#fff}
.gcm-toast.warn{background:rgba(249,115,22,.92);color:#fff}

@media(max-width:768px){.gcm-sidebar{width:200px}}

/* ── Chat Panel ── */
.gcm-chat-toggle{color:#a6adc8;text-decoration:none;font-size:.75rem;padding:.25rem .6rem;border-radius:4px;border:1px solid rgba(255,255,255,.08);cursor:pointer;background:none;display:flex;align-items:center;gap:.35rem}
.gcm-chat-toggle:hover{background:rgba(255,255,255,.05);color:#cdd6f4}
.gcm-chat-toggle.active{background:rgba(137,180,250,.15);border-color:rgba(137,180,250,.3);color:#89b4fa}
.gcm-chat-toggle .dot{width:6px;height:6px;border-radius:50%;background:#a6e3a1;flex-shrink:0}

.gcm-chat{width:380px;background:#181825;border-left:1px solid rgba(255,255,255,.06);display:none;flex-direction:column;flex-shrink:0}
.gcm-chat.open{display:flex}
.gcm-chat-hdr{padding:.55rem .75rem;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:.5rem}
.gcm-chat-hdr h3{font-size:.8rem;font-weight:600;color:#cdd6f4;flex:1;margin:0}
.gcm-chat-hdr .agent-badge{font-size:.65rem;padding:.15rem .4rem;border-radius:10px;background:rgba(203,166,247,.12);color:#cba6f7;font-weight:600}
.gcm-chat-hdr button{background:none;border:none;color:#a6adc8;cursor:pointer;padding:.2rem .35rem;font-size:.78rem;border-radius:3px}
.gcm-chat-hdr button:hover{background:rgba(255,255,255,.06);color:#cdd6f4}

.gcm-chat-messages{flex:1;overflow-y:auto;padding:.6rem;display:flex;flex-direction:column;gap:.5rem}
.gcm-chat-messages::-webkit-scrollbar{width:4px}
.gcm-chat-messages::-webkit-scrollbar-thumb{background:rgba(255,255,255,.08);border-radius:4px}

.gcm-msg{max-width:92%;padding:.55rem .7rem;border-radius:10px;font-size:.78rem;line-height:1.55;word-wrap:break-word;animation:gcmFadeIn .25s ease}
.gcm-msg.user{align-self:flex-end;background:rgba(137,180,250,.15);color:#cdd6f4;border-bottom-right-radius:3px}
.gcm-msg.alfred{align-self:flex-start;background:rgba(255,255,255,.04);color:#bac2de;border-bottom-left-radius:3px;border:1px solid rgba(255,255,255,.04)}
.gcm-msg.alfred code{background:rgba(0,0,0,.3);padding:.1rem .3rem;border-radius:3px;font-size:.72rem;font-family:'JetBrains Mono',monospace}
.gcm-msg.alfred pre{background:rgba(0,0,0,.35);padding:.5rem;border-radius:5px;overflow-x:auto;margin:.3rem 0;font-size:.72rem}
.gcm-msg.alfred pre code{background:none;padding:0}
.gcm-msg.system{align-self:center;background:none;color:rgba(255,255,255,.25);font-size:.68rem;text-align:center;padding:.2rem}
@keyframes gcmFadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

.gcm-typing{display:flex;align-items:center;gap:.3rem;padding:.4rem .7rem;font-size:.72rem;color:#a6adc8}
.gcm-typing-dots{display:flex;gap:3px}
.gcm-typing-dots span{width:5px;height:5px;border-radius:50%;background:#cba6f7;animation:gcmBounce 1.2s infinite}
.gcm-typing-dots span:nth-child(2){animation-delay:.2s}
.gcm-typing-dots span:nth-child(3){animation-delay:.4s}
@keyframes gcmBounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

.gcm-chat-input{padding:.5rem;border-top:1px solid rgba(255,255,255,.06);display:flex;gap:.4rem;align-items:flex-end}
.gcm-chat-input textarea{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:7px;padding:.4rem .55rem;color:#cdd6f4;font-size:.78rem;font-family:'Inter',system-ui,sans-serif;resize:none;outline:none;min-height:34px;max-height:120px;line-height:1.4}
.gcm-chat-input textarea::placeholder{color:rgba(255,255,255,.2)}
.gcm-chat-input textarea:focus{border-color:rgba(137,180,250,.4)}
.gcm-chat-input button{width:32px;height:32px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0;transition:all .15s}
.gcm-chat-send{background:linear-gradient(135deg,#89b4fa,#cba6f7);color:#11111b}
.gcm-chat-send:hover{transform:scale(1.05);filter:brightness(1.1)}
.gcm-chat-send:disabled{opacity:.3;cursor:not-allowed;transform:none}

.gcm-chat-welcome{display:flex;flex-direction:column;align-items:center;justify-content:center;flex:1;text-align:center;padding:1.5rem;gap:.6rem}
.gcm-chat-welcome .icon{font-size:2.2rem}
.gcm-chat-welcome h4{font-size:.95rem;font-weight:700;background:linear-gradient(135deg,#89b4fa,#cba6f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0}
.gcm-chat-welcome p{font-size:.72rem;color:rgba(255,255,255,.3);line-height:1.5;margin:0;max-width:280px}
</style>

<div class="gcm-wrap">
    <div class="gcm-topbar">
        <div class="brand">&#9883; <span>Alfred IDE</span></div>
        <a href="/" class="back-link">&#8592; Site</a>
        <a href="/commander-defcon.php" class="back-link">&#9888; DEFCON</a>
        <a href="/commander-emergency.php" class="back-link">&#128296; Emergency</a>
        <div style="flex:1"></div>
        <button class="gcm-chat-toggle" id="gcm-chat-toggle" onclick="GCM.toggleChat()">
            <span class="dot"></span> &#129302; Alfred Chat
        </button>
    </div>
    <div class="gcm-main">
        <div class="gcm-sidebar">
            <div class="gcm-sidebar-hdr">
                <h3>&#128193; Explorer</h3>
                <button onclick="GCM.refresh()" title="Refresh">&#8635;</button>
            </div>
            <div class="gcm-roots">
                <button class="gcm-root-btn" onclick="GCM.nav('/var/www')">&#127968; public_html</button>
                <button class="gcm-root-btn" onclick="GCM.nav('/home/root/.vault')">&#128272; vault</button>
                <button class="gcm-root-btn" onclick="GCM.nav('/home/root/domains/root.com')">&#128194; domain</button>
            </div>
            <div class="gcm-search">
                <input type="text" id="gcm-search" placeholder="Search files... (2+ chars)" onkeyup="GCM.handleSearch(event)">
            </div>
            <div class="gcm-tree" id="gcm-tree"></div>
        </div>
        <div class="gcm-editor">
            <div class="gcm-tabs" id="gcm-tabs"></div>
            <div class="gcm-editor-wrap" id="gcm-editor-wrap">
                <div class="gcm-welcome" id="gcm-welcome">
                    <h2>Alfred IDE</h2>
                    <p>Your safe point to upgrade and fix the ecosystem.<br><br>
                       Open a file from the sidebar or press <kbd>Ctrl+S</kbd> to save.</p>
                    <div class="loading-msg" id="gcm-loading">&#9203; Loading Monaco Editor...</div>
                </div>
                <div id="gcm-monaco" style="display:none"></div>
            </div>
            <div class="gcm-status">
                <div class="l">
                    <span id="st-file">No file open</span>
                    <span id="st-save"></span>
                </div>
                <div class="r">
                    <span id="st-lang">-</span>
                    <span id="st-size">-</span>
                    <span id="st-pos">Ln 1, Col 1</span>
                </div>
            </div>
        </div>
        <div class="gcm-chat" id="gcm-chat">
            <div class="gcm-chat-hdr">
                <h3>&#129302; Alfred AI</h3>
                <span class="agent-badge">IDE Chat</span>
                <button onclick="GCM.clearChat()" title="Clear chat">&#128465;</button>
                <button onclick="GCM.toggleChat()" title="Close chat">&times;</button>
            </div>
            <div class="gcm-chat-messages" id="gcm-chat-msgs">
                <div class="gcm-chat-welcome">
                    <div class="icon">&#129302;</div>
                    <h4>Alfred IDE Assistant</h4>
                    <p>Ask about your code, get help with bugs, or request file changes. I have full context of your open files.</p>
                </div>
            </div>
            <div class="gcm-chat-input">
                <textarea id="gcm-chat-input" placeholder="Ask Alfred..." rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();GCM.sendChat()}"
                    oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px'"></textarea>
                <button class="gcm-chat-send" id="gcm-chat-send" onclick="GCM.sendChat()">&#9654;</button>
            </div>
        </div>
    </div>
</div>
<div class="gcm-toast" id="gcm-toast"></div>

<script src="/assets/vendor/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
<script>
const GCM = {
    CSRF: <?php echo json_encode($csrfToken); ?>,
    chatCSRF: '',
    editor: null,
    tabs: [],
    activeTab: null,
    curPath: '/var/www',
    chatOpen: false,
    chatMessages: [],
    chatConvId: 'ide-' + Date.now() + '-' + Math.floor(Math.random()*9999),
    chatSending: false,
    searchTimer: null,

    // ── Init Monaco ──
    init() {
        require.config({ paths: { vs: '/assets/vendor/monaco-editor/0.45.0/min/vs' }});
        require(['vs/editor/editor.main'], () => {
            monaco.editor.defineTheme('gcm-dark', {
                base: 'vs-dark', inherit: true, rules: [],
                colors: {
                    'editor.background': '#1e1e2e',
                    'editor.foreground': '#cdd6f4',
                    'editorCursor.foreground': '#f5e0dc',
                    'editor.lineHighlightBackground': '#2a2b3d',
                    'editor.selectionBackground': '#45475a',
                    'editorLineNumber.foreground': '#6c7086',
                }
            });
            GCM.editor = monaco.editor.create(document.getElementById('gcm-monaco'), {
                theme: 'gcm-dark',
                fontSize: 14,
                fontFamily: "'JetBrains Mono','Fira Code','Cascadia Code',monospace",
                minimap: { enabled: true },
                scrollBeyondLastLine: false,
                automaticLayout: true,
                wordWrap: 'on',
                tabSize: 4,
                renderWhitespace: 'selection',
                bracketPairColorization: { enabled: true },
            });
            GCM.editor.onDidChangeCursorPosition(e => {
                document.getElementById('st-pos').textContent = 'Ln ' + e.position.lineNumber + ', Col ' + e.position.column;
            });
            GCM.editor.onDidChangeModelContent(() => {
                if (GCM.activeTab !== null && GCM.tabs[GCM.activeTab]) {
                    GCM.tabs[GCM.activeTab].modified = true;
                    GCM.renderTabs();
                    document.getElementById('st-save').innerHTML = '<span class="gcm-badge unsaved">UNSAVED</span>';
                }
            });
            GCM.editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => GCM.save());
            document.getElementById('gcm-loading').textContent = '\u2714 Editor ready';
            setTimeout(() => { document.getElementById('gcm-loading').style.display = 'none'; }, 1500);
            GCM.loadTree(GCM.curPath);
        });
    },

    // ── API ──
    async api(action, extra = {}) {
        const body = new URLSearchParams({action, csrf: GCM.CSRF, ...extra});
        const res = await fetch('', {method: 'POST', body, credentials: 'same-origin'});
        return res.json();
    },

    // ── Toast ──
    toast(msg, type = 'ok') {
        const el = document.getElementById('gcm-toast');
        el.textContent = msg;
        el.className = 'gcm-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3500);
    },

    // ── Tree ──
    async loadTree(path) {
        GCM.curPath = path;
        const data = await GCM.api('list_dir', {path});
        if (data.error) { GCM.toast(data.error, 'err'); return; }
        const tree = document.getElementById('gcm-tree');
        tree.innerHTML = data.items.map(item => {
            const icon = item.isDir ? '&#128193;' : GCM.fileIcon(item.name);
            const cls = item.isDir ? 'gcm-tree-item dir' : 'gcm-tree-item';
            const sz = item.size !== null ? GCM.fmtSize(item.size) : '';
            const oc = item.isDir
                ? "GCM.nav('" + GCM.esc(item.path) + "')"
                : "GCM.open('" + GCM.esc(item.path) + "')";
            return '<div class="' + cls + '" onclick="' + oc + '" title="' + GCM.esc(item.path) + '">'
                + '<span class="ico">' + icon + '</span>'
                + '<span style="flex:1;overflow:hidden;text-overflow:ellipsis">' + GCM.esc(item.name) + '</span>'
                + (sz ? '<span class="sz">' + sz + '</span>' : '')
                + '</div>';
        }).join('');
        document.getElementById('st-file').textContent = data.path;
    },

    nav(p) { GCM.loadTree(p); },
    refresh() { GCM.loadTree(GCM.curPath); },

    fileIcon(name) {
        const ext = name.split('.').pop().toLowerCase();
        const m = {php:'\u{1F49C}',js:'\u{1F7E0}',json:'\u{1F7E1}',html:'\u{1F310}',css:'\u{1F3A8}',md:'\u{1F4C4}',py:'\u{1F40D}',sh:'\u{1F4AC}',sql:'\u{1F5C3}',enc:'\u{1F512}',txt:'\u{1F4C4}'};
        return m[ext] || '\u{1F4C4}';
    },
    fmtSize(b) { return b < 1024 ? b+'B' : b < 1048576 ? (b/1024).toFixed(1)+'K' : (b/1048576).toFixed(1)+'M'; },
    esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); },

    // ── Open File ──
    async open(path) {
        const existing = GCM.tabs.findIndex(t => t.path === path);
        if (existing >= 0) { GCM.switchTab(existing); return; }
        const data = await GCM.api('read_file', {path});
        if (data.error) { GCM.toast(data.error, 'err'); return; }
        const model = monaco.editor.createModel(data.content, data.language);
        GCM.tabs.push({ path: data.path, name: data.path.split('/').pop(), content: data.content,
            language: data.language, modified: false, model, size: data.size });
        GCM.switchTab(GCM.tabs.length - 1);
    },

    // ── Tabs ──
    switchTab(idx) {
        if (idx < 0 || idx >= GCM.tabs.length) return;
        GCM.activeTab = idx;
        const tab = GCM.tabs[idx];
        document.getElementById('gcm-welcome').style.display = 'none';
        document.getElementById('gcm-monaco').style.display = 'block';
        GCM.editor.setModel(tab.model);
        GCM.editor.focus();
        document.getElementById('st-file').textContent = tab.path;
        document.getElementById('st-lang').textContent = tab.language;
        document.getElementById('st-size').textContent = GCM.fmtSize(tab.size);
        document.getElementById('st-save').innerHTML = tab.modified
            ? '<span class="gcm-badge unsaved">UNSAVED</span>'
            : '<span class="gcm-badge saved">SAVED</span>';
        GCM.renderTabs();
    },

    closeTab(idx, e) {
        if (e) e.stopPropagation();
        const tab = GCM.tabs[idx];
        if (tab.modified && !confirm('Unsaved changes in ' + tab.name + '. Close?')) return;
        tab.model.dispose();
        GCM.tabs.splice(idx, 1);
        if (!GCM.tabs.length) {
            GCM.activeTab = null;
            document.getElementById('gcm-welcome').style.display = 'flex';
            document.getElementById('gcm-monaco').style.display = 'none';
            document.getElementById('st-file').textContent = 'No file open';
            document.getElementById('st-save').innerHTML = '';
            document.getElementById('st-lang').textContent = '-';
            document.getElementById('st-size').textContent = '-';
        } else {
            if (GCM.activeTab >= GCM.tabs.length) GCM.activeTab = GCM.tabs.length - 1;
            GCM.switchTab(GCM.activeTab);
        }
        GCM.renderTabs();
    },

    renderTabs() {
        document.getElementById('gcm-tabs').innerHTML = GCM.tabs.map((t, i) => {
            const a = i === GCM.activeTab ? ' active' : '';
            const dot = t.modified ? '<span class="dot">&#9679;</span>' : '';
            return '<div class="gcm-tab' + a + '" onclick="GCM.switchTab(' + i + ')">'
                + dot + GCM.esc(t.name) 
                + ' <span class="x" onclick="GCM.closeTab(' + i + ',event)">&times;</span></div>';
        }).join('');
    },

    // ── Save ──
    async save() {
        if (GCM.activeTab === null) return;
        const tab = GCM.tabs[GCM.activeTab];
        const content = tab.model.getValue();
        const data = await GCM.api('save_file', {path: tab.path, content});
        if (data.error) { GCM.toast(data.error, 'err'); return; }
        tab.modified = false;
        tab.content = content;
        tab.size = data.bytes;
        GCM.renderTabs();
        document.getElementById('st-save').innerHTML = '<span class="gcm-badge saved">SAVED</span>';
        document.getElementById('st-size').textContent = GCM.fmtSize(data.bytes);
        let msg = 'Saved ' + tab.name + ' (' + data.bytes + ' bytes)';
        if (data.syntax && data.syntax !== 'ok') { GCM.toast('Saved — SYNTAX ERROR: ' + data.syntax, 'warn'); }
        else if (data.backup) { GCM.toast(msg + ' [backup created]', 'ok'); }
        else { GCM.toast(msg, 'ok'); }
    },

    // ── Search ──
    handleSearch(e) {
        clearTimeout(GCM.searchTimer);
        const q = e.target.value.trim();
        if (q.length < 2) { GCM.loadTree(GCM.curPath); return; }
        GCM.searchTimer = setTimeout(async () => {
            const data = await GCM.api('search_files', {query: q, path: GCM.curPath});
            if (data.error) return;
            const tree = document.getElementById('gcm-tree');
            if (!data.results.length) {
                tree.innerHTML = '<div style="padding:1rem;opacity:.3;text-align:center;font-size:.78rem">No results for "' + GCM.esc(q) + '"</div>';
                return;
            }
            tree.innerHTML = data.results.map(r =>
                '<div class="gcm-tree-item" onclick="GCM.open(\'' + GCM.esc(r.path) + '\')" title="' + GCM.esc(r.path) + '">'
                + '<span class="ico">&#128269;</span>'
                + '<span style="flex:1;overflow:hidden;text-overflow:ellipsis">' + GCM.esc(r.name) + '</span></div>'
            ).join('');
        }, 350);
    },

    // ── Chat Panel ──
    toggleChat() {
        GCM.chatOpen = !GCM.chatOpen;
        document.getElementById('gcm-chat').classList.toggle('open', GCM.chatOpen);
        document.getElementById('gcm-chat-toggle').classList.toggle('active', GCM.chatOpen);
        if (GCM.chatOpen) document.getElementById('gcm-chat-input').focus();
    },

    addChatMsg(role, text) {
        const msgs = document.getElementById('gcm-chat-msgs');
        // Remove welcome screen on first message
        const welcome = msgs.querySelector('.gcm-chat-welcome');
        if (welcome) welcome.remove();
        const div = document.createElement('div');
        div.className = 'gcm-msg ' + role;
        if (role === 'alfred') {
            div.innerHTML = GCM.renderMarkdown(text);
        } else {
            div.textContent = text;
        }
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        GCM.chatMessages.push({role, text});
    },

    showTyping() {
        const msgs = document.getElementById('gcm-chat-msgs');
        const el = document.createElement('div');
        el.className = 'gcm-typing';
        el.id = 'gcm-typing';
        el.innerHTML = '<div class="gcm-typing-dots"><span></span><span></span><span></span></div> Alfred is thinking...';
        msgs.appendChild(el);
        msgs.scrollTop = msgs.scrollHeight;
    },

    hideTyping() {
        const el = document.getElementById('gcm-typing');
        if (el) el.remove();
    },

    async sendChat() {
        if (GCM.chatSending) return;
        const input = document.getElementById('gcm-chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';
        input.style.height = 'auto';

        // Build context from currently open file
        let context = 'Alfred IDE - Browser code editor.';
        if (GCM.activeTab !== null && GCM.tabs[GCM.activeTab]) {
            const tab = GCM.tabs[GCM.activeTab];
            const snippet = tab.model.getValue().substring(0, 3000);
            context += '\nOpen file: ' + tab.path + ' (' + tab.language + ')\nFile preview (first 3000 chars):\n' + snippet;
        }

        GCM.addChatMsg('user', msg);
        GCM.showTyping();
        GCM.chatSending = true;
        document.getElementById('gcm-chat-send').disabled = true;

        try {
            const body = {
                message: msg,
                agent: 'alfred',
                context: context,
                channel: 'ide-chat',
                conv_id: GCM.chatConvId,
                model: 'sonnet',
                page_url: location.href
            };

            const headers = {'Content-Type': 'application/json'};
            if (GCM.chatCSRF) headers['X-CSRF-Token'] = GCM.chatCSRF;

            const res = await fetch('/api/alfred-chat.php', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(body)
            });
            const data = await res.json();

            // Handle CSRF token refresh
            if (data.csrf_token) GCM.chatCSRF = data.csrf_token;

            // If we got a csrf_refresh response, retry with the new token
            if (data.csrf_refresh) {
                GCM.chatCSRF = data.csrf_token || '';
                // Retry the request with the new token
                headers['X-CSRF-Token'] = GCM.chatCSRF;
                const res2 = await fetch('/api/alfred-chat.php', {
                    method: 'POST',
                    headers,
                    credentials: 'same-origin',
                    body: JSON.stringify(body)
                });
                const data2 = await res2.json();
                if (data2.csrf_token) GCM.chatCSRF = data2.csrf_token;
                GCM.hideTyping();
                GCM.addChatMsg('alfred', data2.response || data2.message || 'No response received.');
            } else if (data.error) {
                GCM.hideTyping();
                GCM.addChatMsg('system', '⚠ ' + data.error);
            } else {
                GCM.hideTyping();
                GCM.addChatMsg('alfred', data.response || data.message || 'No response received.');
            }
        } catch (err) {
            GCM.hideTyping();
            GCM.addChatMsg('system', '⚠ Connection error: ' + err.message);
        }

        GCM.chatSending = false;
        document.getElementById('gcm-chat-send').disabled = false;
        document.getElementById('gcm-chat-input').focus();
    },

    clearChat() {
        if (!confirm('Clear chat history?')) return;
        const msgs = document.getElementById('gcm-chat-msgs');
        msgs.innerHTML = '<div class="gcm-chat-welcome"><div class="icon">&#129302;</div><h4>Alfred IDE Assistant</h4><p>Ask about your code, get help with bugs, or request file changes.</p></div>';
        GCM.chatMessages = [];
        GCM.chatConvId = 'ide-' + Date.now() + '-' + Math.floor(Math.random()*9999);
    },

    renderMarkdown(text) {
        let html = GCM.esc(text);
        // Code blocks
        html = html.replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        // Line breaks
        html = html.replace(/\n/g, '<br>');
        return html;
    }
};

// ── Boot ──
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); GCM.save(); }
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'A' || e.key === 'a')) { e.preventDefault(); GCM.toggleChat(); }
});
GCM.init();
</script>
