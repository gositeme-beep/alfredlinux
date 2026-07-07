"use strict";
/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var _a;
Object.defineProperty(exports, "__esModule", { value: true });
exports.theiaUpdaterPreferenceSchema = void 0;
const core_1 = require("@theia/core");
const frontend_application_config_provider_1 = require("@theia/core/lib/browser/frontend-application-config-provider");
const DEFAULT_UPDATE_CHANNELS = ['stable', 'preview'];
function getAvailableUpdateChannels() {
    var _a;
    try {
        const config = frontend_application_config_provider_1.FrontendApplicationConfigProvider.get();
        return (_a = config['availableUpdateChannels']) !== null && _a !== void 0 ? _a : DEFAULT_UPDATE_CHANNELS;
    }
    catch (_b) {
        return DEFAULT_UPDATE_CHANNELS;
    }
}
exports.theiaUpdaterPreferenceSchema = {
    'properties': {
        'updates.checkForUpdates': {
            type: 'boolean',
            description: 'Automatically check for updates.',
            default: true,
            scope: core_1.PreferenceScope.User
        },
        'updates.checkInterval': {
            type: 'number',
            description: 'Interval in minutes between automatic update checks.',
            default: 60,
            scope: core_1.PreferenceScope.User
        },
        'updates.channel': {
            type: 'string',
            enum: getAvailableUpdateChannels(),
            description: 'Channel to use for updates.',
            default: (_a = getAvailableUpdateChannels()[0]) !== null && _a !== void 0 ? _a : '',
            scope: core_1.PreferenceScope.User
        },
    }
};
//# sourceMappingURL=theia-updater-preferences.js.map