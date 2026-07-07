"use strict";
/********************************************************************************
 * Copyright (C) 2022-2024 EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
var TheiaLauncherServiceEndpoint_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaLauncherServiceEndpoint = void 0;
const inversify_1 = require("@theia/core/shared/inversify");
const express_1 = require("@theia/core/shared/express");
const body_parser_1 = require("body-parser");
const common_1 = require("@theia/core/lib/common");
const env_variables_1 = require("@theia/core/lib/common/env-variables");
const sudo = __importStar(require("@vscode/sudo-prompt"));
const fs = __importStar(require("fs-extra"));
const uri_1 = __importDefault(require("@theia/core/lib/common/uri"));
const launcher_util_1 = require("./launcher-util");
let TheiaLauncherServiceEndpoint = TheiaLauncherServiceEndpoint_1 = class TheiaLauncherServiceEndpoint {
    constructor() {
        this.LAUNCHER_LINK_SOURCE = '/usr/local/bin/theia';
    }
    configure(app) {
        const router = (0, express_1.Router)();
        router.put('/', (request, response) => this.createLauncher(request, response));
        router.get('/initialized', (request, response) => this.isInitialized(request, response));
        app.use((0, body_parser_1.json)());
        app.use(TheiaLauncherServiceEndpoint_1.PATH, router);
    }
    async isInitialized(_request, response) {
        if (!process.env.APPIMAGE) {
            // we are not running from an AppImage, so there's nothing to initialize
            // return true
            response.json({ initialized: true });
        }
        const storageFile = await (0, launcher_util_1.getStorageFilePath)(this.envServer, TheiaLauncherServiceEndpoint_1.STORAGE_FILE_NAME);
        if (!storageFile) {
            throw new Error('Could not resolve path to storage file.');
        }
        if (!fs.existsSync(storageFile)) {
            response.json({ initialized: false });
            return;
        }
        const data = await this.readLauncherPathsFromStorage(storageFile);
        const initialized = !!data.find(entry => entry.source === this.LAUNCHER_LINK_SOURCE);
        response.json({ initialized });
    }
    async readLauncherPathsFromStorage(storageFile) {
        if (!fs.existsSync(storageFile)) {
            return [];
        }
        try {
            return await fs.readJSON(storageFile);
        }
        catch (error) {
            console.error('Failed to parse data from "', storageFile, '". Reason:', error);
            return [];
        }
    }
    async getLogFilePath() {
        const configDirUri = await this.envServer.getConfigDirUri();
        const logFileUri = new uri_1.default(configDirUri).resolve('logs/launcher.log');
        return logFileUri.path.fsPath();
    }
    async createLauncher(request, response) {
        const shouldCreateLauncher = request.body.create;
        const launcher = this.LAUNCHER_LINK_SOURCE;
        const target = process.env.APPIMAGE;
        const logFile = await this.getLogFilePath();
        const command = `printf '%s\n' '#!/bin/bash' 'exec "${target}" \\$1 &> ${logFile} &' >${launcher} && chmod +x ${launcher}`;
        if (shouldCreateLauncher) {
            const targetExists = target && fs.existsSync(target);
            if (!targetExists) {
                throw new Error('Could not find application to launch');
            }
            sudo.exec(command, { name: 'Theia IDE' });
        }
        const storageFile = await (0, launcher_util_1.getStorageFilePath)(this.envServer, TheiaLauncherServiceEndpoint_1.STORAGE_FILE_NAME);
        const data = fs.existsSync(storageFile) ? await this.readLauncherPathsFromStorage(storageFile) : [];
        fs.outputJSONSync(storageFile, [...data, { source: launcher, target: shouldCreateLauncher ? target : undefined }]);
        response.sendStatus(200);
    }
};
TheiaLauncherServiceEndpoint.PATH = '/launcher';
TheiaLauncherServiceEndpoint.STORAGE_FILE_NAME = 'paths.json';
__decorate([
    (0, inversify_1.inject)(common_1.ILogger),
    __metadata("design:type", Object)
], TheiaLauncherServiceEndpoint.prototype, "logger", void 0);
__decorate([
    (0, inversify_1.inject)(env_variables_1.EnvVariablesServer),
    __metadata("design:type", Object)
], TheiaLauncherServiceEndpoint.prototype, "envServer", void 0);
TheiaLauncherServiceEndpoint = TheiaLauncherServiceEndpoint_1 = __decorate([
    (0, inversify_1.injectable)()
], TheiaLauncherServiceEndpoint);
exports.TheiaLauncherServiceEndpoint = TheiaLauncherServiceEndpoint;
//# sourceMappingURL=launcher-endpoint.js.map