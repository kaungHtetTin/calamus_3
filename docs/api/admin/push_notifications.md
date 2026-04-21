# Admin API — Push Notifications (User Topics)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- Requires Sanctum authentication and an **Admin** token (checked by `EnsureAdminApi`).
- Sends push notifications via Firebase Cloud Messaging (FCM) **topics** configured per language.
- This endpoint targets **user topics** (`languages.firebase_topic_user`).
- Response keys are **camelCase**.

---

## Endpoints overview

| Method | Endpoint            | Purpose                                                     |
| ------ | ------------------- | ----------------------------------------------------------- |
| POST   | `/push/user-topics` | Send push to all languages or selected languages (by topic) |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## Language targeting

You must choose **one** targeting method:

1. **All languages**

- `allLanguages: true`

2. **Specific languages** (any of the following)

- `languageIds: [1,2,3]`
- `languageCodes: ["english", "korea"]`

Language selection only includes active languages (`is_active = 1`) and only sends to languages with a non-empty `firebase_topic_user`.

---

## Send push to user topics

### **POST `/push/user-topics`**

Body:

| Field           | Type     | Required | Description                                 |
| --------------- | -------- | -------- | ------------------------------------------- |
| `title`         | string   | Yes      | Notification title (max 120)                |
| `body`          | string   | Yes      | Notification body (max 500)                 |
| `image`         | string   | No       | Optional image URL (max 500)                |
| `data`          | object   | No       | Extra FCM data payload (merged into `data`) |
| `allLanguages`  | boolean  | No       | If `true`, targets all active languages     |
| `languageIds`   | int[]    | No       | Target by language ids                      |
| `languageCodes` | string[] | No       | Target by language code/name/module_code    |

Rules:

- If `allLanguages` is not `true`, you must provide at least one of: `languageIds`, `languageCodes`.
- Server de-duplicates topics, so the same FCM topic is only sent once.

Success response: **200**

```json
{
    "success": true,
    "data": {
        "sentTopics": 2,
        "failedTopics": 0,
        "results": [
            { "topic": "user_english", "major": "english", "ok": true },
            { "topic": "user_korea", "major": "korea", "ok": true }
        ]
    }
}
```

### Example A — Push to all languages

```json
{
    "title": "System Maintenance",
    "body": "We will update the system tonight.",
    "allLanguages": true,
    "data": { "type": "admin.broadcast" }
}
```

### Example B — Push to specific languages by codes

```json
{
    "title": "New Content",
    "body": "New lessons are available now.",
    "languageCodes": ["english", "korea"],
    "data": { "type": "content.update" }
}
```

### Example C — Push with image

```json
{
    "title": "Promotion",
    "body": "Special discount for VIP plans.",
    "allLanguages": true,
    "image": "https://example.com/banner.png",
    "data": { "type": "promo.vip" }
}
```

Typical errors:

| HTTP | When                                                                                |
| ---- | ----------------------------------------------------------------------------------- |
| 401  | Missing/expired token                                                               |
| 403  | Token is not an admin                                                               |
| 422  | Invalid request, no target selected, no languages matched, or topics not configured |
