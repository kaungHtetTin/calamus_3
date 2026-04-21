# Admin API — Support Chat

Base URL for routes in this document: **`{APP_URL}/api/admin/chat`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- This API is intended for **admin mobile** and admin tooling.
- The admin “support agent” sender is **fixed** at `user_id = 10000` (server-side).
- Timestamp fields like `created_at`, `updated_at`, `last_message_at` are returned as **epoch milliseconds** when available.

---

## Endpoints overview

| Method | Endpoint         | Purpose                                                        |
| ------ | ---------------- | -------------------------------------------------------------- |
| GET    | `/conversations` | List support conversations                                     |
| GET    | `/conversation`  | Get (or create) a conversation by other user id                |
| GET    | `/messages`      | List messages for a conversation (marks user messages as read) |
| POST   | `/messages`      | Send text and/or image message (sender is always `10000`)      |
| GET    | `/unread-count`  | Total unread count across support conversations                |

Legacy alias:

- `POST /upload-image` behaves the same as `POST /messages` (multipart) for backward compatibility.

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## 1) List conversations

### **GET `/conversations`**

Query params:

| Param     | Type | Required | Default | Notes               |
| --------- | ---- | -------- | ------- | ------------------- |
| `perPage` | int  | No       | `25`    | Min `10`, max `100` |

Success response: **200**

This endpoint returns a standard Laravel paginator JSON.

Example response:

```json
{
    "current_page": 1,
    "data": [
        {
            "id": 123,
            "major": "english",
            "other_user_id": 445566,
            "unread_count": 2,
            "last_message_text": "Hello",
            "last_message_type": "text",
            "last_message_file_path": "",
            "last_message_at": 1710000000000,
            "created_at": 1710000000000,
            "updated_at": 1710000000000,
            "friend": {
                "id": 445566,
                "name": "User Name",
                "image": "https://...",
                "phone": "09....",
                "email": "user@example.com"
            }
        }
    ],
    "first_page_url": "https://.../api/admin/chat/conversations?page=1",
    "from": 1,
    "last_page": 10,
    "last_page_url": "https://.../api/admin/chat/conversations?page=10",
    "next_page_url": "https://.../api/admin/chat/conversations?page=2",
    "path": "https://.../api/admin/chat/conversations",
    "per_page": 25,
    "prev_page_url": null,
    "to": 25,
    "total": 250
}
```

---

## 2) List messages

### **GET `/messages`**

Query params:

| Param            | Type | Required | Default | Notes                            |
| ---------------- | ---- | -------- | ------- | -------------------------------- |
| `conversationId` | int  | Yes      | -       | Conversation id                  |
| `limit`          | int  | No       | `50`    | Max `100`                        |
| `beforeId`       | int  | No       | `0`     | Fetch older than this message id |
| `afterId`        | int  | No       | `0`     | Fetch newer than this message id |

Behavior:

- Verifies this is a support conversation involving admin `10000`.
- Marks messages from the user (sender != `10000`) as read (`is_read = 1`) for the conversation.

Success response: **200**

Example response:

```json
{
    "data": [
        {
            "id": 999,
            "conversation_id": 123,
            "sender_id": 10000,
            "major": "english",
            "message_type": "text",
            "message_text": "How can I help?",
            "file_path": "",
            "file_size": 0,
            "is_read": 0,
            "created_at": 1710000000000,
            "updated_at": 1710000000000
        }
    ],
    "meta": {
        "nextBeforeId": 950,
        "newestId": 999
    }
}
```

Typical errors:

| HTTP | When                             |
| ---- | -------------------------------- |
| 422  | `conversationId` missing/invalid |
| 403  | Not a support conversation       |
| 404  | Conversation not found           |

---

## 3) Get conversation by other user id

### **GET `/conversation`**

Query params:

| Param | Type | Required | Notes |
| ----- | ---- | -------- | ----- |
| `otherUserId` | int | Yes | The learner user id |
| `major` | string | No | If provided and no conversation exists, server creates one under this major |

Success response: **200**

Returns a single conversation object (same item shape as `/conversations` list):

```json
{
  "id": 123,
  "major": "english",
  "other_user_id": 445566,
  "unread_count": 2,
  "last_message_text": "Hello",
  "last_message_type": "text",
  "last_message_file_path": "",
  "last_message_at": 1710000000000,
  "created_at": 1710000000000,
  "updated_at": 1710000000000,
  "friend": {
    "id": 445566,
    "name": "User Name",
    "image": "https://...",
    "phone": "09....",
    "email": "user@example.com"
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 422 | `otherUserId` missing/invalid |
| 404 | Conversation not found (when `major` is not provided) |

---

## 4) Send message (text and/or image)

### **POST `/messages`**

This endpoint supports:

- `application/json` for text-only
- `multipart/form-data` for image-only or image + text (caption)

Request fields:

| Field            | Type   | Required | Notes                                     |
| ---------------- | ------ | -------- | ----------------------------------------- |
| `conversationId` | int    | Yes      | -                                         |
| `messageText`    | string | No\*     | Optional text (caption if image included) |
| `image`          | file   | No\*     | Image file, max ~10MB                     |

\*At least one of `messageText` or `image` is required.

Notes:

- Sender is always `10000`.
- Also updates conversation `last_message_at`.
- If `image` is included, the server uploads to `uploads` disk and stores the full URL into `message.file_path`.
- If `image` is included and `messageText` is provided, it is stored as the image caption in `message_text`.

Example request:

```json
{
    "conversationId": 123,
    "messageText": "Hello!"
}
```

Success response: **200**

```json
{
    "message": {
        "id": 1001,
        "conversation_id": 123,
        "sender_id": 10000,
        "major": "english",
        "message_type": "text",
        "message_text": "Hello!",
        "file_path": "",
        "file_size": 0,
        "is_read": 0,
        "created_at": 1710000000000,
        "updated_at": 1710000000000
    },
    "conversation": {
        "id": 123,
        "last_message_at": 1710000000000
    }
}
```

Typical errors:

| HTTP | When                                                           |
| ---- | -------------------------------------------------------------- |
| 422  | Validation failed (`conversationId`, `messageText` or `image`) |
| 403  | Not a support conversation                                     |
| 404  | Conversation not found                                         |

---

## 5) Unread count

### **GET `/unread-count`**

Success response: **200**

```json
{ "unread": 12 }
```
