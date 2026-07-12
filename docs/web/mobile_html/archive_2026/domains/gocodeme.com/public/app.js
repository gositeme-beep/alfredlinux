// GoCodeMe.com Client Application

class GoCodeMeApp {
  constructor() {
    this.socket = null;
    this.isConnected = false;
    this.currentTab = 'vscode';
    this.init();
  }

  init() {
    this.setupEventListeners();
    this.connectWebSocket();
    this.hideLoading();
  }

  setupEventListeners() {
    // AI Panel Toggle
    document.getElementById('ai-toggle').addEventListener('click', () => {
      this.toggleAIPanel();
    });

    document.getElementById('close-ai').addEventListener('click', () => {
      this.hideAIPanel();
    });

    // Tab Switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        this.switchTab(e.target.dataset.tab);
      });
    });

    // Chat Input
    document.getElementById('send-chat').addEventListener('click', () => {
      this.sendChatMessage();
    });

    document.getElementById('chat-input').addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendChatMessage();
      }
    });

    // AI Feature Buttons
    document.getElementById('new-file').addEventListener('click', () => {
      this.createNewFile();
    });

    document.getElementById('save-file').addEventListener('click', () => {
      this.saveCurrentFile();
    });
  }

  connectWebSocket() {
    this.socket = io();
    
    this.socket.on('connect', () => {
      console.log('Connected to GoCodeMe server');
      this.isConnected = true;
      this.updateConnectionStatus('🟢 Connected');
    });

    this.socket.on('disconnect', () => {
      console.log('Disconnected from GoCodeMe server');
      this.isConnected = false;
      this.updateConnectionStatus('🔴 Disconnected');
    });

    this.socket.on('ai-response', (data) => {
      this.handleAIResponse(data);
    });
  }

  toggleAIPanel() {
    const panel = document.getElementById('ai-panel');
    panel.classList.toggle('hidden');
  }

  hideAIPanel() {
    document.getElementById('ai-panel').classList.add('hidden');
  }

  switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

    // Update editor frames
    document.querySelectorAll('.editor-frame').forEach(frame => {
      frame.classList.remove('active');
    });
    document.getElementById(`${tabName}-frame`).classList.add('active');

    this.currentTab = tabName;
  }

  async sendChatMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    
    if (!message) return;

    // Add user message to chat
    this.addChatMessage('user', message);
    input.value = '';

    // Show typing indicator
    this.addTypingIndicator();

    try {
      if (this.socket && this.isConnected) {
        // Send via WebSocket for real-time response
        this.socket.emit('ai-chat', {
          message: message,
          context: this.getCurrentContext(),
          fileContent: this.getCurrentFileContent()
        });
      } else {
        // Fallback to HTTP API
        const response = await fetch('/api/chat', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            message: message,
            context: this.getCurrentContext(),
            fileContent: this.getCurrentFileContent()
          })
        });

        const data = await response.json();
        this.handleAIResponse(data);
      }
    } catch (error) {
      console.error('Chat error:', error);
      this.addChatMessage('ai', 'Sorry, I encountered an error. Please try again.');
    }
  }

  handleAIResponse(data) {
    this.removeTypingIndicator();
    
    if (data.success) {
      this.addChatMessage('ai', data.response);
    } else {
      this.addChatMessage('ai', `Error: ${data.error || 'Unknown error'}`);
    }
  }

  addChatMessage(type, content) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'ai' ? 'ai-message' : 'user-message';
    
    if (type === 'ai') {
      messageDiv.innerHTML = `
        <div class="ai-avatar">🤖</div>
        <div class="message-content">${this.formatMessage(content)}</div>
      `;
    } else {
      messageDiv.innerHTML = `
        <div class="message-content user-message-content">${this.formatMessage(content)}</div>
        <div class="user-avatar">👤</div>
      `;
    }
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  addTypingIndicator() {
    const messagesContainer = document.getElementById('chat-messages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'ai-message typing-indicator';
    typingDiv.id = 'typing-indicator';
    typingDiv.innerHTML = `
      <div class="ai-avatar">🤖</div>
      <div class="message-content">
        <div class="typing-dots">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    `;
    messagesContainer.appendChild(typingDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
  }

  removeTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
      typingIndicator.remove();
    }
  }

  formatMessage(content) {
    // Convert markdown-like formatting to HTML
    return content
      .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
      .replace(/\*(.*?)\*/g, '<em>$1</em>')
      .replace(/`(.*?)`/g, '<code>$1</code>')
      .replace(/\n/g, '<br>');
  }

  getCurrentContext() {
    return `Current tab: ${this.currentTab}`;
  }

  getCurrentFileContent() {
    // This would get the current file content from the VS Code iframe
    // For now, return empty string
    return '';
  }

  updateConnectionStatus(status) {
    document.getElementById('connection-status').textContent = status;
  }

  hideLoading() {
    document.getElementById('loading-overlay').classList.add('hidden');
  }

  createNewFile() {
    // This would create a new file in the VS Code iframe
    console.log('Creating new file...');
  }

  saveCurrentFile() {
    // This would save the current file in the VS Code iframe
    console.log('Saving current file...');
  }
}

// AI Feature Functions (global scope for onclick handlers)
function explainCode() {
  const code = document.getElementById('explain-code').value;
  if (!code.trim()) {
    alert('Please paste some code to explain.');
    return;
  }

  // Send to AI service
  fetch('/api/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: 'Please explain this code in detail:',
      context: 'Code explanation',
      fileContent: code
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Code Explanation:\n\n' + data.response);
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('Error: ' + error.message);
  });
}

function fixCode() {
  const code = document.getElementById('fix-code').value;
  if (!code.trim()) {
    alert('Please paste some code to fix.');
    return;
  }

  fetch('/api/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: 'Please review and fix any issues in this code:',
      context: 'Code fixing',
      fileContent: code
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Fixed Code:\n\n' + data.response);
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('Error: ' + error.message);
  });
}

function generateCode() {
  const description = document.getElementById('generate-code').value;
  if (!description.trim()) {
    alert('Please describe what you want to build.');
    return;
  }

  fetch('/api/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: `Please generate code for: ${description}`,
      context: 'Code generation'
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Generated Code:\n\n' + data.response);
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('Error: ' + error.message);
  });
}

function optimizeCode() {
  const code = document.getElementById('optimize-code').value;
  if (!code.trim()) {
    alert('Please paste some code to optimize.');
    return;
  }

  fetch('/api/chat', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      message: 'Please optimize this code for better performance and readability:',
      context: 'Code optimization',
      fileContent: code
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Optimized Code:\n\n' + data.response);
    } else {
      alert('Error: ' + data.error);
    }
  })
  .catch(error => {
    alert('Error: ' + error.message);
  });
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
  new GoCodeMeApp();
}); 