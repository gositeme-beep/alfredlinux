#!/usr/bin/env node

/* ==========================================================
   Alfred CLI — Main Entry Point
   ========================================================== */

import { Command } from 'commander';
import chalk from 'chalk';
import ora from 'ora';
import inquirer from 'inquirer';
import { AlfredAPI } from '../lib/api.js';
import { Config } from '../lib/config.js';

const config = new Config();
const program = new Command();

program
  .name('alfred')
  .description('Command-line access to Alfred AI\'s 1,290+ tools')
  .version('1.0.0');

/* ---------- alfred login ---------- */
program
  .command('login')
  .description('Authenticate with your Alfred API key')
  .action(async () => {
    const { apiKey } = await inquirer.prompt([
      {
        type: 'password',
        name: 'apiKey',
        message: 'Enter your Alfred API key:',
        mask: '*',
        validate: v => v.length > 10 || 'API key seems too short'
      }
    ]);

    const spinner = ora('Verifying API key…').start();
    try {
      const api = new AlfredAPI(apiKey, config.get('baseUrl'));
      const result = await api.chat('Hello, verify my key works.');
      config.set('apiKey', apiKey);
      spinner.succeed(chalk.green('Authenticated successfully!'));
      if (result.reply) console.log(chalk.dim(`Alfred says: ${result.reply}`));
    } catch (err) {
      spinner.fail(chalk.red(`Authentication failed: ${err.message}`));
    }
  });

/* ---------- alfred chat ---------- */
program
  .command('chat')
  .description('Chat with Alfred')
  .argument('<message>', 'Message to send to Alfred')
  .option('-j, --json', 'Output raw JSON response')
  .action(async (message, opts) => {
    const api = getApi();
    const spinner = ora('Thinking…').start();
    try {
      const result = await api.chat(message);
      spinner.stop();
      if (opts.json) {
        console.log(JSON.stringify(result, null, 2));
      } else {
        console.log(chalk.cyan('Alfred: ') + (result.reply || JSON.stringify(result)));
      }
    } catch (err) {
      spinner.fail(chalk.red(err.message));
    }
  });

/* ---------- alfred exec ---------- */
program
  .command('exec')
  .description('Execute a specific Alfred tool')
  .argument('<tool>', 'Tool name or ID')
  .option('-a, --args <json>', 'Tool arguments as JSON string', '{}')
  .option('-j, --json', 'Output raw JSON response')
  .action(async (tool, opts) => {
    const api = getApi();
    let args;
    try {
      args = JSON.parse(opts.args);
    } catch {
      console.error(chalk.red('Invalid JSON for --args'));
      process.exit(1);
    }

    const spinner = ora(`Executing tool: ${tool}…`).start();
    try {
      const result = await api.executeTool(tool, args);
      spinner.stop();
      if (opts.json) {
        console.log(JSON.stringify(result, null, 2));
      } else {
        console.log(chalk.green(`✔ ${tool} completed`));
        console.log(result.result || JSON.stringify(result, null, 2));
      }
    } catch (err) {
      spinner.fail(chalk.red(err.message));
    }
  });

/* ---------- alfred tools ---------- */
program
  .command('tools')
  .description('List or search available tools')
  .option('-s, --search <query>', 'Search tools by name or description')
  .option('-c, --category <cat>', 'Filter by category')
  .option('-j, --json', 'Output raw JSON')
  .action(async (opts) => {
    const api = getApi();
    const spinner = ora('Fetching tools…').start();
    try {
      const result = await api.listTools(opts.search, opts.category);
      spinner.stop();

      if (opts.json) {
        console.log(JSON.stringify(result, null, 2));
        return;
      }

      const tools = result.tools || result.data || [];
      if (!tools.length) {
        console.log(chalk.yellow('No tools found.'));
        return;
      }

      console.log(chalk.bold(`\n  Found ${tools.length} tool(s)\n`));
      tools.forEach(t => {
        console.log(`  ${chalk.cyan(t.name || t.slug)}  ${chalk.dim(t.description || '')}`);
      });
      console.log();
    } catch (err) {
      spinner.fail(chalk.red(err.message));
    }
  });

/* ---------- alfred agents ---------- */
program
  .command('agents')
  .description('List available agents')
  .option('-j, --json', 'Output raw JSON')
  .action(async (opts) => {
    const api = getApi();
    const spinner = ora('Fetching agents…').start();
    try {
      const result = await api.listAgents();
      spinner.stop();

      if (opts.json) {
        console.log(JSON.stringify(result, null, 2));
        return;
      }

      const agents = result.agents || result.data || [];
      console.log(chalk.bold(`\n  ${agents.length} agent(s)\n`));
      agents.forEach(a => {
        const status = a.status === 'active' ? chalk.green('●') : chalk.dim('○');
        console.log(`  ${status} ${chalk.cyan(a.name)}  ${chalk.dim(a.description || '')}`);
      });
      console.log();
    } catch (err) {
      spinner.fail(chalk.red(err.message));
    }
  });

/* ---------- alfred fleet ---------- */
program
  .command('fleet')
  .description('Show fleet status')
  .option('-j, --json', 'Output raw JSON')
  .action(async (opts) => {
    const api = getApi();
    const spinner = ora('Fetching fleet status…').start();
    try {
      const result = await api.fleetStatus();
      spinner.stop();

      if (opts.json) {
        console.log(JSON.stringify(result, null, 2));
        return;
      }

      console.log(chalk.bold('\n  Fleet Status\n'));
      const fleet = result.fleet || result.data || {};
      console.log(`  Agents: ${chalk.cyan(fleet.total_agents || 0)}`);
      console.log(`  Active: ${chalk.green(fleet.active || 0)}`);
      console.log(`  Tasks:  ${chalk.yellow(fleet.pending_tasks || 0)} pending`);
      console.log();
    } catch (err) {
      spinner.fail(chalk.red(err.message));
    }
  });

/* ---------- alfred interactive ---------- */
program
  .command('interactive')
  .alias('repl')
  .description('Start interactive REPL mode')
  .action(async () => {
    const api = getApi();
    console.log(chalk.bold.cyan('\n  Alfred Interactive Mode'));
    console.log(chalk.dim('  Type your message and press Enter. Type "exit" or "quit" to leave.\n'));

    const readline = await import('readline');
    const rl = readline.createInterface({
      input: process.stdin,
      output: process.stdout,
      prompt: chalk.cyan('alfred> ')
    });

    rl.prompt();

    rl.on('line', async (line) => {
      const input = line.trim();
      if (!input) { rl.prompt(); return; }
      if (input === 'exit' || input === 'quit') {
        console.log(chalk.dim('\n  Goodbye!\n'));
        rl.close();
        process.exit(0);
      }

      const spinner = ora('Thinking…').start();
      try {
        const result = await api.chat(input);
        spinner.stop();
        console.log(chalk.green('\n  Alfred: ') + (result.reply || JSON.stringify(result)));
        console.log();
      } catch (err) {
        spinner.fail(chalk.red(err.message));
      }
      rl.prompt();
    });
  });

/* ---------- alfred voice ---------- */
program
  .command('voice')
  .description('Start voice mode (coming soon)')
  .action(() => {
    console.log(chalk.yellow('\n  Voice mode is coming soon!'));
    console.log(chalk.dim('  This feature will allow voice interaction with Alfred from the terminal.\n'));
  });

/* ---------- alfred config ---------- */
program
  .command('config')
  .description('Show or edit configuration')
  .option('--set <key=value>', 'Set a configuration value')
  .option('--reset', 'Reset all configuration')
  .action((opts) => {
    if (opts.reset) {
      config.clear();
      console.log(chalk.green('Configuration reset.'));
      return;
    }

    if (opts.set) {
      const [key, ...rest] = opts.set.split('=');
      const value = rest.join('=');
      config.set(key, value);
      console.log(chalk.green(`Set ${key} = ${value}`));
      return;
    }

    console.log(chalk.bold('\n  Alfred CLI Configuration\n'));
    console.log(`  API Key:    ${config.get('apiKey') ? chalk.green('✔ configured') : chalk.red('✘ not set')}`);
    console.log(`  Base URL:   ${chalk.cyan(config.get('baseUrl'))}`);
    console.log(`  Format:     ${chalk.cyan(config.get('outputFormat'))}`);
    console.log();
  });

/* ---------- Helpers ---------- */
function getApi() {
  const apiKey = config.get('apiKey');
  if (!apiKey) {
    console.error(chalk.red('Not authenticated. Run: alfred login'));
    process.exit(1);
  }
  return new AlfredAPI(apiKey, config.get('baseUrl'));
}

program.parse();
