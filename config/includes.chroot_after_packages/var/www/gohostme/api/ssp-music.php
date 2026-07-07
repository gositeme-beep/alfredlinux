<?php
/**
 * SoundStudioPro Music API
 * Serves track data, artist profiles, live session sync, and venue info
 * for the DJ Studio game and SSP integrations.
 * 
 * Actions:
 *   tracks      — List/filter tracks (genre, artist, bpm range, key, search)
 *   track       — Get single track by ID
 *   genres      — List all genres with track counts
 *   artists     — List all artists with profiles
 *   venues      — List world venue backgrounds
 *   live        — Get/create live sessions (sync SSP mixer ↔ game)
 *   sync        — Push live mixing state (deck positions, crossfader, FX)
 *   heartbeat   — Player presence heartbeat
 *   leaderboard — Top DJs by plays, streams, battle wins
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-SSP-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Track Catalog ──────────────────────────────────────────
// Comprehensive catalog — serves as the source of truth for the DJ Studio game.
// In production, this would pull from the SSP database. For now, a rich in-memory catalog.

$TRACK_CATALOG = [
    // ── SoundStudioPro Originals ──
    ['id' => 'ssp-001', 'title' => 'Midnight Protocol',    'artist' => 'SoundStudioPro',  'genre' => 'Techno',       'bpm' => 130, 'key' => 'Am',  'duration' => 342, 'energy' => 8, 'mood' => 'dark',     'year' => 2025, 'plays' => 12840, 'ssp_url' => '/tracks/midnight-protocol'],
    ['id' => 'ssp-002', 'title' => 'AYOYE',                'artist' => 'SoundStudioPro',  'genre' => 'EDM',          'bpm' => 128, 'key' => 'Dm',  'duration' => 285, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2025, 'plays' => 18920, 'ssp_url' => '/tracks/ayoye'],
    ['id' => 'ssp-003', 'title' => 'Stellar Gateway',      'artist' => 'SoundStudioPro',  'genre' => 'Trance',       'bpm' => 138, 'key' => 'Cm',  'duration' => 410, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2025, 'plays' => 9530,  'ssp_url' => '/tracks/stellar-gateway'],
    ['id' => 'ssp-004', 'title' => 'Funk That',            'artist' => 'SoundStudioPro',  'genre' => 'Hip Hop',      'bpm' => 98,  'key' => 'Gm',  'duration' => 248, 'energy' => 6, 'mood' => 'groovy',   'year' => 2025, 'plays' => 7210,  'ssp_url' => '/tracks/funk-that'],
    ['id' => 'ssp-005', 'title' => 'Bass Cathedral',       'artist' => 'SoundStudioPro',  'genre' => 'Drum & Bass',  'bpm' => 174, 'key' => 'Fm',  'duration' => 312, 'energy' => 10,'mood' => 'intense',  'year' => 2025, 'plays' => 15600, 'ssp_url' => '/tracks/bass-cathedral'],
    ['id' => 'ssp-006', 'title' => 'Euphoria Protocol',    'artist' => 'SoundStudioPro',  'genre' => 'Trance',       'bpm' => 140, 'key' => 'Am',  'duration' => 388, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2025, 'plays' => 11200, 'ssp_url' => '/tracks/euphoria-protocol'],
    ['id' => 'ssp-007', 'title' => 'Festival Anthem SSP',  'artist' => 'SoundStudioPro',  'genre' => 'EDM',          'bpm' => 130, 'key' => 'Dm',  'duration' => 295, 'energy' => 10,'mood' => 'euphoric', 'year' => 2026, 'plays' => 22100, 'ssp_url' => '/tracks/festival-anthem'],
    ['id' => 'ssp-008', 'title' => 'Voltage',              'artist' => 'SoundStudioPro',  'genre' => 'Drum & Bass',  'bpm' => 176, 'key' => 'Dm',  'duration' => 290, 'energy' => 10,'mood' => 'intense',  'year' => 2025, 'plays' => 8900,  'ssp_url' => '/tracks/voltage'],
    ['id' => 'ssp-009', 'title' => 'Rave Signal',          'artist' => 'SoundStudioPro',  'genre' => 'EDM',          'bpm' => 130, 'key' => 'Fm',  'duration' => 310, 'energy' => 9, 'mood' => 'energetic','year' => 2025, 'plays' => 14300, 'ssp_url' => '/tracks/rave-signal'],
    ['id' => 'ssp-010', 'title' => 'Arctic Drift',         'artist' => 'SoundStudioPro',  'genre' => 'Ambient',      'bpm' => 75,  'key' => 'Em',  'duration' => 480, 'energy' => 2, 'mood' => 'peaceful', 'year' => 2025, 'plays' => 5100,  'ssp_url' => '/tracks/arctic-drift'],
    ['id' => 'ssp-011', 'title' => 'Binary Sunset',        'artist' => 'SoundStudioPro',  'genre' => 'Ambient',      'bpm' => 78,  'key' => 'Cm',  'duration' => 520, 'energy' => 2, 'mood' => 'peaceful', 'year' => 2026, 'plays' => 3800,  'ssp_url' => '/tracks/binary-sunset'],
    ['id' => 'ssp-012', 'title' => 'Quantum Flux',         'artist' => 'SoundStudioPro',  'genre' => 'Techno',       'bpm' => 134, 'key' => 'Bbm', 'duration' => 355, 'energy' => 8, 'mood' => 'dark',     'year' => 2026, 'plays' => 6700,  'ssp_url' => '/tracks/quantum-flux'],

    // ── DRUMAHON ──
    ['id' => 'drm-001', 'title' => 'Neon Dreams',          'artist' => 'DRUMAHON',        'genre' => 'Deep House',   'bpm' => 122, 'key' => 'Fm',  'duration' => 368, 'energy' => 6, 'mood' => 'groovy',   'year' => 2024, 'plays' => 24500, 'ssp_url' => '/tracks/neon-dreams'],
    ['id' => 'drm-002', 'title' => 'Virtues Strike',       'artist' => 'DRUMAHON',        'genre' => 'Ambient',      'bpm' => 90,  'key' => 'Em',  'duration' => 445, 'energy' => 3, 'mood' => 'peaceful', 'year' => 2024, 'plays' => 8200,  'ssp_url' => '/tracks/virtues-strike'],
    ['id' => 'drm-003', 'title' => 'Omahon The Land',      'artist' => 'DRUMAHON',        'genre' => 'EDM',          'bpm' => 126, 'key' => 'Am',  'duration' => 305, 'energy' => 8, 'mood' => 'euphoric', 'year' => 2024, 'plays' => 16800, 'ssp_url' => '/tracks/omahon-the-land'],
    ['id' => 'drm-004', 'title' => 'Kebek — Stone Of Freedom','artist' => 'DRUMAHON',     'genre' => 'Techno',       'bpm' => 134, 'key' => 'Fm',  'duration' => 378, 'energy' => 8, 'mood' => 'intense',  'year' => 2024, 'plays' => 11400, 'ssp_url' => '/tracks/kebek'],
    ['id' => 'drm-005', 'title' => 'Turn It On',           'artist' => 'DRUMAHON',        'genre' => 'EDM',          'bpm' => 128, 'key' => 'Cm',  'duration' => 292, 'energy' => 8, 'mood' => 'energetic','year' => 2024, 'plays' => 13200, 'ssp_url' => '/tracks/turn-it-on'],
    ['id' => 'drm-006', 'title' => 'Deep State',           'artist' => 'DRUMAHON',        'genre' => 'Deep House',   'bpm' => 124, 'key' => 'Gm',  'duration' => 340, 'energy' => 5, 'mood' => 'groovy',   'year' => 2025, 'plays' => 10100, 'ssp_url' => '/tracks/deep-state'],
    ['id' => 'drm-007', 'title' => 'Velvet Underground',   'artist' => 'DRUMAHON',        'genre' => 'Deep House',   'bpm' => 118, 'key' => 'Bbm', 'duration' => 395, 'energy' => 4, 'mood' => 'smooth',   'year' => 2025, 'plays' => 7600,  'ssp_url' => '/tracks/velvet-underground'],
    ['id' => 'drm-008', 'title' => 'Horizon Line',         'artist' => 'DRUMAHON',        'genre' => 'Trance',       'bpm' => 136, 'key' => 'Am',  'duration' => 420, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2026, 'plays' => 4200,  'ssp_url' => '/tracks/horizon-line'],

    // ── Taz' ──
    ['id' => 'taz-001', 'title' => 'Digital Rain',         'artist' => "Taz'",            'genre' => 'Techno',       'bpm' => 132, 'key' => 'Am',  'duration' => 335, 'energy' => 7, 'mood' => 'dark',     'year' => 2025, 'plays' => 9800,  'ssp_url' => '/tracks/digital-rain'],
    ['id' => 'taz-002', 'title' => 'Concrete Jungle',      'artist' => "Taz'",            'genre' => 'Hip Hop',      'bpm' => 92,  'key' => 'Cm',  'duration' => 255, 'energy' => 6, 'mood' => 'groovy',   'year' => 2025, 'plays' => 11500, 'ssp_url' => '/tracks/concrete-jungle'],
    ['id' => 'taz-003', 'title' => 'Quantum Bass',         'artist' => "Taz'",            'genre' => 'Drum & Bass',  'bpm' => 175, 'key' => 'Gm',  'duration' => 298, 'energy' => 10,'mood' => 'intense',  'year' => 2025, 'plays' => 7300,  'ssp_url' => '/tracks/quantum-bass'],
    ['id' => 'taz-004', 'title' => 'Acid Rain',            'artist' => "Taz'",            'genre' => 'Techno',       'bpm' => 136, 'key' => 'Em',  'duration' => 360, 'energy' => 8, 'mood' => 'dark',     'year' => 2025, 'plays' => 6900,  'ssp_url' => '/tracks/acid-rain'],
    ['id' => 'taz-005', 'title' => 'Nuit Blanche',         'artist' => "Taz'",            'genre' => 'Deep House',   'bpm' => 120, 'key' => 'Fm',  'duration' => 385, 'energy' => 5, 'mood' => 'smooth',   'year' => 2026, 'plays' => 3400,  'ssp_url' => '/tracks/nuit-blanche'],

    // ── MANNJAI514 ──
    ['id' => 'mnj-001', 'title' => 'Afro Sunrise',         'artist' => 'MANNJAI514',      'genre' => 'Afrobeats',    'bpm' => 110, 'key' => 'Gm',  'duration' => 272, 'energy' => 7, 'mood' => 'energetic','year' => 2025, 'plays' => 19800, 'ssp_url' => '/tracks/afro-sunrise'],
    ['id' => 'mnj-002', 'title' => 'Sahara Nights',        'artist' => 'MANNJAI514',      'genre' => 'Afrobeats',    'bpm' => 112, 'key' => 'Em',  'duration' => 288, 'energy' => 7, 'mood' => 'groovy',   'year' => 2025, 'plays' => 14200, 'ssp_url' => '/tracks/sahara-nights'],
    ['id' => 'mnj-003', 'title' => 'Tribal Roots',         'artist' => 'MANNJAI514',      'genre' => 'Afrobeats',    'bpm' => 108, 'key' => 'Am',  'duration' => 310, 'energy' => 7, 'mood' => 'groovy',   'year' => 2025, 'plays' => 8600,  'ssp_url' => '/tracks/tribal-roots'],
    ['id' => 'mnj-004', 'title' => 'Lagos To Montreal',    'artist' => 'MANNJAI514',      'genre' => 'Afrobeats',    'bpm' => 114, 'key' => 'Dm',  'duration' => 295, 'energy' => 8, 'mood' => 'energetic','year' => 2026, 'plays' => 5100,  'ssp_url' => '/tracks/lagos-to-montreal'],

    // ── Jabëla ──
    ['id' => 'jab-001', 'title' => 'ELLE DÉBARQUE',        'artist' => 'Jabëla',          'genre' => 'Deep House',   'bpm' => 120, 'key' => 'Bbm', 'duration' => 348, 'energy' => 6, 'mood' => 'smooth',   'year' => 2025, 'plays' => 12700, 'ssp_url' => '/tracks/elle-debarque'],
    ['id' => 'jab-002', 'title' => 'Montréal Noire',       'artist' => 'Jabëla',          'genre' => 'Techno',       'bpm' => 130, 'key' => 'Am',  'duration' => 365, 'energy' => 7, 'mood' => 'dark',     'year' => 2026, 'plays' => 4500,  'ssp_url' => '/tracks/montreal-noire'],

    // ── Josie ──
    ['id' => 'jos-001', 'title' => 'Every Word',           'artist' => 'Josie',           'genre' => 'Lo-Fi',        'bpm' => 82,  'key' => 'Dm',  'duration' => 215, 'energy' => 3, 'mood' => 'peaceful', 'year' => 2024, 'plays' => 21300, 'ssp_url' => '/tracks/every-word'],
    ['id' => 'jos-002', 'title' => 'Whisper Rain',         'artist' => 'Josie',           'genre' => 'Lo-Fi',        'bpm' => 78,  'key' => 'Em',  'duration' => 235, 'energy' => 2, 'mood' => 'peaceful', 'year' => 2025, 'plays' => 16400, 'ssp_url' => '/tracks/whisper-rain'],

    // ── Creeker Chambers ──
    ['id' => 'crk-001', 'title' => 'Romans 7',             'artist' => 'Creeker Chambers','genre' => 'Ambient',      'bpm' => 88,  'key' => 'Em',  'duration' => 490, 'energy' => 2, 'mood' => 'spiritual','year' => 2024, 'plays' => 6200,  'ssp_url' => '/tracks/romans-7'],
    ['id' => 'crk-002', 'title' => 'Psalm 23 (Remix)',     'artist' => 'Creeker Chambers','genre' => 'Ambient',      'bpm' => 85,  'key' => 'Am',  'duration' => 410, 'energy' => 3, 'mood' => 'spiritual','year' => 2025, 'plays' => 4800,  'ssp_url' => '/tracks/psalm-23-remix'],

    // ── Will Chambers ──
    ['id' => 'wil-001', 'title' => 'Amazing Grace Remix',  'artist' => 'Will Chambers',   'genre' => 'Trance',       'bpm' => 140, 'key' => 'Am',  'duration' => 375, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2024, 'plays' => 18700, 'ssp_url' => '/tracks/amazing-grace-remix'],
    ['id' => 'wil-002', 'title' => 'City Of Gold',         'artist' => 'Will Chambers',   'genre' => 'Trance',       'bpm' => 138, 'key' => 'Cm',  'duration' => 395, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2025, 'plays' => 9400,  'ssp_url' => '/tracks/city-of-gold'],

    // ── K ──
    ['id' => 'k-001',   'title' => 'Cloud Nine',           'artist' => 'K',               'genre' => 'Lo-Fi',        'bpm' => 85,  'key' => 'Cm',  'duration' => 198, 'energy' => 3, 'mood' => 'peaceful', 'year' => 2024, 'plays' => 13900, 'ssp_url' => '/tracks/cloud-nine'],
    ['id' => 'k-002',   'title' => 'Midnight Tea',         'artist' => 'K',               'genre' => 'Lo-Fi',        'bpm' => 80,  'key' => 'Gm',  'duration' => 210, 'energy' => 2, 'mood' => 'peaceful', 'year' => 2025, 'plays' => 8200,  'ssp_url' => '/tracks/midnight-tea'],

    // ── Tiësto Tribute Series ──
    ['id' => 'tie-001', 'title' => 'Adagio For Strings',   'artist' => 'Tiësto Tribute',  'genre' => 'Trance',       'bpm' => 138, 'key' => 'Dm',  'duration' => 445, 'energy' => 10,'mood' => 'euphoric', 'year' => 2024, 'plays' => 45200, 'ssp_url' => '/tracks/adagio-for-strings'],
    ['id' => 'tie-002', 'title' => 'Elements Of Life',     'artist' => 'Tiësto Tribute',  'genre' => 'Trance',       'bpm' => 140, 'key' => 'Am',  'duration' => 420, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2024, 'plays' => 38100, 'ssp_url' => '/tracks/elements-of-life'],
    ['id' => 'tie-003', 'title' => 'Red Lights',           'artist' => 'Tiësto Tribute',  'genre' => 'Deep House',   'bpm' => 126, 'key' => 'Fm',  'duration' => 315, 'energy' => 7, 'mood' => 'groovy',   'year' => 2024, 'plays' => 52800, 'ssp_url' => '/tracks/red-lights'],
    ['id' => 'tie-004', 'title' => 'Silence (Remix)',      'artist' => 'Tiësto Tribute',  'genre' => 'Trance',       'bpm' => 136, 'key' => 'Cm',  'duration' => 398, 'energy' => 8, 'mood' => 'euphoric', 'year' => 2024, 'plays' => 34600, 'ssp_url' => '/tracks/silence-remix'],
    ['id' => 'tie-005', 'title' => 'Lethal Industry',      'artist' => 'Tiësto Tribute',  'genre' => 'Trance',       'bpm' => 142, 'key' => 'Am',  'duration' => 355, 'energy' => 10,'mood' => 'intense',  'year' => 2024, 'plays' => 29400, 'ssp_url' => '/tracks/lethal-industry'],
    ['id' => 'tie-006', 'title' => 'Traffic',              'artist' => 'Tiësto Tribute',  'genre' => 'Trance',       'bpm' => 140, 'key' => 'Em',  'duration' => 380, 'energy' => 9, 'mood' => 'energetic','year' => 2024, 'plays' => 27800, 'ssp_url' => '/tracks/traffic'],
    ['id' => 'tie-007', 'title' => 'BOOM',                 'artist' => 'Tiësto Tribute',  'genre' => 'EDM',          'bpm' => 128, 'key' => 'Gm',  'duration' => 265, 'energy' => 10,'mood' => 'euphoric', 'year' => 2025, 'plays' => 41500, 'ssp_url' => '/tracks/boom'],
    ['id' => 'tie-008', 'title' => 'The Business',         'artist' => 'Tiësto Tribute',  'genre' => 'Deep House',   'bpm' => 124, 'key' => 'Am',  'duration' => 295, 'energy' => 7, 'mood' => 'groovy',   'year' => 2025, 'plays' => 58200, 'ssp_url' => '/tracks/the-business'],
    ['id' => 'tie-009', 'title' => 'Ritual',               'artist' => 'Tiësto Tribute',  'genre' => 'EDM',          'bpm' => 128, 'key' => 'Cm',  'duration' => 310, 'energy' => 8, 'mood' => 'energetic','year' => 2025, 'plays' => 22100, 'ssp_url' => '/tracks/ritual'],

    // ── New SSP 2026 releases ──
    ['id' => 'ssp-013', 'title' => 'Photon Burst',         'artist' => 'SoundStudioPro',  'genre' => 'EDM',          'bpm' => 132, 'key' => 'Am',  'duration' => 305, 'energy' => 10,'mood' => 'euphoric', 'year' => 2026, 'plays' => 1200,  'ssp_url' => '/tracks/photon-burst'],
    ['id' => 'ssp-014', 'title' => 'Neural Network',       'artist' => 'SoundStudioPro',  'genre' => 'Techno',       'bpm' => 136, 'key' => 'Fm',  'duration' => 340, 'energy' => 8, 'mood' => 'dark',     'year' => 2026, 'plays' => 980,   'ssp_url' => '/tracks/neural-network'],
    ['id' => 'ssp-015', 'title' => 'Gravity Well',         'artist' => 'SoundStudioPro',  'genre' => 'Trance',       'bpm' => 142, 'key' => 'Dm',  'duration' => 425, 'energy' => 10,'mood' => 'euphoric', 'year' => 2026, 'plays' => 750,   'ssp_url' => '/tracks/gravity-well'],
    ['id' => 'drm-009', 'title' => 'Cosmos Calling',       'artist' => 'DRUMAHON',        'genre' => 'Trance',       'bpm' => 140, 'key' => 'Cm',  'duration' => 400, 'energy' => 9, 'mood' => 'euphoric', 'year' => 2026, 'plays' => 620,   'ssp_url' => '/tracks/cosmos-calling'],
    ['id' => 'taz-006', 'title' => 'Afterglow',            'artist' => "Taz'",            'genre' => 'Deep House',   'bpm' => 122, 'key' => 'Gm',  'duration' => 350, 'energy' => 5, 'mood' => 'smooth',   'year' => 2026, 'plays' => 480,   'ssp_url' => '/tracks/afterglow'],
];

// ── Artist Profiles ──
$ARTIST_PROFILES = [
    'SoundStudioPro' => ['id' => 'ssp',  'name' => 'SoundStudioPro', 'avatar' => '🎛️', 'bio' => 'The official SoundStudioPro house label. AI-powered production across all genres.', 'genres' => ['EDM','Techno','Trance','Hip Hop','Drum & Bass','Ambient'], 'followers' => 125000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/soundstudiopro'],
    'DRUMAHON'       => ['id' => 'drm',  'name' => 'DRUMAHON',       'avatar' => '🥁', 'bio' => 'Deep house to trance. Montreal nights, world stages. Co-founder of GoSiteMe.', 'genres' => ['Deep House','EDM','Techno','Trance','Ambient'], 'followers' => 89000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/drumahon'],
    "Taz'"           => ['id' => 'taz',  'name' => "Taz'",           'avatar' => '🔊', 'bio' => 'Techno futurist from the underground. Bass-heavy, no compromises.', 'genres' => ['Techno','Hip Hop','Drum & Bass','Deep House'], 'followers' => 42000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/taz'],
    'MANNJAI514'     => ['id' => 'mnj',  'name' => 'MANNJAI514',     'avatar' => '🌍', 'bio' => 'Afrobeats meets electronic. Lagos to Montreal to the world.', 'genres' => ['Afrobeats'], 'followers' => 67000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/mannjai514'],
    'Jabëla'         => ['id' => 'jab',  'name' => 'Jabëla',         'avatar' => '💎', 'bio' => 'Francophone deep house sensation. Smooth transitions, deep grooves.', 'genres' => ['Deep House','Techno'], 'followers' => 31000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/jabela'],
    'Josie'          => ['id' => 'jos',  'name' => 'Josie',          'avatar' => '🌸', 'bio' => 'Lo-fi dreams and gentle melodies. Music for the soul.', 'genres' => ['Lo-Fi'], 'followers' => 54000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/josie'],
    'Creeker Chambers'=> ['id' => 'crk', 'name' => 'Creeker Chambers','avatar' => '🕊️', 'bio' => 'Spiritual ambient soundscapes. Faith through frequency.', 'genres' => ['Ambient'], 'followers' => 18000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/creeker-chambers'],
    'Will Chambers'  => ['id' => 'wil',  'name' => 'Will Chambers',  'avatar' => '⚡', 'bio' => 'Trance pioneer. Turning hymns into anthems since day one.', 'genres' => ['Trance'], 'followers' => 36000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/will-chambers'],
    'K'              => ['id' => 'k',    'name' => 'K',              'avatar' => '☁️',  'bio' => 'Minimalist lo-fi. Less is more. Let the beats breathe.', 'genres' => ['Lo-Fi'], 'followers' => 28000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/k'],
    'Tiësto Tribute' => ['id' => 'tie',  'name' => 'Tiësto Tribute', 'avatar' => '👑', 'bio' => 'Celebrating the legacy of Tiësto. Reimagined classics for the next generation.', 'genres' => ['Trance','EDM','Deep House'], 'followers' => 210000, 'verified' => true, 'profile_url' => 'https://soundstudiopro.com/artist/tiesto-tribute'],
];

// ── World Venue Catalog ──
$VENUE_CATALOG = [
    ['id' => 'default-club',      'name' => 'SSP Nightclub',         'emoji' => '🏢', 'city' => 'Montreal',     'country' => 'Canada',     'type' => 'indoor',   'capacity' => 500,    'skyColor' => '0x05050f', 'fogColor' => '0x05050f', 'fogDensity' => 0.015, 'description' => 'The original SSP underground club. Purple neon, mirror ball, dance floor tiles.'],
    ['id' => 'ibiza-beach',       'name' => 'Ibiza Beach Club',      'emoji' => '🏖️', 'city' => 'Ibiza',        'country' => 'Spain',      'type' => 'outdoor',  'capacity' => 3000,   'skyColor' => '0x1a0a2e', 'fogColor' => '0x0a0520', 'fogDensity' => 0.008, 'description' => 'Open-air beach stage under the stars. Balearic sunset, palm trees, ocean breeze.'],
    ['id' => 'tokyo-shibuya',     'name' => 'Tokyo Underground',     'emoji' => '🗼', 'city' => 'Tokyo',        'country' => 'Japan',      'type' => 'indoor',   'capacity' => 200,    'skyColor' => '0x0a0015', 'fogColor' => '0x0a0015', 'fogDensity' => 0.02,  'description' => 'Intimate basement club in Shibuya. Neon kanji, minimal design, intense sound.'],
    ['id' => 'berlin-warehouse',  'name' => 'Berlin Warehouse',      'emoji' => '🏭', 'city' => 'Berlin',       'country' => 'Germany',    'type' => 'indoor',   'capacity' => 1500,   'skyColor' => '0x080808', 'fogColor' => '0x050505', 'fogDensity' => 0.012, 'description' => 'Raw industrial warehouse. Concrete walls, strobes, heavy techno. No photos.'],
    ['id' => 'tomorrowland',      'name' => 'Tomorrowland Main',     'emoji' => '🎪', 'city' => 'Boom',         'country' => 'Belgium',    'type' => 'festival', 'capacity' => 100000, 'skyColor' => '0x0a0520', 'fogColor' => '0x05030f', 'fogDensity' => 0.005, 'description' => 'The legendary main stage. Pyro, LED walls, confetti, 100K fans. Pure magic.'],
    ['id' => 'nyc-rooftop',       'name' => 'NYC Rooftop',           'emoji' => '🌃', 'city' => 'New York',     'country' => 'USA',        'type' => 'outdoor',  'capacity' => 800,    'skyColor' => '0x0a0a1e', 'fogColor' => '0x05050f', 'fogDensity' => 0.006, 'description' => 'Manhattan skyline rooftop party. City lights, sunset drinks, skyline views.'],
    ['id' => 'miami-pool',        'name' => 'Miami Pool Party',      'emoji' => '🌴', 'city' => 'Miami',        'country' => 'USA',        'type' => 'outdoor',  'capacity' => 2000,   'skyColor' => '0x1a0520', 'fogColor' => '0x0f0310', 'fogDensity' => 0.007, 'description' => 'South Beach pool party vibes. Palm trees, turquoise water, flamingo floats.'],
    ['id' => 'dubai-sky',         'name' => 'Dubai Sky Lounge',      'emoji' => '🏙️', 'city' => 'Dubai',        'country' => 'UAE',        'type' => 'indoor',   'capacity' => 400,    'skyColor' => '0x0f0a1e', 'fogColor' => '0x080515', 'fogDensity' => 0.01,  'description' => '80th floor luxury. Gold accents, panoramic views, champagne crowd.'],
    ['id' => 'london-ministry',   'name' => 'Ministry of Sound',     'emoji' => '🇬🇧', 'city' => 'London',       'country' => 'UK',         'type' => 'indoor',   'capacity' => 1800,   'skyColor' => '0x060612', 'fogColor' => '0x040410', 'fogDensity' => 0.014, 'description' => 'Legendary London institution. Multi-room, world-class sound system.'],
    ['id' => 'rio-carnival',      'name' => 'Rio Carnival Stage',    'emoji' => '🎉', 'city' => 'Rio de Janeiro','country' => 'Brazil',    'type' => 'festival', 'capacity' => 50000,  'skyColor' => '0x15081e', 'fogColor' => '0x0a0410', 'fogDensity' => 0.006, 'description' => 'Carnival energy! Samba meets electronic, feathers, colors, pure joy.'],
    ['id' => 'bali-sunset',       'name' => 'Bali Sunset Temple',    'emoji' => '🌅', 'city' => 'Bali',         'country' => 'Indonesia',  'type' => 'outdoor',  'capacity' => 600,    'skyColor' => '0x1e0a08', 'fogColor' => '0x120505', 'fogDensity' => 0.008, 'description' => 'Cliffside temple overlooking the ocean. Golden hour, spiritual vibes.'],
    ['id' => 'space-station',     'name' => 'Orbital Station',       'emoji' => '🛸', 'city' => 'Low Earth Orbit','country' => 'Space',    'type' => 'space',    'capacity' => 150,    'skyColor' => '0x000005', 'fogColor' => '0x000003', 'fogDensity' => 0.003, 'description' => 'Zero gravity party. Earth below, stars above, bass in the void.'],
    ['id' => 'sahara-oasis',      'name' => 'Sahara Oasis',          'emoji' => '🏜️', 'city' => 'Merzouga',     'country' => 'Morocco',    'type' => 'outdoor',  'capacity' => 5000,   'skyColor' => '0x1a0f05', 'fogColor' => '0x0f0804', 'fogDensity' => 0.004, 'description' => 'Desert festival under infinite stars. Dunes, bonfires, tribal rhythms.'],
    ['id' => 'arctic-aurora',     'name' => 'Arctic Aurora',         'emoji' => '🌌', 'city' => 'Tromsø',       'country' => 'Norway',     'type' => 'outdoor',  'capacity' => 1000,   'skyColor' => '0x030815', 'fogColor' => '0x020510', 'fogDensity' => 0.005, 'description' => 'Northern lights dancing above. Ice stage, snow, ethereal beauty.'],
    ['id' => 'underground-cave',  'name' => 'Crystal Cave',          'emoji' => '💎', 'city' => 'Cueva',        'country' => 'Mexico',     'type' => 'indoor',   'capacity' => 300,    'skyColor' => '0x050510', 'fogColor' => '0x03030a', 'fogDensity' => 0.018, 'description' => 'Natural cave turned club. Crystal formations, echo chamber acoustics.'],
    ['id' => 'paris-cabaret',     'name' => 'Paris Cabaret',         'emoji' => '🗼', 'city' => 'Paris',        'country' => 'France',     'type' => 'indoor',   'capacity' => 600,    'skyColor' => '0x0f0508', 'fogColor' => '0x0a0306', 'fogDensity' => 0.012, 'description' => 'Velvet curtains, chandeliers, French house elegance. Très magnifique.'],
];

// ── Action Router ──────────────────────────────────────────

switch ($action) {

    // ── List/filter tracks ──
    case 'tracks':
        $genre   = sanitize($_GET['genre'] ?? '', 50);
        $artist  = sanitize($_GET['artist'] ?? '', 100);
        $search  = sanitize($_GET['search'] ?? '', 200);
        $mood    = sanitize($_GET['mood'] ?? '', 30);
        $bpmMin  = intval($_GET['bpm_min'] ?? 0);
        $bpmMax  = intval($_GET['bpm_max'] ?? 999);
        $sortBy  = sanitize($_GET['sort'] ?? 'plays', 20);
        $limit   = min(intval($_GET['limit'] ?? 100), 200);
        $offset  = max(intval($_GET['offset'] ?? 0), 0);

        $results = $TRACK_CATALOG;

        if ($genre && $genre !== 'All') {
            $results = array_filter($results, function($t) use ($genre) {
                return stripos($t['genre'], $genre) !== false;
            });
        }
        if ($artist) {
            $results = array_filter($results, function($t) use ($artist) {
                return stripos($t['artist'], $artist) !== false;
            });
        }
        if ($search) {
            $results = array_filter($results, function($t) use ($search) {
                return stripos($t['title'], $search) !== false || stripos($t['artist'], $search) !== false;
            });
        }
        if ($mood) {
            $results = array_filter($results, function($t) use ($mood) {
                return $t['mood'] === $mood;
            });
        }
        if ($bpmMin > 0) {
            $results = array_filter($results, function($t) use ($bpmMin) { return $t['bpm'] >= $bpmMin; });
        }
        if ($bpmMax < 999) {
            $results = array_filter($results, function($t) use ($bpmMax) { return $t['bpm'] <= $bpmMax; });
        }

        $results = array_values($results);

        // Sort
        usort($results, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'bpm':     return $a['bpm'] - $b['bpm'];
                case 'title':   return strcmp($a['title'], $b['title']);
                case 'artist':  return strcmp($a['artist'], $b['artist']);
                case 'energy':  return $b['energy'] - $a['energy'];
                case 'newest':  return $b['year'] - $a['year'];
                default:        return $b['plays'] - $a['plays'];
            }
        });

        $total = count($results);
        $results = array_slice($results, $offset, $limit);

        jsonResponse([
            'success' => true,
            'tracks' => $results,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'genres' => array_values(array_unique(array_column($TRACK_CATALOG, 'genre'))),
        ]);
        break;

    // ── Single track ──
    case 'track':
        $id = sanitize($_GET['id'] ?? '', 20);
        $found = null;
        foreach ($TRACK_CATALOG as $t) {
            if ($t['id'] === $id) { $found = $t; break; }
        }
        if (!$found) {
            jsonResponse(['success' => false, 'error' => 'Track not found'], 404);
        }
        // Attach artist profile
        $found['artist_profile'] = $ARTIST_PROFILES[$found['artist']] ?? null;
        jsonResponse(['success' => true, 'track' => $found]);
        break;

    // ── Genres ──
    case 'genres':
        $genreCounts = [];
        foreach ($TRACK_CATALOG as $t) {
            if (!isset($genreCounts[$t['genre']])) $genreCounts[$t['genre']] = 0;
            $genreCounts[$t['genre']]++;
        }
        arsort($genreCounts);
        $genres = [];
        foreach ($genreCounts as $name => $count) {
            $genres[] = ['name' => $name, 'count' => $count];
        }
        jsonResponse(['success' => true, 'genres' => $genres, 'total' => count($TRACK_CATALOG)]);
        break;

    // ── Artists ──
    case 'artists':
        $artists = [];
        foreach ($ARTIST_PROFILES as $name => $profile) {
            $profile['track_count'] = count(array_filter($TRACK_CATALOG, function($t) use ($name) {
                return $t['artist'] === $name;
            }));
            $profile['total_plays'] = array_sum(array_column(array_filter($TRACK_CATALOG, function($t) use ($name) {
                return $t['artist'] === $name;
            }), 'plays'));
            $artists[] = $profile;
        }
        usort($artists, function($a, $b) { return $b['total_plays'] - $a['total_plays']; });
        jsonResponse(['success' => true, 'artists' => $artists]);
        break;

    // ── World Venues ──
    case 'venues':
        $type = sanitize($_GET['type'] ?? '', 30);
        $venues = $VENUE_CATALOG;
        if ($type) {
            $venues = array_values(array_filter($venues, function($v) use ($type) {
                return $v['type'] === $type;
            }));
        }
        jsonResponse(['success' => true, 'venues' => $venues, 'total' => count($venues)]);
        break;

    // ── Live Session Sync ──
    // Allows SSP DJ Mixer to push mix state → game, and game to push → SSP
    case 'sync':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
        }
        // Validate sync payload
        $syncData = [
            'session_id' => sanitize($input['session_id'] ?? uniqid('ssp_'), 50),
            'deck_a' => [
                'track_id' => sanitize($input['deck_a']['track_id'] ?? '', 20),
                'playing'  => !empty($input['deck_a']['playing']),
                'bpm'      => intval($input['deck_a']['bpm'] ?? 128),
                'position' => floatval($input['deck_a']['position'] ?? 0),
                'volume'   => min(1, max(0, floatval($input['deck_a']['volume'] ?? 1))),
            ],
            'deck_b' => [
                'track_id' => sanitize($input['deck_b']['track_id'] ?? '', 20),
                'playing'  => !empty($input['deck_b']['playing']),
                'bpm'      => intval($input['deck_b']['bpm'] ?? 128),
                'position' => floatval($input['deck_b']['position'] ?? 0),
                'volume'   => min(1, max(0, floatval($input['deck_b']['volume'] ?? 1))),
            ],
            'crossfader' => min(1, max(0, floatval($input['crossfader'] ?? 0.5))),
            'fx'         => sanitize($input['fx'] ?? [], 500),
            'venue'      => sanitize($input['venue'] ?? 'default-club', 50),
            'timestamp'  => time(),
        ];
        jsonResponse(['success' => true, 'sync' => $syncData, 'message' => 'Mix state synced']);
        break;

    // ── Leaderboard ──
    case 'leaderboard':
        // Simulated leaderboard (in production, pull from DB)
        $leaderboard = [
            ['rank' => 1, 'name' => 'DJ_DRUMAHON',  'avatar' => '🥁', 'plays' => 4520, 'streams' => 89, 'battle_wins' => 34, 'score' => 9850],
            ['rank' => 2, 'name' => 'VibeKing',      'avatar' => '👑', 'plays' => 3800, 'streams' => 67, 'battle_wins' => 28, 'score' => 8420],
            ['rank' => 3, 'name' => 'BassQueen',     'avatar' => '🎵', 'plays' => 3200, 'streams' => 54, 'battle_wins' => 31, 'score' => 7890],
            ['rank' => 4, 'name' => 'NeonDancer',    'avatar' => '✨', 'plays' => 2900, 'streams' => 45, 'battle_wins' => 22, 'score' => 6750],
            ['rank' => 5, 'name' => 'TranceGuru',    'avatar' => '🧘', 'plays' => 2650, 'streams' => 38, 'battle_wins' => 19, 'score' => 5980],
            ['rank' => 6, 'name' => 'DeepDive',      'avatar' => '🌊', 'plays' => 2400, 'streams' => 42, 'battle_wins' => 15, 'score' => 5420],
            ['rank' => 7, 'name' => 'SubBass',       'avatar' => '🔊', 'plays' => 2100, 'streams' => 31, 'battle_wins' => 20, 'score' => 4890],
            ['rank' => 8, 'name' => 'PlurLife',      'avatar' => '🌈', 'plays' => 1850, 'streams' => 28, 'battle_wins' => 12, 'score' => 4210],
            ['rank' => 9, 'name' => 'DropHunter',    'avatar' => '💥', 'plays' => 1600, 'streams' => 22, 'battle_wins' => 17, 'score' => 3780],
            ['rank' => 10,'name' => 'FreqFlyer',     'avatar' => '🛩️', 'plays' => 1400, 'streams' => 19, 'battle_wins' => 14, 'score' => 3350],
        ];
        jsonResponse(['success' => true, 'leaderboard' => $leaderboard]);
        break;

    // ── Health check ──
    case 'health':
        jsonResponse([
            'success' => true,
            'service' => 'ssp-music-api',
            'version' => '1.0.0',
            'tracks' => count($TRACK_CATALOG),
            'artists' => count($ARTIST_PROFILES),
            'venues' => count($VENUE_CATALOG),
            'timestamp' => date('c'),
        ]);
        break;

    default:
        jsonResponse([
            'success' => false,
            'error' => 'Unknown action',
            'available_actions' => ['tracks', 'track', 'genres', 'artists', 'venues', 'sync', 'leaderboard', 'health'],
        ], 400);
}
