/********************************************************************************
 * Copyright (C) 2024 STMicroelectronics and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
/// <reference types="express" />
import { BackendApplicationContribution } from '@theia/core/lib/node/backend-application';
import { Application } from '@theia/core/shared/express';
import { Request, Response } from 'express-serve-static-core';
import { EnvVariablesServer } from '@theia/core/lib/common/env-variables';
interface DesktopFileInformation {
    appImage: string;
    declined: string[];
}
export declare class TheiaDesktopFileServiceEndpoint implements BackendApplicationContribution {
    protected static PATH: string;
    protected static STORAGE_FILE_NAME: string;
    protected readonly envServer: EnvVariablesServer;
    configure(app: Application): void;
    protected isInitialized(_request: Request, response: Response): Promise<void>;
    protected readAppImageInformationFromStorage(storageFile: string): Promise<DesktopFileInformation | undefined>;
    protected createOrUpdateDesktopfile(request: Request, response: Response): Promise<void>;
    protected getDesktopFileContents(applicationName: string, appImagePath: string, imagePath: string): string;
    protected getDesktopURLFileContents(applicationName: string, appImagePath: string, imagePath: string): string;
}
export {};
//# sourceMappingURL=desktopfile-endpoint.d.ts.map