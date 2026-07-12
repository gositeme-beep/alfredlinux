/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
import { ElectronMainApplication, ElectronMainApplicationContribution } from '@theia/core/lib/electron-main/electron-main-application';
import { TheiaUpdater, TheiaUpdaterClient, UpdaterSettings } from '../../common/updater/theia-updater';
export declare class TheiaUpdaterImpl implements TheiaUpdater, ElectronMainApplicationContribution {
    protected clients: Array<TheiaUpdaterClient>;
    protected settings: UpdaterSettings;
    private initialCheck;
    private reportOnFirstRegistration;
    private cancellationToken;
    private updateCheckTimer;
    constructor();
    checkForUpdates(): void;
    setUpdaterSettings(settings: UpdaterSettings): void;
    onRestartToUpdateRequested(): void;
    cancel(): void;
    downloadUpdate(): void;
    onStart(application: ElectronMainApplication): void;
    onStop(application: ElectronMainApplication): void;
    private scheduleUpdateChecks;
    private stopUpdateCheckTimer;
    setClient(client: TheiaUpdaterClient | undefined): void;
    protected getFeedURL(channel: string): string;
    disconnectClient(client: TheiaUpdaterClient): void;
    dispose(): void;
}
//# sourceMappingURL=theia-updater-impl.d.ts.map