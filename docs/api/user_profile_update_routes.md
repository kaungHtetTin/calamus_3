# API Documentation - User Profile Update Routes

This document describes the dedicated user profile update endpoints:
- `POST /api/users/update-bio`
- `POST /api/users/update-cover-photo`
- `POST /api/users/update-credentials`

**Base path:** `{APP_URL}/api/users`

---

## 1) Update learner bio

### **POST `/users/update-bio`**

- **Authentication:** Required (`auth:sanctum`)
- **Content-Type:** `application/json`

### Request body

| Field | Type | Required | Description |
|------|------|----------|-------------|
| `bio` | string | Yes | New bio text for the learner profile |

### Example request

```bash
curl -X POST "{APP_URL}/api/users/update-bio" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "bio": "I love language learning and daily practice."
  }'
```

### Success response (200)

```json
{
  "success": true,
  "data": {
    "success": true,
    "message": "Bio updated successfully",
    "bio": "I love language learning and daily practice."
  }
}
```

---

## 2) Update cover photo

### **POST `/users/update-cover-photo`**

- **Authentication:** Required (`auth:sanctum`)
- **Content-Type:** `multipart/form-data`

### Request body

| Field | Type | Required | Description |
|------|------|----------|-------------|
| `coverImage` | file (image) | Yes | Cover photo image file (max 5MB) |

### Validation rules

- `coverImage` must be an image file
- Maximum file size: `5120 KB` (5 MB)

### Example request

```bash
curl -X POST "{APP_URL}/api/users/update-cover-photo" \
  -H "Authorization: Bearer {TOKEN}" \
  -F "coverImage=@/path/to/cover.jpg"
```

### Success response (200)

```json
{
  "success": true,
  "data": {
    "success": true,
    "message": "Cover photo updated successfully",
    "coverImage": "https://your-domain.com/uploads/users/abc123.jpg"
  }
}
```

---

## 3) Update credential information

### **POST `/users/update-credentials`**

- **Authentication:** Required (`auth:sanctum`)
- **Content-Type:** `application/json`

### Request body

| Field | Type | Required | Description |
|------|------|----------|-------------|
| `password` | string | Yes | Current password for verification |
| `email` | string | No | New email address (must be unique) |
| `phone` | string | No | New phone number (must be unique) |

### Example request

```bash
curl -X POST "{APP_URL}/api/users/update-credentials" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "password": "yourpassword",
    "email": "newemail@example.com",
    "phone": "0912345678"
  }'
```

### Success response (200)

```json
{
  "success": true,
  "data": {
    "success": true,
    "message": "Credentials updated successfully",
    "user": {
      "email": "newemail@example.com",
      "phone": "0912345678"
    }
  }
}
```

---

## Common error format

```json
{
  "success": false,
  "error": "Error message"
}
```

### Common errors

- `401` - Not authenticated (missing/invalid token)
- `400` - Validation failed (missing `bio`, missing/invalid `coverImage`)
- `500` - Failed to upload cover image

