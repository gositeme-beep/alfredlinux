/********************************************************************************
 * GoCodeMe: Always resolve a chat agent (Alfred / Architect / Universal fallbacks).
 * Fixes "No agent was found" when defaultChatAgent or @mention id does not match
 * bundled agent ids (patched vs unpatched Theia, or Universal disabled).
 ********************************************************************************/
import { FrontendChatServiceImpl } from '@theia/ai-chat/lib/browser/frontend-chat-service';
import { ChatAgent } from '@theia/ai-chat/lib/common/chat-agents';
import { ParsedChatRequest } from '@theia/ai-chat/lib/common/parsed-chat-request';
export declare class GoCodeMeChatService extends FrontendChatServiceImpl {
    protected initialAgentSelection(parsedRequest: ParsedChatRequest): ChatAgent | undefined;
}
//# sourceMappingURL=gocodeme-chat-service.d.ts.map