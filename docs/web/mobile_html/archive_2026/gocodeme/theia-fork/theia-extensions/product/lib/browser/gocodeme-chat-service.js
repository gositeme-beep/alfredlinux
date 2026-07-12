"use strict";
/********************************************************************************
 * GoCodeMe: Always resolve a chat agent (Alfred / Architect / Universal fallbacks).
 * Fixes "No agent was found" when defaultChatAgent or @mention id does not match
 * bundled agent ids (patched vs unpatched Theia, or Universal disabled).
 ********************************************************************************/
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.GoCodeMeChatService = void 0;
const frontend_chat_service_1 = require("@theia/ai-chat/lib/browser/frontend-chat-service");
const inversify_1 = require("@theia/core/shared/inversify");
let GoCodeMeChatService = class GoCodeMeChatService extends frontend_chat_service_1.FrontendChatServiceImpl {
    initialAgentSelection(parsedRequest) {
        var _a, _b;
        const agentPart = this.getMentionedAgent(parsedRequest);
        if (agentPart) {
            let agent = this.chatAgentService.getAgent(agentPart.agentId);
            if (!agent && String(agentPart.agentId).toLowerCase() === 'alfred') {
                agent = (_b = (_a = this.chatAgentService.getAgent('Alfred')) !== null && _a !== void 0 ? _a : this.chatAgentService.getAgent('Architect')) !== null && _b !== void 0 ? _b : this.chatAgentService.getAgents().find(a => a.name === 'Alfred');
            }
            return agent;
        }
        const fromSuper = super.initialAgentSelection(parsedRequest);
        if (fromSuper) {
            return fromSuper;
        }
        const tryIds = ['Alfred', 'Architect', 'Universal', 'Orchestrator', 'Coder'];
        for (const id of tryIds) {
            const a = this.chatAgentService.getAgent(id);
            if (a) {
                return a;
            }
        }
        const agents = this.chatAgentService.getAgents();
        return agents.length > 0 ? agents[0] : undefined;
    }
};
GoCodeMeChatService = __decorate([
    (0, inversify_1.injectable)()
], GoCodeMeChatService);
exports.GoCodeMeChatService = GoCodeMeChatService;
//# sourceMappingURL=gocodeme-chat-service.js.map