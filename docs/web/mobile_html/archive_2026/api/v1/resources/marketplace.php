<?php
/**
 * Alfred API v1 — Marketplace Resource Handler
 *
 * Endpoints:
 *   GET /marketplace          — Browse marketplace listings
 *   GET /marketplace/{id}     — Get listing details
 *
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

/**
 * Handle marketplace requests
 */
function handleMarketplaceRequest(array $ctx): void
{
    $method = $ctx['method'];
    $route  = $ctx['route'];
    $id     = $route['id'] ?? null;

    if ($method === 'GET' && $id === null) {
        browseMarketplace($ctx);
    } elseif ($method === 'GET' && $id !== null) {
        getMarketplaceItem($ctx, (int) $id);
    } else {
        respondError("Method {$method} not allowed on /marketplace", 405, 'method_not_allowed');
    }
}

/**
 * GET /marketplace — Browse marketplace with filters and pagination
 */
function browseMarketplace(array $ctx): void
{
    requireScopes($ctx['auth'], 'marketplace:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $pg       = getPagination();
    $search   = sanitizeInput($_GET['search'] ?? $_GET['q'] ?? '', 200);
    $category = sanitizeInput($_GET['category'] ?? '', 80);
    $type     = sanitizeInput($_GET['type'] ?? '', 20);
    $sort     = sanitizeInput($_GET['sort'] ?? 'created_at', 30);
    $order    = strtoupper(sanitizeInput($_GET['order'] ?? 'DESC', 4));
    $free     = isset($_GET['free']) ? (bool) $_GET['free'] : null;

    // Validate sort columns
    $allowedSorts = ['created_at', 'rating', 'downloads', 'price', 'title'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'created_at';
    }
    if (!in_array($order, ['ASC', 'DESC'])) {
        $order = 'DESC';
    }

    try {
        $where  = "status = 'active'";
        $params = [];

        if ($search !== '') {
            $where .= ' AND (title LIKE :search OR description LIKE :search2)';
            $params[':search']  = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }
        if ($category !== '') {
            $where .= ' AND category = :category';
            $params[':category'] = $category;
        }
        if ($type !== '' && in_array($type, ['tool', 'template', 'workflow', 'integration'])) {
            $where .= ' AND item_type = :type';
            $params[':type'] = $type;
        }
        if ($free === true) {
            $where .= ' AND price = 0';
        } elseif ($free === false) {
            $where .= ' AND price > 0';
        }

        // Count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_marketplace_items WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT id, seller_user_id, item_type, title, description, price, currency, category, tags, downloads, rating, review_count, created_at
                FROM alfred_marketplace_items
                WHERE {$where}
                ORDER BY `{$sort}` {$order}
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $items = array_map(
            fn($r) => formatRow($r, ['id', 'seller_user_id', 'downloads', 'review_count'], ['tags']),
            $items
        );

        // Cast numeric fields
        foreach ($items as &$item) {
            $item['price']  = (float) $item['price'];
            $item['rating'] = $item['rating'] !== null ? (float) $item['rating'] : null;
        }

        logUsage($ctx['auth']['user_id'], 'marketplace', 1, 'GET /marketplace');

        respond(paginatedResponse($items, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 marketplace: browse failed: ' . $e->getMessage());
        respondError('Failed to browse marketplace', 500, 'internal_error');
    }
}

/**
 * GET /marketplace/{id} — Get marketplace item details
 */
function getMarketplaceItem(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'marketplace:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    try {
        $stmt = $db->prepare("
            SELECT m.*, c.firstname as seller_name
            FROM alfred_marketplace_items m
            LEFT JOIN clients c ON m.seller_user_id = c.id
            WHERE m.id = :id AND m.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            respondError('Marketplace item not found', 404, 'item_not_found');
        }

        $item = formatRow($item, ['id', 'seller_user_id', 'downloads', 'review_count'], ['tags']);
        $item['price']  = (float) $item['price'];
        $item['rating'] = $item['rating'] !== null ? (float) $item['rating'] : null;

        logUsage($ctx['auth']['user_id'], 'marketplace', 1, "GET /marketplace/{$id}");

        respond(['data' => $item]);
    } catch (\PDOException $e) {
        error_log('API v1 marketplace: get item failed: ' . $e->getMessage());
        respondError('Failed to get marketplace item', 500, 'internal_error');
    }
}
