/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import * as React from 'react';
import { Message } from '@theia/core/lib/browser';
import { PreferenceService } from '@theia/core/lib/common';
import { GettingStartedWidget } from '@theia/getting-started/lib/browser/getting-started-widget';
import { VSXEnvironment } from '@theia/vsx-registry/lib/common/vsx-environment';
import { WindowService } from '@theia/core/lib/browser/window/window-service';
export declare class TheiaIDEGettingStartedWidget extends GettingStartedWidget {
    protected readonly environment: VSXEnvironment;
    protected readonly windowService: WindowService;
    protected readonly preferenceService: PreferenceService;
    protected vscodeApiVersion: string;
    protected doInit(): Promise<void>;
    protected onActivateRequest(msg: Message): void;
    protected render(): React.ReactNode;
    protected renderActions(): React.ReactNode;
    protected renderHelp(): React.ReactNode;
    protected renderNews(): React.ReactNode;
    protected renderHeader(): React.ReactNode;
    protected renderVersion(): React.ReactNode;
    protected renderAIBanner(): React.ReactNode;
}
//# sourceMappingURL=theia-ide-getting-started-widget.d.ts.map