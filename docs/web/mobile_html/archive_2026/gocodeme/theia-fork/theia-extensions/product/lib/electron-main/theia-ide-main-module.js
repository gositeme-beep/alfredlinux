"use strict";
/********************************************************************************
 * Copyright (C) 2021 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", { value: true });
const inversify_1 = require("@theia/core/shared/inversify");
const electron_main_application_1 = require("@theia/core/lib/electron-main/electron-main-application");
const icon_contribution_1 = require("./icon-contribution");
exports.default = new inversify_1.ContainerModule(bind => {
    bind(icon_contribution_1.IconContribution).toSelf().inSingletonScope();
    bind(electron_main_application_1.ElectronMainApplicationContribution).toService(icon_contribution_1.IconContribution);
});
//# sourceMappingURL=theia-ide-main-module.js.map