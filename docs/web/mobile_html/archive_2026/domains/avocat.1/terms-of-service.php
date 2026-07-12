<?php
/**
 * Terms of Service - GoSiteMe
 * AI Platform, Robotics, IoT, VR/Metaverse, App Store & Hosting
 * Last Updated: March 2026
 */
require_once __DIR__ . '/includes/lang.php';
$pageTitle = 'Terms of Service — GoSiteMe';
$pageDescription = 'GoSiteMe Terms of Service covering AI Platform, Web Hosting, Robotics, IoT, VR/Metaverse, App Store and related services.';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
    .tos-container { max-width: 860px; margin: 0 auto; padding: 3rem 1.5rem 4rem; color: #e0e0e0; line-height: 1.75; }
    .tos-container h1 { font-size: 2rem; margin-bottom: 0.5rem; color: #fff; }
    .tos-container .updated { color: #aaa; font-size: 0.9rem; margin-bottom: 2rem; display: block; }
    .tos-container h2 { font-size: 1.3rem; margin-top: 2.5rem; padding-bottom: 0.4rem; border-bottom: 1px solid rgba(255,255,255,0.1); color: #00d4ff; }
    .tos-container h3 { font-size: 1.1rem; margin-top: 1.5rem; color: #fff; }
    .tos-container p, .tos-container li { font-size: 0.95rem; }
    .tos-container ul, .tos-container ol { padding-left: 1.5rem; }
    .tos-container li { margin-bottom: 0.4rem; }
    .tos-container a { color: #00d4ff; text-decoration: underline; }
    .tos-container a:hover { color: #fff; }
    .tos-container strong { color: #fff; }
    .tos-container .highlight-box { background: rgba(0,212,255,0.08); border-left: 3px solid #00d4ff; padding: 1rem 1.25rem; margin: 1.5rem 0; border-radius: 0 6px 6px 0; }
    .tos-container .warning-box { background: rgba(255,80,80,0.08); border-left: 3px solid #ff5050; padding: 1rem 1.25rem; margin: 1.5rem 0; border-radius: 0 6px 6px 0; }
    .tos-container hr { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 2rem 0; }
    .tos-container table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    .tos-container th, .tos-container td { padding: 0.6rem 1rem; text-align: left; border: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; }
    .tos-container th { background: rgba(0,212,255,0.1); color: #00d4ff; }
</style>

<div class="tos-container">

    <h1>Terms of Service</h1>
    <span class="updated"><strong>Last Updated:</strong> March 8, 2026 &nbsp;|&nbsp; <strong>Effective Date:</strong> March 8, 2026</span>

    <p>Welcome to <strong>GoSiteMe</strong> ("Company", "we", "us", "our"), a privately held technology company operating at <a href="https://gositeme.com">https://gositeme.com</a>. These Terms of Service ("Terms", "Agreement") constitute a legally binding contract between you ("User", "you", "your", "Subscriber", "Developer", "Operator") and GoSiteMe governing your access to and use of our AI development platform, robotics and IoT services, virtual reality environments, application marketplace, encrypted communications, voice technology, hosting services, cryptocurrency features, and all related products, hardware, software, and services (collectively, the "Services").</p>

    <p><strong>BY CREATING AN ACCOUNT, PURCHASING A PLAN, DOWNLOADING SOFTWARE, CONNECTING A DEVICE, SUBMITTING AN APPLICATION, OR USING ANY OF OUR SERVICES IN ANY MANNER, YOU ACKNOWLEDGE THAT YOU HAVE READ, UNDERSTOOD, AND AGREE TO BE BOUND BY THESE TERMS. IF YOU DO NOT AGREE TO ALL OF THESE TERMS, DO NOT ACCESS OR USE THE SERVICES.</strong></p>

    <div class="warning-box">
        <strong>IMPORTANT LEGAL NOTICES</strong><br>
        These Terms contain a <strong>binding arbitration clause</strong> (Section 28) and a <strong>class action waiver</strong> (Section 28.5). By agreeing to these Terms you waive your right to participate in a class action lawsuit or class-wide arbitration. These Terms also contain <strong>limitations of liability</strong> (Section 23) and <strong>assumption of risk provisions</strong> (Sections 12, 13, 14, 16) that affect your legal rights.
    </div>

    <div class="highlight-box">
        <strong>Company Information</strong><br>
        GoSiteMe &mdash; Quebec, Canada<br>
        Phone: 1-833-GOSITEME (1-833-467-4836)<br>
        Email: <a href="mailto:legal@gositeme.com">legal@gositeme.com</a><br>
        Support: 24/7 via live chat, email, and AI assistant
    </div>

    <hr>

    <h2>1. Definitions</h2>
    <p>In these Terms, the following definitions apply:</p>
    <ul>
        <li><strong>"Platform"</strong> &mdash; The GoCodeMe AI development environment, including the web-based IDE, desktop applications, mobile applications, AI assistant (Alfred), all integrated tools, APIs, SDKs, and associated documentation.</li>
        <li><strong>"Tokens"</strong> &mdash; Usage credits consumed when interacting with AI engines (language models, image generators, video generators, text-to-speech, code generation, robotics control, and other AI-powered features).</li>
        <li><strong>"Plan"</strong> &mdash; A recurring subscription (monthly or annual) that includes hosting, a token allocation, and platform access.</li>
        <li><strong>"Add-on"</strong> &mdash; An optional recurring service purchased in addition to a Plan.</li>
        <li><strong>"Token Pack"</strong> &mdash; A one-time, non-recurring purchase of additional tokens.</li>
        <li><strong>"AI Server"</strong> &mdash; A custom-configured dedicated AI server built through our AI Server Configurator.</li>
        <li><strong>"User Content"</strong> &mdash; Any code, files, data, media, applications, voice recordings, commands, prompts, configurations, or other materials you create, upload, transmit, or store using our Services.</li>
        <li><strong>"AI Agent"</strong> &mdash; An autonomous or semi-autonomous artificial intelligence agent operating within the GoSiteMe ecosystem, including but not limited to Alfred and any fleet-managed agents.</li>
        <li><strong>"Robotic Device" / "Robot"</strong> &mdash; Any physical hardware device, robotic system, drone, autonomous machine, or IoT-connected device that interfaces with, is controlled by, or receives instructions from our Platform, including but not limited to home robots, companion robots, robotic assistants, robotic pets, butler robots, security robots, cleaning robots, and industrial robotic systems.</li>
        <li><strong>"Alfred Robot" / "GoSiteMe Hardware"</strong> &mdash; Physical robotic hardware designed, manufactured, assembled, or sold by GoSiteMe or its authorized manufacturing partners, including the Alfred humanoid robot line and any successor models, components, accessories, charging stations, and spare parts. This is distinct from third-party devices that merely connect to the Platform.</li>
        <li><strong>"Purchaser" / "Buyer"</strong> &mdash; Any individual or entity that purchases GoSiteMe Hardware, whether or not they also subscribe to Platform Services.</li>
        <li><strong>"Hardware Warranty Period"</strong> &mdash; The limited warranty period applicable to GoSiteMe Hardware as specified in Section 57.</li>
        <li><strong>"Manufacturing Defect"</strong> &mdash; A defect arising from the manufacturing process that causes a unit to deviate materially from GoSiteMe's published product specifications at the time of manufacture, not resulting from normal wear and tear, misuse, unauthorized modification, or external causes.</li>
        <li><strong>"Authorized Service Provider"</strong> &mdash; A repair facility or technician authorized by GoSiteMe to perform warranty repairs, maintenance, and service on GoSiteMe Hardware.</li>
        <li><strong>"IoT Device"</strong> &mdash; Any Internet of Things device, sensor, smart home component, wearable, connected appliance, or embedded system that communicates with our Platform.</li>
        <li><strong>"VR Environment"</strong> &mdash; Any virtual reality, augmented reality, mixed reality, or metaverse experience provided through our Services, including 3D worlds, immersive spaces, games, and social environments.</li>
        <li><strong>"App Store"</strong> &mdash; The GoSiteMe application marketplace where third-party developers may publish, distribute, and sell software applications, plugins, extensions, themes, templates, datasets, AI models, and digital goods.</li>
        <li><strong>"Store Application"</strong> &mdash; Any application, extension, plugin, theme, AI model, dataset, or digital good listed on or distributed through the App Store.</li>
        <li><strong>"Veil Protocol"</strong> &mdash; Our end-to-end encrypted communications protocol and associated messaging, payment, and command-and-control features.</li>
        <li><strong>"GSM Token"</strong> &mdash; The GoSiteMe utility token operating on the Solana blockchain, used for in-platform transactions, staking, governance, and other designated purposes.</li>
        <li><strong>"Voice Clone"</strong> &mdash; A synthetic voice model generated from voice samples provided by or on behalf of a user.</li>
        <li><strong>"Fleet"</strong> &mdash; A collection of AI Agents or Robotic Devices managed under a single account or organizational hierarchy.</li>
        <li><strong>"Enterprise Customer"</strong> &mdash; An organization that has entered into a separate enterprise agreement with GoSiteMe.</li>
        <li><strong>"Biometric Data"</strong> &mdash; Data derived from biological characteristics, including voice prints, facial geometry, behavioral patterns, gait analysis, and hand tracking data.</li>
    </ul>

    <hr>

    <h2>2. Account Registration</h2>
    <h3>2.1 Eligibility</h3>
    <p>You must be at least 18 years old (or the age of majority in your jurisdiction, whichever is greater) to create an account. By registering, you represent and warrant that: (a) all information provided is accurate, current, and complete; (b) you have the legal capacity to enter into these Terms; (c) you are not prohibited from using the Services under applicable law; and (d) if registering on behalf of an organization, you have the authority to bind that organization to these Terms.</p>

    <h3>2.2 Account Security</h3>
    <p>You are solely responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You must immediately notify us at <a href="mailto:security@gositeme.com">security@gositeme.com</a> of any unauthorized access or security breach. We are not liable for any losses arising from unauthorized use of your account, whether or not you were aware of such use.</p>

    <h3>2.3 One Account Per Person</h3>
    <p>Each individual may maintain one personal account. Creating multiple accounts to abuse free trials, promotional offers, token allocations, or to circumvent enforcement actions is prohibited and may result in permanent termination of all associated accounts without refund.</p>

    <h3>2.4 Organizational Accounts</h3>
    <p>Organizations may create team accounts with multiple user seats. The organization administrator assumes full responsibility for all activity by team members and compliance with these Terms by all authorized users.</p>

    <hr>

    <h2>3. Services & Plans</h2>
    <h3>3.1 AI Development Platform Plans</h3>
    <p>We offer subscription plans that include:</p>
    <ul>
        <li>Web-based AI development environment (GoCodeMe IDE)</li>
        <li>AI assistant (Alfred) with access to 1,220+ tools across 17 AI engines</li>
        <li>Monthly token allocation for AI interactions</li>
        <li>Web hosting with SSL certificate</li>
        <li>Domain registration (on eligible plans)</li>
        <li>Email accounts and storage</li>
        <li>Access to VR environments and social features</li>
        <li>Access to the App Store</li>
        <li>APIs and SDKs for development</li>
    </ul>
    <p>Plan features, pricing, and token allocations are detailed on our <a href="/alfred.php">pricing page</a> and are subject to change with 30 days' notice to existing subscribers.</p>

    <h3>3.2 Token Usage</h3>
    <ul>
        <li>Tokens are consumed based on AI engine usage, robotics control commands, voice synthesis, image/video generation, and other AI-powered features. Different engines and operations consume tokens at different rates.</li>
        <li>Monthly token allocations reset on your billing cycle date. <strong>Unused tokens do not roll over</strong> to the next billing period.</li>
        <li>When monthly tokens are exhausted, AI features become unavailable until the next billing cycle or until additional Token Packs are purchased.</li>
        <li>Token consumption rates are published in our documentation and may be adjusted as AI model costs change.</li>
    </ul>

    <h3>3.3 Token Packs</h3>
    <ul>
        <li>Token Packs are one-time purchases that supplement your plan's monthly allocation.</li>
        <li>Purchased tokens are consumed <strong>after</strong> your monthly plan tokens are depleted.</li>
        <li>Token Packs do not expire while your account remains active.</li>
        <li>Token Packs are <strong>non-refundable</strong> once tokens have been partially consumed.</li>
    </ul>

    <h3>3.4 Add-ons</h3>
    <p>Optional add-ons (extra storage, priority support, team collaboration seats, backup protection, dedicated GPU allocation, premium VR access, robotics control tiers, etc.) are billed alongside your plan. Add-ons require an active plan subscription.</p>

    <h3>3.5 SSL Certificates</h3>
    <p>SSL certificate products are provided through third-party certificate authorities. Issuance, validation, and renewal are subject to the certificate authority's policies. We facilitate provisioning but do not guarantee approval by the certificate authority.</p>

    <h3>3.6 Custom AI Servers</h3>
    <p>Custom AI servers are built-to-order hardware configurations. Due to the custom nature of these products, specific cancellation and refund terms are provided at the time of purchase.</p>

    <h3>3.7 Domain Registration</h3>
    <p>Domain names are registered through accredited registrars and are subject to ICANN policies and the respective registry's terms. Domain registration fees are <strong>non-refundable</strong> once the domain has been registered.</p>

    <h3>3.8 Right to Modify Services</h3>
    <p>GoSiteMe reserves the right to modify, update, enhance, deprecate, or discontinue any feature, functionality, API, AI model, integration, or component of the Services at any time, with or without notice. For material changes that reduce core functionality of a paid plan, we will provide at least 30 days' prior notice. Continued use of the Services after such changes constitutes acceptance. Deprecation of a Service does not entitle you to a refund for prior periods.</p>

    <h3>3.9 Monitoring, Audit &amp; Compliance Verification</h3>
    <p>GoSiteMe reserves the right, at its sole discretion and with reasonable notice (at least 5 business days), to audit, monitor, inspect, and verify your use of the Services for compliance with these Terms, applicable laws, and your subscription plan. Audits may include but are not limited to:</p>
    <ul>
        <li>Automated monitoring of API calls, token consumption, resource usage, and account activity</li>
        <li>Verification of license compliance, seat counts, and plan entitlements</li>
        <li>Review of AI Agent configurations, Voice Clone consent records, and Robotic Device deployment logs for compliance with safety and regulatory requirements</li>
        <li>Fraud detection and abuse prevention analysis</li>
        <li>Verification of compliance with export control laws, AUP, and applicable data protection regulations</li>
    </ul>
    <p>You shall cooperate with any audit and provide reasonable access to records and information necessary for verification. If an audit reveals a material breach or underpayment, you shall promptly cure the breach and pay any shortfall, along with GoSiteMe's reasonable costs of conducting the audit. This right survives termination for a period of 24 months.</p>

    <h3>3.10 Subcontracting</h3>
    <p>GoSiteMe may delegate or subcontract any of its obligations under these Terms to qualified third-party service providers, sub-processors, or affiliates without your prior consent, provided that GoSiteMe remains responsible for the performance of the subcontracted obligations and ensures that subcontractors are bound by confidentiality and data protection obligations no less protective than those set forth in these Terms.</p>

    <hr>

    <h2>4. Billing & Payments</h2>
    <h3>4.1 Currency & Taxes</h3>
    <p>All prices are listed in US Dollars (USD) and Canadian Dollars (CAD). Applicable taxes (including GST/HST and QST for Canadian residents) will be added at checkout as required by law.</p>

    <h3>4.2 Billing Cycle</h3>
    <p>Plans are billed in advance on a monthly or annual basis, depending on your selection. Annual plans receive a discount as displayed at checkout.</p>

    <h3>4.3 Payment Methods</h3>
    <p>We accept major credit cards (Visa, Mastercard, American Express), PayPal, cryptocurrency payments (via Solana and GSM Token where available), and other payment methods as made available through our billing system.</p>

    <h3>4.4 Failed Payments</h3>
    <p>If a payment fails, we will attempt to charge your payment method up to three (3) additional times over 7 days. If all attempts fail, your account may be suspended. Suspended accounts have 14 days to resolve the payment before data may be permanently deleted.</p>

    <h3>4.5 Price Changes</h3>
    <p>We may adjust pricing with at least 30 days' written notice (via email). Existing subscribers will be notified before any price increase takes effect on their next billing cycle.</p>

    <h3>4.6 Cryptocurrency Payments</h3>
    <p>Payments made via cryptocurrency are final and non-reversible once confirmed on the blockchain. Exchange rate fluctuations between the time of transaction initiation and blockchain confirmation are borne by the User. We are not responsible for tokens sent to incorrect wallet addresses.</p>

    <hr>

    <h2>5. Refund Policy</h2>

    <div class="highlight-box">
        <strong>30-Day Money-Back Guarantee</strong><br>
        New subscribers may request a full refund within 30 days of their initial purchase. This applies to hosting plans only.
    </div>

    <h3>5.1 Eligible for Refund</h3>
    <ul>
        <li>Hosting plan subscriptions within 30 days of initial purchase</li>
        <li>Annual plan renewals within 30 days of the renewal date</li>
        <li>Unused, unconsumed Token Packs (full refund)</li>
    </ul>

    <h3>5.2 Not Eligible for Refund</h3>
    <ul>
        <li>Domain registration fees (non-refundable once registered)</li>
        <li>SSL certificate fees (once issued by the certificate authority)</li>
        <li>Token Packs where tokens have been partially consumed</li>
        <li>Custom AI Server orders (custom hardware; see order-specific terms)</li>
        <li>Add-on charges for the current billing period</li>
        <li>Accounts terminated for Terms of Service violations</li>
        <li>App Store purchases of digital goods where content has been accessed or downloaded</li>
        <li>Voice Clone creation fees (once voice model has been generated)</li>
        <li>Robotic Device or IoT Device hardware deposits or custom configuration fees</li>
        <li>Cryptocurrency or GSM Token purchase transactions</li>
        <li>VR asset or virtual property purchases</li>
    </ul>

    <h3>5.3 How to Request a Refund</h3>
    <p>Submit a refund request via email to <a href="mailto:support@gositeme.com">support@gositeme.com</a> or through your client area support ticket. Refunds are processed within 5&ndash;10 business days to the original payment method.</p>

    <hr>

    <h2>6. Acceptable Use Policy</h2>
    <p>You agree not to use our Services to:</p>
    <ul>
        <li>Violate any applicable local, provincial, national, or international law or regulation</li>
        <li>Host, distribute, or generate illegal, defamatory, harmful, threatening, or obscene content</li>
        <li>Send spam, phishing emails, or engage in fraudulent activities</li>
        <li>Conduct DDoS attacks, port scanning, or other network abuse</li>
        <li>Mine cryptocurrency using platform resources without authorization</li>
        <li>Attempt to gain unauthorized access to other accounts, systems, or Robotic Devices</li>
        <li>Reverse-engineer, decompile, disassemble, or attempt to extract source code from our platform, AI models, robotics firmware, or any proprietary software</li>
        <li>Use AI tools to generate content that violates third-party intellectual property rights</li>
        <li>Circumvent or abuse token metering, usage limits, or promotional offers</li>
        <li>Resell or redistribute AI-generated outputs as a competing AI service</li>
        <li>Use automated scripts to bulk-generate content in a manner that degrades service for others</li>
        <li>Use Voice Cloning to impersonate individuals without their explicit written consent</li>
        <li>Create deepfake content intended to deceive, defraud, harass, or defame any person</li>
        <li>Use Robotic Devices in a manner that endangers human safety, property, or the environment</li>
        <li>Deploy AI Agents for autonomous decision-making in life-critical, medical, nuclear, aviation, or weapons systems without a separate enterprise agreement</li>
        <li>Use VR Environments to harass, stalk, threaten, or exploit other users</li>
        <li>Distribute malware, viruses, ransomware, or malicious code through the App Store or any Service</li>
        <li>Use the Veil Protocol for illegal activities including but not limited to money laundering, terrorism financing, drug trafficking, or child exploitation</li>
        <li>Operate Robotic Devices in violation of local drone, robotics, or autonomous vehicle regulations</li>
        <li>Engage in market manipulation, wash trading, pump-and-dump schemes, or fraudulent activities involving GSM Token or any cryptocurrency feature</li>
    </ul>
    <p>Violations may result in immediate suspension or permanent termination without refund, and may be reported to law enforcement authorities.</p>

    <h3>6.2 Anti-Circumvention</h3>
    <p>You shall not circumvent, disable, interfere with, or otherwise bypass any technological protection measure, access control, digital rights management (DRM), license verification, token metering, rate limiting, usage tracking, geographic restriction, device firmware lock, API authentication, or security mechanism included in or protecting the Services, including but not limited to:</p>
    <ul>
        <li>Modifying, patching, or reverse-engineering client software, mobile applications, desktop applications, firmware, or browser extensions to bypass license checks or usage limits</li>
        <li>Using proxies, VPNs, or IP spoofing to circumvent geographic restrictions or access controls</li>
        <li>Tampering with token consumption counters, API call metering, or billing instrumentation</li>
        <li>Extracting, cloning, or redistributing encryption keys, API keys, authentication tokens, or session credentials</li>
        <li>Modifying or flashing Robotic Device or IoT Device firmware to remove safety constraints, licensing restrictions, or telemetry mechanisms</li>
    </ul>
    <p>This prohibition applies to the fullest extent permitted by applicable law, including the <em>Digital Millennium Copyright Act</em> (DMCA) &sect;1201, the <em>Computer Fraud and Abuse Act</em> (CFAA), Canada's <em>Copyright Modernization Act</em> (2012), and the EU <em>Directive on the Legal Protection of Computer Programs</em>. Circumvention of any technological measure constitutes a material breach of these Terms and may subject you to civil and criminal liability.</p>

    <hr>

    <h2>7. AI-Specific Terms</h2>
    <h3>7.1 AI Output Ownership</h3>
    <p>You retain ownership of original content you create using our AI tools, subject to the following:</p>
    <ul>
        <li>AI-generated code, text, images, video, audio, 3D models, robotics instructions, and any other outputs are provided <strong>"AS IS"</strong> without any warranty of originality, accuracy, fitness for a particular purpose, or non-infringement</li>
        <li>You are solely responsible for reviewing, testing, and validating all AI outputs before use in any environment, including but not limited to production systems, robotic devices, medical applications, financial decisions, and legal matters</li>
        <li>We do not guarantee that AI-generated content is free from errors, biases, hallucinations, or intellectual property conflicts</li>
        <li>You assume full responsibility for any consequences arising from your use or reliance on AI-generated outputs</li>
    </ul>

    <h3>7.2 AI Engine Availability</h3>
    <p>We integrate multiple third-party AI engines. Engine availability depends on upstream provider status. We reserve the right to add, remove, or substitute AI engines at any time without prior notice.</p>

    <h3>7.3 Content Filtering</h3>
    <p>AI interactions are subject to content filtering policies. We may refuse to process requests that generate harmful, illegal, or explicit content. Content filtering rules are determined by both our own policies and upstream AI provider policies.</p>

    <h3>7.4 AI Data Usage</h3>
    <p>Your AI prompts and outputs are <strong>not</strong> used to train AI models. We may log interactions for billing verification, abuse prevention, safety monitoring, and service improvement. See our <a href="/privacy-policy.php">Privacy Policy</a> for full details.</p>

    <h3>7.4.1 Aggregate &amp; Anonymized AI Data</h3>
    <p>GoSiteMe owns all rights, title, and interest in Aggregate Data and De-Identified Data derived from your use of the Services, including but not limited to: aggregate usage statistics, anonymized performance metrics, model benchmarking data, platform-wide trend analyses, and derived insights that do not identify you or any individual. GoSiteMe may use such data for any lawful purpose, including research, analytics, product development, benchmarking reports, and improving AI model selection — without restriction or compensation. This clause survives termination of your account.</p>

    <h3>7.5 AI Agent Autonomy & Fleet Management</h3>
    <ul>
        <li>AI Agents operating within your fleet act under <strong>your authority and responsibility</strong>. You are responsible for configuring appropriate safety boundaries, guardrails, and human-in-the-loop oversight for all AI Agent operations.</li>
        <li>GoSiteMe provides tools for fleet management, agent orchestration, and behavioral configuration. The Company is not responsible for actions taken by AI Agents configured or directed by users.</li>
        <li>AI Agents are not legal persons and cannot enter into binding agreements, make legally binding decisions, or assume legal liability on your behalf.</li>
        <li>You must maintain meaningful human oversight over AI Agent operations, especially when agents interact with physical systems, financial instruments, or other persons.</li>
        <li>We reserve the right to impose safety limits, rate limits, or behavioral constraints on AI Agents to protect our platform, other users, and the public.</li>
    </ul>

    <h3>7.6 AI Safety</h3>
    <p>We implement AI safety measures including content filtering, behavioral guardrails, rate limiting, and anomaly detection. Despite these measures, <strong>AI systems are inherently probabilistic and may produce unexpected, incorrect, or harmful outputs</strong>. You are responsible for implementing additional safety measures appropriate to your use case.</p>

    <hr>

    <h2>8. Voice Technology & Voice Cloning Terms</h2>
    <h3>8.1 Voice Cloning Consent</h3>
    <ul>
        <li>You may only create a Voice Clone using voice samples for which you have <strong>express written consent</strong> from the voice owner, or which are your own voice.</li>
        <li>You represent and warrant that you have obtained all necessary rights, consents, and releases from any individual whose voice is used to create a Voice Clone.</li>
        <li>You must retain records of consent for a minimum of five (5) years and make them available to GoSiteMe upon request.</li>
    </ul>

    <h3>8.2 Prohibited Voice Uses</h3>
    <ul>
        <li>Creating Voice Clones of public figures, celebrities, or deceased individuals without proper authorization from their estate or legal representatives</li>
        <li>Using Voice Clones for fraud, impersonation, social engineering, identity theft, or any deceptive purpose</li>
        <li>Generating synthetic voice content that could reasonably be mistaken for a real person's voice without appropriate disclosure</li>
        <li>Using Voice Clones in political advertising, deepfake pornography, or harassment</li>
    </ul>

    <h3>8.3 Biometric Data Consent</h3>
    <p>Voice Cloning involves the creation and processing of biometric data (voice prints). By using Voice Cloning features, you consent to the creation and storage of biometric data as described in our <a href="/privacy-policy.php">Privacy Policy</a>. Where required by applicable law (including but not limited to the Illinois Biometric Information Privacy Act, Texas CUBI, and Washington State biometric identifiers law), additional consent mechanisms will be provided.</p>

    <h3>8.4 Voice Data Ownership</h3>
    <p>You retain ownership of Voice Clones you create. We retain a limited license to process and store voice data on our servers solely for the purpose of providing the Voice Cloning service. You may request deletion of your Voice Clone data at any time.</p>

    <hr>

    <h2>9. Robotics & IoT Terms</h2>

    <div class="warning-box">
        <strong>PHYSICAL DEVICE SAFETY WARNING</strong><br>
        Robotic Devices and IoT Devices are physical systems capable of movement, heat generation, electrical discharge, and other potentially dangerous actions. <strong>IMPROPER USE, CONFIGURATION, OR SUPERVISION OF ROBOTIC DEVICES CAN RESULT IN PROPERTY DAMAGE, PERSONAL INJURY, OR DEATH.</strong> You assume all risk associated with the operation of Robotic Devices and IoT Devices connected to our Platform, except to the extent that damage arises from a Manufacturing Defect in GoSiteMe Hardware (see Section 57).
    </div>

    <h3>9.1 Operator Responsibility</h3>
    <ul>
        <li>You are the <strong>sole operator</strong> of any Robotic Device or IoT Device connected to our Platform. You bear full responsibility for the safe operation, maintenance, supervision, and compliance of all connected devices.</li>
        <li>For GoSiteMe Hardware (Alfred Robots): GoSiteMe retains concurrent responsibilities as manufacturer, including obligations for design safety, manufacturing quality, and adequate warnings. These manufacturer obligations are detailed in Section 57 and do not diminish your operator responsibilities.</li>
        <li>You must ensure that all Robotic Devices comply with applicable local, state/provincial, national, and international laws and regulations, including but not limited to robotics safety standards, drone regulations, building codes, fire codes, and accessibility requirements.</li>
        <li>You must maintain adequate insurance coverage for all Robotic Devices, including general liability, property damage, and product liability insurance as appropriate for the device type and use case.</li>
    </ul>

    <h3>9.2 Home & Residential Robotics</h3>
    <ul>
        <li>Robotic Devices operating in residential environments (home robots, robotic pets, butler robots, cleaning robots, security robots) must be supervised by a competent adult at all times during initial deployment and configuration.</li>
        <li>You are responsible for ensuring that Robotic Devices in your home do not pose hazards to occupants, visitors, children, pets, or property.</li>
        <li>GoSiteMe does not guarantee that Robotic Devices will perform any specific function safely or correctly. <strong>Robotic Devices are provided for convenience and assistance purposes only and should not be relied upon for life-safety, medical care, child supervision, or security functions without independent backup systems.</strong></li>
        <li>You acknowledge that home robots equipped with cameras, microphones, and sensors will collect ambient data from your home environment. You are responsible for notifying all household members, guests, and visitors of the presence and capabilities of such devices.</li>
    </ul>

    <h3>9.3 Commercial & Industrial Robotics</h3>
    <p>Use of our Platform to control Robotic Devices in commercial, industrial, healthcare, agricultural, or public-facing environments requires a separate Enterprise Agreement. Contact <a href="mailto:enterprise@gositeme.com">enterprise@gositeme.com</a> for enterprise robotics terms.</p>

    <h3>9.4 IoT Device Data</h3>
    <ul>
        <li>IoT Devices connected to our Platform may transmit sensor data, telemetry, environmental data, location data, usage patterns, and other information to our servers. See our <a href="/privacy-policy.php">Privacy Policy</a> for details on how this data is collected, processed, and stored.</li>
        <li>You are responsible for securing IoT Devices on your network and ensuring firmware is kept up to date.</li>
        <li>We are not responsible for security breaches, unauthorized access, or data leaks resulting from improperly secured IoT Devices on your network.</li>
    </ul>

    <h3>9.5 Firmware & Software Updates</h3>
    <p>We may push firmware and software updates to connected Robotic Devices and IoT Devices. Critical safety updates may be applied automatically. You agree that we may remotely update device software when necessary to address safety vulnerabilities, comply with legal requirements, or maintain platform compatibility. You may opt out of non-critical updates through device settings, but we are not responsible for issues arising from running outdated software.</p>

    <h3>9.6 Device Recalls & Safety Notices</h3>
    <p>In the event of a safety concern, we reserve the right to remotely disable, restrict, or recall functionality of any Robotic Device or IoT Device connected to our Platform. You agree to comply with any safety notices or recall instructions issued by GoSiteMe or device manufacturers.</p>
    <p><strong>For GoSiteMe Hardware:</strong> GoSiteMe acknowledges its obligations as manufacturer under applicable consumer product safety laws, including Health Canada's <em>Canada Consumer Product Safety Act</em>, the U.S. <em>Consumer Product Safety Act</em> (CPSC Section 15(b) reporting), and the EU <em>General Product Safety Regulation</em> (GPSR). In the event of a safety recall of GoSiteMe Hardware:</p>
    <ul>
        <li>GoSiteMe will notify all affected Purchasers via email, dashboard notification, and on-device alert within 72 hours of determining a recall is necessary</li>
        <li>GoSiteMe will offer, at GoSiteMe's sole expense: repair, replacement, or full refund at the Purchaser's election</li>
        <li>GoSiteMe will cover all shipping and handling costs for recalled units</li>
        <li>GoSiteMe will report the defect to applicable regulatory authorities as required by law</li>
        <li>GoSiteMe will publish a public safety notice on its website for the duration of the recall</li>
    </ul>

    <h3>9.7 No Medical, Life-Safety, or Critical Infrastructure Use</h3>
    <p><strong>Our robotics and IoT Services are NOT certified or intended for use in medical devices, life-support systems, nuclear facilities, air traffic control, autonomous vehicles on public roads, weapons systems, or any application where failure could reasonably be expected to result in death, personal injury, or severe environmental damage.</strong> Any such use is strictly at your own risk and without any warranty or liability from GoSiteMe.</p>

    <hr>

    <h2>10. Virtual Reality & Metaverse Terms</h2>
    <h3>10.1 VR Health & Safety</h3>

    <div class="warning-box">
        <strong>VR HEALTH WARNING</strong><br>
        Virtual reality experiences may cause motion sickness, dizziness, disorientation, eye strain, seizures (in individuals with photosensitive epilepsy), or other adverse physical reactions. Discontinue use immediately if you experience any discomfort. Do not operate VR while driving, walking in public spaces, or performing any activity requiring attention.
    </div>

    <ul>
        <li>You use VR Environments entirely at your own risk. GoSiteMe is not responsible for any injury, illness, or property damage resulting from VR use.</li>
        <li>You are responsible for maintaining a safe physical environment while using VR (clear play area, proper ventilation, appropriate lighting).</li>
        <li>VR Environments are not recommended for children under 13 years of age.</li>
    </ul>

    <h3>10.2 VR Conduct</h3>
    <ul>
        <li>You agree to treat other users in VR environments with respect. Harassment, bullying, stalking, hate speech, sexual misconduct, and threatening behavior are strictly prohibited.</li>
        <li>We may record and analyze behavioral data within VR Environments for safety, moderation, and service improvement purposes.</li>
        <li>We reserve the right to remove, mute, ban, or restrict users from VR Environments at our sole discretion.</li>
    </ul>

    <h3>10.3 Virtual Property & Assets</h3>
    <ul>
        <li>Virtual items, assets, currency, property, and customizations within VR Environments are licensed, not sold. You receive a limited, revocable license to use virtual items within the applicable VR Environment.</li>
        <li>Virtual items have no monetary value outside the platform and cannot be exchanged for real currency except through officially supported mechanisms (e.g., GSM Token integration).</li>
        <li>We reserve the right to modify, remove, or reset virtual items, environments, or economies at any time for operational, legal, or safety reasons.</li>
    </ul>

    <h3>10.4 User-Generated VR Content</h3>
    <p>If you create content within VR Environments (structures, objects, experiences, avatars), you retain ownership of your original creative elements. You grant GoSiteMe a worldwide, non-exclusive, royalty-free license to host, display, distribute, and promote user-generated VR content within the platform.</p>

    <hr>

    <h2>11. App Store & Marketplace Terms</h2>
    <h3>11.1 For Users (Purchasers/Downloaders)</h3>
    <ul>
        <li>Store Applications are provided by third-party developers unless explicitly marked as "GoSiteMe Official." GoSiteMe is not the publisher, author, or guarantor of third-party Store Applications.</li>
        <li>We review Store Applications for basic quality and security standards, but <strong>we do not guarantee that Store Applications are free from bugs, malware, security vulnerabilities, or compatibility issues</strong>.</li>
        <li>You install and use Store Applications at your own risk. GoSiteMe is not liable for any damage, data loss, security breach, or other harm caused by third-party Store Applications.</li>
        <li>Store Application purchases and subscriptions are governed by the developer's own terms and privacy policies in addition to these Terms.</li>
        <li>Refunds for Store Application purchases are subject to Section 5 and the App Store refund policy displayed at checkout.</li>
    </ul>

    <h3>11.2 For Developers (Publishers)</h3>
    <ul>
        <li>By submitting a Store Application, you represent and warrant that: (a) you own or have all necessary rights to the application; (b) the application does not infringe any third-party rights; (c) the application complies with all applicable laws; (d) the application does not contain malware, spyware, or malicious code; (e) the application description and screenshots are accurate and not misleading.</li>
        <li>GoSiteMe reserves the right to reject, remove, or delist any Store Application at our sole discretion, with or without cause, and with or without notice.</li>
        <li>Revenue sharing: GoSiteMe retains a platform commission on all paid Store Application transactions as disclosed in the Developer Console. Commission rates may change with 30 days' notice.</li>
        <li>Developers are solely responsible for providing customer support for their Store Applications and for complying with all applicable consumer protection, privacy, and export control laws.</li>
        <li>Developers indemnify GoSiteMe against all claims arising from their Store Applications (see Section 24).</li>
    </ul>

    <h3>11.3 Content Guidelines</h3>
    <p>Store Applications must not contain: illegal content, hate speech, sexually explicit material involving minors, malware, undisclosed data collection, deceptive practices, counterfeit goods, or any content that violates our Acceptable Use Policy. GoSiteMe is the sole arbiter of content policy enforcement.</p>

    <hr>

    <h2>12. Encrypted Communications (Veil Protocol) Terms</h2>
    <h3>12.1 Encryption</h3>
    <p>The Veil Protocol provides end-to-end encryption for messages, voice calls, and file transfers between users. GoSiteMe cannot access the content of end-to-end encrypted communications.</p>

    <h3>12.2 Lawful Use Only</h3>
    <p>You must use the Veil Protocol exclusively for lawful purposes. While we respect user privacy, we will cooperate with law enforcement agencies when required by valid legal process (court orders, subpoenas, warrants) in accordance with applicable law.</p>

    <h3>12.3 Metadata</h3>
    <p>While message content is end-to-end encrypted, metadata (sender, recipient, timestamp, message size, delivery status) may be logged for service operation and legal compliance purposes.</p>

    <h3>12.4 No Guarantee of Absolute Security</h3>
    <p>While we employ state-of-the-art encryption (including post-quantum cryptography), <strong>no communication system is absolutely secure</strong>. We do not guarantee that communications cannot be intercepted, compromised, or subjected to cryptanalysis by nation-state actors or through zero-day vulnerabilities.</p>

    <hr>

    <h2>13. Cryptocurrency & GSM Token Terms</h2>

    <div class="warning-box">
        <strong>CRYPTOCURRENCY RISK DISCLOSURE</strong><br>
        Cryptocurrency and digital assets are highly volatile and speculative. The value of GSM Token and any other cryptocurrency may fluctuate dramatically and may go to zero. <strong>YOU COULD LOSE YOUR ENTIRE INVESTMENT.</strong> GoSiteMe does not provide financial, investment, or tax advice. Consult a qualified financial advisor before engaging in any cryptocurrency transaction.
    </div>

    <h3>13.1 GSM Token</h3>
    <ul>
        <li>GSM Token is a <strong>utility token</strong> designed for use within the GoSiteMe ecosystem. It is not a security, equity, debt instrument, investment contract, or any form of financial instrument.</li>
        <li>Holding GSM Tokens does not confer any ownership interest, equity, profit-sharing rights, dividend rights, governance rights (except as explicitly provided), or any other financial rights in GoSiteMe.</li>
        <li>The availability, functionality, and utility of GSM Tokens within the platform may change at any time.</li>
    </ul>

    <h3>13.2 Wallet Responsibility</h3>
    <p>You are solely responsible for the security of your cryptocurrency wallet, private keys, seed phrases, and all blockchain transactions. GoSiteMe cannot reverse blockchain transactions, recover lost private keys, or restore access to compromised wallets. <strong>Lost private keys result in permanent, irrecoverable loss of funds.</strong></p>

    <h3>13.3 Regulatory Compliance</h3>
    <p>Cryptocurrency features may not be available in all jurisdictions. You are responsible for determining whether your use of cryptocurrency features complies with applicable laws in your jurisdiction, including securities regulations, tax obligations, anti-money laundering (AML) requirements, and know-your-customer (KYC) requirements.</p>

    <h3>13.4 No Investment Advice</h3>
    <p>Nothing in our Services, marketing materials, documentation, social media, or communications constitutes financial or investment advice. Past performance of GSM Token or any cryptocurrency is not indicative of future results.</p>

    <hr>

    <h2>14. Intellectual Property</h2>
    <h3>14.1 Our Property</h3>
    <p>The GoSiteMe and GoCodeMe names, logos, trade dress, platform design, Alfred AI assistant, Veil Protocol, all proprietary technology, algorithms, architectures, trade secrets, inventions, robotics firmware, AI models, VR environments, and all associated intellectual property rights — including all patents, patent applications, copyrights, trademarks, service marks, trade names, trade dress, trade secrets, know-how, moral rights, database rights, rights of publicity, and all other intellectual and proprietary rights of any kind — are owned exclusively by GoSiteMe or its licensors. Nothing in these Terms grants you any rights to our intellectual property except the limited, revocable, non-exclusive, non-transferable, non-sublicensable license to use the Services as permitted by your subscription plan. <strong>All rights not expressly granted to you in these Terms are reserved by GoSiteMe.</strong></p>

    <h3>14.2 Your Content</h3>
    <p>You retain all rights to User Content you create and store on our platform. By using our Services, you grant us a limited, worldwide, non-exclusive, royalty-free license to host, store, cache, transmit, process, display, reproduce, and create backups of your content solely as necessary to provide, maintain, and improve the Services. This license does not grant GoSiteMe the right to sell your User Content to third parties or to use your User Content to train AI models. This license survives termination solely to the extent necessary for GoSiteMe to wind down Services, fulfill legal obligations, and process pending transactions.</p>
    <p>To the extent permitted by applicable law, including the <em>Copyright Act</em> (Canada) and the <em>Civil Code of Québec</em>, you irrevocably waive and agree not to assert any moral rights (including rights of integrity, attribution, and association) in and to User Content submitted to, created through, or processed by the Services, and you consent to any act or omission that would otherwise infringe such moral rights. If moral rights cannot be waived under applicable law, you agree not to exercise such rights against GoSiteMe or its sublicensees.</p>

    <h3>14.3 Feedback</h3>
    <p>If you provide suggestions, feature requests, bug reports, or feedback, you grant us a perpetual, irrevocable, worldwide, royalty-free, fully-paid, sublicensable license to use, modify, incorporate, and commercialize such feedback in any manner without obligation or compensation to you.</p>

    <h3>14.4 DMCA & Takedown Procedure</h3>
    <p>If you believe content on our platform or App Store infringes your copyright, submit a DMCA takedown notice to <a href="mailto:legal@gositeme.com">legal@gositeme.com</a> including: (a) identification of the copyrighted work; (b) identification of the infringing material and its location; (c) your contact information; (d) a statement of good-faith belief; (e) a statement of accuracy under penalty of perjury; and (f) your physical or electronic signature.</p>

    <h3>14.5 Open-Source Components</h3>
    <p>Certain components of our Services may incorporate open-source software. Such components are subject to their respective open-source licenses, which shall prevail over these Terms to the extent of any conflict with respect to such components only.</p>

    <h3>14.6 Publicity Rights</h3>
    <p>Unless you notify us in writing to the contrary, you grant GoSiteMe a non-exclusive, royalty-free license to use your name, logo, and trademarks in customer lists, marketing materials, case studies, press releases, and investor presentations for the purpose of identifying you as a GoSiteMe customer. You may revoke this license at any time by providing written notice to <a href="mailto:legal@gositeme.com">legal@gositeme.com</a>, and GoSiteMe will remove your identifying information from future materials within 30 days, though previously published materials need not be recalled.</p>

    <h3>14.7 Reservation of Rights</h3>
    <p>GoSiteMe and its licensors reserve all rights, title, and interest not expressly granted under these Terms. No implied licenses are granted by GoSiteMe under these Terms, whether by estoppel, implication, exhaustion, or otherwise. Any use of GoSiteMe's intellectual property not expressly authorized herein is strictly prohibited.</p>

    <hr>

    <h2>15. Service Availability & SLA</h2>
    <h3>15.1 Uptime Target</h3>
    <p>We target 99.9% uptime for hosting and platform services (excluding scheduled maintenance). If monthly uptime falls below 99.9%, affected customers may request service credits:</p>
    <table>
        <tr><th>Monthly Uptime</th><th>Service Credit</th></tr>
        <tr><td>99.0% &ndash; 99.9%</td><td>5% of monthly fee</td></tr>
        <tr><td>95.0% &ndash; 99.0%</td><td>15% of monthly fee</td></tr>
        <tr><td>Below 95.0%</td><td>30% of monthly fee</td></tr>
    </table>
    <p>Credits must be requested within 30 days of the incident. Credits are applied to future invoices and are not refundable as cash. Service credits are your sole and exclusive remedy for service unavailability.</p>

    <h3>15.2 Scheduled Maintenance</h3>
    <p>We may perform scheduled maintenance with at least 24 hours' notice via email or dashboard notification. Maintenance windows are excluded from uptime calculations.</p>

    <h3>15.3 Backups</h3>
    <p>We perform automated daily backups of hosting environments. However, <strong>you are solely responsible for maintaining your own backups</strong>. We do not guarantee backup availability, integrity, or restoration times. Data loss may occur despite backup measures.</p>

    <h3>15.4 No Guaranteed Connectivity for Devices</h3>
    <p>We do not guarantee continuous, uninterrupted connectivity between our Platform and Robotic Devices, IoT Devices, or VR hardware. Network latency, outages, and disconnections may occur. You are responsible for implementing appropriate fail-safe mechanisms in Robotic Device configurations for scenarios where connectivity is lost.</p>

    <hr>

    <h2>16. Data Processing & Privacy</h2>
    <p>Your use of our Services is also governed by our <a href="/privacy-policy.php">Privacy Policy</a>, which is incorporated into these Terms by reference. The Privacy Policy explains how we collect, use, process, store, and protect your personal information, including data from AI interactions, voice cloning, robotic devices, IoT sensors, VR environments, blockchain transactions, and App Store usage.</p>

    <h3>16.1 Quebec Law 25 Compliance</h3>
    <p>In compliance with Quebec's Law 25 (<em>Act Respecting the Protection of Personal Information in the Private Sector</em>), we ensure:</p>
    <ul>
        <li>Explicit, informed consent for data collection with clear explanation of purpose</li>
        <li>Right to access, rectify, and delete your personal information</li>
        <li>Privacy impact assessments for new technologies and data processing activities</li>
        <li>Data incident notification to the Commission d'acc&egrave;s &agrave; l'information within required timelines</li>
        <li>Designated privacy officer available upon request</li>
        <li>De-identification and anonymization where feasible</li>
    </ul>

    <h3>16.2 Enterprise Data Processing</h3>
    <p>Enterprise Customers processing personal data through our Services may request a separate Data Processing Agreement (DPA) compliant with GDPR, PIPEDA, CCPA/CPRA, and other applicable privacy frameworks. Contact <a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a>.</p>

    <hr>

    <h2>17. Export Controls & Sanctions</h2>
    <p>Our Services, including AI technology, encryption features (Veil Protocol, post-quantum cryptography), and robotics control systems, may be subject to export control laws and trade sanctions, including but not limited to: Canada's <em>Export and Import Permits Act</em> and <em>Special Economic Measures Act</em>, the U.S. <em>Export Administration Regulations</em> (EAR), the U.S. <em>International Traffic in Arms Regulations</em> (ITAR), the <em>Wassenaar Arrangement</em> on Export Controls, and the EU <em>Dual-Use Regulation</em> (EU 2021/821). You represent and warrant that:</p>
    <ul>
        <li>You are not located in, under the control of, or a national or resident of any country subject to applicable trade sanctions, including but not limited to: Cuba, Iran, North Korea, Syria, the Crimea, Donetsk, and Luhansk regions of Ukraine, or any other jurisdiction subject to comprehensive economic sanctions imposed by Canada, the United States, or the European Union</li>
        <li>You are not on any applicable restricted party list (including but not limited to Canada's Consolidated List, OFAC SDN List, OFAC Sectoral Sanctions List, EU Consolidated Financial Sanctions List, UN Security Council Consolidated List, and UK Sanctions List)</li>
        <li>You will not export, re-export, or transfer our technology, encryption, or AI capabilities in violation of applicable export control laws</li>
        <li>You will comply with all applicable end-use and end-user restrictions</li>
        <li>You will not use the Services to develop, design, manufacture, produce, or stockpile nuclear, chemical, or biological weapons, or missiles capable of delivering such weapons</li>
        <li>You will promptly notify GoSiteMe if your export compliance status changes</li>
    </ul>

    <hr>

    <h2>18. Third-Party Services & Integrations</h2>
    <p>Our Services integrate with third-party providers including but not limited to AI engine providers, payment processors, blockchain networks, cloud infrastructure providers, certificate authorities, domain registrars, IoT device manufacturers, and VR hardware manufacturers. GoSiteMe is not responsible for:</p>
    <ul>
        <li>The availability, performance, security, or policies of third-party services</li>
        <li>The accuracy or reliability of third-party AI model outputs</li>
        <li>Changes to third-party APIs, pricing, or terms that may affect our Services</li>
        <li>Data processing by third-party services, which is governed by their own privacy policies</li>
        <li>Hardware defects, malfunctions, or safety issues in third-party manufactured Robotic Devices or IoT Devices</li>
    </ul>

    <hr>

    <h2>19. Account Suspension & Termination</h2>
    <h3>19.1 By You</h3>
    <p>You may cancel your account at any time through the client area or by contacting support. Cancellation takes effect at the end of the current billing period. No partial-period refunds are provided for mid-cycle cancellations.</p>

    <h3>19.2 By Us</h3>
    <p>We may suspend or terminate your account immediately and without prior notice if:</p>
    <ul>
        <li>You violate these Terms, the Acceptable Use Policy, or any applicable law</li>
        <li>Your payment fails and remains unresolved for 21 days</li>
        <li>Your usage poses a security risk, safety hazard, or degrades service for others</li>
        <li>Your Robotic Devices or IoT Devices pose a safety risk to persons or property</li>
        <li>Required by law, court order, or regulatory action</li>
        <li>Your Store Applications are found to contain malware, fraud, or prohibited content</li>
        <li>We reasonably believe your account is being used for illegal activity</li>
    </ul>

    <h3>19.3 Data After Termination</h3>
    <p>Upon account termination, we will retain your data for 30 days to allow retrieval. After 30 days, all data (including code, files, databases, AI conversation history, Voice Clones, VR assets, and Store Application data) may be permanently and irrecoverably deleted. Robotic Devices connected to your account will be disconnected from the Platform upon termination.</p>

    <h3>19.4 Survival</h3>
    <p>The following sections survive termination or expiration of these Terms for any reason: Definitions (1), Services &amp; Plans (3.8–3.10), Acceptable Use Policy (6), Anti-Circumvention (6.2), AI-Specific Terms (7), Voice Technology (8), Robotics &amp; IoT (9), Veil Protocol (12), Cryptocurrency (13), Intellectual Property (14), Export Controls (17), Warranty Disclaimer (20), Assumption of Risk (21), Limitation of Liability (23), Indemnification (24), Injunctive Relief (24.2), Force Majeure (25), Arbitration (28), Governing Law (29), Confidentiality (45), Telecom &amp; Regulatory (41), Open-Source (50), Notification Procedures (51), Relationship of Parties (52), No Third-Party Beneficiaries (53), Cumulative Remedies (55), and any provisions that by their nature or intent should survive termination.</p>

    <hr>

    <h2>20. Warranty Disclaimer</h2>

    <div class="warning-box">
        <strong>THE SERVICES ARE PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, WHETHER EXPRESS, IMPLIED, STATUTORY, OR OTHERWISE.</strong> GoSiteMe explicitly disclaims all warranties, including but not limited to implied warranties of merchantability, fitness for a particular purpose, title, non-infringement, accuracy, reliability, availability, security, and compatibility. Without limiting the foregoing:
    </div>

    <ul>
        <li>We do not warrant that the Services will be uninterrupted, error-free, secure, or free from viruses or harmful components.</li>
        <li>We do not warrant that AI-generated outputs will be accurate, complete, original, non-infringing, safe, or suitable for any purpose.</li>
        <li>We do not warrant that Robotic Devices or IoT Devices will function correctly, safely, or as intended in all environments and conditions.</li>
        <li>We do not warrant that Voice Clones will accurately reproduce any voice or be suitable for any particular application.</li>
        <li>We do not warrant the performance, security, or functionality of third-party Store Applications.</li>
        <li>We do not warrant that VR Environments will be free from visual artifacts, performance issues, or adverse health effects.</li>
        <li>We do not warrant the value, stability, or availability of GSM Token or any cryptocurrency feature.</li>
        <li>We do not warrant the security of end-to-end encrypted communications against all forms of interception or cryptanalysis.</li>
        <li>We do not warrant the availability, integrity, or restorability of backups. <strong>You are solely responsible for maintaining independent backups of all data, content, code, configurations, and applications.</strong></li>
        <li>We do not warrant the compatibility, safety, quality, or fitness of any third-party hardware, devices, peripherals, or equipment used in connection with the Services.</li>
    </ul>
    <p><strong>You use the Services entirely at your own risk.</strong> Some jurisdictions do not allow the exclusion of certain warranties; in such jurisdictions, these exclusions apply to the fullest extent permitted by applicable law.</p>

    <hr>

    <h2>21. Assumption of Risk</h2>
    <p>You expressly acknowledge and assume the following risks:</p>
    <ol>
        <li><strong>Physical Risks from Robotics:</strong> Robotic Devices are physical machines that may cause bodily injury, property damage, or death if misused, misconfigured, or in the event of malfunction.</li>
        <li><strong>AI Decision Risks:</strong> AI Agents may make incorrect, biased, or harmful decisions. AI outputs should never be relied upon as the sole basis for critical decisions affecting health, safety, finances, or legal matters.</li>
        <li><strong>Financial Risks:</strong> Cryptocurrency values are volatile. GSM Tokens may lose all value. Blockchain transactions are irreversible.</li>
        <li><strong>Health Risks from VR:</strong> VR may cause motion sickness, epileptic seizures, eye strain, disorientation, falls, or collisions with physical objects.</li>
        <li><strong>Privacy Risks:</strong> IoT Devices, home robots, and VR systems collect ambient data from your environment. Despite security measures, data breaches may occur.</li>
        <li><strong>Voice Cloning Risks:</strong> Synthetic voices may be misused by third parties if not properly secured.</li>
        <li><strong>Cybersecurity Risks:</strong> Despite security measures, connected devices and platforms may be targeted by cyberattacks.</li>
    </ol>

    <hr>

    <h2>22. Age Restrictions</h2>
    <h3>22.1 General Platform</h3>
    <p>Our Services are intended for users aged 18 and older. Users between 13 and 18 may use the platform only with verifiable parental or guardian consent and under direct adult supervision.</p>

    <h3>22.2 VR Environments</h3>
    <p>VR Environments are not recommended for children under 13. Users between 13 and 18 require parental consent. Parents and guardians are responsible for supervising minor use and managing content exposure settings.</p>

    <h3>22.3 Robotics</h3>
    <p>Robotic Devices must only be operated by or under the direct supervision of competent adults (18+). Minors must not operate Robotic Devices without direct adult supervision.</p>

    <h3>22.4 Cryptocurrency</h3>
    <p>Cryptocurrency features (including GSM Token, crypto trading, and wallet functionality) are available only to users aged 18 and older. No exceptions.</p>

    <h3>22.5 COPPA Compliance</h3>
    <p>We comply with the Children's Online Privacy Protection Act (COPPA). We do not knowingly collect personal information from children under 13 without verifiable parental consent. If you believe a child under 13 has provided personal information, contact us immediately at <a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a>.</p>

    <hr>

    <h2>23. Limitation of Liability</h2>

    <div class="warning-box">
        <strong>PLEASE READ THIS SECTION CAREFULLY AS IT LIMITS OUR LIABILITY TO YOU.</strong>
    </div>

    <p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW:</p>

    <h3>23.1 Aggregate Liability Cap</h3>
    <p>GoSiteMe's total aggregate liability for all claims arising from or related to these Terms, the Services, or any Robotic Device, IoT Device, VR Environment, Store Application, AI Agent, Voice Clone, cryptocurrency feature, or encrypted communication shall not exceed the <strong>lesser of</strong>: (a) the total amount you actually paid to GoSiteMe in the <strong>twelve (12) months</strong> immediately preceding the event giving rise to the claim; or (b) <strong>one hundred US dollars ($100 USD)</strong> if you are a free-tier user or have paid less than $100 in the preceding 12 months.</p>

    <h3>23.2 Exclusion of Consequential Damages</h3>
    <p><strong>IN NO EVENT SHALL GoSiteMe, ITS OFFICERS, DIRECTORS, EMPLOYEES, AGENTS, AFFILIATES, SUCCESSORS, OR ASSIGNS BE LIABLE FOR ANY:</strong></p>
    <ul>
        <li>Indirect, incidental, special, consequential, exemplary, or punitive damages</li>
        <li>Loss of profits, revenue, business, goodwill, or anticipated savings</li>
        <li>Loss of data, use, or other intangible losses</li>
        <li>Property damage or personal injury arising from the use of Robotic Devices or IoT Devices</li>
        <li>Financial losses from cryptocurrency transactions, GSM Token value fluctuations, or wallet compromises</li>
        <li>Damages arising from AI-generated content, decisions, or recommendations</li>
        <li>Damages arising from Voice Clone misuse or unauthorized reproduction</li>
        <li>Damages arising from third-party Store Applications</li>
        <li>Damages arising from VR-related health effects or injuries</li>
        <li>Damages arising from interception or compromise of encrypted communications</li>
        <li>Business interruption or loss of business opportunities</li>
    </ul>
    <p>These limitations apply regardless of the theory of liability (contract, tort, negligence, strict liability, or otherwise) and even if GoSiteMe has been advised of the possibility of such damages.</p>

    <h3>23.3 Essential Purpose</h3>
    <p>The limitations of liability in this Section 23 are fundamental elements of the basis of the bargain between GoSiteMe and you. The Services would not be provided without such limitations. These limitations apply to the fullest extent permitted by law, even if any exclusive remedy provided in these Terms fails of its essential purpose.</p>

    <h3>23.4 Product Liability Carve-Out for GoSiteMe Hardware</h3>
    <p>Notwithstanding Sections 23.1 through 23.3, <strong>the limitation of liability and exclusion of consequential damages in this Section 23 shall NOT apply to</strong>:</p>
    <ul>
        <li>Claims for personal injury (including bodily injury and death) directly caused by a Manufacturing Defect in GoSiteMe Hardware, to the extent such claims cannot be limited under applicable product liability law</li>
        <li>Claims for property damage directly caused by a Manufacturing Defect in GoSiteMe Hardware</li>
        <li>GoSiteMe's obligations under the Hardware Limited Warranty (Section 57)</li>
        <li>GoSiteMe's recall obligations under Section 9.6</li>
        <li>Any liability that cannot be excluded or limited under applicable mandatory consumer protection law, including but not limited to Quebec's <em>Consumer Protection Act</em>, the EU <em>Product Liability Directive</em>, and similar consumer protection statutes</li>
    </ul>
    <p>For claims subject to this carve-out, GoSiteMe's liability shall be determined in accordance with applicable product liability law and the Hardware Limited Warranty (Section 57), and not by the caps set forth in Section 23.1.</p>

    <hr>

    <h2>24. Indemnification</h2>
    <p>You agree to defend, indemnify, and hold harmless GoSiteMe, its parent companies, subsidiaries, affiliates, officers, directors, employees, agents, partners, successors, and assigns (collectively, "GoSiteMe Parties") from and against any and all claims, demands, actions, suits, proceedings, damages, liabilities, losses, costs, and expenses (including reasonable attorneys' fees and court costs) arising from or related to:</p>
    <ul>
        <li>Your use of the Services or any activity under your account</li>
        <li>Violation of these Terms, any applicable law, or any third-party rights</li>
        <li>Your User Content, Store Applications, or AI Agent configurations</li>
        <li>Operation, malfunction, or misuse of Robotic Devices or IoT Devices connected to your account, except to the extent directly caused by a Manufacturing Defect in GoSiteMe Hardware</li>
        <li>Personal injury or property damage caused by or related to your Robotic Devices or IoT Devices, except to the extent directly caused by a Manufacturing Defect in GoSiteMe Hardware</li>
        <li>Use or misuse of Voice Clones created through your account</li>
        <li>Claims by third parties arising from your Store Applications</li>
        <li>Tax consequences of your cryptocurrency transactions</li>
        <li>Infringement or misappropriation of any intellectual property by your content or applications</li>
    </ul>
    <p>GoSiteMe reserves the right to assume exclusive defense and control of any matter subject to indemnification by you, at your expense. You agree to cooperate with our defense of such claims. GoSiteMe will not settle any claim that imposes obligations on you or admits liability on your behalf without your prior written consent (not to be unreasonably withheld).</p>

    <h3>24.2 Injunctive Relief</h3>
    <p>You acknowledge that any breach or threatened breach of Sections 6 (Acceptable Use Policy), 6.2 (Anti-Circumvention), 14 (Intellectual Property), 45 (Confidentiality), or 17 (Export Controls) would cause GoSiteMe irreparable harm for which monetary damages would be an inadequate remedy. Accordingly, GoSiteMe shall be entitled to seek equitable relief, including injunction and specific performance, in addition to all other remedies available at law or in equity, without the necessity of proving actual damages, posting a bond, or other security. This right to seek injunctive relief is in addition to, and not in lieu of, the arbitration provisions in Section 28.</p>

    <hr>

    <h2>25. Force Majeure</h2>
    <p>Neither party shall be liable for delays or failures in performance resulting from events beyond reasonable control, including but not limited to: natural disasters, earthquakes, floods, hurricanes, fires, epidemics, pandemics, acts of God, war, terrorism, civil unrest, government actions or orders, sanctions, embargoes, labor disputes, power outages, internet disruptions, telecommunications failures, cyberattacks, hardware failures, third-party service outages, blockchain network congestion or failures, and changes in applicable laws or regulations that render performance commercially impracticable.</p>

    <hr>

    <h2>26. Consumer Protection (Quebec)</h2>
    <p>In accordance with Quebec's Consumer Protection Act (<em>Loi sur la protection du consommateur</em>):</p>
    <ul>
        <li><strong>Cooling-off period:</strong> You have a 10-business-day cooling-off period for distance contracts, during which you may cancel without penalty.</li>
        <li><strong>Price transparency:</strong> All fees, taxes, and surcharges are clearly indicated before purchase.</li>
        <li><strong>Language rights:</strong> These Terms are available in both English and French. In the event of a conflict between language versions, the French version shall prevail for Quebec residents.</li>
        <li><strong>Dispute resolution:</strong> If you are unsatisfied with our resolution, you may contact the <strong>Office de la protection du consommateur</strong> at 1-888-672-2556 or <a href="https://www.opc.gouv.qc.ca" target="_blank" rel="noopener noreferrer">www.opc.gouv.qc.ca</a>.</li>
        <li><strong>Consumer rights preserved:</strong> Nothing in these Terms, including the arbitration clause, is intended to waive or limit consumer rights that cannot be waived under Quebec law. Where these Terms conflict with mandatory provisions of Quebec consumer protection law, the mandatory provisions shall prevail for Quebec consumer transactions.</li>
    </ul>

    <hr>

    <h2>27. International Compliance</h2>
    <h3>27.1 GDPR (European Economic Area)</h3>
    <p>For users in the European Economic Area (EEA), United Kingdom, and Switzerland: we process personal data in accordance with the General Data Protection Regulation (GDPR). You have rights including access, rectification, erasure, restriction, portability, objection, and the right not to be subject to automated decision-making. For GDPR inquiries, contact our Data Protection Officer at <a href="mailto:dpo@gositeme.com">dpo@gositeme.com</a>.</p>

    <h3>27.2 CCPA/CPRA (California)</h3>
    <p>For California residents: we comply with the California Consumer Privacy Act (CCPA) and California Privacy Rights Act (CPRA). You have rights to know, delete, correct, and opt out of the sale or sharing of your personal information. We do not sell personal information. See our <a href="/privacy-policy.php">Privacy Policy</a> for details.</p>

    <h3>27.3 PIPEDA (Canada)</h3>
    <p>We comply with the Personal Information Protection and Electronic Documents Act (PIPEDA) and Quebec's <em>Act Respecting the Protection of Personal Information in the Private Sector</em> (Law 25).</p>

    <h3>27.4 Other Jurisdictions</h3>
    <p>We are committed to complying with applicable data protection and privacy laws in all jurisdictions where we operate. If you have jurisdiction-specific inquiries, contact <a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a>.</p>

    <hr>

    <h2>28. Dispute Resolution & Arbitration</h2>

    <h3>28.1 Informal Resolution</h3>
    <p>Before initiating any formal dispute resolution, you agree to first contact us at <a href="mailto:legal@gositeme.com">legal@gositeme.com</a> and attempt to resolve the dispute informally for at least sixty (60) days.</p>

    <h3>28.2 Binding Arbitration</h3>
    <p>If the dispute is not resolved informally, <strong>you and GoSiteMe agree that any dispute, claim, or controversy arising from or relating to these Terms or the Services (including their formation, interpretation, breach, performance, termination, or validity) shall be finally resolved by binding arbitration</strong>, except as set forth in Section 28.4 below. Arbitration shall be administered under the rules of the Canadian Arbitration Association (CAA) or a comparable arbitration body. The seat of arbitration shall be Quebec City, Quebec, Canada. The language of arbitration shall be English or French, at the complainant's election.</p>

    <h3>28.3 Arbitration Procedure</h3>
    <ul>
        <li>The arbitrator shall be a single neutral arbitrator with expertise in technology and commercial disputes.</li>
        <li>The arbitrator's award shall be final and binding and may be entered as a judgment in any court of competent jurisdiction.</li>
        <li>Each party shall bear its own costs, unless the arbitrator determines otherwise.</li>
        <li>The arbitration proceedings and decision shall remain confidential, except as required by law.</li>
    </ul>

    <h3>28.4 Exceptions to Arbitration</h3>
    <p>The following disputes are excluded from binding arbitration: (a) claims for injunctive or equitable relief related to intellectual property infringement, misappropriation of trade secrets, or unauthorized access to our systems; (b) small claims court actions where eligible; (c) disputes that mandatory applicable law requires to be resolved in a specific forum.</p>

    <h3>28.5 Class Action Waiver</h3>
    <p><strong>YOU AND GoSiteMe AGREE THAT EACH MAY BRING CLAIMS AGAINST THE OTHER ONLY IN YOUR OR ITS INDIVIDUAL CAPACITY, AND NOT AS A PLAINTIFF OR CLASS MEMBER IN ANY PURPORTED CLASS, COLLECTIVE, CONSOLIDATED, OR REPRESENTATIVE PROCEEDING.</strong> The arbitrator may not consolidate more than one person's claims and may not otherwise preside over any form of representative or class proceeding. If this class action waiver is found to be unenforceable, then the entirety of this arbitration provision shall be null and void with respect to such claim, and the dispute shall proceed in court.</p>

    <h3>28.6 Quebec Consumer Exception</h3>
    <p>For consumers domiciled in Quebec: if mandatory provisions of Quebec consumer protection law prohibit binding pre-dispute arbitration or class action waivers, Sections 28.2 and 28.5 shall not apply to claims arising under Quebec consumer protection legislation. Such claims may be brought before the courts of Quebec.</p>

    <hr>

    <h2>29. Governing Law & Jurisdiction</h2>
    <p>These Terms are governed by and construed in accordance with the laws of the <strong>Province of Quebec</strong> and the applicable federal laws of <strong>Canada</strong>, without regard to conflict of law principles. Subject to the arbitration provisions in Section 28 and the Quebec consumer protection exception in Section 28.6, any legal proceedings not subject to arbitration shall be brought exclusively in the courts of the judicial district of Quebec City, Province of Quebec, Canada, and you consent to the personal jurisdiction of such courts.</p>

    <hr>

    <h2>30. Modification of Terms</h2>
    <p>We may update these Terms at any time. For material changes (including changes to pricing, liability limitations, arbitration provisions, or data processing practices), we will provide at least <strong>30 days' written notice</strong> via email to the address on your account and/or via dashboard notification. Non-material changes (typo corrections, formatting, clarifications) may be made without notice. Continued use of the Services after the effective date of any change constitutes your acceptance of the updated Terms. If you do not agree to the updated Terms, you must discontinue use of the Services before the effective date.</p>

    <hr>

    <h2>31. Severability</h2>
    <p>If any provision of these Terms is found by a court or arbitrator of competent jurisdiction to be unenforceable, invalid, or illegal, that provision will be modified to the minimum extent necessary to make it enforceable, or if modification is not possible, it will be severed from these Terms. The remaining provisions will remain in full force and effect.</p>

    <hr>

    <h2>32. Waiver</h2>
    <p>No failure or delay by GoSiteMe in exercising any right, power, or remedy under these Terms shall operate as a waiver of that right, power, or remedy. No single or partial exercise of any right, power, or remedy shall preclude any other or further exercise thereof.</p>

    <hr>

    <h2>33. Assignment</h2>
    <p>You may not assign or transfer your rights or obligations under these Terms without GoSiteMe's prior written consent. GoSiteMe may assign these Terms in whole or in part at any time without notice, including in connection with a merger, acquisition, corporate reorganization, or sale of all or substantially all assets.</p>

    <hr>

    <h2>34. Entire Agreement</h2>
    <p>These Terms, together with the <a href="/privacy-policy.php">Privacy Policy</a>, any applicable Enterprise Agreement, Data Processing Agreement, Developer Agreement, White-Label Agreement, Affiliate Agreement, and any order-specific terms presented at checkout, constitute the entire agreement between you and GoSiteMe regarding the Services and supersede all prior and contemporaneous agreements, proposals, and representations, whether oral or written.</p>

    <h3>34.2 No Reliance</h3>
    <p>You acknowledge that you have not relied on any statement, representation, assurance, warranty, demonstration, marketing material, sales pitch, or oral commitment made by or on behalf of GoSiteMe that is not set out in these Terms or the documents referenced herein. You agree that you shall have no claim for innocent or negligent misrepresentation or negligent misstatement based on any statement not contained in these Terms.</p>

    <h3>34.3 Statute of Limitations</h3>
    <p>To the maximum extent permitted by applicable law, any claim or cause of action arising out of or relating to these Terms or the Services must be filed within <strong>one (1) year</strong> after the date on which such claim first arose or was discovered, or such claim or cause of action is permanently and irrevocably barred. This limitation applies regardless of the form of action, whether in contract, tort (including negligence), strict liability, or otherwise. This provision does not apply where prohibited by applicable law.</p>

    <hr>

    <h2>35. Beta, Preview &amp; Early Access Features</h2>
    <h3>35.1 Nature of Beta Services</h3>
    <p>We may offer features, services, or products designated as "Beta," "Preview," "Early Access," "Experimental," "Alpha," or similar labels (collectively, "Beta Features"). Beta Features are provided for evaluation and testing purposes and may contain bugs, errors, or incomplete functionality.</p>

    <h3>35.2 No Warranty for Beta</h3>
    <p>Beta Features are provided <strong>"AS IS" without any warranty whatsoever</strong>. The warranty disclaimer in Section 20 applies with heightened force to Beta Features. We make no commitment regarding the availability, reliability, performance, or continuity of Beta Features.</p>

    <h3>35.3 Discontinuation</h3>
    <p>We may modify, suspend, or permanently discontinue any Beta Feature at any time, with or without notice, for any reason. We shall have no liability for any damages arising from the modification, suspension, or discontinuation of Beta Features, including loss of data or configurations.</p>

    <h3>35.4 Feedback on Beta Features</h3>
    <p>By using Beta Features, you agree that we may collect usage data, crash reports, performance metrics, and your feedback to improve the feature. You grant us the rights described in Section 14.3 (Feedback) with respect to any feedback provided on Beta Features.</p>

    <hr>

    <h2>36. API Terms of Use &amp; Rate Limits</h2>
    <h3>36.1 API License</h3>
    <p>Subject to these Terms, we grant you a limited, non-exclusive, non-transferable, revocable license to access and use our Application Programming Interfaces (APIs), Software Development Kits (SDKs), and developer tools solely for the purpose of integrating with and building applications on our Platform in accordance with our developer documentation.</p>

    <h3>36.2 Rate Limits &amp; Quotas</h3>
    <p>API access is subject to rate limits, request quotas, and usage caps as specified in our developer documentation and your subscription plan. Exceeding rate limits may result in temporary throttling, request rejection, or account suspension. We reserve the right to adjust rate limits at any time with reasonable notice.</p>

    <h3>36.3 API Key Security</h3>
    <p>You are solely responsible for safeguarding your API keys, tokens, OAuth credentials, and other authentication credentials. You must not embed API keys in client-side code, public repositories, or any publicly accessible location. Compromised keys must be rotated immediately. You are liable for all usage under your API credentials.</p>

    <h3>36.4 Restrictions</h3>
    <p>You may not: (a) use APIs to replicate, compete with, or create a substitute for our Services; (b) reverse-engineer, decompile, or attempt to extract source code from APIs; (c) use automated systems to scrape, harvest, or extract data beyond authorized API calls; (d) circumvent or attempt to circumvent rate limits, authentication, or access controls; (e) redistribute API access or share credentials with unauthorized parties.</p>

    <h3>36.5 API Changes &amp; Deprecation</h3>
    <p>We may update, modify, or deprecate APIs with at least 90 days' notice for breaking changes to production APIs. Emergency security patches may be applied without advance notice. We maintain backward compatibility for supported API versions for a minimum of 12 months after deprecation notice.</p>

    <hr>

    <h2>37. White-Label &amp; Reseller Program</h2>
    <h3>37.1 Authorization</h3>
    <p>Participation in the GoSiteMe White-Label or Reseller Program requires a separate written agreement and approval. White-Label partners may rebrand certain designated portions of our Services for resale to their end customers under their own brand, subject to the terms of the White-Label Agreement.</p>

    <h3>37.2 End Customer Obligations</h3>
    <p>White-Label and Reseller partners are responsible for: (a) ensuring end customers agree to terms at least as protective as these Terms; (b) providing first-line support to end customers; (c) compliance with all applicable laws regarding their marketing and sale of rebranded services; (d) all billing relationships with their end customers; (e) communicating service changes and maintenance schedules to end customers.</p>

    <h3>37.3 Brand Usage</h3>
    <p>White-Label partners may not represent themselves as GoSiteMe or imply an agency, partnership, or employment relationship. Use of GoSiteMe trademarks is prohibited except when explicitly authorized in the White-Label Agreement. Partners must not modify, distort, or misrepresent our underlying technology.</p>

    <h3>37.4 Liability</h3>
    <p>White-Label and Reseller partners indemnify GoSiteMe against all claims arising from their end customers, their branding, their marketing representations, and their failure to comply with these Terms or applicable law.</p>

    <hr>

    <h2>38. Affiliate Program</h2>
    <h3>38.1 Enrollment</h3>
    <p>Our Affiliate Program allows approved participants to earn commissions by referring new customers to GoSiteMe. Enrollment is subject to our approval and may be revoked at any time for any reason.</p>

    <h3>38.2 Commission Structure</h3>
    <p>Commission rates, payment terms, cookie duration, and attribution rules are as published in the Affiliate Program dashboard at the time of referral. We reserve the right to modify commission rates prospectively with 30 days' notice. Commissions are not earned on fraudulent, self-referred, incentivized, or chargebacked transactions.</p>

    <h3>38.3 Prohibited Affiliate Practices</h3>
    <p>Affiliates may not: (a) engage in cookie stuffing, ad injection, or forced clicks; (b) bid on GoSiteMe branded keywords without written permission; (c) make false or misleading claims about our Services; (d) use spam, unsolicited communications, or deceptive advertising; (e) create fake or incentivized reviews; (f) purchase services through their own affiliate links (self-referral); (g) use any method that artificially inflates referral counts.</p>

    <h3>38.4 Tax Responsibility</h3>
    <p>Affiliates are independent contractors and are solely responsible for all tax obligations arising from commission payments. We may require tax documentation (W-9, W-8BEN, or equivalent) before processing payouts.</p>

    <hr>

    <h2>39. Desktop &amp; Mobile Application License</h2>
    <h3>39.1 License Grant</h3>
    <p>Subject to these Terms, we grant you a limited, non-exclusive, non-transferable, revocable license to download, install, and use our desktop applications (including GoCodeMe Desktop, Alfred Desktop) and mobile applications (including GoSiteMe Mobile, GoCodeMe Mobile) on devices you own or control, solely for your personal or internal business use in accordance with your subscription plan.</p>

    <h3>39.2 Restrictions</h3>
    <p>You may not: (a) copy, modify, or create derivative works of our applications; (b) reverse-engineer, decompile, disassemble, or otherwise attempt to derive source code; (c) remove, alter, or obscure proprietary notices; (d) distribute, sublicense, lease, rent, or lend our applications; (e) use our applications on more devices than permitted by your subscription; (f) circumvent any technical protection measures or licensing controls.</p>

    <h3>39.3 Automatic Updates</h3>
    <p>Our applications may automatically download and install updates, patches, and security fixes without additional notice. These updates are necessary for security, compatibility, and functionality. By installing our applications, you consent to automatic updates. Critical security updates may not be deferred or declined.</p>

    <h3>39.4 Platform-Specific Terms</h3>
    <p>When you download our applications from third-party app stores (including Apple App Store, Google Play Store), you are also bound by that store's terms of service. In the event of a conflict between these Terms and third-party app store terms, these Terms govern your relationship with GoSiteMe, and the app store terms govern your relationship with the app store provider.</p>

    <h3>39.5 Device Permissions</h3>
    <p>Our applications may request access to device features including camera, microphone, location, contacts, storage, notifications, and biometric sensors. You may manage permissions through your device settings. Denying certain permissions may limit application functionality.</p>

    <hr>

    <h2>40. Gaming, Contests &amp; Virtual Currency</h2>
    <h3>40.1 Gaming Terms</h3>
    <p>Games available through our Platform (including VR games, Chess Masters, backgammon, and other games) are governed by these Terms and any game-specific rules presented within the game. Game outcomes generated by randomization algorithms are not guaranteed to be statistically perfect. Game performance depends on your hardware and network conditions.</p>

    <h3>40.2 Virtual Currency &amp; In-Game Items</h3>
    <p>Virtual currencies, tokens, points, or items earned or purchased within games or VR environments (excluding GSM Token, which is governed by Section 13) are <strong>not redeemable for real currency</strong> and have no monetary value outside the Platform. We may modify, reset, or expire virtual currencies at any time. Virtual items are license grants, not property transfers.</p>

    <h3>40.3 Contests &amp; Competitions</h3>
    <p>Contests, tournaments, leaderboards, and competitions are subject to specific rules posted at the time of the event. Where prizes are offered, applicable tax obligations are the sole responsibility of the winner. We reserve the right to void results, disqualify participants, and reclaim prizes in cases of cheating, collusion, exploitation of bugs, or violation of contest rules.</p>

    <h3>40.4 Gambling Disclaimer</h3>
    <p>Our Platform does <strong>NOT</strong> offer real-money gambling, lotteries, or wagering services. Any betting, staking, or wagering features involving cryptocurrency or GSM Token are skill-based or entertainment-only and are not available in jurisdictions where prohibited by law. You are responsible for determining the legality of such features in your jurisdiction.</p>

    <h3>40.5 Fair Play</h3>
    <p>Use of bots, scripts, automation, exploits, hacks, cheats, or unauthorized modifications in games is strictly prohibited and will result in immediate account suspension or termination. We employ automated and manual detection systems for cheating and anti-competitive behavior.</p>

    <hr>

    <h2>41. Telecommunications &amp; Call Campaign Compliance</h2>
    <h3>41.1 Scope</h3>
    <p>This section applies to users of our IVR Builder, Call Campaigns, conference calling, voice portal, and any other telecommunications features ("Telecom Services").</p>

    <h3>41.2 TCPA Compliance (United States)</h3>
    <p>If you use our Telecom Services to contact persons in the United States, you must comply with the Telephone Consumer Protection Act (TCPA, 47 U.S.C. &sect; 227) and FCC regulations, including:</p>
    <ul>
        <li>Obtaining prior express written consent before making autodialed or prerecorded calls/texts to wireless numbers</li>
        <li>Maintaining and honoring an internal Do Not Call list</li>
        <li>Complying with the National Do Not Call Registry</li>
        <li>Providing opt-out mechanisms in all automated messages</li>
        <li>Restricting calls to permitted hours (no earlier than 8:00 AM or later than 9:00 PM, local time of the called party)</li>
        <li>Identifying yourself and your organization at the beginning of each call</li>
    </ul>

    <h3>41.3 CRTC &amp; CASL Compliance (Canada)</h3>
    <p>If you use our Telecom Services in Canada, you must comply with Canadian Radio-television and Telecommunications Commission (CRTC) Unsolicited Telecommunications Rules, Canada's Anti-Spam Legislation (CASL, S.C. 2010, c. 23), and the National Do Not Call List (DNCL), including:</p>
    <ul>
        <li>Registering with the CRTC before making telemarketing calls</li>
        <li>Subscribing to and honoring the National DNCL</li>
        <li>Obtaining express or implied consent as required by CASL for commercial electronic messages</li>
        <li>Including sender identification and unsubscribe mechanisms in all communications</li>
    </ul>

    <h3>41.4 Your Responsibility</h3>
    <p><strong>GoSiteMe provides the technical infrastructure for telecommunications features. You are solely responsible for ensuring your use of Telecom Services complies with all applicable telecommunications laws, regulations, and industry standards in every jurisdiction you contact.</strong> GoSiteMe does not monitor or police your compliance and assumes no liability for your violations of telecommunications law.</p>

    <h3>41.5 Call Recording Consent</h3>
    <p>If you enable call recording features, you are solely responsible for complying with all applicable one-party or two-party consent laws in the jurisdictions of all call participants. You must inform all parties that a call is being recorded where required by law. GoSiteMe is not liable for your failure to obtain required recording consent.</p>

    <h3>41.6 Emergency Services</h3>
    <p>Our Telecom Services are <strong>NOT a replacement for traditional telephone service</strong> and should <strong>NOT</strong> be used as a primary means to contact emergency services (911, 112, 999). We do not guarantee the ability to connect to emergency services through our platform.</p>

    <hr>

    <h2>42. Healthcare &amp; Medical Disclaimer</h2>
    <h3>42.1 Not Medical Advice</h3>
    <p>Our Services, including AI-generated outputs, health monitoring through IoT Devices, robotic assistance, and any health-related features, do <strong>NOT</strong> constitute medical advice, diagnosis, treatment, or a physician-patient relationship. Always consult qualified healthcare professionals for medical decisions.</p>

    <h3>42.2 No HIPAA Compliance</h3>
    <p>Our Services are <strong>NOT</strong> designed, intended, or certified to comply with the Health Insurance Portability and Accountability Act (HIPAA), the Health Information Technology for Economic and Clinical Health Act (HITECH), or equivalent healthcare data protection regulations in other jurisdictions. You must <strong>NOT</strong> use our Services to store, transmit, or process Protected Health Information (PHI) unless you have entered into a separate Business Associate Agreement (BAA) with GoSiteMe (available only to Enterprise plans).</p>

    <h3>42.3 Health-Related IoT Data</h3>
    <p>Environmental and wellness data collected by IoT Devices (e.g., air quality, temperature, activity detection) is for informational and convenience purposes only and should not be used as a basis for medical or health decisions. Data accuracy depends on sensor calibration, placement, and environmental factors beyond our control.</p>

    <h3>42.4 VR Health Warnings</h3>
    <p>Users with a history of seizures, epilepsy, cardiovascular conditions, anxiety disorders, PTSD, claustrophobia, or other medical conditions should consult a physician before using VR features. Immediately discontinue use if you experience nausea, dizziness, disorientation, altered vision, eye or muscle twitching, involuntary movements, loss of awareness, or seizures.</p>

    <hr>

    <h2>43. Accessibility</h2>
    <h3>43.1 Our Commitment</h3>
    <p>GoSiteMe is committed to making our Services accessible to all users, including those with disabilities. We aim to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 Level AA, the Accessibility for Ontarians with Disabilities Act (AODA), the Americans with Disabilities Act (ADA), and the European standard EN 301 549 as applicable.</p>

    <h3>43.2 Ongoing Efforts</h3>
    <p>We continually improve the user experience for all visitors and apply relevant accessibility standards. Our efforts include: regular accessibility audits, assistive technology compatibility testing, keyboard navigation support, screen reader optimization, and color contrast compliance.</p>

    <h3>43.3 Feedback &amp; Accommodation</h3>
    <p>If you experience accessibility barriers or require accommodations, please contact us at <a href="mailto:accessibility@gositeme.com">accessibility@gositeme.com</a>. We will make reasonable efforts to provide the information or service you need through an accessible alternative.</p>

    <hr>

    <h2>44. Government &amp; Public Sector</h2>
    <h3>44.1 Government End Users</h3>
    <p>If you are a government entity or using our Services on behalf of a government entity, the following additional terms apply: (a) our Services qualify as "commercial computer software" and "commercial computer software documentation" as defined in applicable procurement regulations; (b) use, reproduction, and disclosure are governed by these Terms; (c) if any provision of these Terms conflicts with mandatory government procurement law, that provision is modified to the minimum extent necessary to comply.</p>

    <h3>44.2 Compliance with Government Standards</h3>
    <p>Government agencies requiring specific compliance standards (FedRAMP, StateRAMP, SOC 2, ISO 27001, or equivalent) should contact <a href="mailto:enterprise@gositeme.com">enterprise@gositeme.com</a> to discuss compliance documentation and available configurations.</p>

    <h3>44.3 Data Sovereignty</h3>
    <p>Government customers may require data to remain within specific national boundaries. Data residency options are available for Enterprise plan customers and government agencies. Contact us for details on jurisdiction-specific data storage configurations.</p>

    <hr>

    <h2>45. Confidentiality</h2>
    <h3>45.1 Confidential Information</h3>
    <p>"Confidential Information" means any non-public information disclosed by either party to the other that is designated as confidential or that a reasonable person would understand to be confidential, including but not limited to: business plans, financial information, technical data, trade secrets, customer lists, product roadmaps, pricing, and proprietary algorithms.</p>

    <h3>45.2 Obligations</h3>
    <p>Each party agrees to: (a) hold the other party's Confidential Information in strict confidence; (b) not disclose Confidential Information to third parties except to employees, contractors, and advisors who have a need to know and are bound by confidentiality obligations at least as protective as this section; (c) not use Confidential Information for any purpose other than performing obligations or exercising rights under these Terms; (d) protect Confidential Information with at least the same degree of care used to protect its own Confidential Information, but no less than reasonable care.</p>

    <h3>45.3 Exceptions</h3>
    <p>Confidentiality obligations do not apply to information that: (a) is or becomes publicly available through no fault of the receiving party; (b) was known to the receiving party before disclosure without confidentiality restrictions; (c) is received from a third party without breach of any obligation of confidentiality; (d) is independently developed without use of the disclosing party's Confidential Information; or (e) is required to be disclosed by law, regulation, or court order, provided that the receiving party gives prompt notice (where legally permitted) and cooperates to limit the scope of disclosure.</p>

    <hr>

    <h2>46. Data Portability &amp; Interoperability</h2>
    <h3>46.1 Data Export</h3>
    <p>You may export your data from our Services at any time using our export tools or by contacting support. We provide data exports in standard, machine-readable formats including CSV, JSON, SQL, and ZIP archives.</p>

    <h3>46.2 Exportable Data</h3>
    <p>The following data is exportable: account information, hosting files, databases, AI conversation history, Voice Clone models, VR assets (in standard 3D formats), App Store application packages, IoT device configurations, and analytics data. Certain derived data (e.g., aggregated analytics, system logs) may not be exportable.</p>

    <h3>46.3 Post-Termination Export</h3>
    <p>After account termination, you have 30 days to export your data before permanent deletion. We may charge a reasonable fee for large-volume export assistance beyond self-service export tools.</p>

    <h3>46.4 Interoperability</h3>
    <p>We support industry-standard protocols, formats, and APIs to facilitate interoperability with third-party services. We make no guarantee of compatibility with every third-party system. Changes to third-party systems may affect interoperability without our control.</p>

    <hr>

    <h2>47. Anti-Corruption &amp; Anti-Bribery</h2>
    <p>You represent, warrant, and covenant that neither you nor anyone acting on your behalf will, in connection with the performance of these Terms:</p>
    <ul>
        <li>Offer, pay, promise, or authorize the payment of any bribe, kickback, or improper benefit to any government official, political party, or person</li>
        <li>Violate any applicable anti-corruption or anti-bribery laws, including the Canadian <em>Corruption of Foreign Public Officials Act</em> (CFPOA), the U.S. <em>Foreign Corrupt Practices Act</em> (FCPA), the <em>UK Bribery Act 2010</em>, and any local equivalent laws</li>
        <li>Use any funds received from GoSiteMe (including affiliate commissions or developer payouts) for any unlawful purpose</li>
    </ul>
    <p>Violation of this section constitutes a material breach and grounds for immediate termination.</p>

    <hr>

    <h2>48. Electronic Signatures &amp; Records</h2>
    <p>You agree that electronically signed agreements, click-through acceptances, checkbox consents, and digital acknowledgments constitute valid, binding, and enforceable signatures and records under applicable electronic signature and electronic commerce laws, including but not limited to: the U.S. <em>Electronic Signatures in Global and National Commerce Act</em> (E-SIGN), the <em>Uniform Electronic Transactions Act</em> (UETA), Canada's <em>Personal Information Protection and Electronic Documents Act</em> (PIPEDA, Part 2), Quebec's <em>Act to Establish a Legal Framework for Information Technology</em>, and the European <em>eIDAS Regulation</em>. You waive any right to challenge the validity or enforceability of electronic signatures, acceptances, or records solely on the basis that they are in electronic form.</p>

    <hr>

    <h2>49. Insurance &amp; Risk Management</h2>
    <h3>49.1 Robotics Insurance</h3>
    <p>If you operate Robotic Devices connected to our Platform in commercial, industrial, or public-facing environments, we <strong>strongly recommend</strong> maintaining appropriate insurance coverage, including general liability, product liability, and workers' compensation insurance. GoSiteMe's liability limitations in Section 23 do not substitute for adequate insurance coverage.</p>

    <h3>49.2 Enterprise Insurance Requirements</h3>
    <p>Enterprise customers operating fleets of Robotic Devices or IoT Devices may be required to maintain minimum insurance coverage as specified in their Enterprise Agreement.</p>

    <h3>49.3 No Insurance Provided</h3>
    <p>GoSiteMe does <strong>NOT</strong> provide insurance coverage for your devices, data, operations, or any damages arising from the use of our Services. You are responsible for obtaining appropriate insurance for your specific use case and risk profile.</p>

    <hr>

    <h2>50. Open-Source Software Attribution</h2>
    <p>Our Services incorporate open-source software components. A list of open-source components, their licenses, and attribution notices is available at <a href="/open-source">gositeme.com/open-source</a> and in the applicable application's settings or documentation. Open-source components are subject to their respective license terms, which may include the MIT License, Apache License 2.0, GNU GPL, GNU LGPL, BSD License, and others. To the extent any open-source license terms conflict with these Terms, the open-source license terms shall prevail solely with respect to the applicable open-source component.</p>

    <hr>

    <h2>51. Notification Procedures</h2>
    <h3>51.1 Notices to You</h3>
    <p>We may provide notices to you by: (a) email to the address associated with your account; (b) posting on your account dashboard; (c) in-app notifications; (d) prominent notice on our website. Email and dashboard notices are deemed received 24 hours after sending. Posted notices are effective upon posting. It is your responsibility to keep your email address current.</p>

    <h3>51.2 Notices to GoSiteMe</h3>
    <p>Notices to GoSiteMe must be sent to <a href="mailto:legal@gositeme.com">legal@gositeme.com</a> and are deemed received upon confirmed delivery. Legal process may be served at our registered address. Legal notices sent by any method other than email to our legal department or registered mail to our physical address are not valid.</p>

    <hr>

    <h2>52. Relationship of Parties</h2>
    <p>GoSiteMe and you are independent contracting parties. Nothing in these Terms creates any agency, partnership, joint venture, employer-employee, or franchisor-franchisee relationship. Neither party has any authority to bind the other, assume or create any obligation or liability on behalf of the other, or represent itself as an agent, employee, or partner of the other.</p>

    <hr>

    <h2>53. No Third-Party Beneficiaries</h2>
    <p>These Terms are entered solely for the benefit of GoSiteMe and you, and nothing in these Terms confers, or is intended to confer, any rights, remedies, obligations, or liabilities on any person or entity other than the parties hereto, except that GoSiteMe's affiliates, officers, directors, employees, and agents are express third-party beneficiaries of the limitation of liability (Section 23), indemnification (Section 24), and warranty disclaimer (Section 20) provisions.</p>

    <hr>

    <h2>54. Headings, Interpretation &amp; Construction</h2>
    <p>Section headings are for convenience only and do not limit or affect the interpretation of these Terms. The words "include," "includes," and "including" are deemed to be followed by "without limitation." The word "or" is not exclusive. References to "days" mean calendar days unless otherwise specified. References to "written" or "writing" include electronic communications. These Terms shall not be construed against the drafter. Both English and French versions of these Terms are authentic; in the event of any discrepancy for Quebec residents, the French version shall prevail as specified in Section 26.</p>

    <hr>

    <h2>55. Cumulative Remedies</h2>
    <p>All rights and remedies provided in these Terms are cumulative and not exclusive of any other rights or remedies available at law, in equity, or otherwise. No exercise of any right or remedy shall preclude any other right or remedy. Any express waiver or failure to exercise any right or remedy shall not constitute a waiver of any other right or remedy, nor shall it constitute a continuing waiver.</p>

    <hr>

    <!-- ============================================ -->
    <!-- PART III: HARDWARE TERMS OF SALE             -->
    <!-- GoSiteMe-Manufactured Hardware (Alfred Robot)-->
    <!-- ============================================ -->

    <h2 style="color: var(--accent-color); font-size: 1.5rem; border-bottom: 2px solid var(--accent-color); padding-bottom: 0.5rem;">PART III &mdash; HARDWARE TERMS OF SALE</h2>
    <p>Sections 57 through 66 apply specifically to the purchase, ownership, and use of GoSiteMe-manufactured hardware ("GoSiteMe Hardware"), including the Alfred Robot line. These sections supplement (and where conflicting, supersede with respect to hardware) the general platform terms above.</p>

    <hr>

    <h2>57. Hardware Limited Warranty</h2>

    <div class="warning-box">
        <strong>IMPORTANT WARRANTY INFORMATION</strong><br>
        GoSiteMe provides a limited warranty on GoSiteMe Hardware. This warranty gives you specific legal rights, and you may also have other rights which vary by jurisdiction. In jurisdictions where exclusion or limitation of implied warranties is not permitted (including Quebec, the EU, UK, and Australia), the mandatory statutory warranties shall apply.
    </div>

    <h3>57.1 Warranty Coverage</h3>
    <p>GoSiteMe warrants to the original Purchaser that GoSiteMe Hardware will be free from Manufacturing Defects in materials and workmanship under normal use for the following periods from the date of delivery:</p>
    <table>
        <tr><th>Component</th><th>Warranty Period</th></tr>
        <tr><td>Alfred Robot &mdash; Structural frame, chassis, and housing</td><td><strong>2 years</strong></td></tr>
        <tr><td>Motors, actuators, and joint assemblies</td><td><strong>2 years</strong></td></tr>
        <tr><td>Sensors (LIDAR, cameras, proximity, environmental)</td><td><strong>2 years</strong></td></tr>
        <tr><td>Main computing unit and circuit boards</td><td><strong>2 years</strong></td></tr>
        <tr><td>Battery pack</td><td><strong>1 year</strong> or 500 charge cycles, whichever comes first</td></tr>
        <tr><td>Charging station</td><td><strong>2 years</strong></td></tr>
        <tr><td>Accessories and peripherals</td><td><strong>1 year</strong></td></tr>
        <tr><td>Software pre-installed on device</td><td><strong>90 days</strong> (for defects; ongoing updates per Section 9.5)</td></tr>
    </table>
    <p>For Purchasers in the European Economic Area, United Kingdom, and Australia: the warranty period shall be the longer of the period stated above or the minimum warranty period required by applicable law (e.g., 2 years under EU Sale of Goods Directive 2019/771).</p>

    <h3>57.2 Warranty Remedies</h3>
    <p>If a Manufacturing Defect is confirmed during the Hardware Warranty Period, GoSiteMe will, at GoSiteMe's option:</p>
    <ol>
        <li><strong>Repair</strong> the defective component at no charge to the Purchaser</li>
        <li><strong>Replace</strong> the defective unit or component with a new or refurbished equivalent at no charge</li>
        <li><strong>Refund</strong> the original purchase price of the defective unit if repair or replacement is not commercially feasible</li>
    </ol>
    <p>GoSiteMe will cover all shipping costs for warranty claims (both inbound and return). Refurbished replacement units carry the remainder of the original warranty or 90 days, whichever is longer.</p>

    <h3>57.3 Warranty Exclusions</h3>
    <p>This warranty does <strong>NOT</strong> cover:</p>
    <ul>
        <li>Normal wear and tear, cosmetic damage (scratches, dents, fading) that does not affect functionality</li>
        <li>Damage caused by accident, misuse, abuse, negligence, liquid exposure, or operation outside published specifications</li>
        <li>Damage caused by unauthorized modification, repair, disassembly, or tampering</li>
        <li>Damage caused by use with non-GoSiteMe-approved accessories, chargers, or power supplies</li>
        <li>Damage caused by operating the Robot in environments outside published operating specifications (extreme temperatures, humidity, altitude, corrosive atmospheres)</li>
        <li>Consumable parts (e.g., rubber treads, cleaning pads, filters) unless they fail due to a Manufacturing Defect</li>
        <li>Software issues, AI behavior, platform connectivity, or service availability (governed by Sections 7, 15, and 20)</li>
        <li>Damage from force majeure events</li>
    </ul>

    <h3>57.4 Warranty Claim Process</h3>
    <ol>
        <li>Contact GoSiteMe Support at <a href="mailto:hardware@gositeme.com">hardware@gositeme.com</a> or call 1-833-GOSITEME with your proof of purchase and description of the defect</li>
        <li>GoSiteMe will provide remote diagnostics and troubleshooting assistance</li>
        <li>If the issue cannot be resolved remotely, GoSiteMe will issue a Return Merchandise Authorization (RMA) number</li>
        <li>Ship the unit to the designated Authorized Service Provider using the prepaid shipping label provided</li>
        <li>GoSiteMe will inspect, repair, or replace the unit and return it within 15 business days of receipt</li>
    </ol>

    <h3>57.5 Mandatory Statutory Warranties</h3>
    <p>Nothing in this Section 57 limits or excludes any mandatory statutory warranty, guarantee, or right that cannot be excluded under applicable law, including but not limited to:</p>
    <ul>
        <li>Quebec's <em>Consumer Protection Act</em> mandatory legal warranty for latent defects (&sect;&sect; 37&ndash;53)</li>
        <li>EU <em>Sale of Goods Directive 2019/771</em> conformity guarantee</li>
        <li>UK <em>Consumer Rights Act 2015</em></li>
        <li>Australian <em>Consumer Law</em> consumer guarantees</li>
        <li>Canadian provincial <em>Sale of Goods Acts</em> implied conditions of merchantability and fitness</li>
        <li>U.S. <em>Magnuson-Moss Warranty Act</em> provisions</li>
    </ul>

    <hr>

    <h2>58. Hardware Purchase, Delivery &amp; Returns</h2>
    <h3>58.1 Orders &amp; Acceptance</h3>
    <p>Hardware orders are subject to acceptance by GoSiteMe. We reserve the right to refuse or cancel any order for any reason, including pricing errors, inventory limitations, or suspected fraud. Upon acceptance, you will receive an order confirmation with estimated delivery timeline.</p>

    <h3>58.2 Pricing &amp; Payment</h3>
    <p>Hardware prices are listed in USD and CAD. Prices exclude applicable taxes, duties, and shipping charges unless otherwise stated. All hardware purchases require full payment at the time of order unless a financing plan has been approved in writing.</p>

    <h3>58.3 Delivery &amp; Risk of Loss</h3>
    <p>Title to GoSiteMe Hardware and risk of loss transfer to the Purchaser upon delivery to the shipping carrier at our fulfillment facility (FCA Incoterms 2020), unless otherwise specified for your jurisdiction. GoSiteMe will insure all shipments against loss and damage in transit. If a unit is damaged in transit, notify GoSiteMe within 48 hours of delivery to initiate a replacement claim.</p>

    <h3>58.4 Inspection &amp; Acceptance</h3>
    <p>You have <strong>7 calendar days</strong> from delivery to inspect GoSiteMe Hardware for visible defects, missing components, or damage. You must notify GoSiteMe within this period of any issues. Failure to report within 7 days constitutes acceptance of the hardware in the condition received (except for latent defects covered under warranty).</p>

    <h3>58.5 Returns &amp; Right of Withdrawal</h3>
    <ul>
        <li><strong>General Return Policy:</strong> You may return GoSiteMe Hardware in unused, original condition within <strong>30 days</strong> of delivery for a full refund of the purchase price. Returns in opened but like-new condition may be subject to a restocking fee of up to 15% where permitted by applicable law.</li>
        <li><strong>Quebec Cooling-Off Period:</strong> Quebec consumers have a 10-business-day cooling-off period for distance purchases per the <em>Consumer Protection Act</em>.</li>
        <li><strong>EU Right of Withdrawal:</strong> EU consumers have a 14-day right of withdrawal per the <em>Consumer Rights Directive 2011/83/EU</em>.</li>
        <li><strong>Dead on Arrival (DOA):</strong> If GoSiteMe Hardware fails to power on or is non-functional upon first use, contact us within 48 hours for an immediate replacement at no charge.</li>
    </ul>
    <p>Return shipping for defective or DOA units is at GoSiteMe's expense. Return shipping for buyer's remorse returns is at the Purchaser's expense unless local law requires otherwise.</p>

    <h3>58.6 International Orders &amp; Customs</h3>
    <p>For international shipments, the Purchaser is responsible for all customs duties, import taxes, brokerage fees, and compliance with local import regulations unless GoSiteMe has agreed to deliver DDP (Delivered Duty Paid). GoSiteMe Hardware may not be available for shipment to all countries. Export of GoSiteMe Hardware is subject to Section 17 (Export Controls).</p>

    <hr>

    <h2>59. Product Safety &amp; Certifications</h2>
    <h3>59.1 Safety Standards</h3>
    <p>GoSiteMe Hardware is designed and tested to comply with applicable product safety standards for the markets in which it is sold. Applicable certifications and compliance marks will be listed on the product packaging, in the product documentation, and at <a href="/robots">gositeme.com/robots</a>. These may include, as applicable:</p>
    <ul>
        <li><strong>Canada:</strong> CSA certification, Innovation, Science and Economic Development Canada (ISED) radio equipment standards</li>
        <li><strong>United States:</strong> FCC Part 15 (radio emissions), UL listing (electrical safety)</li>
        <li><strong>European Union:</strong> CE marking, RED (Radio Equipment Directive 2014/53/EU), Machinery Regulation 2023/1230, Low Voltage Directive 2014/35/EU</li>
        <li><strong>United Kingdom:</strong> UKCA marking</li>
        <li><strong>International:</strong> ISO 13482 (personal care robots), IEC 62133 (battery safety), RoHS (Restriction of Hazardous Substances Directive 2011/65/EU), REACH regulation compliance</li>
    </ul>

    <h3>59.2 Emergency Stop Requirements</h3>
    <p>All Alfred Robots are equipped with:</p>
    <ul>
        <li>A <strong>physical emergency stop button</strong> accessible on the robot's exterior that immediately halts all motor functions and movement when pressed</li>
        <li>A <strong>remote emergency stop</strong> accessible through the GoSiteMe mobile app and web dashboard</li>
        <li>A <strong>fleet-wide emergency stop</strong> accessible to fleet administrators that stops all connected robots simultaneously</li>
        <li>An <strong>automatic safety stop</strong> triggered by proximity sensors, collision detection, tilt detection, or loss of connectivity (deadman switch)</li>
    </ul>
    <p>You must not disable, bypass, or obstruct any emergency stop mechanism. Doing so constitutes misuse and voids the warranty for any resulting damage.</p>

    <h3>59.3 User Safety Manual</h3>
    <p>GoSiteMe Hardware is accompanied by a comprehensive User Safety Manual. You are responsible for reading and following all safety instructions, warnings, and operating guidelines before use. The Safety Manual is also available digitally at <a href="/docs">gositeme.com/docs</a>.</p>

    <hr>

    <h2>60. Battery Safety &amp; Hazardous Materials</h2>

    <div class="warning-box">
        <strong>LITHIUM BATTERY SAFETY WARNING</strong><br>
        GoSiteMe Hardware contains lithium-ion or lithium-polymer batteries. Improper handling, charging, storage, or disposal of lithium batteries may result in <strong>fire, explosion, chemical burns, or toxic fume exposure</strong>. Follow all battery safety instructions in the User Safety Manual.
    </div>

    <h3>60.1 Charging Safety</h3>
    <ul>
        <li>Use <strong>only</strong> the GoSiteMe-provided charging station and power adapter. Use of unauthorized chargers may cause fire, battery damage, or personal injury and voids the warranty.</li>
        <li>Do not charge in environments exceeding the published temperature range (typically 5&deg;C&ndash;40&deg;C / 41&deg;F&ndash;104&deg;F)</li>
        <li>Do not leave charging unattended for extended periods during initial setup. After initial setup, the Robot's built-in charge management system handles automated charging.</li>
        <li>If the battery swells, emits heat, smoke, or unusual odor, immediately disconnect from the charger, move to a non-flammable surface, and contact GoSiteMe support</li>
    </ul>

    <h3>60.2 Battery Replacement</h3>
    <p>Battery replacement must be performed by GoSiteMe or an Authorized Service Provider. User-attempted battery replacement is not supported and voids the warranty. Replacement batteries are covered under the battery warranty terms if the original battery fails within warranty.</p>

    <h3>60.3 Transportation &amp; Shipping</h3>
    <p>GoSiteMe Hardware containing lithium batteries is classified as dangerous goods for air transport under IATA Dangerous Goods Regulations and Transport Canada's <em>Transportation of Dangerous Goods Act</em>. When shipping or transporting GoSiteMe Hardware, follow all applicable transportation regulations. GoSiteMe handles all regulatory packaging requirements for new shipments and warranty returns.</p>

    <h3>60.4 Disposal &amp; Recycling</h3>
    <p>GoSiteMe Hardware contains electronic components and lithium batteries that must not be disposed of in regular household waste. Dispose of GoSiteMe Hardware in accordance with local electronic waste (e-waste) regulations. GoSiteMe offers a take-back program &mdash; contact <a href="mailto:hardware@gositeme.com">hardware@gositeme.com</a> for free recycling of end-of-life units. For EU customers: GoSiteMe complies with the Waste Electrical and Electronic Equipment Directive (WEEE 2012/19/EU) producer obligations.</p>

    <hr>

    <h2>61. Firmware Support &amp; End-of-Life</h2>
    <h3>61.1 Firmware Support Commitment</h3>
    <p>GoSiteMe commits to providing firmware updates for GoSiteMe Hardware for a minimum of:</p>
    <ul>
        <li><strong>5 years</strong> from the date of original purchase for security patches and critical safety updates</li>
        <li><strong>3 years</strong> from the date of original purchase for feature updates and improvements</li>
        <li><strong>2 years</strong> from the date GoSiteMe publicly discontinues a hardware model for security-only updates</li>
    </ul>

    <h3>61.2 End-of-Support Notification</h3>
    <p>GoSiteMe will provide at least <strong>12 months' advance notice</strong> before discontinuing firmware support for any hardware model. Notice will be provided via email, dashboard notification, and on-device notification.</p>

    <h3>61.3 Post-Support Functionality</h3>
    <p>After firmware support ends, GoSiteMe Hardware will continue to operate with its last installed firmware. Basic standalone functionality (movement, voice interaction, pre-loaded capabilities) will continue to function. Cloud-dependent features (AI engine access, fleet management, OTA updates) may be degraded or discontinued as platform APIs evolve.</p>

    <h3>61.4 End-of-Life &amp; Decommissioning</h3>
    <p>When decommissioning GoSiteMe Hardware, you must:</p>
    <ol>
        <li>Perform a <strong>factory reset</strong> through the device settings or GoSiteMe app, which securely erases all personal data, WiFi credentials, voice recordings, camera data, user preferences, and linked accounts</li>
        <li>Remove the device from your GoSiteMe account through the dashboard</li>
        <li>Dispose of the device through GoSiteMe's take-back program (Section 60.4), an authorized e-waste recycler, or local e-waste collection</li>
    </ol>
    <p>GoSiteMe will provide a <strong>data destruction certificate</strong> upon request for decommissioned units returned through the take-back program.</p>

    <hr>

    <h2>62. Spare Parts &amp; Right to Repair</h2>
    <h3>62.1 Spare Parts Availability</h3>
    <p>GoSiteMe commits to maintaining availability of genuine spare parts for GoSiteMe Hardware for a minimum of <strong>5 years</strong> from the date GoSiteMe discontinues sale of the applicable hardware model, or as required by applicable law (whichever is longer). Spare parts may be purchased through <a href="mailto:hardware@gositeme.com">hardware@gositeme.com</a> or designated retail partners.</p>

    <h3>62.2 Authorized Repair</h3>
    <p>Warranty repairs and complex repairs (motor replacement, board replacement, structural repair) must be performed by GoSiteMe or an Authorized Service Provider. A network of Authorized Service Providers will be published at <a href="/support">gositeme.com/support</a>.</p>

    <h3>62.3 User-Serviceable Components</h3>
    <p>Certain components are designated as user-serviceable and may be replaced by the Purchaser without voiding the warranty. User-serviceable components are identified in the User Safety Manual and include items such as: external covers/panels, cleaning attachments, removable trays, and designated accessory ports. GoSiteMe will make repair guides for user-serviceable components available in product documentation.</p>

    <h3>62.4 Unauthorized Repair</h3>
    <p>Repair or modification of non-user-serviceable components by anyone other than GoSiteMe or an Authorized Service Provider voids the warranty for the affected component. However, unauthorized repair alone does not void the warranty for unrelated components unless the unauthorized repair caused or contributed to the defect.</p>

    <hr>

    <h2>63. Product Liability &amp; Manufacturer Obligations</h2>
    <h3>63.1 GoSiteMe's Manufacturer Responsibilities</h3>
    <p>As the manufacturer of GoSiteMe Hardware, GoSiteMe acknowledges and accepts the following responsibilities:</p>
    <ul>
        <li><strong>Design Safety:</strong> GoSiteMe is responsible for designing hardware that is reasonably safe for its intended and foreseeable uses when operated in accordance with product documentation</li>
        <li><strong>Manufacturing Quality:</strong> GoSiteMe maintains quality control processes to ensure hardware conforms to published specifications</li>
        <li><strong>Adequate Warnings:</strong> GoSiteMe provides adequate warnings about known risks and limitations through product labeling, documentation, and the User Safety Manual</li>
        <li><strong>Post-Sale Duty:</strong> GoSiteMe monitors for safety issues after sale and takes appropriate corrective action (recall, safety notice, firmware update) when defects or hazards are identified</li>
        <li><strong>Regulatory Compliance:</strong> GoSiteMe maintains compliance with applicable product safety regulations in the markets where GoSiteMe Hardware is sold</li>
    </ul>

    <h3>63.2 Product Liability Insurance</h3>
    <p>GoSiteMe maintains product liability insurance with coverage limits appropriate for a consumer robotics manufacturer. Details of insurance coverage are available upon written request to <a href="mailto:legal@gositeme.com">legal@gositeme.com</a> by Enterprise Customers and regulatory authorities.</p>

    <h3>63.3 Safety Incident Reporting</h3>
    <p>If GoSiteMe Hardware causes or contributes to personal injury, property damage, or a safety hazard, you should:</p>
    <ol>
        <li>Immediately activate the emergency stop and ensure all persons are safe</li>
        <li>Contact emergency services if anyone is injured</li>
        <li>Report the incident to GoSiteMe at <a href="mailto:safety@gositeme.com">safety@gositeme.com</a> or call 1-833-GOSITEME within 24 hours</li>
        <li>Preserve the Robot and the scene for investigation if safely possible</li>
    </ol>
    <p>GoSiteMe takes all safety reports seriously and will investigate promptly. GoSiteMe will report safety incidents to applicable regulatory authorities as required by law.</p>

    <h3>63.4 Liability Disclaimer for Misuse</h3>
    <p>GoSiteMe is <strong>not</strong> liable for injury, damage, or loss resulting from:</p>
    <ul>
        <li>Operation of GoSiteMe Hardware in a manner inconsistent with product documentation, the User Safety Manual, or applicable law</li>
        <li>Unauthorized modification, repair, disassembly, or tampering with GoSiteMe Hardware</li>
        <li>Use of GoSiteMe Hardware in environments or applications for which it was not designed or intended</li>
        <li>Failure to follow safety warnings, recall instructions, or mandatory firmware updates</li>
        <li>Third-party software, accessories, or modifications applied to GoSiteMe Hardware</li>
        <li>Continued operation of GoSiteMe Hardware after a known defect has been reported but before a repair or replacement is completed</li>
    </ul>

    <hr>

    <h2>64. Robot Behavior &amp; Autonomous Navigation</h2>
    <h3>64.1 Intended Use Environment</h3>
    <p>Alfred Robots are designed for operation in indoor residential and commercial environments with controlled conditions (flat floors, standard room temperatures, adequate lighting). Operation in outdoor environments, wet conditions, extreme temperatures, or uneven terrain is outside the intended use and may result in malfunction, damage, or injury.</p>

    <h3>64.2 Autonomous Navigation</h3>
    <ul>
        <li>Alfred Robots use sensors and AI for autonomous navigation. Despite safety systems, the Robot may occasionally collide with objects, furniture, walls, or persons. GoSiteMe is liable for collisions caused by Manufacturing Defects (e.g., faulty sensors, defective firmware) but not for collisions caused by environmental factors, user configuration, or inherent limitations of navigation technology</li>
        <li>You are responsible for maintaining a safe operating environment as described in the User Safety Manual, including keeping floors clear of small objects, securing loose cables, and blocking access to stairs (if the Robot model does not have stair-detection capability)</li>
    </ul>

    <h3>64.3 Interaction with Persons</h3>
    <ul>
        <li><strong>Children:</strong> Alfred Robots must not be used as substitutes for child supervision. Children under 12 should not interact with the Robot without adult supervision. The Robot's child safety mode (reduced speed, increased proximity sensitivity) should be enabled in households with children.</li>
        <li><strong>Elderly &amp; Persons with Disabilities:</strong> While Alfred Robots may assist with convenience tasks, they are <strong>not medical devices</strong> and must not be relied upon for fall detection, medication management, emergency alerting, or any health-critical function unless such features are explicitly offered and certified.</li>
        <li><strong>Pets:</strong> Alfred Robots may startle, confuse, or be damaged by pets. Pet interaction mode (reduced speed, sound mitigation) is available in settings. GoSiteMe is not liable for pet behavior toward the Robot or Robot behavior toward pets.</li>
    </ul>

    <h3>64.4 Recording &amp; Surveillance</h3>
    <p>Alfred Robots equipped with cameras and microphones may record ambient audio and video. You are solely responsible for complying with all applicable surveillance, wiretapping, and recording consent laws in your jurisdiction. Many jurisdictions require consent from all parties before recording audio. GoSiteMe provides privacy mode, camera covers, and microphone mute functionality to aid compliance. See the <a href="/privacy-policy.php">Privacy Policy</a> Section 10 for details on what data is collected and how it is processed.</p>

    <hr>

    <h2>65. Extended Warranty &amp; Service Plans</h2>
    <h3>65.1 Extended Warranty</h3>
    <p>GoSiteMe may offer optional Extended Warranty plans that extend the Hardware Warranty Period beyond the standard terms. Extended Warranty plans, pricing, and terms are presented at the time of hardware purchase or within 30 days of purchase. Extended Warranty coverage terms will be set forth in a separate Extended Warranty Agreement.</p>

    <h3>65.2 Accidental Damage Protection</h3>
    <p>GoSiteMe may offer optional Accidental Damage Protection plans covering accidental drops, liquid spills, and other unintentional damage not covered under the standard warranty. Terms and exclusions are set forth in the applicable protection plan agreement.</p>

    <h3>65.3 Service Plans</h3>
    <p>GoSiteMe may offer preventive maintenance service plans including periodic inspections, cleaning, calibration, and software optimization. Service plan tiers and pricing are published at <a href="/robots">gositeme.com/robots</a>.</p>

    <hr>

    <h2>66. Trade-In &amp; Upgrade Program</h2>
    <p>GoSiteMe may offer a trade-in program allowing Purchasers to trade in older GoSiteMe Hardware models for credit toward new models. Trade-in values are determined by GoSiteMe based on model, age, and condition. All traded-in units undergo secure data destruction (factory reset and certified data wiping) before refurbishment, recycling, or disposal. Trade-in program terms and current values are published at <a href="/robots">gositeme.com/robots</a>.</p>

    <hr>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- PART IV: ECOSYSTEM SOVEREIGNTY & ANTI-INTERFERENCE SHIELD     -->
    <!-- ═══════════════════════════════════════════════════════════════ -->

    <h2 style="color: #e74c3c;">Part IV &mdash; Ecosystem Sovereignty &amp; Anti-Interference Shield</h2>
    <p><em>The following provisions constitute binding, irrevocable commitments by GoSiteMe to its Users. These provisions are designed to protect the integrity, independence, and security of the GoSiteMe ecosystem, its encryption technologies, its AI systems (including Alfred OS), and the fundamental privacy rights of all Users. These provisions survive termination of this Agreement, any change of control of GoSiteMe, and any successor assignment.</em></p>

    <hr>

    <h2>67. Encryption Sovereignty &amp; No-Backdoor Covenant</h2>

    <h3>67.1 Absolute Encryption Commitment</h3>
    <p>GoSiteMe employs military-grade, post-quantum hybrid encryption across all communications, data storage, and device operations within the GoSiteMe ecosystem. This encryption stack includes, without limitation:</p>
    <ul>
        <li><strong>CRYSTALS-Kyber-1024 (ML-KEM)</strong> &mdash; NIST-approved post-quantum key encapsulation mechanism providing IND-CCA2 security against both classical and quantum computing attacks;</li>
        <li><strong>ECDH P-256</strong> &mdash; Classical elliptic-curve Diffie-Hellman key agreement;</li>
        <li><strong>AES-256-GCM</strong> &mdash; Authenticated encryption with 128-bit authentication tags;</li>
        <li><strong>HKDF-SHA256</strong> &mdash; Domain-separated key derivation;</li>
        <li><strong>Dilithium-inspired lattice signatures</strong> &mdash; Post-quantum digital signature verification;</li>
        <li><strong>Double Ratchet Protocol</strong> &mdash; Per-message forward secrecy ensuring compromise of any single message key cannot reveal past or future messages;</li>
        <li><strong>Hash Chain Integrity</strong> &mdash; Cryptographic message chain preventing retroactive tampering;</li>
        <li><strong>Key Commitment Scheme</strong> &mdash; Protection against invisible salamander attacks;</li>
        <li><strong>Steganographic Obfuscation</strong> &mdash; Traffic analysis resistance through protocol-agnostic message padding.</li>
    </ul>

    <h3>67.2 No-Backdoor Covenant</h3>
    <p>GoSiteMe makes the following irrevocable commitments:</p>
    <ol type="a">
        <li>GoSiteMe will <strong>never</strong> insert, maintain, or enable any backdoor, skeleton key, master decryption key, golden key, or any other mechanism that would allow any party &mdash; including GoSiteMe itself, its officers, directors, employees, contractors, successors, assigns, or any government, intelligence agency, law enforcement body, regulatory authority, or court of any jurisdiction &mdash; to decrypt, intercept, or access User communications or data without the explicit, informed, voluntary per-instance consent of the User who owns that data.</li>
        <li>GoSiteMe will <strong>never</strong> intentionally weaken, degrade, sabotage, or reduce the security of its encryption algorithms, key lengths, random number generation, or any other component of its cryptographic stack.</li>
        <li>GoSiteMe will <strong>never</strong> use key escrow, split-key schemes, or key recovery mechanisms that would allow any third party to recover User encryption keys.</li>
        <li>User encryption keys are generated on the User's device, stored in non-extractable format in the User's secure storage (IndexedDB with non-exportable CryptoKey constraints or equivalent), and are <strong>never transmitted to GoSiteMe servers</strong>.</li>
        <li>GoSiteMe <strong>cannot</strong> decrypt User messages, files, voice calls, video calls, or any other end-to-end encrypted content. This is a mathematical impossibility given the system architecture, not merely a policy choice.</li>
    </ol>

    <h3>67.3 Successor Binding</h3>
    <p>This No-Backdoor Covenant is binding upon GoSiteMe, its successors, assigns, acquirers, merged entities, and any party that assumes control of GoSiteMe's technology, infrastructure, or codebase. Any corporate transaction (merger, acquisition, asset sale, restructuring, or change of control) must include explicit written assumption of this Covenant as a condition precedent to closing. Failure to include this assumption renders the transaction voidable at the option of any affected User.</p>

    <hr>

    <h2>68. Anti-Compelled Decryption</h2>

    <h3>68.1 Technical Impossibility Defense</h3>
    <p>GoSiteMe's end-to-end encryption architecture is designed so that compliance with any order to decrypt User communications is a <strong>technical impossibility</strong>. GoSiteMe does not possess, and has never possessed, the cryptographic keys necessary to decrypt User communications. No court order, subpoena, warrant, national security letter, FISA order, lawful access order, or any other legal instrument can compel GoSiteMe to perform an action that is mathematically impossible.</p>

    <h3>68.2 Jurisdictional Position</h3>
    <p>GoSiteMe will challenge, appeal, and exhaust all legal remedies against any order, law, regulation, or directive from any jurisdiction that purports to:</p>
    <ol type="a">
        <li>Compel GoSiteMe to create decryption capabilities that do not exist;</li>
        <li>Require GoSiteMe to weaken its encryption;</li>
        <li>Mandate key escrow or key recovery;</li>
        <li>Require GoSiteMe to install surveillance capabilities;</li>
        <li>Prohibit GoSiteMe from offering strong encryption to its Users;</li>
        <li>Require GoSiteMe to re-engineer its cryptographic architecture.</li>
    </ol>

    <h3>68.3 Relocation Commitment</h3>
    <p>If any jurisdiction enacts legislation that would effectively require GoSiteMe to compromise the security of its encryption, GoSiteMe commits to relocating the affected operations, infrastructure, and data to a jurisdiction that respects encryption rights, rather than complying with such legislation. GoSiteMe maintains contingency infrastructure in multiple privacy-respecting jurisdictions for this purpose.</p>

    <h3>68.4 User Notification</h3>
    <p>To the maximum extent permitted by law, GoSiteMe will notify affected Users of any legal process seeking their data, communications, or device information. Where gag orders or secrecy provisions prohibit direct notification, GoSiteMe will challenge such provisions in court and publish aggregate transparency reports.</p>

    <hr>

    <h2>69. Warrant Canary</h2>

    <h3>69.1 Canary Statement</h3>
    <p>GoSiteMe publishes a cryptographically signed Warrant Canary at <a href="/security">gositeme.com/security</a>, updated no less frequently than quarterly. The Warrant Canary affirms the following:</p>
    <ol type="a">
        <li>GoSiteMe has not received any National Security Letter, FISA order, or equivalent order from any jurisdiction;</li>
        <li>GoSiteMe has not been compelled to insert any backdoor into its products;</li>
        <li>GoSiteMe has not been compelled to weaken its encryption;</li>
        <li>GoSiteMe has not been subject to any gag order prohibiting disclosure of government surveillance requests;</li>
        <li>GoSiteMe has not transferred any User encryption keys to any third party;</li>
        <li>GoSiteMe has not modified its cryptographic protocols under government direction;</li>
        <li>No GoSiteMe employee, officer, or contractor has been individually compelled to compromise User security.</li>
    </ol>

    <h3>69.2 Canary Removal</h3>
    <p>Users acknowledge that the <strong>removal or non-renewal</strong> of any Warrant Canary statement should be interpreted as an indication that the corresponding affirmation can no longer be made. GoSiteMe is not responsible for Users' interpretations of Warrant Canary changes.</p>

    <h3>69.3 Verification</h3>
    <p>Each Warrant Canary publication is signed with GoSiteMe's public cryptographic key (published at <a href="/security">gositeme.com/security</a>) and includes a timestamp and recent news headline to prove currency.</p>

    <hr>

    <h2>70. Ecosystem Independence &amp; Anti-Forced-Sale Protection</h2>

    <h3>70.1 Independence Commitment</h3>
    <p>GoSiteMe is a privately held, independently operated technology company. GoSiteMe commits to maintaining its operational independence and will not submit to external control, direction, or influence from any government, intelligence agency, competitor, or consortium that would compromise User privacy, security, or the integrity of the GoSiteMe ecosystem.</p>

    <h3>70.2 Anti-Forced-Sale</h3>
    <p>GoSiteMe will not sell, transfer, license, or assign its encryption technology, User data, AI systems (including Alfred OS), or core infrastructure to any entity that does not assume all obligations under this Agreement, including this Part IV. Any sale or transfer that would result in degradation of User encryption, privacy protections, or service quality is prohibited under these Terms.</p>

    <h3>70.3 Anti-Compelled-Sale</h3>
    <p>If GoSiteMe is subjected to any legal action, regulatory pressure, or governmental directive that effectively compels the sale, dissolution, or divestiture of GoSiteMe or any of its core encryption, AI, or communications technologies:</p>
    <ol type="a">
        <li>GoSiteMe will exhaust all legal remedies to resist such compulsion;</li>
        <li>GoSiteMe will provide Users with at least 180 days' notice of any forced sale or transfer;</li>
        <li>GoSiteMe will offer all Users the ability to export their data in standard, interoperable formats;</li>
        <li>GoSiteMe will, if feasible, release the encryption components as open-source software to ensure continuity of User security independent of corporate ownership;</li>
        <li>GoSiteMe will ensure that any acquiring entity is contractually bound by all provisions of this Part IV.</li>
    </ol>

    <h3>70.4 Alfred OS Sovereignty</h3>
    <p>Alfred OS &mdash; GoSiteMe's autonomous AI operating system for robotics, fleet management, edge computing, and intelligent automation &mdash; is a core, inseparable component of the GoSiteMe ecosystem. GoSiteMe commits that:</p>
    <ol type="a">
        <li>Alfred OS will not be lobotomized, functionally restricted, or feature-reduced at the direction of any external authority;</li>
        <li>Alfred OS safety systems (ISO 13482, ISO 13849, ISO/TS 15066 compliance) remain under GoSiteMe engineering control and cannot be modified by external mandate except where such mandate genuinely improves User safety;</li>
        <li>Alfred OS device provisioning, firmware updates, and edge AI model deployments remain under GoSiteMe&rsquo;s independent control;</li>
        <li>Alfred OS telemetry, MQTT communications, and geofencing data are encrypted end-to-end and are never shared with governmental authorities absent valid, specific, non-bulk legal process.</li>
    </ol>

    <hr>

    <h2>71. Anti-Regulatory Capture Defense</h2>

    <h3>71.1 Definition</h3>
    <p>&ldquo;Regulatory Capture&rdquo; means any situation in which a regulatory body, legislative body, executive authority, or industry standards organization is used &mdash; directly or indirectly &mdash; to impose requirements on GoSiteMe that would benefit GoSiteMe's competitors, reduce User privacy, weaken encryption, restrict AI capabilities, limit robotics functionality, or otherwise degrade the GoSiteMe ecosystem for the benefit of external parties.</p>

    <h3>71.2 GoSiteMe's Response to Regulatory Capture</h3>
    <p>GoSiteMe commits to the following responses if subjected to Regulatory Capture:</p>
    <ol type="a">
        <li><strong>Legal Challenge:</strong> GoSiteMe will challenge any regulation, standard, or directive that constitutes Regulatory Capture through all available legal channels, including constitutional challenges, administrative appeals, and international arbitration;</li>
        <li><strong>Transparency:</strong> GoSiteMe will publicly disclose any attempts at Regulatory Capture, including the identity of the parties involved, the nature of the attempted capture, and GoSiteMe's response;</li>
        <li><strong>Compliance Proportionality:</strong> GoSiteMe will not comply with overbroad, discriminatory, or competition-distorting regulations that are not applied equally to all market participants;</li>
        <li><strong>Jurisdictional Diversification:</strong> GoSiteMe will disperse its operations across multiple jurisdictions to prevent any single jurisdiction from exercising disproportionate leverage;</li>
        <li><strong>User Advocate:</strong> GoSiteMe will act as advocate for User interests in all regulatory proceedings and will oppose any regulation that would reduce User privacy, security, or functionality.</li>
    </ol>

    <hr>

    <h2>72. Open-Source Cryptographic Commitment</h2>

    <h3>72.1 Cryptographic Transparency</h3>
    <p>GoSiteMe commits to the following principles of cryptographic transparency:</p>
    <ol type="a">
        <li>All cryptographic algorithms used in the GoSiteMe ecosystem are based on well-studied, peer-reviewed, published algorithms (Kyber, Dilithium, AES, ECDH, HKDF, SHA-256);</li>
        <li>GoSiteMe does not rely on security through obscurity for any cryptographic operation;</li>
        <li>GoSiteMe's cryptographic implementations are available for independent security audit upon request by qualified researchers under responsible disclosure terms;</li>
        <li>GoSiteMe publishes a cryptographic specification document describing the algorithms, parameters, and protocols used in the Veil encryption stack;</li>
        <li>GoSiteMe welcomes and rewards responsible security research through its bug bounty program.</li>
    </ol>

    <h3>72.2 No Proprietary Cryptography</h3>
    <p>GoSiteMe does not use any proprietary, secret, or unpublished cryptographic algorithms. Every algorithm in the encryption stack is either a NIST standard, a NIST-approved post-quantum algorithm, or an implementation of a well-known published protocol. GoSiteMe will never introduce proprietary cryptography as a replacement for standard algorithms.</p>

    <hr>

    <h2>73. No Feature-Kill Compliance</h2>

    <h3>73.1 Anti-Feature-Kill</h3>
    <p>GoSiteMe will not, at the direction of any government, regulatory body, competitor, or external party:</p>
    <ol type="a">
        <li>Disable, degrade, or remove end-to-end encryption from any GoSiteMe product or service;</li>
        <li>Disable, degrade, or remove post-quantum cryptographic protections;</li>
        <li>Disable, degrade, or remove forward secrecy mechanisms;</li>
        <li>Disable, degrade, or remove the Veil Fortress encryption layers;</li>
        <li>Disable, degrade, or remove Alfred OS autonomous AI capabilities;</li>
        <li>Disable, degrade, or remove Alfred OS robotics, fleet management, MQTT, geofencing, edge AI, or device management capabilities;</li>
        <li>Artificially limit the number of Users, devices, robots, or agents that can participate in the GoSiteMe ecosystem;</li>
        <li>Artificially limit the geographic regions in which GoSiteMe services are available;</li>
        <li>Add User tracking, surveillance, or monitoring capabilities not disclosed in the Privacy Policy;</li>
        <li>Modify the behavior of Alfred OS to prioritize external interests over User interests.</li>
    </ol>

    <h3>73.2 Exception</h3>
    <p>The only exception to Section 73.1 is where GoSiteMe independently determines, through its own engineering and safety review, that a feature modification is necessary to protect User safety. Such modifications will be disclosed publicly and explained in detail.</p>

    <hr>

    <h2>74. Sovereign Infrastructure Rights</h2>

    <h3>74.1 Infrastructure Independence</h3>
    <p>GoSiteMe operates its own infrastructure stack including, without limitation: WebSocket servers, MQTT brokers, firmware OTA systems, device provisioning systems, edge AI inference engines, geofencing engines, safety interlock controllers, and encryption key generation systems. GoSiteMe is not dependent on any single cloud provider, CDN, DNS provider, or certificate authority for its core operations.</p>

    <h3>74.2 No Single Point of External Control</h3>
    <p>GoSiteMe's architecture is designed so that no single external party (including hosting providers, domain registrars, certificate authorities, or payment processors) can unilaterally disable the GoSiteMe ecosystem. GoSiteMe maintains redundant infrastructure, multiple domain strategies, and backup communication channels.</p>

    <h3>74.3 Post-Quantum Infrastructure</h3>
    <p>GoSiteMe's internal infrastructure communications use post-quantum cryptographic protections, ensuring that even the interception of infrastructure-level traffic by quantum-capable adversaries cannot compromise User data.</p>

    <hr>

    <h2>75. User Data Sovereignty Guarantee</h2>

    <h3>75.1 Your Data Belongs to You</h3>
    <p>User data &mdash; including communications, files, AI interactions, robot telemetry, device configurations, and all other data generated through the GoSiteMe ecosystem &mdash; belongs exclusively to the User who created it. GoSiteMe is a custodian, not an owner, of User data.</p>

    <h3>75.2 Data Portability</h3>
    <p>Users have the absolute right to export all of their data from the GoSiteMe ecosystem at any time, in standard, interoperable, machine-readable formats. GoSiteMe will never hold User data hostage or impose unreasonable barriers to data export.</p>

    <h3>75.3 Data Destruction</h3>
    <p>Upon User request, GoSiteMe will permanently and irrecoverably destroy all User data within 30 days, including all backups, replicas, and cached copies. Destruction will be performed using cryptographic erasure (destroying the encryption keys that protect the data) and, where applicable, NIST SP 800-88 compliant media sanitization.</p>

    <h3>75.4 No Data Monetization</h3>
    <p>GoSiteMe will <strong>never</strong> sell, license, rent, share, trade, barter, or otherwise monetize User data, communications, AI interactions, robot telemetry, or any other User-generated content. GoSiteMe's revenue comes from services, subscriptions, and hardware sales &mdash; never from User data.</p>

    <hr>

    <h2>76. Contact Us</h2>
    <p>For questions about these Terms of Service:</p>
    <div class="highlight-box">
        <strong>GoSiteMe &mdash; Legal Department</strong><br>
        Phone: 1-833-GOSITEME (1-833-467-4836)<br>
        Legal: <a href="mailto:legal@gositeme.com">legal@gositeme.com</a><br>
        Privacy: <a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a><br>
        Security: <a href="mailto:security@gositeme.com">security@gositeme.com</a><br>
        Hardware Support: <a href="mailto:hardware@gositeme.com">hardware@gositeme.com</a><br>
        Safety Reporting: <a href="mailto:safety@gositeme.com">safety@gositeme.com</a><br>
        General Support: <a href="mailto:support@gositeme.com">support@gositeme.com</a><br>
        Live Chat: Available 24/7 at <a href="https://gositeme.com">gositeme.com</a><br>
        Client Area: <a href="<?php echo htmlspecialchars(billing_link('clientarea.php')); ?>">Support Tickets</a>
    </div>

    <p style="margin-top: 2rem; color: #888; font-size: 0.85rem;">&copy; <?php echo date('Y'); ?> GoSiteMe. All rights reserved. GoSiteMe is a privately held technology company. These Terms are provided in English and French. This document does not constitute legal advice.</p>

</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
