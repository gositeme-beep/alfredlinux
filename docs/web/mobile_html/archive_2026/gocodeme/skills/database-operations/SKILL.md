---
name: database-operations
description: "Database operations guide — MySQL/PDO queries, schema design, migrations, and optimization"
---

# Database Operations Guide

## Querying (ALWAYS use prepared statements)
```php
// Correct — parameterized query
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// NEVER do this — SQL injection vulnerability
$pdo->query("SELECT * FROM users WHERE id = $userId");
```

## Common Operations
- **Schema inspection**: Use `db_schema` MCP tool to see table structures
- **Query execution**: Use `db_query` MCP tool for SELECT queries
- **Database listing**: Use `db_list` MCP tool to see available databases

## Schema Design Guidelines
- Use `BIGINT UNSIGNED AUTO_INCREMENT` for primary keys
- Timestamp columns: `created_at DATETIME DEFAULT CURRENT_TIMESTAMP`
- Index frequently queried columns (WHERE, JOIN, ORDER BY)
- Use `ENUM` for columns with fixed values
- Foreign keys for referential integrity

## Performance
- Add composite indexes for multi-column WHERE clauses
- Use EXPLAIN to analyze query plans
- Avoid SELECT * — name columns explicitly
- LIMIT results for pagination
- Use JOIN instead of subqueries when possible

## Safety
- Never run DELETE/DROP without WHERE clause
- Always backup before schema changes: use `db_backup` MCP tool
- Test queries with SELECT before running UPDATE/DELETE
- Use transactions for multi-statement operations
