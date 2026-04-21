# Admin API — Users (Lookup)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- These endpoints are intended for **admin mobile** and admin tooling.
- Requires Sanctum authentication and admin access permission `user`.
- Response keys are **camelCase**.

---

## Endpoints overview

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/users/lookup` | Lookup a single user by email or phone |
| GET | `/users/{userId}` | Get a single user by user id |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## 1) Lookup user by email/phone

### **GET `/users/lookup`**

Returns a **single** user record. If multiple users match the same value, the endpoint returns `422`.

Query params (choose one):

| Param | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `email` | string | No* | User email |
| `phone` | string | No* | User phone |
| `q` | string | No* | Convenience: if contains `@` treated as email, otherwise treated as phone |

*At least one of `email`, `phone`, `q` is required.

Success response: **200**

```json
{
  "success": true,
  "data": {
    "userId": 445566,
    "username": "User Name",
    "email": "user@example.com",
    "phone": 959123456789,
    "image": "https://.../uploads/...",
    "emailVerifiedAt": "2026-01-01 12:30:00"
  }
}
```

Field notes:

- `phone` is returned as integer.
- `image` is the raw image path/value from learners table (may be empty).
- `emailVerifiedAt` can be `null`.

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin or admin lacks `user` permission |
| 404 | User not found |
| 422 | Missing query, multiple users matched, or table/column not available |

---

## 2) Get user by userId

### **GET `/users/{userId}`**

Returns a single user record by `userId`.

Path params:

| Param | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `userId` | int | Yes | Learner user id |

Success response: **200**

```json
{
  "success": true,
  "data": {
    "userId": 445566,
    "username": "User Name",
    "email": "user@example.com",
    "phone": 959123456789,
    "image": "https://.../uploads/...",
    "emailVerifiedAt": "2026-01-01 12:30:00"
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin or admin lacks `user` permission |
| 404 | User not found |
| 422 | Learners table/columns not available |
