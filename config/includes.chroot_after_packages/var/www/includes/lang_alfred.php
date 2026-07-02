<?php
/**
 * Alfred AI page translations (EN/FR)
 * Loaded by alfred.php — extends the main $LANG_STRINGS array.
 */

if (!isset($LANG_STRINGS)) {
    $LANG_STRINGS = ['en' => [], 'fr' => [], 'he' => []];
}

// ─── ENGLISH ───
$LANG_STRINGS['en'] = array_merge($LANG_STRINGS['en'], [
    // Page meta
    'alf_meta_title'       => 'Meet Alfred — The World\'s First AI Hosting Assistant | 13,000+ Tools, 17 AI Engines | GoSiteMe',
    'alf_meta_desc'        => 'Alfred is the world\'s first AI hosting assistant. 13,000+ tools. 17 intelligence engines. 89 categories including e-commerce, SEO, DevOps, design, authentication, data, content, accessibility, and more — all by talking naturally. No commands. No code. Just results.',

    // Hero
    'alf_hero_badge'       => 'The World\'s First AI Hosting Assistant',
    'alf_hero_h1'          => 'Meet Alfred.<br>The AI That Runs Your Entire Business.',
    'alf_hero_sub'         => '13,000+ AI Tools. 17 intelligence engines. 89 categories — with command steering so you never wait. E-commerce, SEO, DevOps, design, authentication, data management, content, accessibility — plus browser agent, code interpreter, and RAG knowledge base. Talk to Alfred like a friend — and watch your entire business just... happen. From any device. Any app. Anywhere.',
    'alf_hero_sub2'        => 'No terminal. No cPanel. No code. Alfred browses the web for you. Runs Python. Generates charts. Monitors your servers 24/7. Fixes problems before you wake up.<br>This isn\'t hosting. <strong style="color:#00D4FF;">This is the future.</strong>',
    'alf_hero_cta_start'   => 'Start AI Hosting — $15/mo',
    'alf_hero_cta_watch'   => 'Watch It In Action',
    'alf_hero_promo'       => 'Use code <strong>LAUNCH50</strong> for 50% off your first year. Cancel anytime.',
    'alf_stat_tools'       => 'AI Tools',
    'alf_stat_categories'  => 'Categories',
    'alf_stat_commands'    => 'Commands to Learn',
    'alf_stat_always'      => 'Always On',
    'alf_stat_possibilities' => 'Possibilities',

    // Commercial
    'alf_scene_label'      => 'Picture This',
    'alf_scene_h2'         => 'It\'s 9 PM. You Just Had an Idea for a Business.',
    'alf_scene_intro'      => 'No developer. No agency. No $5,000 invoice. Just you, a laptop, and Alfred.<br>Here\'s what happens in the next 12 minutes.',
    'alf_scene_v1'         => '"Alfred, set up a WordPress site on mybakery.com"',
    'alf_scene_r1'         => '<strong>&check; Done in 8 seconds.</strong> WordPress installed. SSL certificate provisioned. DNS configured. Your site is LIVE at <code>https://mybakery.com</code>.',
    'alf_scene_v2'         => '"Install WooCommerce and add 3 products with prices"',
    'alf_scene_r2'         => '<strong>&check; Done.</strong> WooCommerce activated. 3 products created with descriptions, images, and pricing. Payment gateway ready.',
    'alf_scene_v3'         => '"Generate a hero image — a rustic bakery with warm morning light"',
    'alf_scene_r3'         => '<strong>&check; Image generated.</strong> AI created a stunning 1024&times;1024 hero image in 4.2 seconds. Saved to <code>/ai-images/</code>. Public URL ready.',
    'alf_scene_v4'         => '"Set up email me@mybakery.com and forward to my Gmail"',
    'alf_scene_r4'         => '<strong>&check; Done.</strong> Email account created. Forwarding enabled. DKIM, SPF, and DMARC configured.',
    'alf_scene_v5'         => '"Check if my site is fast and secure"',
    'alf_scene_r5'         => '<strong>&check; Health Report:</strong> SSL valid, DNS resolving, response time 0.28s, no malware, permissions correct. Performance: <strong style="color:#10b981;">A+</strong>.',
    'alf_scene_v6'         => '"Back up everything and commit to Git"',
    'alf_scene_r6'         => '<strong>&check; Done.</strong> Full backup created. Git repository initialized, all files committed. Roll back anytime.',
    'alf_scene_time'       => 'Total time: <strong style="color:#fff;">~12 minutes</strong>. Commands typed: <strong style="color:#10b981;">zero</strong>.',
    'alf_scene_cost'       => 'Agency cost: <strong style="color:#fb923c;">$2,000–$10,000</strong>. With Alfred: <strong style="color:#00D4FF;">$15/month</strong>.',

    // Image generation
    'alf_img_h2'           => 'AI Image Generation.<br>Built Right In.',
    'alf_img_sub'          => 'Cursor can\'t do this. Lovable can\'t do this. VS Code can\'t do this.<br><strong style="color:#00D4FF;">Alfred can.</strong> Generate images from text and save them directly to your website.',
    'alf_img_p1'           => '"Create a hero image of a modern coffee shop"',
    'alf_img_p2'           => '"Design a logo for TechFlow Solutions"',
    'alf_img_p3'           => '"Product photo of artisan bread on wood"',
    'alf_img_p4'           => '"Abstract header background, purple waves"',
    'alf_img_gen'          => 'Generated in',
    'alf_img_style'        => 'Style:',
    'alf_img_presets'      => '7 style presets:',
    'alf_img_presets_list' => 'photo &middot; illustration &middot; logo &middot; abstract &middot; hero &middot; product &middot; avatar',
    'alf_img_powered'      => 'Multiple AI models. Images saved directly to your <code>/ai-images/</code> folder.',

    // Voice & Messaging
    'alf_vm_badge'         => 'The Feature Nobody Else Has',
    'alf_vm_h2'            => 'Command Alfred From Anywhere.<br>Voice. Messages. Any App.',
    'alf_vm_sub'           => 'Talk to Alfred in your browser with voice. Or send a message from WhatsApp, Signal, Discord, Telegram, or SMS.<br><strong style="color:#00D4FF;">Your website listens to you — wherever you are.</strong>',
    'alf_vm_connected'     => '&#10003; Connected',
    'alf_vm_voice'         => 'Voice',
    'alf_vm_browser'       => '&#10003; Browser Native',
    'alf_vm_how'           => 'See How It Works',
    'alf_vm_q1'            => 'Hey Alfred, how\'s my traffic today?',
    'alf_vm_a1'            => '<strong>&#10003; 847 visitors today</strong> (+12% vs yesterday). Top page: /products (312 views). 94% human traffic. No anomalies. Site health: <strong>A+</strong>.',
    'alf_vm_q2'            => 'Install a contact form plugin on mybakery.com',
    'alf_vm_a2'            => '<strong>&#10003; Done.</strong> WPForms Lite installed and activated on mybakery.com. A default contact form is ready at /contact. Customize at /wp-admin/admin.php?page=wpforms.',
    'alf_vm_q3'            => 'Generate a banner image — sunset over mountains, warm tones',
    'alf_vm_a3'            => '<strong>&#10003; Image generated</strong> in 4.1 seconds. 1792&times;1024, style: hero. Saved to /ai-images/banner-sunset-mountains.png. <strong>Public URL ready.</strong>',
    'alf_vm_q4'            => 'Is my SSL still valid?',
    'alf_vm_a4'            => '<strong>&#10003; SSL valid.</strong> Let\'s Encrypt certificate for mybakery.com expires March 14, 2027. Auto-renewal enabled. Grade: A+. All good.',
    'alf_vm_kicker1'       => 'You\'re at a coffee shop. On the bus. In a meeting.',
    'alf_vm_kicker2'       => 'Your phone buzzes — a customer asked a question on your site.',
    'alf_vm_kicker3'       => 'You open <strong>WhatsApp</strong> and type: <em style="color:#c084fc;">"Alfred, check my latest contact form submissions."</em>',
    'alf_vm_kicker4'       => '<strong>3 seconds later</strong>, you have the answer.',
    'alf_vm_kicker5'       => '<strong class="accent">No laptop. No login. No dashboard.</strong>',
    'alf_vm_kicker6'       => 'Just you, your phone, and an AI that never sleeps.',
    'alf_vm_kicker7'       => 'Cursor can\'t do this. Lovable can\'t do this. cPanel can\'t do this. <strong style="color:#fff;">Nobody</strong> can do this.',

    // Comparison
    'alf_cmp_h2'           => 'Alfred vs. Everything Else',
    'alf_cmp_sub'          => 'We built what the industry said was impossible. Here\'s the proof.',
    'alf_cmp_feature'      => 'Feature',
    'alf_cmp_footer'       => '<strong style="color:#fff;">We\'re not just an editor.</strong> We\'re not just hosting. We\'re not just AI.<br>We\'re <strong style="color:#00D4FF;">the entire stack</strong> — code editor, browser agent, code interpreter, RAG knowledge base, image &amp; video AI, workflow engine, server monitor — in one place, for less money.',

    // Comparison row labels
    'alf_cmp_code_editor'  => 'AI Code Editor',
    'alf_cmp_img_gen'      => 'AI Image Generation',
    'alf_cmp_wp_mgmt'      => 'WordPress Management',
    'alf_cmp_domain_dns'   => 'Domain &amp; DNS',
    'alf_cmp_email'        => 'Email Management',
    'alf_cmp_ssl'          => 'SSL &amp; Security',
    'alf_cmp_backups'      => 'Backups &amp; Git',
    'alf_cmp_analytics'    => 'Analytics &amp; Traffic',
    'alf_cmp_billing'      => 'Billing Management',
    'alf_cmp_nl'           => 'Natural Language',
    'alf_cmp_voice'        => 'Voice Commands',
    'alf_cmp_messaging'    => 'Messaging (WhatsApp, Signal&hellip;)',
    'alf_cmp_hosting'      => 'Hosting Included',
    'alf_cmp_total'        => 'Total AI Tools',
    'alf_cmp_price'        => 'Starting Price',

    // Comparison cell values
    'alf_cmp_full_ide'     => '&check; Full IDE',
    'alf_cmp_flux'         => '&check; Multiple AI Models',
    'alf_cmp_11tools'      => '&check; 11 tools',
    'alf_cmp_full_ctrl'    => '&check; Full control',
    'alf_cmp_with_ai'      => '&check; With AI',
    'alf_cmp_automated'    => '&check; Automated',
    'alf_cmp_both'         => '&check; Both',
    'alf_cmp_builtin'      => '&check; Built-in',
    'alf_cmp_14tools'      => '&check; 14 tools',
    'alf_cmp_talk'         => '&check; Talk naturally',
    'alf_cmp_browser_native' => '&check; Browser native',
    'alf_cmp_5plus'        => '&check; 5+ platforms',
    'alf_cmp_ssd_ssl'      => '&check; SSD + SSL + Email',
    'alf_cmp_cant'         => '&cross; Can\'t',
    'alf_cmp_limited'      => 'Limited',
    'alf_cmp_basic'        => 'Basic',
    'alf_cmp_manual'       => 'Manual',
    'alf_cmp_git_only'     => 'Git only',
    'alf_cmp_backups_only' => 'Backups only',
    'alf_cmp_via_plugins'  => 'Via plugins',
    'alf_cmp_visual'       => 'Visual',
    'alf_cmp_gui_only'     => '&cross; GUI only',
    'alf_cmp_byoh'         => '&cross; BYOH',
    'alf_cmp_varies'       => 'Varies',

    // Tokens
    'alf_tok_h2'           => 'Transparent Token Pricing',
    'alf_tok_sub'          => 'Every AI interaction uses tokens. Generous monthly allowances so you never think about it. Here\'s exactly what you get.',
    'alf_tok_builder'      => 'Builder',
    'alf_tok_pro'          => 'Professional',
    'alf_tok_studio'       => 'Studio',
    'alf_tok_business'     => 'Business',
    'alf_tok_per_month'    => 'tokens/month',
    'alf_tok_roughly'      => 'That\'s roughly:',
    'alf_tok_conversations' => '~750 conversations',
    'alf_tok_ai_ops'       => '~150 AI coding sessions',
    'alf_tok_img_gens'     => '~100 image generations',
    'alf_tok_2x'           => '2&times; Builder. For:',
    'alf_tok_freelancers'  => 'Freelancers',
    'alf_tok_small_teams'  => 'Small teams',
    'alf_tok_side_projects' => 'Side projects',
    'alf_tok_5x'           => '5&times; Builder. For:',
    'alf_tok_startups'     => 'Startups',
    'alf_tok_dev_studios'  => 'Dev studios',
    'alf_tok_opus_access'  => 'Premium Model access',
    'alf_tok_10x'          => '10&times; Builder. For:',
    'alf_tok_agencies'     => 'Agencies',
    'alf_tok_enterprise_teams' => 'Enterprise teams',
    'alf_tok_unlimited_opus' => 'Unlimited Premium',
    'alf_tok_how'          => '<strong style="color:#fff;">How tokens work:</strong> A typical question ~400 tokens. A code refactor ~800. An image generation ~1,500. A multi-step task ~2,000. Monitor usage in real-time.',

    // Token Top-Ups
    'alf_topup_h3'         => 'Need More Tokens? Buy a Top-Up Pack',
    'alf_topup_sub'        => 'One-time token packs. Credited instantly. Never expires. Add to any plan.',

    // Power-Up Add-Ons
    'alf_addons_h2'        => 'Power-Up Add-Ons',
    'alf_addons_sub'       => 'Supercharge any plan with extra capabilities. Add one or stack them all. Cancel anytime.',
    'alf_addon_opus'       => 'Unlock our Premium AI Model — the most powerful engine available — for complex reasoning and large-scale refactoring.',
    'alf_addon_sessions'   => 'Run 3 parallel AI conversations simultaneously. Perfect for multitasking across projects.',
    'alf_addon_team'       => 'Add 5 collaborator seats with full IDE access and shared AI token pool.',
    'alf_addon_priority'   => 'Guaranteed 4-hour response time. Priority AI queue. Direct channel with engineering.',
    'alf_addon_backup'     => 'Daily automated workspace snapshots. 30-day retention. One-click restore.',
    'alf_addon_domain'     => 'Map your own domain to deployed apps. Automatic SSL and CDN included.',
    'alf_addon_images'     => 'Extra 300 AI image generations/month. Multiple models for photos, logos, banners, and product shots.',
    'alf_addon_video'      => 'Generate 50 AI videos/month. Multiple AI video models for cinematic-quality content.',
    'alf_addon_dedicated'  => 'Isolated container: 4 vCPU, 8GB RAM, 100GB NVMe. No resource sharing.',
    'alf_addon_whitelabel' => 'Remove Alfred IDE branding. Add your logo, colors, and domain. Perfect for agencies.',

    // 13,000+ Tools section
    'alf_tools_h2'         => '13,000+ Tools. Every One Explained.',
    'alf_tools_sub'        => 'The most powerful AI toolkit ever built for web hosting. 17 intelligence engines. 89 categories. Each tool works through natural conversation.',

    // Tool categories
    'alf_cat_files'        => 'File Management',
    'alf_cat_db'           => 'Database Management',
    'alf_cat_domains'      => 'Domains, DNS &amp; SSL',
    'alf_cat_email'        => 'Email Management',
    'alf_cat_backups'      => 'Backups, Cron &amp; Account',
    'alf_cat_wp'           => 'WordPress Management',
    'alf_cat_git'          => 'Git Version Control',
    'alf_cat_security'     => 'Security &amp; Error Logs',
    'alf_cat_analytics'    => 'Analytics &amp; Health',
    'alf_cat_billing'      => 'Billing &amp; Commerce',
    'alf_cat_imggen'       => 'AI Image Generation',
    'alf_cat_media'        => 'AI Media &amp; Voice',
    'alf_cat_ecommerce'    => 'E-Commerce',
    'alf_cat_seo'          => 'SEO &amp; Search Optimization',
    'alf_cat_devops'       => 'DevOps &amp; CI/CD',
    'alf_cat_design'       => 'Design &amp; Branding',
    'alf_cat_auth'         => 'Authentication &amp; Security',
    'alf_cat_data'         => 'Data &amp; Integrations',
    'alf_cat_content'      => 'Content Creation',
    'alf_cat_a11y'         => 'Accessibility &amp; Compliance',
    'alf_cat_custsuccess'  => 'Customer Success',
    'alf_cat_projintel'    => 'Project Intelligence',
    'alf_cat_scheduling'   => 'Scheduling &amp; Maintenance',
    'alf_cat_comms'        => 'Communication &amp; Outreach',
    'alf_cat_steering'     => 'Voice Steering',
    'alf_cat_agentmgmt'    => 'AI Agent Management',
    'alf_cat_phonemgmt'    => 'Phone &amp; SMS &amp; Fax',
    'alf_cat_callcenter'   => 'Call Center &amp; Campaigns',
    'alf_cat_voiceproducts'=> 'Voice Products &amp; Ordering',
    'alf_tools_label'      => 'tools',

    // Safety
    'alf_safe_h2'          => 'Your Safety Net Has a Safety Net',
    'alf_safe_sub'         => 'Letting AI manage your website feels scary at first. Here\'s why you can relax.',
    'alf_safe_bills_h'     => 'No Surprise Bills',
    'alf_safe_bills_p'     => 'Alfred confirms before buying anything. You always approve. No accidental charges.',
    'alf_safe_sandbox_h'   => 'Sandboxed to Your Account',
    'alf_safe_sandbox_p'   => 'Alfred only accesses YOUR files, YOUR databases, YOUR domains. Impossible to touch another customer.',
    'alf_safe_git_h'       => 'Git Checkpoints',
    'alf_safe_git_p'       => 'Every change can be committed to Git. Mistake? Roll back to any point. History always intact.',
    'alf_safe_transparent_h' => '100% Transparent',
    'alf_safe_transparent_p' => 'Alfred shows every action, every tool called, every result. No black box. Full visibility.',
    'alf_safe_delete_h'    => 'Delete Protection',
    'alf_safe_delete_p'    => 'Critical files protected. Alfred warns about dangerous operations. Won\'t delete WordPress casually.',
    'alf_safe_humans_h'    => 'Humans Standing By',
    'alf_safe_humans_p'    => 'Real human support via ticket or phone. 24/7 — <strong>1-833-GOSITEME</strong>.',

    // Final CTA
    'alf_cta_h2'           => 'Ready to Let Your Website<br>Run Itself?',
    'alf_cta_sub'          => '13,000+ AI Tools. 17 intelligence engines. 89 categories. E-commerce, SEO, DevOps, design, authentication, and more.<br>All-in-one. Starting at <strong style="color:#00D4FF;">$15/month</strong>.',
    'alf_cta_start'        => 'Start AI Hosting',
    'alf_cta_voice'        => 'Try Alfred Voice',
    'alf_cta_domain'       => 'Register a Domain First',
    'alf_cta_promo'        => 'Use code <strong>LAUNCH50</strong> for 50% off your first year. No contracts. Cancel anytime.',

    // FAQ
    'alf_faq_h2'           => 'Frequently Asked Questions',
    'alf_faq1_q'           => 'Do I need to know how to code?',
    'alf_faq1_a'           => 'Absolutely not. Alfred understands plain English (and French). Say "install WordPress" and it\'s done.',
    'alf_faq2_q'           => 'Can Alfred break my website?',
    'alf_faq2_a'           => 'Alfred confirms destructive actions, recommends backups, and you can always roll back with Git. Plus, our support team is one message away.',
    'alf_faq3_q'           => 'What are tokens and will I run out?',
    'alf_faq3_a'           => 'Tokens measure AI usage. Builder includes 300,000/month — ~750 conversations. Most users never hit their limit. Monitor in real-time.',
    'alf_faq4_q'           => 'Can Alfred really generate images?',
    'alf_faq4_a'           => 'Yes. Multiple AI models create photos, logos, banners, product images from text. Saved directly to your site.',
    'alf_faq5_q'           => 'Is this better than Cursor or Lovable?',
    'alf_faq5_a'           => 'For web hosting and development, dramatically. Cursor is great for code but can\'t manage hosting, domains, email, SSL, or generate images. It can\'t browse the web, run Python in a sandbox, build a RAG knowledge base, or monitor your servers. We do all of that — plus 13,000+ tools across 89 categories including e-commerce, SEO, DevOps, design, authentication, and more. Persistent memory and hosting included in the price.',
    'alf_faq6_q'           => 'Is this like Make.com or Zapier?',
    'alf_faq6_a'           => 'No — those are automation-only tools. Alfred is a full AI hosting platform: editor + assistant + hosting + images + billing, all in one browser tab. No "nodes" — just conversation.',
    'alf_faq7_q'           => 'What if I need more tokens?',
    'alf_faq7_a'           => 'Upgrade your plan, or buy a one-time Token Top-Up Pack: 100K ($5), 500K ($19), 1M ($35), or 5M ($149). Tokens are credited instantly and never expire. You can also add +100K to +1M extra tokens/month at checkout.',
    'alf_faq8_q'           => 'What model powers Alfred?',
    'alf_faq8_a'           => 'A proprietary multi-model AI architecture purpose-built for web development and hosting. 13,000+ AI Tools across 89 categories — e-commerce, SEO, DevOps, design, authentication, data management, content, accessibility, customer success, project intelligence, scheduling, and communication. 17 proprietary engines: ELEPHANT (Memory), ORACLE (Semantic Search), PLAYBOOK (Workflows), CLOCKWORK (Scheduler), HIVEMIND (Multi-Agent), SENTINEL (Proactive Monitoring), NEXUS (Browser Agent), FORGE (Code Interpreter), CORTEX (RAG Knowledge Base).',
    'alf_faq9_q'           => 'Can I use this on my phone?',
    'alf_faq9_a'           => 'Yes. Alfred IDE editor works in any modern browser — phone, iPad, laptop. Alfred from anywhere.',
    'alf_faq10_q'          => 'Is my data safe?',
    'alf_faq10_a'          => 'Hardened enterprise infrastructure. Sandboxed access. Encrypted connections. We don\'t sell data or train on your files. Your code is YOUR code.',
    'alf_faq11_q'          => 'Can I really control my website from WhatsApp?',
    'alf_faq11_a'          => 'Yes. And Signal, Discord, Telegram, and SMS too. Send a message like "check my traffic" or "install a plugin" and Alfred responds in seconds. Manage your website from your phone without ever opening a browser.',
    'alf_faq12_q'          => 'How do voice commands work?',
    'alf_faq12_a'          => 'Built into your Alfred IDE browser session. Click the microphone, speak naturally — "Set up SSL on mysite.com" — and Alfred executes it. No extensions. No plugins. Just your browser and your voice.',

    // v2 Engines section
    'alf_engines_label'    => 'Built Different',
    'alf_engines_h2'       => '16+ Intelligence Engines.<br>Zero Competition.',
    'alf_engines_sub'      => 'Alfred doesn\'t just execute commands. He thinks, remembers, learns, schedules, and delegates — autonomously.',
    'alf_eng_elephant_h'   => 'ELEPHANT',
    'alf_eng_elephant_sub' => 'Persistent Memory',
    'alf_eng_elephant_p'   => 'Alfred remembers your preferences, decisions, and project context across every session. Smart pruning ensures only the most relevant memories surface. Never repeat yourself.',
    'alf_eng_oracle_h'     => 'ORACLE',
    'alf_eng_oracle_sub'   => 'Semantic Code Intelligence',
    'alf_eng_oracle_p'     => 'Search your codebase by meaning, not keywords. "Find the authentication logic" returns <code>verifyToken()</code>. Understands 25+ languages with local ONNX inference.',
    'alf_eng_playbook_h'   => 'PLAYBOOK',
    'alf_eng_playbook_sub' => 'Self-Healing Workflows',
    'alf_eng_playbook_p'   => 'Save multi-step workflows as natural language templates. Deploy WordPress, audit security, onboard domains — in one command. 10 built-in templates. If a step fails, Alfred adapts.',
    'alf_eng_clockwork_h'  => 'CLOCKWORK',
    'alf_eng_clockwork_sub'=> 'Autonomous Scheduler',
    'alf_eng_clockwork_p'  => 'Schedule nightly backups, SSL renewals, security audits that run while you sleep. Cron-powered, Redis-persisted, with full execution logs.',
    'alf_eng_hivemind_h'   => 'HIVEMIND',
    'alf_eng_hivemind_sub' => 'Multi-Agent Delegation',
    'alf_eng_hivemind_p'   => 'Alfred spawns parallel sub-agents — researchers, analyzers, workers — to tackle complex problems 3&times; faster. Role-based access keeps everything safe.',
    'alf_eng_sentinel_h'   => 'SENTINEL',
    'alf_eng_sentinel_sub' => 'Proactive Monitoring &amp; Auto-Heal',
    'alf_eng_sentinel_p'   => 'Watches your sites 24/7 — uptime, SSL expiry, disk space, response times. When something breaks, Alfred fixes it automatically before you even notice.',
    'alf_eng_nexus_h'      => 'NEXUS',
    'alf_eng_nexus_sub'    => 'Browser Agent &amp; Web Intelligence',
    'alf_eng_nexus_p'      => 'A full browser Alfred controls autonomously. Navigate sites, fill forms, extract data, take screenshots, run audits — all by conversation.',
    'alf_eng_forge_h'      => 'FORGE',
    'alf_eng_forge_sub'    => 'Code Interpreter &amp; Sandbox',
    'alf_eng_forge_p'      => 'Execute Python, Node.js, Bash, Ruby, and PHP in a secure sandbox. Data analysis, prototyping, debugging — Alfred runs real code and returns real results.',
    'alf_eng_cortex_h'     => 'CORTEX',
    'alf_eng_cortex_sub'   => 'RAG Knowledge Base',
    'alf_eng_cortex_p'     => 'Ingest docs, PDFs, and codebases into a vector-indexed knowledge base. Alfred answers questions with pinpoint accuracy grounded in your own data.',
    'alf_eng_plus_h'       => 'Plus: Smart Commits &amp; AI Code Review',
    'alf_eng_plus_p'       => 'AI writes your git commit messages from diff analysis. Senior-engineer code review scores every change. Dependency audits catch CVEs before they\'re exploited.',

    // New commercial scenes
    'alf_scene_v7'         => '"Alfred, review my last commit for bugs"',
    'alf_scene_r7'         => '<strong>&check; Code Review Complete.</strong> Score: <strong style="color:#10b981;">9.2/10</strong>. 2 suggestions (style), 0 critical issues. No security vulnerabilities detected.',
    'alf_scene_v8'         => '"Remember: I always deploy to staging first"',
    'alf_scene_r8'         => '<strong>&check; Memory saved.</strong> Alfred will remember this preference in every future session. Across devices. Forever.',
    'alf_scene_v9'         => '"Schedule nightly backups at 3 AM and weekly security scans"',
    'alf_scene_r9'         => '<strong>&check; 2 tasks scheduled.</strong> Database backup runs nightly at 3:00 AM. Full security audit runs every Sunday at 6:00 AM. Fully autonomous.',

    // New comparison rows
    'alf_cmp_memory'       => 'Persistent AI Memory',
    'alf_cmp_code_review'  => 'AI Code Review',
    'alf_cmp_auto_sched'   => 'Automated Scheduling',
    'alf_cmp_db_query'     => 'Database Querying',
    'alf_cmp_workflows'    => 'Saved Workflows',
    'alf_cmp_multi_agent'  => 'Multi-Agent AI',
    'alf_cmp_smart_git'    => 'AI Git Commits',
    'alf_cmp_dep_audit'    => 'Dependency Audit',
    'alf_cmp_cross_session'=> '&check; Cross-session',
    'alf_cmp_10score'      => '&check; 1-10 scoring',
    'alf_cmp_cron_play'    => '&check; Cron + Playbooks',
    'alf_cmp_conversational'=> '&check; Conversational SQL',
    'alf_cmp_10builtin'    => '&check; 10 built-in',
    'alf_cmp_3agents'      => '&check; 3 parallel agents',
    'alf_cmp_conventional' => '&check; Conventional commits',
    'alf_cmp_npm_composer'  => '&check; npm + Composer',
    'alf_cmp_not_persist'  => 'Per-session only',

    // New FAQ 
    'alf_faq13_q'          => 'What is ELEPHANT memory?',
    'alf_faq13_a'          => 'Alfred remembers your preferences, project decisions, and lessons learned permanently using vector embeddings. Say "remember I prefer TypeScript" once, and Alfred knows it forever — across every session, every device.',
    'alf_faq14_q'          => 'Can Alfred review my code?',
    'alf_faq14_a'          => 'Yes. The AI code review engine analyzes your git diffs for bugs, security vulnerabilities, performance issues, and style problems. It scores changes 1-10 and provides specific suggestions with line numbers.',
    'alf_faq15_q'          => 'How does autonomous scheduling work?',
    'alf_faq15_a'          => 'CLOCKWORK lets you schedule any workflow to run automatically — nightly database backups, weekly security scans, SSL renewals. Tasks run via cron, execute playbooks, and log every result. Alfred works while you sleep.',

    // New tool categories
    'alf_cat_memory'       => 'ELEPHANT — AI Memory',
    'alf_cat_oracle'       => 'ORACLE — Code Intelligence',
    'alf_cat_playbook'     => 'PLAYBOOK — Workflows',
    'alf_cat_clockwork'    => 'CLOCKWORK — Scheduler',
    'alf_cat_hivemind'     => 'HIVEMIND — Multi-Agent',
    'alf_cat_devtools'     => 'Developer Power Tools',

    // v6.0.0 categories
    'alf_cat_rag'          => 'RAG Knowledge Base',
    'alf_cat_interpreter'  => 'Code Interpreter',
    'alf_cat_browser'      => 'Browser Agent',
    'alf_cat_mcpclient'    => 'MCP Client Gateway',
    'alf_cat_workflows'    => 'Workflow Automation',
    'alf_cat_proactive'    => 'Proactive Monitoring',

    // ═══ v6.0.0 COMMERCIAL SCENES ═══
    'alf_scene_v10'        => '"Alfred, go to shopify.com and summarize their pricing plans"',
    'alf_scene_r10'        => '<strong>&check; Browser Agent deployed.</strong> Navigated to shopify.com/pricing. Extracted 3 plans: Basic $39/mo, Shopify $105/mo, Advanced $399/mo. Full comparison saved to <code>/reports/competitor-pricing.md</code>.',
    'alf_scene_v11'        => '"Run this Python script — analyze my sales CSV and chart the trends"',
    'alf_scene_r11'        => '<strong>&check; Code executed.</strong> Python sandbox processed 2,847 rows. Revenue up 23% YoY. Generated interactive chart artifact. <strong style="color:#10b981;">View the chart live in your editor.</strong>',
    'alf_scene_v12'        => '"Ingest our API docs and tell me: what\'s the rate limit for /v2/orders?"',
    'alf_scene_r12'        => '<strong>&check; RAG Knowledge Base built.</strong> 47 documents indexed with vector embeddings. Answer: <em>/v2/orders rate limit is 250 requests/minute per API key, with burst allowance of 50.</em>',
    'alf_scene_v13'        => '"Monitor all my sites 24/7 — auto-restart anything that goes down"',
    'alf_scene_r13'        => '<strong>&check; Proactive Monitoring active.</strong> Watching 4 sites: mybakery.com, shop.mybakery.com, api.mybakery.com, staging.mybakery.com. Auto-healing enabled. You\'ll be notified on WhatsApp if anything happens.',

    // ═══ v6.0.0 SUPERPOWERS SECTION ═══
    'alf_super_label'      => 'Version 9.0 — The 13,000+-Tool Platform',
    'alf_super_h2'         => '17 AI Engines. 66 Categories. 25 New Disciplines.<br>Nothing Else Comes Close.',
    'alf_super_sub'        => 'We didn\'t just add tools — we added entire disciplines. 14 AI engines (Conduit, Architect, Sentinel, Forge, Chronicle, Nexus, Cortex, Empathy, Muse, Prism, Tempo, Echo, Pulse, Sage), Autopilot browser agent, voice commerce, agent swarm, and 35 infrastructure tools. 13,000+ tools across 89 categories. This is the biggest upgrade in AI hosting history.',

    'alf_super_browser_h'  => 'Browser Agent',
    'alf_super_browser_tag'=> 'Alfred can see the web',
    'alf_super_browser_p'  => 'Alfred opens a real browser, navigates websites, extracts data, fills forms, takes screenshots, downloads files. Competitor research, price monitoring, web scraping — all by conversation.',

    'alf_super_interp_h'   => 'Code Interpreter',
    'alf_super_interp_tag' => 'Run code. See results.',
    'alf_super_interp_p'   => 'Execute Python, Node.js, Bash, Ruby, or PHP in a sandboxed environment. Analyze CSVs, generate charts, process data, run tests — then see the output as live artifacts. Your data never leaves your server.',

    'alf_super_rag_h'      => 'RAG Knowledge Base',
    'alf_super_rag_tag'    => 'Teach Alfred your docs',
    'alf_super_rag_p'      => 'Upload documentation, code, or any text — Alfred indexes it with vector embeddings and answers questions with pinpoint accuracy. API docs, internal wikis, customer FAQs — your private AI knowledge base.',

    'alf_super_monitor_h'  => 'Proactive Monitoring',
    'alf_super_monitor_tag'=> 'Alfred watches. Alfred fixes.',
    'alf_super_monitor_p'  => 'Continuous server surveillance with auto-healing. Services go down? Alfred restarts them. Disk filling up? Alfred cleans it. SSL expiring? Alfred renews it. You get a WhatsApp message — after the problem is already solved.',

    'alf_super_workflow_h' => 'Workflow Automation',
    'alf_super_workflow_tag'=> 'Built-in automation',
    'alf_super_workflow_p' => 'Create visual automation workflows — or just tell Alfred what you want. "When a form is submitted, email me and add to the spreadsheet." Multi-step automations that run 24/7.',

    'alf_super_artifact_h' => 'Live Artifacts',
    'alf_super_artifact_tag'=> 'Charts. Diagrams. Previews.',
    'alf_super_artifact_p' => 'Alfred generates interactive charts, Mermaid diagrams, and live HTML previews right inside your editor. "Show me my traffic as a bar chart" — and there it is. No plugins. No export. Just results.',

    'alf_super_steering_h' => 'Command Steering',
    'alf_super_steering_tag'=> 'Queue commands. Redirect anytime.',
    'alf_super_steering_p' => 'Queue commands while Alfred works. Say \'stop\' to redirect. No waiting.',

    // ═══ v6.0.0 COMPARISON ROWS ═══
    'alf_cmp_browser_agent'=> 'Web Browsing Agent',
    'alf_cmp_code_interp'  => 'Code Interpreter',
    'alf_cmp_rag'          => 'RAG Knowledge Base',
    'alf_cmp_proactive_mon'=> 'Proactive Monitoring',
    'alf_cmp_video_audio'  => 'Video &amp; Audio AI',
    'alf_cmp_artifacts'    => 'Charts &amp; Artifacts',
    'alf_cmp_mcp_gateway'  => 'MCP Client Gateway',

    // v6 comparison cell values
    'alf_cmp_playwright'   => '&check; Full browser automation',
    'alf_cmp_5langs'       => '&check; 5 languages sandboxed',
    'alf_cmp_vector_embed' => '&check; Vector embeddings',
    'alf_cmp_auto_heal'    => '&check; Auto-healing',
    'alf_cmp_25plus'       => '&check; Multiple models',
    'alf_cmp_live_charts'  => '&check; Live in-editor',
    'alf_cmp_ext_tools'    => '&check; Connect external tools',

    // ═══ v6.0.0 FAQ ═══
    'alf_faq16_q'          => 'Can Alfred browse the web?',
    'alf_faq16_a'          => 'Yes. Alfred has a built-in browser agent. It can navigate any website, extract data, fill forms, take screenshots, and download files. Ask him to research competitors, check pricing, or monitor any URL — all by natural conversation.',
    'alf_faq17_q'          => 'Can Alfred run Python code?',
    'alf_faq17_a'          => 'Yes. The Code Interpreter runs Python, Node.js, Bash, Ruby, and PHP in a secure sandbox. Analyze data, generate charts, process CSVs, run scripts — and see the output as live artifacts in your editor. Your data stays on your server.',
    'alf_faq18_q'          => 'What is a RAG Knowledge Base?',
    'alf_faq18_a'          => 'RAG (Retrieval-Augmented Generation) lets you feed Alfred your documentation, API specs, or any text. Alfred indexes everything with vector embeddings and can answer questions with surgical precision — citing specific docs. Like giving Alfred a library of your knowledge.',
    'alf_faq19_q'          => 'Can Alfred generate charts and diagrams?',
    'alf_faq19_a'          => 'Yes. Ask Alfred to visualize any data: bar charts, line graphs, pie charts, Mermaid diagrams, even live HTML previews. Artifacts appear right inside your editor — no exporting, no plugins, no setup.',
    'alf_faq20_q'          => 'Does Alfred monitor my server automatically?',
    'alf_faq20_a'          => 'Yes. Proactive Monitoring watches your sites 24/7. If a service goes down, Alfred auto-restarts it. If SSL is expiring, Alfred renews it. If disk is filling up, Alfred cleans it. You get notified after the problem is already fixed.',

    // ═══ UPGRADED IMAGE → AI MEDIA SECTION ═══
    'alf_img_h2_v6'        => 'AI Images. AI Video. AI Audio.<br>All Built In.',
    'alf_img_sub_v6'       => 'Cursor can\'t do this. Lovable can\'t do this. VS Code can\'t do this.<br><strong style="color:#00D4FF;">Alfred can.</strong> Multiple image models. Multiple video models. Multiple voice/audio models. Vision analysis. Media processing. All by conversation.',
    'alf_img_powered_v6'   => '13,000+ AI Tools for images, video, and audio — all saved directly to your hosting.',
]);

// ─── FRENCH ───
$LANG_STRINGS['fr'] = array_merge($LANG_STRINGS['fr'], [
    // Page meta
    'alf_meta_title'       => 'Découvrez Alfred — Le tout premier assistant d\'hébergement IA | 13,000+ outils, 17 moteurs IA | GoSiteMe',
    'alf_meta_desc'        => 'Alfred est le premier assistant d\'hébergement IA au monde. 13,000+ outils. 17 moteurs d\'intelligence. 89 catégories incluant e-commerce, SEO, DevOps, design, authentification, données, et plus — le tout en parlant naturellement.',

    // Hero
    'alf_hero_badge'       => 'Le tout premier assistant d\'hébergement IA au monde',
    'alf_hero_h1'          => 'Découvrez Alfred.<br>L\'IA qui gère toute votre entreprise.',
    'alf_hero_sub'         => '13,000+ outils IA. 17 moteurs d\'intelligence. 89 catégories — avec pilotage de commandes pour ne jamais attendre. E-commerce, SEO, DevOps, design, authentification, gestion de données, contenu, accessibilité — plus agent navigateur, interpréteur de code et base de connaissances RAG. Parlez à Alfred comme à un ami — et regardez votre entreprise se gérer... toute seule. Depuis n\'importe quel appareil.',
    'alf_hero_sub2'        => 'Pas de terminal. Pas de cPanel. Pas de code. Alfred navigue le web pour vous. Exécute du Python. Génère des graphiques. Surveille vos serveurs 24/7. Corrige les problèmes avant votre réveil.<br>Ce n\'est pas de l\'hébergement. <strong style="color:#00D4FF;">C\'est le futur.</strong>',
    'alf_hero_cta_start'   => 'Hébergement IA — 15 $/mois',
    'alf_hero_cta_watch'   => 'Voir en action',
    'alf_hero_promo'       => 'Utilisez le code <strong>LAUNCH50</strong> pour 50 % de réduction la 1re année. Annulez quand vous voulez.',
    'alf_stat_tools'       => 'Outils IA',
    'alf_stat_categories'  => 'Catégories',
    'alf_stat_commands'    => 'Commandes à apprendre',
    'alf_stat_always'      => 'Disponible 24/7',
    'alf_stat_possibilities' => 'Possibilités',

    // Commercial
    'alf_scene_label'      => 'Imaginez',
    'alf_scene_h2'         => 'Il est 21 h. Vous venez d\'avoir une idée de commerce.',
    'alf_scene_intro'      => 'Pas de développeur. Pas d\'agence. Pas de facture de 5 000 $. Juste vous, un portable et Alfred.<br>Voici ce qui se passe dans les 12 prochaines minutes.',
    'alf_scene_v1'         => '« Alfred, installe un site WordPress sur maboulangerie.com »',
    'alf_scene_r1'         => '<strong>&check; Fait en 8 secondes.</strong> WordPress installé. Certificat SSL activé. DNS configuré. Votre site est EN LIGNE à <code>https://maboulangerie.com</code>.',
    'alf_scene_v2'         => '« Installe WooCommerce et ajoute 3 produits avec les prix »',
    'alf_scene_r2'         => '<strong>&check; Fait.</strong> WooCommerce activé. 3 produits créés avec descriptions, images et tarifs. Passerelle de paiement prête.',
    'alf_scene_v3'         => '« Génère une image principale — une boulangerie rustique avec une lumière matinale chaude »',
    'alf_scene_r3'         => '<strong>&check; Image générée.</strong> L\'IA a créé une superbe image 1024&times;1024 en 4,2 secondes. Enregistrée dans <code>/ai-images/</code>. URL publique prête.',
    'alf_scene_v4'         => '« Configure le courriel moi@maboulangerie.com et redirige vers mon Gmail »',
    'alf_scene_r4'         => '<strong>&check; Fait.</strong> Compte courriel créé. Redirection activée. DKIM, SPF et DMARC configurés.',
    'alf_scene_v5'         => '« Vérifie si mon site est rapide et sécurisé »',
    'alf_scene_r5'         => '<strong>&check; Rapport de santé :</strong> SSL valide, DNS résolu, temps de réponse 0,28 s, aucun logiciel malveillant, permissions correctes. Performance : <strong style="color:#10b981;">A+</strong>.',
    'alf_scene_v6'         => '« Sauvegarde tout et enregistre dans Git »',
    'alf_scene_r6'         => '<strong>&check; Fait.</strong> Sauvegarde complète créée. Dépôt Git initialisé, tous les fichiers validés. Retour en arrière possible à tout moment.',
    'alf_scene_time'       => 'Temps total : <strong style="color:#fff;">~12 minutes</strong>. Commandes tapées : <strong style="color:#10b981;">zéro</strong>.',
    'alf_scene_cost'       => 'Coût agence : <strong style="color:#fb923c;">2 000 $ – 10 000 $</strong>. Avec Alfred : <strong style="color:#00D4FF;">15 $/mois</strong>.',

    // Image generation
    'alf_img_h2'           => 'Génération d\'images par IA.<br>Intégrée directement.',
    'alf_img_sub'          => 'Cursor ne peut pas faire ça. Lovable ne peut pas. VS Code non plus.<br><strong style="color:#00D4FF;">Alfred, oui.</strong> Générez des images à partir de texte et enregistrez-les directement sur votre site.',
    'alf_img_p1'           => '« Crée une image principale d\'un café moderne »',
    'alf_img_p2'           => '« Dessine un logo pour TechFlow Solutions »',
    'alf_img_p3'           => '« Photo produit de pain artisanal sur bois »',
    'alf_img_p4'           => '« Arrière-plan abstrait, vagues violettes »',
    'alf_img_gen'          => 'Généré en',
    'alf_img_style'        => 'Style :',
    'alf_img_presets'      => '7 styles prédéfinis :',
    'alf_img_presets_list' => 'photo &middot; illustration &middot; logo &middot; abstrait &middot; héro &middot; produit &middot; avatar',
    'alf_img_powered'      => 'Plusieurs modèles IA. Images enregistrées directement dans votre dossier <code>/ai-images/</code>.',

    // Voice & Messaging
    'alf_vm_badge'         => 'La fonctionnalité que personne d\'autre n\'a',
    'alf_vm_h2'            => 'Contrôlez Alfred de partout.<br>Voix. Messages. N\'importe quelle appli.',
    'alf_vm_sub'           => 'Parlez à Alfred dans votre navigateur par la voix. Ou envoyez un message via WhatsApp, Signal, Discord, Telegram ou SMS.<br><strong style="color:#00D4FF;">Votre site web vous écoute — où que vous soyez.</strong>',
    'alf_vm_connected'     => '&#10003; Connecté',
    'alf_vm_voice'         => 'Voix',
    'alf_vm_browser'       => '&#10003; Natif navigateur',
    'alf_vm_how'           => 'Voyez comment ça marche',
    'alf_vm_q1'            => 'Hé Alfred, comment va mon trafic aujourd\'hui ?',
    'alf_vm_a1'            => '<strong>&#10003; 847 visiteurs aujourd\'hui</strong> (+12 % vs hier). Page principale : /products (312 vues). 94 % de trafic humain. Aucune anomalie. Santé du site : <strong>A+</strong>.',
    'alf_vm_q2'            => 'Installe un plugin de formulaire de contact sur maboulangerie.com',
    'alf_vm_a2'            => '<strong>&#10003; Fait.</strong> WPForms Lite installé et activé sur maboulangerie.com. Un formulaire de contact est prêt à /contact.',
    'alf_vm_q3'            => 'Génère une bannière — coucher de soleil sur les montagnes, tons chauds',
    'alf_vm_a3'            => '<strong>&#10003; Image générée</strong> en 4,1 secondes. 1792&times;1024, style : héro. Enregistrée dans /ai-images/banner-sunset-mountains.png. <strong>URL publique prête.</strong>',
    'alf_vm_q4'            => 'Mon SSL est-il encore valide ?',
    'alf_vm_a4'            => '<strong>&#10003; SSL valide.</strong> Certificat Let\'s Encrypt pour maboulangerie.com expire le 14 mars 2027. Renouvellement automatique activé. Note : A+.',
    'alf_vm_kicker1'       => 'Vous êtes dans un café. Dans le bus. En réunion.',
    'alf_vm_kicker2'       => 'Votre téléphone vibre — un client a posé une question sur votre site.',
    'alf_vm_kicker3'       => 'Vous ouvrez <strong>WhatsApp</strong> et tapez : <em style="color:#c084fc;">« Alfred, montre-moi les dernières soumissions du formulaire de contact. »</em>',
    'alf_vm_kicker4'       => '<strong>3 secondes plus tard</strong>, vous avez la réponse.',
    'alf_vm_kicker5'       => '<strong class="accent">Pas de portable. Pas de connexion. Pas de tableau de bord.</strong>',
    'alf_vm_kicker6'       => 'Juste vous, votre téléphone et une IA qui ne dort jamais.',
    'alf_vm_kicker7'       => 'Cursor ne peut pas faire ça. Lovable non plus. cPanel non plus. <strong style="color:#fff;">Personne</strong> ne peut faire ça.',

    // Comparison
    'alf_cmp_h2'           => 'Alfred vs. Tout le reste',
    'alf_cmp_sub'          => 'Nous avons construit ce que l\'industrie disait être impossible. Voici la preuve.',
    'alf_cmp_feature'      => 'Fonctionnalité',
    'alf_cmp_footer'       => '<strong style="color:#fff;">Nous ne sommes pas juste un éditeur.</strong> Pas juste un hébergeur. Pas juste de l\'IA.<br>Nous sommes <strong style="color:#00D4FF;">toute la pile technologique</strong> — au même endroit, pour moins cher.',

    // Comparison row labels
    'alf_cmp_code_editor'  => 'Éditeur de code IA',
    'alf_cmp_img_gen'      => 'Génération d\'images IA',
    'alf_cmp_wp_mgmt'      => 'Gestion WordPress',
    'alf_cmp_domain_dns'   => 'Domaine &amp; DNS',
    'alf_cmp_email'        => 'Gestion des courriels',
    'alf_cmp_ssl'          => 'SSL &amp; Sécurité',
    'alf_cmp_backups'      => 'Sauvegardes &amp; Git',
    'alf_cmp_analytics'    => 'Analytique &amp; Trafic',
    'alf_cmp_billing'      => 'Gestion de la facturation',
    'alf_cmp_nl'           => 'Langage naturel',
    'alf_cmp_voice'        => 'Commandes vocales',
    'alf_cmp_messaging'    => 'Messagerie (WhatsApp, Signal&hellip;)',
    'alf_cmp_hosting'      => 'Hébergement inclus',
    'alf_cmp_total'        => 'Total d\'outils IA',
    'alf_cmp_price'        => 'Prix de départ',

    // Comparison cell values
    'alf_cmp_full_ide'     => '&check; IDE complet',
    'alf_cmp_flux'         => '&check; Plusieurs modèles IA',
    'alf_cmp_11tools'      => '&check; 11 outils',
    'alf_cmp_full_ctrl'    => '&check; Contrôle complet',
    'alf_cmp_with_ai'      => '&check; Avec IA',
    'alf_cmp_automated'    => '&check; Automatisé',
    'alf_cmp_both'         => '&check; Les deux',
    'alf_cmp_builtin'      => '&check; Intégré',
    'alf_cmp_14tools'      => '&check; 14 outils',
    'alf_cmp_talk'         => '&check; Parlez naturellement',
    'alf_cmp_browser_native' => '&check; Natif navigateur',
    'alf_cmp_5plus'        => '&check; 5+ plateformes',
    'alf_cmp_ssd_ssl'      => '&check; SSD + SSL + Courriel',
    'alf_cmp_cant'         => '&cross; Impossible',
    'alf_cmp_limited'      => 'Limité',
    'alf_cmp_basic'        => 'De base',
    'alf_cmp_manual'       => 'Manuel',
    'alf_cmp_git_only'     => 'Git seulement',
    'alf_cmp_backups_only' => 'Sauvegardes seulement',
    'alf_cmp_via_plugins'  => 'Via plugins',
    'alf_cmp_visual'       => 'Visuel',
    'alf_cmp_gui_only'     => '&cross; Interface seulement',
    'alf_cmp_byoh'         => '&cross; Non inclus',
    'alf_cmp_varies'       => 'Variable',

    // Tokens
    'alf_tok_h2'           => 'Tarification transparente des jetons',
    'alf_tok_sub'          => 'Chaque interaction IA utilise des jetons. Des allocations mensuelles généreuses pour ne jamais y penser. Voici exactement ce que vous obtenez.',
    'alf_tok_builder'      => 'Builder',
    'alf_tok_pro'          => 'Professionnel',
    'alf_tok_studio'       => 'Studio',
    'alf_tok_business'     => 'Business',
    'alf_tok_per_month'    => 'jetons/mois',
    'alf_tok_roughly'      => 'C\'est environ :',
    'alf_tok_conversations' => '~750 conversations',
    'alf_tok_ai_ops'       => '~150 sessions de code IA',
    'alf_tok_img_gens'     => '~100 générations d\'images',
    'alf_tok_2x'           => '2&times; Builder. Pour :',
    'alf_tok_freelancers'  => 'Pigistes',
    'alf_tok_small_teams'  => 'Petites équipes',
    'alf_tok_side_projects' => 'Projets personnels',
    'alf_tok_5x'           => '5&times; Builder. Pour :',
    'alf_tok_startups'     => 'Startups',
    'alf_tok_dev_studios'  => 'Studios de développement',
    'alf_tok_opus_access'  => 'Accès Modèle Premium',
    'alf_tok_10x'          => '10&times; Builder. Pour :',
    'alf_tok_agencies'     => 'Agences',
    'alf_tok_enterprise_teams' => 'Équipes entreprise',
    'alf_tok_unlimited_opus' => 'Premium illimité',
    'alf_tok_how'          => '<strong style="color:#fff;">Comment fonctionnent les jetons :</strong> Une question typique ~400 jetons. Un refactoring de code ~800. Une génération d\'image ~1 500. Une tâche complexe ~2 000. Suivi en temps réel.',

    // Token Top-Ups
    'alf_topup_h3'         => 'Besoin de plus de jetons ? Achetez un pack de recharge',
    'alf_topup_sub'        => 'Packs de jetons uniques. Crédités instantanément. N\'expirent jamais. Ajoutez à n\'importe quel forfait.',

    // Power-Up Add-Ons
    'alf_addons_h2'        => 'Modules complémentaires',
    'alf_addons_sub'       => 'Boostez n\'importe quel forfait avec des fonctionnalités supplémentaires. Ajoutez-en un ou cumulez-les. Annulez à tout moment.',
    'alf_addon_opus'       => 'Débloquez notre Modèle IA Premium — le moteur le plus puissant disponible — pour le raisonnement complexe et les refactorisations à grande échelle.',
    'alf_addon_sessions'   => 'Exécutez 3 conversations IA simultanément. Idéal pour le multitâche entre projets.',
    'alf_addon_team'       => 'Ajoutez 5 sièges collaborateurs avec accès complet à l\'IDE et pool de jetons partagé.',
    'alf_addon_priority'   => 'Temps de réponse garanti de 4 heures. File d\'attente IA prioritaire. Canal direct avec l\'ingénierie.',
    'alf_addon_backup'     => 'Instantanés quotidiens automatiques des espaces de travail. Rétention de 30 jours. Restauration en un clic.',
    'alf_addon_domain'     => 'Associez votre propre domaine aux applications déployées. SSL automatique et CDN inclus.',
    'alf_addon_images'     => '300 générations d\'images IA supplémentaires/mois. Plusieurs modèles pour photos, logos, bannières et prises de vue produit.',
    'alf_addon_video'      => 'Générez 50 vidéos IA/mois. Plusieurs modèles vidéo IA pour du contenu de qualité cinématographique.',
    'alf_addon_dedicated'  => 'Conteneur isolé : 4 vCPU, 8 Go RAM, 100 Go NVMe. Aucun partage de ressources.',
    'alf_addon_whitelabel' => 'Supprimez la marque Alfred IDE. Ajoutez votre logo, couleurs et domaine. Idéal pour les agences.',

    // 13,000+ Tools section
    'alf_tools_h2'         => '13,000+ outils. Chacun expliqué.',
    'alf_tools_sub'        => 'La boîte à outils IA la plus puissante jamais construite pour l\'hébergement web. 16+ moteurs. 26 catégories. Chaque outil fonctionne par conversation naturelle.',

    // Tool categories
    'alf_cat_files'        => 'Gestion des fichiers',
    'alf_cat_db'           => 'Gestion des bases de données',
    'alf_cat_domains'      => 'Domaines, DNS &amp; SSL',
    'alf_cat_email'        => 'Gestion des courriels',
    'alf_cat_backups'      => 'Sauvegardes, Cron &amp; Compte',
    'alf_cat_wp'           => 'Gestion WordPress',
    'alf_cat_git'          => 'Contrôle de version Git',
    'alf_cat_security'     => 'Sécurité &amp; Journaux d\'erreurs',
    'alf_cat_analytics'    => 'Analytique &amp; Santé',
    'alf_cat_billing'      => 'Facturation &amp; Commerce',
    'alf_cat_imggen'       => 'Génération d\'images IA',
    'alf_cat_media'        => 'Médias IA &amp; Voix',
    'alf_cat_ecommerce'    => 'E-Commerce',
    'alf_cat_seo'          => 'SEO &amp; Optimisation de recherche',
    'alf_cat_devops'       => 'DevOps &amp; CI/CD',
    'alf_cat_design'       => 'Design &amp; Image de marque',
    'alf_cat_auth'         => 'Authentification &amp; Sécurité',
    'alf_cat_data'         => 'Données &amp; Intégrations',
    'alf_cat_content'      => 'Création de contenu',
    'alf_cat_a11y'         => 'Accessibilité &amp; Conformité',
    'alf_cat_custsuccess'  => 'Succès client',
    'alf_cat_projintel'    => 'Intelligence projet',
    'alf_cat_scheduling'   => 'Planification &amp; Maintenance',
    'alf_cat_comms'        => 'Communication &amp; Diffusion',
    'alf_cat_steering'     => 'Pilotage vocal',
    'alf_tools_label'      => 'outils',

    // Safety
    'alf_safe_h2'          => 'Votre filet de sécurité a un filet de sécurité',
    'alf_safe_sub'         => 'Confier votre site web à une IA peut sembler effrayant au début. Voici pourquoi vous pouvez être tranquille.',
    'alf_safe_bills_h'     => 'Aucune surprise sur la facture',
    'alf_safe_bills_p'     => 'Alfred confirme avant tout achat. Vous approuvez toujours. Aucun frais accidentel.',
    'alf_safe_sandbox_h'   => 'Isolé dans votre compte',
    'alf_safe_sandbox_p'   => 'Alfred n\'accède qu\'à VOS fichiers, VOS bases de données, VOS domaines. Impossible de toucher un autre client.',
    'alf_safe_git_h'       => 'Points de contrôle Git',
    'alf_safe_git_p'       => 'Chaque modification peut être enregistrée dans Git. Erreur ? Revenez en arrière à tout moment.',
    'alf_safe_transparent_h' => '100 % transparent',
    'alf_safe_transparent_p' => 'Alfred affiche chaque action, chaque outil appelé, chaque résultat. Pas de boîte noire. Visibilité totale.',
    'alf_safe_delete_h'    => 'Protection contre la suppression',
    'alf_safe_delete_p'    => 'Les fichiers critiques sont protégés. Alfred avertit avant les opérations dangereuses.',
    'alf_safe_humans_h'    => 'Des humains en renfort',
    'alf_safe_humans_p'    => 'Support humain par billet ou téléphone. 24/7 — <strong>1-833-GOSITEME</strong>.',

    // Final CTA
    'alf_cta_h2'           => 'Prêt à laisser votre site web<br>se gérer tout seul ?',
    'alf_cta_sub'          => '13,000+ outils IA. 17 moteurs d\'intelligence. 89 catégories. E-commerce, SEO, DevOps, design, authentification, et plus.<br>Tout-en-un. À partir de <strong style="color:#00D4FF;">15 $/mois</strong>.',
    'alf_cta_start'        => 'Commencer l\'hébergement IA',
    'alf_cta_voice'        => 'Essayer Alfred vocal',
    'alf_cta_domain'       => 'Enregistrer un domaine d\'abord',
    'alf_cta_promo'        => 'Utilisez le code <strong>LAUNCH50</strong> pour 50 % de réduction la 1re année. Sans contrat. Annulez quand vous voulez.',

    // FAQ
    'alf_faq_h2'           => 'Questions fréquentes',
    'alf_faq1_q'           => 'Dois-je savoir coder ?',
    'alf_faq1_a'           => 'Absolument pas. Alfred comprend le français et l\'anglais. Dites « installe WordPress » et c\'est fait.',
    'alf_faq2_q'           => 'Alfred peut-il casser mon site web ?',
    'alf_faq2_a'           => 'Alfred confirme les actions destructives, recommande des sauvegardes et vous pouvez toujours revenir en arrière avec Git. De plus, notre équipe de support est à un message près.',
    'alf_faq3_q'           => 'Que sont les jetons et vais-je en manquer ?',
    'alf_faq3_a'           => 'Les jetons mesurent l\'utilisation de l\'IA. Le forfait Builder inclut 300 000/mois — ~750 conversations. La plupart des utilisateurs n\'atteignent jamais leur limite.',
    'alf_faq4_q'           => 'Alfred peut-il vraiment générer des images ?',
    'alf_faq4_a'           => 'Oui. Plusieurs modèles IA créent des photos, logos, bannières et images produit à partir de texte. Enregistrées directement sur votre site.',
    'alf_faq5_q'           => 'Est-ce mieux que Cursor ou Lovable ?',
    'alf_faq5_a'           => 'Pour l\'hébergement et le développement web, largement. Cursor est excellent pour le code mais ne peut pas gérer l\'hébergement, les domaines, les courriels, le SSL ni générer des images. Nous faisons tout cela — plus 13,000+ outils dans 89 catégories incluant e-commerce, SEO, DevOps, design, authentification, et plus. Mémoire persistante et hébergement inclus dans le prix.',
    'alf_faq6_q'           => 'Est-ce comme Make.com ou Zapier ?',
    'alf_faq6_a'           => 'Non — ce sont des outils d\'automatisation uniquement. Alfred est une plateforme d\'hébergement IA complète : éditeur + assistant + hébergement + images + facturation, le tout dans un seul onglet de navigateur.',
    'alf_faq7_q'           => 'Et si j\'ai besoin de plus de jetons ?',
    'alf_faq7_a'           => 'Passez à un forfait supérieur, ou achetez un pack de recharge unique : 100K (5 $), 500K (19 $), 1M (35 $), ou 5M (149 $). Les jetons sont crédités instantanément et n\'expirent jamais. Vous pouvez aussi ajouter de +100K à +1M jetons supplémentaires/mois lors du paiement.',
    'alf_faq8_q'           => 'Quel modèle propulse Alfred ?',
    'alf_faq8_a'           => 'Une architecture IA multi-modèles propriétaire conçue pour le développement et l\'hébergement web. 13,000+ outils IA répartis dans 89 catégories — e-commerce, SEO, DevOps, design, authentification, gestion de données, contenu, accessibilité, succès client, intelligence projet, planification et communication. 17 moteurs propriétaires : ELEPHANT (Mémoire), ORACLE (Recherche Sémantique), PLAYBOOK (Flux de travail), CLOCKWORK (Planificateur), HIVEMIND (Multi-Agent), SENTINEL (Surveillance Proactive), NEXUS (Agent Navigateur), FORGE (Interpréteur de Code), CORTEX (Base de Connaissances RAG).',
    'alf_faq9_q'           => 'Puis-je utiliser ceci sur mon téléphone ?',
    'alf_faq9_a'           => 'Oui. L\'éditeur Alfred IDE fonctionne dans tout navigateur moderne — téléphone, iPad, portable. Alfred depuis n\'importe où.',
    'alf_faq10_q'          => 'Mes données sont-elles en sécurité ?',
    'alf_faq10_a'          => 'Infrastructure d\'entreprise renforcée. Accès isolé. Connexions chiffrées. Nous ne vendons pas vos données et ne nous entraînons pas sur vos fichiers.',
    'alf_faq11_q'          => 'Puis-je vraiment contrôler mon site web depuis WhatsApp ?',
    'alf_faq11_a'          => 'Oui. Et Signal, Discord, Telegram et SMS aussi. Envoyez un message comme « vérifie mon trafic » ou « installe un plugin » et Alfred répond en quelques secondes.',
    'alf_faq12_q'          => 'Comment fonctionnent les commandes vocales ?',
    'alf_faq12_a'          => 'Intégrées à votre session Alfred IDE dans le navigateur. Cliquez sur le microphone, parlez naturellement — « Configure le SSL sur monsite.com » — et Alfred l\'exécute. Aucune extension requise.',

    // v2 Engines section
    'alf_engines_label'    => 'Construit différemment',
    'alf_engines_h2'       => '16+ moteurs d\'intelligence.<br>Zéro compétition.',
    'alf_engines_sub'      => 'Alfred n\'exécute pas de simples commandes. Il réfléchit, se souvient, apprend, planifie et délègue — de façon autonome.',
    'alf_eng_elephant_h'   => 'ELEPHANT',
    'alf_eng_elephant_sub' => 'Mémoire persistante',
    'alf_eng_elephant_p'   => 'Alfred se souvient de vos préférences, décisions et contexte de projet à travers chaque session. Un élagage intelligent fait remonter les souvenirs les plus pertinents.',
    'alf_eng_oracle_h'     => 'ORACLE',
    'alf_eng_oracle_sub'   => 'Intelligence de code sémantique',
    'alf_eng_oracle_p'     => 'Cherchez dans votre code par sens, pas par mots-clés. « Trouve la logique d\'authentification » retourne <code>verifyToken()</code>. Comprend 25+ langages.',
    'alf_eng_playbook_h'   => 'PLAYBOOK',
    'alf_eng_playbook_sub' => 'Flux de travail auto-réparateurs',
    'alf_eng_playbook_p'   => 'Enregistrez des flux de travail multi-étapes en langage naturel. Déployez WordPress, auditez la sécurité — en une commande. 10 modèles intégrés.',
    'alf_eng_clockwork_h'  => 'CLOCKWORK',
    'alf_eng_clockwork_sub'=> 'Planificateur autonome',
    'alf_eng_clockwork_p'  => 'Planifiez des sauvegardes nocturnes, renouvellements SSL, audits de sécurité qui s\'exécutent pendant votre sommeil.',
    'alf_eng_hivemind_h'   => 'HIVEMIND',
    'alf_eng_hivemind_sub' => 'Délégation multi-agents',
    'alf_eng_hivemind_p'   => 'Alfred lance des sous-agents parallèles — chercheurs, analyseurs, ouvriers — pour résoudre des problèmes complexes 3&times; plus vite.',
    'alf_eng_sentinel_h'   => 'SENTINEL',
    'alf_eng_sentinel_sub' => 'Surveillance proactive &amp; auto-réparation',
    'alf_eng_sentinel_p'   => 'Surveille vos sites 24/7 — disponibilité, expiration SSL, espace disque, temps de réponse. Quand quelque chose casse, Alfred le répare automatiquement.',
    'alf_eng_nexus_h'      => 'NEXUS',
    'alf_eng_nexus_sub'    => 'Agent navigateur &amp; intelligence web',
    'alf_eng_nexus_p'      => 'Un navigateur complet qu\'Alfred contrôle de façon autonome. Naviguer, remplir des formulaires, extraire des données, capturer des écrans — par conversation.',
    'alf_eng_forge_h'      => 'FORGE',
    'alf_eng_forge_sub'    => 'Interpréteur de code &amp; bac à sable',
    'alf_eng_forge_p'      => 'Exécutez Python, Node.js, Bash, Ruby et PHP dans un bac à sable sécurisé. Analyse de données, prototypage, débogage — Alfred exécute du vrai code.',
    'alf_eng_cortex_h'     => 'CORTEX',
    'alf_eng_cortex_sub'   => 'Base de connaissances RAG',
    'alf_eng_cortex_p'     => 'Ingérez docs, PDF et bases de code dans un index vectoriel. Alfred répond avec une précision chirurgicale, ancré dans vos propres données.',
    'alf_eng_plus_h'       => 'En prime : Smart Commits &amp; Revue de code IA',
    'alf_eng_plus_p'       => 'L\'IA écrit vos messages de commit à partir de l\'analyse du diff. Revue de code de niveau ingénieur senior. Les audits de dépendances détectent les CVE.',

    // New commercial scenes
    'alf_scene_v7'         => '« Alfred, vérifie mon dernier commit pour les bogues »',
    'alf_scene_r7'         => '<strong>&check; Revue de code terminée.</strong> Score : <strong style="color:#10b981;">9,2/10</strong>. 2 suggestions (style), 0 problème critique. Aucune vulnérabilité de sécurité.',
    'alf_scene_v8'         => '« Souviens-toi : je déploie toujours en staging d\'abord »',
    'alf_scene_r8'         => '<strong>&check; Mémoire enregistrée.</strong> Alfred se souviendra de cette préférence dans chaque session future. Sur tous les appareils. Pour toujours.',
    'alf_scene_v9'         => '« Planifie des sauvegardes chaque nuit à 3 h et des audits de sécurité hebdomadaires »',
    'alf_scene_r9'         => '<strong>&check; 2 tâches planifiées.</strong> Sauvegarde BD à 3 h 00 chaque nuit. Audit de sécurité complet chaque dimanche à 6 h 00. Entièrement autonome.',

    // New comparison rows
    'alf_cmp_memory'       => 'Mémoire IA persistante',
    'alf_cmp_code_review'  => 'Revue de code IA',
    'alf_cmp_auto_sched'   => 'Planification automatisée',
    'alf_cmp_db_query'     => 'Requêtes de bases de données',
    'alf_cmp_workflows'    => 'Flux de travail sauvegardés',
    'alf_cmp_multi_agent'  => 'IA multi-agents',
    'alf_cmp_smart_git'    => 'Commits Git IA',
    'alf_cmp_dep_audit'    => 'Audit des dépendances',
    'alf_cmp_cross_session'=> '&check; Inter-sessions',
    'alf_cmp_10score'      => '&check; Score 1-10',
    'alf_cmp_cron_play'    => '&check; Cron + Playbooks',
    'alf_cmp_conversational'=> '&check; SQL conversationnel',
    'alf_cmp_10builtin'    => '&check; 10 intégrés',
    'alf_cmp_3agents'      => '&check; 3 agents parallèles',
    'alf_cmp_conventional' => '&check; Commits conventionnels',
    'alf_cmp_npm_composer'  => '&check; npm + Composer',
    'alf_cmp_not_persist'  => 'Par session seulement',

    // New FAQ
    'alf_faq13_q'          => 'Qu\'est-ce que la mémoire ELEPHANT ?',
    'alf_faq13_a'          => 'Alfred se souvient de vos préférences, décisions et leçons de façon permanente grâce aux embeddings vectoriels. Dites « souviens-toi que je préfère TypeScript » une fois, et Alfred le sait pour toujours.',
    'alf_faq14_q'          => 'Alfred peut-il réviser mon code ?',
    'alf_faq14_a'          => 'Oui. Le moteur de revue de code IA analyse vos diffs Git pour les bogues, vulnérabilités de sécurité, problèmes de performance et de style. Il note les changements de 1 à 10.',
    'alf_faq15_q'          => 'Comment fonctionne la planification autonome ?',
    'alf_faq15_a'          => 'CLOCKWORK vous permet de planifier n\'importe quel flux de travail automatiquement — sauvegardes nocturnes, audits de sécurité hebdomadaires, renouvellements SSL. Alfred travaille pendant que vous dormez.',

    // New tool categories
    'alf_cat_memory'       => 'ELEPHANT — Mémoire IA',
    'alf_cat_oracle'       => 'ORACLE — Intelligence de code',
    'alf_cat_playbook'     => 'PLAYBOOK — Flux de travail',
    'alf_cat_clockwork'    => 'CLOCKWORK — Planificateur',
    'alf_cat_hivemind'     => 'HIVEMIND — Multi-agents',
    'alf_cat_devtools'     => 'Outils de développement avancés',

    // ═══ v9.0.0 FRENCH — Commercial scenes ═══
    'alf_scene_v10'        => '« Alfred, va sur shopify.com et résume leurs forfaits de prix »',
    'alf_scene_r10'        => '<strong>&check; Agent navigateur déployé.</strong> Navigation vers shopify.com/pricing. 3 forfaits extraits : Basic 39 $/mois, Shopify 105 $/mois, Advanced 399 $/mois. Comparaison sauvegardée dans <code>/reports/competitor-pricing.md</code>.',
    'alf_scene_v11'        => '« Exécute ce script Python — analyse mon CSV de ventes et trace les tendances »',
    'alf_scene_r11'        => '<strong>&check; Code exécuté.</strong> Sandbox Python a traité 2 847 lignes. Revenus en hausse de 23 % vs année précédente. Graphique interactif généré. <strong style="color:#10b981;">Visualisez le graphique en direct dans votre éditeur.</strong>',
    'alf_scene_v12'        => '« Ingère nos docs API et dis-moi : quelle est la limite de débit pour /v2/orders ? »',
    'alf_scene_r12'        => '<strong>&check; Base de connaissances RAG créée.</strong> 47 documents indexés avec des embeddings vectoriels. Réponse : <em>La limite de débit de /v2/orders est de 250 requêtes/minute par clé API, avec une tolérance de rafale de 50.</em>',
    'alf_scene_v13'        => '« Surveille tous mes sites 24/7 — redémarre automatiquement tout ce qui tombe »',
    'alf_scene_r13'        => '<strong>&check; Surveillance proactive activée.</strong> Surveillance de 4 sites : maboulangerie.com, shop.maboulangerie.com, api.maboulangerie.com, staging.maboulangerie.com. Auto-réparation activée. Vous serez notifié sur WhatsApp.',

    // ═══ v9.0.0 FRENCH — Superpowers section ═══
    'alf_super_label'      => 'Version 9.0 — La plateforme 13 000+ outils',
    'alf_super_h2'         => '17 moteurs IA. 66 catégories. 25 nouvelles disciplines.<br>Rien d\'autre n\'approche.',
    'alf_super_sub'        => 'Nous n\'avons pas juste ajouté des outils — nous avons ajouté des disciplines entières. 13 000+ outils répartis dans 89 catégories. IA, E-Commerce, SEO, DevOps, Design, Sécurité, Données, Contenu, Accessibilité, Voix, Blockchain, VR et plus encore.',

    'alf_super_browser_h'  => 'Agent navigateur',
    'alf_super_browser_tag'=> 'Alfred voit le web',
    'alf_super_browser_p'  => 'Alfred ouvre un vrai navigateur, navigue sur les sites web, extrait des données, remplit des formulaires, prend des captures d\'écran, télécharge des fichiers. Recherche concurrentielle, surveillance des prix, web scraping — le tout par conversation.',

    'alf_super_interp_h'   => 'Interpréteur de code',
    'alf_super_interp_tag' => 'Exécutez du code. Voyez les résultats.',
    'alf_super_interp_p'   => 'Exécutez Python, Node.js, Bash, Ruby ou PHP dans un environnement sandboxé. Analysez des CSV, générez des graphiques, traitez des données, lancez des tests — puis voyez la sortie en artefacts interactifs. Vos données ne quittent jamais votre serveur.',

    'alf_super_rag_h'      => 'Base de connaissances RAG',
    'alf_super_rag_tag'    => 'Enseignez vos docs à Alfred',
    'alf_super_rag_p'      => 'Téléversez de la documentation, du code ou tout texte — Alfred l\'indexe avec des embeddings vectoriels et répond aux questions avec une précision chirurgicale. Docs API, wikis internes, FAQ clients — votre base de connaissances IA privée.',

    'alf_super_monitor_h'  => 'Surveillance proactive',
    'alf_super_monitor_tag'=> 'Alfred surveille. Alfred répare.',
    'alf_super_monitor_p'  => 'Surveillance continue du serveur avec auto-réparation. Un service tombe ? Alfred le redémarre. Le disque se remplit ? Alfred le nettoie. SSL expire ? Alfred le renouvelle. Vous recevez un message WhatsApp — après que le problème est déjà résolu.',

    'alf_super_workflow_h' => 'Automatisation de flux',
    'alf_super_workflow_tag'=> 'Automatisation intégrée',
    'alf_super_workflow_p' => 'Créez des flux d\'automatisation visuels — ou dites simplement à Alfred ce que vous voulez. « Quand un formulaire est soumis, envoie-moi un courriel et ajoute au tableur. » Des automatisations multi-étapes qui tournent 24/7.',

    'alf_super_artifact_h' => 'Artefacts interactifs',
    'alf_super_artifact_tag'=> 'Graphiques. Diagrammes. Aperçus.',
    'alf_super_artifact_p' => 'Alfred génère des graphiques interactifs, des diagrammes Mermaid et des aperçus HTML en direct dans votre éditeur. « Montre-moi mon trafic en diagramme à barres » — et le voilà. Sans plugins. Sans export. Juste des résultats.',

    'alf_super_steering_h' => 'Pilotage de commandes',
    'alf_super_steering_tag'=> 'Empilez vos commandes. Redirigez à tout moment.',
    'alf_super_steering_p' => 'Envoyez des commandes pendant qu\'Alfred travaille. Dites « arrête » pour rediriger. Zéro attente.',

    // ═══ v6.0.0 FRENCH — Comparison rows ═══
    'alf_cmp_browser_agent'=> 'Agent de navigation web',
    'alf_cmp_code_interp'  => 'Interpréteur de code',
    'alf_cmp_rag'          => 'Base de connaissances RAG',
    'alf_cmp_proactive_mon'=> 'Surveillance proactive',
    'alf_cmp_video_audio'  => 'Vidéo &amp; Audio IA',
    'alf_cmp_artifacts'    => 'Graphiques &amp; Artefacts',
    'alf_cmp_mcp_gateway'  => 'Passerelle MCP Client',
    'alf_cmp_playwright'   => '&check; Automatisation complète du navigateur',
    'alf_cmp_5langs'       => '&check; 5 langages sandboxés',
    'alf_cmp_vector_embed' => '&check; Embeddings vectoriels',
    'alf_cmp_auto_heal'    => '&check; Auto-réparation',
    'alf_cmp_25plus'       => '&check; Plusieurs modèles',
    'alf_cmp_live_charts'  => '&check; En direct dans l\'éditeur',
    'alf_cmp_ext_tools'    => '&check; Connecter outils externes',

    // ═══ v6.0.0 FRENCH — Comparison footer ═══
    'alf_cmp_footer'       => '<strong style="color:#fff;">Nous ne sommes pas juste un éditeur.</strong> Pas juste un hébergeur. Pas juste de l\'IA.<br>Nous sommes <strong style="color:#00D4FF;">toute la pile technologique</strong> — éditeur de code, agent navigateur, interpréteur de code, base de connaissances RAG, IA image &amp; vidéo, moteur de flux de travail, moniteur de serveur — au même endroit, pour moins cher.',

    // ═══ v6.0.0 FRENCH — FAQ ═══
    'alf_faq16_q'          => 'Alfred peut-il naviguer sur le web ?',
    'alf_faq16_a'          => 'Oui. Alfred dispose d\'un agent navigateur intégré. Il peut naviguer sur n\'importe quel site, extraire des données, remplir des formulaires, prendre des captures d\'écran et télécharger des fichiers. Demandez-lui de rechercher des concurrents, vérifier des prix ou surveiller n\'importe quelle URL — le tout par conversation naturelle.',
    'alf_faq17_q'          => 'Alfred peut-il exécuter du code Python ?',
    'alf_faq17_a'          => 'Oui. L\'interpréteur de code exécute Python, Node.js, Bash, Ruby et PHP dans un sandbox sécurisé. Analysez des données, générez des graphiques, traitez des CSV, lancez des scripts — et voyez la sortie en artefacts interactifs dans votre éditeur.',
    'alf_faq18_q'          => 'Qu\'est-ce qu\'une base de connaissances RAG ?',
    'alf_faq18_a'          => 'RAG (Retrieval-Augmented Generation) vous permet de nourrir Alfred avec votre documentation, spécifications API ou tout texte. Alfred indexe tout avec des embeddings vectoriels et répond avec une précision chirurgicale — en citant les documents spécifiques.',
    'alf_faq19_q'          => 'Alfred peut-il générer des graphiques et diagrammes ?',
    'alf_faq19_a'          => 'Oui. Demandez à Alfred de visualiser n\'importe quelles données : diagrammes à barres, courbes, camemberts, diagrammes Mermaid, aperçus HTML en direct. Les artefacts apparaissent directement dans votre éditeur — sans export, sans plugins, sans configuration.',
    'alf_faq20_q'          => 'Alfred surveille-t-il mon serveur automatiquement ?',
    'alf_faq20_a'          => 'Oui. La surveillance proactive veille sur vos sites 24/7. Si un service tombe, Alfred le redémarre. Si le SSL expire, Alfred le renouvelle. Si le disque se remplit, Alfred le nettoie. Vous êtes notifié après que le problème est déjà résolu.',

    // ═══ v6.0.0 FRENCH — AI Media section ═══
    'alf_img_h2_v6'        => 'Images IA. Vidéo IA. Audio IA.<br>Tout intégré.',
    'alf_img_sub_v6'       => 'Cursor ne peut pas faire ça. Lovable ne peut pas. VS Code non plus.<br><strong style="color:#00D4FF;">Alfred, oui.</strong> Plusieurs modèles d\'images. Plusieurs modèles de vidéo. Plusieurs modèles voix/audio. Analyse de vision. Traitement multimédia. Le tout par conversation.',
    'alf_img_powered_v6'   => '13,000+ outils IA pour images, vidéo et audio — enregistrés directement sur votre hébergement.',

    // v6.0.0 FR categories
    'alf_cat_rag'          => 'Base de connaissances RAG',
    'alf_cat_interpreter'  => 'Interpréteur de code',
    'alf_cat_browser'      => 'Agent navigateur',
    'alf_cat_mcpclient'    => 'Passerelle MCP Client',
    'alf_cat_workflows'    => 'Automatisation de flux',
    'alf_cat_proactive'    => 'Surveillance proactive',
]);

// Hebrew mirrors English until translated — must refresh after Alfred keys merge into EN.
if (isset($LANG_STRINGS['en'], $root_he_shell) && is_array($LANG_STRINGS['en']) && is_array($root_he_shell) && $root_he_shell !== []) {
    $LANG_STRINGS['he'] = array_merge($LANG_STRINGS['en'], $root_he_shell);
}

// ─── 13,000+ TOOLS — structured data (EN / FR) ───
// Each category: icon, color, lang_key (for category title from L()), count, tools[]
// Each tool: name_en, name_fr, desc_en, desc_fr, try_en, try_fr
$ALFRED_TOOL_CATS = [
    [
        'icon' => 'fas fa-folder-open', 'color' => 'purple', 'key' => 'alf_cat_files', 'count' => 8,
        'tools' => [
            ['Read File','Lire un fichier','Read any file from your hosting. Configs, logs, code — anything.','Lisez n\'importe quel fichier de votre hébergement. Configs, journaux, code — tout.','"Show me my .htaccess file"','"Montre-moi mon fichier .htaccess"'],
            ['Write File','Écrire un fichier','Create or overwrite files. Build pages, configs, scripts from scratch.','Créez ou écrasez des fichiers. Construisez des pages, configs, scripts à partir de zéro.','"Create a coming soon page"','"Crée une page bientôt disponible"'],
            ['List Directory','Lister le répertoire','See folder contents with sizes, permissions, dates.','Voir le contenu des dossiers avec tailles, permissions, dates.','"What\'s in my plugins folder?"','"Qu\'y a-t-il dans mon dossier plugins ?"'],
            ['Delete File','Supprimer un fichier','Remove files safely with confirmation before deleting.','Supprimez des fichiers en toute sécurité avec confirmation.','"Delete the old backups in /tmp"','"Supprime les anciennes sauvegardes dans /tmp"'],
            ['Rename / Move','Renommer / Déplacer','Rename or relocate files and folders.','Renommez ou déplacez des fichiers et dossiers.','"Rename logo.png to logo-old.png"','"Renomme logo.png en logo-old.png"'],
            ['Copy File','Copier un fichier','Duplicate files or directories. Great for pre-change backups.','Dupliquez des fichiers ou répertoires. Parfait pour les sauvegardes avant modification.','"Copy index.php to index.php.bak"','"Copie index.php vers index.php.bak"'],
            ['Set Permissions','Modifier les permissions','Change chmod/ownership. Fix permission issues instantly.','Changez chmod/propriétaire. Corrigez les problèmes de permissions instantanément.','"Fix my uploads folder permissions"','"Corrige les permissions de mon dossier uploads"'],
            ['Extract Archive','Extraire une archive','Unzip, untar — extract any archive on the server.','Décompressez n\'importe quelle archive sur le serveur.','"Extract theme.zip"','"Extrais theme.zip"'],
        ]
    ],
    [
        'icon' => 'fas fa-database', 'color' => 'cyan', 'key' => 'alf_cat_db', 'count' => 4,
        'tools' => [
            ['List Databases','Lister les bases de données','All MySQL databases with sizes and user access.','Toutes les bases de données MySQL avec tailles et accès utilisateur.','"Show me my databases"','"Montre-moi mes bases de données"'],
            ['Create Database','Créer une base de données','New MySQL databases with users and secure passwords.','Nouvelles bases de données MySQL avec utilisateurs et mots de passe sécurisés.','"Create a database for my blog"','"Crée une base de données pour mon blogue"'],
            ['Delete Database','Supprimer une base de données','Remove databases with confirmation before proceeding.','Supprimez des bases de données avec confirmation.','"Remove the test database"','"Supprime la base de données de test"'],
            ['Optimize Database','Optimiser la base de données','Clean revisions, spam, transients. Speed up WordPress.','Nettoyez révisions, spam, données temporaires. Accélérez WordPress.','"My site is slow, optimize the DB"','"Mon site est lent, optimise la BD"'],
        ]
    ],
    [
        'icon' => 'fas fa-globe', 'color' => 'green', 'key' => 'alf_cat_domains', 'count' => 10,
        'tools' => [
            ['List Domains','Lister les domaines','All domains, subdomains, and addon domains.','Tous les domaines, sous-domaines et domaines complémentaires.','"What domains do I have?"','"Quels domaines ai-je ?"'],
            ['Create Subdomain','Créer un sous-domaine','Add blog.mysite.com or shop.mysite.com instantly.','Ajoutez blog.monsite.com ou boutique.monsite.com instantanément.','"Create a staging subdomain"','"Crée un sous-domaine de test"'],
            ['Delete Subdomain','Supprimer un sous-domaine','Remove subdomains you no longer need.','Supprimez les sous-domaines dont vous n\'avez plus besoin.','"Remove the test subdomain"','"Supprime le sous-domaine de test"'],
            ['List DNS Records','Lister les enregistrements DNS','View A, AAAA, CNAME, MX, TXT, NS — complete picture.','Consultez A, AAAA, CNAME, MX, TXT, NS — vue complète.','"Show my DNS records"','"Montre mes enregistrements DNS"'],
            ['Add DNS Record','Ajouter un enregistrement DNS','Add records for email verification, services, routing.','Ajoutez des enregistrements pour la vérification courriel, services, routage.','"Add a TXT record for Google"','"Ajoute un enregistrement TXT pour Google"'],
            ['Remove DNS Record','Supprimer un enregistrement DNS','Delete DNS records no longer needed.','Supprimez les enregistrements DNS obsolètes.','"Remove the old CNAME"','"Supprime l\'ancien CNAME"'],
            ['Manage SSL','Gérer le SSL','SSL certificates — free Let\'s Encrypt or custom certs.','Certificats SSL — Let\'s Encrypt gratuit ou certificats personnalisés.','"Set up SSL for my domain"','"Configure le SSL pour mon domaine"'],
            ['Search Domains','Rechercher des domaines','Check availability for purchase. .com, .net, .io, more.','Vérifiez la disponibilité. .com, .net, .io et plus.','"Is mybusiness.com available?"','"Est-ce que moncommerce.com est disponible ?"'],
            ['Register Domain','Enregistrer un domaine','Buy and register domain names through Alfred.','Achetez et enregistrez des noms de domaine via Alfred.','"Register mybakery.ca"','"Enregistre maboulangerie.ca"'],
            ['Domain Contacts','Contacts du domaine','View/manage WHOIS contact info for your domains.','Consultez/gérez les infos WHOIS de vos domaines.','"Show my domain contact info"','"Montre mes infos de contact de domaine"'],
        ]
    ],
    [
        'icon' => 'fas fa-envelope', 'color' => 'orange', 'key' => 'alf_cat_email', 'count' => 5,
        'tools' => [
            ['List Emails','Lister les courriels','All email accounts with usage and quotas.','Tous les comptes courriel avec utilisation et quotas.','"Show my email accounts"','"Montre mes comptes courriel"'],
            ['Create Email','Créer un courriel','Professional email you@yourdomain.com in seconds.','Courriel professionnel vous@votredomaine.com en secondes.','"Create hello@mybusiness.com"','"Crée bonjour@moncommerce.com"'],
            ['Delete Email','Supprimer un courriel','Remove email accounts you no longer need.','Supprimez les comptes courriel dont vous n\'avez plus besoin.','"Delete the old info@ email"','"Supprime l\'ancien courriel info@"'],
            ['Email Forwarding','Redirection de courriel','Forward to Gmail, Outlook, or any address.','Redirigez vers Gmail, Outlook ou toute adresse.','"Forward sales@ to my Gmail"','"Redirige ventes@ vers mon Gmail"'],
            ['Autoresponder','Répondeur automatique','Auto-reply for out-of-office or welcome messages.','Réponse automatique pour absence ou messages de bienvenue.','"Set up an autoresponder"','"Configure un répondeur automatique"'],
        ]
    ],
    [
        'icon' => 'fas fa-shield-alt', 'color' => 'blue', 'key' => 'alf_cat_backups', 'count' => 9,
        'tools' => [
            ['Create Backup','Créer une sauvegarde','Full account backup — files, databases, emails, configs.','Sauvegarde complète du compte — fichiers, bases de données, courriels, configs.','"Back up everything"','"Sauvegarde tout"'],
            ['List Backups','Lister les sauvegardes','All backups with dates and sizes.','Toutes les sauvegardes avec dates et tailles.','"Show me my backups"','"Montre-moi mes sauvegardes"'],
            ['Restore Backup','Restaurer une sauvegarde','Restore your entire account or specific files.','Restaurez votre compte entier ou des fichiers spécifiques.','"Restore yesterday\'s backup"','"Restaure la sauvegarde d\'hier"'],
            ['List Cron Jobs','Lister les tâches Cron','View scheduled automated tasks.','Consultez les tâches automatisées planifiées.','"What cron jobs do I have?"','"Quelles tâches cron ai-je ?"'],
            ['Create Cron Job','Créer une tâche Cron','Schedule backups, cleanups, reports — any schedule.','Planifiez sauvegardes, nettoyages, rapports — tout horaire.','"Run a backup every night at 3am"','"Lance une sauvegarde chaque nuit à 3h"'],
            ['Delete Cron Job','Supprimer une tâche Cron','Remove scheduled tasks.','Supprimez des tâches planifiées.','"Remove that hourly cron"','"Supprime cette tâche cron horaire"'],
            ['Account Stats','Statistiques du compte','Disk, bandwidth, emails, databases — full overview.','Disque, bande passante, courriels, bases de données — vue complète.','"How much disk space am I using?"','"Combien d\'espace disque est-ce que j\'utilise ?"'],
            ['Account Config','Configuration du compte','PHP version, limits, features enabled.','Version PHP, limites, fonctionnalités activées.','"What PHP version am I on?"','"Quelle version de PHP ai-je ?"'],
            ['Server Info','Info serveur','PHP, Node.js, Git versions, disk, OS details.','Versions PHP, Node.js, Git, disque, détails OS.','"What can my server do?"','"Que peut faire mon serveur ?"'],
        ]
    ],
    [
        'icon' => 'fab fa-wordpress', 'color' => 'red', 'key' => 'alf_cat_wp', 'count' => 11,
        'tools' => [
            ['Install WordPress','Installer WordPress','One-command install with WP-CLI. Database, admin, everything.','Installation en une commande avec WP-CLI. Base de données, admin, tout.','"Install WordPress on myblog.com"','"Installe WordPress sur monblogue.com"'],
            ['Site Info','Info du site','Name, URL, version, theme, plugins, health status.','Nom, URL, version, thème, plugins, état de santé.','"What WordPress version am I running?"','"Quelle version de WordPress ai-je ?"'],
            ['List Plugins','Lister les plugins','All plugins with active/inactive status and versions.','Tous les plugins avec statut actif/inactif et versions.','"What plugins do I have?"','"Quels plugins ai-je ?"'],
            ['Install Plugin','Installer un plugin','Install and activate any WP plugin from the repo.','Installez et activez tout plugin WP du dépôt.','"Install Yoast SEO"','"Installe Yoast SEO"'],
            ['List Themes','Lister les thèmes','Available themes, active theme, versions.','Thèmes disponibles, thème actif, versions.','"Show me my themes"','"Montre-moi mes thèmes"'],
            ['Install Theme','Installer un thème','Install and activate any WordPress theme.','Installez et activez tout thème WordPress.','"Install a restaurant theme"','"Installe un thème de restaurant"'],
            ['Check Updates','Vérifier les mises à jour','Core, plugin, and theme updates available.','Mises à jour du cœur, plugins et thèmes disponibles.','"Any updates I should install?"','"Y a-t-il des mises à jour à installer ?"'],
            ['Search Plugins','Rechercher des plugins','Search the entire WP plugin repository.','Recherchez dans tout le dépôt de plugins WP.','"Find a good contact form plugin"','"Trouve un bon plugin de formulaire de contact"'],
            ['Search Themes','Rechercher des thèmes','Search themes by keyword, style, or purpose.','Recherchez des thèmes par mot-clé, style ou utilité.','"Find a portfolio theme"','"Trouve un thème portfolio"'],
            ['DB Optimize','Optimisation BD','Clean revisions, spam, transients, orphaned data.','Nettoyez révisions, spam, données temporaires, données orphelines.','"Clean up my database"','"Nettoie ma base de données"'],
            ['Run WP-CLI','Exécuter WP-CLI','Any WP-CLI command for advanced WordPress admin.','Toute commande WP-CLI pour administration avancée.','"Run wp cache flush"','"Exécute wp cache flush"'],
        ]
    ],
    [
        'icon' => 'fab fa-git-alt', 'color' => 'pink', 'key' => 'alf_cat_git', 'count' => 6,
        'tools' => [
            ['Git Status','Statut Git','Changed, staged, and untracked files.','Fichiers modifiés, indexés et non suivis.','"What files have I changed?"','"Quels fichiers ai-je modifiés ?"'],
            ['Git Log','Journal Git','Commit history with dates and messages.','Historique des commits avec dates et messages.','"Show recent commits"','"Montre les commits récents"'],
            ['Git Diff','Diff Git','What changed in each file, line by line.','Ce qui a changé dans chaque fichier, ligne par ligne.','"What changed since last commit?"','"Qu\'est-ce qui a changé depuis le dernier commit ?"'],
            ['Git Commit','Commit Git','Save work with a descriptive message.','Enregistrez votre travail avec un message descriptif.','"Commit with \'Added contact page\'"','"Enregistre avec \'Page contact ajoutée\'"'],
            ['Git Revert','Retour Git','Undo changes, go back to any previous commit.','Annulez les changements, revenez à n\'importe quel commit.','"Undo the last commit"','"Annule le dernier commit"'],
            ['Git Init','Init Git','Start a new Git repo for version control.','Démarrez un nouveau dépôt Git pour le versionnement.','"Start tracking my site with Git"','"Commence à suivre mon site avec Git"'],
        ]
    ],
    [
        'icon' => 'fas fa-lock', 'color' => 'red', 'key' => 'alf_cat_security', 'count' => 6,
        'tools' => [
            ['Malware Scan','Analyse de malware','Deep scan for malware, backdoors, suspicious code.','Analyse approfondie des malwares, portes dérobées, code suspect.','"Is my site hacked?"','"Mon site est-il piraté ?"'],
            ['Audit Permissions','Audit des permissions','Find dangerous permissions, world-writable files.','Trouvez les permissions dangereuses, fichiers en écriture globale.','"Are my permissions secure?"','"Mes permissions sont-elles sécurisées ?"'],
            ['Security Scan','Analyse de sécurité','Full audit: malware + permissions + config analysis.','Audit complet : malware + permissions + analyse de config.','"Run a full security audit"','"Lance un audit de sécurité complet"'],
            ['Error Log','Journal d\'erreurs','PHP errors and warnings. Find what\'s broken fast.','Erreurs et avertissements PHP. Trouvez rapidement ce qui ne va pas.','"Show recent PHP errors"','"Montre les erreurs PHP récentes"'],
            ['Access Log','Journal d\'accès','Who\'s visiting: IPs, pages, status codes.','Qui visite : IPs, pages, codes de statut.','"Who\'s been hitting my site?"','"Qui visite mon site ?"'],
            ['Analyze Errors','Analyser les erreurs','AI groups errors by type, frequency, suggests fixes.','L\'IA regroupe les erreurs par type, fréquence et suggère des correctifs.','"Why is my site showing white?"','"Pourquoi mon site affiche une page blanche ?"'],
        ]
    ],
    [
        'icon' => 'fas fa-chart-line', 'color' => 'teal', 'key' => 'alf_cat_analytics', 'count' => 5,
        'tools' => [
            ['Visitor Stats','Statistiques visiteurs','Page views, unique visitors, top pages, referrers.','Pages vues, visiteurs uniques, pages populaires, référents.','"How many visitors today?"','"Combien de visiteurs aujourd\'hui ?"'],
            ['Bandwidth Stats','Statistiques bande passante','Usage by domain, heavy assets, prevent overages.','Utilisation par domaine, fichiers lourds, prévention des dépassements.','"Am I using too much bandwidth?"','"Est-ce que j\'utilise trop de bande passante ?"'],
            ['Traffic Report','Rapport de trafic','Trends, peaks, geography, bots vs humans.','Tendances, pics, géographie, robots vs humains.','"Traffic report for this month"','"Rapport de trafic pour ce mois"'],
            ['Site Health','Santé du site','DNS, HTTP, SSL, response time, performance score.','DNS, HTTP, SSL, temps de réponse, score de performance.','"Is my site healthy and fast?"','"Mon site est-il en bonne santé et rapide ?"'],
            ['Server Info','Info serveur','PHP, Node.js, Git, disk, domain count, OS.','PHP, Node.js, Git, disque, nombre de domaines, OS.','"What can my server do?"','"Que peut faire mon serveur ?"'],
        ]
    ],
    [
        'icon' => 'fas fa-credit-card', 'color' => 'yellow', 'key' => 'alf_cat_billing', 'count' => 14,
        'tools' => [
            ['My Profile','Mon profil','Account name, email, company, contact details.','Nom du compte, courriel, entreprise, coordonnées.','"What account am I on?"','"Quel compte ai-je ?"'],
            ['My Services','Mes services','Active hosting plans, statuses, renewal dates.','Forfaits d\'hébergement actifs, statuts, dates de renouvellement.','"What plans do I have?"','"Quels forfaits ai-je ?"'],
            ['Service Details','Détails du service','Domain, IP, server, resources for a plan.','Domaine, IP, serveur, ressources d\'un forfait.','"Details of my hosting"','"Détails de mon hébergement"'],
            ['Browse Products','Parcourir les produits','Available hosting plans, add-ons, services.','Forfaits d\'hébergement, ajouts et services disponibles.','"What upgrades are available?"','"Quelles mises à niveau sont disponibles ?"'],
            ['Search Domains','Rechercher des domaines','Check domain availability for purchase.','Vérifiez la disponibilité des domaines à l\'achat.','"Is coolbusiness.com taken?"','"Est-ce que boncommerce.com est pris ?"'],
            ['Register Domain','Enregistrer un domaine','Buy and register domains through Alfred.','Achetez et enregistrez des domaines via Alfred.','"Buy myproject.com"','"Achète monprojet.com"'],
            ['Order Hosting','Commander l\'hébergement','Purchase a hosting plan with a domain.','Achetez un forfait d\'hébergement avec un domaine.','"Buy Builder plan"','"Achète le forfait Builder"'],
            ['My Invoices','Mes factures','Unpaid and recent invoices with amounts.','Factures impayées et récentes avec montants.','"Any unpaid invoices?"','"Des factures impayées ?"'],
            ['Pay Invoice','Payer une facture','Pay outstanding invoices via saved method.','Payez les factures en souffrance avec votre méthode enregistrée.','"Pay my latest invoice"','"Paie ma dernière facture"'],
            ['Add Credit','Ajouter un crédit','Pre-pay with account credit.','Prépayez avec un crédit sur votre compte.','"Add $50 credit"','"Ajoute 50 $ de crédit"'],
            ['Available Add-ons','Ajouts disponibles','Extra IPs, backup, SSL add-ons available.','IPs supplémentaires, sauvegardes, ajouts SSL disponibles.','"What add-ons can I get?"','"Quels ajouts puis-je obtenir ?"'],
            ['Order Add-on','Commander un ajout','Purchase add-ons for existing plans.','Achetez des ajouts pour vos forfaits existants.','"Add daily backups"','"Ajoute des sauvegardes quotidiennes"'],
            ['Submit Ticket','Soumettre un billet','Open a support ticket. Alfred fills details.','Ouvrez un billet de support. Alfred remplit les détails.','"I need help with billing"','"J\'ai besoin d\'aide avec la facturation"'],
            ['My Tickets','Mes billets','Open tickets and latest replies.','Billets ouverts et dernières réponses.','"Any updates on my ticket?"','"Des nouvelles de mon billet ?"'],
        ]
    ],
    [
        'icon' => 'fas fa-image', 'color' => 'indigo', 'key' => 'alf_cat_imggen', 'count' => 2,
        'tools' => [
            ['Generate Image','Générer une image','AI images from text. Multiple models for photorealism, illustration, logos, and product shots. Saved directly to your website.','Images IA à partir de texte. Plusieurs modèles pour photo-réalisme, illustration, logos et prises de vue produit. Enregistrées sur votre site.','"Generate a hero image for my restaurant"','"Génère une image principale pour mon restaurant"', false],
            ['List Generated Images','Lister les images générées','Browse all AI-generated images with URLs and dates.','Parcourez toutes les images générées par IA avec URLs et dates.','"Show all images we\'ve created"','"Montre toutes les images que nous avons créées"', false],
        ]
    ],
    [
        'icon' => 'fas fa-film', 'color' => 'blue', 'key' => 'alf_cat_media', 'count' => 10,
        'tools' => [
            ['Generate Video','Générer une vidéo','AI video from text or images. Multiple models for cinematic-quality video generation.','Vidéo IA à partir de texte ou images. Plusieurs modèles pour génération vidéo de qualité cinématographique.','"Generate a 5-second product intro video"','"Génère une vidéo d\'intro produit de 5 secondes"', false],
            ['Generate Audio','Générer de l\'audio','Text-to-speech with multiple TTS models. Natural voices saved as MP3.','Synthèse vocale avec plusieurs modèles TTS. Voix naturelles en MP3.','"Read this paragraph aloud with a warm voice"','"Lis ce paragraphe à voix haute avec une voix chaleureuse"', false],
            ['Vision Analyze','Analyser une image','AI vision: send screenshots, mockups, diagrams for analysis. Screenshot-to-code, UI review, OCR, accessibility audit.','Vision IA : envoyez captures d\'écran, maquettes, diagrammes pour analyse. Capture-vers-code, revue UI, OCR.','"Convert this screenshot to HTML/CSS"','"Convertis cette capture d\'écran en HTML/CSS"', false],
            ['Process Video','Traiter une vidéo','Video processing: trim, resize, convert, extract audio, compress, create GIFs, thumbnails, speed control.','Traitement vidéo : couper, redimensionner, convertir, extraire audio, compresser, créer GIFs, miniatures.','"Extract audio from video.mp4"','"Extrais l\'audio de video.mp4"', false],
            ['Process Image','Traiter une image','Image processing: resize, compress, convert, watermark, crop, rotate, blur, optimize, get info.','Traitement image : redimensionner, compresser, convertir, filigrane, recadrer, optimiser.','"Resize all images to 800px wide"','"Redimensionne toutes les images à 800px de large"', false],
            ['Download Media','Télécharger des médias','Download videos/audio from YouTube, Vimeo, and 1000+ sites. Extract audio, get metadata.','Téléchargez vidéos/audio de YouTube, Vimeo et 1000+ sites. Extraire audio, métadonnées.','"Download that tutorial video"','"Télécharge cette vidéo tutoriel"', false],
            ['Execute SQL','Exécuter du SQL','Run SQL queries on your MySQL databases. SELECT, INSERT, UPDATE with safety checks.','Exécutez des requêtes SQL sur vos bases MySQL. SELECT, INSERT, UPDATE avec vérifications de sécurité.','"Show me the last 10 orders"','"Montre-moi les 10 dernières commandes"', false],
            ['Switch PHP Version','Changer la version PHP','Switch between PHP 8.2 and 8.3 for any domain via .htaccess. Instant, no downtime.','Basculez entre PHP 8.2 et 8.3 pour tout domaine via .htaccess. Instantané, sans interruption.','"Switch to PHP 8.3"','"Passe à PHP 8.3"', false],
            ['Voice Status','Statut vocal','Check the live voice server status — active sessions, WebSocket health.','Vérifiez le statut du serveur vocal — sessions actives, santé WebSocket.','"Is the voice server running?"','"Le serveur vocal fonctionne-t-il ?"', false],
            ['List AI Models','Lister les modèles IA','Browse all available AI models for image, video, audio, vision, and text generation. Model aliases and IDs.','Parcourez tous les modèles IA disponibles pour image, vidéo, audio, vision et génération de texte. Alias et identifiants.','"What image models are available?"','"Quels modèles d\'image sont disponibles ?"', false],
        ]
    ],

    // ─── v2 INTELLIGENCE ENGINES ───
    [
        'icon' => 'fas fa-brain', 'color' => 'purple', 'key' => 'alf_cat_memory', 'count' => 5,
        'tools' => [
            ['Remember','Mémoriser','Save facts, preferences, decisions to permanent AI memory. Alfred recalls them across all future sessions.','Enregistrez faits, préférences et décisions dans la mémoire IA permanente. Alfred s\'en souvient dans toutes les sessions futures.','"Remember I prefer TypeScript with no semicolons"','"Souviens-toi que je préfère TypeScript sans points-virgules"', false],
            ['Recall','Se souvenir','Search Alfred\'s long-term memory semantically. Find decisions, preferences, project context.','Recherchez dans la mémoire à long terme d\'Alfred. Retrouvez décisions, préférences, contexte de projet.','"What do you remember about my project?"','"Que te souviens-tu de mon projet ?"', false],
            ['Forget','Oublier','Remove specific memories or clear all. Full control over what Alfred knows.','Supprimez des souvenirs spécifiques ou effacez tout. Contrôle total sur ce qu\'Alfred sait.','"Forget what I said about the API key"','"Oublie ce que j\'ai dit sur la clé API"', false],
            ['Memory Summary','Résumé mémoire','View all stored memories organized by category: preferences, decisions, facts, lessons.','Consultez tous les souvenirs stockés par catégorie : préférences, décisions, faits, leçons.','"What do you know about me?"','"Que sais-tu de moi ?"', false],
            ['Save Session','Sauvegarder la session','Summarize and save the current conversation to long-term memory for future reference.','Résumez et sauvegardez la conversation actuelle dans la mémoire à long terme.','"Save a summary of what we did today"','"Sauvegarde un résumé de ce qu\'on a fait aujourd\'hui"', false],
        ]
    ],
    [
        'icon' => 'fas fa-search-plus', 'color' => 'cyan', 'key' => 'alf_cat_oracle', 'count' => 4,
        'tools' => [
            ['Semantic Code Search','Recherche sémantique de code','Search your entire codebase by meaning. "Find authentication middleware" returns verifyToken(). 25+ languages.','Cherchez dans tout votre code par le sens. « Trouve le middleware d\'authentification » retourne verifyToken(). 25+ langages.','"Find where we handle user login"','"Trouve où on gère la connexion utilisateur"', false],
            ['Reindex Workspace','Réindexer l\'espace de travail','Index all code files for semantic search. Incremental — only re-indexes changed files.','Indexez tous les fichiers de code pour la recherche sémantique. Incrémental — ne réindexe que les fichiers modifiés.','"Reindex my project after the refactor"','"Réindexe mon projet après le refactoring"', false],
            ['Index Stats','Statistiques d\'index','Files indexed, chunks created, languages detected, storage size.','Fichiers indexés, morceaux créés, langages détectés, taille du stockage.','"How much of my code is indexed?"','"Quelle portion de mon code est indexée ?"', false],
            ['Auto-Index Watcher','Observateur auto-index','Start/stop a file watcher that automatically keeps the search index fresh as you code.','Démarrez/arrêtez un observateur qui maintient l\'index de recherche à jour automatiquement.','"Keep my search index fresh automatically"','"Garde mon index de recherche à jour automatiquement"', false],
        ]
    ],
    [
        'icon' => 'fas fa-list-check', 'color' => 'green', 'key' => 'alf_cat_playbook', 'count' => 3,
        'tools' => [
            ['Run Playbook','Exécuter un playbook','Execute a multi-step workflow. 10 built-in templates: WordPress deploy, Laravel, Node.js, nightly backups, SSL check, security audit, and more.','Exécutez un flux de travail multi-étapes. 10 modèles intégrés : déploiement WordPress, Laravel, Node.js, sauvegardes nocturnes, vérification SSL, audit de sécurité, et plus.','"Run the WordPress deploy playbook"','"Exécute le playbook de déploiement WordPress"', false],
            ['List Playbooks','Lister les playbooks','View all available playbooks — built-in templates plus your custom ones.','Consultez tous les playbooks disponibles — modèles intégrés plus vos personnalisations.','"What workflows are available?"','"Quels flux de travail sont disponibles ?"', false],
            ['Save Playbook','Sauvegarder un playbook','Create your own reusable workflow template from natural language steps.','Créez votre propre modèle de flux de travail réutilisable en langage naturel.','"Save what we just did as a playbook"','"Sauvegarde ce qu\'on vient de faire comme playbook"', false],
        ]
    ],
    [
        'icon' => 'fas fa-clock', 'color' => 'orange', 'key' => 'alf_cat_clockwork', 'count' => 4,
        'tools' => [
            ['Schedule Task','Planifier une tâche','Schedule any playbook to run automatically on a cron schedule. Nightly backups, weekly audits, hourly checks.','Planifiez n\'importe quel playbook pour s\'exécuter automatiquement. Sauvegardes nocturnes, audits hebdomadaires.','"Schedule a nightly backup at 3 AM"','"Planifie une sauvegarde chaque nuit à 3 h"', false],
            ['List Tasks','Lister les tâches','View all scheduled tasks with status, last run, next run.','Consultez toutes les tâches planifiées avec statut, dernière exécution, prochaine exécution.','"What tasks are scheduled?"','"Quelles tâches sont planifiées ?"', false],
            ['Delete Task','Supprimer une tâche','Remove a scheduled task.','Supprimez une tâche planifiée.','"Cancel the nightly backup schedule"','"Annule la planification de sauvegarde nocturne"', false],
            ['Task Logs','Journaux de tâches','View execution history — timestamps, success/failure, elapsed time.','Consultez l\'historique d\'exécution — horodatages, succès/échec, durée.','"Show me the backup task logs"','"Montre-moi les journaux de la tâche de sauvegarde"', false],
        ]
    ],
    [
        'icon' => 'fas fa-project-diagram', 'color' => 'pink', 'key' => 'alf_cat_hivemind', 'count' => 2,
        'tools' => [
            ['Spawn Sub-Agent','Lancer un sous-agent','Spawn parallel AI agents — researchers, analyzers, workers. 3 simultaneous agents with role-based access.','Lancez des agents IA parallèles — chercheurs, analyseurs, ouvriers. 3 agents simultanés avec accès selon le rôle.','"Research these 3 APIs in parallel"','"Recherche ces 3 API en parallèle"', false],
            ['Collect Results','Collecter les résultats','Gather and merge results from running sub-agents into a unified response.','Rassemblez et fusionnez les résultats des sous-agents en cours dans une réponse unifiée.','"What did the researchers find?"','"Qu\'ont trouvé les chercheurs ?"', false],
        ]
    ],
    [
        'icon' => 'fas fa-rocket', 'color' => 'teal', 'key' => 'alf_cat_devtools', 'count' => 12,
        'tools' => [
            ['AI Code Review','Revue de code IA','Senior-engineer code review on your git diffs. Scores 1-10 for quality, flags bugs, security holes, and style issues.','Revue de code de niveau ingénieur senior sur vos diffs Git. Score de 1 à 10, signale bogues, failles de sécurité et problèmes de style.','"Review my last commit for bugs"','"Révise mon dernier commit pour les bogues"', false],
            ['Smart Commit','Commit intelligent','Stage changes and commit with an AI-generated conventional commit message (feat/fix/refactor). No more generic messages.','Indexez les changements et committez avec un message conventionnel généré par IA.','"Commit with a smart message"','"Committe avec un message intelligent"', false],
            ['Amend Commit','Modifier le commit','Rewrite the last commit message with AI analysis of the actual diff.','Réécrivez le dernier message de commit avec l\'analyse IA du diff.','"Rewrite my last commit message"','"Réécris mon dernier message de commit"', false],
            ['Dependency Audit','Audit des dépendances','Scan npm, Composer, pip dependencies for security vulnerabilities and outdated packages.','Analysez les dépendances npm, Composer, pip : vulnérabilités de sécurité et paquets obsolètes.','"Are any of my packages vulnerable?"','"Est-ce que mes paquets ont des vulnérabilités ?"', false],
            ['Project Snapshot','Snapshot du projet','Full project overview: files, dependencies, git status, disk usage, health check.','Vue complète du projet : fichiers, dépendances, statut Git, utilisation disque, bilan de santé.','"Give me a snapshot of this project"','"Donne-moi un snapshot de ce projet"', false],
            ['Run Terminal','Exécuter le terminal','Execute shell commands directly. 30-second timeout, sandboxed to your account.','Exécutez des commandes shell directement. Délai de 30 secondes, isolé dans votre compte.','"Run npm install"','"Exécute npm install"', false],
            ['Fetch URL','Récupérer une URL','Fetch any URL — HTML converted to clean text, JSON parsed, XML supported.','Récupérez n\'importe quelle URL — HTML converti en texte propre, JSON parsé, XML supporté.','"Fetch the API docs from that URL"','"Récupère la doc API depuis cette URL"', false],
            ['Read PDF','Lire un PDF','Extract text and metadata from PDF documents up to 50MB.','Extrayez texte et métadonnées de documents PDF jusqu\'à 50 Mo.','"Read the contract PDF I uploaded"','"Lis le PDF de contrat que j\'ai téléchargé"', false],
            ['Create Word Doc','Créer un document Word','Generate professional .docx documents with headers, tables, and formatting.','Générez des documents .docx professionnels avec en-têtes, tableaux et mise en forme.','"Create a proposal document"','"Crée un document de proposition"', false],
            ['Create PDF','Créer un PDF','Generate professional PDFs with page numbers, headers, and tables.','Générez des PDF professionnels avec numéros de page, en-têtes et tableaux.','"Generate a PDF report"','"Génère un rapport PDF"', false],
            ['Git Branches','Branches Git','List all local and remote branches with current branch highlighted.','Listez toutes les branches locales et distantes avec la branche actuelle en surbrillance.','"What branches do I have?"','"Quelles branches ai-je ?"', false],
            ['Tool Analytics','Analytique des outils','View which tools you use most, average response times, success rates.','Consultez vos outils les plus utilisés, temps de réponse moyen, taux de succès.','"Show me my tool usage stats"','"Montre-moi mes statistiques d\'utilisation"', false],
        ]
    ],

    // ─── v6.0.0 NEW CAPABILITIES ───
    [
        'icon' => 'fas fa-book-open', 'color' => 'indigo', 'key' => 'alf_cat_rag', 'count' => 4,
        'tools' => [
            ['RAG Ingest','Ingérer RAG','Upload PDFs, DOCX, Markdown, code files or even URLs into a searchable knowledge base. Alfred chunks, embeds, and stores for instant queries.','Téléchargez des PDF, DOCX, Markdown, fichiers de code ou URL dans une base de connaissances. Alfred découpe, vectorise et stocke pour des requêtes instantanées.','"Ingest all our API docs into the project-docs collection"','"Ingère toute notre documentation API dans la collection project-docs"', false],
            ['RAG Query','Requête RAG','Ask questions against your knowledge base. Semantic search retrieves the most relevant chunks and generates grounded answers.','Posez des questions à votre base de connaissances. La recherche sémantique retrouve les morceaux les plus pertinents et génère des réponses fondées.','"What does our API say about rate limits?"','"Que dit notre API sur les limites de débit ?"', false],
            ['RAG Collections','Collections RAG','List all knowledge-base collections with document counts, chunk counts, and storage size.','Listez toutes les collections de la base de connaissances avec nombre de documents, morceaux et taille.','"Show my RAG collections"','"Montre mes collections RAG"', false],
            ['RAG Delete','Supprimer RAG','Remove an entire collection or a specific document source from a collection.','Supprimez une collection entière ou un document spécifique d\'une collection.','"Delete the old-docs collection"','"Supprime la collection old-docs"', false],
        ]
    ],
    [
        'icon' => 'fas fa-terminal', 'color' => 'green', 'key' => 'alf_cat_interpreter', 'count' => 3,
        'tools' => [
            ['Run Code','Exécuter du code','Execute Python, Node.js, Bash, Ruby, or PHP in an isolated sandbox. Captures stdout, stderr, exit codes, and matplotlib images.','Exécutez Python, Node.js, Bash, Ruby ou PHP dans un bac à sable isolé. Capture stdout, stderr, codes de sortie et images matplotlib.','"Run this Python script and show the output"','"Exécute ce script Python et montre le résultat"', false],
            ['List Sessions','Lister les sessions','View active code interpreter sessions with language, creation time, and execution count per user.','Consultez les sessions actives de l\'interpréteur de code avec langue, heure de création et nombre d\'exécutions.','"How many interpreter sessions are running?"','"Combien de sessions d\'interpréteur sont en cours ?"', false],
            ['Kill Session','Terminer la session','Terminate a code interpreter session and clean up its temporary files and state.','Terminez une session d\'interpréteur de code et nettoyez ses fichiers temporaires.','"Kill the Python session"','"Termine la session Python"', false],
        ]
    ],
    [
        'icon' => 'fas fa-globe-americas', 'color' => 'cyan', 'key' => 'alf_cat_browser', 'count' => 6,
        'tools' => [
            ['Browse Web','Naviguer le web','Navigate to any URL with a headless browser. Extracts text, links, and metadata from JavaScript-rendered pages.','Accédez à n\'importe quelle URL avec un navigateur headless. Extrait texte, liens et métadonnées des pages JavaScript.','"Go to that docs page and read the API reference"','"Va sur cette page de docs et lis la référence API"', false],
            ['Screenshot Page','Capture d\'écran','Take a full-page or element-specific screenshot of any web page. Returns a PNG image.','Prenez une capture d\'écran de page complète ou d\'un élément. Retourne une image PNG.','"Screenshot the homepage of my site"','"Fais une capture d\'écran de la page d\'accueil de mon site"', false],
            ['Click Element','Cliquer un élément','Click any element on a web page by CSS selector. Returns the page state after the click.','Cliquez sur n\'importe quel élément par sélecteur CSS. Retourne l\'état de la page après le clic.','"Click the login button on the page"','"Clique sur le bouton de connexion sur la page"', false],
            ['Fill Form','Remplir un formulaire','Fill out web forms by providing field selectors and values, then optionally submit.','Remplissez des formulaires web en fournissant sélecteurs et valeurs, puis soumettez optionnellement.','"Fill in the contact form and submit it"','"Remplis le formulaire de contact et soumets-le"', false],
            ['Extract Data','Extraire des données','Extract structured data from web pages: tables, lists, prices, emails — using CSS selectors.','Extrayez des données structurées des pages web : tableaux, listes, prix, courriels — via sélecteurs CSS.','"Extract all product prices from that page"','"Extrais tous les prix des produits de cette page"', false],
            ['Web Search','Recherche web','Search the web via DuckDuckGo and return results with titles, URLs, and snippets.','Recherchez sur le web via DuckDuckGo et obtenez titres, URLs et extraits.','"Search for the latest Node.js security advisory"','"Cherche le dernier avis de sécurité Node.js"', false],
        ]
    ],
    [
        'icon' => 'fas fa-plug', 'color' => 'purple', 'key' => 'alf_cat_mcpclient', 'count' => 4,
        'tools' => [
            ['MCP Connect','Connecter MCP','Connect to external MCP servers (GitHub, Slack, Brave Search, Postgres, etc.) and discover their tools.','Connectez-vous à des serveurs MCP externes (GitHub, Slack, Brave Search, Postgres, etc.) et découvrez leurs outils.','"Connect to the GitHub MCP server"','"Connecte-toi au serveur MCP GitHub"', false],
            ['MCP Disconnect','Déconnecter MCP','Disconnect from an external MCP server and unregister its tools.','Déconnectez-vous d\'un serveur MCP externe et désenregistrez ses outils.','"Disconnect from GitHub"','"Déconnecte-toi de GitHub"', false],
            ['MCP List Servers','Lister les serveurs MCP','List all connected MCP servers, their tools, plus known servers you can connect to.','Listez tous les serveurs MCP connectés, leurs outils, ainsi que les serveurs connus auxquels vous pouvez vous connecter.','"What MCP servers are available?"','"Quels serveurs MCP sont disponibles ?"', false],
            ['MCP Call Tool','Appeler un outil MCP','Call any tool on a connected external MCP server with full argument passing.','Appelez n\'importe quel outil sur un serveur MCP externe connecté avec passage complet d\'arguments.','"Use GitHub to create an issue"','"Utilise GitHub pour créer un issue"', false],
        ]
    ],
    [
        'icon' => 'fas fa-cogs', 'color' => 'orange', 'key' => 'alf_cat_workflows', 'count' => 4,
        'tools' => [
            ['Create Workflow','Créer un workflow','Create automation workflows from templates (deploy-notification, health-check, backup, rss-monitor, data-pipeline) or custom definitions.','Créez des workflows à partir de modèles (notification-déploiement, bilan-santé, sauvegarde, moniteur-RSS, pipeline-données) ou définitions personnalisées.','"Create a health check workflow"','"Crée un workflow de vérification de santé"', false],
            ['Execute Workflow','Exécuter un workflow','Run a workflow by ID and get the execution result.','Exécutez un workflow par ID et obtenez le résultat.','"Run the deploy notification workflow"','"Exécute le workflow de notification de déploiement"', false],
            ['List Workflows','Lister les workflows','See all workflows with active/inactive status and last execution time.','Consultez tous les workflows avec statut actif/inactif et dernière exécution.','"What workflows do I have?"','"Quels workflows ai-je ?"', false],
            ['Workflow Status','Statut du workflow','Get execution history and current status of any workflow.','Obtenez l\'historique d\'exécution et le statut actuel de tout workflow.','"Is the backup workflow running?"','"Le workflow de sauvegarde s\'exécute-t-il ?"', false],
        ]
    ],
    [
        'icon' => 'fas fa-heartbeat', 'color' => 'red', 'key' => 'alf_cat_proactive', 'count' => 3,
        'tools' => [
            ['Enable Monitoring','Activer la surveillance','Turn on autonomous monitoring. Alfred watches disk, RAM, CPU, services, and project health — alerts you and optionally auto-fixes issues.','Activez la surveillance autonome. Alfred surveille disque, RAM, CPU, services et santé du projet — vous alerte et corrige optionnellement les problèmes.','"Enable proactive monitoring with auto-fix"','"Active la surveillance proactive avec correction automatique"', false],
            ['Alert History','Historique des alertes','View all monitoring alerts: resource warnings, service outages, security issues — filtered by severity.','Consultez toutes les alertes : avertissements de ressources, pannes de services, problèmes de sécurité — filtrés par sévérité.','"Show me critical alerts from today"','"Montre-moi les alertes critiques d\'aujourd\'hui"', false],
            ['Auto-Fix Config','Config auto-correction','View or update auto-fix settings: which problems Alfred can automatically remediate.','Consultez ou mettez à jour les paramètres de correction automatique : quels problèmes Alfred peut corriger automatiquement.','"What can Alfred auto-fix?"','"Que peut Alfred corriger automatiquement ?"', false],
        ]
    ],

    // ─── NEW: 12 EXPANDED CATEGORIES (69 tools) ───
    [
        'icon' => 'fas fa-shopping-cart', 'color' => 'green', 'key' => 'alf_cat_ecommerce', 'count' => 6,
        'tools' => [
            ['Setup Online Store','Configurer une boutique en ligne','Install and configure WooCommerce, Shopify integration, or custom e-commerce with products, categories, and payment gateways.','Installez et configurez WooCommerce, intégration Shopify, ou e-commerce personnalisé avec produits, catégories et passerelles de paiement.','"Set up an online store with WooCommerce"','"Configure une boutique en ligne avec WooCommerce"', false],
            ['Configure Payments','Configurer les paiements','Set up Stripe, PayPal, Square, or other payment processors. Test and live modes with webhook verification.','Configurez Stripe, PayPal, Square ou d\'autres processeurs de paiement. Modes test et production avec vérification webhook.','"Connect Stripe to my store"','"Connecte Stripe à ma boutique"', false],
            ['Generate Invoices','Générer des factures','Create professional PDF invoices with line items, taxes, and branding. Auto-number and email delivery.','Créez des factures PDF professionnelles avec postes, taxes et image de marque. Numérotation automatique et envoi par courriel.','"Generate an invoice for order #1234"','"Génère une facture pour la commande #1234"', false],
            ['Setup Shipping','Configurer la livraison','Configure shipping zones, rates, free shipping thresholds, and carrier integrations (USPS, FedEx, Canada Post).','Configurez les zones de livraison, tarifs, seuils de livraison gratuite et intégrations transporteurs (USPS, FedEx, Postes Canada).','"Set up flat rate shipping for Canada"','"Configure la livraison forfaitaire pour le Canada"', false],
            ['Optimize Checkout','Optimiser le paiement','Streamline checkout flow: guest checkout, express pay, cart abandonment emails, upsell suggestions.','Optimisez le flux de paiement : commande invité, paiement express, courriels de panier abandonné, suggestions de vente incitative.','"Enable guest checkout and cart recovery emails"','"Active le paiement invité et les courriels de récupération de panier"', false],
            ['Product Catalog','Catalogue de produits','Bulk import/export products, manage inventory levels, set sale prices, and organize product categories.','Importez/exportez des produits en masse, gérez les niveaux de stock, définissez les prix soldés et organisez les catégories.','"Import 50 products from this CSV"','"Importe 50 produits depuis ce CSV"', false],
        ]
    ],
    [
        'icon' => 'fas fa-search', 'color' => 'teal', 'key' => 'alf_cat_seo', 'count' => 6,
        'tools' => [
            ['SEO Audit','Audit SEO','Full on-page SEO analysis: meta tags, headings, keyword density, internal links, image alt text, page speed, mobile-friendliness.','Analyse SEO complète de la page : balises meta, titres, densité de mots-clés, liens internes, texte alt des images, vitesse, compatibilité mobile.','"Run an SEO audit on my homepage"','"Lance un audit SEO sur ma page d\'accueil"', false],
            ['Generate Sitemap','Générer un sitemap','Create or regenerate XML sitemaps with proper priorities, change frequencies, and submit to Google Search Console.','Créez ou régénérez des sitemaps XML avec priorités, fréquences de modification et soumission à Google Search Console.','"Generate a sitemap for my site"','"Génère un sitemap pour mon site"', false],
            ['Manage Robots.txt','Gérer robots.txt','Create, edit, or audit your robots.txt file. Block crawlers from admin pages, allow indexing of key content.','Créez, modifiez ou auditez votre fichier robots.txt. Bloquez les robots des pages admin, autorisez l\'indexation du contenu clé.','"Update my robots.txt to block /wp-admin"','"Mets à jour mon robots.txt pour bloquer /wp-admin"', false],
            ['Setup Analytics','Configurer les analytiques','Install Google Analytics, Plausible, or Matomo tracking. Configure goals, events, and conversion tracking.','Installez le suivi Google Analytics, Plausible ou Matomo. Configurez objectifs, événements et suivi de conversion.','"Add Google Analytics to my site"','"Ajoute Google Analytics à mon site"', false],
            ['Social Cards','Cartes sociales','Generate and configure Open Graph and Twitter Card meta tags. Preview how your pages appear when shared on social media.','Générez et configurez les balises Open Graph et Twitter Card. Prévisualisez l\'apparence de vos pages sur les réseaux sociaux.','"Set up social sharing cards for my blog posts"','"Configure les cartes de partage social pour mes articles"', false],
            ['Keyword Research','Recherche de mots-clés','Analyze keyword opportunities, search volume estimates, competitor keyword gaps, and content suggestions for ranking.','Analysez les opportunités de mots-clés, estimations de volume de recherche, lacunes de mots-clés concurrentiels et suggestions de contenu.','"Find keyword opportunities for my bakery site"','"Trouve des opportunités de mots-clés pour mon site de boulangerie"', false],
        ]
    ],
    [
        'icon' => 'fas fa-server', 'color' => 'cyan', 'key' => 'alf_cat_devops', 'count' => 6,
        'tools' => [
            ['Setup CI/CD','Configurer CI/CD','Create GitHub Actions, GitLab CI, or custom deployment pipelines. Auto-deploy on push, run tests, notify on failure.','Créez des pipelines GitHub Actions, GitLab CI ou déploiement personnalisé. Déploiement automatique au push, exécution de tests, notification en cas d\'échec.','"Set up auto-deploy from GitHub on push"','"Configure le déploiement automatique depuis GitHub au push"', false],
            ['Create Staging','Créer un staging','Clone your production site to a staging environment. Test changes safely before going live with one-click promotion.','Clonez votre site de production vers un environnement de staging. Testez en toute sécurité avant la mise en production.','"Create a staging copy of my site"','"Crée une copie de staging de mon site"', false],
            ['Docker Manage','Gérer Docker','Build, run, and manage Docker containers. Compose files, volume management, container health checks.','Construisez, exécutez et gérez des conteneurs Docker. Fichiers compose, gestion des volumes, vérifications de santé.','"Start the Docker containers from docker-compose.yml"','"Démarre les conteneurs Docker depuis docker-compose.yml"', false],
            ['Run Tests','Exécuter les tests','Run unit tests, integration tests, and end-to-end tests. PHPUnit, Jest, Mocha, pytest — with result summaries.','Exécutez des tests unitaires, d\'intégration et de bout en bout. PHPUnit, Jest, Mocha, pytest — avec résumés des résultats.','"Run all tests and show failures"','"Exécute tous les tests et montre les échecs"', false],
            ['Performance Benchmark','Benchmark de performance','Run page load benchmarks, Core Web Vitals analysis, TTFB measurement, and Lighthouse-style scoring.','Exécutez des benchmarks de chargement de page, analyse Core Web Vitals, mesure TTFB et score de type Lighthouse.','"Benchmark my site performance"','"Fais un benchmark de performance de mon site"', false],
            ['Manage Webhooks','Gérer les webhooks','Create, test, and manage webhooks for deployment triggers, form submissions, payment notifications, and integrations.','Créez, testez et gérez des webhooks pour les déclencheurs de déploiement, soumissions de formulaire, notifications de paiement.','"Set up a webhook for Stripe payment events"','"Configure un webhook pour les événements de paiement Stripe"', false],
        ]
    ],
    [
        'icon' => 'fas fa-palette', 'color' => 'pink', 'key' => 'alf_cat_design', 'count' => 6,
        'tools' => [
            ['Generate Logo','Générer un logo','AI-powered logo generation with multiple style options. SVG and PNG output with transparent backgrounds.','Génération de logo par IA avec plusieurs options de style. Sortie SVG et PNG avec fonds transparents.','"Create a modern logo for my tech startup"','"Crée un logo moderne pour ma startup tech"', false],
            ['Create Favicon','Créer un favicon','Generate favicons in all required sizes (16x16 to 512x512), Apple touch icons, and web manifest icons from any image or prompt.','Générez des favicons dans toutes les tailles requises (16x16 à 512x512), icônes Apple touch et icônes manifest web.','"Create a favicon from my logo"','"Crée un favicon à partir de mon logo"', false],
            ['Color Palette','Palette de couleurs','Generate harmonious color palettes from a brand color, image, or mood. CSS variables, Tailwind config, and accessibility contrast checks.','Générez des palettes de couleurs harmonieuses à partir d\'une couleur de marque, image ou ambiance. Variables CSS, config Tailwind et vérifications de contraste.','"Generate a warm color palette for my restaurant site"','"Génère une palette de couleurs chaudes pour mon site de restaurant"', false],
            ['Build Landing Page','Créer une page d\'atterrissage','Generate complete landing pages with hero sections, features, testimonials, CTA, and responsive design. HTML/CSS or WordPress.','Générez des pages d\'atterrissage complètes avec sections héros, fonctionnalités, témoignages, CTA et design responsive.','"Build a landing page for my SaaS product"','"Crée une page d\'atterrissage pour mon produit SaaS"', false],
            ['Optimize Images','Optimiser les images','Batch compress, resize, and convert images to WebP/AVIF. Lazy loading setup and responsive image srcsets.','Compressez, redimensionnez et convertissez les images en WebP/AVIF par lot. Configuration du chargement différé et srcsets responsives.','"Optimize all images on my site for speed"','"Optimise toutes les images de mon site pour la vitesse"', false],
            ['CSS Theme Builder','Constructeur de thème CSS','Generate complete CSS themes with custom properties, dark/light modes, typography scales, and component styles from a design prompt.','Générez des thèmes CSS complets avec propriétés personnalisées, modes sombre/clair, échelles typographiques et styles de composants.','"Create a dark theme for my dashboard"','"Crée un thème sombre pour mon tableau de bord"', false],
        ]
    ],
    [
        'icon' => 'fas fa-user-shield', 'color' => 'red', 'key' => 'alf_cat_auth', 'count' => 5,
        'tools' => [
            ['Setup Auth','Configurer l\'authentification','Implement user authentication — login, registration, password reset, email verification. PHP sessions, JWT, or OAuth flows.','Implémentez l\'authentification utilisateur — connexion, inscription, réinitialisation de mot de passe, vérification par courriel.','"Set up user login and registration"','"Configure la connexion et l\'inscription utilisateur"', false],
            ['Create User Tables','Créer les tables utilisateurs','Generate database schema for user management: users, roles, permissions, sessions, password resets, and audit logs.','Générez le schéma de base de données pour la gestion des utilisateurs : utilisateurs, rôles, permissions, sessions, réinitialisations.','"Create user and roles tables in my database"','"Crée les tables utilisateurs et rôles dans ma base de données"', false],
            ['Manage API Keys','Gérer les clés API','Generate, rotate, and revoke API keys. Rate limiting configuration, usage tracking, and key scoping.','Générez, faites tourner et révoquez les clés API. Configuration de limitation de débit, suivi d\'utilisation et portée des clés.','"Generate an API key for the mobile app"','"Génère une clé API pour l\'application mobile"', false],
            ['Setup OAuth','Configurer OAuth','Configure OAuth2 social login — Google, GitHub, Facebook, Apple. Redirect URIs, client secrets, and token handling.','Configurez la connexion sociale OAuth2 — Google, GitHub, Facebook, Apple. URIs de redirection, secrets client et gestion de jetons.','"Add Google and GitHub login to my app"','"Ajoute la connexion Google et GitHub à mon app"', false],
            ['Enable 2FA','Activer le 2FA','Set up two-factor authentication with TOTP (Google Authenticator), SMS codes, or email verification for extra security.','Configurez l\'authentification à deux facteurs avec TOTP (Google Authenticator), codes SMS ou vérification par courriel.','"Add two-factor authentication to admin accounts"','"Ajoute l\'authentification à deux facteurs aux comptes admin"', false],
        ]
    ],
    [
        'icon' => 'fas fa-exchange-alt', 'color' => 'blue', 'key' => 'alf_cat_data', 'count' => 6,
        'tools' => [
            ['Import CSV','Importer un CSV','Parse and import CSV/TSV files into database tables. Column mapping, data validation, duplicate detection.','Analysez et importez des fichiers CSV/TSV dans des tables de base de données. Mapping de colonnes, validation, détection de doublons.','"Import customers.csv into the users table"','"Importe customers.csv dans la table utilisateurs"', false],
            ['Export Data','Exporter des données','Export database tables or query results to CSV, JSON, XML, or Excel. Filtered exports with date ranges.','Exportez des tables de base de données ou résultats de requête en CSV, JSON, XML ou Excel. Exports filtrés par plage de dates.','"Export all orders from last month as CSV"','"Exporte toutes les commandes du mois dernier en CSV"', false],
            ['API Connections','Connexions API','Connect to external REST APIs — configure endpoints, headers, authentication, and data mapping for integrations.','Connectez-vous à des API REST externes — configurez les endpoints, en-têtes, authentification et mapping de données.','"Connect to the Mailchimp API"','"Connecte-toi à l\'API Mailchimp"', false],
            ['Configure CORS','Configurer CORS','Set up Cross-Origin Resource Sharing rules. Allow specific domains, methods, and headers for API access.','Configurez les règles de partage de ressources inter-origines. Autorisez des domaines, méthodes et en-têtes spécifiques.','"Allow my frontend at app.mysite.com to call my API"','"Autorise mon frontend à app.monsite.com à appeler mon API"', false],
            ['Build REST API','Construire une API REST','Scaffold RESTful API endpoints with CRUD operations, input validation, authentication middleware, and documentation.','Créez des endpoints API RESTful avec opérations CRUD, validation d\'entrée, middleware d\'authentification et documentation.','"Create a REST API for products"','"Crée une API REST pour les produits"', false],
            ['Migrate Site','Migrer le site','Full site migration between servers or platforms. Database export/import, file transfer, DNS update, and SSL provisioning.','Migration complète de site entre serveurs ou plateformes. Export/import de base de données, transfert de fichiers, mise à jour DNS et SSL.','"Migrate my site from the old server"','"Migre mon site depuis l\'ancien serveur"', false],
        ]
    ],
    [
        'icon' => 'fas fa-pen-fancy', 'color' => 'indigo', 'key' => 'alf_cat_content', 'count' => 6,
        'tools' => [
            ['Write Blog Post','Écrire un article de blogue','AI-generated blog posts with SEO optimization, meta descriptions, featured image suggestions, and WordPress publishing.','Articles de blogue générés par IA avec optimisation SEO, méta-descriptions, suggestions d\'images et publication WordPress.','"Write a blog post about sustainable baking"','"Écris un article de blogue sur la boulangerie durable"', false],
            ['Product Descriptions','Descriptions de produits','Generate compelling product descriptions with features, benefits, specs, and SEO keywords. Bulk generation for catalogs.','Générez des descriptions de produits captivantes avec fonctionnalités, avantages, spécifications et mots-clés SEO.','"Write descriptions for my 10 new products"','"Écris des descriptions pour mes 10 nouveaux produits"', false],
            ['Translate Content','Traduire le contenu','Translate pages, posts, or entire sites between languages. Maintains formatting, links, and SEO structure.','Traduisez des pages, articles ou sites entiers entre les langues. Conserve le formatage, les liens et la structure SEO.','"Translate my homepage to French"','"Traduis ma page d\'accueil en français"', false],
            ['Legal Pages','Pages légales','Generate privacy policy, terms of service, cookie policy, disclaimer, and refund policy pages customized to your business.','Générez des pages de politique de confidentialité, conditions d\'utilisation, politique de cookies, avertissement et politique de remboursement.','"Create a privacy policy for my Canadian e-commerce site"','"Crée une politique de confidentialité pour mon site e-commerce canadien"', false],
            ['Generate README','Générer un README','Create comprehensive README.md files with installation instructions, API documentation, badges, and usage examples.','Créez des fichiers README.md complets avec instructions d\'installation, documentation API, badges et exemples d\'utilisation.','"Generate a README for this project"','"Génère un README pour ce projet"', false],
            ['Social Media Posts','Publications réseaux sociaux','Generate platform-specific social media content with hashtags, emojis, calls-to-action, and image suggestions for each post.','Générez du contenu pour les réseaux sociaux avec hashtags, emojis, appels à l\'action et suggestions d\'images.','"Write 5 Instagram posts promoting my new collection"','"Écris 5 publications Instagram pour ma nouvelle collection"', false],
        ]
    ],
    [
        'icon' => 'fas fa-universal-access', 'color' => 'green', 'key' => 'alf_cat_a11y', 'count' => 5,
        'tools' => [
            ['Accessibility Audit','Audit d\'accessibilité','WCAG 2.1 compliance audit: color contrast, alt text, ARIA labels, keyboard navigation, screen reader compatibility, semantic HTML.','Audit de conformité WCAG 2.1 : contraste des couleurs, texte alt, étiquettes ARIA, navigation clavier, compatibilité lecteur d\'écran.','"Run an accessibility audit on my site"','"Lance un audit d\'accessibilité sur mon site"', false],
            ['Cookie Consent','Consentement aux cookies','Implement GDPR/CCPA-compliant cookie consent banners with preference management, cookie categorization, and consent logging.','Implémentez des bannières de consentement aux cookies conformes RGPD/CCPA avec gestion des préférences et journalisation.','"Add a cookie consent banner to my site"','"Ajoute une bannière de consentement aux cookies"', false],
            ['GDPR Compliance','Conformité RGPD','Audit and implement GDPR requirements: data processing register, right to deletion, data export, consent tracking, DPO contact.','Auditez et implémentez les exigences RGPD : registre de traitement, droit à la suppression, export de données, suivi du consentement.','"Make my site GDPR compliant"','"Rends mon site conforme au RGPD"', false],
            ['ADA Compliance','Conformité ADA','Check and fix ADA/Section 508 compliance issues: form labels, skip navigation, focus indicators, color contrast, text sizing.','Vérifiez et corrigez les problèmes de conformité ADA/Section 508 : étiquettes de formulaire, navigation par saut, indicateurs de focus.','"Fix ADA compliance issues on my contact page"','"Corrige les problèmes de conformité ADA sur ma page contact"', false],
            ['Screen Reader Test','Test lecteur d\'écran','Simulate screen reader navigation of your pages. Identify missing labels, heading hierarchy issues, and focus traps.','Simulez la navigation par lecteur d\'écran de vos pages. Identifiez les étiquettes manquantes et problèmes de hiérarchie.','"Test how my checkout page works with a screen reader"','"Teste comment ma page de paiement fonctionne avec un lecteur d\'écran"', false],
        ]
    ],
    [
        'icon' => 'fas fa-heart', 'color' => 'pink', 'key' => 'alf_cat_custsuccess', 'count' => 5,
        'tools' => [
            ['Customer Journey','Parcours client','Map and analyze customer journey touchpoints: acquisition, onboarding, engagement, retention, and expansion metrics.','Cartographiez et analysez les points de contact du parcours client : acquisition, onboarding, engagement, rétention.','"Map the customer journey for my SaaS product"','"Cartographie le parcours client de mon produit SaaS"', false],
            ['Churn Risk Analysis','Analyse de risque d\'attrition','Identify customers at risk of churning based on usage patterns, support tickets, payment history, and engagement signals.','Identifiez les clients à risque d\'attrition basé sur les schémas d\'utilisation, billets de support, historique de paiement.','"Which customers are at risk of churning?"','"Quels clients sont à risque d\'attrition ?"', false],
            ['Upsell Suggestions','Suggestions de vente incitative','AI-powered product and plan upgrade recommendations based on customer usage, needs, and behavior patterns.','Recommandations de produits et mises à niveau propulsées par IA basées sur l\'utilisation et les besoins des clients.','"Suggest upsell opportunities for my top customers"','"Suggère des opportunités de vente incitative pour mes meilleurs clients"', false],
            ['NPS Survey','Sondage NPS','Create and deploy Net Promoter Score surveys with automated follow-up, response tracking, and trend analysis.','Créez et déployez des sondages Net Promoter Score avec suivi automatique, suivi des réponses et analyse de tendances.','"Set up an NPS survey for my customers"','"Configure un sondage NPS pour mes clients"', false],
            ['Customer Health Score','Score de santé client','Calculate composite customer health scores combining product usage, support interactions, billing status, and engagement metrics.','Calculez des scores de santé client composites combinant utilisation, interactions support, statut de facturation et engagement.','"Show me customer health scores for this quarter"','"Montre-moi les scores de santé client pour ce trimestre"', false],
        ]
    ],
    [
        'icon' => 'fas fa-microscope', 'color' => 'purple', 'key' => 'alf_cat_projintel', 'count' => 5,
        'tools' => [
            ['Detect Framework','Détecter le framework','Auto-detect project framework (Laravel, Next.js, WordPress, Django, Rails, etc.), language versions, and architecture patterns.','Détectez automatiquement le framework (Laravel, Next.js, WordPress, Django, Rails, etc.), versions et architecture.','"What framework is this project using?"','"Quel framework ce projet utilise-t-il ?"', false],
            ['Health Report','Rapport de santé','Comprehensive project health: code quality, dependency freshness, test coverage, security vulnerabilities, performance score.','Santé complète du projet : qualité du code, fraîcheur des dépendances, couverture de tests, vulnérabilités, performance.','"Generate a health report for this project"','"Génère un rapport de santé pour ce projet"', false],
            ['Complexity Estimate','Estimation de complexité','Analyze a feature request or task description and estimate implementation complexity, time, and required skills.','Analysez une demande de fonctionnalité et estimez la complexité d\'implémentation, le temps et les compétences requises.','"How complex would it be to add multi-tenancy?"','"Quelle serait la complexité d\'ajouter le multi-tenant ?"', false],
            ['Auto Documentation','Documentation automatique','Generate API documentation, code comments, JSDoc/PHPDoc annotations, and architecture decision records from your codebase.','Générez de la documentation API, commentaires de code, annotations JSDoc/PHPDoc et registres de décisions d\'architecture.','"Document all my API endpoints"','"Documente tous mes endpoints API"', false],
            ['Tech Debt Tracker','Suivi de dette technique','Identify and prioritize technical debt: deprecated dependencies, code smells, missing tests, TODO comments, and legacy patterns.','Identifiez et priorisez la dette technique : dépendances obsolètes, code smells, tests manquants, TODO et patterns legacy.','"What tech debt should I prioritize?"','"Quelle dette technique devrais-je prioriser ?"', false],
        ]
    ],
    [
        'icon' => 'fas fa-calendar-check', 'color' => 'orange', 'key' => 'alf_cat_scheduling', 'count' => 5,
        'tools' => [
            ['Uptime Monitor','Surveillance de disponibilité','Monitor website uptime from multiple global locations. HTTP, HTTPS, TCP, and ping checks with configurable intervals and alerts.','Surveillez la disponibilité de votre site depuis plusieurs emplacements. Vérifications HTTP, HTTPS, TCP et ping avec alertes configurables.','"Monitor my site uptime every 5 minutes"','"Surveille la disponibilité de mon site toutes les 5 minutes"', false],
            ['Maintenance Window','Fenêtre de maintenance','Schedule maintenance windows with automatic "under maintenance" page, notification emails, and countdown timers.','Planifiez des fenêtres de maintenance avec page automatique, courriels de notification et comptes à rebours.','"Schedule maintenance for Saturday 2-4 AM"','"Planifie une maintenance pour samedi 2 h-4 h"', false],
            ['Auto-Backup Schedule','Planification de sauvegardes auto','Configure automated backup schedules for files, databases, and configurations. Retention policies and off-site storage.','Configurez des planifications de sauvegarde automatique pour fichiers, bases de données et configurations. Politiques de rétention.','"Set up daily backups with 30-day retention"','"Configure des sauvegardes quotidiennes avec rétention de 30 jours"', false],
            ['Dead Link Scanner','Détecteur de liens morts','Crawl your entire site for broken links (404s), redirect chains, and orphaned pages. Exportable report with fix suggestions.','Parcourez votre site entier pour détecter les liens cassés (404), chaînes de redirections et pages orphelines.','"Scan my site for broken links"','"Scanne mon site pour les liens cassés"', false],
            ['Report Scheduling','Planification de rapports','Schedule automated reports — traffic, performance, security, SEO — delivered via email, WhatsApp, or saved to your workspace.','Planifiez des rapports automatiques — trafic, performance, sécurité, SEO — livrés par courriel, WhatsApp ou dans votre espace.','"Send me a weekly traffic report every Monday"','"Envoie-moi un rapport de trafic chaque lundi"', false],
        ]
    ],
    [
        'icon' => 'fas fa-comments', 'color' => 'cyan', 'key' => 'alf_cat_comms', 'count' => 8,
        'tools' => [
            ['Send SMS','Envoyer un SMS','Send SMS messages via Twilio, Vonage, or other providers. Single or bulk messaging with template support.','Envoyez des SMS via Twilio, Vonage ou d\'autres fournisseurs. Messages individuels ou en masse avec modèles.','"Send an SMS order confirmation to the customer"','"Envoie un SMS de confirmation de commande au client"', false],
            ['Push Notifications','Notifications push','Set up web push notifications with subscription management, segmentation, and scheduled delivery.','Configurez les notifications push web avec gestion des abonnements, segmentation et envoi planifié.','"Send a push notification about our new feature"','"Envoie une notification push sur notre nouvelle fonctionnalité"', false],
            ['Build Contact Form','Créer un formulaire de contact','Generate HTML/PHP contact forms with validation, spam protection (reCAPTCHA/honeypot), email delivery, and database storage.','Générez des formulaires de contact HTML/PHP avec validation, protection anti-spam, envoi par courriel et stockage en base.','"Create a contact form with spam protection"','"Crée un formulaire de contact avec protection anti-spam"', false],
            ['Setup Live Chat','Configurer le chat en direct','Install live chat widgets (Crisp, Tawk.to, custom) with auto-responses, office hours, and chat-to-ticket escalation.','Installez des widgets de chat en direct (Crisp, Tawk.to, personnalisé) avec réponses automatiques et heures de bureau.','"Add a live chat widget to my site"','"Ajoute un widget de chat en direct à mon site"', false],
            ['Create Newsletter','Créer une infolettre','Design and send HTML email newsletters with subscriber management, templates, personalization, and delivery tracking.','Concevez et envoyez des infolettres HTML avec gestion des abonnés, modèles, personnalisation et suivi de livraison.','"Create a monthly newsletter for my subscribers"','"Crée une infolettre mensuelle pour mes abonnés"', false],
            ['Email Campaign','Campagne courriel','Build multi-step email marketing campaigns with triggers, A/B testing, open tracking, and automated follow-ups.','Créez des campagnes de marketing par courriel multi-étapes avec déclencheurs, tests A/B, suivi d\'ouverture.','"Set up a welcome email sequence for new signups"','"Configure une séquence de courriels de bienvenue"', false],
            ['Notification Rules','Règles de notification','Configure event-driven notifications: new orders, form submissions, errors, security alerts — routed to email, SMS, or Slack.','Configurez des notifications basées sur les événements : nouvelles commandes, soumissions de formulaire, erreurs, alertes de sécurité.','"Notify me on Slack when a new order comes in"','"Notifie-moi sur Slack quand une nouvelle commande arrive"', false],
            ['Webhook Alerts','Alertes webhook','Create outbound webhook notifications triggered by site events. JSON payloads to any URL with retry logic and logging.','Créez des notifications webhook sortantes déclenchées par les événements du site. Charges JSON vers toute URL avec logique de réessai.','"Send a webhook to Zapier when someone submits the form"','"Envoie un webhook à Zapier quand quelqu\'un soumet le formulaire"', false],
        ]
    ],

    // ─── VOICE STEERING ───
    [
        'icon' => 'fas fa-bullseye', 'color' => 'yellow', 'key' => 'alf_cat_steering', 'count' => 5,
        'tools' => [
            ['Queue Command','Empiler une commande','Queue a command while Alfred is working. It will execute next.','Empilez une commande pendant qu\'Alfred travaille. Elle s\'exécutera ensuite.','"While you do that, also check my SSL"','"Pendant que tu fais ça, vérifie aussi mon SSL"', false],
            ['Abort Task','Annuler une tâche','Stop the current task immediately. Alfred acknowledges and waits for your next instruction.','Arrêtez la tâche en cours immédiatement. Alfred confirme et attend votre prochaine instruction.','"Stop — cancel that"','"Arrête — annule ça"', false],
            ['Redirect Alfred','Rediriger Alfred','Change what Alfred is doing mid-execution. Interrupt and pivot to a new task seamlessly.','Changez ce qu\'Alfred fait en pleine exécution. Interrompez et pivotez vers une nouvelle tâche.','"Actually, do this instead"','"En fait, fais plutôt ça"', false],
            ['Priority Override','Priorité de remplacement','Mark a command as urgent — Alfred drops the current queue and handles it immediately.','Marquez une commande comme urgente — Alfred abandonne la file et la traite immédiatement.','"This is urgent — fix the 500 error now"','"C\'est urgent — corrige l\'erreur 500 maintenant"', false],
            ['View Queue','Voir la file','See what commands are queued, in progress, or completed in the current session.','Consultez les commandes en file d\'attente, en cours ou terminées dans la session actuelle.','"What\'s in my command queue?"','"Qu\'y a-t-il dans ma file de commandes ?"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // 14 AI ENGINE CATEGORIES (133 engine-specific tools)
    // ═══════════════════════════════════════════════════════════

    // ─── CONDUIT ENGINE — API Hub ───
    [
        'icon' => 'fas fa-plug', 'color' => 'cyan', 'key' => 'alf_cat_conduit', 'count' => 13,
        'tools' => [
            ['Register API','Enregistrer une API','Register an external API endpoint for use in workflows and automations.','Enregistrez un point de terminaison d\'API externe pour l\'utiliser dans les flux et automatisations.','"Register the Stripe API endpoint"','"Enregistre le point de terminaison de l\'API Stripe"', false],
            ['List APIs','Lister les API','View all registered API integrations and their status.','Consultez toutes les intégrations API enregistrées et leur état.','"Show me my registered APIs"','"Montre-moi mes API enregistrées"', false],
            ['Call API','Appeler une API','Execute a call to any registered API with parameters.','Exécutez un appel à toute API enregistrée avec des paramètres.','"Call the weather API for Toronto"','"Appelle l\'API météo pour Toronto"', false],
            ['Remove API','Supprimer une API','Unregister an API endpoint from the system.','Désenregistrez un point de terminaison API du système.','"Remove the old payment API"','"Supprime l\'ancienne API de paiement"', false],
            ['Create Webhook','Créer un webhook','Set up an incoming webhook to trigger automations.','Configurez un webhook entrant pour déclencher des automatisations.','"Create a webhook for Stripe payments"','"Crée un webhook pour les paiements Stripe"', false],
            ['List Webhooks','Lister les webhooks','View all configured webhooks and their trigger history.','Consultez tous les webhooks configurés et leur historique de déclenchement.','"Show all my webhooks"','"Montre tous mes webhooks"', false],
            ['Test Webhook','Tester un webhook','Send a test payload to verify webhook configuration.','Envoyez un payload de test pour vérifier la configuration du webhook.','"Test the deploy webhook"','"Teste le webhook de déploiement"', false],
            ['Delete Webhook','Supprimer un webhook','Remove a webhook endpoint.','Supprimez un point de terminaison webhook.','"Delete the old Slack webhook"','"Supprime l\'ancien webhook Slack"', false],
            ['Create Pipeline','Créer un pipeline','Build a multi-step API pipeline that chains calls together.','Construisez un pipeline API multi-étapes qui enchaîne les appels.','"Create a pipeline: fetch data, transform, and post to Slack"','"Crée un pipeline : récupérer les données, transformer et poster sur Slack"', false],
            ['List Pipelines','Lister les pipelines','View all API pipelines and their execution status.','Consultez tous les pipelines API et leur état d\'exécution.','"Show my API pipelines"','"Montre mes pipelines API"', false],
            ['Run Pipeline','Exécuter un pipeline','Execute a previously defined API pipeline.','Exécutez un pipeline API précédemment défini.','"Run the daily-report pipeline"','"Exécute le pipeline rapport-quotidien"', false],
            ['Delete Pipeline','Supprimer un pipeline','Remove an API pipeline definition.','Supprimez une définition de pipeline API.','"Delete the old sync pipeline"','"Supprime l\'ancien pipeline de synchronisation"', false],
            ['View Conduit Logs','Voir les logs Conduit','View API call logs, errors, and response times.','Consultez les logs d\'appels API, les erreurs et les temps de réponse.','"Show Conduit logs from today"','"Montre les logs Conduit d\'aujourd\'hui"', false],
        ]
    ],

    // ─── ARCHITECT ENGINE — Infrastructure ───
    [
        'icon' => 'fas fa-drafting-compass', 'color' => 'blue', 'key' => 'alf_cat_architect', 'count' => 9,
        'tools' => [
            ['List Environments','Lister les environnements','View all environments (dev, staging, production) and their status.','Consultez tous les environnements (dev, staging, production) et leur état.','"Show all my environments"','"Montre tous mes environnements"', false],
            ['Get Environment','Obtenir un environnement','Get detailed configuration for a specific environment.','Obtenez la configuration détaillée d\'un environnement spécifique.','"Get staging environment details"','"Obtiens les détails de l\'environnement staging"', false],
            ['Set Environment','Définir un environnement','Create or update an environment configuration.','Créez ou mettez à jour une configuration d\'environnement.','"Set up a new staging environment"','"Configure un nouvel environnement staging"', false],
            ['Scaffold Project','Échafauder un projet','Generate a complete project scaffold with best practices.','Générez un échafaudage de projet complet avec les meilleures pratiques.','"Scaffold a React + Node project"','"Échafaude un projet React + Node"', false],
            ['Create Deployment','Créer un déploiement','Define a deployment configuration with rollback strategy.','Définissez une configuration de déploiement avec stratégie de rollback.','"Create a blue-green deployment for production"','"Crée un déploiement blue-green pour la production"', false],
            ['List Deployments','Lister les déploiements','View all deployments, their status, and rollback history.','Consultez tous les déploiements, leur état et l\'historique de rollback.','"Show recent deployments"','"Montre les déploiements récents"', false],
            ['Run Deployment','Exécuter un déploiement','Execute a deployment to push changes to an environment.','Exécutez un déploiement pour pousser les changements vers un environnement.','"Deploy v2.1 to production"','"Déploie v2.1 en production"', false],
            ['Analyze Architecture','Analyser l\'architecture','Analyze project architecture for patterns, anti-patterns, and improvements.','Analysez l\'architecture du projet pour les patterns, anti-patterns et améliorations.','"Analyze my app architecture"','"Analyse l\'architecture de mon application"', false],
            ['View Resources','Voir les ressources','Monitor infrastructure resource usage (CPU, memory, disk, bandwidth).','Surveillez l\'utilisation des ressources d\'infrastructure (CPU, mémoire, disque, bande passante).','"Show my resource usage"','"Montre mon utilisation des ressources"', false],
        ]
    ],

    // ─── SENTINEL ENGINE — Security Ops ───
    [
        'icon' => 'fas fa-shield-virus', 'color' => 'red', 'key' => 'alf_cat_sentinel', 'count' => 10,
        'tools' => [
            ['Create Security Baseline','Créer une ligne de sécurité','Establish a security baseline snapshot of your system.','Établissez un snapshot de référence de sécurité de votre système.','"Create a security baseline for my server"','"Crée une ligne de base de sécurité pour mon serveur"', false],
            ['Check Integrity','Vérifier l\'intégrité','Compare current system state against security baseline.','Comparez l\'état actuel du système à la référence de sécurité.','"Check file integrity against baseline"','"Vérifie l\'intégrité des fichiers par rapport à la référence"', false],
            ['Analyze Access Logs','Analyser les logs d\'accès','Deep-analyze access logs for suspicious patterns and threats.','Analysez en profondeur les logs d\'accès pour les patterns suspects et menaces.','"Analyze access logs for the last 24 hours"','"Analyse les logs d\'accès des dernières 24 heures"', false],
            ['Vulnerability Scan','Scan de vulnérabilités','Run a comprehensive vulnerability scan on your infrastructure.','Exécutez un scan de vulnérabilités complet sur votre infrastructure.','"Scan my site for vulnerabilities"','"Scanne mon site pour les vulnérabilités"', false],
            ['Check IP Reputation','Vérifier la réputation IP','Check if an IP address is blacklisted or suspicious.','Vérifiez si une adresse IP est sur liste noire ou suspecte.','"Check IP 192.168.1.100"','"Vérifie l\'IP 192.168.1.100"', false],
            ['Log Security Incident','Logger un incident','Log a security incident with severity and affected systems.','Enregistrez un incident de sécurité avec la gravité et les systèmes affectés.','"Log a brute-force attempt on SSH"','"Enregistre une tentative de brute-force sur SSH"', false],
            ['List Incidents','Lister les incidents','View all security incidents, their status, and resolution.','Consultez tous les incidents de sécurité, leur état et leur résolution.','"Show all open security incidents"','"Montre tous les incidents de sécurité ouverts"', false],
            ['Resolve Incident','Résoudre un incident','Mark a security incident as resolved with resolution notes.','Marquez un incident de sécurité comme résolu avec des notes de résolution.','"Resolve incident #42"','"Résous l\'incident #42"', false],
            ['Set Security Policy','Définir une politique','Define or update a security policy rule.','Définissez ou mettez à jour une règle de politique de sécurité.','"Set a rate-limiting policy for API endpoints"','"Définis une politique de limitation de taux pour les endpoints API"', false],
            ['List Policies','Lister les politiques','View all active security policies and their configurations.','Consultez toutes les politiques de sécurité actives et leurs configurations.','"Show all security policies"','"Montre toutes les politiques de sécurité"', false],
        ]
    ],

    // ─── FORGE ENGINE — Code Generation ───
    [
        'icon' => 'fas fa-hammer', 'color' => 'orange', 'key' => 'alf_cat_forge', 'count' => 7,
        'tools' => [
            ['Generate CRUD','Générer CRUD','Auto-generate complete CRUD endpoints (Create, Read, Update, Delete) for any entity.','Générez automatiquement des endpoints CRUD complets pour toute entité.','"Generate CRUD for a products table"','"Génère le CRUD pour une table produits"', false],
            ['Generate Component','Générer un composant','Create a complete UI component with template, styles, and logic.','Créez un composant UI complet avec template, styles et logique.','"Generate a React user-profile component"','"Génère un composant React profil-utilisateur"', false],
            ['Generate Tests','Générer des tests','Auto-generate unit and integration tests for existing code.','Générez automatiquement des tests unitaires et d\'intégration pour le code existant.','"Generate tests for my auth module"','"Génère des tests pour mon module d\'auth"', false],
            ['Analyze Code','Analyser le code','Deep code analysis: complexity, duplication, patterns, and quality metrics.','Analyse de code approfondie : complexité, duplication, patterns et métriques de qualité.','"Analyze code quality of src/"','"Analyse la qualité du code dans src/"', false],
            ['Save Snippet','Enregistrer un snippet','Save a reusable code snippet to your personal library.','Enregistrez un snippet de code réutilisable dans votre bibliothèque personnelle.','"Save this auth middleware as a snippet"','"Enregistre ce middleware d\'auth comme snippet"', false],
            ['List Snippets','Lister les snippets','View all saved code snippets with tags and descriptions.','Consultez tous les snippets de code enregistrés avec tags et descriptions.','"Show my saved snippets"','"Montre mes snippets enregistrés"', false],
            ['Get Snippet','Obtenir un snippet','Retrieve a specific code snippet by name or ID.','Récupérez un snippet de code spécifique par nom ou ID.','"Get the auth-middleware snippet"','"Obtiens le snippet auth-middleware"', false],
        ]
    ],

    // ─── CHRONICLE ENGINE — Audit Trail ───
    [
        'icon' => 'fas fa-scroll', 'color' => 'amber', 'key' => 'alf_cat_chronicle', 'count' => 11,
        'tools' => [
            ['Log Event','Logger un événement','Record a timestamped event in the immutable audit trail.','Enregistrez un événement horodaté dans la piste d\'audit immuable.','"Log a deployment event"','"Enregistre un événement de déploiement"', false],
            ['Query Events','Requêter les événements','Search and filter audit trail events by type, date, or actor.','Recherchez et filtrez les événements de la piste d\'audit par type, date ou acteur.','"Show all events from yesterday"','"Montre tous les événements d\'hier"', false],
            ['Verify Trail Integrity','Vérifier l\'intégrité','Cryptographically verify the integrity of the audit trail.','Vérifiez cryptographiquement l\'intégrité de la piste d\'audit.','"Verify audit trail integrity"','"Vérifie l\'intégrité de la piste d\'audit"', false],
            ['Track Activity','Suivre l\'activité','Enable activity tracking for a specific resource or user.','Activez le suivi d\'activité pour une ressource ou un utilisateur spécifique.','"Track all changes to the database"','"Suis tous les changements de la base de données"', false],
            ['Activity Summary','Résumé d\'activité','Get an AI-powered summary of recent activity and trends.','Obtenez un résumé propulsé par l\'IA des activités récentes et tendances.','"Summarize this week\'s activity"','"Résume l\'activité de cette semaine"', false],
            ['Record Change','Enregistrer un changement','Record a specific change with before/after state.','Enregistrez un changement spécifique avec l\'état avant/après.','"Record the config change"','"Enregistre le changement de configuration"', false],
            ['Change History','Historique des changements','View the full change history for any resource.','Consultez l\'historique complet des changements pour toute ressource.','"Show change history for nginx.conf"','"Montre l\'historique des changements pour nginx.conf"', false],
            ['Start Session','Démarrer une session','Begin an audit session that groups related events.','Démarrez une session d\'audit qui regroupe les événements liés.','"Start an audit session for the migration"','"Démarre une session d\'audit pour la migration"', false],
            ['End Session','Terminer une session','Close an audit session and generate a summary.','Fermez une session d\'audit et générez un résumé.','"End migration audit session"','"Termine la session d\'audit de migration"', false],
            ['List Sessions','Lister les sessions','View all audit sessions with their status and event counts.','Consultez toutes les sessions d\'audit avec leur état et nombre d\'événements.','"Show audit sessions from this month"','"Montre les sessions d\'audit de ce mois"', false],
            ['Compliance Report','Rapport de conformité','Generate a compliance report from audit trail data.','Générez un rapport de conformité à partir des données de la piste d\'audit.','"Generate a SOC2 compliance report"','"Génère un rapport de conformité SOC2"', false],
        ]
    ],

    // ─── NEXUS ENGINE — Knowledge Graph ───
    [
        'icon' => 'fas fa-project-diagram', 'color' => 'purple', 'key' => 'alf_cat_nexus', 'count' => 11,
        'tools' => [
            ['Add Entity','Ajouter une entité','Add a node to the knowledge graph with properties and type.','Ajoutez un nœud au graphe de connaissances avec propriétés et type.','"Add a \'user-service\' entity"','"Ajoute une entité \'user-service\'"', false],
            ['Add Relation','Ajouter une relation','Create a typed relationship between two entities.','Créez une relation typée entre deux entités.','"Link user-service DEPENDS_ON auth-service"','"Lie user-service DÉPEND_DE auth-service"', false],
            ['Remove Entity','Supprimer une entité','Remove an entity and its relationships from the graph.','Supprimez une entité et ses relations du graphe.','"Remove the deprecated-api entity"','"Supprime l\'entité deprecated-api"', false],
            ['Query Graph','Requêter le graphe','Run a query against the knowledge graph.','Exécutez une requête sur le graphe de connaissances.','"Find all services that depend on Redis"','"Trouve tous les services qui dépendent de Redis"', false],
            ['Find Neighbors','Trouver les voisins','Get all directly connected entities for a given node.','Obtenez toutes les entités directement connectées pour un nœud donné.','"Show neighbors of auth-service"','"Montre les voisins de auth-service"', false],
            ['Impact Analysis','Analyse d\'impact','Analyze the blast radius of changing a specific entity.','Analysez le rayon d\'impact de la modification d\'une entité spécifique.','"What\'s impacted if I change the database schema?"','"Quel est l\'impact si je change le schéma de base de données ?"', false],
            ['Discover Dependencies','Découvrir les dépendances','Auto-discover dependencies by scanning code and configs.','Découvrez automatiquement les dépendances en scannant le code et les configs.','"Discover all dependencies in my project"','"Découvre toutes les dépendances dans mon projet"', false],
            ['Graph Statistics','Statistiques du graphe','View knowledge graph statistics: nodes, edges, clusters.','Consultez les statistiques du graphe de connaissances : nœuds, arêtes, clusters.','"Show knowledge graph stats"','"Montre les statistiques du graphe de connaissances"', false],
            ['Add Knowledge','Ajouter des connaissances','Store a piece of knowledge (fact, insight, decision) in the graph.','Stockez une connaissance (fait, insight, décision) dans le graphe.','"Remember: we use PostgreSQL 15 in production"','"Souviens-toi : on utilise PostgreSQL 15 en production"', false],
            ['Search Knowledge','Chercher des connaissances','Semantic search across all stored knowledge.','Recherche sémantique à travers toutes les connaissances stockées.','"Search for everything about our auth system"','"Cherche tout sur notre système d\'auth"', false],
            ['List Knowledge','Lister les connaissances','Browse all stored knowledge entries by category.','Parcourez toutes les entrées de connaissances stockées par catégorie.','"List all stored knowledge"','"Liste toutes les connaissances stockées"', false],
        ]
    ],

    // ─── CORTEX ENGINE — Planning & Reasoning ───
    [
        'icon' => 'fas fa-brain', 'color' => 'pink', 'key' => 'alf_cat_cortex', 'count' => 15,
        'tools' => [
            ['Decompose Task','Décomposer une tâche','Break a complex task into actionable steps with dependencies.','Décomposez une tâche complexe en étapes actionnables avec dépendances.','"Break down: migrate to microservices"','"Décompose : migrer vers les microservices"', false],
            ['Update Step','Mettre à jour une étape','Update the status or details of a plan step.','Mettez à jour le statut ou les détails d\'une étape de plan.','"Mark step 3 as complete"','"Marque l\'étape 3 comme terminée"', false],
            ['Get Plan','Obtenir un plan','Retrieve a full plan with progress indicators.','Récupérez un plan complet avec indicateurs de progression.','"Show the migration plan"','"Montre le plan de migration"', false],
            ['List Plans','Lister les plans','View all active and completed plans.','Consultez tous les plans actifs et terminés.','"Show all my plans"','"Montre tous mes plans"', false],
            ['Set Goal','Définir un objectif','Create a high-level goal with success criteria.','Créez un objectif de haut niveau avec critères de succès.','"Set goal: reduce page load to under 2 seconds"','"Définis un objectif : réduire le chargement à moins de 2 secondes"', false],
            ['Update Goal','Mettre à jour un objectif','Update progress or criteria for an existing goal.','Mettez à jour la progression ou les critères d\'un objectif existant.','"Update the performance goal progress"','"Mets à jour la progression de l\'objectif de performance"', false],
            ['List Goals','Lister les objectifs','View all goals with completion percentages.','Consultez tous les objectifs avec pourcentages de complétion.','"Show my goals"','"Montre mes objectifs"', false],
            ['Analyze Decision','Analyser une décision','AI-powered decision analysis with pros, cons, and recommendation.','Analyse de décision propulsée par l\'IA avec avantages, inconvénients et recommandation.','"Should we use GraphQL or REST?"','"Devrait-on utiliser GraphQL ou REST ?"', false],
            ['Record Decision','Enregistrer une décision','Record an architectural or technical decision with rationale.','Enregistrez une décision architecturale ou technique avec la justification.','"Record: we chose PostgreSQL over MySQL"','"Enregistre : nous avons choisi PostgreSQL plutôt que MySQL"', false],
            ['List Decisions','Lister les décisions','View all recorded decisions with their context.','Consultez toutes les décisions enregistrées avec leur contexte.','"Show all technical decisions"','"Montre toutes les décisions techniques"', false],
            ['Add Reasoning','Ajouter un raisonnement','Document a chain of reasoning for future reference.','Documentez une chaîne de raisonnement pour référence future.','"Document why we chose this approach"','"Documente pourquoi nous avons choisi cette approche"', false],
            ['Get Reasoning','Obtenir un raisonnement','Retrieve reasoning documentation for a decision or approach.','Récupérez la documentation de raisonnement pour une décision ou approche.','"Get the reasoning behind the cache architecture"','"Obtiens le raisonnement derrière l\'architecture de cache"', false],
            ['List Reasoning','Lister les raisonnements','Browse all documented reasoning entries.','Parcourez toutes les entrées de raisonnement documentées.','"Show all reasoning docs"','"Montre tous les docs de raisonnement"', false],
            ['Score Priority','Évaluer la priorité','AI-scored priority ranking for tasks and features.','Classement de priorité évalué par l\'IA pour les tâches et fonctionnalités.','"Prioritize my backlog items"','"Priorise mes éléments de backlog"', false],
            ['Get Context','Obtenir le contexte','Build rich context about a topic from all available knowledge.','Construisez un contexte riche sur un sujet à partir de toutes les connaissances disponibles.','"Get full context about our auth system"','"Obtiens le contexte complet sur notre système d\'auth"', false],
        ]
    ],

    // ─── EMPATHY ENGINE — Emotional AI ───
    [
        'icon' => 'fas fa-heart', 'color' => 'rose', 'key' => 'alf_cat_empathy', 'count' => 11,
        'tools' => [
            ['Analyze Sentiment','Analyser le sentiment','Detect sentiment (positive, negative, neutral) in text.','Détectez le sentiment (positif, négatif, neutre) dans le texte.','"Analyze sentiment of customer reviews"','"Analyse le sentiment des avis clients"', false],
            ['Detect Tone','Détecter le ton','Identify the emotional tone and communication style in text.','Identifiez le ton émotionnel et le style de communication dans le texte.','"What tone is this email using?"','"Quel ton cet email utilise-t-il ?"', false],
            ['Track Mood','Suivre l\'humeur','Track user/team mood over time using interactions.','Suivez l\'humeur de l\'utilisateur/équipe au fil du temps via les interactions.','"Track my team\'s mood this sprint"','"Suis l\'humeur de mon équipe ce sprint"', false],
            ['Mood History','Historique d\'humeur','View mood trends over time with visualizations.','Consultez les tendances d\'humeur au fil du temps avec visualisations.','"Show user mood history for this month"','"Montre l\'historique d\'humeur des utilisateurs ce mois"', false],
            ['Suggest Response','Suggérer une réponse','Get an AI-suggested response calibrated to the user\'s emotional state.','Obtenez une réponse suggérée par l\'IA calibrée sur l\'état émotionnel de l\'utilisateur.','"Suggest a response to this frustrated customer"','"Suggère une réponse à ce client frustré"', false],
            ['Detect Frustration','Détecter la frustration','Identify signs of user frustration and suggest de-escalation.','Identifiez les signes de frustration de l\'utilisateur et suggérez la désescalade.','"Check if this user seems frustrated"','"Vérifie si cet utilisateur semble frustré"', false],
            ['De-escalate','Désescalader','Generate a de-escalation response for tense interactions.','Générez une réponse de désescalade pour les interactions tendues.','"Help me de-escalate this support ticket"','"Aide-moi à désescalader ce ticket de support"', false],
            ['Analyze Feedback','Analyser les retours','Deep-analyze user feedback for actionable insights.','Analysez en profondeur les retours utilisateurs pour des insights actionnables.','"Analyze our latest NPS feedback"','"Analyse nos derniers retours NPS"', false],
            ['Emotional Summary','Résumé émotionnel','Generate an emotional health summary for a period.','Générez un résumé de santé émotionnelle pour une période.','"Emotional summary for Q4 support interactions"','"Résumé émotionnel des interactions support du T4"', false],
            ['Set Tone','Définir le ton','Configure the AI\'s communication tone for different contexts.','Configurez le ton de communication de l\'IA pour différents contextes.','"Set a friendly, casual tone for chat"','"Définis un ton amical et décontracté pour le chat"', false],
            ['Rapport Score','Score de rapport','Measure the rapport level between AI and user.','Mesurez le niveau de rapport entre l\'IA et l\'utilisateur.','"Check my rapport score with Alfred"','"Vérifie mon score de rapport avec Alfred"', false],
        ]
    ],

    // ─── MUSE ENGINE — Creative Studio ───
    [
        'icon' => 'fas fa-palette', 'color' => 'violet', 'key' => 'alf_cat_muse', 'count' => 10,
        'tools' => [
            ['Brainstorm','Brainstormer','Generate creative ideas using AI-powered brainstorming techniques.','Générez des idées créatives en utilisant des techniques de brainstorming propulsées par l\'IA.','"Brainstorm names for my SaaS product"','"Brainstorme des noms pour mon produit SaaS"', false],
            ['Brand Voice','Voix de marque','Define or analyze brand voice and communication style.','Définissez ou analysez la voix de marque et le style de communication.','"Create a brand voice guide for my startup"','"Crée un guide de voix de marque pour ma startup"', false],
            ['Storytell','Raconter','Create compelling narratives and stories for any purpose.','Créez des narratifs et histoires captivants pour tout usage.','"Tell the story of how our product was born"','"Raconte l\'histoire de la naissance de notre produit"', false],
            ['Name Generator','Générateur de noms','Generate creative names for products, features, or companies.','Générez des noms créatifs pour produits, fonctionnalités ou entreprises.','"Generate 10 names for a fintech app"','"Génère 10 noms pour une application fintech"', false],
            ['Create Tagline','Créer un slogan','Generate catchy taglines and slogans for your brand.','Générez des slogans accrocheurs pour votre marque.','"Create a tagline for GoSiteMe"','"Crée un slogan pour GoSiteMe"', false],
            ['Generate Variations','Générer des variations','Create multiple creative variations of content.','Créez plusieurs variations créatives de contenu.','"Give me 5 variations of this headline"','"Donne-moi 5 variations de ce titre"', false],
            ['Create Metaphor','Créer une métaphore','Generate vivid metaphors and analogies for concepts.','Générez des métaphores et analogies vivantes pour des concepts.','"Create a metaphor for our security features"','"Crée une métaphore pour nos fonctionnalités de sécurité"', false],
            ['Mood Board','Tableau d\'ambiance','Generate a text-based creative mood board for projects.','Générez un tableau d\'ambiance créatif textuel pour les projets.','"Create a mood board for a luxury brand"','"Crée un tableau d\'ambiance pour une marque de luxe"', false],
            ['Copywrite','Rédiger','Generate professional marketing copy for any channel.','Générez du texte marketing professionnel pour tout canal.','"Write landing page copy for Alfred IDE"','"Rédige le texte de la page d\'accueil pour Alfred IDE"', false],
            ['Pitch Creator','Créateur de pitch','Create compelling pitch decks and elevator pitches.','Créez des pitch decks et elevator pitches captivants.','"Create a 60-second elevator pitch"','"Crée un elevator pitch de 60 secondes"', false],
        ]
    ],

    // ─── PRISM ENGINE — Visual Design ───
    [
        'icon' => 'fas fa-eye-dropper', 'color' => 'indigo', 'key' => 'alf_cat_prism', 'count' => 9,
        'tools' => [
            ['Analyze Colors','Analyser les couleurs','Analyze color usage in a design or website.','Analysez l\'utilisation des couleurs dans un design ou site web.','"Analyze colors on my homepage"','"Analyse les couleurs de ma page d\'accueil"', false],
            ['Suggest Palette','Suggérer une palette','Generate harmonious color palettes for any purpose.','Générez des palettes de couleurs harmonieuses pour tout usage.','"Suggest a palette for a health app"','"Suggère une palette pour une appli santé"', false],
            ['Check Contrast','Vérifier le contraste','Check color contrast ratios for accessibility compliance.','Vérifiez les ratios de contraste de couleurs pour la conformité d\'accessibilité.','"Check contrast of #333 on #f5f5f5"','"Vérifie le contraste de #333 sur #f5f5f5"', false],
            ['Analyze Layout','Analyser la mise en page','AI analysis of page layout, spacing, and visual hierarchy.','Analyse IA de la mise en page, l\'espacement et la hiérarchie visuelle.','"Analyze my dashboard layout"','"Analyse la mise en page de mon tableau de bord"', false],
            ['Design System','Système de design','Generate or audit a design system with tokens and guidelines.','Générez ou auditez un système de design avec tokens et directives.','"Create a design system for my app"','"Crée un système de design pour mon appli"', false],
            ['Responsive Check','Vérification responsive','Check if a design works across different screen sizes.','Vérifiez si un design fonctionne sur différentes tailles d\'écran.','"Check my site at mobile, tablet, and desktop"','"Vérifie mon site en mobile, tablette et bureau"', false],
            ['Typography Audit','Audit typographique','Analyze typography choices: hierarchy, readability, pairing.','Analysez les choix typographiques : hiérarchie, lisibilité, appariement.','"Audit the typography on my landing page"','"Audite la typographie de ma page d\'accueil"', false],
            ['Visual Quality Score','Score de qualité visuelle','Get an overall visual quality score with improvement suggestions.','Obtenez un score de qualité visuelle global avec suggestions d\'amélioration.','"Score the visual quality of my homepage"','"Évalue la qualité visuelle de ma page d\'accueil"', false],
            ['Icon Suggestions','Suggestions d\'icônes','Get icon recommendations for features and actions.','Obtenez des recommandations d\'icônes pour les fonctionnalités et actions.','"Suggest icons for my navigation menu"','"Suggère des icônes pour mon menu de navigation"', false],
        ]
    ],

    // ─── TEMPO ENGINE — Trend Intelligence ───
    [
        'icon' => 'fas fa-chart-line', 'color' => 'emerald', 'key' => 'alf_cat_tempo', 'count' => 9,
        'tools' => [
            ['Trend Analysis','Analyse de tendances','Analyze trends in data with statistical methods.','Analysez les tendances dans les données avec des méthodes statistiques.','"Analyze traffic trends for the last 90 days"','"Analyse les tendances de trafic des 90 derniers jours"', false],
            ['Predict Trend','Prédire une tendance','AI-powered prediction of future trends based on historical data.','Prédiction de tendances futures propulsée par l\'IA basée sur les données historiques.','"Predict our revenue for next quarter"','"Prédis notre revenu pour le prochain trimestre"', false],
            ['Seasonality Analysis','Analyse de saisonnalité','Detect seasonal patterns in your data.','Détectez les patterns saisonniers dans vos données.','"Find seasonality in our support tickets"','"Trouve la saisonnalité dans nos tickets de support"', false],
            ['Deadline Risk','Risque de deadline','Assess the probability of meeting a deadline.','Évaluez la probabilité de respecter une deadline.','"What\'s the risk of missing the March 1st deadline?"','"Quel est le risque de rater la deadline du 1er mars ?"', false],
            ['Team Velocity','Vélocité de l\'équipe','Calculate and track team development velocity.','Calculez et suivez la vélocité de développement de l\'équipe.','"Show our velocity for the last 5 sprints"','"Montre notre vélocité des 5 derniers sprints"', false],
            ['Capacity Planning','Planification de capacité','Forecast resource capacity needs based on growth trends.','Prévoyez les besoins en capacité de ressources basés sur les tendances de croissance.','"Plan capacity for the next quarter"','"Planifie la capacité pour le prochain trimestre"', false],
            ['Create Timeline','Créer une chronologie','Generate a visual timeline for a project or initiative.','Générez une chronologie visuelle pour un projet ou une initiative.','"Create a timeline for the Q2 launch"','"Crée une chronologie pour le lancement du T2"', false],
            ['Peak Hours Analysis','Analyse des heures de pointe','Identify peak usage hours and optimize accordingly.','Identifiez les heures de pointe d\'utilisation et optimisez en conséquence.','"When are our peak traffic hours?"','"Quand sont nos heures de pointe de trafic ?"', false],
            ['ETA Calculator','Calculateur d\'ETA','Estimate time of arrival/completion for tasks and projects.','Estimez le temps d\'arrivée/complétion pour les tâches et projets.','"How long will this migration take?"','"Combien de temps prendra cette migration ?"', false],
        ]
    ],

    // ─── ECHO ENGINE — Anomaly Detection ───
    [
        'icon' => 'fas fa-wave-square', 'color' => 'teal', 'key' => 'alf_cat_echo', 'count' => 9,
        'tools' => [
            ['Detect Anomaly','Détecter une anomalie','AI-powered anomaly detection in metrics, logs, or behavior.','Détection d\'anomalies propulsée par l\'IA dans les métriques, logs ou comportement.','"Check for anomalies in our error rates"','"Vérifie les anomalies dans nos taux d\'erreurs"', false],
            ['Find Patterns','Trouver des patterns','Discover hidden patterns in data using machine learning.','Découvrez des patterns cachés dans les données en utilisant l\'apprentissage machine.','"Find patterns in user signup data"','"Trouve des patterns dans les données d\'inscription"', false],
            ['Cluster Data','Regrouper les données','Group similar data points using clustering algorithms.','Regroupez des points de données similaires en utilisant des algorithmes de clustering.','"Cluster our support tickets by topic"','"Regroupe nos tickets de support par sujet"', false],
            ['Predict Failure','Prédire une panne','Predict system failures before they happen.','Prédisez les pannes système avant qu\'elles ne surviennent.','"Predict if our server will have issues"','"Prédis si notre serveur aura des problèmes"', false],
            ['Correlate Events','Corréler les événements','Find correlations between different event streams.','Trouvez des corrélations entre différents flux d\'événements.','"Correlate deploy events with error spikes"','"Corrèle les événements de déploiement avec les pics d\'erreurs"', false],
            ['Baseline Drift','Dérive de référence','Detect drift from established baseline metrics.','Détectez la dérive par rapport aux métriques de référence établies.','"Check if performance has drifted from baseline"','"Vérifie si la performance a dérivé de la référence"', false],
            ['Root Cause Analysis','Analyse de cause racine','AI-driven root cause analysis for issues and outages.','Analyse de cause racine dirigée par l\'IA pour les problèmes et pannes.','"Find the root cause of the latency spike"','"Trouve la cause racine du pic de latence"', false],
            ['Fingerprint Event','Empreinte d\'événement','Create a unique fingerprint/signature for event types.','Créez une empreinte/signature unique pour les types d\'événements.','"Fingerprint this error pattern"','"Crée l\'empreinte de ce pattern d\'erreur"', false],
            ['Forecast Metric','Prévoir une métrique','Forecast future values of any metric using time series analysis.','Prévoyez les valeurs futures de toute métrique en utilisant l\'analyse de séries temporelles.','"Forecast CPU usage for the next 7 days"','"Prévois l\'utilisation CPU pour les 7 prochains jours"', false],
        ]
    ],

    // ─── PULSE ENGINE — Engagement AI ───
    [
        'icon' => 'fas fa-heartbeat', 'color' => 'fuchsia', 'key' => 'alf_cat_pulse', 'count' => 9,
        'tools' => [
            ['Engagement Score','Score d\'engagement','Calculate user/customer engagement score from behavior data.','Calculez le score d\'engagement utilisateur/client à partir des données de comportement.','"Score my users\' engagement level"','"Évalue le niveau d\'engagement de mes utilisateurs"', false],
            ['Behavior Tracking','Suivi du comportement','Track and analyze user behavior patterns.','Suivez et analysez les patterns de comportement utilisateur.','"Track feature usage behavior"','"Suis le comportement d\'utilisation des fonctionnalités"', false],
            ['Cohort Analysis','Analyse de cohorte','Analyze user cohorts by signup date, behavior, or attributes.','Analysez les cohortes d\'utilisateurs par date d\'inscription, comportement ou attributs.','"Compare Q1 vs Q2 signup cohorts"','"Compare les cohortes d\'inscription T1 vs T2"', false],
            ['Churn Prediction','Prédiction de désabonnement','Predict which users are at risk of churning.','Prédisez quels utilisateurs risquent de se désabonner.','"Which users are at risk of churning?"','"Quels utilisateurs risquent de se désabonner ?"', false],
            ['Satisfaction Pulse','Pouls de satisfaction','Real-time satisfaction measurement from interaction data.','Mesure de satisfaction en temps réel à partir des données d\'interaction.','"Check customer satisfaction pulse"','"Vérifie le pouls de satisfaction client"', false],
            ['Community Health','Santé de la communauté','Measure community engagement, growth, and health metrics.','Mesurez l\'engagement communautaire, la croissance et les métriques de santé.','"How healthy is our user community?"','"Comment se porte notre communauté d\'utilisateurs ?"', false],
            ['Collaboration Score','Score de collaboration','Measure team collaboration effectiveness.','Mesurez l\'efficacité de collaboration de l\'équipe.','"Score our team\'s collaboration level"','"Évalue le niveau de collaboration de notre équipe"', false],
            ['Influence Map','Carte d\'influence','Map influence networks and key connectors.','Cartographiez les réseaux d\'influence et les connecteurs clés.','"Map influence in our user community"','"Cartographie l\'influence dans notre communauté"', false],
            ['Feedback Loop','Boucle de rétroaction','Create automated feedback collection and analysis loops.','Créez des boucles automatisées de collecte et d\'analyse de rétroaction.','"Set up a feedback loop for the new feature"','"Configure une boucle de rétroaction pour la nouvelle fonctionnalité"', false],
        ]
    ],

    // ─── SAGE ENGINE — Language AI ───
    [
        'icon' => 'fas fa-language', 'color' => 'sky', 'key' => 'alf_cat_sage', 'count' => 10,
        'tools' => [
            ['Translate Text','Traduire le texte','Translate text between 100+ languages with context preservation.','Traduisez du texte entre plus de 100 langues avec préservation du contexte.','"Translate this to French"','"Traduis ça en français"', false],
            ['Readability Score','Score de lisibilité','Analyze text readability using Flesch-Kincaid and other metrics.','Analysez la lisibilité du texte en utilisant Flesch-Kincaid et d\'autres métriques.','"Check readability of my landing page"','"Vérifie la lisibilité de ma page d\'accueil"', false],
            ['Grammar Check','Vérification grammaticale','Advanced grammar, spelling, and style checking.','Vérification avancée de la grammaire, de l\'orthographe et du style.','"Check grammar in this document"','"Vérifie la grammaire de ce document"', false],
            ['Localize Content','Localiser le contenu','Culturally adapt content for specific regions and markets.','Adaptez culturellement le contenu pour des régions et marchés spécifiques.','"Localize our app for the Japanese market"','"Localise notre appli pour le marché japonais"', false],
            ['Summarize Text','Résumer le texte','Create concise summaries of long documents or conversations.','Créez des résumés concis de longs documents ou conversations.','"Summarize this 20-page report"','"Résume ce rapport de 20 pages"', false],
            ['Extract Keywords','Extraire les mots-clés','Extract key terms, entities, and concepts from text.','Extrayez les termes clés, entités et concepts du texte.','"Extract keywords from this article"','"Extrais les mots-clés de cet article"', false],
            ['Tone Matching','Correspondance de ton','Rewrite text to match a specific tone or voice.','Réécrivez du texte pour correspondre à un ton ou une voix spécifique.','"Rewrite this in a professional tone"','"Réécris ça dans un ton professionnel"', false],
            ['Simplify Text','Simplifier le texte','Simplify complex text to a target reading level.','Simplifiez du texte complexe à un niveau de lecture cible.','"Simplify this legal text for customers"','"Simplifie ce texte juridique pour les clients"', false],
            ['Manage Glossary','Gérer le glossaire','Create and manage terminology glossaries for consistency.','Créez et gérez des glossaires de terminologie pour la cohérence.','"Add \'MCP\' to our glossary"','"Ajoute \'MCP\' à notre glossaire"', false],
            ['Compare Texts','Comparer les textes','Compare two texts for differences in meaning, tone, and style.','Comparez deux textes pour les différences de sens, ton et style.','"Compare the old and new copy"','"Compare l\'ancien et le nouveau texte"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // AUTOPILOT — Browser Agent (7 tools)
    // ═══════════════════════════════════════════════════════════

    // ─── AUTOPILOT — Autonomous Browser Agent ───
    [
        'icon' => 'fas fa-robot', 'color' => 'lime', 'key' => 'alf_cat_autopilot', 'count' => 7,
        'tools' => [
            ['Start Autopilot','Démarrer l\'autopilote','Launch autonomous browser agent to complete tasks with vision AI.','Lancez l\'agent de navigateur autonome pour compléter des tâches avec l\'IA de vision.','"Start autopilot to build my signup page"','"Démarre l\'autopilote pour construire ma page d\'inscription"', false],
            ['Autopilot Action','Action autopilote','Execute a specific browser action (click, type, scroll, navigate).','Exécutez une action de navigateur spécifique (clic, saisie, défilement, navigation).','"Click the submit button"','"Clique sur le bouton soumettre"', false],
            ['Autopilot Observe','Observer l\'autopilote','Take a screenshot and analyze the current browser state.','Prenez une capture d\'écran et analysez l\'état actuel du navigateur.','"What do you see on the screen?"','"Que vois-tu à l\'écran ?"', false],
            ['Stop Autopilot','Arrêter l\'autopilote','Stop the autonomous browser agent and save session state.','Arrêtez l\'agent de navigateur autonome et sauvegardez l\'état de la session.','"Stop autopilot"','"Arrête l\'autopilote"', false],
            ['Autopilot Templates','Modèles autopilote','Browse and use pre-built autopilot task templates.','Parcourez et utilisez des modèles de tâches autopilote préconstruits.','"Show autopilot templates for e-commerce"','"Montre les modèles autopilote pour le e-commerce"', false],
            ['Autopilot Batch','Lot autopilote','Run multiple autopilot tasks in parallel or sequence.','Exécutez plusieurs tâches autopilote en parallèle ou en séquence.','"Batch run: update prices on all product pages"','"Exécution en lot : mettre à jour les prix sur toutes les pages produits"', false],
            ['Schedule Autopilot','Planifier l\'autopilote','Schedule autopilot tasks to run at specific times.','Planifiez des tâches autopilote à exécuter à des heures spécifiques.','"Schedule daily site health check at 6am"','"Planifie une vérification quotidienne du site à 6h"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // SERVER INFRASTRUCTURE (35 tools split into 3 categories)
    // ═══════════════════════════════════════════════════════════

    // ─── SERVER ADMIN — Core Infrastructure ───
    [
        'icon' => 'fas fa-server', 'color' => 'slate', 'key' => 'alf_cat_serveradmin', 'count' => 14,
        'tools' => [
            ['SSH Execute','Exécution SSH','Run commands on remote servers via SSH.','Exécutez des commandes sur des serveurs distants via SSH.','"SSH into production and check disk space"','"Connecte-toi en SSH à la production et vérifie l\'espace disque"', false],
            ['SFTP Transfer','Transfert SFTP','Securely upload/download files via SFTP.','Téléchargez/téléversez des fichiers de manière sécurisée via SFTP.','"Upload the config file to staging"','"Téléverse le fichier de config vers staging"', false],
            ['Rsync Sync','Synchronisation Rsync','Synchronize files between servers using rsync.','Synchronisez les fichiers entre serveurs avec rsync.','"Sync the assets folder to production"','"Synchronise le dossier assets vers la production"', false],
            ['Process Manager','Gestionnaire de processus','View and manage running system processes.','Consultez et gérez les processus système en cours.','"Show top CPU-consuming processes"','"Montre les processus les plus gourmands en CPU"', false],
            ['Service Manager','Gestionnaire de services','Start, stop, restart, and monitor system services.','Démarrez, arrêtez, redémarrez et surveillez les services système.','"Restart the nginx service"','"Redémarre le service nginx"', false],
            ['Network Diagnostics','Diagnostics réseau','Run network diagnostics (ping, traceroute, DNS lookup).','Exécutez des diagnostics réseau (ping, traceroute, lookup DNS).','"Run a traceroute to google.com"','"Exécute un traceroute vers google.com"', false],
            ['DNS Propagation','Propagation DNS','Check DNS propagation status across global nameservers.','Vérifiez l\'état de propagation DNS à travers les serveurs de noms globaux.','"Check DNS propagation for example.com"','"Vérifie la propagation DNS pour example.com"', false],
            ['Firewall Manager','Gestionnaire de pare-feu','View and manage firewall rules (iptables, ufw).','Consultez et gérez les règles de pare-feu (iptables, ufw).','"Show firewall rules and add port 8080"','"Montre les règles de pare-feu et ajoute le port 8080"', false],
            ['Tail Logs','Consulter les logs','Live-tail or search server log files.','Consultez en direct ou recherchez dans les fichiers de logs serveur.','"Tail the error log for the last 100 lines"','"Consulte les 100 dernières lignes du log d\'erreurs"', false],
            ['Package Manager','Gestionnaire de paquets','Install, update, or remove system packages.','Installez, mettez à jour ou supprimez les paquets système.','"Update all packages"','"Mets à jour tous les paquets"', false],
            ['Permission Manager','Gestionnaire de permissions','Set and audit file/directory permissions.','Définissez et auditez les permissions de fichiers/répertoires.','"Fix permissions on the uploads folder"','"Corrige les permissions du dossier uploads"', false],
            ['Certificate Manager','Gestionnaire de certificats','Manage SSL/TLS certificates — issue, renew, and monitor.','Gérez les certificats SSL/TLS — émission, renouvellement et surveillance.','"Renew the SSL certificate for my domain"','"Renouvelle le certificat SSL pour mon domaine"', false],
            ['Cron Tools','Outils Cron','Create, list, edit, and monitor cron jobs.','Créez, listez, modifiez et surveillez les tâches cron.','"Show all cron jobs"','"Montre toutes les tâches cron"', false],
            ['System Analyzer','Analyseur système','Deep system analysis: CPU, memory, disk, network, and bottlenecks.','Analyse système approfondie : CPU, mémoire, disque, réseau et goulots d\'étranglement.','"Analyze system performance"','"Analyse la performance système"', false],
        ]
    ],

    // ─── DATABASE & CACHE — Data Infrastructure ───
    [
        'icon' => 'fas fa-database', 'color' => 'blue', 'key' => 'alf_cat_datacache', 'count' => 11,
        'tools' => [
            ['Docker Manager','Gestionnaire Docker','Manage Docker containers, images, and compose stacks.','Gérez les conteneurs Docker, images et stacks compose.','"List running Docker containers"','"Liste les conteneurs Docker en cours"', false],
            ['Redis Manager','Gestionnaire Redis','Manage Redis: keys, memory, config, and monitoring.','Gérez Redis : clés, mémoire, config et surveillance.','"Show Redis memory usage"','"Montre l\'utilisation mémoire de Redis"', false],
            ['PostgreSQL Manager','Gestionnaire PostgreSQL','Manage PostgreSQL: databases, queries, backups, and users.','Gérez PostgreSQL : bases de données, requêtes, sauvegardes et utilisateurs.','"List all PostgreSQL databases"','"Liste toutes les bases de données PostgreSQL"', false],
            ['MongoDB Manager','Gestionnaire MongoDB','Manage MongoDB: collections, queries, indexes, and aggregations.','Gérez MongoDB : collections, requêtes, index et agrégations.','"Show MongoDB collection stats"','"Montre les stats de collections MongoDB"', false],
            ['Cache Manager','Gestionnaire de cache','Manage application caches: view, clear, warm, and optimize.','Gérez les caches d\'application : consulter, vider, préchauffer et optimiser.','"Clear and warm the application cache"','"Vide et préchauffe le cache de l\'application"', false],
            ['Queue Manager','Gestionnaire de files d\'attente','Manage job queues: view, process, retry, and purge.','Gérez les files d\'attente : consulter, traiter, réessayer et purger.','"Show pending jobs in the email queue"','"Montre les tâches en attente dans la file d\'emails"', false],
            ['DB Migration','Migration de base de données','Run, rollback, and manage database migrations.','Exécutez, annulez et gérez les migrations de base de données.','"Run pending database migrations"','"Exécute les migrations de base de données en attente"', false],
            ['List Databases','Lister les bases','List all databases on the server with size info.','Listez toutes les bases de données du serveur avec la taille.','"Show all databases and their sizes"','"Montre toutes les bases de données et leurs tailles"', false],
            ['Database Schema','Schéma de base de données','View and analyze database table schemas.','Consultez et analysez les schémas de tables de base de données.','"Show schema for the users table"','"Montre le schéma de la table utilisateurs"', false],
            ['Database Query','Requête de base de données','Execute safe read-only database queries.','Exécutez des requêtes de base de données sécurisées en lecture seule.','"Run a query: SELECT count(*) FROM orders"','"Exécute une requête : SELECT count(*) FROM orders"', false],
            ['Database Stats','Statistiques de base de données','View database performance metrics and statistics.','Consultez les métriques de performance et statistiques de base de données.','"Show database performance stats"','"Montre les statistiques de performance de la base"', false],
        ]
    ],

    // ─── DEV UTILITIES — Code & System Tools ───
    [
        'icon' => 'fas fa-tools', 'color' => 'gray', 'key' => 'alf_cat_devutils', 'count' => 16,
        'tools' => [
            ['Git Advanced','Git avancé','Advanced git operations: cherry-pick, rebase, bisect, stash.','Opérations git avancées : cherry-pick, rebase, bisect, stash.','"Cherry-pick commit abc123 to main"','"Cherry-pick le commit abc123 vers main"', false],
            ['Archive Manager','Gestionnaire d\'archives','Create and extract archive files (zip, tar, gzip).','Créez et extrayez des fichiers d\'archive (zip, tar, gzip).','"Create a zip of the src/ directory"','"Crée un zip du répertoire src/"', false],
            ['Code Transform','Transformation de code','Transform code between formats, languages, or styles.','Transformez le code entre formats, langages ou styles.','"Convert this class to TypeScript"','"Convertis cette classe en TypeScript"', false],
            ['Performance Profiler','Profileur de performance','Profile application performance: CPU, memory, and timing.','Profilez la performance de l\'application : CPU, mémoire et timing.','"Profile the login endpoint performance"','"Profile la performance du endpoint de connexion"', false],
            ['PDF Manipulator','Manipulateur PDF','Create, merge, split, and convert PDF files.','Créez, fusionnez, divisez et convertissez des fichiers PDF.','"Merge these 3 PDFs into one"','"Fusionne ces 3 PDF en un seul"', false],
            ['API Tester','Testeur d\'API','Test API endpoints with custom requests and assertions.','Testez les endpoints API avec des requêtes personnalisées et assertions.','"Test POST to /api/users with sample data"','"Teste POST vers /api/users avec des données exemples"', false],
            ['K8s Manager','Gestionnaire K8s','Manage Kubernetes: pods, deployments, services, and logs.','Gérez Kubernetes : pods, déploiements, services et logs.','"List all pods in the production namespace"','"Liste tous les pods dans le namespace production"', false],
            ['Feature Flags','Drapeaux de fonctionnalités','Manage feature flags: create, toggle, and target users.','Gérez les drapeaux de fonctionnalités : créer, basculer et cibler.','"Enable the dark-mode flag for beta users"','"Active le drapeau dark-mode pour les utilisateurs bêta"', false],
            ['Email Diagnostics','Diagnostics email','Diagnose email delivery: SPF, DKIM, DMARC, and deliverability.','Diagnostiquez la livraison d\'email : SPF, DKIM, DMARC et délivrabilité.','"Check email deliverability for my domain"','"Vérifie la délivrabilité email pour mon domaine"', false],
            ['Security Headers','En-têtes de sécurité','Check and configure HTTP security headers.','Vérifiez et configurez les en-têtes HTTP de sécurité.','"Check security headers on my site"','"Vérifie les en-têtes de sécurité de mon site"', false],
            ['Utility Generator','Générateur d\'utilitaires','Generate common utility functions and helpers.','Générez des fonctions utilitaires et helpers communs.','"Generate a UUID utility function"','"Génère une fonction utilitaire UUID"', false],
            ['Crypto Tools','Outils de cryptographie','Hashing, encryption, JWT generation, and key management.','Hachage, chiffrement, génération JWT et gestion de clés.','"Generate a SHA-256 hash of this text"','"Génère un hash SHA-256 de ce texte"', false],
            ['Data Validator','Validateur de données','Validate data against schemas, formats, and business rules.','Validez les données contre les schémas, formats et règles métier.','"Validate this JSON against the user schema"','"Valide ce JSON contre le schéma utilisateur"', false],
            ['Regex Tools','Outils regex','Build, test, and explain regular expressions.','Construisez, testez et expliquez les expressions régulières.','"Build a regex for email validation"','"Construis une regex pour la validation d\'email"', false],
            ['Text Utilities','Utilitaires de texte','Text processing: diff, transform, encode/decode, format.','Traitement de texte : diff, transformation, encodage/décodage, formatage.','"Base64 encode this string"','"Encode en Base64 cette chaîne"', false],
            ['Calculator','Calculatrice','Advanced mathematical calculations and unit conversions.','Calculs mathématiques avancés et conversions d\'unités.','"Calculate compound interest on $10,000 at 5% for 3 years"','"Calcule l\'intérêt composé sur 10 000 $ à 5 % pendant 3 ans"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // V9.0 — Voice Commerce & Beyond Autopilot (15 tools)
    // ═══════════════════════════════════════════════════════════

    // ─── VOICE COMMERCE — Sign Up & Pay by Voice ───
    [
        'icon' => 'fas fa-cash-register', 'color' => 'green', 'key' => 'alf_cat_voicecommerce', 'count' => 7,
        'tools' => [
            ['Create Client','Créer un client','Sign up a new client with name, email, and optional company.','Inscrivez un nouveau client avec nom, email et entreprise optionnelle.','"Sign me up — John, john@example.com"','"Inscris-moi — Jean, jean@example.com"', false],
            ['Update Client Profile','Mettre à jour le profil','Update client profile information: name, company, phone, address.','Mettez à jour les informations de profil client : nom, entreprise, téléphone, adresse.','"Update my phone number to 555-1234"','"Mets à jour mon numéro de téléphone à 555-1234"', false],
            ['Add Payment Method','Ajouter un paiement','Securely add a credit card via Stripe tokenization.','Ajoutez de manière sécurisée une carte de crédit via la tokenisation Stripe.','"Add my Visa ending in 4242"','"Ajoute ma Visa finissant par 4242"', false],
            ['List Payment Methods','Lister les paiements','View all saved payment methods on your account.','Consultez tous les moyens de paiement enregistrés sur votre compte.','"Show my saved payment methods"','"Montre mes moyens de paiement enregistrés"', false],
            ['Process Payment','Traiter un paiement','Process a payment for an invoice or order.','Traitez un paiement pour une facture ou une commande.','"Pay invoice #1234"','"Paie la facture #1234"', false],
            ['Accept Order','Accepter une commande','Confirm and accept a pending order after review.','Confirmez et acceptez une commande en attente après vérification.','"Accept my hosting order"','"Accepte ma commande d\'hébergement"', false],
            ['Voice Onboard','Intégration vocale','Complete full signup, order, and payment in a single voice conversation.','Complétez l\'inscription, la commande et le paiement en une seule conversation vocale.','"I want to sign up for the Pro hosting plan"','"Je veux m\'inscrire au plan d\'hébergement Pro"', false],
        ]
    ],

    // ─── BEYOND AUTOPILOT — Next-Gen AI Capabilities ───
    [
        'icon' => 'fas fa-rocket', 'color' => 'gradient', 'key' => 'alf_cat_beyondautopilot', 'count' => 8,
        'tools' => [
            ['Agent Swarm','Essaim d\'agents','Deploy multiple AI agents in parallel to tackle complex tasks.','Déployez plusieurs agents IA en parallèle pour traiter des tâches complexes.','"Deploy an agent swarm to audit my entire site"','"Déploie un essaim d\'agents pour auditer tout mon site"', false],
            ['Self-Evolve','Auto-évolution','Alfred generates, tests, and registers new tools autonomously.','Alfred génère, teste et enregistre de nouveaux outils de manière autonome.','"Create a tool to check domain WHOIS"','"Crée un outil pour vérifier le WHOIS de domaine"', false],
            ['Predictive Build','Construction prédictive','AI predicts what you need next and pre-builds it.','L\'IA prédit ce dont vous avez besoin ensuite et le pré-construit.','"Predict and prepare my next development steps"','"Prédis et prépare mes prochaines étapes de développement"', false],
            ['Cross-Channel Sync','Synchronisation multicanal','Sync context seamlessly between chat, voice, and IDE.','Synchronisez le contexte de manière transparente entre chat, voix et IDE.','"Sync my chat context to the IDE"','"Synchronise mon contexte de chat vers l\'IDE"', false],
            ['Ambient Intelligence','Intelligence ambiante','AI monitors your patterns and proactively offers help.','L\'IA surveille vos patterns et offre proactivement de l\'aide.','"Enable ambient intelligence for my workspace"','"Active l\'intelligence ambiante pour mon espace de travail"', false],
            ['Time-Travel Debug','Débogage temporel','Step backwards through execution history to find when a bug was introduced.','Remontez dans l\'historique d\'exécution pour trouver quand un bug a été introduit.','"Time-travel debug: when did the login break?"','"Débogage temporel : quand la connexion s\'est-elle cassée ?"', false],
            ['Reality Bridge','Pont de réalité','Bridge AI plans into real infrastructure actions with safety checks.','Reliez les plans de l\'IA aux actions d\'infrastructure réelles avec des vérifications de sécurité.','"Bridge the deployment plan to production"','"Relie le plan de déploiement à la production"', false],
            ['Fleet Orchestrator','Orchestrateur de flotte','Orchestrate actions across multiple servers simultaneously.','Orchestrez des actions sur plusieurs serveurs simultanément.','"Update all 5 servers in the cluster"','"Mets à jour les 5 serveurs du cluster"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // ADVANCED PLATFORM TOOLS (33 tools in 5 categories)
    // ═══════════════════════════════════════════════════════════

    // ─── A2A PROTOCOL — Agent-to-Agent Communication ───
    [
        'icon' => 'fas fa-network-wired', 'color' => 'cyan', 'key' => 'alf_cat_a2a', 'count' => 4,
        'tools' => [
            ['Discover Agents','Découvrir des agents','Discover available AI agents on the A2A network.','Découvrez les agents IA disponibles sur le réseau A2A.','"Find agents that can help with DevOps"','"Trouve des agents qui peuvent aider avec le DevOps"', false],
            ['Send Task to Agent','Envoyer une tâche','Send a task to another AI agent via A2A protocol.','Envoyez une tâche à un autre agent IA via le protocole A2A.','"Send a code review task to the security agent"','"Envoie une tâche de revue de code à l\'agent sécurité"', false],
            ['List Agent Tasks','Lister les tâches d\'agent','View all tasks sent/received via A2A protocol.','Consultez toutes les tâches envoyées/reçues via le protocole A2A.','"Show all A2A tasks"','"Montre toutes les tâches A2A"', false],
            ['Publish Agent Card','Publier une carte d\'agent','Publish your agent\'s capabilities to the A2A network.','Publiez les capacités de votre agent sur le réseau A2A.','"Publish Alfred\'s agent card"','"Publie la carte d\'agent d\'Alfred"', false],
        ]
    ],

    // ─── CHARTS & ARTIFACTS — Visual Output ───
    [
        'icon' => 'fas fa-chart-pie', 'color' => 'orange', 'key' => 'alf_cat_charts', 'count' => 4,
        'tools' => [
            ['Create Chart','Créer un graphique','Generate interactive charts: bar, line, pie, scatter, and more.','Générez des graphiques interactifs : barres, lignes, camembert, nuage de points et plus.','"Create a line chart of monthly revenue"','"Crée un graphique linéaire du revenu mensuel"', false],
            ['Create Diagram','Créer un diagramme','Generate diagrams: flowcharts, sequence, ER, architecture.','Générez des diagrammes : organigrammes, séquence, ER, architecture.','"Create a flowchart of the signup process"','"Crée un organigramme du processus d\'inscription"', false],
            ['Preview HTML','Aperçu HTML','Render and preview HTML/CSS/JS artifacts in real-time.','Affichez et prévisualisez les artefacts HTML/CSS/JS en temps réel.','"Preview this landing page HTML"','"Aperçu de ce HTML de page d\'accueil"', false],
            ['List Artifacts','Lister les artefacts','View all generated charts, diagrams, and artifacts.','Consultez tous les graphiques, diagrammes et artefacts générés.','"Show all my generated artifacts"','"Montre tous mes artefacts générés"', false],
        ]
    ],

    // ─── VOICE ROOMS — Collaborative AI Voice ───
    [
        'icon' => 'fas fa-headset', 'color' => 'yellow', 'key' => 'alf_cat_voicerooms', 'count' => 3,
        'tools' => [
            ['Create Voice Room','Créer un salon vocal','Create a collaborative voice room with AI agents.','Créez un salon vocal collaboratif avec des agents IA.','"Create a team standup voice room"','"Crée un salon vocal pour la réunion quotidienne"', false],
            ['Join Voice Room','Rejoindre un salon vocal','Join an existing voice room with a specific AI agent.','Rejoignez un salon vocal existant avec un agent IA spécifique.','"Join the design review voice room"','"Rejoins le salon vocal de revue de design"', false],
            ['List Voice Rooms','Lister les salons vocaux','View all active voice rooms and participants.','Consultez tous les salons vocaux actifs et les participants.','"Show active voice rooms"','"Montre les salons vocaux actifs"', false],
        ]
    ],

    // ─── LOCAL AI — On-Device Models ───
    [
        'icon' => 'fas fa-microchip', 'color' => 'emerald', 'key' => 'alf_cat_locallm', 'count' => 4,
        'tools' => [
            ['Local LLM Chat','Chat LLM local','Run AI conversations using local on-device models for privacy.','Exécutez des conversations IA en utilisant des modèles locaux pour la confidentialité.','"Chat with the local Llama model"','"Discute avec le modèle Llama local"', false],
            ['List Local Models','Lister les modèles locaux','View all available local AI models and their status.','Consultez tous les modèles IA locaux disponibles et leur état.','"Show available local models"','"Montre les modèles locaux disponibles"', false],
            ['Pull Local Model','Télécharger un modèle local','Download and install a local AI model.','Téléchargez et installez un modèle IA local.','"Pull the CodeLlama model"','"Télécharge le modèle CodeLlama"', false],
            ['Route to Local','Acheminer vers le local','Automatically route sensitive queries to local models.','Acheminez automatiquement les requêtes sensibles vers les modèles locaux.','"Route all code queries to the local model"','"Achemine toutes les requêtes de code vers le modèle local"', false],
        ]
    ],

    // ─── PLATFORM OPS — System Management ───
    [
        'icon' => 'fas fa-cogs', 'color' => 'stone', 'key' => 'alf_cat_platformops', 'count' => 18,
        'tools' => [
            ['OG Preview','Aperçu OG','Generate and preview Open Graph meta tags for social sharing.','Générez et prévisualisez les balises méta Open Graph pour le partage social.','"Preview OG tags for my homepage"','"Aperçu des balises OG pour ma page d\'accueil"', false],
            ['Env File Manager','Gestionnaire .env','Manage environment files: view, edit, and sync .env files.','Gérez les fichiers d\'environnement : consultez, modifiez et synchronisez les fichiers .env.','"Show all variables in .env"','"Montre toutes les variables dans .env"', false],
            ['Image Tools','Outils d\'image','Resize, compress, convert, and optimize images.','Redimensionnez, compressez, convertissez et optimisez les images.','"Optimize all images in the assets folder"','"Optimise toutes les images dans le dossier assets"', false],
            ['Scratchpad','Bloc-notes','Temporary workspace for notes, code, and ideas during a session.','Espace de travail temporaire pour notes, code et idées pendant une session.','"Save this code snippet to my scratchpad"','"Enregistre ce snippet de code dans mon bloc-notes"', false],
            ['Send Email','Envoyer un email','Send emails directly from Alfred with templates and tracking.','Envoyez des emails directement depuis Alfred avec modèles et suivi.','"Send a welcome email to the new client"','"Envoie un email de bienvenue au nouveau client"', false],
            ['Create Checkpoint','Créer un checkpoint','Save the current state as a named checkpoint for rollback.','Sauvegardez l\'état actuel comme checkpoint nommé pour rollback.','"Create a checkpoint before the migration"','"Crée un checkpoint avant la migration"', false],
            ['List Checkpoints','Lister les checkpoints','View all saved checkpoints with dates and descriptions.','Consultez tous les checkpoints sauvegardés avec dates et descriptions.','"Show all my checkpoints"','"Montre tous mes checkpoints"', false],
            ['Restore Checkpoint','Restaurer un checkpoint','Restore files and state from a saved checkpoint.','Restaurez les fichiers et l\'état depuis un checkpoint sauvegardé.','"Restore the pre-migration checkpoint"','"Restaure le checkpoint pré-migration"', false],
            ['Terminal Status','Statut du terminal','View active terminal sessions and their output.','Consultez les sessions de terminal actives et leur sortie.','"Show terminal session status"','"Montre le statut des sessions de terminal"', false],
            ['Terminal History','Historique du terminal','View command history for terminal sessions.','Consultez l\'historique des commandes des sessions de terminal.','"Show my terminal command history"','"Montre l\'historique de mes commandes de terminal"', false],
            ['Terminal Reset','Réinitialiser le terminal','Reset or clean up a terminal session.','Réinitialisez ou nettoyez une session de terminal.','"Reset the terminal"','"Réinitialise le terminal"', false],
            ['Search Tools','Rechercher des outils','Search all 13,000+ AI tools by keyword or capability.','Recherchez parmi tous les 13,000+ outils IA par mot-clé ou capacité.','"Find tools related to email"','"Trouve des outils liés à l\'email"', false],
            ['Tool Documentation','Documentation d\'outil','Get detailed documentation for any tool.','Obtenez la documentation détaillée pour tout outil.','"Show docs for the docker_manage tool"','"Montre la doc pour l\'outil docker_manage"', false],
            ['Get Tool Doc','Obtenir un doc d\'outil','Get the full specification and examples for a tool.','Obtenez la spécification complète et les exemples pour un outil.','"Get full docs for ssh_exec"','"Obtiens les docs complets pour ssh_exec"', false],
            ['Isolation Status','Statut d\'isolation','Check the current session isolation and security status.','Vérifiez l\'isolation de session actuelle et le statut de sécurité.','"Check my isolation status"','"Vérifie mon statut d\'isolation"', false],
            ['MCP Usage Stats','Stats d\'utilisation MCP','View MCP tool usage statistics and analytics.','Consultez les statistiques et analyses d\'utilisation des outils MCP.','"Show MCP usage stats"','"Montre les stats d\'utilisation MCP"', false],
            ['Error Summary','Résumé des erreurs','View aggregated error counts and trends.','Consultez les comptages d\'erreurs agrégés et les tendances.','"Show error summary for the last 24 hours"','"Montre le résumé des erreurs des dernières 24 heures"', false],
            ['Database Backup','Sauvegarde de base de données','Create and manage database backups with scheduling.','Créez et gérez les sauvegardes de base de données avec planification.','"Backup the production database"','"Sauvegarde la base de données de production"', false],
        ]
    ],

    // ═══════════════════════════════════════════════════════════
    // V9.1 — Voice Management & Communications (51 tools in 4 categories)
    // ═══════════════════════════════════════════════════════════

    // ─── AI AGENT MANAGEMENT — Create, configure, deploy AI voice agents ───
    [
        'icon' => 'fas fa-robot', 'color' => 'cyan', 'key' => 'alf_cat_agentmgmt', 'count' => 8,
        'tools' => [
            ['List My Agents','Lister mes agents','View all your AI voice agents with names, personas, and assigned phone numbers.','Consultez tous vos agents vocaux IA avec noms, personas et numéros assignés.','"Show my AI agents"','"Montre mes agents IA"', false],
            ['Create AI Agent','Créer un agent IA','Create a new AI voice agent with custom persona, greeting, and language.','Créez un nouvel agent vocal IA avec persona, salutation et langue personnalisés.','"Create a receptionist agent for my dental office"','"Crée un agent réceptionniste pour mon cabinet dentaire"', false],
            ['Update AI Agent','Mettre à jour un agent','Update an existing agent\'s name, persona, greeting, voice, or transfer number.','Mettez à jour le nom, la persona, la salutation, la voix ou le numéro de transfert d\'un agent.','"Change my agent\'s greeting to mention our holiday hours"','"Change la salutation de mon agent pour mentionner nos heures de vacances"', false],
            ['Delete AI Agent','Supprimer un agent','Remove an AI agent and unassign its phone numbers.','Supprimez un agent IA et désassignez ses numéros de téléphone.','"Delete the old test agent"','"Supprime l\'ancien agent de test"', false],
            ['Voice Dashboard','Tableau de bord vocal','Get an overview of your voice portal: agents, phones, calls, SMS, fax, usage.','Obtenez un aperçu de votre portail vocal : agents, téléphones, appels, SMS, fax, utilisation.','"Show my voice dashboard"','"Montre mon tableau de bord vocal"', false],
            ['Voice Usage','Utilisation vocale','View minutes used, SMS sent, faxes, and billing for current and past periods.','Consultez les minutes utilisées, SMS envoyés, fax et facturation pour les périodes actuelles et passées.','"How many voice minutes have I used this month?"','"Combien de minutes vocales ai-je utilisées ce mois-ci ?"', false],
            ['Assign Phone to Agent','Assigner un numéro','Connect a phone number to an AI agent so it answers calls on that line.','Connectez un numéro de téléphone à un agent IA pour qu\'il réponde aux appels sur cette ligne.','"Assign my toll-free number to my receptionist agent"','"Assigne mon numéro sans frais à mon agent réceptionniste"', false],
            ['Get Call Details','Détails d\'un appel','View complete details of a specific call: duration, sentiment, transcript, agent used.','Consultez les détails complets d\'un appel spécifique : durée, sentiment, transcription, agent utilisé.','"Show me details of call #42"','"Montre-moi les détails de l\'appel #42"', false],
        ]
    ],

    // ─── PHONE, SMS & FAX — Communication tools ───
    [
        'icon' => 'fas fa-phone-alt', 'color' => 'green', 'key' => 'alf_cat_phonemgmt', 'count' => 12,
        'tools' => [
            ['List Phone Numbers','Lister les numéros','View all your phone numbers, their type, and agent assignments.','Consultez tous vos numéros de téléphone, leur type et les agents assignés.','"Show my phone numbers"','"Montre mes numéros de téléphone"', false],
            ['Order Phone Number','Commander un numéro','Order a new local, toll-free, international, vanity, or fax number.','Commandez un nouveau numéro local, sans frais, international, vanité ou fax.','"Get me a toll-free number"','"Obtiens-moi un numéro sans frais"', false],
            ['View Call Log','Voir le journal d\'appels','Browse your call history with direction, duration, status, and sentiment.','Parcourez votre historique d\'appels avec direction, durée, statut et sentiment.','"Show my recent calls"','"Montre mes appels récents"', false],
            ['Send SMS','Envoyer un SMS','Send a text message to any phone number from your SMS-enabled line.','Envoyez un message texte à n\'importe quel numéro depuis votre ligne SMS.','"Text 555-1234: Your appointment is confirmed for tomorrow at 2pm"','"Envoie un texto au 555-1234 : Votre rendez-vous est confirmé pour demain à 14h"', false],
            ['List SMS Messages','Lister les SMS','View your SMS message history — sent and received.','Consultez votre historique de messages SMS — envoyés et reçus.','"Show my text messages"','"Montre mes messages texte"', false],
            ['Send Fax','Envoyer un fax','Send a fax document to any fax number from your fax-enabled line.','Envoyez un document par fax à n\'importe quel numéro de fax depuis votre ligne fax.','"Fax this contract to 555-9876"','"Envoie ce contrat par fax au 555-9876"', false],
            ['List Faxes','Lister les fax','View your fax history with status and document links.','Consultez votre historique de fax avec statut et liens de documents.','"Show my fax history"','"Montre mon historique de fax"', false],
            ['List Documents','Lister les documents','View your document templates for fax cover sheets, scripts, and more.','Consultez vos modèles de documents pour pages de couverture fax, scripts et plus.','"Show my document templates"','"Montre mes modèles de documents"', false],
            ['Create Document','Créer un document','Create a new fax cover sheet, call script, or custom document template.','Créez une nouvelle page de couverture fax, script d\'appel ou modèle personnalisé.','"Create a fax cover sheet for my law firm"','"Crée une page de couverture fax pour mon cabinet d\'avocats"', false],
            ['Delete Document','Supprimer un document','Remove a document template you no longer need.','Supprimez un modèle de document dont vous n\'avez plus besoin.','"Delete the old fax template"','"Supprime l\'ancien modèle de fax"', false],
            ['Order SMS Plan','Commander un plan SMS','Order an SMS messaging plan: Starter (500/mo), Business (2000/mo), or Enterprise (10000/mo).','Commandez un plan de messagerie SMS : Starter (500/mois), Business (2000/mois) ou Enterprise (10000/mois).','"I need an SMS plan for my business"','"J\'ai besoin d\'un plan SMS pour mon entreprise"', false],
            ['Order Fax Plan','Commander un plan fax','Order a fax plan: Fax Pro (500 pages/mo) or Fax Enterprise (5000 pages/mo).','Commandez un plan fax : Fax Pro (500 pages/mois) ou Fax Enterprise (5000 pages/mois).','"Set up fax for my office"','"Configure le fax pour mon bureau"', false],
        ]
    ],

    // ─── CALL CENTER & CAMPAIGNS — Outbound calling, scheduling, surveys ───
    [
        'icon' => 'fas fa-headset', 'color' => 'orange', 'key' => 'alf_cat_callcenter', 'count' => 11,
        'tools' => [
            ['List Campaigns','Lister les campagnes','View all your voice/SMS campaigns with status and contact counts.','Consultez toutes vos campagnes vocales/SMS avec statut et nombre de contacts.','"Show my campaigns"','"Montre mes campagnes"', false],
            ['Create Campaign','Créer une campagne','Launch a new outbound calling or SMS campaign with AI agents.','Lancez une nouvelle campagne d\'appels sortants ou SMS avec des agents IA.','"Create an appointment reminder campaign for next week"','"Crée une campagne de rappels de rendez-vous pour la semaine prochaine"', false],
            ['Update Campaign','Mettre à jour une campagne','Pause, resume, schedule, or cancel an active campaign.','Mettez en pause, reprenez, planifiez ou annulez une campagne active.','"Pause the current campaign"','"Mets en pause la campagne en cours"', false],
            ['Call Center Starter','Centre d\'appels Starter','5 agents, call queue, IVR, basic reporting — $149/mo.','5 agents, file d\'attente, SVI, rapports de base — 149 $/mois.','"Set up a call center for my support team"','"Configure un centre d\'appels pour mon équipe de support"', false],
            ['Call Center Growth','Centre d\'appels Growth','15 agents, workforce management, quality monitoring — $299/mo.','15 agents, gestion des effectifs, contrôle qualité — 299 $/mois.','"I need a bigger call center"','"J\'ai besoin d\'un plus grand centre d\'appels"', false],
            ['Call Center Enterprise','Centre d\'appels Enterprise','50+ agents, predictive dialer, omnichannel — $599/mo.','50+ agents, numéroteur prédictif, omnicanal — 599 $/mois.','"Enterprise call center solution"','"Solution de centre d\'appels entreprise"', false],
            ['Predictive Dialer','Numéroteur prédictif','Auto-dial campaigns with do-not-call compliance — $99/mo.','Campagnes d\'appels automatiques avec conformité LNE — 99 $/mois.','"Add a predictive dialer to my account"','"Ajoute un numéroteur prédictif à mon compte"', false],
            ['Appointment Booking AI','IA de prise de rendez-vous','AI-powered scheduling with calendar sync and reminders — $49/mo.','Planification alimentée par IA avec synchronisation de calendrier et rappels — 49 $/mois.','"Set up appointment booking for my clinic"','"Configure la prise de rendez-vous pour ma clinique"', false],
            ['Survey & Feedback AI','IA de sondage','Post-call surveys with NPS tracking and analytics — $39/mo.','Sondages post-appel avec suivi NPS et analyses — 39 $/mois.','"Add customer satisfaction surveys to my calls"','"Ajoute des sondages de satisfaction client à mes appels"', false],
            ['Office Suite','Suite téléphonique','Business phone system: voicemail, auto-attendant, ring groups, conference — from $25/mo.','Système téléphonique professionnel : messagerie vocale, standard automatique, groupes d\'appel — à partir de 25 $/mois.','"Set up an office phone system"','"Configure un système téléphonique de bureau"', false],
            ['Virtual Receptionist','Réceptionniste virtuel','AI receptionist that answers, transfers, takes messages, and schedules — $35/mo.','Réceptionniste IA qui répond, transfère, prend des messages et planifie — 35 $/mois.','"I need a virtual receptionist for after-hours"','"J\'ai besoin d\'un réceptionniste virtuel pour les heures creuses"', false],
        ]
    ],

    // ─── VOICE PRODUCTS & INDUSTRY AI — Full product catalog ───
    [
        'icon' => 'fas fa-store', 'color' => 'gradient', 'key' => 'alf_cat_voiceproducts', 'count' => 20,
        'tools' => [
            ['Browse Voice Products','Parcourir les produits vocaux','View all 52 voice products across 8 categories with pricing and features.','Consultez les 52 produits vocaux dans 8 catégories avec tarifs et fonctionnalités.','"Show me all voice products"','"Montre-moi tous les produits vocaux"', false],
            ['Voice Recommendation','Recommandation vocale','Get a personalized product recommendation based on your industry and needs.','Obtenez une recommandation de produit personnalisée basée sur votre industrie et vos besoins.','"I\'m a dentist, what do you recommend?"','"Je suis dentiste, que recommandez-vous ?"', false],
            ['Order Voice Product','Commander un produit','Purchase any voice product directly through Alfred.','Achetez n\'importe quel produit vocal directement via Alfred.','"Order the Legal AI Agent plan"','"Commande le plan Agent IA Juridique"', false],
            ['Legal AI Agent','Agent IA juridique','AI for law firms: intake calls, appointment booking, case updates — $149/mo.','IA pour cabinets d\'avocats : appels d\'admission, rendez-vous, mises à jour de dossiers — 149 $/mois.','"Set up a legal AI agent for my law firm"','"Configure un agent IA juridique pour mon cabinet"', false],
            ['Real Estate AI','IA immobilier','Lead qualification, showing scheduler, property information — $99/mo.','Qualification de leads, planificateur de visites, informations sur les propriétés — 99 $/mois.','"I need an AI agent for real estate leads"','"J\'ai besoin d\'un agent IA pour les leads immobiliers"', false],
            ['Medical & Dental AI','IA médical et dentaire','HIPAA compliant: appointments, reminders, prescription refills — $149/mo.','Conforme HIPAA : rendez-vous, rappels, renouvellements d\'ordonnances — 149 $/mois.','"Set up a medical AI for my clinic"','"Configure une IA médicale pour ma clinique"', false],
            ['Restaurant AI','IA restauration','Reservations, takeout orders, menu info, hours — $69/mo.','Réservations, commandes à emporter, menu, heures — 69 $/mois.','"I need an AI to take restaurant reservations"','"J\'ai besoin d\'une IA pour les réservations de restaurant"', false],
            ['Automotive AI','IA automobile','Service appointments, parts inquiries, test drives — $99/mo.','Rendez-vous de service, demandes de pièces, essais routiers — 99 $/mois.','"Set up AI for my auto dealership"','"Configure l\'IA pour ma concession automobile"', false],
            ['Insurance AI','IA assurance','Quote generation, claims status, policy info — $129/mo.','Génération de devis, statut des réclamations, infos de police — 129 $/mois.','"I need an AI for insurance inquiries"','"J\'ai besoin d\'une IA pour les demandes d\'assurance"', false],
            ['Education AI','IA éducation','Enrollment info, campus tours, financial aid — $79/mo.','Informations d\'inscription, visites du campus, aide financière — 79 $/mois.','"Set up an AI for our university"','"Configure une IA pour notre université"', false],
            ['Hotel & Hospitality AI','IA hôtellerie','Reservations, concierge, room service, checkout — $89/mo.','Réservations, concierge, service de chambre, départ — 89 $/mois.','"I need AI for hotel reservations"','"J\'ai besoin d\'IA pour les réservations d\'hôtel"', false],
            ['E-Commerce AI','IA e-commerce','Order status, returns, product recommendations — $69/mo.','Statut des commandes, retours, recommandations de produits — 69 $/mois.','"Add AI support for my online store"','"Ajoute un support IA pour ma boutique en ligne"', false],
            ['Financial Services AI','IA services financiers','Account inquiries, transfers, fraud alerts — $149/mo.','Demandes de compte, transferts, alertes fraude — 149 $/mois.','"Set up AI for our bank"','"Configure l\'IA pour notre banque"', false],
            ['Government AI','IA gouvernement','Citizen services, permit status, public info — $99/mo.','Services aux citoyens, statut des permis, info publique — 99 $/mois.','"Configure AI for citizen services"','"Configure l\'IA pour les services aux citoyens"', false],
            ['Nonprofit AI','IA organisme sans but lucratif','Donations, volunteer coordination, event info — $49/mo.','Dons, coordination bénévole, info événements — 49 $/mois.','"Set up AI for our nonprofit"','"Configure l\'IA pour notre organisme"', false],
            ['Call Recording Add-on','Ajout enregistrement','Record all calls with 90-day storage and download — $10/mo.','Enregistrez tous les appels avec stockage 90 jours et téléchargement — 10 $/mois.','"Add call recording to my account"','"Ajoute l\'enregistrement d\'appels à mon compte"', false],
            ['CRM Integration','Intégration CRM','Sync with Salesforce, HubSpot, Zoho, Pipedrive — $15/mo.','Synchronisez avec Salesforce, HubSpot, Zoho, Pipedrive — 15 $/mois.','"Connect my voice system to HubSpot"','"Connecte mon système vocal à HubSpot"', false],
            ['HIPAA Compliance','Conformité HIPAA','BAA, encrypted storage, audit logging for healthcare — $30/mo.','BAE, stockage chiffré, journalisation d\'audit pour la santé — 30 $/mois.','"Add HIPAA compliance to my account"','"Ajoute la conformité HIPAA à mon compte"', false],
            ['White Label','Marque blanche','Custom branding, your domain, no GoSiteMe branding — $50/mo.','Marque personnalisée, votre domaine, pas de marque GoSiteMe — 50 $/mois.','"White label my voice system"','"Met mon système vocal en marque blanche"', false],
            ['Custom Model Training','Entraînement de modèle','Fine-tune AI on your data with custom vocabulary — $200/mo.','Affinez l\'IA sur vos données avec vocabulaire personnalisé — 200 $/mois.','"Train the AI on my company\'s FAQ"','"Entraîne l\'IA sur la FAQ de mon entreprise"', false],
        ]
    ],
];
