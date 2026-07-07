/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
/// <reference types="express" />
import { BackendApplicationContribution } from '@theia/core/lib/node/backend-application';
import { Application } from '@theia/core/shared/express';
import { ILogger } from '@theia/core/lib/common';
import { EnvVariablesServer } from '@theia/core/lib/common/env-variables';
export declare class TheiaLauncherServiceEndpoint implements BackendApplicationContribution {
    protected static PATH: string;
    protected static STORAGE_FILE_NAME: string;
    private LAUNCHER_LINK_SOURCE;
    protected readonly logger: ILogger;
    protected readonly envServer: EnvVariablesServer;
    configure(app: Application): void;
    private isInitialized;
    private readLauncherPathsFromStorage;
    private getLogFilePath;
    private createLauncher;
}
//# sourceMappingURL=launcher-endpoint.d.ts.map