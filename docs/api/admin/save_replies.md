# Admin API — Save Replies (CRUD)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- Requires Sanctum authentication and an **Admin** token (checked by `EnsureAdminApi`).
- No additional permission checks are applied for this module.
- Response keys are **camelCase**.
- `major` is intentionally not part of this API (it will be dropped later). The server writes a safe fallback value while the DB column still exists.

---

## Endpoints overview

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/save-replies` | List saved replies (supports pagination + search) |
| POST | `/save-replies` | Create a saved reply |
| GET | `/save-replies/{id}` | Get a single saved reply |
| PATCH | `/save-replies/{id}` | Update a saved reply |
| DELETE | `/save-replies/{id}` | Delete a saved reply |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## Data model

`SaveReply` (API fields):

| Field | Type | Notes |
| ----- | ---- | ----- |
| `id` | int | Primary key |
| `title` | string | Max 120 |
| `message` | string | Max 5000 |

---

## 1) List saved replies

### **GET `/save-replies`**

Query params:

| Param | Type | Required | Default | Description |
| ----- | ---- | -------- | ------- | ----------- |
| `page` | int | No | `1` | 1-based page |
| `limit` | int | No | `50` | Max `200` |
| `q` | string | No | `""` | Search in `title` and `message` |

Success response: **200**

```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "title": "Basic ✅✅",
      "message": "..."
    }
  ],
  "meta": {
    "currentPage": 1,
    "lastPage": 1,
    "nextPageToken": null,
    "total": 1,
    "limit": 50
  }
}
```

---

## 2) Create a saved reply

### **POST `/save-replies`**

Body:

```json
{
  "title": "Payment approved",
  "message": "✅ Your payment is approved..."
}
```

Success response: **201**

```json
{
  "success": true,
  "data": {
    "id": 10,
    "title": "Payment approved",
    "message": "✅ Your payment is approved..."
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin |
| 422 | Validation error |

---

## 3) Get a single saved reply

### **GET `/save-replies/{id}`**

Success response: **200**

```json
{
  "success": true,
  "data": {
    "id": 10,
    "title": "Payment approved",
    "message": "✅ Your payment is approved..."
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin |
| 404 | Not found |

---

## 4) Update a saved reply

### **PATCH `/save-replies/{id}`**

Body (partial update allowed):

```json
{
  "title": "Payment approved (v2)"
}
```

Success response: **200**

```json
{
  "success": true,
  "data": {
    "id": 10,
    "title": "Payment approved (v2)",
    "message": "✅ Your payment is approved..."
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin |
| 404 | Not found |
| 422 | Validation error |

---

## 5) Delete a saved reply

### **DELETE `/save-replies/{id}`**

Success response: **200**

```json
{
  "success": true,
  "data": {
    "deleted": true
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin |
| 404 | Not found |

