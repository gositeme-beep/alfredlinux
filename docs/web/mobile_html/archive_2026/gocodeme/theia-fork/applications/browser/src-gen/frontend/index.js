// @ts-check
require('reflect-metadata');
const { Container } = require('@theia/core/shared/inversify');
const { FrontendApplicationConfigProvider } = require('@theia/core/lib/browser/frontend-application-config-provider');

FrontendApplicationConfigProvider.set({
    "applicationName": "GoCodeMe IDE",
    "defaultTheme": {
        "light": "light",
        "dark": "dark"
    },
    "defaultIconTheme": "theia-file-icons",
    "electron": {
        "windowOptions": {},
        "showWindowEarly": true,
        "splashScreenOptions": {},
        "uriScheme": "theia"
    },
    "defaultLocale": "",
    "validatePreferencesSchema": true,
    "reloadOnReconnect": true,
    "uriScheme": "theia",
    "warnOnPotentiallyInsecureHostPattern": false,
    "preferences": {
        "toolbar.showToolbar": true,
        "files.enableTrash": false,
        "security.workspace.trust.enabled": false,
        "workbench.startupEditor": "none",
        "workbench.welcomePage.walkthroughs.openOnInstall": false,
        "ai-features.aiEnable": true,
        "ai-features.defaultModelId": "claude-sonnet-4-6",
        "ai-features.codeCompletion.enable": true,
        "ai-features.inlineCompletion.enable": true,
        "ai-features.chat.enable": true,
        "ai-features.chat.defaultChatAgent": "Universal",
        "ai-features.claudeCode.enable": true,
        "ai-features.mcp.mcpServers": {
            "gocodeme-files": {
                "serverUrl": "http://localhost:3005/mcp",
                "autostart": true
            }
        },
        "ai-features.AiEnable.enableAI": true,
        "ai-features.anthropic.AnthropicModels": [
            "claude-sonnet-4-6",
            "claude-opus-4-6",
            "claude-haiku-4-5-20251001",
            "claude-sonnet-4-5-20250929",
            "claude-opus-4-5-20251101",
            "claude-opus-4-1-20250805",
            "claude-sonnet-4-20250514",
            "claude-opus-4-20250514"
        ],
        "ai-features.openAiCustom.customOpenAiModels": [
            {
                "model": "llama-3.3-70b-versatile",
                "url": "https://api.groq.com/openai/v1",
                "apiKey": "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"
            },
            {
                "model": "llama-3.1-8b-instant",
                "url": "https://api.groq.com/openai/v1",
                "apiKey": "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"
            },
            {
                "model": "gemma2-9b-it",
                "url": "https://api.groq.com/openai/v1",
                "apiKey": "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"
            },
            {
                "model": "mixtral-8x7b-32768",
                "url": "https://api.groq.com/openai/v1",
                "apiKey": "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"
            }
        ]
    }
});


self.MonacoEnvironment = {
    getWorkerUrl: function (moduleId, label) {
        return './editor.worker.js';
    }
}

function load(container, jsModule) {
    return Promise.resolve(jsModule)
        .then(containerModule => container.load(containerModule.default));
}

async function preload(container) {
    try {
        await load(container, import('@theia/core/lib/browser/preload/preload-module'));
        const { Preloader } = require('@theia/core/lib/browser/preload/preloader');
        const preloader = container.get(Preloader);
        await preloader.initialize();
    } catch (reason) {
        console.error('Failed to run preload scripts.');
        if (reason) {
            console.error(reason);
        }
    }
}

module.exports = (async () => {
    const { messagingFrontendModule } = require('@theia/core/lib/browser/messaging/messaging-frontend-module');
    const container = new Container();
    container.load(messagingFrontendModule);
    

    await preload(container);

    
    const { MonacoInit } = require('@theia/monaco/lib/browser/monaco-init');
    ;

    const { FrontendApplication } = require('@theia/core/lib/browser');
    const { frontendApplicationModule } = require('@theia/core/lib/browser/frontend-application-module');    
    const { loggerFrontendModule } = require('@theia/core/lib/browser/logger-frontend-module');

    container.load(frontendApplicationModule);
    undefined
    
    container.load(loggerFrontendModule);
    

    try {
        await load(container, import('@theia/core/lib/browser/i18n/i18n-frontend-module'));
        await load(container, import('@theia/core/lib/browser/menu/browser-menu-module'));
        await load(container, import('@theia/core/lib/browser/window/browser-window-module'));
        await load(container, import('@theia/core/lib/browser/keyboard/browser-keyboard-module'));
        await load(container, import('@theia/core/lib/browser/request/browser-request-module'));
        await load(container, import('@theia/variable-resolver/lib/browser/variable-resolver-frontend-module'));
        await load(container, import('@theia/editor/lib/browser/editor-frontend-module'));
        await load(container, import('@theia/filesystem/lib/browser/filesystem-frontend-module'));
        await load(container, import('@theia/filesystem/lib/browser/download/file-download-frontend-module'));
        await load(container, import('@theia/filesystem/lib/browser/file-dialog/file-dialog-module'));
        await load(container, import('@theia/workspace/lib/browser/workspace-frontend-module'));
        await load(container, import('@theia/markers/lib/browser/problem/problem-frontend-module'));
        await load(container, import('@theia/outline-view/lib/browser/outline-view-frontend-module'));
        await load(container, import('@theia/monaco/lib/browser/monaco-frontend-module'));
        await load(container, import('@theia/output/lib/browser/output-frontend-module'));
        await load(container, import('@theia/ai-core/lib/browser/ai-core-frontend-module'));
        await load(container, import('@theia/ai-anthropic/lib/browser/anthropic-frontend-module'));
        await load(container, import('@theia/process/lib/common/process-common-module'));
        await load(container, import('@theia/file-search/lib/browser/file-search-frontend-module'));
        await load(container, import('@theia/ai-chat/lib/browser/ai-chat-frontend-module'));
        await load(container, import('@theia/navigator/lib/browser/navigator-frontend-module'));
        await load(container, import('@theia/editor-preview/lib/browser/editor-preview-frontend-module'));
        await load(container, import('@theia/userstorage/lib/browser/user-storage-frontend-module'));
        await load(container, import('@theia/preferences/lib/browser/preference-frontend-module'));
        await load(container, import('@theia/ai-chat-ui/lib/browser/ai-chat-ui-frontend-module'));
        await load(container, import('@theia/ai-claude-code/lib/browser/claude-code-frontend-module'));
        await load(container, import('@theia/ai-code-completion/lib/browser/ai-code-completion-frontend-module'));
        await load(container, import('@theia/ai-openai/lib/browser/openai-frontend-module'));
        await load(container, import('@theia/ai-codex/lib/browser/codex-frontend-module'));
        await load(container, import('@theia/ai-copilot/lib/browser/copilot-frontend-module'));
        await load(container, import('@theia/ai-core-ui/lib/browser/ai-core-ui-frontend-module'));
        await load(container, import('@theia/ai-editor/lib/browser/ai-editor-frontend-module'));
        await load(container, import('@theia/ai-google/lib/browser/google-frontend-module'));
        await load(container, import('@theia/ai-history/lib/browser/ai-history-frontend-module'));
        await load(container, import('@theia/ai-huggingface/lib/browser/huggingface-frontend-module'));
        await load(container, import('@theia/ai-mcp/lib/browser/mcp-frontend-module'));
        await load(container, import('@theia/console/lib/browser/console-frontend-module'));
        await load(container, import('@theia/terminal/lib/browser/terminal-frontend-module'));
        await load(container, import('@theia/task/lib/browser/task-frontend-module'));
        await load(container, import('@theia/test/lib/browser/view/test-view-frontend-module'));
        await load(container, import('@theia/debug/lib/browser/debug-frontend-module'));
        await load(container, import('@theia/scm/lib/browser/scm-frontend-module'));
        await load(container, import('@theia/search-in-workspace/lib/browser/search-in-workspace-frontend-module'));
        await load(container, import('@theia/ai-ide/lib/browser/frontend-module'));
        await load(container, import('@theia/ai-llamafile/lib/browser/llamafile-frontend-module'));
        await load(container, import('@theia/ai-mcp-server/lib/browser/mcp-frontend-module'));
        await load(container, import('@theia/ai-mcp-ui/lib/browser/mcp-ui-frontend-module'));
        await load(container, import('@theia/ai-ollama/lib/browser/ollama-frontend-module'));
        await load(container, import('@theia/scanoss/lib/browser/scanoss-frontend-module'));
        await load(container, import('@theia/ai-scanoss/lib/browser/ai-scanoss-frontend-module'));
        await load(container, import('@theia/ai-terminal/lib/browser/ai-terminal-frontend-module'));
        await load(container, import('@theia/ai-vercel-ai/lib/browser/vercel-ai-frontend-module'));
        await load(container, import('@theia/bulk-edit/lib/browser/bulk-edit-frontend-module'));
        await load(container, import('@theia/callhierarchy/lib/browser/callhierarchy-frontend-module'));
        await load(container, import('@theia/collaboration/lib/browser/collaboration-frontend-module'));
        await load(container, import('@theia/keymaps/lib/browser/keymaps-frontend-module'));
        await load(container, import('@theia/mini-browser/lib/browser/mini-browser-frontend-module'));
        await load(container, import('@theia/mini-browser/lib/browser/environment/mini-browser-environment-module'));
        await load(container, import('@theia/preview/lib/browser/preview-frontend-module'));
        await load(container, import('@theia/getting-started/lib/browser/getting-started-frontend-module'));
        await load(container, import('@theia/memory-inspector/lib/browser/memory-inspector-frontend-module'));
        await load(container, import('@theia/messages/lib/browser/messages-frontend-module'));
        await load(container, import('@theia/metrics/lib/browser/metrics-frontend-module'));
        await load(container, import('@theia/notebook/lib/browser/notebook-frontend-module'));
        await load(container, import('@theia/timeline/lib/browser/timeline-frontend-module'));
        await load(container, import('@theia/typehierarchy/lib/browser/typehierarchy-frontend-module'));
        await load(container, import('@theia/plugin-ext/lib/plugin-ext-frontend-module'));
        await load(container, import('@theia/plugin-dev/lib/browser/plugin-dev-frontend-module'));
        await load(container, import('@theia/plugin-ext-vscode/lib/browser/plugin-vscode-frontend-module'));
        await load(container, import('@theia/property-view/lib/browser/property-view-frontend-module'));
        await load(container, import('@theia/secondary-window/lib/browser/secondary-window-frontend-module'));
        await load(container, import('@theia/terminal-manager/lib/browser/terminal-manager-frontend-module'));
        await load(container, import('@theia/toolbar/lib/browser/toolbar-frontend-module'));
        await load(container, import('@theia/vsx-registry/lib/common/vsx-registry-common-module'));
        await load(container, import('@theia/vsx-registry/lib/browser/vsx-registry-frontend-module'));
        await load(container, import('theia-ide-product-ext/lib/browser/theia-ide-frontend-module'));
        
        MonacoInit.init(container);
        ;
        await start();
    } catch (reason) {
        console.error('Failed to start the frontend application.');
        if (reason) {
            console.error(reason);
        }
    }

    function start() {
        (window['theia'] = window['theia'] || {}).container = container;
        return container.get(FrontendApplication).start();
    }
})();
