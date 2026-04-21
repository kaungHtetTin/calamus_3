# API — Payments History (User)

Base URL for routes in this document: **`{APP_URL}/api`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {USER_TOKEN}`

Notes:

- Requires Sanctum authentication (user token).
- Response keys are **camelCase**.

---

## Endpoints overview

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/payments/history` | Get authenticated user payment history |

---

## Authentication

Use user Sanctum token:

```http
Authorization: Bearer {USER_TOKEN}
```

---

## Get payments history

### **GET `/payments/history`**

Query params:

| Param | Type | Required | Default | Description |
| ----- | ---- | -------- | ------- | ----------- |
| `page` | int | No | `1` | 1-based pagination |
| `limit` | int | No | `20` | Max `100` |
| `major` | string | No | `""` | Filter by `payments.major` |

Success response: **200**

```json
{
  "success": true,
  "data": [
    {
      "paymentId": 123,
      "amount": 20,
      "major": "english",
      "transactionId": "TXN-001",
      "screenshot": "https://example.com/uploads/payments/screenshots/abc.jpg",
      "packagePlan": "monthly",
      "approved": false,
      "activated": false,
      "date": "2026-04-20 13:30:00",
      "courses": [
        {
          "courseId": 11,
          "title": "Basic Course",
          "coverUrl": "https://example.com/uploads/covers/basic.png",
          "major": "english",
          "isVip": 1
        }
      ]
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 1,
    "nextPageToken": null,
    "total": 1,
    "limit": 20
  }
}
```

Field notes:

- `approved` is derived from whichever DB column exists: `payments.approved` or `payments.approve`. If neither exists, it can be `null`.
- `courses` is built from `payments.courses` and the `courses` table (when available).

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |

