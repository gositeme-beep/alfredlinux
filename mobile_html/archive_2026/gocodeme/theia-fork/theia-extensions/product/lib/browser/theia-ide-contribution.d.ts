/********************************************************************************
 * Copyright (C) 2021 Ericsson and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import { Command, CommandContribution, CommandRegistry } from '@theia/core/lib/common/command';
import { MenuContribution, MenuModelRegistry, MenuPath } from '@theia/core/lib/common/menu';
import { WindowService } from '@theia/core/lib/browser/window/window-service';
export declare namespace TheiaIDEMenus {
    const THEIA_IDE_HELP: MenuPath;
}
export declare namespace TheiaIDECommands {
    const CATEGORY = "TheiaIDE";
    const REPORT_ISSUE: Command;
    const DOCUMENTATION: Command;
}
export declare class TheiaIDEContribution implements CommandContribution, MenuContribution {
    protected readonly windowService: WindowService;
    static REPORT_ISSUE_URL: string;
    static DOCUMENTATION_URL: string;
    registerCommands(commandRegistry: CommandRegistry): void;
    registerMenus(menus: MenuModelRegistry): void;
}
//# sourceMappingURL=theia-ide-contribution.d.ts.map