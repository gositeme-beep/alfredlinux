<?php
/**
 * Client Dashboard API
 * Returns client services, domains, invoices, tickets
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');

// Check authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$clientId = $_SESSION['client_id'];
$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        getDashboard($clientId);
        break;
    case 'services':
        getServices($clientId);
        break;
    case 'domains':
        getDomains($clientId);
        break;
    case 'invoices':
        getInvoices($clientId);
        break;
    case 'tickets':
        getTickets($clientId);
        break;
    case 'profile':
        getProfile($clientId);
        break;
    case 'update_profile':
        updateProfile($clientId);
        break;
    case 'credit':
        getCredit($clientId);
        break;
    case 'emails':
        getEmails($clientId);
        break;
    case 'quotes':
        getQuotes($clientId);
        break;
    case 'payment_methods':
        getPaymentMethods($clientId);
        break;
    default:
        jsonResponse(['error' => 'Invalid action'], 400);
}

/**
 * Get dashboard overview
 */
function getDashboard($clientId) {
    $db = getDB();
    
    // Get counts
    $stats = [
        'active_services' => 0,
        'active_domains' => 0,
        'unpaid_invoices' => 0,
        'open_tickets' => 0,
        'total_due' => 0
    ];
    
    // Active services
    $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
    $stmt->execute([$clientId]);
    $stats['active_services'] = (int)$stmt->fetchColumn();
    
    // Active domains
    $stmt = $db->prepare("SELECT COUNT(*) FROM domains WHERE client_id = ? AND status = 'Active'");
    $stmt->execute([$clientId]);
    $stats['active_domains'] = (int)$stmt->fetchColumn();
    
    // Unpaid invoices
    $stmt = $db->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM invoices WHERE client_id = ? AND status = 'Unpaid'");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $stats['unpaid_invoices'] = (int)$row[0];
    $stats['total_due'] = number_format($row[1], 2);
    
    // Open tickets
    $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE client_id = ? AND status IN ('Open', 'Answered', 'Customer-Reply')");
    $stmt->execute([$clientId]);
    $stats['open_tickets'] = (int)$stmt->fetchColumn();
    
    // Recent activity
    $stmt = $db->prepare("
        SELECT date, description 
        FROM activity_log 
        WHERE userid = ? 
        ORDER BY date DESC 
        LIMIT 5
    ");
    $stmt->execute([$clientId]);
    $recentActivity = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'stats' => $stats,
        'recent_activity' => $recentActivity
    ]);
}

/**
 * Get client services/hosting
 */
function getServices($clientId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            h.id,
            h.domain,
            h.status as status,
            h.registration_date as regdate,
            h.next_due_date,
            h.amount,
            h.billing_cycle,
            p.name as product_name,
            p.type as product_type,
            p.server_module,
            g.name as group_name
        FROM services h
        JOIN products p ON h.product_id = p.id
        JOIN product_groups g ON p.group_id = g.id
        WHERE h.client_id = ?
        ORDER BY h.status = 'Active' DESC, h.id DESC
    ");
    $stmt->execute([$clientId]);
    $services = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($services as $service) {
        $formatted[] = [
            'id' => $service['id'],
            'product' => $service['product_name'],
            'group' => $service['group_name'],
            'domain' => $service['domain'],
            'status' => $service['status'],
            'status_class' => getStatusClass($service['status']),
            'registered' => $service['regdate'],
            'next_due' => $service['next_due_date'],
            'amount' => '$' . number_format($service['amount'], 2),
            'amount_raw' => floatval($service['amount']),
            'cycle' => ucfirst($service['billing_cycle']),
            'product_type' => $service['product_type'] ?? '',
            'server_module' => $service['server_module'] ?? ''
        ];
    }
    
    jsonResponse([
        'success' => true,
        'services' => $formatted
    ]);
}

/**
 * Get client domains
 */
function getDomains($clientId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            domain,
            status,
            registration_date as registrationdate,
            expiry_date as expirydate,
            registrar,
            recurring_amount as recurringamount
        FROM domains
        WHERE client_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$clientId]);
    $domains = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($domains as $domain) {
        $daysUntilExpiry = floor((strtotime($domain['expirydate']) - time()) / 86400);
        
        $formatted[] = [
            'id' => $domain['id'],
            'domain' => $domain['domain'],
            'status' => $domain['status'],
            'status_class' => getStatusClass($domain['status']),
            'registered' => $domain['registrationdate'],
            'expires' => $domain['expirydate'],
            'days_until_expiry' => $daysUntilExpiry,
            'expiring_soon' => $daysUntilExpiry <= 30,
            'renewal_price' => '$' . number_format($domain['recurringamount'], 2)
        ];
    }
    
    jsonResponse([
        'success' => true,
        'domains' => $formatted
    ]);
}

/**
 * Get client invoices
 */
function getInvoices($clientId) {
    $db = getDB();
    
    $status = sanitize($_GET['status'] ?? 'all');
    
    $sql = "
        SELECT 
            id,
            invoice_number,
            created_at as date,
            due_date as duedate,
            total,
            status
        FROM invoices
        WHERE client_id = ?
    ";
    
    $params = [$clientId];
    
    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = ucfirst($status);
    }
    
    $sql .= " ORDER BY id DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($invoices as $invoice) {
        $formatted[] = [
            'id' => $invoice['id'],
            'number' => $invoice['invoice_number'] ?: $invoice['id'],
            'date' => $invoice['date'],
            'due_date' => $invoice['duedate'],
            'total' => '$' . number_format($invoice['total'], 2),
            'status' => $invoice['status'],
            'status_class' => getStatusClass($invoice['status']),
            'pay_url' => '/view-invoice?id=' . $invoice['id']
        ];
    }
    
    jsonResponse([
        'success' => true,
        'invoices' => $formatted
    ]);
}

/**
 * Get client tickets
 */
function getTickets($clientId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.tid,
            t.subject,
            t.status,
            t.priority,
            t.created_at,
            t.last_reply,
            d.name as department
        FROM tickets t
        LEFT JOIN ticket_departments d ON t.department_id = d.id
        WHERE t.client_id = ?
        ORDER BY t.last_reply DESC
        LIMIT 50
    ");
    $stmt->execute([$clientId]);
    $tickets = $stmt->fetchAll();
    
    $formatted = [];
    foreach ($tickets as $ticket) {
        $formatted[] = [
            'id' => $ticket['id'],
            'ticket_id' => $ticket['tid'],
            'subject' => $ticket['subject'],
            'department' => $ticket['department'],
            'status' => $ticket['status'],
            'status_class' => getTicketStatusClass($ticket['status']),
            'priority' => $ticket['priority'],
            'created' => $ticket['created_at'],
            'last_reply' => $ticket['last_reply'],
            'view_url' => '/view-ticket?tid=' . $ticket['tid']
        ];
    }
    
    jsonResponse([
        'success' => true,
        'tickets' => $formatted
    ]);
}

/**
 * Get client profile
 */
function getProfile($clientId) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            id, firstname, lastname, company, email,
            address1, address2, city, state, postcode, country,
            phone, date_created, status
        FROM clients
        WHERE id = ?
    ");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        jsonResponse(['error' => 'Client not found'], 404);
    }
    
    jsonResponse([
        'success' => true,
        'profile' => [
            'id' => $client['id'],
            'name' => $client['firstname'] . ' ' . $client['lastname'],
            'firstname' => $client['firstname'],
            'lastname' => $client['lastname'],
            'company' => $client['company'],
            'email' => $client['email'],
            'address' => [
                'line1' => $client['address1'],
                'line2' => $client['address2'],
                'city' => $client['city'],
                'state' => $client['state'],
                'postcode' => $client['postcode'],
                'country' => $client['country']
            ],
            'phone' => $client['phone'],
            'member_since' => $client['date_created'],
            'status' => $client['status']
        ]
    ]);
}

/**
 * Update client profile (POST)
 */
function updateProfile($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $db = getDB();

    // Allowed fields for update
    $allowed = ['firstname', 'lastname', 'company', 'address1', 'address2', 'city', 'state', 'postcode', 'country', 'phone'];
    $sets = [];
    $values = [];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $sets[] = "$field = ?";
            $values[] = sanitize($input[$field]);
        }
    }

    if (empty($sets)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $values[] = $clientId;
    $sql = "UPDATE clients SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // Log activity
    $logStmt = $db->prepare("INSERT INTO activity_log (date, description, userid, ipaddr) VALUES (NOW(), 'Client Updated Profile via Dashboard', ?, ?)");
    $logStmt->execute([$clientId, $_SERVER['REMOTE_ADDR'] ?? '']);

    jsonResponse([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
}

/**
 * Get client credit balance and history
 */
function getCredit($clientId) {
    $db = getDB();

    // Credit balance from clients table
    $stmt = $db->prepare("SELECT credit FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $balance = (float) $stmt->fetchColumn();

    // Credit history
    $stmt = $db->prepare("
        SELECT id, date, description, amount
        FROM credit_log
        WHERE client_id = ?
        ORDER BY id DESC
        LIMIT 20
    ");
    $stmt->execute([$clientId]);
    $history = $stmt->fetchAll();

    $formatted = [];
    foreach ($history as $row) {
        $formatted[] = [
            'id'          => $row['id'],
            'date'        => $row['date'],
            'description' => $row['description'],
            'amount'      => ($row['amount'] >= 0 ? '+' : '') . '$' . number_format($row['amount'], 2)
        ];
    }

    jsonResponse([
        'success' => true,
        'balance' => '$' . number_format($balance, 2),
        'balance_raw' => $balance,
        'history' => $formatted
    ]);
}

/**
 * Get client email history
 */
function getEmails($clientId) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, subject, created_at as date, to_email as `to`
        FROM email_log
        WHERE client_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$clientId]);
    $emails = $stmt->fetchAll();

    $formatted = [];
    foreach ($emails as $email) {
        $formatted[] = [
            'id'      => $email['id'],
            'subject' => $email['subject'],
            'date'    => $email['date'],
            'to'      => $email['to']
        ];
    }

    jsonResponse([
        'success' => true,
        'emails' => $formatted
    ]);
}

/**
 * Get client quotes
 */
function getQuotes($clientId) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, subject, stage, datecreated as date_created, validuntil, total
        FROM quotes
        WHERE userid = ?
        ORDER BY id DESC
        LIMIT 50
    ");
    $stmt->execute([$clientId]);
    $quotes = $stmt->fetchAll();

    $formatted = [];
    foreach ($quotes as $quote) {
        $stageClasses = [
            'Draft'     => 'secondary',
            'Delivered' => 'info',
            'On Hold'   => 'warning',
            'Accepted'  => 'success',
            'Lost'      => 'danger',
            'Dead'      => 'secondary'
        ];

        $formatted[] = [
            'id'          => $quote['id'],
            'subject'     => $quote['subject'],
            'stage'       => $quote['stage'],
            'stage_class' => $stageClasses[$quote['stage']] ?? 'secondary',
            'created'     => $quote['date_created'],
            'valid_until' => $quote['validuntil'],
            'total'       => '$' . number_format($quote['total'], 2),
            'view_url'    => '/view-quote?id=' . $quote['id']
        ];
    }

    jsonResponse([
        'success' => true,
        'quotes' => $formatted
    ]);
}

/**
 * Get client saved payment methods
 */
function getPaymentMethods($clientId) {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT id, description, gateway_name, created_at
        FROM payment_methods
        WHERE userid = ? AND deleted_at IS NULL
        ORDER BY order_preference ASC, id DESC
    ");
    $stmt->execute([$clientId]);
    $methods = $stmt->fetchAll();

    $formatted = [];
    foreach ($methods as $m) {
        // Gateway display names
        $gatewayNames = [
            'stripe'       => 'Credit/Debit Card',
            'paypal'       => 'PayPal',
            'stripe_ach'   => 'Bank Account (ACH)',
            'paypalcheckout' => 'PayPal',
            'authorizecim' => 'Credit Card'
        ];

        $gatewayIcons = [
            'stripe'       => 'fa-credit-card',
            'paypal'       => 'fa-brands fa-paypal',
            'stripe_ach'   => 'fa-building-columns',
            'paypalcheckout' => 'fa-brands fa-paypal',
            'authorizecim' => 'fa-credit-card'
        ];

        $formatted[] = [
            'id'           => $m['id'],
            'description'  => $m['description'],
            'gateway'      => $gatewayNames[$m['gateway_name']] ?? ucfirst($m['gateway_name']),
            'gateway_icon' => $gatewayIcons[$m['gateway_name']] ?? 'fa-credit-card',
            'created'      => $m['created_at']
        ];
    }

    jsonResponse([
        'success' => true,
        'payment_methods' => $formatted
    ]);
}

/**
 * Get CSS class for status
 */
function getStatusClass($status) {
    $classes = [
        'Active' => 'success',
        'Pending' => 'warning',
        'Suspended' => 'danger',
        'Terminated' => 'danger',
        'Cancelled' => 'secondary',
        'Unpaid' => 'danger',
        'Paid' => 'success',
        'Expired' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

/**
 * Get CSS class for ticket status
 */
function getTicketStatusClass($status) {
    $classes = [
        'Open' => 'warning',
        'Answered' => 'info',
        'Customer-Reply' => 'primary',
        'Closed' => 'secondary',
        'In Progress' => 'info'
    ];
    return $classes[$status] ?? 'secondary';
}
