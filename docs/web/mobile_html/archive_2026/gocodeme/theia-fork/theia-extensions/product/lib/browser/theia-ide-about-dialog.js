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
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaIDEAboutDialog = void 0;
const React = __importStar(require("react"));
const about_dialog_1 = require("@theia/core/lib/browser/about-dialog");
const inversify_1 = require("@theia/core/shared/inversify");
const branding_util_1 = require("./branding-util");
const vsx_environment_1 = require("@theia/vsx-registry/lib/common/vsx-environment");
const window_service_1 = require("@theia/core/lib/browser/window/window-service");
let TheiaIDEAboutDialog = class TheiaIDEAboutDialog extends about_dialog_1.AboutDialog {
    constructor(props) {
        super(props);
        this.props = props;
    }
    async doInit() {
        this.vscodeApiVersion = await this.environment.getVscodeApiVersion();
        super.doInit();
    }
    render() {
        return React.createElement("div", { className: about_dialog_1.ABOUT_CONTENT_CLASS }, this.renderContent());
    }
    renderContent() {
        return React.createElement("div", { className: 'ad-container' },
            React.createElement("div", { className: 'ad-float' },
                React.createElement("div", { className: 'ad-logo' }),
                this.renderExtensions()),
            this.renderTitle(),
            React.createElement("hr", { className: 'gs-hr' }),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderWhatIs)(this.windowService))),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderSupport)(this.windowService))),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderTickets)(this.windowService))),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderSourceCode)(this.windowService))),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderDocumentation)(this.windowService))),
            React.createElement("div", { className: 'flex-grid' },
                React.createElement("div", { className: 'col' }, (0, branding_util_1.renderDownloads)())));
    }
    renderTitle() {
        return React.createElement("div", { className: 'gs-header' },
            (0, branding_util_1.renderProductName)(),
            this.renderVersion());
    }
    renderExtensions() {
        const extensionsInfos = this.extensionsInfos || [];
        const cleaned = extensionsInfos
            .filter((ext) => !ext.name.includes('product-ext'))
            .map((ext) => ({
            name: ext.name
                .replace(/^@theia\/ai-/, 'GoCodeMe AI: ')
                .replace(/^@theia\//, 'GoCodeMe: '),
            version: ext.version
        }));
        return React.createElement(React.Fragment, null,
            React.createElement("h3", null, "Components"),
            React.createElement("ul", { className: 'about-extensions' }, cleaned
                .sort((a, b) => a.name.toLowerCase().localeCompare(b.name.toLowerCase()))
                .map((ext) => React.createElement("li", { key: ext.name },
                ext.name,
                " ",
                ext.version))));
    }
    renderVersion() {
        return React.createElement("div", null,
            React.createElement("p", { className: 'gs-sub-header' }, this.applicationInfo ? 'Version ' + this.applicationInfo.version : '-'),
            React.createElement("p", { className: 'gs-sub-header' }, 'API Version: ' + this.vscodeApiVersion));
    }
};
__decorate([
    (0, inversify_1.inject)(vsx_environment_1.VSXEnvironment),
    __metadata("design:type", Object)
], TheiaIDEAboutDialog.prototype, "environment", void 0);
__decorate([
    (0, inversify_1.inject)(window_service_1.WindowService),
    __metadata("design:type", Object)
], TheiaIDEAboutDialog.prototype, "windowService", void 0);
TheiaIDEAboutDialog = __decorate([
    (0, inversify_1.injectable)(),
    __param(0, (0, inversify_1.inject)(about_dialog_1.AboutDialogProps)),
    __metadata("design:paramtypes", [about_dialog_1.AboutDialogProps])
], TheiaIDEAboutDialog);
exports.TheiaIDEAboutDialog = TheiaIDEAboutDialog;
//# sourceMappingURL=theia-ide-about-dialog.js.map