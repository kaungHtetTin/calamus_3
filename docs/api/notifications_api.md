# API Documentation - Notifications

Notifications for social interactions (post comments, replies, likes, comment likes). All endpoints require authentication (Sanctum).

**Base path:** `{APP_URL}/api/notifications`

**Auth header:** `Authorization: Bearer {token}`

For navigation and FCM payload rules, see: `docs/api/notifications_navigation.md`.

---

## 1. Get notifications

### **GET `/notifications/get`**

Get paginated notification list for the authenticated user.

- **Authentication:** Required (Sanctum)
- **Request parameters:**

| Parameter | Type   | Required | Default | Description            |
|-----------|--------|----------|---------|------------------------|
| `page`    | integer| No       | 1       | Page number            |
| `limit`   | integer| No       | 20      | Items per page (1–100) |

- **Success response (200):**

```json
{
    "success": true,
    "data": [
        {
            "id": "9d3f8a2b-4c1e-4a5b-9f2d-8e7c6b5a4d3e",
            "type": "post.comment",
            "actor": {
                "id": 1001,
                "name": "Jane Doe",
                "image": "https://example.com/uploads/users/avatar.jpg"
            },
            "target": {
                "type": "post",
                "id": "123",
                "title": "Post body preview...",
                "image": "https://example.com/uploads/posts/image.jpg"
            },
            "navigation": {
                "routeName": "PostDetail",
                "params": {
                    "postId": "123",
                    "commentId": 1732281234567
                }
            },
            "metadata": {
                "commentId": 1732281234567,
                "parentId": 0
            },
            "readAt": null,
            "createdAt": "2024-01-15T10:00:00.000000Z"
        }
    ],
    "meta": {
        "currentPage": 1,
        "lastPage": 5,
        "nextPageToken": "2",
        "total": 85,
        "limit": 20,
        "unreadCount": 12
    }
}
```

- **Notification types:**

| `type`            | Description                        |
|-------------------|------------------------------------|
| `post.comment`    | Someone commented on your post     |
| `post.comment_reply` | Someone replied to your comment |
| `post.like`       | Someone liked your post            |
| `comment.like`    | Someone liked your comment         |

- **Navigation by target:**

| `target.type` | `navigation.routeName` | `navigation.params`                  |
|---------------|------------------------|--------------------------------------|
| `post`        | PostDetail             | `postId`, `commentId` (optional)     |
| `course`      | CourseDetail           | `courseId`                           |
| `lesson`      | LessonDetail           | `id`, `courseId` (optional)          |

- **Error responses:**
  - `401` — Not authenticated

---

## 2. Mark one notification as read

### **POST `/notifications/mark-one-read`**

Mark a single notification as read.

- **Authentication:** Required (Sanctum)
- **Request body (JSON):**

| Field            | Type   | Required | Description                    |
|------------------|--------|----------|--------------------------------|
| `notificationId` | string | Yes      | Notification UUID              |

- **Success response (200):**

```json
{
    "success": true,
    "data": {
        "success": true
    }
}
```

- **Error responses:**
  - `400` — notificationId is required
  - `401` — Not authenticated
  - `404` — Notification not found
  - `500` — Server error

---

## 3. Mark notifications as seen

### **POST `/notifications/mark-seen`**

Mark one or all notifications as read.

- **Authentication:** Required (Sanctum)
- **Request body (JSON):**

| Field            | Type   | Required | Description                                  |
|------------------|--------|----------|----------------------------------------------|
| `id` or `notificationId` | string | No | Notification UUID. If omitted, marks all as read |

- **Success response (200):**

```json
{
    "success": true,
    "data": {
        "success": true
    }
}
```

- **Error responses:**
  - `401` — Not authenticated
  - `500` — Server error

---

## Data types

### Actor

User who performed the action.

| Field  | Type   | Description       |
|--------|--------|-------------------|
| `id`   | integer| Learner user ID   |
| `name` | string | Display name      |
| `image`| string | Avatar URL        |

### Target

Content the notification refers to.

| Field  | Type   | Description                 |
|--------|--------|-----------------------------|
| `type` | string | `post`, `course`, or `lesson` |
| `id`   | string | Target resource ID          |
| `title`| string | Preview text                |
| `image`| string | Thumbnail URL               |

### Metadata

Extra context, varies by type.

| Key         | Type   | Used when                |
|-------------|--------|---------------------------|
| `commentId` | integer| Comment-related actions   |
| `parentId`  | integer| Reply to comment          |
| `courseId`  | string | Lesson-related            |

### Pagination meta

| Field          | Type    | Description                |
|----------------|---------|----------------------------|
| `currentPage`  | integer | Current page               |
| `lastPage`     | integer | Last page number           |
| `nextPageToken`| string  | Next page, or `null`       |
| `total`        | integer | Total notifications        |
| `limit`        | integer | Page size                  |
| `unreadCount`  | integer | Unread notifications count |

---

## Common error format

```json
{
    "success": false,
    "error": "Error message"
}
```

---

## Client implementation checklist

| Task | Endpoint | Notes |
|------|----------|-------|
| Fetch list | `GET /api/notifications/get?page=1&limit=20` | Paginate with `nextPageToken` |
| Badge count | Use `meta.unreadCount` from GET response | Or call GET with limit=1 to get count only |
| Mark one read | `POST /api/notifications/mark-one-read` | Body: `{"notificationId": "uuid"}` |
| Mark all read | `POST /api/notifications/mark-seen` | Body: `{}` or omit body |
| Navigate on tap | Use `navigation.routeName` + `navigation.params` | Route to screen with params |
| Check unread | `readAt === null` | Unread when `readAt` is null |
| Show time | Use `createdAt` | ISO 8601 format |
| Empty state | When `data.length === 0` and `meta.total === 0` | |
