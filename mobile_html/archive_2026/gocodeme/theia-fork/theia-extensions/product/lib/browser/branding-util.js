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
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.renderDownloads = exports.renderCollaboration = exports.renderDocumentation = exports.renderSourceCode = exports.renderTickets = exports.renderSupport = exports.renderExtendingCustomizing = exports.renderWhatIs = exports.renderProductName = void 0;
const React = __importStar(require("react"));
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
//# sourceMappingURL=branding-util.js.map