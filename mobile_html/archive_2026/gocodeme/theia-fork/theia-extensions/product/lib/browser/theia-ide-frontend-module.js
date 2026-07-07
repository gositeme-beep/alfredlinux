"use strict";
/********************************************************************************
 * Copyright (C) 2020 TypeFox, EclipseSource and others.
 *
 * This program and the accompanying materials are made available under the
 * terms of the MIT License, which is available in the project root.
 *
 * SPDX-License-Identifier: MIT
 ********************************************************************************/
Object.defineProperty(exports, "__esModule", { value: true });
require("../../src/browser/style/index.css");
const browser_1 = require("@theia/core/lib/browser");
const about_dialog_1 = require("@theia/core/lib/browser/about-dialog");
const theia_ide_config_1 = require("./theia-ide-config");
const command_1 = require("@theia/core/lib/common/command");
const chat_service_1 = require("@theia/ai-chat/lib/common/chat-service");
const inversify_1 = require("@theia/core/shared/inversify");
const gocodeme_chat_service_1 = require("./gocodeme-chat-service");
const getting_started_widget_1 = require("@theia/getting-started/lib/browser/getting-started-widget");
const menu_1 = require("@theia/core/lib/common/menu");
const theia_ide_about_dialog_1 = require("./theia-ide-about-dialog");
const theia_ide_contribution_1 = require("./theia-ide-contribution");
const theia_ide_getting_started_widget_1 = require("./theia-ide-getting-started-widget");
exports.default = new inversify_1.ContainerModule((bind, _unbind, isBound, rebind) => {
    (0, theia_ide_config_1.applyBranding)();
    rebind(chat_service_1.ChatService).to(gocodeme_chat_service_1.GoCodeMeChatService).inSingletonScope();
    bind(theia_ide_getting_started_widget_1.TheiaIDEGettingStartedWidget).toSelf();
    bind(browser_1.WidgetFactory).toDynamicValue(context => ({
        id: getting_started_widget_1.GettingStartedWidget.ID,
        createWidget: () => context.container.get(theia_ide_getting_started_widget_1.TheiaIDEGettingStartedWidget),
    })).inSingletonScope();
    if (isBound(about_dialog_1.AboutDialog)) {
        rebind(about_dialog_1.AboutDialog).to(theia_ide_about_dialog_1.TheiaIDEAboutDialog).inSingletonScope();
    }
    else {
        bind(about_dialog_1.AboutDialog).to(theia_ide_about_dialog_1.TheiaIDEAboutDialog).inSingletonScope();
    }
    bind(theia_ide_contribution_1.TheiaIDEContribution).toSelf().inSingletonScope();
    [command_1.CommandContribution, menu_1.MenuContribution].forEach(serviceIdentifier => bind(serviceIdentifier).toService(theia_ide_contribution_1.TheiaIDEContribution));
});
//# sourceMappingURL=theia-ide-frontend-module.js.map