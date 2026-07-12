"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
const create_launcher_contribution_1 = require("./create-launcher-contribution");
const inversify_1 = require("@theia/core/shared/inversify");
const launcher_service_1 = require("./launcher-service");
const browser_1 = require("@theia/core/lib/browser");
const desktopfile_service_1 = require("./desktopfile-service");
exports.default = new inversify_1.ContainerModule(bind => {
    bind(browser_1.FrontendApplicationContribution).to(create_launcher_contribution_1.CreateLauncherCommandContribution);
    bind(launcher_service_1.LauncherService).toSelf().inSingletonScope();
    bind(desktopfile_service_1.DesktopFileService).toSelf().inSingletonScope();
});
//# sourceMappingURL=create-launcher-frontend-module.js.map