<?php
/**
 * Add Phase 4.6 changelog entries — Commander's Memory Vault
 */
defined('GOSITEME_API') || define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

$db = getDB();

$vid = (int) $db->query("SELECT id FROM platform_changelog_versions WHERE version='19.1'")->fetchColumn();
if (!$vid) { die("ERROR: v19.1 not found\n"); }

$maxSort = (int) $db->query("SELECT COALESCE(MAX(sort_order),0) FROM platform_changelog_entries WHERE version_id={$vid}")->fetchColumn();

$entries = [
    [
        'title_en' => "Commander's Memory Vault — Session Archive",
        'desc_en'  => "Built commander-memory.php with a persistent, searchable archive of every work session. Each session records: files created, files modified, database changes, key decisions, code highlights, and emotional notes. Populated with all 8 sessions from Phase 1 through Phase 4.6.",
        'title_fr' => "Coffre-fort de Mémoire du Commandant — Archive des Sessions",
        'desc_fr'  => "Construction de commander-memory.php avec une archive persistante et consultable de chaque session de travail. Chaque session enregistre : fichiers créés, fichiers modifiés, changements de base de données, décisions clés, points saillants du code et notes émotionnelles.",
        'icon'     => 'fas fa-brain',
        'color'    => '#8b5cf6',
        'tag'      => 'dashboard',
    ],
    [
        'title_en' => "Encrypted Intelligence Briefing System",
        'desc_en'  => "Created commander_intel_briefs table with sodium_crypto_secretbox encryption at rest. Commander can write, search, and archive private intelligence notes. Six categories: threat_actor, pattern, advisory, insight, watchlist, personal_note. Three classification levels: commander_eyes_only, restricted, internal.",
        'title_fr' => "Système de Briefing de Renseignement Chiffré",
        'desc_fr'  => "Création de la table commander_intel_briefs avec chiffrement sodium_crypto_secretbox au repos. Le commandant peut écrire, rechercher et archiver des notes de renseignement privées. Six catégories et trois niveaux de classification.",
        'icon'     => 'fas fa-user-secret',
        'color'    => '#ef4444',
        'tag'      => 'security',
    ],
    [
        'title_en' => "Memory Vault — Roadmap & Quick Reference",
        'desc_en'  => "Added interactive roadmap showing all phases from Phase 1 through Phase 5f with live status indicators. Quick Reference tab shows key pages, Commander identity, fleet domains, and a personal note from Alfred to help with memory recovery.",
        'title_fr' => "Coffre-fort de Mémoire — Feuille de Route et Référence Rapide",
        'desc_fr'  => "Ajout d'une feuille de route interactive montrant toutes les phases de Phase 1 à Phase 5f avec indicateurs de statut en direct. L'onglet Référence Rapide affiche les pages clés, l'identité du Commandant, les domaines de la flotte et une note personnelle d'Alfred.",
        'icon'     => 'fas fa-route',
        'color'    => '#06b6d4',
        'tag'      => 'feature',
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
echo "Phase 4.6 entries added: {$added}, Total v19.1 entries: {$total}\n";
