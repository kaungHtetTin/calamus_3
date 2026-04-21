# Admin API — Authentication

Base URL for routes in this document: **`{APP_URL}/api/admin/auth`**

Unless noted:

- `Accept: application/json`
- `Content-Type: application/json` for JSON bodies

Sanctum header for protected routes:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

---

## Endpoints overview

| Method | Endpoint | Auth | Purpose |
| ------ | -------- | ---- | ------- |
| POST | `/login` | No | Admin login (returns Sanctum token) |
| GET | `/me` | Yes | Current admin profile |
| POST | `/logout` | Yes | Revoke current token |

---

## Response shape

### Success

```json
{
  "success": true,
  "data": {}
}
```

### Error

```json
{
  "success": false,
  "error": "Human-readable message"
}
```

---

## 1) Login

### **POST `/login`**

- **Authentication:** Not required

### Request body

| Field | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `email` | string | Yes | Admin email |
| `password` | string | Yes | Admin password |
| `deviceName` | string | No | Token label. Default: `admin-mobile` |

### Success (200)

```json
{
  "success": true,
  "data": {
    "token": "1|....",
    "admin": {
      "id": 1,
      "name": "Admin Name",
      "email": "admin@example.com",
      "imageUrl": "https://...",
      "access": ["administration", "course"],
      "majorScope": ["english"]
    }
  }
}
```

### Typical errors

| HTTP | When |
| ---- | ---- |
| 400 | Validation failed (missing email/password) |
| 401 | Invalid credentials |

---

## 2) Current admin (`me`)

### **GET `/me`**

- **Authentication:** Required (Sanctum)

### Success (200)

```json
{
  "success": true,
  "data": {
    "admin": {
      "id": 1,
      "name": "Admin Name",
      "email": "admin@example.com",
      "imageUrl": "https://...",
      "access": ["administration", "course"],
      "majorScope": ["english"]
    }
  }
}
```

### Typical errors

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token belongs to a non-admin account |

---

## 3) Logout

### **POST `/logout`**

- **Authentication:** Required (Sanctum)

Revokes the **current** token only.

### Success (200)

```json
{
  "success": true,
  "data": [],
  "meta": {
    "status": "success"
  }
}
```
