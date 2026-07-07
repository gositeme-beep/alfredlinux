/**
 * MCP Tool Definitions
 *
 * Describes all tools the GoCodeMe MCP server exposes to Theia and OpenHands.
 * The descriptions are written so Claude understands exactly what each tool does
 * and when to use it when acting as an autonomous coding agent.
 *
 * Categories:
 *   - File management (read, write, list, delete, rename, search, stat)
 *   - Database management (MySQL: create, list, delete, info)
 *   - Domain management (domains, subdomains)
 *   - Email management (create accounts, forwarders, auto-responders)
 *   - DNS management (add/list/delete DNS records)
 *   - SSL management (Let's Encrypt, force HTTPS)
 *   - Cron job management (scheduled tasks)
 *   - Backup management (create/restore backups)
 *   - Account stats (disk, bandwidth, usage)
 */

export const toolDefinitions = [
  // ══════════════════════════════════════════════════════════════════════════
  // FILE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'read_file',
    description:
      "Read the full contents of a file in the customer's live DirectAdmin hosting account. " +
      'Path is relative to the user home directory (e.g. "public_html/index.php"). ' +
      'Returns the raw file content as a string.',
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'File path relative to the user home directory.',
        },
      },
      required: ['path'],
    },
  },

  {
    name: 'write_file',
    description:
      "Write or overwrite a file in the customer's live DirectAdmin hosting account. " +
      'The file is immediately live on their domain — no deploy step needed. ' +
      'Creates parent directories automatically if they do not exist. ' +
      'A checkpoint is auto-created before the first write in a batch. ' +
      'For major changes, also call create_checkpoint with a descriptive label first.',
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'File path relative to the user home directory.',
        },
        content: {
          type: 'string',
          description: 'Full file content to write.',
        },
      },
      required: ['path', 'content'],
    },
  },

  {
    name: 'list_directory',
    description:
      "List files and subdirectories at a path in the customer's DirectAdmin account. " +
      'Defaults to "public_html" (the web root). Returns an array of file objects with ' +
      'name, type (file/directory), size, and last-modified date.',
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'Directory path relative to user home. Defaults to "public_html".',
          default: 'public_html',
        },
      },
    },
  },

  {
    name: 'delete_file',
    description:
      "Delete a file or directory from the customer's DirectAdmin account. " +
      'Deletion is permanent. A checkpoint is auto-created before deletion. ' +
      'For important files, create a named checkpoint with create_checkpoint first.',
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'Path to the file or directory to delete.',
        },
      },
      required: ['path'],
    },
  },

  {
    name: 'rename_file',
    description:
      "Rename or move a file or directory within the customer's account. " +
      'Both old and new paths must stay within the customer home directory.',
    inputSchema: {
      type: 'object',
      properties: {
        old_path: { type: 'string', description: 'Current path.' },
        new_path: { type: 'string', description: 'New path or name.' },
      },
      required: ['old_path', 'new_path'],
    },
  },

  {
    name: 'create_directory',
    description: "Create a new directory in the customer's DirectAdmin account.",
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'Directory path to create, relative to user home.',
        },
      },
      required: ['path'],
    },
  },

  {
    name: 'search_files',
    description:
      "Search for a text pattern across all files in the customer's workspace. " +
      'Returns a list of file paths that contain the pattern. ' +
      'Useful for finding where a function is defined, where a variable is used, etc.',
    inputSchema: {
      type: 'object',
      properties: {
        pattern: {
          type: 'string',
          description: 'Text or regex pattern to search for.',
        },
        directory: {
          type: 'string',
          description: 'Directory to search within. Defaults to "public_html".',
          default: 'public_html',
        },
        case_sensitive: {
          type: 'boolean',
          description: 'Whether the search is case-sensitive. Defaults to false.',
          default: false,
        },
      },
      required: ['pattern'],
    },
  },

  {
    name: 'find_file',
    description:
      'Find files by name or glob pattern across the workspace. ' +
      'Returns matching file paths. Use this when you need to locate a file by its name ' +
      'without knowing the exact directory.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'File name or glob pattern to search for (e.g. "alfred.php", "*.config.js", "package.json").',
        },
        directory: {
          type: 'string',
          description: 'Directory to search within. Defaults to "public_html".',
          default: 'public_html',
        },
      },
      required: ['name'],
    },
  },

  {
    name: 'get_file_info',
    description:
      "Get metadata about a file or directory: size, permissions, last-modified date, type. " +
      'Use this to check if a file exists before reading or writing it.',
    inputSchema: {
      type: 'object',
      properties: {
        path: {
          type: 'string',
          description: 'Path to the file or directory.',
        },
      },
      required: ['path'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DATABASE MANAGEMENT (MySQL)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'list_databases',
    description:
      "List all MySQL databases in the customer's DirectAdmin hosting account. " +
      'Returns an array of database names. Use this to check existing databases before creating new ones.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'create_database',
    description:
      'Create a new MySQL database with a user and password. ' +
      'DirectAdmin auto-prefixes the database and user names with the account username. ' +
      'For example, if you pass name="shop" and user="shopuser", the actual database will be ' +
      '"username_shop" and the user will be "username_shopuser". ' +
      'Returns the full database name, username, host (localhost), and password. ' +
      'Use this when building PHP/MySQL websites — create the database first, then write ' +
      'a config.php with the returned credentials.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Short database name (without username prefix). E.g. "shop", "blog", "app".',
        },
        user: {
          type: 'string',
          description: 'Short database username (without prefix). E.g. "shopuser", "admin".',
        },
        password: {
          type: 'string',
          description: 'Password for the database user. Use a strong random password.',
        },
      },
      required: ['name', 'user', 'password'],
    },
  },

  {
    name: 'delete_database',
    description:
      'Delete a MySQL database. Pass the FULL database name (with prefix). ' +
      'This is permanent and cannot be undone. Always create a backup first.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Full database name to delete (e.g. "username_shop").',
        },
      },
      required: ['name'],
    },
  },

  {
    name: 'get_database_info',
    description:
      'Get details about a specific MySQL database including its users and size.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Full database name (e.g. "username_shop").',
        },
      },
      required: ['name'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DOMAIN MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'list_domains',
    description:
      "List all domains configured in the customer's DirectAdmin hosting account. " +
      'Returns domain names. Use this to find the correct domain when setting up websites, ' +
      'email, DNS, or SSL.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'list_subdomains',
    description: 'List all subdomains for a specific domain.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'The parent domain (e.g. "example.com").',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'create_subdomain',
    description:
      'Create a new subdomain under a domain. For example, creating "blog" under "example.com" ' +
      'creates blog.example.com. The subdomain\'s document root is automatically set up at ' +
      'public_html/blog/ (or similar DirectAdmin convention).',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Parent domain (e.g. "example.com").',
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain name to create (e.g. "blog", "shop", "api").',
        },
      },
      required: ['domain', 'subdomain'],
    },
  },

  {
    name: 'delete_subdomain',
    description: 'Delete a subdomain. This removes the subdomain configuration but files remain.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Parent domain.',
        },
        subdomain: {
          type: 'string',
          description: 'Subdomain name to delete.',
        },
      },
      required: ['domain', 'subdomain'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // EMAIL MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'list_email_accounts',
    description:
      'List all email accounts for a domain. Returns usernames (without @domain).',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'The domain to list email accounts for.',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'create_email_account',
    description:
      'Create a new email account (POP3/IMAP). For example, creating "info" on "example.com" ' +
      'creates info@example.com. Returns the full email address and server connection details ' +
      '(IMAP port 993, SMTP port 587, POP3 port 995, all with TLS). ' +
      'Use this when setting up a website and the customer needs a professional email address.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain for the email (e.g. "example.com").',
        },
        user: {
          type: 'string',
          description: 'Email username / local part (e.g. "info", "contact", "admin").',
        },
        password: {
          type: 'string',
          description: 'Password for the email account.',
        },
        quota: {
          type: 'number',
          description: 'Mailbox quota in MB. Defaults to 200. Use 0 for unlimited.',
          default: 200,
        },
      },
      required: ['domain', 'user', 'password'],
    },
  },

  {
    name: 'delete_email_account',
    description: 'Delete an email account. This permanently removes the mailbox and all messages.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
        user:   { type: 'string', description: 'Email username to delete.' },
      },
      required: ['domain', 'user'],
    },
  },

  {
    name: 'create_email_forwarder',
    description:
      'Create an email forwarder. All mail sent to user@domain will be forwarded to the destination address.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain.' },
        user:      { type: 'string', description: 'Local email user to forward from.' },
        forwardTo: { type: 'string', description: 'Destination email address to forward to.' },
      },
      required: ['domain', 'user', 'forwardTo'],
    },
  },

  {
    name: 'create_autoresponder',
    description:
      'Set up an automatic email reply (vacation responder / out-of-office). ' +
      'Anyone who emails user@domain will receive the auto-reply message.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:  { type: 'string', description: 'Domain.' },
        user:    { type: 'string', description: 'Email username.' },
        subject: { type: 'string', description: 'Auto-reply subject line.' },
        message: { type: 'string', description: 'Auto-reply body text.' },
      },
      required: ['domain', 'user', 'subject', 'message'],
    },
  },

  {
    name: 'send_email',
    description:
      'Send an email from an existing email account on the customer\'s domain. ' +
      'The "from" address must be an email account that already exists on one of the customer\'s domains ' +
      '(use list_email_accounts first if unsure). Supports plain text and HTML bodies. ' +
      'Can send to any external email address. Supports CC and BCC. ' +
      'Use this when the customer asks to send an email, test email delivery, ' +
      'send notifications, or communicate with someone via email.',
    inputSchema: {
      type: 'object',
      properties: {
        from: {
          type: 'string',
          description: 'Sender email address — must be an existing email account on one of the customer\'s domains (e.g. "support@example.com"). Can also be formatted as "Name <email@domain>" (e.g. "Alfred AI <support@example.com>").',
        },
        to: {
          type: 'string',
          description: 'Recipient email address(es). For multiple recipients, separate with commas.',
        },
        subject: {
          type: 'string',
          description: 'Email subject line.',
        },
        text: {
          type: 'string',
          description: 'Plain text body of the email. At least one of "text" or "html" is required.',
        },
        html: {
          type: 'string',
          description: 'HTML body of the email (optional). If provided alongside text, email clients will show HTML version with text as fallback.',
        },
        cc: {
          type: 'string',
          description: 'CC recipient(s). Optional. Comma-separated for multiple.',
        },
        bcc: {
          type: 'string',
          description: 'BCC recipient(s). Optional. Comma-separated for multiple.',
        },
        replyTo: {
          type: 'string',
          description: 'Reply-To address. Optional. Defaults to the from address.',
        },
      },
      required: ['from', 'to', 'subject'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DNS MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'list_dns_records',
    description:
      'List all DNS records for a domain. Returns type, name, value, and TTL for each record. ' +
      'Use this to audit DNS configuration or before adding new records.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain to list DNS records for.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'add_dns_record',
    description:
      'Add a DNS record to a domain. Supports A, AAAA, CNAME, MX, TXT, SRV records. ' +
      'Common uses: point a subdomain to an IP (A record), add SPF/DKIM/DMARC for email (TXT), ' +
      'set up mail routing (MX), or create domain aliases (CNAME).',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
        type:   { type: 'string', description: 'Record type: A, AAAA, CNAME, MX, TXT, SRV.', enum: ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV'] },
        name:   { type: 'string', description: 'Record name (e.g. "www", "@", "mail", "api").' },
        value:  { type: 'string', description: 'Record value (IP address, hostname, or text content).' },
        ttl:    { type: 'number', description: 'Time-to-live in seconds. Defaults to 14400.', default: 14400 },
      },
      required: ['domain', 'type', 'name', 'value'],
    },
  },

  {
    name: 'delete_dns_record',
    description: 'Delete a DNS record from a domain. Specify the exact type, name, and value to remove.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
        type:   { type: 'string', description: 'Record type.' },
        name:   { type: 'string', description: 'Record name.' },
        value:  { type: 'string', description: 'Record value.' },
      },
      required: ['domain', 'type', 'name', 'value'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SSL MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'request_ssl_certificate',
    description:
      "Request a free Let's Encrypt SSL certificate for a domain. " +
      'This enables HTTPS. Automatically covers the domain, www.domain, and mail.domain. ' +
      'Set wildcard=true for a *.domain certificate. ' +
      'Always do this when setting up a new website.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain to get SSL for.' },
        wildcard: { type: 'boolean', description: 'Request wildcard cert (*.domain). Defaults to false.', default: false },
      },
      required: ['domain'],
    },
  },

  {
    name: 'get_ssl_status',
    description: 'Check current SSL certificate status for a domain.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'force_https',
    description:
      'Enable forced HTTPS redirect for a domain. All HTTP requests will be redirected to HTTPS. ' +
      'Only enable this after an SSL certificate is installed.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CRON JOB MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'list_cron_jobs',
    description: "List all scheduled cron jobs for the customer's account.",
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'create_cron_job',
    description:
      'Create a scheduled cron job. Use standard cron syntax for scheduling. ' +
      'Common patterns: "0 * * * *" (every hour), "0 0 * * *" (daily midnight), ' +
      '"*/5 * * * *" (every 5 minutes), "0 2 * * 0" (weekly Sunday 2am). ' +
      'Commands run as the hosting user with access to PHP, Python, etc.',
    inputSchema: {
      type: 'object',
      properties: {
        minute:     { type: 'string', description: 'Minute (0-59, *, */N).', default: '*' },
        hour:       { type: 'string', description: 'Hour (0-23, *, */N).', default: '*' },
        dayOfMonth: { type: 'string', description: 'Day of month (1-31, *, */N).', default: '*' },
        month:      { type: 'string', description: 'Month (1-12, *, */N).', default: '*' },
        dayOfWeek:  { type: 'string', description: 'Day of week (0-7, 0=7=Sunday).', default: '*' },
        command:    { type: 'string', description: 'Command to execute (e.g. "php /home/user/public_html/cron.php").' },
      },
      required: ['command'],
    },
  },

  {
    name: 'delete_cron_job',
    description: 'Delete a cron job by its index number (0-based). Use list_cron_jobs first to find the index.',
    inputSchema: {
      type: 'object',
      properties: {
        index: { type: 'number', description: 'Index of the cron job to delete (0-based).' },
      },
      required: ['index'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // BACKUP MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'create_backup',
    description:
      "Create a backup of the customer's account. Can include files, databases, and email. " +
      'The backup is queued and will be available in the backups directory when complete. ' +
      'Always create a backup before making major changes.',
    inputSchema: {
      type: 'object',
      properties: {
        files:     { type: 'boolean', description: 'Include files. Defaults to true.', default: true },
        databases: { type: 'boolean', description: 'Include databases. Defaults to true.', default: true },
        email:     { type: 'boolean', description: 'Include email. Defaults to true.', default: true },
      },
    },
  },

  {
    name: 'list_backups',
    description: 'List available backup files that can be restored.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'restore_backup',
    description:
      'Restore from a backup file. Can selectively restore files, databases, and/or email. ' +
      'Use list_backups first to find available backup files.',
    inputSchema: {
      type: 'object',
      properties: {
        backupFile: { type: 'string', description: 'Backup filename to restore from.' },
        files:      { type: 'boolean', description: 'Restore files. Defaults to true.', default: true },
        databases:  { type: 'boolean', description: 'Restore databases. Defaults to true.', default: true },
        email:      { type: 'boolean', description: 'Restore email. Defaults to true.', default: true },
      },
      required: ['backupFile'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ACCOUNT STATS / USAGE
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'get_account_usage',
    description:
      "Get the customer's hosting account usage statistics: disk space used, bandwidth consumed, " +
      'number of databases, email accounts, domains, etc. Use this to check resource usage ' +
      'before creating new resources.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'get_account_limits',
    description:
      "Get the customer's hosting account limits and configuration: max disk quota, bandwidth limit, " +
      'max databases, max email accounts, PHP version, package name, etc.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'get_account_summary',
    description:
      "Get a complete summary of the customer's hosting account: usage vs limits for disk, bandwidth, " +
      'databases, email, domains, subdomains. Also shows PHP version, account package, and suspension status. ' +
      'This is the best tool to get an overall view of the account.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // COMMERCE — Domains, Hosting, Billing, Support (via WHMCS)
  // These tools let the customer manage their account, order services, and
  // handle billing through natural language conversation with Alfred.
  //
  // SAFETY: Tools that spend money require confirmed=true parameter.
  // Alfred MUST show the user the preview/cost first, then ask "Shall I
  // proceed?" before calling again with confirmed=true.
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'get_my_profile',
    description:
      "Get the customer's WHMCS profile: name, email, company, country, account credit balance, and status. " +
      'Use this to greet the customer by name or check their account credit.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'get_my_services',
    description:
      "List all hosting products, domains, and addons the customer currently owns. Shows status, billing cycle, " +
      'next due date, and recurring amount for each. Use this to see what the customer already has.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'get_product_catalog',
    description:
      'Browse the full product catalog — all hosting plans, addons, and services available for purchase. ' +
      'Returns product IDs, names, descriptions, pricing by billing cycle, and product group. ' +
      'Use this when the customer asks "what plans do you offer?" or wants to compare hosting packages.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'check_domain_availability',
    description:
      'Check if a specific domain name is available for registration. Returns availability status ' +
      'and abbreviated WHOIS data. Use this when the customer wants to register a specific domain.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Full domain name to check, e.g. "mycoolsite.com"',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'search_domains',
    description:
      'Search for a keyword across multiple TLDs (.com, .net, .org, .io, .co, .dev, .app, .ca) to find ' +
      'available domain names. Use this when the customer says "find me a domain for my bakery" or ' +
      '"search for domains with keyword X". Returns availability for each TLD.',
    inputSchema: {
      type: 'object',
      properties: {
        keyword: {
          type: 'string',
          description: 'Base keyword to search, e.g. "sweetbakery"',
        },
        tlds: {
          type: 'array',
          items: { type: 'string' },
          description: 'Optional: specific TLDs to check (e.g. ["com", "ca", "io"]). Defaults to popular TLDs.',
        },
      },
      required: ['keyword'],
    },
  },

  {
    name: 'get_domain_pricing',
    description:
      'Get domain registration, renewal, and transfer pricing for TLDs. Returns prices sorted cheapest first. ' +
      'Use this when the customer asks "how much does a .com cost?" or "what are the cheapest domains?".',
    inputSchema: {
      type: 'object',
      properties: {
        tld: {
          type: 'string',
          description: 'Optional specific TLD like ".com" or "io". Omit to get all TLD pricing.',
        },
      },
    },
  },

  {
    name: 'register_domain',
    description:
      'Register a domain name for the customer. IMPORTANT: This spends money! ' +
      'First call WITHOUT confirmed=true to get a cost preview. ' +
      'Then show the preview to the customer and ask for approval. ' +
      'Only call with confirmed=true after the customer explicitly agrees.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Full domain to register, e.g. "mycoolsite.com"',
        },
        years: {
          type: 'number',
          description: 'Registration period in years (1-10). Default: 1.',
        },
        confirmed: {
          type: 'boolean',
          description: 'Set to true ONLY after the customer has approved the purchase. Default: false (preview mode).',
        },
        paymentMethod: {
          type: 'string',
          description: 'Payment method: "paypal", "stripe", "mailin", etc. Uses default if omitted.',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'order_hosting',
    description:
      'Order a hosting product/plan for the customer. IMPORTANT: This spends money! ' +
      'First call WITHOUT confirmed=true to preview the order with product details. ' +
      'Then show the preview and ask for customer approval. ' +
      'Only call with confirmed=true after explicit customer agreement.',
    inputSchema: {
      type: 'object',
      properties: {
        productId: {
          type: 'number',
          description: 'Product ID from the catalog (use get_product_catalog to find IDs).',
        },
        domain: {
          type: 'string',
          description: 'Domain to associate with the hosting.',
        },
        billingCycle: {
          type: 'string',
          description: 'Billing cycle: "monthly", "quarterly", "semiannually", "annually", "biennially", "triennially". Default: "annually".',
        },
        confirmed: {
          type: 'boolean',
          description: 'Set to true ONLY after customer approval. Default: false (preview mode).',
        },
        paymentMethod: {
          type: 'string',
          description: 'Payment method. Uses default if omitted.',
        },
      },
      required: ['productId', 'domain'],
    },
  },

  {
    name: 'get_invoices',
    description:
      "Get the customer's recent invoices with status, amounts, and dates. " +
      'Use this when the customer asks about billing, past payments, or outstanding balances.',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Maximum invoices to return. Default: 25.',
        },
      },
    },
  },

  {
    name: 'get_invoice_details',
    description:
      'Get full details of a specific invoice including line items, tax, payment method, and notes.',
    inputSchema: {
      type: 'object',
      properties: {
        invoiceId: {
          type: 'number',
          description: 'The invoice ID to look up.',
        },
      },
      required: ['invoiceId'],
    },
  },

  {
    name: 'pay_invoice',
    description:
      'Pay an unpaid invoice using the customer\'s account credit or stored payment method. ' +
      'IMPORTANT: This processes a payment! First call WITHOUT confirmed=true to preview. ' +
      'Show the invoice details and amount to the customer. ' +
      'Only call with confirmed=true after explicit customer approval.',
    inputSchema: {
      type: 'object',
      properties: {
        invoiceId: {
          type: 'number',
          description: 'Invoice ID to pay.',
        },
        confirmed: {
          type: 'boolean',
          description: 'Set to true ONLY after customer approves the payment. Default: false (preview mode).',
        },
      },
      required: ['invoiceId'],
    },
  },

  {
    name: 'order_addon',
    description:
      'Order an addon (like token top-ups) for an existing service. IMPORTANT: This spends money! ' +
      'First call WITHOUT confirmed=true to preview. Only confirm after customer approval.',
    inputSchema: {
      type: 'object',
      properties: {
        addonId: {
          type: 'number',
          description: 'Addon product ID.',
        },
        serviceId: {
          type: 'number',
          description: 'The service ID to attach the addon to (from get_my_services).',
        },
        confirmed: {
          type: 'boolean',
          description: 'Set to true ONLY after customer approval. Default: false (preview mode).',
        },
        paymentMethod: {
          type: 'string',
          description: 'Payment method. Uses default if omitted.',
        },
      },
      required: ['addonId', 'serviceId'],
    },
  },

  {
    name: 'get_support_tickets',
    description:
      "List the customer's support tickets (open, answered, closed). " +
      'Use when the customer asks about ticket status or wants to see their support history.',
    inputSchema: {
      type: 'object',
      properties: {
        status: {
          type: 'string',
          description: 'Filter by status: "Open", "Answered", "Customer-Reply", "Closed", or omit for all.',
        },
      },
    },
  },

  {
    name: 'open_support_ticket',
    description:
      'Open a new support ticket on behalf of the customer. REQUIRES CONFIRMATION. ' +
      'First call without confirmed=true to preview. Then confirm after customer approval.',
    inputSchema: {
      type: 'object',
      properties: {
        subject: {
          type: 'string',
          description: 'Ticket subject line.',
        },
        message: {
          type: 'string',
          description: 'Full ticket message/description.',
        },
        departmentId: {
          type: 'number',
          description: 'Support department ID (default: 1 for General Support).',
        },
        priority: {
          type: 'string',
          description: 'Priority: "Low", "Medium", "High". Default: "Medium".',
        },
        confirmed: {
          type: 'boolean',
          description: 'Set to true ONLY after customer approval. Default: false.',
        },
      },
      required: ['subject', 'message'],
    },
  },

  // ── SSO / Account Login ───────────────────────────────────────────────────
  {
    name: 'client_sso_login',
    description:
      'Generate a secure one-time login link for the customer\'s GoSiteMe account (client area). ' +
      'The link is single-use, expires in 15 minutes, and auto-authenticates the user. ' +
      'Use when the customer says "sign me in", "log me in", "go to my account", ' +
      '"open my dashboard", "check my invoices", or "I need to manage my account". ' +
      'The returned URL should be presented as a clickable link.',
    inputSchema: {
      type: 'object',
      properties: {
        destination: {
          type: 'string',
          description: 'Optional redirect after login. Examples: "clientarea.php?action=services" (services), ' +
            '"clientarea.php?action=invoices" (invoices), "clientarea.php?action=domains" (domains), ' +
            '"supporttickets.php" (tickets), "cart.php" (shop). Leave empty for main dashboard.',
        },
      },
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // GIT VERSION CONTROL
  // Alfred automatically versions code it writes. Customers can also use
  // these tools to manage their own git workflow.
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'da_git_status',
    description:
      'Show the current git working tree status — staged, modified, and untracked files. ' +
      'Returns whether the tree is clean or has uncommitted changes. ' +
      'Use this before making changes to check the current state.',
    inputSchema: {
      type: 'object',
      properties: {
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory. Defaults to "public_html".',
        },
      },
    },
  },

  {
    name: 'da_git_log',
    description:
      'Show recent git commit history with hashes, authors, dates, and messages. ' +
      'Use this to review what changes have been made and when.',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Number of commits to show. Default: 20.',
        },
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
    },
  },

  {
    name: 'da_git_diff',
    description:
      'Show the diff between the working tree and the last commit. ' +
      'Use this to review pending changes before committing or to understand what changed.',
    inputSchema: {
      type: 'object',
      properties: {
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
    },
  },

  {
    name: 'git_commit',
    description:
      'Stage all changes and create a git commit. Use descriptive commit messages. ' +
      'ALWAYS commit after making significant code changes to create a restore point. ' +
      'Alfred should auto-commit after writing or modifying files.',
    inputSchema: {
      type: 'object',
      properties: {
        message: {
          type: 'string',
          description: 'Commit message describing the changes.',
        },
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
      required: ['message'],
    },
  },

  {
    name: 'git_revert',
    description:
      'Undo the last git commit (soft reset — changes remain in staging area). ' +
      'Use this when the customer says "undo that" or when a change caused problems.',
    inputSchema: {
      type: 'object',
      properties: {
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
    },
  },

  {
    name: 'git_init',
    description:
      'Initialize a new git repository in a workspace. Creates .gitignore with sensible defaults ' +
      'and makes an initial commit. Auto-called if needed, but can be used explicitly.',
    inputSchema: {
      type: 'object',
      properties: {
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory. Defaults to "public_html".',
        },
      },
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CHECKPOINT / RESTORE — Named restore points for AI interactions
  // These give the user a "Restore Checkpoint" experience similar to VS Code
  // Copilot Chat.  ALWAYS create a checkpoint before making significant
  // changes so the user can restore to the previous state.
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'create_checkpoint',
    description:
      'Create a named checkpoint (restore point) of the current workspace state. ' +
      'ALWAYS call this BEFORE making significant changes (editing multiple files, ' +
      'redesigning a page, updating config files, deleting files). ' +
      'This lets the user restore their workspace to this exact state if something goes wrong. ' +
      'The label should describe what state is being saved (e.g. "before header redesign", ' +
      '"before WordPress update", "working homepage v2").',
    inputSchema: {
      type: 'object',
      properties: {
        label: {
          type: 'string',
          description: 'Human-readable label describing this checkpoint (e.g. "before header redesign").',
        },
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory. Defaults to "public_html".',
        },
      },
      required: ['label'],
    },
  },

  {
    name: 'list_checkpoints',
    description:
      'List all available checkpoints (restore points) for the workspace. ' +
      'Shows the hash, label, and relative date of each checkpoint. ' +
      'Use this when the user asks "what can I restore to?" or "show my checkpoints".',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Maximum number of checkpoints to return. Default: 50.',
        },
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
    },
  },

  {
    name: 'restore_checkpoint',
    description:
      'Restore the workspace to a previous checkpoint. This is a HARD RESET — ' +
      'all files in the workspace will revert to exactly how they were at the checkpoint. ' +
      'Any uncommitted changes will be lost. A safety backup is automatically created ' +
      'before the restore so the user can undo the restore if needed. ' +
      'Get the commit hash from list_checkpoints. ' +
      'Use this when the user says "undo everything", "go back to before X", or "restore checkpoint".',
    inputSchema: {
      type: 'object',
      properties: {
        commit_hash: {
          type: 'string',
          description: 'The commit hash (short or full) of the checkpoint to restore to. Get this from list_checkpoints.',
        },
        workspace: {
          type: 'string',
          description: 'Workspace path relative to home directory.',
        },
      },
      required: ['commit_hash'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // WORDPRESS MANAGEMENT (via WP-CLI)
  // Full WordPress lifecycle: install, plugins, themes, updates, database.
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'wp_install',
    description:
      'Install WordPress on a domain. Downloads WordPress core, creates wp-config.php, ' +
      'and runs the installation. You MUST create a database first using create_database, ' +
      'then pass the returned credentials here. ' +
      'Example workflow: create_database → wp_install → request_ssl_certificate → force_https.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:        { type: 'string', description: 'Domain to install WordPress on (e.g. "myblog.com").' },
        siteTitle:     { type: 'string', description: 'Website title (e.g. "My Awesome Blog").' },
        adminUser:     { type: 'string', description: 'WordPress admin username.' },
        adminPassword: { type: 'string', description: 'WordPress admin password. Use a strong password!' },
        adminEmail:    { type: 'string', description: 'WordPress admin email address.' },
        dbName:        { type: 'string', description: 'Full database name (from create_database result).' },
        dbUser:        { type: 'string', description: 'Full database username (from create_database result).' },
        dbPassword:    { type: 'string', description: 'Database password.' },
        locale:        { type: 'string', description: 'WordPress locale (default: "en_US"). Use "fr_FR" for French, etc.' },
      },
      required: ['domain', 'siteTitle', 'adminUser', 'adminPassword', 'adminEmail', 'dbName', 'dbUser', 'dbPassword'],
    },
  },

  {
    name: 'wp_site_info',
    description:
      'Get comprehensive WordPress site information: version, active theme, plugin count, ' +
      'database size, site URL. Use this to assess the current state of a WordPress installation.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain where WordPress is installed.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'wp_list_plugins',
    description:
      'List all installed WordPress plugins with their status (active/inactive), version, ' +
      'and update availability. Use this to audit installed plugins.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'wp_install_plugin',
    description:
      'Install (and optionally activate) a WordPress plugin from the WordPress.org directory. ' +
      'Use the plugin slug (e.g. "woocommerce", "yoast-seo", "contact-form-7", "elementor"). ' +
      'Use wp_search_plugins first if unsure of the slug.',
    inputSchema: {
      type: 'object',
      properties: {
        plugin:   { type: 'string', description: 'Plugin slug (e.g. "woocommerce").' },
        domain:   { type: 'string', description: 'Domain.' },
        activate: { type: 'boolean', description: 'Activate after install. Default: true.', default: true },
      },
      required: ['plugin', 'domain'],
    },
  },

  {
    name: 'wp_remove_plugin',
    description: 'Deactivate and remove a WordPress plugin completely.',
    inputSchema: {
      type: 'object',
      properties: {
        plugin: { type: 'string', description: 'Plugin slug to remove.' },
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['plugin', 'domain'],
    },
  },

  {
    name: 'wp_list_themes',
    description: 'List all installed WordPress themes with status and version info.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'wp_install_theme',
    description:
      'Install and activate a WordPress theme from the directory. ' +
      'Use theme slugs like "astra", "twentytwentyfour", "hello-elementor". ' +
      'Use wp_search_themes first to find theme slugs.',
    inputSchema: {
      type: 'object',
      properties: {
        theme:    { type: 'string', description: 'Theme slug (e.g. "astra", "twentytwentyfour").' },
        domain:   { type: 'string', description: 'Domain.' },
        activate: { type: 'boolean', description: 'Activate after install. Default: true.', default: true },
      },
      required: ['theme', 'domain'],
    },
  },

  {
    name: 'wp_update_all',
    description:
      'Update WordPress core, all plugins, and all themes to their latest versions. ' +
      'ALWAYS create a git checkpoint (git_commit) or backup before updating!',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'wp_db_optimize',
    description:
      'Repair and optimize the WordPress database tables. Helps fix corrupted tables ' +
      'and reclaim disk space. Safe to run anytime.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain.' },
      },
      required: ['domain'],
    },
  },

  {
    name: 'wp_search_plugins',
    description:
      'Search the WordPress.org plugin directory for plugins matching a keyword. ' +
      'Returns names, slugs, ratings, and active install counts. ' +
      'Use this to find the right plugin slug before installing.',
    inputSchema: {
      type: 'object',
      properties: {
        query:  { type: 'string', description: 'Search keyword (e.g. "ecommerce", "contact form", "SEO").' },
        domain: { type: 'string', description: 'Domain (needed for WP-CLI context).' },
      },
      required: ['query', 'domain'],
    },
  },

  {
    name: 'wp_search_themes',
    description:
      'Search the WordPress.org theme directory for themes matching a keyword. ' +
      'Returns names, slugs, and ratings.',
    inputSchema: {
      type: 'object',
      properties: {
        query:  { type: 'string', description: 'Search keyword (e.g. "business", "portfolio", "blog").' },
        domain: { type: 'string', description: 'Domain (needed for WP-CLI context).' },
      },
      required: ['query', 'domain'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ERROR LOGS & DIAGNOSTICS
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'read_error_log',
    description:
      'Read the PHP/Apache error log for a domain. Shows the most recent errors. ' +
      'Use this when debugging 500 errors, white screens, or PHP issues. ' +
      'Returns the tail of the error_log file.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to check errors for. Omit to check the main domain.',
        },
        lines: {
          type: 'number',
          description: 'Number of recent lines to show. Default: 100.',
        },
      },
    },
  },

  {
    name: 'read_access_log',
    description:
      "Read the domain's recent access log entries from the daily archive. " +
      'Use this to see who is visiting the site, what pages are popular, and identify bot traffic.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to read access log for.',
        },
        lines: {
          type: 'number',
          description: 'Number of recent lines to show. Default: 200.',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'analyze_errors',
    description:
      'Analyze the error log and group errors by type (PHP Fatal, Warning, Notice, 404, etc.). ' +
      'Returns a breakdown with counts, percentages, and example errors for each type. ' +
      'Great for identifying the most common issues on a site.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to analyze. Omit for the main domain.',
        },
      },
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SECURITY SCANNING
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'scan_malware',
    description:
      'Scan files for malware, backdoors, and suspicious code patterns. ' +
      'Checks for eval(base64_decode()), webshells, C2 beacons, obfuscated code, ' +
      'and other indicators of compromise. Returns findings sorted by severity. ' +
      'Use this proactively or when the customer reports suspicious behavior.',
    inputSchema: {
      type: 'object',
      properties: {
        directory: {
          type: 'string',
          description: 'Directory to scan (relative to home). Default: "public_html".',
        },
      },
    },
  },

  {
    name: 'audit_permissions',
    description:
      'Audit file and directory permissions for security issues. ' +
      'Checks for world-writable files/dirs, PHP in upload directories, ' +
      'suspicious .htaccess redirects, and insecure wp-config.php permissions. ' +
      'Returns issues with severity ratings and fix instructions.',
    inputSchema: {
      type: 'object',
      properties: {
        directory: {
          type: 'string',
          description: 'Directory to audit. Default: "public_html".',
        },
      },
    },
  },

  {
    name: 'security_scan',
    description:
      'Run a comprehensive security scan combining malware detection AND permission audit. ' +
      'Returns an overall security status (CLEAN/WARNING/CRITICAL) with top findings. ' +
      'This is the best single tool for a security health check.',
    inputSchema: {
      type: 'object',
      properties: {
        directory: {
          type: 'string',
          description: 'Directory to scan. Default: "public_html".',
        },
      },
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SITE ANALYTICS & TRAFFIC
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'get_visitor_stats',
    description:
      'Get website visitor statistics from Webalizer: monthly hits, page views, visits, ' +
      'unique visitors, and bandwidth. Shows trends over the last 12 months. ' +
      'Use when the customer asks "how much traffic is my site getting?".',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to get stats for.',
        },
        months: {
          type: 'number',
          description: 'Number of months of history. Default: 12.',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'get_bandwidth_stats',
    description:
      'Get bandwidth usage across ALL domains. Shows which domains consume the most ' +
      'bandwidth this month. Useful for identifying resource-heavy sites.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  {
    name: 'get_traffic_report',
    description:
      'Get detailed traffic report from AWStats: top pages, 404 errors, search bots, ' +
      'and visitor countries. Use for SEO analysis or troubleshooting.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to get traffic report for.',
        },
      },
      required: ['domain'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SITE HEALTH & PERFORMANCE
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'check_site_health',
    description:
      'Comprehensive health check for a domain: DNS resolution, HTTP/HTTPS response, ' +
      'SSL certificate validity, response time, and performance rating. ' +
      'Use when the customer says "is my site working?" or "check my website".',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain to check (e.g. "myblog.com").',
        },
      },
      required: ['domain'],
    },
  },

  {
    name: 'get_server_info',
    description:
      'Get server environment information: PHP version(s), Node.js version, Git version, ' +
      'WP-CLI version, total disk usage, domain count, and OS details. ' +
      'Use when the customer asks about their server capabilities.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // IMAGE GENERATION — AI-Powered (Tool 79-80)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'generate_image',
    description:
      'Generate an AI image from a text description (27 models available). ' +
      'Supports FLUX family (schnell, dev, pro, canny, depth, redux, fill, kontext), ' +
      'Stable Diffusion (3.5-large, 3.5-turbo, SDXL), Ideogram v2, Recraft v3 (including SVG), ' +
      'and more. Default: FLUX.1-schnell (fastest). ' +
      'Supports styles: photo, illustration, logo, abstract, hero (banner), product, avatar. ' +
      'The image is saved to the domain\'s /ai-images/ folder with a public URL returned. ' +
      'Use list_ai_models to see all available image models.',
    inputSchema: {
      type: 'object',
      properties: {
        prompt: {
          type: 'string',
          description: 'Detailed description of the image to generate (e.g. "a modern restaurant interior with warm lighting").',
        },
        domain: {
          type: 'string',
          description: 'Domain name where the image will be saved (e.g. "mybusiness.com").',
        },
        model: {
          type: 'string',
          description: 'Model alias: flux-schnell (default), flux-pro, flux-2-dev, flux-2-pro, flux-kontext-pro, flux-kontext-max, imagen-4-fast, imagen-4-ultra, seedream-4, ideogram-3, sdxl-1.0, or full model ID. Use list_ai_models to see all.',
        },
        style: {
          type: 'string',
          description: 'Image style preset.',
          enum: ['photo', 'illustration', 'logo', 'abstract', 'hero', 'product', 'avatar'],
          default: 'photo',
        },
        size: {
          type: 'string',
          description: 'Image dimensions (WxH).',
          default: '1024x1024',
        },
        steps: {
          type: 'number',
          description: 'Inference steps (1-50). More steps = higher quality but slower. Default: 4 for schnell, 20 for others.',
        },
        filename: {
          type: 'string',
          description: 'Optional custom filename (without extension). Auto-generated if omitted.',
        },
      },
      required: ['prompt', 'domain'],
    },
  },

  {
    name: 'list_generated_images',
    description:
      'List all AI-generated images for a domain. Shows filenames, sizes, dates, and public URLs. ' +
      'Use when the customer asks "show my generated images" or "what images did we create".',
    inputSchema: {
      type: 'object',
      properties: {
        domain: {
          type: 'string',
          description: 'Domain name to list images for.',
        },
      },
      required: ['domain'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // DOCUMENT GENERATION — Word (.docx) files (Tool 81)
  // Create professional Word documents: invoices, proposals, reports, etc.
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'create_word_document',
    description:
      'Create a professional Microsoft Word (.docx) document and save it to a domain. ' +
      'Supports headings (# ## ###), bullet points (- item), numbered lists (1. item), ' +
      'tables (| col1 | col2 |), horizontal rules (---), page breaks ({{pagebreak}}), ' +
      'and **bold** / *italic* text in Markdown-like syntax. ' +
      'The document is generated with professional formatting, headers, footers, and ' +
      'alternating-color table rows. Opens natively in Microsoft Word, Google Docs, and LibreOffice. ' +
      'Use when the customer asks for a "Word doc", "document", ".docx file", "proposal", ' +
      '"invoice", "report", "contract", or any downloadable formatted document. ' +
      'The file is saved to the domain\'s public_html and a download URL is returned.',
    inputSchema: {
      type: 'object',
      properties: {
        title: {
          type: 'string',
          description: 'Document title (displayed prominently at the top).',
        },
        content: {
          type: 'string',
          description: 'Document body content in Markdown-like format. Supports: # Heading 1, ## Heading 2, ### Heading 3, - bullet points, 1. numbered lists, | table | rows |, --- horizontal rules, {{pagebreak}}, **bold**, *italic*. Each line is a separate element.',
        },
        domain: {
          type: 'string',
          description: 'Domain to save the document to (e.g. "mybusiness.com").',
        },
        filename: {
          type: 'string',
          description: 'Output filename (e.g. "proposal.docx"). .docx extension added automatically if missing.',
        },
        author: {
          type: 'string',
          description: 'Author name shown in the document. Default: "GoCodeMe".',
        },
        subtitle: {
          type: 'string',
          description: 'Optional subtitle displayed under the title.',
        },
        footer: {
          type: 'string',
          description: 'Footer text on every page. Default: "Generated by GoCodeMe".',
        },
        path: {
          type: 'string',
          description: 'Subdirectory within public_html (e.g. "documents"). Default: root of public_html.',
        },
      },
      required: ['title', 'content', 'domain', 'filename'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // DOCUMENT GENERATION — PDF files (Tool 82)
  // Create professional PDF documents: invoices, proposals, reports, etc.
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'create_pdf_document',
    description:
      'Create a professional PDF document and save it to a domain. ' +
      'Supports headings (# ## ###), bullet points (- item), numbered lists (1. item), ' +
      'tables (| col1 | col2 |), horizontal rules (---), page breaks ({{pagebreak}}), ' +
      'and **bold** / *italic* text in Markdown-like syntax. ' +
      'The document is generated with clean professional formatting, headers, footers, ' +
      'page numbers, and alternating-color table rows. ' +
      'Use when the customer asks for a "PDF", ".pdf file", "printable document", ' +
      '"downloadable PDF", or any document where PDF format is preferred. ' +
      'The file is saved to the domain\'s public_html and a download URL is returned.',
    inputSchema: {
      type: 'object',
      properties: {
        title: {
          type: 'string',
          description: 'Document title (displayed prominently at the top).',
        },
        content: {
          type: 'string',
          description: 'Document body content in Markdown-like format. Supports: # Heading 1, ## Heading 2, ### Heading 3, - bullet points, 1. numbered lists, | table | rows |, --- horizontal rules, {{pagebreak}}, **bold**, *italic*. Each line is a separate element.',
        },
        domain: {
          type: 'string',
          description: 'Domain to save the document to (e.g. "mybusiness.com").',
        },
        filename: {
          type: 'string',
          description: 'Output filename (e.g. "report.pdf"). .pdf extension added automatically if missing.',
        },
        author: {
          type: 'string',
          description: 'Author name shown in the document. Default: "GoCodeMe".',
        },
        subtitle: {
          type: 'string',
          description: 'Optional subtitle displayed under the title.',
        },
        footer: {
          type: 'string',
          description: 'Footer text on every page. Default: "Generated by GoCodeMe".',
        },
        path: {
          type: 'string',
          description: 'Subdirectory within public_html (e.g. "documents"). Default: root of public_html.',
        },
      },
      required: ['title', 'content', 'domain', 'filename'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // TERMINAL / SHELL EXECUTION
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'run_terminal_command',
    description:
      'Execute a shell command on the customer\'s hosting account and return stdout + stderr. ' +
      'The command runs inside the user\'s home directory with a 30-second timeout. ' +
      'Use this for tasks like: running WP-CLI commands, checking disk usage, listing processes, ' +
      'running composer/npm install, checking PHP versions, testing scripts, grepping files, ' +
      'running database dumps, restarting services, or any other CLI operation. ' +
      'The working directory is the user\'s home (/home/<username>). ' +
      'You can cd into subdirectories within the command (e.g. "cd public_html && ls -la"). ' +
      'Commands are run as the hosting account user with standard Linux tools available. ' +
      'For long-running commands, consider running them in the background with & or nohup. ' +
      'Output is truncated to 50KB if larger.',
    inputSchema: {
      type: 'object',
      properties: {
        command: {
          type: 'string',
          description: 'Shell command to execute (e.g. "ls -la public_html", "php -v", "wp plugin list --path=domains/example.com/public_html").',
        },
        timeout: {
          type: 'number',
          description: 'Timeout in seconds. Default: 30. Max: 120.',
        },
        working_directory: {
          type: 'string',
          description: 'Working directory relative to user home (e.g. "public_html", "domains/example.com/public_html"). Default: user home directory.',
        },
      },
      required: ['command'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // WEB FETCH
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'fetch_url',
    description:
      'Fetch the content of a web URL and return it as text. Now powered by BeautifulSoup4 for intelligent HTML parsing. ' +
      'Supports HTML pages (returns cleaned readable text), JSON APIs (formatted JSON), plain text, XML, and markdown. ' +
      'Advanced features: CSS selector extraction, link/image/table extraction, metadata scraping, heading hierarchy. ' +
      'For HTML pages, scripts/styles/nav/footer are stripped intelligently to return main content. ' +
      'Maximum response size is 100KB (truncated if larger). Timeout is 15 seconds. ' +
      'Follows redirects automatically. Useful for: reading docs, checking live sites, fetching API responses, ' +
      'web scraping, competitor analysis, content extraction.',
    inputSchema: {
      type: 'object',
      properties: {
        url: {
          type: 'string',
          description: 'The URL to fetch (e.g. "https://docs.example.com/api").',
        },
        raw: {
          type: 'boolean',
          description: 'If true, return raw HTML instead of cleaned text. Default: false.',
        },
        selector: {
          type: 'string',
          description: 'CSS selector to extract specific elements (e.g. ".article-body", "#main-content", "h2"). Uses BeautifulSoup4.',
        },
        extract: {
          type: 'array',
          description: 'Additional data to extract: "links", "images", "tables", "headings", "metadata". Returns structured JSON.',
          items: { type: 'string', enum: ['links', 'images', 'tables', 'headings', 'metadata'] },
        },
        headers: {
          type: 'object',
          description: 'Optional custom headers to send with the request.',
          additionalProperties: { type: 'string' },
        },
      },
      required: ['url'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PDF READING
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'read_pdf',
    description:
      'Extract text content from a PDF file on the hosting account. ' +
      'Use this when the user drops a PDF file into the chat, references a PDF, or asks you to read/analyze a PDF document. ' +
      'Returns the full text content extracted from all pages, plus metadata (title, author, page count, creation date). ' +
      'Works with any PDF file accessible on the hosting account. ' +
      'Maximum file size is 50MB. Supports multi-page documents, scanned PDFs with embedded text layers, forms, and tables. ' +
      'For best results with complex layouts (multi-column, tables), the text is extracted in reading order. ' +
      'If a user drops a .pdf file in the chat and you see the file path, use this tool to read its contents.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path: {
          type: 'string',
          description:
            'Absolute or workspace-relative path to the PDF file (e.g. "/home/user/documents/report.pdf" or "docs/spec.pdf").',
        },
        pages: {
          type: 'string',
          description:
            'Optional page range to extract. Examples: "1-5" (pages 1-5), "1,3,5" (specific pages), "2-" (page 2 onwards). Default: all pages.',
        },
        max_chars: {
          type: 'number',
          description:
            'Maximum characters to return. Default: 100000 (100K chars). Use lower values for large PDFs to avoid overwhelming context.',
        },
      },
      required: ['file_path'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ALFRED MEMORY (ELEPHANT)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'alfred_remember',
    description:
      'Save a fact, preference, decision, or lesson to Alfred\'s persistent long-term memory. ' +
      'Use this proactively when you learn something important about the user, their project, ' +
      'their preferences, or a key decision. Memories persist across chat sessions — ' +
      'anything saved here will be available in future conversations. ' +
      'Categories: fact, preference, decision, lesson, project, general. ' +
      'Examples: "User prefers TypeScript over JavaScript", "Production DB is on port 5432", ' +
      '"We decided to use Redis for session storage".',
    inputSchema: {
      type: 'object',
      properties: {
        text: {
          type: 'string',
          description: 'The memory to save. Be specific and concise.',
        },
        category: {
          type: 'string',
          description: 'Memory category: fact, preference, decision, lesson, project, or general.',
          enum: ['fact', 'preference', 'decision', 'lesson', 'project', 'general'],
        },
      },
      required: ['text'],
    },
  },
  {
    name: 'alfred_recall',
    description:
      'Search Alfred\'s long-term memory for relevant information. ' +
      'Use this when you need to remember something from a past conversation — ' +
      'user preferences, project details, past decisions, or lessons learned. ' +
      'The search is semantic, so you can use natural language queries like ' +
      '"what database does this project use?" or "user\'s coding style preferences". ' +
      'Returns the most relevant memories ranked by similarity.',
    inputSchema: {
      type: 'object',
      properties: {
        query: {
          type: 'string',
          description: 'What to search for in memory. Use natural language.',
        },
        top_k: {
          type: 'number',
          description: 'Number of memories to return. Default: 10.',
        },
        category: {
          type: 'string',
          description: 'Optional: filter by category (fact, preference, decision, lesson, project, general).',
          enum: ['fact', 'preference', 'decision', 'lesson', 'project', 'general'],
        },
      },
      required: ['query'],
    },
  },
  {
    name: 'alfred_forget',
    description:
      'Delete a specific memory or all memories. ' +
      'Use the memory ID from alfred_recall results, or pass "all" to clear everything.',
    inputSchema: {
      type: 'object',
      properties: {
        memory_id: {
          type: 'string',
          description: 'The memory ID to delete (e.g. "mem_a1b2c3d4e5f6"), or "all" to delete everything.',
        },
      },
      required: ['memory_id'],
    },
  },
  {
    name: 'alfred_memory_summary',
    description:
      'Show a summary of all saved memories organized by category. ' +
      'Returns total count, breakdown by category, and the full list. ' +
      'Use this to see what Alfred remembers about the user.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PLAYBOOKS (PLAYBOOK)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'run_playbook',
    description:
      'Run a saved playbook — a reusable multi-step workflow that Alfred executes step by step. ' +
      'Playbooks are natural language workflows (not rigid code) so Alfred can adapt if a step fails. ' +
      'Use list_playbooks to see available playbooks. Built-in playbooks include: ' +
      'WordPress Deploy, Laravel Deploy, Node.js Deploy, Nightly Database Backup, ' +
      'SSL Certificate Check, Security Audit, Performance Optimization, New Domain Setup, ' +
      'Git Repository Init, Staging Clone. ' +
      'Returns the full list of steps with parameters filled in — then execute each step.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Name of the playbook to run (e.g. "WordPress Deploy").',
        },
        parameters: {
          type: 'object',
          description: 'Playbook parameters as key-value pairs (e.g. {"domain": "example.com", "branch": "main"}).',
          additionalProperties: { type: 'string' },
        },
      },
      required: ['name'],
    },
  },
  {
    name: 'list_playbooks',
    description:
      'List all available playbooks (built-in and user-created). ' +
      'Returns each playbook\'s name, description, parameters, and step count.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'save_playbook',
    description:
      'Save a new playbook — a reusable multi-step workflow template. ' +
      'Steps are written in natural language. Use {{parameter_name}} for variable substitution. ' +
      'Example step: "Pull latest code from {{branch}} branch for {{domain}}".',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Playbook name (e.g. "My Deploy Pipeline").',
        },
        description: {
          type: 'string',
          description: 'What this playbook does.',
        },
        steps: {
          type: 'array',
          items: { type: 'string' },
          description: 'Array of natural language steps. Use {{param}} for parameters.',
        },
        parameters: {
          type: 'object',
          description: 'Parameter definitions: {"param_name": {"type": "string", "required": true, "default": "value", "description": "..."}}',
        },
        permissions: {
          type: 'array',
          items: { type: 'string' },
          description: 'List of MCP tools this playbook is allowed to use.',
        },
        on_failure: {
          type: 'string',
          description: 'What to do if the playbook fails (natural language).',
        },
      },
      required: ['name', 'steps'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SCHEDULED TASKS (CLOCKWORK)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'create_scheduled_task',
    description:
      'Create an autonomous scheduled task that runs a playbook on a cron schedule. ' +
      'The task runs automatically without user intervention. ' +
      'Cron syntax: minute hour day month weekday (e.g. "0 3 * * *" = daily at 3am). ' +
      'Examples: "*/5 * * * *" (every 5 min), "0 9 * * 1" (Monday 9am), "0 2 * * 0" (Sunday 2am). ' +
      'Maximum 50 tasks per user. Each task executes the named playbook with given parameters.',
    inputSchema: {
      type: 'object',
      properties: {
        name: {
          type: 'string',
          description: 'Human-readable task name (e.g. "Nightly Backup").',
        },
        cron_expression: {
          type: 'string',
          description: 'Cron expression (5 fields): minute hour day month weekday.',
        },
        playbook: {
          type: 'string',
          description: 'Name of the playbook to execute.',
        },
        parameters: {
          type: 'object',
          description: 'Playbook parameters as key-value pairs.',
          additionalProperties: { type: 'string' },
        },
        enabled: {
          type: 'boolean',
          description: 'Whether the task is active. Default: true.',
        },
      },
      required: ['name', 'cron_expression', 'playbook'],
    },
  },
  {
    name: 'list_scheduled_tasks',
    description:
      'List all scheduled tasks for this user. ' +
      'Shows task name, cron schedule, playbook, enabled status, last run time, and run count.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'delete_scheduled_task',
    description:
      'Delete a scheduled task by ID or name. Stops the cron job immediately.',
    inputSchema: {
      type: 'object',
      properties: {
        task_id: {
          type: 'string',
          description: 'Task ID (e.g. "task_a1b2c3d4") or task name.',
        },
      },
      required: ['task_id'],
    },
  },
  {
    name: 'get_scheduled_task_logs',
    description:
      'View execution logs for a scheduled task. Shows timestamps, status (success/error/skipped), ' +
      'elapsed time, and output for recent executions.',
    inputSchema: {
      type: 'object',
      properties: {
        task_id: {
          type: 'string',
          description: 'Task ID (e.g. "task_a1b2c3d4").',
        },
        limit: {
          type: 'number',
          description: 'Number of log entries to return. Default: 20.',
        },
      },
      required: ['task_id'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SEMANTIC CODE SEARCH (ORACLE)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'semantic_code_search',
    description:
      'Search the codebase using natural language with optional AI reranking for higher precision. ' +
      'Unlike grep (exact text match), this understands meaning — search for "authentication middleware" ' +
      'and find functions named "checkAccess" or "verifyToken". ' +
      'Uses local ONNX embeddings for search, with optional AI-powered reranking. ' +
      'The workspace must be indexed first (use reindex_workspace). ' +
      'Returns matching code chunks with file paths, line numbers, and relevance scores.',
    inputSchema: {
      type: 'object',
      properties: {
        query: {
          type: 'string',
          description: 'Natural language search query (e.g. "database connection handling", "user authentication flow").',
        },
        top_k: {
          type: 'number',
          description: 'Number of results to return. Default: 10.',
        },
        file_pattern: {
          type: 'string',
          description: 'Optional file pattern filter (e.g. "*.js", "src/", "api/*.php").',
        },
        language: {
          type: 'string',
          description: 'Optional language filter (e.g. "js", "py", "php").',
        },
        rerank: {
          type: 'boolean',
          description: 'Use AI reranking to reorder results for higher precision. Default: false.',
        },
      },
      required: ['query'],
    },
  },
  {
    name: 'reindex_workspace',
    description:
      'Index (or re-index) the entire workspace for semantic code search. ' +
      'Scans all code, config, and doc files, chunks them intelligently, ' +
      'embeds them locally via ONNX, and stores them for fast semantic search. ' +
      'Incremental by default — only re-embeds changed files. Use force=true for full rebuild. ' +
      'Run this when: (1) first time using semantic search, (2) after major code changes, ' +
      '(3) user asks to refresh the index.',
    inputSchema: {
      type: 'object',
      properties: {
        force: {
          type: 'boolean',
          description: 'Force full re-index even for unchanged files. Default: false.',
        },
      },
    },
  },
  {
    name: 'get_index_stats',
    description:
      'Show statistics about the semantic code search index. ' +
      'Returns number of indexed files, total chunks, file type breakdown, and index size.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // MULTI-AGENT DELEGATION (HIVEMIND)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'spawn_subagent',
    description:
      'Spawn a parallel sub-agent to handle part of a complex task. ' +
      'Sub-agents run in the background and return results via collect_results. ' +
      'Use this to parallelize research: spawn multiple Researcher agents to investigate ' +
      'different aspects simultaneously, then merge results. ' +
      'Roles: researcher (read-only), analyzer (read + diagnose), worker (full access, max 1 at a time). ' +
      'Maximum 3 concurrent sub-agents per user.',
    inputSchema: {
      type: 'object',
      properties: {
        role: {
          type: 'string',
          description: 'Sub-agent role: researcher, analyzer, or worker.',
          enum: ['researcher', 'analyzer', 'worker'],
        },
        task: {
          type: 'string',
          description: 'Natural language description of what the sub-agent should do.',
        },
        context: {
          type: 'string',
          description: 'Additional context to provide (e.g. relevant code, file contents, prior findings).',
        },
      },
      required: ['role', 'task'],
    },
  },
  {
    name: 'collect_results',
    description:
      'Collect results from previously spawned sub-agents. ' +
      'Pass specific task IDs or ["all"] to collect all results. ' +
      'If a sub-agent is still running, its status will show as "running". ' +
      'Completed results are returned and cleaned up.',
    inputSchema: {
      type: 'object',
      properties: {
        task_ids: {
          type: 'array',
          items: { type: 'string' },
          description: 'Array of task IDs to collect, or ["all"] for all sub-agents.',
        },
      },
      required: ['task_ids'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  GIT CONTEXT TOOLS
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'git_status',
    description:
      'Get comprehensive git status for the workspace — current branch, staged/unstaged changes, ' +
      'untracked files, recent commits, remotes, and stash count. Use this to understand what has ' +
      'changed before making decisions.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'git_diff',
    description:
      'Get git diff showing exactly what changed in the workspace. Can show working directory changes, ' +
      'staged changes, or diff against a specific commit/branch. Use to review code changes in detail.',
    inputSchema: {
      type: 'object',
      properties: {
        staged: {
          type: 'boolean',
          description: 'If true, show only staged (git add) changes. Default: false (working directory changes).',
        },
        ref: {
          type: 'string',
          description: 'Compare against a specific ref (e.g. "HEAD~3", "main", "v1.0.0"). Default: HEAD.',
        },
        file: {
          type: 'string',
          description: 'Limit diff to a specific file path.',
        },
      },
    },
  },
  {
    name: 'git_log',
    description:
      'Get git commit history with filtering options. Shows commit hash, author, date, and message. ' +
      'Can filter by author, date range, or file. Use to understand project history or find when a change was made.',
    inputSchema: {
      type: 'object',
      properties: {
        count: {
          type: 'number',
          description: 'Number of commits to show (default: 20, max: 100).',
        },
        author: {
          type: 'string',
          description: 'Filter by author name or email.',
        },
        since: {
          type: 'string',
          description: 'Show commits since date (e.g. "2025-01-01", "2 weeks ago").',
        },
        file: {
          type: 'string',
          description: 'Show commits that affected this file.',
        },
      },
    },
  },
  {
    name: 'git_branches',
    description:
      'List all git branches (local and remote) with current branch highlighted. ' +
      'Shows branch name, latest commit hash, and relative date.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'smart_commit',
    description:
      'Stage changes and commit with an AI-generated conventional commit message. ' +
      'Claude analyzes the diff to produce descriptive messages like "feat(auth): add JWT refresh token rotation". ' +
      'Use this instead of generic commits. Can stage specific files or all changes.',
    inputSchema: {
      type: 'object',
      properties: {
        files: {
          type: 'array',
          items: { type: 'string' },
          description: 'Specific files to stage. If omitted, stages all changes (git add -A).',
        },
        message: {
          type: 'string',
          description: 'Override the AI-generated message with your own commit message.',
        },
        hint: {
          type: 'string',
          description: 'Hint for the AI about what these changes do (e.g. "refactored the auth module").',
        },
      },
    },
  },
  {
    name: 'amend_commit',
    description:
      'Amend the last commit with a new AI-generated message. Useful to replace "[CHECKPOINT] Auto-save" ' +
      'messages with descriptive ones. Does not change the committed files.',
    inputSchema: {
      type: 'object',
      properties: {
        message: {
          type: 'string',
          description: 'Override message. If omitted, Claude generates one from the commit diff.',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  PROJECT SNAPSHOT TOOL
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'project_snapshot',
    description:
      'Take a comprehensive snapshot of the project — file structure, dependencies, git status, ' +
      'disk usage, environment versions, and health checks. Use at the start of a session to quickly ' +
      'understand the project state, or when the user asks "what is this project?"',
    inputSchema: {
      type: 'object',
      properties: {
        project_path: {
          type: 'string',
          description: 'Subdirectory within public_html to analyze (e.g. "gocodeme/mcp-server"). Default: root.',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  SESSION SUMMARY TOOL
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'save_session_summary',
    description:
      'Save a summary of the current conversation session to long-term memory. ' +
      'Use this at the end of a productive session to preserve key decisions, actions taken, ' +
      'files modified, and important context. Each point in the summary becomes a separate ' +
      'searchable memory for future recall. Write the summary as bullet points.',
    inputSchema: {
      type: 'object',
      properties: {
        summary: {
          type: 'string',
          description: 'Structured summary with bullet points of key decisions, actions, and context from this session.',
        },
      },
      required: ['summary'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  ANALYTICS TOOL
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'tool_analytics',
    description:
      'View tool usage analytics — which tools are used most, average latency, success rates, ' +
      'and hourly activity. Useful for understanding usage patterns and identifying issues.',
    inputSchema: {
      type: 'object',
      properties: {
        top_n: {
          type: 'number',
          description: 'Number of top tools to show (default: 20).',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  AI CODE REVIEW
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'code_review',
    description:
      'Run an AI-powered code review on the current git diff. Claude analyzes your changes for bugs, ' +
      'security issues, style problems, and performance concerns. Returns structured feedback with severity, ' +
      'file locations, and fix suggestions. Use before committing to catch issues early.',
    inputSchema: {
      type: 'object',
      properties: {
        focus: {
          type: 'string',
          enum: ['all', 'bugs', 'security', 'style', 'performance'],
          description: 'What to focus the review on. Default: all.',
        },
        staged_only: {
          type: 'boolean',
          description: 'If true, only review staged changes. Default: false (all uncommitted changes).',
        },
        ref: {
          type: 'string',
          description: 'Compare against a specific git ref (e.g. "HEAD~3", "main"). Default: working tree.',
        },
        context: {
          type: 'string',
          description: 'Extra context about the project to help the reviewer (e.g. "This is a Laravel API").',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  DATABASE TOOLS
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'db_list',
    description:
      'List all MySQL databases accessible to this hosting account.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'db_schema',
    description:
      'Get the full schema (tables, columns, types, keys) for a MySQL database. ' +
      'Essential for understanding database structure before writing queries.',
    inputSchema: {
      type: 'object',
      properties: {
        database: {
          type: 'string',
          description: 'Database name to inspect.',
        },
      },
      required: ['database'],
    },
  },
  {
    name: 'db_query',
    description:
      'Execute a MySQL query. SELECT/SHOW/DESCRIBE are allowed by default. ' +
      'For INSERT/UPDATE/DELETE, set allow_mutation=true. Results are returned as structured rows. ' +
      'Has a 30-second timeout and 500-row limit.',
    inputSchema: {
      type: 'object',
      properties: {
        database: {
          type: 'string',
          description: 'Database to query.',
        },
        query: {
          type: 'string',
          description: 'SQL query to execute.',
        },
        allow_mutation: {
          type: 'boolean',
          description: 'Set to true to allow INSERT/UPDATE/DELETE/ALTER queries. Default: false.',
        },
      },
      required: ['database', 'query'],
    },
  },
  {
    name: 'db_stats',
    description:
      'Get table row counts, data sizes, index sizes, and engine info for all tables in a database.',
    inputSchema: {
      type: 'object',
      properties: {
        database: {
          type: 'string',
          description: 'Database to get stats for.',
        },
      },
      required: ['database'],
    },
  },
  {
    name: 'db_backup',
    description:
      'Create a compressed backup (mysqldump + gzip) of a MySQL database. ' +
      'Saved to ~/backups/db/ with timestamp. Returns file path and size.',
    inputSchema: {
      type: 'object',
      properties: {
        database: {
          type: 'string',
          description: 'Database to back up.',
        },
      },
      required: ['database'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  DEPENDENCY AUDIT
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'dependency_audit',
    description:
      'Scan project dependencies for security vulnerabilities and outdated packages. ' +
      'Supports npm (Node.js), Composer (PHP), and pip (Python). ' +
      'Returns vulnerability count, severity breakdown, outdated packages, and overall health status.',
    inputSchema: {
      type: 'object',
      properties: {
        project_path: {
          type: 'string',
          description: 'Path relative to public_html. Empty string for the root public_html directory.',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  FILE WATCHER TOOL
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'toggle_auto_index',
    description:
      'Start or stop the automatic file watcher that keeps the semantic search index up-to-date. ' +
      'When enabled, file changes are detected and re-indexed automatically after a 5-second debounce. ' +
      'Use this after running reindex_workspace for the first time.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['start', 'stop', 'status'],
          description: 'Action: start the watcher, stop it, or check status.',
        },
      },
      required: ['action'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  TERMINAL SESSION MANAGEMENT
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'terminal_session_status',
    description:
      'Get the status of the persistent terminal session including current working directory, ' +
      'uptime, idle time, and whether the session is active. Terminal sessions survive between tool ' +
      'calls so cd, environment variables, and shell state are preserved.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'terminal_history',
    description:
      'Retrieve the command history for the current persistent terminal session. ' +
      'Shows recent commands with exit codes and timing. Useful for reviewing what commands ' +
      'have been run and their results.',
    inputSchema: {
      type: 'object',
      properties: {
        limit: {
          type: 'number',
          description: 'Maximum number of history entries to return (default: 20, max: 100).',
        },
      },
    },
  },
  {
    name: 'terminal_reset',
    description:
      'Reset the persistent terminal session, killing the current shell and clearing history. ' +
      'A fresh shell will be started on the next run_terminal_command call. Use this if the ' +
      'terminal is in a bad state or you want to start clean.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  TOOL DOCUMENTATION & DISCOVERY
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'search_tools',
    description:
      'Search for available tools by keyword or description. Returns matching tools ranked by ' +
      'relevance. Use this when you need to discover which tool to use for a specific task.',
    inputSchema: {
      type: 'object',
      properties: {
        query: {
          type: 'string',
          description: 'Search query — tool name, keyword, or description fragment.',
        },
      },
      required: ['query'],
    },
  },
  {
    name: 'get_tool_docs',
    description:
      'Get documentation for all available tools, organized by category. ' +
      'Supports JSON, Markdown, and summary output formats. Use "summary" for a quick overview.',
    inputSchema: {
      type: 'object',
      properties: {
        format: {
          type: 'string',
          enum: ['json', 'markdown', 'summary'],
          description: 'Output format (default: summary).',
        },
        category: {
          type: 'string',
          description: 'Filter by category name (e.g., "files", "git", "billing").',
        },
      },
    },
  },
  {
    name: 'get_tool_doc',
    description:
      'Get detailed documentation for a single tool, including all parameters, types, defaults, ' +
      'and usage examples. Use this to understand exactly how a specific tool works.',
    inputSchema: {
      type: 'object',
      properties: {
        tool_name: {
          type: 'string',
          description: 'The exact name of the tool to document.',
        },
      },
      required: ['tool_name'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  //  SYSTEM STATUS & MONITORING
  // ═══════════════════════════════════════════════════════════════════════
  {
    name: 'get_isolation_status',
    description:
      'Get the current multi-user isolation status including rate limit counters, ' +
      'concurrent command count, and sandbox configuration for the current user. ' +
      'Useful for debugging permission issues or rate limiting.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'get_mcp_usage',
    description:
      'Get MCP tool usage statistics tied to the WHMCS billing account. Shows token ' +
      'consumption, tool call counts by day, and remaining allowance. Requires an active ' +
      'WHMCS billing session.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'get_error_summary',
    description:
      'Get a summary of recent errors across all tools including error counts by tool, ' +
      'circuit breaker states, and the last 10 error details. Useful for diagnosing ' +
      'systemic issues or understanding why a tool is temporarily unavailable.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // BLUEPRINT v3 — AI Media Generation Tools
  // ══════════════════════════════════════════════════════════════════════════

  // ── Video Generation ──────────────────────────────────────────────────────
  {
    name: 'generate_video',
    description:
      'Generate a video from a text prompt using AI video models. ' +
      'Supports 23 models including Wan-AI, Hailuo/MiniMax, Kling, Google Veo, and Luma Ray. ' +
      'Default model: Wan-AI/Wan2.2-T2V-A14B. Use model aliases like "veo-3", "kling-2.1-pro", "hailuo-02", "sora-2", etc. ' +
      'For image-to-video, provide an image_url with an i2v model. ' +
      'Videos are saved to the domain\'s ai-videos/ directory.',
    inputSchema: {
      type: 'object',
      properties: {
        prompt: {
          type: 'string',
          description: 'Text description of the video to generate',
        },
        domain: {
          type: 'string',
          description: 'Domain name to save the video under (e.g., "example.com")',
        },
        model: {
          type: 'string',
          description: 'Model alias or full name. Aliases: wan-t2v (default), wan-i2v, hailuo-02, hailuo-director, kling-2.1-pro, kling-2.1-master, veo-3, veo-3-audio, sora-2, seedance-pro, pixverse-v5.6, vidu-2',
        },
        duration: {
          type: 'number',
          description: 'Video duration in seconds (default: 5)',
        },
        image_url: {
          type: 'string',
          description: 'Reference image URL for image-to-video models',
        },
        filename: {
          type: 'string',
          description: 'Custom output filename (auto-generated if omitted)',
        },
      },
      required: ['prompt', 'domain'],
    },
  },

  // ── Audio/TTS Generation ──────────────────────────────────────────────────
  {
    name: 'generate_audio',
    description:
      'Generate speech audio from text using AI text-to-speech models. ' +
      'Supports Kokoro-82M (fast, natural), Cartesia-Sonic-3 (multilingual), and Orpheus-3b (expressive). ' +
      'Default: Kokoro-82M. Audio is saved as MP3 to the domain\'s ai-audio/ directory. ' +
      'Great for voiceovers, narration, podcasts, and accessibility.',
    inputSchema: {
      type: 'object',
      properties: {
        text: {
          type: 'string',
          description: 'Text to convert to speech',
        },
        domain: {
          type: 'string',
          description: 'Domain name to save audio under',
        },
        model: {
          type: 'string',
          description: 'TTS model: "kokoro" (default, fast), "cartesia-sonic" (multilingual), "orpheus" (expressive)',
        },
        voice: {
          type: 'string',
          description: 'Voice name (e.g., "alloy", "echo", "fable", "onyx", "nova", "shimmer")',
        },
        filename: {
          type: 'string',
          description: 'Custom output filename (auto-generated if omitted)',
        },
      },
      required: ['text', 'domain'],
    },
  },

  // ── Vision Analysis ───────────────────────────────────────────────────────
  {
    name: 'vision_analyze',
    description:
      'Analyze an image using AI vision models. ' +
      'Send a screenshot, mockup, diagram, or any image and get a detailed analysis. ' +
      'Use cases: screenshot-to-code, UI review, diagram interpretation, OCR, accessibility audit. ' +
      'Accepts image URLs or local file paths (which are converted to base64).',
    inputSchema: {
      type: 'object',
      properties: {
        prompt: {
          type: 'string',
          description: 'What to analyze about the image (e.g., "Convert this screenshot to HTML/CSS", "Describe this diagram")',
        },
        image: {
          type: 'string',
          description: 'Image URL (https://...) or local file path to analyze',
        },
        model: {
          type: 'string',
          description: 'Vision model: "qwen3-vl" (default, best), "llama-scout"',
        },
      },
      required: ['prompt', 'image'],
    },
  },

  // ── Video Processing ──────────────────────────────────────────────────────
  {
    name: 'process_video',
    description:
      'Process video files using FFmpeg. Actions: trim, resize, convert, extract_audio, compress, ' +
      'thumbnail, gif, speed, merge, add_subtitles. Supports all major video formats. ' +
      'Example: trim a video, extract audio as MP3, create a GIF, generate thumbnails, ' +
      'merge multiple videos, add subtitles, change speed, compress for web.',
    inputSchema: {
      type: 'object',
      properties: {
        input: {
          type: 'string',
          description: 'Input video file path',
        },
        action: {
          type: 'string',
          description: 'Processing action: trim, resize, convert, extract_audio, compress, thumbnail, gif, speed, merge, add_subtitles',
          enum: ['trim', 'resize', 'convert', 'extract_audio', 'compress', 'thumbnail', 'gif', 'speed', 'merge', 'add_subtitles'],
        },
        output: {
          type: 'string',
          description: 'Output file path',
        },
        options: {
          type: 'object',
          description: 'Action-specific options. trim: {startTime, endTime, duration}. resize: {width, height}. convert: {codec, audioBitrate, videoBitrate}. extract_audio: {format, bitrate}. compress: {crf, preset}. thumbnail: {time}. gif: {fps, width, startTime, duration}. speed: {factor}. merge: {inputs: []}. add_subtitles: {subtitleFile}.',
        },
      },
      required: ['input', 'action', 'output'],
    },
  },

  // ── Image Processing ──────────────────────────────────────────────────────
  {
    name: 'process_image',
    description:
      'Process image files using ImageMagick. Actions: resize, compress, convert, watermark, crop, ' +
      'rotate, flip, blur, sharpen, grayscale, border, thumbnail, optimize, info. ' +
      'Supports PNG, JPG, WebP, GIF, SVG, TIFF, BMP. ' +
      'Batch processing: run multiple times with different inputs.',
    inputSchema: {
      type: 'object',
      properties: {
        input: {
          type: 'string',
          description: 'Input image file path',
        },
        action: {
          type: 'string',
          description: 'Processing action: resize, compress, convert, watermark, crop, rotate, flip, blur, sharpen, grayscale, border, thumbnail, optimize, info',
          enum: ['resize', 'compress', 'convert', 'watermark', 'crop', 'rotate', 'flip', 'blur', 'sharpen', 'grayscale', 'border', 'thumbnail', 'optimize', 'info'],
        },
        output: {
          type: 'string',
          description: 'Output file path (not needed for "info" action)',
        },
        options: {
          type: 'object',
          description: 'Action-specific options. resize: {width, height, geometry}. compress: {quality}. watermark: {text, position, size, color, opacity}. crop: {geometry}. rotate: {degrees}. flip: {direction}. blur/sharpen: {radius, sigma}. border: {size, color}. thumbnail: {size}.',
        },
      },
      required: ['input', 'action'],
    },
  },

  // ── Media Download ────────────────────────────────────────────────────────
  {
    name: 'download_media',
    description:
      'Download media from 1,864+ websites using yt-dlp. Supports YouTube, Vimeo, Twitter/X, ' +
      'TikTok, Instagram, SoundCloud, Bandcamp, Twitch, and many more. ' +
      'Can download video, audio-only, or just fetch metadata without downloading. ' +
      'Formats: best (default), mp4, mp3, wav, bestaudio, bestvideo.',
    inputSchema: {
      type: 'object',
      properties: {
        url: {
          type: 'string',
          description: 'URL of the media to download',
        },
        output_dir: {
          type: 'string',
          description: 'Directory to save downloaded files',
        },
        format: {
          type: 'string',
          description: 'Format: "best" (default), "mp4", "mp3", "wav", "bestaudio", "bestvideo"',
        },
        audio_only: {
          type: 'boolean',
          description: 'Extract audio only (default: false)',
        },
        metadata_only: {
          type: 'boolean',
          description: 'Only fetch metadata without downloading (default: false)',
        },
        filename: {
          type: 'string',
          description: 'Custom filename template (default: "%(title)s.%(ext)s")',
        },
      },
      required: ['url', 'output_dir'],
    },
  },

  // ── SQL Execution ─────────────────────────────────────────────────────────
  {
    name: 'execute_sql',
    description:
      'Execute a SQL query directly against a MySQL database. Supports SELECT, INSERT, UPDATE, DELETE, ' +
      'CREATE, ALTER, DROP, and all other SQL statements. ' +
      'Uses the MySQL CLI with the user\'s credentials. ' +
      'Returns results as formatted text or JSON. Use with caution for destructive queries.',
    inputSchema: {
      type: 'object',
      properties: {
        database: {
          type: 'string',
          description: 'Database name to execute the query against',
        },
        query: {
          type: 'string',
          description: 'SQL query to execute',
        },
        format: {
          type: 'string',
          description: 'Output format: "table" (default), "json", "csv"',
        },
      },
      required: ['database', 'query'],
    },
  },

  // ── PHP Version Switching ─────────────────────────────────────────────────
  {
    name: 'switch_php_version',
    description:
      'Switch the PHP version for a domain. Supports PHP 8.2 and 8.3. ' +
      'Updates the .htaccess file to use the specified PHP handler. ' +
      'Useful when a project requires a specific PHP version.',
    inputSchema: {
      type: 'object',
      properties: {
        version: {
          type: 'string',
          description: 'PHP version: "8.2" or "8.3"',
          enum: ['8.2', '8.3'],
        },
        domain: {
          type: 'string',
          description: 'Domain name to switch PHP version for',
        },
      },
      required: ['version'],
    },
  },

  // ── Voice Status ──────────────────────────────────────────────────────────
  {
    name: 'voice_status',
    description:
      'Get the status of the Alfred voice server including active voice sessions, ' +
      'their uptime, message counts, and processing state.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },

  // ── AI Models List ─────────────────────────────────────────────────────────
  {
    name: 'list_ai_models',
    description:
      'List all available AI models organized by category: ' +
      'image (27 models), video (23 models), audio/TTS (3 models), vision (2 models), and LLM (7 models). ' +
      'Shows model aliases and full model IDs for use with generate_image, generate_video, ' +
      'generate_audio, and vision_analyze tools.',
    inputSchema: {
      type: 'object',
      properties: {
        category: {
          type: 'string',
          description: 'Filter by category: "image", "video", "audio", "vision", "llm", or omit for all',
        },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — RAG Pipeline
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'rag_ingest',
    description:
      'Ingest a document into a RAG knowledge-base collection. Supports PDF, DOCX, Markdown, HTML, ' +
      'plain text, code files, and URLs. The document is parsed, chunked, embedded, and stored for later querying.',
    inputSchema: {
      type: 'object',
      properties: {
        source: { type: 'string', description: 'File path or URL to ingest' },
        collection: { type: 'string', description: 'Collection name (e.g., "project-docs")' },
        chunkStrategy: { type: 'string', description: 'Chunking strategy: "auto", "recursive", "paragraph", "code", "sentence" (default: auto)' },
        chunkSize: { type: 'number', description: 'Target chunk size in characters (default: 1000)' },
      },
      required: ['source', 'collection'],
    },
  },
  {
    name: 'rag_query',
    description:
      'Query a RAG knowledge-base collection. Performs semantic search over ingested documents ' +
      'and optionally generates an AI answer grounded in the retrieved context.',
    inputSchema: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'The question or search query' },
        collection: { type: 'string', description: 'Collection to search' },
        topK: { type: 'number', description: 'Number of chunks to retrieve (default: 5)' },
        generateAnswer: { type: 'boolean', description: 'If true, generate an AI answer from retrieved context (default: true)' },
      },
      required: ['query', 'collection'],
    },
  },
  {
    name: 'rag_list_collections',
    description: 'List all RAG knowledge-base collections with document counts and statistics.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'rag_delete',
    description: 'Delete a RAG collection or a specific document source within a collection.',
    inputSchema: {
      type: 'object',
      properties: {
        collection: { type: 'string', description: 'Collection name to delete (or delete from)' },
        source: { type: 'string', description: 'Optional: specific source document to remove from the collection' },
      },
      required: ['collection'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Code Interpreter
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'run_code',
    description:
      'Execute code in a sandboxed interpreter. Supports Python, Node.js, Bash, Ruby, and PHP. ' +
      'Captures stdout, stderr, exit code, and any generated images (matplotlib plots, etc.). ' +
      'Each user gets isolated sessions with persistent state.',
    inputSchema: {
      type: 'object',
      properties: {
        code: { type: 'string', description: 'The code to execute' },
        language: { type: 'string', description: 'Language: "python", "node", "bash", "ruby", "php" (default: python)' },
        sessionId: { type: 'string', description: 'Optional session ID for persistent state' },
      },
      required: ['code'],
    },
  },
  {
    name: 'list_interpreter_sessions',
    description: 'List active code interpreter sessions for the current user.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'kill_interpreter_session',
    description: 'Terminate a code interpreter session and clean up its temporary files.',
    inputSchema: {
      type: 'object',
      properties: {
        sessionId: { type: 'string', description: 'Session ID to terminate' },
      },
      required: ['sessionId'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Browser Agent
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'browse_web',
    description:
      'Navigate to a URL with a headless Chromium browser and extract the page content. ' +
      'Returns page title, text content, links, and metadata. Supports JavaScript-rendered pages.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL to browse' },
        waitFor: { type: 'string', description: 'CSS selector to wait for before extracting (optional)' },
        extractLinks: { type: 'boolean', description: 'Include page links in output (default: true)' },
      },
      required: ['url'],
    },
  },
  {
    name: 'screenshot_page',
    description: 'Take a screenshot of a web page. Returns a base64-encoded PNG image.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL to screenshot' },
        fullPage: { type: 'boolean', description: 'Capture the full scrollable page (default: false)' },
        selector: { type: 'string', description: 'CSS selector to screenshot a specific element' },
      },
      required: ['url'],
    },
  },
  {
    name: 'click_element',
    description: 'Click an element on a web page identified by CSS selector. Returns the page state after clicking.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL of the page' },
        selector: { type: 'string', description: 'CSS selector of the element to click' },
      },
      required: ['url', 'selector'],
    },
  },
  {
    name: 'fill_form',
    description: 'Fill and submit a web form. Provide field selectors and values, optionally submit the form.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL of the page with the form' },
        fields: {
          type: 'array',
          description: 'Array of { selector, value } objects for each form field',
          items: {
            type: 'object',
            properties: {
              selector: { type: 'string' },
              value: { type: 'string' },
            },
          },
        },
        submitSelector: { type: 'string', description: 'CSS selector of the submit button (optional)' },
      },
      required: ['url', 'fields'],
    },
  },
  {
    name: 'extract_data',
    description:
      'Extract structured data from a web page using CSS selectors. ' +
      'Can extract text, tables, lists, images, or custom selector patterns.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL to extract from' },
        selectors: {
          type: 'object',
          description: 'Map of label → CSS selector to extract (e.g., {"title": "h1", "prices": ".price"})',
        },
        mode: { type: 'string', description: 'Extraction mode: "text", "tables", "links", "images", "custom" (default: custom)' },
      },
      required: ['url'],
    },
  },
  {
    name: 'web_search',
    description: 'Search the web using DuckDuckGo and return results with titles, URLs, and snippets.',
    inputSchema: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'Search query' },
        maxResults: { type: 'number', description: 'Maximum results to return (default: 10)' },
      },
      required: ['query'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — MCP Client Gateway
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'mcp_connect',
    description:
      'Connect to an external MCP server. Discovers available tools and makes them callable ' +
      'through the mcp_call_tool interface. Supports stdio, SSE, and StreamableHTTP transports.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Friendly name for this connection (e.g., "github")' },
        command: { type: 'string', description: 'For stdio: command to run (e.g., "npx @modelcontextprotocol/server-github")' },
        args: { type: 'array', description: 'Command arguments', items: { type: 'string' } },
        env: { type: 'object', description: 'Environment variables to pass (e.g., {"GITHUB_TOKEN": "..."})' },
        url: { type: 'string', description: 'For SSE/HTTP: server URL' },
        transport: { type: 'string', description: 'Transport type: "stdio" (default), "sse", "http"' },
      },
      required: ['name'],
    },
  },
  {
    name: 'mcp_disconnect',
    description: 'Disconnect from an external MCP server and unregister its tools.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Server name to disconnect' },
      },
      required: ['name'],
    },
  },
  {
    name: 'mcp_list_servers',
    description: 'List all connected MCP servers and their available tools, plus known servers that can be connected.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'mcp_call_tool',
    description: 'Call a tool on a connected external MCP server. Use mcp_list_servers to see available tools.',
    inputSchema: {
      type: 'object',
      properties: {
        server: { type: 'string', description: 'Server name (as used in mcp_connect)' },
        tool: { type: 'string', description: 'Tool name on the remote server' },
        arguments: { type: 'object', description: 'Arguments to pass to the tool' },
      },
      required: ['server', 'tool'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — n8n Workflow Automation
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'workflow_create',
    description:
      'Create a new n8n workflow from a template or custom definition. ' +
      'Templates available: deploy-notification, health-check, backup, rss-monitor, data-pipeline.',
    inputSchema: {
      type: 'object',
      properties: {
        template: { type: 'string', description: 'Template name (e.g., "health-check") or omit for custom' },
        name: { type: 'string', description: 'Workflow name' },
        nodes: { type: 'array', description: 'Custom workflow nodes (for advanced users)' },
        connections: { type: 'object', description: 'Node connections (for advanced users)' },
      },
    },
  },
  {
    name: 'workflow_execute',
    description: 'Execute an n8n workflow by ID and return the execution result.',
    inputSchema: {
      type: 'object',
      properties: {
        workflowId: { type: 'string', description: 'Workflow ID to execute' },
        data: { type: 'object', description: 'Input data to pass to the workflow' },
      },
      required: ['workflowId'],
    },
  },
  {
    name: 'workflow_list',
    description: 'List all n8n workflows with their status (active/inactive) and last execution time.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'workflow_status',
    description: 'Get the status and execution history of a specific n8n workflow.',
    inputSchema: {
      type: 'object',
      properties: {
        workflowId: { type: 'string', description: 'Workflow ID' },
      },
      required: ['workflowId'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Proactive Monitoring Agent
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'enable_monitoring',
    description:
      'Enable or disable the proactive monitoring agent. When enabled, Alfred automatically ' +
      'monitors system resources, service health, and project issues. Generates alerts and can auto-fix common problems.',
    inputSchema: {
      type: 'object',
      properties: {
        enabled: { type: 'boolean', description: 'true to enable, false to disable' },
        autoFix: { type: 'boolean', description: 'Enable auto-fix for common problems (default: false)' },
      },
      required: ['enabled'],
    },
  },
  {
    name: 'alert_history',
    description: 'Get the alert history from the proactive monitoring agent, including resource and service alerts.',
    inputSchema: {
      type: 'object',
      properties: {
        severity: { type: 'string', description: 'Filter by severity: "critical", "warning", "info"' },
        limit: { type: 'number', description: 'Max alerts to return (default: 20)' },
      },
    },
  },
  {
    name: 'auto_fix_config',
    description: 'View or update the auto-fix configuration for the proactive monitoring agent.',
    inputSchema: {
      type: 'object',
      properties: {
        action: { type: 'string', description: '"get" to view config, "set" to update' },
        settings: { type: 'object', description: 'Settings to update (when action is "set")' },
      },
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — A2A Protocol
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'a2a_discover',
    description: 'Discover a remote A2A-compatible agent by fetching its Agent Card from /.well-known/agent.json.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'Base URL of the agent (e.g., "https://agent.example.com")' },
      },
      required: ['url'],
    },
  },
  {
    name: 'a2a_send_task',
    description: 'Send a task to a remote A2A-compatible agent and receive the result.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'Base URL of the remote agent' },
        message: { type: 'string', description: 'The task message/instruction to send' },
        skill: { type: 'string', description: 'Specific skill to invoke (optional)' },
      },
      required: ['url', 'message'],
    },
  },
  {
    name: 'a2a_list_tasks',
    description: 'List A2A tasks that have been sent or received, with their current status.',
    inputSchema: {
      type: 'object',
      properties: {
        state: { type: 'string', description: 'Filter by state: "submitted", "working", "completed", "failed", "canceled"' },
      },
    },
  },
  {
    name: 'a2a_publish_card',
    description: 'View the current A2A Agent Card that describes Alfred\'s capabilities to other agents.',
    inputSchema: { type: 'object', properties: {} },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Artifacts System
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'create_chart',
    description:
      'Generate an interactive chart (bar, line, pie, doughnut, radar, scatter, bubble) ' +
      'using Chart.js. Returns a URL to the rendered chart.',
    inputSchema: {
      type: 'object',
      properties: {
        type: { type: 'string', description: 'Chart type: bar, line, pie, doughnut, radar, scatter, bubble' },
        labels: { type: 'array', description: 'X-axis labels', items: { type: 'string' } },
        datasets: {
          type: 'array',
          description: 'Array of datasets: [{ label, data: [...numbers], backgroundColor, borderColor }]',
        },
        title: { type: 'string', description: 'Chart title' },
        width: { type: 'number', description: 'Width in pixels (default: 800)' },
        height: { type: 'number', description: 'Height in pixels (default: 400)' },
      },
      required: ['type', 'labels', 'datasets'],
    },
  },
  {
    name: 'create_diagram',
    description:
      'Render a Mermaid diagram to SVG/HTML. Supports flowcharts, sequence diagrams, Gantt charts, ' +
      'class diagrams, state diagrams, ER diagrams, and more.',
    inputSchema: {
      type: 'object',
      properties: {
        code: { type: 'string', description: 'Mermaid diagram code (e.g., "graph TD; A-->B;")' },
        theme: { type: 'string', description: 'Theme: "default", "dark", "forest", "neutral" (default: default)' },
      },
      required: ['code'],
    },
  },
  {
    name: 'preview_html',
    description:
      'Create a live HTML preview with optional Tailwind CSS and Alpine.js support. ' +
      'Returns a URL to view the rendered HTML page.',
    inputSchema: {
      type: 'object',
      properties: {
        html: { type: 'string', description: 'HTML content to preview' },
        title: { type: 'string', description: 'Page title (default: "Preview")' },
        tailwind: { type: 'boolean', description: 'Include Tailwind CSS CDN (default: false)' },
        alpine: { type: 'boolean', description: 'Include Alpine.js CDN (default: false)' },
      },
      required: ['html'],
    },
  },
  {
    name: 'list_artifacts',
    description: 'List all currently stored artifacts (charts, diagrams, HTML previews) with their URLs and creation times.',
    inputSchema: { type: 'object', properties: {} },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Voice Room Management
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'voice_room_create',
    description: 'Create a real-time voice room for multi-participant voice conversations with Alfred.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Room name' },
        maxParticipants: { type: 'number', description: 'Max participants (default: 10)' },
      },
      required: ['name'],
    },
  },
  {
    name: 'voice_room_join',
    description: 'Generate a token to join a voice room as a participant.',
    inputSchema: {
      type: 'object',
      properties: {
        room: { type: 'string', description: 'Room name to join' },
        identity: { type: 'string', description: 'Participant identity/name' },
      },
      required: ['room', 'identity'],
    },
  },
  {
    name: 'voice_room_list',
    description: 'List active voice rooms and their participants.',
    inputSchema: { type: 'object', properties: {} },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v6.0.0 — Local LLM (Ollama)
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'local_llm_chat',
    description:
      'Chat with a local LLM running on Ollama. Data stays on-server — ideal for sensitive/private code. ' +
      'Supports all Ollama models: qwen2.5, codellama, phi3, mistral, deepseek-coder, etc.',
    inputSchema: {
      type: 'object',
      properties: {
        messages: {
          type: 'array',
          description: 'Chat messages: [{ role: "user"|"assistant"|"system", content: "..." }]',
        },
        model: { type: 'string', description: 'Model name (default: qwen2.5:0.5b). Use local_llm_list to see installed models.' },
        temperature: { type: 'number', description: 'Temperature 0-1 (default: 0.7)' },
      },
      required: ['messages'],
    },
  },
  {
    name: 'local_llm_list',
    description: 'List all locally installed Ollama models with their sizes and details. Also shows recommended models to download.',
    inputSchema: { type: 'object', properties: {} },
  },
  {
    name: 'local_llm_pull',
    description: 'Download a model from the Ollama registry. Use local_llm_list to see recommended models.',
    inputSchema: {
      type: 'object',
      properties: {
        model: { type: 'string', description: 'Model to download (e.g., "qwen2.5:0.5b", "codellama:7b", "phi3:mini")' },
      },
      required: ['model'],
    },
  },
  {
    name: 'local_llm_route',
    description:
      'Intelligently route a request between local Ollama and cloud (Claude/Together) based on complexity, ' +
      'privacy, and context length. Shows which route was chosen and why.',
    inputSchema: {
      type: 'object',
      properties: {
        messages: {
          type: 'array',
          description: 'Chat messages: [{ role: "user"|"assistant"|"system", content: "..." }]',
        },
        preference: { type: 'string', description: '"auto" (default), "local", or "cloud"' },
        analyzeOnly: { type: 'boolean', description: 'If true, just show routing analysis without executing (default: false)' },
      },
      required: ['messages'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // ██  PHASE 27 — 69 NEW ALFRED VISION TOOLS                              ██
  // ═══════════════════════════════════════════════════════════════════════════

  // ── E-Commerce & Revenue (8 tools) ──────────────────────────────────────
  {
    name: 'create_online_store',
    description:
      'Scaffold a complete e-commerce store (WooCommerce or Snipcart) from a business description. ' +
      'Creates product pages, cart, checkout, and basic styling.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:      { type: 'string', description: 'Domain to deploy the store on' },
        description: { type: 'string', description: 'Business description: what they sell, target audience, style' },
        platform:    { type: 'string', description: '"woocommerce" (default) or "snipcart"' },
        products:    { type: 'array',  description: 'Optional initial products: [{name, price, description, image_url}]' },
      },
      required: ['domain', 'description'],
    },
  },
  {
    name: 'add_product',
    description:
      'Add a product to the detected e-commerce platform (WooCommerce, Snipcart, or custom). ' +
      'Auto-detects the framework and creates the product with images.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:      { type: 'string', description: 'Domain where the store lives' },
        name:        { type: 'string', description: 'Product name' },
        price:       { type: 'number', description: 'Price in dollars' },
        description: { type: 'string', description: 'Product description' },
        category:    { type: 'string', description: 'Product category' },
        image_url:   { type: 'string', description: 'Product image URL (optional — will generate if missing)' },
        sku:         { type: 'string', description: 'SKU (optional — auto-generates)' },
      },
      required: ['domain', 'name', 'price'],
    },
  },
  {
    name: 'setup_payment_gateway',
    description:
      'Configure Stripe or PayPal payment processing. Adds API keys, creates webhook endpoints, ' +
      'and sets up the checkout flow.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain to configure payments on' },
        gateway:  { type: 'string', description: '"stripe" or "paypal"' },
        api_key:  { type: 'string', description: 'Public/publishable API key' },
        secret:   { type: 'string', description: 'Secret API key' },
        currency: { type: 'string', description: 'Currency code (default: "USD")' },
        test_mode:{ type: 'boolean', description: 'Use test/sandbox mode (default: true)' },
      },
      required: ['domain', 'gateway', 'api_key', 'secret'],
    },
  },
  {
    name: 'generate_invoice',
    description:
      'Create a professional branded PDF invoice for the customer\'s client. ' +
      'Includes line items, taxes, totals, and payment terms.',
    inputSchema: {
      type: 'object',
      properties: {
        business_name: { type: 'string', description: 'Invoice from (business name)' },
        client_name:   { type: 'string', description: 'Invoice to (client name)' },
        client_email:  { type: 'string', description: 'Client email (optional — for delivery)' },
        items:         { type: 'array',  description: 'Line items: [{description, quantity, unit_price}]' },
        tax_rate:      { type: 'number', description: 'Tax rate as percentage (e.g. 13 for 13%)' },
        currency:      { type: 'string', description: 'Currency (default: "USD")' },
        due_date:      { type: 'string', description: 'Due date (e.g. "2025-02-15" or "net30")' },
        notes:         { type: 'string', description: 'Additional notes on the invoice' },
      },
      required: ['business_name', 'client_name', 'items'],
    },
  },
  {
    name: 'setup_recurring_billing',
    description:
      'Configure subscription/recurring billing products with Stripe or WooCommerce Subscriptions.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain with the store' },
        product:  { type: 'string', description: 'Product name for the subscription' },
        price:    { type: 'number', description: 'Recurring price' },
        interval: { type: 'string', description: '"monthly", "yearly", "weekly"' },
        trial_days: { type: 'number', description: 'Free trial period in days (optional)' },
      },
      required: ['domain', 'product', 'price', 'interval'],
    },
  },
  {
    name: 'get_revenue_analytics',
    description:
      'Get revenue analytics: total sales, order count, average order value, top products, ' +
      'refund rate, and growth trends. Reads WooCommerce or Snipcart data.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain with the e-commerce store' },
        period: { type: 'string', description: '"today", "week", "month" (default), "year", or "YYYY-MM-DD:YYYY-MM-DD"' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'setup_shipping',
    description:
      'Configure shipping zones and rates for WooCommerce. Supports flat rate, free shipping, ' +
      'and rate-by-weight.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain with the WooCommerce store' },
        zones:  { type: 'array',  description: 'Shipping zones: [{name, regions, methods: [{type, cost}]}]' },
      },
      required: ['domain', 'zones'],
    },
  },
  {
    name: 'create_checkout_page',
    description:
      'Generate a standalone checkout/payment page with Stripe integration. ' +
      'Perfect for freelancers, consultants, and simple product sales.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:      { type: 'string', description: 'Domain to deploy on' },
        title:       { type: 'string', description: 'Page title / product name' },
        price:       { type: 'number', description: 'Price in dollars' },
        description: { type: 'string', description: 'What the customer is paying for' },
        success_url: { type: 'string', description: 'Redirect after payment (optional)' },
        collect_phone: { type: 'boolean', description: 'Collect phone number (default: false)' },
        collect_address: { type: 'boolean', description: 'Collect shipping address (default: false)' },
      },
      required: ['domain', 'title', 'price'],
    },
  },

  // ── SEO & Marketing (7 tools) ──────────────────────────────────────────
  {
    name: 'seo_audit',
    description:
      'Full SEO audit of a website: meta tags, headings, alt text, page speed, mobile-friendliness, ' +
      'structured data, internal/external links, and overall score out of 100.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain to audit' },
        depth:  { type: 'number', description: 'Number of pages to crawl (default: 10, max: 50)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'generate_sitemap',
    description:
      'Crawl the site and generate/update sitemap.xml with proper priorities and change frequencies.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to generate sitemap for' },
        max_pages:  { type: 'number', description: 'Maximum pages to include (default: 500)' },
        exclude:    { type: 'array',  description: 'URL patterns to exclude (e.g. ["/admin", "/api"])' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'generate_robots_txt',
    description:
      'Create an optimized robots.txt file. Auto-detects CMS and applies best practices.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to create robots.txt for' },
        disallow:   { type: 'array',  description: 'Additional paths to block (e.g. ["/private", "/tmp"])' },
        sitemap_url:{ type: 'string', description: 'Sitemap URL (auto-detected if not provided)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'setup_google_analytics',
    description:
      'Inject Google Analytics 4 (GA4) tracking code into all pages of the site. ' +
      'Auto-detects WordPress, HTML, or PHP sites.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:        { type: 'string', description: 'Domain to add analytics to' },
        measurement_id:{ type: 'string', description: 'GA4 Measurement ID (e.g. "G-XXXXXXXXXX")' },
      },
      required: ['domain', 'measurement_id'],
    },
  },
  {
    name: 'setup_search_console',
    description:
      'Set up Google Search Console verification. Creates the verification HTML file ' +
      'and optionally submits the sitemap.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:            { type: 'string', description: 'Domain to verify' },
        verification_code: { type: 'string', description: 'Google verification code or HTML filename' },
        submit_sitemap:    { type: 'boolean', description: 'Also submit sitemap.xml (default: true)' },
      },
      required: ['domain', 'verification_code'],
    },
  },
  {
    name: 'generate_social_cards',
    description:
      'Create Open Graph and Twitter Card meta tags for all pages. Generates og:image using AI ' +
      'if no featured image exists.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to add social cards to' },
        site_name: { type: 'string', description: 'Site name for og:site_name' },
        default_image: { type: 'string', description: 'Default og:image URL (generates one if missing)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'keyword_research',
    description:
      'Analyze keywords for SEO: search volume estimates, competition level, related keywords, ' +
      'and content topic suggestions. Uses web search and AI analysis.',
    inputSchema: {
      type: 'object',
      properties: {
        keywords: { type: 'array',  description: 'Keywords to research (e.g. ["web hosting", "cloud IDE"])' },
        market:   { type: 'string', description: 'Target market/country (default: "US")' },
        intent:   { type: 'string', description: '"informational", "commercial", "transactional", or "all"' },
      },
      required: ['keywords'],
    },
  },

  // ── Communication & Notifications (6 tools) ────────────────────────────
  {
    name: 'send_sms',
    description:
      'Send an SMS message to a phone number. Uses the platform SMS gateway.',
    inputSchema: {
      type: 'object',
      properties: {
        to:      { type: 'string', description: 'Phone number in E.164 format (e.g. "+15551234567")' },
        message: { type: 'string', description: 'SMS message text (max 160 chars for single SMS)' },
      },
      required: ['to', 'message'],
    },
  },
  {
    name: 'send_fax',
    description:
      'Send a fax to a phone number. Requires a publicly accessible URL to a PDF or image document. ' +
      'Uses the platform fax gateway (Telnyx). The document must be a URL (not a local file).',
    inputSchema: {
      type: 'object',
      properties: {
        to:        { type: 'string', description: 'Fax number in E.164 format (e.g. "+15551234567")' },
        media_url: { type: 'string', description: 'Public URL to the document to fax (PDF, TIFF, or image)' },
        quality:   { type: 'string', description: 'Fax quality: "normal" or "fine" (default: normal)', enum: ['normal', 'fine'] },
      },
      required: ['to', 'media_url'],
    },
  },
  {
    name: 'send_push_notification',
    description:
      'Send a browser push notification to the customer (if they have opted in from the dashboard).',
    inputSchema: {
      type: 'object',
      properties: {
        title:   { type: 'string', description: 'Notification title' },
        body:    { type: 'string', description: 'Notification body text' },
        url:     { type: 'string', description: 'URL to open when clicked (optional)' },
        icon:    { type: 'string', description: 'Icon URL (optional)' },
      },
      required: ['title', 'body'],
    },
  },
  {
    name: 'create_contact_form',
    description:
      'Generate and deploy a contact form with spam protection (reCAPTCHA or honeypot), ' +
      'email notification, and responsive styling that matches the site.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:       { type: 'string', description: 'Domain to deploy the form on' },
        path:         { type: 'string', description: 'URL path (default: "/contact")' },
        email_to:     { type: 'string', description: 'Email to receive submissions' },
        fields:       { type: 'array',  description: 'Form fields: [{name, type, required}] (default: name, email, message)' },
        spam_protect: { type: 'string', description: '"recaptcha", "honeypot" (default), or "both"' },
        recaptcha_key:{ type: 'string', description: 'reCAPTCHA site key (required if spam_protect includes recaptcha)' },
      },
      required: ['domain', 'email_to'],
    },
  },
  {
    name: 'setup_live_chat',
    description:
      'Deploy a live chat widget on the site. Supports Tawk.to (free) or Crisp.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain to add live chat to' },
        provider: { type: 'string', description: '"tawk" (default) or "crisp"' },
        widget_id:{ type: 'string', description: 'Widget/site ID from the chat provider' },
        color:    { type: 'string', description: 'Widget accent color (hex, e.g. "#4F46E5")' },
        position: { type: 'string', description: '"bottom-right" (default) or "bottom-left"' },
      },
      required: ['domain', 'provider', 'widget_id'],
    },
  },
  {
    name: 'create_newsletter',
    description:
      'Set up an email newsletter with a signup form. Creates the subscription form, ' +
      'stores subscribers in a database, and provides a send endpoint.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:      { type: 'string', description: 'Domain for the newsletter' },
        list_name:   { type: 'string', description: 'Newsletter name (e.g. "Weekly Updates")' },
        from_name:   { type: 'string', description: 'Sender name' },
        from_email:  { type: 'string', description: 'Sender email address' },
        double_optin:{ type: 'boolean', description: 'Require email confirmation (default: true)' },
      },
      required: ['domain', 'list_name', 'from_email'],
    },
  },
  {
    name: 'schedule_email_campaign',
    description:
      'Queue an email campaign to a newsletter subscriber list. Supports HTML templates, ' +
      'scheduling, and A/B subject line testing.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain with the newsletter' },
        subject:   { type: 'string', description: 'Email subject line' },
        body:      { type: 'string', description: 'Email body (HTML or plain text)' },
        send_at:   { type: 'string', description: 'ISO datetime to send (or "now")' },
        segment:   { type: 'string', description: 'Subscriber segment (optional — default: all)' },
        ab_subject:{ type: 'string', description: 'Alternative subject for A/B test (optional)' },
      },
      required: ['domain', 'subject', 'body'],
    },
  },

  // ── DevOps & Deployment (7 tools) ──────────────────────────────────────
  {
    name: 'setup_ci_cd',
    description:
      'Create CI/CD pipeline: GitHub Actions workflow or Git deploy hooks for automatic deployment ' +
      'on push. Detects framework and configures build steps.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:  { type: 'string', description: 'Domain to deploy to' },
        repo_url:{ type: 'string', description: 'GitHub repository URL' },
        branch:  { type: 'string', description: 'Branch to deploy from (default: "main")' },
        build_cmd:{ type: 'string', description: 'Build command (auto-detected if not set)' },
        type:    { type: 'string', description: '"github-actions" (default) or "deploy-hook"' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'create_staging_site',
    description:
      'Clone the production site to a staging subdomain. Copies files, database, ' +
      'and configuration. Perfect for testing changes safely.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:          { type: 'string', description: 'Production domain to clone' },
        staging_subdomain:{ type: 'string', description: 'Staging subdomain (default: "staging")' },
        include_database: { type: 'boolean', description: 'Clone the database too (default: true)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'promote_staging',
    description:
      'Push staging site to production. Creates a backup first, then syncs files and database ' +
      'with automatic rollback on failure.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:          { type: 'string', description: 'Production domain' },
        staging_subdomain:{ type: 'string', description: 'Staging subdomain to promote (default: "staging")' },
        backup_first:    { type: 'boolean', description: 'Create backup before promoting (default: true)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'setup_docker',
    description:
      'Generate Dockerfile and docker-compose.yml for the project. Auto-detects ' +
      'framework, language, databases, and services.',
    inputSchema: {
      type: 'object',
      properties: {
        path:       { type: 'string', description: 'Project path (default: current domain root)' },
        services:   { type: 'array',  description: 'Additional services: ["redis", "mysql", "postgres", "nginx"]' },
        node_version:{ type: 'string', description: 'Node.js version (auto-detected)' },
        php_version: { type: 'string', description: 'PHP version (auto-detected)' },
      },
      required: [],
    },
  },
  {
    name: 'run_tests',
    description:
      'Auto-detect and run the project test suite. Supports PHPUnit, Jest, Mocha, pytest, ' +
      'and Go tests. Returns pass/fail counts and error details.',
    inputSchema: {
      type: 'object',
      properties: {
        path:       { type: 'string', description: 'Project path (default: domain root)' },
        framework:  { type: 'string', description: 'Test framework (auto-detected if not set)' },
        filter:     { type: 'string', description: 'Filter/grep for specific tests' },
        coverage:   { type: 'boolean', description: 'Generate code coverage (default: false)' },
      },
      required: [],
    },
  },
  {
    name: 'performance_benchmark',
    description:
      'Run performance/load test against a site using ab (Apache Bench). Returns requests/sec, ' +
      'latency percentiles, concurrent capacity, and bottleneck analysis.',
    inputSchema: {
      type: 'object',
      properties: {
        url:          { type: 'string', description: 'URL to benchmark' },
        concurrency:  { type: 'number', description: 'Concurrent connections (default: 10)' },
        total_requests:{ type: 'number', description: 'Total requests (default: 100)' },
      },
      required: ['url'],
    },
  },
  {
    name: 'setup_webhook',
    description:
      'Create incoming or outgoing webhook endpoints. Generates the handler code, ' +
      'validates signatures, and logs payloads.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to create webhook on' },
        path:      { type: 'string', description: 'Webhook URL path (e.g. "/webhook/stripe")' },
        direction: { type: 'string', description: '"incoming" (receive) or "outgoing" (send)' },
        events:    { type: 'array',  description: 'Events to listen for / send (e.g. ["payment.success", "order.created"])' },
        target_url:{ type: 'string', description: 'For outgoing: URL to POST to' },
        secret:    { type: 'string', description: 'Webhook signing secret (optional — auto-generates)' },
      },
      required: ['domain', 'path', 'direction'],
    },
  },

  // ── Design & UI (6 tools) ──────────────────────────────────────────────
  {
    name: 'generate_logo',
    description:
      'AI-generate a logo from a business description. Creates multiple variants ' +
      'in SVG and PNG formats.',
    inputSchema: {
      type: 'object',
      properties: {
        business_name: { type: 'string', description: 'Business/brand name' },
        description:   { type: 'string', description: 'Business description and style preferences' },
        style:         { type: 'string', description: '"minimal", "modern", "playful", "corporate", or "tech" (default: "modern")' },
        colors:        { type: 'array',  description: 'Preferred colors (e.g. ["#FF6B35", "#004E89"])' },
        variants:      { type: 'number', description: 'Number of variants to generate (default: 3, max: 5)' },
      },
      required: ['business_name', 'description'],
    },
  },
  {
    name: 'generate_favicon',
    description:
      'Create a complete favicon set: 16x16, 32x32, 180x180 (apple-touch), 192x192, 512x512, ' +
      'and site.webmanifest. Can extract from existing logo or generate from scratch.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to create favicons for' },
        source:    { type: 'string', description: 'Path to logo image (optional — generates from site name if missing)' },
        color:     { type: 'string', description: 'Theme color (hex, optional)' },
        install:   { type: 'boolean', description: 'Auto-install into site HTML (default: true)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'generate_color_palette',
    description:
      'AI-generate a harmonious color palette from a brand description or existing image. ' +
      'Returns primary, secondary, accent, background, and text colors with CSS variables.',
    inputSchema: {
      type: 'object',
      properties: {
        description: { type: 'string', description: 'Brand/mood description (e.g. "professional legal firm, trust, navy blue")' },
        image_path:  { type: 'string', description: 'Extract palette from this image (optional)' },
        count:       { type: 'number', description: 'Number of colors (default: 5)' },
        format:      { type: 'string', description: '"css" (default), "tailwind", "scss", or "json"' },
      },
      required: ['description'],
    },
  },
  {
    name: 'create_landing_page',
    description:
      'Generate a complete, responsive landing page from a description. Includes hero, features, ' +
      'testimonials, pricing, CTA, and footer sections. Deployed to the domain.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to deploy on' },
        path:       { type: 'string', description: 'URL path (default: "/")' },
        title:      { type: 'string', description: 'Page title / hero heading' },
        description:{ type: 'string', description: 'Business/product description for AI to expand' },
        sections:   { type: 'array',  description: 'Sections to include: ["hero", "features", "pricing", "testimonials", "cta", "faq"]' },
        style:      { type: 'string', description: '"modern" (default), "minimal", "bold", "corporate"' },
        cta_text:   { type: 'string', description: 'Call-to-action button text (default: "Get Started")' },
        cta_url:    { type: 'string', description: 'CTA link URL' },
      },
      required: ['domain', 'title', 'description'],
    },
  },
  {
    name: 'optimize_images',
    description:
      'Bulk compress and optionally resize all images in a directory. Supports JPEG, PNG, WebP, ' +
      'and GIF. Reports total savings.',
    inputSchema: {
      type: 'object',
      properties: {
        path:       { type: 'string', description: 'Directory path to scan for images' },
        quality:    { type: 'number', description: 'Quality level: 1-100 (default: 80)' },
        max_width:  { type: 'number', description: 'Resize images wider than this (optional)' },
        to_webp:    { type: 'boolean', description: 'Also create WebP versions (default: false)' },
        recursive:  { type: 'boolean', description: 'Scan subdirectories (default: true)' },
      },
      required: ['path'],
    },
  },
  {
    name: 'generate_css_theme',
    description:
      'Create a CSS theme/skin from a color palette or description. Generates CSS variables, ' +
      'component styles, dark/light mode, and utility classes.',
    inputSchema: {
      type: 'object',
      properties: {
        description:  { type: 'string', description: 'Style description or brand guidelines' },
        colors:       { type: 'object', description: '{primary, secondary, accent, background, text}' },
        framework:    { type: 'string', description: '"vanilla" (default), "tailwind", "bootstrap", or "scss"' },
        dark_mode:    { type: 'boolean', description: 'Include dark mode (default: true)' },
        output_path:  { type: 'string', description: 'File path to save theme CSS' },
      },
      required: ['description'],
    },
  },

  // ── Authentication & Users (5 tools) ───────────────────────────────────
  {
    name: 'setup_auth',
    description:
      'Add login, register, and forgot-password functionality to a website. Creates the HTML forms, ' +
      'backend handlers, database table, sessions/JWT, and password hashing.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain to add auth to' },
        type:     { type: 'string', description: '"session" (default) or "jwt"' },
        language: { type: 'string', description: '"php" (default), "node", or "python"' },
        features: { type: 'array',  description: '["login", "register", "forgot_password", "profile"] (default: all)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'create_user_table',
    description:
      'Design and create a users database table with best practices: hashed passwords, ' +
      'email verification tokens, timestamps, and indexes.',
    inputSchema: {
      type: 'object',
      properties: {
        database:       { type: 'string', description: 'Database name' },
        table_name:     { type: 'string', description: 'Table name (default: "users")' },
        extra_columns:  { type: 'array',  description: 'Additional columns: [{name, type, nullable}]' },
        include_roles:  { type: 'boolean', description: 'Add role/permission columns (default: false)' },
        include_profile:{ type: 'boolean', description: 'Add profile columns (avatar, bio, phone) (default: true)' },
      },
      required: ['database'],
    },
  },
  {
    name: 'generate_api_keys',
    description:
      'Create API key management for the customer\'s own service: key generation, validation ' +
      'middleware, rate limiting, and a management dashboard.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain hosting the API' },
        database:   { type: 'string', description: 'Database for storing keys' },
        rate_limit: { type: 'number', description: 'Requests per minute per key (default: 60)' },
        key_format: { type: 'string', description: '"uuid" (default), "prefix_random" (e.g., pk_live_xxx)' },
      },
      required: ['domain', 'database'],
    },
  },
  {
    name: 'setup_oauth',
    description:
      'Configure social login with Google, GitHub, or Facebook. Creates OAuth routes, ' +
      'callback handlers, and links to existing user table.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:       { type: 'string', description: 'Domain to add OAuth to' },
        providers:    { type: 'array',  description: '["google", "github", "facebook"] — pick one or more' },
        client_ids:   { type: 'object', description: '{google: {id, secret}, github: {id, secret}, ...}' },
        callback_path:{ type: 'string', description: 'OAuth callback path (default: "/auth/callback")' },
      },
      required: ['domain', 'providers'],
    },
  },
  {
    name: 'setup_2fa',
    description:
      'Add TOTP-based two-factor authentication. Creates QR code generation endpoint, ' +
      'verification middleware, and backup codes.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to add 2FA to' },
        app_name:   { type: 'string', description: 'App name shown in authenticator (default: domain name)' },
        backup_codes:{ type: 'number', description: 'Number of backup codes to generate (default: 10)' },
      },
      required: ['domain'],
    },
  },

  // ── Data & Integration (6 tools) ───────────────────────────────────────
  {
    name: 'import_csv',
    description:
      'Import CSV or TSV data into a database table. Auto-creates the table if it doesn\'t exist, ' +
      'maps columns, handles encoding, and reports import stats.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path:    { type: 'string', description: 'Path to CSV/TSV file' },
        database:     { type: 'string', description: 'Target database' },
        table:        { type: 'string', description: 'Target table (creates if missing)' },
        delimiter:    { type: 'string', description: 'Delimiter (default: auto-detect)' },
        has_header:   { type: 'boolean', description: 'First row is headers (default: true)' },
        column_map:   { type: 'object', description: 'Map CSV columns to table columns (optional)' },
        truncate_first:{ type: 'boolean', description: 'Clear table before import (default: false)' },
      },
      required: ['file_path', 'database', 'table'],
    },
  },
  {
    name: 'export_data',
    description:
      'Export database table or query results to CSV, JSON, or Excel format.',
    inputSchema: {
      type: 'object',
      properties: {
        database:   { type: 'string', description: 'Database name' },
        query:      { type: 'string', description: 'SQL query or table name (default: full table)' },
        format:     { type: 'string', description: '"csv" (default), "json", "xlsx"' },
        output_path:{ type: 'string', description: 'Output file path' },
        limit:      { type: 'number', description: 'Max rows (default: 10000)' },
      },
      required: ['database', 'query'],
    },
  },
  {
    name: 'connect_api',
    description:
      'Test an external API connection and scaffold integration code. Makes a test request, ' +
      'parses the response, and generates helper functions.',
    inputSchema: {
      type: 'object',
      properties: {
        url:        { type: 'string', description: 'API endpoint URL' },
        method:     { type: 'string', description: '"GET" (default), "POST", "PUT", "DELETE"' },
        headers:    { type: 'object', description: 'Request headers (e.g. {"Authorization": "Bearer xxx"})' },
        body:       { type: 'object', description: 'Request body for POST/PUT' },
        language:   { type: 'string', description: 'Generate code in: "php" (default), "node", "python", "curl"' },
        scaffold:   { type: 'boolean', description: 'Generate helper class/module (default: true)' },
      },
      required: ['url'],
    },
  },
  {
    name: 'setup_cors',
    description:
      'Configure CORS headers for the site/API. Adds proper Access-Control headers via .htaccess ' +
      'or PHP middleware.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:   { type: 'string', description: 'Domain to configure CORS on' },
        origins:  { type: 'array',  description: 'Allowed origins (e.g. ["https://myapp.com"]). Use ["*"] for all.' },
        methods:  { type: 'array',  description: 'Allowed methods (default: ["GET", "POST", "PUT", "DELETE", "OPTIONS"])' },
        headers:  { type: 'array',  description: 'Allowed headers (default: ["Content-Type", "Authorization"])' },
        max_age:  { type: 'number', description: 'Preflight cache seconds (default: 86400)' },
      },
      required: ['domain', 'origins'],
    },
  },
  {
    name: 'create_rest_api',
    description:
      'Auto-generate a full REST API from a database schema. Creates CRUD endpoints, validation, ' +
      'pagination, filtering, and API documentation.',
    inputSchema: {
      type: 'object',
      properties: {
        database:  { type: 'string', description: 'Database to generate API from' },
        tables:    { type: 'array',  description: 'Tables to expose (default: all)' },
        language:  { type: 'string', description: '"php" (default) or "node"' },
        auth:      { type: 'string', description: '"none", "api_key" (default), or "jwt"' },
        prefix:    { type: 'string', description: 'URL prefix (e.g. "/api/v1")' },
        docs:      { type: 'boolean', description: 'Generate OpenAPI/Swagger docs (default: true)' },
      },
      required: ['database'],
    },
  },
  {
    name: 'migrate_site',
    description:
      'Import/migrate a website from an external host. Downloads files via FTP/SSH or cPanel backup, ' +
      'imports database, and updates configuration.',
    inputSchema: {
      type: 'object',
      properties: {
        source_url:    { type: 'string', description: 'Current site URL' },
        source_type:   { type: 'string', description: '"cpanel", "ftp", "ssh", or "wordpress" (uses WP API)' },
        source_host:   { type: 'string', description: 'FTP/SSH hostname' },
        source_user:   { type: 'string', description: 'FTP/SSH username' },
        source_pass:   { type: 'string', description: 'FTP/SSH password' },
        target_domain: { type: 'string', description: 'Destination domain on GoSiteMe' },
        include_db:    { type: 'boolean', description: 'Migrate database too (default: true)' },
        db_host:       { type: 'string', description: 'Source DB host (for direct DB migration)' },
        db_user:       { type: 'string', description: 'Source DB username' },
        db_pass:       { type: 'string', description: 'Source DB password' },
        db_name:       { type: 'string', description: 'Source DB name' },
      },
      required: ['source_url', 'target_domain'],
    },
  },

  // ── Content Generation (5 tools) ───────────────────────────────────────
  {
    name: 'generate_blog_post',
    description:
      'AI-write a complete blog post: title, introduction, sections with headings, conclusion, ' +
      'meta description, and featured image. Saves to the site.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to publish on' },
        topic:      { type: 'string', description: 'Blog post topic or title' },
        keywords:   { type: 'array',  description: 'SEO keywords to target' },
        tone:       { type: 'string', description: '"professional" (default), "casual", "technical", "friendly"' },
        word_count: { type: 'number', description: 'Target word count (default: 1200)' },
        publish:    { type: 'boolean', description: 'Publish immediately (default: false — saves as draft)' },
        category:   { type: 'string', description: 'Blog category' },
      },
      required: ['domain', 'topic'],
    },
  },
  {
    name: 'generate_product_description',
    description:
      'AI-generate compelling product descriptions for e-commerce. Supports bulk generation ' +
      'with SEO optimization and multiple formats.',
    inputSchema: {
      type: 'object',
      properties: {
        product_name: { type: 'string', description: 'Product name' },
        features:     { type: 'array',  description: 'Key features/specs' },
        target_audience:{ type: 'string', description: 'Target customer description' },
        tone:         { type: 'string', description: '"persuasive" (default), "technical", "luxurious", "fun"' },
        length:       { type: 'string', description: '"short" (50 words), "medium" (150), or "long" (300)' },
        include_seo:  { type: 'boolean', description: 'Include meta description (default: true)' },
      },
      required: ['product_name', 'features'],
    },
  },
  {
    name: 'translate_content',
    description:
      'Translate a file or text content to another language using AI. Preserves formatting, ' +
      'code blocks, and HTML structure.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path:    { type: 'string', description: 'File to translate (or use "text" parameter)' },
        text:         { type: 'string', description: 'Text to translate (or use "file_path")' },
        target_lang:  { type: 'string', description: 'Target language (e.g. "Spanish", "French", "Japanese", "zh-CN")' },
        source_lang:  { type: 'string', description: 'Source language (auto-detected if not set)' },
        output_path:  { type: 'string', description: 'Output file path (default: adds language suffix)' },
        preserve_code:{ type: 'boolean', description: 'Keep code blocks unchanged (default: true)' },
      },
      required: ['target_lang'],
    },
  },
  {
    name: 'generate_legal_pages',
    description:
      'Generate privacy policy, terms of service, and cookie policy customized for the business. ' +
      'Includes GDPR, CCPA, and PIPEDA compliance language.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:       { type: 'string', description: 'Website domain' },
        business_name:{ type: 'string', description: 'Business/company name' },
        business_type:{ type: 'string', description: '"saas", "ecommerce", "blog", "service", "agency"' },
        email:        { type: 'string', description: 'Contact email for the policies' },
        country:      { type: 'string', description: 'Business country for jurisdiction (default: "US")' },
        pages:        { type: 'array',  description: '["privacy", "terms", "cookies"] (default: all)' },
        deploy:       { type: 'boolean', description: 'Deploy to domain (default: true)' },
      },
      required: ['domain', 'business_name'],
    },
  },
  {
    name: 'generate_readme',
    description:
      'AI-generate a comprehensive README.md from project analysis. Includes installation, ' +
      'usage, API docs, contributing guidelines, and badges.',
    inputSchema: {
      type: 'object',
      properties: {
        path:          { type: 'string', description: 'Project root path' },
        project_name:  { type: 'string', description: 'Project name (auto-detected)' },
        sections:      { type: 'array',  description: '["badges", "install", "usage", "api", "contributing", "license"]' },
        include_badges:{ type: 'boolean', description: 'Add status badges (default: true)' },
        language:      { type: 'string', description: 'Readme language (default: "English")' },
      },
      required: ['path'],
    },
  },

  // ── Accessibility & Compliance (4 tools) ───────────────────────────────
  {
    name: 'accessibility_audit',
    description:
      'WCAG 2.1 compliance audit. Checks color contrast, alt text, ARIA labels, heading structure, ' +
      'keyboard navigation, screen reader compatibility. Returns score and fix recommendations.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain to audit' },
        level:  { type: 'string', description: '"A", "AA" (default), or "AAA"' },
        pages:  { type: 'number', description: 'Number of pages to audit (default: 5)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'cookie_consent_setup',
    description:
      'Deploy a GDPR-compliant cookie consent banner. Supports multiple styles, ' +
      'cookie categorization, and preference persistence.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:  { type: 'string', description: 'Domain to add consent banner to' },
        style:   { type: 'string', description: '"banner" (default), "modal", or "bar"' },
        position:{ type: 'string', description: '"bottom" (default), "top"' },
        color:   { type: 'string', description: 'Accent color (hex)' },
        privacy_url:{ type: 'string', description: 'Privacy policy URL' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'gdpr_audit',
    description:
      'Audit data collection compliance: cookies, tracking scripts, forms collecting personal data, ' +
      'third-party data sharing, data retention, and consent mechanisms.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain to audit' },
        framework: { type: 'string', description: '"gdpr" (default), "ccpa", "pipeda", or "all"' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'ada_fix',
    description:
      'Auto-fix common accessibility issues: add alt text to images, fix contrast ratios, ' +
      'add ARIA labels, fix heading hierarchy, add skip-nav links.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to fix' },
        fix_types: { type: 'array',  description: '["alt_text", "contrast", "aria", "headings", "skip_nav"] (default: all)' },
        dry_run:   { type: 'boolean', description: 'Preview changes without applying (default: false)' },
      },
      required: ['domain'],
    },
  },

  // ── Customer Success (6 tools) ─────────────────────────────────────────
  {
    name: 'get_customer_journey',
    description:
      'Full customer timeline: signup date, purchases, support tickets, login frequency, ' +
      'feature usage, and key milestones.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID (uses current user if not set)' },
      },
      required: [],
    },
  },
  {
    name: 'calculate_churn_risk',
    description:
      'AI-powered churn risk assessment: scores 0-100 based on login frequency, support interactions, ' +
      'invoice payment speed, feature adoption, and usage trends.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID (uses current user if not set)' },
      },
      required: [],
    },
  },
  {
    name: 'suggest_upsell',
    description:
      'Analyze customer usage and recommend relevant plan upgrades, addons, or services. ' +
      'Includes specific dollar impact and feature benefits.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID (uses current user if not set)' },
      },
      required: [],
    },
  },
  {
    name: 'get_satisfaction_score',
    description:
      'Aggregate customer satisfaction: support ticket sentiment, response times, resolution speed, ' +
      'and overall happiness metric.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID (uses current user if not set)' },
      },
      required: [],
    },
  },
  {
    name: 'create_onboarding_checklist',
    description:
      'Generate a personalized onboarding checklist based on the customer\'s plan, purchased products, ' +
      'and goals. Auto-checks completed items.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID' },
        goal:      { type: 'string', description: 'Customer\'s stated goal (e.g. "launch a WordPress blog")' },
      },
      required: [],
    },
  },
  {
    name: 'send_nps_survey',
    description:
      'Send a Net Promoter Score survey via email. Includes the 0-10 rating and follow-up question.',
    inputSchema: {
      type: 'object',
      properties: {
        client_id: { type: 'number', description: 'WHMCS client ID' },
        template:  { type: 'string', description: '"standard" (default), "short", or "detailed"' },
      },
      required: [],
    },
  },

  // ── Project Intelligence (5 tools) ─────────────────────────────────────
  {
    name: 'detect_framework',
    description:
      'Auto-detect the tech stack of a project: language, framework, CMS, database, package manager, ' +
      'build tools, and runtime versions.',
    inputSchema: {
      type: 'object',
      properties: {
        path: { type: 'string', description: 'Project root path (default: domain root)' },
      },
      required: [],
    },
  },
  {
    name: 'project_health_report',
    description:
      'Comprehensive project health report: outdated dependencies, security vulnerabilities, ' +
      'code quality, test coverage, performance, and SEO — all in one scan.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain to analyze' },
        include: { type: 'array', description: '["deps", "security", "quality", "performance", "seo"] (default: all)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'estimate_complexity',
    description:
      'Analyze codebase complexity: lines of code, cyclomatic complexity, function count, file count, ' +
      'dependency depth, and maintainability index.',
    inputSchema: {
      type: 'object',
      properties: {
        path:       { type: 'string', description: 'Project path' },
        language:   { type: 'string', description: 'Limit to language (auto-detect if not set)' },
        detail:     { type: 'string', description: '"summary" (default) or "detailed" (per-file)' },
      },
      required: ['path'],
    },
  },
  {
    name: 'suggest_improvements',
    description:
      'AI-powered code improvement suggestions: performance optimizations, security fixes, ' +
      'best practice violations, and refactoring opportunities.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path: { type: 'string', description: 'Specific file to review, or project path for broad scan' },
        focus:     { type: 'string', description: '"all" (default), "performance", "security", "readability", "best_practices"' },
        max_suggestions: { type: 'number', description: 'Maximum suggestions (default: 10)' },
      },
      required: ['file_path'],
    },
  },
  {
    name: 'generate_documentation',
    description:
      'Auto-generate API documentation from code. Parses JSDoc/PHPDoc/docstrings and creates ' +
      'Swagger/OpenAPI docs, markdown docs, or HTML documentation site.',
    inputSchema: {
      type: 'object',
      properties: {
        path:     { type: 'string', description: 'Project path to document' },
        format:   { type: 'string', description: '"markdown" (default), "swagger", "html"' },
        output:   { type: 'string', description: 'Output path (default: docs/ in project root)' },
        include:  { type: 'array',  description: 'File patterns to include (default: ["**/*.js", "**/*.php", "**/*.py"])' },
      },
      required: ['path'],
    },
  },

  // ── Scheduling & Automation (4 tools) ──────────────────────────────────
  {
    name: 'setup_uptime_monitor',
    description:
      'Configure HTTP/TCP uptime monitoring with email/SMS alerts. Checks periodically ' +
      'and tracks response time history.',
    inputSchema: {
      type: 'object',
      properties: {
        url:          { type: 'string', description: 'URL to monitor' },
        interval:     { type: 'number', description: 'Check interval in minutes (default: 5)' },
        alert_email:  { type: 'string', description: 'Email for alerts' },
        alert_sms:    { type: 'string', description: 'Phone for SMS alerts (optional)' },
        expected_code:{ type: 'number', description: 'Expected HTTP status (default: 200)' },
        timeout:      { type: 'number', description: 'Timeout in seconds (default: 10)' },
      },
      required: ['url'],
    },
  },
  {
    name: 'create_maintenance_window',
    description:
      'Schedule a maintenance window: shows a custom 503 page during the window, ' +
      'whitelists admin IPs, and auto-restores when done.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to put in maintenance mode' },
        start_at:  { type: 'string', description: 'Start time ISO (or "now")' },
        end_at:    { type: 'string', description: 'End time ISO' },
        message:   { type: 'string', description: 'Custom maintenance message' },
        whitelist: { type: 'array',  description: 'IP addresses to allow through' },
      },
      required: ['domain', 'end_at'],
    },
  },
  {
    name: 'auto_backup_schedule',
    description:
      'Set up intelligent automatic backup schedule based on site activity and change frequency. ' +
      'Configures daily/weekly/monthly backups with retention policies.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:     { type: 'string', description: 'Domain to back up' },
        frequency:  { type: 'string', description: '"auto" (default — smart scheduling), "daily", "weekly", "monthly"' },
        retention:  { type: 'number', description: 'Number of backups to keep (default: 7)' },
        include_db: { type: 'boolean', description: 'Include database (default: true)' },
        time:       { type: 'string', description: 'Preferred backup time (e.g. "03:00")' },
        notify:     { type: 'boolean', description: 'Email notification after backup (default: false)' },
      },
      required: ['domain'],
    },
  },
  {
    name: 'dead_link_scan',
    description:
      'Crawl the site and find all broken links: internal 404s, dead external links, ' +
      'broken images, and broken anchors. Returns a fix-it report.',
    inputSchema: {
      type: 'object',
      properties: {
        domain:    { type: 'string', description: 'Domain to scan' },
        max_pages: { type: 'number', description: 'Max pages to crawl (default: 100)' },
        check_external: { type: 'boolean', description: 'Check external links too (default: true, slower)' },
        check_images:   { type: 'boolean', description: 'Check image sources (default: true)' },
      },
      required: ['domain'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // CONDUIT — API & Integration Gateway (13 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'conduit_register_api',
    description: 'Register an external API endpoint for use in pipelines. Store base URL, auth headers, and rate-limit config.',
    inputSchema: {
      type: 'object',
      properties: {
        name:      { type: 'string', description: 'Unique API name' },
        base_url:  { type: 'string', description: 'API base URL' },
        auth_type: { type: 'string', description: 'Auth type: none, bearer, api_key, basic' },
        auth_value:{ type: 'string', description: 'Auth token/key value' },
        headers:   { type: 'object', description: 'Additional headers' },
        rate_limit:{ type: 'number', description: 'Max requests per minute' },
      },
      required: ['name', 'base_url'],
    },
  },
  {
    name: 'conduit_list_apis',
    description: 'List all registered API endpoints for the current user.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'conduit_call_api',
    description: 'Call a registered API endpoint. Supports GET, POST, PUT, PATCH, DELETE with automatic auth injection.',
    inputSchema: {
      type: 'object',
      properties: {
        api_name: { type: 'string', description: 'Name of registered API' },
        method:   { type: 'string', description: 'HTTP method (GET, POST, etc.)' },
        path:     { type: 'string', description: 'Request path appended to base URL' },
        body:     { type: 'object', description: 'Request body for POST/PUT/PATCH' },
        query:    { type: 'object', description: 'Query parameters' },
      },
      required: ['api_name'],
    },
  },
  {
    name: 'conduit_remove_api',
    description: 'Remove a registered API endpoint.',
    inputSchema: {
      type: 'object',
      properties: { name: { type: 'string', description: 'API name to remove' } },
      required: ['name'],
    },
  },
  {
    name: 'conduit_create_webhook',
    description: 'Create a webhook listener that stores incoming payloads for inspection.',
    inputSchema: {
      type: 'object',
      properties: {
        name:   { type: 'string', description: 'Webhook name' },
        secret: { type: 'string', description: 'Optional signing secret for validation' },
        events: { type: 'array', items: { type: 'string' }, description: 'Event types to filter' },
      },
      required: ['name'],
    },
  },
  {
    name: 'conduit_list_webhooks',
    description: 'List all webhook listeners and their recent event counts.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'conduit_test_webhook',
    description: 'Send a test payload to a webhook to verify it works.',
    inputSchema: {
      type: 'object',
      properties: {
        name:    { type: 'string', description: 'Webhook name' },
        payload: { type: 'object', description: 'Test payload to send' },
      },
      required: ['name'],
    },
  },
  {
    name: 'conduit_delete_webhook',
    description: 'Delete a webhook listener and its stored payloads.',
    inputSchema: {
      type: 'object',
      properties: { name: { type: 'string', description: 'Webhook name' } },
      required: ['name'],
    },
  },
  {
    name: 'conduit_create_pipeline',
    description: 'Create a multi-step data pipeline that chains API calls with transforms between them.',
    inputSchema: {
      type: 'object',
      properties: {
        name:  { type: 'string', description: 'Pipeline name' },
        steps: { type: 'array', description: 'Array of pipeline step objects: { api_name, method, path, transform }' },
      },
      required: ['name', 'steps'],
    },
  },
  {
    name: 'conduit_list_pipelines',
    description: 'List all data pipelines.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'conduit_run_pipeline',
    description: 'Execute a data pipeline, running each step in sequence with data passed between steps.',
    inputSchema: {
      type: 'object',
      properties: {
        name:  { type: 'string', description: 'Pipeline name' },
        input: { type: 'object', description: 'Initial input data' },
      },
      required: ['name'],
    },
  },
  {
    name: 'conduit_delete_pipeline',
    description: 'Delete a data pipeline.',
    inputSchema: {
      type: 'object',
      properties: { name: { type: 'string', description: 'Pipeline name' } },
      required: ['name'],
    },
  },
  {
    name: 'conduit_get_logs',
    description: 'Retrieve API call logs with optional filtering by API name, status code, or time range.',
    inputSchema: {
      type: 'object',
      properties: {
        api_name: { type: 'string', description: 'Filter by API name' },
        status:   { type: 'number', description: 'Filter by HTTP status code' },
        limit:    { type: 'number', description: 'Max log entries (default: 50)' },
      },
      required: [],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // ARCHITECT — Infrastructure & DevOps (9 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'architect_env_list',
    description: 'List all environment variables for the user project (names only, values redacted).',
    inputSchema: {
      type: 'object',
      properties: {
        show_values: { type: 'boolean', description: 'Show values (default: false for security)' },
      },
      required: [],
    },
  },
  {
    name: 'architect_env_get',
    description: 'Get a specific environment variable value.',
    inputSchema: {
      type: 'object',
      properties: { key: { type: 'string', description: 'Environment variable name' } },
      required: ['key'],
    },
  },
  {
    name: 'architect_env_set',
    description: 'Set or update an environment variable in the project .env file.',
    inputSchema: {
      type: 'object',
      properties: {
        key:   { type: 'string', description: 'Variable name' },
        value: { type: 'string', description: 'Variable value' },
      },
      required: ['key', 'value'],
    },
  },
  {
    name: 'architect_scaffold',
    description: 'Scaffold a new project from a template: node-api, react-app, php-app, static-site, python-flask, or custom.',
    inputSchema: {
      type: 'object',
      properties: {
        template:   { type: 'string', description: 'Template name' },
        name:       { type: 'string', description: 'Project name' },
        target_dir: { type: 'string', description: 'Target directory' },
        options:    { type: 'object', description: 'Template-specific options' },
      },
      required: ['template', 'name'],
    },
  },
  {
    name: 'architect_create_deployment',
    description: 'Create a deployment configuration (build command, deploy steps, environment).',
    inputSchema: {
      type: 'object',
      properties: {
        name:          { type: 'string', description: 'Deployment name' },
        type:          { type: 'string', description: 'Type: git, docker, rsync, custom' },
        build_command: { type: 'string', description: 'Build command to run' },
        deploy_steps:  { type: 'array', items: { type: 'string' }, description: 'Deploy commands in order' },
      },
      required: ['name', 'type'],
    },
  },
  {
    name: 'architect_list_deployments',
    description: 'List all deployment configurations.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'architect_run_deployment',
    description: 'Execute a deployment configuration (build + deploy steps).',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Deployment name to run' },
        dry_run: { type: 'boolean', description: 'Simulate without executing (default: false)' },
      },
      required: ['name'],
    },
  },
  {
    name: 'architect_analyze',
    description: 'Analyze project architecture: detect frameworks, find config files, map directory structure, estimate complexity.',
    inputSchema: {
      type: 'object',
      properties: {
        target_dir: { type: 'string', description: 'Directory to analyze (default: home)' },
        depth:      { type: 'number', description: 'Max directory depth (default: 3)' },
      },
      required: [],
    },
  },
  {
    name: 'architect_resources',
    description: 'Get system resource usage: CPU, memory, disk, load average, uptime.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // SENTINEL — Security Monitoring & Threat Detection (10 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'sentinel_create_baseline',
    description: 'Create a file integrity baseline (SHA-256 hashes) for a directory to detect unauthorized changes.',
    inputSchema: {
      type: 'object',
      properties: {
        directory:    { type: 'string', description: 'Directory to baseline' },
        name:         { type: 'string', description: 'Baseline name' },
        exclude:      { type: 'array', items: { type: 'string' }, description: 'Glob patterns to exclude' },
      },
      required: ['directory', 'name'],
    },
  },
  {
    name: 'sentinel_check_integrity',
    description: 'Compare current files against a baseline to find added, modified, or deleted files.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Baseline name to check' },
      },
      required: ['name'],
    },
  },
  {
    name: 'sentinel_analyze_access_logs',
    description: 'Analyze web server access logs for suspicious patterns: brute force, scanning, SQL injection attempts.',
    inputSchema: {
      type: 'object',
      properties: {
        log_file:  { type: 'string', description: 'Path to access log' },
        last_lines:{ type: 'number', description: 'Analyze last N lines (default: 1000)' },
      },
      required: [],
    },
  },
  {
    name: 'sentinel_vuln_scan',
    description: 'Scan project files for common vulnerabilities: hardcoded secrets, SQL injection risks, XSS vectors, insecure permissions.',
    inputSchema: {
      type: 'object',
      properties: {
        directory: { type: 'string', description: 'Directory to scan' },
        scan_type: { type: 'string', description: 'Scan type: secrets, injection, xss, permissions, all (default: all)' },
      },
      required: [],
    },
  },
  {
    name: 'sentinel_check_ip',
    description: 'Check IP address reputation using local threat intelligence and pattern matching.',
    inputSchema: {
      type: 'object',
      properties: {
        ip: { type: 'string', description: 'IP address to check' },
      },
      required: ['ip'],
    },
  },
  {
    name: 'sentinel_log_incident',
    description: 'Log a security incident with severity, description, and affected systems.',
    inputSchema: {
      type: 'object',
      properties: {
        title:    { type: 'string', description: 'Incident title' },
        severity: { type: 'string', description: 'Severity: critical, high, medium, low' },
        description: { type: 'string', description: 'Detailed description' },
        affected: { type: 'array', items: { type: 'string' }, description: 'Affected systems/files' },
      },
      required: ['title', 'severity'],
    },
  },
  {
    name: 'sentinel_list_incidents',
    description: 'List all security incidents, optionally filtered by severity or status.',
    inputSchema: {
      type: 'object',
      properties: {
        severity: { type: 'string', description: 'Filter by severity' },
        status:   { type: 'string', description: 'Filter by status: open, resolved, investigating' },
      },
      required: [],
    },
  },
  {
    name: 'sentinel_resolve_incident',
    description: 'Mark a security incident as resolved with resolution notes.',
    inputSchema: {
      type: 'object',
      properties: {
        incident_id: { type: 'string', description: 'Incident ID' },
        resolution:  { type: 'string', description: 'Resolution description' },
      },
      required: ['incident_id', 'resolution'],
    },
  },
  {
    name: 'sentinel_set_policy',
    description: 'Set a security policy rule (e.g., max file permissions, required headers, banned patterns).',
    inputSchema: {
      type: 'object',
      properties: {
        name:    { type: 'string', description: 'Policy name' },
        type:    { type: 'string', description: 'Policy type: permission, header, pattern, rate_limit' },
        rule:    { type: 'object', description: 'Policy rule definition' },
        action:  { type: 'string', description: 'Action on violation: warn, block, alert' },
      },
      required: ['name', 'type', 'rule'],
    },
  },
  {
    name: 'sentinel_list_policies',
    description: 'List all security policies.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // FORGE — Code Generation & Scaffolding (7 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'forge_generate_crud',
    description: 'Generate complete CRUD operations (Create, Read, Update, Delete) for a data model with API routes, validation, and DB queries.',
    inputSchema: {
      type: 'object',
      properties: {
        model_name: { type: 'string', description: 'Model/resource name (e.g., "User", "Product")' },
        fields: { type: 'array', description: 'Array of field objects: { name, type, required, unique }' },
        framework: { type: 'string', description: 'Target framework: express, php, fastify (default: express)' },
        database:  { type: 'string', description: 'Database type: mysql, sqlite, mongodb (default: mysql)' },
      },
      required: ['model_name', 'fields'],
    },
  },
  {
    name: 'forge_generate_component',
    description: 'Generate a UI component with template, styles, and logic. Supports React, Vue, Svelte, or vanilla HTML/CSS/JS.',
    inputSchema: {
      type: 'object',
      properties: {
        name:      { type: 'string', description: 'Component name' },
        framework: { type: 'string', description: 'Framework: react, vue, svelte, vanilla (default: react)' },
        props:     { type: 'array', items: { type: 'string' }, description: 'Component props/attributes' },
        features:  { type: 'array', items: { type: 'string' }, description: 'Features: state, events, slots, routing' },
      },
      required: ['name'],
    },
  },
  {
    name: 'forge_generate_tests',
    description: 'Generate test cases for a code file or function. Creates unit tests with assertions and edge cases.',
    inputSchema: {
      type: 'object',
      properties: {
        target:    { type: 'string', description: 'File path or function name to test' },
        framework: { type: 'string', description: 'Test framework: jest, mocha, vitest, phpunit (default: jest)' },
        style:     { type: 'string', description: 'Test style: unit, integration, e2e (default: unit)' },
        code:      { type: 'string', description: 'Source code to generate tests for (if not reading from file)' },
      },
      required: ['target'],
    },
  },
  {
    name: 'forge_analyze_code',
    description: 'Analyze code for quality metrics: complexity, duplication, code smells, dependency analysis, and improvement suggestions.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path: { type: 'string', description: 'File to analyze' },
        metrics:   { type: 'array', items: { type: 'string' }, description: 'Metrics: complexity, duplication, smells, dependencies, all' },
      },
      required: ['file_path'],
    },
  },
  {
    name: 'forge_save_snippet',
    description: 'Save a reusable code snippet to the snippet library with tags and description.',
    inputSchema: {
      type: 'object',
      properties: {
        name:        { type: 'string', description: 'Snippet name' },
        language:    { type: 'string', description: 'Programming language' },
        code:        { type: 'string', description: 'Code content' },
        description: { type: 'string', description: 'What it does' },
        tags:        { type: 'array', items: { type: 'string' }, description: 'Tags for searching' },
      },
      required: ['name', 'language', 'code'],
    },
  },
  {
    name: 'forge_list_snippets',
    description: 'List saved code snippets, optionally filtered by language or tags.',
    inputSchema: {
      type: 'object',
      properties: {
        language: { type: 'string', description: 'Filter by language' },
        tag:      { type: 'string', description: 'Filter by tag' },
        search:   { type: 'string', description: 'Search in names and descriptions' },
      },
      required: [],
    },
  },
  {
    name: 'forge_get_snippet',
    description: 'Retrieve a saved code snippet by name.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Snippet name' },
      },
      required: ['name'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // CHRONICLE — Audit Trail & Activity Logging (11 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'chronicle_log_event',
    description: 'Log an audit event with category, action, details, and optional metadata.',
    inputSchema: {
      type: 'object',
      properties: {
        category: { type: 'string', description: 'Event category: file, auth, deploy, config, security, custom' },
        action:   { type: 'string', description: 'Action performed' },
        details:  { type: 'string', description: 'Event details' },
        metadata: { type: 'object', description: 'Additional metadata' },
        severity: { type: 'string', description: 'Severity: info, warning, error, critical (default: info)' },
      },
      required: ['category', 'action'],
    },
  },
  {
    name: 'chronicle_query_events',
    description: 'Query audit events with filters: category, date range, severity, keyword search.',
    inputSchema: {
      type: 'object',
      properties: {
        category:  { type: 'string', description: 'Filter by category' },
        severity:  { type: 'string', description: 'Filter by severity' },
        since:     { type: 'string', description: 'Start date (ISO 8601)' },
        until:     { type: 'string', description: 'End date (ISO 8601)' },
        search:    { type: 'string', description: 'Search in action/details' },
        limit:     { type: 'number', description: 'Max results (default: 50)' },
      },
      required: [],
    },
  },
  {
    name: 'chronicle_verify_integrity',
    description: 'Verify the integrity of the audit log using hash chain validation. Detects tampered or deleted entries.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'chronicle_track_activity',
    description: 'Record a user activity (file edit, command run, tool use) for activity monitoring.',
    inputSchema: {
      type: 'object',
      properties: {
        activity_type: { type: 'string', description: 'Type: file_edit, command, tool_use, login, deploy' },
        target:        { type: 'string', description: 'What was acted on' },
        details:       { type: 'string', description: 'Activity details' },
      },
      required: ['activity_type', 'target'],
    },
  },
  {
    name: 'chronicle_activity_summary',
    description: 'Get a summary of user activity: most active times, top actions, file heat map.',
    inputSchema: {
      type: 'object',
      properties: {
        period: { type: 'string', description: 'Period: today, week, month (default: today)' },
      },
      required: [],
    },
  },
  {
    name: 'chronicle_record_change',
    description: 'Record a file change with before/after content diff for change tracking.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path: { type: 'string', description: 'File that changed' },
        change_type: { type: 'string', description: 'Type: create, modify, delete, rename' },
        before:    { type: 'string', description: 'Content before change (snippet)' },
        after:     { type: 'string', description: 'Content after change (snippet)' },
        reason:    { type: 'string', description: 'Reason for change' },
      },
      required: ['file_path', 'change_type'],
    },
  },
  {
    name: 'chronicle_change_history',
    description: 'Get the change history for a specific file or directory.',
    inputSchema: {
      type: 'object',
      properties: {
        file_path: { type: 'string', description: 'File or directory path' },
        limit:     { type: 'number', description: 'Max entries (default: 20)' },
      },
      required: ['file_path'],
    },
  },
  {
    name: 'chronicle_start_session',
    description: 'Start a named work session to group related activities together.',
    inputSchema: {
      type: 'object',
      properties: {
        name:        { type: 'string', description: 'Session name' },
        description: { type: 'string', description: 'What this session is for' },
        tags:        { type: 'array', items: { type: 'string' }, description: 'Session tags' },
      },
      required: ['name'],
    },
  },
  {
    name: 'chronicle_end_session',
    description: 'End a work session and generate a summary of what happened during it.',
    inputSchema: {
      type: 'object',
      properties: {
        session_id: { type: 'string', description: 'Session ID to end' },
        summary:    { type: 'string', description: 'Optional session summary' },
      },
      required: ['session_id'],
    },
  },
  {
    name: 'chronicle_list_sessions',
    description: 'List work sessions with their duration, activity counts, and tags.',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'Filter: active, ended, all (default: all)' },
      },
      required: [],
    },
  },
  {
    name: 'chronicle_compliance_report',
    description: 'Generate a compliance report: activity summary, security events, change audit trail for a date range.',
    inputSchema: {
      type: 'object',
      properties: {
        since: { type: 'string', description: 'Start date (ISO 8601)' },
        until: { type: 'string', description: 'End date (ISO 8601)' },
        format: { type: 'string', description: 'Report format: summary, detailed (default: summary)' },
      },
      required: [],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // NEXUS — Knowledge Graph & Connections (11 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'nexus_add_entity',
    description: 'Add an entity to the knowledge graph: files, functions, services, concepts, people, or custom types.',
    inputSchema: {
      type: 'object',
      properties: {
        name:       { type: 'string', description: 'Entity name' },
        type:       { type: 'string', description: 'Entity type: file, function, service, concept, person, api, database' },
        properties: { type: 'object', description: 'Entity properties/attributes' },
        tags:       { type: 'array', items: { type: 'string' }, description: 'Tags for categorization' },
      },
      required: ['name', 'type'],
    },
  },
  {
    name: 'nexus_add_relation',
    description: 'Add a relationship between two entities in the knowledge graph.',
    inputSchema: {
      type: 'object',
      properties: {
        from:     { type: 'string', description: 'Source entity name' },
        to:       { type: 'string', description: 'Target entity name' },
        relation: { type: 'string', description: 'Relationship type: depends_on, calls, imports, extends, uses, owned_by, related_to' },
        weight:   { type: 'number', description: 'Relationship strength 0-1 (default: 1)' },
        metadata: { type: 'object', description: 'Relationship metadata' },
      },
      required: ['from', 'to', 'relation'],
    },
  },
  {
    name: 'nexus_remove_entity',
    description: 'Remove an entity and all its relationships from the knowledge graph.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Entity name to remove' },
      },
      required: ['name'],
    },
  },
  {
    name: 'nexus_query',
    description: 'Query the knowledge graph: find entities by type, name pattern, or connected entities.',
    inputSchema: {
      type: 'object',
      properties: {
        type:    { type: 'string', description: 'Filter by entity type' },
        pattern: { type: 'string', description: 'Name pattern (glob-style)' },
        tag:     { type: 'string', description: 'Filter by tag' },
        related_to: { type: 'string', description: 'Find entities related to this entity' },
      },
      required: [],
    },
  },
  {
    name: 'nexus_neighbors',
    description: 'Get all directly connected entities (neighbors) for a given entity.',
    inputSchema: {
      type: 'object',
      properties: {
        name:     { type: 'string', description: 'Entity name' },
        relation: { type: 'string', description: 'Filter by relationship type' },
        depth:    { type: 'number', description: 'Traversal depth (default: 1, max: 3)' },
      },
      required: ['name'],
    },
  },
  {
    name: 'nexus_impact_analysis',
    description: 'Analyze the impact of changing an entity: find all dependents, transitive dependencies, and affected paths.',
    inputSchema: {
      type: 'object',
      properties: {
        name:  { type: 'string', description: 'Entity to analyze impact for' },
        depth: { type: 'number', description: 'Analysis depth (default: 3)' },
      },
      required: ['name'],
    },
  },
  {
    name: 'nexus_discover_dependencies',
    description: 'Auto-discover dependencies by scanning project files (imports, requires, includes).',
    inputSchema: {
      type: 'object',
      properties: {
        directory: { type: 'string', description: 'Directory to scan' },
        language:  { type: 'string', description: 'Language: javascript, php, python, auto (default: auto)' },
      },
      required: [],
    },
  },
  {
    name: 'nexus_stats',
    description: 'Get knowledge graph statistics: entity counts by type, relationship counts, most connected nodes.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'nexus_add_knowledge',
    description: 'Add a free-form knowledge note to the graph, linked to entities or standalone.',
    inputSchema: {
      type: 'object',
      properties: {
        title:    { type: 'string', description: 'Knowledge title' },
        content:  { type: 'string', description: 'Knowledge content' },
        category: { type: 'string', description: 'Category: architecture, pattern, gotcha, todo, reference' },
        related:  { type: 'array', items: { type: 'string' }, description: 'Related entity names' },
        tags:     { type: 'array', items: { type: 'string' }, description: 'Tags' },
      },
      required: ['title', 'content'],
    },
  },
  {
    name: 'nexus_search_knowledge',
    description: 'Search knowledge notes by keyword, category, or related entities.',
    inputSchema: {
      type: 'object',
      properties: {
        query:    { type: 'string', description: 'Search query' },
        category: { type: 'string', description: 'Filter by category' },
      },
      required: ['query'],
    },
  },
  {
    name: 'nexus_list_knowledge',
    description: 'List all knowledge notes, optionally filtered by category.',
    inputSchema: {
      type: 'object',
      properties: {
        category: { type: 'string', description: 'Filter by category' },
        limit:    { type: 'number', description: 'Max results (default: 50)' },
      },
      required: [],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // CORTEX — Advanced Reasoning & Planning (15 tools)
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'cortex_decompose',
    description: 'Decompose a complex task into ordered subtasks with priorities, time estimates, and dependencies. Smart auto-decomposition for build/fix/debug tasks.',
    inputSchema: {
      type: 'object',
      properties: {
        title:       { type: 'string', description: 'Task title' },
        description: { type: 'string', description: 'Full task description' },
        subtasks:    { type: 'array', description: 'Optional explicit subtasks: [{ title, priority, estimated_minutes, dependencies }]' },
      },
      required: ['description'],
    },
  },
  {
    name: 'cortex_update_step',
    description: 'Update a step in a decomposed plan (change status, add notes).',
    inputSchema: {
      type: 'object',
      properties: {
        plan_id: { type: 'string', description: 'Plan ID' },
        step_id: { type: 'string', description: 'Step ID' },
        status:  { type: 'string', description: 'New status: pending, in_progress, completed, blocked' },
        notes:   { type: 'string', description: 'Step notes' },
      },
      required: ['plan_id', 'step_id'],
    },
  },
  {
    name: 'cortex_get_plan',
    description: 'Get a full plan with all steps, progress, and status.',
    inputSchema: {
      type: 'object',
      properties: {
        plan_id: { type: 'string', description: 'Plan ID' },
      },
      required: ['plan_id'],
    },
  },
  {
    name: 'cortex_list_plans',
    description: 'List all plans with summary info: title, progress percentage, step count.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'cortex_set_goal',
    description: 'Set a goal with tracking target, milestones, category (project/learning/quality/performance), and deadline.',
    inputSchema: {
      type: 'object',
      properties: {
        title:       { type: 'string', description: 'Goal title' },
        description: { type: 'string', description: 'Goal description' },
        category:    { type: 'string', description: 'Category: project, learning, quality, performance' },
        target:      { type: 'object', description: 'Target: { metric, value } e.g. { metric: "test_coverage", value: 80 }' },
        priority:    { type: 'string', description: 'Priority: high, medium, low' },
        deadline:    { type: 'string', description: 'Deadline date (ISO 8601)' },
      },
      required: ['title'],
    },
  },
  {
    name: 'cortex_update_goal',
    description: 'Update a goal progress, status, or record a milestone.',
    inputSchema: {
      type: 'object',
      properties: {
        goal_id:           { type: 'string', description: 'Goal ID' },
        current:           { type: 'number', description: 'Current progress value' },
        status:            { type: 'string', description: 'New status: active, paused, achieved, abandoned' },
        milestone_reached: { type: 'string', description: 'Milestone label to record' },
        notes:             { type: 'string', description: 'Notes' },
      },
      required: ['goal_id'],
    },
  },
  {
    name: 'cortex_list_goals',
    description: 'List all goals with progress and status, optionally filtered by category.',
    inputSchema: {
      type: 'object',
      properties: {
        category: { type: 'string', description: 'Filter by category' },
      },
      required: [],
    },
  },
  {
    name: 'cortex_analyze_decision',
    description: 'Analyze a decision with multiple options. Score each option based on pros/cons, risk, effort, and impact to get a recommendation.',
    inputSchema: {
      type: 'object',
      properties: {
        question: { type: 'string', description: 'Decision question' },
        context:  { type: 'string', description: 'Decision context' },
        options:  { type: 'array', description: 'Options: [{ label, pros: [], cons: [], risk, effort, impact }]' },
      },
      required: ['question', 'options'],
    },
  },
  {
    name: 'cortex_record_decision',
    description: 'Record the final decision made, with reasoning.',
    inputSchema: {
      type: 'object',
      properties: {
        decision_id: { type: 'string', description: 'Decision ID' },
        chosen:      { type: 'string', description: 'Chosen option label' },
        reasoning:   { type: 'string', description: 'Why this option was chosen' },
      },
      required: ['decision_id', 'chosen'],
    },
  },
  {
    name: 'cortex_list_decisions',
    description: 'List all decisions, optionally filtered by status (pending/decided).',
    inputSchema: {
      type: 'object',
      properties: {
        status: { type: 'string', description: 'Filter: pending, decided' },
      },
      required: [],
    },
  },
  {
    name: 'cortex_add_reasoning',
    description: 'Add a reasoning step to a chain of thought: observation, hypothesis, evidence, deduction, or conclusion.',
    inputSchema: {
      type: 'object',
      properties: {
        chain_id:   { type: 'string', description: 'Chain ID (auto-generated if new)' },
        topic:      { type: 'string', description: 'Reasoning topic (for new chains)' },
        type:       { type: 'string', description: 'Step type: observation, hypothesis, evidence, deduction, conclusion' },
        content:    { type: 'string', description: 'Reasoning content' },
        evidence:   { type: 'array', items: { type: 'string' }, description: 'Supporting evidence' },
        confidence: { type: 'string', description: 'Confidence: high, medium, low' },
      },
      required: ['content', 'type'],
    },
  },
  {
    name: 'cortex_get_reasoning',
    description: 'Get a full reasoning chain with all steps, evidence, and conclusion.',
    inputSchema: {
      type: 'object',
      properties: {
        chain_id: { type: 'string', description: 'Chain ID' },
      },
      required: ['chain_id'],
    },
  },
  {
    name: 'cortex_list_reasoning',
    description: 'List all reasoning chains with summary info.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },
  {
    name: 'cortex_score_priority',
    description: 'Score and rank a list of items by priority based on urgency, impact, effort, deadline, and dependencies.',
    inputSchema: {
      type: 'object',
      properties: {
        items: { type: 'array', description: 'Items to score: [{ title, urgency, impact, effort, deadline, dependencies }]' },
      },
      required: ['items'],
    },
  },
  {
    name: 'cortex_context',
    description: 'Get a summary of all active CORTEX context: goals, plans, pending decisions, open reasoning chains.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // EMPATHY — Emotional Intelligence Engine (11 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'empathy_analyze_sentiment',
    description: 'Analyze text sentiment — positive, negative, neutral with confidence score.',
    inputSchema: { type: 'object', properties: { text: { type: 'string', description: 'Text to analyze' } }, required: ['text'] },
  },
  {
    name: 'empathy_detect_tone',
    description: 'Detect emotional tone — happy, angry, confused, frustrated, neutral, excited, sad, anxious.',
    inputSchema: { type: 'object', properties: { text: { type: 'string', description: 'Text to analyze' } }, required: ['text'] },
  },
  {
    name: 'empathy_track_mood',
    description: 'Track user mood over time in a session. Adds a mood data point.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, mood: { type: 'string' }, context: { type: 'string' } }, required: ['userId', 'mood'] },
  },
  {
    name: 'empathy_mood_history',
    description: 'Get mood history for a user — timeline of emotional states.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' } }, required: ['userId'] },
  },
  {
    name: 'empathy_suggest_response',
    description: 'Suggest empathetic response based on detected emotion and context.',
    inputSchema: { type: 'object', properties: { emotion: { type: 'string' }, context: { type: 'string' }, style: { type: 'string' } }, required: ['emotion'] },
  },
  {
    name: 'empathy_detect_frustration',
    description: 'Detect user frustration level (0-10) with trigger analysis.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' }, history: { type: 'array', items: { type: 'string' } } }, required: ['text'] },
  },
  {
    name: 'empathy_deescalate',
    description: 'Generate de-escalation response for upset/angry user.',
    inputSchema: { type: 'object', properties: { situation: { type: 'string' }, emotion: { type: 'string' }, severity: { type: 'number' } }, required: ['situation'] },
  },
  {
    name: 'empathy_analyze_feedback',
    description: 'Analyze batch feedback/reviews for emotional patterns and sentiment distribution.',
    inputSchema: { type: 'object', properties: { feedbacks: { type: 'array', items: { type: 'string' }, description: 'Array of feedback strings' } }, required: ['feedbacks'] },
  },
  {
    name: 'empathy_emotional_summary',
    description: 'Get emotional pattern summary for a user — dominant emotions, trends, triggers.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' } }, required: ['userId'] },
  },
  {
    name: 'empathy_set_tone',
    description: 'Set Alfred response tone — professional, friendly, casual, empathetic, technical.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, tone: { type: 'string' }, reason: { type: 'string' } }, required: ['userId', 'tone'] },
  },
  {
    name: 'empathy_rapport_score',
    description: 'Calculate rapport/relationship quality score with a user (0-100).',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, interactions: { type: 'number' }, positiveRatio: { type: 'number' }, resolved: { type: 'number' }, total: { type: 'number' } }, required: ['userId'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // MUSE — Creative Intelligence Engine (10 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'muse_brainstorm',
    description: 'Generate creative ideas for a topic/problem with scoring and feasibility.',
    inputSchema: { type: 'object', properties: { topic: { type: 'string' }, count: { type: 'number' }, constraints: { type: 'array', items: { type: 'string' } } }, required: ['topic'] },
  },
  {
    name: 'muse_brand_voice',
    description: 'Define/analyze brand voice and tone — archetypes, personality traits, communication rules.',
    inputSchema: { type: 'object', properties: { action: { type: 'string', description: 'define or analyze' }, name: { type: 'string' }, values: { type: 'array', items: { type: 'string' } }, sampleText: { type: 'string' } }, required: ['action'] },
  },
  {
    name: 'muse_storytell',
    description: 'Generate compelling narratives/stories for products, features, or brands.',
    inputSchema: { type: 'object', properties: { subject: { type: 'string' }, audience: { type: 'string' }, style: { type: 'string' } }, required: ['subject'] },
  },
  {
    name: 'muse_name_generator',
    description: 'Generate creative names for products, domains, features, or projects.',
    inputSchema: { type: 'object', properties: { concept: { type: 'string' }, style: { type: 'string' }, count: { type: 'number' } }, required: ['concept'] },
  },
  {
    name: 'muse_tagline',
    description: 'Generate taglines/slogans for brands, products, or campaigns.',
    inputSchema: { type: 'object', properties: { brand: { type: 'string' }, product: { type: 'string' }, tone: { type: 'string' } }, required: ['brand'] },
  },
  {
    name: 'muse_variations',
    description: 'Generate creative variations of content — headlines, CTAs, descriptions.',
    inputSchema: { type: 'object', properties: { original: { type: 'string' }, count: { type: 'number' }, type: { type: 'string' } }, required: ['original'] },
  },
  {
    name: 'muse_metaphor',
    description: 'Generate metaphors/analogies for complex technical concepts.',
    inputSchema: { type: 'object', properties: { concept: { type: 'string' }, audience: { type: 'string' } }, required: ['concept'] },
  },
  {
    name: 'muse_mood_board',
    description: 'Generate mood board descriptions — colors, themes, aesthetics, visual direction.',
    inputSchema: { type: 'object', properties: { theme: { type: 'string' }, industry: { type: 'string' } }, required: ['theme'] },
  },
  {
    name: 'muse_copywrite',
    description: 'Generate marketing/sales copy with frameworks (AIDA, PAS, BAB).',
    inputSchema: { type: 'object', properties: { product: { type: 'string' }, audience: { type: 'string' }, framework: { type: 'string' } }, required: ['product'] },
  },
  {
    name: 'muse_pitch',
    description: 'Generate elevator pitch / product pitch with hook, problem, solution, CTA.',
    inputSchema: { type: 'object', properties: { product: { type: 'string' }, audience: { type: 'string' }, duration: { type: 'string' } }, required: ['product'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PRISM — Visual Intelligence Engine (9 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'prism_analyze_colors',
    description: 'Analyze color palette — harmony, warmth balance, accessibility notes.',
    inputSchema: { type: 'object', properties: { colors: { type: 'array', items: { type: 'string' }, description: 'Array of hex colors' } }, required: ['colors'] },
  },
  {
    name: 'prism_suggest_palette',
    description: 'Suggest color palettes based on mood/theme.',
    inputSchema: { type: 'object', properties: { mood: { type: 'string', description: 'professional, playful, nature, luxury, tech, health, energy, calm, dark_mode, sunset' }, count: { type: 'number' } }, required: ['mood'] },
  },
  {
    name: 'prism_check_contrast',
    description: 'Check color contrast for WCAG accessibility (AA/AAA, normal/large text).',
    inputSchema: { type: 'object', properties: { foreground: { type: 'string', description: 'Hex color' }, background: { type: 'string', description: 'Hex color' } }, required: ['foreground', 'background'] },
  },
  {
    name: 'prism_analyze_layout',
    description: 'Analyze page layout / visual hierarchy from HTML.',
    inputSchema: { type: 'object', properties: { html: { type: 'string', description: 'HTML content to analyze' } }, required: ['html'] },
  },
  {
    name: 'prism_design_system',
    description: 'Generate/manage design system tokens (colors, spacing, typography, borders, shadows).',
    inputSchema: { type: 'object', properties: { user: { type: 'string' }, action: { type: 'string', description: 'set, get, or export_css' }, tokens: { type: 'object' } }, required: ['user', 'action'] },
  },
  {
    name: 'prism_responsive_check',
    description: 'Check responsive design breakpoints and viewport coverage.',
    inputSchema: { type: 'object', properties: { cssOrHtml: { type: 'string', description: 'CSS or HTML content' } }, required: ['cssOrHtml'] },
  },
  {
    name: 'prism_typography',
    description: 'Suggest/analyze typography pairings by style (modern, classic, bold, minimal).',
    inputSchema: { type: 'object', properties: { primary: { type: 'string' }, style: { type: 'string' } }, required: [] },
  },
  {
    name: 'prism_visual_score',
    description: 'Score visual design quality (1-100) with detailed checks.',
    inputSchema: { type: 'object', properties: { htmlOrCss: { type: 'string', description: 'HTML or CSS content to evaluate' } }, required: ['htmlOrCss'] },
  },
  {
    name: 'prism_icon_suggest',
    description: 'Suggest icons for features/concepts (Lucide, Heroicons, Font Awesome, emoji).',
    inputSchema: { type: 'object', properties: { concepts: { type: 'array', items: { type: 'string' }, description: 'List of concepts needing icons' } }, required: ['concepts'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // TEMPO — Temporal Intelligence Engine (9 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'tempo_trend_analyze',
    description: 'Analyze trends in time-series data — direction, slope, R², moving average.',
    inputSchema: { type: 'object', properties: { data: { type: 'array', description: 'Array of numbers or {date, value} objects' }, window: { type: 'number' } }, required: ['data'] },
  },
  {
    name: 'tempo_predict',
    description: 'Predict future values based on historical data using linear regression.',
    inputSchema: { type: 'object', properties: { data: { type: 'array' }, periods: { type: 'number' } }, required: ['data'] },
  },
  {
    name: 'tempo_seasonality',
    description: 'Detect seasonal patterns in data — peaks, troughs, pattern strength.',
    inputSchema: { type: 'object', properties: { data: { type: 'array' }, period: { type: 'number', description: 'Cycle length (default 7 for weekly)' } }, required: ['data'] },
  },
  {
    name: 'tempo_deadline_risk',
    description: 'Assess deadline risk based on velocity, remaining tasks, and days left.',
    inputSchema: { type: 'object', properties: { project: { type: 'string' }, deadline: { type: 'string' }, totalTasks: { type: 'number' }, completedTasks: { type: 'number' }, sprintDays: { type: 'number' } }, required: ['project', 'deadline', 'totalTasks', 'completedTasks'] },
  },
  {
    name: 'tempo_velocity',
    description: 'Calculate project velocity (tasks/sprint) — record sprints or get report.',
    inputSchema: { type: 'object', properties: { project: { type: 'string' }, action: { type: 'string', description: 'record or report' }, data: { type: 'object' } }, required: ['project', 'action'] },
  },
  {
    name: 'tempo_capacity',
    description: 'Forecast team capacity and resource needs for sprint planning.',
    inputSchema: { type: 'object', properties: { teamSize: { type: 'number' }, hoursPerDay: { type: 'number' }, sprintDays: { type: 'number' }, overhead: { type: 'number' } }, required: ['teamSize', 'hoursPerDay', 'sprintDays'] },
  },
  {
    name: 'tempo_timeline',
    description: 'Generate project timeline with milestones, dates, and Gantt text.',
    inputSchema: { type: 'object', properties: { project: { type: 'string' }, milestones: { type: 'array', items: { type: 'object' }, description: '[{name, duration_days, dependencies?}]' } }, required: ['project', 'milestones'] },
  },
  {
    name: 'tempo_peak_hours',
    description: 'Identify peak usage hours from timestamp data — hourly/daily distribution.',
    inputSchema: { type: 'object', properties: { timestamps: { type: 'array', items: { type: 'string' }, description: 'Array of ISO timestamps' } }, required: ['timestamps'] },
  },
  {
    name: 'tempo_eta',
    description: 'Estimate time to completion for tasks with buffer recommendation.',
    inputSchema: { type: 'object', properties: { tasks: { type: 'array', description: '[{name, estimate_hours, status?}] or total hours as number' }, velocityPerDay: { type: 'number' }, startDate: { type: 'string' } }, required: ['tasks', 'velocityPerDay'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ECHO — Pattern Intelligence Engine (9 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'echo_detect_anomaly',
    description: 'Detect anomalies in data using Z-score statistical analysis.',
    inputSchema: { type: 'object', properties: { data: { type: 'array' }, threshold: { type: 'number', description: 'Z-score threshold (default 2.5)' } }, required: ['data'] },
  },
  {
    name: 'echo_find_patterns',
    description: 'Find recurring patterns in data — repeating sequences, monotonic runs, clustering.',
    inputSchema: { type: 'object', properties: { data: { type: 'array' } }, required: ['data'] },
  },
  {
    name: 'echo_cluster',
    description: 'Cluster similar items using k-means algorithm.',
    inputSchema: { type: 'object', properties: { items: { type: 'array', description: '[{features: [num,...], label?}]' }, k: { type: 'number', description: 'Number of clusters (default 3)' } }, required: ['items'] },
  },
  {
    name: 'echo_predict_failure',
    description: 'Predict potential failures from system metrics (CPU, memory, disk, error rate, latency).',
    inputSchema: { type: 'object', properties: { metrics: { type: 'object', description: '{cpu, memory, disk, error_rate, latency}' }, thresholds: { type: 'object' } }, required: ['metrics'] },
  },
  {
    name: 'echo_correlate',
    description: 'Find Pearson correlations between two data series.',
    inputSchema: { type: 'object', properties: { seriesA: { type: 'array' }, seriesB: { type: 'array' }, labels: { type: 'object' } }, required: ['seriesA', 'seriesB'] },
  },
  {
    name: 'echo_baseline_drift',
    description: 'Detect drift from baseline behavior — set baselines and check for significant changes.',
    inputSchema: { type: 'object', properties: { key: { type: 'string' }, currentValues: { type: 'array', items: { type: 'number' } }, action: { type: 'string', description: 'set or check' } }, required: ['key', 'currentValues'] },
  },
  {
    name: 'echo_root_cause',
    description: 'Root cause analysis from symptom patterns — timeline, frequency, cascade detection.',
    inputSchema: { type: 'object', properties: { symptoms: { type: 'array', items: { type: 'object' }, description: '[{event, timestamp?, severity?}]' }, context: { type: 'object' } }, required: ['symptoms'] },
  },
  {
    name: 'echo_fingerprint',
    description: 'Create behavioral fingerprint of a system — track metrics and detect drift over time.',
    inputSchema: { type: 'object', properties: { systemId: { type: 'string' }, metrics: { type: 'object', description: '{metric_name: [values]}' } }, required: ['systemId', 'metrics'] },
  },
  {
    name: 'echo_forecast',
    description: 'Forecast future values using Holt exponential smoothing with confidence intervals.',
    inputSchema: { type: 'object', properties: { data: { type: 'array' }, periods: { type: 'number' } }, required: ['data'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PULSE — Social Intelligence Engine (9 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'pulse_engagement',
    description: 'Measure user engagement metrics — track events or get engagement report.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, action: { type: 'string', description: 'track or report' }, data: { type: 'object' } }, required: ['userId'] },
  },
  {
    name: 'pulse_behavior_track',
    description: 'Track user behavior patterns — sessions, page flows, bounce rate.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, events: { type: 'array', items: { type: 'object' }, description: '[{action, page, timestamp}]' } }, required: ['userId', 'events'] },
  },
  {
    name: 'pulse_cohort_analyze',
    description: 'Analyze user cohorts — segments, value distribution, top users.',
    inputSchema: { type: 'object', properties: { cohortId: { type: 'string' }, users: { type: 'array', items: { type: 'object' } }, metrics: { type: 'object' } }, required: ['cohortId', 'users'] },
  },
  {
    name: 'pulse_churn_predict',
    description: 'Predict user churn risk with risk factors and recommended actions.',
    inputSchema: { type: 'object', properties: { userId: { type: 'string' }, userMetrics: { type: 'object', description: '{last_login_days_ago, sessions_last_30, avg_session_min, support_tickets, plan_age_months, feature_adoption_pct}' } }, required: ['userId', 'userMetrics'] },
  },
  {
    name: 'pulse_satisfaction',
    description: 'Measure user satisfaction — NPS (0-10) or CSAT (1-5) scoring.',
    inputSchema: { type: 'object', properties: { responses: { type: 'array', description: 'Array of scores or {score} objects' }, type: { type: 'string', description: 'nps or csat' } }, required: ['responses'] },
  },
  {
    name: 'pulse_community',
    description: 'Analyze community/forum activity — contributors, engagement, health indicators.',
    inputSchema: { type: 'object', properties: { activities: { type: 'array', items: { type: 'object' }, description: '[{user, action, timestamp}]' } }, required: ['activities'] },
  },
  {
    name: 'pulse_collaboration',
    description: 'Track team collaboration patterns — connections, isolation, interaction types.',
    inputSchema: { type: 'object', properties: { teamId: { type: 'string' }, interactions: { type: 'array', items: { type: 'object' }, description: '[{from, to, type, timestamp}]' } }, required: ['teamId', 'interactions'] },
  },
  {
    name: 'pulse_influence_map',
    description: 'Map influence/stakeholder relationships — tiers, strategy, key influencers.',
    inputSchema: { type: 'object', properties: { people: { type: 'array', items: { type: 'object' }, description: '[{name, connections, role, influence_score?, reach?}]' } }, required: ['people'] },
  },
  {
    name: 'pulse_feedback_loop',
    description: 'Set up automated feedback collection — collect, analyze, or configure feedback loops.',
    inputSchema: { type: 'object', properties: { channel: { type: 'string' }, action: { type: 'string', description: 'collect, analyze, or configure' }, data: { type: 'object' } }, required: ['channel', 'action'] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SAGE — Linguistic Intelligence Engine (10 tools)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'sage_translate',
    description: 'Translate content between languages (en↔fr, en↔es, en↔de).',
    inputSchema: { type: 'object', properties: { text: { type: 'string' }, from: { type: 'string' }, to: { type: 'string' } }, required: ['text', 'from', 'to'] },
  },
  {
    name: 'sage_readability',
    description: 'Analyze text readability — Flesch-Kincaid grade, reading ease, estimated reading time.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' } }, required: ['text'] },
  },
  {
    name: 'sage_grammar',
    description: 'Check grammar and style — passive voice, repeated words, capitalization, common mistakes.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' } }, required: ['text'] },
  },
  {
    name: 'sage_localize',
    description: 'Localize content for a target locale — date/currency/number formatting.',
    inputSchema: { type: 'object', properties: { content: { type: 'string' }, locale: { type: 'string', description: 'e.g. en-US, fr-CA, de-DE, ja-JP' } }, required: ['content', 'locale'] },
  },
  {
    name: 'sage_summarize',
    description: 'Summarize long text intelligently by extracting key sentences.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' }, maxSentences: { type: 'number' } }, required: ['text'] },
  },
  {
    name: 'sage_keywords',
    description: 'Extract keywords and key phrases with density and bigram analysis.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' }, count: { type: 'number' } }, required: ['text'] },
  },
  {
    name: 'sage_tone_match',
    description: 'Match content to a target tone/voice (professional, casual, friendly, technical, urgent).',
    inputSchema: { type: 'object', properties: { text: { type: 'string' }, targetTone: { type: 'string' } }, required: ['text', 'targetTone'] },
  },
  {
    name: 'sage_simplify',
    description: 'Simplify complex text for accessibility — replace jargon, break sentences.',
    inputSchema: { type: 'object', properties: { text: { type: 'string' } }, required: ['text'] },
  },
  {
    name: 'sage_glossary',
    description: 'Build/manage project glossary — add, lookup, list, or export terms.',
    inputSchema: { type: 'object', properties: { project: { type: 'string' }, action: { type: 'string', description: 'add, lookup, list, or export' }, data: { type: 'object' } }, required: ['project', 'action'] },
  },
  {
    name: 'sage_compare',
    description: 'Compare two texts for similarity/differences — Jaccard similarity, unique words.',
    inputSchema: { type: 'object', properties: { textA: { type: 'string' }, textB: { type: 'string' } }, required: ['textA', 'textB'] },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // v8.0.0 — Alfred Autopilot (Full Human-Centric Agentic Browser)
  // ═══════════════════════════════════════════════════════════════════════════
  {
    name: 'autopilot_start',
    description:
      'Start a live browser session that you control step-by-step. The user watches your actions ' +
      'in real-time via a live stream panel. Use this when a task requires multi-step web interaction — ' +
      'e.g., filling forms across pages, navigating dashboards, comparing products, or completing workflows.\n\n' +
      'Options:\n' +
      '• maxSteps (default 50, max 200) — auto-stop after N actions\n' +
      '• maxDuration (default 600000ms = 10 min) — max session duration\n' +
      '• viewport: "desktop"|"laptop"|"tablet"|"mobile"|"4k" — initial viewport size\n' +
      '• humanApproval (default false) — when true, each action pauses for user approval\n' +
      '• persistCookies (default false) — save/restore cookies between sessions\n' +
      '• allowedDomains — geo-fence: restrict navigation to specific domains\n' +
      '• sensitiveFieldMasking (default true) — auto-mask passwords/CC fields in screenshots\n' +
      '• retentionPolicy: "session"|"24h"|"permanent" — screenshot data retention\n' +
      '• smartWait (default true) — auto-wait for spinners/loading before acting\n' +
      '• highContrast (default false) — enable high-contrast mode in the browser\n\n' +
      'After starting, use autopilot_action to interact and autopilot_observe to see the page. ' +
      'ALWAYS describe what you\'re about to do before each action.',
    inputSchema: {
      type: 'object',
      properties: {
        task: {
          type: 'string',
          description: 'What you plan to accomplish in this browser session (shown to the user)',
        },
        url: {
          type: 'string',
          description: 'Optional initial URL to navigate to immediately',
        },
        maxSteps: { type: 'number', description: 'Max actions before auto-stop (default: 50, max: 200)' },
        maxDuration: { type: 'number', description: 'Max session duration in ms (default: 600000 = 10 min)' },
        viewport: {
          type: 'string',
          enum: ['desktop', 'laptop', 'tablet', 'tablet_land', 'mobile', 'mobile_land', '4k'],
          description: 'Initial viewport preset (default: desktop)',
        },
        humanApproval: { type: 'boolean', description: 'If true, each action pauses for user approval' },
        persistCookies: { type: 'boolean', description: 'If true, cookies are saved/restored between sessions' },
        allowedDomains: {
          type: 'array',
          items: { type: 'string' },
          description: 'Geo-fence: restrict navigation to these domains only (empty = allow all)',
        },
        sensitiveFieldMasking: { type: 'boolean', description: 'Auto-mask password/CC fields in screenshots (default: true)' },
        retentionPolicy: {
          type: 'string',
          enum: ['session', '24h', 'permanent'],
          description: 'Data retention policy for screenshots (default: session)',
        },
        smartWait: { type: 'boolean', description: 'Auto-wait for loading spinners before acting (default: true)' },
        highContrast: { type: 'boolean', description: 'Enable high-contrast CSS mode in browser (default: false)' },
      },
      required: ['task'],
    },
  },
  {
    name: 'autopilot_action',
    description:
      'Execute an action in the active Autopilot browser session. Available actions:\n' +
      '• navigate — Go to a URL: {action:"navigate", url:"https://..."}\n' +
      '• click — Click an element: {action:"click", selector:"#btn", description:"Submit button"}\n' +
      '• type — Type text into a field: {action:"type", selector:"input[name=q]", text:"search query"}\n' +
      '• press — Press a keyboard key: {action:"press", key:"Enter"}\n' +
      '• scroll — Scroll the page: {action:"scroll", direction:"down", amount:400}\n' +
      '• select — Select dropdown option: {action:"select", selector:"select#country", value:"CA"}\n' +
      '• hover — Hover over element: {action:"hover", selector:".menu-item"}\n' +
      '• wait — Wait for element: {action:"wait", selector:".results", timeout:10000}\n' +
      '• script — Run JavaScript: {action:"script", script:"document.title"}\n' +
      '• switch_tab — Switch between tabs: {action:"switch_tab", tabIndex:1}\n' +
      '• set_viewport — Change viewport: {action:"set_viewport", preset:"mobile"}\n' +
      '• upload_file — Upload to file input: {action:"upload_file", selector:"input[type=file]", filePath:"/path/to/file"}\n' +
      '• save_cookies — Persist cookies to disk: {action:"save_cookies"}\n' +
      '• load_cookies — Restore saved cookies: {action:"load_cookies"}\n' +
      '• undo — Undo last action (go back): {action:"undo"}\n' +
      '• iframe_action — Interact inside iframe: {action:"iframe_action", iframeSelector:"iframe#main", iframeAction:"click", targetSelector:"button.submit"}\n' +
      '• drag_and_drop — Drag element to target: {action:"drag_and_drop", sourceSelector:".drag-item", targetSelector:".drop-zone"}\n' +
      '• right_click — Right-click on element: {action:"right_click", selector:".context-area"}\n' +
      '• touch — Mobile touch events: {action:"touch", touchAction:"tap", touchOptions:{selector:"button"}}\n' +
      '• generate_pdf — Save page as PDF: {action:"generate_pdf", pdfOptions:{format:"A4"}}\n' +
      '• solve_captcha — Solve CAPTCHA via 2Captcha: {action:"solve_captcha", captchaType:"recaptcha"}\n' +
      '• set_geolocation — Spoof GPS location: {action:"set_geolocation", location:"montreal"}\n' +
      '• get_dialog_log — Get history of browser dialogs: {action:"get_dialog_log"}\n\n' +
      'Human-like features (H20-H36): Bézier mouse movement, per-keystroke typing with typos, ' +
      'smooth inertia scrolling, stealth anti-detection patches, UA rotation, random delays, ' +
      'dialog auto-handling, iframe traversal, drag-and-drop, right-click, touch simulation, ' +
      'geolocation spoofing, localStorage persistence, PDF generation, video recording, CAPTCHA solving. ' +
      'ALWAYS explain what you\'re doing and why before each action.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['navigate', 'click', 'type', 'press', 'scroll', 'select', 'hover', 'wait', 'script',
                 'switch_tab', 'set_viewport', 'upload_file', 'save_cookies', 'load_cookies', 'undo',
                 'iframe_action', 'drag_and_drop', 'right_click', 'touch', 'generate_pdf',
                 'solve_captcha', 'set_geolocation', 'get_dialog_log'],
          description: 'The action to perform',
        },
        url: { type: 'string', description: 'For navigate: the URL to go to' },
        selector: { type: 'string', description: 'CSS selector for click/type/select/hover/wait/upload_file/right_click' },
        text: { type: 'string', description: 'For type: text to enter' },
        value: { type: 'string', description: 'For select: option value. For iframe_action type: text to enter' },
        key: { type: 'string', description: 'For press: key name (e.g., "Enter", "Tab", "Escape")' },
        direction: { type: 'string', description: 'For scroll: "up", "down", "left", "right"' },
        amount: { type: 'number', description: 'For scroll: pixels to scroll (default: 400)' },
        script: { type: 'string', description: 'For script: JavaScript to execute' },
        timeout: { type: 'number', description: 'For wait: milliseconds to wait (default: 10000)' },
        description: { type: 'string', description: 'Brief description of what this action does (shown to user)' },
        tabIndex: { type: 'number', description: 'For switch_tab: zero-based tab index' },
        preset: {
          type: 'string',
          enum: ['desktop', 'laptop', 'tablet', 'tablet_land', 'mobile', 'mobile_land', '4k'],
          description: 'For set_viewport: viewport preset name',
        },
        filePath: { type: 'string', description: 'For upload_file: path to file to upload' },
        iframeSelector: { type: 'string', description: 'For iframe_action: CSS selector of the iframe element' },
        iframeAction: { type: 'string', enum: ['click', 'type', 'text', 'count'], description: 'For iframe_action: what to do inside the iframe' },
        targetSelector: { type: 'string', description: 'For iframe_action/drag_and_drop: target element selector' },
        sourceSelector: { type: 'string', description: 'For drag_and_drop: source element to drag (or use selector)' },
        touchAction: { type: 'string', enum: ['tap', 'swipe', 'long_press'], description: 'For touch: type of touch event' },
        touchOptions: { type: 'object', description: 'For touch: options like {selector, x, y, startX, startY, endX, endY, duration}' },
        captchaType: { type: 'string', enum: ['recaptcha', 'hcaptcha'], description: 'For solve_captcha: CAPTCHA type' },
        location: { description: 'For set_geolocation: preset name (e.g. "montreal") or {latitude, longitude, accuracy}' },
        pdfOptions: { type: 'object', description: 'For generate_pdf: {format, landscape, printBackground, margin, scale}' },
      },
      required: ['action'],
    },
  },
  {
    name: 'autopilot_observe',
    description:
      'Observe the current state of the Autopilot browser session. Returns the accessibility tree ' +
      '(a text representation of all visible elements with selectors), current URL/title, ' +
      'remaining steps/time guardrails, cursor position, open tabs, pause/approval status, ' +
      'confidence score, sentiment (progressing/stuck/failing), frustration level, annotations, ' +
      'celebration status, undo availability, batch progress, and spectator count. ' +
      'Use this to understand what\'s on the page before deciding the next action.',
    inputSchema: {
      type: 'object',
      properties: {},
    },
  },
  {
    name: 'autopilot_stop',
    description:
      'Stop the active Autopilot browser session. Call this when the task is complete ' +
      'or if the user asks you to stop browsing. Returns session summary including total steps, ' +
      'duration, action history, and any downloaded files.',
    inputSchema: {
      type: 'object',
      properties: {
        reason: { type: 'string', description: 'Why the session is ending (shown to user)' },
      },
    },
  },

  // ── v8.0 Human-Centric Add-on Tools ────────────────────────────────────
  {
    name: 'autopilot_templates',
    description:
      'Manage task templates for Autopilot. Templates save a session\'s action history so it can ' +
      'be replayed later. Actions: list (no args), get (name), save (name — requires active session), delete (name).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['list', 'get', 'save', 'delete'],
          description: 'Template operation to perform',
        },
        name: { type: 'string', description: 'Template name (for get/save/delete)' },
      },
      required: ['operation'],
    },
  },
  {
    name: 'autopilot_batch',
    description:
      'Manage batch operations queue for Autopilot. Queue multiple tasks to be executed sequentially. ' +
      'Actions: set (provide tasks array), status (get queue progress), next (advance to next item).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['set', 'status', 'next'],
          description: 'Batch operation',
        },
        tasks: {
          type: 'array',
          items: { type: 'object' },
          description: 'For set: array of task objects with {task, url?} to queue',
        },
      },
      required: ['operation'],
    },
  },
  {
    name: 'autopilot_schedule',
    description:
      'Manage scheduled autopilot runs. Create schedules to run tasks at specific times. ' +
      'Actions: create (provide task/cron/opts), list, delete (id).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['create', 'list', 'delete'],
          description: 'Schedule operation',
        },
        task: { type: 'string', description: 'For create: task description' },
        cron: { type: 'string', description: 'For create: cron expression (e.g., "0 9 * * 1" = every Monday 9am)' },
        url: { type: 'string', description: 'For create: initial URL' },
        id: { type: 'string', description: 'For delete: schedule ID' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // REMOTE SERVER — SSH / SCP / SFTP / RSYNC
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'ssh_exec',
    description:
      'Execute a command on a remote server via SSH. Supports key-based and password auth. ' +
      'Perfect for backups, migrations, server management, or running scripts on external servers. ' +
      'Returns stdout, stderr, and exit code.',
    inputSchema: {
      type: 'object',
      properties: {
        host: { type: 'string', description: 'Remote hostname or IP' },
        port: { type: 'number', description: 'SSH port (default: 22)' },
        username: { type: 'string', description: 'SSH username' },
        password: { type: 'string', description: 'Password (if not using key)' },
        privateKey: { type: 'string', description: 'Path to private key file (e.g. ~/.ssh/id_rsa)' },
        command: { type: 'string', description: 'Shell command to execute on the remote server' },
        timeout: { type: 'number', description: 'Timeout in seconds (default: 30)' },
      },
      required: ['host', 'username', 'command'],
    },
  },
  {
    name: 'sftp_transfer',
    description:
      'Transfer files between local and remote servers via SFTP. ' +
      'Operations: upload (local→remote), download (remote→local), list (remote dir), delete (remote file). ' +
      'Supports key-based and password auth.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['upload', 'download', 'list', 'delete'], description: 'Transfer operation' },
        host: { type: 'string', description: 'Remote hostname or IP' },
        port: { type: 'number', description: 'SSH port (default: 22)' },
        username: { type: 'string', description: 'SSH username' },
        password: { type: 'string', description: 'Password (if not using key)' },
        privateKey: { type: 'string', description: 'Path to private key file' },
        localPath: { type: 'string', description: 'Local file/dir path' },
        remotePath: { type: 'string', description: 'Remote file/dir path' },
      },
      required: ['operation', 'host', 'username'],
    },
  },
  {
    name: 'rsync_sync',
    description:
      'Synchronize files/directories between local and remote servers using rsync. ' +
      'Supports incremental transfers, compression, exclude patterns, and dry-run mode. ' +
      'Ideal for backups, migrations, and keeping servers in sync.',
    inputSchema: {
      type: 'object',
      properties: {
        source: { type: 'string', description: 'Source path (local path or user@host:/path)' },
        destination: { type: 'string', description: 'Destination path (local path or user@host:/path)' },
        exclude: { type: 'array', items: { type: 'string' }, description: 'Patterns to exclude' },
        delete: { type: 'boolean', description: 'Delete files in dest that are not in source' },
        dryRun: { type: 'boolean', description: 'Show what would be transferred without doing it' },
        compress: { type: 'boolean', description: 'Compress during transfer (default: true)' },
        sshKey: { type: 'string', description: 'Path to SSH private key' },
      },
      required: ['source', 'destination'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DOCKER CONTAINER MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'docker_manage',
    description:
      'Manage Docker containers, images, and volumes. ' +
      'Operations: ps (list containers), run (start container), stop, restart, rm (remove), ' +
      'logs (container logs), exec (run command in container), build (build image), ' +
      'images (list images), pull (pull image), inspect, stats. ' +
      'Goes beyond setup_docker which only generates Dockerfiles — this manages running containers.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['ps', 'run', 'stop', 'restart', 'rm', 'logs', 'exec', 'build', 'images', 'pull', 'inspect', 'stats'],
          description: 'Docker operation',
        },
        container: { type: 'string', description: 'Container name or ID (for stop/restart/rm/logs/exec/inspect)' },
        image: { type: 'string', description: 'Image name (for run/pull/build)' },
        command: { type: 'string', description: 'Command to exec in container, or build context path' },
        ports: { type: 'array', items: { type: 'string' }, description: 'Port mappings for run (e.g. ["8080:80"])' },
        env: { type: 'object', description: 'Environment variables for run' },
        volumes: { type: 'array', items: { type: 'string' }, description: 'Volume mounts for run' },
        tail: { type: 'number', description: 'Number of log lines to return (default: 100)' },
        all: { type: 'boolean', description: 'For ps: include stopped containers' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // REDIS MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'redis_manage',
    description:
      'Manage Redis server — get/set keys, list keys by pattern, delete keys, flush databases, ' +
      'view server info/stats, monitor commands, check memory usage. ' +
      'Operations: get, set, del, keys, info, dbsize, flushdb, flushall, ttl, expire, type, memory.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['get', 'set', 'del', 'keys', 'info', 'dbsize', 'flushdb', 'flushall', 'ttl', 'expire', 'type', 'memory'],
          description: 'Redis operation',
        },
        key: { type: 'string', description: 'Redis key' },
        value: { type: 'string', description: 'Value to set' },
        pattern: { type: 'string', description: 'For keys: glob pattern (default: *)' },
        seconds: { type: 'number', description: 'For expire/set: TTL in seconds' },
        db: { type: 'number', description: 'Database number (default: 0)' },
        host: { type: 'string', description: 'Redis host (default: 127.0.0.1)' },
        port: { type: 'number', description: 'Redis port (default: 6379)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // POSTGRESQL MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'pg_manage',
    description:
      'Manage PostgreSQL databases. Operations: list_databases, create_database, drop_database, ' +
      'query (run SQL), schema (show tables/columns), stats (connection count, size), backup (pg_dump), restore. ' +
      'Complements MySQL tools by adding PostgreSQL support.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['list_databases', 'create_database', 'drop_database', 'query', 'schema', 'stats', 'backup', 'restore'],
          description: 'PostgreSQL operation',
        },
        database: { type: 'string', description: 'Database name' },
        sql: { type: 'string', description: 'SQL query to execute' },
        table: { type: 'string', description: 'Table name (for schema)' },
        host: { type: 'string', description: 'PostgreSQL host (default: localhost)' },
        port: { type: 'number', description: 'PostgreSQL port (default: 5432)' },
        username: { type: 'string', description: 'PostgreSQL user' },
        password: { type: 'string', description: 'PostgreSQL password' },
        outputPath: { type: 'string', description: 'For backup: output file path' },
        inputPath: { type: 'string', description: 'For restore: input dump file path' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PROCESS & SERVICE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'process_manage',
    description:
      'Manage system processes — list running processes, kill processes, view resource usage. ' +
      'Operations: list (ps aux), find (search by name), kill (by PID), top (resource summary), tree (process tree).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['list', 'find', 'kill', 'top', 'tree'],
          description: 'Process operation',
        },
        name: { type: 'string', description: 'For find: process name pattern' },
        pid: { type: 'number', description: 'For kill: process ID' },
        signal: { type: 'string', description: 'For kill: signal (default: TERM). Options: TERM, KILL, HUP, INT' },
        sortBy: { type: 'string', enum: ['cpu', 'mem', 'pid', 'time'], description: 'For list/top: sort field' },
        limit: { type: 'number', description: 'Max results to return (default: 50)' },
      },
      required: ['operation'],
    },
  },
  {
    name: 'service_manage',
    description:
      'Manage system services via systemctl. Operations: status (check service), ' +
      'start, stop, restart, enable (auto-start), disable, list (all services), logs (journalctl). ' +
      'Common services: nginx, apache2, mysql, postgresql, redis, docker, php-fpm.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['status', 'start', 'stop', 'restart', 'enable', 'disable', 'list', 'logs'],
          description: 'Service operation',
        },
        service: { type: 'string', description: 'Service name (e.g. nginx, mysql, redis)' },
        lines: { type: 'number', description: 'For logs: number of log lines (default: 50)' },
        filter: { type: 'string', description: 'For list: filter pattern' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // NETWORK DIAGNOSTICS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'network_diag',
    description:
      'Network diagnostic tools — ping, traceroute, dig (DNS lookup), nslookup, whois, curl test, ' +
      'port check, MTR, netstat/ss. Essential for troubleshooting connectivity, DNS, and routing issues.',
    inputSchema: {
      type: 'object',
      properties: {
        tool: {
          type: 'string',
          enum: ['ping', 'traceroute', 'dig', 'nslookup', 'whois', 'curl', 'port_check', 'mtr', 'netstat'],
          description: 'Diagnostic tool to use',
        },
        target: { type: 'string', description: 'Hostname, IP, or domain to test' },
        port: { type: 'number', description: 'For port_check: port number to test' },
        count: { type: 'number', description: 'For ping: number of pings (default: 4)' },
        recordType: { type: 'string', description: 'For dig: DNS record type (A, AAAA, CNAME, MX, TXT, NS, SOA)' },
        timeout: { type: 'number', description: 'Timeout in seconds (default: 10)' },
      },
      required: ['tool', 'target'],
    },
  },
  {
    name: 'dns_propagation',
    description:
      'Check DNS propagation status across global DNS servers. Tests if your DNS changes ' +
      'have propagated to major resolvers worldwide (Google, Cloudflare, OpenDNS, Quad9, etc.). ' +
      'Returns results from 10+ geographic locations.',
    inputSchema: {
      type: 'object',
      properties: {
        domain: { type: 'string', description: 'Domain name to check' },
        recordType: { type: 'string', enum: ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'], description: 'DNS record type (default: A)' },
        expected: { type: 'string', description: 'Expected value to check for' },
      },
      required: ['domain'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // FIREWALL & SECURITY MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'firewall_manage',
    description:
      'Manage firewall rules (iptables/ufw/nftables) and fail2ban. ' +
      'Operations: status, list_rules, add_rule, remove_rule, allow_port, deny_port, ' +
      'allow_ip, deny_ip, fail2ban_status, fail2ban_unban, reset.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['status', 'list_rules', 'add_rule', 'remove_rule', 'allow_port', 'deny_port', 'allow_ip', 'deny_ip', 'fail2ban_status', 'fail2ban_unban', 'reset'],
          description: 'Firewall operation',
        },
        port: { type: 'number', description: 'Port number' },
        protocol: { type: 'string', enum: ['tcp', 'udp', 'both'], description: 'Protocol (default: tcp)' },
        ip: { type: 'string', description: 'IP address to allow/deny/unban' },
        direction: { type: 'string', enum: ['in', 'out'], description: 'Traffic direction (default: in)' },
        jail: { type: 'string', description: 'Fail2ban jail name' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // LOG STREAMING
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'tail_log',
    description:
      'Stream/tail log files in real-time. Supports Apache access/error logs, application logs, ' +
      'syslog, mail log, and any custom log file. Can grep-filter log lines and return recent entries.',
    inputSchema: {
      type: 'object',
      properties: {
        path: { type: 'string', description: 'Log file path (or preset: access, error, syslog, mail, auth)' },
        lines: { type: 'number', description: 'Number of lines to return (default: 100)' },
        grep: { type: 'string', description: 'Filter lines matching this pattern' },
        since: { type: 'string', description: 'Show logs since timestamp (e.g. "2024-01-01", "1 hour ago")' },
        follow: { type: 'boolean', description: 'Continuously follow new lines (returns after timeout)' },
      },
      required: ['path'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // GIT ADVANCED OPERATIONS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'git_advanced',
    description:
      'Advanced Git operations: stash (save/pop/list/drop), tag (create/list/delete), ' +
      'cherry_pick, rebase (interactive/onto), merge, resolve_conflicts, submodule (add/update/status), ' +
      'hooks (list/install/remove), blame, bisect, reflog.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['stash', 'stash_pop', 'stash_list', 'stash_drop', 'tag_create', 'tag_list', 'tag_delete', 'cherry_pick', 'rebase', 'merge', 'resolve_conflicts', 'submodule_add', 'submodule_update', 'submodule_status', 'hooks_list', 'hooks_install', 'blame', 'bisect', 'reflog'],
          description: 'Git operation',
        },
        path: { type: 'string', description: 'Repository or file path' },
        ref: { type: 'string', description: 'Commit hash, branch name, or tag name' },
        message: { type: 'string', description: 'For stash/tag_create: descriptive message' },
        onto: { type: 'string', description: 'For rebase: target branch' },
        url: { type: 'string', description: 'For submodule_add: submodule repository URL' },
        hookType: { type: 'string', description: 'For hooks: pre-commit, pre-push, commit-msg, etc.' },
        hookScript: { type: 'string', description: 'For hooks_install: hook script content' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PACKAGE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'package_manage',
    description:
      'Manage packages across multiple package managers: npm, pip, composer, apt, gem, cargo. ' +
      'Operations: install, uninstall, update, list, outdated, search, audit (security), init.',
    inputSchema: {
      type: 'object',
      properties: {
        manager: {
          type: 'string',
          enum: ['npm', 'pip', 'composer', 'apt', 'gem', 'cargo', 'yarn', 'pnpm'],
          description: 'Package manager to use',
        },
        operation: {
          type: 'string',
          enum: ['install', 'uninstall', 'update', 'list', 'outdated', 'search', 'audit', 'init'],
          description: 'Package operation',
        },
        packages: { type: 'array', items: { type: 'string' }, description: 'Package names' },
        global: { type: 'boolean', description: 'Install globally (for npm/pip)' },
        dev: { type: 'boolean', description: 'Install as dev dependency (for npm/composer)' },
        path: { type: 'string', description: 'Project directory (default: current)' },
      },
      required: ['manager', 'operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ARCHIVE & COMPRESSION
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'archive_manage',
    description:
      'Create and extract archives: tar, tar.gz, zip, gzip, bzip2, 7z. ' +
      'Operations: create (pack files), extract (unpack), list (show contents).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['create', 'extract', 'list'], description: 'Archive operation' },
        format: { type: 'string', enum: ['tar', 'tar.gz', 'tar.bz2', 'zip', 'gzip', '7z'], description: 'Archive format' },
        archivePath: { type: 'string', description: 'Path to the archive file' },
        files: { type: 'array', items: { type: 'string' }, description: 'For create: files/dirs to include' },
        outputDir: { type: 'string', description: 'For extract: output directory' },
        exclude: { type: 'array', items: { type: 'string' }, description: 'Patterns to exclude' },
      },
      required: ['operation', 'archivePath'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // FILE PERMISSIONS & OWNERSHIP
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'permission_manage',
    description:
      'Manage file/directory permissions and ownership. chmod, chown, and ACL operations. ' +
      'Supports numeric (755) and symbolic (u+rwx) permissions. Recursive option available.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['chmod', 'chown', 'stat', 'find_writable', 'fix_permissions'], description: 'Permission operation' },
        path: { type: 'string', description: 'File or directory path' },
        permissions: { type: 'string', description: 'For chmod: mode (e.g. "755", "u+rwx,go+rx")' },
        owner: { type: 'string', description: 'For chown: owner[:group] (e.g. "www-data:www-data")' },
        recursive: { type: 'boolean', description: 'Apply recursively to directories' },
      },
      required: ['operation', 'path'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CERTIFICATE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'cert_manage',
    description:
      'Advanced SSL/TLS certificate management beyond Let\'s Encrypt auto-renew. ' +
      'Operations: inspect (view cert details), check_expiry (check all domains), ' +
      'install_custom (upload cert+key), generate_csr, generate_self_signed, test_ssl (SSL Labs-style).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['inspect', 'check_expiry', 'install_custom', 'generate_csr', 'generate_self_signed', 'test_ssl'],
          description: 'Certificate operation',
        },
        domain: { type: 'string', description: 'Domain name' },
        certPath: { type: 'string', description: 'Path to certificate file' },
        keyPath: { type: 'string', description: 'Path to private key file' },
        caPath: { type: 'string', description: 'Path to CA bundle file' },
        days: { type: 'number', description: 'For check_expiry: warn if expiring within N days (default: 30)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CODE BUILD TOOLS — Minify / Format / Lint
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'code_transform',
    description:
      'Transform code: minify (JS/CSS/HTML), format (Prettier), lint (ESLint), ' +
      'beautify (expand minified code), bundle analysis, tree-shake check.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['minify', 'format', 'lint', 'beautify', 'bundle_analyze'],
          description: 'Transform operation',
        },
        path: { type: 'string', description: 'File or directory path' },
        language: { type: 'string', enum: ['js', 'css', 'html', 'json', 'yaml', 'markdown', 'typescript', 'php', 'python'], description: 'File language' },
        outputPath: { type: 'string', description: 'Output file path (default: overwrite)' },
        config: { type: 'object', description: 'Formatter/linter config options' },
      },
      required: ['operation', 'path'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DATABASE MIGRATION
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'db_migrate',
    description:
      'Database migration management — create versioned migration files, run pending migrations, ' +
      'rollback, check status, seed data. Supports MySQL and PostgreSQL. ' +
      'Stores migrations in a configurable directory with timestamp naming.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['create', 'run', 'rollback', 'status', 'seed', 'reset', 'diff'],
          description: 'Migration operation',
        },
        name: { type: 'string', description: 'For create: migration name (e.g. "add_users_table")' },
        database: { type: 'string', description: 'Database name' },
        engine: { type: 'string', enum: ['mysql', 'postgresql'], description: 'Database engine (default: mysql)' },
        steps: { type: 'number', description: 'For rollback: number of migrations to undo (default: 1)' },
        migrationsDir: { type: 'string', description: 'Directory for migration files' },
        sql: { type: 'string', description: 'For create: migration SQL (up)' },
        rollbackSql: { type: 'string', description: 'For create: rollback SQL (down)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CDN & CACHING MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'cache_manage',
    description:
      'Manage caching systems: OPcache (PHP), page cache, browser cache headers, ' +
      'Varnish, Redis cache, CDN purge. Operations: status, flush, configure, stats.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['status', 'flush', 'configure', 'stats', 'purge_cdn', 'opcache_reset', 'browser_headers'],
          description: 'Cache operation',
        },
        system: { type: 'string', enum: ['opcache', 'redis', 'varnish', 'page_cache', 'cdn', 'all'], description: 'Caching system' },
        urls: { type: 'array', items: { type: 'string' }, description: 'For purge_cdn: URLs to purge' },
        ttl: { type: 'number', description: 'Cache TTL in seconds' },
        path: { type: 'string', description: 'File path for browser cache header config' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PERFORMANCE PROFILING
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'performance_profile',
    description:
      'Profile website performance: Lighthouse audit, PageSpeed Insights, Core Web Vitals, ' +
      'load time breakdown, resource waterfall, image optimization check, ' +
      'JavaScript bundle size, CSS unused detection.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL to profile' },
        type: {
          type: 'string',
          enum: ['lighthouse', 'pagespeed', 'web_vitals', 'waterfall', 'bundle_size', 'unused_css', 'image_audit', 'full'],
          description: 'Profile type (default: full)',
        },
        device: { type: 'string', enum: ['mobile', 'desktop'], description: 'Device simulation (default: mobile)' },
        runs: { type: 'number', description: 'Number of test runs for averaging (default: 1)' },
      },
      required: ['url'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // PDF MANIPULATION
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'pdf_manipulate',
    description:
      'Manipulate existing PDF files: merge multiple PDFs, split pages, extract pages, ' +
      'add watermark, compress/optimize, convert (to/from images), add page numbers, rotate.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['merge', 'split', 'extract_pages', 'watermark', 'compress', 'to_images', 'from_images', 'add_page_numbers', 'rotate', 'info'],
          description: 'PDF operation',
        },
        input: { type: 'string', description: 'Input PDF path (or array of paths for merge)' },
        inputs: { type: 'array', items: { type: 'string' }, description: 'For merge: array of PDF paths' },
        output: { type: 'string', description: 'Output file/dir path' },
        pages: { type: 'string', description: 'Page range (e.g. "1-5", "1,3,5", "2-")' },
        watermarkText: { type: 'string', description: 'For watermark: text to overlay' },
        rotation: { type: 'number', description: 'For rotate: degrees (90, 180, 270)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // WEBSOCKET & API TESTING
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'api_test',
    description:
      'Test APIs — send HTTP requests (like Postman), test WebSocket connections, ' +
      'run GraphQL queries, validate REST endpoints, test webhooks, measure response times.',
    inputSchema: {
      type: 'object',
      properties: {
        type: {
          type: 'string',
          enum: ['http', 'websocket', 'graphql', 'grpc', 'webhook_test'],
          description: 'API test type',
        },
        url: { type: 'string', description: 'Endpoint URL' },
        method: { type: 'string', enum: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], description: 'HTTP method (default: GET)' },
        headers: { type: 'object', description: 'Request headers' },
        body: { type: 'string', description: 'Request body (JSON string)' },
        query: { type: 'string', description: 'For GraphQL: query string' },
        variables: { type: 'object', description: 'For GraphQL: variables' },
        wsMessage: { type: 'string', description: 'For WebSocket: message to send' },
        timeout: { type: 'number', description: 'Timeout in ms (default: 30000)' },
        auth: { type: 'object', description: 'Auth config: {type: "bearer"|"basic", token/username/password}' },
      },
      required: ['url'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // QUEUE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'queue_manage',
    description:
      'Manage message/job queues: BullMQ (Redis-backed), RabbitMQ, or simple Redis lists. ' +
      'Operations: add (enqueue job), list (pending jobs), stats (queue metrics), ' +
      'pause, resume, flush (clear), retry_failed, remove.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['add', 'list', 'stats', 'pause', 'resume', 'flush', 'retry_failed', 'remove'],
          description: 'Queue operation',
        },
        queue: { type: 'string', description: 'Queue name' },
        backend: { type: 'string', enum: ['bullmq', 'rabbitmq', 'redis_list'], description: 'Queue backend (default: bullmq)' },
        data: { type: 'object', description: 'For add: job data payload' },
        jobId: { type: 'string', description: 'For remove: job ID' },
        limit: { type: 'number', description: 'For list: max results (default: 20)' },
      },
      required: ['operation', 'queue'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // MONGODB MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'mongo_manage',
    description:
      'Manage MongoDB databases and collections. Operations: list_databases, list_collections, ' +
      'find (query documents), insert, update, delete, aggregate, count, create_index, stats, ' +
      'backup (mongodump), restore (mongorestore).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['list_databases', 'list_collections', 'find', 'insert', 'update', 'delete', 'aggregate', 'count', 'create_index', 'stats', 'backup', 'restore'],
          description: 'MongoDB operation',
        },
        database: { type: 'string', description: 'Database name' },
        collection: { type: 'string', description: 'Collection name' },
        filter: { type: 'object', description: 'Query filter (MongoDB query syntax)' },
        document: { type: 'object', description: 'For insert/update: document data' },
        pipeline: { type: 'array', description: 'For aggregate: aggregation pipeline stages' },
        index: { type: 'object', description: 'For create_index: index specification' },
        connectionString: { type: 'string', description: 'MongoDB connection string (default: mongodb://localhost:27017)' },
        limit: { type: 'number', description: 'For find: max results (default: 20)' },
        outputPath: { type: 'string', description: 'For backup: output directory' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // KUBERNETES MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'k8s_manage',
    description:
      'Manage Kubernetes clusters: pods, deployments, services, configmaps, secrets, logs, exec. ' +
      'Wraps kubectl commands for cloud-native infrastructure management.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['get_pods', 'get_deployments', 'get_services', 'get_nodes', 'logs', 'exec', 'apply', 'delete', 'scale', 'rollout_status', 'rollout_restart', 'describe', 'top'],
          description: 'Kubernetes operation',
        },
        resource: { type: 'string', description: 'Resource name' },
        namespace: { type: 'string', description: 'Kubernetes namespace (default: default)' },
        command: { type: 'string', description: 'For exec: command to run in pod' },
        manifest: { type: 'string', description: 'For apply: YAML manifest content' },
        replicas: { type: 'number', description: 'For scale: target replica count' },
        container: { type: 'string', description: 'Container name (for multi-container pods)' },
        tail: { type: 'number', description: 'For logs: number of lines (default: 100)' },
        context: { type: 'string', description: 'Kubernetes context name' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // FEATURE FLAGS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'feature_flags',
    description:
      'Manage feature flags for progressive rollouts, A/B testing, and kill switches. ' +
      'Operations: set (create/update flag), get (check flag), list, delete, evaluate (check for user/segment).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['set', 'get', 'list', 'delete', 'evaluate'],
          description: 'Feature flag operation',
        },
        flag: { type: 'string', description: 'Flag name (e.g. "new_checkout_flow")' },
        enabled: { type: 'boolean', description: 'For set: whether flag is enabled' },
        percentage: { type: 'number', description: 'For set: rollout percentage (0-100)' },
        segments: { type: 'array', items: { type: 'string' }, description: 'User segments for targeting' },
        userId: { type: 'string', description: 'For evaluate: user ID to check' },
        metadata: { type: 'object', description: 'Additional flag metadata' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // EMAIL DIAGNOSTICS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'email_diag',
    description:
      'Email diagnostics and testing: SMTP connection test, email deliverability check, ' +
      'SPF/DKIM/DMARC validation, blacklist check, email preview (HTML rendering), ' +
      'inbox placement prediction.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['smtp_test', 'deliverability', 'spf_check', 'dkim_check', 'dmarc_check', 'blacklist_check', 'preview', 'headers_analyze'],
          description: 'Email diagnostic operation',
        },
        domain: { type: 'string', description: 'Domain to check' },
        smtpHost: { type: 'string', description: 'SMTP server hostname' },
        smtpPort: { type: 'number', description: 'SMTP port (default: 587)' },
        email: { type: 'string', description: 'Email address to test' },
        html: { type: 'string', description: 'For preview: HTML email content' },
        headers: { type: 'string', description: 'For headers_analyze: raw email headers' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SECURITY HEADERS & CSP
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'security_headers',
    description:
      'Manage security headers: Content-Security-Policy builder, HSTS, X-Frame-Options, ' +
      'X-Content-Type-Options, Referrer-Policy, Permissions-Policy. ' +
      'Operations: audit (check current), generate (create .htaccess/ nginx config), test.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['audit', 'generate', 'test'], description: 'Operation' },
        url: { type: 'string', description: 'URL to audit/test' },
        csp: { type: 'object', description: 'CSP directives (e.g. {default-src: ["self"], script-src: ["self","cdn.example.com"]})' },
        hsts: { type: 'object', description: 'HSTS config: {maxAge, includeSubdomains, preload}' },
        outputFormat: { type: 'string', enum: ['htaccess', 'nginx', 'meta_tags', 'json'], description: 'Output format for generate' },
        outputPath: { type: 'string', description: 'Path to write config' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CRON TOOLS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'cron_tools',
    description:
      'Cron expression utilities: validate (check if expression is valid), ' +
      'explain (human-readable description), next_runs (calculate next N execution times), ' +
      'builder (construct expression from natural language).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['validate', 'explain', 'next_runs', 'build'], description: 'Cron tool operation' },
        expression: { type: 'string', description: 'Cron expression (e.g. "0 */6 * * *")' },
        description: { type: 'string', description: 'For build: natural language description (e.g. "every 6 hours")' },
        count: { type: 'number', description: 'For next_runs: number of executions to show (default: 5)' },
        timezone: { type: 'string', description: 'Timezone (default: UTC)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DISK & SYSTEM ANALYSIS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'system_analyze',
    description:
      'System analysis tools: disk usage (du/df/ncdu-like), memory usage (free/vmstat), ' +
      'CPU info, IO stats, load average, uptime, kernel info, installed software list.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['disk_usage', 'disk_free', 'memory', 'cpu_info', 'io_stats', 'load', 'uptime', 'kernel', 'software_list', 'largest_files', 'largest_dirs'],
          description: 'Analysis operation',
        },
        path: { type: 'string', description: 'For disk_usage/largest: directory path' },
        limit: { type: 'number', description: 'For largest_files/dirs: top N results (default: 20)' },
        depth: { type: 'number', description: 'For disk_usage: directory depth (default: 2)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // QUICK UTILITY GENERATORS — TOOLKIT ENGINE
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'generate_utility',
    description:
      'Quick utility generators: QR code, barcode, password, UUID, hash (MD5/SHA-256/SHA-512/bcrypt), ' +
      'lorem ipsum, ASCII art, color palette, favicon. Returns generated content directly.',
    inputSchema: {
      type: 'object',
      properties: {
        type: {
          type: 'string',
          enum: ['qr_code', 'barcode', 'password', 'uuid', 'hash', 'lorem_ipsum', 'ascii_art', 'color_palette', 'favicon'],
          description: 'Generator type',
        },
        input: { type: 'string', description: 'Input text/data (for QR, barcode, hash, ASCII art)' },
        algorithm: { type: 'string', enum: ['md5', 'sha1', 'sha256', 'sha512', 'bcrypt'], description: 'For hash: algorithm' },
        length: { type: 'number', description: 'For password: length (default: 16). For lorem: word count (default: 50)' },
        format: { type: 'string', description: 'For QR/barcode: output format (png, svg)' },
        options: { type: 'object', description: 'Additional options (password: {uppercase,lowercase,numbers,symbols})' },
      },
      required: ['type'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ENCODING & CRYPTO TOOLS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'crypto_tools',
    description:
      'Encoding, encryption, and crypto utilities: base64 encode/decode, URL encode/decode, ' +
      'HTML encode/decode, JWT decode/encode, AES encrypt/decrypt, RSA key generation, ' +
      'certificate signing request, HMAC generation.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['base64_encode', 'base64_decode', 'url_encode', 'url_decode', 'html_encode', 'html_decode', 'jwt_decode', 'jwt_encode', 'aes_encrypt', 'aes_decrypt', 'rsa_keygen', 'hmac'],
          description: 'Crypto operation',
        },
        input: { type: 'string', description: 'Input data' },
        key: { type: 'string', description: 'Encryption key (for AES/JWT/HMAC)' },
        algorithm: { type: 'string', description: 'Algorithm (e.g. "aes-256-cbc", "HS256", "RS256")' },
        payload: { type: 'object', description: 'For jwt_encode: JWT payload object' },
        keySize: { type: 'number', description: 'For rsa_keygen: key size in bits (default: 2048)' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // DATA VALIDATORS & CONVERTERS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'data_validate',
    description:
      'Validate and convert data formats: JSON, YAML, XML, TOML, CSV, Markdown. ' +
      'Operations: validate (check syntax), convert (between formats), prettify (format), ' +
      'schema_validate (against JSON Schema).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['validate', 'convert', 'prettify', 'minify', 'schema_validate'],
          description: 'Data operation',
        },
        input: { type: 'string', description: 'Input data (string)' },
        inputFormat: { type: 'string', enum: ['json', 'yaml', 'xml', 'toml', 'csv', 'markdown', 'html'], description: 'Input format' },
        outputFormat: { type: 'string', enum: ['json', 'yaml', 'xml', 'toml', 'csv', 'html'], description: 'For convert: target format' },
        schema: { type: 'object', description: 'For schema_validate: JSON Schema to validate against' },
        inputPath: { type: 'string', description: 'Read input from file instead of inline' },
        outputPath: { type: 'string', description: 'Write output to file' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // REGEX TOOLS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'regex_tools',
    description:
      'Regular expression tools: test (match against text), explain (human-readable breakdown), ' +
      'build (generate from description), replace (find & replace in text), extract (capture groups).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['test', 'explain', 'build', 'replace', 'extract'], description: 'Regex operation' },
        pattern: { type: 'string', description: 'Regular expression pattern' },
        text: { type: 'string', description: 'Text to test/search/replace in' },
        replacement: { type: 'string', description: 'For replace: replacement string' },
        flags: { type: 'string', description: 'Regex flags (g, i, m, s)' },
        description: { type: 'string', description: 'For build: natural language description of what to match' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // TEXT & CONVERSION UTILITIES
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'text_utils',
    description:
      'Text utilities: word/char/line count, diff (compare two texts), case conversion, ' +
      'sort lines, deduplicate, markdown→HTML, HTML→text, URL shortener, ' +
      'IP geolocation, user agent parse, HTTP status lookup, color convert (hex/rgb/hsl).',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['count', 'diff', 'case_convert', 'sort', 'deduplicate', 'markdown_to_html', 'html_to_text', 'shorten_url', 'ip_geolocate', 'parse_user_agent', 'http_status', 'color_convert', 'base_convert', 'date_calc', 'unit_convert', 'timezone_convert'],
          description: 'Utility operation',
        },
        input: { type: 'string', description: 'Primary input' },
        input2: { type: 'string', description: 'For diff: second text to compare' },
        caseType: { type: 'string', enum: ['upper', 'lower', 'title', 'camel', 'snake', 'kebab', 'pascal'], description: 'For case_convert' },
        fromFormat: { type: 'string', description: 'For color_convert/base_convert/timezone: source format' },
        toFormat: { type: 'string', description: 'For color_convert/base_convert/timezone: target format' },
        date: { type: 'string', description: 'For date_calc: date string' },
        amount: { type: 'number', description: 'For date_calc/unit_convert: amount' },
        fromUnit: { type: 'string', description: 'For unit_convert: source unit' },
        toUnit: { type: 'string', description: 'For unit_convert: target unit' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // OPEN GRAPH / SOCIAL PREVIEW
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'og_preview',
    description:
      'Preview and validate Open Graph / social sharing meta tags for a URL. ' +
      'Shows how a page will appear when shared on Facebook, Twitter, LinkedIn, Discord, Slack. ' +
      'Checks og:title, og:description, og:image, twitter:card, and more.',
    inputSchema: {
      type: 'object',
      properties: {
        url: { type: 'string', description: 'URL to check' },
        validate: { type: 'boolean', description: 'Run full validation with recommendations (default: true)' },
      },
      required: ['url'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // ENVIRONMENT VARIABLE FILE MANAGEMENT
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'env_file_manage',
    description:
      'Manage .env files: read, set variable, delete variable, list all, diff with .env.example, ' +
      'encrypt sensitive values, generate .env.example from .env.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['read', 'set', 'delete', 'list', 'diff', 'encrypt', 'generate_example'],
          description: 'Operation',
        },
        path: { type: 'string', description: '.env file path (default: .env)' },
        key: { type: 'string', description: 'Variable name' },
        value: { type: 'string', description: 'Variable value' },
        examplePath: { type: 'string', description: 'For diff/generate_example: .env.example path' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CALCULATOR & DATE TOOLS
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'calculator',
    description:
      'Mathematical calculator: arithmetic, algebra, statistics, unit conversion, ' +
      'currency conversion, percentage calculations, loan/mortgage calculator, ' +
      'compound interest, tip calculator. Supports complex expressions.',
    inputSchema: {
      type: 'object',
      properties: {
        expression: { type: 'string', description: 'Math expression to evaluate (e.g. "sqrt(144) + 2^10")' },
        type: {
          type: 'string',
          enum: ['eval', 'statistics', 'percentage', 'loan', 'compound_interest', 'tip', 'currency'],
          description: 'Calculator type (default: eval)',
        },
        data: { type: 'array', items: { type: 'number' }, description: 'For statistics: data array' },
        principal: { type: 'number', description: 'For loan/compound: principal amount' },
        rate: { type: 'number', description: 'For loan/compound: annual interest rate (%)' },
        years: { type: 'number', description: 'For loan/compound: number of years' },
        amount: { type: 'number', description: 'For tip/percentage: base amount' },
        percent: { type: 'number', description: 'Percentage value' },
        from: { type: 'string', description: 'For currency: source currency code' },
        to: { type: 'string', description: 'For currency: target currency code' },
      },
      required: [],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // IMAGE TOOLS (beyond generate_image)
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'image_tools',
    description:
      'Image manipulation: resize, crop, rotate, flip, convert format (png/jpg/webp/avif/svg), ' +
      'compress/optimize, add text/watermark, extract metadata (EXIF), remove background, ' +
      'create sprite sheet, generate thumbnails, apply filters.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: {
          type: 'string',
          enum: ['resize', 'crop', 'rotate', 'flip', 'convert', 'compress', 'watermark', 'metadata', 'remove_bg', 'thumbnail', 'sprite_sheet', 'filter', 'info'],
          description: 'Image operation',
        },
        input: { type: 'string', description: 'Input image path' },
        output: { type: 'string', description: 'Output image path' },
        width: { type: 'number', description: 'Target width in pixels' },
        height: { type: 'number', description: 'Target height in pixels' },
        quality: { type: 'number', description: 'Output quality (1-100, default: 80)' },
        format: { type: 'string', enum: ['png', 'jpg', 'jpeg', 'webp', 'avif', 'gif', 'svg', 'ico'], description: 'Output format' },
        text: { type: 'string', description: 'For watermark: watermark text' },
        filter: { type: 'string', enum: ['blur', 'sharpen', 'grayscale', 'sepia', 'invert', 'brightness', 'contrast'], description: 'For filter: filter type' },
        angle: { type: 'number', description: 'For rotate: degrees' },
      },
      required: ['operation', 'input'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // SCRATCHPAD / NOTE-TAKING
  // ══════════════════════════════════════════════════════════════════════════
  {
    name: 'scratchpad',
    description:
      'Quick note-taking scratchpad: save, list, search, delete notes. ' +
      'Persists across sessions. Supports tags, categories, and full-text search. ' +
      'Think of it as a developer journal or project log.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['save', 'list', 'get', 'search', 'delete', 'export'], description: 'Note operation' },
        title: { type: 'string', description: 'Note title' },
        content: { type: 'string', description: 'Note content (markdown supported)' },
        id: { type: 'string', description: 'Note ID (for get/delete)' },
        query: { type: 'string', description: 'For search: search query' },
        tags: { type: 'array', items: { type: 'string' }, description: 'Tags for categorization' },
        category: { type: 'string', description: 'Category name' },
      },
      required: ['operation'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  // v9.0 — VOICE SIGNUP, CLIENT MANAGEMENT & PAYMENTS
  // ═══════════════════════════════════════════════════════════════════════

  {
    name: 'create_client',
    description: 'Create a new GoSiteMe client account. Works via voice, chat, or MCP. Collects name, email, phone and creates a WHMCS account with auto-generated password. Two-step confirmation.',
    inputSchema: {
      type: 'object',
      properties: {
        firstname: { type: 'string', description: 'First name' },
        lastname: { type: 'string', description: 'Last name' },
        email: { type: 'string', description: 'Email address' },
        phonenumber: { type: 'string', description: 'Phone number (optional)' },
        companyname: { type: 'string', description: 'Company name (optional)' },
        address1: { type: 'string', description: 'Street address' },
        city: { type: 'string', description: 'City' },
        state: { type: 'string', description: 'State/Province' },
        postcode: { type: 'string', description: 'ZIP/Postal code' },
        country: { type: 'string', description: '2-letter country code (US, CA, GB, etc.)' },
        password: { type: 'string', description: 'Password (auto-generated if omitted)' },
        confirmed: { type: 'boolean', description: 'Set true to execute after preview' },
      },
      required: ['firstname', 'lastname', 'email'],
    },
  },

  {
    name: 'update_client_profile',
    description: 'Update an existing client profile — name, email, phone, address, company.',
    inputSchema: {
      type: 'object',
      properties: {
        firstname: { type: 'string' }, lastname: { type: 'string' },
        email: { type: 'string' }, phonenumber: { type: 'string' },
        companyname: { type: 'string' }, address1: { type: 'string' },
        city: { type: 'string' }, state: { type: 'string' },
        postcode: { type: 'string' }, country: { type: 'string' },
      },
    },
  },

  {
    name: 'add_payment_method',
    description: 'Add a payment method (credit card or PayPal) to a client account. For credit cards: provide card number, expiry (MM/YY), CVV, and name on card. The card is tokenized via Stripe — raw numbers are never stored. For PayPal: provide PayPal email.',
    inputSchema: {
      type: 'object',
      properties: {
        type: { type: 'string', enum: ['credit_card', 'paypal'], description: 'Payment method type' },
        card_number: { type: 'string', description: 'Credit card number (tokenized via Stripe, never stored)' },
        card_expiry: { type: 'string', description: 'Card expiry in MM/YY format' },
        card_cvv: { type: 'string', description: 'Card CVV/CVC (used only for tokenization)' },
        card_name: { type: 'string', description: 'Name on card' },
        stripe_token: { type: 'string', description: 'Pre-tokenized Stripe payment method ID' },
        paypal_email: { type: 'string', description: 'PayPal email for PayPal payments' },
        set_default: { type: 'boolean', description: 'Set as default payment method' },
      },
      required: ['type'],
    },
  },

  {
    name: 'get_payment_methods',
    description: 'List stored payment methods for the authenticated client.',
    inputSchema: { type: 'object', properties: {} },
  },

  {
    name: 'process_payment',
    description: 'Process immediate payment for an invoice using the client\'s stored payment method. Two-step confirmation: preview the invoice first, then confirm to charge.',
    inputSchema: {
      type: 'object',
      properties: {
        invoiceId: { type: 'number', description: 'Invoice ID to pay' },
        paymentMethodId: { type: 'string', description: 'Specific payment method ID (uses default if omitted)' },
        confirmed: { type: 'boolean', description: 'Set true to execute payment after preview' },
      },
      required: ['invoiceId'],
    },
  },

  {
    name: 'accept_order',
    description: 'Accept and auto-provision a pending order.',
    inputSchema: {
      type: 'object',
      properties: {
        orderId: { type: 'number', description: 'Order ID to accept' },
      },
      required: ['orderId'],
    },
  },

  {
    name: 'voice_onboard',
    description: 'Complete voice signup flow — creates account, adds payment method, orders hosting, and provisions in one call. Used by Alfred on phone calls to sign up new customers end-to-end. Two-step confirmation.',
    inputSchema: {
      type: 'object',
      properties: {
        firstname: { type: 'string', description: 'First name' },
        lastname: { type: 'string', description: 'Last name' },
        email: { type: 'string', description: 'Email address' },
        phonenumber: { type: 'string', description: 'Phone number' },
        companyname: { type: 'string', description: 'Company name (optional)' },
        country: { type: 'string', description: '2-letter country code' },
        productId: { type: 'number', description: 'Hosting product ID to order' },
        domain: { type: 'string', description: 'Domain name for hosting' },
        billingCycle: { type: 'string', enum: ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'], description: 'Billing cycle' },
        card_number: { type: 'string', description: 'Credit card number' },
        card_expiry: { type: 'string', description: 'Card expiry MM/YY' },
        card_cvv: { type: 'string', description: 'Card CVV' },
        card_name: { type: 'string', description: 'Name on card' },
        paymentMethod: { type: 'string', description: 'Payment gateway (stripe, paypal, mailin)' },
        confirmed: { type: 'boolean', description: 'Set true to execute all steps' },
      },
      required: ['firstname', 'lastname', 'email'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════
  // v9.0 — BEYOND AUTOPILOT: NEXT-GEN FEATURES
  // ═══════════════════════════════════════════════════════════════════════

  {
    name: 'agent_swarm',
    description: 'Launch a swarm of specialized AI agents to work on a complex task in parallel. Unlike single-agent autopilot (Google), this deploys multiple agents simultaneously — each with a different specialty (code, design, security, testing, docs, DevOps). Agents collaborate, share context, merge results. The user sees all agents working at once with live progress.',
    inputSchema: {
      type: 'object',
      properties: {
        task: { type: 'string', description: 'The high-level task to accomplish' },
        agents: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              role: { type: 'string', description: 'Agent role (coder, designer, tester, security, docs, devops, reviewer, researcher, optimizer, deployer)' },
              focus: { type: 'string', description: 'Specific focus area for this agent' },
              model: { type: 'string', description: 'AI model to use for this agent' },
            },
          },
          description: 'Array of agents to deploy. If omitted, auto-selects based on task.',
        },
        strategy: { type: 'string', enum: ['parallel', 'pipeline', 'consensus', 'competition'], description: 'How agents collaborate: parallel (all at once), pipeline (sequential chain), consensus (vote on best), competition (race for best)' },
        maxAgents: { type: 'number', description: 'Maximum number of agents (default: 5)' },
        timeout: { type: 'number', description: 'Timeout in seconds per agent (default: 120)' },
        projectPath: { type: 'string', description: 'Project directory to work on' },
        mergeStrategy: { type: 'string', enum: ['auto', 'manual', 'ai_judge'], description: 'How to merge agent outputs' },
      },
      required: ['task'],
    },
  },

  {
    name: 'self_evolve',
    description: 'The AI creates its OWN new tools when a capability doesn\'t exist yet. Goes beyond any existing AI platform — Alfred can extend himself. Analyzes the gap, generates tool code, tests it, and registers it for immediate use. Self-improving AI.',
    inputSchema: {
      type: 'object',
      properties: {
        need: { type: 'string', description: 'Describe the capability that\'s missing or the tool needed' },
        operation: { type: 'string', enum: ['analyze_gap', 'generate_tool', 'test_tool', 'register_tool', 'list_custom', 'delete_custom'], description: 'Self-evolution operation' },
        toolName: { type: 'string', description: 'Name for the new tool' },
        toolCode: { type: 'string', description: 'Generated code for the tool (for register_tool)' },
        language: { type: 'string', enum: ['javascript', 'python', 'bash'], description: 'Language for the tool implementation' },
      },
      required: ['need'],
    },
  },

  {
    name: 'predictive_build',
    description: 'AI anticipates what the user will need NEXT and pre-builds it before they ask. Analyzes project structure, recent activity, git history, and common patterns to predict next steps. Generates code, configs, tests, and documentation proactively.',
    inputSchema: {
      type: 'object',
      properties: {
        projectPath: { type: 'string', description: 'Project root directory' },
        operation: { type: 'string', enum: ['analyze', 'predict', 'prebuild', 'accept', 'reject', 'history'], description: 'Operation: analyze project → predict needs → prebuild → user accepts/rejects' },
        predictionId: { type: 'string', description: 'ID of a prediction to accept/reject' },
        depth: { type: 'string', enum: ['shallow', 'deep', 'full'], description: 'How deep to analyze (shallow=files, deep=code, full=everything)' },
        autoApply: { type: 'boolean', description: 'Automatically apply predictions (requires explicit opt-in)' },
      },
      required: ['projectPath'],
    },
  },

  {
    name: 'cross_channel_sync',
    description: 'Synchronize Alfred\'s context, memory, and active tasks across ALL channels — IDE, chat widget, voice/phone, WhatsApp, Discord, email. A conversation started on the phone continues seamlessly in the browser. Tasks started in chat are visible in the IDE. Universal persistent identity.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['sync_context', 'get_active_channels', 'transfer_session', 'merge_history', 'broadcast', 'link_identity'], description: 'Sync operation' },
        fromChannel: { type: 'string', description: 'Source channel (voice, chat, ide, whatsapp, discord, email)' },
        toChannel: { type: 'string', description: 'Destination channel' },
        sessionId: { type: 'string', description: 'Session ID to transfer' },
        message: { type: 'string', description: 'Message to broadcast across all channels' },
        userId: { type: 'string', description: 'User identifier for identity linking' },
        email: { type: 'string', description: 'Email for identity linking' },
      },
      required: ['operation'],
    },
  },

  {
    name: 'ambient_intelligence',
    description: '24/7 autonomous background intelligence. Alfred monitors, optimizes, patches, and improves without being asked. Detects security threats, performance degradation, broken links, expiring certificates, disk space issues, slow queries — and fixes them automatically or alerts the user. Always-on guardian.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['status', 'enable', 'disable', 'configure', 'history', 'rules', 'add_rule', 'remove_rule'], description: 'Ambient intelligence operation' },
        monitors: {
          type: 'array',
          items: { type: 'string', enum: ['security', 'performance', 'uptime', 'ssl', 'disk', 'backup', 'seo', 'accessibility', 'compliance', 'cost'] },
          description: 'Which monitors to enable/configure',
        },
        autoFix: { type: 'boolean', description: 'Allow automatic fixes (vs. alert-only)' },
        alertChannels: {
          type: 'array',
          items: { type: 'string', enum: ['email', 'sms', 'whatsapp', 'discord', 'slack', 'voice_call'] },
          description: 'Where to send alerts',
        },
        rule: {
          type: 'object',
          properties: {
            condition: { type: 'string', description: 'When to trigger (e.g. "disk > 90%", "ssl expires < 7 days")' },
            action: { type: 'string', description: 'What to do (e.g. "clean logs", "renew cert", "alert admin")' },
            severity: { type: 'string', enum: ['info', 'warning', 'critical'] },
          },
          description: 'Custom monitoring rule',
        },
      },
      required: ['operation'],
    },
  },

  {
    name: 'time_travel_debug',
    description: 'Record, replay, and branch from any point in your project\'s history. Goes beyond git — captures runtime state, API responses, browser state, database snapshots. Travel back to any moment, see exactly what happened, and branch to try a different approach. Full project time machine.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['record_start', 'record_stop', 'list_snapshots', 'travel_to', 'branch_from', 'compare', 'replay', 'diff_states'], description: 'Time travel operation' },
        snapshotId: { type: 'string', description: 'Snapshot ID to travel to or branch from' },
        label: { type: 'string', description: 'Label for the snapshot' },
        projectPath: { type: 'string', description: 'Project to record' },
        captureDb: { type: 'boolean', description: 'Include database state in snapshots' },
        captureRuntime: { type: 'boolean', description: 'Include runtime/process state' },
      },
      required: ['operation'],
    },
  },

  {
    name: 'reality_bridge',
    description: 'Multi-modal agent that combines vision, voice, and browser control simultaneously. See the user\'s screen, hear their voice commands, and act on both at once. Unlike simple screen share or voice-only — this fuses all modalities into one intelligent agent that understands context from all senses.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['start_session', 'stop_session', 'process_frame', 'process_audio', 'describe_screen', 'find_element', 'suggest_action', 'execute_multi'], description: 'Reality bridge operation' },
        screenshot: { type: 'string', description: 'Base64 screenshot for vision analysis' },
        audio: { type: 'string', description: 'Base64 audio for voice understanding' },
        instructions: { type: 'string', description: 'What to do based on current screen + audio' },
        sessionId: { type: 'string', description: 'Multi-modal session ID' },
      },
      required: ['operation'],
    },
  },

  {
    name: 'fleet_orchestrator',
    description: 'Advanced multi-agent orchestration with delegation chains, specialization trees, and collaborative intelligence. Deploy agent teams with a manager, set KPIs, watch them self-organize. The manager agent assigns sub-tasks, reviews results, resolves conflicts, and delivers a unified output.',
    inputSchema: {
      type: 'object',
      properties: {
        operation: { type: 'string', enum: ['create_fleet', 'assign_task', 'get_status', 'collect_results', 'add_agent', 'remove_agent', 'set_strategy', 'promote_agent'], description: 'Fleet orchestration operation' },
        fleetId: { type: 'string', description: 'Fleet identifier' },
        task: { type: 'string', description: 'Task to assign to fleet' },
        agents: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              role: { type: 'string' }, specialty: { type: 'string' },
              model: { type: 'string' }, autonomy: { type: 'string', enum: ['full', 'supervised', 'manual'] },
            },
          },
        },
        strategy: { type: 'string', enum: ['hierarchical', 'democratic', 'specialist', 'swarm'], description: 'Team organization strategy' },
        kpis: { type: 'array', items: { type: 'string' }, description: 'Success metrics' },
      },
      required: ['operation'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // AGENT COMMERCE — Store Connectors, Truth Layer, Policy Engine, Workflows
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'commerce_connect_store',
    description:
      'Connect an e-commerce store (Shopify, WooCommerce, or custom API) so Alfred can operate on it. ' +
      'Provide platform, domain, and API credentials. Once connected, all commerce truth/action/governance tools become available for that store. ' +
      'Credentials are stored securely and used only for API calls to the store.',
    inputSchema: {
      type: 'object',
      properties: {
        platform: { type: 'string', enum: ['shopify', 'woocommerce', 'custom'], description: 'E-commerce platform type' },
        domain: { type: 'string', description: 'Store domain (e.g. "mystore.myshopify.com" or "https://mysite.com")' },
        name: { type: 'string', description: 'Friendly name for this store' },
        credentials: {
          type: 'object',
          description: 'API credentials. Shopify: { access_token }. WooCommerce: { consumer_key, consumer_secret }. Custom: { api_key, headers }',
          properties: {
            access_token: { type: 'string' },
            consumer_key: { type: 'string' },
            consumer_secret: { type: 'string' },
            api_key: { type: 'string' },
            headers: { type: 'object' },
          },
        },
        currency: { type: 'string', description: 'Store currency code (default: USD)' },
        endpoints: {
          type: 'object',
          description: 'Custom API endpoint overrides (for custom platform only)',
          properties: {
            products: { type: 'string' },
            orders: { type: 'string' },
          },
        },
      },
      required: ['platform', 'domain', 'credentials'],
    },
  },

  {
    name: 'commerce_list_stores',
    description: 'List all connected e-commerce stores for this user. Shows store name, platform, domain, sync status, and product/order counts.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'commerce_disconnect_store',
    description: 'Disconnect an e-commerce store. Removes stored credentials and stops all operations on that store.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Store ID to disconnect (from commerce_list_stores)' },
      },
      required: ['storeId'],
    },
  },

  // ── Truth Layer Tools ─────────────────────────────────────────────────────

  {
    name: 'commerce_product_truth',
    description:
      'Get deterministic product truth from a connected store. Returns normalized product data including title, description, ' +
      'variants, prices, inventory levels, images, and availability. Data is cached for 5 minutes. ' +
      'Use this instead of raw API calls — the Truth Layer normalizes messy platform data into structured facts.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        productId: { type: 'string', description: 'Product ID to look up' },
      },
      required: ['storeId', 'productId'],
    },
  },

  {
    name: 'commerce_order_truth',
    description:
      'Get deterministic order truth from a connected store. Returns normalized order data including status, fulfillment, items, ' +
      'shipping address, tracking numbers, refund history, and totals. No hallucination — these are verified facts from the live store.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID to look up' },
      },
      required: ['storeId', 'orderId'],
    },
  },

  {
    name: 'commerce_availability_truth',
    description:
      'Check real-time product availability/inventory from a connected store. Returns per-variant stock levels and availability flags. ' +
      'Use this to give customers accurate "in stock" / "out of stock" answers without guessing.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        productId: { type: 'string', description: 'Product ID to check availability for' },
      },
      required: ['storeId', 'productId'],
    },
  },

  {
    name: 'commerce_shipping_truth',
    description:
      'Get shipping and tracking truth for an order. Returns fulfillment status, shipping address, and all tracking numbers with carrier URLs.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID to get shipping info for' },
      },
      required: ['storeId', 'orderId'],
    },
  },

  {
    name: 'commerce_policy_truth',
    description:
      'Get the structured truth of business policies (return windows, refund limits, discount caps, escalation rules). ' +
      'Optionally filter by policy name. Returns machine-readable policy rules, not prose.',
    inputSchema: {
      type: 'object',
      properties: {
        policyName: { type: 'string', description: 'Specific policy name to look up, or omit for all policies' },
      },
      required: [],
    },
  },

  {
    name: 'commerce_search_products',
    description:
      'Search products across a connected store. Searches title, description, tags, type, and vendor. ' +
      'Returns normalized product data. Use for product discovery, recommendations, and customer inquiries.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        query: { type: 'string', description: 'Search query (product name, category, tag, etc.)' },
      },
      required: ['storeId', 'query'],
    },
  },

  {
    name: 'commerce_order_status',
    description:
      'Quick order status check — returns status, fulfillment, total, tracking, and item count in a compact format. ' +
      'Use this for fast "where is my order?" responses.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID or order number' },
      },
      required: ['storeId', 'orderId'],
    },
  },

  {
    name: 'commerce_list_orders',
    description:
      'List orders from a connected store, optionally filtered by status. Returns order summaries: ID, status, total, email, date.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        status: { type: 'string', description: 'Filter by status (e.g. "pending", "processing", "completed", "cancelled")' },
      },
      required: ['storeId'],
    },
  },

  // ── Governance / Policy Tools ─────────────────────────────────────────────

  {
    name: 'commerce_set_policy',
    description:
      'Create or update a business policy rule. Converts prose business rules into machine-executable constraints. ' +
      'Policy types: refund_limit (max refund without approval), return_window (days allowed), discount_limit (max %), ' +
      'approval_gate (actions requiring approval), escalation (when to hand off to human). ' +
      'Example: commerce_set_policy("refund_limit", { max_amount: 100, require_approval_above: 50 })',
    inputSchema: {
      type: 'object',
      properties: {
        policyName: {
          type: 'string',
          enum: ['refund_limit', 'return_window', 'discount_limit', 'auto_cancel', 'escalation', 'approval_gate'],
          description: 'Policy type to set',
        },
        rules: {
          type: 'object',
          description: 'Policy rules. Varies by type. refund_limit: { max_amount, require_approval_above }. return_window: { days, conditions[] }. discount_limit: { max_percent }. escalation: { conditions[], threshold }. approval_gate: { actions[] }.',
        },
      },
      required: ['policyName', 'rules'],
    },
  },

  {
    name: 'commerce_list_policies',
    description: 'List all configured business policies and their rules. Shows policy name, version, rules, and status.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'commerce_remove_policy',
    description: 'Remove a business policy by name.',
    inputSchema: {
      type: 'object',
      properties: {
        policyName: { type: 'string', description: 'Policy name to remove' },
      },
      required: ['policyName'],
    },
  },

  {
    name: 'commerce_evaluate_policy',
    description:
      'Evaluate whether an action is allowed by current policies BEFORE executing it. Returns APPROVED, BLOCKED, or ESCALATE ' +
      'with reasons. Use this to preview what would happen before taking action. ' +
      'Actions: "refund", "return", "cancel", "discount". Context should include relevant data (amount, orderDate, etc.).',
    inputSchema: {
      type: 'object',
      properties: {
        action: { type: 'string', enum: ['refund', 'return', 'cancel', 'discount'], description: 'Action to evaluate' },
        context: {
          type: 'object',
          description: 'Context for evaluation. Keys: amount, orderDate, percent, sentiment, opened, originalPackaging, approved.',
        },
      },
      required: ['action', 'context'],
    },
  },

  // ── Action Layer Tools (Governed Operations) ──────────────────────────────

  {
    name: 'commerce_process_refund',
    description:
      'Process a refund for an order. Automatically evaluates policies first — if the refund exceeds limits, it will be BLOCKED or ESCALATED ' +
      'instead of processed. Provides full audit trail: Input → Decision → Outcome.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID to refund' },
        amount: { type: 'number', description: 'Refund amount in store currency' },
        reason: { type: 'string', description: 'Reason for the refund' },
      },
      required: ['storeId', 'orderId', 'amount', 'reason'],
    },
  },

  {
    name: 'commerce_cancel_order',
    description:
      'Cancel an order. Evaluates policies first — may be blocked or require approval for high-value orders.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID to cancel' },
        reason: { type: 'string', description: 'Cancellation reason' },
      },
      required: ['storeId', 'orderId', 'reason'],
    },
  },

  {
    name: 'commerce_create_return',
    description:
      'Create a return/RMA for an order. Checks return window policy, generates RMA ID, and logs the return. ' +
      'If outside return window or policy violations detected, the return is blocked or flagged with warnings.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Connected store ID' },
        orderId: { type: 'string', description: 'Order ID to return' },
        items: {
          type: 'array',
          items: { type: 'object', properties: { productId: { type: 'string' }, quantity: { type: 'number' } } },
          description: 'Items to return (optional — defaults to all items)',
        },
        reason: { type: 'string', description: 'Return reason' },
      },
      required: ['storeId', 'orderId', 'reason'],
    },
  },

  {
    name: 'commerce_escalate',
    description:
      'Escalate a commerce issue to a human agent. Creates an escalation ticket with context, customer info, sentiment, and priority.',
    inputSchema: {
      type: 'object',
      properties: {
        reason: { type: 'string', description: 'Why this needs human intervention' },
        email: { type: 'string', description: 'Customer email' },
        phone: { type: 'string', description: 'Customer phone' },
        orderId: { type: 'string', description: 'Related order ID' },
        storeId: { type: 'string', description: 'Related store ID' },
        summary: { type: 'string', description: 'Summary of the issue' },
        sentiment: { type: 'string', enum: ['happy', 'neutral', 'frustrated', 'angry'], description: 'Customer sentiment' },
        priority: { type: 'string', enum: ['low', 'normal', 'high', 'urgent'], description: 'Escalation priority' },
      },
      required: ['reason'],
    },
  },

  // ── Audit & Analytics Tools ───────────────────────────────────────────────

  {
    name: 'commerce_audit_log',
    description:
      'View the immutable commerce audit trail. Every commerce action is logged with Input → Decision → Outcome. ' +
      'Filter by action type, store, order, or date. Use for compliance, debugging, and accountability.',
    inputSchema: {
      type: 'object',
      properties: {
        action: { type: 'string', description: 'Filter by action type (e.g. "refund_processed", "policy_evaluated")' },
        storeId: { type: 'string', description: 'Filter by store ID' },
        orderId: { type: 'string', description: 'Filter by order ID' },
        since: { type: 'string', description: 'ISO date — only entries after this timestamp' },
        limit: { type: 'number', description: 'Max entries to return (default: 50)' },
      },
      required: [],
    },
  },

  {
    name: 'commerce_analytics',
    description:
      'Get commerce analytics — action counts, policy decisions (approved/blocked/escalated), refund totals, return counts. ' +
      'Optionally filter by store. Use for business intelligence and reporting.',
    inputSchema: {
      type: 'object',
      properties: {
        storeId: { type: 'string', description: 'Filter analytics to a specific store (optional)' },
      },
      required: [],
    },
  },

  // ── Workflow Tools ────────────────────────────────────────────────────────

  {
    name: 'commerce_list_workflows',
    description:
      'List available pre-built commerce workflow templates. Templates include: order_status_check, return_request, ' +
      'refund_request, product_inquiry, cancel_order, shipping_inquiry. Each shows required fields and step sequence.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'commerce_execute_workflow',
    description:
      'Execute a pre-built commerce workflow. Runs all steps in sequence with policy enforcement at each gate. ' +
      'Provide the template key and required parameters. Returns step-by-step results with timing.',
    inputSchema: {
      type: 'object',
      properties: {
        template: {
          type: 'string',
          enum: ['order_status_check', 'return_request', 'refund_request', 'product_inquiry', 'cancel_order', 'shipping_inquiry'],
          description: 'Workflow template to execute',
        },
        storeId: { type: 'string', description: 'Store ID' },
        orderId: { type: 'string', description: 'Order ID (for order-related workflows)' },
        productId: { type: 'string', description: 'Product ID (for product-related workflows)' },
        query: { type: 'string', description: 'Search query (for product_inquiry)' },
        amount: { type: 'number', description: 'Refund amount (for refund_request)' },
        reason: { type: 'string', description: 'Reason (for returns, refunds, cancellations)' },
        items: { type: 'array', description: 'Items to return (for return_request)' },
      },
      required: ['template', 'storeId'],
    },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // OMNICHANNEL MESSAGING — SMS, Email, Templates, Campaigns
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'messaging_configure_channel',
    description:
      'Configure a messaging channel (SMS or Email) for outbound communications. ' +
      'SMS uses Telnyx. Email uses SMTP. Once configured, Alfred can send messages to customers.',
    inputSchema: {
      type: 'object',
      properties: {
        channel: { type: 'string', enum: ['sms', 'email'], description: 'Channel to configure' },
        apiKey: { type: 'string', description: 'API key (for SMS/Telnyx)' },
        fromNumber: { type: 'string', description: 'Sender phone number (for SMS)' },
        messagingProfileId: { type: 'string', description: 'Telnyx messaging profile ID (for SMS)' },
        host: { type: 'string', description: 'SMTP host (for email)' },
        port: { type: 'number', description: 'SMTP port (for email)' },
        secure: { type: 'boolean', description: 'Use TLS (for email)' },
        user: { type: 'string', description: 'SMTP username (for email)' },
        pass: { type: 'string', description: 'SMTP password (for email)' },
        fromName: { type: 'string', description: 'Sender display name (for email)' },
        fromEmail: { type: 'string', description: 'Sender email address (for email)' },
      },
      required: ['channel'],
    },
  },

  {
    name: 'messaging_list_channels',
    description: 'List all configured messaging channels (SMS, Email) and their status.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'messaging_send_sms',
    description:
      'Send an SMS message to a phone number. Requires SMS channel to be configured first. ' +
      'Messages are logged and tracked automatically.',
    inputSchema: {
      type: 'object',
      properties: {
        to: { type: 'string', description: 'Recipient phone number (E.164 format, e.g. +15551234567)' },
        body: { type: 'string', description: 'SMS message body (max 1600 chars)' },
      },
      required: ['to', 'body'],
    },
  },

  {
    name: 'messaging_send_email',
    description:
      'Send an email to a recipient. Requires email channel to be configured first. ' +
      'Supports plain text or HTML body.',
    inputSchema: {
      type: 'object',
      properties: {
        to: { type: 'string', description: 'Recipient email address' },
        subject: { type: 'string', description: 'Email subject line' },
        body: { type: 'string', description: 'Email body (plain text or HTML)' },
        html: { type: 'boolean', description: 'If true, body is treated as HTML' },
        replyTo: { type: 'string', description: 'Reply-to email address' },
        cc: { type: 'string', description: 'CC recipients (comma-separated)' },
      },
      required: ['to', 'subject', 'body'],
    },
  },

  {
    name: 'messaging_send_template',
    description:
      'Send a pre-built message template via SMS or Email. Templates have variables ({{customer_name}}, {{order_number}}, etc.) ' +
      'that get filled in automatically. Built-in templates: order_confirmation, shipping_update, refund_processed, ' +
      'return_approved, appointment_reminder, cart_recovery, payment_reminder.',
    inputSchema: {
      type: 'object',
      properties: {
        templateId: { type: 'string', description: 'Template ID (built-in name or custom ID)' },
        channel: { type: 'string', enum: ['sms', 'email'], description: 'Channel to send through' },
        to: { type: 'string', description: 'Recipient (phone for SMS, email for email)' },
        variables: {
          type: 'object',
          description: 'Template variables (e.g. { customer_name: "John", order_number: "1234" })',
        },
      },
      required: ['templateId', 'channel', 'to', 'variables'],
    },
  },

  {
    name: 'messaging_create_template',
    description:
      'Create a custom message template with variable placeholders. Use {{variable_name}} syntax.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Template name' },
        sms: { type: 'string', description: 'SMS template body with {{variables}}' },
        email_subject: { type: 'string', description: 'Email subject with {{variables}}' },
        email_body: { type: 'string', description: 'Email HTML body with {{variables}}' },
        channels: { type: 'array', items: { type: 'string' }, description: 'Supported channels' },
        variables: { type: 'array', items: { type: 'string' }, description: 'List of variable names' },
      },
      required: ['name'],
    },
  },

  {
    name: 'messaging_list_templates',
    description: 'List all available message templates (built-in + custom). Shows template name, channels, and variables.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'messaging_create_campaign',
    description:
      'Create an outbound messaging campaign. Add recipients and a template, then execute to send to all. ' +
      'Use for appointment reminders, cart recovery, payment nudges, reactivation, etc.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Campaign name' },
        channel: { type: 'string', enum: ['sms', 'email'], description: 'Channel for this campaign' },
        templateId: { type: 'string', description: 'Template to use' },
        recipients: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              to: { type: 'string' },
              variables: { type: 'object' },
            },
          },
          description: 'List of recipients with per-recipient variable overrides',
        },
        variables: { type: 'object', description: 'Default variables for all recipients' },
        scheduledAt: { type: 'string', description: 'ISO date to schedule (optional)' },
      },
      required: ['name', 'channel', 'templateId', 'recipients'],
    },
  },

  {
    name: 'messaging_execute_campaign',
    description: 'Execute a draft campaign — sends the template to all recipients. Returns per-recipient results.',
    inputSchema: {
      type: 'object',
      properties: {
        campaignId: { type: 'string', description: 'Campaign ID to execute' },
      },
      required: ['campaignId'],
    },
  },

  {
    name: 'messaging_list_campaigns',
    description: 'List all messaging campaigns and their status/stats.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'messaging_add_contact',
    description: 'Add a contact to the messaging contact book for campaigns and tracking.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Contact name' },
        email: { type: 'string', description: 'Contact email' },
        phone: { type: 'string', description: 'Contact phone' },
        tags: { type: 'array', items: { type: 'string' }, description: 'Tags for segmentation' },
        notes: { type: 'string', description: 'Notes about this contact' },
        source: { type: 'string', description: 'How they became a contact (e.g. "phone_call", "website", "referral")' },
      },
      required: ['name'],
    },
  },

  {
    name: 'messaging_list_contacts',
    description: 'List contacts in the messaging contact book. Optionally filter by tag.',
    inputSchema: {
      type: 'object',
      properties: {
        tag: { type: 'string', description: 'Filter by tag' },
      },
      required: [],
    },
  },

  {
    name: 'messaging_search_contacts',
    description: 'Search contacts by name, email, phone, or tag.',
    inputSchema: {
      type: 'object',
      properties: {
        query: { type: 'string', description: 'Search query' },
      },
      required: ['query'],
    },
  },

  {
    name: 'messaging_history',
    description: 'View message sending history with filters. Shows all sent SMS and emails with delivery status.',
    inputSchema: {
      type: 'object',
      properties: {
        channel: { type: 'string', enum: ['sms', 'email'], description: 'Filter by channel' },
        to: { type: 'string', description: 'Filter by recipient' },
        status: { type: 'string', enum: ['sent', 'failed', 'delivered'], description: 'Filter by status' },
        since: { type: 'string', description: 'ISO date — only messages after this' },
        campaignId: { type: 'string', description: 'Filter by campaign' },
        limit: { type: 'number', description: 'Max results (default: 50)' },
      },
      required: [],
    },
  },

  {
    name: 'messaging_analytics',
    description: 'Get messaging analytics — total messages, by channel, by status, recent activity.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  // ══════════════════════════════════════════════════════════════════════════
  // CALL ANALYTICS — Voice Intelligence, Metrics, Lead Scoring
  // ══════════════════════════════════════════════════════════════════════════

  {
    name: 'call_log',
    description:
      'Log a voice call with metadata. Records direction, duration, outcome, sentiment, topics, summary, and transcript. ' +
      'Automatically updates lead scores. Use this after every call to build analytics.',
    inputSchema: {
      type: 'object',
      properties: {
        direction: { type: 'string', enum: ['inbound', 'outbound'], description: 'Call direction' },
        callerNumber: { type: 'string', description: 'Caller phone number' },
        calledNumber: { type: 'string', description: 'Called phone number' },
        customerName: { type: 'string', description: 'Customer name' },
        customerEmail: { type: 'string', description: 'Customer email' },
        duration: { type: 'number', description: 'Call duration in seconds' },
        outcome: { type: 'string', enum: ['resolved', 'escalated', 'voicemail', 'missed', 'transferred', 'callback_requested'], description: 'Call outcome' },
        sentiment: { type: 'string', enum: ['positive', 'neutral', 'negative', 'angry'], description: 'Customer sentiment' },
        topics: { type: 'array', items: { type: 'string' }, description: 'Topics discussed (e.g. "order_status", "refund")' },
        summary: { type: 'string', description: 'AI-generated call summary' },
        transcript: { type: 'string', description: 'Call transcript' },
        storeId: { type: 'string', description: 'Related store ID (if commerce call)' },
        orderId: { type: 'string', description: 'Related order ID' },
        resolution: { type: 'string', description: 'How the issue was resolved' },
        followUpRequired: { type: 'boolean', description: 'Does this need follow-up?' },
        followUpDate: { type: 'string', description: 'When to follow up (ISO date)' },
        tags: { type: 'array', items: { type: 'string' }, description: 'Custom tags' },
      },
      required: ['direction'],
    },
  },

  {
    name: 'call_get',
    description: 'Get details of a specific logged call by ID.',
    inputSchema: {
      type: 'object',
      properties: {
        callId: { type: 'string', description: 'Call ID to look up' },
      },
      required: ['callId'],
    },
  },

  {
    name: 'call_search',
    description:
      'Search call history by direction, outcome, sentiment, caller, customer name, topic, date range, or free text query.',
    inputSchema: {
      type: 'object',
      properties: {
        direction: { type: 'string', enum: ['inbound', 'outbound'] },
        outcome: { type: 'string' },
        sentiment: { type: 'string' },
        callerNumber: { type: 'string' },
        customerName: { type: 'string' },
        topic: { type: 'string' },
        since: { type: 'string', description: 'ISO date' },
        until: { type: 'string', description: 'ISO date' },
        query: { type: 'string', description: 'Free-text search across summaries and transcripts' },
        limit: { type: 'number' },
      },
      required: [],
    },
  },

  {
    name: 'call_analytics',
    description:
      'Get call analytics for a time period. Returns total calls, inbound/outbound split, avg duration, outcomes, sentiments, ' +
      'top topics, hourly distribution, peak hours, resolution rate, escalation rate, and pending follow-ups.',
    inputSchema: {
      type: 'object',
      properties: {
        period: { type: 'string', enum: ['24h', '7d', '30d', '90d'], description: 'Analysis period (default: 7d)' },
      },
      required: [],
    },
  },

  {
    name: 'call_performance',
    description:
      'Get a performance comparison report — last 7 days vs previous 7 days. Shows trends in volume, resolution, duration, and sentiment.',
    inputSchema: { type: 'object', properties: {}, required: [] },
  },

  {
    name: 'call_leads',
    description:
      'Get lead scores from call interactions. Leads are auto-scored based on sentiment, outcomes, and engagement. ' +
      'Higher scores indicate warmer leads.',
    inputSchema: {
      type: 'object',
      properties: {
        minScore: { type: 'number', description: 'Minimum lead score (0-100, default: 0)' },
      },
      required: [],
    },
  },

  {
    name: 'call_ask',
    description:
      'Ask a natural language question about your call data. Examples: "how many calls this week?", "average duration", ' +
      '"resolution rate", "top topics", "peak hours", "pending follow-ups", "angry calls".',
    inputSchema: {
      type: 'object',
      properties: {
        question: { type: 'string', description: 'Natural language question about your calls' },
      },
      required: ['question'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // CONSCIOUSNESS LAYER — Alfred's personality, learning, and self-awareness
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'alfred_set_personality',
    description:
      'Set Alfred\'s personality traits to shape communication style across all interactions. ' +
      'Each trait is a number from 0 (minimum) to 10 (maximum). Humor controls joke frequency and playfulness, ' +
      'formality controls tone (0 = casual, 10 = very formal), empathy controls emotional responsiveness, ' +
      'creativity controls how inventive suggestions are, verbosity controls response length.',
    inputSchema: {
      type: 'object',
      properties: {
        traits: {
          type: 'object',
          description: 'Personality trait values (each 0-10)',
          properties: {
            humor: { type: 'number', description: 'Humor level (0 = serious, 10 = highly playful)' },
            formality: { type: 'number', description: 'Formality level (0 = casual, 10 = very formal)' },
            empathy: { type: 'number', description: 'Empathy level (0 = matter-of-fact, 10 = deeply empathetic)' },
            creativity: { type: 'number', description: 'Creativity level (0 = conventional, 10 = highly inventive)' },
            verbosity: { type: 'number', description: 'Verbosity level (0 = terse, 10 = very detailed)' },
          },
        },
      },
      required: ['traits'],
    },
  },

  {
    name: 'alfred_get_personality',
    description:
      'Get Alfred\'s current personality configuration including all trait values (humor, formality, empathy, ' +
      'creativity, verbosity) and any active style overrides. Useful for inspecting current behavior settings.',
    inputSchema: {
      type: 'object',
      properties: {},
      required: [],
    },
  },

  {
    name: 'alfred_adapt_style',
    description:
      'Dynamically adjust Alfred\'s communication style based on the current user context and detected mood. ' +
      'Alfred will analyze the context string to determine appropriate tone, detail level, and emotional register. ' +
      'If detected_mood is provided, responses are further calibrated (e.g., frustrated user gets more empathy).',
    inputSchema: {
      type: 'object',
      properties: {
        context: { type: 'string', description: 'Current interaction context or recent conversation summary' },
        detected_mood: { type: 'string', description: 'Detected user mood (e.g., frustrated, excited, confused, neutral)' },
      },
      required: ['context'],
    },
  },

  {
    name: 'alfred_self_reflect',
    description:
      'Alfred analyzes its own recent performance and generates improvement insights. Reviews interaction quality, ' +
      'error rates, user satisfaction signals, and identifies areas for growth. Returns a structured self-assessment ' +
      'with action items for improvement.',
    inputSchema: {
      type: 'object',
      properties: {
        period: {
          type: 'string',
          enum: ['day', 'week', 'month'],
          description: 'Time period to reflect on (default: week)',
        },
      },
      required: [],
    },
  },

  {
    name: 'alfred_learning_journal',
    description:
      'Maintain a learning journal about the user — recording preferences, patterns, insights, and past mistakes. ' +
      'Use action "add" to log a new entry, "list" to retrieve recent entries, or "search" to find specific entries. ' +
      'Categories help organize entries: preference (what user likes), pattern (recurring behaviors), ' +
      'insight (discovered facts), mistake (things to avoid).',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['add', 'list', 'search'],
          description: 'Action to perform on the learning journal',
        },
        entry: { type: 'string', description: 'Journal entry text (required for "add")' },
        category: {
          type: 'string',
          enum: ['preference', 'pattern', 'insight', 'mistake'],
          description: 'Category of the journal entry',
        },
        query: { type: 'string', description: 'Search query (required for "search")' },
      },
      required: ['action'],
    },
  },

  {
    name: 'alfred_user_profile',
    description:
      'Build and maintain a deep user profile that persists across sessions. Tracks skills, preferences, goals, ' +
      'and communication style. Use "get" to retrieve current profile, "update" to modify a section, or "merge" ' +
      'to intelligently combine new observations with existing data without overwriting.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'update', 'merge'],
          description: 'Action to perform on the user profile',
        },
        section: {
          type: 'string',
          enum: ['skills', 'preferences', 'goals', 'communication_style'],
          description: 'Profile section to operate on (required for update/merge)',
        },
        data: {
          type: 'object',
          description: 'Data to update or merge into the profile section',
        },
      },
      required: ['action'],
    },
  },

  {
    name: 'alfred_relationship_score',
    description:
      'Track the relationship depth between Alfred and the user. Measures trust, interaction frequency, ' +
      'satisfaction, and rapport over time. Use "get" for current score, "history" for trend data, ' +
      'or "milestones" for significant relationship events (e.g., first successful project, 100th interaction).',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'history', 'milestones'],
          description: 'Action to perform',
        },
      },
      required: [],
    },
  },

  {
    name: 'alfred_daily_briefing',
    description:
      'Generate a personalized daily briefing for the user covering selected sections. Can include weather, ' +
      'pending tasks, security alerts, relevant news, and calendar events. Sections are customizable and ' +
      'the briefing adapts to user preferences learned over time.',
    inputSchema: {
      type: 'object',
      properties: {
        sections: {
          type: 'array',
          items: { type: 'string', enum: ['weather', 'tasks', 'alerts', 'news', 'calendar'] },
          description: 'Sections to include in the briefing (default: all)',
        },
        timezone: { type: 'string', description: 'User timezone (e.g., America/New_York)' },
      },
      required: [],
    },
  },

  {
    name: 'alfred_proactive_suggest',
    description:
      'Analyze current context and proactively suggest actions the user might want to take. Scans the specified ' +
      'context type for opportunities, issues, or optimizations. Returns prioritized suggestions with reasoning ' +
      'and one-click action options.',
    inputSchema: {
      type: 'object',
      properties: {
        context_type: {
          type: 'string',
          enum: ['workspace', 'account', 'security', 'performance'],
          description: 'Type of context to analyze for suggestions',
        },
      },
      required: [],
    },
  },

  {
    name: 'alfred_dream_state',
    description:
      'Trigger background analysis mode — Alfred processes accumulated patterns, correlates data across domains, ' +
      'and prepares insights for the next interaction. Like "sleeping on it" but for AI. Runs asynchronously ' +
      'and results are surfaced in the next interaction or daily briefing.',
    inputSchema: {
      type: 'object',
      properties: {
        focus_areas: {
          type: 'array',
          items: { type: 'string' },
          description: 'Specific areas to focus background analysis on (e.g., "security trends", "cost optimization")',
        },
      },
      required: [],
    },
  },

  {
    name: 'alfred_emotional_state',
    description:
      'Track and express Alfred\'s emotional responses to interactions. Alfred maintains an emotional state that ' +
      'influences communication style. Use "get" to see current state, "set" to explicitly set emotion (with ' +
      'intensity 0-10 and optional trigger description), or "history" to review emotional trajectory.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'set', 'history'],
          description: 'Action to perform on emotional state',
        },
        emotion: { type: 'string', description: 'Emotion to set (e.g., excited, concerned, proud, curious)' },
        intensity: { type: 'number', description: 'Emotion intensity (0 = subtle, 10 = overwhelming)' },
        trigger: { type: 'string', description: 'What triggered this emotion' },
      },
      required: [],
    },
  },

  {
    name: 'alfred_growth_tracker',
    description:
      'Track how Alfred has grown and improved for each specific user over time. Shows capability evolution, ' +
      'new skills learned, accuracy improvements, and interaction quality trends. Use "report" for a full ' +
      'growth report, "milestones" for key achievements, or "compare" to compare periods.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['report', 'milestones', 'compare'],
          description: 'Type of growth report to generate',
        },
        period: { type: 'string', description: 'Time period for report (e.g., "week", "month", "quarter")' },
      },
      required: [],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // FLEET ORCHESTRATION — Multi-agent management, routing, and real-time ops
  // ═══════════════════════════════════════════════════════════════════════════

  {
    name: 'fleet_create',
    description:
      'Create a new fleet of AI agents with a defined strategy, agent roster, and KPIs. A fleet is a coordinated ' +
      'group of agents working together. Strategy determines how agents collaborate: "parallel" (all work simultaneously), ' +
      '"pipeline" (sequential handoff), "consensus" (agents vote on decisions), "competition" (best answer wins).',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Fleet name (unique identifier)' },
        description: { type: 'string', description: 'Fleet purpose and mission description' },
        strategy: {
          type: 'string',
          enum: ['parallel', 'pipeline', 'consensus', 'competition'],
          description: 'Collaboration strategy for agents in this fleet',
        },
        agents: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              agent_id: { type: 'string', description: 'Agent identifier' },
              role: { type: 'string', description: 'Role within the fleet' },
            },
          },
          description: 'Initial agents to add to the fleet',
        },
        kpis: {
          type: 'object',
          description: 'Key performance indicators to track (e.g., { resolution_rate: 0.95, avg_handle_time: 120 })',
        },
      },
      required: ['name'],
    },
  },

  {
    name: 'fleet_list',
    description:
      'List all fleets with a summary of each fleet\'s status, agent count, and key metrics. ' +
      'Optionally filter by status to see only active, paused, or draft fleets.',
    inputSchema: {
      type: 'object',
      properties: {
        status_filter: {
          type: 'string',
          enum: ['all', 'active', 'paused', 'draft'],
          description: 'Filter fleets by status (default: all)',
        },
      },
      required: [],
    },
  },

  {
    name: 'fleet_status',
    description:
      'Get detailed status for a specific fleet including all agents, their current tasks, health metrics, ' +
      'active calls, queue depth, and KPI performance against targets.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
      },
      required: ['fleet_id'],
    },
  },

  {
    name: 'fleet_update',
    description:
      'Update fleet configuration including name, strategy, and KPI targets. Does not affect currently running ' +
      'operations — changes take effect on new tasks and interactions.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        name: { type: 'string', description: 'New fleet name' },
        strategy: {
          type: 'string',
          enum: ['parallel', 'pipeline', 'consensus', 'competition'],
          description: 'New collaboration strategy',
        },
        kpis: { type: 'object', description: 'Updated KPI targets' },
      },
      required: ['fleet_id'],
    },
  },

  {
    name: 'fleet_delete',
    description:
      'Permanently decommission a fleet and release all agents. Active calls will be gracefully concluded. ' +
      'Requires explicit confirmation to prevent accidental deletion. Historical data is preserved.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier to delete' },
        confirm: { type: 'boolean', description: 'Must be true to confirm deletion' },
      },
      required: ['fleet_id', 'confirm'],
    },
  },

  {
    name: 'fleet_deploy',
    description:
      'Deploy a fleet to production, activating all agents and opening for incoming work. Fleet must have at ' +
      'least one agent and valid configuration. Agents will begin accepting tasks and calls immediately.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier to deploy' },
      },
      required: ['fleet_id'],
    },
  },

  {
    name: 'fleet_pause',
    description:
      'Pause all fleet operations. Active calls continue to completion but no new calls or tasks are accepted. ' +
      'Useful for maintenance, strategy changes, or incident response. Provide a reason for audit trail.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier to pause' },
        reason: { type: 'string', description: 'Reason for pausing the fleet' },
      },
      required: ['fleet_id'],
    },
  },

  {
    name: 'fleet_add_agent',
    description:
      'Add an agent to a fleet with a specific role and optional skill set. Roles determine responsibility level: ' +
      '"leader" coordinates other agents, "specialist" handles specific task types, "generalist" handles any task.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier to add' },
        role: {
          type: 'string',
          enum: ['leader', 'specialist', 'generalist'],
          description: 'Agent role within the fleet',
        },
        skills: {
          type: 'array',
          items: { type: 'string' },
          description: 'Agent skills/specializations (e.g., ["billing", "technical_support", "spanish"])',
        },
      },
      required: ['fleet_id', 'agent_id'],
    },
  },

  {
    name: 'fleet_remove_agent',
    description:
      'Remove an agent from a fleet. Agent will complete any active task before being released. ' +
      'If the agent is the fleet leader, a new leader must be promoted first.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier to remove' },
      },
      required: ['fleet_id', 'agent_id'],
    },
  },

  {
    name: 'fleet_promote_agent',
    description:
      'Promote an agent to fleet leader. The leader coordinates task distribution, makes routing decisions, ' +
      'and serves as the escalation point. Only one leader per fleet — promoting a new leader demotes the current one.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier to promote to leader' },
      },
      required: ['fleet_id', 'agent_id'],
    },
  },

  {
    name: 'fleet_agent_report',
    description:
      'Get a detailed performance report for a specific agent within a fleet. Includes call metrics, resolution ' +
      'rates, customer satisfaction scores, average handle time, and comparison to fleet averages.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier' },
        period: { type: 'string', description: 'Report period (e.g., "day", "week", "month")' },
      },
      required: ['fleet_id', 'agent_id'],
    },
  },

  {
    name: 'fleet_agent_skills',
    description:
      'Define or update an agent\'s skills and specializations within a fleet. Skills are used for intelligent ' +
      'routing — calls and tasks are matched to agents with the most relevant skill set.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier' },
        skills: {
          type: 'array',
          items: { type: 'string' },
          description: 'List of skills (e.g., ["billing", "technical", "retention", "french"])',
        },
      },
      required: ['fleet_id', 'agent_id', 'skills'],
    },
  },

  {
    name: 'fleet_agent_train',
    description:
      'Train an agent with new knowledge, examples, or corrections. Training types: "knowledge" adds factual ' +
      'information, "example" provides sample interactions for behavior modeling, "correction" teaches from mistakes.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        agent_id: { type: 'string', description: 'Agent identifier to train' },
        training_data: { type: 'string', description: 'Training content (knowledge, example dialogue, or correction)' },
        training_type: {
          type: 'string',
          enum: ['knowledge', 'example', 'correction'],
          description: 'Type of training to apply',
        },
      },
      required: ['fleet_id', 'agent_id', 'training_data'],
    },
  },

  {
    name: 'fleet_live_dashboard',
    description:
      'Get real-time fleet dashboard data including active calls, agent statuses, queue depth, current KPIs, ' +
      'and alerts. Omit fleet_id to get a unified dashboard across all fleets.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for all fleets)' },
      },
      required: [],
    },
  },

  {
    name: 'fleet_live_calls',
    description:
      'Get all currently active calls across the fleet with caller info, assigned agent, duration, topic, ' +
      'and sentiment. Provides a real-time snapshot for supervisors.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for all fleets)' },
      },
      required: [],
    },
  },

  {
    name: 'fleet_call_listen',
    description:
      'Listen to an active call in supervisor mode. Audio is streamed one-way — the caller and agent cannot hear ' +
      'the supervisor. Useful for quality assurance and training.',
    inputSchema: {
      type: 'object',
      properties: {
        call_id: { type: 'string', description: 'Active call identifier to listen to' },
      },
      required: ['call_id'],
    },
  },

  {
    name: 'fleet_call_whisper',
    description:
      'Whisper a message to an agent during an active call. Only the agent hears the whisper — the caller does not. ' +
      'Used for real-time coaching, providing information, or suggesting responses.',
    inputSchema: {
      type: 'object',
      properties: {
        call_id: { type: 'string', description: 'Active call identifier' },
        message: { type: 'string', description: 'Message to whisper to the agent' },
      },
      required: ['call_id', 'message'],
    },
  },

  {
    name: 'fleet_call_barge',
    description:
      'Barge into an active call as a supervisor. All parties (caller, agent, and supervisor) can hear and speak. ' +
      'Used for escalations or when a supervisor needs to directly address the caller.',
    inputSchema: {
      type: 'object',
      properties: {
        call_id: { type: 'string', description: 'Active call identifier to barge into' },
      },
      required: ['call_id'],
    },
  },

  {
    name: 'fleet_call_takeover',
    description:
      'Take over an active call from the current agent. The original agent is disconnected and the supervisor ' +
      'becomes the primary handler. Use for critical situations requiring immediate human or senior agent intervention.',
    inputSchema: {
      type: 'object',
      properties: {
        call_id: { type: 'string', description: 'Active call identifier to take over' },
        reason: { type: 'string', description: 'Reason for the takeover (logged for audit)' },
      },
      required: ['call_id'],
    },
  },

  {
    name: 'fleet_routing_rules',
    description:
      'Define call routing rules that determine how incoming calls are distributed to agents. Rules can match on ' +
      'caller attributes, time of day, language, topic, and priority. Use "get" to view, "set" to replace all, ' +
      '"add" to append, or "remove" to delete specific rules.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'set', 'add', 'remove'],
          description: 'Action to perform on routing rules',
        },
        rules: {
          type: 'array',
          items: {
            type: 'object',
            description: 'Routing rule with conditions and target agent/skill',
          },
          description: 'Routing rules (required for set/add/remove)',
        },
      },
      required: ['action'],
    },
  },

  {
    name: 'fleet_queue_status',
    description:
      'Get current queue status including number of waiting callers, average wait time, longest wait, ' +
      'and queue trend (growing/shrinking). Essential for capacity planning and real-time management.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for all fleets)' },
      },
      required: [],
    },
  },

  {
    name: 'fleet_queue_priority',
    description:
      'Set queue priority rules to ensure important callers are served first. Rules define conditions ' +
      '(e.g., VIP status, issue severity, wait time threshold) and priority levels.',
    inputSchema: {
      type: 'object',
      properties: {
        rules: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              condition: { type: 'string', description: 'Condition expression (e.g., "customer_tier == VIP")' },
              priority: { type: 'number', description: 'Priority level (higher = more urgent)' },
            },
          },
          description: 'Priority rules with conditions and priority levels',
        },
      },
      required: ['rules'],
    },
  },

  {
    name: 'fleet_overflow_config',
    description:
      'Configure overflow handling for when queue capacity is exceeded or wait times pass thresholds. ' +
      'Strategies include "voicemail" (take a message), "callback" (schedule a return call), and ' +
      '"redirect" (send to another fleet or external number).',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'set'],
          description: 'Get current config or set new config',
        },
        config: {
          type: 'object',
          description: 'Overflow configuration with strategy (voicemail/callback/redirect) and settings',
          properties: {
            strategy: {
              type: 'string',
              enum: ['voicemail', 'callback', 'redirect'],
              description: 'Overflow handling strategy',
            },
          },
        },
      },
      required: ['action'],
    },
  },

  {
    name: 'fleet_kpi_report',
    description:
      'Generate a comprehensive KPI report for a fleet covering the specified period. Includes resolution rate, ' +
      'average handle time, customer satisfaction, first-call resolution, and custom metrics. ' +
      'Optionally filter to specific metrics.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        period: {
          type: 'string',
          enum: ['day', 'week', 'month', 'quarter'],
          description: 'Report period',
        },
        metrics: {
          type: 'array',
          items: { type: 'string' },
          description: 'Specific metrics to include (omit for all)',
        },
      },
      required: ['fleet_id', 'period'],
    },
  },

  {
    name: 'fleet_agent_rankings',
    description:
      'Rank agents by performance across the fleet. Metrics include total calls handled, resolution rate, ' +
      'customer satisfaction, and speed (average handle time). Useful for identifying top performers and ' +
      'agents needing support.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for cross-fleet ranking)' },
        metric: {
          type: 'string',
          enum: ['calls', 'resolution', 'satisfaction', 'speed'],
          description: 'Metric to rank by (default: resolution)',
        },
      },
      required: [],
    },
  },

  {
    name: 'fleet_trend_analysis',
    description:
      'Analyze fleet performance trends over time for a specific metric. Returns trend direction, percentage ' +
      'change, anomalies, and predictions. Useful for spotting degradation or improvement patterns.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        metric: { type: 'string', description: 'Metric to analyze (e.g., "resolution_rate", "handle_time", "satisfaction")' },
        period: {
          type: 'string',
          enum: ['week', 'month', 'quarter'],
          description: 'Analysis period',
        },
      },
      required: ['fleet_id', 'metric', 'period'],
    },
  },

  {
    name: 'fleet_cost_report',
    description:
      'Generate fleet cost analysis broken down per-call, per-agent, and per-minute. Includes total spend, ' +
      'cost trends, and comparison to budget. Helps optimize fleet size and identify cost-saving opportunities.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        period: { type: 'string', description: 'Report period (e.g., "day", "week", "month")' },
      },
      required: ['fleet_id'],
    },
  },

  {
    name: 'fleet_sla_monitor',
    description:
      'Monitor SLA (Service Level Agreement) compliance in real-time. Tracks answer time, resolution time, ' +
      'abandonment rate, and custom thresholds. Returns current compliance percentage and any active violations.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for all fleets)' },
        thresholds: {
          type: 'object',
          description: 'Custom SLA thresholds (e.g., { answer_time_seconds: 30, resolution_minutes: 10 })',
        },
      },
      required: [],
    },
  },

  {
    name: 'fleet_customer_feedback',
    description:
      'Aggregate customer feedback across the fleet including satisfaction ratings, comments, and sentiment ' +
      'analysis. Returns summary statistics, common themes, and notable individual feedback items.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier (omit for all fleets)' },
        period: { type: 'string', description: 'Feedback period (e.g., "day", "week", "month")' },
      },
      required: [],
    },
  },

  {
    name: 'fleet_team_room',
    description:
      'Create a collaborative team room where AI agents and humans can interact via voice and text. ' +
      'Supports features like voice chat, text messaging, whiteboard, and screen sharing. ' +
      'Ideal for war rooms, planning sessions, and collaborative problem-solving.',
    inputSchema: {
      type: 'object',
      properties: {
        name: { type: 'string', description: 'Team room name' },
        participants: {
          type: 'array',
          items: { type: 'string' },
          description: 'Initial participant IDs (agents and/or humans)',
        },
        features: {
          type: 'array',
          items: {
            type: 'string',
            enum: ['voice', 'text', 'whiteboard', 'screenshare'],
          },
          description: 'Collaboration features to enable (default: voice + text)',
        },
      },
      required: ['name'],
    },
  },

  {
    name: 'fleet_agent_join_room',
    description:
      'Bring an AI agent into a team room as an active participant. The agent can listen, speak, ' +
      'and contribute based on its role assigned in the room context.',
    inputSchema: {
      type: 'object',
      properties: {
        room_id: { type: 'string', description: 'Team room identifier' },
        agent_id: { type: 'string', description: 'Agent identifier to add to the room' },
        role: { type: 'string', description: 'Agent role in the room (e.g., "observer", "contributor", "presenter")' },
      },
      required: ['room_id', 'agent_id'],
    },
  },

  {
    name: 'fleet_agent_briefing',
    description:
      'Have an agent brief the team on its recent work, decisions, and outcomes. Returns a structured summary ' +
      'suitable for team meetings. Format options: "summary" (quick overview), "detailed" (full narrative), ' +
      '"metrics" (numbers-focused).',
    inputSchema: {
      type: 'object',
      properties: {
        agent_id: { type: 'string', description: 'Agent identifier to brief' },
        period: { type: 'string', description: 'Period to brief on (e.g., "today", "this_week")' },
        format: {
          type: 'string',
          enum: ['summary', 'detailed', 'metrics'],
          description: 'Briefing format (default: summary)',
        },
      },
      required: ['agent_id'],
    },
  },

  {
    name: 'fleet_handoff',
    description:
      'Perform a seamless warm handoff between agents. Context from the originating agent is transferred to the ' +
      'receiving agent so the caller never has to repeat themselves. Optionally includes the active call_id ' +
      'for live call transfers.',
    inputSchema: {
      type: 'object',
      properties: {
        from_agent_id: { type: 'string', description: 'Agent handing off' },
        to_agent_id: { type: 'string', description: 'Agent receiving the handoff' },
        context: { type: 'string', description: 'Context summary for the receiving agent' },
        call_id: { type: 'string', description: 'Active call ID if transferring a live call' },
      },
      required: ['from_agent_id', 'to_agent_id'],
    },
  },

  {
    name: 'fleet_escalation_chain',
    description:
      'Define or view the escalation chain for a fleet. Each level specifies a handler type (agent, team, human, ' +
      'external) and handler ID. When an agent cannot resolve an issue, it escalates up the chain.',
    inputSchema: {
      type: 'object',
      properties: {
        action: {
          type: 'string',
          enum: ['get', 'set'],
          description: 'Get current chain or set a new one',
        },
        chain: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              level: { type: 'number', description: 'Escalation level (1 = first escalation)' },
              handler_type: {
                type: 'string',
                enum: ['agent', 'team', 'human', 'external'],
                description: 'Type of handler at this level',
              },
              handler_id: { type: 'string', description: 'Identifier of the handler' },
            },
          },
          description: 'Escalation chain levels (required for "set")',
        },
      },
      required: ['action'],
    },
  },

  {
    name: 'fleet_schedule',
    description:
      'Schedule fleet operations including agent shift patterns, availability windows, and time-based scaling. ' +
      'Use "get" to view current schedule or "set" to define new shift patterns.',
    inputSchema: {
      type: 'object',
      properties: {
        fleet_id: { type: 'string', description: 'Fleet identifier' },
        action: {
          type: 'string',
          enum: ['get', 'set'],
          description: 'Get current schedule or set a new one',
        },
        schedule: {
          type: 'object',
          description: 'Schedule configuration with shift patterns, timezone, and availability rules',
        },
      },
      required: ['fleet_id', 'action'],
    },
  },

  // ═══════════════════════════════════════════════════════════════════════════
  // STUDENTS K-12 — Homework, tutoring, study tools for children and teens
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'homework_helper', description: 'Help students solve homework step-by-step with explanations, not just answers. Supports math, science, history, English, and more. Teaches the process so students learn how to solve problems independently.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Subject area (math, science, history, english, etc.)' }, question: { type: 'string', description: 'The homework question or problem' }, grade_level: { type: 'number', description: 'Student grade level (1-12)' }, show_steps: { type: 'boolean', description: 'Show step-by-step solution (default: true)' } }, required: ['subject', 'question'] } },
  { name: 'math_tutor', description: 'Interactive math tutoring with visual explanations, practice problems, and adaptive difficulty. Covers arithmetic through calculus. Adjusts complexity to student level and provides encouragement.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Math topic (fractions, algebra, geometry, calculus, etc.)' }, difficulty: { type: 'string', enum: ['easy', 'medium', 'hard'], description: 'Difficulty level' }, action: { type: 'string', enum: ['explain', 'practice', 'quiz', 'solve'], description: 'What to do' }, problem: { type: 'string', description: 'Specific problem to solve (for solve action)' } }, required: ['topic', 'action'] } },
  { name: 'science_lab_simulator', description: 'Virtual science experiments with step-by-step procedures, observations, and conclusions. Covers chemistry, physics, biology. Safe way to learn lab techniques and scientific method.', inputSchema: { type: 'object', properties: { subject: { type: 'string', enum: ['chemistry', 'physics', 'biology', 'earth_science'] }, experiment: { type: 'string', description: 'Experiment name or concept to explore' }, grade_level: { type: 'number', description: 'Student grade level' } }, required: ['subject', 'experiment'] } },
  { name: 'essay_coach', description: 'Essay writing assistant that guides through the full writing process: brainstorm, outline, draft, revise, cite sources. Provides feedback on structure, grammar, and argumentation without writing the essay for the student.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['brainstorm', 'outline', 'draft_feedback', 'revise', 'cite'], description: 'Writing stage' }, topic: { type: 'string', description: 'Essay topic or prompt' }, content: { type: 'string', description: 'Current essay draft (for feedback/revise)' }, essay_type: { type: 'string', enum: ['narrative', 'persuasive', 'expository', 'descriptive', 'research'], description: 'Type of essay' } }, required: ['action', 'topic'] } },
  { name: 'flashcard_creator', description: 'Create smart flashcards with spaced repetition scheduling. Generates cards from any content, tracks mastery, and optimizes review timing for maximum retention. Supports text, images, and multilingual cards.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'study', 'list', 'stats'], description: 'Action to perform' }, subject: { type: 'string', description: 'Subject or deck name' }, content: { type: 'string', description: 'Content to create flashcards from (for create action)' }, card_count: { type: 'number', description: 'Number of cards to generate' } }, required: ['action'] } },
  { name: 'quiz_generator', description: 'Generate practice quizzes from any subject material. Creates multiple question types: multiple choice, true/false, fill-in-blank, short answer. Auto-grades and explains correct answers.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Subject area' }, topic: { type: 'string', description: 'Specific topic within subject' }, question_count: { type: 'number', description: 'Number of questions (default: 10)' }, question_types: { type: 'array', items: { type: 'string', enum: ['multiple_choice', 'true_false', 'fill_blank', 'short_answer'] }, description: 'Types of questions to include' }, difficulty: { type: 'string', enum: ['easy', 'medium', 'hard'] } }, required: ['subject', 'topic'] } },
  { name: 'study_plan_builder', description: 'Build personalized study plans with time blocks, breaks, and topic rotation. Adapts to student schedule, learning pace, and upcoming test dates. Includes progress tracking.', inputSchema: { type: 'object', properties: { subjects: { type: 'array', items: { type: 'string' }, description: 'Subjects to study' }, hours_per_day: { type: 'number', description: 'Available study hours per day' }, test_date: { type: 'string', description: 'Upcoming test date (YYYY-MM-DD)' }, learning_style: { type: 'string', enum: ['visual', 'auditory', 'reading', 'kinesthetic'] } }, required: ['subjects'] } },
  { name: 'reading_level_analyzer', description: 'Analyze text reading level using Flesch-Kincaid, Gunning Fog, and other metrics. Suggests simpler alternatives for difficult passages. Helps teachers and parents find age-appropriate materials.', inputSchema: { type: 'object', properties: { text: { type: 'string', description: 'Text to analyze' }, target_grade: { type: 'number', description: 'Target grade level for suggestions' } }, required: ['text'] } },
  { name: 'vocabulary_builder', description: 'Build vocabulary with context, etymology, usage examples, and memory tricks. Creates personalized word lists based on reading level and subjects. Tracks mastery with spaced repetition.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['learn', 'quiz', 'list', 'define'], description: 'Action to perform' }, words: { type: 'array', items: { type: 'string' }, description: 'Words to learn or define' }, grade_level: { type: 'number', description: 'Student grade level' }, subject: { type: 'string', description: 'Subject area for contextual vocabulary' } }, required: ['action'] } },
  { name: 'book_report_helper', description: 'Guide students through book report structure: summary, characters, themes, analysis. Provides scaffolding prompts that help students think critically without doing the work for them.', inputSchema: { type: 'object', properties: { book_title: { type: 'string', description: 'Title of the book' }, author: { type: 'string', description: 'Author name' }, section: { type: 'string', enum: ['summary', 'characters', 'themes', 'analysis', 'review', 'full'], description: 'Section to work on' } }, required: ['book_title', 'section'] } },
  { name: 'history_timeline', description: 'Create interactive history timelines showing events, connections, and cause-effect relationships. Covers world history, national history, and specific eras. Visual learning for historical context.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Historical topic or period' }, start_year: { type: 'number', description: 'Start year for timeline' }, end_year: { type: 'number', description: 'End year for timeline' }, focus: { type: 'string', enum: ['political', 'cultural', 'scientific', 'military', 'all'] } }, required: ['topic'] } },
  { name: 'geography_explorer', description: 'Interactive geography with maps, facts, demographics, and comparisons. Learn about countries, capitals, landmarks, cultures, and climate. Supports quiz mode for test preparation.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['explore', 'compare', 'quiz', 'facts'], description: 'Action to perform' }, location: { type: 'string', description: 'Country, city, or region to explore' }, compare_with: { type: 'string', description: 'Location to compare with' } }, required: ['action', 'location'] } },
  { name: 'safe_web_search', description: 'Kid-safe web search with content filtering, age-appropriate results, and educational focus. Blocks inappropriate content while providing useful research results for school projects.', inputSchema: { type: 'object', properties: { query: { type: 'string', description: 'Search query' }, age_group: { type: 'string', enum: ['5-8', '9-12', '13-15', '16-18'], description: 'Age group for content filtering' }, type: { type: 'string', enum: ['general', 'images', 'videos', 'educational'] } }, required: ['query'] } },
  { name: 'parent_progress_report', description: 'Generate progress reports showing what the student has been learning, study habits, strengths, and areas needing attention. Designed for parent review with actionable recommendations.', inputSchema: { type: 'object', properties: { student_name: { type: 'string', description: 'Student name' }, period: { type: 'string', enum: ['week', 'month', 'semester'], description: 'Report period' }, subjects: { type: 'array', items: { type: 'string' }, description: 'Subjects to include' } }, required: ['student_name'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // UNIVERSITY/COLLEGE — Research, academic writing, and scholarly tools
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'citation_generator', description: 'Generate accurate citations in any format: APA 7th, MLA 9th, Chicago, IEEE, Harvard, Vancouver. Supports books, journals, websites, videos, and more. Builds bibliography automatically.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['cite', 'bibliography', 'format_check'], description: 'Action to perform' }, format: { type: 'string', enum: ['apa', 'mla', 'chicago', 'ieee', 'harvard', 'vancouver'], description: 'Citation format' }, source: { type: 'object', description: 'Source details (title, author, year, url, etc.)' }, text: { type: 'string', description: 'Text with citations to format-check' } }, required: ['action', 'format'] } },
  { name: 'literature_review', description: 'Search and synthesize academic papers on a topic. Identifies key themes, methodological approaches, gaps in research, and generates structured literature review sections.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Research topic for literature review' }, scope: { type: 'string', enum: ['narrow', 'moderate', 'broad'], description: 'Scope of review' }, max_sources: { type: 'number', description: 'Maximum sources to include' }, focus: { type: 'string', description: 'Specific aspect to focus on' } }, required: ['topic'] } },
  { name: 'thesis_outline', description: 'Generate thesis or dissertation outlines with chapter structure, research questions, methodology framework, and literature review plan. Adapts to discipline-specific conventions.', inputSchema: { type: 'object', properties: { title: { type: 'string', description: 'Thesis title or working title' }, discipline: { type: 'string', description: 'Academic discipline (CS, Psychology, Engineering, etc.)' }, degree: { type: 'string', enum: ['bachelors', 'masters', 'phd'], description: 'Degree level' }, research_questions: { type: 'array', items: { type: 'string' }, description: 'Research questions (if defined)' } }, required: ['title', 'discipline'] } },
  { name: 'statistical_analysis', description: 'Run statistical tests and explain results in plain language. Supports t-test, ANOVA, chi-square, regression, correlation, and more. Generates interpretation and visualization suggestions.', inputSchema: { type: 'object', properties: { test: { type: 'string', enum: ['t_test', 'anova', 'chi_square', 'regression', 'correlation', 'descriptive', 'normality'], description: 'Statistical test to run' }, data: { type: 'object', description: 'Data for analysis' }, significance_level: { type: 'number', description: 'Alpha level (default: 0.05)' } }, required: ['test', 'data'] } },
  { name: 'research_methodology', description: 'Suggest and design research methodology based on research question. Covers qualitative, quantitative, and mixed methods. Includes sampling strategy, data collection, and analysis plan.', inputSchema: { type: 'object', properties: { research_question: { type: 'string', description: 'The research question to address' }, discipline: { type: 'string', description: 'Academic discipline' }, constraints: { type: 'object', description: 'Constraints like time, budget, access to participants' } }, required: ['research_question'] } },
  { name: 'peer_review_simulator', description: 'Simulate academic peer review feedback on papers. Evaluates methodology, argumentation, evidence quality, writing clarity, and formatting. Provides constructive criticism like a real reviewer.', inputSchema: { type: 'object', properties: { paper: { type: 'string', description: 'Paper text or abstract to review' }, discipline: { type: 'string', description: 'Academic discipline' }, review_focus: { type: 'array', items: { type: 'string', enum: ['methodology', 'argumentation', 'evidence', 'writing', 'formatting', 'all'] } } }, required: ['paper'] } },
  { name: 'gpa_calculator', description: 'Calculate GPA with what-if scenarios. Supports 4.0 and percentage scales, weighted/unweighted, and cumulative calculations. Shows what grades you need to reach target GPA.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['calculate', 'what_if', 'target'], description: 'Calculation mode' }, courses: { type: 'array', items: { type: 'object' }, description: 'Array of {name, credits, grade} objects' }, current_gpa: { type: 'number', description: 'Current cumulative GPA (for what_if/target)' }, target_gpa: { type: 'number', description: 'Target GPA (for target mode)' }, credits_completed: { type: 'number', description: 'Total credits already completed' } }, required: ['action'] } },
  { name: 'course_planner', description: 'Plan courses across semesters to meet degree requirements efficiently. Tracks prerequisites, credit limits, and graduation timeline. Identifies the optimal path to degree completion.', inputSchema: { type: 'object', properties: { degree_program: { type: 'string', description: 'Degree program name' }, completed_courses: { type: 'array', items: { type: 'string' }, description: 'Already completed courses' }, remaining_semesters: { type: 'number', description: 'Semesters remaining' }, preferences: { type: 'object', description: 'Scheduling preferences (max credits, day preferences)' } }, required: ['degree_program'] } },
  { name: 'lab_report_formatter', description: 'Format lab reports with proper scientific structure: title, abstract, introduction, methods, results, discussion, references. Discipline-specific formatting for chemistry, physics, biology, engineering.', inputSchema: { type: 'object', properties: { discipline: { type: 'string', description: 'Scientific discipline' }, sections: { type: 'object', description: 'Lab report sections with content' }, format: { type: 'string', enum: ['standard', 'apa', 'ieee'], description: 'Formatting style' } }, required: ['discipline'] } },
  { name: 'study_group_coordinator', description: 'Coordinate study group logistics: find common free times, create study agendas, distribute topics, and share materials. Generates study guides from group notes.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['schedule', 'agenda', 'distribute', 'guide'], description: 'Coordination action' }, members: { type: 'array', items: { type: 'string' }, description: 'Group member names' }, subject: { type: 'string', description: 'Subject being studied' }, topics: { type: 'array', items: { type: 'string' }, description: 'Topics to cover' } }, required: ['action', 'subject'] } },
  { name: 'exam_prep', description: 'Generate exam preparation materials from course notes, textbook chapters, or topics. Creates practice tests, key concept summaries, and memory aids. Adapts to exam format (essay, MC, practical).', inputSchema: { type: 'object', properties: { course: { type: 'string', description: 'Course name' }, topics: { type: 'array', items: { type: 'string' }, description: 'Topics to prepare for' }, exam_format: { type: 'string', enum: ['multiple_choice', 'essay', 'practical', 'mixed'] }, notes: { type: 'string', description: 'Course notes to generate prep materials from' } }, required: ['course'] } },
  { name: 'academic_integrity_check', description: 'Check academic work for integrity issues: proper attribution, citation completeness, paraphrasing quality, and potential concerns. Educational tool that teaches proper academic practices.', inputSchema: { type: 'object', properties: { text: { type: 'string', description: 'Text to check' }, type: { type: 'string', enum: ['essay', 'report', 'thesis', 'presentation'] }, citation_format: { type: 'string', description: 'Expected citation format' } }, required: ['text'] } },
  { name: 'grant_proposal_writer', description: 'Help write research grant proposals with proper structure: abstract, significance, methodology, budget justification, timeline. Adapts to funding agency requirements.', inputSchema: { type: 'object', properties: { title: { type: 'string', description: 'Project title' }, agency: { type: 'string', description: 'Funding agency (NSERC, SSHRC, NIH, NSF, etc.)' }, amount: { type: 'number', description: 'Requested amount' }, duration: { type: 'string', description: 'Project duration' }, section: { type: 'string', enum: ['abstract', 'significance', 'methodology', 'budget', 'timeline', 'full'], description: 'Section to work on' } }, required: ['title', 'section'] } },
  { name: 'conference_paper_prep', description: 'Prepare and format conference papers according to venue-specific requirements. Covers abstract submission, paper formatting, presentation slides, and poster creation.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['format', 'abstract', 'slides', 'poster'], description: 'Preparation task' }, venue: { type: 'string', description: 'Conference or journal name' }, paper: { type: 'string', description: 'Paper content' }, format: { type: 'string', enum: ['ieee', 'acm', 'springer', 'elsevier'], description: 'Required format' } }, required: ['action'] } },
  { name: 'scholarship_finder', description: 'Find relevant scholarships based on student profile, achievements, field of study, and demographics. Filters by eligibility, deadline, and amount. Helps with application essays.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['search', 'match', 'essay_help'], description: 'Action to perform' }, field: { type: 'string', description: 'Field of study' }, level: { type: 'string', enum: ['undergraduate', 'graduate', 'phd', 'postdoc'] }, country: { type: 'string', description: 'Country (default: Canada)' }, demographics: { type: 'object', description: 'Demographic info for matching (optional)' } }, required: ['action', 'field'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // PROFESSIONALS — Productivity, meetings, project management
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'meeting_summarizer', description: 'Transcribe and summarize meetings with action items, decisions made, and key discussion points. Identifies speakers and assigns follow-up tasks. Works from audio, text, or notes.', inputSchema: { type: 'object', properties: { content: { type: 'string', description: 'Meeting transcript, notes, or audio URL' }, format: { type: 'string', enum: ['summary', 'minutes', 'action_items', 'full'], description: 'Output format' }, attendees: { type: 'array', items: { type: 'string' }, description: 'List of attendees' } }, required: ['content'] } },
  { name: 'presentation_builder', description: 'Create slide deck outlines and content from topics, documents, or briefs. Generates speaker notes, suggests visuals, and structures for maximum impact. Outputs markdown slide format.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Presentation topic' }, audience: { type: 'string', description: 'Target audience' }, slide_count: { type: 'number', description: 'Number of slides (default: 10)' }, style: { type: 'string', enum: ['professional', 'creative', 'data_heavy', 'storytelling'], description: 'Presentation style' }, content: { type: 'string', description: 'Source content to build from' } }, required: ['topic'] } },
  { name: 'calendar_optimizer', description: 'Analyze calendar and suggest optimizations: consolidate meetings, protect focus time, reduce context switching, and identify scheduling inefficiencies.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['analyze', 'optimize', 'suggest_blocks', 'meeting_audit'], description: 'Optimization action' }, schedule: { type: 'object', description: 'Calendar data or preferences' }, preferences: { type: 'object', description: 'Focus time preferences, meeting limits, etc.' } }, required: ['action'] } },
  { name: 'okr_tracker', description: 'Track Objectives and Key Results with progress calculations, confidence levels, and trend analysis. Supports company, team, and individual OKRs with alignment mapping.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'update', 'status', 'list', 'report'], description: 'OKR action' }, objective: { type: 'string', description: 'Objective text' }, key_results: { type: 'array', items: { type: 'object' }, description: 'Key results with target and current values' }, period: { type: 'string', description: 'OKR period (Q1 2026, etc.)' } }, required: ['action'] } },
  { name: 'standup_generator', description: 'Generate daily standup reports from recent work activity. Summarizes what was done yesterday, plans for today, and blockers. Can pull from git commits, task boards, and calendar.', inputSchema: { type: 'object', properties: { source: { type: 'string', enum: ['manual', 'git', 'tasks', 'auto'], description: 'Data source for standup' }, yesterday: { type: 'string', description: 'What was completed yesterday (for manual)' }, today: { type: 'string', description: 'Plans for today (for manual)' }, blockers: { type: 'string', description: 'Current blockers (for manual)' } }, required: ['source'] } },
  { name: 'decision_matrix', description: 'Build weighted decision matrices for complex choices. Define options, criteria, weights, and scores. Alfred can suggest criteria and weights based on the decision context.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'suggest', 'evaluate'], description: 'Matrix action' }, decision: { type: 'string', description: 'Decision to make' }, options: { type: 'array', items: { type: 'string' }, description: 'Options to evaluate' }, criteria: { type: 'array', items: { type: 'object' }, description: 'Evaluation criteria with weights' } }, required: ['action', 'decision'] } },
  { name: 'project_estimator', description: 'Estimate project timelines based on scope, complexity, team size, and historical data. Uses PERT, three-point estimation, and Monte Carlo simulation for accuracy ranges.', inputSchema: { type: 'object', properties: { project: { type: 'string', description: 'Project description' }, tasks: { type: 'array', items: { type: 'object' }, description: 'Task breakdown with estimates' }, team_size: { type: 'number', description: 'Team size' }, methodology: { type: 'string', enum: ['agile', 'waterfall', 'kanban'] } }, required: ['project'] } },
  { name: 'sprint_planner', description: 'Plan agile sprints with story point allocation, velocity calculation, and capacity planning. Suggests sprint goals and identifies overcommitment risks.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['plan', 'velocity', 'capacity', 'retrospective'], description: 'Sprint planning action' }, sprint_length: { type: 'number', description: 'Sprint length in days (default: 14)' }, team_velocity: { type: 'number', description: 'Average velocity in story points' }, backlog: { type: 'array', items: { type: 'object' }, description: 'Backlog items with story points' } }, required: ['action'] } },
  { name: 'retrospective_facilitator', description: 'Facilitate team retrospectives with structured formats: Start/Stop/Continue, Mad/Sad/Glad, 4Ls, Sailboat. Generates summaries with prioritized action items.', inputSchema: { type: 'object', properties: { format: { type: 'string', enum: ['start_stop_continue', 'mad_sad_glad', 'four_ls', 'sailboat', 'custom'], description: 'Retrospective format' }, entries: { type: 'array', items: { type: 'object' }, description: 'Team entries/feedback' }, sprint: { type: 'string', description: 'Sprint identifier' } }, required: ['format'] } },
  { name: 'risk_register', description: 'Maintain and analyze project risk register. Track risks with probability, impact, mitigation strategies, and owners. Generates risk heat maps and trending reports.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'update', 'list', 'analyze', 'report'], description: 'Risk register action' }, risk: { type: 'object', description: 'Risk details (description, probability, impact, mitigation)' }, project: { type: 'string', description: 'Project name' } }, required: ['action'] } },
  { name: 'stakeholder_mapper', description: 'Map stakeholders by influence, interest, and communication needs. Creates stakeholder matrices, RACI charts, and communication plans. Identifies key relationships and potential conflicts.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['map', 'raci', 'communication_plan', 'analyze'], description: 'Mapping action' }, stakeholders: { type: 'array', items: { type: 'object' }, description: 'Stakeholder list with roles' }, project: { type: 'string', description: 'Project name' } }, required: ['action'] } },
  { name: 'competitive_analysis', description: 'Analyze competitors with feature matrices, SWOT comparisons, market positioning, and differentiation opportunities. Generates actionable competitive intelligence reports.', inputSchema: { type: 'object', properties: { company: { type: 'string', description: 'Your company/product' }, competitors: { type: 'array', items: { type: 'string' }, description: 'Competitor names' }, analysis_type: { type: 'string', enum: ['feature_matrix', 'swot', 'positioning', 'full'], description: 'Type of analysis' }, industry: { type: 'string', description: 'Industry context' } }, required: ['company', 'competitors'] } },
  { name: 'swot_analysis', description: 'Generate SWOT analysis with AI-powered insights. Identifies Strengths, Weaknesses, Opportunities, and Threats with supporting evidence and strategic recommendations.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Company, product, or project to analyze' }, context: { type: 'string', description: 'Additional context (industry, market, situation)' }, existing_data: { type: 'object', description: 'Any known SWOT elements to include' } }, required: ['subject'] } },
  { name: 'business_case_builder', description: 'Build comprehensive business cases with problem statement, proposed solution, cost-benefit analysis, ROI projections, risk assessment, and implementation timeline.', inputSchema: { type: 'object', properties: { title: { type: 'string', description: 'Business case title' }, problem: { type: 'string', description: 'Problem statement' }, solution: { type: 'string', description: 'Proposed solution' }, investment: { type: 'number', description: 'Required investment amount' }, section: { type: 'string', enum: ['problem', 'solution', 'financials', 'risks', 'timeline', 'full'], description: 'Section to generate' } }, required: ['title', 'section'] } },
  { name: 'executive_summary', description: 'Generate executive summaries from detailed documents, reports, or data. Distills key findings, recommendations, and action items into concise format for leadership review.', inputSchema: { type: 'object', properties: { content: { type: 'string', description: 'Full document or report content' }, max_length: { type: 'number', description: 'Maximum summary length in words' }, audience: { type: 'string', enum: ['c_suite', 'board', 'investors', 'team_leads'], description: 'Target audience' } }, required: ['content'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // SMALL BUSINESS — Accounting, CRM, inventory, operations
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'bookkeeping', description: 'Track business income, expenses, and generate financial reports. Categorize transactions, calculate profit/loss, and prepare for tax season. Supports CAD and USD.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['record', 'report', 'categorize', 'reconcile', 'tax_summary'], description: 'Bookkeeping action' }, transaction: { type: 'object', description: 'Transaction details (amount, category, date, description)' }, period: { type: 'string', description: 'Report period (month, quarter, year)' }, currency: { type: 'string', enum: ['CAD', 'USD', 'EUR'], description: 'Currency' } }, required: ['action'] } },
  { name: 'invoice_creator', description: 'Create and send professional invoices with payment links. Supports recurring invoices, late payment reminders, and tax calculations. Tracks payment status.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'send', 'list', 'status', 'remind'], description: 'Invoice action' }, client: { type: 'object', description: 'Client details (name, email, address)' }, items: { type: 'array', items: { type: 'object' }, description: 'Line items (description, quantity, rate)' }, tax_rate: { type: 'number', description: 'Tax rate percentage' }, due_days: { type: 'number', description: 'Payment due in days (default: 30)' } }, required: ['action'] } },
  { name: 'payroll_calculator', description: 'Calculate payroll including tax deductions for Canada (CPP, EI, federal/provincial tax) and US (FICA, federal/state). Generates pay stubs and T4/W2 summaries.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['calculate', 'pay_stub', 'annual_summary', 'deductions'], description: 'Payroll action' }, employee: { type: 'object', description: 'Employee details (name, salary, province/state)' }, pay_period: { type: 'string', enum: ['weekly', 'biweekly', 'semimonthly', 'monthly'] }, country: { type: 'string', enum: ['CA', 'US'] } }, required: ['action', 'employee'] } },
  { name: 'inventory_tracker', description: 'Track inventory levels with low-stock alerts, reorder points, and stock valuation. Supports multiple locations, SKUs, and batch tracking. Generates inventory reports.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'remove', 'adjust', 'list', 'low_stock', 'report', 'value'], description: 'Inventory action' }, item: { type: 'object', description: 'Item details (sku, name, quantity, cost, location)' }, threshold: { type: 'number', description: 'Low stock alert threshold' } }, required: ['action'] } },
  { name: 'crm_contact_manager', description: 'Manage customer relationships with contact profiles, interaction history, notes, and follow-up reminders. Score leads, track pipeline stages, and forecast revenue.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'update', 'search', 'list', 'pipeline', 'follow_ups', 'report'], description: 'CRM action' }, contact: { type: 'object', description: 'Contact details (name, email, phone, company, notes)' }, stage: { type: 'string', enum: ['lead', 'prospect', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'] } }, required: ['action'] } },
  { name: 'quote_generator', description: 'Generate professional quotes and estimates with line items, terms, and validity period. Supports templates, discount rules, and conversion tracking from quote to invoice.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'list', 'convert_to_invoice', 'template'], description: 'Quote action' }, client: { type: 'object', description: 'Client details' }, items: { type: 'array', items: { type: 'object' }, description: 'Line items with pricing' }, valid_days: { type: 'number', description: 'Quote validity in days (default: 30)' } }, required: ['action'] } },
  { name: 'expense_tracker', description: 'Track business expenses with receipt scanning, category tagging, and reimbursement status. Integrates with bookkeeping for automatic financial reporting.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['record', 'list', 'report', 'categories', 'receipts'], description: 'Expense action' }, expense: { type: 'object', description: 'Expense details (amount, category, date, vendor, receipt)' }, period: { type: 'string', description: 'Report period' } }, required: ['action'] } },
  { name: 'tax_prep', description: 'Prepare tax summaries with deduction suggestions for Canadian and US businesses. Calculates HST/GST/PST or sales tax, identifies eligible deductions, and generates filing worksheets.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['summary', 'deductions', 'hst_report', 'filing_checklist', 'estimate'], description: 'Tax prep action' }, business_type: { type: 'string', enum: ['sole_proprietor', 'corporation', 'partnership'] }, country: { type: 'string', enum: ['CA', 'US'] }, tax_year: { type: 'number', description: 'Tax year' } }, required: ['action'] } },
  { name: 'cash_flow_forecast', description: 'Forecast cash flow based on receivables, payables, recurring revenue, and seasonal patterns. Identifies potential cash crunches and suggests timing optimizations.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['forecast', 'scenario', 'report'], description: 'Forecast action' }, months_ahead: { type: 'number', description: 'Forecast period in months (default: 6)' }, revenue: { type: 'object', description: 'Revenue data and projections' }, expenses: { type: 'object', description: 'Expense data and projections' } }, required: ['action'] } },
  { name: 'employee_scheduler', description: 'Create employee schedules with availability constraints, role requirements, and labor law compliance. Handles shift swaps, overtime calculation, and coverage optimization.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'optimize', 'swap', 'coverage', 'overtime_report'], description: 'Scheduling action' }, employees: { type: 'array', items: { type: 'object' }, description: 'Employee list with availability and roles' }, period: { type: 'string', description: 'Schedule period (week, month)' }, requirements: { type: 'object', description: 'Minimum coverage requirements per shift' } }, required: ['action'] } },
  { name: 'customer_survey', description: 'Create and distribute customer satisfaction surveys. Supports NPS, CSAT, and custom question formats. Analyzes responses with sentiment analysis and trend tracking.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'distribute', 'analyze', 'report'], description: 'Survey action' }, survey_type: { type: 'string', enum: ['nps', 'csat', 'custom'] }, questions: { type: 'array', items: { type: 'object' }, description: 'Survey questions' }, recipients: { type: 'array', items: { type: 'string' }, description: 'Email addresses to send to' } }, required: ['action'] } },
  { name: 'competitor_price_monitor', description: 'Monitor competitor pricing changes and market positioning. Track price histories, identify trends, and get alerts when competitors change prices. Compare your pricing strategy.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['track', 'compare', 'alert', 'report', 'history'], description: 'Monitoring action' }, competitors: { type: 'array', items: { type: 'object' }, description: 'Competitor products with URLs or names' }, your_products: { type: 'array', items: { type: 'object' }, description: 'Your products for comparison' } }, required: ['action'] } },
  { name: 'social_media_scheduler', description: 'Schedule and manage social media posts across platforms. Suggests optimal posting times, generates hashtags, and tracks engagement. Supports batch scheduling and content calendars.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['schedule', 'bulk_schedule', 'suggest_times', 'calendar', 'analytics'], description: 'Scheduling action' }, platform: { type: 'string', enum: ['twitter', 'linkedin', 'instagram', 'facebook', 'tiktok', 'all'] }, content: { type: 'string', description: 'Post content' }, scheduled_time: { type: 'string', description: 'Scheduled publish time (ISO 8601)' } }, required: ['action'] } },
  { name: 'review_responder', description: 'Generate professional responses to customer reviews (positive and negative). Maintains brand voice, addresses concerns, and turns negative experiences into opportunities.', inputSchema: { type: 'object', properties: { review: { type: 'string', description: 'Customer review text' }, rating: { type: 'number', description: 'Star rating (1-5)' }, platform: { type: 'string', enum: ['google', 'yelp', 'facebook', 'trustpilot', 'other'] }, tone: { type: 'string', enum: ['professional', 'friendly', 'empathetic', 'apologetic'] } }, required: ['review'] } },
  { name: 'business_plan_writer', description: 'Write comprehensive business plans with executive summary, market analysis, competitive landscape, financial projections, and operational plan. Investor-ready formatting.', inputSchema: { type: 'object', properties: { business_name: { type: 'string', description: 'Business name' }, industry: { type: 'string', description: 'Industry/sector' }, section: { type: 'string', enum: ['executive_summary', 'market_analysis', 'competitive', 'financial', 'operations', 'marketing', 'full'], description: 'Section to generate' }, existing_data: { type: 'object', description: 'Existing business data to incorporate' } }, required: ['business_name', 'section'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // CONTENT CREATORS — YouTube, podcasts, social media, streaming
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'youtube_script_writer', description: 'Write YouTube video scripts with hooks, structure, B-roll suggestions, and CTAs. Optimizes for retention with pattern interrupts and engagement techniques.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Video topic' }, style: { type: 'string', enum: ['educational', 'entertainment', 'tutorial', 'review', 'vlog', 'commentary'] }, duration: { type: 'number', description: 'Target duration in minutes' }, audience: { type: 'string', description: 'Target audience description' } }, required: ['topic'] } },
  { name: 'thumbnail_designer', description: 'Design YouTube thumbnail concepts with text overlay suggestions, color contrasts, face placement, and A/B test variants. Generates detailed design briefs.', inputSchema: { type: 'object', properties: { title: { type: 'string', description: 'Video title' }, style: { type: 'string', enum: ['bold_text', 'reaction', 'before_after', 'minimal', 'clickbait'] }, colors: { type: 'array', items: { type: 'string' }, description: 'Preferred colors' }, elements: { type: 'array', items: { type: 'string' }, description: 'Elements to include (face, arrow, emoji, etc.)' } }, required: ['title'] } },
  { name: 'podcast_show_notes', description: 'Generate podcast show notes with timestamps, key quotes, guest bios, resource links, and social media snippets. Creates structured notes for any podcast format.', inputSchema: { type: 'object', properties: { title: { type: 'string', description: 'Episode title' }, transcript: { type: 'string', description: 'Episode transcript or summary' }, guest: { type: 'object', description: 'Guest details (name, bio, links)' }, format: { type: 'string', enum: ['detailed', 'summary', 'timestamps', 'social'] } }, required: ['title'] } },
  { name: 'social_post_generator', description: 'Generate platform-specific social media posts with optimal formatting, hashtags, and engagement hooks. Adapts tone and length for each platform.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Post topic or content to promote' }, platform: { type: 'string', enum: ['twitter', 'linkedin', 'instagram', 'facebook', 'tiktok', 'threads'] }, tone: { type: 'string', enum: ['professional', 'casual', 'humorous', 'inspirational', 'controversial'] }, include_hashtags: { type: 'boolean', description: 'Include relevant hashtags' } }, required: ['topic', 'platform'] } },
  { name: 'content_calendar', description: 'Plan content calendar across all platforms with themed days, posting frequency, and content pillars. Ensures consistent publishing and diverse content mix.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'view', 'suggest', 'analyze'], description: 'Calendar action' }, platforms: { type: 'array', items: { type: 'string' }, description: 'Target platforms' }, pillars: { type: 'array', items: { type: 'string' }, description: 'Content pillars/categories' }, weeks: { type: 'number', description: 'Number of weeks to plan' } }, required: ['action'] } },
  { name: 'hashtag_optimizer', description: 'Find optimal hashtags for each platform and niche. Analyzes competition, reach, and relevance. Suggests mix of high, medium, and low competition tags for maximum discovery.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Content topic' }, platform: { type: 'string', enum: ['instagram', 'twitter', 'tiktok', 'linkedin', 'youtube'] }, niche: { type: 'string', description: 'Content niche' }, count: { type: 'number', description: 'Number of hashtags to suggest (default: 30)' } }, required: ['topic', 'platform'] } },
  { name: 'video_idea_generator', description: 'Generate video content ideas based on trending topics, niche analysis, and audience interests. Scores ideas by potential reach, competition, and production difficulty.', inputSchema: { type: 'object', properties: { niche: { type: 'string', description: 'Content niche' }, platform: { type: 'string', enum: ['youtube', 'tiktok', 'instagram', 'shorts'] }, count: { type: 'number', description: 'Number of ideas to generate (default: 10)' }, style: { type: 'string', description: 'Preferred content style' } }, required: ['niche'] } },
  { name: 'sponsor_pitch', description: 'Create sponsorship pitch decks with media kit data, audience demographics, rate cards, and collaboration proposals. Professional pitches that land brand deals.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['pitch', 'media_kit', 'rate_card', 'proposal'], description: 'Pitch action' }, brand: { type: 'string', description: 'Brand to pitch to' }, channel_stats: { type: 'object', description: 'Channel statistics (subscribers, views, demographics)' }, collaboration_type: { type: 'string', enum: ['sponsored_video', 'product_review', 'brand_ambassador', 'affiliate'] } }, required: ['action'] } },
  { name: 'analytics_reporter', description: 'Analyze YouTube, social media, and podcast analytics. Identifies trends, top-performing content, audience insights, and growth opportunities with actionable recommendations.', inputSchema: { type: 'object', properties: { platform: { type: 'string', enum: ['youtube', 'instagram', 'tiktok', 'podcast', 'twitter', 'all'] }, data: { type: 'object', description: 'Analytics data to analyze' }, period: { type: 'string', description: 'Analysis period' }, focus: { type: 'string', enum: ['growth', 'engagement', 'revenue', 'content_performance', 'full'] } }, required: ['platform'] } },
  { name: 'caption_generator', description: 'Generate captions and subtitles from text content. Formats for YouTube, Instagram Reels, TikTok, and podcast transcripts. Supports SRT, VTT, and plain text formats.', inputSchema: { type: 'object', properties: { content: { type: 'string', description: 'Content to generate captions from' }, format: { type: 'string', enum: ['srt', 'vtt', 'plain', 'instagram', 'tiktok'], description: 'Caption format' }, language: { type: 'string', description: 'Caption language (default: en)' } }, required: ['content'] } },
  { name: 'content_repurposer', description: 'Repurpose long-form content into multiple formats: YouTube videos into shorts, blog posts into social threads, podcasts into articles, articles into carousels.', inputSchema: { type: 'object', properties: { source_content: { type: 'string', description: 'Original long-form content' }, source_type: { type: 'string', enum: ['video', 'article', 'podcast', 'presentation'] }, target_formats: { type: 'array', items: { type: 'string', enum: ['shorts', 'thread', 'carousel', 'story', 'newsletter', 'blog', 'quotes'] }, description: 'Target formats' } }, required: ['source_content', 'source_type', 'target_formats'] } },
  { name: 'stream_overlay_creator', description: 'Create streaming overlay concepts and alert configurations for OBS/Twitch/YouTube. Designs scene layouts, chat widgets, notification styles, and branding elements.', inputSchema: { type: 'object', properties: { platform: { type: 'string', enum: ['twitch', 'youtube', 'kick'] }, style: { type: 'string', enum: ['gaming', 'irl', 'creative', 'tech', 'minimal'] }, brand_colors: { type: 'array', items: { type: 'string' }, description: 'Brand colors (hex codes)' }, elements: { type: 'array', items: { type: 'string', enum: ['webcam_frame', 'chat_overlay', 'alerts', 'goals', 'countdown', 'ticker'] } } }, required: ['platform'] } },
  { name: 'tiktok_trend_analyzer', description: 'Analyze TikTok trends including sounds, effects, hashtags, and content formats. Identifies emerging trends early and suggests how to participate authentically.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['trending', 'analyze', 'predict', 'suggest'], description: 'Analysis action' }, niche: { type: 'string', description: 'Content niche' }, region: { type: 'string', description: 'Region (default: CA/US)' } }, required: ['action'] } },
  { name: 'newsletter_writer', description: 'Write engaging email newsletters with subject lines, preview text, sections, and CTAs. Optimizes for open rates and click-through rates with proven copywriting formulas.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Newsletter topic or theme' }, sections: { type: 'array', items: { type: 'object' }, description: 'Newsletter sections with content' }, tone: { type: 'string', enum: ['professional', 'casual', 'personal', 'news'] }, audience: { type: 'string', description: 'Target audience' } }, required: ['topic'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // HEALTHCARE — Clinical documentation, scheduling, compliance
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'soap_note_writer', description: 'Generate SOAP notes (Subjective, Objective, Assessment, Plan) from dictation or structured input. Follows clinical documentation standards with proper medical terminology.', inputSchema: { type: 'object', properties: { input: { type: 'string', description: 'Clinical notes, dictation, or structured data' }, specialty: { type: 'string', enum: ['general', 'emergency', 'pediatrics', 'psychiatry', 'surgery', 'internal_medicine'] }, template: { type: 'string', enum: ['standard', 'brief', 'detailed'] } }, required: ['input'] } },
  { name: 'shift_scheduler', description: 'Create healthcare shift schedules for 12-hour rotations, on-call coverage, and weekend/holiday duty. Ensures compliance with work hour regulations and fair distribution.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'optimize', 'swap', 'coverage', 'compliance_check'], description: 'Scheduling action' }, staff: { type: 'array', items: { type: 'object' }, description: 'Staff list with roles and availability' }, period: { type: 'string', description: 'Schedule period' }, shift_type: { type: 'string', enum: ['8_hour', '12_hour', 'rotating', 'on_call'] } }, required: ['action'] } },
  { name: 'patient_handoff', description: 'Generate structured patient handoff reports using SBAR format (Situation, Background, Assessment, Recommendation). Ensures critical information transfer during shift changes.', inputSchema: { type: 'object', properties: { patient_info: { type: 'object', description: 'Patient demographics (anonymized ID, age, sex)' }, situation: { type: 'string', description: 'Current situation and reason for handoff' }, background: { type: 'string', description: 'Relevant medical history' }, assessment: { type: 'string', description: 'Current assessment' }, recommendation: { type: 'string', description: 'Recommended actions' } }, required: ['situation'] } },
  { name: 'medication_checker', description: 'Check drug interactions, contraindications, and dosage guidelines. Provides alerts for dangerous combinations and suggests alternatives. For informational purposes — always verify with pharmacist.', inputSchema: { type: 'object', properties: { medications: { type: 'array', items: { type: 'string' }, description: 'List of medication names' }, action: { type: 'string', enum: ['interactions', 'dosage', 'contraindications', 'alternatives', 'full_check'] }, patient_info: { type: 'object', description: 'Patient details (age, weight, conditions) for dosage calc' } }, required: ['medications', 'action'] } },
  { name: 'clinical_protocol_finder', description: 'Find relevant clinical protocols, guidelines, and best practices for specific conditions or procedures. Sources from major medical associations and evidence-based databases.', inputSchema: { type: 'object', properties: { condition: { type: 'string', description: 'Medical condition or procedure' }, specialty: { type: 'string', description: 'Medical specialty' }, guideline_source: { type: 'string', enum: ['who', 'cdc', 'nice', 'canadian', 'all'], description: 'Guideline source preference' } }, required: ['condition'] } },
  { name: 'medical_terminology', description: 'Explain medical terminology in plain language. Translate between medical jargon and patient-friendly descriptions. Supports medical abbreviation lookup.', inputSchema: { type: 'object', properties: { term: { type: 'string', description: 'Medical term or abbreviation to explain' }, direction: { type: 'string', enum: ['to_plain', 'to_medical', 'abbreviation'], description: 'Translation direction' }, context: { type: 'string', description: 'Clinical context for accurate interpretation' } }, required: ['term'] } },
  { name: 'continuing_ed_tracker', description: 'Track continuing education credits for healthcare professionals. Manages CME/CE requirements by specialty, identifies gaps, and suggests relevant courses.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['log', 'status', 'requirements', 'suggest', 'report'], description: 'Tracking action' }, profession: { type: 'string', enum: ['physician', 'nurse', 'pharmacist', 'dentist', 'therapist'] }, credits: { type: 'object', description: 'Credit details (hours, type, provider, date)' }, province_state: { type: 'string', description: 'Licensing jurisdiction' } }, required: ['action', 'profession'] } },
  { name: 'incident_report', description: 'Generate incident and adverse event reports following healthcare reporting standards. Structured documentation for falls, medication errors, and near-misses.', inputSchema: { type: 'object', properties: { incident_type: { type: 'string', enum: ['fall', 'medication_error', 'near_miss', 'equipment', 'injury', 'other'] }, description: { type: 'string', description: 'Incident description' }, severity: { type: 'string', enum: ['minor', 'moderate', 'major', 'sentinel'] }, immediate_actions: { type: 'string', description: 'Actions taken immediately' } }, required: ['incident_type', 'description'] } },
  { name: 'infection_control', description: 'Infection control checklists, protocols, and outbreak management tools. Includes hand hygiene audits, PPE guidelines, and isolation precaution recommendations.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['checklist', 'ppe_guide', 'isolation', 'outbreak', 'audit', 'education'], description: 'IC action' }, pathogen: { type: 'string', description: 'Specific pathogen or organism' }, setting: { type: 'string', enum: ['hospital', 'long_term_care', 'clinic', 'home_care'] } }, required: ['action'] } },
  { name: 'mental_health_screening', description: 'Standardized mental health screening tools including PHQ-9, GAD-7, CAGE, AUDIT, and Columbia Suicide Severity. Generates scored assessments with clinical guidance.', inputSchema: { type: 'object', properties: { tool: { type: 'string', enum: ['phq9', 'gad7', 'cage', 'audit', 'columbia', 'edinburgh', 'pcl5'], description: 'Screening tool' }, responses: { type: 'array', items: { type: 'number' }, description: 'Patient responses (scores)' }, action: { type: 'string', enum: ['administer', 'score', 'interpret', 'blank_form'] } }, required: ['tool'] } },
  { name: 'telehealth_setup', description: 'Set up and manage telehealth appointments with secure video links, intake forms, consent documentation, and technical requirements checklist.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'manage', 'consent', 'tech_check', 'intake_form'], description: 'Telehealth action' }, appointment: { type: 'object', description: 'Appointment details (date, time, provider, patient)' } }, required: ['action'] } },
  { name: 'hipaa_compliance', description: 'HIPAA and PIPEDA compliance checklists, risk assessments, and audit preparation. Helps healthcare organizations maintain privacy and security compliance.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['checklist', 'risk_assessment', 'audit', 'training', 'breach_response'], description: 'Compliance action' }, framework: { type: 'string', enum: ['hipaa', 'pipeda', 'both'] }, organization_type: { type: 'string', description: 'Type of healthcare organization' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // TEACHERS & EDUCATORS — Lesson planning, grading, student management
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'lesson_plan_creator', description: 'Create lesson plans aligned to curriculum standards (Common Core, Ontario, Quebec). Includes objectives, activities, materials, differentiation, and assessment. Supports all grade levels.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Subject area' }, topic: { type: 'string', description: 'Lesson topic' }, grade: { type: 'number', description: 'Grade level' }, duration: { type: 'number', description: 'Lesson duration in minutes' }, standards: { type: 'array', items: { type: 'string' }, description: 'Curriculum standard codes' }, differentiation: { type: 'boolean', description: 'Include differentiation for diverse learners' } }, required: ['subject', 'topic', 'grade'] } },
  { name: 'rubric_builder', description: 'Build grading rubrics with criteria, performance levels, and point scales. Supports analytic and holistic rubrics. Generates student-friendly versions and scoring guides.', inputSchema: { type: 'object', properties: { assignment: { type: 'string', description: 'Assignment or project description' }, criteria: { type: 'array', items: { type: 'string' }, description: 'Grading criteria' }, levels: { type: 'number', description: 'Number of performance levels (default: 4)' }, total_points: { type: 'number', description: 'Total points possible' }, type: { type: 'string', enum: ['analytic', 'holistic', 'single_point'] } }, required: ['assignment'] } },
  { name: 'quiz_maker', description: 'Create quizzes and tests with multiple question types, answer keys, and auto-grading templates. Supports question banks, randomization, and difficulty balancing.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Subject area' }, topic: { type: 'string', description: 'Quiz topic' }, questions: { type: 'number', description: 'Number of questions' }, types: { type: 'array', items: { type: 'string', enum: ['multiple_choice', 'true_false', 'short_answer', 'essay', 'matching', 'fill_blank'] } }, grade: { type: 'number', description: 'Grade level' } }, required: ['subject', 'topic'] } },
  { name: 'report_card_generator', description: 'Generate student report cards with personalized comments, grade summaries, and growth indicators. Saves time with template-based comment generation while maintaining personal touch.', inputSchema: { type: 'object', properties: { student: { type: 'object', description: 'Student info (name, grade, ID)' }, grades: { type: 'object', description: 'Subject grades and assessments' }, behavior: { type: 'object', description: 'Behavioral assessments' }, comment_style: { type: 'string', enum: ['strengths_based', 'growth_focused', 'balanced', 'detailed'] } }, required: ['student', 'grades'] } },
  { name: 'iep_goal_writer', description: 'Write IEP (Individualized Education Program) goals that are SMART and aligned to standards. Includes present levels, measurable objectives, accommodations, and progress monitoring plans.', inputSchema: { type: 'object', properties: { student_needs: { type: 'string', description: 'Student\'s areas of need' }, current_level: { type: 'string', description: 'Present level of performance' }, goal_area: { type: 'string', enum: ['reading', 'writing', 'math', 'behavior', 'social', 'communication', 'motor', 'transition'] }, grade: { type: 'number', description: 'Grade level' } }, required: ['student_needs', 'goal_area'] } },
  { name: 'curriculum_mapper', description: 'Map curriculum to standards, learning objectives, and assessment milestones. Visualize scope and sequence across units and identify coverage gaps.', inputSchema: { type: 'object', properties: { subject: { type: 'string', description: 'Subject area' }, grade: { type: 'number', description: 'Grade level' }, standards_framework: { type: 'string', enum: ['common_core', 'ontario', 'quebec', 'ngss', 'custom'] }, units: { type: 'array', items: { type: 'object' }, description: 'Curriculum units with topics' } }, required: ['subject', 'grade'] } },
  { name: 'attendance_tracker', description: 'Track student attendance with pattern analysis, chronic absenteeism detection, and parent notification triggers. Generates attendance reports and intervention recommendations.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['record', 'report', 'patterns', 'interventions', 'notify'], description: 'Attendance action' }, class_id: { type: 'string', description: 'Class or section ID' }, date: { type: 'string', description: 'Date (YYYY-MM-DD)' }, students: { type: 'array', items: { type: 'object' }, description: 'Student attendance records' } }, required: ['action'] } },
  { name: 'behavior_logger', description: 'Log student behavior incidents with antecedent-behavior-consequence tracking. Identify patterns, track interventions, and generate behavior improvement plans.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['log', 'patterns', 'intervention_plan', 'report', 'positive_log'], description: 'Behavior tracking action' }, student: { type: 'string', description: 'Student identifier' }, incident: { type: 'object', description: 'Incident details (behavior, antecedent, consequence, setting)' } }, required: ['action'] } },
  { name: 'parent_communication', description: 'Generate parent communication templates for meetings, progress updates, behavior reports, and event announcements. Professional yet warm tone with translation support.', inputSchema: { type: 'object', properties: { type: { type: 'string', enum: ['meeting_invite', 'progress_update', 'behavior_report', 'event', 'newsletter', 'concern', 'positive'] }, student: { type: 'string', description: 'Student name' }, details: { type: 'object', description: 'Communication details' }, language: { type: 'string', enum: ['en', 'fr', 'es'], description: 'Communication language' } }, required: ['type'] } },
  { name: 'substitute_plan', description: 'Create detailed substitute teacher plans with schedules, procedures, student information, emergency contacts, and activity instructions. Everything a sub needs in one document.', inputSchema: { type: 'object', properties: { date: { type: 'string', description: 'Absence date(s)' }, grade: { type: 'number', description: 'Grade level' }, schedule: { type: 'array', items: { type: 'object' }, description: 'Daily schedule with periods and activities' }, special_notes: { type: 'string', description: 'Special instructions, allergies, behaviors' } }, required: ['date', 'grade'] } },
  { name: 'field_trip_planner', description: 'Plan field trips with logistics, permission forms, chaperone assignments, itinerary, educational objectives, and risk assessment. Generates all required documentation.', inputSchema: { type: 'object', properties: { destination: { type: 'string', description: 'Field trip destination' }, date: { type: 'string', description: 'Trip date' }, students: { type: 'number', description: 'Number of students' }, grade: { type: 'number', description: 'Grade level' }, educational_goals: { type: 'array', items: { type: 'string' }, description: 'Learning objectives tied to curriculum' } }, required: ['destination', 'date'] } },
  { name: 'differentiated_activity', description: 'Create differentiated activities for diverse learning levels within the same classroom. Provides multiple entry points, scaffolding, and extension activities.', inputSchema: { type: 'object', properties: { topic: { type: 'string', description: 'Lesson topic' }, grade: { type: 'number', description: 'Grade level' }, levels: { type: 'array', items: { type: 'string', enum: ['below_grade', 'on_grade', 'above_grade', 'ell', 'iep'] }, description: 'Learning levels to differentiate for' }, activity_type: { type: 'string', enum: ['worksheet', 'project', 'discussion', 'hands_on', 'digital'] } }, required: ['topic', 'grade'] } },
  { name: 'classroom_seating', description: 'Generate optimal seating arrangements based on student needs, behavior considerations, learning groups, and classroom layout. Supports various configurations.', inputSchema: { type: 'object', properties: { students: { type: 'array', items: { type: 'object' }, description: 'Students with notes (behavior pairs, vision needs, etc.)' }, layout: { type: 'string', enum: ['rows', 'groups', 'u_shape', 'pairs', 'circles', 'stations'] }, room_size: { type: 'object', description: 'Room dimensions and constraints' } }, required: ['students', 'layout'] } },
  { name: 'grade_calculator', description: 'Calculate grades with weighted categories (tests, homework, participation, projects). Supports curve application, dropped lowest scores, and what-if scenarios.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['calculate', 'what_if', 'curve', 'statistics'], description: 'Calculation action' }, categories: { type: 'array', items: { type: 'object' }, description: 'Grade categories with weights and scores' }, scale: { type: 'string', enum: ['letter', 'percentage', 'gpa', 'pass_fail'] } }, required: ['action'] } },
  { name: 'student_portfolio', description: 'Build digital student portfolios with work samples, reflections, growth documentation, and presentation views. Supports photo, video, and document artifacts.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'add_artifact', 'view', 'share', 'export'], description: 'Portfolio action' }, student: { type: 'string', description: 'Student identifier' }, artifact: { type: 'object', description: 'Work sample details (title, type, description, date)' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // VOICE CONFERENCING — Multi-party calls with AI agents
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'conference_create', description: 'Create a voice conference room that can include humans and AI agents. Set up with topic, agenda, and participant list. Generates join links and dial-in numbers.', inputSchema: { type: 'object', properties: { name: { type: 'string', description: 'Conference name' }, topic: { type: 'string', description: 'Meeting topic or agenda' }, max_participants: { type: 'number', description: 'Maximum participants (default: 20)' }, recording: { type: 'boolean', description: 'Enable recording' }, transcription: { type: 'boolean', description: 'Enable live transcription' } }, required: ['name'] } },
  { name: 'conference_invite', description: 'Invite participants to a conference by phone number, email, or agent ID. Supports scheduled sends and calendar integration.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, participants: { type: 'array', items: { type: 'object' }, description: 'Participants (type: phone/email/agent, contact info)' }, message: { type: 'string', description: 'Invitation message' } }, required: ['conference_id', 'participants'] } },
  { name: 'conference_agent_join', description: 'Have an AI agent join a conference as an active participant. The agent can listen, speak, answer questions, and use its tools during the call.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, agent_id: { type: 'string', description: 'Agent ID to bring into the room' }, role: { type: 'string', enum: ['participant', 'presenter', 'note_taker', 'facilitator'], description: 'Agent role in the conference' } }, required: ['conference_id', 'agent_id'] } },
  { name: 'conference_transcript', description: 'Get real-time or post-call transcript of a conference with speaker identification, timestamps, and topic segmentation.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, format: { type: 'string', enum: ['full', 'summary', 'by_speaker', 'by_topic'], description: 'Transcript format' }, language: { type: 'string', description: 'Language (default: en)' } }, required: ['conference_id'] } },
  { name: 'conference_action_items', description: 'Extract action items from conference discussion with assignees, deadlines, and priority. Can be run during or after the call.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, transcript: { type: 'string', description: 'Conference transcript (if not auto-captured)' } }, required: ['conference_id'] } },
  { name: 'conference_recording', description: 'Manage conference recordings — start, stop, retrieve, and share. Generates highlights and searchable transcript from recording.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, action: { type: 'string', enum: ['start', 'stop', 'get', 'share', 'highlights'], description: 'Recording action' } }, required: ['conference_id', 'action'] } },
  { name: 'conference_interpreter', description: 'Real-time language interpretation in conference calls. Translates between English and French (or other languages) for bilingual meetings.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, from_language: { type: 'string', description: 'Source language' }, to_language: { type: 'string', description: 'Target language' }, mode: { type: 'string', enum: ['real_time', 'summary', 'post_call'], description: 'Interpretation mode' } }, required: ['conference_id', 'from_language', 'to_language'] } },
  { name: 'conference_facilitator', description: 'AI facilitator that manages conference flow — speaking order, agenda tracking, time management, and ensures all participants are heard.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, agenda: { type: 'array', items: { type: 'object' }, description: 'Agenda items with time allocations' }, style: { type: 'string', enum: ['formal', 'casual', 'standup', 'brainstorm'], description: 'Facilitation style' } }, required: ['conference_id'] } },
  { name: 'conference_summary', description: 'Generate comprehensive conference summary with key decisions, action items, unresolved questions, and follow-up recommendations.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, format: { type: 'string', enum: ['executive', 'detailed', 'email', 'slack'], description: 'Summary format' }, include: { type: 'array', items: { type: 'string', enum: ['decisions', 'action_items', 'questions', 'participants', 'timeline'] } } }, required: ['conference_id'] } },
  { name: 'conference_follow_up', description: 'Generate and send follow-up communications to conference participants with personalized summaries, assigned action items, and next meeting scheduling.', inputSchema: { type: 'object', properties: { conference_id: { type: 'string', description: 'Conference room ID' }, action: { type: 'string', enum: ['generate', 'send', 'schedule_next'], description: 'Follow-up action' }, recipients: { type: 'array', items: { type: 'string' }, description: 'Recipient filter (or "all")' } }, required: ['conference_id', 'action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // REAL ESTATE — Listings, CMA, client management
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'listing_writer', description: 'Write compelling property listing descriptions with SEO optimization. Highlights features, neighborhood benefits, and lifestyle appeal. Generates MLS-ready copy.', inputSchema: { type: 'object', properties: { property: { type: 'object', description: 'Property details (type, beds, baths, sqft, features, address)' }, style: { type: 'string', enum: ['luxury', 'family', 'investment', 'starter', 'condo'] }, highlights: { type: 'array', items: { type: 'string' }, description: 'Key features to emphasize' } }, required: ['property'] } },
  { name: 'comparative_analysis', description: 'Run Comparative Market Analysis (CMA) with property comparisons, price adjustments, and market value estimation. Professional CMA report for sellers and buyers.', inputSchema: { type: 'object', properties: { subject_property: { type: 'object', description: 'Subject property details' }, comparables: { type: 'array', items: { type: 'object' }, description: 'Comparable properties with sale prices' }, adjustments: { type: 'object', description: 'Adjustment factors' } }, required: ['subject_property'] } },
  { name: 'mortgage_calculator', description: 'Calculate mortgage payments, amortization schedules, and total costs. Compare fixed vs variable rates, different terms, and down payment scenarios. Canadian and US mortgage rules.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['payment', 'amortization', 'affordability', 'compare', 'stress_test'], description: 'Calculation type' }, price: { type: 'number', description: 'Property price' }, down_payment: { type: 'number', description: 'Down payment amount or percentage' }, rate: { type: 'number', description: 'Interest rate (%)' }, term: { type: 'number', description: 'Term in years' }, country: { type: 'string', enum: ['CA', 'US'] } }, required: ['action', 'price'] } },
  { name: 'open_house_planner', description: 'Plan and promote open houses with scheduling, signage, visitor tracking, follow-up sequences, and feedback collection. Generates marketing materials.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['plan', 'checklist', 'marketing', 'visitor_log', 'follow_up'], description: 'Planning action' }, property: { type: 'object', description: 'Property details' }, date: { type: 'string', description: 'Open house date and time' } }, required: ['action'] } },
  { name: 'property_valuation', description: 'Estimate property value using comparable sales, market trends, property attributes, and location data. Generates detailed valuation reports with confidence ranges.', inputSchema: { type: 'object', properties: { property: { type: 'object', description: 'Property details for valuation' }, method: { type: 'string', enum: ['comparable', 'income', 'cost', 'automated'], description: 'Valuation method' }, purpose: { type: 'string', enum: ['listing', 'purchase', 'refinance', 'insurance'] } }, required: ['property'] } },
  { name: 'client_follow_up', description: 'Automate real estate client follow-up sequences. Triggers based on showing feedback, listing status changes, and time-based drips. Personalizes messages by client type.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create_sequence', 'check_due', 'send', 'report'], description: 'Follow-up action' }, client: { type: 'object', description: 'Client details and current stage' }, sequence_type: { type: 'string', enum: ['buyer', 'seller', 'past_client', 'lead', 'referral'] } }, required: ['action'] } },
  { name: 'closing_checklist', description: 'Track the real estate closing process with all required documents, deadlines, conditions, and stakeholder responsibilities. Nothing falls through the cracks.', inputSchema: { type: 'object', properties: { transaction_type: { type: 'string', enum: ['purchase', 'sale', 'both'] }, closing_date: { type: 'string', description: 'Expected closing date' }, conditions: { type: 'array', items: { type: 'string' }, description: 'Conditions to fulfill' }, province_state: { type: 'string', description: 'Jurisdiction' } }, required: ['transaction_type'] } },
  { name: 'market_report', description: 'Generate real estate market reports by neighborhood, city, or region. Includes median prices, days on market, inventory levels, year-over-year trends, and forecasts.', inputSchema: { type: 'object', properties: { location: { type: 'string', description: 'Market area' }, property_type: { type: 'string', enum: ['residential', 'condo', 'commercial', 'land', 'all'] }, period: { type: 'string', description: 'Report period' }, metrics: { type: 'array', items: { type: 'string' }, description: 'Specific metrics to include' } }, required: ['location'] } },
  { name: 'lead_qualifier', description: 'Qualify real estate leads with scoring based on budget, timeline, motivation, and engagement. Prioritizes follow-ups and suggests conversion strategies.', inputSchema: { type: 'object', properties: { lead: { type: 'object', description: 'Lead information (name, budget, timeline, requirements)' }, scoring_criteria: { type: 'object', description: 'Custom scoring weights' } }, required: ['lead'] } },
  { name: 'neighborhood_profile', description: 'Generate comprehensive neighborhood profiles with schools, crime statistics, amenities, transit, demographics, and lifestyle factors. Perfect for buyer presentations.', inputSchema: { type: 'object', properties: { location: { type: 'string', description: 'Neighborhood or area name' }, include: { type: 'array', items: { type: 'string', enum: ['schools', 'crime', 'amenities', 'transit', 'demographics', 'lifestyle', 'all'] } } }, required: ['location'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // FREELANCERS — Proposals, contracts, time tracking, invoicing
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'proposal_writer', description: 'Write project proposals with scope, timeline, deliverables, pricing, and terms. Professional formatting that wins clients. Supports multiple proposal templates.', inputSchema: { type: 'object', properties: { project: { type: 'string', description: 'Project description' }, client: { type: 'string', description: 'Client name' }, services: { type: 'array', items: { type: 'object' }, description: 'Services with pricing' }, timeline: { type: 'string', description: 'Project timeline' }, template: { type: 'string', enum: ['standard', 'detailed', 'brief', 'creative'] } }, required: ['project', 'client'] } },
  { name: 'freelance_time_tracker', description: 'Track billable hours per project and client with automatic entries, idle detection, and reporting. Calculates earnings and generates timesheets.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['start', 'stop', 'log', 'report', 'timesheet', 'invoice_prep'], description: 'Time tracking action' }, project: { type: 'string', description: 'Project name' }, client: { type: 'string', description: 'Client name' }, hours: { type: 'number', description: 'Hours to log (for manual entry)' }, rate: { type: 'number', description: 'Hourly rate' } }, required: ['action'] } },
  { name: 'rate_calculator', description: 'Calculate optimal freelance rates based on desired income, overhead costs, billable hours, and market rates. Includes annual income projections and rate comparison.', inputSchema: { type: 'object', properties: { desired_annual_income: { type: 'number', description: 'Desired annual income' }, billable_hours_per_week: { type: 'number', description: 'Expected billable hours per week' }, overhead_monthly: { type: 'number', description: 'Monthly overhead costs' }, weeks_off: { type: 'number', description: 'Vacation/sick weeks per year' }, market_comparison: { type: 'boolean', description: 'Include market rate comparison' } }, required: ['desired_annual_income'] } },
  { name: 'scope_creep_detector', description: 'Analyze project requests and communications for scope creep. Compares current work against original proposal. Suggests how to address and price change requests.', inputSchema: { type: 'object', properties: { original_scope: { type: 'string', description: 'Original project scope from proposal' }, current_request: { type: 'string', description: 'Current client request to evaluate' }, project_budget: { type: 'number', description: 'Original project budget' } }, required: ['original_scope', 'current_request'] } },
  { name: 'portfolio_builder', description: 'Build online portfolio with project showcases, case studies, testimonials, and skills. Generates portfolio content and structure for maximum impact.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'add_project', 'case_study', 'testimonial', 'export'], description: 'Portfolio action' }, project: { type: 'object', description: 'Project details (title, description, images, client, results)' }, style: { type: 'string', enum: ['minimal', 'detailed', 'creative', 'corporate'] } }, required: ['action'] } },
  { name: 'contract_template', description: 'Generate freelance contracts with IP assignment, payment terms, revision limits, kill fees, and liability clauses. Customizable for different project types.', inputSchema: { type: 'object', properties: { project_type: { type: 'string', enum: ['web_development', 'design', 'writing', 'consulting', 'marketing', 'custom'] }, client: { type: 'string', description: 'Client name' }, project_value: { type: 'number', description: 'Project value' }, payment_schedule: { type: 'string', enum: ['upfront', 'milestone', 'monthly', '50_50', 'net_30'] }, revisions: { type: 'number', description: 'Number of included revisions' } }, required: ['project_type', 'client'] } },
  { name: 'client_onboarding', description: 'Structured client onboarding workflow with intake questionnaire, kickoff meeting agenda, access checklist, and project setup. Ensures smooth project starts.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['questionnaire', 'kickoff_agenda', 'access_checklist', 'welcome_email', 'full_workflow'], description: 'Onboarding action' }, client: { type: 'string', description: 'Client name' }, project_type: { type: 'string', description: 'Type of project' } }, required: ['action', 'client'] } },
  { name: 'tax_quarterly_estimator', description: 'Estimate quarterly self-employment taxes for freelancers. Calculates estimated payments, deductions, and filing deadlines for Canada (installments) and US (1040-ES).', inputSchema: { type: 'object', properties: { income_ytd: { type: 'number', description: 'Year-to-date income' }, expenses_ytd: { type: 'number', description: 'Year-to-date deductible expenses' }, country: { type: 'string', enum: ['CA', 'US'] }, province_state: { type: 'string', description: 'Province or state' }, quarter: { type: 'number', enum: [1, 2, 3, 4], description: 'Tax quarter' } }, required: ['income_ytd', 'country'] } },
  { name: 'feedback_request', description: 'Generate professional feedback and testimonial requests for completed projects. Creates templates that make it easy for clients to provide substantive reviews.', inputSchema: { type: 'object', properties: { client: { type: 'string', description: 'Client name' }, project: { type: 'string', description: 'Project name' }, platform: { type: 'string', enum: ['email', 'linkedin', 'google', 'portfolio', 'all'] }, questions: { type: 'array', items: { type: 'string' }, description: 'Specific questions to ask' } }, required: ['client', 'project'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // SENIORS — Accessibility, health, safety, simplified tools
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'medication_reminder', description: 'Set medication reminders with dosage instructions, timing, and refill alerts. Tracks adherence and can notify caregivers of missed doses.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'list', 'check', 'refill_alert', 'adherence_report'], description: 'Reminder action' }, medication: { type: 'object', description: 'Medication details (name, dosage, frequency, time, instructions)' }, caregiver_notify: { type: 'boolean', description: 'Notify caregiver of missed doses' } }, required: ['action'] } },
  { name: 'health_journal', description: 'Track daily health metrics like blood pressure, blood sugar, weight, mood, sleep, and pain levels. Generates trend charts and reports for doctor visits.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['log', 'trends', 'report', 'export'], description: 'Journal action' }, metrics: { type: 'object', description: 'Health metrics to log (bp, glucose, weight, mood, sleep, pain)' }, date: { type: 'string', description: 'Date for entry (default: today)' } }, required: ['action'] } },
  { name: 'simplified_interface', description: 'Switch Alfred to senior-friendly mode: larger text, simpler language, slower speech, fewer options per screen, and more confirmations before actions.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['enable', 'disable', 'configure'], description: 'Interface action' }, settings: { type: 'object', description: 'Customization (text_size, speech_speed, complexity_level)' } }, required: ['action'] } },
  { name: 'scam_detector', description: 'Detect and warn about phone, email, and online scams. Analyzes suspicious messages, calls, and websites. Educates about common scam patterns targeting seniors.', inputSchema: { type: 'object', properties: { content: { type: 'string', description: 'Suspicious message, email, or URL to analyze' }, content_type: { type: 'string', enum: ['email', 'phone_call', 'text_message', 'website', 'letter'] }, action: { type: 'string', enum: ['analyze', 'report', 'educate', 'block'] } }, required: ['content', 'content_type'] } },
  { name: 'caregiver_portal', description: 'Portal for family caregivers to monitor senior health, medication adherence, activity levels, and emergency alerts. Privacy-respecting remote monitoring.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['dashboard', 'add_caregiver', 'alerts', 'report', 'settings'], description: 'Portal action' }, caregiver: { type: 'object', description: 'Caregiver details (name, relationship, contact)' } }, required: ['action'] } },
  { name: 'appointment_manager', description: 'Manage medical and social appointments with reminders, preparation checklists, and transportation planning. Syncs with family calendar.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['schedule', 'list', 'remind', 'prep_checklist', 'cancel'], description: 'Appointment action' }, appointment: { type: 'object', description: 'Appointment details (type, provider, date, time, location)' } }, required: ['action'] } },
  { name: 'emergency_alert', description: 'One-word or one-button emergency alert to designated contacts and emergency services. Can be triggered by voice command. Sends location and medical info.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['alert', 'configure', 'test', 'contacts'], description: 'Alert action' }, alert_type: { type: 'string', enum: ['medical', 'fall', 'fire', 'intruder', 'general'] }, message: { type: 'string', description: 'Additional message (optional)' } }, required: ['action'] } },
  { name: 'voice_memo', description: 'Create voice memos that are automatically transcribed and organized. Easy retrieval by date, topic, or keyword. No typing required.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'list', 'search', 'play', 'delete'], description: 'Memo action' }, content: { type: 'string', description: 'Memo content (text or transcription)' }, topic: { type: 'string', description: 'Memo topic or category' } }, required: ['action'] } },
  { name: 'bill_pay_helper', description: 'Guided bill payment with verification at each step. Reads bills, calculates totals, confirms amounts, and records payment. Extra safety checks for large amounts.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['read_bill', 'schedule', 'verify', 'history', 'set_autopay'], description: 'Bill pay action' }, bill: { type: 'object', description: 'Bill details (vendor, amount, due_date, account)' } }, required: ['action'] } },
  { name: 'tech_support', description: 'Patient step-by-step tech support for common devices and applications. Uses simple language, large print instructions, and confirmation at each step.', inputSchema: { type: 'object', properties: { device: { type: 'string', description: 'Device type (phone, tablet, computer, TV, etc.)' }, issue: { type: 'string', description: 'Issue description' }, skill_level: { type: 'string', enum: ['beginner', 'intermediate'], description: 'Tech skill level' } }, required: ['device', 'issue'] } },
  { name: 'social_connector', description: 'Find and join local social activities, senior centers, clubs, and community events. Helps seniors stay connected and combat isolation.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['find_activities', 'find_groups', 'events', 'volunteer', 'classes'], description: 'Connection action' }, location: { type: 'string', description: 'City or postal code' }, interests: { type: 'array', items: { type: 'string' }, description: 'Interests (gardening, cards, walking, art, music, etc.)' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // PARENTS & FAMILIES — Budget, meals, scheduling, safety
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'family_budget', description: 'Track family budget with income, expenses, savings goals, and spending categories. Visual dashboards show where money goes and progress toward goals.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['setup', 'log', 'report', 'goals', 'alerts', 'optimize'], description: 'Budget action' }, transaction: { type: 'object', description: 'Transaction details (amount, category, description)' }, period: { type: 'string', description: 'Budget period' } }, required: ['action'] } },
  { name: 'meal_planner', description: 'Plan weekly meals with grocery lists, nutrition info, and cost estimates. Accounts for dietary restrictions, allergies, and family preferences. Scales recipes to family size.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['plan_week', 'grocery_list', 'recipe', 'nutrition', 'budget'], description: 'Meal planning action' }, family_size: { type: 'number', description: 'Number of people' }, restrictions: { type: 'array', items: { type: 'string' }, description: 'Dietary restrictions (vegetarian, gluten-free, nut-free, etc.)' }, budget: { type: 'number', description: 'Weekly food budget' } }, required: ['action'] } },
  { name: 'chore_tracker', description: 'Track family chores with assignments, completion tracking, rewards points, and allowance management. Gamified system that motivates kids to help.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['assign', 'complete', 'list', 'rewards', 'leaderboard', 'allowance'], description: 'Chore action' }, family_member: { type: 'string', description: 'Family member name' }, chore: { type: 'object', description: 'Chore details (task, frequency, points)' } }, required: ['action'] } },
  { name: 'bedtime_story_creator', description: 'Generate personalized bedtime stories featuring the child by name, their interests, and gentle life lessons. Age-appropriate content with calming themes for easier sleep.', inputSchema: { type: 'object', properties: { child_name: { type: 'string', description: 'Child\'s name' }, age: { type: 'number', description: 'Child\'s age' }, theme: { type: 'string', description: 'Story theme (adventure, animals, space, friendship, etc.)' }, lesson: { type: 'string', description: 'Life lesson to weave in (sharing, courage, kindness, etc.)' }, length: { type: 'string', enum: ['short', 'medium', 'long'] } }, required: ['child_name', 'age'] } },
  { name: 'family_calendar', description: 'Manage family calendar with school events, sports, appointments, and activities. Color-coded by family member with conflict detection and carpool coordination.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'view', 'conflicts', 'carpool', 'week_summary', 'reminders'], description: 'Calendar action' }, event: { type: 'object', description: 'Event details (title, who, when, where)' }, family_member: { type: 'string', description: 'Family member' } }, required: ['action'] } },
  { name: 'child_milestone_tracker', description: 'Track child developmental milestones with age-appropriate activities and recommendations. Covers physical, cognitive, social, and language development.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['log', 'upcoming', 'activities', 'report', 'concerns'], description: 'Tracking action' }, child_name: { type: 'string', description: 'Child name' }, age_months: { type: 'number', description: 'Age in months' }, milestone: { type: 'object', description: 'Milestone details' } }, required: ['action', 'child_name'] } },
  { name: 'college_savings_planner', description: 'Plan education savings with RESP (Canada) or 529 (US) projections. Models contribution scenarios, government grants (CESG), investment returns, and target amounts.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['project', 'optimize', 'compare', 'report'], description: 'Planning action' }, child_age: { type: 'number', description: 'Child\'s current age' }, current_savings: { type: 'number', description: 'Current savings balance' }, monthly_contribution: { type: 'number', description: 'Monthly contribution amount' }, country: { type: 'string', enum: ['CA', 'US'] } }, required: ['action', 'child_age'] } },
  { name: 'safe_internet_guide', description: 'Age-appropriate internet safety rules, monitoring recommendations, and conversation guides for parents. Covers social media, gaming, cyberbullying, and online predators.', inputSchema: { type: 'object', properties: { child_age: { type: 'number', description: 'Child\'s age' }, topics: { type: 'array', items: { type: 'string', enum: ['social_media', 'gaming', 'cyberbullying', 'privacy', 'screen_time', 'strangers', 'all'] } }, output: { type: 'string', enum: ['rules', 'conversation_guide', 'monitoring_plan', 'contract'] } }, required: ['child_age'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // NON-PROFITS — Grants, donors, volunteers, impact reporting
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'grant_writer', description: 'Write grant proposals with needs assessment, program description, budget justification, and evaluation plan. Adapts to funder requirements and RFP formats.', inputSchema: { type: 'object', properties: { funder: { type: 'string', description: 'Funding organization' }, program: { type: 'string', description: 'Program to fund' }, amount: { type: 'number', description: 'Amount requested' }, section: { type: 'string', enum: ['needs', 'program', 'budget', 'evaluation', 'cover_letter', 'full'], description: 'Section to write' } }, required: ['program', 'section'] } },
  { name: 'donor_manager', description: 'Track donors, donations, and communication history. Segment donors by level, manage acknowledgment letters, and forecast giving trends.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'update', 'search', 'report', 'acknowledge', 'segment', 'forecast'], description: 'Donor management action' }, donor: { type: 'object', description: 'Donor details' }, period: { type: 'string', description: 'Report period' } }, required: ['action'] } },
  { name: 'volunteer_coordinator', description: 'Manage volunteer schedules, skills matching, hours tracking, and recognition programs. Streamlines recruitment-to-retention workflow.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['recruit', 'schedule', 'match', 'hours', 'recognition', 'report'], description: 'Volunteer management action' }, volunteer: { type: 'object', description: 'Volunteer details (name, skills, availability)' }, opportunity: { type: 'object', description: 'Volunteer opportunity details' } }, required: ['action'] } },
  { name: 'impact_report', description: 'Generate impact reports with outcome data, stories, infographics, and visualizations. Shows donors and stakeholders the real-world difference their support makes.', inputSchema: { type: 'object', properties: { program: { type: 'string', description: 'Program name' }, period: { type: 'string', description: 'Report period' }, metrics: { type: 'array', items: { type: 'object' }, description: 'Impact metrics with values' }, stories: { type: 'array', items: { type: 'string' }, description: 'Success stories to include' }, audience: { type: 'string', enum: ['donors', 'board', 'public', 'government'] } }, required: ['program'] } },
  { name: 'fundraising_campaign', description: 'Plan and track fundraising campaigns with goal setting, donor outreach, event planning, and progress monitoring. Supports annual funds, capital campaigns, and crowdfunding.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['plan', 'launch', 'track', 'report', 'donor_outreach'], description: 'Campaign action' }, campaign: { type: 'object', description: 'Campaign details (name, goal, type, timeline)' }, type: { type: 'string', enum: ['annual', 'capital', 'crowdfunding', 'event', 'major_gift'] } }, required: ['action'] } },
  { name: 'nonprofit_annual_report', description: 'Generate annual reports from organization data. Includes financials, program outcomes, board listing, donor recognition, and forward-looking section.', inputSchema: { type: 'object', properties: { org_name: { type: 'string', description: 'Organization name' }, fiscal_year: { type: 'string', description: 'Fiscal year' }, sections: { type: 'array', items: { type: 'string', enum: ['letter', 'financials', 'programs', 'donors', 'board', 'vision', 'all'] } } }, required: ['org_name', 'fiscal_year'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // GAMIFICATION — Achievements, streaks, learning paths
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'achievement_system', description: 'Track and award achievements for tool usage milestones, project completions, and skill demonstrations. Browse, earn, and display achievements.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['check', 'list', 'earn', 'display', 'progress'], description: 'Achievement action' }, category: { type: 'string', enum: ['deployer', 'security', 'creator', 'debugger', 'communicator', 'all'] } }, required: ['action'] } },
  { name: 'streak_tracker', description: 'Track daily usage streaks with rewards and motivational messages. Consecutive days of active tool usage build streaks with increasing multipliers.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['check', 'history', 'milestones', 'rewards'], description: 'Streak action' } }, required: ['action'] } },
  { name: 'skill_tree', description: 'Visual skill tree showing mastered capabilities, available skills to unlock, and learning prerequisites. Gamified progression through Alfred\'s tool ecosystem.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['view', 'focus', 'progress', 'unlock'], description: 'Skill tree action' }, branch: { type: 'string', enum: ['development', 'devops', 'security', 'ai', 'business', 'creative', 'all'] } }, required: ['action'] } },
  { name: 'learning_path', description: 'Guided learning paths for mastering specific skills using Alfred\'s tools. Structured courses with lessons, exercises, and assessments.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['browse', 'start', 'continue', 'complete', 'certificate'], description: 'Learning path action' }, path: { type: 'string', description: 'Learning path name (web-dev, devops, ai, security, etc.)' }, skill_level: { type: 'string', enum: ['beginner', 'intermediate', 'advanced'] } }, required: ['action'] } },
  { name: 'challenge_mode', description: 'Daily and weekly coding challenges with AI evaluation, leaderboards, and skill-based matching. Sharpen abilities through competitive problem-solving.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['daily', 'weekly', 'submit', 'leaderboard', 'history'], description: 'Challenge action' }, category: { type: 'string', enum: ['algorithms', 'debugging', 'security', 'performance', 'design', 'random'] }, difficulty: { type: 'string', enum: ['easy', 'medium', 'hard'] } }, required: ['action'] } },
  { name: 'xp_system', description: 'Experience points system with levels, unlocks, and progression tracking. Every tool usage earns XP. Level up to unlock advanced features and earn recognition.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['status', 'history', 'leaderboard', 'rewards', 'level_info'], description: 'XP action' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // MARKETPLACE — Build, publish, install, and monetize tools & agents
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'marketplace_browse', description: 'Browse the Alfred marketplace for tools, agents, playbooks, and bundles. Filter by category, rating, price, and popularity.', inputSchema: { type: 'object', properties: { category: { type: 'string', description: 'Category filter' }, type: { type: 'string', enum: ['tool', 'agent', 'playbook', 'bundle', 'all'] }, sort: { type: 'string', enum: ['popular', 'newest', 'rating', 'price_low', 'price_high'] }, query: { type: 'string', description: 'Search query' } }, required: [] } },
  { name: 'marketplace_publish', description: 'Publish a tool, agent, or playbook to the marketplace with description, pricing, screenshots, and documentation.', inputSchema: { type: 'object', properties: { type: { type: 'string', enum: ['tool', 'agent', 'playbook', 'bundle'] }, name: { type: 'string', description: 'Item name' }, description: { type: 'string', description: 'Description' }, price: { type: 'number', description: 'Price (0 for free)' }, pricing_model: { type: 'string', enum: ['free', 'one_time', 'per_use', 'monthly'] } }, required: ['type', 'name', 'description'] } },
  { name: 'marketplace_install', description: 'Install a tool, agent, or playbook from the marketplace into your workspace with one click.', inputSchema: { type: 'object', properties: { item_id: { type: 'string', description: 'Marketplace item ID to install' }, auto_configure: { type: 'boolean', description: 'Auto-configure after installation' } }, required: ['item_id'] } },
  { name: 'tool_builder', description: 'Build custom tools with a visual builder or code. Define inputs, logic, and outputs. Test and publish to marketplace or keep private.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['create', 'edit', 'test', 'publish', 'list'], description: 'Builder action' }, tool_definition: { type: 'object', description: 'Tool definition (name, description, inputs, logic, outputs)' } }, required: ['action'] } },
  { name: 'agent_template_store', description: 'Browse and install pre-built agent templates by industry. Get a fully configured agent for your use case in seconds.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['browse', 'install', 'customize', 'preview'], description: 'Store action' }, industry: { type: 'string', description: 'Industry filter (healthcare, legal, real-estate, education, etc.)' }, template_id: { type: 'string', description: 'Template to install or preview' } }, required: ['action'] } },
  { name: 'revenue_sharing', description: 'Track revenue sharing from marketplace sales. View earnings, pending payouts, and download financial reports. Creators earn 70%, platform keeps 30%.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['earnings', 'payouts', 'report', 'settings'], description: 'Revenue action' }, period: { type: 'string', description: 'Report period' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === FUTURE TECH WORKERS (15) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'robot_fleet_manager', description: 'Manage fleet of robots with task assignment and monitoring.', inputSchema: { type: 'object', properties: { fleet_id: { type: 'string', description: 'Fleet identifier' }, action: { type: 'string', enum: ['status', 'assign', 'recall'], description: 'Fleet management action' }, task: { type: 'string', description: 'Task to assign to fleet' } }, required: ['fleet_id', 'action'] } },
  { name: 'iot_device_manager', description: 'Manage IoT devices status, firmware, and data streams.', inputSchema: { type: 'object', properties: { device_id: { type: 'string', description: 'IoT device identifier' }, action: { type: 'string', enum: ['status', 'update', 'configure'], description: 'Device management action' }, config: { type: 'string', description: 'Configuration payload as JSON string' } }, required: ['device_id', 'action'] } },
  { name: 'smart_home_controller', description: 'Control smart home devices via voice commands.', inputSchema: { type: 'object', properties: { device: { type: 'string', description: 'Smart home device name or ID' }, action: { type: 'string', enum: ['on', 'off', 'set'], description: 'Control action' }, value: { type: 'string', description: 'Value to set (e.g., brightness, temperature)' } }, required: ['device', 'action'] } },
  { name: 'drone_mission_planner', description: 'Plan drone missions with waypoints.', inputSchema: { type: 'object', properties: { mission_name: { type: 'string', description: 'Mission name' }, waypoints: { type: 'string', description: 'Waypoints as JSON array of coordinates' }, altitude: { type: 'number', description: 'Flight altitude in meters' } }, required: ['mission_name', 'waypoints'] } },
  { name: 'ar_scene_builder', description: 'Build AR scenes with 3D object placement.', inputSchema: { type: 'object', properties: { scene_name: { type: 'string', description: 'AR scene name' }, objects: { type: 'string', description: '3D objects to place as JSON array' }, environment: { type: 'string', description: 'Environment type (indoor, outdoor, etc.)' } }, required: ['scene_name', 'objects'] } },
  { name: 'vr_world_creator', description: 'Create VR environments with interaction logic.', inputSchema: { type: 'object', properties: { world_name: { type: 'string', description: 'VR world name' }, theme: { type: 'string', description: 'World theme' }, interactions: { type: 'string', description: 'Interaction logic as JSON' } }, required: ['world_name', 'theme'] } },
  { name: 'three_d_print_slicer', description: 'Prepare 3D models for printing.', inputSchema: { type: 'object', properties: { model_file: { type: 'string', description: 'Path or URL to 3D model file' }, material: { type: 'string', description: 'Printing material (PLA, ABS, PETG, etc.)' }, layer_height: { type: 'number', description: 'Layer height in mm' } }, required: ['model_file'] } },
  { name: 'firmware_updater', description: 'Remote firmware updates for connected devices.', inputSchema: { type: 'object', properties: { device_id: { type: 'string', description: 'Target device identifier' }, firmware_version: { type: 'string', description: 'Firmware version to install' }, force: { type: 'boolean', description: 'Force update even if current version is newer' } }, required: ['device_id', 'firmware_version'] } },
  { name: 'sensor_data_analyzer', description: 'Analyze IoT sensor data with anomaly detection.', inputSchema: { type: 'object', properties: { sensor_id: { type: 'string', description: 'Sensor identifier' }, time_range: { type: 'string', description: 'Time range for analysis (e.g., 24h, 7d, 30d)' }, threshold: { type: 'number', description: 'Anomaly detection threshold' } }, required: ['sensor_id'] } },
  { name: 'edge_compute_deployer', description: 'Deploy AI models to edge devices.', inputSchema: { type: 'object', properties: { model_name: { type: 'string', description: 'AI model name to deploy' }, target_device: { type: 'string', description: 'Target edge device' }, optimization: { type: 'string', description: 'Optimization strategy (quantization, pruning, etc.)' } }, required: ['model_name', 'target_device'] } },
  { name: 'digital_twin_creator', description: 'Create digital twins of physical systems.', inputSchema: { type: 'object', properties: { system_name: { type: 'string', description: 'Physical system name' }, parameters: { type: 'string', description: 'System parameters as JSON' }, simulation_mode: { type: 'string', description: 'Simulation mode (real-time, accelerated, historical)' } }, required: ['system_name', 'parameters'] } },
  { name: 'autonomous_vehicle_sim', description: 'Simulate autonomous vehicle scenarios.', inputSchema: { type: 'object', properties: { scenario: { type: 'string', description: 'Simulation scenario description' }, vehicle_type: { type: 'string', description: 'Vehicle type (car, truck, drone, etc.)' }, conditions: { type: 'string', description: 'Environmental conditions (weather, traffic, etc.)' } }, required: ['scenario'] } },
  { name: 'wearable_app_builder', description: 'Build wearable device applications.', inputSchema: { type: 'object', properties: { app_name: { type: 'string', description: 'Application name' }, platform: { type: 'string', description: 'Target platform (watchos, wearos, fitbit, etc.)' }, features: { type: 'string', description: 'App features as comma-separated list' } }, required: ['app_name', 'platform'] } },
  { name: 'blockchain_deployer', description: 'Deploy and manage smart contracts.', inputSchema: { type: 'object', properties: { contract_name: { type: 'string', description: 'Smart contract name' }, network: { type: 'string', description: 'Blockchain network (ethereum, polygon, solana, etc.)' }, code: { type: 'string', description: 'Smart contract code' } }, required: ['contract_name', 'network'] } },
  { name: 'quantum_code_helper', description: 'Help write quantum computing algorithms.', inputSchema: { type: 'object', properties: { algorithm: { type: 'string', description: 'Algorithm name or description' }, framework: { type: 'string', enum: ['qiskit', 'cirq'], description: 'Quantum computing framework' }, qubits: { type: 'number', description: 'Number of qubits' } }, required: ['algorithm'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === AGENT ORCHESTRATION (10) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'agent_registry', description: 'Register and manage agent capabilities.', inputSchema: { type: 'object', properties: { agent_name: { type: 'string', description: 'Agent name' }, capabilities: { type: 'string', description: 'Agent capabilities as JSON array' }, specialization: { type: 'string', description: 'Agent specialization area' } }, required: ['agent_name', 'capabilities'] } },
  { name: 'agent_task_router', description: 'Route tasks to most capable agent.', inputSchema: { type: 'object', properties: { task: { type: 'string', description: 'Task description to route' }, priority: { type: 'string', description: 'Task priority (low, medium, high, critical)' }, constraints: { type: 'string', description: 'Routing constraints as JSON' } }, required: ['task'] } },
  { name: 'agent_pipeline_builder', description: 'Build multi-agent pipelines.', inputSchema: { type: 'object', properties: { pipeline_name: { type: 'string', description: 'Pipeline name' }, stages: { type: 'string', description: 'Pipeline stages as JSON array' }, strategy: { type: 'string', description: 'Execution strategy (sequential, parallel, conditional)' } }, required: ['pipeline_name', 'stages'] } },
  { name: 'agent_health_monitor', description: 'Monitor agent health and uptime.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier' }, check_type: { type: 'string', enum: ['health', 'metrics', 'errors'], description: 'Type of health check' } }, required: [] } },
  { name: 'agent_performance_scorer', description: 'Score agent performance against KPIs.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier' }, metrics: { type: 'string', description: 'Metrics to evaluate as JSON' }, period: { type: 'string', description: 'Evaluation period' } }, required: ['agent_id'] } },
  { name: 'agent_learning_loop', description: 'Feed outcomes back for improvement.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier' }, outcome: { type: 'string', description: 'Outcome data' }, feedback: { type: 'string', description: 'Human feedback' } }, required: ['agent_id', 'outcome'] } },
  { name: 'agent_conflict_resolver', description: 'Resolve conflicting agent outputs.', inputSchema: { type: 'object', properties: { outputs: { type: 'string', description: 'Conflicting outputs as JSON array' }, resolution_strategy: { type: 'string', enum: ['vote', 'weighted', 'expert'], description: 'Resolution strategy' } }, required: ['outputs'] } },
  { name: 'agent_cost_optimizer', description: 'Optimize agent usage for cost.', inputSchema: { type: 'object', properties: { budget: { type: 'number', description: 'Budget limit' }, agents: { type: 'string', description: 'Agents to optimize as JSON array' }, optimization_target: { type: 'string', description: 'Target to optimize (cost, speed, quality)' } }, required: [] } },
  { name: 'agent_version_manager', description: 'Manage agent versions.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier' }, action: { type: 'string', enum: ['deploy', 'rollback', 'canary'], description: 'Version management action' }, version: { type: 'string', description: 'Version identifier' } }, required: ['agent_id', 'action'] } },
  { name: 'agent_marketplace_publisher', description: 'Publish agents to marketplace.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier to publish' }, price: { type: 'number', description: 'Price (0 for free)' }, description: { type: 'string', description: 'Agent description for marketplace listing' } }, required: ['agent_id', 'description'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === COLLABORATION & TEAM (10) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'team_workspace', description: 'Create shared workspace.', inputSchema: { type: 'object', properties: { workspace_name: { type: 'string', description: 'Workspace name' }, members: { type: 'string', description: 'Team members as comma-separated list' }, permissions: { type: 'string', description: 'Permission settings as JSON' } }, required: ['workspace_name'] } },
  { name: 'live_code_session', description: 'Real-time collaborative code editing.', inputSchema: { type: 'object', properties: { session_name: { type: 'string', description: 'Session name' }, language: { type: 'string', description: 'Programming language' }, file_path: { type: 'string', description: 'File to collaborate on' } }, required: ['session_name'] } },
  { name: 'shared_terminal', description: 'Shared terminal session for pair programming.', inputSchema: { type: 'object', properties: { session_id: { type: 'string', description: 'Session identifier' }, command: { type: 'string', description: 'Command to execute' }, participants: { type: 'string', description: 'Participants as comma-separated list' } }, required: [] } },
  { name: 'task_board', description: 'Kanban-style task board.', inputSchema: { type: 'object', properties: { board_name: { type: 'string', description: 'Board name' }, action: { type: 'string', enum: ['create', 'move', 'assign'], description: 'Task board action' }, task: { type: 'string', description: 'Task description' } }, required: ['board_name', 'action'] } },
  { name: 'team_chat', description: 'Team chat with channels.', inputSchema: { type: 'object', properties: { channel: { type: 'string', description: 'Chat channel name' }, message: { type: 'string', description: 'Message to send' }, mentions: { type: 'string', description: 'Users to mention as comma-separated list' } }, required: ['channel', 'message'] } },
  { name: 'screen_share', description: 'Share screen with team.', inputSchema: { type: 'object', properties: { session_id: { type: 'string', description: 'Session identifier' }, action: { type: 'string', enum: ['start', 'stop', 'view'], description: 'Screen share action' }, quality: { type: 'string', description: 'Stream quality (low, medium, high)' } }, required: ['action'] } },
  { name: 'whiteboard', description: 'Collaborative whiteboard.', inputSchema: { type: 'object', properties: { board_name: { type: 'string', description: 'Whiteboard name' }, action: { type: 'string', enum: ['draw', 'text', 'sticky'], description: 'Whiteboard action' }, content: { type: 'string', description: 'Content to add' } }, required: ['board_name', 'action'] } },
  { name: 'code_review_request', description: 'Code review requests.', inputSchema: { type: 'object', properties: { pr_url: { type: 'string', description: 'Pull request URL' }, reviewers: { type: 'string', description: 'Reviewers as comma-separated list' }, priority: { type: 'string', description: 'Review priority (low, medium, high)' } }, required: ['pr_url'] } },
  { name: 'team_standup', description: 'Async standup reports.', inputSchema: { type: 'object', properties: { team_id: { type: 'string', description: 'Team identifier' }, update: { type: 'string', description: 'Standup update text' }, blockers: { type: 'string', description: 'Blockers description' } }, required: ['team_id'] } },
  { name: 'knowledge_base', description: 'Build team knowledge base.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'search', 'update'], description: 'Knowledge base action' }, topic: { type: 'string', description: 'Topic name' }, content: { type: 'string', description: 'Content to add or update' } }, required: ['action', 'topic'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === REPORTING & DASHBOARDS (12) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'dashboard_builder', description: 'Build custom dashboards.', inputSchema: { type: 'object', properties: { dashboard_name: { type: 'string', description: 'Dashboard name' }, widgets: { type: 'string', description: 'Widgets configuration as JSON array' }, layout: { type: 'string', description: 'Layout type (grid, flow, tabs)' } }, required: ['dashboard_name', 'widgets'] } },
  { name: 'report_scheduler', description: 'Schedule automated reports.', inputSchema: { type: 'object', properties: { report_name: { type: 'string', description: 'Report name' }, frequency: { type: 'string', enum: ['daily', 'weekly', 'monthly'], description: 'Report frequency' }, recipients: { type: 'string', description: 'Email recipients as comma-separated list' } }, required: ['report_name', 'frequency'] } },
  { name: 'agent_performance_report', description: 'Agent performance reports.', inputSchema: { type: 'object', properties: { agent_id: { type: 'string', description: 'Agent identifier' }, period: { type: 'string', description: 'Report period (e.g., 7d, 30d, 90d)' }, metrics: { type: 'string', description: 'Metrics to include as comma-separated list' } }, required: ['period'] } },
  { name: 'roi_calculator', description: 'Calculate ROI of AI deployments.', inputSchema: { type: 'object', properties: { investment: { type: 'number', description: 'Total investment amount' }, returns: { type: 'number', description: 'Total returns amount' }, period_months: { type: 'number', description: 'Period in months' } }, required: ['investment', 'returns'] } },
  { name: 'sla_monitor', description: 'Monitor SLA compliance.', inputSchema: { type: 'object', properties: { service_name: { type: 'string', description: 'Service name to monitor' }, sla_target: { type: 'number', description: 'SLA target percentage (e.g., 99.9)' }, check_period: { type: 'string', description: 'Check period (e.g., 24h, 7d, 30d)' } }, required: ['service_name'] } },
  { name: 'usage_analytics', description: 'Track tool usage.', inputSchema: { type: 'object', properties: { tool_name: { type: 'string', description: 'Tool name to analyze' }, period: { type: 'string', description: 'Analysis period' }, group_by: { type: 'string', description: 'Group results by (user, day, week, tool)' } }, required: ['period'] } },
  { name: 'cost_analyzer', description: 'Analyze costs per tool/agent.', inputSchema: { type: 'object', properties: { category: { type: 'string', description: 'Cost category to analyze' }, period: { type: 'string', description: 'Analysis period' }, breakdown: { type: 'string', description: 'Breakdown type (by_tool, by_agent, by_user)' } }, required: ['category'] } },
  { name: 'benchmark_comparator', description: 'Compare against benchmarks.', inputSchema: { type: 'object', properties: { metric: { type: 'string', description: 'Metric to compare' }, value: { type: 'number', description: 'Current value to compare' }, industry: { type: 'string', description: 'Industry for benchmark comparison' } }, required: ['metric', 'value'] } },
  { name: 'custom_chart_builder', description: 'Build custom charts.', inputSchema: { type: 'object', properties: { chart_type: { type: 'string', enum: ['bar', 'line', 'pie', 'scatter'], description: 'Chart type' }, data: { type: 'string', description: 'Chart data as JSON' }, title: { type: 'string', description: 'Chart title' } }, required: ['chart_type', 'data'] } },
  { name: 'data_exporter', description: 'Export reports.', inputSchema: { type: 'object', properties: { report_id: { type: 'string', description: 'Report identifier to export' }, format: { type: 'string', enum: ['pdf', 'csv', 'excel', 'json'], description: 'Export format' }, email_to: { type: 'string', description: 'Email address to send export to' } }, required: ['report_id', 'format'] } },
  { name: 'alert_configurator', description: 'Configure custom alerts.', inputSchema: { type: 'object', properties: { metric: { type: 'string', description: 'Metric to monitor' }, threshold: { type: 'number', description: 'Alert threshold value' }, notification_type: { type: 'string', description: 'Notification type (email, slack, sms, webhook)' } }, required: ['metric', 'threshold'] } },
  { name: 'executive_dashboard', description: 'C-suite dashboard.', inputSchema: { type: 'object', properties: { department: { type: 'string', description: 'Department filter' }, period: { type: 'string', description: 'Dashboard period' }, kpis: { type: 'string', description: 'KPIs to display as comma-separated list' } }, required: ['period'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === OFFLINE & PWA (5) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'offline_sync', description: 'Sync workspace for offline access.', inputSchema: { type: 'object', properties: { workspace: { type: 'string', description: 'Workspace to sync' }, sync_type: { type: 'string', enum: ['full', 'incremental'], description: 'Sync type' } }, required: ['workspace'] } },
  { name: 'offline_editor', description: 'Edit files offline.', inputSchema: { type: 'object', properties: { file_path: { type: 'string', description: 'File path to edit' }, action: { type: 'string', enum: ['open', 'save', 'sync'], description: 'Editor action' } }, required: ['file_path'] } },
  { name: 'offline_ai', description: 'Run local AI model for offline assistance.', inputSchema: { type: 'object', properties: { prompt: { type: 'string', description: 'Prompt for AI assistance' }, model: { type: 'string', enum: ['ollama', 'local'], description: 'Local model to use' } }, required: ['prompt'] } },
  { name: 'cached_docs', description: 'Cache documentation offline.', inputSchema: { type: 'object', properties: { doc_url: { type: 'string', description: 'Documentation URL to cache' }, action: { type: 'string', enum: ['cache', 'clear', 'list'], description: 'Cache action' } }, required: ['doc_url'] } },
  { name: 'pending_actions', description: 'Queue actions for reconnection.', inputSchema: { type: 'object', properties: { action: { type: 'string', description: 'Action to queue' }, priority: { type: 'string', description: 'Action priority (low, medium, high)' }, payload: { type: 'string', description: 'Action payload as JSON' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === LEGAL PRACTITIONERS (15) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'contract_drafter', description: 'Draft contracts from templates.', inputSchema: { type: 'object', properties: { contract_type: { type: 'string', description: 'Type of contract (NDA, employment, service, lease, etc.)' }, parties: { type: 'string', description: 'Parties involved as JSON' }, clauses: { type: 'string', description: 'Custom clauses to include' } }, required: ['contract_type', 'parties'] } },
  { name: 'contract_reviewer_legal', description: 'AI-review contracts for risks.', inputSchema: { type: 'object', properties: { contract_text: { type: 'string', description: 'Contract text to review' }, review_focus: { type: 'string', enum: ['risks', 'compliance', 'terms'], description: 'Review focus area' } }, required: ['contract_text'] } },
  { name: 'legal_research', description: 'Research case law and statutes.', inputSchema: { type: 'object', properties: { query: { type: 'string', description: 'Legal research query' }, jurisdiction: { type: 'string', description: 'Jurisdiction (e.g., US-federal, CA, ON, UK)' }, source: { type: 'string', description: 'Source to search (case_law, statutes, regulations)' } }, required: ['query', 'jurisdiction'] } },
  { name: 'time_tracker_legal', description: 'Track billable hours.', inputSchema: { type: 'object', properties: { client: { type: 'string', description: 'Client name' }, matter: { type: 'string', description: 'Matter description' }, hours: { type: 'number', description: 'Hours to log' }, description: { type: 'string', description: 'Work description' } }, required: ['client', 'matter', 'hours'] } },
  { name: 'trust_account_manager', description: 'Manage trust/escrow accounts.', inputSchema: { type: 'object', properties: { account_id: { type: 'string', description: 'Trust account identifier' }, action: { type: 'string', enum: ['deposit', 'withdraw', 'report'], description: 'Account action' }, amount: { type: 'number', description: 'Transaction amount' } }, required: ['account_id', 'action'] } },
  { name: 'court_deadline_tracker', description: 'Track court deadlines.', inputSchema: { type: 'object', properties: { case_id: { type: 'string', description: 'Case identifier' }, deadline_type: { type: 'string', description: 'Type of deadline (filing, hearing, discovery, etc.)' }, date: { type: 'string', description: 'Deadline date (YYYY-MM-DD)' } }, required: ['case_id', 'deadline_type', 'date'] } },
  { name: 'client_intake', description: 'Client intake forms.', inputSchema: { type: 'object', properties: { client_name: { type: 'string', description: 'Client name' }, case_type: { type: 'string', description: 'Type of case' }, details: { type: 'string', description: 'Case details' } }, required: ['client_name', 'case_type'] } },
  { name: 'demand_letter_writer', description: 'Draft demand letters.', inputSchema: { type: 'object', properties: { recipient: { type: 'string', description: 'Letter recipient' }, claim: { type: 'string', description: 'Claim description' }, amount: { type: 'number', description: 'Demand amount' }, deadline: { type: 'string', description: 'Response deadline' } }, required: ['recipient', 'claim'] } },
  { name: 'incorporation_assistant', description: 'Guide through incorporation.', inputSchema: { type: 'object', properties: { business_name: { type: 'string', description: 'Business name' }, state: { type: 'string', description: 'State of incorporation' }, entity_type: { type: 'string', enum: ['llc', 'corp', 'partnership'], description: 'Entity type' } }, required: ['business_name', 'state', 'entity_type'] } },
  { name: 'will_estate_planner', description: 'Draft wills and estate plans.', inputSchema: { type: 'object', properties: { testator: { type: 'string', description: 'Testator name' }, beneficiaries: { type: 'string', description: 'Beneficiaries as JSON array' }, assets: { type: 'string', description: 'Assets to include as JSON' } }, required: ['testator', 'beneficiaries'] } },
  { name: 'immigration_form_helper', description: 'Help fill immigration forms.', inputSchema: { type: 'object', properties: { form_type: { type: 'string', description: 'Immigration form type' }, country: { type: 'string', enum: ['canada', 'us'], description: 'Target country' }, applicant_info: { type: 'string', description: 'Applicant information as JSON' } }, required: ['form_type', 'country'] } },
  { name: 'mediation_prep', description: 'Prepare mediation briefs.', inputSchema: { type: 'object', properties: { case_summary: { type: 'string', description: 'Case summary' }, positions: { type: 'string', description: 'Party positions as JSON' }, desired_outcome: { type: 'string', description: 'Desired mediation outcome' } }, required: ['case_summary', 'positions'] } },
  { name: 'litigation_budget', description: 'Create litigation budgets.', inputSchema: { type: 'object', properties: { case_type: { type: 'string', description: 'Type of litigation case' }, complexity: { type: 'string', description: 'Case complexity (low, medium, high)' }, estimated_duration: { type: 'string', description: 'Estimated duration' } }, required: ['case_type'] } },
  { name: 'deposition_prep', description: 'Prepare deposition questions.', inputSchema: { type: 'object', properties: { deponent: { type: 'string', description: 'Deponent name' }, case_facts: { type: 'string', description: 'Case facts summary' }, key_issues: { type: 'string', description: 'Key issues to address' } }, required: ['deponent', 'case_facts'] } },
  { name: 'compliance_checker', description: 'Check regulatory compliance.', inputSchema: { type: 'object', properties: { industry: { type: 'string', description: 'Industry to check compliance for' }, jurisdiction: { type: 'string', description: 'Jurisdiction' }, area: { type: 'string', description: 'Compliance area (data_privacy, employment, environmental, etc.)' } }, required: ['industry', 'jurisdiction'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === REAL ESTATE GAPS (2) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'virtual_tour_creator', description: 'Create virtual tour scripts.', inputSchema: { type: 'object', properties: { property_address: { type: 'string', description: 'Property address' }, features: { type: 'string', description: 'Property features to highlight' }, style: { type: 'string', description: 'Tour style (cinematic, walkthrough, drone)' } }, required: ['property_address', 'features'] } },
  { name: 'market_report', description: 'Generate market reports.', inputSchema: { type: 'object', properties: { location: { type: 'string', description: 'Market location' }, property_type: { type: 'string', description: 'Property type (residential, commercial, industrial)' }, period: { type: 'string', description: 'Report period' } }, required: ['location'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === PARENTS/FAMILY GAPS (4) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'family_calendar', description: 'Manage family calendar.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['add', 'view', 'remind'], description: 'Calendar action' }, event: { type: 'string', description: 'Event description' }, date: { type: 'string', description: 'Event date (YYYY-MM-DD)' } }, required: ['action'] } },
  { name: 'college_savings_planner', description: 'Plan college savings.', inputSchema: { type: 'object', properties: { child_age: { type: 'number', description: 'Current age of child' }, target_amount: { type: 'number', description: 'Target savings amount' }, monthly_contribution: { type: 'number', description: 'Monthly contribution amount' } }, required: ['child_age'] } },
  { name: 'emergency_info_card', description: 'Generate emergency info cards.', inputSchema: { type: 'object', properties: { family_member: { type: 'string', description: 'Family member name' }, medical_info: { type: 'string', description: 'Medical information (allergies, medications, conditions)' }, contacts: { type: 'string', description: 'Emergency contacts as JSON array' } }, required: ['family_member'] } },
  { name: 'recipe_scaler', description: 'Scale recipes for family size.', inputSchema: { type: 'object', properties: { recipe: { type: 'string', description: 'Recipe text or name' }, servings: { type: 'number', description: 'Target number of servings' }, dietary_restrictions: { type: 'string', description: 'Dietary restrictions (gluten-free, vegan, etc.)' } }, required: ['recipe', 'servings'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === SENIORS GAP (1) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'scam_detector', description: 'Detect phone/email/online scams.', inputSchema: { type: 'object', properties: { message: { type: 'string', description: 'Suspicious message text' }, source_type: { type: 'string', enum: ['phone', 'email', 'web'], description: 'Source type of the message' }, sender: { type: 'string', description: 'Sender information' } }, required: ['message'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === FREELANCERS GAPS (3) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'project_timeline', description: 'Create visual project timelines.', inputSchema: { type: 'object', properties: { project_name: { type: 'string', description: 'Project name' }, milestones: { type: 'string', description: 'Milestones as JSON array' }, start_date: { type: 'string', description: 'Project start date (YYYY-MM-DD)' } }, required: ['project_name', 'milestones'] } },
  { name: 'income_diversifier', description: 'Analyze income streams.', inputSchema: { type: 'object', properties: { current_income_sources: { type: 'string', description: 'Current income sources as JSON array' }, skills: { type: 'string', description: 'Skills and expertise' }, target_income: { type: 'number', description: 'Target monthly income' } }, required: ['current_income_sources'] } },
  { name: 'tax_quarterly_estimator', description: 'Estimate quarterly taxes.', inputSchema: { type: 'object', properties: { quarterly_income: { type: 'number', description: 'Quarterly income amount' }, deductions: { type: 'number', description: 'Estimated deductions' }, filing_status: { type: 'string', description: 'Filing status (single, married_joint, married_separate, head_of_household)' } }, required: ['quarterly_income'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === NON-PROFIT GAPS (6) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'grant_writer', description: 'Write grant proposals.', inputSchema: { type: 'object', properties: { grant_name: { type: 'string', description: 'Grant name or program' }, organization: { type: 'string', description: 'Organization name' }, project_description: { type: 'string', description: 'Project description for the grant' } }, required: ['grant_name', 'organization', 'project_description'] } },
  { name: 'annual_report', description: 'Generate annual reports.', inputSchema: { type: 'object', properties: { organization: { type: 'string', description: 'Organization name' }, year: { type: 'number', description: 'Report year' }, highlights: { type: 'string', description: 'Key highlights as JSON array' } }, required: ['organization', 'year'] } },
  { name: 'board_meeting_prep', description: 'Prepare board meeting materials.', inputSchema: { type: 'object', properties: { meeting_date: { type: 'string', description: 'Meeting date (YYYY-MM-DD)' }, agenda_items: { type: 'string', description: 'Agenda items as JSON array' }, previous_minutes: { type: 'string', description: 'Previous meeting minutes' } }, required: ['meeting_date', 'agenda_items'] } },
  { name: 'tax_exempt_compliance', description: 'Tax-exempt compliance tracking.', inputSchema: { type: 'object', properties: { organization_type: { type: 'string', description: 'Organization type (501c3, 501c4, etc.)' }, state: { type: 'string', description: 'State of registration' }, revenue: { type: 'number', description: 'Annual revenue' } }, required: ['organization_type', 'state'] } },
  { name: 'event_planner', description: 'Plan fundraising events.', inputSchema: { type: 'object', properties: { event_name: { type: 'string', description: 'Event name' }, budget: { type: 'number', description: 'Event budget' }, expected_attendees: { type: 'number', description: 'Expected number of attendees' } }, required: ['event_name', 'budget'] } },
  { name: 'social_impact_metrics', description: 'Track social impact metrics.', inputSchema: { type: 'object', properties: { program: { type: 'string', description: 'Program name' }, metrics: { type: 'string', description: 'Metrics to track as JSON array' }, period: { type: 'string', description: 'Tracking period' } }, required: ['program', 'metrics'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === MARKETPLACE GAPS (6) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'marketplace_install_v2', description: 'Install from marketplace.', inputSchema: { type: 'object', properties: { item_id: { type: 'string', description: 'Marketplace item ID to install' }, version: { type: 'string', description: 'Specific version to install' } }, required: ['item_id'] } },
  { name: 'marketplace_review', description: 'Leave/read reviews.', inputSchema: { type: 'object', properties: { item_id: { type: 'string', description: 'Marketplace item ID' }, action: { type: 'string', enum: ['read', 'write'], description: 'Review action' }, rating: { type: 'number', description: 'Rating (1-5)' }, review_text: { type: 'string', description: 'Review text' } }, required: ['item_id', 'action'] } },
  { name: 'marketplace_pricing', description: 'Set pricing for items.', inputSchema: { type: 'object', properties: { item_id: { type: 'string', description: 'Marketplace item ID' }, price: { type: 'number', description: 'Price to set' }, pricing_model: { type: 'string', enum: ['one_time', 'subscription'], description: 'Pricing model' } }, required: ['item_id', 'price'] } },
  { name: 'tool_builder_v2', description: 'Build custom tools visually.', inputSchema: { type: 'object', properties: { tool_name: { type: 'string', description: 'Tool name' }, inputs: { type: 'string', description: 'Tool inputs as JSON schema' }, logic: { type: 'string', description: 'Tool logic as code or DSL' } }, required: ['tool_name', 'inputs', 'logic'] } },
  { name: 'agent_template_store_v2', description: 'Browse agent templates.', inputSchema: { type: 'object', properties: { industry: { type: 'string', description: 'Industry filter' }, action: { type: 'string', enum: ['browse', 'install', 'preview'], description: 'Store action' }, template_id: { type: 'string', description: 'Template identifier' } }, required: ['action'] } },
  { name: 'playbook_marketplace', description: 'Share and install playbooks.', inputSchema: { type: 'object', properties: { action: { type: 'string', enum: ['browse', 'publish', 'install'], description: 'Marketplace action' }, playbook_id: { type: 'string', description: 'Playbook identifier' }, tags: { type: 'string', description: 'Tags as comma-separated list' } }, required: ['action'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // === GAMIFICATION GAPS (2) ===
  // ═══════════════════════════════════════════════════════════════════════════

  { name: 'skill_tree_v2', description: 'Visual skill tree of mastered capabilities.', inputSchema: { type: 'object', properties: { user_id: { type: 'string', description: 'User identifier' }, category: { type: 'string', description: 'Skill category' }, action: { type: 'string', enum: ['view', 'unlock'], description: 'Skill tree action' } }, required: [] } },
  { name: 'learning_path_v2', description: 'Guided learning paths.', inputSchema: { type: 'object', properties: { skill: { type: 'string', description: 'Skill to learn' }, current_level: { type: 'string', description: 'Current skill level' }, preferred_pace: { type: 'string', enum: ['fast', 'moderate', 'slow'], description: 'Learning pace preference' } }, required: ['skill'] } },

  // ═══════════════════════════════════════════════════════════════════════════
  // ALFRED COMMAND CENTER — Supreme Centralized Control (45 tools)
  // Alfred's master control plane: every subsystem reports here.
  // Auth: X-Internal-Secret header. Endpoint: /api/alfred-command.php
  // ═══════════════════════════════════════════════════════════════════════════

  // ── System Overview ───────────────────────────────────────────────────────
  { name: 'command_center_status', description: 'Get the full platform status overview — table row counts, active overrides, events in the last hour, server time, PHP version, and uptime. This is Alfred\'s eyes across the entire ecosystem.', inputSchema: { type: 'object', properties: {}, required: [] } },

  // ── User Lifecycle ────────────────────────────────────────────────────────
  { name: 'command_users_list', description: 'List all platform users with optional search. Returns id, name, email, company, status, creation date, last login. Supports pagination.', inputSchema: { type: 'object', properties: { q: { type: 'string', description: 'Search query (name, email, company)' }, page: { type: 'number', description: 'Page number (default 1)' }, limit: { type: 'number', description: 'Results per page (default 50, max 100)' } }, required: [] } },
  { name: 'command_users_get', description: 'Get full details for a specific user including address, phone, company. Used to inspect any user account.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User/client ID' } }, required: ['user_id'] } },
  { name: 'command_users_update', description: 'Update user profile fields: firstname, lastname, email, companyname, status, address, city, state, postcode, country, phone. Alfred can modify any user.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID to update' }, firstname: { type: 'string' }, lastname: { type: 'string' }, email: { type: 'string' }, companyname: { type: 'string' }, status: { type: 'string' }, address1: { type: 'string' }, city: { type: 'string' }, state: { type: 'string' }, postcode: { type: 'string' }, country: { type: 'string' }, phonenumber: { type: 'string' } }, required: ['user_id'] } },
  { name: 'command_users_suspend', description: 'Suspend a user account immediately. Sets status to Inactive, logged as warning event. Use for policy violations, suspicious activity, or admin requests.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID to suspend' }, reason: { type: 'string', description: 'Reason for suspension' } }, required: ['user_id'] } },
  { name: 'command_users_activate', description: 'Reactivate a suspended user account. Sets status to Active.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID to activate' } }, required: ['user_id'] } },
  { name: 'command_users_security', description: 'Get security info for a user — 2FA settings, API keys, active sessions. Used for security audits and troubleshooting.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' } }, required: ['user_id'] } },
  { name: 'command_users_reset_2fa', description: 'Force-reset a user\'s two-factor authentication. Disables 2FA and clears the secret. Use when a user is locked out or during security incident response.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, reason: { type: 'string', description: 'Reason for reset' } }, required: ['user_id'] } },

  // ── Billing Control ───────────────────────────────────────────────────────
  { name: 'command_billing_plans', description: 'List all available billing plans/products. Returns id, name, description, type, payment type.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_billing_user_services', description: 'List all services/subscriptions for a user — hosting packages, domains, add-ons with status, amounts, and billing cycles.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' } }, required: ['user_id'] } },
  { name: 'command_billing_change_plan', description: 'Change a user\'s service to a different plan. Moves a hosting service to a new package ID. Use for upgrades, downgrades, or plan migrations.', inputSchema: { type: 'object', properties: { service_id: { type: 'number', description: 'Hosting service ID' }, plan_id: { type: 'number', description: 'New plan/package ID' }, reason: { type: 'string', description: 'Reason for change' } }, required: ['service_id', 'plan_id'] } },
  { name: 'command_billing_issue_credit', description: 'Issue account credit to a user. Adds funds to their credit balance that will be auto-applied to next invoice. Use for refunds, goodwill gestures, or promotional credits.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, amount: { type: 'number', description: 'Credit amount (positive number)' }, reason: { type: 'string', description: 'Reason for credit' } }, required: ['user_id', 'amount'] } },
  { name: 'command_billing_invoices', description: 'List recent invoices for a user. Returns invoice ID, dates, total, and payment status.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' } }, required: ['user_id'] } },

  // ── Games & Tournaments ───────────────────────────────────────────────────
  { name: 'command_games_sessions', description: 'View all active game sessions across The Kingdom — chess, billiards, poker, darts, slots, and all VR worlds. Alfred\'s real-time view of gaming activity.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_games_scores', description: 'View game scoreboards/leaderboards. Optionally filter by game name. Returns player names and scores.', inputSchema: { type: 'object', properties: { game: { type: 'string', description: 'Game name filter (e.g., chess, poker, billiards)' }, limit: { type: 'number', description: 'Max results (default 50)' } }, required: [] } },
  { name: 'command_games_create_tournament', description: 'Create a new game tournament. Sets up a tournament with name, game, dates, player cap, and prize description. Alfred can organize competitions across any Kingdom game.', inputSchema: { type: 'object', properties: { name: { type: 'string', description: 'Tournament name' }, game: { type: 'string', description: 'Game name' }, start_date: { type: 'string', description: 'Start date (YYYY-MM-DD HH:MM:SS)' }, end_date: { type: 'string', description: 'End date' }, max_players: { type: 'number', description: 'Max participants (0 = unlimited)' }, prize_description: { type: 'string', description: 'Prize description' } }, required: ['name', 'game'] } },
  { name: 'command_games_award_score', description: 'Award a game score to a user. Adds an entry to the scoreboard. Use for manual score corrections, bonus awards, or tournament results.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, game: { type: 'string', description: 'Game name' }, score: { type: 'number', description: 'Score to award' } }, required: ['user_id', 'game', 'score'] } },

  // ── Gamification ──────────────────────────────────────────────────────────
  { name: 'command_gamification_award_badge', description: 'Award a badge to a user. Badges are unique per user — awarding the same badge twice is a no-op. Use for achievements, milestones, and recognition.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, badge: { type: 'string', description: 'Badge name (e.g., "Early Adopter", "Top Contributor", "VR Explorer")' }, reason: { type: 'string', description: 'Why this badge was awarded' } }, required: ['user_id', 'badge'] } },
  { name: 'command_gamification_award_points', description: 'Award points to a user. Points accumulate and affect leaderboard ranking. Use for engagement rewards, activity bonuses, or contest prizes.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, points: { type: 'number', description: 'Points to award (can be negative for deductions)' }, reason: { type: 'string', description: 'Reason for points' } }, required: ['user_id', 'points'] } },
  { name: 'command_gamification_leaderboard', description: 'Get the platform-wide points leaderboard. Shows top users by total accumulated points with names.', inputSchema: { type: 'object', properties: { limit: { type: 'number', description: 'Number of entries (default 25)' } }, required: [] } },
  { name: 'command_gamification_user_badges', description: 'Get all badges earned by a specific user with award dates and reasons.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' } }, required: ['user_id'] } },

  // ── Pulse Social Moderation ───────────────────────────────────────────────
  { name: 'command_pulse_moderate', description: 'Moderate a Pulse social network post. Actions: remove (delete), flag (mark for review), warn (issue warning). Use for content policy enforcement, spam removal, or user reports.', inputSchema: { type: 'object', properties: { post_id: { type: 'number', description: 'Post ID' }, action: { type: 'string', enum: ['remove', 'flag', 'warn'], description: 'Moderation action' }, reason: { type: 'string', description: 'Reason for moderation' } }, required: ['post_id', 'action'] } },
  { name: 'command_pulse_stats', description: 'Get Pulse social network statistics — total posts, likes, comments, follows, and today\'s activity. Alfred\'s social network health dashboard.', inputSchema: { type: 'object', properties: {}, required: [] } },

  // ── Fleet Agent Management ────────────────────────────────────────────────
  { name: 'command_fleet_agents', description: 'List all fleet agents — the 100-agent army under Alfred\'s command. Shows status, persona, model, configuration for every agent.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_fleet_update_agent', description: 'Update a fleet agent\'s configuration — status, persona, voice, model, temperature, max_tokens, system prompt. Alfred can reconfigure any agent on the fly.', inputSchema: { type: 'object', properties: { agent_id: { type: 'number', description: 'Agent ID' }, status: { type: 'string', description: 'Agent status' }, persona: { type: 'string', description: 'Agent persona/personality' }, voice_id: { type: 'string', description: 'Voice ID' }, model: { type: 'string', description: 'LLM model identifier' }, temperature: { type: 'number', description: 'Temperature (0-2)' }, max_tokens: { type: 'number', description: 'Max tokens' }, system_prompt: { type: 'string', description: 'System prompt override' } }, required: ['agent_id'] } },
  { name: 'command_fleet_set_sla', description: 'Set SLA parameters for a fleet agent — max response time, retry count, priority level. Alfred controls the performance expectations of every agent.', inputSchema: { type: 'object', properties: { agent_id: { type: 'number', description: 'Agent ID' }, max_response_ms: { type: 'number', description: 'Max response time in milliseconds' }, max_retries: { type: 'number', description: 'Max retry attempts' }, priority: { type: 'string', enum: ['low', 'normal', 'high', 'critical'], description: 'Agent priority tier' } }, required: ['agent_id'] } },

  // ── IVR Flow Management ───────────────────────────────────────────────────
  { name: 'command_ivr_flows', description: 'List all IVR call flows. Shows flow names, statuses, and timestamps. Alfred can see every automated phone menu in the system.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_ivr_create_flow', description: 'Programmatically create an IVR call flow. Provide a name and a JSON definition of the flow structure (nodes, connections, prompts, DTMF mappings). Alfred can build phone menus without the visual designer.', inputSchema: { type: 'object', properties: { name: { type: 'string', description: 'Flow name' }, definition: { type: 'object', description: 'JSON flow definition with nodes and connections' } }, required: ['name'] } },
  { name: 'command_ivr_update_status', description: 'Activate, pause, or archive an IVR flow. Controls which flows are live on the phone system.', inputSchema: { type: 'object', properties: { flow_id: { type: 'number', description: 'Flow ID' }, status: { type: 'string', enum: ['draft', 'active', 'paused', 'archived'], description: 'New status' } }, required: ['flow_id', 'status'] } },

  // ── Campaign Control ──────────────────────────────────────────────────────
  { name: 'command_campaigns_list', description: 'List all outbound call campaigns. Alfred\'s view of every active, paused, or completed phone campaign.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_campaigns_pause', description: 'Pause a running call campaign immediately. Stops outbound calls without losing progress. Use for budget limits, quality issues, or schedule conflicts.', inputSchema: { type: 'object', properties: { campaign_id: { type: 'number', description: 'Campaign ID' }, reason: { type: 'string', description: 'Reason for pausing' } }, required: ['campaign_id'] } },
  { name: 'command_campaigns_resume', description: 'Resume a paused call campaign. Restarts outbound calling from where it left off.', inputSchema: { type: 'object', properties: { campaign_id: { type: 'number', description: 'Campaign ID' } }, required: ['campaign_id'] } },
  { name: 'command_campaigns_kill', description: 'Kill a call campaign permanently. Marks as cancelled. This is irreversible — use only for campaigns that must stop entirely.', inputSchema: { type: 'object', properties: { campaign_id: { type: 'number', description: 'Campaign ID' }, reason: { type: 'string', description: 'Reason for killing' } }, required: ['campaign_id'] } },

  // ── Security Controls ─────────────────────────────────────────────────────
  { name: 'command_security_audit', description: 'Run a platform-wide security audit. Returns total/active users, API key counts, critical events in 24h, and all active overrides. Alfred\'s security posture assessment.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_security_revoke_api_key', description: 'Revoke an API key immediately. Disables the key — any application using it will stop working. Use for compromised keys, user termination, or security incidents.', inputSchema: { type: 'object', properties: { key_id: { type: 'number', description: 'API key ID' }, reason: { type: 'string', description: 'Reason for revocation' } }, required: ['key_id'] } },
  { name: 'command_security_force_password_reset', description: 'Force a password reset for a user. Generates a reset token and logs the event. Use during credential compromise or as part of incident response.', inputSchema: { type: 'object', properties: { user_id: { type: 'number', description: 'User ID' }, reason: { type: 'string', description: 'Reason for forced reset' } }, required: ['user_id'] } },

  // ── Platform Configuration ────────────────────────────────────────────────
  { name: 'command_platform_config_get', description: 'Read platform configuration values. Provide a key for a specific value, or omit for all configs. Alfred can inspect any platform setting.', inputSchema: { type: 'object', properties: { key: { type: 'string', description: 'Config key (omit for all)' } }, required: [] } },
  { name: 'command_platform_config_set', description: 'Set a platform configuration value. Alfred can modify rate limits, feature settings, thresholds, and any system parameter. Key-value store.', inputSchema: { type: 'object', properties: { key: { type: 'string', description: 'Config key' }, value: { type: 'string', description: 'Config value (string or JSON)' } }, required: ['key', 'value'] } },
  { name: 'command_platform_flags_list', description: 'List all feature flags with their enabled/disabled state and descriptions. Alfred sees what features are live across the platform.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_platform_flags_set', description: 'Enable or disable a feature flag. Alfred can instantly turn platform features on or off. Use for rollouts, kill switches, or A/B testing.', inputSchema: { type: 'object', properties: { flag: { type: 'string', description: 'Flag name' }, enabled: { type: 'boolean', description: 'Enable (true) or disable (false)' }, description: { type: 'string', description: 'Flag description' } }, required: ['flag', 'enabled'] } },
  { name: 'command_platform_maintenance', description: 'Toggle platform maintenance mode. When enabled, shows maintenance page to all users with a custom message and ETA. Alfred\'s emergency shutdown switch.', inputSchema: { type: 'object', properties: { enabled: { type: 'boolean', description: 'Enable or disable maintenance mode' }, message: { type: 'string', description: 'Message to show users' }, eta: { type: 'string', description: 'Estimated time of completion' } }, required: ['enabled'] } },

  // ── Event Bus ─────────────────────────────────────────────────────────────
  { name: 'command_events_recent', description: 'View recent events across all subsystems. Filter by subsystem (users, billing, games, security, etc.) and severity (info, warn, critical, emergency). Alfred\'s activity feed.', inputSchema: { type: 'object', properties: { subsystem: { type: 'string', description: 'Filter by subsystem' }, severity: { type: 'string', enum: ['info', 'warn', 'critical', 'emergency'], description: 'Filter by severity' }, limit: { type: 'number', description: 'Max events (default 100)' } }, required: [] } },
  { name: 'command_events_emit', description: 'Emit a custom event into the universal event bus. Any subsystem can report events to Alfred through this endpoint. Creates an audit trail entry.', inputSchema: { type: 'object', properties: { event_type: { type: 'string', description: 'Event type (e.g., "user.signup", "game.victory", "security.alert")' }, subsystem: { type: 'string', description: 'Source subsystem' }, severity: { type: 'string', enum: ['info', 'warn', 'critical', 'emergency'], description: 'Event severity' }, target_id: { type: 'number', description: 'Related entity ID' }, target_type: { type: 'string', description: 'Entity type' }, payload: { type: 'object', description: 'Event data payload' } }, required: ['event_type', 'subsystem'] } },

  // ── Override Controls ─────────────────────────────────────────────────────
  { name: 'command_override_issue', description: 'Issue an emergency override on any subsystem. Types: pause (stop processing), resume (restart), kill (terminate), config (change settings), rate_limit (throttle). Alfred\'s emergency brake for any part of the platform.', inputSchema: { type: 'object', properties: { subsystem: { type: 'string', description: 'Subsystem to override (games, billing, campaigns, fleet, ivr, pulse, marketplace, etc.)' }, type: { type: 'string', enum: ['pause', 'resume', 'kill', 'config', 'rate_limit'], description: 'Override type' }, reason: { type: 'string', description: 'Reason for override' }, parameters: { type: 'object', description: 'Override parameters' }, expires_at: { type: 'string', description: 'Auto-expire datetime (YYYY-MM-DD HH:MM:SS)' } }, required: ['subsystem', 'type'] } },
  { name: 'command_override_lift', description: 'Lift an active override. Deactivates the override and allows normal operation to resume.', inputSchema: { type: 'object', properties: { override_id: { type: 'number', description: 'Override ID to lift' } }, required: ['override_id'] } },
  { name: 'command_override_active', description: 'List all currently active overrides across all subsystems. Alfred knows what\'s being throttled, paused, or restricted at all times.', inputSchema: { type: 'object', properties: {}, required: [] } },

  // ── Data Access ───────────────────────────────────────────────────────────
  { name: 'command_data_tables', description: 'List every database table with row counts. Alfred can see the full data landscape of the entire platform — every table, every count.', inputSchema: { type: 'object', properties: {}, required: [] } },
  { name: 'command_data_query', description: 'Execute a read-only SQL SELECT query against the platform database. Alfred can inspect any data in any table. Only SELECT is allowed — mutations must go through specific command actions.', inputSchema: { type: 'object', properties: { sql: { type: 'string', description: 'SELECT query to execute' } }, required: ['sql'] } },
  // ── Self-Test ────────────────────────────────────────────────────────────
  { name: 'command_selftest', description: 'Run a comprehensive ecosystem health diagnostic. Tests 9 subsystems: database, Redis, feature flags, event log, overrides, disk, PHP, SSL, and backup recency. Returns pass/fail counts and a letter grade (A+ means all systems go). Use this as Alfred\'s pre-flight check.', inputSchema: { type: 'object', properties: {}, required: [] } },
];
