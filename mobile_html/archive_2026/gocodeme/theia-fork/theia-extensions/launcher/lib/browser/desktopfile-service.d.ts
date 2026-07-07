/********************************************************************************
 * Copyright (C) 2024 STMicroelectronics and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
export interface DesktopFileOptions {
    applicationName?: string;
    createUrlHandler?: boolean;
}
export declare class DesktopFileService {
    isInitialized(): Promise<boolean>;
    createOrUpdateDesktopfile(create: boolean, options?: DesktopFileOptions): Promise<void>;
    protected endpoint(): string;
}
//# sourceMappingURL=desktopfile-service.d.ts.map