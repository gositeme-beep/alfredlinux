/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import * as React from 'react';
import { AboutDialog, AboutDialogProps } from '@theia/core/lib/browser/about-dialog';
import { VSXEnvironment } from '@theia/vsx-registry/lib/common/vsx-environment';
import { WindowService } from '@theia/core/lib/browser/window/window-service';
export declare class TheiaIDEAboutDialog extends AboutDialog {
    protected readonly props: AboutDialogProps;
    protected readonly environment: VSXEnvironment;
    protected readonly windowService: WindowService;
    protected vscodeApiVersion: string;
    constructor(props: AboutDialogProps);
    protected doInit(): Promise<void>;
    protected render(): React.ReactNode;
    protected renderContent(): React.ReactNode;
    protected renderTitle(): React.ReactNode;
    protected renderExtensions(): React.ReactNode;
    protected renderVersion(): React.ReactNode;
}
//# sourceMappingURL=theia-ide-about-dialog.d.ts.map