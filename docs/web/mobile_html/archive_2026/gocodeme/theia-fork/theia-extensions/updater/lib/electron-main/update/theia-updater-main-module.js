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
const theia_updater_1 = require("../../common/updater/theia-updater");
const inversify_1 = require("@theia/core/shared/inversify");
const electron_connection_handler_1 = require("@theia/core/lib/electron-main/messaging/electron-connection-handler");
const electron_main_application_1 = require("@theia/core/lib/electron-main/electron-main-application");
const proxy_factory_1 = require("@theia/core/lib/common/messaging/proxy-factory");
const theia_updater_impl_1 = require("./theia-updater-impl");
exports.default = new inversify_1.ContainerModule(bind => {
    bind(theia_updater_impl_1.TheiaUpdaterImpl).toSelf().inSingletonScope();
    bind(theia_updater_1.TheiaUpdater).toService(theia_updater_impl_1.TheiaUpdaterImpl);
    bind(electron_main_application_1.ElectronMainApplicationContribution).toService(theia_updater_1.TheiaUpdater);
    bind(electron_connection_handler_1.ElectronConnectionHandler).toDynamicValue(context => new proxy_factory_1.JsonRpcConnectionHandler(theia_updater_1.TheiaUpdaterPath, client => {
        const server = context.container.get(theia_updater_1.TheiaUpdater);
        server.setClient(client);
        client.onDidCloseConnection(() => server.disconnectClient(client));
        return server;
    })).inSingletonScope();
});
//# sourceMappingURL=theia-updater-main-module.js.map