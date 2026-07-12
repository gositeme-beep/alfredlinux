const vscode = require('vscode');

/** @type {vscode.WebviewViewProvider} */
class AlfredChatProvider {
    static viewType = 'alfred.chatView';
    
    constructor(extensionUri) {
        this._extensionUri = extensionUri;
        this._view = undefined;
    }

    resolveWebviewView(webviewView) {
        this._view = webviewView;
        webviewView.webview.options = {
            enableScripts: true,
            localResourceRoots: [this._extensionUri]
        };
        webviewView.webview.html = this._getHtml(webviewView.webview);
        
        webviewView.webview.onDidReceiveMessage(async (msg) => {
            switch (msg.type) {
                case 'chat':
                    const reply = await this._callAlfred(msg.text, msg.context);
                    webviewView.webview.postMessage({ type: 'response', text: reply });
                    break;
                case 'insert':
                    const editor = vscode.window.activeTextEditor;
                    if (editor) {
                        editor.edit(eb => eb.insert(editor.selection.active, msg.text));
                    }
                    break;
            }
        });
    }

    async _callAlfred(prompt, context = '') {
        const config = vscode.workspace.getConfiguration('alfred');
        const apiKey = config.get('apiKey');
        const apiUrl = config.get('apiUrl') || 'https://gositeme.com/api/v1';
        
        if (!apiKey) {
            vscode.window.showWarningMessage('Alfred: Set your API key in settings (alfred.apiKey)');
            return 'Please set your API key in VS Code settings → Alfred AI → API Key';
        }

        try {
            const resp = await fetch(`${apiUrl}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiKey}`
                },
                body: JSON.stringify({
                    message: prompt,
                    context: context,
                    model: config.get('model') || 'auto',
                    source: 'vscode-extension'
                })
            });
            
            if (!resp.ok) {
                const err = await resp.text();
                return `Error (${resp.status}): ${err}`;
            }
            
            const data = await resp.json();
            return data.response || data.message || JSON.stringify(data);
        } catch (err) {
            return `Connection error: ${err.message}`;
        }
    }

    _getHtml(webview) {
        return `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--vscode-font-family);background:var(--vscode-sideBar-background);color:var(--vscode-foreground);display:flex;flex-direction:column;height:100vh;overflow:hidden}
.header{padding:12px 16px;border-bottom:1px solid var(--vscode-panel-border);display:flex;align-items:center;gap:8px}
.header h2{font-size:13px;font-weight:600;flex:1}
.header .status{width:8px;height:8px;border-radius:50%;background:#4caf50}
.messages{flex:1;overflow-y:auto;padding:12px 16px;display:flex;flex-direction:column;gap:12px}
.msg{padding:10px 14px;border-radius:8px;font-size:12.5px;line-height:1.5;white-space:pre-wrap;word-break:break-word}
.msg.user{background:var(--vscode-button-background);color:var(--vscode-button-foreground);align-self:flex-end;max-width:85%;border-bottom-right-radius:2px}
.msg.alfred{background:var(--vscode-editor-background);border:1px solid var(--vscode-panel-border);align-self:flex-start;max-width:90%;border-bottom-left-radius:2px}
.msg.alfred pre{background:var(--vscode-textBlockQuote-background);padding:8px 10px;border-radius:4px;overflow-x:auto;margin:6px 0;font-family:var(--vscode-editor-font-family);font-size:12px}
.msg.alfred code{font-family:var(--vscode-editor-font-family);font-size:12px;background:var(--vscode-textBlockQuote-background);padding:1px 4px;border-radius:3px}
.input-area{padding:12px 16px;border-top:1px solid var(--vscode-panel-border);display:flex;gap:8px}
.input-area textarea{flex:1;background:var(--vscode-input-background);color:var(--vscode-input-foreground);border:1px solid var(--vscode-input-border);border-radius:6px;padding:8px 12px;font-family:var(--vscode-font-family);font-size:12.5px;resize:none;min-height:36px;max-height:120px;outline:none}
.input-area textarea:focus{border-color:var(--vscode-focusBorder)}
.input-area button{background:var(--vscode-button-background);color:var(--vscode-button-foreground);border:none;border-radius:6px;padding:0 14px;cursor:pointer;font-size:12px;font-weight:600}
.input-area button:hover{background:var(--vscode-button-hoverBackground)}
.loading{display:inline-flex;gap:4px;padding:4px 0}
.loading span{width:6px;height:6px;border-radius:50%;background:var(--vscode-foreground);opacity:.4;animation:bounce .6s ease-in-out infinite}
.loading span:nth-child(2){animation-delay:.1s}
.loading span:nth-child(3){animation-delay:.2s}
@keyframes bounce{0%,100%{opacity:.4;transform:translateY(0)}50%{opacity:1;transform:translateY(-4px)}}
.welcome{text-align:center;padding:40px 20px;color:var(--vscode-descriptionForeground)}
.welcome h3{margin-bottom:8px;font-size:14px;color:var(--vscode-foreground)}
.welcome p{font-size:12px;line-height:1.6}
.shortcuts{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:12px}
.shortcuts button{background:var(--vscode-editor-background);border:1px solid var(--vscode-panel-border);color:var(--vscode-foreground);padding:5px 10px;border-radius:12px;font-size:11px;cursor:pointer}
.shortcuts button:hover{background:var(--vscode-button-background);color:var(--vscode-button-foreground);border-color:var(--vscode-button-background)}
</style>
</head>
<body>
<div class="header">
  <span class="status"></span>
  <h2>Alfred AI</h2>
</div>
<div class="messages" id="messages">
  <div class="welcome">
    <h3>👋 Hey! I'm Alfred</h3>
    <p>Your AI coding assistant with 1,290+ tools.<br>Ask me anything or select code and right-click.</p>
    <div class="shortcuts">
      <button onclick="quickSend('Explain this project structure')">📁 Project</button>
      <button onclick="quickSend('Find bugs in my code')">🐛 Find Bugs</button>
      <button onclick="quickSend('Suggest optimizations')">⚡ Optimize</button>
      <button onclick="quickSend('Write tests')">🧪 Tests</button>
      <button onclick="quickSend('Help me deploy')">🚀 Deploy</button>
    </div>
  </div>
</div>
<div class="input-area">
  <textarea id="input" placeholder="Ask Alfred anything..." rows="1"></textarea>
  <button onclick="send()">Send</button>
</div>
<script>
const vscode = acquireVsCodeApi();
const messagesEl = document.getElementById('messages');
const inputEl = document.getElementById('input');

function send() {
  const text = inputEl.value.trim();
  if (!text) return;
  addMessage(text, 'user');
  inputEl.value = '';
  inputEl.style.height = '36px';
  const loading = addLoading();
  vscode.postMessage({ type: 'chat', text });
}

function quickSend(text) {
  inputEl.value = text;
  send();
}

function addMessage(text, role) {
  const welcome = messagesEl.querySelector('.welcome');
  if (welcome) welcome.remove();
  const div = document.createElement('div');
  div.className = 'msg ' + role;
  div.textContent = text;
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return div;
}

function addLoading() {
  const div = document.createElement('div');
  div.className = 'msg alfred loading-msg';
  div.innerHTML = '<div class="loading"><span></span><span></span><span></span></div>';
  messagesEl.appendChild(div);
  messagesEl.scrollTop = messagesEl.scrollHeight;
  return div;
}

window.addEventListener('message', e => {
  const msg = e.data;
  if (msg.type === 'response') {
    const loading = messagesEl.querySelector('.loading-msg');
    if (loading) loading.remove();
    addMessage(msg.text, 'alfred');
  }
});

inputEl.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
});
inputEl.addEventListener('input', () => {
  inputEl.style.height = '36px';
  inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
});
</script>
</body>
</html>`;
    }
}

/**
 * @param {vscode.ExtensionContext} context
 */
function activate(context) {
    const provider = new AlfredChatProvider(context.extensionUri);
    
    context.subscriptions.push(
        vscode.window.registerWebviewViewProvider(AlfredChatProvider.viewType, provider)
    );

    // Register all commands
    const commands = {
        'alfred.chat': () => vscode.commands.executeCommand('alfred.chatView.focus'),
        'alfred.ask': async () => {
            const q = await vscode.window.showInputBox({ prompt: 'Ask Alfred anything...', placeHolder: 'e.g., How do I optimize this database query?' });
            if (q) sendToChat(provider, q);
        },
        'alfred.explain': () => sendSelectedCode(provider, 'Explain this code in detail:'),
        'alfred.refactor': () => sendSelectedCode(provider, 'Refactor this code for better readability, performance, and best practices:'),
        'alfred.test': () => sendSelectedCode(provider, 'Generate comprehensive unit tests for this code:'),
        'alfred.document': () => sendSelectedCode(provider, 'Generate documentation (JSDoc/docstring) for this code:'),
        'alfred.review': () => sendSelectedCode(provider, 'Review this code for bugs, security issues, and improvements:'),
        'alfred.fix': async () => {
            const editor = vscode.window.activeTextEditor;
            if (!editor) return;
            const diagnostics = vscode.languages.getDiagnostics(editor.document.uri);
            if (diagnostics.length === 0) {
                vscode.window.showInformationMessage('No errors found in this file.');
                return;
            }
            const errors = diagnostics.map(d => `Line ${d.range.start.line + 1}: ${d.message}`).join('\n');
            const code = editor.document.getText();
            sendToChat(provider, `Fix these errors in my ${editor.document.languageId} code:\n\nErrors:\n${errors}\n\nCode:\n\`\`\`${editor.document.languageId}\n${code.substring(0, 3000)}\n\`\`\``);
        },
        'alfred.optimize': () => sendSelectedCode(provider, 'Optimize this code for better performance:')
    };

    for (const [cmd, handler] of Object.entries(commands)) {
        context.subscriptions.push(vscode.commands.registerCommand(cmd, handler));
    }

    // Status bar item
    const statusBar = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Right, 100);
    statusBar.text = '$(hubot) Alfred';
    statusBar.tooltip = 'Open Alfred AI Chat (Ctrl+Shift+A)';
    statusBar.command = 'alfred.chat';
    statusBar.show();
    context.subscriptions.push(statusBar);

    vscode.window.showInformationMessage('Alfred AI is ready! Press Ctrl+Shift+A to chat.');
}

function sendSelectedCode(provider, prompt) {
    const editor = vscode.window.activeTextEditor;
    if (!editor || editor.selection.isEmpty) {
        vscode.window.showWarningMessage('Select some code first.');
        return;
    }
    const code = editor.document.getText(editor.selection);
    const lang = editor.document.languageId;
    const file = editor.document.fileName;
    sendToChat(provider, `${prompt}\n\nFile: ${file}\nLanguage: ${lang}\n\n\`\`\`${lang}\n${code}\n\`\`\``);
}

function sendToChat(provider, text) {
    if (provider._view) {
        provider._view.show(true);
        provider._view.webview.postMessage({ type: 'externalChat', text });
        // Also trigger the API call from extension side
        provider._callAlfred(text).then(reply => {
            provider._view.webview.postMessage({ type: 'response', text: reply });
        });
    }
}

function deactivate() {}

module.exports = { activate, deactivate };
