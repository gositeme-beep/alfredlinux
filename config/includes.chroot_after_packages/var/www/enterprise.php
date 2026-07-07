<?php
require_once __DIR__ . '/includes/lang.php';

$page_translations = [
    'en' => [
        'page_title' => 'Enterprise AI — Alfred for Enterprise | Fleet Management & SLA | GoSiteMe',
        'page_desc' => 'Alfred AI for enterprise: unlimited fleets, dedicated account manager, 99.9% SLA, SSO, custom tools, 24/7 priority support. AI at scale starting at $24.99/mo.',
        'og_title' => 'Alfred for Enterprise — AI at Scale',
        'og_desc' => 'Deploy unlimited AI agents with 13,000+ tools, 99.9% SLA, SSO, and priority support. Enterprise AI that scales with your business.',
        'hero_badge' => 'Enterprise Solutions',
        'hero_title' => 'Alfred for Enterprise — ',
        'hero_title_accent' => 'AI at Scale',
        'hero_desc' => 'Deploy unlimited AI agents across your organization. 13,000+ tools, 99.9% SLA, SSO, and dedicated support — built for businesses that demand reliability.',
        'hero_cta' => 'Talk to Sales',
        'stat_tools' => 'AI Tools',
        'stat_agents' => 'Agents',
        'stat_agents_val' => 'Unlimited',
        'stat_sla' => 'SLA Uptime',
        'stat_support' => 'Support',
        'feat_title' => 'Enterprise-Grade Features',
        'feat_sub' => 'Everything you need to deploy AI at organizational scale',
        'feat1_title' => 'Unlimited Fleets & Agents',
        'feat1_desc' => 'Create as many AI agent fleets as you need. No limits on agents, deployments, or concurrent sessions. Scale horizontally without friction.',
        'feat2_title' => 'Dedicated Account Manager',
        'feat2_desc' => 'A named account manager who knows your business, handles escalations, and coordinates custom integrations for your team.',
        'feat3_title' => 'SLA Guarantee — 99.9% Uptime',
        'feat3_desc' => 'Enterprise-grade uptime with service credits if we miss the mark. Your business depends on AI — we take that seriously.',
        'feat4_title' => 'SSO & Team Management',
        'feat4_desc' => 'SAML/OAuth SSO integration, role-based access control, team workspaces, and centralized billing for your organization.',
        'feat5_title' => 'Custom Tool Development',
        'feat5_desc' => 'We build custom tools specific to your workflows. Connect Alfred to your internal APIs, databases, and business logic.',
        'feat6_title' => 'Priority Support — 24/7',
        'feat6_desc' => 'Round-the-clock priority support via phone, email, and Slack. Escalation paths and guaranteed response times in your SLA.',
        'feat7_title' => 'Compliance & Security',
        'feat7_desc' => 'SOC2 and HIPAA-ready infrastructure. Data encryption at rest and in transit, audit logs, and compliance reporting. Post-quantum Kyber-1024 hybrid encryption for future-proof communications.',
        'feat8_title' => 'Volume Pricing',
        'feat8_desc' => 'Custom pricing for large deployments. Annual contracts with significant discounts. Pay per seat, per agent, or per call — your choice.',
        'roi_title' => 'ROI Calculator',
        'roi_sub' => 'See how much Alfred can save your business each month',
        'roi_agents' => 'Number of Support Agents',
        'roi_calls' => 'Avg Calls per Day (per agent)',
        'roi_duration' => 'Avg Call Duration (minutes)',
        'roi_btn' => 'Calculate Savings',
        'roi_est_savings' => 'Estimated monthly savings with Alfred',
        'roi_current' => 'Current monthly cost',
        'roi_alfred' => 'With Alfred',
        'roi_automated' => 'Calls automated',
        'roi_time' => 'Hours saved/month',
        'uc_title' => 'Enterprise Use Cases',
        'uc_sub' => 'Alfred powers AI across industries',
        'uc1_title' => 'Call Centers',
        'uc1_desc' => 'Automate 60%+ of inbound calls. AI agents handle FAQs, route complex issues, and provide 24/7 support coverage.',
        'uc1_li1' => 'Automated call routing & triage',
        'uc1_li2' => 'Real-time sentiment analysis',
        'uc1_li3' => 'Multi-language support (EN/FR)',
        'uc1_li4' => 'Call recording & transcription',
        'uc2_title' => 'Legal Firms',
        'uc2_desc' => '43 specialized legal tools for case research, motion drafting, statute lookup, and client intake automation.',
        'uc2_li1' => 'Case law research & citation',
        'uc2_li2' => 'Contract review & analysis',
        'uc2_li3' => 'Automated motion drafting',
        'uc2_li4' => 'Client intake via voice',
        'uc3_title' => 'Healthcare Systems',
        'uc3_desc' => 'HIPAA-ready AI for patient triage, appointment scheduling, symptom checking, and insurance verification.',
        'uc3_li1' => 'Patient symptom triage',
        'uc3_li2' => 'Appointment scheduling',
        'uc3_li3' => 'Insurance pre-authorization',
        'uc3_li4' => 'HIPAA-compliant data handling',
        'uc4_title' => 'Education Districts',
        'uc4_desc' => '52 education tools for tutoring, homework help, curriculum planning, and student engagement analytics.',
        'uc4_li1' => 'AI tutoring assistants',
        'uc4_li2' => 'Homework help & explanations',
        'uc4_li3' => 'Curriculum generation',
        'uc4_li4' => 'Parent communication bots',
        'uc5_title' => 'Enterprise IT',
        'uc5_desc' => '48 DevOps tools for infrastructure management, CI/CD, monitoring, and incident response automation.',
        'uc5_li1' => 'Infrastructure monitoring',
        'uc5_li2' => 'Incident auto-response',
        'uc5_li3' => 'CI/CD pipeline management',
        'uc5_li4' => 'Security scanning & alerts',
        'contact_title' => 'Talk to Sales',
        'contact_sub' => 'Tell us about your needs and we\'ll put together a custom plan for your team.',
        'lbl_company' => 'Company Name *',
        'lbl_email' => 'Work Email *',
        'lbl_phone' => 'Phone Number',
        'lbl_size' => 'Company Size *',
        'lbl_message' => 'How can we help? *',
        'ph_company' => 'Acme Corporation',
        'ph_email' => 'you@company.com',
        'ph_phone' => '+1 (555) 123-4567',
        'ph_size' => 'Select size...',
        'ph_message' => 'Tell us about your use case, team size, and what tools you\'re interested in...',
        'size_1' => '1–10 employees',
        'size_2' => '11–50 employees',
        'size_3' => '51–200 employees',
        'size_4' => '201–1,000 employees',
        'size_5' => '1,001–5,000 employees',
        'size_6' => '5,000+ employees',
        'btn_send' => 'Send Message',
        'success_title' => 'Message Sent!',
        'success_text' => 'Our sales team will contact you within 24 hours. In the meantime, feel free to call us at <strong>1-833-GOSITEME</strong>.',
        'trust_title' => 'Trusted by Teams Worldwide',
        'trust_soc2' => 'SOC2 Ready',
        'trust_hipaa' => 'HIPAA Ready',
        'trust_ddos' => 'DDoS Protected',
        'trust_ssl' => 'SSL Encrypted',
        'trust_pq' => 'Post-Quantum Ready',
        'trust_bilingual' => 'Bilingual EN/FR',
        'trust_sla' => '99.9% SLA',
    ],
    'fr' => [
        'page_title' => 'IA Entreprise — Alfred pour les entreprises | Gestion de flotte & SLA | GoSiteMe',
        'page_desc' => 'Alfred IA pour les entreprises : flottes illimitées, gestionnaire de compte dédié, SLA 99,9 %, SSO, outils personnalisés, support prioritaire 24/7. IA à grande échelle à partir de 24,99 $ USD/mois.',
        'og_title' => 'Alfred pour les entreprises — IA à grande échelle',
        'og_desc' => 'Déployez des agents IA illimités avec 13,000+ outils, SLA 99,9 %, SSO et support prioritaire. L\'IA entreprise qui évolue avec votre business.',
        'hero_badge' => 'Solutions Entreprise',
        'hero_title' => 'Alfred pour les entreprises — ',
        'hero_title_accent' => 'IA à grande échelle',
        'hero_desc' => 'Déployez des agents IA illimités dans votre organisation. 13,000+ outils, SLA 99,9 %, SSO et support dédié — conçu pour les entreprises qui exigent la fiabilité.',
        'hero_cta' => 'Contacter les ventes',
        'stat_tools' => 'Outils IA',
        'stat_agents' => 'Agents',
        'stat_agents_val' => 'Illimités',
        'stat_sla' => 'SLA Disponibilité',
        'stat_support' => 'Support',
        'feat_title' => 'Fonctionnalités de niveau entreprise',
        'feat_sub' => 'Tout ce qu\'il faut pour déployer l\'IA à l\'échelle organisationnelle',
        'feat1_title' => 'Flottes et agents illimités',
        'feat1_desc' => 'Créez autant de flottes d\'agents IA que nécessaire. Aucune limite sur les agents, déploiements ou sessions concurrentes. Évoluez horizontalement sans friction.',
        'feat2_title' => 'Gestionnaire de compte dédié',
        'feat2_desc' => 'Un gestionnaire de compte attitré qui connaît votre entreprise, gère les escalades et coordonne les intégrations personnalisées pour votre équipe.',
        'feat3_title' => 'Garantie SLA — 99,9 % de disponibilité',
        'feat3_desc' => 'Disponibilité de niveau entreprise avec crédits de service si nous manquons l\'objectif. Votre entreprise dépend de l\'IA — nous prenons ça au sérieux.',
        'feat4_title' => 'SSO et gestion d\'équipe',
        'feat4_desc' => 'Intégration SSO SAML/OAuth, contrôle d\'accès basé sur les rôles, espaces de travail d\'équipe et facturation centralisée pour votre organisation.',
        'feat5_title' => 'Développement d\'outils personnalisés',
        'feat5_desc' => 'Nous créons des outils sur mesure pour vos flux de travail. Connectez Alfred à vos API internes, bases de données et logique métier.',
        'feat6_title' => 'Support prioritaire — 24/7',
        'feat6_desc' => 'Support prioritaire en tout temps par téléphone, courriel et Slack. Chemins d\'escalade et temps de réponse garantis dans votre SLA.',
        'feat7_title' => 'Conformité et sécurité',
        'feat7_desc' => 'Infrastructure prête pour SOC2 et HIPAA. Chiffrement des données au repos et en transit, journaux d\'audit et rapports de conformité. Chiffrement hybride post-quantique Kyber-1024 pour des communications à l\'épreuve du futur.',
        'feat8_title' => 'Tarification par volume',
        'feat8_desc' => 'Tarification personnalisée pour les grands déploiements. Contrats annuels avec rabais importants. Payez par siège, par agent ou par appel — à vous de choisir.',
        'roi_title' => 'Calculateur de ROI',
        'roi_sub' => 'Voyez combien Alfred peut faire économiser à votre entreprise chaque mois',
        'roi_agents' => 'Nombre d\'agents de support',
        'roi_calls' => 'Appels moyens par jour (par agent)',
        'roi_duration' => 'Durée moyenne d\'appel (minutes)',
        'roi_btn' => 'Calculer les économies',
        'roi_est_savings' => 'Économies mensuelles estimées avec Alfred',
        'roi_current' => 'Coût mensuel actuel',
        'roi_alfred' => 'Avec Alfred',
        'roi_automated' => 'Appels automatisés',
        'roi_time' => 'Heures économisées/mois',
        'uc_title' => 'Cas d\'utilisation entreprise',
        'uc_sub' => 'Alfred propulse l\'IA dans tous les secteurs',
        'uc1_title' => 'Centres d\'appels',
        'uc1_desc' => 'Automatisez 60 %+ des appels entrants. Les agents IA gèrent les FAQ, acheminent les problèmes complexes et offrent un support 24/7.',
        'uc1_li1' => 'Acheminement et triage automatisés des appels',
        'uc1_li2' => 'Analyse de sentiment en temps réel',
        'uc1_li3' => 'Support multilingue (EN/FR)',
        'uc1_li4' => 'Enregistrement et transcription des appels',
        'uc2_title' => 'Cabinets d\'avocats',
        'uc2_desc' => '43 outils juridiques spécialisés pour la recherche de cas, la rédaction de motions, la consultation de lois et l\'automatisation de l\'accueil client.',
        'uc2_li1' => 'Recherche et citation de jurisprudence',
        'uc2_li2' => 'Révision et analyse de contrats',
        'uc2_li3' => 'Rédaction automatisée de motions',
        'uc2_li4' => 'Accueil client par la voix',
        'uc3_title' => 'Systèmes de santé',
        'uc3_desc' => 'IA conforme HIPAA pour le triage des patients, la prise de rendez-vous, la vérification des symptômes et la vérification d\'assurance.',
        'uc3_li1' => 'Triage des symptômes des patients',
        'uc3_li2' => 'Prise de rendez-vous',
        'uc3_li3' => 'Pré-autorisation d\'assurance',
        'uc3_li4' => 'Traitement des données conforme HIPAA',
        'uc4_title' => 'Districts scolaires',
        'uc4_desc' => '52 outils éducatifs pour le tutorat, l\'aide aux devoirs, la planification des programmes et l\'analyse de l\'engagement étudiant.',
        'uc4_li1' => 'Assistants de tutorat IA',
        'uc4_li2' => 'Aide aux devoirs et explications',
        'uc4_li3' => 'Génération de programmes d\'études',
        'uc4_li4' => 'Bots de communication avec les parents',
        'uc5_title' => 'TI d\'entreprise',
        'uc5_desc' => '48 outils DevOps pour la gestion d\'infrastructure, CI/CD, surveillance et automatisation de la réponse aux incidents.',
        'uc5_li1' => 'Surveillance d\'infrastructure',
        'uc5_li2' => 'Réponse automatique aux incidents',
        'uc5_li3' => 'Gestion des pipelines CI/CD',
        'uc5_li4' => 'Analyse de sécurité et alertes',
        'contact_title' => 'Contacter les ventes',
        'contact_sub' => 'Parlez-nous de vos besoins et nous préparerons un plan personnalisé pour votre équipe.',
        'lbl_company' => 'Nom de l\'entreprise *',
        'lbl_email' => 'Courriel professionnel *',
        'lbl_phone' => 'Numéro de téléphone',
        'lbl_size' => 'Taille de l\'entreprise *',
        'lbl_message' => 'Comment pouvons-nous aider ? *',
        'ph_company' => 'Société Acmé',
        'ph_email' => 'vous@entreprise.com',
        'ph_phone' => '+1 (555) 123-4567',
        'ph_size' => 'Sélectionner la taille...',
        'ph_message' => 'Parlez-nous de votre cas d\'utilisation, de la taille de votre équipe et des outils qui vous intéressent...',
        'size_1' => '1–10 employés',
        'size_2' => '11–50 employés',
        'size_3' => '51–200 employés',
        'size_4' => '201–1 000 employés',
        'size_5' => '1 001–5 000 employés',
        'size_6' => '5 000+ employés',
        'btn_send' => 'Envoyer le message',
        'success_title' => 'Message envoyé !',
        'success_text' => 'Notre équipe de vente vous contactera dans les 24 heures. En attendant, n\'hésitez pas à nous appeler au <strong>1-833-GOSITEME</strong>.',
        'trust_title' => 'Approuvé par des équipes du monde entier',
        'trust_soc2' => 'SOC2 Prêt',
        'trust_hipaa' => 'HIPAA Prêt',
        'trust_ddos' => 'Protection DDoS',
        'trust_ssl' => 'Chiffrement SSL',
        'trust_pq' => 'Post-Quantique Prêt',
        'trust_bilingual' => 'Bilingue EN/FR',
        'trust_sla' => 'SLA 99,9 %',
    ],
];
if (!function_exists('PT')) {
    function PT($key) {
        global $page_translations, $current_lang;
        return $page_translations[$current_lang][$key] ?? $page_translations['en'][$key] ?? $key;
    }
}

$page_title = PT('page_title');
$page_description = PT('page_desc');
$page_canonical = 'https://root.com/enterprise.php';
$page_og_title = PT('og_title');
$page_og_description = PT('og_desc');
include __DIR__ . '/includes/site-header.inc.php';
?>

<!-- Schema.org Organization markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Alfred AI for Enterprise",
  "description": "Enterprise-grade AI platform with unlimited fleets, SLA, SSO, and custom tool development.",
  "url": "https://root.com/enterprise.php",
  "publisher": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://root.com",
    "logo": "https://root.com/brand/logo_w.png",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+1-833-467-4836",
      "contactType": "sales",
      "availableLanguage": ["English", "French"]
    }
  },
  "offers": {
    "@type": "Offer",
    "name": "Alfred AI Enterprise",
    "price": "24.99",
    "priceCurrency": "USD",
    "description": "Unlimited fleets, agents, tool calls. 99.9% SLA, SSO, dedicated account manager, 24/7 priority support.",
    "availability": "https://schema.org/InStock"
  }
}
</script>

<style>
/* ===== Enterprise Page Styles ===== */
:root {
    --ent-bg: #0a0a14;
    --ent-surface: #12121e;
    --ent-surface-2: #1a1a2e;
    --ent-surface-3: #22223a;
    --ent-border: rgba(255,255,255,0.08);
    --ent-accent: #6c5ce7;
    --ent-accent-light: #a29bfe;
    --ent-blue: #0984e3;
    --ent-green: #00b894;
    --ent-orange: #fdcb6e;
    --ent-fire: #e17055;
    --ent-pink: #fd79a8;
    --ent-cyan: #00cec9;
    --ent-text: #e8e8f0;
    --ent-text-muted: #8a8a9a;
    --ent-radius: 16px;
}

/* Hero */
.ent-hero {
    padding: 140px 20px 100px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--ent-bg) 70%);
}
.ent-hero::before {
    content: '';
    position: absolute;
    top: -40%; left: -20%;
    width: 140%; height: 180%;
    background:
        radial-gradient(circle at 30% 25%, rgba(108,92,231,0.18) 0%, transparent 50%),
        radial-gradient(circle at 70% 65%, rgba(9,132,227,0.12) 0%, transparent 50%),
        radial-gradient(circle at 50% 85%, rgba(0,184,148,0.08) 0%, transparent 40%);
    pointer-events: none;
}
.ent-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    position: relative;
}
.ent-hero h1 span {
    background: linear-gradient(135deg, var(--ent-accent), var(--ent-blue), var(--ent-green));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ent-hero p {
    color: var(--ent-text-muted);
    font-size: 1.15rem;
    margin-bottom: 30px;
    position: relative;
    max-width: 640px;
    margin-left: auto;
    margin-right: auto;
}
.ent-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    border-radius: 50px;
    background: rgba(108,92,231,0.15);
    border: 1px solid rgba(108,92,231,0.3);
    color: var(--ent-accent-light);
    font-weight: 600;
    font-size: 0.9rem;
    position: relative;
    margin-bottom: 20px;
}
.ent-hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    border-radius: 50px;
    background: linear-gradient(135deg, var(--ent-accent), var(--ent-blue));
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    font-size: 1.05rem;
    transition: all 0.3s;
    position: relative;
}
.ent-hero-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(108,92,231,0.4);
}

/* Features Grid */
.ent-features {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 100px;
}
.ent-features-title {
    text-align: center;
    margin-bottom: 50px;
}
.ent-features-title h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 10px;
}
.ent-features-title p {
    color: var(--ent-text-muted);
    font-size: 1.05rem;
}
.ent-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}
.ent-feature-card {
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    padding: 32px 28px;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.ent-feature-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, var(--ent-accent), var(--ent-blue));
    opacity: 0;
    transition: opacity 0.3s;
}
.ent-feature-card:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}
.ent-feature-card:hover::before { opacity: 1; }
.ent-feature-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin-bottom: 18px;
}
.ent-feature-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.ent-feature-card p {
    color: var(--ent-text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Stats Bar */
.ent-stats {
    max-width: 1000px;
    margin: 0 auto 80px;
    padding: 0 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    overflow: hidden;
}
.ent-stat {
    text-align: center;
    padding: 28px 16px;
    position: relative;
}
.ent-stat + .ent-stat::before {
    content: '';
    position: absolute;
    left: 0; top: 20%;
    width: 1px; height: 60%;
    background: var(--ent-border);
}
.ent-stat-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 4px;
}
.ent-stat-label {
    color: var(--ent-text-muted);
    font-size: 0.85rem;
}

/* ROI Calculator */
.ent-roi {
    max-width: 800px;
    margin: 0 auto 100px;
    padding: 0 20px;
}
.ent-roi-card {
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    padding: 40px;
}
.ent-roi-title {
    text-align: center;
    margin-bottom: 32px;
}
.ent-roi-title h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.ent-roi-title p {
    color: var(--ent-text-muted);
    font-size: 0.95rem;
}
.ent-roi-inputs {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}
.ent-roi-field label {
    display: block;
    color: var(--ent-text-muted);
    font-size: 0.85rem;
    margin-bottom: 8px;
    font-weight: 500;
}
.ent-roi-field input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--ent-border);
    background: var(--ent-surface-2);
    color: var(--ent-text);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}
.ent-roi-field input:focus {
    border-color: var(--ent-accent);
}
.ent-roi-result {
    background: var(--ent-surface-2);
    border: 1px solid var(--ent-border);
    border-radius: 12px;
    padding: 28px;
    text-align: center;
    display: none;
}
.ent-roi-result.visible { display: block; animation: entFadeIn 0.3s ease; }
@keyframes entFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.ent-roi-savings {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--ent-green), var(--ent-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
}
.ent-roi-label {
    color: var(--ent-text-muted);
    font-size: 0.9rem;
}
.ent-roi-breakdown {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    margin-top: 20px;
}
.ent-roi-item {
    padding: 12px;
    background: var(--ent-surface);
    border-radius: 8px;
}
.ent-roi-item-value {
    font-weight: 700;
    color: var(--ent-text);
    font-size: 1.1rem;
}
.ent-roi-item-label {
    color: var(--ent-text-muted);
    font-size: 0.8rem;
    margin-top: 2px;
}
.ent-roi-calculate {
    display: block;
    width: 100%;
    padding: 14px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ent-accent), var(--ent-blue));
    color: #fff;
    border: none;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 20px;
}
.ent-roi-calculate:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(108,92,231,0.4);
}

/* Use Cases */
.ent-usecases {
    max-width: 1100px;
    margin: 0 auto 100px;
    padding: 0 20px;
}
.ent-usecases-title {
    text-align: center;
    margin-bottom: 50px;
}
.ent-usecases-title h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.ent-usecases-title p {
    color: var(--ent-text-muted);
    font-size: 1rem;
}
.ent-usecases-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}
.ent-usecase {
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    padding: 28px;
    transition: all 0.3s;
}
.ent-usecase:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-3px);
}
.ent-usecase-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 16px;
}
.ent-usecase h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.ent-usecase p {
    color: var(--ent-text-muted);
    font-size: 0.88rem;
    line-height: 1.6;
}
.ent-usecase ul {
    list-style: none;
    padding: 0;
    margin: 12px 0 0;
}
.ent-usecase ul li {
    padding: 4px 0;
    color: var(--ent-text-muted);
    font-size: 0.85rem;
}
.ent-usecase ul li i {
    color: var(--ent-green);
    margin-right: 6px;
    font-size: 0.75rem;
}

/* Contact Form */
.ent-contact {
    max-width: 700px;
    margin: 0 auto 100px;
    padding: 0 20px;
}
.ent-contact-card {
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: var(--ent-radius);
    padding: 48px 40px;
}
.ent-contact-title {
    text-align: center;
    margin-bottom: 32px;
}
.ent-contact-title h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.ent-contact-title p {
    color: var(--ent-text-muted);
    font-size: 0.95rem;
}
.ent-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.ent-form-field {
    display: flex;
    flex-direction: column;
}
.ent-form-field.full { grid-column: 1 / -1; }
.ent-form-field label {
    color: var(--ent-text-muted);
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 6px;
}
.ent-form-field input,
.ent-form-field select,
.ent-form-field textarea {
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--ent-border);
    background: var(--ent-surface-2);
    color: var(--ent-text);
    font-size: 0.95rem;
    font-family: inherit;
    outline: none;
    transition: border-color 0.3s;
}
.ent-form-field input:focus,
.ent-form-field select:focus,
.ent-form-field textarea:focus {
    border-color: var(--ent-accent);
}
.ent-form-field select { cursor: pointer; }
.ent-form-field select option { background: var(--ent-surface-2); color: var(--ent-text); }
.ent-form-field textarea { resize: vertical; min-height: 120px; }
.ent-form-submit {
    display: block;
    width: 100%;
    padding: 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ent-accent), var(--ent-blue));
    color: #fff;
    border: none;
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 8px;
}
.ent-form-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(108,92,231,0.4);
}
.ent-form-success {
    display: none;
    text-align: center;
    padding: 40px 20px;
}
.ent-form-success.visible { display: block; }
.ent-form-success i {
    font-size: 3rem;
    color: var(--ent-green);
    margin-bottom: 16px;
}
.ent-form-success h3 {
    color: #fff;
    font-size: 1.3rem;
    margin-bottom: 8px;
}
.ent-form-success p {
    color: var(--ent-text-muted);
}

/* Trust Badges */
.ent-trust {
    max-width: 900px;
    margin: 0 auto 100px;
    padding: 0 20px;
    text-align: center;
}
.ent-trust h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--ent-text-muted);
    margin-bottom: 30px;
}
.ent-trust-grid {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}
.ent-trust-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px 28px;
    background: var(--ent-surface);
    border: 1px solid var(--ent-border);
    border-radius: 12px;
    min-width: 130px;
}
.ent-trust-badge i {
    font-size: 1.6rem;
    color: var(--ent-accent-light);
}
.ent-trust-badge span {
    color: var(--ent-text-muted);
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 768px) {
    .ent-stats { grid-template-columns: repeat(2, 1fr); }
    .ent-stat + .ent-stat::before { display: none; }
    .ent-form-grid { grid-template-columns: 1fr; }
    .ent-contact-card { padding: 32px 24px; }
    .ent-roi-card { padding: 28px 20px; }
    .ent-roi-savings { font-size: 2rem; }
}
</style>

<!-- Hero -->
<section class="ent-hero">
    <div class="ent-hero-badge"><i class="fas fa-building"></i> <?php echo PT('hero_badge'); ?></div>
    <h1><?php echo PT('hero_title'); ?><span><?php echo PT('hero_title_accent'); ?></span></h1>
    <p><?php echo PT('hero_desc'); ?></p>
    <a href="#contact" class="ent-hero-cta">
        <i class="fas fa-phone-alt"></i> <?php echo PT('hero_cta'); ?>
    </a>
</section>

<!-- Stats Bar -->
<div class="ent-stats">
    <div class="ent-stat">
        <div class="ent-stat-value" style="color:var(--ent-accent-light)">13,000+</div>
        <div class="ent-stat-label"><?php echo PT('stat_tools'); ?></div>
    </div>
    <div class="ent-stat">
        <div class="ent-stat-value" style="color:var(--ent-green)"><?php echo PT('stat_agents_val'); ?></div>
        <div class="ent-stat-label"><?php echo PT('stat_agents'); ?></div>
    </div>
    <div class="ent-stat">
        <div class="ent-stat-value" style="color:var(--ent-cyan)">99.9%</div>
        <div class="ent-stat-label"><?php echo PT('stat_sla'); ?></div>
    </div>
    <div class="ent-stat">
        <div class="ent-stat-value" style="color:var(--ent-orange)">24/7</div>
        <div class="ent-stat-label"><?php echo PT('stat_support'); ?></div>
    </div>
</div>

<!-- Enterprise Features -->
<section class="ent-features">
    <div class="ent-features-title">
        <h2><?php echo PT('feat_title'); ?></h2>
        <p><?php echo PT('feat_sub'); ?></p>
    </div>
    <div class="ent-features-grid">
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(108,92,231,0.15);color:var(--ent-accent-light);">
                <i class="fas fa-users-cog"></i>
            </div>
            <h3><?php echo PT('feat1_title'); ?></h3>
            <p><?php echo PT('feat1_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(9,132,227,0.15);color:var(--ent-blue);">
                <i class="fas fa-user-tie"></i>
            </div>
            <h3><?php echo PT('feat2_title'); ?></h3>
            <p><?php echo PT('feat2_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(0,184,148,0.15);color:var(--ent-green);">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3><?php echo PT('feat3_title'); ?></h3>
            <p><?php echo PT('feat3_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(253,203,110,0.15);color:var(--ent-orange);">
                <i class="fas fa-key"></i>
            </div>
            <h3><?php echo PT('feat4_title'); ?></h3>
            <p><?php echo PT('feat4_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(253,121,168,0.15);color:var(--ent-pink);">
                <i class="fas fa-puzzle-piece"></i>
            </div>
            <h3><?php echo PT('feat5_title'); ?></h3>
            <p><?php echo PT('feat5_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(225,112,85,0.15);color:var(--ent-fire);">
                <i class="fas fa-headset"></i>
            </div>
            <h3><?php echo PT('feat6_title'); ?></h3>
            <p><?php echo PT('feat6_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(0,206,201,0.15);color:var(--ent-cyan);">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3><?php echo PT('feat7_title'); ?></h3>
            <p><?php echo PT('feat7_desc'); ?></p>
        </div>
        <div class="ent-feature-card">
            <div class="ent-feature-icon" style="background:rgba(162,155,254,0.15);color:var(--ent-accent-light);">
                <i class="fas fa-tags"></i>
            </div>
            <h3><?php echo PT('feat8_title'); ?></h3>
            <p><?php echo PT('feat8_desc'); ?></p>
        </div>
    </div>
</section>

<!-- ROI Calculator -->
<section class="ent-roi" id="roi">
    <div class="ent-roi-card">
        <div class="ent-roi-title">
            <h2><i class="fas fa-calculator"></i> <?php echo PT('roi_title'); ?></h2>
            <p><?php echo PT('roi_sub'); ?></p>
        </div>
        <div class="ent-roi-inputs">
            <div class="ent-roi-field">
                <label for="roiAgents"><i class="fas fa-user"></i> <?php echo PT('roi_agents'); ?></label>
                <input type="number" id="roiAgents" value="10" min="1" max="10000" placeholder="e.g. 10">
            </div>
            <div class="ent-roi-field">
                <label for="roiCalls"><i class="fas fa-phone"></i> <?php echo PT('roi_calls'); ?></label>
                <input type="number" id="roiCalls" value="30" min="1" max="500" placeholder="e.g. 30">
            </div>
            <div class="ent-roi-field">
                <label for="roiDuration"><i class="fas fa-clock"></i> <?php echo PT('roi_duration'); ?></label>
                <input type="number" id="roiDuration" value="8" min="1" max="120" placeholder="e.g. 8">
            </div>
        </div>
        <button class="ent-roi-calculate" onclick="calculateROI()">
            <i class="fas fa-chart-bar"></i> <?php echo PT('roi_btn'); ?>
        </button>
        <div class="ent-roi-result" id="roiResult">
            <div class="ent-roi-savings" id="roiSavings">$0</div>
            <div class="ent-roi-label"><?php echo PT('roi_est_savings'); ?></div>
            <div class="ent-roi-breakdown">
                <div class="ent-roi-item">
                    <div class="ent-roi-item-value" id="roiCurrentCost">$0</div>
                    <div class="ent-roi-item-label"><?php echo PT('roi_current'); ?></div>
                </div>
                <div class="ent-roi-item">
                    <div class="ent-roi-item-value" id="roiAlfredCost">$0</div>
                    <div class="ent-roi-item-label"><?php echo PT('roi_alfred'); ?></div>
                </div>
                <div class="ent-roi-item">
                    <div class="ent-roi-item-value" id="roiAutomated">0%</div>
                    <div class="ent-roi-item-label"><?php echo PT('roi_automated'); ?></div>
                </div>
                <div class="ent-roi-item">
                    <div class="ent-roi-item-value" id="roiTimeSaved">0 hrs</div>
                    <div class="ent-roi-item-label"><?php echo PT('roi_time'); ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases -->
<section class="ent-usecases">
    <div class="ent-usecases-title">
        <h2><?php echo PT('uc_title'); ?></h2>
        <p><?php echo PT('uc_sub'); ?></p>
    </div>
    <div class="ent-usecases-grid">
        <div class="ent-usecase">
            <div class="ent-usecase-icon" style="background:rgba(108,92,231,0.15);color:var(--ent-accent-light);">
                <i class="fas fa-headset"></i>
            </div>
            <h3><?php echo PT('uc1_title'); ?></h3>
            <p><?php echo PT('uc1_desc'); ?></p>
            <ul>
                <li><i class="fas fa-check"></i> <?php echo PT('uc1_li1'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc1_li2'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc1_li3'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc1_li4'); ?></li>
            </ul>
        </div>
        <div class="ent-usecase">
            <div class="ent-usecase-icon" style="background:rgba(225,112,85,0.15);color:var(--ent-fire);">
                <i class="fas fa-gavel"></i>
            </div>
            <h3><?php echo PT('uc2_title'); ?></h3>
            <p><?php echo PT('uc2_desc'); ?></p>
            <ul>
                <li><i class="fas fa-check"></i> <?php echo PT('uc2_li1'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc2_li2'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc2_li3'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc2_li4'); ?></li>
            </ul>
        </div>
        <div class="ent-usecase">
            <div class="ent-usecase-icon" style="background:rgba(0,184,148,0.15);color:var(--ent-green);">
                <i class="fas fa-heartbeat"></i>
            </div>
            <h3><?php echo PT('uc3_title'); ?></h3>
            <p><?php echo PT('uc3_desc'); ?></p>
            <ul>
                <li><i class="fas fa-check"></i> <?php echo PT('uc3_li1'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc3_li2'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc3_li3'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc3_li4'); ?></li>
            </ul>
        </div>
        <div class="ent-usecase">
            <div class="ent-usecase-icon" style="background:rgba(9,132,227,0.15);color:var(--ent-blue);">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h3><?php echo PT('uc4_title'); ?></h3>
            <p><?php echo PT('uc4_desc'); ?></p>
            <ul>
                <li><i class="fas fa-check"></i> <?php echo PT('uc4_li1'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc4_li2'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc4_li3'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc4_li4'); ?></li>
            </ul>
        </div>
        <div class="ent-usecase">
            <div class="ent-usecase-icon" style="background:rgba(253,203,110,0.15);color:var(--ent-orange);">
                <i class="fas fa-server"></i>
            </div>
            <h3><?php echo PT('uc5_title'); ?></h3>
            <p><?php echo PT('uc5_desc'); ?></p>
            <ul>
                <li><i class="fas fa-check"></i> <?php echo PT('uc5_li1'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc5_li2'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc5_li3'); ?></li>
                <li><i class="fas fa-check"></i> <?php echo PT('uc5_li4'); ?></li>
            </ul>
        </div>
    </div>
</section>

<!-- Contact Form -->
<section class="ent-contact" id="contact">
    <div class="ent-contact-card">
        <div class="ent-contact-title">
            <h2><i class="fas fa-envelope"></i> <?php echo PT('contact_title'); ?></h2>
            <p><?php echo PT('contact_sub'); ?></p>
        </div>
        <form id="entContactForm" onsubmit="submitEntForm(event)">
            <div class="ent-form-grid">
                <div class="ent-form-field">
                    <label for="entCompany"><?php echo PT('lbl_company'); ?></label>
                    <input type="text" id="entCompany" name="company" required placeholder="<?php echo PT('ph_company'); ?>">
                </div>
                <div class="ent-form-field">
                    <label for="entEmail"><?php echo PT('lbl_email'); ?></label>
                    <input type="email" id="entEmail" name="email" required placeholder="<?php echo PT('ph_email'); ?>">
                </div>
                <div class="ent-form-field">
                    <label for="entPhone"><?php echo PT('lbl_phone'); ?></label>
                    <input type="tel" id="entPhone" name="phone" placeholder="<?php echo PT('ph_phone'); ?>">
                </div>
                <div class="ent-form-field">
                    <label for="entSize"><?php echo PT('lbl_size'); ?></label>
                    <select id="entSize" name="company_size" required>
                        <option value="" disabled selected><?php echo PT('ph_size'); ?></option>
                        <option value="1-10"><?php echo PT('size_1'); ?></option>
                        <option value="11-50"><?php echo PT('size_2'); ?></option>
                        <option value="51-200"><?php echo PT('size_3'); ?></option>
                        <option value="201-1000"><?php echo PT('size_4'); ?></option>
                        <option value="1001-5000"><?php echo PT('size_5'); ?></option>
                        <option value="5000+"><?php echo PT('size_6'); ?></option>
                    </select>
                </div>
                <div class="ent-form-field full">
                    <label for="entMessage"><?php echo PT('lbl_message'); ?></label>
                    <textarea id="entMessage" name="message" required placeholder="<?php echo PT('ph_message'); ?>"></textarea>
                </div>
            </div>
            <button type="submit" class="ent-form-submit">
                <i class="fas fa-paper-plane"></i> <?php echo PT('btn_send'); ?>
            </button>
        </form>
        <div class="ent-form-success" id="entFormSuccess">
            <i class="fas fa-check-circle"></i>
            <h3><?php echo PT('success_title'); ?></h3>
            <p><?php echo PT('success_text'); ?></p>
        </div>
    </div>
</section>

<!-- Trust Badges -->
<section class="ent-trust">
    <h2><?php echo PT('trust_title'); ?></h2>
    <div class="ent-trust-grid">
        <div class="ent-trust-badge">
            <i class="fas fa-lock"></i>
            <span><?php echo PT('trust_soc2'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-hospital"></i>
            <span><?php echo PT('trust_hipaa'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-shield-alt"></i>
            <span><?php echo PT('trust_ddos'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-certificate"></i>
            <span><?php echo PT('trust_ssl'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-atom"></i>
            <span><?php echo PT('trust_pq'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-globe-americas"></i>
            <span><?php echo PT('trust_bilingual'); ?></span>
        </div>
        <div class="ent-trust-badge">
            <i class="fas fa-clock"></i>
            <span><?php echo PT('trust_sla'); ?></span>
        </div>
    </div>
</section>

<script src="/assets/js/enterprise-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
