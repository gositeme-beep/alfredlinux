const { app, BrowserWindow, Menu, Tray, shell, session, nativeImage, ipcMain, dialog, Notification } = require('electron');
const { autoUpdater } = require('electron-updater');
const path = require('path');
const fs = require('fs');

const VEIL_URL = 'https://gositeme.com/';
const APP_NAME = 'Veil Browser';
const APP_VERSION = '3.0.0';

let mainWindow = null;
let tray = null;
let isQuitting = false;
let isMiningActive = false;

// ═══════════════════════════════════════════════════════════════
// BOOKMARKS SYSTEM
// ═══════════════════════════════════════════════════════════════
function getBookmarksPath() {
    return path.join(app.getPath('userData'), 'bookmarks.json');
}

function loadBookmarks() {
    try {
        const data = fs.readFileSync(getBookmarksPath(), 'utf8');
        return JSON.parse(data);
    } catch {
        // Default bookmark structure
        const defaults = {
            toolbar: [
                { id: 'default-1', title: 'Home', url: 'https://gositeme.com/', icon: 'fas fa-home', createdAt: Date.now() },
                { id: 'default-2', title: 'Alfred AI', url: 'https://gositeme.com/alfred', icon: 'fas fa-robot', createdAt: Date.now() },
                { id: 'default-3', title: 'Dashboard', url: 'https://gositeme.com/dashboard', icon: 'fas fa-tachometer-alt', createdAt: Date.now() },
            ],
            folders: [
                {
                    id: 'folder-1', name: 'GoSiteMe Apps', bookmarks: [
                        { id: 'app-1', title: 'Pulse Network', url: 'https://gositeme.com/pulse', createdAt: Date.now() },
                        { id: 'app-2', title: 'Games & VR', url: 'https://gositeme.com/games', createdAt: Date.now() },
                        { id: 'app-3', title: 'Marketplace', url: 'https://gositeme.com/marketplace', createdAt: Date.now() },
                        { id: 'app-4', title: 'Veil Protocol', url: 'https://gositeme.com/veil/', createdAt: Date.now() },
                    ]
                },
                {
                    id: 'folder-2', name: 'Tools', bookmarks: [
                        { id: 'tool-1', title: 'GoCodeMe IDE', url: 'https://gositeme.com/gocodeme', createdAt: Date.now() },
                        { id: 'tool-2', title: 'Mining & Wallet', url: 'https://gositeme.com/wallet', createdAt: Date.now() },
                        { id: 'tool-3', title: 'Search', url: 'https://gositeme.com/search', createdAt: Date.now() },
                    ]
                },
            ],
            other: []
        };
        saveBookmarks(defaults);
        return defaults;
    }
}

function saveBookmarks(bookmarks) {
    fs.writeFileSync(getBookmarksPath(), JSON.stringify(bookmarks, null, 2), 'utf8');
}

function generateId() {
    return 'bm-' + Date.now() + '-' + Math.random().toString(36).substring(2, 8);
}

// ═══════════════════════════════════════════════════════════════
// AUTO-UPDATER CONFIGURATION
// ═══════════════════════════════════════════════════════════════
autoUpdater.autoDownload = false;
autoUpdater.autoInstallOnAppQuit = true;
autoUpdater.setFeedURL({
    provider: 'generic',
    url: 'https://gositeme.com/api/app-updates.php?action=electron_update',
});

autoUpdater.on('update-available', (info) => {
    if (mainWindow) {
        mainWindow.webContents.send('update-available', info);
    }
    // Show native notification
    if (Notification.isSupported()) {
        new Notification({
            title: 'Veil Browser Update Available',
            body: `Version ${info.version} is ready to download.`,
            icon: path.join(__dirname, 'build', 'icon.png'),
        }).show();
    }
    // Auto-download the update
    autoUpdater.downloadUpdate();
});

autoUpdater.on('update-not-available', () => {
    // Silently ignore — app is up to date
});

autoUpdater.on('download-progress', (progress) => {
    if (mainWindow) {
        mainWindow.webContents.send('update-progress', {
            percent: Math.round(progress.percent),
            transferred: progress.transferred,
            total: progress.total,
        });
        mainWindow.setProgressBar(progress.percent / 100);
    }
});

autoUpdater.on('update-downloaded', (info) => {
    if (mainWindow) {
        mainWindow.webContents.send('update-downloaded', info);
        mainWindow.setProgressBar(-1); // Clear progress bar
    }
    // Show dialog offering to restart
    dialog.showMessageBox(mainWindow, {
        type: 'info',
        title: 'Update Ready',
        message: `Veil Browser v${info.version} has been downloaded.`,
        detail: 'Restart now to apply the update?',
        buttons: ['Restart Now', 'Later'],
        defaultId: 0,
    }).then(({ response }) => {
        if (response === 0) {
            isQuitting = true;
            autoUpdater.quitAndInstall();
        }
    });
});

autoUpdater.on('error', (err) => {
    if (mainWindow) {
        mainWindow.webContents.send('update-error', err.message);
    }
});

// ═══════════════════════════════════════════════════════════════
// IPC HANDLERS
// ═══════════════════════════════════════════════════════════════
ipcMain.on('check-for-updates', () => autoUpdater.checkForUpdates());
ipcMain.on('install-update', () => { isQuitting = true; autoUpdater.quitAndInstall(); });
ipcMain.on('navigate', (_, path) => { if (mainWindow) mainWindow.loadURL('https://gositeme.com' + path); });
ipcMain.on('go-back', () => { if (mainWindow && mainWindow.webContents.canGoBack()) mainWindow.webContents.goBack(); });
ipcMain.on('go-forward', () => { if (mainWindow && mainWindow.webContents.canGoForward()) mainWindow.webContents.goForward(); });
ipcMain.on('page-reload', () => { if (mainWindow) mainWindow.reload(); });
ipcMain.on('set-mining-status', (_, active) => { isMiningActive = !!active; });
ipcMain.handle('get-mining-status', () => isMiningActive);

// Bookmark IPC handlers
ipcMain.handle('bookmarks-get-all', () => loadBookmarks());
ipcMain.handle('bookmarks-add', (_, { title, url, folder }) => {
    const bookmarks = loadBookmarks();
    const entry = { id: generateId(), title, url, createdAt: Date.now() };
    if (folder === 'toolbar') {
        bookmarks.toolbar.push(entry);
    } else if (folder) {
        const f = bookmarks.folders.find(f => f.id === folder);
        if (f) f.bookmarks.push(entry);
        else bookmarks.other.push(entry);
    } else {
        bookmarks.other.push(entry);
    }
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-remove', (_, id) => {
    const bookmarks = loadBookmarks();
    bookmarks.toolbar = bookmarks.toolbar.filter(b => b.id !== id);
    bookmarks.other = bookmarks.other.filter(b => b.id !== id);
    bookmarks.folders.forEach(f => { f.bookmarks = f.bookmarks.filter(b => b.id !== id); });
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-create-folder', (_, name) => {
    const bookmarks = loadBookmarks();
    bookmarks.folders.push({ id: generateId(), name, bookmarks: [] });
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-remove-folder', (_, folderId) => {
    const bookmarks = loadBookmarks();
    bookmarks.folders = bookmarks.folders.filter(f => f.id !== folderId);
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-rename-folder', (_, { folderId, name }) => {
    const bookmarks = loadBookmarks();
    const f = bookmarks.folders.find(f => f.id === folderId);
    if (f) f.name = name;
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-move', (_, { bookmarkId, targetFolder }) => {
    const bookmarks = loadBookmarks();
    let entry = null;
    // Find and remove from current location
    const idx1 = bookmarks.toolbar.findIndex(b => b.id === bookmarkId);
    if (idx1 >= 0) { entry = bookmarks.toolbar.splice(idx1, 1)[0]; }
    if (!entry) { const idx2 = bookmarks.other.findIndex(b => b.id === bookmarkId); if (idx2 >= 0) entry = bookmarks.other.splice(idx2, 1)[0]; }
    if (!entry) { for (const f of bookmarks.folders) { const idx3 = f.bookmarks.findIndex(b => b.id === bookmarkId); if (idx3 >= 0) { entry = f.bookmarks.splice(idx3, 1)[0]; break; } } }
    if (entry) {
        if (targetFolder === 'toolbar') bookmarks.toolbar.push(entry);
        else if (targetFolder === 'other') bookmarks.other.push(entry);
        else { const f = bookmarks.folders.find(f => f.id === targetFolder); if (f) f.bookmarks.push(entry); }
    }
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-edit', (_, { id, title, url }) => {
    const bookmarks = loadBookmarks();
    const find = (arr) => arr.find(b => b.id === id);
    let bm = find(bookmarks.toolbar) || find(bookmarks.other);
    if (!bm) { for (const f of bookmarks.folders) { bm = find(f.bookmarks); if (bm) break; } }
    if (bm) { if (title !== undefined) bm.title = title; if (url !== undefined) bm.url = url; }
    saveBookmarks(bookmarks);
    return bookmarks;
});
ipcMain.handle('bookmarks-export', () => JSON.stringify(loadBookmarks(), null, 2));
ipcMain.handle('bookmarks-import', (_, jsonStr) => {
    try {
        const imported = JSON.parse(jsonStr);
        if (imported.toolbar && imported.folders) {
            saveBookmarks(imported);
            return { success: true, bookmarks: imported };
        }
        return { success: false, error: 'Invalid bookmark format' };
    } catch { return { success: false, error: 'Invalid JSON' }; }
});
ipcMain.handle('bookmarks-get-current-page', () => {
    if (mainWindow) {
        return { title: mainWindow.webContents.getTitle(), url: mainWindow.webContents.getURL() };
    }
    return { title: '', url: '' };
});

// ═══════════════════════════════════════════════════════════════
// SINGLE INSTANCE
// ═══════════════════════════════════════════════════════════════
const gotTheLock = app.requestSingleInstanceLock();
if (!gotTheLock) {
    app.quit();
}

app.on('second-instance', () => {
    if (mainWindow) {
        if (mainWindow.isMinimized()) mainWindow.restore();
        mainWindow.focus();
    }
});

// ═══════════════════════════════════════════════════════════════
// MAIN WINDOW
// ═══════════════════════════════════════════════════════════════
function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 900,
        minHeight: 600,
        title: APP_NAME,
        icon: path.join(__dirname, 'build', 'icon.png'),
        backgroundColor: '#0a0a1a',
        show: false,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
            sandbox: true,
            webviewTag: false,
        },
        titleBarStyle: 'default',
        autoHideMenuBar: false,
    });

    // Show splash while loading
    mainWindow.loadFile('splash.html');
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
        setTimeout(() => {
            mainWindow.loadURL(VEIL_URL);
        }, 1500);
    });

    // Handle external links — open in system browser
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (!url.startsWith('https://gositeme.com')) {
            shell.openExternal(url);
            return { action: 'deny' };
        }
        return { action: 'allow' };
    });

    // Navigation guard
    mainWindow.webContents.on('will-navigate', (event, url) => {
        try {
            const parsed = new URL(url);
            if (parsed.hostname !== 'gositeme.com' && parsed.hostname !== 'www.gositeme.com') {
                event.preventDefault();
                shell.openExternal(url);
            }
        } catch (e) {
            // Invalid URL — ignore
        }
    });

    // Inject desktop bridge on every page load + remove Electron traces
    mainWindow.webContents.on('did-finish-load', () => {
        mainWindow.webContents.executeJavaScript(`
            window.isVeilBrowser = true;
            window.veilVersion = '${APP_VERSION}';
            window.veilPlatform = 'desktop';
            // Remove any Electron/GitHub fingerprints from navigator
            if (navigator.userAgent.includes('Electron')) {
                Object.defineProperty(navigator, 'userAgent', {
                    get: () => navigator.userAgent.replace(/Electron\\/[\\d.]+\\s*/g, '') + ' VeilBrowser/${APP_VERSION}'
                });
            }
            console.log('[Veil Browser] Desktop bridge active — v${APP_VERSION}');
        `);
    });

    // Override window title to never show "Electron" or defaults
    mainWindow.on('page-title-updated', (event, title) => {
        if (title.toLowerCase().includes('electron') || title.toLowerCase().includes('github')) {
            event.preventDefault();
            mainWindow.setTitle(APP_NAME);
        }
    });

    // Handle loading errors — show splash and retry
    mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
        if (errorCode !== -3) {
            mainWindow.loadFile('splash.html');
            setTimeout(() => mainWindow.loadURL(VEIL_URL), 5000);
        }
    });

    // Close to tray instead of quitting
    mainWindow.on('close', (event) => {
        if (!isQuitting) {
            event.preventDefault();
            mainWindow.hide();
        }
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });

    // ═══════════════════════════════════════════════════════════════
    // APP MENU — Veil Browser 3.0
    // ═══════════════════════════════════════════════════════════════
    const menuTemplate = [
        {
            label: 'Veil',
            submenu: [
                { label: 'Home', accelerator: 'CmdOrCtrl+Shift+H', click: () => mainWindow.loadURL(VEIL_URL) },
                { label: 'Search', accelerator: 'CmdOrCtrl+L', click: () => mainWindow.loadURL('https://gositeme.com/search') },
                { type: 'separator' },
                { label: 'Dashboard', click: () => mainWindow.loadURL('https://gositeme.com/dashboard') },
                { label: 'Command Center', click: () => mainWindow.loadURL('https://gositeme.com/command-center') },
                { label: 'Fleet Dashboard', click: () => mainWindow.loadURL('https://gositeme.com/fleet-dashboard') },
                { type: 'separator' },
                { label: 'Check for Updates', click: () => autoUpdater.checkForUpdates() },
                { type: 'separator' },
                { label: 'Reload', accelerator: 'CmdOrCtrl+R', click: () => mainWindow.reload() },
                { role: 'quit', label: 'Exit Veil' }
            ]
        },
        {
            label: 'Bookmarks',
            submenu: [
                { label: 'Bookmark This Page', accelerator: 'CmdOrCtrl+D', click: () => {
                    if (mainWindow) {
                        const url = mainWindow.webContents.getURL();
                        const title = mainWindow.webContents.getTitle();
                        mainWindow.webContents.send('show-bookmark-dialog', { title, url });
                    }
                }},
                { label: 'Bookmark Manager', accelerator: 'CmdOrCtrl+B', click: () => {
                    if (mainWindow) mainWindow.loadFile('bookmarks.html');
                }},
                { type: 'separator' },
                { label: 'Import Bookmarks...', click: () => {
                    dialog.showOpenDialog(mainWindow, {
                        filters: [{ name: 'JSON', extensions: ['json'] }],
                        properties: ['openFile']
                    }).then(({ canceled, filePaths }) => {
                        if (!canceled && filePaths[0]) {
                            const data = fs.readFileSync(filePaths[0], 'utf8');
                            try {
                                const imported = JSON.parse(data);
                                if (imported.toolbar && imported.folders) {
                                    saveBookmarks(imported);
                                    dialog.showMessageBox(mainWindow, { type: 'info', title: 'Import Complete', message: 'Bookmarks imported successfully.' });
                                }
                            } catch { dialog.showMessageBox(mainWindow, { type: 'error', title: 'Import Failed', message: 'Invalid bookmark file.' }); }
                        }
                    });
                }},
                { label: 'Export Bookmarks...', click: () => {
                    dialog.showSaveDialog(mainWindow, {
                        defaultPath: 'veil-bookmarks.json',
                        filters: [{ name: 'JSON', extensions: ['json'] }]
                    }).then(({ canceled, filePath }) => {
                        if (!canceled && filePath) {
                            fs.writeFileSync(filePath, JSON.stringify(loadBookmarks(), null, 2), 'utf8');
                            dialog.showMessageBox(mainWindow, { type: 'info', title: 'Export Complete', message: 'Bookmarks exported successfully.' });
                        }
                    });
                }},
                { type: 'separator' },
                // Dynamic bookmarks from toolbar will be added below
            ]
        },
        {
            label: 'Tools',
            submenu: [
                { label: 'Alfred AI Chat', accelerator: 'CmdOrCtrl+Shift+A', click: () => mainWindow.loadURL('https://gositeme.com/alfred') },
                { label: 'Mining & Wallet', click: () => mainWindow.loadURL('https://gositeme.com/wallet') },
                { label: 'Team Chat', click: () => mainWindow.loadURL('https://gositeme.com/team-chat') },
                { type: 'separator' },
                { label: 'Marketplace', click: () => mainWindow.loadURL('https://gositeme.com/marketplace') },
                { label: 'Extensions', click: () => mainWindow.loadURL('https://gositeme.com/extensions') },
                { type: 'separator' },
                { label: 'GoCodeMe IDE', click: () => shell.openExternal('https://gositeme.com/gocodeme') },
                { label: 'Developer Portal', click: () => mainWindow.loadURL('https://gositeme.com/developer-portal') },
                { type: 'separator' },
                { label: 'Developer Tools', accelerator: 'F12', click: () => mainWindow.webContents.toggleDevTools() },
            ]
        },
        {
            label: 'Apps',
            submenu: [
                { label: 'Games & VR', click: () => mainWindow.loadURL('https://gositeme.com/games') },
                { label: 'Chess Masters', click: () => mainWindow.loadURL('https://gositeme.com/vr/chess-ultimate/') },
                { label: 'VR Metaverse', click: () => mainWindow.loadURL('https://gositeme.com/vr/hub/') },
                { type: 'separator' },
                { label: 'Pulse Network', click: () => mainWindow.loadURL('https://gositeme.com/pulse') },
                { label: 'Video Conference', click: () => mainWindow.loadURL('https://gositeme.com/conference-room') },
                { type: 'separator' },
                { label: 'Veil Protocol', click: () => mainWindow.loadURL('https://gositeme.com/veil/') },
                { label: 'Post-Quantum', click: () => mainWindow.loadURL('https://gositeme.com/post-quantum') },
                { label: 'Emergency Kit', click: () => mainWindow.loadURL('https://gositeme.com/veil/emergency-kit') },
            ]
        },
        {
            label: 'Account',
            submenu: [
                { label: 'Billing & Domains', click: () => shell.openExternal('https://gositeme.com/pay/') },
                { label: 'Security', click: () => mainWindow.loadURL('https://gositeme.com/security') },
                { label: 'Integrations', click: () => mainWindow.loadURL('https://gositeme.com/integrations') },
                { type: 'separator' },
                { label: 'Help & Support', click: () => mainWindow.loadURL('https://gositeme.com/help') },
                { label: 'Status', click: () => mainWindow.loadURL('https://gositeme.com/status') },
            ]
        },
        {
            label: 'Window',
            submenu: [
                { role: 'minimize' },
                { role: 'togglefullscreen' },
                { role: 'close' },
            ]
        },
        {
            label: 'Help',
            submenu: [
                {
                    label: 'About Veil Browser',
                    click: () => {
                        dialog.showMessageBox(mainWindow, {
                            type: 'info',
                            title: 'About Veil Browser',
                            message: 'Veil Browser',
                            detail: `Version ${APP_VERSION}\n\nSovereign AI Browser by GoSiteMe Inc.\nEncrypted. Private. Yours.\n\n\u00A9 2026 GoSiteMe Inc.\nhttps://gositeme.com`,
                            icon: nativeImage.createFromPath(path.join(__dirname, 'build', 'icon.png')),
                            buttons: ['OK']
                        });
                    }
                },
                { type: 'separator' },
                { label: 'GoSiteMe Website', click: () => shell.openExternal('https://gositeme.com') },
                { label: 'Help & Support', click: () => mainWindow.loadURL('https://gositeme.com/help') },
                { label: 'Report a Bug', click: () => mainWindow.loadURL('https://gositeme.com/contact') },
            ]
        }
    ];

    // On macOS, override the default app menu name and about item
    if (process.platform === 'darwin') {
        menuTemplate.unshift({
            label: app.name,
            submenu: [
                {
                    label: 'About Veil Browser',
                    click: () => {
                        dialog.showMessageBox(mainWindow, {
                            type: 'info',
                            title: 'About Veil Browser',
                            message: 'Veil Browser',
                            detail: `Version ${APP_VERSION}\n\nSovereign AI Browser by GoSiteMe Inc.\nEncrypted. Private. Yours.\n\n\u00A9 2026 GoSiteMe Inc.\nhttps://gositeme.com`,
                            icon: nativeImage.createFromPath(path.join(__dirname, 'build', 'icon.png')),
                            buttons: ['OK']
                        });
                    }
                },
                { type: 'separator' },
                { role: 'services' },
                { type: 'separator' },
                { role: 'hide' },
                { role: 'hideOthers' },
                { role: 'unhide' },
                { type: 'separator' },
                { role: 'quit', label: 'Quit Veil Browser' }
            ]
        });
    }

    Menu.setApplicationMenu(Menu.buildFromTemplate(menuTemplate));
}

// ═══════════════════════════════════════════════════════════════
// SYSTEM TRAY
// ═══════════════════════════════════════════════════════════════
function createTray() {
    try {
        const iconPath = path.join(__dirname, 'build', 'icon.png');
        tray = new Tray(nativeImage.createFromPath(iconPath).resize({ width: 16, height: 16 }));
    } catch (e) {
        return;
    }

    const contextMenu = Menu.buildFromTemplate([
        { label: 'Open Veil Browser', click: () => { mainWindow.show(); mainWindow.focus(); } },
        { type: 'separator' },
        { label: 'Home', click: () => { mainWindow.show(); mainWindow.loadURL(VEIL_URL); } },
        { label: 'Search', click: () => { mainWindow.show(); mainWindow.loadURL('https://gositeme.com/search'); } },
        { label: 'Alfred AI', click: () => { mainWindow.show(); mainWindow.loadURL('https://gositeme.com/alfred'); } },
        { label: 'Dashboard', click: () => { mainWindow.show(); mainWindow.loadURL('https://gositeme.com/dashboard'); } },
        { label: 'Mining & Wallet', click: () => { mainWindow.show(); mainWindow.loadURL('https://gositeme.com/wallet'); } },
        { type: 'separator' },
        { label: 'Check for Updates', click: () => autoUpdater.checkForUpdates() },
        { type: 'separator' },
        { label: 'Quit', click: () => { isQuitting = true; app.quit(); } },
    ]);

    tray.setToolTip(`${APP_NAME} v${APP_VERSION}`);
    tray.setContextMenu(contextMenu);

    tray.on('click', () => {
        if (mainWindow) {
            mainWindow.show();
            mainWindow.focus();
        }
    });
}

// ═══════════════════════════════════════════════════════════════
// APP LIFECYCLE
// ═══════════════════════════════════════════════════════════════
app.on('ready', () => {
    // ═══════════════════════════════════════════════════════════════
    // REMOVE ALL ELECTRON/GITHUB/CHROMIUM BRANDING
    // ═══════════════════════════════════════════════════════════════
    app.setAboutPanelOptions({
        applicationName: 'Veil Browser',
        applicationVersion: APP_VERSION,
        copyright: 'Copyright \u00A9 2026 GoSiteMe Inc.',
        version: APP_VERSION,
        credits: 'Built by GoSiteMe Inc.\nSovereign AI Browser Platform',
        authors: ['GoSiteMe Inc.'],
        website: 'https://gositeme.com',
        iconPath: path.join(__dirname, 'build', 'icon.png')
    });

    // Replace the user agent entirely — strip Electron/Chrome references
    session.defaultSession.webRequest.onBeforeSendHeaders((details, callback) => {
        const ua = details.requestHeaders['User-Agent'] || '';
        details.requestHeaders['User-Agent'] = ua
            .replace(/Electron\/[\d.]+\s*/g, '')
            .replace(/\s+/g, ' ')
            .trim() + ` VeilBrowser/${APP_VERSION}`;
        callback({ cancel: false, requestHeaders: details.requestHeaders });
    });

    createWindow();
    createTray();

    // Check for updates 5 seconds after launch (non-blocking)
    setTimeout(() => {
        autoUpdater.checkForUpdates().catch(() => {});
    }, 5000);
});

app.on('before-quit', () => {
    isQuitting = true;
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (mainWindow === null) {
        createWindow();
    } else {
        mainWindow.show();
    }
});
