"use strict";
/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
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
Object.defineProperty(exports, "__esModule", { value: true });
exports.CreateLauncherCommandContribution = void 0;
const browser_1 = require("@theia/core/lib/browser");
const frontend_application_config_provider_1 = require("@theia/core/lib/browser/frontend-application-config-provider");
const common_1 = require("@theia/core/lib/common");
const nls_1 = require("@theia/core/lib/common/nls");
const inversify_1 = require("@theia/core/shared/inversify");
const launcher_service_1 = require("./launcher-service");
const desktopfile_service_1 = require("./desktopfile-service");
let CreateLauncherCommandContribution = class CreateLauncherCommandContribution {
    onStart(_app) {
        const appConfig = frontend_application_config_provider_1.FrontendApplicationConfigProvider.get();
        const applicationName = appConfig.applicationName;
        const brandingVariant = appConfig['brandingVariant'];
        const isNext = brandingVariant === 'next';
        // Only offer CLI launcher for standard (non-next) builds
        if (!isNext) {
            this.launcherService.isInitialized().then(async (initialized) => {
                if (!initialized) {
                    const messageContainer = document.createElement('div');
                    // eslint-disable-next-line max-len
                    messageContainer.textContent = nls_1.nls.localizeByDefault("Would you like to install a shell command that launches the application?\nYou will be able to run the Theia IDE from the command line by typing 'theia'.");
                    messageContainer.setAttribute('style', 'white-space: pre-line');
                    const details = document.createElement('p');
                    details.textContent = 'Administrator privileges are required, you will need to enter your password next.';
                    messageContainer.appendChild(details);
                    const dialog = new browser_1.ConfirmDialog({
                        title: nls_1.nls.localizeByDefault('Create launcher'),
                        msg: messageContainer,
                        ok: browser_1.Dialog.YES,
                        cancel: browser_1.Dialog.NO
                    });
                    const install = await dialog.open();
                    this.launcherService.createLauncher(!!install);
                    this.logger.info('Initialized application launcher.');
                }
                else {
                    this.logger.info('Application launcher was already initialized.');
                }
            });
        }
        this.desktopFileService.isInitialized().then(async (initialized) => {
            if (!initialized) {
                const messageContainer = document.createElement('div');
                // eslint-disable-next-line max-len
                messageContainer.textContent = nls_1.nls.localizeByDefault(`Would you like to create a .desktop file for ${applicationName}?\nThis will make it easier to open ${applicationName} directly\nfrom your applications menu and enables further features.`);
                messageContainer.setAttribute('style', 'white-space: pre-line');
                const dialog = new browser_1.ConfirmDialog({
                    title: nls_1.nls.localizeByDefault('Create .desktop file'),
                    msg: messageContainer,
                    ok: browser_1.Dialog.YES,
                    cancel: browser_1.Dialog.NO
                });
                const install = await dialog.open();
                this.desktopFileService.createOrUpdateDesktopfile(!!install, {
                    applicationName,
                    createUrlHandler: !isNext
                });
                this.logger.info('Created or updated .desktop file.');
            }
            else {
                this.logger.info('Desktop file was not updated or created.');
            }
        });
    }
};
__decorate([
    (0, inversify_1.inject)(browser_1.StorageService),
    __metadata("design:type", Object)
], CreateLauncherCommandContribution.prototype, "storageService", void 0);
__decorate([
    (0, inversify_1.inject)(common_1.ILogger),
    __metadata("design:type", Object)
], CreateLauncherCommandContribution.prototype, "logger", void 0);
__decorate([
    (0, inversify_1.inject)(launcher_service_1.LauncherService),
    __metadata("design:type", launcher_service_1.LauncherService)
], CreateLauncherCommandContribution.prototype, "launcherService", void 0);
__decorate([
    (0, inversify_1.inject)(desktopfile_service_1.DesktopFileService),
    __metadata("design:type", desktopfile_service_1.DesktopFileService)
], CreateLauncherCommandContribution.prototype, "desktopFileService", void 0);
CreateLauncherCommandContribution = __decorate([
    (0, inversify_1.injectable)()
], CreateLauncherCommandContribution);
exports.CreateLauncherCommandContribution = CreateLauncherCommandContribution;
//# sourceMappingURL=create-launcher-contribution.js.map