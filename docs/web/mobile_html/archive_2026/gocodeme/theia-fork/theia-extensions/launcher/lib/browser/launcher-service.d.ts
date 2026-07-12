/********************************************************************************
 * Copyright (C) 2022 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
export declare class LauncherService {
    isInitialized(): Promise<boolean>;
    createLauncher(create: boolean): Promise<void>;
    protected endpoint(): string;
}
//# sourceMappingURL=launcher-service.d.ts.map