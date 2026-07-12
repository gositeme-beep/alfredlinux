# Alfred CLI

Command-line access to Alfred AI's 1,290+ tools. Chat, execute tools, manage agents, and more — all from your terminal.

## Installation

```bash
npm install -g alfred-cli
```

## Quick Start

```bash
# Authenticate with your API key
alfred login

# Chat with Alfred
alfred chat "What are the best SEO practices for 2026?"

# Execute a tool
alfred exec seo-analyzer --args '{"url": "https://example.com"}'

# List available tools
alfred tools --search "image"

# Start interactive mode
alfred interactive
```

## Commands

| Command | Description |
|---------|-------------|
| `alfred login` | Authenticate with your API key |
| `alfred chat "<message>"` | Send a message to Alfred |
| `alfred exec <tool> [--args <json>]` | Execute a specific tool |
| `alfred tools [--search <q>] [--category <cat>]` | List or search tools |
| `alfred agents` | List available agents |
| `alfred fleet` | Show fleet status |
| `alfred interactive` | Start interactive REPL mode |
| `alfred voice` | Voice mode (coming soon) |
| `alfred config` | Show/edit configuration |
| `alfred version` | Show version |

## Options

All commands support:
- `-j, --json` — Output raw JSON response
- `-h, --help` — Show help for command

## Configuration

Configuration is stored in your system config directory:
- **macOS**: `~/Library/Preferences/alfred-cli-nodejs/config.json`
- **Linux**: `~/.config/alfred-cli-nodejs/config.json`
- **Windows**: `%APPDATA%/alfred-cli-nodejs/config.json`

### Config options

```bash
# Set custom base URL
alfred config --set baseUrl=https://gositeme.com/api/v1/

# Set default output format
alfred config --set outputFormat=json

# Reset all configuration
alfred config --reset
```

## Getting Your API Key

1. Visit [https://gositeme.com/developer-portal.php](https://gositeme.com/developer-portal.php)
2. Sign in to your account
3. Generate an API key from the dashboard
4. Run `alfred login` and paste your key

## Examples

### Chat

```bash
alfred chat "Write a Python function to calculate fibonacci numbers"
```

### Tool Execution

```bash
# SEO analysis
alfred exec seo-analyzer --args '{"url": "https://example.com"}'

# Image generation
alfred exec image-generator --args '{"prompt": "A sunset over mountains"}'

# Code review
alfred exec code-review --args '{"code": "function add(a,b){return a+b}", "language": "javascript"}'
```

### Interactive Mode

```bash
$ alfred interactive
  Alfred Interactive Mode
  Type your message and press Enter. Type "exit" to leave.

alfred> What is quantum computing?

  Alfred: Quantum computing uses quantum mechanics principles...

alfred> exit
  Goodbye!
```

## Using as a Library

```javascript
import { AlfredAPI } from 'alfred-cli';

const api = new AlfredAPI('your-api-key');

// Chat
const response = await api.chat('Hello, Alfred!');
console.log(response.reply);

// Execute tool
const result = await api.executeTool('seo-analyzer', { url: 'https://example.com' });
console.log(result);

// List tools
const tools = await api.listTools('image');
console.log(tools);
```

## Requirements

- Node.js 18+ 
- An Alfred API key ([get one here](https://gositeme.com/developer-portal.php))

## License

MIT — [GoSiteMe](https://gositeme.com)
