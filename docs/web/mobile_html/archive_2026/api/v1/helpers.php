<?php
/**
 * Alfred API v1 — Shared Helpers
 * 
 * Utility functions shared across resource handlers.
 * Included automatically by the router context.
 * 
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

// ─── Tool Registry (embedded from api/tools.php) ───────────────────────────

/**
 * Get the full tool registry.
 * Loads from the master registry in api/tools.php's $TOOL_REGISTRY via include.
 *
 * @return array
 */
function getToolRegistry(): array
{
    static $registry = null;
    if ($registry !== null) {
        return $registry;
    }

    // Try to load the registry from tools.php file by extracting the array
    $toolsFile = dirname(__DIR__) . '/tools.php';
    if (file_exists($toolsFile)) {
        $content = file_get_contents($toolsFile);
        // Extract the $TOOL_REGISTRY array — it's defined as a PHP array literal
        if (preg_match('/\$TOOL_REGISTRY\s*=\s*\[/s', $content)) {
            // Use a sandbox approach: define the needed constants/functions, then eval
            // Actually, just include a helper that returns the registry
            // For safety, we parse it from the known structure
            $registry = extractToolRegistry($content);
            if (!empty($registry)) {
                return $registry;
            }
        }
    }

    // Fallback: return key categories with sample tools
    $registry = getDefaultToolRegistry();
    return $registry;
}

/**
 * Extract tool registry from tools.php content
 *
 * @param string $content File content
 * @return array
 */
function extractToolRegistry(string $content): array
{
    $tools = [];
    // Match each tool array entry: ['name' => '...', 'category' => '...', ...]
    preg_match_all(
        "/\['name'\s*=>\s*'([^']+)',\s*'category'\s*=>\s*'([^']+)',\s*'description'\s*=>\s*'([^']+)',\s*'icon'\s*=>\s*'([^']+)',\s*'tier'\s*=>\s*'([^']+)'/",
        $content,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $m) {
        $tools[] = [
            'name'        => $m[1],
            'category'    => $m[2],
            'description' => $m[3],
            'icon'        => $m[4],
            'tier'        => $m[5],
        ];
    }

    return $tools;
}

/**
 * Default tool registry fallback
 *
 * @return array
 */
function getDefaultToolRegistry(): array
{
    return [
        ['name' => 'homework_helper', 'category' => 'students_k12', 'description' => 'AI-powered homework assistance', 'icon' => '📚', 'tier' => 'starter'],
        ['name' => 'essay_coach', 'category' => 'university', 'description' => 'Academic essay writing coach', 'icon' => '✍️', 'tier' => 'starter'],
        ['name' => 'email_composer', 'category' => 'professionals', 'description' => 'Draft professional emails', 'icon' => '📧', 'tier' => 'starter'],
        ['name' => 'invoice_generator', 'category' => 'small_business', 'description' => 'Create professional invoices', 'icon' => '💰', 'tier' => 'starter'],
        ['name' => 'blog_writer', 'category' => 'content_creators', 'description' => 'AI blog post writer', 'icon' => '✏️', 'tier' => 'starter'],
        ['name' => 'contract_reviewer', 'category' => 'legal', 'description' => 'AI contract review', 'icon' => '⚖️', 'tier' => 'professional'],
        ['name' => 'fleet_commander', 'category' => 'agent_orchestration', 'description' => 'Deploy and manage multi-agent fleets', 'icon' => '🚀', 'tier' => 'professional'],
        ['name' => 'server_monitor', 'category' => 'devops', 'description' => 'Real-time server monitoring', 'icon' => '🖲️', 'tier' => 'professional'],
        ['name' => 'image_generator', 'category' => 'ai_media', 'description' => 'Generate images from text', 'icon' => '🎨', 'tier' => 'professional'],
        ['name' => 'security_scanner', 'category' => 'security', 'description' => 'Scan for vulnerabilities', 'icon' => '🛡️', 'tier' => 'professional'],
    ];
}

/**
 * Get tool categories with counts from the registry
 *
 * @return array
 */
function getToolCategories(): array
{
    $registry = getToolRegistry();
    $categories = [];

    foreach ($registry as $tool) {
        $cat = $tool['category'];
        if (!isset($categories[$cat])) {
            $categories[$cat] = [
                'name'  => $cat,
                'label' => ucwords(str_replace('_', ' ', $cat)),
                'count' => 0,
                'tools' => [],
            ];
        }
        $categories[$cat]['count']++;
        $categories[$cat]['tools'][] = $tool['name'];
    }

    ksort($categories);
    return array_values($categories);
}

/**
 * Find a tool by name in the registry
 *
 * @param string $name Tool name
 * @return array|null
 */
function findTool(string $name): ?array
{
    foreach (getToolRegistry() as $tool) {
        if ($tool['name'] === $name) {
            return $tool;
        }
    }
    return null;
}

/**
 * Search tools by query string
 *
 * @param string $query   Search query
 * @param string $category Optional category filter
 * @param string $tier    Optional tier filter
 * @return array
 */
function searchTools(string $query = '', string $category = '', string $tier = ''): array
{
    $registry = getToolRegistry();
    $results = [];

    $queryLower = strtolower(trim($query));

    foreach ($registry as $tool) {
        // Category filter
        if ($category !== '' && $tool['category'] !== $category) {
            continue;
        }
        // Tier filter
        if ($tier !== '' && $tool['tier'] !== $tier) {
            continue;
        }

        // Text search
        if ($queryLower !== '') {
            $score = 0;
            $name = strtolower($tool['name']);
            $desc = strtolower($tool['description']);
            $cat  = strtolower($tool['category']);

            if ($name === $queryLower) {
                $score += 100;
            } elseif (str_contains($name, $queryLower)) {
                $score += 50;
            }
            if (str_contains($desc, $queryLower)) {
                $score += 20;
            }
            if (str_contains($cat, $queryLower)) {
                $score += 10;
            }

            if ($score === 0) {
                continue;
            }
            $tool['_score'] = $score;
        }

        $results[] = $tool;
    }

    // Sort by relevance if searching
    if ($queryLower !== '') {
        usort($results, fn($a, $b) => ($b['_score'] ?? 0) - ($a['_score'] ?? 0));
        $results = array_map(function ($t) {
            unset($t['_score']);
            return $t;
        }, $results);
    }

    return $results;
}

/**
 * Call the MCP server (localhost:3005) for tool execution
 *
 * @param string $toolName  Tool name to execute
 * @param array  $arguments Tool arguments
 * @param int    $timeout   Timeout in seconds
 * @return array
 */
function callMcpServer(string $toolName, array $arguments = [], int $timeout = 30): array
{
    $payload = json_encode([
        'jsonrpc' => '2.0',
        'method'  => 'tools/call',
        'params'  => [
            'name'      => $toolName,
            'arguments' => (object) $arguments,
        ],
        'id' => bin2hex(random_bytes(8)),
    ]);

    $ch = curl_init('http://localhost:3005');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => 'MCP server unreachable: ' . $error, 'status' => 'error'];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid MCP response', 'raw' => substr($response, 0, 500), 'status' => 'error'];
    }

    return $decoded;
}

/**
 * Format a database row for API output (cast types, remove sensitive fields)
 *
 * @param array $row      Database row
 * @param array $intFields Fields to cast to int
 * @param array $jsonFields Fields to decode as JSON
 * @param array $removeFields Fields to remove
 * @return array
 */
function formatRow(array $row, array $intFields = ['id', 'user_id'], array $jsonFields = [], array $removeFields = []): array
{
    foreach ($intFields as $f) {
        if (isset($row[$f])) {
            $row[$f] = (int) $row[$f];
        }
    }
    foreach ($jsonFields as $f) {
        if (isset($row[$f]) && is_string($row[$f])) {
            $row[$f] = json_decode($row[$f], true);
        }
    }
    foreach ($removeFields as $f) {
        unset($row[$f]);
    }
    return $row;
}

/**
 * Sanitize a string input for safe use
 *
 * @param string $input
 * @param int    $maxLength
 * @return string
 */
function sanitizeInput(string $input, int $maxLength = 255): string
{
    $input = htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    if ($maxLength > 0) {
        $input = substr($input, 0, $maxLength);
    }
    return $input;
}

/**
 * Verify the authenticated user owns a specific resource
 *
 * @param PDO    $db
 * @param string $table    Table name
 * @param int    $id       Resource ID
 * @param int    $userId   User ID
 * @param string $userCol  Column name for user ID
 * @return array|null
 */
function getOwnedResource(PDO $db, string $table, int $id, int $userId, string $userCol = 'user_id'): ?array
{
    $allowedTables = [
        'alfred_fleets', 'alfred_fleet_agents', 'alfred_conferences',
        'alfred_marketplace_items', 'alfred_consciousness',
    ];
    if (!in_array($table, $allowedTables, true)) {
        return null;
    }

    $stmt = $db->prepare("SELECT * FROM `{$table}` WHERE id = :id AND `{$userCol}` = :uid LIMIT 1");
    $stmt->execute([':id' => $id, ':uid' => $userId]);
    return $stmt->fetch() ?: null;
}
