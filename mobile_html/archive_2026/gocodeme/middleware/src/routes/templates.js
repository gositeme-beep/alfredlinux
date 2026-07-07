'use strict';

/**
 * Workspace Templates — /api/templates/*
 *
 * "Start from template" feature like Cursor/Windsurf.
 * Users can launch a new workspace pre-populated with a project scaffold
 * (Next.js, Laravel, WordPress, static site, Python API, etc.)
 *
 * Templates are stored as Git repo URLs or tarballs.
 * When a user selects one, we clone/extract into their workspace.
 *
 * Endpoints:
 *   GET  /api/templates         — List available templates
 *   POST /api/templates/apply   — Apply a template to the user's workspace
 */

const express  = require('express');
const { execSync, execFileSync } = require('child_process');
const fs       = require('fs');
const path     = require('path');
const router   = express.Router();

const { requireSession } = require('../auth/middleware');
const logger = require('../logger');
const safeError = require('../utils/safeError');

// ── Template catalog ─────────────────────────────────────────────────────────
const TEMPLATES = [
  {
    id: 'nextjs',
    name: 'Next.js App',
    description: 'React framework with App Router, Tailwind CSS, TypeScript',
    icon: '⚡',
    category: 'Frontend',
    command: 'npx create-next-app@latest . --typescript --tailwind --eslint --app --src-dir --use-npm --no-git --import-alias "@/*"',
  },
  {
    id: 'react-vite',
    name: 'React + Vite',
    description: 'Lightning-fast React with Vite, TypeScript, ESLint',
    icon: '⚛️',
    category: 'Frontend',
    command: 'npm create vite@latest tmpvite -- --template react-ts && mv tmpvite/* tmpvite/.* . 2>/dev/null; rm -rf tmpvite',
  },
  {
    id: 'vue-vite',
    name: 'Vue 3 + Vite',
    description: 'Vue 3 Composition API with Vite and TypeScript',
    icon: '💚',
    category: 'Frontend',
    command: 'npm create vite@latest tmpvue -- --template vue-ts && mv tmpvue/* tmpvue/.* . 2>/dev/null; rm -rf tmpvue',
  },
  {
    id: 'express-api',
    name: 'Express API',
    description: 'Node.js REST API with Express, CORS, dotenv',
    icon: '🟢',
    category: 'Backend',
    setup: (dir) => {
      const pkg = { name: 'express-api', version: '1.0.0', main: 'src/index.js', scripts: { start: 'node src/index.js', dev: 'node --watch src/index.js' }, dependencies: {} };
      const indexJs = `const express = require('express');
const cors = require('cors');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

app.get('/', (req, res) => {
  res.json({ message: 'Hello from GoCodeMe!', timestamp: new Date().toISOString() });
});

app.get('/api/health', (req, res) => {
  res.json({ ok: true });
});

app.listen(PORT, () => console.log(\`Server running on port \${PORT}\`));
`;
      const env = 'PORT=3000\nNODE_ENV=development\n';
      fs.mkdirSync(path.join(dir, 'src'), { recursive: true });
      fs.writeFileSync(path.join(dir, 'package.json'), JSON.stringify(pkg, null, 2));
      fs.writeFileSync(path.join(dir, 'src', 'index.js'), indexJs);
      fs.writeFileSync(path.join(dir, '.env'), env);
      fs.writeFileSync(path.join(dir, '.gitignore'), 'node_modules/\n.env\n');
      // SECURITY (R3 M-08): Use execFileSync (no shell) for npm install
      execFileSync('npm', ['install', 'express', 'cors', 'dotenv'], { cwd: dir, stdio: 'pipe', timeout: 60000 });
    },
  },
  {
    id: 'python-flask',
    name: 'Python Flask API',
    description: 'Flask REST API with CORS, dotenv, requirements.txt',
    icon: '🐍',
    category: 'Backend',
    setup: (dir) => {
      const appPy = `from flask import Flask, jsonify
from flask_cors import CORS
from dotenv import load_dotenv
import os

load_dotenv()

app = Flask(__name__)
CORS(app)

@app.route('/')
def hello():
    return jsonify(message='Hello from GoCodeMe!', status='ok')

@app.route('/api/health')
def health():
    return jsonify(ok=True)

if __name__ == '__main__':
    port = int(os.getenv('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=True)
`;
      const reqs = 'flask\nflask-cors\npython-dotenv\n';
      fs.writeFileSync(path.join(dir, 'app.py'), appPy);
      fs.writeFileSync(path.join(dir, 'requirements.txt'), reqs);
      fs.writeFileSync(path.join(dir, '.env'), 'PORT=5000\nFLASK_DEBUG=1\n');
      fs.writeFileSync(path.join(dir, '.gitignore'), '__pycache__/\n*.pyc\n.env\nvenv/\n');
      try {
        // SECURITY (R3 M-08): Use execFileSync (no shell) for pip install
        execFileSync('pip', ['install', '-r', 'requirements.txt', '--user', '--quiet'], { cwd: dir, stdio: 'pipe', timeout: 60000 });
      } catch { /* pip may not be available */ }
    },
  },
  {
    id: 'static-html',
    name: 'Static Website',
    description: 'HTML5 + CSS3 + vanilla JS starter with responsive layout',
    icon: '🌐',
    category: 'Frontend',
    setup: (dir) => {
      const html = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Website</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header>
    <nav>
      <h1>My Website</h1>
      <ul>
        <li><a href="#home">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>
    </nav>
  </header>
  <main>
    <section id="home">
      <h2>Welcome</h2>
      <p>Start building your website here.</p>
    </section>
  </main>
  <footer>
    <p>&copy; ${new Date().getFullYear()} My Website. Built with GoCodeMe.</p>
  </footer>
  <script src="script.js"></script>
</body>
</html>`;
      const css = `*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: system-ui, -apple-system, sans-serif; line-height: 1.6; color: #333; }
header { background: #1a1a2e; color: #fff; padding: 1rem 2rem; }
nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; }
nav ul { list-style: none; display: flex; gap: 1.5rem; }
nav a { color: #fff; text-decoration: none; transition: opacity .2s; }
nav a:hover { opacity: .7; }
main { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
footer { text-align: center; padding: 2rem; color: #666; margin-top: 4rem; }
`;
      const js = `// Your JavaScript here\nconsole.log('Website loaded!');\n`;
      fs.writeFileSync(path.join(dir, 'index.html'), html);
      fs.writeFileSync(path.join(dir, 'style.css'), css);
      fs.writeFileSync(path.join(dir, 'script.js'), js);
      fs.writeFileSync(path.join(dir, '.gitignore'), '.DS_Store\n');
    },
  },
  {
    id: 'php-laravel',
    name: 'Laravel API',
    description: 'PHP Laravel framework (requires Composer)',
    icon: '🔴',
    category: 'Backend',
    command: 'composer create-project laravel/laravel . --prefer-dist --no-interaction 2>/dev/null || echo "Composer not available — install PHP/Composer first"',
  },
  {
    id: 'wordpress',
    name: 'WordPress Theme',
    description: 'Custom WordPress theme starter with functions.php',
    icon: '📝',
    category: 'CMS',
    setup: (dir) => {
      const styleCss = `/*\nTheme Name: GoCodeMe Theme\nAuthor: GoCodeMe User\nDescription: Custom WordPress theme\nVersion: 1.0\n*/\n\nbody { font-family: system-ui, sans-serif; }`;
      const indexPhp = `<?php get_header(); ?>\n<main>\n  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>\n    <article>\n      <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>\n      <?php the_excerpt(); ?>\n    </article>\n  <?php endwhile; endif; ?>\n</main>\n<?php get_footer(); ?>`;
      const functionsPhp = `<?php\n// Theme setup\nfunction theme_setup() {\n  add_theme_support('title-tag');\n  add_theme_support('post-thumbnails');\n  add_theme_support('html5', ['search-form', 'comment-form', 'gallery']);\n}\nadd_action('after_setup_theme', 'theme_setup');\n\n// Enqueue styles\nfunction theme_styles() {\n  wp_enqueue_style('theme-style', get_stylesheet_uri());\n}\nadd_action('wp_enqueue_scripts', 'theme_styles');`;
      const headerPhp = `<!DOCTYPE html>\n<html <?php language_attributes(); ?>>\n<head>\n  <meta charset="<?php bloginfo('charset'); ?>">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <?php wp_head(); ?>\n</head>\n<body <?php body_class(); ?>>\n<header>\n  <h1><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h1>\n</header>`;
      const footerPhp = `<footer>\n  <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>\n</footer>\n<?php wp_footer(); ?>\n</body>\n</html>`;
      fs.writeFileSync(path.join(dir, 'style.css'), styleCss);
      fs.writeFileSync(path.join(dir, 'index.php'), indexPhp);
      fs.writeFileSync(path.join(dir, 'functions.php'), functionsPhp);
      fs.writeFileSync(path.join(dir, 'header.php'), headerPhp);
      fs.writeFileSync(path.join(dir, 'footer.php'), footerPhp);
      fs.writeFileSync(path.join(dir, '.gitignore'), '.DS_Store\n');
    },
  },
];

// ── GET /api/templates — List all templates ─────────────────────────────────
router.get('/', (_req, res) => {
  const catalog = TEMPLATES.map(t => ({
    id: t.id,
    name: t.name,
    description: t.description,
    icon: t.icon,
    category: t.category,
  }));
  res.json({ ok: true, templates: catalog });
});

// ── POST /api/templates/apply — Apply a template ───────────────────────────
router.post('/apply', requireSession, async (req, res) => {
  const { templateId } = req.body;
  if (!templateId) return res.status(400).json({ ok: false, error: 'templateId required' });

  const template = TEMPLATES.find(t => t.id === templateId);
  if (!template) return res.status(404).json({ ok: false, error: 'Template not found' });

  const { daUsername } = req.user;
  // SECURITY (R3 M-08): Validate daUsername before constructing paths used by shell
  if (!/^[a-z][a-z0-9]{2,15}$/.test(daUsername)) {
    return res.status(400).json({ ok: false, error: 'Invalid username format' });
  }
  const workDir = `/tmp/gocodeme-workspace-${daUsername}`;

  if (!fs.existsSync(workDir)) {
    return res.status(400).json({ ok: false, error: 'Workspace not found. Launch IDE first.' });
  }

  // Check if workspace is not empty (has more than config dirs)
  const files = fs.readdirSync(workDir).filter(f => !f.startsWith('.'));
  if (files.length > 0) {
    return res.status(409).json({ ok: false, error: 'Workspace is not empty. Clear it first or use an empty workspace.' });
  }

  try {
    if (template.setup) {
      // Inline setup function
      template.setup(workDir);
    } else if (template.command) {
      // Shell command
      execSync(template.command, {
        cwd: workDir,
        stdio: 'pipe',
        timeout: 120000,
        env: { ...process.env, HOME: workDir },
      });
    }

    logger.info(`Template ${templateId} applied to workspace for ${daUsername}`);
    res.json({ ok: true, template: templateId, message: `${template.name} template applied successfully` });
  } catch (err) {
    logger.error(`Template apply error (${templateId}):`, err.message);
    res.status(500).json({ ok: false, error: 'Failed to apply template. Please try again or contact support.' });
  }
});

module.exports = router;
