const axios = require('axios');

class ClaudeService {
  constructor() {
    this.apiKey = process.env.OPENROUTER_API_KEY;
    this.baseURL = process.env.OPENROUTER_BASE_URL || 'https://openrouter.ai/api/v1';
    this.model = process.env.CLAUDE_MODEL || 'anthropic/claude-3-sonnet';
    
    if (!this.apiKey) {
      console.warn('⚠️  OPENROUTER_API_KEY not set. AI features will be disabled.');
    }
  }

  async chat(message, context = '', fileContent = '') {
    if (!this.apiKey) {
      throw new Error('OpenRouter API key not configured');
    }

    try {
      // Build context-aware prompt
      let systemPrompt = `You are an AI coding assistant for GoCodeMe.com, a web-based code editor. 
      
Your role is to help users write, debug, and improve code. You should:
- Provide clear, concise code explanations
- Suggest improvements and optimizations
- Help debug issues
- Generate code based on requirements
- Follow best practices for the language being used

Current context: ${context}`;

      let userPrompt = message;
      
      // If file content is provided, include it in the context
      if (fileContent) {
        userPrompt = `File content:\n\`\`\`\n${fileContent}\n\`\`\`\n\nUser question: ${message}`;
      }

      const response = await axios.post(
        `${this.baseURL}/chat/completions`,
        {
          model: this.model,
          messages: [
            {
              role: 'system',
              content: systemPrompt
            },
            {
              role: 'user',
              content: userPrompt
            }
          ],
          max_tokens: 4000,
          temperature: 0.7,
          stream: false
        },
        {
          headers: {
            'Authorization': `Bearer ${this.apiKey}`,
            'Content-Type': 'application/json',
            'HTTP-Referer': 'https://gocodeme.com',
            'X-Title': 'GoCodeMe.com'
          }
        }
      );

      return response.data.choices[0].message.content;
    } catch (error) {
      console.error('Claude API Error:', error.response?.data || error.message);
      throw new Error(`AI service error: ${error.response?.data?.error?.message || error.message}`);
    }
  }

  async explainCode(code, language = 'javascript') {
    return this.chat(
      `Please explain this ${language} code in detail:`,
      `Code explanation for ${language}`,
      code
    );
  }

  async fixCode(code, language = 'javascript', issue = '') {
    const prompt = issue 
      ? `Please fix this ${language} code. Issue: ${issue}`
      : `Please review and fix any issues in this ${language} code:`;
    
    return this.chat(prompt, `Code fixing for ${language}`, code);
  }

  async generateCode(description, language = 'javascript') {
    return this.chat(
      `Please generate ${language} code for: ${description}`,
      `Code generation for ${language}`
    );
  }

  async optimizeCode(code, language = 'javascript') {
    return this.chat(
      `Please optimize this ${language} code for better performance and readability:`,
      `Code optimization for ${language}`,
      code
    );
  }

  // Test the AI connection
  async testConnection() {
    try {
      const response = await this.chat('Hello! Please respond with "GoCodeMe.com AI is working!"');
      return {
        success: true,
        message: response
      };
    } catch (error) {
      return {
        success: false,
        error: error.message
      };
    }
  }
}

module.exports = new ClaudeService(); 