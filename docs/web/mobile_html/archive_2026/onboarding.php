<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
$page_title = 'Welcome to Alfred AI - Getting Started';
$userName = htmlspecialchars($clientName ?: 'there');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <style>
        :root {
            --al-bg: #0a0a14;
            --al-surface: #12121e;
            --al-surface-2: #1a1a2e;
            --al-accent: #6c5ce7;
            --al-accent-light: #a29bfe;
            --al-text: #e0e0e0;
            --al-text-muted: #8892b0;
            --al-success: #10b981;
            --al-warning: #f59e0b;
            --al-danger: #ef4444;
            --al-border: rgba(255, 255, 255, 0.08);
            --al-radius: 12px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--al-bg);
            color: var(--al-text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Skip link ── */
        .skip-wizard {
            position: fixed;
            top: 20px;
            right: 24px;
            color: var(--al-text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            z-index: 100;
            transition: color 0.2s;
        }
        .skip-wizard:hover { color: var(--al-accent-light); }

        /* ── Progress bar ── */
        .progress-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
            background: var(--al-surface);
            border-bottom: 1px solid var(--al-border);
            padding: 16px 24px;
        }
        .progress-inner {
            max-width: 720px;
            margin: 0 auto;
        }
        .progress-bar {
            height: 6px;
            background: rgba(108, 92, 231, 0.15);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--al-accent), var(--al-accent-light));
            border-radius: 3px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            width: 20%;
        }
        .step-indicators {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .step-dot {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--al-text-muted);
            transition: color 0.3s;
        }
        .step-dot .dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            background: var(--al-surface-2);
            border: 2px solid var(--al-border);
            transition: all 0.3s;
        }
        .step-dot.active .dot {
            background: var(--al-accent);
            border-color: var(--al-accent);
            color: #fff;
            box-shadow: 0 0 12px rgba(108, 92, 231, 0.4);
        }
        .step-dot.completed .dot {
            background: var(--al-success);
            border-color: var(--al-success);
            color: #fff;
        }
        .step-dot .label {
            display: none;
        }
        @media (min-width: 640px) {
            .step-dot .label { display: inline; }
        }

        /* ── Main container ── */
        .onboarding-wrap {
            max-width: 720px;
            margin: 0 auto;
            padding: 110px 20px 60px;
            min-height: 100vh;
        }

        /* ── Steps ── */
        .step {
            display: none;
            animation: fadeSlide 0.4s ease forwards;
        }
        .step.active { display: block; }

        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .step-subtitle {
            color: var(--al-text-muted);
            margin-bottom: 32px;
            font-size: 1rem;
            line-height: 1.6;
        }

        /* ── Alfred mascot ── */
        .mascot {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--al-accent), #a29bfe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
            animation: mascotPulse 3s ease-in-out infinite;
        }
        @keyframes mascotPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(108, 92, 231, 0.3); }
            50% { box-shadow: 0 0 40px rgba(108, 92, 231, 0.5); }
        }

        /* ── Selection cards ── */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .select-card {
            background: var(--al-surface-2);
            border: 2px solid var(--al-border);
            border-radius: var(--al-radius);
            padding: 20px 16px;
            cursor: pointer;
            transition: all 0.25s;
            text-align: center;
        }
        .select-card:hover {
            border-color: rgba(108, 92, 231, 0.4);
            transform: translateY(-2px);
        }
        .select-card.selected {
            border-color: var(--al-accent);
            background: rgba(108, 92, 231, 0.1);
            box-shadow: 0 0 20px rgba(108, 92, 231, 0.15);
        }
        .select-card .card-icon {
            font-size: 1.6rem;
            margin-bottom: 8px;
            display: block;
        }
        .select-card .card-label {
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* ── Form elements ── */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 0.9rem;
            color: var(--al-text-muted);
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: var(--al-surface-2);
            border: 1px solid var(--al-border);
            border-radius: 8px;
            color: var(--al-text);
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--al-accent);
        }
        .form-input::placeholder {
            color: var(--al-text-muted);
            opacity: 0.6;
        }

        /* ── Radio group ── */
        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }
        .radio-pill {
            padding: 8px 16px;
            background: var(--al-surface-2);
            border: 1px solid var(--al-border);
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
            color: var(--al-text-muted);
        }
        .radio-pill:hover {
            border-color: var(--al-accent);
            color: var(--al-text);
        }
        .radio-pill.selected {
            background: var(--al-accent);
            border-color: var(--al-accent);
            color: #fff;
        }

        /* ── Navigation ── */
        .step-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--al-border);
        }
        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--al-accent), #a29bfe);
            color: #fff;
        }
        .btn-primary:hover {
            box-shadow: 0 4px 20px rgba(108, 92, 231, 0.4);
            transform: translateY(-1px);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-secondary {
            background: var(--al-surface-2);
            color: var(--al-text-muted);
            border: 1px solid var(--al-border);
        }
        .btn-secondary:hover {
            color: var(--al-text);
            border-color: rgba(255,255,255,0.15);
        }
        .btn-ghost {
            background: none;
            color: var(--al-text-muted);
            padding: 12px 16px;
        }
        .btn-ghost:hover { color: var(--al-accent-light); }

        .btn .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        .btn.loading .spinner { display: inline-block; }
        .btn.loading .btn-text { display: none; }

        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Template cards ── */
        .template-card {
            background: var(--al-surface-2);
            border: 2px solid var(--al-border);
            border-radius: var(--al-radius);
            padding: 20px;
            cursor: pointer;
            transition: all 0.25s;
            margin-bottom: 12px;
        }
        .template-card:hover { border-color: rgba(108, 92, 231, 0.4); }
        .template-card.selected {
            border-color: var(--al-accent);
            background: rgba(108, 92, 231, 0.08);
        }
        .template-card .tmpl-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .template-card .tmpl-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .template-card .tmpl-name {
            font-weight: 600;
            font-size: 1rem;
        }
        .template-card .tmpl-desc {
            color: var(--al-text-muted);
            font-size: 0.85rem;
            line-height: 1.5;
        }

        /* ── Channel cards ── */
        .channel-card {
            background: var(--al-surface-2);
            border: 2px solid var(--al-border);
            border-radius: var(--al-radius);
            padding: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            cursor: pointer;
            transition: all 0.25s;
        }
        .channel-card:hover { border-color: rgba(108, 92, 231, 0.4); }
        .channel-card.selected {
            border-color: var(--al-accent);
            background: rgba(108, 92, 231, 0.08);
        }
        .channel-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .channel-info h4 { font-weight: 600; margin-bottom: 4px; }
        .channel-info p { color: var(--al-text-muted); font-size: 0.85rem; line-height: 1.5; }

        /* ── Completion ── */
        .completion-hero {
            text-align: center;
            padding: 40px 0 30px;
        }
        .completion-hero .check-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--al-success), #34d399);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .summary-box {
            background: var(--al-surface-2);
            border: 1px solid var(--al-border);
            border-radius: var(--al-radius);
            padding: 20px;
            margin: 24px 0;
            text-align: left;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--al-border);
            font-size: 0.9rem;
        }
        .summary-row:last-child { border-bottom: none; }
        .summary-row .label { color: var(--al-text-muted); }
        .summary-row .value { font-weight: 500; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin: 24px 0;
        }
        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 20px 16px;
            background: var(--al-surface-2);
            border: 1px solid var(--al-border);
            border-radius: var(--al-radius);
            text-decoration: none;
            color: var(--al-text);
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .quick-action:hover {
            border-color: var(--al-accent);
            transform: translateY(-2px);
        }
        .quick-action i { font-size: 1.3rem; color: var(--al-accent-light); }

        .dont-show {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            font-size: 0.85rem;
            color: var(--al-text-muted);
            justify-content: center;
        }
        .dont-show input[type="checkbox"] {
            accent-color: var(--al-accent);
        }

        /* ── Agent customize panel ── */
        .agent-customize {
            display: none;
            background: var(--al-surface);
            border: 1px solid var(--al-border);
            border-radius: var(--al-radius);
            padding: 20px;
            margin-top: 16px;
            animation: fadeSlide 0.3s ease;
        }
        .agent-customize.show { display: block; }

        /* ── Confetti ── */
        .confetti-piece {
            position: fixed;
            width: 10px;
            height: 10px;
            border-radius: 2px;
            top: -10px;
            z-index: 200;
            pointer-events: none;
        }
        @keyframes confettiFall {
            0% { transform: translateY(0) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }

        /* ── Mobile ── */
        @media (max-width: 600px) {
            .step-title { font-size: 1.4rem; }
            .card-grid { grid-template-columns: repeat(2, 1fr); }
            .quick-actions { grid-template-columns: 1fr 1fr; }
            .step-nav { flex-wrap: wrap; gap: 12px; }
            .step-dot .label { display: none; }
        }
        @media (max-width: 480px) {
            .step-title { font-size: 1.2rem; }
            .step-subtitle { font-size: 0.9rem; margin-bottom: 24px; }
            .card-grid { grid-template-columns: 1fr; gap: 10px; }
            .quick-actions { grid-template-columns: 1fr; gap: 10px; }
            .select-card { padding: 16px 12px; }
            .select-card .icon { font-size: 1.4rem; }
            .select-card .title { font-size: 0.85rem; }
            .onboarding-wrap { padding: 20px 16px; }
            .mascot { width: 64px; height: 64px; font-size: 1.6rem; }
            .progress-inner { padding: 0 12px; }
            .step-dot .dot { width: 28px; height: 28px; font-size: 0.7rem; }
            .summary-row { font-size: 0.85rem; padding: 10px 0; }
            .quick-action { padding: 16px 12px; font-size: 0.85rem; min-height: 44px; }
        }
        @media (pointer: coarse) {
            .select-card { min-height: 44px; }
            .quick-action { min-height: 44px; }
            .step-dot { min-height: 44px; }
        }

        /* ── Toast ── */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--al-surface-2);
            border: 1px solid var(--al-border);
            border-radius: 10px;
            padding: 14px 20px;
            font-size: 0.9rem;
            z-index: 300;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error { border-color: var(--al-danger); }
        .toast.success { border-color: var(--al-success); }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>
    <!-- Skip link -->
    <a href="/dashboard" class="skip-wizard">I know what I'm doing → Dashboard</a>

    <!-- Progress bar -->
    <div class="progress-container">
        <div class="progress-inner">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="step-indicators">
                <div class="step-dot active" data-step="1">
                    <span class="dot">1</span>
                    <span class="label">Profile</span>
                </div>
                <div class="step-dot" data-step="2">
                    <span class="dot">2</span>
                    <span class="label">Use Cases</span>
                </div>
                <div class="step-dot" data-step="3">
                    <span class="dot">3</span>
                    <span class="label">First Agent</span>
                </div>
                <div class="step-dot" data-step="4">
                    <span class="dot">4</span>
                    <span class="label">Channels</span>
                </div>
                <div class="step-dot" data-step="5">
                    <span class="dot">5</span>
                    <span class="label">Complete</span>
                </div>
            </div>
        </div>
    </div>

    <div class="onboarding-wrap">

        <!-- ═══════ STEP 1: Welcome & Profile ═══════ -->
        <div class="step active" id="step1">
            <div class="mascot">
                <i class="fas fa-robot"></i>
            </div>
            <h1 class="step-title">Welcome, <?= $userName ?>!</h1>
            <p class="step-subtitle">Let's get you set up with Alfred AI. This will only take a couple of minutes.</p>

            <p style="font-weight:500; margin-bottom:12px;">What best describes you?</p>
            <div class="card-grid" id="roleCards">
                <div class="select-card" data-value="developer" onclick="selectRole(this)">
                    <span class="card-icon">👨‍💻</span>
                    <span class="card-label">Developer</span>
                </div>
                <div class="select-card" data-value="business_owner" onclick="selectRole(this)">
                    <span class="card-icon">🏢</span>
                    <span class="card-label">Business Owner</span>
                </div>
                <div class="select-card" data-value="marketing" onclick="selectRole(this)">
                    <span class="card-icon">📢</span>
                    <span class="card-label">Marketing</span>
                </div>
                <div class="select-card" data-value="customer_support" onclick="selectRole(this)">
                    <span class="card-icon">🎧</span>
                    <span class="card-label">Customer Support</span>
                </div>
                <div class="select-card" data-value="it_devops" onclick="selectRole(this)">
                    <span class="card-icon">⚙️</span>
                    <span class="card-label">IT / DevOps</span>
                </div>
                <div class="select-card" data-value="other" onclick="selectRole(this)">
                    <span class="card-icon">✨</span>
                    <span class="card-label">Other</span>
                </div>
            </div>

            <div class="form-group">
                <label for="companyName">Company Name (optional)</label>
                <input type="text" id="companyName" class="form-input" placeholder="Your company name" maxlength="200">
            </div>

            <p style="font-weight:500; margin-bottom:8px;">Company Size</p>
            <div class="radio-group" id="companySizeGroup">
                <span class="radio-pill" data-value="solo" onclick="selectSize(this)">Solo</span>
                <span class="radio-pill" data-value="2-10" onclick="selectSize(this)">2-10</span>
                <span class="radio-pill" data-value="11-50" onclick="selectSize(this)">11-50</span>
                <span class="radio-pill" data-value="51-200" onclick="selectSize(this)">51-200</span>
                <span class="radio-pill" data-value="200+" onclick="selectSize(this)">200+</span>
            </div>

            <div class="step-nav">
                <div></div>
                <button class="btn btn-primary" onclick="saveStep1()">
                    <span class="btn-text">Next <i class="fas fa-arrow-right"></i></span>
                    <span class="spinner"></span>
                </button>
            </div>
        </div>

        <!-- ═══════ STEP 2: Use Case Selection ═══════ -->
        <div class="step" id="step2">
            <h1 class="step-title">What do you want to do with Alfred?</h1>
            <p class="step-subtitle">Select all that apply — we'll customize your experience.</p>

            <div class="card-grid" id="useCaseCards" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                <div class="select-card" data-value="customer_support" onclick="toggleUseCase(this)">
                    <span class="card-icon">🎧</span>
                    <span class="card-label">Customer Support Automation</span>
                </div>
                <div class="select-card" data-value="voice_agent" onclick="toggleUseCase(this)">
                    <span class="card-icon">📞</span>
                    <span class="card-label">AI Voice Agent / Phone Bot</span>
                </div>
                <div class="select-card" data-value="tool_automation" onclick="toggleUseCase(this)">
                    <span class="card-icon">🔧</span>
                    <span class="card-label">Internal Tool Automation</span>
                </div>
                <div class="select-card" data-value="content_generation" onclick="toggleUseCase(this)">
                    <span class="card-icon">✍️</span>
                    <span class="card-label">Content Generation</span>
                </div>
                <div class="select-card" data-value="data_analysis" onclick="toggleUseCase(this)">
                    <span class="card-icon">📊</span>
                    <span class="card-label">Data Analysis</span>
                </div>
                <div class="select-card" data-value="code_assistant" onclick="toggleUseCase(this)">
                    <span class="card-icon">💻</span>
                    <span class="card-label">Code Assistant</span>
                </div>
                <div class="select-card" data-value="lead_generation" onclick="toggleUseCase(this)">
                    <span class="card-icon">🎯</span>
                    <span class="card-label">Lead Generation</span>
                </div>
                <div class="select-card" data-value="appointment_scheduling" onclick="toggleUseCase(this)">
                    <span class="card-icon">📅</span>
                    <span class="card-label">Appointment Scheduling</span>
                </div>
            </div>

            <div class="step-nav">
                <button class="btn btn-secondary" onclick="goToStep(1)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button class="btn btn-primary" onclick="saveStep2()">
                    <span class="btn-text">Next <i class="fas fa-arrow-right"></i></span>
                    <span class="spinner"></span>
                </button>
            </div>
        </div>

        <!-- ═══════ STEP 3: Create Your First Agent ═══════ -->
        <div class="step" id="step3">
            <h1 class="step-title">Create Your First Agent</h1>
            <p class="step-subtitle">Pick a template to get started — you can always customize later.</p>

            <div id="agentTemplates">
                <div class="template-card" data-template="customer_support" onclick="selectTemplate(this)">
                    <div class="tmpl-header">
                        <div class="tmpl-icon" style="background:rgba(108,92,231,0.15);color:var(--al-accent-light);">
                            <i class="fas fa-headset"></i>
                        </div>
                        <span class="tmpl-name">Customer Support Agent</span>
                    </div>
                    <p class="tmpl-desc">Handles inquiries, resolves issues, and provides helpful answers from your knowledge base.</p>
                </div>
                <div class="template-card" data-template="sales_agent" onclick="selectTemplate(this)">
                    <div class="tmpl-header">
                        <div class="tmpl-icon" style="background:rgba(16,185,129,0.15);color:var(--al-success);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="tmpl-name">Sales Agent</span>
                    </div>
                    <p class="tmpl-desc">Qualifies leads, answers product questions, and guides prospects through the funnel.</p>
                </div>
                <div class="template-card" data-template="knowledge_base" onclick="selectTemplate(this)">
                    <div class="tmpl-header">
                        <div class="tmpl-icon" style="background:rgba(245,158,11,0.15);color:var(--al-warning);">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="tmpl-name">Knowledge Base Bot</span>
                    </div>
                    <p class="tmpl-desc">Answers questions using your company docs and documentation.</p>
                </div>
                <div class="template-card" data-template="voice_receptionist" onclick="selectTemplate(this)">
                    <div class="tmpl-header">
                        <div class="tmpl-icon" style="background:rgba(0,212,255,0.15);color:#00D4FF;">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <span class="tmpl-name">Voice Receptionist</span>
                    </div>
                    <p class="tmpl-desc">Answers calls, routes callers, takes messages, and schedules appointments.</p>
                </div>
            </div>

            <div class="agent-customize" id="agentCustomize">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="agentName">Agent Name</label>
                    <input type="text" id="agentName" class="form-input" placeholder="e.g. My Support Agent" maxlength="100">
                </div>
            </div>

            <div class="step-nav">
                <button class="btn btn-secondary" onclick="goToStep(2)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-ghost" onclick="goToStep(4)">Skip</button>
                    <button class="btn btn-primary" id="createAgentBtn" onclick="saveStep3()" disabled>
                        <span class="btn-text">Create Agent <i class="fas fa-wand-magic-sparkles"></i></span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════ STEP 4: Connect a Channel ═══════ -->
        <div class="step" id="step4">
            <h1 class="step-title">Connect a Channel</h1>
            <p class="step-subtitle">Choose how your agent reaches people. You can add more later.</p>

            <div id="channelCards">
                <div class="channel-card" data-channel="web_chat" onclick="toggleChannel(this)">
                    <div class="channel-icon" style="background:rgba(108,92,231,0.15);color:var(--al-accent-light);">
                        <i class="fas fa-comment-dots"></i>
                    </div>
                    <div class="channel-info">
                        <h4>Web Chat Widget</h4>
                        <p>Embed an AI chat widget on your website. Copy a single script tag and you're live.</p>
                    </div>
                </div>
                <div class="channel-card" data-channel="voice" onclick="toggleChannel(this)">
                    <div class="channel-icon" style="background:rgba(16,185,129,0.15);color:var(--al-success);">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="channel-info">
                        <h4>Voice</h4>
                        <p>Set up AI voice agents to answer calls and make outbound calls automatically.</p>
                    </div>
                </div>
                <div class="channel-card" data-channel="api" onclick="toggleChannel(this)">
                    <div class="channel-icon" style="background:rgba(245,158,11,0.15);color:var(--al-warning);">
                        <i class="fas fa-code"></i>
                    </div>
                    <div class="channel-info">
                        <h4>API</h4>
                        <p>Integrate Alfred into your own apps with our REST API and SDKs.</p>
                    </div>
                </div>
                <div class="channel-card" data-channel="chrome_extension" onclick="toggleChannel(this)">
                    <div class="channel-icon" style="background:rgba(0,212,255,0.15);color:#00D4FF;">
                        <i class="fab fa-chrome"></i>
                    </div>
                    <div class="channel-info">
                        <h4>Chrome Extension</h4>
                        <p>Use Alfred directly from your browser on any website you visit.</p>
                    </div>
                </div>
            </div>

            <div class="step-nav">
                <button class="btn btn-secondary" onclick="goToStep(3)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-ghost" onclick="skipToComplete()">Skip for now</button>
                    <button class="btn btn-primary" onclick="saveStep4()">
                        <span class="btn-text">Next <i class="fas fa-arrow-right"></i></span>
                        <span class="spinner"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══════ STEP 5: Complete! ═══════ -->
        <div class="step" id="step5">
            <div class="completion-hero">
                <div class="check-circle">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="step-title">You're All Set! 🎉</h1>
                <p class="step-subtitle">Alfred is ready to work for you. Here's a summary of what we set up.</p>
            </div>

            <div class="summary-box" id="summaryBox">
                <div class="summary-row">
                    <span class="label">Role</span>
                    <span class="value" id="sumRole">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">Company</span>
                    <span class="value" id="sumCompany">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">Use Cases</span>
                    <span class="value" id="sumUseCases">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">First Agent</span>
                    <span class="value" id="sumAgent">—</span>
                </div>
                <div class="summary-row">
                    <span class="label">Channels</span>
                    <span class="value" id="sumChannels">—</span>
                </div>
            </div>

            <div class="quick-actions">
                <a href="/dashboard" class="quick-action">
                    <i class="fas fa-gauge-high"></i>
                    Go to Dashboard
                </a>
                <a href="/alfred-tools" class="quick-action">
                    <i class="fas fa-toolbox"></i>
                    Explore Tools
                </a>
                <a href="/docs/getting-started" class="quick-action">
                    <i class="fas fa-book-open"></i>
                    Read the Docs
                </a>
                <a href="#" class="quick-action" onclick="showToast('Community coming soon!','success');return false;">
                    <i class="fas fa-users"></i>
                    Join Community
                </a>
            </div>

            <div class="dont-show">
                <input type="checkbox" id="dontShowAgain" onchange="markDontShow()">
                <label for="dontShowAgain">Don't show this wizard again</label>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div class="toast" id="toast"></div>

<script src="/assets/js/onboarding-engine.js"></script>
</body>
</html>
