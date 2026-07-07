"use strict";
/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
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
exports.TheiaIDEGettingStartedWidget = void 0;
const React = __importStar(require("react"));
const common_1 = require("@theia/core/lib/common");
const inversify_1 = require("@theia/core/shared/inversify");
const branding_util_1 = require("./branding-util");
const getting_started_widget_1 = require("@theia/getting-started/lib/browser/getting-started-widget");
const vsx_environment_1 = require("@theia/vsx-registry/lib/common/vsx-environment");
const window_service_1 = require("@theia/core/lib/browser/window/window-service");
let TheiaIDEGettingStartedWidget = class TheiaIDEGettingStartedWidget extends getting_started_widget_1.GettingStartedWidget {
    async doInit() {
        super.doInit();
        this.vscodeApiVersion = await this.environment.getVscodeApiVersion();
        await this.preferenceService.ready;
        this.update();
    }
    onActivateRequest(msg) {
        super.onActivateRequest(msg);
        const htmlElement = document.getElementById('alwaysShowWelcomePage');
        if (htmlElement) {
            htmlElement.focus();
        }
    }
    render() {
        return React.createElement("div", { className: 'gs-container' },
            React.createElement("div", { className: 'gs-content-container' },
                React.createElement("div", { className: 'gs-float' },
                    React.createElement("div", { className: 'gs-logo' }),
                    this.renderActions()),
                this.renderHeader(),
                React.createElement("hr", { className: 'gs-hr' }),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, this.renderNews())),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderWhatIs)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderExtendingCustomizing)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderSupport)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderTickets)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderSourceCode)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderDocumentation)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, this.renderAIBanner())),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderCollaboration)(this.windowService))),
                React.createElement("div", { className: 'flex-grid' },
                    React.createElement("div", { className: 'col' }, (0, branding_util_1.renderDownloads)()))),
            React.createElement("div", { className: 'gs-preference-container' }, this.renderPreferences()));
    }
    renderActions() {
        return React.createElement("div", { className: 'gs-container' },
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, this.renderStart())),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, this.renderRecentWorkspaces())),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, this.renderSettings())),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, this.renderHelp())));
    }
    renderHelp() {
        return React.createElement("div", { className: 'gs-section' },
            React.createElement("h3", { className: 'gs-section-header' },
                React.createElement("i", { className: 'codicon codicon-question' }),
                "Help"),
            React.createElement("div", { className: 'gs-action-container' },
                React.createElement("a", { role: 'button', tabIndex: 0, onClick: () => this.doOpenExternalLink('https://gositeme.com/whmcs/knowledgebase') }, "Documentation")),
            React.createElement("div", { className: 'gs-action-container' },
                React.createElement("a", { role: 'button', tabIndex: 0, onClick: () => this.doOpenExternalLink('https://gositeme.com/whmcs/submitticket.php') }, "Submit a Support Ticket")));
    }
    renderNews() {
        return React.createElement("div", { className: 'gs-section' },
            React.createElement("h3", { className: 'gs-section-header' }, '🚀 AI Support in GoCodeMe is available! ✨'),
            React.createElement("div", { className: 'gs-action-container' },
                React.createElement("a", { role: 'button', style: { fontSize: 'var(--theia-ui-font-size2)' }, tabIndex: 0, onClick: () => this.doOpenAIChatView() }, "Open the AI Chat View now to get started! \u2728")));
    }
    renderHeader() {
        return React.createElement("div", { className: 'gs-header' },
            (0, branding_util_1.renderProductName)(),
            this.renderVersion());
    }
    renderVersion() {
        return React.createElement("div", null,
            React.createElement("p", { className: 'gs-sub-header' }, this.applicationInfo ? 'Version ' + this.applicationInfo.version : '-'),
            React.createElement("p", { className: 'gs-sub-header' }, 'API Version: ' + this.vscodeApiVersion));
    }
    renderAIBanner() {
        const framework = super.renderAIBanner();
        if (React.isValidElement(framework)) {
            return React.cloneElement(framework, { className: 'gs-section' });
        }
        return framework;
    }
};
__decorate([
    (0, inversify_1.inject)(vsx_environment_1.VSXEnvironment),
    __metadata("design:type", Object)
], TheiaIDEGettingStartedWidget.prototype, "environment", void 0);
__decorate([
    (0, inversify_1.inject)(window_service_1.WindowService),
    __metadata("design:type", Object)
], TheiaIDEGettingStartedWidget.prototype, "windowService", void 0);
__decorate([
    (0, inversify_1.inject)(common_1.PreferenceService),
    __metadata("design:type", Object)
], TheiaIDEGettingStartedWidget.prototype, "preferenceService", void 0);
TheiaIDEGettingStartedWidget = __decorate([
    (0, inversify_1.injectable)()
], TheiaIDEGettingStartedWidget);
exports.TheiaIDEGettingStartedWidget = TheiaIDEGettingStartedWidget;
//# sourceMappingURL=theia-ide-getting-started-widget.js.map