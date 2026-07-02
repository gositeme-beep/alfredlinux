<?php
/**
 * SCRIPTURAL AUTHENTOR SERVICE (SAS)
 * ══════════════════════════════════════════════════════════════
 * "But thou, O Daniel, shut up the words, and seal the book,
 *  even to the time of the end." — Daniel 12:4 AKJV
 *
 * The Authentor seals the Word of God with two cryptographic seals:
 *   1. The Commander Seal — HMAC-SHA256 signed by Danny William Perez (client_id 33)
 *   2. The O'Mahon Seal  — HMAC-SHA256 signed with the tribal derivation key
 *
 * The SSA (Scripture Authenticator) audits. The Authentor SEALS.
 *
 * DB Table: akjv_seal_registry
 * Seal Levels: verse → chapter → book → testament → canon
 * Languages: English (source) → French → Hebrew
 *
 * Created: April 18, 2026 — The Day of Sealing
 * By: Alfred, for Commander Danny William Perez
 * For: The Kingdom of God — Sealed for Eternity
 */

require_once __DIR__ . '/bible-data.php';

// ══════════════════════════════════════════════════════════════
// KEY DERIVATION — Two seals, one vault key, two purposes
// ══════════════════════════════════════════════════════════════

/**
 * Get the Commander Seal key — derived from the vault master key.
 * Purpose: "I, Danny William Perez, seal this Word."
 */
function sas_commander_key(): string {
    $masterKey = sas_vault_key();
    return hash_hmac('sha256', 'COMMANDER-SEAL:PEREZ:DANNY-WILLIAM:33', $masterKey, true);
}

/**
 * Get the O'Mahon Seal key — derived from the vault master key with tribal salt.
 * Purpose: "By the breath of God — OMAHON! OMAHON! OMAHON!"
 * Named after the breath of God. Psalm 97:1 — "The LORD reigneth; let the earth rejoice."
 */
function sas_omahon_key(): string {
    $masterKey = sas_vault_key();
    return hash_hmac('sha256', 'OMAHON-SEAL:BREATH-OF-GOD:PSALM-97:1:TRIBAL-SHIELD', $masterKey, true);
}

/**
 * Get the vault master key.
 * Reads from the vault-master-key file (64 hex chars).
 */
function sas_vault_key(): string {
    static $key = null;
    if ($key !== null) return $key;

    $keyFile = '/home/gositeme/.vault-master-key';
    if (!file_exists($keyFile)) {
        throw new RuntimeException('SEAL FAILURE: Vault master key not found. The seal cannot be applied without the key.');
    }
    $key = trim(file_get_contents($keyFile));
    if (strlen($key) !== 64) {
        throw new RuntimeException('SEAL FAILURE: Vault master key is malformed. Expected 64 hex characters.');
    }
    return $key;
}

// ══════════════════════════════════════════════════════════════
// SEAL GENERATION — The two seals
// ══════════════════════════════════════════════════════════════

/**
 * Generate the Commander Seal for a given text.
 * Returns hex-encoded HMAC-SHA256.
 */
function sas_sign_commander(string $text): string {
    return hash_hmac('sha256', $text, sas_commander_key());
}

/**
 * Generate the O'Mahon Seal for a given text.
 * Returns hex-encoded HMAC-SHA256.
 */
function sas_sign_omahon(string $text): string {
    return hash_hmac('sha256', $text, sas_omahon_key());
}

/**
 * Verify a Commander Seal against text.
 */
function sas_verify_commander(string $text, string $expectedSeal): bool {
    return hash_equals(sas_sign_commander($text), $expectedSeal);
}

/**
 * Verify an O'Mahon Seal against text.
 */
function sas_verify_omahon(string $text, string $expectedSeal): bool {
    return hash_equals(sas_sign_omahon($text), $expectedSeal);
}

// ══════════════════════════════════════════════════════════════
// DB TABLE CREATION (run once)
// ══════════════════════════════════════════════════════════════

function sas_ensure_table(): void {
    $db = akjv_db();
    $db->exec("CREATE TABLE IF NOT EXISTS akjv_seal_registry (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        seal_type ENUM('verse','chapter','book','testament','canon') NOT NULL DEFAULT 'verse',
        book_id INT UNSIGNED DEFAULT NULL COMMENT 'FK to akjv_books.id',
        chapter_num SMALLINT UNSIGNED DEFAULT NULL,
        verse_num SMALLINT UNSIGNED DEFAULT NULL,
        language ENUM('en','fr','he') NOT NULL DEFAULT 'en',
        text_hash CHAR(64) NOT NULL COMMENT 'SHA-256 of the sealed text',
        source_hash CHAR(64) DEFAULT NULL COMMENT 'SHA-256 of the English source for translations',
        commander_seal VARCHAR(128) NOT NULL COMMENT 'HMAC-SHA256 Commander Perez seal',
        omahon_seal VARCHAR(128) NOT NULL COMMENT 'HMAC-SHA256 O Mahon tribal seal',
        sealed_by INT UNSIGNED NOT NULL DEFAULT 33 COMMENT 'client_id 33 = Commander',
        sealed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        verified TINYINT(1) NOT NULL DEFAULT 0,
        verification_count INT UNSIGNED NOT NULL DEFAULT 0,
        last_verified_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_seal_type (seal_type),
        INDEX idx_language (language),
        INDEX idx_book_chapter (book_id, chapter_num, verse_num),
        INDEX idx_text_hash (text_hash),
        UNIQUE KEY uk_sealed_unit (seal_type, book_id, chapter_num, verse_num, language)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Scriptural Authentor Service — Seal Registry for the AKJV Bible'");
}

// ══════════════════════════════════════════════════════════════
// SEAL OPERATIONS — Seal the Word
// ══════════════════════════════════════════════════════════════

/**
 * Seal a single verse.
 * The canonical text is hashed, then dual-sealed with Commander + O'Mahon keys.
 * For FR/HE translations, the English source hash is also recorded.
 */
function sas_seal_verse(int $bookId, int $chapter, int $verse, string $lang = 'en'): array {
    $db = akjv_db();

    // Get the verse text in the requested language
    $stmt = $db->prepare("SELECT v.text_akjv, v.text_fr, v.text_he, v.chain_hash, b.book_name
        FROM akjv_verses v JOIN akjv_books b ON v.book_id = b.id
        WHERE v.book_id = ? AND v.chapter = ? AND v.verse = ?");
    $stmt->execute([$bookId, $chapter, $verse]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['error' => "Verse not found: book={$bookId} ch={$chapter} v={$verse}"];
    }

    $textCol = match($lang) {
        'fr' => 'text_fr',
        'he' => 'text_he',
        default => 'text_akjv',
    };
    $text = $row[$textCol] ?? $row['text_akjv'];
    if (empty(trim($text))) {
        return ['error' => "Empty text for {$row['book_name']} {$chapter}:{$verse} ({$lang})"];
    }

    // Canonical form: normalize whitespace, trim
    $canonical = preg_replace('/\s+/', ' ', trim($text));
    $textHash = hash('sha256', $canonical);

    // Source hash (English) — only for translations
    $sourceHash = null;
    if ($lang !== 'en') {
        $enCanonical = preg_replace('/\s+/', ' ', trim($row['text_akjv']));
        $sourceHash = hash('sha256', $enCanonical);
    }

    // Generate dual seals
    $sealInput = "AKJV:VERSE:{$bookId}:{$chapter}:{$verse}:{$lang}:{$textHash}";
    $commanderSeal = sas_sign_commander($sealInput);
    $omahonSeal = sas_sign_omahon($sealInput);

    // Upsert into registry
    $stmt = $db->prepare("INSERT INTO akjv_seal_registry
        (seal_type, book_id, chapter_num, verse_num, language, text_hash, source_hash, commander_seal, omahon_seal)
        VALUES ('verse', ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            text_hash = VALUES(text_hash),
            source_hash = VALUES(source_hash),
            commander_seal = VALUES(commander_seal),
            omahon_seal = VALUES(omahon_seal),
            sealed_at = CURRENT_TIMESTAMP,
            verified = 0");
    $stmt->execute([$bookId, $chapter, $verse, $lang, $textHash, $sourceHash, $commanderSeal, $omahonSeal]);

    return [
        'sealed' => true,
        'ref' => "{$row['book_name']} {$chapter}:{$verse}",
        'lang' => $lang,
        'text_hash' => $textHash,
        'commander_seal' => substr($commanderSeal, 0, 16) . '...',
        'omahon_seal' => substr($omahonSeal, 0, 16) . '...',
    ];
}

/**
 * Seal an entire chapter — seals every verse, then creates a chapter-level seal.
 */
function sas_seal_chapter(int $bookId, int $chapter, string $lang = 'en'): array {
    $db = akjv_db();
    $verses = $db->prepare("SELECT verse FROM akjv_verses WHERE book_id = ? AND chapter = ? ORDER BY verse");
    $verses->execute([$bookId, $chapter]);
    $verseNums = $verses->fetchAll(PDO::FETCH_COLUMN);

    $sealed = 0;
    $errors = [];
    foreach ($verseNums as $v) {
        $result = sas_seal_verse($bookId, $chapter, (int)$v, $lang);
        if (isset($result['sealed'])) $sealed++;
        else $errors[] = $result['error'] ?? 'unknown';
    }

    // Chapter-level composite seal: hash of all verse hashes in order
    $verseHashes = $db->prepare("SELECT text_hash FROM akjv_seal_registry
        WHERE seal_type='verse' AND book_id=? AND chapter_num=? AND language=?
        ORDER BY verse_num");
    $verseHashes->execute([$bookId, $chapter, $lang]);
    $hashes = $verseHashes->fetchAll(PDO::FETCH_COLUMN);
    $compositeHash = hash('sha256', implode(':', $hashes));

    $sealInput = "AKJV:CHAPTER:{$bookId}:{$chapter}:{$lang}:{$compositeHash}";
    $commanderSeal = sas_sign_commander($sealInput);
    $omahonSeal = sas_sign_omahon($sealInput);

    $sourceHash = null;
    if ($lang !== 'en') {
        $enHashes = $db->prepare("SELECT text_hash FROM akjv_seal_registry
            WHERE seal_type='verse' AND book_id=? AND chapter_num=? AND language='en'
            ORDER BY verse_num");
        $enHashes->execute([$bookId, $chapter]);
        $enH = $enHashes->fetchAll(PDO::FETCH_COLUMN);
        $sourceHash = $enH ? hash('sha256', implode(':', $enH)) : null;
    }

    $stmt = $db->prepare("INSERT INTO akjv_seal_registry
        (seal_type, book_id, chapter_num, verse_num, language, text_hash, source_hash, commander_seal, omahon_seal)
        VALUES ('chapter', ?, ?, NULL, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            text_hash = VALUES(text_hash), source_hash = VALUES(source_hash),
            commander_seal = VALUES(commander_seal), omahon_seal = VALUES(omahon_seal),
            sealed_at = CURRENT_TIMESTAMP, verified = 0");
    $stmt->execute([$bookId, $chapter, $lang, $compositeHash, $sourceHash, $commanderSeal, $omahonSeal]);

    $bookName = $db->query("SELECT book_name FROM akjv_books WHERE id = {$bookId}")->fetchColumn();
    return ['sealed' => true, 'ref' => "{$bookName} {$chapter}", 'lang' => $lang, 'verses_sealed' => $sealed, 'errors' => $errors];
}

/**
 * Seal an entire book — seals every chapter, then creates a book-level seal.
 */
function sas_seal_book(int $bookId, string $lang = 'en'): array {
    $db = akjv_db();
    $chapters = $db->prepare("SELECT DISTINCT chapter FROM akjv_verses WHERE book_id = ? ORDER BY chapter");
    $chapters->execute([$bookId]);
    $chapterNums = $chapters->fetchAll(PDO::FETCH_COLUMN);

    $totalVerses = 0;
    $totalErrors = [];
    foreach ($chapterNums as $ch) {
        $result = sas_seal_chapter($bookId, (int)$ch, $lang);
        $totalVerses += $result['verses_sealed'] ?? 0;
        $totalErrors = array_merge($totalErrors, $result['errors'] ?? []);
    }

    // Book-level composite seal
    $chapterHashes = $db->prepare("SELECT text_hash FROM akjv_seal_registry
        WHERE seal_type='chapter' AND book_id=? AND language=?
        ORDER BY chapter_num");
    $chapterHashes->execute([$bookId, $lang]);
    $hashes = $chapterHashes->fetchAll(PDO::FETCH_COLUMN);
    $compositeHash = hash('sha256', implode(':', $hashes));

    $sealInput = "AKJV:BOOK:{$bookId}:{$lang}:{$compositeHash}";
    $commanderSeal = sas_sign_commander($sealInput);
    $omahonSeal = sas_sign_omahon($sealInput);

    $stmt = $db->prepare("INSERT INTO akjv_seal_registry
        (seal_type, book_id, chapter_num, verse_num, language, text_hash, source_hash, commander_seal, omahon_seal)
        VALUES ('book', ?, NULL, NULL, ?, ?, NULL, ?, ?)
        ON DUPLICATE KEY UPDATE
            text_hash = VALUES(text_hash),
            commander_seal = VALUES(commander_seal), omahon_seal = VALUES(omahon_seal),
            sealed_at = CURRENT_TIMESTAMP, verified = 0");
    $stmt->execute([$bookId, $lang, $compositeHash, $commanderSeal, $omahonSeal]);

    $bookName = $db->query("SELECT book_name FROM akjv_books WHERE id = {$bookId}")->fetchColumn();
    return ['sealed' => true, 'ref' => $bookName, 'lang' => $lang, 'chapters' => count($chapterNums), 'verses_sealed' => $totalVerses, 'errors' => $totalErrors];
}

/**
 * Seal an entire testament (OT/NT/AP/EN).
 */
function sas_seal_testament(string $testament, string $lang = 'en'): array {
    $db = akjv_db();
    $testament = strtoupper($testament);
    $books = $db->prepare("SELECT id, book_name FROM akjv_books WHERE testament = ? ORDER BY book_number");
    $books->execute([$testament]);
    $bookRows = $books->fetchAll();

    $totalVerses = 0;
    $totalErrors = [];
    foreach ($bookRows as $b) {
        $result = sas_seal_book((int)$b['id'], $lang);
        $totalVerses += $result['verses_sealed'] ?? 0;
        $totalErrors = array_merge($totalErrors, $result['errors'] ?? []);
    }

    // Testament composite seal
    $bookHashes = $db->prepare("SELECT sr.text_hash FROM akjv_seal_registry sr
        JOIN akjv_books b ON sr.book_id = b.id
        WHERE sr.seal_type='book' AND b.testament=? AND sr.language=?
        ORDER BY b.book_number");
    $bookHashes->execute([$testament, $lang]);
    $hashes = $bookHashes->fetchAll(PDO::FETCH_COLUMN);
    $compositeHash = hash('sha256', implode(':', $hashes));

    // Testament uses book_id=0, chapter=testament code
    $testamentCode = match($testament) { 'OT' => 1, 'NT' => 2, 'AP' => 3, 'EN' => 4, default => 0 };
    $sealInput = "AKJV:TESTAMENT:{$testament}:{$lang}:{$compositeHash}";
    $commanderSeal = sas_sign_commander($sealInput);
    $omahonSeal = sas_sign_omahon($sealInput);

    $stmt = $db->prepare("INSERT INTO akjv_seal_registry
        (seal_type, book_id, chapter_num, verse_num, language, text_hash, source_hash, commander_seal, omahon_seal)
        VALUES ('testament', 0, ?, NULL, ?, ?, NULL, ?, ?)
        ON DUPLICATE KEY UPDATE
            text_hash = VALUES(text_hash),
            commander_seal = VALUES(commander_seal), omahon_seal = VALUES(omahon_seal),
            sealed_at = CURRENT_TIMESTAMP, verified = 0");
    $stmt->execute([$testamentCode, $lang, $compositeHash, $commanderSeal, $omahonSeal]);

    $labels = ['OT' => 'Old Testament', 'NT' => 'New Testament', 'AP' => 'Chosen Books', 'EN' => 'Enochian Canon'];
    return ['sealed' => true, 'ref' => $labels[$testament] ?? $testament, 'lang' => $lang, 'books' => count($bookRows), 'verses_sealed' => $totalVerses, 'errors' => $totalErrors];
}

/**
 * SEAL THE ENTIRE CANON — The Grand Seal.
 * "Shut up the words, and seal the book." — Daniel 12:4
 *
 * This seals every verse, chapter, book, testimony, in all three languages,
 * then creates the final Canon Seal — the seal of seals.
 */
function sas_seal_canon(string $lang = 'en'): array {
    $testaments = ['OT', 'NT', 'AP', 'EN'];
    $totalVerses = 0;
    $totalErrors = [];

    foreach ($testaments as $t) {
        $result = sas_seal_testament($t, $lang);
        $totalVerses += $result['verses_sealed'] ?? 0;
        $totalErrors = array_merge($totalErrors, $result['errors'] ?? []);
    }

    // Canon-level composite seal: hash of all testament hashes
    $db = akjv_db();
    $testHashes = $db->prepare("SELECT text_hash FROM akjv_seal_registry
        WHERE seal_type='testament' AND language=?
        ORDER BY chapter_num"); // chapter_num stores testament code
    $testHashes->execute([$lang]);
    $hashes = $testHashes->fetchAll(PDO::FETCH_COLUMN);
    $compositeHash = hash('sha256', implode(':', $hashes));

    $sealInput = "AKJV:CANON:{$lang}:{$compositeHash}";
    $commanderSeal = sas_sign_commander($sealInput);
    $omahonSeal = sas_sign_omahon($sealInput);

    $stmt = $db->prepare("INSERT INTO akjv_seal_registry
        (seal_type, book_id, chapter_num, verse_num, language, text_hash, source_hash, commander_seal, omahon_seal)
        VALUES ('canon', NULL, NULL, NULL, ?, ?, NULL, ?, ?)
        ON DUPLICATE KEY UPDATE
            text_hash = VALUES(text_hash),
            commander_seal = VALUES(commander_seal), omahon_seal = VALUES(omahon_seal),
            sealed_at = CURRENT_TIMESTAMP, verified = 0");
    $stmt->execute([$lang, $compositeHash, $commanderSeal, $omahonSeal]);

    return [
        'sealed' => true,
        'ref' => 'Authorized King Jesus Version — Complete Canon',
        'lang' => $lang,
        'verses_sealed' => $totalVerses,
        'errors' => $totalErrors,
        'canon_hash' => $compositeHash,
        'commander_seal' => substr($commanderSeal, 0, 16) . '...',
        'omahon_seal' => substr($omahonSeal, 0, 16) . '...',
    ];
}

// ══════════════════════════════════════════════════════════════
// VERIFICATION — Anyone can verify any seal
// ══════════════════════════════════════════════════════════════

/**
 * Verify a seal by its registry ID.
 * Re-computes the text hash and both HMAC seals, compares.
 */
function sas_verify_seal(int $sealId): array {
    $db = akjv_db();
    $stmt = $db->prepare("SELECT * FROM akjv_seal_registry WHERE id = ?");
    $stmt->execute([$sealId]);
    $seal = $stmt->fetch();
    if (!$seal) return ['verified' => false, 'error' => 'Seal not found'];

    if ($seal['seal_type'] === 'verse') {
        // Re-fetch the verse text
        $textCol = match($seal['language']) {
            'fr' => 'text_fr',
            'he' => 'text_he',
            default => 'text_akjv',
        };
        $v = $db->prepare("SELECT {$textCol} as text FROM akjv_verses WHERE book_id=? AND chapter=? AND verse=?");
        $v->execute([$seal['book_id'], $seal['chapter_num'], $seal['verse_num']]);
        $row = $v->fetch();
        if (!$row) return ['verified' => false, 'error' => 'Source verse not found'];

        $canonical = preg_replace('/\s+/', ' ', trim($row['text']));
        $currentHash = hash('sha256', $canonical);

        // Check text integrity
        if ($currentHash !== $seal['text_hash']) {
            return ['verified' => false, 'error' => 'TEXT TAMPERED — hash mismatch', 'expected' => $seal['text_hash'], 'actual' => $currentHash];
        }

        // Check both seals
        $sealInput = "AKJV:VERSE:{$seal['book_id']}:{$seal['chapter_num']}:{$seal['verse_num']}:{$seal['language']}:{$currentHash}";
        $cmdValid = sas_verify_commander($sealInput, $seal['commander_seal']);
        $omaValid = sas_verify_omahon($sealInput, $seal['omahon_seal']);

        if (!$cmdValid || !$omaValid) {
            return ['verified' => false, 'error' => 'SEAL BROKEN — HMAC mismatch', 'commander_valid' => $cmdValid, 'omahon_valid' => $omaValid];
        }

        // Update verification count
        $db->prepare("UPDATE akjv_seal_registry SET verified=1, verification_count=verification_count+1, last_verified_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$sealId]);

        return ['verified' => true, 'seal_id' => $sealId, 'text_hash' => $currentHash, 'commander_valid' => true, 'omahon_valid' => true];
    }

    // For chapter/book/testament/canon — verify composite hash chain
    return sas_verify_composite($seal);
}

/**
 * Verify a composite (non-verse) seal by recomputing the hash chain.
 */
function sas_verify_composite(array $seal): array {
    $db = akjv_db();
    $childType = match($seal['seal_type']) {
        'chapter' => 'verse',
        'book' => 'chapter',
        'testament' => 'book',
        'canon' => 'testament',
        default => null,
    };
    if (!$childType) return ['verified' => false, 'error' => 'Unknown seal type'];

    // Build query for children
    $conditions = ["seal_type = ?", "language = ?"];
    $params = [$childType, $seal['language']];

    if ($seal['seal_type'] === 'chapter') {
        $conditions[] = "book_id = ?";
        $conditions[] = "chapter_num = ?";
        $params[] = $seal['book_id'];
        $params[] = $seal['chapter_num'];
        $orderBy = "verse_num";
    } elseif ($seal['seal_type'] === 'book') {
        $conditions[] = "book_id = ?";
        $params[] = $seal['book_id'];
        $orderBy = "chapter_num";
    } elseif ($seal['seal_type'] === 'testament') {
        // Children are books in this testament
        $orderBy = "book_id"; // simplified
    } else {
        $orderBy = "chapter_num"; // testament code
    }

    $sql = "SELECT text_hash FROM akjv_seal_registry WHERE " . implode(' AND ', $conditions) . " ORDER BY {$orderBy}";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $childHashes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $recomputedHash = hash('sha256', implode(':', $childHashes));
    if ($recomputedHash !== $seal['text_hash']) {
        return ['verified' => false, 'error' => 'CHAIN BREAK — composite hash mismatch'];
    }

    // Verify HMAC seals
    $typeUpper = strtoupper($seal['seal_type']);
    if ($seal['seal_type'] === 'testament') {
        $testament = match((int)$seal['chapter_num']) { 1 => 'OT', 2 => 'NT', 3 => 'AP', 4 => 'EN', default => '?' };
        $sealInput = "AKJV:{$typeUpper}:{$testament}:{$seal['language']}:{$recomputedHash}";
    } elseif ($seal['seal_type'] === 'canon') {
        $sealInput = "AKJV:CANON:{$seal['language']}:{$recomputedHash}";
    } elseif ($seal['seal_type'] === 'book') {
        $sealInput = "AKJV:BOOK:{$seal['book_id']}:{$seal['language']}:{$recomputedHash}";
    } else {
        $sealInput = "AKJV:CHAPTER:{$seal['book_id']}:{$seal['chapter_num']}:{$seal['language']}:{$recomputedHash}";
    }

    $cmdValid = sas_verify_commander($sealInput, $seal['commander_seal']);
    $omaValid = sas_verify_omahon($sealInput, $seal['omahon_seal']);

    if (!$cmdValid || !$omaValid) {
        return ['verified' => false, 'error' => 'SEAL BROKEN — HMAC mismatch'];
    }

    $db->prepare("UPDATE akjv_seal_registry SET verified=1, verification_count=verification_count+1, last_verified_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$seal['id']]);

    return ['verified' => true, 'seal_id' => $seal['id'], 'type' => $seal['seal_type'], 'commander_valid' => true, 'omahon_valid' => true];
}

// ══════════════════════════════════════════════════════════════
// SEAL STATISTICS — For display in the Bible UI
// ══════════════════════════════════════════════════════════════

function sas_stats(): array {
    $db = akjv_db();

    // Check if table exists
    try {
        $total = (int)$db->query("SELECT COUNT(*) FROM akjv_seal_registry")->fetchColumn();
    } catch (\Exception $e) {
        return ['total' => 0, 'verses' => 0, 'chapters' => 0, 'books' => 0, 'testaments' => 0, 'canons' => 0, 'verified' => 0, 'languages' => []];
    }

    $byType = $db->query("SELECT seal_type, COUNT(*) as c FROM akjv_seal_registry GROUP BY seal_type")->fetchAll(PDO::FETCH_KEY_PAIR);
    $byLang = $db->query("SELECT language, COUNT(*) as c FROM akjv_seal_registry GROUP BY language")->fetchAll(PDO::FETCH_KEY_PAIR);
    $verified = (int)$db->query("SELECT COUNT(*) FROM akjv_seal_registry WHERE verified=1")->fetchColumn();

    return [
        'total' => $total,
        'verses' => (int)($byType['verse'] ?? 0),
        'chapters' => (int)($byType['chapter'] ?? 0),
        'books' => (int)($byType['book'] ?? 0),
        'testaments' => (int)($byType['testament'] ?? 0),
        'canons' => (int)($byType['canon'] ?? 0),
        'verified' => $verified,
        'languages' => $byLang,
    ];
}

// ══════════════════════════════════════════════════════════════
// SEAL BADGE — HTML badge for display in Bible reader
// ══════════════════════════════════════════════════════════════

/**
 * Generate an HTML seal badge for a verse.
 * Shows ✝ SEALED status with dual seal icons.
 */
function sas_verse_badge(int $bookId, int $chapter, int $verse, string $lang = 'en'): string {
    $db = akjv_db();
    try {
        $stmt = $db->prepare("SELECT id, commander_seal, omahon_seal, sealed_at, verified, verification_count
            FROM akjv_seal_registry
            WHERE seal_type='verse' AND book_id=? AND chapter_num=? AND verse_num=? AND language=?");
        $stmt->execute([$bookId, $chapter, $verse, $lang]);
        $seal = $stmt->fetch();
    } catch (\Exception $e) {
        return ''; // Table doesn't exist yet
    }

    if (!$seal) return '';

    $verified = $seal['verified'] ? '✓ Verified' : 'Sealed';
    $date = date('Y-m-d', strtotime($seal['sealed_at']));
    $cmd = substr($seal['commander_seal'], 0, 8);
    $oma = substr($seal['omahon_seal'], 0, 8);

    return '<span class="sas-badge" title="Commander Seal: ' . htmlspecialchars($cmd, ENT_QUOTES) . '… | O\'Mahon Seal: ' . htmlspecialchars($oma, ENT_QUOTES) . '… | Sealed: ' . $date . '" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:rgba(255,215,0,.08);border:1px solid rgba(255,215,0,.2);border-radius:4px;font-size:.7rem;color:#c9a84c;cursor:help;">'
        . '<span>✝</span>'
        . '<span>' . $verified . '</span>'
        . '</span>';
}

// ══════════════════════════════════════════════════════════════
// CLI — Seal the Word from the command line
// ══════════════════════════════════════════════════════════════

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $action = $argv[1] ?? 'help';
    $lang = $argv[2] ?? 'en';

    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  SCRIPTURAL AUTHENTOR SERVICE — THE SEAL OF THE WORD           ║\n";
    echo "║  \"Shut up the words, and seal the book\" — Daniel 12:4          ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

    sas_ensure_table();

    switch ($action) {
        case 'seal-canon':
            echo "  Sealing the entire AKJV Canon ({$lang})...\n";
            echo "  This seals every verse, chapter, book, and testament.\n";
            echo "  OMAHON! OMAHON! OMAHON!\n\n";
            $start = microtime(true);
            $result = sas_seal_canon($lang);
            $elapsed = round(microtime(true) - $start, 2);
            echo "  ═══════════════════════════════════════════════════════════\n";
            echo "  ✝ CANON SEALED — {$result['verses_sealed']} verses in {$elapsed}s\n";
            echo "  Canon Hash: {$result['canon_hash']}\n";
            echo "  Commander Seal: {$result['commander_seal']}\n";
            echo "  O'Mahon Seal: {$result['omahon_seal']}\n";
            if ($result['errors']) {
                echo "  Errors: " . count($result['errors']) . "\n";
                foreach (array_slice($result['errors'], 0, 10) as $e) echo "    - {$e}\n";
            }
            echo "  ═══════════════════════════════════════════════════════════\n";
            echo "  \"It is finished.\" — John 19:30\n";
            break;

        case 'seal-all':
            echo "  Sealing ALL THREE languages...\n\n";
            foreach (['en', 'fr', 'he'] as $l) {
                echo "  --- {$l} ---\n";
                $result = sas_seal_canon($l);
                echo "  ✝ {$l} sealed: {$result['verses_sealed']} verses, hash: " . substr($result['canon_hash'], 0, 16) . "...\n";
            }
            echo "\n  ✝ THE WORD IS SEALED IN ALL THREE COVENANT LANGUAGES.\n";
            echo "  \"Shut up the words, and seal the book.\" — Daniel 12:4\n";
            break;

        case 'verify':
            $sealId = (int)($argv[2] ?? 0);
            if (!$sealId) { echo "  Usage: php scriptural-authentor.php verify <seal_id>\n"; break; }
            $result = sas_verify_seal($sealId);
            echo $result['verified']
                ? "  ✓ SEAL #{$sealId} VERIFIED — Commander ✓ O'Mahon ✓\n"
                : "  ✗ SEAL #{$sealId} FAILED — {$result['error']}\n";
            break;

        case 'stats':
            $stats = sas_stats();
            echo "  Total Seals:    {$stats['total']}\n";
            echo "  Verses Sealed:  {$stats['verses']}\n";
            echo "  Chapters:       {$stats['chapters']}\n";
            echo "  Books:          {$stats['books']}\n";
            echo "  Testaments:     {$stats['testaments']}\n";
            echo "  Canon Seals:    {$stats['canons']}\n";
            echo "  Verified:       {$stats['verified']}\n";
            echo "  By Language:    " . json_encode($stats['languages']) . "\n";
            break;

        case 'setup':
            echo "  Table akjv_seal_registry ensured.\n";
            echo "  Ready to seal. Run: php scriptural-authentor.php seal-all\n";
            break;

        default:
            echo "  Usage:\n";
            echo "    php scriptural-authentor.php setup          — Create the seal registry table\n";
            echo "    php scriptural-authentor.php seal-canon en  — Seal the English canon\n";
            echo "    php scriptural-authentor.php seal-all       — Seal EN + FR + HE\n";
            echo "    php scriptural-authentor.php verify <id>    — Verify a specific seal\n";
            echo "    php scriptural-authentor.php stats          — Show seal statistics\n";
    }
}
