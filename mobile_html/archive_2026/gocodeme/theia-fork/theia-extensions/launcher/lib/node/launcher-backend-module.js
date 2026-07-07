"use strict";
/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", { value: true });
const inversify_1 = require("@theia/core/shared/inversify");
const launcher_endpoint_1 = require("./launcher-endpoint");
const backend_application_1 = require("@theia/core/lib/node/backend-application");
const desktopfile_endpoint_1 = require("./desktopfile-endpoint");
exports.default = new inversify_1.ContainerModule(bind => {
    bind(launcher_endpoint_1.TheiaLauncherServiceEndpoint).toSelf().inSingletonScope();
    bind(backend_application_1.BackendApplicationContribution).toService(launcher_endpoint_1.TheiaLauncherServiceEndpoint);
    bind(desktopfile_endpoint_1.TheiaDesktopFileServiceEndpoint).toSelf().inSingletonScope();
    bind(backend_application_1.BackendApplicationContribution).toService(desktopfile_endpoint_1.TheiaDesktopFileServiceEndpoint);
});
//# sourceMappingURL=launcher-backend-module.js.map