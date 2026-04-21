# Notification Navigation & Payload Guide

This document explains how to **navigate on notification click** using:

1. **In-app notification list** from `GET /api/notifications/get`
2. **FCM data payload** from push notifications

The goal is: the client can reliably route to the correct screen with the correct params.

---

## 1) Unified client logic (recommended)

When the user taps a notification (from list or from push):

1. If `navigation.routeName` exists → navigate using it.
2. Else if `type` is known → map `type` to a route and build params.
3. Mark it as read using `POST /api/notifications/mark-one-read`.

---

## 2) Notification API (in-app list)

**Base path:** `{APP_URL}/api/notifications`

### GET `/notifications/get`

Returns notifications for the authenticated user.

**Implementation details**

- If `navigation.routeName === "LessonDetail"` and `courseId` is missing, the API resolves `courseId` from DB using:
    - `lessons.id → lessons.category_id → lessons_categories.course_id`
      and injects it into `navigation.params.courseId` and `target.courseId` (when `target.type === "lesson"`).

Each item is a normalized object:

```json
{
    "id": "uuid",
    "type": "post.like",
    "actor": { "userId": 123, "name": "John", "image": "https://..." },
    "target": { "postId": 10 },
    "navigation": { "routeName": "PostDetail", "params": { "postId": "10" } },
    "metadata": {},
    "readAt": null,
    "createdAt": "2026-04-12T10:00:00.000000Z"
}
```

### POST `/notifications/mark-one-read`

```json
{ "notificationId": "uuid" }
```

### POST `/notifications/mark-seen`

- If `notificationId` is provided: marks one
- If omitted: marks all

---

## 3) FCM payload (push)

FCM uses the `data` object (all values are strings).

### Minimum recommended payload

```json
{
    "type": "post.like",
    "navigation": "{\"routeName\":\"PostDetail\",\"params\":{\"postId\":\"10\"}}"
}
```

### Important client note

In this project, `FcmService` stringifies arrays into JSON strings.
So `data.navigation` may arrive as a stringified JSON object. The client should do:

- if `typeof navigation === 'string'` → `JSON.parse(navigation)`

---

## 4) Supported notification types and routes

Below are the types currently generated in the backend (API/database notifications) and how the client should navigate.

### A) Social → Post

| type              | When                    | routeName                      | params                           |
| ----------------- | ----------------------- | ------------------------------ | -------------------------------- |
| `post.like`       | someone liked a post    | `PostDetail`                   | `{ postId }`                     |
| `post.comment`    | someone commented       | `PostDetail`                   | `{ postId, commentId? }`         |
| `comment.created` | fallback alias          | `PostDetail`                   | `{ postId, commentId? }`         |
| `comment.reply`   | replied to your comment | `PostDetail`                   | `{ postId, commentId }`          |
| `comment.like`    | liked your comment      | `PostDetail` or `LessonDetail` | if post: `{ postId, commentId }` |

**Notes**

- Comment IDs in the legacy comment system use the `comment.time` field (milliseconds) as identifier.

### B) Lessons

| type                   | When                         | routeName      | params                                   |
| ---------------------- | ---------------------------- | -------------- | ---------------------------------------- |
| `lesson.added`         | admin added a lesson         | `LessonDetail` | `{ id: lessonId, courseId }`             |
| `lesson.comment`       | new comment on a lesson      | `LessonDetail` | `{ id: lessonId, courseId, commentId? }` |
| `lesson.comment_reply` | reply to your lesson comment | `LessonDetail` | `{ id: lessonId, courseId, commentId }`  |

**Notes**

- The API guarantees `courseId` is present for `LessonDetail` navigation, even for older saved notifications.

### C) Payments

| type                | When                              | routeName                       | params                   |
| ------------------- | --------------------------------- | ------------------------------- | ------------------------ |
| `payment.created`   | user submitted payment            | `AdminEnrollCourse` (admin app) | `{ paymentId?, major? }` |
| `payment.activated` | admin activated user subscription | `PurchasedCourses` (client app) | `{ major? }`             |

**Notes**

- Admin-only screens are handled in the admin web app; mobile clients may ignore admin-only types.

### D) Chat

| type           | When                  | routeName                 | params                         |
| -------------- | --------------------- | ------------------------- | ------------------------------ |
| `chat.message` | new chat message      | `ChatDetail`              | `{ conversationId, friendId }` |
| `chat.support` | user messaged support | `SupportChat` (admin app) | `{ conversationId }`           |

---

## 5) Client fallback mapping (example)

If `navigation` is missing, map types:

```js
function mapNotificationToRoute(n) {
    if (n.navigation?.routeName) return n.navigation;

    switch (n.type) {
        case "post.like":
            return {
                routeName: "PostDetail",
                params: {
                    postId: String(
                        n.target?.postId || n.metadata?.postId || "",
                    ),
                },
            };
        case "lesson.added":
            return {
                routeName: "LessonDetail",
                params: {
                    id: String(
                        n.target?.lessonId || n.metadata?.lessonId || "",
                    ),
                    courseId: String(
                        n.target?.courseId || n.metadata?.courseId || "",
                    ),
                },
            };
        case "lesson.comment":
        case "lesson.comment_reply":
            return {
                routeName: "LessonDetail",
                params: {
                    id: String(
                        n.target?.lessonId || n.metadata?.lessonId || "",
                    ),
                    courseId: String(
                        n.target?.courseId || n.metadata?.courseId || "",
                    ),
                    commentId: String(
                        n.target?.commentId || n.metadata?.commentId || "",
                    ),
                },
            };
        default:
            return null;
    }
}
```

---

## 6) Topic vs Single push (reference)

- Single push: send to `user_data.token`
- Topic push to users: `languages.firebase_topic_user`
- Topic push to admins: `languages.firebase_topic_admin`
