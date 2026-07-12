"use strict";
/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaUpdaterFrontendContribution = exports.ElectronMenuUpdater = exports.TheiaUpdaterClientImpl = exports.TheiaUpdaterMenu = exports.TheiaUpdaterCommands = void 0;
const common_1 = require("@theia/core/lib/common");
const common_2 = require("@theia/core/lib/common");
const theia_updater_1 = require("../../common/updater/theia-updater");
const inversify_1 = require("@theia/core/shared/inversify");
const browser_1 = require("@theia/core/lib/browser");
const electron_main_menu_factory_1 = require("@theia/core/lib/electron-browser/menu/electron-main-menu-factory");
const uri_1 = __importDefault(require("@theia/core/lib/common/uri"));
const vscode_uri_1 = require("vscode-uri");
var TheiaUpdaterCommands;
(function (TheiaUpdaterCommands) {
    const category = 'Theia Electron Updater';
    TheiaUpdaterCommands.CHECK_FOR_UPDATES = {
        id: 'electron-theia:check-for-updates',
        label: 'Check for Updates...',
        category
    };
    TheiaUpdaterCommands.RESTART_TO_UPDATE = {
        id: 'electron-theia:restart-to-update',
        label: 'Restart to Update',
        category
    };
})(TheiaUpdaterCommands = exports.TheiaUpdaterCommands || (exports.TheiaUpdaterCommands = {}));
var TheiaUpdaterMenu;
(function (TheiaUpdaterMenu) {
    TheiaUpdaterMenu.MENU_PATH = [...browser_1.CommonMenus.FILE_SETTINGS_SUBMENU, '3_settings_submenu_update'];
})(TheiaUpdaterMenu = exports.TheiaUpdaterMenu || (exports.TheiaUpdaterMenu = {}));
let TheiaUpdaterClientImpl = class TheiaUpdaterClientImpl {
    constructor() {
        this.onReadyToInstallEmitter = new common_1.Emitter();
        this.onReadyToInstall = this.onReadyToInstallEmitter.event;
        this.onUpdateAvailableEmitter = new common_1.Emitter();
        this.onUpdateAvailable = this.onUpdateAvailableEmitter.event;
        this.onErrorEmitter = new common_1.Emitter();
        this.onError = this.onErrorEmitter.event;
        this.onCancelEmitter = new common_1.Emitter();
        this.onCancel = this.onCancelEmitter.event;
    }
    notifyReadyToInstall() {
        this.onReadyToInstallEmitter.fire();
    }
    updateAvailable(available, updateInfo) {
        this.onUpdateAvailableEmitter.fire({ available, updateInfo });
    }
    reportError(error) {
        this.onErrorEmitter.fire(error);
    }
    reportCancelled() {
        this.onCancelEmitter.fire();
    }
};
TheiaUpdaterClientImpl = __decorate([
    (0, inversify_1.injectable)()
], TheiaUpdaterClientImpl);
exports.TheiaUpdaterClientImpl = TheiaUpdaterClientImpl;
// Dynamic menus aren't yet supported by electron: https://github.com/eclipse-theia/theia/issues/446
let ElectronMenuUpdater = class ElectronMenuUpdater {
    update() {
        this.setMenu();
    }
    setMenu() {
        window.electronTheiaCore.setMenu(this.factory.createElectronMenuBar());
    }
};
__decorate([
    (0, inversify_1.inject)(electron_main_menu_factory_1.ElectronMainMenuFactory),
    __metadata("design:type", electron_main_menu_factory_1.ElectronMainMenuFactory)
], ElectronMenuUpdater.prototype, "factory", void 0);
ElectronMenuUpdater = __decorate([
    (0, inversify_1.injectable)()
], ElectronMenuUpdater);
exports.ElectronMenuUpdater = ElectronMenuUpdater;
let TheiaUpdaterFrontendContribution = class TheiaUpdaterFrontendContribution {
    constructor() {
        this.readyToUpdate = false;
    }
    init() {
        this.updaterClient.onUpdateAvailable(({ available, updateInfo }) => {
            if (available) {
                this.currentUpdateInfo = updateInfo;
                this.handleDownloadUpdate(updateInfo);
            }
            else {
                this.handleNoUpdate();
            }
        });
        this.updaterClient.onReadyToInstall(async () => {
            this.readyToUpdate = true;
            this.menuUpdater.update();
            this.handleUpdatesAvailable();
        });
        this.updaterClient.onError(error => this.handleError(error));
        this.updaterClient.onCancel(() => this.stopProgress());
        this.preferenceService.ready.then(() => {
            this.syncUpdaterSettings();
        });
        this.preferenceService.onPreferenceChanged(e => {
            if (e.preferenceName === 'updates.checkForUpdates' ||
                e.preferenceName === 'updates.checkInterval' ||
                e.preferenceName === 'updates.channel') {
                this.syncUpdaterSettings();
            }
        });
    }
    syncUpdaterSettings() {
        const settings = {
            checkForUpdates: this.preferenceService.get('updates.checkForUpdates', true),
            checkInterval: this.preferenceService.get('updates.checkInterval', 60),
            channel: this.preferenceService.get('updates.channel', 'stable')
        };
        this.updater.setUpdaterSettings(settings);
    }
    registerCommands(registry) {
        registry.registerCommand(TheiaUpdaterCommands.CHECK_FOR_UPDATES, {
            execute: async () => {
                this.updater.checkForUpdates();
            },
            isEnabled: () => !this.readyToUpdate,
            isVisible: () => !this.readyToUpdate
        });
        registry.registerCommand(TheiaUpdaterCommands.RESTART_TO_UPDATE, {
            execute: () => this.updater.onRestartToUpdateRequested(),
            isEnabled: () => this.readyToUpdate,
            isVisible: () => this.readyToUpdate
        });
    }
    registerMenus(registry) {
        registry.registerMenuAction(TheiaUpdaterMenu.MENU_PATH, {
            commandId: TheiaUpdaterCommands.CHECK_FOR_UPDATES.id
        });
        registry.registerMenuAction(TheiaUpdaterMenu.MENU_PATH, {
            commandId: TheiaUpdaterCommands.RESTART_TO_UPDATE.id
        });
    }
    async handleDownloadUpdate(updateInfo) {
        const message = updateInfo
            ? `Update to version ${updateInfo.version} found, do you want to update?`
            : 'Updates found, do you want to update?';
        const actions = ['Not now', 'Yes'];
        const checkForUpdates = this.preferenceService.get('updates.checkForUpdates', true);
        if (checkForUpdates) {
            actions.push('Never');
        }
        const answer = await this.messageService.info(message, ...actions);
        if (answer === 'Never') {
            this.preferenceService.set('updates.checkForUpdates', false, common_2.PreferenceScope.User);
            return;
        }
        if (answer === 'Yes') {
            this.stopProgress();
            this.progress = await this.messageService.showProgress({
                text: 'Theia IDE Update',
                options: { cancelable: true }
            }, () => this.updater.cancel());
            let dots = 0;
            this.intervalId = setInterval(() => {
                if (this.progress !== undefined) {
                    dots = (dots + 1) % 4;
                    this.progress.report({ message: 'Downloading' + '.'.repeat(dots) });
                }
            }, 1000);
            this.updater.downloadUpdate();
        }
    }
    async handleNoUpdate() {
        this.messageService.info('Already using the latest version');
    }
    async handleUpdatesAvailable() {
        if (this.progress !== undefined) {
            this.progress.report({ work: { done: 1, total: 1 } });
            this.stopProgress();
        }
        const message = this.currentUpdateInfo
            ? `An update to version ${this.currentUpdateInfo.version} has been downloaded and will be automatically installed on exit. Do you want to restart now?`
            : 'An update has been downloaded and will be automatically installed on exit. Do you want to restart now?';
        const answer = await this.messageService.info(message, 'No', 'Yes');
        if (answer === 'Yes') {
            this.updater.onRestartToUpdateRequested();
        }
    }
    async handleError(error) {
        this.stopProgress();
        if (error.errorLogPath) {
            const viewLogAction = 'View Error Log';
            const answer = await this.messageService.error(error.message, viewLogAction);
            if (answer === viewLogAction) {
                const uri = new uri_1.default(vscode_uri_1.URI.file(error.errorLogPath));
                const opener = await this.openerService.getOpener(uri);
                opener.open(uri);
            }
        }
        else {
            this.messageService.error(error.message);
        }
    }
    stopProgress() {
        if (this.intervalId !== undefined) {
            clearInterval(this.intervalId);
            this.intervalId = undefined;
        }
        if (this.progress !== undefined) {
            this.progress.cancel();
            this.progress = undefined;
        }
    }
};
__decorate([
    (0, inversify_1.inject)(common_1.MessageService),
    __metadata("design:type", common_1.MessageService)
], TheiaUpdaterFrontendContribution.prototype, "messageService", void 0);
__decorate([
    (0, inversify_1.inject)(ElectronMenuUpdater),
    __metadata("design:type", ElectronMenuUpdater)
], TheiaUpdaterFrontendContribution.prototype, "menuUpdater", void 0);
__decorate([
    (0, inversify_1.inject)(theia_updater_1.TheiaUpdater),
    __metadata("design:type", Object)
], TheiaUpdaterFrontendContribution.prototype, "updater", void 0);
__decorate([
    (0, inversify_1.inject)(TheiaUpdaterClientImpl),
    __metadata("design:type", TheiaUpdaterClientImpl)
], TheiaUpdaterFrontendContribution.prototype, "updaterClient", void 0);
__decorate([
    (0, inversify_1.inject)(common_2.PreferenceService),
    __metadata("design:type", Object)
], TheiaUpdaterFrontendContribution.prototype, "preferenceService", void 0);
__decorate([
    (0, inversify_1.inject)(browser_1.OpenerService),
    __metadata("design:type", Object)
], TheiaUpdaterFrontendContribution.prototype, "openerService", void 0);
__decorate([
    (0, inversify_1.postConstruct)(),
    __metadata("design:type", Function),
    __metadata("design:paramtypes", []),
    __metadata("design:returntype", void 0)
], TheiaUpdaterFrontendContribution.prototype, "init", null);
TheiaUpdaterFrontendContribution = __decorate([
    (0, inversify_1.injectable)()
], TheiaUpdaterFrontendContribution);
exports.TheiaUpdaterFrontendContribution = TheiaUpdaterFrontendContribution;
//# sourceMappingURL=theia-updater-frontend-contribution.js.map