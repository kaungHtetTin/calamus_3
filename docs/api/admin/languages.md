# Admin API — Languages (Scoped)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

All endpoints in this document require:

```http
Authorization: Bearer {ADMIN_TOKEN}
Accept: application/json
```

---

## 1) Get languages (scoped to current admin)

### **GET `/languages/get`**

Returns active languages filtered by the admin's `major_scope`:

- If `major_scope` contains `*`, returns all active languages
- Otherwise, returns languages whose `code` is in `major_scope`
- If `major_scope` is empty (and not `*`), returns an empty list

### Success (200)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "English",
      "displayName": "English",
      "code": "english",
      "moduleCode": "en",
      "imagePath": "https://...",
      "primaryColor": "#123456",
      "secondaryColor": "#abcdef"
    }
  ],
  "meta": {
    "total": 1,
    "scope": ["english"]
  }
}
```

### Typical errors

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token belongs to a non-admin account, or admin has no sector permission |
