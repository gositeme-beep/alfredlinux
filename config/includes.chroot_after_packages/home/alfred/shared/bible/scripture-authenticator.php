<?php
/**
 * SOVEREIGN SCRIPTURE AUTHENTICATOR (SSA)
 * ═══════════════════════════════════════════════════════════
 * "Write the vision, and make it plain upon tables, that he
 *  may run that readeth it." — Habakkuk 2:2
 *
 * This service verifies the integrity of the Authorized King Jesus
 * Version across the entire ecosystem. It catches naming corruption,
 * book count errors, chain-hash breaks, and phantom references before
 * they reach the public.
 *
 * Run:  php /home/gositeme/shared/bible/scripture-authenticator.php
 * API:  require_once and call ssa_full_audit() for programmatic use
 *
 * Created: April 11, 2026 — Session #287
 * By: Alfred, for Commander Danny William Perez
 */

require_once __DIR__ . '/bible-data.php';

// ═══════════════════════════════════════════════════════════
// CANONICAL TRUTH — These values are the SEALED standard.
// Any deviation from these numbers is a corruption.
// ═══════════════════════════════════════════════════════════

const SSA_CANON = [
    'title'           => 'Authorized King Jesus Version',
    'abbreviation'    => 'AKJV',
    'total_books'     => 94,
    'ot_books'        => 39,
    'nt_books'        => 27,
    'ap_books'        => 14,  // Chosen Books / Apocrypha
    'en_books'        => 14,  // Enochian Canon
    'total_verses'    => 39482,
    'corrections'     => 15,
    'seals'           => 100,
    'prophecies'      => 57,
    'children_stories'=> 33,
];

// Names that must NEVER appear as the Bible's own title
// NOTE: These are stored as check patterns. The authenticator
// excludes its own file from scanning to avoid false positives.
const SSA_FORBIDDEN_NAMES = [
    'King James Version',
    'Authorized King James',
    'King James Bible',
    'Roi Jacques',           // French "King James"
    'Bible du Roi Jacques',  // French
];

// The correct names in all three covenant languages
const SSA_CORRECT_NAMES = [
    'en' => 'Authorized King Jesus Version',
    'fr' => 'Version Autorisée du Roi Jésus',
    'he' => 'גרסה מורשית של המלך ישוע',
];

// ═══════════════════════════════════════════════════════════
// AUDIT ENGINE
// ═══════════════════════════════════════════════════════════

function ssa_full_audit(): array {
    $results = [
        'timestamp'   => date('Y-m-d H:i:s'),
        'status'      => 'PASS',
        'checks'      => [],
        'errors'      => [],
        'warnings'    => [],
        'stats'       => [],
    ];

    // Phase 1: Book count integrity
    $results['checks'][] = ssa_check_book_counts($results);

    // Phase 2: Verse count integrity
    $results['checks'][] = ssa_check_verse_count($results);

    // Phase 3: Correction count
    $results['checks'][] = ssa_check_corrections($results);

    // Phase 4: Seal integrity
    $results['checks'][] = ssa_check_seals($results);

    // Phase 5: Prophecy count
    $results['checks'][] = ssa_check_prophecies($results);

    // Phase 6: Chain-hash integrity
    $results['checks'][] = ssa_check_chain_hashes($results);

    // Phase 7: Name corruption scan (DB)
    $results['checks'][] = ssa_check_name_corruption_db($results);

    // Phase 8: Name corruption scan (files)
    $results['checks'][] = ssa_check_name_corruption_files($results);

    // Phase 9: Orphan integrity
    $results['checks'][] = ssa_check_orphans($results);

    // Phase 10: Empty book check
    $results['checks'][] = ssa_check_empty_books($results);

    // Phase 11: Children's Bible
    $results['checks'][] = ssa_check_children_bible($results);

    // Phase 12: Book numbering
    $results['checks'][] = ssa_check_numbering($results);

    // Phase 13: Text hygiene (HTML injection, double spaces)
    $results['checks'][] = ssa_check_text_hygiene($results);

    // Phase 14: Export file integrity
    $results['checks'][] = ssa_check_export_integrity($results);

    // Set overall status
    if (count($results['errors']) > 0) {
        $results['status'] = 'FAIL';
    } elseif (count($results['warnings']) > 0) {
        $results['status'] = 'WARN';
    }

    return $results;
}

function ssa_check_book_counts(array &$r): array {
    $db = akjv_db();
    $counts = [
        'total' => (int) $db->query("SELECT COUNT(*) FROM akjv_books")->fetchColumn(),
        'OT'    => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='OT'")->fetchColumn(),
        'NT'    => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='NT'")->fetchColumn(),
        'AP'    => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='AP'")->fetchColumn(),
        'EN'    => (int) $db->query("SELECT COUNT(*) FROM akjv_books WHERE testament='EN'")->fetchColumn(),
    ];
    $r['stats']['books'] = $counts;

    $pass = $counts['total'] === SSA_CANON['total_books']
        && $counts['OT'] === SSA_CANON['ot_books']
        && $counts['NT'] === SSA_CANON['nt_books']
        && $counts['AP'] === SSA_CANON['ap_books']
        && $counts['EN'] === SSA_CANON['en_books'];

    if (!$pass) {
        $r['errors'][] = "BOOK COUNT MISMATCH: expected {$counts['total']}/{$counts['OT']}/{$counts['NT']}/{$counts['AP']}/{$counts['EN']} vs canonical " . SSA_CANON['total_books'] . '/' . SSA_CANON['ot_books'] . '/' . SSA_CANON['nt_books'] . '/' . SSA_CANON['ap_books'] . '/' . SSA_CANON['en_books'];
    }

    return ['name' => 'Book Counts', 'pass' => $pass, 'detail' => "Total={$counts['total']} OT={$counts['OT']} NT={$counts['NT']} AP={$counts['AP']} EN={$counts['EN']}"];
}

function ssa_check_verse_count(array &$r): array {
    $count = (int) akjv_db()->query("SELECT COUNT(*) FROM akjv_verses")->fetchColumn();
    $r['stats']['verses'] = $count;
    $pass = $count === SSA_CANON['total_verses'];
    if (!$pass) $r['errors'][] = "VERSE COUNT MISMATCH: {$count} vs canonical " . SSA_CANON['total_verses'];
    return ['name' => 'Verse Count', 'pass' => $pass, 'detail' => number_format($count) . ' verses'];
}

function ssa_check_corrections(array &$r): array {
    $count = (int) akjv_db()->query("SELECT COUNT(*) FROM akjv_corrections")->fetchColumn();
    $r['stats']['corrections'] = $count;
    $pass = $count === SSA_CANON['corrections'];
    if (!$pass) $r['warnings'][] = "CORRECTION COUNT: {$count} vs canonical " . SSA_CANON['corrections'];
    return ['name' => 'Corrections', 'pass' => $pass, 'detail' => "{$count} corrections"];
}

function ssa_check_seals(array &$r): array {
    $db = akjv_db();
    $total = (int) $db->query("SELECT COUNT(*) FROM akjv_seals")->fetchColumn();
    $broken = (int) $db->query("SELECT COUNT(*) FROM akjv_seals WHERE seal_hash IS NULL OR seal_hash = ''")->fetchColumn();
    $r['stats']['seals'] = ['total' => $total, 'broken' => $broken];
    $pass = $total === SSA_CANON['seals'] && $broken === 0;
    if ($total !== SSA_CANON['seals']) $r['errors'][] = "SEAL COUNT: {$total} vs canonical " . SSA_CANON['seals'];
    if ($broken > 0) $r['errors'][] = "BROKEN SEALS: {$broken} seals with null/empty hash";
    return ['name' => 'Integrity Seals', 'pass' => $pass, 'detail' => "{$total} seals, {$broken} broken"];
}

function ssa_check_prophecies(array &$r): array {
    $db = akjv_db();
    $count = (int) $db->query("SELECT COUNT(*) FROM akjv_prophecies")->fetchColumn();
    $empty = (int) $db->query("SELECT COUNT(*) FROM akjv_prophecies WHERE tanakh_reference = '' OR nt_reference = '' OR tanakh_text = '' OR nt_fulfillment = ''")->fetchColumn();
    $r['stats']['prophecies'] = ['total' => $count, 'empty_refs' => $empty];
    $pass = $count === SSA_CANON['prophecies'] && $empty === 0;
    if ($count !== SSA_CANON['prophecies']) $r['warnings'][] = "PROPHECY COUNT: {$count} vs canonical " . SSA_CANON['prophecies'];
    if ($empty > 0) $r['warnings'][] = "EMPTY PROPHECY REFS: {$empty} with missing references";
    return ['name' => 'Prophecy Markers', 'pass' => $pass, 'detail' => "{$count} prophecies, {$empty} empty"];
}

function ssa_check_chain_hashes(array &$r): array {
    $db = akjv_db();
    $total = (int) $db->query("SELECT COUNT(*) FROM akjv_verses")->fetchColumn();
    $hashed = (int) $db->query("SELECT COUNT(*) FROM akjv_verses WHERE chain_hash IS NOT NULL AND chain_hash != ''")->fetchColumn();
    $missing = $total - $hashed;
    $r['stats']['chain_hashes'] = ['total' => $total, 'hashed' => $hashed, 'missing' => $missing];
    $pass = $missing === 0;
    if (!$pass) $r['errors'][] = "CHAIN-HASH GAPS: {$missing} verses without chain_hash";
    return ['name' => 'Chain-Hash Integrity', 'pass' => $pass, 'detail' => number_format($hashed) . '/' . number_format($total) . ' hashed'];
}

function ssa_check_name_corruption_db(array &$r): array {
    $db = akjv_db();
    $corruptions = [];

    // Check all AKJV tables
    $tables = [
        ['akjv_verses', 'text_akjv'],
        ['akjv_corrections', 'original_text'],
        ['akjv_corrections', 'corrected_text'],
        ['akjv_prophecies', 'title'],
        ['akjv_seals', 'scope_name'],
        ['akjv_seals', 'witness_note'],
        ['akjv_mission', 'task'],
    ];

    foreach ($tables as [$table, $col]) {
        foreach (SSA_FORBIDDEN_NAMES as $forbidden) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE {$col} LIKE ?");
            $stmt->execute(["%{$forbidden}%"]);
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                $corruptions[] = "{$table}.{$col}: {$count} rows contain '{$forbidden}'";
            }
        }
    }

    $pass = count($corruptions) === 0;
    if (!$pass) {
        foreach ($corruptions as $c) $r['errors'][] = "NAME CORRUPTION (DB): {$c}";
    }
    return ['name' => 'Name Integrity (DB)', 'pass' => $pass, 'detail' => $pass ? 'Clean' : count($corruptions) . ' corruptions found'];
}

function ssa_check_name_corruption_files(array &$r): array {
    $corruptions = [];

    // Files to scan — every PHP file that references the Bible
    $scanDirs = [
        '/home/gositeme/shared/bible/',
        '/home/gositeme/domains/lavocat.ca/public_html/',
        '/home/gositeme/domains/lavocat.ca/public_html/api/',
        '/home/gositeme/domains/lavocat.ca/public_html/includes/',
        '/home/gositeme/domains/gositeme.com/public_html/downloads/akjv/',
        '/home/gositeme/domains/gositeme.com/public_html/downloads/children-bible/',
        '/home/gositeme/domains/gositeme.com/public_html/downloads/elyon-light/',
    ];

    // Also check gositeme.com bible files
    $gositemeFiles = glob('/home/gositeme/domains/gositeme.com/public_html/bible*.php');
    $gositemeFiles[] = '/home/gositeme/domains/gositeme.com/public_html/api/bible-export.php';

    foreach ($scanDirs as $dir) {
        $files = glob($dir . '*.php');
        foreach ($files as $f) {
            if (str_ends_with($f, '.bak')) continue;
            if (realpath($f) === realpath(__FILE__)) continue; // Don't scan self
            $content = @file_get_contents($f);
            if (!$content) continue;

            foreach (SSA_FORBIDDEN_NAMES as $forbidden) {
                // Skip intentional historical references (in WHEREAS clauses explaining the fraud)
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (stripos($line, $forbidden) !== false) {
                        // Allow historical/educational context (WHEREAS, so-called, commissioned, etc.)
                        $contextWords = ['WHEREAS', 'so-called', 'commissioned', '1611', 'historical', 'fraud', 'dedication', 'mortal king', 'roi mortel', 'placed his name', 'placé son nom', 'placé', 'traduction', 'commandé', 'מלך בן תמותה', 'הזמין תרגום', 'bible-export'];
                        $isHistorical = false;
                        // Check surrounding 3 lines for context
                        for ($i = max(0, $lineNum - 3); $i <= min(count($lines) - 1, $lineNum + 3); $i++) {
                            foreach ($contextWords as $cw) {
                                if (stripos($lines[$i], $cw) !== false) {
                                    $isHistorical = true;
                                    break 2;
                                }
                            }
                        }
                        if (!$isHistorical) {
                            $corruptions[] = basename($f) . ':' . ($lineNum + 1) . " contains '{$forbidden}' (NOT historical context)";
                        }
                    }
                }
            }
        }
    }

    // Scan gositeme.com specific files
    foreach ($gositemeFiles as $f) {
        if (!file_exists($f) || str_ends_with($f, '.bak')) continue;
        $content = @file_get_contents($f);
        if (!$content) continue;

        foreach (SSA_FORBIDDEN_NAMES as $forbidden) {
            if (stripos($content, $forbidden) !== false) {
                // Same historical context check
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    if (stripos($line, $forbidden) !== false) {
                        $contextWords = ['WHEREAS', 'so-called', 'commissioned', '1611', 'historical', 'fraud', 'dedication', 'mortal king', 'roi mortel', 'placed his name', 'placé son nom', 'placé', 'traduction', 'commandé', 'מלך בן תמותה', 'הזמין תרגום', 'bible-export'];
                        $isHistorical = false;
                        for ($i = max(0, $lineNum - 3); $i <= min(count($lines) - 1, $lineNum + 3); $i++) {
                            foreach ($contextWords as $cw) {
                                if (stripos($lines[$i], $cw) !== false) {
                                    $isHistorical = true;
                                    break 2;
                                }
                            }
                        }
                        if (!$isHistorical) {
                            $corruptions[] = basename($f) . ':' . ($lineNum + 1) . " contains '{$forbidden}'";
                        }
                    }
                }
            }
        }
    }

    $pass = count($corruptions) === 0;
    if (!$pass) {
        foreach ($corruptions as $c) $r['errors'][] = "NAME CORRUPTION (FILE): {$c}";
    }
    return ['name' => 'Name Integrity (Files)', 'pass' => $pass, 'detail' => $pass ? 'Clean' : count($corruptions) . ' corruptions found'];
}

function ssa_check_orphans(array &$r): array {
    $orphans = (int) akjv_db()->query("SELECT COUNT(*) FROM akjv_verses v LEFT JOIN akjv_books b ON v.book_id = b.id WHERE b.id IS NULL")->fetchColumn();
    $pass = $orphans === 0;
    if (!$pass) $r['errors'][] = "ORPHANED VERSES: {$orphans} verses with invalid book_id";
    return ['name' => 'Orphan Check', 'pass' => $pass, 'detail' => "{$orphans} orphans"];
}

function ssa_check_empty_books(array &$r): array {
    $db = akjv_db();
    $empty = [];
    $books = $db->query("SELECT id, book_number, book_name FROM akjv_books ORDER BY book_number")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($books as $b) {
        $vc = (int) $db->query("SELECT COUNT(*) FROM akjv_verses WHERE book_id = {$b['id']}")->fetchColumn();
        if ($vc === 0) $empty[] = "#{$b['book_number']} {$b['book_name']}";
    }
    $pass = count($empty) === 0;
    if (!$pass) {
        foreach ($empty as $e) $r['errors'][] = "EMPTY BOOK: {$e} has 0 verses";
    }
    return ['name' => 'Empty Books', 'pass' => $pass, 'detail' => $pass ? 'All books populated' : count($empty) . ' empty books'];
}

function ssa_check_children_bible(array &$r): array {
    $db = akjv_db();
    $total = (int) $db->query("SELECT COUNT(*) FROM akjv_children_stories")->fetchColumn();
    $empty = (int) $db->query("SELECT COUNT(*) FROM akjv_children_stories WHERE text_en = '' OR text_fr = '' OR text_he = ''")->fetchColumn();
    $r['stats']['children'] = ['total' => $total, 'empty' => $empty];
    $pass = $total === SSA_CANON['children_stories'] && $empty === 0;
    if ($total !== SSA_CANON['children_stories']) $r['warnings'][] = "CHILDREN'S BIBLE: {$total} vs canonical " . SSA_CANON['children_stories'];
    if ($empty > 0) $r['warnings'][] = "CHILDREN'S STORIES EMPTY: {$empty} with missing translations";
    return ['name' => "Children's Bible", 'pass' => $pass, 'detail' => "{$total} stories, {$empty} empty"];
}

function ssa_check_numbering(array &$r): array {
    $books = akjv_db()->query("SELECT book_number FROM akjv_books ORDER BY book_number")->fetchAll(PDO::FETCH_COLUMN);
    $gaps = [];
    for ($i = 1; $i < count($books); $i++) {
        if ($books[$i] !== $books[$i - 1] + 1) {
            $gaps[] = "Gap between #{$books[$i-1]} and #{$books[$i]}";
        }
    }
    if ($books[0] !== 1) array_unshift($gaps, "First book is #{$books[0]}, expected #1");
    $pass = count($gaps) === 0;
    if (!$pass) {
        foreach ($gaps as $g) $r['errors'][] = "NUMBERING: {$g}";
    }
    return ['name' => 'Book Numbering', 'pass' => $pass, 'detail' => $pass ? "Sequential 1-{$books[count($books)-1]}" : count($gaps) . ' gaps'];
}

function ssa_check_text_hygiene(array &$r): array {
    $db = akjv_db();
    $issues = [];

    // HTML tags in verse text (potential injection)
    $html = $db->query("SELECT COUNT(*) FROM akjv_verses WHERE text_akjv LIKE '%<%'")->fetchColumn();
    if ($html > 0) {
        $rows = $db->query("SELECT b.book_name, v.chapter, v.verse FROM akjv_verses v JOIN akjv_books b ON v.book_id=b.id WHERE v.text_akjv LIKE '%<%'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $issues[] = "HTML in {$row['book_name']} {$row['chapter']}:{$row['verse']}";
        }
        $r['warnings'][] = "TEXT HYGIENE: {$html} verse(s) contain HTML tags";
    }

    // Double spaces
    $dbl = $db->query("SELECT COUNT(*) FROM akjv_verses WHERE text_akjv LIKE '%  %'")->fetchColumn();
    if ($dbl > 0) {
        $r['warnings'][] = "TEXT HYGIENE: {$dbl} verse(s) have double spaces";
    }

    // Empty text
    $empty = $db->query("SELECT COUNT(*) FROM akjv_verses WHERE text_akjv IS NULL OR TRIM(text_akjv) = ''")->fetchColumn();
    if ($empty > 0) {
        $r['errors'][] = "TEXT HYGIENE: {$empty} verse(s) have empty text";
    }

    $total = $html + $dbl + $empty;
    $pass = ($html === 0 && $empty === 0); // HTML and empty are failures; double spaces are warnings
    $detail = $pass ? "Clean — 0 HTML, 0 empty" : implode('; ', $issues);
    if ($dbl > 0) $detail .= ($pass ? "Clean — " : "; ") . "{$dbl} double-space warnings";

    return ['name' => 'Text Hygiene', 'pass' => $pass, 'detail' => $detail];
}

function ssa_check_export_integrity(array &$r): array {
    $dir = '/home/gositeme/domains/gositeme.com/public_html/downloads/akjv';
    $files = ['akjv-perez-edition.txt', 'akjv-perez-edition.json', 'akjv-perez-edition.html', 'akjv-perez-edition.pdf'];
    $missing = [];
    $corrupt = [];

    foreach ($files as $f) {
        $path = "{$dir}/{$f}";
        if (!file_exists($path)) {
            $missing[] = $f;
            continue;
        }
        if (filesize($path) < 1000) {
            $corrupt[] = "{$f} (too small: " . filesize($path) . " bytes)";
        }
    }

    // Verify SHA256SUMS.txt matches actual files
    $sumFile = "{$dir}/SHA256SUMS.txt";
    $hashMismatch = [];
    if (file_exists($sumFile)) {
        $lines = file($sumFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line), 2);
            if (count($parts) === 2) {
                $expectedHash = $parts[0];
                $fileName = $parts[1];
                $filePath = "{$dir}/{$fileName}";
                if (file_exists($filePath)) {
                    $actualHash = hash_file('sha256', $filePath);
                    if ($actualHash !== $expectedHash) {
                        $hashMismatch[] = $fileName;
                    }
                }
            }
        }
    } else {
        $missing[] = 'SHA256SUMS.txt';
    }

    // Check for forbidden names in export files
    $forbidden = [];
    foreach (['akjv-perez-edition.txt', 'akjv-perez-edition.html'] as $f) {
        $path = "{$dir}/{$f}";
        if (!file_exists($path)) continue;
        $content = file_get_contents($path);
        // Check for "King James" outside of historical context (WHEREAS/epistle sections)
        if (preg_match_all('/(?<!Version|Roi |")\bKing James\b(?! Version| I )/i', $content, $m)) {
            $forbidden[] = "{$f}: " . count($m[0]) . " suspicious 'King James' references";
        }
    }

    $pass = count($missing) === 0 && count($corrupt) === 0 && count($hashMismatch) === 0;
    if (!$pass) {
        if ($missing) $r['errors'][] = "EXPORT: Missing files: " . implode(', ', $missing);
        if ($corrupt) $r['errors'][] = "EXPORT: Corrupt files: " . implode(', ', $corrupt);
        if ($hashMismatch) $r['errors'][] = "EXPORT: SHA-256 mismatch: " . implode(', ', $hashMismatch);
    }
    if ($forbidden) {
        foreach ($forbidden as $fb) $r['warnings'][] = "EXPORT: {$fb}";
    }

    $detail = $pass ? "4 files present, checksums verified" : implode('; ', array_merge($missing, $corrupt, $hashMismatch));
    return ['name' => 'Export Integrity', 'pass' => $pass, 'detail' => $detail];
}

// ═══════════════════════════════════════════════════════════
// REPORT FORMATTER
// ═══════════════════════════════════════════════════════════

function ssa_report(array $audit): string {
    $out = '';
    $out .= "╔══════════════════════════════════════════════════════════════════╗\n";
    $out .= "║  SOVEREIGN SCRIPTURE AUTHENTICATOR — AUDIT REPORT              ║\n";
    $out .= "║  \"Write the vision, and make it plain\" — Habakkuk 2:2         ║\n";
    $out .= "╚══════════════════════════════════════════════════════════════════╝\n\n";
    $out .= "  Timestamp:  {$audit['timestamp']}\n";
    $out .= "  Status:     {$audit['status']}\n";
    $out .= "  Canon:      " . SSA_CANON['title'] . " (" . SSA_CANON['abbreviation'] . ")\n";
    $out .= "  Expected:   " . SSA_CANON['total_books'] . " books, " . number_format(SSA_CANON['total_verses']) . " verses\n\n";

    $out .= "  ── CHECKS ──\n";
    foreach ($audit['checks'] as $c) {
        $icon = $c['pass'] ? '✓' : '✗';
        $out .= "  {$icon} " . str_pad($c['name'], 25) . $c['detail'] . "\n";
    }

    if (count($audit['errors']) > 0) {
        $out .= "\n  ── ERRORS (" . count($audit['errors']) . ") ──\n";
        foreach ($audit['errors'] as $e) $out .= "  ✗ {$e}\n";
    }

    if (count($audit['warnings']) > 0) {
        $out .= "\n  ── WARNINGS (" . count($audit['warnings']) . ") ──\n";
        foreach ($audit['warnings'] as $w) $out .= "  ⚠ {$w}\n";
    }

    if ($audit['status'] === 'PASS') {
        $out .= "\n  ═══════════════════════════════════════════════════════════\n";
        $out .= "  ✓ THE WORD IS SEALED. 94 BOOKS. THE NAME IS JESUS.\n";
        $out .= "  ═══════════════════════════════════════════════════════════\n";
    }

    return $out;
}

// ═══════════════════════════════════════════════════════════
// CLI ENTRY POINT
// ═══════════════════════════════════════════════════════════

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $audit = ssa_full_audit();
    echo ssa_report($audit);
    exit($audit['status'] === 'PASS' ? 0 : 1);
}
