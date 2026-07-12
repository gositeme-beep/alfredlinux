"use strict";
/********************************************************************************
 * Copyright (C) 2024 STMicroelectronics and others.
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
var TheiaDesktopFileServiceEndpoint_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.TheiaDesktopFileServiceEndpoint = void 0;
const express_1 = require("@theia/core/shared/express");
const inversify_1 = require("@theia/core/shared/inversify");
const body_parser_1 = require("body-parser");
const env_variables_1 = require("@theia/core/lib/common/env-variables");
const launcher_util_1 = require("./launcher-util");
const fs = __importStar(require("fs-extra"));
const path = __importStar(require("path"));
let TheiaDesktopFileServiceEndpoint = TheiaDesktopFileServiceEndpoint_1 = class TheiaDesktopFileServiceEndpoint {
    configure(app) {
        const router = (0, express_1.Router)();
        router.put('/', (request, response) => this.createOrUpdateDesktopfile(request, response));
        router.get('/initialized', (request, response) => this.isInitialized(request, response));
        app.use((0, body_parser_1.json)());
        app.use(TheiaDesktopFileServiceEndpoint_1.PATH, router);
    }
    async isInitialized(_request, response) {
        if (!process.env.APPIMAGE) {
            // we only want to create Desktop Files when running as an App Image
            response.json({ initialized: true });
        }
        if (process.env.HOME === undefined) {
            // log error but assume initialized, since we can't proceed
            console.error('Desktop files can only be created if there is a set HOME directory');
            response.json({ initialized: true });
        }
        const storageFile = await (0, launcher_util_1.getStorageFilePath)(this.envServer, TheiaDesktopFileServiceEndpoint_1.STORAGE_FILE_NAME);
        if (!storageFile) {
            throw new Error('Could not resolve path to storage file.');
        }
        if (!fs.existsSync(storageFile)) {
            response.json({ initialized: false });
            return;
        }
        const appImageInformation = await this.readAppImageInformationFromStorage(storageFile);
        if (appImageInformation === undefined) {
            response.json({ initialized: false });
            return;
        }
        if (appImageInformation.declined !== undefined && appImageInformation.declined.includes(process.env.APPIMAGE)) {
            // we don't want to create Desktop Files for this App Image
            response.json({ initialized: true });
            return;
        }
        const initialized = appImageInformation.appImage === process.env.APPIMAGE;
        response.json({ initialized });
    }
    async readAppImageInformationFromStorage(storageFile) {
        if (!fs.existsSync(storageFile)) {
            return undefined;
        }
        try {
            const data = await fs.readJSON(storageFile);
            return data;
        }
        catch (error) {
            console.error('Failed to parse data from "', storageFile, '". Reason:', error);
            return undefined;
        }
    }
    async createOrUpdateDesktopfile(request, response) {
        const storageFile = await (0, launcher_util_1.getStorageFilePath)(this.envServer, TheiaDesktopFileServiceEndpoint_1.STORAGE_FILE_NAME);
        let appImageInformation = await this.readAppImageInformationFromStorage(storageFile);
        if (appImageInformation === undefined) {
            appImageInformation = { appImage: '', declined: [] };
        }
        const createOrUpdate = request.body.create;
        const applicationName = request.body.applicationName || 'Theia IDE';
        const createUrlHandler = request.body.createUrlHandler !== false;
        const appId = applicationName.toLowerCase().replace(/\s+/g, '-');
        if (createOrUpdate) {
            const iconFileName = appId + '-electron-app.png';
            const applicationsDir = path.join(process.env.HOME, '.local', 'share', 'applications');
            const imagePath = path.join(applicationsDir, iconFileName);
            if (!fs.existsSync(imagePath)) {
                const appDir = process.env.APPDIR;
                if (appDir !== undefined) {
                    let unpackedImagePath = path.join(appDir, iconFileName);
                    if (!fs.existsSync(unpackedImagePath)) {
                        // Fallback: find any .png icon in the AppImage root
                        try {
                            const pngFile = fs.readdirSync(appDir).find((f) => f.endsWith('.png'));
                            if (pngFile) {
                                unpackedImagePath = path.join(appDir, pngFile);
                            }
                        }
                        catch ( /* ignore */_a) { /* ignore */ }
                    }
                    if (fs.existsSync(unpackedImagePath)) {
                        fs.copyFileSync(unpackedImagePath, imagePath);
                    }
                    else {
                        console.warn('Launcher Icon not Found in App Image');
                    }
                }
                else {
                    console.warn('Path for unpacked App Image not found');
                }
            }
            const desktopFilePath = path.join(applicationsDir, `${appId}-launcher.desktop`);
            fs.outputFileSync(desktopFilePath, this.getDesktopFileContents(applicationName, process.env.APPIMAGE, imagePath));
            if (createUrlHandler) {
                const desktopURLFilePath = path.join(applicationsDir, `${appId}-launcher-url.desktop`);
                fs.outputFileSync(desktopURLFilePath, this.getDesktopURLFileContents(applicationName, process.env.APPIMAGE, imagePath));
            }
            appImageInformation.appImage = process.env.APPIMAGE;
            fs.outputJSONSync(storageFile, appImageInformation);
        }
        else {
            appImageInformation.declined.push(process.env.APPIMAGE);
            fs.outputJSONSync(storageFile, appImageInformation);
        }
        response.sendStatus(200);
    }
    getDesktopFileContents(applicationName, appImagePath, imagePath) {
        return `[Desktop Entry]
Name=${applicationName}
GenericName=Integrated Development Environment
Exec=${appImagePath} %U
Terminal=false
Type=Application
Icon=${imagePath}
StartupWMClass=${applicationName}
Comment=IDE for cloud and desktop
Categories=Development;IDE;`;
    }
    getDesktopURLFileContents(applicationName, appImagePath, imagePath) {
        return `[Desktop Entry]
Name=${applicationName} - URL Handler
GenericName=Integrated Development Environment
Exec=${appImagePath} --open-url %U
Terminal=false
Type=Application
NoDisplay=true
Icon=${imagePath}
MimeType=x-scheme-handler/theia;
Comment=IDE for cloud and desktop
Categories=Development;IDE;`;
    }
};
TheiaDesktopFileServiceEndpoint.PATH = '/desktopfile';
TheiaDesktopFileServiceEndpoint.STORAGE_FILE_NAME = 'desktopfile.json';
__decorate([
    (0, inversify_1.inject)(env_variables_1.EnvVariablesServer),
    __metadata("design:type", Object)
], TheiaDesktopFileServiceEndpoint.prototype, "envServer", void 0);
TheiaDesktopFileServiceEndpoint = TheiaDesktopFileServiceEndpoint_1 = __decorate([
    (0, inversify_1.injectable)()
], TheiaDesktopFileServiceEndpoint);
exports.TheiaDesktopFileServiceEndpoint = TheiaDesktopFileServiceEndpoint;
//# sourceMappingURL=desktopfile-endpoint.js.map