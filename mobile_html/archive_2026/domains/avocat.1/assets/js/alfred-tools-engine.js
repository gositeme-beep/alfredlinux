/**
 * GoSiteMe Alfred Tools Engine v2.0
 * Extracted from alfred-tools.php inline JS
 * Features: Category cards, tool panels, search, animated counter, Stripe checkout
 */
(function(){
"use strict";

/* ===== CATEGORY & TOOL DATA ===== */
var categories = [
  {
    id:"legal", name:"Legal & Compliance", count:24, icon:"fa-scale-balanced",
    desc:"Draft motions, summarize case law, track deadlines, generate contracts, and ensure regulatory compliance.",
    tools:[
      {n:"Contract Drafter",d:"Generate legally-sound contracts from plain-language descriptions."},
      {n:"Case Law Summarizer",d:"Summarize court decisions and extract key holdings."},
      {n:"Motion Generator",d:"Draft court motions with proper formatting and citations."},
      {n:"Compliance Checker",d:"Scan documents for regulatory compliance issues."},
      {n:"Legal Research Assistant",d:"Search and analyze legal databases and statutes."},
      {n:"NDA Builder",d:"Create customized non-disclosure agreements in minutes."},
      {n:"Deadline Tracker",d:"Manage court deadlines, filing dates, and statute of limitations."},
      {n:"Demand Letter Writer",d:"Generate professional demand letters from fact summaries."},
      {n:"Lease Agreement Generator",d:"Draft residential and commercial lease agreements."},
      {n:"Privacy Policy Generator",d:"Create GDPR, CCPA, and PIPEDA compliant policies."},
      {n:"Terms of Service Builder",d:"Generate TOS documents tailored to your business."},
      {n:"IP Rights Analyzer",d:"Evaluate intellectual property claims and protections."},
      {n:"Settlement Calculator",d:"Estimate fair settlement values based on case factors."},
      {n:"Court Filing Formatter",d:"Format documents per court-specific filing requirements."},
      {n:"Evidence Organizer",d:"Catalog and index evidence for case preparation."},
      {n:"Deposition Prep",d:"Generate deposition questions and preparation outlines."},
      {n:"Liability Assessment",d:"Analyze potential liability exposure in disputes."},
      {n:"Regulatory Monitor",d:"Track regulatory changes relevant to your industry."},
      {n:"Power of Attorney Drafter",d:"Create POA documents with jurisdiction-specific requirements."},
      {n:"Arbitration Clause Builder",d:"Draft enforceable arbitration clauses and agreements."},
      {n:"Document Redactor",d:"Automatically identify and redact sensitive information."},
      {n:"Billing & Time Tracker",d:"Track billable hours and generate invoicing reports."},
      {n:"Client Intake Automator",d:"Streamline client onboarding with smart intake forms."},
      {n:"Legal Citation Checker",d:"Verify and format legal citations across documents."}
    ]
  },
  {
    id:"k12", name:"Students K-12", count:14, icon:"fa-school",
    desc:"AI tutoring, homework help, reading comprehension, math problem-solving, and safe learning environments.",
    tools:[
      {n:"Math Tutor",d:"Step-by-step math problem solving for all grade levels."},
      {n:"Reading Companion",d:"Interactive reading comprehension exercises and quizzes."},
      {n:"Science Explorer",d:"Virtual lab experiments and science concept explanations."},
      {n:"Essay Helper",d:"Guided essay writing with structure and grammar support."},
      {n:"Vocabulary Builder",d:"Contextual vocabulary learning with spaced repetition."},
      {n:"History Timeline",d:"Interactive historical timelines with key event summaries."},
      {n:"Spelling Coach",d:"Personalized spelling practice with pronunciation guides."},
      {n:"Geography Quiz Master",d:"Interactive geography quizzes with maps and visuals."},
      {n:"Art & Creativity Studio",d:"AI-assisted drawing prompts and art history lessons."},
      {n:"Music Theory Basics",d:"Learn notes, scales, and rhythms interactively."},
      {n:"Study Planner",d:"Create personalized study schedules and track progress."},
      {n:"Homework Checker",d:"Verify answers and explain where mistakes were made."},
      {n:"Book Report Assistant",d:"Generate outlines and analysis for book reports."},
      {n:"Safe Search Filter",d:"Age-appropriate content filtering for all queries."}
    ]
  },
  {
    id:"university", name:"University", count:15, icon:"fa-graduation-cap",
    desc:"Research assistance, thesis writing, citation management, peer review, and academic collaboration tools.",
    tools:[
      {n:"Thesis Advisor",d:"Structure and outline thesis papers with methodology guidance."},
      {n:"Citation Manager",d:"Auto-format citations in APA, MLA, Chicago, and more."},
      {n:"Research Paper Analyzer",d:"Summarize and critique academic papers."},
      {n:"Plagiarism Checker",d:"Detect potential plagiarism and suggest rewrites."},
      {n:"Statistics Assistant",d:"Run statistical analyses and interpret results."},
      {n:"Literature Review Builder",d:"Compile and synthesize literature review sections."},
      {n:"Lab Report Generator",d:"Structure lab reports with proper scientific formatting."},
      {n:"Presentation Coach",d:"Create academic presentations and practice delivery."},
      {n:"Grant Proposal Writer",d:"Draft research grant proposals with budget templates."},
      {n:"Peer Review Helper",d:"Generate constructive peer review feedback."},
      {n:"Academic Calendar",d:"Track assignments, exams, and academic deadlines."},
      {n:"Study Group Coordinator",d:"Organize study sessions and share resources."},
      {n:"Foreign Language Tutor",d:"Practice language skills with AI conversation partners."},
      {n:"Exam Prep Engine",d:"Generate practice tests from course materials."},
      {n:"Course Note Synthesizer",d:"Condense lecture notes into study-ready summaries."}
    ]
  },
  {
    id:"professionals", name:"Professionals", count:15, icon:"fa-briefcase",
    desc:"Productivity tools for career growth, project management, communication, and professional development.",
    tools:[
      {n:"Resume Builder",d:"Create ATS-optimized resumes from your experience."},
      {n:"Cover Letter Writer",d:"Generate tailored cover letters for each application."},
      {n:"Email Composer",d:"Draft professional emails with appropriate tone and formatting."},
      {n:"Meeting Summarizer",d:"Transcribe and summarize meeting notes and action items."},
      {n:"Project Planner",d:"Create Gantt charts, milestones, and task assignments."},
      {n:"Presentation Maker",d:"Design slide decks with data visualization."},
      {n:"LinkedIn Optimizer",d:"Optimize your LinkedIn profile for maximum visibility."},
      {n:"Salary Negotiator",d:"Research market rates and prepare negotiation scripts."},
      {n:"Report Generator",d:"Transform data into professional reports and dashboards."},
      {n:"Time Management Coach",d:"Analyze schedules and suggest productivity improvements."},
      {n:"Networking Assistant",d:"Draft outreach messages and track professional contacts."},
      {n:"Interview Prep",d:"Practice interviews with AI feedback on responses."},
      {n:"Goal Tracker",d:"Set, track, and visualize professional development goals."},
      {n:"Delegation Advisor",d:"Analyze tasks and suggest optimal delegation strategies."},
      {n:"Work-Life Balance Analyzer",d:"Track hours and suggest healthy boundaries."}
    ]
  },
  {
    id:"smallbiz", name:"Small Business", count:15, icon:"fa-store",
    desc:"Business planning, invoicing, inventory, marketing, customer management, and financial forecasting.",
    tools:[
      {n:"Business Plan Generator",d:"Create comprehensive business plans with financial projections."},
      {n:"Invoice Creator",d:"Generate and send professional invoices automatically."},
      {n:"Bookkeeping Assistant",d:"Categorize expenses and prepare financial statements."},
      {n:"Marketing Campaign Builder",d:"Design multi-channel marketing campaigns."},
      {n:"Social Media Scheduler",d:"Plan and auto-publish across social platforms."},
      {n:"Customer CRM",d:"Track customer interactions and manage relationships."},
      {n:"Inventory Manager",d:"Monitor stock levels and automate reorder points."},
      {n:"Competitor Analyzer",d:"Research and benchmark against competitors."},
      {n:"Tax Prep Assistant",d:"Organize deductions and prepare tax documents."},
      {n:"Cash Flow Forecaster",d:"Predict revenue and expenses with AI modeling."},
      {n:"Employee Scheduler",d:"Create and manage employee shift schedules."},
      {n:"Review Responder",d:"Draft professional responses to online reviews."},
      {n:"Local SEO Optimizer",d:"Improve local search visibility and GMB listings."},
      {n:"Product Pricing Tool",d:"Analyze market data to set competitive pricing."},
      {n:"Vendor Negotiation Coach",d:"Prepare for vendor negotiations with data-driven strategies."}
    ]
  },
  {
    id:"creators", name:"Content Creators", count:14, icon:"fa-video",
    desc:"Content planning, scriptwriting, thumbnail design, SEO optimization, and audience growth strategies.",
    tools:[
      {n:"Blog Post Writer",d:"Generate SEO-optimized blog posts with keyword targeting."},
      {n:"Video Script Generator",d:"Write engaging video scripts with hooks and CTAs."},
      {n:"Thumbnail Concept Creator",d:"Design eye-catching thumbnail concepts with text overlays."},
      {n:"Content Calendar",d:"Plan and schedule content across platforms."},
      {n:"Hashtag Research",d:"Find trending and niche hashtags for maximum reach."},
      {n:"Audience Analyzer",d:"Understand audience demographics and preferences."},
      {n:"Podcast Show Notes",d:"Generate structured show notes and timestamps."},
      {n:"Caption Writer",d:"Create engaging social media captions for any platform."},
      {n:"SEO Keyword Planner",d:"Research keywords with search volume and difficulty data."},
      {n:"A/B Title Tester",d:"Generate and compare title variants for click-through rates."},
      {n:"Newsletter Builder",d:"Draft email newsletters with personalization."},
      {n:"Repurpose Engine",d:"Transform one piece of content into multi-platform formats."},
      {n:"Collaboration Finder",d:"Find and evaluate potential brand collaborations."},
      {n:"Analytics Interpreter",d:"Translate analytics data into actionable insights."}
    ]
  },
  {
    id:"healthcare", name:"Healthcare", count:12, icon:"fa-heartbeat",
    desc:"Patient documentation, symptom analysis, medication info, wellness tracking, and health education.",
    tools:[
      {n:"Symptom Analyzer",d:"Document and analyze patient symptoms for clinical review."},
      {n:"Medication Info Lookup",d:"Comprehensive drug information, interactions, and dosages."},
      {n:"Patient Note Generator",d:"Streamline clinical documentation with structured notes."},
      {n:"Health Education Builder",d:"Create patient education materials and handouts."},
      {n:"Wellness Tracker",d:"Monitor health metrics and generate wellness reports."},
      {n:"Diet Plan Creator",d:"Design personalized nutrition plans based on health goals."},
      {n:"Exercise Routine Builder",d:"Create customized workout plans for patients."},
      {n:"Mental Health Check-In",d:"Guided mood assessments and wellness journaling."},
      {n:"Appointment Scheduler",d:"Manage patient appointments and send reminders."},
      {n:"Insurance Code Assistant",d:"Look up ICD-10 and CPT codes for billing."},
      {n:"Lab Result Interpreter",d:"Help patients understand lab result values and ranges."},
      {n:"Telehealth Session Prep",d:"Prepare intake forms and session summaries."}
    ]
  },
  {
    id:"teachers", name:"Teachers & Educators", count:15, icon:"fa-chalkboard-teacher",
    desc:"Lesson planning, assignment creation, grading rubrics, student progress tracking, and classroom management.",
    tools:[
      {n:"Lesson Plan Creator",d:"Generate standards-aligned lesson plans for any subject."},
      {n:"Quiz & Test Generator",d:"Create assessments with multiple question formats."},
      {n:"Grading Rubric Builder",d:"Design detailed grading rubrics for assignments."},
      {n:"Student Progress Tracker",d:"Monitor individual and class-wide academic progress."},
      {n:"Worksheet Generator",d:"Create printable worksheets customized to skill levels."},
      {n:"Differentiation Planner",d:"Adapt lessons for diverse learning needs."},
      {n:"Parent Communication",d:"Draft parent newsletters and progress reports."},
      {n:"IEP Goal Writer",d:"Create measurable IEP goals aligned to standards."},
      {n:"Classroom Seating Planner",d:"Optimize seating arrangements for learning."},
      {n:"Substitute Teacher Prep",d:"Generate comprehensive substitute teacher packets."},
      {n:"Field Trip Organizer",d:"Plan field trips with permission forms and itineraries."},
      {n:"Behavior Tracker",d:"Log and analyze student behavior patterns."},
      {n:"Professional Development Log",d:"Track PD hours and learning objectives."},
      {n:"Curriculum Mapper",d:"Align curriculum across grade levels and standards."},
      {n:"Report Card Comment Writer",d:"Generate personalized, constructive report card comments."}
    ]
  },
  {
    id:"voice", name:"Voice Conferencing", count:10, icon:"fa-phone-volume",
    desc:"AI-powered voice calls, conference management, real-time transcription, and automated follow-ups.",
    tools:[
      {n:"Conference Scheduler",d:"Set up multi-party calls with calendar integration."},
      {n:"Real-Time Transcription",d:"Live speech-to-text during voice calls."},
      {n:"Call Summarizer",d:"Generate post-call summaries with action items."},
      {n:"Voice Command Hub",d:"Central dashboard for all voice-activated tool access."},
      {n:"Outbound Dialer",d:"Automated outbound calling with customizable scripts."},
      {n:"Call Queue Manager",d:"Manage hold queues and callback scheduling."},
      {n:"Voice Analytics",d:"Analyze call patterns, sentiment, and quality metrics."},
      {n:"IVR Builder",d:"Design interactive voice response menus."},
      {n:"Call Recording Manager",d:"Record, store, and search through call recordings."},
      {n:"Multi-Language Translator",d:"Real-time voice translation during calls."}
    ]
  },
  {
    id:"realestate", name:"Real Estate", count:10, icon:"fa-house-chimney",
    desc:"Property listings, market analysis, CMA reports, lead management, and virtual tour coordination.",
    tools:[
      {n:"Listing Description Writer",d:"Generate compelling property listing descriptions."},
      {n:"CMA Report Generator",d:"Create comparative market analysis reports."},
      {n:"Lead Capture Manager",d:"Track and nurture real estate leads."},
      {n:"Open House Planner",d:"Organize open houses with marketing materials."},
      {n:"Mortgage Calculator",d:"Calculate payments with various loan scenarios."},
      {n:"Property Valuation Estimator",d:"AI-driven property value estimates."},
      {n:"Client Follow-Up Automator",d:"Schedule and send personalized follow-up messages."},
      {n:"Market Trend Analyzer",d:"Track local and national market trends."},
      {n:"Offer Letter Drafter",d:"Generate real estate offer letters and counteroffers."},
      {n:"Virtual Tour Coordinator",d:"Schedule and manage 3D property tours."}
    ]
  },
  {
    id:"freelancers", name:"Freelancers", count:9, icon:"fa-laptop-code",
    desc:"Proposals, contracts, time tracking, portfolio management, and client communication tools.",
    tools:[
      {n:"Proposal Generator",d:"Create winning client proposals with project scopes."},
      {n:"Freelance Contract Builder",d:"Draft project-specific contracts with payment terms."},
      {n:"Time & Expense Tracker",d:"Log hours and expenses per client project."},
      {n:"Portfolio Builder",d:"Showcase work with a polished online portfolio."},
      {n:"Client Onboarding Kit",d:"Automate client welcome packets and questionnaires."},
      {n:"Rate Calculator",d:"Calculate competitive freelance rates by market."},
      {n:"Project Milestone Tracker",d:"Track deliverables and milestone payments."},
      {n:"Invoice & Payment Hub",d:"Send invoices and track payment statuses."},
      {n:"Scope Creep Detector",d:"Flag out-of-scope requests and suggest change orders."}
    ]
  },
  {
    id:"seniors", name:"Seniors", count:11, icon:"fa-user-shield",
    desc:"Simplified interfaces, health reminders, family photo sharing, simplified tech support, and safety tools.",
    tools:[
      {n:"Medication Reminder",d:"Set voice-activated medication schedules and alerts."},
      {n:"Simplified Email",d:"Large-text, easy-use email composition and reading."},
      {n:"Video Call Helper",d:"One-touch video calling with family and friends."},
      {n:"Health Appointment Tracker",d:"Track doctor visits, prescriptions, and health notes."},
      {n:"Photo Sharing Hub",d:"Share and receive family photos with simple controls."},
      {n:"Voice Assistant Companion",d:"Conversational AI for daily tasks and reminders."},
      {n:"Emergency Contact Dialer",d:"One-button emergency calling with location sharing."},
      {n:"News Reader",d:"Curated news with adjustable text size and audio."},
      {n:"Brain Exercise Games",d:"Cognitive exercises designed for mental sharpness."},
      {n:"Tech Support Guide",d:"Step-by-step help for common technology questions."},
      {n:"Daily Routine Manager",d:"Guided daily routines with gentle voice reminders."}
    ]
  },
  {
    id:"parents", name:"Parents & Family", count:8, icon:"fa-people-roof",
    desc:"Family scheduling, parental controls, meal planning, homework help, and activity coordination.",
    tools:[
      {n:"Family Calendar",d:"Coordinate family schedules and share events."},
      {n:"Meal Planner",d:"Weekly meal plans with grocery lists and recipes."},
      {n:"Chore Tracker",d:"Assign and track family chores with rewards."},
      {n:"Screen Time Manager",d:"Set and monitor device usage limits."},
      {n:"Homework Helper Dashboard",d:"Overview of children's assignments and due dates."},
      {n:"Activity Finder",d:"Discover local family-friendly events and activities."},
      {n:"Budget Planner",d:"Track family expenses and savings goals."},
      {n:"Bedtime Story Generator",d:"Create personalized bedtime stories for children."}
    ]
  },
  {
    id:"nonprofits", name:"Non-Profits", count:6, icon:"fa-hand-holding-heart",
    desc:"Grant writing, donor management, volunteer coordination, campaign tracking, and impact reporting.",
    tools:[
      {n:"Grant Writer",d:"Draft compelling grant proposals with budget narratives."},
      {n:"Donor Management CRM",d:"Track donations, donors, and engagement history."},
      {n:"Volunteer Coordinator",d:"Schedule volunteers and manage sign-ups."},
      {n:"Impact Report Generator",d:"Create data-driven impact reports for stakeholders."},
      {n:"Fundraising Campaign Planner",d:"Design and execute fundraising campaigns."},
      {n:"Non-Profit Compliance Checker",d:"Ensure filings and compliance with regulations."}
    ]
  },
  {
    id:"gamification", name:"Gamification", count:6, icon:"fa-trophy",
    desc:"Achievement systems, progress tracking, rewards engines, leaderboards, and engagement mechanics.",
    tools:[
      {n:"Achievement System Builder",d:"Design badge and achievement systems for any app."},
      {n:"Progress Tracker",d:"Visual progress bars and milestone celebrations."},
      {n:"Leaderboard Manager",d:"Create and manage competitive leaderboards."},
      {n:"Rewards Engine",d:"Configure point systems and reward redemptions."},
      {n:"Challenge Creator",d:"Design daily/weekly challenges for user engagement."},
      {n:"Engagement Analytics",d:"Track gamification metrics and user behavior."}
    ]
  },
  {
    id:"marketplace", name:"Marketplace", count:6, icon:"fa-cart-shopping",
    desc:"E-commerce tools for product listings, pricing, order management, and customer engagement.",
    tools:[
      {n:"Product Listing Optimizer",d:"Create SEO-optimized product listings with AI copy."},
      {n:"Dynamic Pricing Engine",d:"Adjust prices based on demand and competition."},
      {n:"Order Fulfillment Tracker",d:"Manage orders from purchase to delivery."},
      {n:"Customer Review Analyzer",d:"Extract insights from customer reviews."},
      {n:"Inventory Sync Manager",d:"Sync inventory across multiple sales channels."},
      {n:"Abandoned Cart Recovery",d:"Automated emails and strategies to recover lost sales."}
    ]
  },
  {
    id:"consciousness", name:"Consciousness & AI", count:12, icon:"fa-brain",
    desc:"AI ethics, consciousness exploration, philosophical reasoning, creative AI generation, and self-awareness tools.",
    tools:[
      {n:"AI Ethics Analyzer",d:"Evaluate AI decisions for ethical implications."},
      {n:"Consciousness Journal",d:"Guided self-reflection and mindfulness exercises."},
      {n:"Philosophical Debate Partner",d:"Engage in structured philosophical discussions."},
      {n:"Creative AI Generator",d:"Generate art, music concepts, and creative writing."},
      {n:"Meditation Guide",d:"Personalized meditation sessions with voice guidance."},
      {n:"Dream Interpreter",d:"AI-powered dream analysis and symbolism exploration."},
      {n:"Thought Experiment Lab",d:"Explore classic and novel thought experiments."},
      {n:"Cognitive Bias Detector",d:"Identify cognitive biases in reasoning and decisions."},
      {n:"AI Transparency Report",d:"Understand how AI reaches its conclusions."},
      {n:"Emotion Pattern Tracker",d:"Track emotional patterns and identify triggers."},
      {n:"Worldview Explorer",d:"Compare philosophical and cultural worldviews."},
      {n:"Sentience Debate Forum",d:"Explore arguments around AI consciousness."}
    ]
  },
  {
    id:"fleet", name:"Fleet Orchestration", count:35, icon:"fa-network-wired",
    desc:"Multi-agent coordination, task distribution, resource allocation, fleet monitoring, and intelligent scaling.",
    tools:[
      {n:"Agent Spawner",d:"Dynamically create and configure AI agent instances."},
      {n:"Task Distributor",d:"Intelligently assign tasks across agent pools."},
      {n:"Load Balancer",d:"Distribute workloads evenly across fleet resources."},
      {n:"Fleet Monitor Dashboard",d:"Real-time visibility into all active agents."},
      {n:"Agent Health Checker",d:"Monitor agent performance and auto-restart failures."},
      {n:"Resource Allocator",d:"Dynamically allocate compute resources based on demand."},
      {n:"Priority Queue Manager",d:"Manage task priorities across the agent fleet."},
      {n:"Auto-Scaler",d:"Scale agents up or down based on workload."},
      {n:"Inter-Agent Messenger",d:"Secure communication channel between agents."},
      {n:"Workflow Orchestrator",d:"Design and run multi-step agent workflows."},
      {n:"Rollback Manager",d:"Safely revert agent actions when issues arise."},
      {n:"Agent Version Controller",d:"Manage and deploy agent software versions."},
      {n:"Fleet Analytics",d:"Track fleet performance metrics and trends."},
      {n:"Anomaly Detector",d:"Identify unusual patterns in fleet behavior."},
      {n:"Cost Optimizer",d:"Minimize fleet operating costs without sacrificing performance."},
      {n:"Dependency Resolver",d:"Manage inter-task dependencies in workflows."},
      {n:"Parallel Executor",d:"Run independent tasks simultaneously for speed."},
      {n:"Rate Limiter",d:"Control request rates to prevent overload."},
      {n:"Circuit Breaker",d:"Automatically halt failing processes to prevent cascades."},
      {n:"Event Stream Processor",d:"Process real-time event streams from agents."},
      {n:"Log Aggregator",d:"Centralize and search logs from all fleet agents."},
      {n:"Fleet Configuration Manager",d:"Centrally manage configuration across all agents."},
      {n:"Canary Deployer",d:"Gradual rollout of changes with automatic rollback."},
      {n:"Agent Sandbox",d:"Test agents in isolated environments before deployment."},
      {n:"Multi-Tenant Router",d:"Route requests to appropriate tenant-specific agents."},
      {n:"Dead Letter Queue",d:"Handle failed messages and retry logic."},
      {n:"Cron Scheduler",d:"Schedule recurring tasks across the fleet."},
      {n:"Fleet Audit Logger",d:"Immutable audit trail of all fleet operations."},
      {n:"Blue-Green Deployer",d:"Zero-downtime deployments with instant switchover."},
      {n:"Chaos Tester",d:"Inject failures to test fleet resilience."},
      {n:"Agent Permissions Manager",d:"Control what each agent can access and modify."},
      {n:"Fleet Backup Manager",d:"Automated fleet state and data backups."},
      {n:"Heartbeat Monitor",d:"Track agent liveness with configurable intervals."},
      {n:"Service Mesh Router",d:"Intelligent routing between fleet microservices."},
      {n:"Fleet Incident Manager",d:"Track, escalate, and resolve fleet incidents."}
    ]
  },
  {
    id:"servers", name:"Server Management", count:108, icon:"fa-server",
    desc:"Full server lifecycle management: provisioning, monitoring, maintenance, backups, and performance optimization.",
    tools:[
      {n:"Server Provisioner",d:"Spin up and configure new servers automatically."},
      {n:"Uptime Monitor",d:"24/7 server uptime monitoring with instant alerts."},
      {n:"Backup Manager",d:"Automated scheduled backups with retention policies."},
      {n:"SSL Certificate Manager",d:"Install, renew, and manage SSL certificates."},
      {n:"DNS Manager",d:"Configure DNS records and manage zones."},
      {n:"Firewall Configurator",d:"Set up and manage firewall rules."},
      {n:"Performance Monitor",d:"Track CPU, RAM, disk I/O, and network metrics."},
      {n:"Apache/Nginx Tuner",d:"Optimize web server configurations for speed."},
      {n:"PHP Version Manager",d:"Switch PHP versions and manage extensions."},
      {n:"MySQL Optimizer",d:"Tune database queries, indexes, and configuration."},
      {n:"Cron Job Manager",d:"Create, edit, and monitor scheduled cron tasks."},
      {n:"Log Analyzer",d:"Parse and analyze server access and error logs."},
      {n:"Mail Server Manager",d:"Configure and troubleshoot email services."},
      {n:"FTP Account Manager",d:"Create and manage FTP/SFTP accounts."},
      {n:"Resource Usage Reporter",d:"Generate reports on server resource consumption."},
      {n:"Process Manager",d:"View and manage running server processes."},
      {n:"Server Migration Tool",d:"Migrate sites and data between servers seamlessly."},
      {n:"WHM/cPanel Automator",d:"Automate common WHM and cPanel operations."},
      {n:"IP Blocklist Manager",d:"Block/unblock IPs and manage access lists."},
      {n:"Service Restart Manager",d:"Safely restart services with dependency awareness."},
      {n:"Disk Space Analyzer",d:"Find large files and clean up disk space."},
      {n:"ModSecurity Tuner",d:"Fine-tune web application firewall rules."},
      {n:"Connection Pool Manager",d:"Optimize database connection pools."},
      {n:"Cache Manager",d:"Configure Redis, Memcached, and Varnish caches."},
      {n:"Load Test Runner",d:"Run stress tests and analyze server capacity."},
      {n:"Server Hardening Wizard",d:"Apply security best practices automatically."},
      {n:"Kernel Update Manager",d:"Safely manage and apply kernel updates."},
      {n:"Network Diagnostics",d:"Run traceroutes, ping tests, and DNS lookups."},
      {n:"Container Manager",d:"Manage Docker containers and images."},
      {n:"Swap Space Manager",d:"Configure and optimize swap memory."},
      {n:"Auto-Healing Monitor",d:"Automatically restart failed services on detection."},
      {n:"PHP-FPM Tuner",d:"Optimize PHP-FPM pool settings for performance."},
      {n:"HTTP/2 & HTTP/3 Configurator",d:"Enable and tune modern HTTP protocols."},
      {n:"Gzip/Brotli Compressor",d:"Configure response compression settings."},
      {n:"CDN Integrator",d:"Set up and manage CDN integrations."},
      {n:"Database Backup Scheduler",d:"Schedule and manage database-specific backups."},
      {n:"User Account Manager",d:"Create, modify, and audit server user accounts."},
      {n:"SSH Key Manager",d:"Generate, deploy, and rotate SSH keys."},
      {n:"Server Inventory Tracker",d:"Track all servers, specs, and configurations."},
      {n:"Bandwidth Monitor",d:"Track bandwidth usage with per-domain breakdown."},
      {n:"inode Monitor",d:"Monitor inode usage and find cleanup opportunities."},
      {n:"WordPress Manager",d:"Install, update, and manage WordPress instances."},
      {n:"LiteSpeed Tuner",d:"Optimize LiteSpeed web server and cache."},
      {n:"Email Deliverability Checker",d:"Test SPF, DKIM, DMARC, and inbox placement."},
      {n:"Malware Scanner",d:"Scan for and remove malicious files."},
      {n:"Server Snapshot Manager",d:"Create and restore server snapshots."},
      {n:"API Rate Monitor",d:"Track API usage rates and prevent abuse."},
      {n:"Reverse Proxy Configurator",d:"Set up and manage HAProxy/Nginx reverse proxies."},
      {n:"Web Application Firewall",d:"Deploy and tune WAF rulesets."},
      {n:"TLS Certificate Scanner",d:"Monitor TLS certificate expiry across domains."},
      /* Additional tools to reach 100+ */
      {n:"Automated Patch Manager",d:"Apply security patches across server fleet."},
      {n:"RAID Monitor",d:"Monitor RAID array health and predict failures."},
      {n:"NFS Share Manager",d:"Configure and manage network file shares."},
      {n:"LDAP Integration",d:"Set up and manage directory services."},
      {n:"Time Sync Manager",d:"Configure NTP and ensure accurate server time."},
      {n:"Environment Variable Manager",d:"Manage server environment configurations."},
      {n:"Service Dependency Mapper",d:"Visualize service dependencies and impact."},
      {n:"Quota Manager",d:"Set and enforce disk and resource quotas."},
      {n:"Server Compliance Scanner",d:"Check servers against security benchmarks."},
      {n:"Network Interface Manager",d:"Configure network interfaces and bonding."},
      {n:"Memory Leak Detector",d:"Identify and report memory leak patterns."},
      {n:"Boot Manager",d:"Configure bootloaders and startup sequences."},
      {n:"Archive Manager",d:"Compress, archive, and extract files efficiently."},
      {n:"System Update Scheduler",d:"Schedule and coordinate system updates."},
      {n:"Access Control List Manager",d:"Manage file and directory ACLs."},
      {n:"Custom Monitoring Dashboard",d:"Build personalized server monitoring views."},
      {n:"Port Scanner",d:"Scan and audit open ports for security."},
      {n:"Service Health Dashboard",d:"Aggregate health status of all services."},
      {n:"Automated Failover Manager",d:"Configure and test failover procedures."},
      {n:"VPN Server Manager",d:"Set up and manage VPN services."},
      {n:"Two-Factor Auth Manager",d:"Configure 2FA for server access."},
      {n:"Server Cost Calculator",d:"Track and optimize hosting costs."},
      {n:"Performance Benchmark Suite",d:"Run and compare server benchmarks."},
      {n:"Incident Postmortem Generator",d:"Create structured incident reports."},
      {n:"SLA Monitor",d:"Track uptime SLA compliance and reporting."},
      {n:"Custom Script Runner",d:"Execute and schedule custom maintenance scripts."},
      {n:"Multi-Server Command Runner",d:"Run commands across multiple servers."},
      {n:"Server Documentation Generator",d:"Auto-document server configurations."},
      {n:"Scheduled Maintenance Planner",d:"Plan and communicate maintenance windows."},
      {n:"Traffic Analyzer",d:"Deep analysis of server traffic patterns."},
      {n:"Error Rate Monitor",d:"Track 4xx/5xx error rates and trends."},
      {n:"Geographic Latency Tester",d:"Test response times from global locations."},
      {n:"Image Optimization Pipeline",d:"Automatically optimize images on upload."},
      {n:"Static Asset Manager",d:"Manage and version static file deployments."},
      {n:"Reverse DNS Manager",d:"Configure PTR records for mail delivery."},
      {n:"Server Temperature Monitor",d:"Track hardware temperature and cooling."},
      {n:"Power Usage Monitor",d:"Track and optimize server power consumption."},
      {n:"Server Lifecycle Manager",d:"Track server age, warranties, and refresh cycles."},
      {n:"Compliance Report Generator",d:"Generate regulatory compliance reports."},
      {n:"Custom Dashboard Builder",d:"Create personalized admin dashboards."},
      {n:"Multi-PHP Environment",d:"Run multiple PHP versions across domains."},
      {n:"Apache Module Manager",d:"Enable, disable, and configure Apache modules."},
      {n:"Nginx Config Validator",d:"Test and validate Nginx configurations."},
      {n:"Database Replication Monitor",d:"Monitor master-slave replication health."},
      {n:"Log Rotation Manager",d:"Configure and manage log rotation policies."},
      {n:"Server Changelog",d:"Track all changes made to server configurations."},
      {n:"IP Reputation Monitor",d:"Monitor server IP reputation and blocklists."},
      {n:"Temp File Cleaner",d:"Safely clean temporary files and sessions."},
      {n:"Connection Tracker",d:"Monitor active connections and identify abuse."},
      {n:"Hosting Account Migrator",d:"Migrate hosting accounts between servers."},
      {n:"Resource Limit Enforcer",d:"Set and enforce per-account resource limits."},
      {n:"MariaDB Optimizer",d:"Tune MariaDB performance and queries."},
      {n:"PostgreSQL Manager",d:"Manage PostgreSQL databases and configurations."},
      {n:"Redis Cache Manager",d:"Configure and monitor Redis instances."},
      {n:"Node.js App Manager",d:"Deploy and manage Node.js applications."},
      {n:"Python App Manager",d:"Deploy and manage Python web applications."},
      {n:"Crontab Validator",d:"Validate and test cron expressions."},
      {n:"Server Warranty Tracker",d:"Track hardware warranties and support contracts."},
      {n:"OS Version Manager",d:"Track and plan OS version upgrades."}
    ]
  },
  {
    id:"domains", name:"Domain Management", count:52, icon:"fa-globe",
    desc:"Domain registration, DNS configuration, WHOIS management, transfers, and bulk domain operations.",
    tools:[
      {n:"Domain Search",d:"Search and check domain name availability."},
      {n:"Bulk Domain Checker",d:"Check availability of multiple domains at once."},
      {n:"DNS Zone Editor",d:"Edit DNS records with validation and preview."},
      {n:"WHOIS Lookup",d:"Query domain registration information."},
      {n:"Domain Transfer Manager",d:"Initiate and track domain transfers."},
      {n:"Auto-Renewal Manager",d:"Configure auto-renewal for all domains."},
      {n:"Domain Privacy Manager",d:"Enable/disable WHOIS privacy protection."},
      {n:"Nameserver Configurator",d:"Set custom nameservers for domains."},
      {n:"Subdomain Manager",d:"Create and manage subdomains."},
      {n:"Domain Redirect Manager",d:"Set up URL forwards and redirects."},
      {n:"Email Routing Manager",d:"Configure MX records and email routing."},
      {n:"Domain Lock Manager",d:"Enable/disable domain transfer locks."},
      {n:"EPP Code Generator",d:"Generate transfer authorization codes."},
      {n:"Domain Expiry Monitor",d:"Track and alert on domain expiration dates."},
      {n:"Bulk DNS Updater",d:"Update DNS records across multiple domains."},
      {n:"Domain Portfolio Analyzer",d:"Evaluate and appraise domain portfolio value."},
      {n:"DNSSEC Manager",d:"Configure DNSSEC signing for enhanced security."},
      {n:"Domain Suggestion Engine",d:"Generate creative domain name ideas."},
      {n:"Parking Page Manager",d:"Configure domain parking pages."},
      {n:"Domain Auction Monitor",d:"Track domain auctions and set alerts."},
      {n:"TLD Comparison Tool",d:"Compare pricing and features across TLDs."},
      {n:"Domain History Checker",d:"Research the history of any domain."},
      {n:"Internationalized Domain Manager",d:"Manage IDN and non-ASCII domains."},
      {n:"CAA Record Manager",d:"Configure Certificate Authority Authorization records."},
      {n:"SPF Record Builder",d:"Create and validate SPF records."},
      {n:"DKIM Key Manager",d:"Generate and manage DKIM keys."},
      {n:"DMARC Policy Manager",d:"Configure and monitor DMARC policies."},
      {n:"Domain Contact Manager",d:"Update registrant contact information."},
      {n:"Registrar Comparison",d:"Compare registrar pricing and services."},
      {n:"Domain Brand Monitor",d:"Watch for domains similar to your brand."},
      {n:"Typosquat Detector",d:"Find typosquatting domains targeting your brand."},
      {n:"Domain API Manager",d:"Programmatic domain management via API."},
      {n:"Zone File Import/Export",d:"Import and export DNS zone files."},
      {n:"Glue Record Manager",d:"Configure glue records for custom nameservers."},
      {n:"Domain Status Checker",d:"Check domain status flags and holds."},
      {n:"Premium Domain Finder",d:"Search for premium domain deals."},
      {n:"Drop Catch Monitor",d:"Track expiring domains for registration."},
      {n:"Domain Value Estimator",d:"AI-powered domain valuation tool."},
      {n:"Multi-Domain SSL Manager",d:"Manage SSL across multiple domains."},
      {n:"Wildcard DNS Manager",d:"Configure wildcard DNS records."},
      {n:"Reverse IP Lookup",d:"Find all domains on a given IP address."},
      {n:"Domain Change Log",d:"Track all changes to domain settings."},
      {n:"Batch Domain Registration",d:"Register multiple domains simultaneously."},
      {n:"Domain Marketplace Lister",d:"List domains for sale on marketplaces."},
      {n:"WHOIS Alert System",d:"Monitor WHOIS changes for any domain."},
      {n:"DNS Propagation Checker",d:"Verify DNS propagation worldwide."},
      {n:"Custom DNS Template Manager",d:"Create reusable DNS record templates."},
      {n:"Domain Dispute Advisor",d:"Guidance for domain name disputes and UDRP."},
      {n:"Grace Period Monitor",d:"Track domain deletion grace periods."},
      {n:"Registry Lock Manager",d:"Apply registry-level domain locks."},
      {n:"Domain Analytics Dashboard",d:"Traffic and DNS query analytics per domain."},
      {n:"Zone Transfer Tester",d:"Test and secure zone transfers."}
    ]
  },
  {
    id:"security", name:"Security", count:32, icon:"fa-shield-halved",
    desc:"Threat detection, vulnerability scanning, DDoS protection, access control, and compliance monitoring.",
    tools:[
      {n:"Vulnerability Scanner",d:"Scan for CVEs and security weaknesses."},
      {n:"DDoS Protection Manager",d:"Configure and manage DDoS mitigation."},
      {n:"Intrusion Detection System",d:"Monitor for unauthorized access attempts."},
      {n:"Access Control Manager",d:"Manage user permissions and roles."},
      {n:"Security Audit Runner",d:"Run comprehensive security audits."},
      {n:"Penetration Test Advisor",d:"Guidance for conducting ethical pen tests."},
      {n:"SSL/TLS Security Grader",d:"Grade HTTPS implementation quality."},
      {n:"Brute Force Protector",d:"Detect and block brute force login attempts."},
      {n:"Malware Signature DB",d:"Maintain and update malware detection signatures."},
      {n:"Security Header Checker",d:"Audit and configure HTTP security headers."},
      {n:"Password Policy Enforcer",d:"Enforce strong password requirements."},
      {n:"Two-Factor Auth Manager",d:"Configure and manage 2FA across services."},
      {n:"IP Reputation Checker",d:"Check if IPs are blocklisted or flagged."},
      {n:"XSS Scanner",d:"Detect cross-site scripting vulnerabilities."},
      {n:"SQL Injection Tester",d:"Test for SQL injection vulnerabilities."},
      {n:"CSRF Protection Manager",d:"Implement and verify CSRF protections."},
      {n:"Security Log Monitor",d:"Real-time monitoring of security-related logs."},
      {n:"File Integrity Monitor",d:"Track unauthorized changes to critical files."},
      {n:"Encryption Manager",d:"Manage data encryption at rest and in transit."},
      {n:"Compliance Scanner",d:"Check against OWASP, PCI-DSS, and HIPAA standards."},
      {n:"Bot Detection System",d:"Identify and manage automated bot traffic."},
      {n:"Geo-Blocking Manager",d:"Restrict access by geographic location."},
      {n:"Session Security Manager",d:"Monitor and secure user sessions."},
      {n:"API Security Tester",d:"Test API endpoints for security flaws."},
      {n:"Certificate Transparency Monitor",d:"Monitor CT logs for unauthorized certificates."},
      {n:"Incident Response Playbook",d:"Pre-built response plans for security incidents."},
      {n:"Security Training Hub",d:"Interactive security awareness training."},
      {n:"Dark Web Monitor",d:"Monitor for leaked credentials and data."},
      {n:"Honeypot Deployer",d:"Deploy decoy systems to detect attackers."},
      {n:"Risk Assessment Tool",d:"Evaluate and prioritize security risks."},
      {n:"Bug Bounty Manager",d:"Manage vulnerability disclosure programs."},
      {n:"Security Dashboard",d:"Centralized security posture overview."}
    ]
  },
  {
    id:"devops", name:"DevOps & CI/CD", count:42, icon:"fa-code-branch",
    desc:"Continuous integration, deployment pipelines, infrastructure as code, monitoring, and automation workflows.",
    tools:[
      {n:"Pipeline Builder",d:"Design CI/CD pipelines with visual editor."},
      {n:"Git Repository Manager",d:"Manage repositories, branches, and PRs."},
      {n:"Docker Compose Generator",d:"Create Docker Compose configurations."},
      {n:"Kubernetes Manager",d:"Deploy and manage K8s clusters and pods."},
      {n:"Terraform Planner",d:"Generate and validate infrastructure-as-code."},
      {n:"Ansible Playbook Writer",d:"Create automation playbooks for server config."},
      {n:"Build Status Dashboard",d:"Monitor builds across all projects."},
      {n:"Deployment Tracker",d:"Track deployments with rollback capability."},
      {n:"Environment Manager",d:"Manage dev, staging, and production environments."},
      {n:"Secret Vault Manager",d:"Securely store and rotate API keys and secrets."},
      {n:"Release Notes Generator",d:"Auto-generate release notes from commits."},
      {n:"Code Quality Gate",d:"Enforce code quality standards before deployment."},
      {n:"Dependency Scanner",d:"Scan for vulnerable dependencies."},
      {n:"Container Registry Manager",d:"Manage container images and tags."},
      {n:"Helm Chart Builder",d:"Create and manage Kubernetes Helm charts."},
      {n:"Service Mesh Configurator",d:"Set up Istio, Linkerd, or Consul."},
      {n:"Feature Flag Manager",d:"Control feature rollouts with toggles."},
      {n:"Database Migration Runner",d:"Run and track database schema migrations."},
      {n:"API Gateway Manager",d:"Configure API gateways and rate limiting."},
      {n:"Webhook Manager",d:"Create and manage webhook integrations."},
      {n:"Infrastructure Cost Monitor",d:"Track cloud infrastructure costs."},
      {n:"Chaos Engineering Runner",d:"Run chaos experiments for reliability."},
      {n:"Alert Manager",d:"Configure alerts across monitoring systems."},
      {n:"Log Pipeline Builder",d:"Set up ELK, Grafana Loki, or Fluentd."},
      {n:"APM Configurator",d:"Configure application performance monitoring."},
      {n:"Status Page Manager",d:"Create and maintain public status pages."},
      {n:"Runbook Automator",d:"Automate operational runbooks."},
      {n:"GitOps Workflow Manager",d:"Implement ArgoCD and Flux workflows."},
      {n:"Artifact Manager",d:"Store and version build artifacts."},
      {n:"Test Environment Spinner",d:"Create ephemeral test environments on demand."},
      {n:"PR Review Bot",d:"Automated code review suggestions for PRs."},
      {n:"Commit Analyzer",d:"Analyze commit patterns and code churn."},
      {n:"Branch Protection Manager",d:"Configure branch protection rules."},
      {n:"Merge Conflict Resolver",d:"AI-assisted merge conflict resolution."},
      {n:"Cloud Cost Optimizer",d:"Right-size instances and reduce cloud waste."},
      {n:"Server Provisioning Template",d:"Create reusable server config templates."},
      {n:"Blue-Green Deploy Manager",d:"Orchestrate blue-green deployments."},
      {n:"Rolling Update Controller",d:"Manage rolling updates with health checks."},
      {n:"Incident Commander",d:"Coordinate incident response and communication."},
      {n:"SRE Dashboard Builder",d:"Build SLI/SLO tracking dashboards."},
      {n:"Post-Deployment Verifier",d:"Automated smoke tests after deployment."},
      {n:"Documentation Generator",d:"Auto-generate API and infrastructure docs."}
    ]
  }
];

/* ===== ANIMATED COUNTER ===== */
function animateCounter(target, duration) {
    var el = document.getElementById('at-counter');
    var start = 0, end = target, startTime = null;
    function step(ts) {
        if (!startTime) startTime = ts;
        var p = Math.min((ts - startTime) / duration, 1);
        var ease = 1 - Math.pow(1 - p, 3); // easeOutCubic
        el.textContent = Math.floor(start + (end - start) * ease);
        if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

/* Run counter when hero is visible */
var counterFired = false;
var obs = new IntersectionObserver(function(entries) {
    if (entries[0].isIntersecting && !counterFired) {
        counterFired = true;
        animateCounter(13000, 2200);
    }
}, {threshold: 0.3});
obs.observe(document.getElementById('at-counter'));

/* ===== RENDER CATEGORY CARDS ===== */
var grid = document.getElementById('at-grid');

categories.forEach(function(cat) {
    var card = document.createElement('div');
    card.className = 'at-card';
    card.setAttribute('data-id', cat.id);
    card.setAttribute('data-name', cat.name.toLowerCase());
    card.setAttribute('data-keywords', cat.tools.map(function(t){return t.n.toLowerCase()}).join('|'));
    card.innerHTML =
        '<div class="at-card__icon"><i class="fas ' + cat.icon + '"></i></div>' +
        '<div class="at-card__head"><h3 class="at-card__name">' + cat.name + '</h3><span class="at-card__count">' + cat.count + '</span></div>' +
        '<p class="at-card__desc">' + cat.desc + '</p>' +
        '<button class="at-card__explore">Explore Tools <i class="fas fa-arrow-right"></i></button>';
    card.addEventListener('click', function(){ togglePanel(cat); });
    grid.appendChild(card);
});

/* ===== EXPAND / COLLAPSE TOOL PANEL ===== */
var activePanel = null;
var activeCard = null;

function togglePanel(cat) {
    /* close existing */
    if (activePanel) {
        activePanel.remove();
        var prevCard = activeCard;
        activePanel = null;
        activeCard.classList.remove('at-card--active');
        activeCard = null;
        if (prevCard && prevCard.getAttribute('data-id') === cat.id) return; // toggle off
    }

    var card = grid.querySelector('[data-id="' + cat.id + '"]');
    card.classList.add('at-card--active');
    activeCard = card;

    var panel = document.createElement('div');
    panel.className = 'at-tools-panel at-tools-panel--open';
    var toolsHtml = cat.tools.map(function(t){
        return '<li><strong>' + t.n + '</strong><span>' + t.d + '</span></li>';
    }).join('');
    panel.innerHTML =
        '<div class="at-tools-panel__head">' +
            '<h3 class="at-tools-panel__title"><i class="fas ' + cat.icon + '" style="margin-right:8px; color:var(--at-accent2);"></i>' + cat.name + ' — ' + cat.count + ' Tools</h3>' +
            '<button class="at-tools-panel__close" title="Close"><i class="fas fa-times"></i></button>' +
        '</div>' +
        '<ul class="at-tools-list">' + toolsHtml + '</ul>';

    panel.querySelector('.at-tools-panel__close').addEventListener('click', function(e){
        e.stopPropagation();
        panel.remove();
        card.classList.remove('at-card--active');
        activePanel = null;
        activeCard = null;
    });

    /* Insert panel after the last card in the current row */
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.at-card:not(.at-card--hidden)'));
    var cardIdx = cards.indexOf(card);
    var cardRect = card.getBoundingClientRect();
    var rowEnd = cardIdx;
    for (var i = cardIdx + 1; i < cards.length; i++) {
        if (cards[i].getBoundingClientRect().top === cardRect.top) rowEnd = i;
        else break;
    }
    var afterEl = cards[rowEnd];
    if (afterEl.nextSibling) grid.insertBefore(panel, afterEl.nextSibling);
    else grid.appendChild(panel);

    activePanel = panel;
    panel.scrollIntoView({behavior:'smooth', block:'nearest'});
}

/* ===== SEARCH ===== */
var searchInput = document.getElementById('at-search');
var searchCount = document.getElementById('at-search-count');

searchInput.addEventListener('input', function(){
    var q = this.value.toLowerCase().trim();
    var cards = grid.querySelectorAll('.at-card');
    var visible = 0;

    /* Close any open panel */
    if (activePanel) {
        activePanel.remove();
        if (activeCard) activeCard.classList.remove('at-card--active');
        activePanel = null;
        activeCard = null;
    }

    cards.forEach(function(card){
        var name = card.getAttribute('data-name');
        var keywords = card.getAttribute('data-keywords');
        if (!q || name.indexOf(q) !== -1 || keywords.indexOf(q) !== -1) {
            card.classList.remove('at-card--hidden');
            visible++;
        } else {
            card.classList.add('at-card--hidden');
        }
    });

    searchCount.textContent = q
        ? 'Showing ' + visible + ' of 22 categories'
        : 'Showing all 22 categories';
});

})();

/* ===== Stripe Checkout (shared with landing page) ===== */
async function startCheckout(plan) {
    try {
        const authResp = await fetch('/api/auth.php?action=check', { credentials: 'same-origin' });
        const authData = await authResp.json();
        if (!authData.success || !authData.logged_in) {
            window.location.href = '/alfred.php?plan=' + encodeURIComponent(plan);
            return;
        }
        const btn = event.target.closest('.at-btn, button') || event.target;
        const origHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        btn.disabled = true;

        const resp = await fetch('/api/stripe.php?action=create_checkout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ plan: plan, interval: 'month' })
        });
        const data = await resp.json();
        if (data.success && data.checkout_url) {
            window.location.href = data.checkout_url;
        } else {
            btn.innerHTML = origHTML;
            btn.disabled = false;
            if (data.error === 'Login required') {
                window.location.href = '/alfred.php?plan=' + encodeURIComponent(plan);
            } else {
                alert(data.error || 'Something went wrong. Please try again.');
            }
        }
    } catch (err) {
        console.error('Checkout error:', err);
        alert('Network error. Please try again.');
    }
}

/* ===== Enhanced Search via API ===== */
(function() {
    var searchInput = document.getElementById('at-search');
    var debounceTimer;
    searchInput.addEventListener('input', function() {
        var q = this.value.trim();
        if (q.length >= 3) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetch('/api/tools.php?action=search&q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        if (data.success && data.results) {
                            console.log('[Alfred Tools] API search returned ' + data.results.length + ' results for: ' + q);
                        }
                    })
                    .catch(function() {});
            }, 400);
        }
    });
})();
