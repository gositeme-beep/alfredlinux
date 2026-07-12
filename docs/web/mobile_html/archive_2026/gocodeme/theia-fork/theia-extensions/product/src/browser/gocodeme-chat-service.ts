/********************************************************************************
 * GoCodeMe: Always resolve a chat agent (Alfred / Architect / Universal fallbacks).
 * Fixes "No agent was found" when defaultChatAgent or @mention id does not match
 * bundled agent ids (patched vs unpatched Theia, or Universal disabled).
 ********************************************************************************/

import { FrontendChatServiceImpl } from '@theia/ai-chat/lib/browser/frontend-chat-service';
import { ChatAgent } from '@theia/ai-chat/lib/common/chat-agents';
import { ParsedChatRequest } from '@theia/ai-chat/lib/common/parsed-chat-request';
import { injectable } from '@theia/core/shared/inversify';

@injectable()
export class GoCodeMeChatService extends FrontendChatServiceImpl {
    protected override initialAgentSelection(parsedRequest: ParsedChatRequest): ChatAgent | undefined {
        const agentPart = this.getMentionedAgent(parsedRequest);
        if (agentPart) {
            let agent = this.chatAgentService.getAgent(agentPart.agentId);
            if (!agent && String(agentPart.agentId).toLowerCase() === 'alfred') {
                agent = this.chatAgentService.getAgent('Alfred')
                    ?? this.chatAgentService.getAgent('Architect')
                    ?? this.chatAgentService.getAgents().find(a => a.name === 'Alfred');
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
}
