/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import { Command, CommandContribution, CommandRegistry, Emitter, MenuContribution, MenuModelRegistry, MenuPath, MessageService } from '@theia/core/lib/common';
import { TheiaUpdater, TheiaUpdaterClient, UpdaterError, UpdateInfo, UpdateAvailabilityInfo } from '../../common/updater/theia-updater';
import { OpenerService } from '@theia/core/lib/browser';
import { ElectronMainMenuFactory } from '@theia/core/lib/electron-browser/menu/electron-main-menu-factory';
export declare namespace TheiaUpdaterCommands {
    const CHECK_FOR_UPDATES: Command;
    const RESTART_TO_UPDATE: Command;
}
export declare namespace TheiaUpdaterMenu {
    const MENU_PATH: MenuPath;
}
export declare class TheiaUpdaterClientImpl implements TheiaUpdaterClient {
    protected readonly onReadyToInstallEmitter: Emitter<void>;
    readonly onReadyToInstall: import("@theia/core/lib/common").Event<void>;
    protected readonly onUpdateAvailableEmitter: Emitter<UpdateAvailabilityInfo>;
    readonly onUpdateAvailable: import("@theia/core/lib/common").Event<UpdateAvailabilityInfo>;
    protected readonly onErrorEmitter: Emitter<UpdaterError>;
    readonly onError: import("@theia/core/lib/common").Event<UpdaterError>;
    protected readonly onCancelEmitter: Emitter<void>;
    readonly onCancel: import("@theia/core/lib/common").Event<void>;
    notifyReadyToInstall(): void;
    updateAvailable(available: boolean, updateInfo?: UpdateInfo): void;
    reportError(error: UpdaterError): void;
    reportCancelled(): void;
}
export declare class ElectronMenuUpdater {
    protected readonly factory: ElectronMainMenuFactory;
    update(): void;
    private setMenu;
}
export declare class TheiaUpdaterFrontendContribution implements CommandContribution, MenuContribution {
    protected readonly messageService: MessageService;
    protected readonly menuUpdater: ElectronMenuUpdater;
    protected readonly updater: TheiaUpdater;
    protected readonly updaterClient: TheiaUpdaterClientImpl;
    private readonly preferenceService;
    protected readonly openerService: OpenerService;
    protected readyToUpdate: boolean;
    private progress;
    private intervalId;
    private currentUpdateInfo;
    protected init(): void;
    protected syncUpdaterSettings(): void;
    registerCommands(registry: CommandRegistry): void;
    registerMenus(registry: MenuModelRegistry): void;
    protected handleDownloadUpdate(updateInfo?: UpdateInfo): Promise<void>;
    protected handleNoUpdate(): Promise<void>;
    protected handleUpdatesAvailable(): Promise<void>;
    protected handleError(error: UpdaterError): Promise<void>;
    private stopProgress;
}
//# sourceMappingURL=theia-updater-frontend-contribution.d.ts.map