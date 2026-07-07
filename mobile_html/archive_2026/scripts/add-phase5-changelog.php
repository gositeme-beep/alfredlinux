<?php
/**
 * Add Phase 5 changelog entries to v19.1 "Fleet Command"
 */
defined('GOSITEME_API') || define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

$db = getDB();

// Get v19.1 version_id
$vid = (int) $db->query("SELECT id FROM platform_changelog_versions WHERE version='19.1'")->fetchColumn();
if (!$vid) { die("ERROR: v19.1 not found\n"); }

// Get current max sort_order in v19.1
$maxSort = (int) $db->query("SELECT COALESCE(MAX(sort_order),0) FROM platform_changelog_entries WHERE version_id={$vid}")->fetchColumn();

$entries = [
    [
        'title_en' => "Commander's Chronicle — Journey Dashboard",
        'desc_en'  => "Created the Commander's Chronicle page documenting every phase of the Danny + Alfred journey, with live stats, phase timeline, version history, and quick links.",
        'title_fr' => "Chronique du Commandant — Tableau de bord du parcours",
        'desc_fr'  => "Création de la page Chronique du Commandant documentant chaque phase du parcours Danny + Alfred, avec stats en direct, chronologie des phases, historique des versions et liens rapides.",
        'icon'     => 'fas fa-scroll',
        'color'    => '#f5c542',
        'tag'      => 'commander',
    ],
    [
        'title_en' => "Intelligence Department — 5,000 New Agents",
        'desc_en'  => "Scaled fleet from 5,000 to 10,000 agents. Added 5 new intelligence domains: intelligence, quantum, biotech, space, and philosophy — each with 1,000 agents.",
        'title_fr' => "Département du Renseignement — 5 000 nouveaux agents",
        'desc_fr'  => "Mise à l'échelle de la flotte de 5 000 à 10 000 agents. Ajout de 5 nouveaux domaines de renseignement : intelligence, quantique, biotechnologie, espace et philosophie — chacun avec 1 000 agents.",
        'icon'     => 'fas fa-brain',
        'color'    => '#8b5cf6',
        'tag'      => 'fleet',
    ],
    [
        'title_en' => "15 Fleet Domains — Full Spectrum Coverage",
        'desc_en'  => "Fleet now spans 15 domains: engineering, security, research, finance, communications, infrastructure, marketing, analytics, creative, robotics, intelligence, quantum, biotech, space, philosophy.",
        'title_fr' => "15 domaines de flotte — Couverture complète",
        'desc_fr'  => "La flotte couvre maintenant 15 domaines : ingénierie, sécurité, recherche, finances, communications, infrastructure, marketing, analytique, création, robotique, renseignement, quantique, biotechnologie, espace, philosophie.",
        'icon'     => 'fas fa-globe',
        'color'    => '#22d3ee',
        'tag'      => 'fleet',
    ],
    [
        'title_en' => "Phase 5 Roadmap — 30 Tasks Across 6 Sub-Phases",
        'desc_en'  => "Intelligence department analyzed the ecosystem and planned Phase 5: Fleet Intelligence API (5a), Quantum Hardening (5b), Biotech Module (5c), Space Ops (5d), Philosophy Engine (5e), Fleet Autonomy (5f).",
        'title_fr' => "Feuille de route Phase 5 — 30 tâches en 6 sous-phases",
        'desc_fr'  => "Le département du renseignement a analysé l'écosystème et planifié la Phase 5 : API de renseignement de flotte (5a), Renforcement quantique (5b), Module biotech (5c), Ops spatiales (5d), Moteur de philosophie (5e), Autonomie de flotte (5f).",
        'icon'     => 'fas fa-map',
        'color'    => '#34d399',
        'tag'      => 'roadmap',
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
echo "Phase 5 entries added: {$added}, Total v19.1 entries: {$total}\n";
