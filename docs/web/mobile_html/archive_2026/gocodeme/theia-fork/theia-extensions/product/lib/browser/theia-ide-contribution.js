"use strict";
/********************************************************************************
 * Copyright (C) 2021 Ericsson and others.
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
var TheiaIDEContribution_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaIDEContribution = exports.TheiaIDECommands = exports.TheiaIDEMenus = void 0;
const inversify_1 = require("@theia/core/shared/inversify");
const common_frontend_contribution_1 = require("@theia/core/lib/browser/common-frontend-contribution");
const window_service_1 = require("@theia/core/lib/browser/window/window-service");
var TheiaIDEMenus;
(function (TheiaIDEMenus) {
    TheiaIDEMenus.THEIA_IDE_HELP = [...common_frontend_contribution_1.CommonMenus.HELP, 'theia-ide'];
})(TheiaIDEMenus = exports.TheiaIDEMenus || (exports.TheiaIDEMenus = {}));
var TheiaIDECommands;
(function (TheiaIDECommands) {
    TheiaIDECommands.CATEGORY = 'TheiaIDE';
    TheiaIDECommands.REPORT_ISSUE = {
        id: 'theia-ide:report-issue',
        category: TheiaIDECommands.CATEGORY,
        label: 'Report Issue'
    };
    TheiaIDECommands.DOCUMENTATION = {
        id: 'theia-ide:documentation',
        category: TheiaIDECommands.CATEGORY,
        label: 'Documentation'
    };
})(TheiaIDECommands = exports.TheiaIDECommands || (exports.TheiaIDECommands = {}));
let TheiaIDEContribution = TheiaIDEContribution_1 = class TheiaIDEContribution {
    registerCommands(commandRegistry) {
        commandRegistry.registerCommand(TheiaIDECommands.REPORT_ISSUE, {
            execute: () => this.windowService.openNewWindow(TheiaIDEContribution_1.REPORT_ISSUE_URL, { external: true })
        });
        commandRegistry.registerCommand(TheiaIDECommands.DOCUMENTATION, {
            execute: () => this.windowService.openNewWindow(TheiaIDEContribution_1.DOCUMENTATION_URL, { external: true })
        });
    }
    registerMenus(menus) {
        menus.registerMenuAction(TheiaIDEMenus.THEIA_IDE_HELP, {
            commandId: TheiaIDECommands.REPORT_ISSUE.id,
            label: TheiaIDECommands.REPORT_ISSUE.label,
            order: '1'
        });
        menus.registerMenuAction(TheiaIDEMenus.THEIA_IDE_HELP, {
            commandId: TheiaIDECommands.DOCUMENTATION.id,
            label: TheiaIDECommands.DOCUMENTATION.label,
            order: '2'
        });
    }
};
TheiaIDEContribution.REPORT_ISSUE_URL = 'https://gositeme.com/whmcs/submitticket.php';
TheiaIDEContribution.DOCUMENTATION_URL = 'https://gositeme.com/whmcs/knowledgebase';
__decorate([
    (0, inversify_1.inject)(window_service_1.WindowService),
    __metadata("design:type", Object)
], TheiaIDEContribution.prototype, "windowService", void 0);
TheiaIDEContribution = TheiaIDEContribution_1 = __decorate([
    (0, inversify_1.injectable)()
], TheiaIDEContribution);
exports.TheiaIDEContribution = TheiaIDEContribution;
//# sourceMappingURL=theia-ide-contribution.js.map