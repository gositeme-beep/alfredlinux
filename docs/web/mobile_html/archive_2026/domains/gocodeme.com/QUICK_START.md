# GoCodeMe.com Quick Start Guide

## 🚀 Get Started in 5 Minutes

### Step 1: Install Dependencies
```bash
cd /home/gositeme/domains/soundstudiopro.com/public_html/gocodeme.com
./setup.sh
```

### Step 2: Configure AI (Required)
1. Get an OpenRouter API key from: https://openrouter.ai/
2. Edit the `.env` file:
```bash
nano .env
```
3. Replace `your_openrouter_api_key_here` with your actual API key

### Step 3: Start Development Server
```bash
npm run dev
```

### Step 4: Access GoCodeMe.com
Open your browser and go to:
- **Local**: http://localhost:3000
- **Server**: http://your-server-ip:3000

## 🎯 What You'll See

### Main Interface
- **Header**: GoCodeMe.com branding with AI Assistant button
- **VS Code Tab**: Full VS Code experience in browser
- **AI Features Tab**: Dedicated AI coding tools
- **AI Chat Panel**: Claude-powered coding assistant

### AI Features Available
1. **🤖 AI Chat**: Ask Claude anything about coding
2. **🔍 Explain Code**: Get detailed code explanations
3. **🔧 Fix Code**: Automatically fix code issues
4. **⚡ Generate Code**: Create code from descriptions
5. **🚀 Optimize Code**: Improve performance and readability

## 🔧 Quick Configuration

### Customize Workspace
Edit `.env` file to change the workspace directory:
```
CODE_SERVER_WORKSPACE=/path/to/your/project
```

### Change AI Model
Edit `.env` file to use different Claude model:
```
CLAUDE_MODEL=anthropic/claude-3-haiku  # Faster, cheaper
CLAUDE_MODEL=anthropic/claude-3-opus   # Most powerful
```

### Security Setup
Generate secure secrets:
```bash
openssl rand -hex 32  # Copy to SESSION_SECRET
openssl rand -hex 32  # Copy to JWT_SECRET
```

## 🐛 Troubleshooting

### AI Not Working?
1. Check your OpenRouter API key in `.env`
2. Test connection: `curl -H "Authorization: Bearer YOUR_KEY" https://openrouter.ai/api/v1/models`
3. Check server logs: `npm run dev`

### code-server Not Loading?
1. Check if code-server is installed: `which code-server`
2. Install manually: `curl -fsSL https://code-server.dev/install.sh | sh`
3. Start code-server: `code-server --port 8080`

### Port Already in Use?
```bash
sudo lsof -i :3000
sudo kill -9 <PID>
```

## 📱 Mobile Access

GoCodeMe.com works on mobile devices! The interface adapts to smaller screens.

## 🔒 Security Notes

- **Development**: No authentication (for easy testing)
- **Production**: Add authentication before deployment
- **API Keys**: Keep your OpenRouter API key secure
- **Firewall**: Configure server firewall for production

## 🚀 Next Steps

1. **Test AI Features**: Try the AI chat and code generation
2. **Customize Branding**: Edit colors and logo in `public/styles.css`
3. **Add Extensions**: Install VS Code extensions in code-server
4. **Deploy to Production**: Follow `DEPLOYMENT.md` guide

## 💡 Tips

- **Keyboard Shortcuts**: Use VS Code shortcuts in the editor
- **AI Prompts**: Be specific when asking Claude for help
- **File Management**: Use the VS Code file explorer
- **Terminal**: Access terminal within VS Code interface

## 🆘 Need Help?

- Check logs: `npm run dev` (shows server logs)
- AI issues: Test API key with curl command above
- UI issues: Check browser console (F12)
- Server issues: Check Node.js and code-server installation

---

**🎉 You're ready to code with AI!** 