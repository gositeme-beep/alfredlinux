"use strict";
/********************************************************************************
 * Copyright (C) 2024 STMicroelectronics and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.DesktopFileService = void 0;
const browser_1 = require("@theia/core/lib/browser");
const inversify_1 = require("@theia/core/shared/inversify");
let DesktopFileService = class DesktopFileService {
    async isInitialized() {
        const response = await fetch(new Request(`${this.endpoint()}/initialized`), {
            body: undefined,
            method: 'GET'
        }).then(r => r.json());
        return !!(response === null || response === void 0 ? void 0 : response.initialized);
    }
    async createOrUpdateDesktopfile(create, options) {
        fetch(new Request(`${this.endpoint()}`), {
            body: JSON.stringify(Object.assign({ create }, options)),
            method: 'PUT',
            headers: new Headers({ 'Content-Type': 'application/json' })
        });
    }
    endpoint() {
        const url = new browser_1.Endpoint({ path: 'desktopfile' }).getRestUrl().toString();
        return url.endsWith('/') ? url.slice(0, -1) : url;
    }
};
DesktopFileService = __decorate([
    (0, inversify_1.injectable)()
], DesktopFileService);
exports.DesktopFileService = DesktopFileService;
//# sourceMappingURL=desktopfile-service.js.map