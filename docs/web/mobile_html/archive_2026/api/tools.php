<?php
/**
 * Alfred Tool Directory API
 * Provides tool listing, search, categories, execution, and usage stats
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/tool-helpers.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ─── Tool Registry ──────────────────────────────────────────────────────────

$TOOL_REGISTRY = [
    // ── Students K-12 ───────────────────────────────────────────────────
    ['name' => 'homework_helper', 'category' => 'students_k12', 'description' => 'AI-powered homework assistance for K-12 students with step-by-step explanations', 'icon' => '📚', 'tier' => 'starter', 'demographics' => ['students', 'parents', 'teachers'], 'related_tools' => ['math_tutor', 'essay_coach']],
    ['name' => 'math_tutor', 'category' => 'students_k12', 'description' => 'Interactive math tutoring from arithmetic to pre-calculus with practice problems', 'icon' => '🔢', 'tier' => 'starter', 'demographics' => ['students', 'teachers'], 'related_tools' => ['homework_helper', 'science_lab']],
    ['name' => 'science_lab', 'category' => 'students_k12', 'description' => 'Virtual science lab simulations and experiment guides for classroom learning', 'icon' => '🔬', 'tier' => 'professional', 'demographics' => ['students', 'teachers'], 'related_tools' => ['math_tutor', 'homework_helper']],
    ['name' => 'reading_companion', 'category' => 'students_k12', 'description' => 'Reading comprehension assistant with vocabulary building and book summaries', 'icon' => '📖', 'tier' => 'starter', 'demographics' => ['students', 'parents'], 'related_tools' => ['essay_coach', 'homework_helper']],

    // ── University ──────────────────────────────────────────────────────
    ['name' => 'essay_coach', 'category' => 'university', 'description' => 'Academic essay writing coach with citation formatting and argument structuring', 'icon' => '✍️', 'tier' => 'starter', 'demographics' => ['university_students', 'researchers'], 'related_tools' => ['citation_manager', 'plagiarism_checker']],
    ['name' => 'citation_manager', 'category' => 'university', 'description' => 'Auto-generate citations in APA, MLA, Chicago, and IEEE formats from URLs or DOIs', 'icon' => '📑', 'tier' => 'starter', 'demographics' => ['university_students', 'researchers'], 'related_tools' => ['essay_coach', 'research_assistant']],
    ['name' => 'research_assistant', 'category' => 'university', 'description' => 'Literature review helper that summarizes papers and finds related research', 'icon' => '🎓', 'tier' => 'professional', 'demographics' => ['university_students', 'researchers', 'professionals'], 'related_tools' => ['citation_manager', 'data_analyzer']],
    ['name' => 'plagiarism_checker', 'category' => 'university', 'description' => 'Check academic work for originality and proper attribution', 'icon' => '🔍', 'tier' => 'professional', 'demographics' => ['university_students', 'teachers'], 'related_tools' => ['essay_coach', 'citation_manager']],

    // ── Professionals ───────────────────────────────────────────────────
    ['name' => 'email_composer', 'category' => 'professionals', 'description' => 'Draft professional emails with tone adjustment and template library', 'icon' => '📧', 'tier' => 'starter', 'demographics' => ['professionals', 'freelancers', 'small_business'], 'related_tools' => ['meeting_summarizer', 'presentation_builder']],
    ['name' => 'meeting_summarizer', 'category' => 'professionals', 'description' => 'Generate meeting minutes, action items, and follow-up emails from transcripts', 'icon' => '📋', 'tier' => 'professional', 'demographics' => ['professionals', 'managers'], 'related_tools' => ['email_composer', 'project_tracker']],
    ['name' => 'presentation_builder', 'category' => 'professionals', 'description' => 'Create slide decks from outlines with AI-generated content and design suggestions', 'icon' => '📊', 'tier' => 'professional', 'demographics' => ['professionals', 'freelancers'], 'related_tools' => ['email_composer', 'report_generator']],
    ['name' => 'project_tracker', 'category' => 'professionals', 'description' => 'AI project management with task breakdown, timeline estimation, and status tracking', 'icon' => '📈', 'tier' => 'professional', 'demographics' => ['professionals', 'managers', 'freelancers'], 'related_tools' => ['meeting_summarizer', 'report_generator']],

    // ── Small Business ──────────────────────────────────────────────────
    ['name' => 'invoice_generator', 'category' => 'small_business', 'description' => 'Create professional invoices with payment tracking and client management', 'icon' => '💰', 'tier' => 'starter', 'demographics' => ['small_business', 'freelancers'], 'related_tools' => ['expense_tracker', 'tax_prep']],
    ['name' => 'expense_tracker', 'category' => 'small_business', 'description' => 'Track business expenses, categorize spending, and generate financial reports', 'icon' => '💳', 'tier' => 'starter', 'demographics' => ['small_business', 'freelancers'], 'related_tools' => ['invoice_generator', 'tax_prep']],
    ['name' => 'tax_prep', 'category' => 'small_business', 'description' => 'Tax preparation assistant with deduction finder and quarterly estimate calculator', 'icon' => '🏛️', 'tier' => 'professional', 'demographics' => ['small_business', 'freelancers'], 'related_tools' => ['expense_tracker', 'invoice_generator']],
    ['name' => 'business_plan_writer', 'category' => 'small_business', 'description' => 'Generate comprehensive business plans with market analysis and financial projections', 'icon' => '📝', 'tier' => 'professional', 'demographics' => ['small_business', 'entrepreneurs'], 'related_tools' => ['market_research', 'competitor_analyzer']],

    // ── Content Creators ────────────────────────────────────────────────
    ['name' => 'blog_writer', 'category' => 'content_creators', 'description' => 'AI blog post writer with SEO optimization, tone control, and content calendar', 'icon' => '✏️', 'tier' => 'starter', 'demographics' => ['content_creators', 'marketers'], 'related_tools' => ['social_media_planner', 'seo_optimizer']],
    ['name' => 'social_media_planner', 'category' => 'content_creators', 'description' => 'Plan and generate social media content across platforms with hashtag suggestions', 'icon' => '📱', 'tier' => 'starter', 'demographics' => ['content_creators', 'small_business', 'marketers'], 'related_tools' => ['blog_writer', 'video_script_writer']],
    ['name' => 'video_script_writer', 'category' => 'content_creators', 'description' => 'Write video scripts with hooks, transitions, and call-to-action suggestions', 'icon' => '🎬', 'tier' => 'professional', 'demographics' => ['content_creators', 'marketers'], 'related_tools' => ['social_media_planner', 'thumbnail_designer']],
    ['name' => 'thumbnail_designer', 'category' => 'content_creators', 'description' => 'AI-powered thumbnail and cover image generation for videos and articles', 'icon' => '🖼️', 'tier' => 'professional', 'demographics' => ['content_creators'], 'related_tools' => ['video_script_writer', 'image_generator']],

    // ── Healthcare ──────────────────────────────────────────────────────
    ['name' => 'symptom_checker', 'category' => 'healthcare', 'description' => 'AI symptom assessment with triage recommendations (not medical advice)', 'icon' => '🏥', 'tier' => 'starter', 'demographics' => ['patients', 'seniors', 'parents'], 'related_tools' => ['medication_tracker', 'health_journal']],
    ['name' => 'medication_tracker', 'category' => 'healthcare', 'description' => 'Track medications, dosages, refill dates, and interaction warnings', 'icon' => '💊', 'tier' => 'starter', 'demographics' => ['patients', 'seniors', 'caregivers'], 'related_tools' => ['symptom_checker', 'appointment_scheduler']],
    ['name' => 'health_journal', 'category' => 'healthcare', 'description' => 'Daily health logging with mood, symptoms, diet, and exercise tracking', 'icon' => '❤️', 'tier' => 'starter', 'demographics' => ['patients', 'seniors'], 'related_tools' => ['symptom_checker', 'fitness_planner']],
    ['name' => 'appointment_scheduler', 'category' => 'healthcare', 'description' => 'Smart appointment scheduling with provider lookup and reminder system', 'icon' => '📅', 'tier' => 'professional', 'demographics' => ['patients', 'healthcare_providers'], 'related_tools' => ['medication_tracker', 'health_journal']],

    // ── Real Estate ─────────────────────────────────────────────────────
    ['name' => 'property_analyzer', 'category' => 'real_estate', 'description' => 'Analyze property values, comparable sales, and investment potential', 'icon' => '🏠', 'tier' => 'professional', 'demographics' => ['real_estate_agents', 'investors'], 'related_tools' => ['mortgage_calculator', 'listing_writer']],
    ['name' => 'mortgage_calculator', 'category' => 'real_estate', 'description' => 'Calculate mortgage payments, amortization schedules, and affordability', 'icon' => '🏦', 'tier' => 'starter', 'demographics' => ['home_buyers', 'real_estate_agents'], 'related_tools' => ['property_analyzer', 'home_inspector_checklist']],
    ['name' => 'listing_writer', 'category' => 'real_estate', 'description' => 'Generate compelling MLS property listings with feature highlights', 'icon' => '🏡', 'tier' => 'professional', 'demographics' => ['real_estate_agents'], 'related_tools' => ['property_analyzer', 'virtual_stager']],
    ['name' => 'virtual_stager', 'category' => 'real_estate', 'description' => 'AI virtual staging for empty rooms with furniture and decor placement', 'icon' => '🛋️', 'tier' => 'enterprise', 'demographics' => ['real_estate_agents', 'home_sellers'], 'related_tools' => ['listing_writer', 'property_analyzer']],

    // ── Legal ───────────────────────────────────────────────────────────
    ['name' => 'contract_reviewer', 'category' => 'legal', 'description' => 'AI contract review highlighting risks, obligations, and unusual clauses', 'icon' => '⚖️', 'tier' => 'professional', 'demographics' => ['lawyers', 'small_business', 'freelancers'], 'related_tools' => ['legal_research', 'document_drafter']],
    ['name' => 'legal_research', 'category' => 'legal', 'description' => 'Search case law, statutes, and legal precedents with AI-powered analysis', 'icon' => '📜', 'tier' => 'professional', 'demographics' => ['lawyers', 'paralegals', 'law_students'], 'related_tools' => ['contract_reviewer', 'case_summarizer']],
    ['name' => 'document_drafter', 'category' => 'legal', 'description' => 'Draft legal documents from templates: NDAs, leases, terms of service', 'icon' => '📄', 'tier' => 'professional', 'demographics' => ['lawyers', 'small_business'], 'related_tools' => ['contract_reviewer', 'legal_research']],
    ['name' => 'case_summarizer', 'category' => 'legal', 'description' => 'Summarize lengthy court decisions and legal briefs into key points', 'icon' => '🔎', 'tier' => 'enterprise', 'demographics' => ['lawyers', 'paralegals'], 'related_tools' => ['legal_research', 'document_drafter']],

    // ── Parents ─────────────────────────────────────────────────────────
    ['name' => 'meal_planner', 'category' => 'parents', 'description' => 'Weekly family meal planning with grocery lists and kid-friendly recipes', 'icon' => '🍽️', 'tier' => 'starter', 'demographics' => ['parents', 'seniors'], 'related_tools' => ['budget_planner', 'activity_finder']],
    ['name' => 'activity_finder', 'category' => 'parents', 'description' => 'Find age-appropriate activities, crafts, and educational games for children', 'icon' => '🎨', 'tier' => 'starter', 'demographics' => ['parents', 'teachers'], 'related_tools' => ['meal_planner', 'story_creator']],
    ['name' => 'story_creator', 'category' => 'parents', 'description' => 'Generate personalized bedtime stories featuring your child as the hero', 'icon' => '📕', 'tier' => 'starter', 'demographics' => ['parents', 'children'], 'related_tools' => ['activity_finder', 'reading_companion']],
    ['name' => 'budget_planner', 'category' => 'parents', 'description' => 'Family budget planning with savings goals and expense categorization', 'icon' => '💵', 'tier' => 'starter', 'demographics' => ['parents', 'families'], 'related_tools' => ['meal_planner', 'expense_tracker']],

    // ── Seniors ──────────────────────────────────────────────────────────
    ['name' => 'voice_assistant', 'category' => 'seniors', 'description' => 'Hands-free voice-controlled assistant with large text and simple interface', 'icon' => '🗣️', 'tier' => 'starter', 'demographics' => ['seniors', 'accessibility'], 'related_tools' => ['medication_tracker', 'emergency_contact']],
    ['name' => 'emergency_contact', 'category' => 'seniors', 'description' => 'One-touch emergency contact notification with location sharing', 'icon' => '🆘', 'tier' => 'starter', 'demographics' => ['seniors', 'caregivers'], 'related_tools' => ['voice_assistant', 'medication_tracker']],
    ['name' => 'memory_games', 'category' => 'seniors', 'description' => 'Cognitive exercise games designed for mental sharpness and memory training', 'icon' => '🧠', 'tier' => 'starter', 'demographics' => ['seniors'], 'related_tools' => ['voice_assistant', 'daily_routine']],
    ['name' => 'daily_routine', 'category' => 'seniors', 'description' => 'Structured daily routine planner with medication, exercise, and meal reminders', 'icon' => '⏰', 'tier' => 'starter', 'demographics' => ['seniors', 'caregivers'], 'related_tools' => ['medication_tracker', 'memory_games']],

    // ── Teachers ─────────────────────────────────────────────────────────
    ['name' => 'lesson_planner', 'category' => 'teachers', 'description' => 'AI lesson plan generator aligned with curriculum standards and learning objectives', 'icon' => '📐', 'tier' => 'professional', 'demographics' => ['teachers', 'educators'], 'related_tools' => ['quiz_maker', 'rubric_builder']],
    ['name' => 'quiz_maker', 'category' => 'teachers', 'description' => 'Generate quizzes, tests, and assessments with multiple question types and answer keys', 'icon' => '❓', 'tier' => 'starter', 'demographics' => ['teachers', 'trainers'], 'related_tools' => ['lesson_planner', 'grade_calculator']],
    ['name' => 'rubric_builder', 'category' => 'teachers', 'description' => 'Create grading rubrics with customizable criteria and proficiency levels', 'icon' => '📏', 'tier' => 'professional', 'demographics' => ['teachers'], 'related_tools' => ['lesson_planner', 'grade_calculator']],
    ['name' => 'grade_calculator', 'category' => 'teachers', 'description' => 'Calculate weighted grades, class averages, and generate progress reports', 'icon' => '🅰️', 'tier' => 'starter', 'demographics' => ['teachers'], 'related_tools' => ['quiz_maker', 'rubric_builder']],

    // ── Freelancers ──────────────────────────────────────────────────────
    ['name' => 'proposal_writer', 'category' => 'freelancers', 'description' => 'Generate professional client proposals with scope, timeline, and pricing', 'icon' => '📃', 'tier' => 'starter', 'demographics' => ['freelancers', 'consultants'], 'related_tools' => ['invoice_generator', 'contract_reviewer']],
    ['name' => 'time_tracker', 'category' => 'freelancers', 'description' => 'Track billable hours by project and client with automated timesheet generation', 'icon' => '⏱️', 'tier' => 'starter', 'demographics' => ['freelancers', 'consultants'], 'related_tools' => ['invoice_generator', 'project_tracker']],
    ['name' => 'portfolio_builder', 'category' => 'freelancers', 'description' => 'Create and manage a professional portfolio website with AI content suggestions', 'icon' => '💼', 'tier' => 'professional', 'demographics' => ['freelancers', 'creatives'], 'related_tools' => ['proposal_writer', 'resume_builder']],
    ['name' => 'client_crm', 'category' => 'freelancers', 'description' => 'Simple CRM for freelancers: track leads, projects, communications, and payments', 'icon' => '🤝', 'tier' => 'professional', 'demographics' => ['freelancers', 'small_business'], 'related_tools' => ['invoice_generator', 'proposal_writer']],

    // ── Nonprofits ───────────────────────────────────────────────────────
    ['name' => 'grant_writer', 'category' => 'nonprofits', 'description' => 'AI-assisted grant writing with funder matching and proposal templates', 'icon' => '🎗️', 'tier' => 'professional', 'demographics' => ['nonprofits', 'ngos'], 'related_tools' => ['donor_manager', 'impact_reporter']],
    ['name' => 'donor_manager', 'category' => 'nonprofits', 'description' => 'Track donors, donations, and generate thank-you letters and tax receipts', 'icon' => '🙏', 'tier' => 'professional', 'demographics' => ['nonprofits'], 'related_tools' => ['grant_writer', 'fundraising_planner']],
    ['name' => 'impact_reporter', 'category' => 'nonprofits', 'description' => 'Generate impact reports with data visualization for stakeholders and funders', 'icon' => '📊', 'tier' => 'enterprise', 'demographics' => ['nonprofits', 'ngos'], 'related_tools' => ['grant_writer', 'donor_manager']],
    ['name' => 'fundraising_planner', 'category' => 'nonprofits', 'description' => 'Plan fundraising campaigns, events, and track progress toward goals', 'icon' => '🎯', 'tier' => 'professional', 'demographics' => ['nonprofits'], 'related_tools' => ['donor_manager', 'social_media_planner']],

    // ── Future Tech ──────────────────────────────────────────────────────
    ['name' => 'ai_model_trainer', 'category' => 'future_tech', 'description' => 'No-code AI model training interface for custom classification and prediction', 'icon' => '🤖', 'tier' => 'enterprise', 'demographics' => ['developers', 'data_scientists'], 'related_tools' => ['data_analyzer', 'ml_pipeline']],
    ['name' => 'data_analyzer', 'category' => 'future_tech', 'description' => 'Upload datasets and get AI-powered insights, visualizations, and trend analysis', 'icon' => '📉', 'tier' => 'professional', 'demographics' => ['analysts', 'researchers', 'data_scientists'], 'related_tools' => ['ai_model_trainer', 'report_generator']],
    ['name' => 'ml_pipeline', 'category' => 'future_tech', 'description' => 'Visual machine learning pipeline builder with automated feature engineering', 'icon' => '⚙️', 'tier' => 'enterprise', 'demographics' => ['data_scientists', 'engineers'], 'related_tools' => ['ai_model_trainer', 'data_analyzer']],
    ['name' => 'quantum_simulator', 'category' => 'future_tech', 'description' => 'Quantum computing circuit simulator for education and algorithm prototyping', 'icon' => '⚛️', 'tier' => 'enterprise', 'demographics' => ['researchers', 'students'], 'related_tools' => ['ai_model_trainer', 'code_generator']],

    // ── Agent Orchestration ──────────────────────────────────────────────
    ['name' => 'fleet_commander', 'category' => 'agent_orchestration', 'description' => 'Deploy and manage multi-agent fleets with strategy selection and monitoring', 'icon' => '🚀', 'tier' => 'professional', 'demographics' => ['power_users', 'developers'], 'related_tools' => ['agent_builder', 'task_router']],
    ['name' => 'agent_builder', 'category' => 'agent_orchestration', 'description' => 'Create custom AI agents with specific roles, skills, and personality traits', 'icon' => '🏗️', 'tier' => 'professional', 'demographics' => ['developers', 'power_users'], 'related_tools' => ['fleet_commander', 'skill_marketplace']],
    ['name' => 'task_router', 'category' => 'agent_orchestration', 'description' => 'Intelligent task routing and load balancing across agent pools', 'icon' => '🔀', 'tier' => 'enterprise', 'demographics' => ['system_admins', 'developers'], 'related_tools' => ['fleet_commander', 'agent_builder']],
    ['name' => 'agent_monitor', 'category' => 'agent_orchestration', 'description' => 'Real-time agent performance monitoring with health checks and auto-scaling', 'icon' => '📡', 'tier' => 'enterprise', 'demographics' => ['system_admins'], 'related_tools' => ['fleet_commander', 'task_router']],
    ['name' => 'agent_orchestrator', 'category' => 'agent_orchestration', 'description' => 'Deploy autonomous AI coding agents to continuously upgrade the codebase with task management, queue processing, and quality validation', 'icon' => '🎯', 'tier' => 'enterprise', 'demographics' => ['developers', 'system_admins'], 'related_tools' => ['fleet_commander', 'agent_builder', 'task_router', 'agent_monitor']],
    ['name' => 'voice_fleet_commander', 'category' => 'agent_orchestration', 'description' => 'Control the agent fleet with natural language voice commands — deploy, status, sprint, stop, retry, import backlogs', 'icon' => '🎙️', 'tier' => 'enterprise', 'demographics' => ['system_admins', 'developers'], 'related_tools' => ['agent_orchestrator', 'fleet_commander', 'voice_assistant']],
    ['name' => 'audit_log', 'category' => 'security', 'description' => 'Structured audit logging API — records and queries security-relevant events across the platform with severity levels and filtering', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['system_admins', 'developers'], 'related_tools' => ['security_monitor', 'agent_monitor']],
    ['name' => 'chart_engine', 'category' => 'visualization', 'description' => 'Lightweight Canvas-based chart library — line, bar, pie, doughnut, area, sparkline charts with dark theme and no dependencies', 'icon' => '📊', 'tier' => 'free', 'demographics' => ['developers', 'data_analysts'], 'related_tools' => ['analytics', 'reporting']],
    ['name' => 'form_validator', 'category' => 'development', 'description' => 'Client-side form validation with real-time feedback — email, phone, password, URL, pattern matching with auto-init', 'icon' => '✅', 'tier' => 'free', 'demographics' => ['developers'], 'related_tools' => ['document_editor']],
    ['name' => 'structured_logger', 'category' => 'development', 'description' => 'PSR-3 inspired structured JSON logger — file output, severity levels, context data, request metadata with atomic writes', 'icon' => '📝', 'tier' => 'professional', 'demographics' => ['developers', 'system_admins'], 'related_tools' => ['audit_log', 'security_monitor']],
    ['name' => 'database_migrator', 'category' => 'development', 'description' => 'Database schema migration system — tracks and applies numbered migrations with up/down support and batch tracking', 'icon' => '🔄', 'tier' => 'professional', 'demographics' => ['developers'], 'related_tools' => ['agent_orchestrator']],

    // ── Collaboration ────────────────────────────────────────────────────
    ['name' => 'shared_workspace', 'category' => 'collaboration', 'description' => 'Real-time collaborative workspace with shared documents and chat', 'icon' => '👥', 'tier' => 'professional', 'demographics' => ['teams', 'professionals'], 'related_tools' => ['document_editor', 'team_chat']],
    ['name' => 'document_editor', 'category' => 'collaboration', 'description' => 'Collaborative rich-text editor with AI suggestions and version history', 'icon' => '📝', 'tier' => 'professional', 'demographics' => ['teams', 'writers'], 'related_tools' => ['shared_workspace', 'file_manager']],
    ['name' => 'team_chat', 'category' => 'collaboration', 'description' => 'Team messaging with channels, threads, file sharing, and AI-assisted responses', 'icon' => '💬', 'tier' => 'professional', 'demographics' => ['teams'], 'related_tools' => ['shared_workspace', 'video_conferencing']],
    ['name' => 'whiteboard', 'category' => 'collaboration', 'description' => 'Digital whiteboard for brainstorming with sticky notes, drawings, and AI mind maps', 'icon' => '🎨', 'tier' => 'professional', 'demographics' => ['teams', 'educators'], 'related_tools' => ['shared_workspace', 'presentation_builder']],

    // ── Conferencing ─────────────────────────────────────────────────────
    ['name' => 'video_conferencing', 'category' => 'conferencing', 'description' => 'AI-enhanced video conferencing with live transcription and action item extraction', 'icon' => '📹', 'tier' => 'professional', 'demographics' => ['professionals', 'teams'], 'related_tools' => ['meeting_summarizer', 'screen_share']],
    ['name' => 'screen_share', 'category' => 'conferencing', 'description' => 'Screen sharing with remote control, annotation tools, and recording', 'icon' => '🖥️', 'tier' => 'professional', 'demographics' => ['professionals', 'support'], 'related_tools' => ['video_conferencing', 'remote_support']],
    ['name' => 'webinar_host', 'category' => 'conferencing', 'description' => 'Host webinars with registration, polls, Q&A, and attendee analytics', 'icon' => '🎤', 'tier' => 'enterprise', 'demographics' => ['marketers', 'educators', 'professionals'], 'related_tools' => ['video_conferencing', 'presentation_builder']],

    // ── Reporting ────────────────────────────────────────────────────────
    ['name' => 'report_generator', 'category' => 'reporting', 'description' => 'Generate formatted reports from data with charts, tables, and executive summaries', 'icon' => '📋', 'tier' => 'professional', 'demographics' => ['managers', 'analysts'], 'related_tools' => ['data_analyzer', 'dashboard_builder']],
    ['name' => 'dashboard_builder', 'category' => 'reporting', 'description' => 'Create custom dashboards with real-time KPIs, widgets, and data connectors', 'icon' => '📊', 'tier' => 'enterprise', 'demographics' => ['managers', 'analysts', 'executives'], 'related_tools' => ['report_generator', 'data_analyzer']],
    ['name' => 'analytics_tracker', 'category' => 'reporting', 'description' => 'Track website and app analytics with AI-powered insights and anomaly detection', 'icon' => '📈', 'tier' => 'professional', 'demographics' => ['marketers', 'developers'], 'related_tools' => ['dashboard_builder', 'seo_optimizer']],

    // ── Marketplace Tools ────────────────────────────────────────────────
    ['name' => 'skill_marketplace', 'category' => 'marketplace_tools', 'description' => 'Browse and install community-built AI skills and tool extensions', 'icon' => '🛒', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['agent_builder', 'plugin_manager']],
    ['name' => 'plugin_manager', 'category' => 'marketplace_tools', 'description' => 'Manage installed plugins and extensions with version control and settings', 'icon' => '🔌', 'tier' => 'professional', 'demographics' => ['developers', 'power_users'], 'related_tools' => ['skill_marketplace', 'api_connector']],
    ['name' => 'api_connector', 'category' => 'marketplace_tools', 'description' => 'Connect Alfred to third-party APIs with visual configuration and data mapping', 'icon' => '🔗', 'tier' => 'professional', 'demographics' => ['developers', 'integrators'], 'related_tools' => ['plugin_manager', 'webhook_manager']],

    // ── Gamification ─────────────────────────────────────────────────────
    ['name' => 'xp_tracker', 'category' => 'gamification', 'description' => 'Track experience points, levels, and achievements across Alfred activities', 'icon' => '⭐', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['achievement_board', 'leaderboard']],
    ['name' => 'achievement_board', 'category' => 'gamification', 'description' => 'View and share earned achievements, badges, and milestones', 'icon' => '🏆', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['xp_tracker', 'leaderboard']],
    ['name' => 'leaderboard', 'category' => 'gamification', 'description' => 'Community leaderboards with weekly, monthly, and all-time rankings', 'icon' => '🥇', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['xp_tracker', 'achievement_board']],
    ['name' => 'streak_tracker', 'category' => 'gamification', 'description' => 'Track daily usage streaks with rewards and streak protection power-ups', 'icon' => '🔥', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['xp_tracker', 'daily_challenges']],
    ['name' => 'daily_challenges', 'category' => 'gamification', 'description' => 'Complete daily AI challenges to earn bonus XP and exclusive badges', 'icon' => '🎮', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['streak_tracker', 'xp_tracker']],

    // ── Offline ──────────────────────────────────────────────────────────
    ['name' => 'offline_notes', 'category' => 'offline', 'description' => 'Take and organize notes offline with auto-sync when connection restores', 'icon' => '📓', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['offline_tasks', 'document_editor']],
    ['name' => 'offline_tasks', 'category' => 'offline', 'description' => 'Manage todo lists and tasks offline with priority sorting and due dates', 'icon' => '✅', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['offline_notes', 'project_tracker']],
    ['name' => 'cached_search', 'category' => 'offline', 'description' => 'Search previously cached AI responses and documents while offline', 'icon' => '🗄️', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['offline_notes', 'offline_tasks']],

    // ── DevOps ───────────────────────────────────────────────────────────
    ['name' => 'server_monitor', 'category' => 'devops', 'description' => 'Real-time server monitoring with CPU, RAM, disk alerts and uptime tracking', 'icon' => '🖲️', 'tier' => 'professional', 'demographics' => ['sysadmins', 'developers'], 'related_tools' => ['deploy_manager', 'log_analyzer']],
    ['name' => 'deploy_manager', 'category' => 'devops', 'description' => 'Automated deployment pipelines with rollback support and deployment history', 'icon' => '🚢', 'tier' => 'professional', 'demographics' => ['developers', 'devops_engineers'], 'related_tools' => ['server_monitor', 'ci_cd_pipeline']],
    ['name' => 'log_analyzer', 'category' => 'devops', 'description' => 'AI-powered log analysis with pattern detection, error grouping, and alerts', 'icon' => '📜', 'tier' => 'professional', 'demographics' => ['sysadmins', 'developers'], 'related_tools' => ['server_monitor', 'incident_manager']],
    ['name' => 'ci_cd_pipeline', 'category' => 'devops', 'description' => 'Configure and manage CI/CD pipelines with build status and test reporting', 'icon' => '🔄', 'tier' => 'enterprise', 'demographics' => ['developers', 'devops_engineers'], 'related_tools' => ['deploy_manager', 'code_reviewer']],
    ['name' => 'incident_manager', 'category' => 'devops', 'description' => 'Incident response management with escalation, runbooks, and post-mortems', 'icon' => '🚨', 'tier' => 'enterprise', 'demographics' => ['devops_engineers', 'sysadmins'], 'related_tools' => ['server_monitor', 'log_analyzer']],

    // ── Hosting ──────────────────────────────────────────────────────────
    ['name' => 'domain_manager', 'category' => 'hosting', 'description' => 'Manage domain registrations, DNS records, and domain transfers', 'icon' => '🌐', 'tier' => 'starter', 'demographics' => ['webmasters', 'small_business'], 'related_tools' => ['ssl_manager', 'dns_checker']],
    ['name' => 'ssl_manager', 'category' => 'hosting', 'description' => 'Manage SSL certificates with auto-renewal, installation, and expiry alerts', 'icon' => '🔒', 'tier' => 'professional', 'demographics' => ['webmasters', 'sysadmins'], 'related_tools' => ['domain_manager', 'security_scanner']],
    ['name' => 'dns_checker', 'category' => 'hosting', 'description' => 'DNS propagation checker and record validator with troubleshooting suggestions', 'icon' => '🔎', 'tier' => 'starter', 'demographics' => ['webmasters', 'developers'], 'related_tools' => ['domain_manager', 'ssl_manager']],
    ['name' => 'backup_manager', 'category' => 'hosting', 'description' => 'Automated backup scheduling with one-click restore and offsite storage', 'icon' => '💾', 'tier' => 'professional', 'demographics' => ['sysadmins', 'webmasters'], 'related_tools' => ['server_monitor', 'deploy_manager']],

    // ── E-commerce ───────────────────────────────────────────────────────
    ['name' => 'product_lister', 'category' => 'ecommerce', 'description' => 'AI product listing generator with SEO titles, descriptions, and tag suggestions', 'icon' => '🏷️', 'tier' => 'professional', 'demographics' => ['ecommerce_sellers', 'small_business'], 'related_tools' => ['inventory_manager', 'pricing_optimizer']],
    ['name' => 'inventory_manager', 'category' => 'ecommerce', 'description' => 'Track inventory levels, set reorder alerts, and sync across sale channels', 'icon' => '📦', 'tier' => 'professional', 'demographics' => ['ecommerce_sellers', 'small_business'], 'related_tools' => ['product_lister', 'order_tracker']],
    ['name' => 'pricing_optimizer', 'category' => 'ecommerce', 'description' => 'AI pricing recommendations based on competition, demand, and margin analysis', 'icon' => '💲', 'tier' => 'enterprise', 'demographics' => ['ecommerce_sellers'], 'related_tools' => ['product_lister', 'competitor_analyzer']],
    ['name' => 'order_tracker', 'category' => 'ecommerce', 'description' => 'Track order status, shipping updates, and customer delivery notifications', 'icon' => '🚚', 'tier' => 'professional', 'demographics' => ['ecommerce_sellers', 'customers'], 'related_tools' => ['inventory_manager', 'customer_support_bot']],

    // ── SEO & Marketing ──────────────────────────────────────────────────
    ['name' => 'seo_optimizer', 'category' => 'seo_marketing', 'description' => 'On-page SEO analysis with keyword suggestions, meta tag optimization, and scoring', 'icon' => '🔍', 'tier' => 'professional', 'demographics' => ['marketers', 'webmasters', 'content_creators'], 'related_tools' => ['keyword_researcher', 'backlink_analyzer']],
    ['name' => 'keyword_researcher', 'category' => 'seo_marketing', 'description' => 'Discover high-value keywords with search volume, difficulty, and trend data', 'icon' => '🎯', 'tier' => 'professional', 'demographics' => ['marketers', 'content_creators'], 'related_tools' => ['seo_optimizer', 'content_planner']],
    ['name' => 'backlink_analyzer', 'category' => 'seo_marketing', 'description' => 'Analyze backlink profiles, find link building opportunities, and monitor new links', 'icon' => '🔗', 'tier' => 'enterprise', 'demographics' => ['marketers', 'seo_professionals'], 'related_tools' => ['seo_optimizer', 'competitor_analyzer']],
    ['name' => 'competitor_analyzer', 'category' => 'seo_marketing', 'description' => 'Competitive analysis with traffic estimates, keyword gaps, and content comparison', 'icon' => '🕵️', 'tier' => 'professional', 'demographics' => ['marketers', 'small_business'], 'related_tools' => ['seo_optimizer', 'keyword_researcher']],
    ['name' => 'content_planner', 'category' => 'seo_marketing', 'description' => 'AI content calendar with topic suggestions, content briefs, and SEO scores', 'icon' => '🗓️', 'tier' => 'professional', 'demographics' => ['marketers', 'content_creators'], 'related_tools' => ['blog_writer', 'keyword_researcher']],

    // ── Communication ────────────────────────────────────────────────────
    ['name' => 'translator', 'category' => 'communication', 'description' => 'Real-time text and voice translation supporting 100+ languages', 'icon' => '🌍', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['voice_assistant', 'email_composer']],
    ['name' => 'speech_to_text', 'category' => 'communication', 'description' => 'Convert audio recordings and live speech to accurate text transcripts', 'icon' => '🎙️', 'tier' => 'professional', 'demographics' => ['professionals', 'content_creators', 'journalists'], 'related_tools' => ['translator', 'text_to_speech']],
    ['name' => 'text_to_speech', 'category' => 'communication', 'description' => 'Convert text to natural-sounding speech with voice selection and speed control', 'icon' => '🔊', 'tier' => 'starter', 'demographics' => ['content_creators', 'seniors', 'accessibility'], 'related_tools' => ['speech_to_text', 'translator']],
    ['name' => 'customer_support_bot', 'category' => 'communication', 'description' => 'AI customer support chatbot with knowledge base integration and ticket routing', 'icon' => '🤖', 'tier' => 'professional', 'demographics' => ['small_business', 'ecommerce_sellers'], 'related_tools' => ['team_chat', 'email_composer']],

    // ── Security ─────────────────────────────────────────────────────────
    ['name' => 'security_scanner', 'category' => 'security', 'description' => 'Scan websites for vulnerabilities, malware, and security misconfigurations', 'icon' => '🛡️', 'tier' => 'professional', 'demographics' => ['sysadmins', 'developers', 'webmasters'], 'related_tools' => ['password_manager', 'ssl_manager']],
    ['name' => 'password_manager', 'category' => 'security', 'description' => 'Secure password generation, storage, and sharing with breach monitoring', 'icon' => '🔐', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['security_scanner', 'two_factor_setup']],
    ['name' => 'two_factor_setup', 'category' => 'security', 'description' => 'Setup and manage two-factor authentication across services and accounts', 'icon' => '🔑', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['password_manager', 'security_scanner']],
    ['name' => 'privacy_audit', 'category' => 'security', 'description' => 'Audit website privacy compliance for GDPR, CCPA, and cookie consent requirements', 'icon' => '🕶️', 'tier' => 'professional', 'demographics' => ['webmasters', 'legal', 'small_business'], 'related_tools' => ['security_scanner', 'contract_reviewer']],

    // ── AI Media ─────────────────────────────────────────────────────────
    ['name' => 'image_generator', 'category' => 'ai_media', 'description' => 'Generate images from text descriptions using AI with style and resolution options', 'icon' => '🎨', 'tier' => 'professional', 'demographics' => ['content_creators', 'designers', 'marketers'], 'related_tools' => ['thumbnail_designer', 'video_generator']],
    ['name' => 'video_generator', 'category' => 'ai_media', 'description' => 'Create short videos from scripts or images with AI narration and music', 'icon' => '🎥', 'tier' => 'enterprise', 'demographics' => ['content_creators', 'marketers'], 'related_tools' => ['image_generator', 'audio_editor']],
    ['name' => 'audio_editor', 'category' => 'ai_media', 'description' => 'AI audio editing with noise removal, enhancement, and podcast production tools', 'icon' => '🎵', 'tier' => 'professional', 'demographics' => ['podcasters', 'content_creators', 'musicians'], 'related_tools' => ['speech_to_text', 'video_generator']],
    ['name' => 'avatar_creator', 'category' => 'ai_media', 'description' => 'Create AI avatars and digital personas for video content and virtual meetings', 'icon' => '👤', 'tier' => 'enterprise', 'demographics' => ['content_creators', 'professionals'], 'related_tools' => ['image_generator', 'video_generator']],

    // ── Legal Aid ────────────────────────────────────────────────────────
    ['name' => 'rights_advisor', 'category' => 'legal_aid', 'description' => 'Know your rights: AI legal information by jurisdiction for common situations', 'icon' => '⚖️', 'tier' => 'starter', 'demographics' => ['general_public', 'tenants', 'employees'], 'related_tools' => ['small_claims_guide', 'tenant_rights']],
    ['name' => 'small_claims_guide', 'category' => 'legal_aid', 'description' => 'Step-by-step guide to filing small claims court cases with document templates', 'icon' => '🏛️', 'tier' => 'starter', 'demographics' => ['general_public', 'small_business'], 'related_tools' => ['rights_advisor', 'document_drafter']],
    ['name' => 'tenant_rights', 'category' => 'legal_aid', 'description' => 'Tenant rights information by state/province with dispute letter templates', 'icon' => '🏢', 'tier' => 'starter', 'demographics' => ['tenants', 'renters'], 'related_tools' => ['rights_advisor', 'small_claims_guide']],
    ['name' => 'divorce_navigator', 'category' => 'legal_aid', 'description' => 'Navigate divorce proceedings with document checklists and process guidance', 'icon' => '📋', 'tier' => 'professional', 'demographics' => ['general_public'], 'related_tools' => ['rights_advisor', 'document_drafter']],

    // ── Additional tools to reach 100+ ───────────────────────────────────
    ['name' => 'resume_builder', 'category' => 'professionals', 'description' => 'AI resume builder with industry-specific templates and ATS optimization', 'icon' => '📄', 'tier' => 'starter', 'demographics' => ['job_seekers', 'professionals'], 'related_tools' => ['cover_letter_writer', 'portfolio_builder']],
    ['name' => 'cover_letter_writer', 'category' => 'professionals', 'description' => 'Generate tailored cover letters matching job descriptions and company culture', 'icon' => '✉️', 'tier' => 'starter', 'demographics' => ['job_seekers'], 'related_tools' => ['resume_builder', 'interview_prep']],
    ['name' => 'interview_prep', 'category' => 'professionals', 'description' => 'AI interview coach with practice questions, feedback, and confidence scoring', 'icon' => '🎯', 'tier' => 'professional', 'demographics' => ['job_seekers', 'professionals'], 'related_tools' => ['resume_builder', 'cover_letter_writer']],
    ['name' => 'code_generator', 'category' => 'devops', 'description' => 'Generate code snippets and boilerplate in 50+ programming languages', 'icon' => '💻', 'tier' => 'professional', 'demographics' => ['developers'], 'related_tools' => ['code_reviewer', 'api_connector']],
    ['name' => 'code_reviewer', 'category' => 'devops', 'description' => 'AI code review with bug detection, style suggestions, and security analysis', 'icon' => '🔍', 'tier' => 'professional', 'demographics' => ['developers'], 'related_tools' => ['code_generator', 'ci_cd_pipeline']],
    ['name' => 'webhook_manager', 'category' => 'devops', 'description' => 'Create, test, and monitor webhooks with payload inspection and retry logic', 'icon' => '🪝', 'tier' => 'professional', 'demographics' => ['developers', 'integrators'], 'related_tools' => ['api_connector', 'deploy_manager']],
    ['name' => 'remote_support', 'category' => 'communication', 'description' => 'Remote desktop support tool with screen sharing, file transfer, and chat', 'icon' => '🖥️', 'tier' => 'professional', 'demographics' => ['support_teams', 'sysadmins'], 'related_tools' => ['screen_share', 'customer_support_bot']],
    ['name' => 'fitness_planner', 'category' => 'healthcare', 'description' => 'AI workout planning with exercise demos, progress tracking, and nutrition tips', 'icon' => '💪', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['health_journal', 'meal_planner']],
    ['name' => 'home_inspector_checklist', 'category' => 'real_estate', 'description' => 'Comprehensive home inspection checklist with photo documentation and reporting', 'icon' => '🔧', 'tier' => 'professional', 'demographics' => ['home_buyers', 'inspectors'], 'related_tools' => ['property_analyzer', 'mortgage_calculator']],
    ['name' => 'market_research', 'category' => 'small_business', 'description' => 'AI market research with industry analysis, audience insights, and trend data', 'icon' => '📊', 'tier' => 'professional', 'demographics' => ['small_business', 'entrepreneurs', 'marketers'], 'related_tools' => ['business_plan_writer', 'competitor_analyzer']],
    ['name' => 'file_manager', 'category' => 'collaboration', 'description' => 'Cloud file management with sharing, versioning, and AI-powered organization', 'icon' => '📁', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['document_editor', 'shared_workspace']],
    ['name' => 'flashcard_maker', 'category' => 'students_k12', 'description' => 'Create AI-generated flashcards from notes or textbooks with spaced repetition', 'icon' => '🃏', 'tier' => 'starter', 'demographics' => ['students', 'teachers'], 'related_tools' => ['quiz_maker', 'homework_helper']],

    // ── Voice & AI Agents ────────────────────────────────────────────────
    ['name' => 'create_my_agent', 'category' => 'voice_ai', 'description' => 'Create a custom AI voice agent with persona, greeting, language, and voice selection', 'icon' => '🤖', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals', 'developers'], 'related_tools' => ['list_my_agents', 'update_my_agent']],
    ['name' => 'list_my_agents', 'category' => 'voice_ai', 'description' => 'List your AI voice agents with names, personas, assigned phone numbers, and status', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['create_my_agent', 'voice_dashboard']],
    ['name' => 'update_my_agent', 'category' => 'voice_ai', 'description' => 'Update an AI voice agent\'s persona, greeting, language, voice, or transfer number', 'icon' => '✏️', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['create_my_agent', 'list_my_agents']],
    ['name' => 'delete_my_agent', 'category' => 'voice_ai', 'description' => 'Permanently delete an AI voice agent. This cannot be undone.', 'icon' => '🗑️', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['list_my_agents', 'create_my_agent']],
    ['name' => 'voice_dashboard', 'category' => 'voice_ai', 'description' => 'Voice portal dashboard: agent count, phone numbers, call stats, SMS, fax, and usage overview', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['voice_usage', 'list_my_agents']],
    ['name' => 'voice_usage', 'category' => 'voice_ai', 'description' => 'Voice usage statistics for current and past billing periods with minute breakdowns', 'icon' => '📈', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['voice_dashboard', 'get_my_calls']],
    ['name' => 'voice_recommendation', 'category' => 'voice_ai', 'description' => 'Get AI-powered voice product recommendations based on your industry and needs', 'icon' => '💡', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals', 'all_users'], 'related_tools' => ['get_voice_products', 'voice_dashboard']],

    // ── Phone Numbers ────────────────────────────────────────────────────
    ['name' => 'order_phone_number', 'category' => 'phone', 'description' => 'Order a new phone number: local, toll-free, international, vanity, fax, or short code', 'icon' => '📞', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals', 'freelancers'], 'related_tools' => ['list_my_phones', 'assign_phone_to_agent']],
    ['name' => 'list_my_phones', 'category' => 'phone', 'description' => 'List your phone numbers and which AI agents they\'re assigned to', 'icon' => '📱', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['order_phone_number', 'assign_phone_to_agent']],
    ['name' => 'assign_phone_to_agent', 'category' => 'phone', 'description' => 'Assign a phone number to an AI agent so it answers calls on that number', 'icon' => '🔗', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['list_my_phones', 'list_my_agents']],

    // ── SMS & Messaging ──────────────────────────────────────────────────
    ['name' => 'send_sms', 'category' => 'sms_messaging', 'description' => 'Send an SMS text message to any phone number from your GoSiteMe number', 'icon' => '💬', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals', 'all_users'], 'related_tools' => ['list_sms', 'send_email']],
    ['name' => 'list_sms', 'category' => 'sms_messaging', 'description' => 'View your SMS message history — sent and received — with timestamps and status', 'icon' => '📨', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['send_sms', 'list_my_phones']],

    // ── Fax ──────────────────────────────────────────────────────────────
    ['name' => 'send_fax', 'category' => 'fax', 'description' => 'Send a fax to any fax number with a document attachment via Telnyx', 'icon' => '📠', 'tier' => 'professional', 'demographics' => ['lawyers', 'healthcare_providers', 'small_business'], 'related_tools' => ['list_faxes', 'list_my_phones']],
    ['name' => 'list_faxes', 'category' => 'fax', 'description' => 'View fax history — sent and received — with delivery status and timestamps', 'icon' => '📋', 'tier' => 'professional', 'demographics' => ['lawyers', 'healthcare_providers', 'small_business'], 'related_tools' => ['send_fax', 'list_documents']],

    // ── Calls & Call Log ─────────────────────────────────────────────────
    ['name' => 'get_my_calls', 'category' => 'calls', 'description' => 'View your call log with direction, duration, sentiment analysis, and status', 'icon' => '📞', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['get_call_details', 'voice_dashboard']],
    ['name' => 'get_call_details', 'category' => 'calls', 'description' => 'Get detailed information about a specific call including transcript and recording', 'icon' => '🔍', 'tier' => 'professional', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['get_my_calls', 'voice_usage']],

    // ── Campaigns ────────────────────────────────────────────────────────
    ['name' => 'create_campaign', 'category' => 'campaigns', 'description' => 'Create outbound calling or SMS campaigns with AI agents and contact lists', 'icon' => '📣', 'tier' => 'professional', 'demographics' => ['small_business', 'marketers', 'professionals'], 'related_tools' => ['list_campaigns', 'update_campaign']],
    ['name' => 'list_campaigns', 'category' => 'campaigns', 'description' => 'List your voice and SMS campaigns with status, progress, and statistics', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['small_business', 'marketers'], 'related_tools' => ['create_campaign', 'update_campaign']],
    ['name' => 'update_campaign', 'category' => 'campaigns', 'description' => 'Schedule, pause, resume, or cancel a campaign', 'icon' => '⚙️', 'tier' => 'professional', 'demographics' => ['small_business', 'marketers'], 'related_tools' => ['create_campaign', 'list_campaigns']],

    // ── Documents ────────────────────────────────────────────────────────
    ['name' => 'create_document', 'category' => 'voice_documents', 'description' => 'Create document templates for fax cover sheets, call scripts, and custom documents', 'icon' => '📝', 'tier' => 'professional', 'demographics' => ['lawyers', 'small_business', 'professionals'], 'related_tools' => ['list_documents', 'send_fax']],
    ['name' => 'list_documents', 'category' => 'voice_documents', 'description' => 'List your voice documents: fax cover sheets, call scripts, and templates', 'icon' => '📁', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['create_document', 'delete_document']],
    ['name' => 'delete_document', 'category' => 'voice_documents', 'description' => 'Delete a document template permanently', 'icon' => '🗑️', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals'], 'related_tools' => ['list_documents', 'create_document']],

    // ── Email ────────────────────────────────────────────────────────────
    ['name' => 'send_email', 'category' => 'communication', 'description' => 'Send an email to customers with summaries, DNS details, account info, or follow-ups', 'icon' => '📧', 'tier' => 'starter', 'demographics' => ['small_business', 'professionals', 'all_users'], 'related_tools' => ['send_sms', 'email_composer']],

    // ── Domain & Hosting ─────────────────────────────────────────────────
    ['name' => 'check_domain', 'category' => 'hosting', 'description' => 'Check if a domain name is available for registration across all TLDs', 'icon' => '🔍', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business', 'webmasters'], 'related_tools' => ['domain_whois', 'domain_pricing']],
    ['name' => 'domain_whois', 'category' => 'hosting', 'description' => 'Look up WHOIS information for any domain — registrar, expiry, nameservers', 'icon' => '🌐', 'tier' => 'starter', 'demographics' => ['webmasters', 'developers'], 'related_tools' => ['check_domain', 'domain_pricing']],
    ['name' => 'domain_pricing', 'category' => 'hosting', 'description' => 'Get pricing for domain TLDs (.com, .ca, .net, .org, .io, and more)', 'icon' => '💲', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business'], 'related_tools' => ['check_domain', 'order_hosting']],
    ['name' => 'order_hosting', 'category' => 'hosting', 'description' => 'Order a web hosting plan — shared, business, GPU server, or AI IDE', 'icon' => '🛒', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business', 'developers'], 'related_tools' => ['product_catalog', 'check_domain']],
    ['name' => 'product_catalog', 'category' => 'hosting', 'description' => 'Browse all hosting plans, GPU servers, AI IDE plans with full pricing', 'icon' => '📦', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business', 'developers'], 'related_tools' => ['order_hosting', 'domain_pricing']],

    // ── Account & Billing ────────────────────────────────────────────────
    ['name' => 'create_client', 'category' => 'account', 'description' => 'Create a new GoSiteMe account — just need name and email to get started', 'icon' => '👤', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['voice_onboard', 'get_profile']],
    ['name' => 'voice_onboard', 'category' => 'account', 'description' => 'Complete signup: create account, add payment, order hosting, and provision — all in one step', 'icon' => '🚀', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business'], 'related_tools' => ['create_client', 'add_payment_method']],
    ['name' => 'get_profile', 'category' => 'account', 'description' => 'View your account profile and contact details', 'icon' => '👤', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['update_client_profile', 'get_services']],
    ['name' => 'get_services', 'category' => 'account', 'description' => 'List your active hosting services, domains, and add-ons', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['get_profile', 'get_invoices']],
    ['name' => 'get_invoices', 'category' => 'billing', 'description' => 'List your invoices and their payment status — paid, unpaid, overdue', 'icon' => '💳', 'tier' => 'starter', 'demographics' => ['all_users', 'small_business'], 'related_tools' => ['process_payment', 'add_payment_method']],
    ['name' => 'process_payment', 'category' => 'billing', 'description' => 'Process payment for a specific invoice by credit card or PayPal', 'icon' => '💰', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['get_invoices', 'add_payment_method']],
    ['name' => 'add_payment_method', 'category' => 'billing', 'description' => 'Add a credit card or PayPal payment method to your account', 'icon' => '💳', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['process_payment', 'get_invoices']],

    // ── Support ──────────────────────────────────────────────────────────
    ['name' => 'open_ticket', 'category' => 'support', 'description' => 'Open a support ticket — general, technical, or billing — with priority level', 'icon' => '🎫', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['get_tickets', 'send_email']],
    ['name' => 'get_tickets', 'category' => 'support', 'description' => 'List your existing support tickets with status and latest replies', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['all_users'], 'related_tools' => ['open_ticket', 'get_profile']],

    // ── Jailhouse Legal Aid ──────────────────────────────────────────────
    ['name' => 'legal_identify', 'category' => 'jailhouse_legal', 'description' => 'Identify an inmate caller for legal aid — uses inmate ID and institution, no account needed', 'icon' => '🏛️', 'tier' => 'starter', 'demographics' => ['inmates', 'legal_aid'], 'related_tools' => ['legal_resume_case', 'legal_draft_motion']],
    ['name' => 'legal_search', 'category' => 'jailhouse_legal', 'description' => 'Search CanLII.org for Canadian case law, legislation, and legal precedents', 'icon' => '⚖️', 'tier' => 'starter', 'demographics' => ['inmates', 'lawyers', 'legal_aid'], 'related_tools' => ['legal_draft_motion', 'legal_court_directory']],
    ['name' => 'legal_draft_motion', 'category' => 'jailhouse_legal', 'description' => 'Draft habeas corpus, bail review, appeal, or general motion — bilingual FR/EN court documents', 'icon' => '📜', 'tier' => 'starter', 'demographics' => ['inmates', 'legal_aid'], 'related_tools' => ['legal_search', 'legal_fax_court']],
    ['name' => 'legal_fax_court', 'category' => 'jailhouse_legal', 'description' => 'Fax a legal motion directly to the court clerk via Telnyx', 'icon' => '📠', 'tier' => 'starter', 'demographics' => ['inmates', 'legal_aid'], 'related_tools' => ['legal_draft_motion', 'legal_court_directory']],
    ['name' => 'legal_court_directory', 'category' => 'jailhouse_legal', 'description' => 'Look up Quebec court info: address, phone, fax, greffe by district', 'icon' => '🏛️', 'tier' => 'starter', 'demographics' => ['inmates', 'lawyers', 'legal_aid'], 'related_tools' => ['legal_fax_court', 'legal_call_court']],

    // ── Stripe Advanced (Tax, Connect, Issuing, Billing Meters) ─────────
    ['name' => 'stripe_tax_calculate', 'category' => 'finance', 'description' => 'Calculate sales tax/VAT for a transaction using Stripe Tax', 'icon' => '🧾', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['stripe_tax_rates', 'taxjar_calculate']],
    ['name' => 'stripe_tax_rates', 'category' => 'finance', 'description' => 'Get tax rates by country via Stripe Tax', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['stripe_tax_calculate']],
    ['name' => 'stripe_connect_onboard', 'category' => 'finance', 'description' => 'Create a Stripe Connect Express account and get onboarding link', 'icon' => '🔗', 'tier' => 'business', 'demographics' => ['marketplace', 'admin'], 'related_tools' => ['stripe_connect_status', 'stripe_connect_payout']],
    ['name' => 'stripe_connect_status', 'category' => 'finance', 'description' => 'Check Stripe Connect account verification and payout status', 'icon' => '✅', 'tier' => 'business', 'demographics' => ['marketplace'], 'related_tools' => ['stripe_connect_onboard']],
    ['name' => 'stripe_connect_payout', 'category' => 'finance', 'description' => 'Send payout to a Stripe Connect marketplace seller', 'icon' => '💸', 'tier' => 'business', 'demographics' => ['marketplace', 'admin'], 'related_tools' => ['stripe_connect_status']],
    ['name' => 'stripe_meter_create', 'category' => 'finance', 'description' => 'Create a Stripe Billing Meter for usage-based pricing', 'icon' => '📏', 'tier' => 'business', 'demographics' => ['admin'], 'related_tools' => ['stripe_meter_report', 'stripe_meter_usage']],
    ['name' => 'stripe_meter_report', 'category' => 'finance', 'description' => 'Report usage event to a Stripe Billing Meter', 'icon' => '📤', 'tier' => 'business', 'demographics' => ['admin'], 'related_tools' => ['stripe_meter_create']],
    ['name' => 'stripe_card_create', 'category' => 'finance', 'description' => 'Issue a virtual or physical card via Stripe Issuing', 'icon' => '💳', 'tier' => 'enterprise', 'demographics' => ['finance', 'admin'], 'related_tools' => ['stripe_card_list', 'stripe_card_transactions']],
    ['name' => 'stripe_card_list', 'category' => 'finance', 'description' => 'List all issued cards and their statuses', 'icon' => '💳', 'tier' => 'enterprise', 'demographics' => ['finance'], 'related_tools' => ['stripe_card_create']],
    ['name' => 'stripe_card_transactions', 'category' => 'finance', 'description' => 'View Stripe Issuing card transaction history', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['finance'], 'related_tools' => ['stripe_card_list']],

    // ── Accounting (Double-Entry, Xero, QuickBooks) ─────────────────────
    ['name' => 'journal_create', 'category' => 'accounting', 'description' => 'Create a double-entry journal entry (debit/credit)', 'icon' => '📝', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['journal_list', 'chart_of_accounts']],
    ['name' => 'journal_list', 'category' => 'accounting', 'description' => 'List journal entries with date/account filters', 'icon' => '📋', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['journal_create', 'trial_balance']],
    ['name' => 'chart_of_accounts', 'category' => 'accounting', 'description' => 'View chart of accounts (assets, liabilities, equity, revenue, expenses)', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['journal_create']],
    ['name' => 'profit_loss', 'category' => 'accounting', 'description' => 'Generate profit & loss report for a date range', 'icon' => '📈', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['balance_sheet', 'cash_flow']],
    ['name' => 'balance_sheet', 'category' => 'accounting', 'description' => 'Generate balance sheet report (assets = liabilities + equity)', 'icon' => '⚖️', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['profit_loss', 'trial_balance']],
    ['name' => 'trial_balance', 'category' => 'accounting', 'description' => 'Generate trial balance showing all account debits and credits', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['balance_sheet']],
    ['name' => 'cash_flow', 'category' => 'accounting', 'description' => 'Generate cash flow statement (operating + investing)', 'icon' => '💧', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['profit_loss']],
    ['name' => 'xero_connect', 'category' => 'accounting', 'description' => 'Connect Xero accounting via OAuth2', 'icon' => '🔗', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['xero_sync', 'xero_invoices']],
    ['name' => 'xero_sync', 'category' => 'accounting', 'description' => 'Sync transactions between GoSiteMe and Xero', 'icon' => '🔄', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['xero_connect', 'xero_invoices']],
    ['name' => 'xero_invoices', 'category' => 'accounting', 'description' => 'List invoices from Xero', 'icon' => '🧾', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['xero_create_invoice']],
    ['name' => 'xero_create_invoice', 'category' => 'accounting', 'description' => 'Create a new invoice in Xero', 'icon' => '📝', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['xero_invoices']],
    ['name' => 'qbo_connect', 'category' => 'accounting', 'description' => 'Connect QuickBooks Online via OAuth2', 'icon' => '🔗', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['qbo_sync', 'qbo_profit_loss']],
    ['name' => 'qbo_sync', 'category' => 'accounting', 'description' => 'Sync transactions with QuickBooks Online', 'icon' => '🔄', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['qbo_connect']],
    ['name' => 'auto_categorize', 'category' => 'accounting', 'description' => 'Auto-categorize transactions into chart of accounts using AI rules', 'icon' => '🤖', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['reconcile']],
    ['name' => 'reconcile', 'category' => 'accounting', 'description' => 'Reconcile journal entries against treasury records, flag discrepancies', 'icon' => '🔍', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['auto_categorize']],

    // ── Banking (Plaid, Mercury, Wise) ──────────────────────────────────
    ['name' => 'plaid_link', 'category' => 'banking', 'description' => 'Create Plaid Link token to connect a bank account', 'icon' => '🏦', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['plaid_balances', 'plaid_transactions']],
    ['name' => 'plaid_balances', 'category' => 'banking', 'description' => 'Get real-time bank account balances via Plaid', 'icon' => '💰', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['plaid_link', 'all_balances']],
    ['name' => 'plaid_transactions', 'category' => 'banking', 'description' => 'Fetch recent bank transactions via Plaid', 'icon' => '📋', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['plaid_balances']],
    ['name' => 'mercury_accounts', 'category' => 'banking', 'description' => 'List Mercury business bank accounts', 'icon' => '🏦', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['mercury_balance', 'mercury_transfer']],
    ['name' => 'mercury_balance', 'category' => 'banking', 'description' => 'Get aggregate Mercury account balances', 'icon' => '💰', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['mercury_accounts']],
    ['name' => 'mercury_transfer', 'category' => 'banking', 'description' => 'Initiate ACH transfer from Mercury account', 'icon' => '💸', 'tier' => 'enterprise', 'demographics' => ['finance', 'admin'], 'related_tools' => ['mercury_balance']],
    ['name' => 'wise_balances', 'category' => 'banking', 'description' => 'Get Wise multi-currency account balances', 'icon' => '🌍', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['wise_transfer', 'wise_rates']],
    ['name' => 'wise_transfer', 'category' => 'banking', 'description' => 'Create international money transfer via Wise', 'icon' => '💸', 'tier' => 'enterprise', 'demographics' => ['finance', 'admin'], 'related_tools' => ['wise_balances', 'wise_rates']],
    ['name' => 'wise_rates', 'category' => 'banking', 'description' => 'Get real-time FX exchange rates from Wise', 'icon' => '💱', 'tier' => 'starter', 'demographics' => ['finance', 'general'], 'related_tools' => ['wise_transfer']],
    ['name' => 'all_balances', 'category' => 'banking', 'description' => 'Aggregate balances across all connected bank accounts', 'icon' => '💰', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['plaid_balances', 'mercury_balance', 'wise_balances']],

    // ── Payouts (PayPal, Deel, Contractors, Affiliates) ─────────────────
    ['name' => 'payout_create', 'category' => 'payouts', 'description' => 'Create a single payout record (PayPal, Stripe, Wise, Deel)', 'icon' => '💸', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['payout_batch', 'payout_list']],
    ['name' => 'payout_batch', 'category' => 'payouts', 'description' => 'Create a batch of payouts for bulk processing', 'icon' => '📦', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['payout_create', 'paypal_mass_payout']],
    ['name' => 'payout_list', 'category' => 'payouts', 'description' => 'List all payouts with status and type filters', 'icon' => '📋', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['payout_create', 'payout_stats']],
    ['name' => 'payout_stats', 'category' => 'payouts', 'description' => 'Get payout statistics by type and status', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['payout_list']],
    ['name' => 'paypal_mass_payout', 'category' => 'payouts', 'description' => 'Send PayPal mass payout to multiple recipients', 'icon' => '💸', 'tier' => 'enterprise', 'demographics' => ['finance', 'admin'], 'related_tools' => ['payout_batch']],
    ['name' => 'deel_contracts', 'category' => 'payouts', 'description' => 'List and manage Deel contractor agreements', 'icon' => '📝', 'tier' => 'enterprise', 'demographics' => ['hr', 'finance'], 'related_tools' => ['deel_pay', 'contractor_list']],
    ['name' => 'deel_pay', 'category' => 'payouts', 'description' => 'Process payment for a Deel contractor', 'icon' => '💸', 'tier' => 'enterprise', 'demographics' => ['finance', 'admin'], 'related_tools' => ['deel_contracts']],
    ['name' => 'contractor_add', 'category' => 'payouts', 'description' => 'Add or update a contractor with payment details', 'icon' => '👤', 'tier' => 'business', 'demographics' => ['hr', 'finance'], 'related_tools' => ['contractor_list', 'contractor_pay']],
    ['name' => 'contractor_list', 'category' => 'payouts', 'description' => 'List all contractors with payment methods and totals', 'icon' => '👥', 'tier' => 'business', 'demographics' => ['hr', 'finance'], 'related_tools' => ['contractor_add']],
    ['name' => 'contractor_pay', 'category' => 'payouts', 'description' => 'Pay a contractor via their preferred payment method', 'icon' => '💸', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['contractor_list']],
    ['name' => 'affiliate_pending', 'category' => 'payouts', 'description' => 'View pending affiliate commission payouts', 'icon' => '🤝', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['affiliate_payout']],
    ['name' => 'affiliate_payout', 'category' => 'payouts', 'description' => 'Process affiliate commission payouts in bulk', 'icon' => '💸', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['affiliate_pending']],

    // ── Analytics & Forecasting ─────────────────────────────────────────
    ['name' => 'saas_mrr', 'category' => 'analytics', 'description' => 'Calculate monthly recurring revenue from internal data', 'icon' => '📈', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_arr', 'saas_churn']],
    ['name' => 'saas_arr', 'category' => 'analytics', 'description' => 'Calculate annual recurring revenue', 'icon' => '📈', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_mrr']],
    ['name' => 'saas_churn', 'category' => 'analytics', 'description' => 'Calculate monthly churn rate trends', 'icon' => '📉', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_mrr', 'saas_ltv']],
    ['name' => 'saas_ltv', 'category' => 'analytics', 'description' => 'Calculate customer lifetime value (ARPU / churn)', 'icon' => '💎', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_churn']],
    ['name' => 'revenue_trend', 'category' => 'analytics', 'description' => 'View monthly revenue trends with growth rates', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_mrr', 'forecast_revenue']],
    ['name' => 'cohort_analysis', 'category' => 'analytics', 'description' => 'Cohort retention analysis by signup month', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_churn']],
    ['name' => 'dashboard_kpis', 'category' => 'analytics', 'description' => 'Get key financial KPIs: revenue, subscriptions, payouts, growth', 'icon' => '🎯', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['saas_mrr', 'revenue_trend']],
    ['name' => 'chartmogul_mrr', 'category' => 'analytics', 'description' => 'Get MRR data from ChartMogul with date range', 'icon' => '📈', 'tier' => 'enterprise', 'demographics' => ['finance'], 'related_tools' => ['chartmogul_churn']],
    ['name' => 'chartmogul_churn', 'category' => 'analytics', 'description' => 'Get churn rate from ChartMogul', 'icon' => '📉', 'tier' => 'enterprise', 'demographics' => ['finance'], 'related_tools' => ['chartmogul_mrr']],
    ['name' => 'profitwell_metrics', 'category' => 'analytics', 'description' => 'Get SaaS metrics from ProfitWell (free MRR analytics)', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['chartmogul_mrr']],
    ['name' => 'forecast_revenue', 'category' => 'analytics', 'description' => 'Forecast revenue using linear regression model', 'icon' => '🔮', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['forecast_churn', 'forecast_cashflow']],
    ['name' => 'forecast_churn', 'category' => 'analytics', 'description' => 'Forecast churn rate using moving average model', 'icon' => '🔮', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['forecast_revenue']],
    ['name' => 'forecast_cashflow', 'category' => 'analytics', 'description' => 'Forecast cash flow with runway estimation', 'icon' => '🔮', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['forecast_revenue']],

    // ── Tax Compliance ──────────────────────────────────────────────────
    ['name' => 'tax_obligations', 'category' => 'tax', 'description' => 'List tax obligations by year and status', 'icon' => '📋', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['tax_upcoming', 'tax_summary']],
    ['name' => 'tax_upcoming', 'category' => 'tax', 'description' => 'Show upcoming tax filing deadlines', 'icon' => '⏰', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['tax_obligations']],
    ['name' => 'tax_summary', 'category' => 'tax', 'description' => 'Annual tax summary by jurisdiction and type', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['tax_obligations']],
    ['name' => 'taxjar_calculate', 'category' => 'tax', 'description' => 'Calculate sales tax via TaxJar API', 'icon' => '🧮', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['taxjar_rates', 'stripe_tax_calculate']],
    ['name' => 'taxjar_rates', 'category' => 'tax', 'description' => 'Look up tax rates by ZIP code via TaxJar', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['finance'], 'related_tools' => ['taxjar_calculate']],
    ['name' => 'taxjar_nexus', 'category' => 'tax', 'description' => 'View nexus regions where you have tax obligations', 'icon' => '🗺️', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['tax_obligations']],
    ['name' => 'koinly_sync', 'category' => 'tax', 'description' => 'Sync DeFi transactions to Koinly for crypto tax tracking', 'icon' => '🔄', 'tier' => 'business', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['koinly_gains']],
    ['name' => 'koinly_gains', 'category' => 'tax', 'description' => 'Get capital gains/losses report from Koinly', 'icon' => '📈', 'tier' => 'business', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['koinly_sync']],
    ['name' => 'gst_report', 'category' => 'tax', 'description' => 'Generate Canadian GST/QST tax report', 'icon' => '🇨🇦', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['tax_summary']],
    ['name' => 'estimate_quarterly_tax', 'category' => 'tax', 'description' => 'Estimate quarterly tax obligations with GST/HST', 'icon' => '🧮', 'tier' => 'business', 'demographics' => ['finance', 'admin'], 'related_tools' => ['gst_report']],

    // ── Trading (CEX, DEX, Cross-Chain) ─────────────────────────────────
    ['name' => 'kraken_ticker', 'category' => 'trading', 'description' => 'Get real-time crypto price ticker from Kraken', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['crypto', 'general'], 'related_tools' => ['kraken_order', 'coinbase_prices']],
    ['name' => 'kraken_balance', 'category' => 'trading', 'description' => 'Get Kraken exchange account balances', 'icon' => '💰', 'tier' => 'business', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['kraken_order', 'portfolio']],
    ['name' => 'kraken_order', 'category' => 'trading', 'description' => 'Place a buy/sell order on Kraken (market or limit) with safety limits', 'icon' => '📈', 'tier' => 'enterprise', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['kraken_ticker', 'daily_trade_limit']],
    ['name' => 'coinbase_prices', 'category' => 'trading', 'description' => 'Get current crypto spot prices from Coinbase', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['crypto', 'general'], 'related_tools' => ['kraken_ticker']],
    ['name' => 'coinbase_order', 'category' => 'trading', 'description' => 'Buy or sell crypto on Coinbase with safety limits', 'icon' => '📈', 'tier' => 'enterprise', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['coinbase_prices', 'daily_trade_limit']],
    ['name' => 'oneinch_quote', 'category' => 'trading', 'description' => 'Get DEX swap quote from 1inch aggregator', 'icon' => '🔄', 'tier' => 'business', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['oneinch_swap', 'lifi_quote']],
    ['name' => 'oneinch_swap', 'category' => 'trading', 'description' => 'Execute DEX swap via 1inch aggregator', 'icon' => '🔄', 'tier' => 'enterprise', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['oneinch_quote']],
    ['name' => 'lifi_quote', 'category' => 'trading', 'description' => 'Get cross-chain bridge/swap quote from Li.Fi', 'icon' => '🌉', 'tier' => 'business', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['lifi_routes', 'oneinch_quote']],
    ['name' => 'lifi_routes', 'category' => 'trading', 'description' => 'Find optimal cross-chain routes via Li.Fi', 'icon' => '🗺️', 'tier' => 'business', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['lifi_quote']],
    ['name' => 'evm_balance', 'category' => 'trading', 'description' => 'Check native token balance on any EVM chain', 'icon' => '💰', 'tier' => 'starter', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['evm_tokens', 'evm_gas']],
    ['name' => 'evm_tokens', 'category' => 'trading', 'description' => 'Check ERC-20 token balances (USDC, USDT, DAI) on EVM chains', 'icon' => '🪙', 'tier' => 'starter', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['evm_balance']],
    ['name' => 'evm_gas', 'category' => 'trading', 'description' => 'Get current gas price on an EVM chain', 'icon' => '⛽', 'tier' => 'starter', 'demographics' => ['crypto', 'defi'], 'related_tools' => ['evm_balance']],
    ['name' => 'trading_portfolio', 'category' => 'trading', 'description' => 'View aggregated crypto portfolio across all exchanges and chains', 'icon' => '💼', 'tier' => 'business', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['kraken_balance', 'coinbase_accounts']],
    ['name' => 'daily_trade_limit', 'category' => 'trading', 'description' => 'Check remaining daily trade limit and usage', 'icon' => '⚠️', 'tier' => 'business', 'demographics' => ['finance', 'crypto'], 'related_tools' => ['kraken_order', 'coinbase_order']],

    // ─── Gamification ───
    ['name' => 'gamify_profile', 'category' => 'gamification', 'description' => 'View your XP, level, streak, and rank', 'icon' => '🎮', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_leaderboard', 'gamify_xp_history']],
    ['name' => 'gamify_award_xp', 'category' => 'gamification', 'description' => 'Award XP points that trigger level-ups and achievements', 'icon' => '⭐', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_profile']],
    ['name' => 'gamify_leaderboard', 'category' => 'gamification', 'description' => 'View the XP leaderboard rankings', 'icon' => '🏆', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_profile']],
    ['name' => 'gamify_achievements', 'category' => 'gamification', 'description' => 'List all available achievements', 'icon' => '🏅', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_my_achievements']],
    ['name' => 'gamify_my_achievements', 'category' => 'gamification', 'description' => 'View achievements you have earned', 'icon' => '🎖️', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_achievements']],
    ['name' => 'gamify_check_streak', 'category' => 'gamification', 'description' => 'Check and update your daily login streak', 'icon' => '🔥', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_profile']],
    ['name' => 'gamify_daily_challenge', 'category' => 'gamification', 'description' => 'Get today\'s daily challenges', 'icon' => '📋', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_complete_challenge']],
    ['name' => 'gamify_complete_challenge', 'category' => 'gamification', 'description' => 'Mark a daily challenge as completed', 'icon' => '✅', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_daily_challenge']],
    ['name' => 'gamify_xp_history', 'category' => 'gamification', 'description' => 'View your recent XP gain history', 'icon' => '📈', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_profile']],
    ['name' => 'gamify_stats', 'category' => 'gamification', 'description' => 'Platform-wide gamification statistics', 'icon' => '📊', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['gamify_leaderboard']],

    // ─── Reporting Engine ───
    ['name' => 'report_usage', 'category' => 'reporting', 'description' => 'Generate platform usage report with conversation and tool metrics', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['business', 'analytics'], 'related_tools' => ['report_tool_usage', 'report_conversations']],
    ['name' => 'report_revenue', 'category' => 'reporting', 'description' => 'Generate revenue report with MRR, ARR, and breakdown', 'icon' => '💰', 'tier' => 'business', 'demographics' => ['finance', 'analytics'], 'related_tools' => ['report_growth', 'report_dashboard_kpis']],
    ['name' => 'report_agent_performance', 'category' => 'reporting', 'description' => 'Analyze AI agent performance metrics', 'icon' => '🤖', 'tier' => 'starter', 'demographics' => ['analytics'], 'related_tools' => ['report_tool_usage']],
    ['name' => 'report_tool_usage', 'category' => 'reporting', 'description' => 'Analyze tool usage patterns and frequency', 'icon' => '🔧', 'tier' => 'starter', 'demographics' => ['analytics'], 'related_tools' => ['report_usage']],
    ['name' => 'report_client', 'category' => 'reporting', 'description' => 'Generate client account activity report', 'icon' => '👤', 'tier' => 'starter', 'demographics' => ['business'], 'related_tools' => ['report_usage']],
    ['name' => 'report_dashboard_kpis', 'category' => 'reporting', 'description' => 'Dashboard KPIs overview with key business metrics', 'icon' => '🎯', 'tier' => 'starter', 'demographics' => ['business', 'analytics'], 'related_tools' => ['report_revenue', 'report_growth']],
    ['name' => 'report_conversations', 'category' => 'reporting', 'description' => 'Conversation analytics and statistics', 'icon' => '💬', 'tier' => 'starter', 'demographics' => ['analytics'], 'related_tools' => ['report_usage']],
    ['name' => 'report_growth', 'category' => 'reporting', 'description' => 'Growth metrics: new users, retention, churn', 'icon' => '📈', 'tier' => 'business', 'demographics' => ['business', 'analytics'], 'related_tools' => ['report_revenue']],
    ['name' => 'report_save', 'category' => 'reporting', 'description' => 'Save a generated report for later access', 'icon' => '💾', 'tier' => 'starter', 'demographics' => ['business'], 'related_tools' => ['report_saved_list']],
    ['name' => 'report_saved_list', 'category' => 'reporting', 'description' => 'List your saved reports', 'icon' => '📁', 'tier' => 'starter', 'demographics' => ['business'], 'related_tools' => ['report_save']],
    ['name' => 'report_export', 'category' => 'reporting', 'description' => 'Export a report in CSV, PDF, or JSON format', 'icon' => '📤', 'tier' => 'starter', 'demographics' => ['business'], 'related_tools' => ['report_save']],
    ['name' => 'report_schedule', 'category' => 'reporting', 'description' => 'Schedule automatic recurring report generation', 'icon' => '⏰', 'tier' => 'business', 'demographics' => ['business'], 'related_tools' => ['report_save']],

    // ─── Marketplace Backend ───
    ['name' => 'marketplace_browse', 'category' => 'marketplace', 'description' => 'Browse marketplace items with pagination', 'icon' => '🛍️', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_search', 'marketplace_categories']],
    ['name' => 'marketplace_search', 'category' => 'marketplace', 'description' => 'Search marketplace by keyword', 'icon' => '🔍', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_browse']],
    ['name' => 'marketplace_categories', 'category' => 'marketplace', 'description' => 'List marketplace categories', 'icon' => '📂', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_browse']],
    ['name' => 'marketplace_detail', 'category' => 'marketplace', 'description' => 'View detailed info about a marketplace item', 'icon' => '📋', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_reviews', 'marketplace_install']],
    ['name' => 'marketplace_featured', 'category' => 'marketplace', 'description' => 'View featured marketplace items', 'icon' => '⭐', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_trending']],
    ['name' => 'marketplace_trending', 'category' => 'marketplace', 'description' => 'View trending marketplace items by install count', 'icon' => '🔥', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_featured']],
    ['name' => 'marketplace_install', 'category' => 'marketplace', 'description' => 'Install a marketplace item', 'icon' => '📥', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_uninstall']],
    ['name' => 'marketplace_uninstall', 'category' => 'marketplace', 'description' => 'Uninstall a marketplace item', 'icon' => '🗑️', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_install']],
    ['name' => 'marketplace_my_installs', 'category' => 'marketplace', 'description' => 'List your installed marketplace items', 'icon' => '📦', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_install']],
    ['name' => 'marketplace_rate', 'category' => 'marketplace', 'description' => 'Rate a marketplace item (1-5 stars)', 'icon' => '⭐', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_review']],
    ['name' => 'marketplace_review', 'category' => 'marketplace', 'description' => 'Write a review for a marketplace item', 'icon' => '✍️', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_reviews']],
    ['name' => 'marketplace_reviews', 'category' => 'marketplace', 'description' => 'Read reviews for a marketplace item', 'icon' => '💬', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_review']],
    ['name' => 'marketplace_wishlist_add', 'category' => 'marketplace', 'description' => 'Add a marketplace item to your wishlist', 'icon' => '❤️', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_my_wishlist']],
    ['name' => 'marketplace_wishlist_remove', 'category' => 'marketplace', 'description' => 'Remove a marketplace item from your wishlist', 'icon' => '💔', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_my_wishlist']],
    ['name' => 'marketplace_my_wishlist', 'category' => 'marketplace', 'description' => 'View your marketplace wishlist', 'icon' => '📋', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_wishlist_add']],
    ['name' => 'marketplace_stats', 'category' => 'marketplace', 'description' => 'Marketplace statistics and counts', 'icon' => '📊', 'tier' => 'free', 'demographics' => ['general'], 'related_tools' => ['marketplace_browse']],

    // ─── Small Business / CRM ───
    ['name' => 'crm_contacts_list', 'category' => 'small-biz', 'description' => 'List CRM contacts with filters', 'icon' => '📇', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contact_search', 'crm_contact_create']],
    ['name' => 'crm_contact_create', 'category' => 'small-biz', 'description' => 'Create a new CRM contact', 'icon' => '➕', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contacts_list']],
    ['name' => 'crm_contact_update', 'category' => 'small-biz', 'description' => 'Update an existing CRM contact', 'icon' => '✏️', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contact_detail']],
    ['name' => 'crm_contact_detail', 'category' => 'small-biz', 'description' => 'View CRM contact details and activity', 'icon' => '👤', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contact_update']],
    ['name' => 'crm_contact_search', 'category' => 'small-biz', 'description' => 'Search CRM contacts by name, email, or company', 'icon' => '🔍', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contacts_list']],
    ['name' => 'crm_activity_log', 'category' => 'small-biz', 'description' => 'View activity history for a CRM contact', 'icon' => '📜', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_activity_create']],
    ['name' => 'crm_activity_create', 'category' => 'small-biz', 'description' => 'Log an activity (call, email, meeting, note) for a contact', 'icon' => '📝', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_activity_log']],
    ['name' => 'time_log', 'category' => 'small-biz', 'description' => 'View time tracking entries', 'icon' => '⏱️', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['time_create', 'time_summary']],
    ['name' => 'time_create', 'category' => 'small-biz', 'description' => 'Log a time entry for a project', 'icon' => '⏰', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['time_log']],
    ['name' => 'time_summary', 'category' => 'small-biz', 'description' => 'Time tracking summary by project', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['time_log']],
    ['name' => 'biz_projects_list', 'category' => 'small-biz', 'description' => 'List business projects', 'icon' => '📁', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_project_create']],
    ['name' => 'biz_project_create', 'category' => 'small-biz', 'description' => 'Create a new business project', 'icon' => '🆕', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_projects_list']],
    ['name' => 'biz_project_update', 'category' => 'small-biz', 'description' => 'Update a business project', 'icon' => '✏️', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_project_detail']],
    ['name' => 'biz_project_detail', 'category' => 'small-biz', 'description' => 'View project details with tasks and time', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_project_update']],
    ['name' => 'biz_tasks_list', 'category' => 'small-biz', 'description' => 'List tasks for a project', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_task_create']],
    ['name' => 'biz_task_create', 'category' => 'small-biz', 'description' => 'Create a new task in a project', 'icon' => '➕', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_tasks_list']],
    ['name' => 'biz_task_update', 'category' => 'small-biz', 'description' => 'Update a task status or details', 'icon' => '✏️', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['biz_tasks_list']],
    ['name' => 'biz_invoice_create', 'category' => 'small-biz', 'description' => 'Create a new invoice with line items', 'icon' => '🧾', 'tier' => 'starter', 'demographics' => ['business', 'smb', 'finance'], 'related_tools' => ['biz_invoice_list']],
    ['name' => 'biz_invoice_list', 'category' => 'small-biz', 'description' => 'List invoices with status filters', 'icon' => '📄', 'tier' => 'starter', 'demographics' => ['business', 'smb', 'finance'], 'related_tools' => ['biz_invoice_create']],
    ['name' => 'biz_invoice_detail', 'category' => 'small-biz', 'description' => 'View invoice details with line items', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['business', 'smb', 'finance'], 'related_tools' => ['biz_invoice_send']],
    ['name' => 'biz_invoice_send', 'category' => 'small-biz', 'description' => 'Mark an invoice as sent', 'icon' => '📧', 'tier' => 'starter', 'demographics' => ['business', 'smb', 'finance'], 'related_tools' => ['biz_invoice_detail']],
    ['name' => 'biz_invoice_from_time', 'category' => 'small-biz', 'description' => 'Generate an invoice from unbilled time entries', 'icon' => '⏱️', 'tier' => 'starter', 'demographics' => ['business', 'smb', 'finance'], 'related_tools' => ['time_log', 'biz_invoice_create']],
    ['name' => 'biz_dashboard', 'category' => 'small-biz', 'description' => 'Small business dashboard with KPIs', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['business', 'smb'], 'related_tools' => ['crm_contacts_list', 'biz_projects_list']],

    // ─── Collaboration & Conferencing ───
    ['name' => 'collab_create_session', 'category' => 'collaboration', 'description' => 'Create a new collaboration session', 'icon' => '🤝', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_join_session']],
    ['name' => 'collab_join_session', 'category' => 'collaboration', 'description' => 'Join an existing collaboration session by code', 'icon' => '🚪', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_create_session']],
    ['name' => 'collab_leave_session', 'category' => 'collaboration', 'description' => 'Leave a collaboration session', 'icon' => '🚶', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_join_session']],
    ['name' => 'collab_end_session', 'category' => 'collaboration', 'description' => 'End a collaboration session (host only)', 'icon' => '🔚', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_create_session']],
    ['name' => 'collab_my_sessions', 'category' => 'collaboration', 'description' => 'List your collaboration sessions', 'icon' => '📋', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_create_session']],
    ['name' => 'collab_session_detail', 'category' => 'collaboration', 'description' => 'View session details and participants', 'icon' => '🔍', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_my_sessions']],
    ['name' => 'collab_invite', 'category' => 'collaboration', 'description' => 'Invite someone to a collaboration session', 'icon' => '📨', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_join_session']],
    ['name' => 'collab_doc_create', 'category' => 'collaboration', 'description' => 'Create a shared document in a session', 'icon' => '📄', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_update', 'collab_doc_list']],
    ['name' => 'collab_doc_update', 'category' => 'collaboration', 'description' => 'Update content of a shared document', 'icon' => '✏️', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_get']],
    ['name' => 'collab_doc_get', 'category' => 'collaboration', 'description' => 'Get content of a shared document', 'icon' => '📖', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_update']],
    ['name' => 'collab_doc_list', 'category' => 'collaboration', 'description' => 'List documents in a session', 'icon' => '📚', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_create']],
    ['name' => 'collab_doc_revisions', 'category' => 'collaboration', 'description' => 'View revision history for a document', 'icon' => '📜', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_get']],
    ['name' => 'collab_doc_lock', 'category' => 'collaboration', 'description' => 'Lock a document for exclusive editing', 'icon' => '🔒', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_unlock']],
    ['name' => 'collab_doc_unlock', 'category' => 'collaboration', 'description' => 'Unlock a locked document', 'icon' => '🔓', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_doc_lock']],
    ['name' => 'collab_wb_create', 'category' => 'collaboration', 'description' => 'Create a shared whiteboard', 'icon' => '🎨', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_wb_update']],
    ['name' => 'collab_wb_update', 'category' => 'collaboration', 'description' => 'Update whiteboard canvas data', 'icon' => '🖌️', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_wb_get']],
    ['name' => 'collab_wb_get', 'category' => 'collaboration', 'description' => 'Get whiteboard canvas data', 'icon' => '📐', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_wb_update']],
    ['name' => 'collab_conf_create', 'category' => 'collaboration', 'description' => 'Create a conference room', 'icon' => '📹', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_join']],
    ['name' => 'collab_conf_join', 'category' => 'collaboration', 'description' => 'Join a conference room', 'icon' => '📞', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_create']],
    ['name' => 'collab_conf_leave', 'category' => 'collaboration', 'description' => 'Leave a conference room', 'icon' => '📴', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_join']],
    ['name' => 'collab_conf_end', 'category' => 'collaboration', 'description' => 'End a conference (host only)', 'icon' => '🔚', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_create']],
    ['name' => 'collab_conf_toggle', 'category' => 'collaboration', 'description' => 'Toggle conference media (mute, video, screen share)', 'icon' => '🎛️', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_status']],
    ['name' => 'collab_conf_status', 'category' => 'collaboration', 'description' => 'View conference room status and participants', 'icon' => '📊', 'tier' => 'business', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_conf_toggle']],
    ['name' => 'collab_chat_send', 'category' => 'collaboration', 'description' => 'Send a message in a session chat', 'icon' => '💬', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_chat_history']],
    ['name' => 'collab_chat_history', 'category' => 'collaboration', 'description' => 'View session chat message history', 'icon' => '📜', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_chat_send']],
    ['name' => 'collab_poll_create', 'category' => 'collaboration', 'description' => 'Create a poll in a session', 'icon' => '📊', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_poll_vote', 'collab_poll_results']],
    ['name' => 'collab_poll_vote', 'category' => 'collaboration', 'description' => 'Vote on a session poll', 'icon' => '🗳️', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_poll_results']],
    ['name' => 'collab_poll_results', 'category' => 'collaboration', 'description' => 'View poll results', 'icon' => '📈', 'tier' => 'starter', 'demographics' => ['business', 'team'], 'related_tools' => ['collab_poll_create']],

    // ─── Healthcare ───
    ['name' => 'hc_patient_create', 'category' => 'healthcare', 'description' => 'Create a new patient record', 'icon' => '🏥', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_patient_list']],
    ['name' => 'hc_patient_update', 'category' => 'healthcare', 'description' => 'Update patient demographics', 'icon' => '✏️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_patient_detail']],
    ['name' => 'hc_patient_list', 'category' => 'healthcare', 'description' => 'List patients with search filters', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_patient_search']],
    ['name' => 'hc_patient_detail', 'category' => 'healthcare', 'description' => 'View complete patient record', 'icon' => '👤', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_patient_update']],
    ['name' => 'hc_patient_search', 'category' => 'healthcare', 'description' => 'Search patients by name, DOB, or MRN', 'icon' => '🔍', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_patient_list']],
    ['name' => 'hc_soap_create', 'category' => 'healthcare', 'description' => 'Create a SOAP clinical note', 'icon' => '📝', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_soap_list']],
    ['name' => 'hc_soap_update', 'category' => 'healthcare', 'description' => 'Update a SOAP note (unsigned only)', 'icon' => '✏️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_soap_detail']],
    ['name' => 'hc_soap_list', 'category' => 'healthcare', 'description' => 'List SOAP notes for a patient', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_soap_create']],
    ['name' => 'hc_soap_detail', 'category' => 'healthcare', 'description' => 'View a SOAP note in full', 'icon' => '📖', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_soap_update']],
    ['name' => 'hc_soap_sign', 'category' => 'healthcare', 'description' => 'Electronically sign and lock a SOAP note', 'icon' => '✍️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_soap_detail']],
    ['name' => 'hc_med_add', 'category' => 'healthcare', 'description' => 'Add a medication to patient record', 'icon' => '💊', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_med_list', 'hc_med_interactions']],
    ['name' => 'hc_med_update', 'category' => 'healthcare', 'description' => 'Update or discontinue a medication', 'icon' => '✏️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_med_list']],
    ['name' => 'hc_med_list', 'category' => 'healthcare', 'description' => 'List patient medications', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_med_add']],
    ['name' => 'hc_med_interactions', 'category' => 'healthcare', 'description' => 'Check for medication interactions', 'icon' => '⚠️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_med_list']],
    ['name' => 'hc_appt_create', 'category' => 'healthcare', 'description' => 'Schedule a patient appointment', 'icon' => '📅', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_list']],
    ['name' => 'hc_appt_update', 'category' => 'healthcare', 'description' => 'Update appointment details', 'icon' => '✏️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_list']],
    ['name' => 'hc_appt_list', 'category' => 'healthcare', 'description' => 'List patient appointments', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_create']],
    ['name' => 'hc_appt_today', 'category' => 'healthcare', 'description' => 'View today\'s appointment schedule', 'icon' => '📆', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_list']],
    ['name' => 'hc_appt_cancel', 'category' => 'healthcare', 'description' => 'Cancel an appointment', 'icon' => '❌', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_list']],
    ['name' => 'hc_intake_create', 'category' => 'healthcare', 'description' => 'Create a patient intake form', 'icon' => '📝', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_intake_submit']],
    ['name' => 'hc_intake_submit', 'category' => 'healthcare', 'description' => 'Submit a completed intake form', 'icon' => '✅', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_intake_list']],
    ['name' => 'hc_intake_list', 'category' => 'healthcare', 'description' => 'List intake forms for a patient', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_intake_create']],
    ['name' => 'hc_vitals_record', 'category' => 'healthcare', 'description' => 'Record patient vital signs', 'icon' => '❤️', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_vitals_history']],
    ['name' => 'hc_vitals_history', 'category' => 'healthcare', 'description' => 'View patient vital signs history', 'icon' => '📈', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_vitals_record']],
    ['name' => 'hc_lab_order', 'category' => 'healthcare', 'description' => 'Order a lab test for a patient', 'icon' => '🧪', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_lab_result']],
    ['name' => 'hc_lab_result', 'category' => 'healthcare', 'description' => 'Record lab test results', 'icon' => '📊', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_lab_list']],
    ['name' => 'hc_lab_list', 'category' => 'healthcare', 'description' => 'List lab orders and results for a patient', 'icon' => '📋', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_lab_order']],
    ['name' => 'hc_dashboard', 'category' => 'healthcare', 'description' => 'Healthcare practice dashboard', 'icon' => '🏥', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_appt_today']],
    ['name' => 'hc_audit_log', 'category' => 'healthcare', 'description' => 'View HIPAA audit log', 'icon' => '🔐', 'tier' => 'enterprise', 'demographics' => ['healthcare'], 'related_tools' => ['hc_dashboard']],
];

// ─── Tool Providers ──────────────────────────────────────────────────────────
// Tools come from multiple sources ("providers"). Native tools are hardcoded
// above. MCP and Composio tools are discovered dynamically at runtime.

$TOOL_PROVIDERS = [
    [
        'id'          => 'native',
        'name'        => 'Alfred Native Tools',
        'description' => 'Built-in tools developed by GoSiteMe — 170+ specialized AI tools',
        'tool_count'  => count($TOOL_REGISTRY),
        'status'      => 'active',
        'type'        => 'built-in',
    ],
    [
        'id'          => 'mcp',
        'name'        => 'MCP Server Tools',
        'description' => 'Internal MCP-protocol tools served via Alfred\'s tool server on port 3005',
        'tool_count'  => 807,
        'status'      => 'active',
        'type'        => 'mcp-server',
    ],
    [
        'id'          => 'mcp-external',
        'name'        => 'External MCP Servers',
        'description' => 'Tools from 870+ community MCP servers — Brave Search, GitHub, Puppeteer, Docker, and more',
        'tool_count'  => 1200,
        'status'      => 'available',
        'type'        => 'mcp-client',
        'registry_url' => 'https://github.com/modelcontextprotocol/servers',
    ],
    [
        'id'          => 'composio',
        'name'        => 'Composio Universal Tools',
        'description' => '850+ apps with managed OAuth — Gmail, Slack, GitHub, Jira, Salesforce, Shopify, and 11,000+ actions',
        'tool_count'  => 11000,
        'status'      => 'available',
        'type'        => 'composio',
        'registry_url' => 'https://composio.dev',
    ],
    [
        'id'          => 'vapi',
        'name'        => 'VAPI Voice Tools',
        'description' => 'Voice-activated tools for phone calls — account management, hosting, legal, voice agents',
        'tool_count'  => 85,
        'status'      => 'active',
        'type'        => 'vapi-webhook',
    ],
    [
        'id'          => 'marketplace',
        'name'        => 'Alfred Marketplace',
        'description' => 'Community-published tools, templates, workflows, and integrations',
        'tool_count'  => 0,
        'status'      => 'active',
        'type'        => 'community',
    ],
];

// ─── Router ─────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        listTools();
        break;
    case 'detail':
        getToolDetail();
        break;
    case 'categories':
        getCategories();
        break;
    case 'search':
        searchTools();
        break;
    case 'execute':
        requireAuth();
        executeTool();
        break;
    case 'stats':
        requireAuth();
        getToolStats();
        break;
    case 'providers':
        listProviders();
        break;
    case 'discover':
        discoverProviderTools();
        break;
    default:
        jsonResponse(['error' => 'Invalid action. Valid: list, detail, categories, search, execute, stats, providers, discover'], 400);
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function getClientId() {
    return (int) $_SESSION['client_id'];
}

function getRegistry() {
    global $TOOL_REGISTRY;
    return $TOOL_REGISTRY;
}

function getProviders() {
    global $TOOL_PROVIDERS;
    return $TOOL_PROVIDERS;
}

/**
 * List all connected tool providers with tool counts.
 */
function listProviders() {
    $providers = getProviders();

    // Calculate totals
    $totalActive = 0;
    $totalAvailable = 0;
    foreach ($providers as $p) {
        if ($p['status'] === 'active') {
            $totalActive += $p['tool_count'];
        }
        $totalAvailable += $p['tool_count'];
    }

    jsonResponse([
        'success'           => true,
        'providers'         => $providers,
        'total_providers'   => count($providers),
        'active_tools'      => $totalActive,
        'total_available'   => $totalAvailable,
        'protocol_version'  => '2025-03-06',
    ]);
}

/**
 * Discover tools from a specific provider.
 * For native/vapi: returns from hardcoded registry.
 * For mcp/composio: returns schema describing how to connect.
 */
function discoverProviderTools() {
    $providerId = sanitize($_GET['provider'] ?? '', 50);
    if (empty($providerId)) {
        jsonResponse(['error' => 'Provider ID required (e.g., ?provider=native)'], 400);
    }

    $providers = getProviders();
    $provider = null;
    foreach ($providers as $p) {
        if ($p['id'] === $providerId) {
            $provider = $p;
            break;
        }
    }

    if (!$provider) {
        jsonResponse(['error' => 'Unknown provider: ' . $providerId], 404);
    }

    switch ($providerId) {
        case 'native':
            // Return actual tool list
            $registry = getRegistry();
            $registry = array_map(function ($t) {
                $t['provider'] = 'native';
                $t['display_name'] = humanizeToolName($t['name']);
                $t['category_label'] = humanizeCategoryName($t['category']);
                return $t;
            }, $registry);

            jsonResponse([
                'success'    => true,
                'provider'   => $provider,
                'tools'      => $registry,
                'tool_count' => count($registry),
            ]);
            break;

        case 'mcp':
            jsonResponse([
                'success'     => true,
                'provider'    => $provider,
                'connection'  => [
                    'type'     => 'mcp',
                    'endpoint' => 'wss://gositeme.com:3005',
                    'protocol' => 'Model Context Protocol 1.0',
                    'method'   => 'tools/list',
                    'note'     => 'Connect via MCP protocol. Call tools/list to enumerate all 807 tools at runtime.',
                ],
                'sample_tools' => [
                    'check_domain', 'dns_lookup', 'whois_lookup', 'ssl_check',
                    'seo_audit', 'website_screenshot', 'send_sms', 'send_fax',
                    'create_voice_agent', 'call_campaigns', 'translate_text',
                ],
            ]);
            break;

        case 'mcp-external':
            jsonResponse([
                'success'     => true,
                'provider'    => $provider,
                'connection'  => [
                    'type'     => 'mcp-client',
                    'protocol' => 'Model Context Protocol 1.0',
                    'method'   => 'Connect Alfred as MCP client to external MCP servers',
                    'registry' => 'https://github.com/modelcontextprotocol/servers',
                ],
                'sample_servers' => [
                    ['name' => 'Brave Search',    'tools' => 3,  'category' => 'search'],
                    ['name' => 'GitHub',           'tools' => 25, 'category' => 'development'],
                    ['name' => 'Puppeteer',        'tools' => 8,  'category' => 'automation'],
                    ['name' => 'Docker',           'tools' => 12, 'category' => 'infrastructure'],
                    ['name' => 'PostgreSQL',       'tools' => 6,  'category' => 'database'],
                    ['name' => 'Slack',            'tools' => 10, 'category' => 'communication'],
                    ['name' => 'Google Maps',      'tools' => 5,  'category' => 'location'],
                    ['name' => 'Filesystem',       'tools' => 8,  'category' => 'system'],
                    ['name' => 'Memory',           'tools' => 4,  'category' => 'ai'],
                    ['name' => 'Sequential Think', 'tools' => 1,  'category' => 'ai'],
                ],
            ]);
            break;

        case 'composio':
            jsonResponse([
                'success'     => true,
                'provider'    => $provider,
                'connection'  => [
                    'type'     => 'composio-sdk',
                    'protocol' => 'Composio REST API + managed OAuth',
                    'endpoint' => 'https://backend.composio.dev/api/v2',
                    'method'   => 'GET /actions to enumerate, POST /actions/{id}/execute to run',
                ],
                'sample_apps' => [
                    ['name' => 'Gmail',       'actions' => 20, 'category' => 'communication'],
                    ['name' => 'Google Calendar', 'actions' => 15, 'category' => 'productivity'],
                    ['name' => 'Slack',       'actions' => 18, 'category' => 'communication'],
                    ['name' => 'GitHub',      'actions' => 30, 'category' => 'development'],
                    ['name' => 'Jira',        'actions' => 22, 'category' => 'project_mgmt'],
                    ['name' => 'Salesforce',  'actions' => 25, 'category' => 'crm'],
                    ['name' => 'Shopify',     'actions' => 18, 'category' => 'ecommerce'],
                    ['name' => 'Stripe',      'actions' => 20, 'category' => 'payments'],
                    ['name' => 'HubSpot',     'actions' => 22, 'category' => 'crm'],
                    ['name' => 'Notion',      'actions' => 15, 'category' => 'productivity'],
                ],
                'total_apps'    => 850,
                'total_actions' => 11000,
            ]);
            break;

        case 'vapi':
            jsonResponse([
                'success'     => true,
                'provider'    => $provider,
                'connection'  => [
                    'type'     => 'vapi-webhook',
                    'endpoint' => 'https://gositeme.com/api/vapi-tools.php',
                    'protocol' => 'VAPI Tool Webhook (voice-activated)',
                    'note'     => 'Tools triggered via phone calls through VAPI voice agents.',
                ],
                'tool_count' => 85,
            ]);
            break;

        case 'marketplace':
            $db = getDB();
            $marketCount = 0;
            if ($db) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) FROM alfred_marketplace_items WHERE status = 'active' AND item_type = 'tool'");
                    $marketCount = (int) $stmt->fetchColumn();
                } catch (\Exception $e) {
                    // Table may not exist yet
                }
            }
            jsonResponse([
                'success'    => true,
                'provider'   => $provider,
                'tool_count' => $marketCount,
                'browse_url' => 'https://gositeme.com/marketplace',
            ]);
            break;
    }
}

// ─── Actions ────────────────────────────────────────────────────────────────

/**
 * List tools with search, filter, and pagination
 */
function listTools() {
    $registry = getRegistry();

    // Add provider tag to native tools
    $registry = array_map(function ($t) {
        $t['provider'] = $t['provider'] ?? 'native';
        return $t;
    }, $registry);

    // Filter by provider
    $provider = sanitize($_GET['provider'] ?? '', 50);
    if ($provider && $provider !== 'native') {
        // Non-native providers return discovery info, not the full list
        jsonResponse([
            'success' => true,
            'tools'   => [],
            'note'    => "Use ?action=discover&provider=$provider to explore tools from this provider.",
            'pagination' => ['page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 0],
        ]);
        return;
    }

    // Filter by category
    $category = sanitize($_GET['category'] ?? '', 60);
    if ($category) {
        $registry = array_filter($registry, function ($t) use ($category) {
            return $t['category'] === $category;
        });
    }

    // Filter by tier
    $tier = sanitize($_GET['tier'] ?? '', 20);
    if ($tier && in_array($tier, ['starter', 'professional', 'enterprise'])) {
        $registry = array_filter($registry, function ($t) use ($tier) {
            return $t['tier'] === $tier;
        });
    }

    // Search filter
    $search = sanitize($_GET['q'] ?? $_GET['search'] ?? '', 100);
    if ($search) {
        $searchLower = strtolower($search);
        $registry = array_filter($registry, function ($t) use ($searchLower) {
            return strpos(strtolower($t['name']), $searchLower) !== false
                || strpos(strtolower($t['description']), $searchLower) !== false
                || strpos(strtolower($t['category']), $searchLower) !== false;
        });
    }

    // Re-index
    $registry = array_values($registry);
    $total = count($registry);

    // Add display_name and category_label to each tool
    $registry = array_map(function ($t) {
        $t['display_name'] = humanizeToolName($t['name']);
        $t['category_label'] = humanizeCategoryName($t['category']);
        return $t;
    }, $registry);

    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(100, max(1, intval($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    $paged = array_slice($registry, $offset, $perPage);

    // Aggregate counts from all providers
    $providers = getProviders();
    $totalAllProviders = 0;
    foreach ($providers as $p) {
        $totalAllProviders += $p['tool_count'];
    }

    jsonResponse([
        'success' => true,
        'tools' => $paged,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
        ],
        'provider_summary' => [
            'current_provider' => 'native',
            'native_tools'     => $total,
            'total_all_providers' => $totalAllProviders,
            'providers' => count($providers),
        ],
    ]);
}

/**
 * Get full tool detail by name
 */
function getToolDetail() {
    $name = sanitize($_GET['name'] ?? $_POST['name'] ?? '', 100);
    if (empty($name)) {
        jsonResponse(['error' => 'Tool name required'], 400);
    }

    $registry = getRegistry();
    foreach ($registry as $tool) {
        if ($tool['name'] === $name) {
            // Attach related tool details
            $related = [];
            foreach ($tool['related_tools'] as $rt) {
                foreach ($registry as $r) {
                    if ($r['name'] === $rt) {
                        $related[] = [
                            'name' => $r['name'],
                            'category' => $r['category'],
                            'description' => $r['description'],
                            'icon' => $r['icon'],
                            'tier' => $r['tier'],
                        ];
                        break;
                    }
                }
            }
            $tool['related_tools_detail'] = $related;

            jsonResponse(['success' => true, 'tool' => $tool]);
        }
    }

    jsonResponse(['error' => 'Tool not found'], 404);
}

/**
 * List all categories with counts
 */
function getCategories() {
    $registry = getRegistry();
    $categories = [];

    foreach ($registry as $tool) {
        $cat = $tool['category'];
        if (!isset($categories[$cat])) {
            $categories[$cat] = [
                'name'  => $cat,
                'label' => humanizeCategoryName($cat),
                'icon'  => getCategoryIcon($cat),
                'count' => 0,
                'tools' => [],
            ];
        }
        $categories[$cat]['count']++;
        $categories[$cat]['tools'][] = [
            'name'         => $tool['name'],
            'display_name' => humanizeToolName($tool['name']),
        ];
    }

    // Sort by name
    ksort($categories);
    $result = array_values($categories);

    jsonResponse([
        'success' => true,
        'categories' => $result,
        'total_categories' => count($result),
        'total_tools' => count($registry),
    ]);
}

/**
 * Full text search across tool names and descriptions
 */
function searchTools() {
    $query = sanitize($_GET['q'] ?? $_POST['q'] ?? '', 200);
    if (empty($query) || strlen($query) < 2) {
        jsonResponse(['error' => 'Search query must be at least 2 characters'], 400);
    }

    $registry = getRegistry();
    $queryLower = strtolower($query);
    $terms = array_filter(explode(' ', $queryLower));

    $scored = [];
    foreach ($registry as $tool) {
        $score = 0;
        $nameLower = strtolower($tool['name']);
        $descLower = strtolower($tool['description']);
        $catLower = strtolower($tool['category']);

        foreach ($terms as $term) {
            // Exact name match = highest score
            if ($nameLower === $term) {
                $score += 100;
            } elseif (strpos($nameLower, $term) !== false) {
                $score += 50;
            }

            if (strpos($descLower, $term) !== false) {
                $score += 20;
            }

            if (strpos($catLower, $term) !== false) {
                $score += 10;
            }

            // Demographics match
            foreach ($tool['demographics'] as $demo) {
                if (strpos(strtolower($demo), $term) !== false) {
                    $score += 5;
                }
            }
        }

        if ($score > 0) {
            $tool['_relevance'] = $score;
            $scored[] = $tool;
        }
    }

    // Sort by relevance descending
    usort($scored, function ($a, $b) {
        return $b['_relevance'] - $a['_relevance'];
    });

    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(50, max(1, intval($_GET['per_page'] ?? 20)));
    $total = count($scored);
    $offset = ($page - 1) * $perPage;
    $paged = array_slice($scored, $offset, $perPage);

    // Remove internal score field, add display names
    $paged = array_map(function ($t) {
        unset($t['_relevance']);
        $t['display_name'] = humanizeToolName($t['name']);
        $t['category_label'] = humanizeCategoryName($t['category']);
        return $t;
    }, $paged);

    jsonResponse([
        'success' => true,
        'query' => $query,
        'results' => $paged,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
        ],
    ]);
}

/**
 * Execute a tool (authenticated)
 */
function executeTool() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $toolName = sanitize($_POST['tool'] ?? $_POST['name'] ?? '', 100);
    if (empty($toolName)) {
        jsonResponse(['error' => 'Tool name required'], 400);
    }

    // Verify tool exists
    $registry = getRegistry();
    $toolEntry = null;
    foreach ($registry as $tool) {
        if ($tool['name'] === $toolName) {
            $toolEntry = $tool;
            break;
        }
    }

    if (!$toolEntry) {
        jsonResponse(['error' => 'Tool not found: ' . $toolName], 404);
    }

    $clientId = getClientId();
    $input = sanitize($_POST['input'] ?? '', 2000);
    $params = $_POST['params'] ?? '{}';

    // Validate params JSON
    $paramsDecoded = json_decode($params, true);
    if ($params !== '{}' && $paramsDecoded === null) {
        jsonResponse(['error' => 'Invalid params JSON'], 400);
    }

    $startTime = microtime(true);

    // Log tool usage
    $db = getDB();
    $success = 1;
    $outputSummary = "Tool '$toolName' executed successfully (mock response)";

    try {
        // For now, return mock success response
        // In production, this would forward to vapi-tools.php functions
        $result = [
            'tool' => $toolName,
            'category' => $toolEntry['category'],
            'status' => 'completed',
            'message' => "Tool '$toolName' executed successfully",
            'input_received' => $input ?: null,
            'params_received' => $paramsDecoded,
            'output' => [
                'summary' => "The $toolName tool processed your request. This is a mock response — real execution will be connected to the vapi-tools.php pipeline.",
                'timestamp' => date('c'),
            ],
        ];
    } catch (\Exception $e) {
        $success = 0;
        $outputSummary = "Tool execution failed: " . $e->getMessage();
        error_log("Tool execution error ($toolName): " . $e->getMessage());
        $result = ['error' => 'Tool execution failed'];
    }

    $executionMs = intval((microtime(true) - $startTime) * 1000);

    // Record usage in alfred_tool_usage
    if ($db) {
        try {
            $stmt = $db->prepare("
                INSERT INTO alfred_tool_usage (user_id, tool_name, category, execution_time_ms, success, input_summary, output_summary, used_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $clientId,
                $toolName,
                $toolEntry['category'],
                $executionMs,
                $success,
                substr($input, 0, 500),
                substr($outputSummary, 0, 500),
            ]);
        } catch (\Exception $e) {
            error_log("Tool usage logging failed: " . $e->getMessage());
        }
    }

    jsonResponse([
        'success' => (bool) $success,
        'result' => $result,
        'execution_time_ms' => $executionMs,
    ]);
}

/**
 * Get tool usage stats for current user
 */
function getToolStats() {
    $clientId = getClientId();
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Total usage count
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM alfred_tool_usage WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $total = $stmt->fetch()['total'];

        // Success rate
        $stmt = $db->prepare("SELECT COUNT(*) as successes FROM alfred_tool_usage WHERE user_id = ? AND success = 1");
        $stmt->execute([$clientId]);
        $successes = $stmt->fetch()['successes'];

        // Most used tools
        $stmt = $db->prepare("
            SELECT tool_name, category, COUNT(*) as usage_count, 
                   AVG(execution_time_ms) as avg_time_ms,
                   SUM(success) as success_count
            FROM alfred_tool_usage 
            WHERE user_id = ? 
            GROUP BY tool_name, category 
            ORDER BY usage_count DESC 
            LIMIT 10
        ");
        $stmt->execute([$clientId]);
        $topTools = $stmt->fetchAll();

        // Usage by category
        $stmt = $db->prepare("
            SELECT category, COUNT(*) as usage_count 
            FROM alfred_tool_usage 
            WHERE user_id = ? 
            GROUP BY category 
            ORDER BY usage_count DESC
        ");
        $stmt->execute([$clientId]);
        $byCategory = $stmt->fetchAll();

        // Recent usage (last 20)
        $stmt = $db->prepare("
            SELECT tool_name, category, success, execution_time_ms, used_at 
            FROM alfred_tool_usage 
            WHERE user_id = ? 
            ORDER BY used_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$clientId]);
        $recent = $stmt->fetchAll();

        // Usage over last 30 days
        $stmt = $db->prepare("
            SELECT DATE(used_at) as date, COUNT(*) as count 
            FROM alfred_tool_usage 
            WHERE user_id = ? AND used_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
            GROUP BY DATE(used_at) 
            ORDER BY date ASC
        ");
        $stmt->execute([$clientId]);
        $daily = $stmt->fetchAll();

        // Distinct tools used
        $stmt = $db->prepare("SELECT COUNT(DISTINCT tool_name) as unique_tools FROM alfred_tool_usage WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $uniqueTools = $stmt->fetch()['unique_tools'];

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_executions' => (int) $total,
                'successful_executions' => (int) $successes,
                'success_rate' => $total > 0 ? round(($successes / $total) * 100, 1) : 0,
                'unique_tools_used' => (int) $uniqueTools,
                'top_tools' => $topTools,
                'usage_by_category' => $byCategory,
                'recent_usage' => $recent,
                'daily_usage_30d' => $daily,
            ],
        ]);
    } catch (\Exception $e) {
        error_log("Tool stats error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to retrieve tool stats'], 500);
    }
}
