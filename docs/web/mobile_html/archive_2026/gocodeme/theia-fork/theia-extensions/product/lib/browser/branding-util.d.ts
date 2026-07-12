/********************************************************************************
 * Copyright (C) 2020 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import { WindowService } from '@theia/core/lib/browser/window/window-service';
import * as React from 'react';
export interface ExternalBrowserLinkProps {
    text: string;
    url: string;
    windowService: WindowService;
}
export declare function renderProductName(): React.ReactNode;
export declare function renderWhatIs(windowService: WindowService): React.ReactNode;
export declare function renderExtendingCustomizing(windowService: WindowService): React.ReactNode;
export declare function renderSupport(windowService: WindowService): React.ReactNode;
export declare function renderTickets(windowService: WindowService): React.ReactNode;
export declare function renderSourceCode(_windowService: WindowService): React.ReactNode;
export declare function renderDocumentation(windowService: WindowService): React.ReactNode;
export declare function renderCollaboration(windowService: WindowService): React.ReactNode;
export declare function renderDownloads(): React.ReactNode;
//# sourceMappingURL=branding-util.d.ts.map