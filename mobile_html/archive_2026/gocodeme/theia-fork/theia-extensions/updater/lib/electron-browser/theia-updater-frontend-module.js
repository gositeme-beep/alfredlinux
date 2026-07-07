"use strict";
/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", { value: true });
const common_1 = require("@theia/core/lib/common");
const theia_updater_frontend_contribution_1 = require("./updater/theia-updater-frontend-contribution");
const theia_updater_1 = require("../common/updater/theia-updater");
const inversify_1 = require("@theia/core/shared/inversify");
const electron_ipc_connection_source_1 = require("@theia/core/lib/electron-browser/messaging/electron-ipc-connection-source");
const common_2 = require("@theia/core/lib/common");
const theia_updater_preferences_1 = require("./updater/theia-updater-preferences");
exports.default = new inversify_1.ContainerModule((bind, _unbind, isBound, rebind) => {
    bind(theia_updater_frontend_contribution_1.ElectronMenuUpdater).toSelf().inSingletonScope();
    bind(theia_updater_frontend_contribution_1.TheiaUpdaterClientImpl).toSelf().inSingletonScope();
    bind(theia_updater_1.TheiaUpdaterClient).toService(theia_updater_frontend_contribution_1.TheiaUpdaterClientImpl);
    bind(theia_updater_1.TheiaUpdater).toDynamicValue(context => {
        const client = context.container.get(theia_updater_frontend_contribution_1.TheiaUpdaterClientImpl);
        return electron_ipc_connection_source_1.ElectronIpcConnectionProvider.createProxy(context.container, theia_updater_1.TheiaUpdaterPath, client);
    }).inSingletonScope();
    bind(theia_updater_frontend_contribution_1.TheiaUpdaterFrontendContribution).toSelf().inSingletonScope();
    bind(common_1.MenuContribution).toService(theia_updater_frontend_contribution_1.TheiaUpdaterFrontendContribution);
    bind(common_1.CommandContribution).toService(theia_updater_frontend_contribution_1.TheiaUpdaterFrontendContribution);
    bind(common_2.PreferenceContribution).toConstantValue({ schema: theia_updater_preferences_1.theiaUpdaterPreferenceSchema });
});
//# sourceMappingURL=theia-updater-frontend-module.js.map