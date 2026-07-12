# Alfred API v1

Public REST API for the Alfred AI platform.

**Base URL:** `https://gositeme.com/api/v1`  
**Version:** 1.0.0  
**Auth:** Bearer token via `Authorization` header

---

## Authentication

All endpoints (except `GET /`) require authentication via API key or OAuth2 token.

```
Authorization: Bearer ak_live_PREFIX_SECRET
```

API keys are managed in the Alfred dashboard. Keys support scoped permissions and per-plan rate limits.

## Rate Limits

| Plan         | Requests/min | Requests/hour | Requests/day |
|-------------|-------------|---------------|-------------|
| Free         | 10          | 100           | 500         |
| Starter      | 30          | 500           | 5,000       |
| Professional | 60          | 2,000         | 20,000      |
| Enterprise   | 200         | 10,000        | 100,000     |

Rate limit headers are included in every response:
- `X-RateLimit-Limit` — Max requests per minute
- `X-RateLimit-Remaining` — Remaining requests in window
- `X-RateLimit-Reset` — Unix timestamp when window resets

## Response Format

### Success
```json
{
  "data": { ... },
  "meta": {
    "total": 100,
    "page": 1,
    "per_page": 20,
    "total_pages": 5
  }
}
```

### Error
```json
{
  "error": {
    "code": "rate_limit_exceeded",
    "message": "Rate limit exceeded. Retry after 30 seconds.",
    "status": 429
  }
}
```

## Endpoints

### Chat
| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/chat` | Send a message to Alfred AI |
| `GET`  | `/chat` | Get chat endpoint info |

### Tools
| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/tools` | List tools (search, filter, paginate) |
| `GET`  | `/tools/categories` | List tool categories |
| `GET`  | `/tools/{name}` | Get tool details + input schema |
| `POST` | `/tools/{name}/execute` | Execute a tool |

### Agents
| Method   | Path | Description |
|----------|------|-------------|
| `POST`   | `/agents` | Create agent |
| `GET`    | `/agents` | List user's agents |
| `GET`    | `/agents/{id}` | Get agent details |
| `PUT`    | `/agents/{id}` | Update agent |
| `DELETE` | `/agents/{id}` | Delete agent |
| `POST`   | `/agents/{id}/execute` | Send task to agent |

### Fleets
| Method   | Path | Description |
|----------|------|-------------|
| `POST`   | `/fleets` | Create fleet |
| `GET`    | `/fleets` | List user's fleets |
| `GET`    | `/fleets/{id}` | Get fleet details + agents |
| `GET`    | `/fleets/{id}/status` | Fleet status + metrics |
| `POST`   | `/fleets/{id}/deploy` | Deploy fleet |
| `POST`   | `/fleets/{id}/pause` | Pause fleet |
| `DELETE` | `/fleets/{id}` | Delete (retire) fleet |

### Voice
| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/voice/calls` | List call history |
| `GET`  | `/voice/calls/{id}` | Get call details + transcript |
| `POST` | `/voice/rooms` | Create conference room |
| `GET`  | `/voice/rooms` | List conference rooms |
| `GET`  | `/voice/rooms/{id}` | Get room details |

### Marketplace
| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/marketplace` | Browse marketplace listings |
| `GET`  | `/marketplace/{id}` | Get listing details |

### Usage & Billing
| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/usage` | Usage overview |
| `GET`  | `/usage/tools` | Tool usage breakdown |
| `GET`  | `/usage/daily` | Daily usage timeseries |
| `GET`  | `/billing` | Plan & billing info |

## Pagination

All list endpoints support pagination:
- `page` — Page number (default: 1)
- `per_page` — Items per page (default: 20, max: 100)

## Quick Start

```bash
# Get API info
curl https://gositeme.com/api/v1/

# List tools
curl -H "Authorization: Bearer ak_live_XXXX_YYYY" \
     https://gositeme.com/api/v1/tools

# Chat with Alfred
curl -X POST \
     -H "Authorization: Bearer ak_live_XXXX_YYYY" \
     -H "Content-Type: application/json" \
     -d '{"message": "Help me write a business plan"}' \
     https://gositeme.com/api/v1/chat

# Execute a tool
curl -X POST \
     -H "Authorization: Bearer ak_live_XXXX_YYYY" \
     -H "Content-Type: application/json" \
     -d '{"input": "Review this NDA for risks", "params": {}}' \
     https://gositeme.com/api/v1/tools/contract_reviewer/execute
```

## Files

| File | Description |
|------|-------------|
| `.htaccess` | URL rewriting for clean routes |
| `router.php` | Main API router — auth, rate limiting, CORS, dispatch |
| `index.php` | Entry point alias (includes router.php) |
| `helpers.php` | Shared utility functions |
| `resources/tools.php` | Tools endpoint handler |
| `resources/agents.php` | Agents endpoint handler |
| `resources/fleets.php` | Fleets endpoint handler |
| `resources/voice.php` | Voice/conferencing endpoint handler |
| `resources/marketplace.php` | Marketplace endpoint handler |
| `resources/usage.php` | Usage & billing endpoint handler |
| `resources/chat.php` | Chat endpoint handler |
