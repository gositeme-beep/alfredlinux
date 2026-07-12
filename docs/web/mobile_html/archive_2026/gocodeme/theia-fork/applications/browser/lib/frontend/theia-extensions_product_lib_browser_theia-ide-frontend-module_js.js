"use strict";
(self["webpackChunktheia_ide_browser_app"] = self["webpackChunktheia_ide_browser_app"] || []).push([["theia-extensions_product_lib_browser_theia-ide-frontend-module_js"],{

/***/ "../../node_modules/css-loader/dist/cjs.js!../../theia-extensions/product/src/browser/style/index.css"
/*!************************************************************************************************************!*\
  !*** ../../node_modules/css-loader/dist/cjs.js!../../theia-extensions/product/src/browser/style/index.css ***!
  \************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/sourceMaps.js */ "../../node_modules/css-loader/dist/runtime/sourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "../../node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/getUrl.js */ "../../node_modules/css-loader/dist/runtime/getUrl.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__);
// Imports



var ___CSS_LOADER_URL_IMPORT_0___ = new URL(/* asset import */ __webpack_require__(/*! ../icons/512-512.png */ "../../theia-extensions/product/src/browser/icons/512-512.png"), __webpack_require__.b);
var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_sourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
var ___CSS_LOADER_URL_REPLACEMENT_0___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_0___);
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/

:root {
    --theia-branding-logo: url(${___CSS_LOADER_URL_REPLACEMENT_0___});
}

.theia-icon {
    background-image: url(${___CSS_LOADER_URL_REPLACEMENT_0___});
    background-position: center;
    background-repeat: no-repeat;
    background-size: contain;
}

.gs-blue-header {
    color: #0ea5e9;
    text-transform: capitalize;
    font-weight: 600;
}

.gs-text-bold {
    font-weight: 600;
}

.gs-text-underline {
    text-decoration: underline;
}

.gs-float {
    float: right;
    padding-left: 20px;
}

.gs-logo {
    background-image: var(--theia-branding-logo);
    background-position: center center;
    background-repeat: no-repeat;
    background-size: contain;
    width: 250px;
    height: 118px;
    padding: 20px;
}

.ad-logo {
    background-image: var(--theia-branding-logo);
    background-position: center center;
    background-repeat: no-repeat;
    background-size: contain;
    width: 250px;
    height: 118px;
    padding: 20px;
}

.ad-float {
    float: right;
}

.ad-container {
    padding: 20px;
    width: 1150px;
    height: 700;
}

ul.theia-aboutExtensions {
    height: 450px;
    overflow: hidden;
    overflow-y: scroll;
    list-style-type: none;
    padding: 0;
    margin-left: 10px;
}`, "",{"version":3,"sources":["webpack://./../../theia-extensions/product/src/browser/style/index.css"],"names":[],"mappings":"AAAA;;;;;;;iFAOiF;;AAEjF;IACI,8DAAgD;AACpD;;AAEA;IACI,yDAA6C;IAC7C,2BAA2B;IAC3B,4BAA4B;IAC5B,wBAAwB;AAC5B;;AAEA;IACI,cAAc;IACd,0BAA0B;IAC1B,gBAAgB;AACpB;;AAEA;IACI,gBAAgB;AACpB;;AAEA;IACI,0BAA0B;AAC9B;;AAEA;IACI,YAAY;IACZ,kBAAkB;AACtB;;AAEA;IACI,4CAA4C;IAC5C,kCAAkC;IAClC,4BAA4B;IAC5B,wBAAwB;IACxB,YAAY;IACZ,aAAa;IACb,aAAa;AACjB;;AAEA;IACI,4CAA4C;IAC5C,kCAAkC;IAClC,4BAA4B;IAC5B,wBAAwB;IACxB,YAAY;IACZ,aAAa;IACb,aAAa;AACjB;;AAEA;IACI,YAAY;AAChB;;AAEA;IACI,aAAa;IACb,aAAa;IACb,WAAW;AACf;;AAEA;IACI,aAAa;IACb,gBAAgB;IAChB,kBAAkB;IAClB,qBAAqB;IACrB,UAAU;IACV,iBAAiB;AACrB","sourcesContent":["/********************************************************************************\n * Copyright (C) 2020 EclipseSource and others.\n *\n * This program and the accompanying materials are made available under the\n * terms of the MIT License, which is available in the project root.\n *\n * SPDX-License-Identifier: MIT\n ********************************************************************************/\n\n:root {\n    --theia-branding-logo: url(../icons/512-512.png);\n}\n\n.theia-icon {\n    background-image: url(\"../icons/512-512.png\");\n    background-position: center;\n    background-repeat: no-repeat;\n    background-size: contain;\n}\n\n.gs-blue-header {\n    color: #0ea5e9;\n    text-transform: capitalize;\n    font-weight: 600;\n}\n\n.gs-text-bold {\n    font-weight: 600;\n}\n\n.gs-text-underline {\n    text-decoration: underline;\n}\n\n.gs-float {\n    float: right;\n    padding-left: 20px;\n}\n\n.gs-logo {\n    background-image: var(--theia-branding-logo);\n    background-position: center center;\n    background-repeat: no-repeat;\n    background-size: contain;\n    width: 250px;\n    height: 118px;\n    padding: 20px;\n}\n\n.ad-logo {\n    background-image: var(--theia-branding-logo);\n    background-position: center center;\n    background-repeat: no-repeat;\n    background-size: contain;\n    width: 250px;\n    height: 118px;\n    padding: 20px;\n}\n\n.ad-float {\n    float: right;\n}\n\n.ad-container {\n    padding: 20px;\n    width: 1150px;\n    height: 700;\n}\n\nul.theia-aboutExtensions {\n    height: 450px;\n    overflow: hidden;\n    overflow-y: scroll;\n    list-style-type: none;\n    padding: 0;\n    margin-left: 10px;\n}"],"sourceRoot":""}]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "../../theia-extensions/product/lib/browser/branding-util.js"
/*!*******************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/branding-util.js ***!
  \*******************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


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
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.renderDownloads = exports.renderCollaboration = exports.renderDocumentation = exports.renderSourceCode = exports.renderTickets = exports.renderSupport = exports.renderExtendingCustomizing = exports.renderWhatIs = exports.renderProductName = void 0;
const React = __importStar(__webpack_require__(/*! react */ "../../node_modules/react/index.js"));
function renderProductName() {
    return React.createElement("h1", null,
        "GoCodeMe ",
        React.createElement("span", { className: "gs-blue-header" }, "IDE"));
}
exports.renderProductName = renderProductName;
function BrowserLink(props) {
    return React.createElement("a", { role: 'button', tabIndex: 0, href: props.url, target: '_blank' }, props.text);
}
function renderWhatIs(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "What is GoCodeMe?"),
        React.createElement("div", null, "GoCodeMe is a full AI coding platform \u2014 your browser-based IDE with an autonomous AI coding agent. Write code, get AI suggestions, or let the AI agent build entire features for you."),
        React.createElement("div", null,
            "Your files are live on your ",
            React.createElement(BrowserLink, { text: "GoSiteMe hosting", url: "https://gositeme.com", windowService: windowService }),
            " account. Changes you make here are instantly live on your domain \u2014 no deploy step needed."));
}
exports.renderWhatIs = renderWhatIs;
function renderExtendingCustomizing(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Extensions"),
        React.createElement("div", null,
            "You can extend GoCodeMe by installing VS Code extensions from the ",
            React.createElement(BrowserLink, { text: "OpenVSX registry", url: "https://open-vsx.org/", windowService: windowService }),
            ". Just open the extension view to browse and install."));
}
exports.renderExtendingCustomizing = renderExtendingCustomizing;
function renderSupport(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Support"),
        React.createElement("div", null,
            "Need help? Visit the ",
            React.createElement(BrowserLink, { text: "GoSiteMe support center", url: "https://gositeme.com/whmcs/submitticket.php", windowService: windowService }),
            " or call us 24/7."));
}
exports.renderSupport = renderSupport;
function renderTickets(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Feedback & Bug Reports"),
        React.createElement("div", null,
            "Found a bug or have a feature request? ",
            React.createElement(BrowserLink, { text: "Submit a support ticket", url: "https://gositeme.com/whmcs/submitticket.php", windowService: windowService }),
            " and our team will get back to you."));
}
exports.renderTickets = renderTickets;
function renderSourceCode(_windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Powered By"),
        React.createElement("div", null,
            React.createElement("a", { href: "https://gocodeme.com", target: "_blank", rel: "noopener noreferrer" }, "GoCodeMe"),
            " \u2014 AI-powered cloud IDE by GoSiteMe."));
}
exports.renderSourceCode = renderSourceCode;
function renderDocumentation(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Documentation"),
        React.createElement("div", null,
            "See the ",
            React.createElement(BrowserLink, { text: "GoCodeMe getting started guide", url: "https://gositeme.com/whmcs/knowledgebase", windowService: windowService }),
            " to learn how to use the IDE and AI agent."));
}
exports.renderDocumentation = renderDocumentation;
function renderCollaboration(windowService) {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Collaboration"),
        React.createElement("div", null,
            "The IDE features a built-in collaboration feature. You can share your workspace with others and work together in real-time by clicking on the ",
            React.createElement("i", null, "Collaborate"),
            " item in the status bar. The collaboration feature is powered by the ",
            React.createElement(BrowserLink, { text: "Open Collaboration Tools", url: "https://www.open-collab.tools/", windowService: windowService }),
            " project and uses their public server infrastructure."));
}
exports.renderCollaboration = renderCollaboration;
function renderDownloads() {
    return React.createElement("div", { className: 'gs-section' },
        React.createElement("h3", { className: 'gs-section-header' }, "Your Plan"),
        React.createElement("div", { className: 'gs-action-container' },
            "Manage your GoCodeMe subscription, token usage, and billing from your GoSiteMe client area at ",
            React.createElement("a", { href: "https://gositeme.com/whmcs/clientarea.php", target: "_blank" }, "gositeme.com"),
            "."));
}
exports.renderDownloads = renderDownloads;


/***/ },

/***/ "../../theia-extensions/product/lib/browser/gocodeme-chat-service.js"
/*!***************************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/gocodeme-chat-service.js ***!
  \***************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


/********************************************************************************
 * GoCodeMe: Always resolve a chat agent (Alfred / Architect / Universal fallbacks).
 * Fixes "No agent was found" when defaultChatAgent or @mention id does not match
 * bundled agent ids (patched vs unpatched Theia, or Universal disabled).
 ********************************************************************************/
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.GoCodeMeChatService = void 0;
const frontend_chat_service_1 = __webpack_require__(/*! @theia/ai-chat/lib/browser/frontend-chat-service */ "../../node_modules/@theia/ai-chat/lib/browser/frontend-chat-service.js");
const inversify_1 = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
let GoCodeMeChatService = class GoCodeMeChatService extends frontend_chat_service_1.FrontendChatServiceImpl {
    initialAgentSelection(parsedRequest) {
        var _a, _b;
        const agentPart = this.getMentionedAgent(parsedRequest);
        if (agentPart) {
            let agent = this.chatAgentService.getAgent(agentPart.agentId);
            if (!agent && String(agentPart.agentId).toLowerCase() === 'alfred') {
                agent = (_b = (_a = this.chatAgentService.getAgent('Alfred')) !== null && _a !== void 0 ? _a : this.chatAgentService.getAgent('Architect')) !== null && _b !== void 0 ? _b : this.chatAgentService.getAgents().find(a => a.name === 'Alfred');
            }
            return agent;
        }
        const fromSuper = super.initialAgentSelection(parsedRequest);
        if (fromSuper) {
            return fromSuper;
        }
        const tryIds = ['Alfred', 'Architect', 'Universal', 'Orchestrator', 'Coder'];
        for (const id of tryIds) {
            const a = this.chatAgentService.getAgent(id);
            if (a) {
                return a;
            }
        }
        const agents = this.chatAgentService.getAgents();
        return agents.length > 0 ? agents[0] : undefined;
    }
};
GoCodeMeChatService = __decorate([
    (0, inversify_1.injectable)()
], GoCodeMeChatService);
exports.GoCodeMeChatService = GoCodeMeChatService;


/***/ },

/***/ "../../theia-extensions/product/lib/browser/theia-ide-about-dialog.js"
/*!****************************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/theia-ide-about-dialog.js ***!
  \****************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


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
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.TheiaIDEAboutDialog = void 0;
const React = __importStar(__webpack_require__(/*! react */ "../../node_modules/react/index.js"));
const about_dialog_1 = __webpack_require__(/*! @theia/core/lib/browser/about-dialog */ "../../node_modules/@theia/core/lib/browser/about-dialog.js");
const inversify_1 = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
const branding_util_1 = __webpack_require__(/*! ./branding-util */ "../../theia-extensions/product/lib/browser/branding-util.js");
const vsx_environment_1 = __webpack_require__(/*! @theia/vsx-registry/lib/common/vsx-environment */ "../../node_modules/@theia/vsx-registry/lib/common/vsx-environment.js");
const window_service_1 = __webpack_require__(/*! @theia/core/lib/browser/window/window-service */ "../../node_modules/@theia/core/lib/browser/window/window-service.js");
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


/***/ },

/***/ "../../theia-extensions/product/lib/browser/theia-ide-config.js"
/*!**********************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/theia-ide-config.js ***!
  \**********************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


/********************************************************************************
 * Copyright (C) 2026 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.applyBranding = exports.getBrandingVariant = void 0;
const frontend_application_config_provider_1 = __webpack_require__(/*! @theia/core/lib/browser/frontend-application-config-provider */ "../../node_modules/@theia/core/lib/browser/frontend-application-config-provider.js");
function getBrandingVariant() {
    var _a;
    try {
        const config = frontend_application_config_provider_1.FrontendApplicationConfigProvider.get();
        return (_a = config['brandingVariant']) !== null && _a !== void 0 ? _a : 'stable';
    }
    catch (_b) {
        return 'stable';
    }
}
exports.getBrandingVariant = getBrandingVariant;
function applyBranding() {
    const variant = getBrandingVariant();
    if (variant !== 'stable') {
        document.body.setAttribute('data-theia-branding', variant);
    }
}
exports.applyBranding = applyBranding;


/***/ },

/***/ "../../theia-extensions/product/lib/browser/theia-ide-contribution.js"
/*!****************************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/theia-ide-contribution.js ***!
  \****************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


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
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.TheiaIDEContribution = exports.TheiaIDECommands = exports.TheiaIDEMenus = void 0;
const inversify_1 = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
const common_frontend_contribution_1 = __webpack_require__(/*! @theia/core/lib/browser/common-frontend-contribution */ "../../node_modules/@theia/core/lib/browser/common-frontend-contribution.js");
const window_service_1 = __webpack_require__(/*! @theia/core/lib/browser/window/window-service */ "../../node_modules/@theia/core/lib/browser/window/window-service.js");
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


/***/ },

/***/ "../../theia-extensions/product/lib/browser/theia-ide-frontend-module.js"
/*!*******************************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/theia-ide-frontend-module.js ***!
  \*******************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", ({ value: true }));
__webpack_require__(/*! ../../src/browser/style/index.css */ "../../theia-extensions/product/src/browser/style/index.css");
const browser_1 = __webpack_require__(/*! @theia/core/lib/browser */ "../../node_modules/@theia/core/lib/browser/index.js");
const about_dialog_1 = __webpack_require__(/*! @theia/core/lib/browser/about-dialog */ "../../node_modules/@theia/core/lib/browser/about-dialog.js");
const theia_ide_config_1 = __webpack_require__(/*! ./theia-ide-config */ "../../theia-extensions/product/lib/browser/theia-ide-config.js");
const command_1 = __webpack_require__(/*! @theia/core/lib/common/command */ "../../node_modules/@theia/core/lib/common/command.js");
const chat_service_1 = __webpack_require__(/*! @theia/ai-chat/lib/common/chat-service */ "../../node_modules/@theia/ai-chat/lib/common/chat-service.js");
const inversify_1 = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
const gocodeme_chat_service_1 = __webpack_require__(/*! ./gocodeme-chat-service */ "../../theia-extensions/product/lib/browser/gocodeme-chat-service.js");
const getting_started_widget_1 = __webpack_require__(/*! @theia/getting-started/lib/browser/getting-started-widget */ "../../node_modules/@theia/getting-started/lib/browser/getting-started-widget.js");
const menu_1 = __webpack_require__(/*! @theia/core/lib/common/menu */ "../../node_modules/@theia/core/lib/common/menu/index.js");
const theia_ide_about_dialog_1 = __webpack_require__(/*! ./theia-ide-about-dialog */ "../../theia-extensions/product/lib/browser/theia-ide-about-dialog.js");
const theia_ide_contribution_1 = __webpack_require__(/*! ./theia-ide-contribution */ "../../theia-extensions/product/lib/browser/theia-ide-contribution.js");
const theia_ide_getting_started_widget_1 = __webpack_require__(/*! ./theia-ide-getting-started-widget */ "../../theia-extensions/product/lib/browser/theia-ide-getting-started-widget.js");
exports["default"] = new inversify_1.ContainerModule((bind, _unbind, isBound, rebind) => {
    (0, theia_ide_config_1.applyBranding)();
    rebind(chat_service_1.ChatService).to(gocodeme_chat_service_1.GoCodeMeChatService).inSingletonScope();
    bind(theia_ide_getting_started_widget_1.TheiaIDEGettingStartedWidget).toSelf();
    bind(browser_1.WidgetFactory).toDynamicValue(context => ({
        id: getting_started_widget_1.GettingStartedWidget.ID,
        createWidget: () => context.container.get(theia_ide_getting_started_widget_1.TheiaIDEGettingStartedWidget),
    })).inSingletonScope();
    if (isBound(about_dialog_1.AboutDialog)) {
        rebind(about_dialog_1.AboutDialog).to(theia_ide_about_dialog_1.TheiaIDEAboutDialog).inSingletonScope();
    }
    else {
        bind(about_dialog_1.AboutDialog).to(theia_ide_about_dialog_1.TheiaIDEAboutDialog).inSingletonScope();
    }
    bind(theia_ide_contribution_1.TheiaIDEContribution).toSelf().inSingletonScope();
    [command_1.CommandContribution, menu_1.MenuContribution].forEach(serviceIdentifier => bind(serviceIdentifier).toService(theia_ide_contribution_1.TheiaIDEContribution));
});


/***/ },

/***/ "../../theia-extensions/product/lib/browser/theia-ide-getting-started-widget.js"
/*!**************************************************************************************!*\
  !*** ../../theia-extensions/product/lib/browser/theia-ide-getting-started-widget.js ***!
  \**************************************************************************************/
(__unused_webpack_module, exports, __webpack_require__) {


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
Object.defineProperty(exports, "__esModule", ({ value: true }));
exports.TheiaIDEGettingStartedWidget = void 0;
const React = __importStar(__webpack_require__(/*! react */ "../../node_modules/react/index.js"));
const common_1 = __webpack_require__(/*! @theia/core/lib/common */ "../../node_modules/@theia/core/lib/common/index.js");
const inversify_1 = __webpack_require__(/*! @theia/core/shared/inversify */ "../../node_modules/@theia/core/shared/inversify/index.js");
const branding_util_1 = __webpack_require__(/*! ./branding-util */ "../../theia-extensions/product/lib/browser/branding-util.js");
const getting_started_widget_1 = __webpack_require__(/*! @theia/getting-started/lib/browser/getting-started-widget */ "../../node_modules/@theia/getting-started/lib/browser/getting-started-widget.js");
const vsx_environment_1 = __webpack_require__(/*! @theia/vsx-registry/lib/common/vsx-environment */ "../../node_modules/@theia/vsx-registry/lib/common/vsx-environment.js");
const window_service_1 = __webpack_require__(/*! @theia/core/lib/browser/window/window-service */ "../../node_modules/@theia/core/lib/browser/window/window-service.js");
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


/***/ },

/***/ "../../theia-extensions/product/src/browser/style/index.css"
/*!******************************************************************!*\
  !*** ../../theia-extensions/product/src/browser/style/index.css ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_index_css__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!./index.css */ "../../node_modules/css-loader/dist/cjs.js!../../theia-extensions/product/src/browser/style/index.css");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_index_css__WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_index_css__WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ },

/***/ "../../theia-extensions/product/src/browser/icons/512-512.png"
/*!********************************************************************!*\
  !*** ../../theia-extensions/product/src/browser/icons/512-512.png ***!
  \********************************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

module.exports = __webpack_require__.p + "aad8bfe063fb0553d728..png";

/***/ }

}]);
//# sourceMappingURL=theia-extensions_product_lib_browser_theia-ide-frontend-module_js.js.map