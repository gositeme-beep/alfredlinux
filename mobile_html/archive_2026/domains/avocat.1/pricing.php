<?php
require_once __DIR__ . '/includes/lang.php';

// Bilingual content for Pricing page (Quebec law requirement)
$page_translations = [
    'en' => [
        'page_title' => 'Pricing — Alfred AI Plans & Pricing | GoSiteMe',
        'page_desc' => 'Simple, transparent pricing for Alfred AI. From $3.99/mo for 1,220+ AI tools, voice commands, fleet management. 14-day free trial, cancel anytime.',
        'og_title' => 'Alfred AI Pricing — Plans Starting at $3.99/mo',
        'og_desc' => 'Get access to 1,220+ AI tools starting at $3.99/mo. 14-day free trial, no credit card required. Compare Starter, Professional & Enterprise plans.',
        'hero_title' => 'Simple, ',
        'hero_title_accent' => 'Transparent Pricing',
        'hero_sub' => 'Start free. Upgrade when you\'re ready. Cancel anytime. All plans include access to 1,220+ AI tools.',
        'trust_trial' => '14-Day Free Trial',
        'trust_cancel' => 'Cancel Anytime',
        'trust_no_cc' => 'No Credit Card Required',
        'toggle_monthly' => 'Monthly',
        'toggle_annual' => 'Annual',
        'toggle_save' => 'Save 20%',
        'plan_starter' => 'Starter',
        'plan_starter_desc' => 'Perfect for individuals getting started with AI',
        'plan_pro' => 'Professional',
        'plan_pro_desc' => 'For power users and growing teams',
        'plan_pro_badge' => 'Most Popular',
        'plan_ent' => 'Enterprise',
        'plan_ent_desc' => 'Full power for organizations &amp; teams',
        'feat_basic_ai' => 'Basic AI assistant',
        'feat_50_calls' => '50 tool calls/day',
        'feat_1_fleet' => '1 fleet (3 agents max)',
        'feat_875_tools' => 'All 1,220+ tools accessible',
        'feat_community' => 'Community support',
        'feat_email' => 'Email support',
        'feat_everything_starter' => 'Everything in Starter',
        'feat_unlimited_calls' => 'Unlimited tool calls',
        'feat_5_fleets' => '5 fleets (10 agents each)',
        'feat_voice' => 'Voice commands &amp; phone access',
        'feat_priority' => 'Priority support',
        'feat_api' => 'API access',
        'feat_custom_agent' => 'Custom agent creation',
        'feat_fleet_dash' => 'Fleet management dashboard',
        'feat_everything_pro' => 'Everything in Professional',
        'feat_unlimited_fleets' => 'Unlimited fleets &amp; agents',
        'feat_account_mgr' => 'Dedicated account manager',
        'feat_custom_tools' => 'Custom tool development',
        'feat_sla' => 'SLA guarantee (99.9%)',
        'feat_sso' => 'SSO &amp; team management',
        'feat_whitelabel' => 'White-label option',
        'feat_phone' => 'Phone support',
        'btn_get_started' => 'Get Started',
        'btn_start_trial' => 'Start Free Trial',
        'btn_contact_sales' => 'Contact Sales',
        'all_plans_title' => 'All Plans Include',
        'all_plans_sub' => 'Every Alfred plan comes loaded with these essentials.',
        'inc_tools_title' => '1,220+ AI Tools',
        'inc_tools_desc' => 'Access the full library of AI-powered tools',
        'inc_cat_title' => '29 Categories',
        'inc_cat_desc' => 'Legal, medical, finance, marketing &amp; more',
        'inc_bilingual_title' => 'Bilingual EN/FR',
        'inc_bilingual_desc' => 'Full support in English and French',
        'inc_ssl_title' => 'SSL + Post-Quantum Encryption',
        'inc_ssl_desc' => 'AES-256, TLS 1.3, and Kyber-1024 quantum-resistant encryption',
        'inc_trial_title' => '14-Day Free Trial',
        'inc_trial_desc' => 'Try any plan risk-free for two weeks',
        'inc_community_title' => 'Community Access',
        'inc_community_desc' => 'Join our growing community of AI users',
        'compare_title' => 'Compare All Features',
        'compare_sub' => 'See exactly what you get with each plan.',
        'th_feature' => 'Feature',
        'td_price' => 'Price (monthly)',
        'td_tools_access' => 'AI Tools Access',
        'td_tool_calls' => 'Tool Calls / Day',
        'td_fleets' => 'Fleets',
        'td_voice_cmd' => 'Voice Commands',
        'td_phone_access' => 'Phone Access',
        'td_api_access' => 'API Access',
        'td_custom_agent' => 'Custom Agent Creation',
        'td_fleet_dash' => 'Fleet Management Dashboard',
        'td_acct_mgr' => 'Dedicated Account Manager',
        'td_custom_tool' => 'Custom Tool Development',
        'td_sla' => 'SLA Guarantee (99.9%)',
        'td_sso' => 'SSO &amp; Team Management',
        'td_whitelabel' => 'White-Label Option',
        'td_support' => 'Support',
        'td_bilingual' => 'Bilingual (EN/FR)',
        'td_ssl' => 'SSL Encryption',
        'td_trial' => '14-Day Free Trial',
        'td_unlimited' => 'Unlimited',
        'td_email_community' => 'Email &amp; Community',
        'td_priority' => 'Priority',
        'td_phone_dedicated' => 'Phone &amp; Dedicated',
        'faq_title' => 'Frequently Asked Questions',
        'faq_sub' => 'Everything you need to know about Alfred AI pricing.',
        'faq_q1' => 'Is there really a free trial?',
        'faq_a1' => 'Yes! Every plan includes a 14-day free trial. No credit card is required to start. You get full access to all features during the trial period.',
        'faq_q2' => 'Can I switch plans later?',
        'faq_a2' => 'Absolutely. You can upgrade or downgrade your plan at any time from your dashboard. Changes take effect immediately, and we\'ll prorate any billing differences.',
        'faq_q3' => 'What payment methods do you accept?',
        'faq_a3' => 'We accept all major credit cards (Visa, Mastercard, American Express), debit cards, and select digital wallets through our secure Stripe payment processing.',
        'faq_q4' => 'How does annual billing work?',
        'faq_a4' => 'Annual billing saves you 20% compared to monthly pricing. You pay once per year at the discounted rate. For example, Professional is $95.90/year instead of $119.88/year.',
        'faq_q5' => 'What are tool calls?',
        'faq_a5' => 'A tool call is when Alfred executes a specific AI tool on your behalf — like drafting a contract, analyzing data, generating an image, or researching a topic. Starter plans include 50 calls/day, while Professional and Enterprise have unlimited calls.',
        'faq_q6' => 'What is a fleet?',
        'faq_a6' => 'A fleet is a group of AI agents that work together on complex tasks. You can assign different specialties to each agent and have them collaborate. Starter includes 1 fleet with 3 agents, Professional includes 5 fleets with 10 agents each, and Enterprise has unlimited fleets and agents.',
        'faq_q7' => 'Can I cancel anytime?',
        'faq_a7' => 'Yes. There are no long-term contracts or cancellation fees. You can cancel your subscription at any time from your dashboard. Your access continues until the end of your current billing period.',
        'faq_q8' => 'Do you offer refunds?',
        'faq_a8' => 'We offer a 14-day free trial so you can test before committing. If you\'re on an annual plan and need a refund, contact our support team within 30 days of purchase and we\'ll work with you.',
        'faq_q9' => 'Is Alfred available in French?',
        'faq_a9' => 'Yes! As a Montreal-based company, we\'re fully bilingual. Alfred works in both English and French across all plans — including voice commands, chat, and all tool outputs.',
        'faq_q10' => 'What\'s included in the Enterprise SLA?',
        'faq_a10' => 'Enterprise plans include a 99.9% uptime SLA guarantee, a dedicated account manager, priority processing queues, and phone support. Contact our sales team for custom SLA requirements.',
        'ent_cta_title' => 'Need a Custom Enterprise Solution?',
        'ent_cta_text' => 'Get a tailored plan with white-label options, custom tool development, SSO, and a dedicated account manager.',
        'loading' => 'Loading...',
    ],
    'fr' => [
        'page_title' => 'Tarifs — Plans et prix Alfred IA | GoSiteMe',
        'page_desc' => 'Tarification simple et transparente pour Alfred IA. À partir de 3,99 $ USD/mois pour 1,220+ outils IA, commandes vocales, gestion de flotte. Essai gratuit de 14 jours.',
        'og_title' => 'Tarifs Alfred IA — Plans à partir de 3,99 $ USD/mois',
        'og_desc' => 'Accédez à 1,220+ outils IA à partir de 3,99 $ USD/mois. Essai gratuit de 14 jours, aucune carte de crédit requise. Comparez les plans Débutant, Professionnel et Entreprise.',
        'hero_title' => 'Tarification ',
        'hero_title_accent' => 'simple et transparente',
        'hero_sub' => 'Commencez gratuitement. Passez au niveau supérieur quand vous êtes prêt. Annulez à tout moment. Tous les plans incluent l\'accès à 1,220+ outils IA.',
        'trust_trial' => 'Essai gratuit de 14 jours',
        'trust_cancel' => 'Annulation à tout moment',
        'trust_no_cc' => 'Aucune carte de crédit requise',
        'toggle_monthly' => 'Mensuel',
        'toggle_annual' => 'Annuel',
        'toggle_save' => 'Économisez 20 %',
        'plan_starter' => 'Débutant',
        'plan_starter_desc' => 'Idéal pour commencer avec l\'IA',
        'plan_pro' => 'Professionnel',
        'plan_pro_desc' => 'Pour les utilisateurs avancés et les équipes en croissance',
        'plan_pro_badge' => 'Le plus populaire',
        'plan_ent' => 'Entreprise',
        'plan_ent_desc' => 'Toute la puissance pour les organisations et les équipes',
        'feat_basic_ai' => 'Assistant IA de base',
        'feat_50_calls' => '50 appels d\'outils/jour',
        'feat_1_fleet' => '1 flotte (3 agents max)',
        'feat_875_tools' => 'Accès aux 1,220+ outils',
        'feat_community' => 'Soutien communautaire',
        'feat_email' => 'Soutien par courriel',
        'feat_everything_starter' => 'Tout ce qui est dans Débutant',
        'feat_unlimited_calls' => 'Appels d\'outils illimités',
        'feat_5_fleets' => '5 flottes (10 agents chacune)',
        'feat_voice' => 'Commandes vocales et accès téléphonique',
        'feat_priority' => 'Soutien prioritaire',
        'feat_api' => 'Accès API',
        'feat_custom_agent' => 'Création d\'agents personnalisés',
        'feat_fleet_dash' => 'Tableau de bord de gestion de flotte',
        'feat_everything_pro' => 'Tout ce qui est dans Professionnel',
        'feat_unlimited_fleets' => 'Flottes et agents illimités',
        'feat_account_mgr' => 'Gestionnaire de compte dédié',
        'feat_custom_tools' => 'Développement d\'outils sur mesure',
        'feat_sla' => 'Garantie SLA (99,9 %)',
        'feat_sso' => 'SSO et gestion d\'équipe',
        'feat_whitelabel' => 'Option marque blanche',
        'feat_phone' => 'Soutien téléphonique',
        'btn_get_started' => 'Commencer',
        'btn_start_trial' => 'Commencer l\'essai gratuit',
        'btn_contact_sales' => 'Contacter les ventes',
        'all_plans_title' => 'Tous les plans incluent',
        'all_plans_sub' => 'Chaque plan Alfred est chargé de ces essentiels.',
        'inc_tools_title' => '1,220+ outils IA',
        'inc_tools_desc' => 'Accédez à la bibliothèque complète d\'outils propulsés par l\'IA',
        'inc_cat_title' => '29 catégories',
        'inc_cat_desc' => 'Juridique, médical, finance, marketing et plus',
        'inc_bilingual_title' => 'Bilingue EN/FR',
        'inc_bilingual_desc' => 'Support complet en anglais et en français',
        'inc_ssl_title' => 'SSL + Chiffrement post-quantique',
        'inc_ssl_desc' => 'AES-256, TLS 1.3 et chiffrement Kyber-1024 résistant au quantique',
        'inc_trial_title' => 'Essai gratuit de 14 jours',
        'inc_trial_desc' => 'Essayez n\'importe quel plan sans risque pendant deux semaines',
        'inc_community_title' => 'Accès communautaire',
        'inc_community_desc' => 'Rejoignez notre communauté grandissante d\'utilisateurs IA',
        'compare_title' => 'Comparer toutes les fonctionnalités',
        'compare_sub' => 'Voyez exactement ce que vous obtenez avec chaque plan.',
        'th_feature' => 'Fonctionnalité',
        'td_price' => 'Prix (mensuel)',
        'td_tools_access' => 'Accès aux outils IA',
        'td_tool_calls' => 'Appels d\'outils / jour',
        'td_fleets' => 'Flottes',
        'td_voice_cmd' => 'Commandes vocales',
        'td_phone_access' => 'Accès téléphonique',
        'td_api_access' => 'Accès API',
        'td_custom_agent' => 'Création d\'agents personnalisés',
        'td_fleet_dash' => 'Tableau de bord de gestion de flotte',
        'td_acct_mgr' => 'Gestionnaire de compte dédié',
        'td_custom_tool' => 'Développement d\'outils sur mesure',
        'td_sla' => 'Garantie SLA (99,9 %)',
        'td_sso' => 'SSO et gestion d\'équipe',
        'td_whitelabel' => 'Option marque blanche',
        'td_support' => 'Soutien',
        'td_bilingual' => 'Bilingue (EN/FR)',
        'td_ssl' => 'Chiffrement SSL',
        'td_trial' => 'Essai gratuit de 14 jours',
        'td_unlimited' => 'Illimité',
        'td_email_community' => 'Courriel et communauté',
        'td_priority' => 'Prioritaire',
        'td_phone_dedicated' => 'Téléphonique et dédié',
        'faq_title' => 'Questions fréquemment posées',
        'faq_sub' => 'Tout ce que vous devez savoir sur les tarifs d\'Alfred IA.',
        'faq_q1' => 'Y a-t-il vraiment un essai gratuit ?',
        'faq_a1' => 'Oui ! Chaque plan inclut un essai gratuit de 14 jours. Aucune carte de crédit n\'est requise pour commencer. Vous avez un accès complet à toutes les fonctionnalités pendant la période d\'essai.',
        'faq_q2' => 'Puis-je changer de plan plus tard ?',
        'faq_a2' => 'Absolument. Vous pouvez passer à un plan supérieur ou inférieur à tout moment depuis votre tableau de bord. Les changements prennent effet immédiatement et nous ajusterons la facturation au prorata.',
        'faq_q3' => 'Quels modes de paiement acceptez-vous ?',
        'faq_a3' => 'Nous acceptons toutes les principales cartes de crédit (Visa, Mastercard, American Express), les cartes de débit et certains portefeuilles numériques via notre traitement sécurisé Stripe.',
        'faq_q4' => 'Comment fonctionne la facturation annuelle ?',
        'faq_a4' => 'La facturation annuelle vous fait économiser 20 % par rapport au tarif mensuel. Vous payez une fois par an au tarif réduit. Par exemple, le plan Professionnel est à 95,90 $ USD/an au lieu de 119,88 $ USD/an.',
        'faq_q5' => 'Qu\'est-ce qu\'un appel d\'outil ?',
        'faq_a5' => 'Un appel d\'outil est quand Alfred exécute un outil IA spécifique en votre nom — comme rédiger un contrat, analyser des données, générer une image ou rechercher un sujet. Les plans Débutant incluent 50 appels/jour, tandis que Professionnel et Entreprise ont des appels illimités.',
        'faq_q6' => 'Qu\'est-ce qu\'une flotte ?',
        'faq_a6' => 'Une flotte est un groupe d\'agents IA qui travaillent ensemble sur des tâches complexes. Vous pouvez assigner différentes spécialités à chaque agent et les faire collaborer. Débutant inclut 1 flotte avec 3 agents, Professionnel inclut 5 flottes avec 10 agents chacune, et Entreprise a des flottes et agents illimités.',
        'faq_q7' => 'Puis-je annuler à tout moment ?',
        'faq_a7' => 'Oui. Il n\'y a pas de contrat à long terme ni de frais d\'annulation. Vous pouvez annuler votre abonnement à tout moment depuis votre tableau de bord. Votre accès continue jusqu\'à la fin de votre période de facturation en cours.',
        'faq_q8' => 'Offrez-vous des remboursements ?',
        'faq_a8' => 'Nous offrons un essai gratuit de 14 jours pour que vous puissiez tester avant de vous engager. Si vous êtes sur un plan annuel et avez besoin d\'un remboursement, contactez notre équipe de soutien dans les 30 jours suivant l\'achat.',
        'faq_q9' => 'Alfred est-il disponible en français ?',
        'faq_a9' => 'Oui ! En tant qu\'entreprise montréalaise, nous sommes entièrement bilingues. Alfred fonctionne en anglais et en français sur tous les plans — y compris les commandes vocales, le clavardage et tous les résultats d\'outils.',
        'faq_q10' => 'Que comprend le SLA Entreprise ?',
        'faq_a10' => 'Les plans Entreprise incluent une garantie SLA de 99,9 % de disponibilité, un gestionnaire de compte dédié, des files d\'attente de traitement prioritaire et un soutien téléphonique. Contactez notre équipe de ventes pour des exigences SLA personnalisées.',
        'ent_cta_title' => 'Besoin d\'une solution Entreprise sur mesure ?',
        'ent_cta_text' => 'Obtenez un plan personnalisé avec options marque blanche, développement d\'outils sur mesure, SSO et un gestionnaire de compte dédié.',
        'loading' => 'Chargement...',
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
$page_canonical = 'https://gositeme.com/pricing.php';
$page_og_title = PT('og_title');
$page_og_description = PT('og_desc');
include __DIR__ . '/includes/site-header.inc.php';
?>

<!-- Schema.org Product markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Alfred AI Pricing",
  "description": "Pricing plans for Alfred AI by GoSiteMe — 6 tiers from Free to Enterprise Custom",
  "offers": [
    {
      "@type": "Offer",
      "name": "Alfred AI Free",
      "price": "0",
      "priceCurrency": "USD",
      "description": "10 tools, 5 voice min/day, 1 agent, Web chat only, 100 API calls/day.",
      "url": "https://gositeme.com/pricing.php"
    },
    {
      "@type": "Offer",
      "name": "Alfred AI Starter",
      "price": "3.99",
      "priceCurrency": "USD",
      "priceSpecification": { "@type": "UnitPriceSpecification", "price": "3.99", "priceCurrency": "USD", "billingDuration": "P1M" },
      "description": "100 tools, 60 voice min/day, 3 agents, Web + voice, Email support, 10K API calls/day.",
      "url": "https://gositeme.com/pricing.php"
    },
    {
      "@type": "Offer",
      "name": "Alfred AI Professional",
      "price": "9.99",
      "priceCurrency": "USD",
      "priceSpecification": { "@type": "UnitPriceSpecification", "price": "9.99", "priceCurrency": "USD", "billingDuration": "P1M" },
      "description": "ALL 1,220+ tools, Unlimited voice, 5 agents, All channels, Priority support, 100K API/day.",
      "url": "https://gositeme.com/pricing.php"
    },
    {
      "@type": "Offer",
      "name": "Alfred AI Enterprise",
      "price": "24.99",
      "priceCurrency": "USD",
      "priceSpecification": { "@type": "UnitPriceSpecification", "price": "24.99", "priceCurrency": "USD", "billingDuration": "P1M" },
      "description": "ALL tools + priority, Unlimited voice, 20 agents, 24/7 support, 500K API/day, Org accounts.",
      "url": "https://gositeme.com/pricing.php"
    },
    {
      "@type": "Offer",
      "name": "Alfred AI Enterprise Plus",
      "price": "99.00",
      "priceCurrency": "USD",
      "priceSpecification": { "@type": "UnitPriceSpecification", "price": "99.00", "priceCurrency": "USD", "billingDuration": "P1M" },
      "description": "SSO, Audit logging, Dedicated CSM, Unlimited API, 50-person rooms, Voice cloning.",
      "url": "https://gositeme.com/pricing.php"
    },
    {
      "@type": "Offer",
      "name": "Alfred AI Enterprise Custom",
      "price": "299.00",
      "priceCurrency": "USD",
      "priceSpecification": { "@type": "UnitPriceSpecification", "price": "299.00", "priceCurrency": "USD", "billingDuration": "P1M" },
      "description": "White-label deploy, Custom SLA, Dedicated infrastructure, Unlimited everything.",
      "url": "https://gositeme.com/pricing.php"
    }
  ]
}
</script>

<style>
/* ===== Pricing Page Styles ===== */
:root {
    --pr-bg: #0a0a14;
    --pr-surface: #12121e;
    --pr-surface-2: #1a1a2e;
    --pr-border: rgba(255,255,255,0.08);
    --pr-accent: #6c5ce7;
    --pr-accent-light: #a29bfe;
    --pr-blue: #0984e3;
    --pr-green: #00b894;
    --pr-orange: #fdcb6e;
    --pr-text: #e8e8f0;
    --pr-text-muted: #8a8a9a;
    --pr-radius: 16px;
    --pr-gradient: linear-gradient(135deg, #6c5ce7 0%, #0984e3 50%, #00b894 100%);
}

/* Hero */
.pr-hero {
    padding: 140px 20px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--pr-bg) 70%);
}
.pr-hero::before {
    content: '';
    position: absolute;
    top: -40%; left: -20%;
    width: 140%; height: 180%;
    background:
        radial-gradient(circle at 25% 30%, rgba(108,92,231,0.12) 0%, transparent 50%),
        radial-gradient(circle at 75% 60%, rgba(9,132,227,0.08) 0%, transparent 50%);
    pointer-events: none;
}
.pr-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 16px;
    position: relative; z-index: 2;
}
.pr-hero h1 span {
    background: var(--pr-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.pr-hero p {
    font-size: 1.2rem;
    color: var(--pr-text-muted);
    max-width: 600px;
    margin: 0 auto;
    position: relative; z-index: 2;
}

/* Trust badges */
.pr-trust-badges {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-top: 32px;
    position: relative; z-index: 2;
}
.pr-trust-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--pr-green);
    font-size: 0.95rem;
    font-weight: 600;
}
.pr-trust-badge i { font-size: 1rem; }

/* Toggle */
.pr-toggle-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 40px 20px 0;
    background: var(--pr-bg);
}
.pr-toggle-label {
    font-size: 1rem;
    color: var(--pr-text-muted);
    font-weight: 600;
    cursor: pointer;
    transition: color 0.3s;
}
.pr-toggle-label.active { color: #fff; }
.pr-toggle-switch {
    width: 56px;
    height: 30px;
    background: var(--pr-surface-2);
    border: 2px solid var(--pr-border);
    border-radius: 30px;
    cursor: pointer;
    position: relative;
    transition: background 0.3s;
}
.pr-toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px; left: 3px;
    width: 22px; height: 22px;
    border-radius: 50%;
    background: var(--pr-accent);
    transition: transform 0.3s;
    box-shadow: 0 2px 8px rgba(108,92,231,0.4);
}
.pr-toggle-switch.annual::after { transform: translateX(26px); }
.pr-save-badge {
    background: rgba(0,184,148,0.15);
    color: var(--pr-green);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
}

/* Pricing Grid — 6 cards: 3 per row */
.pr-grid-section {
    padding: 48px 20px 80px;
    background: var(--pr-bg);
}
.pr-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    max-width: 1200px;
    margin: 0 auto;
    align-items: stretch;
}
.pr-card {
    background: var(--pr-surface);
    border: 1px solid var(--pr-border);
    border-radius: var(--pr-radius);
    padding: 36px 28px;
    position: relative;
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
}
.pr-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}
.pr-card.featured {
    border-color: var(--pr-accent);
    background: linear-gradient(180deg, rgba(108,92,231,0.08) 0%, var(--pr-surface) 40%);
    transform: scale(1.03);
    box-shadow: 0 8px 40px rgba(108,92,231,0.2);
    z-index: 2;
}
.pr-card.featured:hover {
    transform: scale(1.03) translateY(-4px);
    box-shadow: 0 16px 48px rgba(108,92,231,0.3);
}
.pr-card.ent-plus {
    border-color: rgba(253,203,110,0.4);
    background: linear-gradient(180deg, rgba(253,203,110,0.06) 0%, var(--pr-surface) 40%);
}
.pr-popular-badge, .pr-ent-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    color: #fff;
    padding: 6px 20px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    white-space: nowrap;
}
.pr-popular-badge { background: var(--pr-gradient); }
.pr-ent-badge { background: linear-gradient(135deg, #fdcb6e, #e17055); }
.pr-card-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.pr-card-desc {
    color: var(--pr-text-muted);
    font-size: 0.9rem;
    margin-bottom: 20px;
    min-height: 40px;
}
.pr-price { margin-bottom: 8px; }
.pr-price .currency {
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    vertical-align: super;
}
.pr-price .amount {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.8rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
}
.pr-price .period {
    font-size: 0.95rem;
    color: var(--pr-text-muted);
    font-weight: 400;
}
.pr-original-price {
    font-size: 0.88rem;
    color: var(--pr-text-muted);
    margin-bottom: 6px;
    min-height: 20px;
}
.pr-original-price s { color: #e17055; }
.pr-annual-total {
    font-size: 0.83rem;
    color: var(--pr-green);
    margin-bottom: 20px;
    min-height: 18px;
    font-weight: 600;
}
.pr-features {
    list-style: none;
    padding: 0;
    margin: 0 0 28px;
    flex: 1;
}
.pr-features li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 6px 0;
    color: var(--pr-text);
    font-size: 0.9rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.pr-features li:last-child { border-bottom: none; }
.pr-features li i {
    color: var(--pr-green);
    font-size: 0.8rem;
    margin-top: 4px;
    flex-shrink: 0;
}
.pr-btn {
    display: block;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    text-decoration: none;
}
.pr-btn:hover { transform: translateY(-2px); }
.pr-btn-primary {
    background: var(--pr-gradient);
    color: #fff;
    box-shadow: 0 4px 20px rgba(108,92,231,0.35);
}
.pr-btn-primary:hover { box-shadow: 0 8px 32px rgba(108,92,231,0.5); }
.pr-btn-secondary {
    background: var(--pr-surface-2);
    color: #fff;
    border: 1px solid var(--pr-border);
}
.pr-btn-secondary:hover {
    border-color: var(--pr-accent);
    box-shadow: 0 4px 16px rgba(108,92,231,0.2);
}
.pr-btn-gold {
    background: linear-gradient(135deg, #fdcb6e, #e17055);
    color: #fff;
    box-shadow: 0 4px 20px rgba(253,203,110,0.3);
}
.pr-btn-gold:hover { box-shadow: 0 8px 32px rgba(253,203,110,0.5); }

/* All Plans Include */
.pr-all-plans {
    padding: 80px 20px;
    background: var(--pr-surface);
}
.pr-all-plans-inner {
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
}
.pr-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
}
.pr-section-sub {
    color: var(--pr-text-muted);
    font-size: 1.1rem;
    margin-bottom: 48px;
}
.pr-includes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
}
.pr-include-item {
    background: var(--pr-surface-2);
    border: 1px solid var(--pr-border);
    border-radius: 12px;
    padding: 28px 20px;
    text-align: center;
}
.pr-include-item i {
    font-size: 2rem;
    margin-bottom: 12px;
    display: block;
}
.pr-include-item h4 {
    color: #fff;
    font-size: 1.05rem;
    margin: 0 0 6px;
    font-weight: 700;
}
.pr-include-item p {
    color: var(--pr-text-muted);
    font-size: 0.85rem;
    margin: 0;
}

/* Comparison Table */
.pr-comparison {
    padding: 80px 20px;
    background: var(--pr-bg);
}
.pr-comparison-inner {
    max-width: 1200px;
    margin: 0 auto;
}
.pr-table-wrap {
    overflow-x: auto;
    margin-top: 40px;
    border-radius: var(--pr-radius);
    border: 1px solid var(--pr-border);
}
.pr-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}
.pr-table thead th {
    background: var(--pr-surface-2);
    color: #fff;
    padding: 14px 12px;
    font-weight: 700;
    font-size: 0.85rem;
    text-align: center;
    border-bottom: 2px solid var(--pr-border);
}
.pr-table thead th:first-child { text-align: left; }
.pr-table thead th.featured-col {
    background: rgba(108,92,231,0.15);
    color: var(--pr-accent-light);
}
.pr-table thead th.ent-plus-col {
    background: rgba(253,203,110,0.1);
    color: var(--pr-orange);
}
.pr-table tbody td {
    padding: 12px;
    border-bottom: 1px solid var(--pr-border);
    color: var(--pr-text);
    font-size: 0.85rem;
    text-align: center;
}
.pr-table tbody td:first-child {
    text-align: left;
    font-weight: 600;
    color: #fff;
}
.pr-table tbody tr:hover { background: rgba(108,92,231,0.04); }
.pr-table .check { color: var(--pr-green); font-size: 1.1rem; }
.pr-table .dash { color: var(--pr-text-muted); }

/* FAQ */
.pr-faq {
    padding: 80px 20px;
    background: var(--pr-surface);
}
.pr-faq-inner {
    max-width: 760px;
    margin: 0 auto;
}
.pr-faq-item {
    border: 1px solid var(--pr-border);
    border-radius: 12px;
    margin-bottom: 12px;
    overflow: hidden;
    background: var(--pr-surface-2);
}
.pr-faq-q {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    cursor: pointer;
    color: #fff;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
    gap: 16px;
}
.pr-faq-q:hover { background: rgba(108,92,231,0.06); }
.pr-faq-q i {
    transition: transform 0.3s;
    color: var(--pr-accent-light);
    flex-shrink: 0;
}
.pr-faq-item.open .pr-faq-q i { transform: rotate(180deg); }
.pr-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.3s;
    padding: 0 24px;
    color: var(--pr-text-muted);
    font-size: 0.95rem;
    line-height: 1.7;
}
.pr-faq-item.open .pr-faq-a {
    max-height: 300px;
    padding: 0 24px 20px;
}

/* Enterprise CTA */
.pr-enterprise-cta {
    padding: 80px 20px;
    background: var(--pr-bg);
    text-align: center;
}
.pr-enterprise-box {
    max-width: 700px;
    margin: 0 auto;
    background: linear-gradient(135deg, rgba(108,92,231,0.12) 0%, rgba(9,132,227,0.08) 100%);
    border: 1px solid rgba(108,92,231,0.25);
    border-radius: var(--pr-radius);
    padding: 56px 40px;
}
.pr-enterprise-box h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 12px;
}
.pr-enterprise-box p {
    color: var(--pr-text-muted);
    font-size: 1.05rem;
    margin: 0 0 32px;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}
.pr-enterprise-box .pr-btn {
    display: inline-block;
    width: auto;
    padding: 16px 40px;
}

/* ═══ Responsive ═══ */

/* Tablet landscape */
@media (max-width: 1100px) {
    .pr-grid {
        grid-template-columns: repeat(2, 1fr);
        max-width: 700px;
    }
    .pr-comparison-inner { padding: 0; }
}

/* Tablet portrait */
@media (max-width: 768px) {
    .pr-hero { padding: 110px 16px 60px; }
    .pr-hero h1 { font-size: 2rem; }
    .pr-hero p { font-size: 1rem; }
    .pr-trust-badges { flex-direction: column; align-items: center; gap: 10px; }
    .pr-toggle-section { gap: 10px; flex-wrap: wrap; justify-content: center; }
    .pr-grid-section { padding: 32px 16px 60px; }
    .pr-grid {
        grid-template-columns: 1fr;
        max-width: 440px;
        margin: 0 auto;
        gap: 20px;
    }
    .pr-card.featured { transform: none; order: -1; }
    .pr-card.featured:hover { transform: translateY(-4px); }
    .pr-all-plans { padding: 60px 16px; }
    .pr-includes-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .pr-comparison { padding: 60px 16px; }
    .pr-table-wrap { margin-top: 24px; border-radius: 12px; }
    .pr-faq { padding: 60px 16px; }
    .pr-faq-q { padding: 16px 20px; font-size: 0.95rem; }
    .pr-enterprise-cta { padding: 60px 16px; }
    .pr-enterprise-box { padding: 40px 24px; }
    .pr-enterprise-box h2 { font-size: 1.6rem; }
    .pr-section-title { font-size: 1.6rem; }
    .pr-section-sub { font-size: 1rem; margin-bottom: 32px; }
}

/* Phone */
@media (max-width: 480px) {
    .pr-hero { padding: 100px 12px 48px; }
    .pr-hero h1 { font-size: 1.7rem; line-height: 1.25; }
    .pr-hero p { font-size: 0.92rem; }
    .pr-trust-badge { font-size: 0.82rem; }
    .pr-toggle-section { padding: 28px 12px 0; }
    .pr-save-badge { font-size: 0.78rem; padding: 3px 10px; }
    .pr-grid-section { padding: 24px 12px 48px; }
    .pr-card { padding: 24px 18px; border-radius: 12px; }
    .pr-card-name { font-size: 1.15rem; }
    .pr-price .amount { font-size: 2.4rem; }
    .pr-features li { font-size: 0.85rem; }
    .pr-btn { padding: 12px; font-size: 0.95rem; border-radius: 10px; }
    .pr-includes-grid { grid-template-columns: 1fr; }
    .pr-include-item { padding: 20px 16px; }
    .pr-include-item i { font-size: 1.6rem; }
    .pr-include-item h4 { font-size: 0.95rem; }
    .pr-faq-q { padding: 14px 16px; font-size: 0.9rem; }
    .pr-faq-a { font-size: 0.88rem; padding: 0 16px; }
    .pr-faq-item.open .pr-faq-a { padding: 0 16px 16px; }
    .pr-enterprise-box { padding: 32px 18px; border-radius: 12px; }
    .pr-enterprise-box h2 { font-size: 1.4rem; }
    .pr-enterprise-box p { font-size: 0.92rem; }
    .pr-enterprise-box .pr-btn { padding: 14px 28px; font-size: 0.95rem; }
    .pr-section-title { font-size: 1.35rem; }
    .pr-section-sub { font-size: 0.9rem; }
}

/* Touch targets */
@media (hover: none) and (pointer: coarse) {
    .pr-btn { min-height: 48px; }
    .pr-faq-q { min-height: 48px; }
    .pr-toggle-switch { width: 60px; height: 34px; }
    .pr-toggle-switch::after { width: 26px; height: 26px; }
    .pr-toggle-switch.annual::after { transform: translateX(26px); }
}
</style>

<!-- ===== HERO ===== -->
<section class="pr-hero">
    <h1><?php echo PT('hero_title'); ?><span><?php echo PT('hero_title_accent'); ?></span></h1>
    <p><?php echo PT('hero_sub'); ?></p>
    <div class="pr-trust-badges">
        <div class="pr-trust-badge"><i class="fas fa-shield-halved"></i> <?php echo PT('trust_trial'); ?></div>
        <div class="pr-trust-badge"><i class="fas fa-times-circle"></i> <?php echo PT('trust_cancel'); ?></div>
        <div class="pr-trust-badge"><i class="fas fa-credit-card"></i> <?php echo PT('trust_no_cc'); ?></div>
    </div>
</section>

<!-- ===== BILLING TOGGLE ===== -->
<div class="pr-toggle-section">
    <span class="pr-toggle-label active" id="prMonthlyLabel"><?php echo PT('toggle_monthly'); ?></span>
    <div class="pr-toggle-switch" id="prToggle" onclick="toggleBilling()"></div>
    <span class="pr-toggle-label" id="prAnnualLabel"><?php echo PT('toggle_annual'); ?></span>
    <span class="pr-save-badge"><?php echo PT('toggle_save'); ?></span>
</div>

<!-- ===== PRICING GRID — 6 Plans (3+3) ===== -->
<section class="pr-grid-section">
    <div class="pr-grid">

        <!-- Free -->
        <div class="pr-card">
            <div class="pr-card-name">Free</div>
            <div class="pr-card-desc">Get started with AI — no credit card required</div>
            <div class="pr-price">
                <span class="amount" data-monthly="Free" data-annual="Free">Free</span>
            </div>
            <div class="pr-original-price">&nbsp;</div>
            <div class="pr-annual-total">&nbsp;</div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> 10 AI tools</li>
                <li><i class="fas fa-check"></i> 5 voice min/day</li>
                <li><i class="fas fa-check"></i> 1 agent</li>
                <li><i class="fas fa-check"></i> Web chat only</li>
                <li><i class="fas fa-check"></i> 100 API calls/day</li>
                <li><i class="fas fa-check"></i> 1 GB storage</li>
            </ul>
            <a href="/alfred.php" class="pr-btn pr-btn-secondary">Get Started Free</a>
        </div>

        <!-- Starter -->
        <div class="pr-card">
            <div class="pr-card-name"><?php echo PT('plan_starter'); ?></div>
            <div class="pr-card-desc"><?php echo PT('plan_starter_desc'); ?></div>
            <div class="pr-price">
                <span class="currency">$</span><span class="amount" data-monthly="3.99" data-annual="2.77">3.99</span><span class="period">/mo</span>
            </div>
            <div class="pr-original-price" data-original="$3.99/mo"></div>
            <div class="pr-annual-total" data-annual-total="$33.26/year (save 17%)"></div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> 100 tools</li>
                <li><i class="fas fa-check"></i> 60 voice min/day</li>
                <li><i class="fas fa-check"></i> 3 agents</li>
                <li><i class="fas fa-check"></i> Web + voice</li>
                <li><i class="fas fa-check"></i> <?php echo PT('feat_email'); ?></li>
                <li><i class="fas fa-check"></i> 10,000 API calls/day</li>
                <li><i class="fas fa-check"></i> 4-person conference rooms</li>
                <li><i class="fas fa-check"></i> 10 GB storage</li>
            </ul>
            <button onclick="startCheckout('starter')" class="pr-btn pr-btn-secondary"><?php echo PT('btn_start_trial'); ?></button>
        </div>

        <!-- Professional (Most Popular) -->
        <div class="pr-card featured">
            <div class="pr-popular-badge"><i class="fas fa-fire"></i> <?php echo PT('plan_pro_badge'); ?></div>
            <div class="pr-card-name"><?php echo PT('plan_pro'); ?></div>
            <div class="pr-card-desc"><?php echo PT('plan_pro_desc'); ?></div>
            <div class="pr-price">
                <span class="currency">$</span><span class="amount" data-monthly="9.99" data-annual="6.94">9.99</span><span class="period">/mo</span>
            </div>
            <div class="pr-original-price" data-original="$9.99/mo"></div>
            <div class="pr-annual-total" data-annual-total="$83.25/year (save 17%)"></div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> ALL 1,220+ tools</li>
                <li><i class="fas fa-check"></i> Unlimited voice</li>
                <li><i class="fas fa-check"></i> 5 agents</li>
                <li><i class="fas fa-check"></i> All channels</li>
                <li><i class="fas fa-check"></i> <?php echo PT('feat_priority'); ?></li>
                <li><i class="fas fa-check"></i> 100,000 API calls/day</li>
                <li><i class="fas fa-check"></i> 10-person rooms</li>
                <li><i class="fas fa-check"></i> Marketplace publish</li>
                <li><i class="fas fa-check"></i> 50 GB storage</li>
            </ul>
            <button onclick="startCheckout('professional')" class="pr-btn pr-btn-primary"><?php echo PT('btn_start_trial'); ?></button>
        </div>

        <!-- Enterprise -->
        <div class="pr-card">
            <div class="pr-card-name"><?php echo PT('plan_ent'); ?></div>
            <div class="pr-card-desc"><?php echo PT('plan_ent_desc'); ?></div>
            <div class="pr-price">
                <span class="currency">$</span><span class="amount" data-monthly="24.99" data-annual="17.35">24.99</span><span class="period">/mo</span>
            </div>
            <div class="pr-original-price" data-original="$24.99/mo"></div>
            <div class="pr-annual-total" data-annual-total="$208.25/year (save 17%)"></div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> ALL tools + priority access</li>
                <li><i class="fas fa-check"></i> Unlimited voice</li>
                <li><i class="fas fa-check"></i> 20 agents</li>
                <li><i class="fas fa-check"></i> All channels</li>
                <li><i class="fas fa-check"></i> 24/7 support</li>
                <li><i class="fas fa-check"></i> 500,000 API calls/day</li>
                <li><i class="fas fa-check"></i> 20-person rooms</li>
                <li><i class="fas fa-check"></i> Org accounts &amp; team mgmt</li>
                <li><i class="fas fa-check"></i> 200 GB storage</li>
            </ul>
            <button onclick="startCheckout('enterprise')" class="pr-btn pr-btn-secondary"><?php echo PT('btn_start_trial'); ?></button>
        </div>

        <!-- Enterprise Plus -->
        <div class="pr-card ent-plus">
            <div class="pr-ent-badge"><i class="fas fa-crown"></i> Enterprise</div>
            <div class="pr-card-name">Enterprise Plus</div>
            <div class="pr-card-desc">Advanced security, compliance &amp; dedicated support for large teams</div>
            <div class="pr-price">
                <span class="currency">$</span><span class="amount" data-monthly="99" data-annual="68.75">99</span><span class="period">/mo</span>
            </div>
            <div class="pr-original-price" data-original="$99/mo"></div>
            <div class="pr-annual-total" data-annual-total="$825/year (save 17%)"></div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> Everything in Enterprise</li>
                <li><i class="fas fa-check"></i> SSO (SAML/OIDC)</li>
                <li><i class="fas fa-check"></i> Audit logging</li>
                <li><i class="fas fa-check"></i> Dedicated CSM</li>
                <li><i class="fas fa-check"></i> Unlimited API</li>
                <li><i class="fas fa-check"></i> 50-person rooms</li>
                <li><i class="fas fa-check"></i> Revenue sharing</li>
                <li><i class="fas fa-check"></i> Voice cloning</li>
                <li><i class="fas fa-check"></i> Data residency &amp; 1 TB storage</li>
            </ul>
            <button onclick="startCheckout('enterprise_plus')" class="pr-btn pr-btn-gold"><?php echo PT('btn_start_trial'); ?></button>
        </div>

        <!-- Custom -->
        <div class="pr-card">
            <div class="pr-card-name">Enterprise Custom</div>
            <div class="pr-card-desc">White-label, dedicated infrastructure &amp; custom SLA for the largest orgs</div>
            <div class="pr-price">
                <span class="currency">$</span><span class="amount" data-monthly="299+" data-annual="Custom">299+</span><span class="period">/mo</span>
            </div>
            <div class="pr-original-price">&nbsp;</div>
            <div class="pr-annual-total" data-annual-total="Custom annual pricing"></div>
            <ul class="pr-features">
                <li><i class="fas fa-check"></i> Everything in Enterprise Plus</li>
                <li><i class="fas fa-check"></i> White-label deploy</li>
                <li><i class="fas fa-check"></i> Custom SLA (99.95%)</li>
                <li><i class="fas fa-check"></i> Dedicated support team</li>
                <li><i class="fas fa-check"></i> Unlimited everything</li>
                <li><i class="fas fa-check"></i> On-site onboarding</li>
                <li><i class="fas fa-check"></i> Custom AI training</li>
                <li><i class="fas fa-check"></i> Dedicated infrastructure</li>
            </ul>
            <button onclick="window.location.href='/enterprise.php?contact=sales'" class="pr-btn pr-btn-secondary"><i class="fas fa-headset"></i> <?php echo PT('btn_contact_sales'); ?></button>
        </div>

    </div>
</section>

<!-- ===== ALL PLANS INCLUDE ===== -->
<section class="pr-all-plans">
    <div class="pr-all-plans-inner">
        <h2 class="pr-section-title"><?php echo PT('all_plans_title'); ?></h2>
        <p class="pr-section-sub"><?php echo PT('all_plans_sub'); ?></p>
        <div class="pr-includes-grid">
            <div class="pr-include-item">
                <i class="fas fa-toolbox" style="color: var(--pr-accent-light);"></i>
                <h4><?php echo PT('inc_tools_title'); ?></h4>
                <p><?php echo PT('inc_tools_desc'); ?></p>
            </div>
            <div class="pr-include-item">
                <i class="fas fa-layer-group" style="color: var(--pr-blue);"></i>
                <h4><?php echo PT('inc_cat_title'); ?></h4>
                <p><?php echo PT('inc_cat_desc'); ?></p>
            </div>
            <div class="pr-include-item">
                <i class="fas fa-language" style="color: var(--pr-green);"></i>
                <h4><?php echo PT('inc_bilingual_title'); ?></h4>
                <p><?php echo PT('inc_bilingual_desc'); ?></p>
            </div>
            <div class="pr-include-item">
                <i class="fas fa-lock" style="color: var(--pr-orange);"></i>
                <h4><?php echo PT('inc_ssl_title'); ?></h4>
                <p><?php echo PT('inc_ssl_desc'); ?></p>
            </div>
            <div class="pr-include-item">
                <i class="fas fa-calendar-check" style="color: var(--pr-accent);"></i>
                <h4><?php echo PT('inc_trial_title'); ?></h4>
                <p><?php echo PT('inc_trial_desc'); ?></p>
            </div>
            <div class="pr-include-item">
                <i class="fas fa-headset" style="color: #fd79a8;"></i>
                <h4><?php echo PT('inc_community_title'); ?></h4>
                <p><?php echo PT('inc_community_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ===== COMPARISON TABLE — All 6 tiers ===== -->
<section class="pr-comparison">
    <div class="pr-comparison-inner">
        <h2 class="pr-section-title" style="text-align:center;"><?php echo PT('compare_title'); ?></h2>
        <p class="pr-section-sub" style="text-align:center;"><?php echo PT('compare_sub'); ?></p>
        <div class="pr-table-wrap">
            <table class="pr-table">
                <thead>
                    <tr>
                        <th><?php echo PT('th_feature'); ?></th>
                        <th>Free</th>
                        <th><?php echo PT('plan_starter'); ?></th>
                        <th class="featured-col"><?php echo PT('plan_pro'); ?></th>
                        <th><?php echo PT('plan_ent'); ?></th>
                        <th class="ent-plus-col">Enterprise Plus</th>
                        <th>Custom</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo PT('td_price'); ?></td>
                        <td>Free</td>
                        <td>$3.99/mo</td>
                        <td>$9.99/mo</td>
                        <td>$24.99/mo</td>
                        <td>$99/mo</td>
                        <td>$299+/mo</td>
                    </tr>
                    <tr>
                        <td>Annual Price</td>
                        <td>Free</td>
                        <td>$33.26/yr</td>
                        <td>$83.25/yr</td>
                        <td>$208.25/yr</td>
                        <td>$825/yr</td>
                        <td>Custom</td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_tools_access'); ?></td>
                        <td>10 tools</td>
                        <td>100 tools</td>
                        <td>1,220+ tools</td>
                        <td>1,220+ tools</td>
                        <td>1,220+ tools</td>
                        <td>1,220+ tools</td>
                    </tr>
                    <tr>
                        <td>API Calls / Day</td>
                        <td>100</td>
                        <td>10,000</td>
                        <td>100,000</td>
                        <td>500,000</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td>Voice Minutes / Day</td>
                        <td>5 min</td>
                        <td>60 min</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td>Agents</td>
                        <td>1</td>
                        <td>3</td>
                        <td>5</td>
                        <td>20</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td>Fleets</td>
                        <td class="dash">—</td>
                        <td>1</td>
                        <td>3</td>
                        <td>10</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td>Conference Rooms</td>
                        <td class="dash">—</td>
                        <td>4-person</td>
                        <td>10-person</td>
                        <td>20-person</td>
                        <td>50-person</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td>Storage</td>
                        <td>1 GB</td>
                        <td>10 GB</td>
                        <td>50 GB</td>
                        <td>200 GB</td>
                        <td>1 TB</td>
                        <td><?php echo PT('td_unlimited'); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_voice_cmd'); ?></td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_api_access'); ?></td>
                        <td>Basic</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Marketplace Publish</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Team / Org Accounts</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>SSO (SAML/OIDC)</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Audit Logging</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Voice Cloning</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Data Residency</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>White-Label Deploy</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td>Custom SLA (99.95%)</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td>99.9%</td>
                        <td>99.9%</td>
                        <td>99.95%</td>
                    </tr>
                    <tr>
                        <td>Dedicated Infrastructure</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_support'); ?></td>
                        <td>Community</td>
                        <td>Email</td>
                        <td><?php echo PT('td_priority'); ?></td>
                        <td>24/7</td>
                        <td>Dedicated CSM</td>
                        <td>Dedicated Team</td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_bilingual'); ?></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_ssl'); ?></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                    <tr>
                        <td><?php echo PT('td_trial'); ?></td>
                        <td class="dash">—</td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                        <td><i class="fas fa-check check"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="pr-faq">
    <div class="pr-faq-inner">
        <h2 class="pr-section-title" style="text-align:center;"><?php echo PT('faq_title'); ?></h2>
        <p class="pr-section-sub" style="text-align:center;"><?php echo PT('faq_sub'); ?></p>

        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q1'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a1'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q2'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a2'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q3'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a3'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q4'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a">Annual billing saves you 17% compared to monthly pricing. You pay for 10 months and get 12 — that's 2 months free. For example, Professional is $83.25/year instead of $119.88/year.</div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q5'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a5'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q6'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a6'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q7'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a7'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span>What are overage charges?</span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a">If you exceed your plan's limits (e.g., API calls, voice minutes), you'll be billed at transparent per-unit overage rates. For example, extra API calls are $0.001 each and extra voice minutes are $0.05 each. You can view your usage anytime from your dashboard.</div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span><?php echo PT('faq_q9'); ?></span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a"><?php echo PT('faq_a9'); ?></div>
        </div>
        <div class="pr-faq-item">
            <div class="pr-faq-q" onclick="toggleFaq(this)"><span>How does plan switching work?</span><i class="fas fa-chevron-down"></i></div>
            <div class="pr-faq-a">You can upgrade or downgrade at any time. When upgrading, you'll be charged a prorated amount for the remainder of your billing cycle. When downgrading, the credit will be applied to your next bill. Changes take effect immediately.</div>
        </div>
    </div>
</section>

<!-- ===== ENTERPRISE CTA ===== -->
<section class="pr-enterprise-cta">
    <div class="pr-enterprise-box">
        <h2><?php echo PT('ent_cta_title'); ?></h2>
        <p><?php echo PT('ent_cta_text'); ?></p>
        <button onclick="window.location.href='/enterprise.php?contact=sales'" class="pr-btn pr-btn-primary"><i class="fas fa-headset"></i> <?php echo PT('btn_contact_sales'); ?></button>
    </div>
</section>

<script>window.PR_LOADING_TEXT = '<?php echo PT("loading"); ?>';</script>
<script src="/assets/js/pricing-engine.js"></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
