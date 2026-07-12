/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import { FrontendApplication, FrontendApplicationContribution, StorageService } from '@theia/core/lib/browser';
import { ILogger, MaybePromise } from '@theia/core/lib/common';
export declare class CreateLauncherCommandContribution implements FrontendApplicationContribution {
    protected readonly storageService: StorageService;
    protected readonly logger: ILogger;
    private readonly launcherService;
    private readonly desktopFileService;
    onStart(_app: FrontendApplication): MaybePromise<void>;
}
//# sourceMappingURL=create-launcher-contribution.d.ts.map