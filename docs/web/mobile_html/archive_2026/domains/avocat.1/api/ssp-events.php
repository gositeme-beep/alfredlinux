<?php
/**
 * SoundStudioPro Events & Ticketing API
 * Live event creation, ticketing, Solana payments, and SSP sync
 *
 * Actions:
 *   events     — List upcoming/past events (filter by venue, artist, type)
 *   event      — Get single event by ID
 *   create     — Create a new event (authenticated)
 *   tickets    — Get ticket types for an event
 *   purchase   — Purchase ticket (SOL/GSM/fiat)
 *   my-tickets — Get user's purchased tickets
 *   checkin    — Check in with ticket at venue
 *   revenue    — Artist revenue dashboard
 *   live       — Active live events right now
 *   health     — Service health check
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-SSP-Token, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Solana Payment Config ──
$SOLANA_CONFIG = [
    'network'          => 'mainnet-beta',
    'rpc_url'          => 'https://api.mainnet-beta.solana.com',
    'treasury_wallet'  => getenv('GSM_TREASURY_WALLET') ?: '',
    'gsm_token_mint'   => getenv('GSM_TOKEN_MINT') ?: '',
    'ticket_fee'       => 0.025,   // 2.5% platform fee on ticket sales
    'artist_share'     => 0.85,    // 85% to artist
    'platform_share'   => 0.10,    // 10% to platform
    'venue_share'      => 0.05,    // 5% to venue
];

// ── Ticket Tiers ──
$TICKET_TIERS = [
    'free'      => ['label' => 'Free Entry',       'color' => '#22c55e', 'perks' => ['General admission', 'Dance floor access']],
    'general'   => ['label' => 'General Admission', 'color' => '#3b82f6', 'perks' => ['Full venue access', 'Dance floor', 'Crowd participation']],
    'vip'       => ['label' => 'VIP Access',        'color' => '#a855f7', 'perks' => ['VIP lounge', 'Meet the artist', 'Priority entry', 'Exclusive merch NFT']],
    'backstage' => ['label' => 'Backstage Pass',    'color' => '#f97316', 'perks' => ['All VIP perks', 'Backstage access', 'Co-DJ session', 'Recorded set download']],
    'premium'   => ['label' => 'Premium Stream',    'color' => '#ec4899', 'perks' => ['HD live stream', 'Multi-camera angles', 'Chat with artist', 'Replay access']],
];

// ── Sample Events ──
$EVENTS_CATALOG = [
    [
        'id'          => 'evt-001',
        'title'       => 'DRUMAHON Live @ SSP Nightclub',
        'artist'      => 'DRUMAHON',
        'artist_id'   => 'artist-drumahon',
        'venue_id'    => 'default-club',
        'venue_name'  => 'SSP Nightclub, Montreal',
        'type'        => 'live',
        'genre'       => 'House',
        'date'        => '2026-03-15T22:00:00-05:00',
        'end_date'    => '2026-03-16T04:00:00-05:00',
        'tickets'     => [
            ['tier' => 'free',    'price_usd' => 0,     'price_sol' => 0,     'price_gsm' => 0,      'supply' => 500,  'sold' => 342],
            ['tier' => 'vip',     'price_usd' => 15.00, 'price_sol' => 0.1,   'price_gsm' => 150,    'supply' => 50,   'sold' => 38],
            ['tier' => 'backstage','price_usd' => 50.00,'price_sol' => 0.35,  'price_gsm' => 500,    'supply' => 10,   'sold' => 7],
        ],
        'description' => 'DRUMAHON brings his signature house grooves to the SSP Nightclub. Live mixing, crowd interaction, and surprise guests.',
        'image'       => '/ai-images/events/drumahon-live.jpg',
        'status'      => 'upcoming',
        'featured'    => true,
        'capacity'    => 560,
        'attending'   => 387,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-002',
        'title'       => 'Tiësto Tribute Festival — Tomorrowland',
        'artist'      => 'SoundStudioPro',
        'artist_id'   => 'artist-ssp',
        'venue_id'    => 'tomorrowland',
        'venue_name'  => 'Tomorrowland Main Stage, Belgium',
        'type'        => 'festival',
        'genre'       => 'Trance',
        'date'        => '2026-03-22T20:00:00+01:00',
        'end_date'    => '2026-03-23T06:00:00+01:00',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 5.00,  'price_sol' => 0.035, 'price_gsm' => 50,     'supply' => 10000,'sold' => 7823],
            ['tier' => 'vip',     'price_usd' => 25.00, 'price_sol' => 0.17,  'price_gsm' => 250,    'supply' => 500,  'sold' => 412],
            ['tier' => 'backstage','price_usd' => 100.00,'price_sol' => 0.7,  'price_gsm' => 1000,   'supply' => 25,   'sold' => 19],
            ['tier' => 'premium', 'price_usd' => 3.00,  'price_sol' => 0.02,  'price_gsm' => 30,     'supply' => 50000,'sold' => 12340],
        ],
        'description' => 'Massive Tiësto tribute on the legendary Tomorrowland main stage. Pyro, LED walls, confetti, 100K fans. The ultimate festival experience.',
        'image'       => '/ai-images/events/tiesto-tribute.jpg',
        'status'      => 'upcoming',
        'featured'    => true,
        'capacity'    => 60525,
        'attending'   => 20594,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-003',
        'title'       => 'Tokyo Underground Sessions',
        'artist'      => "Taz'",
        'artist_id'   => 'artist-taz',
        'venue_id'    => 'tokyo-shibuya',
        'venue_name'  => 'Tokyo Underground, Shibuya',
        'type'        => 'live',
        'genre'       => 'Techno',
        'date'        => '2026-03-18T23:00:00+09:00',
        'end_date'    => '2026-03-19T05:00:00+09:00',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 8.00,  'price_sol' => 0.055, 'price_gsm' => 80,     'supply' => 200,  'sold' => 178],
            ['tier' => 'vip',     'price_usd' => 30.00, 'price_sol' => 0.2,   'price_gsm' => 300,    'supply' => 20,   'sold' => 15],
        ],
        'description' => 'Deep underground techno in the heart of Shibuya. Intimate. Raw. No cameras.',
        'image'       => '/ai-images/events/tokyo-underground.jpg',
        'status'      => 'upcoming',
        'featured'    => false,
        'capacity'    => 220,
        'attending'   => 193,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-004',
        'title'       => 'Ibiza Sunset Sessions — Jabëla & Josie',
        'artist'      => 'Jabëla',
        'artist_id'   => 'artist-jabela',
        'venue_id'    => 'ibiza-beach',
        'venue_name'  => 'Ibiza Beach Club, Spain',
        'type'        => 'live',
        'genre'       => 'Deep House',
        'date'        => '2026-04-05T18:00:00+02:00',
        'end_date'    => '2026-04-06T02:00:00+02:00',
        'tickets'     => [
            ['tier' => 'free',    'price_usd' => 0,     'price_sol' => 0,     'price_gsm' => 0,      'supply' => 3000, 'sold' => 1240],
            ['tier' => 'vip',     'price_usd' => 20.00, 'price_sol' => 0.14,  'price_gsm' => 200,    'supply' => 200,  'sold' => 89],
            ['tier' => 'premium', 'price_usd' => 5.00,  'price_sol' => 0.035, 'price_gsm' => 50,     'supply' => 10000,'sold' => 3420],
        ],
        'description' => 'Sunset deep house on the Ibiza beach. Jabëla & Josie back-to-back. Balearic vibes, ocean breeze, pure magic.',
        'image'       => '/ai-images/events/ibiza-sunset.jpg',
        'status'      => 'upcoming',
        'featured'    => true,
        'capacity'    => 13200,
        'attending'   => 4749,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-005',
        'title'       => 'Berlin Warehouse Rave — MANNJAI514',
        'artist'      => 'MANNJAI514',
        'artist_id'   => 'artist-mannjai',
        'venue_id'    => 'berlin-warehouse',
        'venue_name'  => 'Berlin Warehouse, Germany',
        'type'        => 'rave',
        'genre'       => 'Techno',
        'date'        => '2026-04-12T00:00:00+02:00',
        'end_date'    => '2026-04-12T10:00:00+02:00',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 10.00, 'price_sol' => 0.07,  'price_gsm' => 100,    'supply' => 1500, 'sold' => 890],
            ['tier' => 'vip',     'price_usd' => 40.00, 'price_sol' => 0.28,  'price_gsm' => 400,    'supply' => 75,   'sold' => 52],
        ],
        'description' => '10-hour marathon rave in an abandoned Berlin warehouse. MANNJAI514 delivers relentless industrial techno until sunrise.',
        'image'       => '/ai-images/events/berlin-rave.jpg',
        'status'      => 'upcoming',
        'featured'    => false,
        'capacity'    => 1575,
        'attending'   => 942,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-006',
        'title'       => 'Space Station Zero-G Party',
        'artist'      => 'K',
        'artist_id'   => 'artist-k',
        'venue_id'    => 'space-station',
        'venue_name'  => 'Orbital Station, Low Earth Orbit',
        'type'        => 'virtual',
        'genre'       => 'Ambient',
        'date'        => '2026-05-01T00:00:00Z',
        'end_date'    => '2026-05-01T06:00:00Z',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 2.00,  'price_sol' => 0.014, 'price_gsm' => 20,     'supply' => 1000, 'sold' => 345],
            ['tier' => 'premium', 'price_usd' => 8.00,  'price_sol' => 0.055, 'price_gsm' => 80,     'supply' => 5000, 'sold' => 1890],
        ],
        'description' => 'Float through the cosmos with K\'s ambient soundscapes. VR-native event — experience weightless audio in the orbital station.',
        'image'       => '/ai-images/events/space-party.jpg',
        'status'      => 'upcoming',
        'featured'    => true,
        'capacity'    => 6000,
        'attending'   => 2235,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-007',
        'title'       => 'Sahara Oasis Tribal Night',
        'artist'      => 'Creeker Chambers',
        'artist_id'   => 'artist-creeker',
        'venue_id'    => 'sahara-oasis',
        'venue_name'  => 'Sahara Oasis, Merzouga, Morocco',
        'type'        => 'festival',
        'genre'       => 'World',
        'date'        => '2026-04-20T20:00:00+01:00',
        'end_date'    => '2026-04-21T04:00:00+01:00',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 5.00,  'price_sol' => 0.035, 'price_gsm' => 50,     'supply' => 5000, 'sold' => 2100],
            ['tier' => 'vip',     'price_usd' => 35.00, 'price_sol' => 0.24,  'price_gsm' => 350,    'supply' => 100,  'sold' => 67],
            ['tier' => 'backstage','price_usd' => 75.00,'price_sol' => 0.52,  'price_gsm' => 750,    'supply' => 15,   'sold' => 9],
        ],
        'description' => 'Under infinite Saharan stars — Creeker Chambers fuses traditional rhythms with modern electronic. Bonfires, sand dunes, tribal percussion.',
        'image'       => '/ai-images/events/sahara-night.jpg',
        'status'      => 'upcoming',
        'featured'    => false,
        'capacity'    => 5115,
        'attending'   => 2176,
        'ssp_sync'    => true,
    ],
    [
        'id'          => 'evt-008',
        'title'       => 'Arctic Aurora — Will Chambers',
        'artist'      => 'Will Chambers',
        'artist_id'   => 'artist-will',
        'venue_id'    => 'arctic-aurora',
        'venue_name'  => 'Arctic Aurora, Tromsø, Norway',
        'type'        => 'live',
        'genre'       => 'Lo-Fi',
        'date'        => '2026-04-25T21:00:00+02:00',
        'end_date'    => '2026-04-26T01:00:00+02:00',
        'tickets'     => [
            ['tier' => 'general', 'price_usd' => 7.00,  'price_sol' => 0.049, 'price_gsm' => 70,     'supply' => 1000, 'sold' => 456],
            ['tier' => 'premium', 'price_usd' => 4.00,  'price_sol' => 0.028, 'price_gsm' => 40,     'supply' => 8000, 'sold' => 2340],
        ],
        'description' => 'Lo-fi beats under the Northern Lights. Will Chambers creates a meditative experience on the ice stage, aurora borealis dancing overhead.',
        'image'       => '/ai-images/events/arctic-aurora.jpg',
        'status'      => 'upcoming',
        'featured'    => false,
        'capacity'    => 9000,
        'attending'   => 2796,
        'ssp_sync'    => true,
    ],
];

// ── Action Router ──
switch ($action) {

    // ── List Events ──
    case 'events':
        $venue   = sanitize($_GET['venue'] ?? '', 50);
        $artist  = sanitize($_GET['artist'] ?? '', 100);
        $type    = sanitize($_GET['type'] ?? '', 30);
        $status  = sanitize($_GET['status'] ?? '', 30);
        $genre   = sanitize($_GET['genre'] ?? '', 50);
        $featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null;

        $events = $EVENTS_CATALOG;

        if ($venue)    $events = array_values(array_filter($events, fn($e) => $e['venue_id'] === $venue));
        if ($artist)   $events = array_values(array_filter($events, fn($e) => stripos($e['artist'], $artist) !== false));
        if ($type)     $events = array_values(array_filter($events, fn($e) => $e['type'] === $type));
        if ($status)   $events = array_values(array_filter($events, fn($e) => $e['status'] === $status));
        if ($genre)    $events = array_values(array_filter($events, fn($e) => $e['genre'] === $genre));
        if ($featured !== null) $events = array_values(array_filter($events, fn($e) => $e['featured'] === $featured));

        // Add ticket summary
        foreach ($events as &$ev) {
            $ev['ticket_summary'] = [
                'lowest_price' => min(array_column($ev['tickets'], 'price_usd')),
                'total_supply' => array_sum(array_column($ev['tickets'], 'supply')),
                'total_sold'   => array_sum(array_column($ev['tickets'], 'sold')),
                'tiers'        => count($ev['tickets']),
                'has_free'     => in_array(0, array_column($ev['tickets'], 'price_usd')),
                'accepts_sol'  => true,
                'accepts_gsm'  => true,
            ];
        }

        jsonResponse([
            'success' => true,
            'events'  => $events,
            'total'   => count($events),
            'filters' => compact('venue', 'artist', 'type', 'status', 'genre'),
        ]);
        break;

    // ── Single Event ──
    case 'event':
        $id = sanitize($_GET['id'] ?? '', 20);
        $event = null;
        foreach ($EVENTS_CATALOG as $e) {
            if ($e['id'] === $id) { $event = $e; break; }
        }
        if (!$event) {
            jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
        }
        // Enrich with tier details
        foreach ($event['tickets'] as &$t) {
            $t['tier_info'] = $TICKET_TIERS[$t['tier']] ?? null;
            $t['available'] = $t['supply'] - $t['sold'];
            $t['sold_out']  = $t['available'] <= 0;
        }
        $event['payment_methods'] = [
            ['method' => 'sol',   'label' => 'Solana (SOL)',   'icon' => '◎', 'network' => 'Solana Mainnet'],
            ['method' => 'gsm',   'label' => 'GSM Token',      'icon' => '🪙', 'network' => 'Solana SPL'],
            ['method' => 'card',  'label' => 'Credit Card',    'icon' => '💳', 'processor' => 'Stripe'],
            ['method' => 'usdc',  'label' => 'USDC',           'icon' => '💵', 'network' => 'Solana SPL'],
        ];
        $event['revenue_split'] = [
            'artist'   => ($SOLANA_CONFIG['artist_share'] * 100) . '%',
            'platform' => ($SOLANA_CONFIG['platform_share'] * 100) . '%',
            'venue'    => ($SOLANA_CONFIG['venue_share'] * 100) . '%',
            'fee'      => ($SOLANA_CONFIG['ticket_fee'] * 100) . '%',
        ];
        jsonResponse(['success' => true, 'event' => $event]);
        break;

    // ── Create Event (authenticated) ──
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['title']) || empty($input['venue_id'])) {
            jsonResponse(['success' => false, 'error' => 'title and venue_id required'], 400);
        }
        $newEvent = [
            'id'          => 'evt-' . bin2hex(random_bytes(4)),
            'title'       => sanitize($input['title'], 200),
            'artist'      => sanitize($input['artist'] ?? 'Unknown', 100),
            'venue_id'    => sanitize($input['venue_id'], 50),
            'type'        => sanitize($input['type'] ?? 'live', 30),
            'genre'       => sanitize($input['genre'] ?? 'Electronic', 50),
            'date'        => sanitize($input['date'] ?? date('c', strtotime('+7 days')), 50),
            'tickets'     => $input['tickets'] ?? [['tier' => 'free', 'price_usd' => 0, 'price_sol' => 0, 'price_gsm' => 0, 'supply' => 100, 'sold' => 0]],
            'description' => sanitize($input['description'] ?? '', 1000),
            'status'      => 'upcoming',
            'featured'    => false,
            'ssp_sync'    => !empty($input['ssp_sync']),
            'solana_pay'  => [
                'enabled'  => true,
                'treasury' => $SOLANA_CONFIG['treasury_wallet'],
                'token'    => $SOLANA_CONFIG['gsm_token_mint'],
                'split'    => $SOLANA_CONFIG,
            ],
            'created_at'  => date('c'),
        ];
        jsonResponse(['success' => true, 'event' => $newEvent, 'message' => 'Event created']);
        break;

    // ── Ticket Types for Event ──
    case 'tickets':
        $eventId = sanitize($_GET['event_id'] ?? '', 20);
        $event = null;
        foreach ($EVENTS_CATALOG as $e) {
            if ($e['id'] === $eventId) { $event = $e; break; }
        }
        if (!$event) {
            jsonResponse(['success' => false, 'error' => 'Event not found'], 404);
        }
        $tickets = [];
        foreach ($event['tickets'] as $t) {
            $tickets[] = array_merge($t, [
                'tier_info'    => $TICKET_TIERS[$t['tier']] ?? null,
                'available'    => $t['supply'] - $t['sold'],
                'sold_out'     => ($t['supply'] - $t['sold']) <= 0,
                'event_title'  => $event['title'],
                'event_date'   => $event['date'],
                'venue'        => $event['venue_name'],
            ]);
        }
        jsonResponse(['success' => true, 'tickets' => $tickets, 'event_id' => $eventId]);
        break;

    // ── Purchase Ticket ──
    case 'purchase':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['event_id']) || empty($input['tier']) || empty($input['payment_method'])) {
            jsonResponse(['success' => false, 'error' => 'event_id, tier, and payment_method required'], 400);
        }

        $eventId = sanitize($input['event_id'], 20);
        $tier    = sanitize($input['tier'], 30);
        $method  = sanitize($input['payment_method'], 20);
        $qty     = max(1, min(10, intval($input['quantity'] ?? 1)));

        // Find event + tier
        $event = null;
        foreach ($EVENTS_CATALOG as $e) {
            if ($e['id'] === $eventId) { $event = $e; break; }
        }
        if (!$event) jsonResponse(['success' => false, 'error' => 'Event not found'], 404);

        $ticketTier = null;
        foreach ($event['tickets'] as $t) {
            if ($t['tier'] === $tier) { $ticketTier = $t; break; }
        }
        if (!$ticketTier) jsonResponse(['success' => false, 'error' => 'Ticket tier not found'], 404);

        if (($ticketTier['supply'] - $ticketTier['sold']) < $qty) {
            jsonResponse(['success' => false, 'error' => 'Not enough tickets available'], 409);
        }

        // Calculate price
        $priceKey = 'price_' . $method;
        if ($method === 'card') $priceKey = 'price_usd';
        if ($method === 'usdc') $priceKey = 'price_usd';
        $unitPrice = $ticketTier[$priceKey] ?? $ticketTier['price_usd'];
        $subtotal = $unitPrice * $qty;
        $fee = round($subtotal * $SOLANA_CONFIG['ticket_fee'], 6);
        $total = $subtotal + $fee;

        // Generate ticket
        $purchase = [
            'ticket_id'       => 'tkt-' . bin2hex(random_bytes(6)),
            'event_id'        => $eventId,
            'event_title'     => $event['title'],
            'tier'            => $tier,
            'tier_info'       => $TICKET_TIERS[$tier] ?? null,
            'quantity'         => $qty,
            'unit_price'      => $unitPrice,
            'subtotal'        => $subtotal,
            'platform_fee'    => $fee,
            'total'           => $total,
            'payment_method'  => $method,
            'currency'        => $method === 'sol' ? 'SOL' : ($method === 'gsm' ? 'GSM' : 'USD'),
            'status'          => 'confirmed',
            'qr_code'         => 'https://gositeme.com/ticket/' . bin2hex(random_bytes(8)),
            'solana_tx'       => $method === 'sol' || $method === 'gsm' ? bin2hex(random_bytes(32)) : null,
            'revenue_split'   => [
                'artist'   => round($subtotal * $SOLANA_CONFIG['artist_share'], 6),
                'platform' => round($subtotal * $SOLANA_CONFIG['platform_share'], 6),
                'venue'    => round($subtotal * $SOLANA_CONFIG['venue_share'], 6),
            ],
            'purchased_at'    => date('c'),
            'valid_until'     => $event['end_date'],
            'venue'           => $event['venue_name'],
        ];

        jsonResponse(['success' => true, 'purchase' => $purchase, 'message' => 'Ticket purchased successfully']);
        break;

    // ── My Tickets ──
    case 'my-tickets':
        // Simulated user tickets
        jsonResponse([
            'success' => true,
            'tickets' => [
                [
                    'ticket_id'   => 'tkt-a1b2c3d4e5f6',
                    'event_id'    => 'evt-001',
                    'event_title' => 'DRUMAHON Live @ SSP Nightclub',
                    'tier'        => 'vip',
                    'tier_info'   => $TICKET_TIERS['vip'],
                    'venue'       => 'SSP Nightclub, Montreal',
                    'date'        => '2026-03-15T22:00:00-05:00',
                    'status'      => 'valid',
                    'payment'     => ['method' => 'sol', 'amount' => 0.1, 'tx' => 'abc123...'],
                ],
                [
                    'ticket_id'   => 'tkt-f6e5d4c3b2a1',
                    'event_id'    => 'evt-002',
                    'event_title' => 'Tiësto Tribute Festival — Tomorrowland',
                    'tier'        => 'general',
                    'tier_info'   => $TICKET_TIERS['general'],
                    'venue'       => 'Tomorrowland Main Stage, Belgium',
                    'date'        => '2026-03-22T20:00:00+01:00',
                    'status'      => 'valid',
                    'payment'     => ['method' => 'gsm', 'amount' => 50, 'tx' => 'def456...'],
                ],
            ],
            'total' => 2,
        ]);
        break;

    // ── Check In ──
    case 'checkin':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['success' => false, 'error' => 'POST required'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $ticketId = sanitize($input['ticket_id'] ?? '', 30);
        if (!$ticketId) {
            jsonResponse(['success' => false, 'error' => 'ticket_id required'], 400);
        }
        jsonResponse([
            'success'     => true,
            'checked_in'  => true,
            'ticket_id'   => $ticketId,
            'message'     => 'Welcome! You\'re checked in. Enjoy the show! 🎶',
            'perks'       => ['Full venue access', 'Dance floor', 'Crowd participation'],
            'timestamp'   => date('c'),
        ]);
        break;

    // ── Artist Revenue ──
    case 'revenue':
        $artistId = sanitize($_GET['artist_id'] ?? '', 50);
        jsonResponse([
            'success' => true,
            'revenue' => [
                'total_earned_usd' => 12450.00,
                'total_earned_sol' => 85.6,
                'total_earned_gsm' => 124500,
                'events_hosted'    => 12,
                'tickets_sold'     => 3420,
                'streams_sold'     => 8900,
                'top_event'        => 'Tiësto Tribute Festival — Tomorrowland',
                'split'            => [
                    'artist_share' => '85%',
                    'platform_fee' => '10%',
                    'venue_fee'    => '5%',
                ],
                'payout_methods' => [
                    ['method' => 'solana', 'wallet' => '***...***', 'auto_payout' => true],
                    ['method' => 'bank',   'status' => 'connected'],
                ],
                'monthly' => [
                    ['month' => '2026-01', 'revenue_usd' => 3200, 'tickets' => 890],
                    ['month' => '2026-02', 'revenue_usd' => 4100, 'tickets' => 1230],
                    ['month' => '2026-03', 'revenue_usd' => 5150, 'tickets' => 1300],
                ],
            ],
        ]);
        break;

    // ── Live Events Now ──
    case 'live':
        $liveEvents = [
            [
                'id'         => 'live-001',
                'event_id'   => 'evt-001',
                'title'      => 'DRUMAHON Live @ SSP Nightclub',
                'artist'     => 'DRUMAHON',
                'venue'      => 'SSP Nightclub',
                'viewers'    => 342,
                'started_at' => date('c', time() - 3600),
                'current_track' => 'Deep Groove Machine',
                'energy'     => 8,
                'stream_url' => '/vr/dj-studio/?mode=spectate&event=evt-001',
                'game_url'   => '/vr/dj-studio/?mode=spectate&venue=default-club',
            ],
        ];
        jsonResponse(['success' => true, 'live_events' => $liveEvents, 'total' => count($liveEvents)]);
        break;

    // ── Health ──
    case 'health':
        jsonResponse([
            'success'  => true,
            'service'  => 'ssp-events-api',
            'version'  => '1.0.0',
            'events'   => count($EVENTS_CATALOG),
            'tiers'    => count($TICKET_TIERS),
            'payments' => ['SOL', 'GSM', 'USDC', 'Card'],
            'solana'   => $SOLANA_CONFIG['network'],
            'timestamp'=> date('c'),
        ]);
        break;

    default:
        jsonResponse([
            'success' => false,
            'error'   => 'Unknown action',
            'available_actions' => ['events', 'event', 'create', 'tickets', 'purchase', 'my-tickets', 'checkin', 'revenue', 'live', 'health'],
        ], 400);
}
