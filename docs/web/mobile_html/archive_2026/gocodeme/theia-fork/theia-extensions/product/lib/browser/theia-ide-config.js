"use strict";
/********************************************************************************
 * Copyright (C) 2026 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", { value: true });
exports.applyBranding = exports.getBrandingVariant = void 0;
const frontend_application_config_provider_1 = require("@theia/core/lib/browser/frontend-application-config-provider");
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
//# sourceMappingURL=theia-ide-config.js.map