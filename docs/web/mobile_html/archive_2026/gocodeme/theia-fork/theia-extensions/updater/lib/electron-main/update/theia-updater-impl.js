"use strict";
/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaUpdaterImpl = void 0;
const fs = __importStar(require("fs-extra"));
const http = __importStar(require("http"));
const os = __importStar(require("os"));
const path = __importStar(require("path"));
const inversify_1 = require("@theia/core/shared/inversify");
const core_1 = require("@theia/core");
const builder_util_runtime_1 = require("builder-util-runtime");
const STABLE_CHANNEL_WINDOWS = 'https://download.eclipse.org/theia/ide/version/windows';
const STABLE_CHANNEL_MACOS = 'https://download.eclipse.org/theia/ide/latest/macos';
const STABLE_CHANNEL_MACOS_ARM = 'https://download.eclipse.org/theia/ide/latest/macos-arm';
const STABLE_CHANNEL_LINUX = 'https://download.eclipse.org/theia/ide/latest/linux';
const PREVIEW_CHANNEL_WINDOWS = 'https://download.eclipse.org/theia/ide-preview/version/windows';
const PREVIEW_CHANNEL_MACOS = 'https://download.eclipse.org/theia/ide-preview/latest/macos';
const PREVIEW_CHANNEL_MACOS_ARM = 'https://download.eclipse.org/theia/ide-preview/latest/macos-arm';
const PREVIEW_CHANNEL_LINUX = 'https://download.eclipse.org/theia/ide-preview/latest/linux';
// Next updates are currently only available for Linux.
// The feed is served from GitHub Release assets (rolling "next" tag).
const NEXT_CHANNEL_LINUX = 'https://github.com/eclipse-theia/theia-ide/releases/download/next';
const { autoUpdater } = require('electron-updater');
autoUpdater.logger = require('electron-log');
autoUpdater.logger.transports.file.level = 'info';
let TheiaUpdaterImpl = class TheiaUpdaterImpl {
    constructor() {
        this.clients = [];
        this.settings = {
            checkForUpdates: true,
            checkInterval: 60,
            channel: 'stable'
        };
        this.initialCheck = true;
        this.reportOnFirstRegistration = false;
        this.cancellationToken = new builder_util_runtime_1.CancellationToken();
        autoUpdater.autoDownload = false;
        autoUpdater.on('update-available', (info) => {
            if (this.initialCheck) {
                this.initialCheck = false;
                if (this.clients.length === 0) {
                    this.reportOnFirstRegistration = true;
                }
            }
            const updateInfo = { version: info.version };
            this.clients.forEach(c => c.updateAvailable(true, updateInfo));
        });
        autoUpdater.on('update-not-available', () => {
            if (this.initialCheck) {
                this.initialCheck = false;
                return;
            }
            this.clients.forEach(c => c.updateAvailable(false));
        });
        autoUpdater.on('update-downloaded', () => {
            this.clients.forEach(c => c.notifyReadyToInstall());
        });
        autoUpdater.on('error', (err) => {
            if (err instanceof Error && err.message.includes('cancelled')) {
                return;
            }
            const errorLogPath = autoUpdater.logger.transports.file.getFile().path;
            this.clients.forEach(c => c.reportError({ message: 'An error has occurred while attempting to update.', errorLogPath }));
        });
    }
    checkForUpdates() {
        const feedURL = this.getFeedURL(this.settings.channel);
        autoUpdater.setFeedURL(feedURL);
        autoUpdater.checkForUpdates();
    }
    setUpdaterSettings(settings) {
        const settingsChanged = this.settings.checkForUpdates !== settings.checkForUpdates ||
            this.settings.checkInterval !== settings.checkInterval ||
            this.settings.channel !== settings.channel;
        this.settings = settings;
        if (settingsChanged) {
            this.scheduleUpdateChecks();
        }
    }
    onRestartToUpdateRequested() {
        autoUpdater.quitAndInstall();
    }
    cancel() {
        autoUpdater.logger.info('Update cancelled by user');
        this.cancellationToken.cancel();
        this.clients.forEach(c => c.reportCancelled());
    }
    downloadUpdate() {
        autoUpdater.logger.info('Downloading update');
        this.cancellationToken = new builder_util_runtime_1.CancellationToken();
        autoUpdater.downloadUpdate(this.cancellationToken);
        // record download stat, ignore errors
        fs.mkdtemp(path.join(os.tmpdir(), 'updater-'))
            .then(tmpDir => {
            const file = fs.createWriteStream(path.join(tmpDir, 'update'));
            http.get('https://www.eclipse.org/downloads/download.php?file=/theia/update&r=1', response => {
                response.pipe(file);
                file.on('finish', () => {
                    file.close();
                });
            });
        });
    }
    onStart(application) {
    }
    onStop(application) {
        this.stopUpdateCheckTimer();
    }
    scheduleUpdateChecks() {
        this.stopUpdateCheckTimer();
        if (!this.settings.checkForUpdates) {
            return;
        }
        this.checkForUpdates();
        const intervalMs = Math.max(this.settings.checkInterval, 1) * 60 * 1000;
        this.updateCheckTimer = setInterval(() => {
            if (this.settings.checkForUpdates) {
                this.checkForUpdates();
            }
        }, intervalMs);
    }
    stopUpdateCheckTimer() {
        if (this.updateCheckTimer) {
            clearInterval(this.updateCheckTimer);
            this.updateCheckTimer = undefined;
        }
    }
    setClient(client) {
        if (client) {
            this.clients.push(client);
            if (this.reportOnFirstRegistration) {
                this.reportOnFirstRegistration = false;
                this.clients.forEach(c => c.updateAvailable(true));
            }
        }
    }
    getFeedURL(channel) {
        if (core_1.isWindows) {
            const curVersion = autoUpdater.currentVersion.toString();
            // Next not yet available on Windows, fall back to stable
            return (channel === 'preview') ? PREVIEW_CHANNEL_WINDOWS.replace('version', curVersion) : STABLE_CHANNEL_WINDOWS.replace('version', curVersion);
        }
        else if (core_1.isOSX) {
            // Next not yet available on macOS, fall back to stable
            if (process.arch === 'arm64') {
                return (channel === 'preview') ? PREVIEW_CHANNEL_MACOS_ARM : STABLE_CHANNEL_MACOS_ARM;
            }
            else {
                return (channel === 'preview') ? PREVIEW_CHANNEL_MACOS : STABLE_CHANNEL_MACOS;
            }
        }
        else {
            if (channel === 'next') {
                return NEXT_CHANNEL_LINUX;
            }
            return (channel === 'preview') ? PREVIEW_CHANNEL_LINUX : STABLE_CHANNEL_LINUX;
        }
    }
    disconnectClient(client) {
        const index = this.clients.indexOf(client);
        if (index !== -1) {
            this.clients.splice(index, 1);
        }
    }
    dispose() {
        this.stopUpdateCheckTimer();
        this.clients.forEach(this.disconnectClient.bind(this));
    }
};
TheiaUpdaterImpl = __decorate([
    (0, inversify_1.injectable)(),
    __metadata("design:paramtypes", [])
], TheiaUpdaterImpl);
exports.TheiaUpdaterImpl = TheiaUpdaterImpl;
//# sourceMappingURL=theia-updater-impl.js.map