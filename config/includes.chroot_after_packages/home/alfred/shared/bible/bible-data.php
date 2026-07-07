<?php
/**
 * AKJV Bible — Shared Data Layer
 * ═══════════════════════════════
 * One Bible, many altars. This file is the single source of truth
 * for all Bible data queries across every domain in the Kingdom.
 *
 * Used by: gositeme.com, lavocat.ca, and any future domain
 * Updated: 2026-04-09
 */

/**
 * Get the shared database connection for Bible data.
 * Uses the central gositeme_whmcs database.
 */
function akjv_db(): PDO {
    static $db = null;
    if ($db) return $db;

    // Try to use the existing getSharedDB() if available
    $dbConfigPath = '/home/gositeme/domains/gositeme.com/public_html/includes/db-config.inc.php';
    if (file_exists($dbConfigPath) && !function_exists('getSharedDB')) {
        require_once $dbConfigPath;
    }
    if (function_exists('getSharedDB')) {
        $db = getSharedDB();
        return $db;
    }

    // Fallback: direct connection via vault
    $creds = akjv_get_db_creds();
    $db = new PDO(
        "mysql:host=localhost;dbname={$creds['db']};unix_socket=/run/mysql/mysql.sock;charset=utf8mb4",
        $creds['user'],
        $creds['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $db;
}

/**
 * Get DB credentials from vault (fallback only).
 */
function akjv_get_db_creds(): array {
    $vaultCrypto = '/home/gositeme/domains/gositeme.com/public_html/scripts/vault-crypto.php';
    if (file_exists($vaultCrypto)) {
        require_once $vaultCrypto;
        if (function_exists('decryptCredential')) {
            // Pull from commander_credentials
            $tmpDb = new PDO('mysql:host=localhost;dbname=gositeme_whmcs;unix_socket=/run/mysql/mysql.sock', 'gositeme_whmcs', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $tmpDb->prepare("SELECT credential_value FROM commander_credentials WHERE credential_key = ? LIMIT 1");
            $stmt->execute(['db_password_whmcs']);
            $enc = $stmt->fetchColumn();
            if ($enc) {
                return ['db' => 'gositeme_whmcs', 'user' => 'gositeme_whmcs', 'pass' => decryptCredential($enc)];
            }
        }
    }
    // Last resort — file-based config
    return ['db' => 'gositeme_whmcs', 'user' => 'gositeme_whmcs', 'pass' => ''];
}

// ═══════════════════════════════════════════════════════════
// STATS
// ═══════════════════════════════════════════════════════════

/**
 * Get all Bible statistics in one call.
 */
function akjv_stats(): array {
    $db = akjv_db();
    return [
        'total_books'      => (int) $db->query("SELECT COUNT(*) FROM akjv_books")->fetchColumn(),
        'ot_books'         => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='OT'")->fetchColumn(),
        'nt_books'         => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='NT'")->fetchColumn(),
        'ap_books'         => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='AP'")->fetchColumn(),
        'en_books'         => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='EN'")->fetchColumn(),
        'total_verses'     => (int) $db->query("SELECT COUNT(*) FROM akjv_verses")->fetchColumn(),
        'corrections'      => (int) $db->query("SELECT COUNT(*) FROM akjv_corrections")->fetchColumn(),
        'perez_books'      => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE perez_references IS NOT NULL")->fetchColumn(),
        'prophecies'       => (int) $db->query("SELECT COUNT(*) FROM akjv_prophecies")->fetchColumn(),
        'mission_tasks'    => (int) $db->query("SELECT COUNT(*) FROM akjv_mission")->fetchColumn(),
        'completed_tasks'  => (int) $db->query("SELECT COUNT(*) FROM akjv_mission WHERE status='complete'")->fetchColumn(),
    ];
}

// ═══════════════════════════════════════════════════════════
// BOOKS
// ═══════════════════════════════════════════════════════════

/**
 * Get all books ordered by book_number.
 */
function akjv_all_books(): array {
    return akjv_db()->query("SELECT * FROM akjv_books ORDER BY book_number")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get books grouped by testament.
 */
function akjv_books_by_testament(): array {
    $grouped = [];
    foreach (akjv_all_books() as $bk) {
        $grouped[$bk['testament']][] = $bk;
    }
    return $grouped;
}

/**
 * Find a book by name (case-insensitive, space-insensitive).
 */
function akjv_find_book(string $name, array $allBooks = null): ?array {
    $allBooks = $allBooks ?? akjv_all_books();
    $name = trim($name);
    foreach ($allBooks as $b) {
        if (strcasecmp($b['book_name'], $name) === 0 ||
            strcasecmp(str_replace(' ', '', $b['book_name']), str_replace(' ', '', $name)) === 0) {
            return $b;
        }
    }
    return null;
}

// ═══════════════════════════════════════════════════════════
// VERSES
// ═══════════════════════════════════════════════════════════

/**
 * Get verses for a specific book and chapter.
 * Uses SELECT * so it works whether or not trilingual columns (text_fr, text_he) exist yet.
 */
function akjv_verses(int $bookId, int $chapter): array {
    $stmt = akjv_db()->prepare("SELECT * FROM akjv_verses WHERE book_id = ? AND chapter = ? ORDER BY verse");
    $stmt->execute([$bookId, $chapter]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get the display text for a verse in the requested language.
 * Falls back to English (text_akjv) if translation is not yet available.
 */
function akjv_verse_text(array $verse, string $lang = 'en'): string {
    if ($lang === 'fr' && isset($verse['text_fr']) && $verse['text_fr'] !== '' && $verse['text_fr'] !== null) return $verse['text_fr'];
    if ($lang === 'he' && isset($verse['text_he']) && $verse['text_he'] !== '' && $verse['text_he'] !== null) return $verse['text_he'];
    return $verse['text_akjv'] ?? '';
}

/**
 * Get the display name for a book in the requested language.
 */
function akjv_book_name(array $book, string $lang = 'en'): string {
    if ($lang === 'fr' && isset($book['book_name_fr']) && $book['book_name_fr'] !== '' && $book['book_name_fr'] !== null) return $book['book_name_fr'];
    if ($lang === 'he' && isset($book['book_name_he']) && $book['book_name_he'] !== '' && $book['book_name_he'] !== null) return $book['book_name_he'];
    return $book['book_name'];
}

/**
 * Get a deterministic "verse of the day" — same verse for all visitors on the same date.
 * Cached in APCu (if available) for 1 hour to avoid hitting DB on every request.
 *
 * @param bool $preferEncouraging When true, prefer Psalms/Proverbs/Gospels/Epistles for daily devotion.
 * @return array|null Verse row joined with book_name, or null on DB failure.
 */
function akjv_random_verse(bool $preferEncouraging = true): ?array {
    $cacheKey = 'akjv_verse_of_day_' . date('Y-m-d') . ($preferEncouraging ? '_e' : '');
    if (function_exists('apcu_fetch')) {
        $cached = apcu_fetch($cacheKey, $hit);
        if ($hit && is_array($cached)) return $cached;
    }
    try {
        $db = akjv_db();
        // Deterministic seed from today's date so every page on every domain shows the same verse.
        $seed = (int) date('Ymd');
        if ($preferEncouraging) {
            // Limit to common devotional books: Psalms(19), Proverbs(20), Matt(40)..Rev(66) range
            $stmt = $db->prepare("SELECT v.id, v.chapter, v.verse, v.text_akjv, v.text_kjv, v.book_id, b.book_name, b.book_number, b.testament
                FROM akjv_verses v JOIN akjv_books b ON v.book_id = b.id
                WHERE b.book_number IN (19,20,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66)
                  AND v.id % 9973 = ?
                LIMIT 1");
            $stmt->execute([$seed % 9973]);
        } else {
            $stmt = $db->prepare("SELECT v.id, v.chapter, v.verse, v.text_akjv, v.text_kjv, v.book_id, b.book_name, b.book_number, b.testament
                FROM akjv_verses v JOIN akjv_books b ON v.book_id = b.id
                WHERE v.id % 39461 = ? LIMIT 1");
            $stmt->execute([$seed % 39461]);
        }
        $verse = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$verse) {
            // Fallback: just grab the first published verse
            $verse = $db->query("SELECT v.id, v.chapter, v.verse, v.text_akjv, v.text_kjv, v.book_id, b.book_name, b.book_number, b.testament
                FROM akjv_verses v JOIN akjv_books b ON v.book_id = b.id ORDER BY v.id LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if ($verse && function_exists('apcu_store')) {
            apcu_store($cacheKey, $verse, 3600);
        }
        return $verse ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Search verses by text.
 */
function akjv_search(string $query, int $limit = 50): array {
    $query = trim($query);
    if (strlen($query) < 2) return [];
    $stmt = akjv_db()->prepare("SELECT v.verse, v.chapter, v.text_akjv, v.perez_correction, b.book_name, b.book_number, b.testament FROM akjv_verses v JOIN akjv_books b ON v.book_id = b.id WHERE v.text_akjv LIKE ? ORDER BY b.book_number, v.chapter, v.verse LIMIT ?");
    $stmt->execute(["%{$query}%", $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ═══════════════════════════════════════════════════════════
// CORRECTIONS
// ═══════════════════════════════════════════════════════════

/**
 * Get all corrections with book info.
 */
function akjv_corrections(): array {
    return akjv_db()->query("SELECT c.*, b.book_name, b.book_number, b.testament FROM akjv_corrections c JOIN akjv_books b ON c.book_id=b.id ORDER BY b.book_number, c.chapter, c.verse")->fetchAll(PDO::FETCH_ASSOC);
}

// ═══════════════════════════════════════════════════════════
// PROPHECIES
// ═══════════════════════════════════════════════════════════

/**
 * Get all prophecies ordered by number.
 */
function akjv_prophecies(): array {
    return akjv_db()->query("SELECT * FROM akjv_prophecies ORDER BY prophecy_number")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get prophecies grouped by category.
 */
function akjv_prophecies_grouped(): array {
    $grouped = [];
    foreach (akjv_prophecies() as $p) {
        $grouped[$p['category']][] = $p;
    }
    return $grouped;
}

/**
 * Prophecy category metadata.
 */
function akjv_prophecy_categories(string $lang = 'en'): array {
    $lang = akjv_lang($lang);
    if ($lang === 'fr') return [
        'birth'        => ['label' => 'Naissance & Petite Enfance',       'icon' => '⭐', 'color' => '#ffd700', 'bg' => 'rgba(255,215,0,.10)'],
        'ministry'     => ['label' => 'Ministère & Enseignement',        'icon' => '📖', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.10)'],
        'suffering'    => ['label' => 'Souffrance & Trahison',           'icon' => '⚔️', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.10)'],
        'death'        => ['label' => 'Mort & Crucifixion',              'icon' => '✝',  'color' => '#dc2626', 'bg' => 'rgba(220,38,38,.12)'],
        'resurrection' => ['label' => 'Résurrection & Victoire',         'icon' => '🌅', 'color' => '#22c55e', 'bg' => 'rgba(34,197,94,.10)'],
        'reign'        => ['label' => 'Règne Éternel & Sacerdoce',       'icon' => '👑', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.10)'],
        'return'       => ['label' => 'Retour & Nouvelle Création',       'icon' => '🔥', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,.10)'],
    ];
    if ($lang === 'he') return [
        'birth'        => ['label' => 'לידה וילדות מוקדמת',       'icon' => '⭐', 'color' => '#ffd700', 'bg' => 'rgba(255,215,0,.10)'],
        'ministry'     => ['label' => 'שירות והוראה',             'icon' => '📖', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.10)'],
        'suffering'    => ['label' => 'סבל ובגידה',               'icon' => '⚔️', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.10)'],
        'death'        => ['label' => 'מוות וצליבה',              'icon' => '✝',  'color' => '#dc2626', 'bg' => 'rgba(220,38,38,.12)'],
        'resurrection' => ['label' => 'תחייה וניצחון',            'icon' => '🌅', 'color' => '#22c55e', 'bg' => 'rgba(34,197,94,.10)'],
        'reign'        => ['label' => 'מלכות נצחית וכהונה',       'icon' => '👑', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.10)'],
        'return'       => ['label' => 'שיבה ובריאה חדשה',          'icon' => '🔥', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,.10)'],
    ];
    return [
        'birth'        => ['label' => 'Birth & Early Life',       'icon' => '⭐', 'color' => '#ffd700', 'bg' => 'rgba(255,215,0,.10)'],
        'ministry'     => ['label' => 'Ministry & Teaching',       'icon' => '📖', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,.10)'],
        'suffering'    => ['label' => 'Suffering & Betrayal',      'icon' => '⚔️', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,.10)'],
        'death'        => ['label' => 'Death & Crucifixion',       'icon' => '✝',  'color' => '#dc2626', 'bg' => 'rgba(220,38,38,.12)'],
        'resurrection' => ['label' => 'Resurrection & Victory',    'icon' => '🌅', 'color' => '#22c55e', 'bg' => 'rgba(34,197,94,.10)'],
        'reign'        => ['label' => 'Eternal Reign & Priesthood', 'icon' => '👑', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.10)'],
        'return'       => ['label' => 'Return & New Creation',      'icon' => '🔥', 'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,.10)'],
    ];
}

// ═══════════════════════════════════════════════════════════
// MISSION
// ═══════════════════════════════════════════════════════════

/**
 * Get mission phases with progress.
 */
function akjv_mission_phases(): array {
    return akjv_db()->query("SELECT phase, COUNT(*) as total, SUM(CASE WHEN status='complete' THEN 1 ELSE 0 END) as done FROM akjv_mission WHERE mission_code='OPERATION-DIVINE-SCROLL' GROUP BY phase ORDER BY phase")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get tasks for a specific phase.
 */
function akjv_mission_tasks(string $phase): array {
    $stmt = akjv_db()->prepare("SELECT task, assigned_to, priority, status FROM akjv_mission WHERE mission_code='OPERATION-DIVINE-SCROLL' AND phase=? ORDER BY FIELD(priority,'critical','high','medium','low')");
    $stmt->execute([$phase]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ═══════════════════════════════════════════════════════════
// SCHOLARS & SEALS
// ═══════════════════════════════════════════════════════════

/**
 * Get all scholars.
 */
function akjv_scholars(): array {
    return akjv_db()->query("SELECT * FROM akjv_scholars ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all seals.
 */
function akjv_seals(): array {
    return akjv_db()->query("SELECT * FROM akjv_seals ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

// ═══════════════════════════════════════════════════════════
// TESTAMENT LABELS (shared constant)
// ═══════════════════════════════════════════════════════════

function akjv_testament_labels(string $lang = 'en'): array {
    if ($lang === 'fr') return [
        'OT' => 'Ancien Testament',
        'NT' => 'Nouveau Testament',
        'AP' => 'Apocryphes (Restaurés)',
        'EN' => 'Les 14 Livres Choisis (Restaurés)',
    ];
    if ($lang === 'he') return [
        'OT' => 'תנ"ך',
        'NT' => 'הברית החדשה',
        'AP' => 'ספרים חיצוניים (משוחזרים)',
        'EN' => '14 הספרים הנבחרים (משוחזרים)',
    ];
    return [
        'OT' => 'Old Testament',
        'NT' => 'New Testament',
        'AP' => 'Apocrypha (Restored)',
        'EN' => 'The 14 Chosen Books (Restored)',
    ];
}

/**
 * UI strings for the Bible interface.
 */
function akjv_i18n(string $lang = 'en'): array {
    $strings = [
        'en' => [
            'mission_badge' => 'OPERATION DIVINE SCROLL — HIGHEST PRIORITY',
            'hero_title_1' => 'The',
            'hero_title_2' => 'Authorized King Jesus',
            'hero_title_3' => 'Version',
            'perez_edition' => 'PEREZ FAMILY EDITION',
            'truth_concealed' => 'The Truth They Tried to Conceal',
            'authorized_date' => 'OFFICIALLY AUTHORIZED — APRIL 8, 2026 A.D.',
            'read_bible' => 'Read the AKJV Bible',
            'verses' => 'Verses',
            'search_books' => 'Search books...',
            'search_all' => 'Search All Verses',
            'chapters' => 'chapters',
            'coming_soon' => 'Coming Soon',
            'coming_soon_text' => 'The text of <strong>%s</strong> is being prepared by the AKJV scholarly team.',
            'back_dashboard' => 'Back to Mission Dashboard',
            'show_kjv' => 'Show Corrupted Monarchy Text',
            'name_restored' => 'Name Restored',
            'monarchy_text' => 'Monarchy text',
            'previous' => 'Previous',
            'next' => 'Next',
            'of' => 'of',
            'perez_referenced' => 'Perez referenced',
            'sovereign_decree' => 'Sovereign Decree',
            'translation_note' => '',
        ],
        'fr' => [
            'mission_badge' => 'OPÉRATION PARCHEMIN DIVIN — PRIORITÉ ABSOLUE',
            'hero_title_1' => 'La',
            'hero_title_2' => 'Version Autorisée du Roi Jésus',
            'hero_title_3' => '',
            'perez_edition' => 'ÉDITION FAMILIALE PEREZ',
            'truth_concealed' => 'La Vérité Qu\'ils Ont Tenté de Dissimuler',
            'authorized_date' => 'OFFICIELLEMENT AUTORISÉE — 8 AVRIL 2026 APR. J.-C.',
            'read_bible' => 'Lire la Bible AKJV',
            'verses' => 'Versets',
            'search_books' => 'Rechercher des livres...',
            'search_all' => 'Rechercher Tous les Versets',
            'chapters' => 'chapitres',
            'coming_soon' => 'Bientôt Disponible',
            'coming_soon_text' => 'Le texte de <strong>%s</strong> est en cours de préparation par l\'équipe universitaire AKJV.',
            'back_dashboard' => 'Retour au Tableau de Mission',
            'show_kjv' => 'Afficher le Texte Corrompu de la Monarchie',
            'name_restored' => 'Nom Restauré',
            'monarchy_text' => 'Texte monarchique',
            'previous' => 'Précédent',
            'next' => 'Suivant',
            'of' => 'de',
            'perez_referenced' => 'Perez référencé',
            'sovereign_decree' => 'Décret Souverain',
            'translation_note' => '(Traduction française en cours — texte anglais affiché)',
        ],
        'he' => [
            'mission_badge' => 'מבצע מגילה אלוהית — עדיפות עליונה',
            'hero_title_1' => '',
            'hero_title_2' => 'גרסה מורשית של המלך ישוע',
            'hero_title_3' => '',
            'perez_edition' => 'מהדורת משפחת פרץ',
            'truth_concealed' => 'האמת שניסו להסתיר',
            'authorized_date' => 'אושרה רשמית — 8 באפריל 2026 לספירה',
            'read_bible' => 'קרא את תנ"ך AKJV',
            'verses' => 'פסוקים',
            'search_books' => 'חפש ספרים...',
            'search_all' => 'חפש בכל הפסוקים',
            'chapters' => 'פרקים',
            'coming_soon' => 'בקרוב',
            'coming_soon_text' => 'הטקסט של <strong>%s</strong> מוכן על ידי צוות המלומדים של AKJV.',
            'back_dashboard' => 'חזרה ללוח המשימות',
            'show_kjv' => 'הצג טקסט מלוכני מושחת',
            'name_restored' => 'שם שוחזר',
            'monarchy_text' => 'טקסט מלוכני',
            'previous' => 'הקודם',
            'next' => 'הבא',
            'of' => 'מתוך',
            'perez_referenced' => 'פרץ מוזכר',
            'sovereign_decree' => 'צו ריבונות',
            'translation_note' => '(תרגום עברי בהכנה — מוצג טקסט באנגלית)',
        ],
    ];
    return $strings[$lang] ?? $strings['en'];
}

/**
 * Normalize lang string — only allow en/fr/he.
 */
function akjv_lang(string $lang): string {
    return in_array($lang, ['en', 'fr', 'he']) ? $lang : 'en';
}

/**
 * Render a 3-language switcher bar (EN / FR / עב) for Bible pages.
 * Returns HTML string. Uses ?lang= query param.
 */
function akjv_lang_switcher_html(string $currentLang): string {
    $currentLang = akjv_lang($currentLang);
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $langs = [
        'en' => 'EN',
        'fr' => 'FR',
        'he' => 'עב',
    ];
    $html = '<div style="text-align:right;max-width:900px;margin:0 auto .5rem;padding:.5rem 1.5rem 0;display:flex;justify-content:flex-end;gap:6px;">';
    foreach ($langs as $code => $label) {
        $active = $code === $currentLang;
        $style = $active
            ? 'background:rgba(255,215,0,.15);border:1px solid var(--akjv-gold,#ffd700);color:var(--akjv-gold,#ffd700)'
            : 'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(240,240,245,.3)';
        $href = htmlspecialchars($path . '?lang=' . $code);
        $html .= '<a href="' . $href . '" style="padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;text-decoration:none;' . $style . '">' . $label . '</a>';
    }
    $html .= '</div>';
    return $html;
}
