<?php
/**
 * Add Phase 4.5 changelog entries to v19.1 "Fleet Command"
 */
defined('GOSITEME_API') || define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

$db = getDB();

$vid = (int) $db->query("SELECT id FROM platform_changelog_versions WHERE version='19.1'")->fetchColumn();
if (!$vid) { die("ERROR: v19.1 not found\n"); }

$maxSort = (int) $db->query("SELECT COALESCE(MAX(sort_order),0) FROM platform_changelog_entries WHERE version_id={$vid}")->fetchColumn();

$entries = [
    [
        'title_en' => "Threat Intelligence Database — 3 New Tables",
        'desc_en'  => "Created threat_intelligence, threat_indicators (IOCs), and accountability_ledger tables. Full schema for tracking external threats, indicators of compromise, and immutable audit trail of all enforcement actions.",
        'title_fr' => "Base de données de renseignement sur les menaces — 3 nouvelles tables",
        'desc_fr'  => "Création des tables threat_intelligence, threat_indicators (IOC) et accountability_ledger. Schéma complet pour le suivi des menaces externes, des indicateurs de compromission et de la piste d'audit immuable.",
        'icon'     => 'fas fa-database',
        'color'    => '#ef4444',
        'tag'      => 'security',
    ],
    [
        'title_en' => "Threat Intel API — 12 Endpoints",
        'desc_en'  => "Built api/threat-intel.php with 12 endpoints: overview, threats, threat_detail, report_threat, update_threat, indicators, add_indicator, ledger, log_action, blocked, block_actor, unblock_actor. Bridges existing justice system.",
        'title_fr' => "API de renseignement sur les menaces — 12 points d'accès",
        'desc_fr'  => "Construction de api/threat-intel.php avec 12 points d'accès : overview, threats, threat_detail, report_threat, update_threat, indicators, add_indicator, ledger, log_action, blocked, block_actor, unblock_actor.",
        'icon'     => 'fas fa-plug',
        'color'    => '#f97316',
        'tag'      => 'api',
    ],
    [
        'title_en' => "Justice & Threat Intelligence Dashboard",
        'desc_en'  => "Created justice-dashboard.php — unified command center with 6 tabs: Threats, Blocked Actors, Accountability Ledger, Court Cases, Jail Population, and Report Threat form. Live stats and full CRUD operations.",
        'title_fr' => "Tableau de bord Justice et Renseignement sur les Menaces",
        'desc_fr'  => "Création de justice-dashboard.php — centre de commandement unifié avec 6 onglets : Menaces, Acteurs bloqués, Registre de responsabilité, Affaires judiciaires, Population carcérale et Formulaire de signalement.",
        'icon'     => 'fas fa-shield-alt',
        'color'    => '#f5c542',
        'tag'      => 'dashboard',
    ],
    [
        'title_en' => "Accountability Ledger — Immutable Audit Trail",
        'desc_en'  => "Every enforcement action now auto-logged to the accountability_ledger with full chain of custody: who did what, when, why, and what happened. Linked to threats, court cases, and infractions.",
        'title_fr' => "Registre de responsabilité — Piste d'audit immuable",
        'desc_fr'  => "Chaque action d'application est maintenant enregistrée automatiquement dans le registre de responsabilité avec la chaîne de garde complète : qui a fait quoi, quand, pourquoi et ce qui s'est passé.",
        'icon'     => 'fas fa-book',
        'color'    => '#8b5cf6',
        'tag'      => 'security',
    ],
];

$stmt = $db->prepare("INSERT INTO platform_changelog_entries
    (version_id, title_en, description_en, title_fr, description_fr, icon, icon_color, tag, sort_order, is_deleted, created_by, agent_name)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 33, 'alfred')");

$added = 0;
foreach ($entries as $i => $e) {
    $sort = $maxSort + $i + 1;
    $stmt->execute([$vid, $e['title_en'], $e['desc_en'], $e['title_fr'], $e['desc_fr'], $e['icon'], $e['color'], $e['tag'], $sort]);
    $added++;
}

$total = (int) $db->query("SELECT COUNT(*) FROM platform_changelog_entries WHERE version_id={$vid} AND is_deleted=0")->fetchColumn();
echo "Phase 4.5 entries added: {$added}, Total v19.1 entries: {$total}\n";
