const { contextBridge, ipcRenderer } = require('electron');

// Expose safe APIs to the renderer process — ZERO Electron/GitHub branding exposed
contextBridge.exposeInMainWorld('veilDesktop', {
    platform: process.platform,
    version: '3.0.0',
    appName: 'Veil Browser',
    publisher: 'GoSiteMe Inc.',
    isDesktopApp: true,
    isVeilBrowser: true,

    // App update controls
    checkForUpdates: () => ipcRenderer.send('check-for-updates'),
    onUpdateAvailable: (callback) => ipcRenderer.on('update-available', (_, info) => callback(info)),
    onUpdateDownloaded: (callback) => ipcRenderer.on('update-downloaded', (_, info) => callback(info)),
    onUpdateProgress: (callback) => ipcRenderer.on('update-progress', (_, progress) => callback(progress)),
    onUpdateError: (callback) => ipcRenderer.on('update-error', (_, err) => callback(err)),
    installUpdate: () => ipcRenderer.send('install-update'),

    // Navigation
    navigate: (path) => ipcRenderer.send('navigate', path),
    goBack: () => ipcRenderer.send('go-back'),
    goForward: () => ipcRenderer.send('go-forward'),
    reload: () => ipcRenderer.send('page-reload'),

    // Mining bridge
    getMiningStatus: () => ipcRenderer.invoke('get-mining-status'),
    setMiningStatus: (active) => ipcRenderer.send('set-mining-status', active),

    // Bookmarks
    bookmarks: {
        getAll: () => ipcRenderer.invoke('bookmarks-get-all'),
        add: (title, url, folder) => ipcRenderer.invoke('bookmarks-add', { title, url, folder }),
        remove: (id) => ipcRenderer.invoke('bookmarks-remove', id),
        edit: (id, title, url) => ipcRenderer.invoke('bookmarks-edit', { id, title, url }),
        move: (bookmarkId, targetFolder) => ipcRenderer.invoke('bookmarks-move', { bookmarkId, targetFolder }),
        createFolder: (name) => ipcRenderer.invoke('bookmarks-create-folder', name),
        removeFolder: (folderId) => ipcRenderer.invoke('bookmarks-remove-folder', folderId),
        renameFolder: (folderId, name) => ipcRenderer.invoke('bookmarks-rename-folder', { folderId, name }),
        exportJSON: () => ipcRenderer.invoke('bookmarks-export'),
        importJSON: (jsonStr) => ipcRenderer.invoke('bookmarks-import', jsonStr),
        getCurrentPage: () => ipcRenderer.invoke('bookmarks-get-current-page'),
        onShowDialog: (callback) => ipcRenderer.on('show-bookmark-dialog', (_, data) => callback(data)),
    },
});
