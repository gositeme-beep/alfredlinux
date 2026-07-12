"use strict";
/********************************************************************************
 * Copyright (C) 2024 STMicroelectronics and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.getStorageFilePath = void 0;
const uri_1 = __importDefault(require("@theia/core/lib/common/uri"));
async function getStorageFilePath(envServer, fileName) {
    const configDirUri = await envServer.getConfigDirUri();
    const globalStorageFolderUri = new uri_1.default(configDirUri).resolve('globalStorage/theia-ide-launcher/' + fileName);
    const globalStorageFolderFsPath = globalStorageFolderUri.path.fsPath();
    return globalStorageFolderFsPath;
}
exports.getStorageFilePath = getStorageFilePath;
//# sourceMappingURL=launcher-util.js.map