# API — Lesson Social Endpoints (Like, Share, Comment)

Base URL for routes in this document: **`{APP_URL}/api`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {USER_TOKEN}` (required — endpoints in this document are protected by Sanctum)

---

## 1) Like a lesson (toggle)

### **POST `/lessons/like`**

Toggle like/unlike for a lesson.

- **Authentication:** Required
- **Request Body:**
    - `lessonId` (integer, required) — lesson primary key
    - `id` (integer, optional alias for `lessonId`)
- **Response Shape:**

```json
{
    "success": true,
    "data": {
        "success": true,
        "isLiked": true,
        "count": 12
    }
}
```

- **Notes:**
    - `count` is `lessons.like_count` after the toggle.
    - Likes are stored per user in `lesson_likes` (unique by `lesson_id + user_id`).

Example:

```bash
curl -X POST "{APP_URL}/api/lessons/like" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {USER_TOKEN}" \
  -d "lessonId=123"
```

---

## 2) Share a lesson (increment)

### **POST `/lessons/share`**

Increment the share counter for a lesson.

- **Authentication:** Required
- **Request Body:**
    - `lessonId` (integer, required)
    - `id` (integer, optional alias for `lessonId`)
- **Response Shape:**

```json
{
    "success": true,
    "data": {
        "success": true,
        "count": 5,
        "link": "https://www.calamuseducation.com/calamus/course/14/lesson/123"
    }
}
```

- **Notes:**
    - `count` is `lessons.share_count` after increment.
    - `link` is the shareable URL generated based on the course type:
        - Main course: `https://www.calamuseducation.com/calamus/course/{courseId}/lesson/{lessonId}`
        - Additional course (`courses.major = 'not'`): `http://www.calamuseducation.com/calamus/watch/{lessonId}`
    - This endpoint only increments a counter; it does not create a new “shared lesson” record.

Example:

```bash
curl -X POST "{APP_URL}/api/lessons/share" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {USER_TOKEN}" \
  -d "lessonId=123"
```

---

## 3) Comment on a lesson (create)

### **POST `/lessons/comment`**

Create a comment on a lesson (supports replying via `parent`).

- **Authentication:** Required
- **Request Body:**
    - `lessonId` (integer, required)
    - `id` (integer, optional alias for `lessonId`)
    - `body` (string, optional) — required if `image` is empty
    - `image` (string, optional) — base64 data URL (`data:image/{ext};base64,...`), required if `body` is empty
    - `parent` (integer, optional, default `0`) — the parent comment ID (uses the legacy `comment.time` value)
- **Response Shape:**

```json
{
    "success": true,
    "data": {
        "success": true,
        "comment": {
            "id": 999,
            "postId": null,
            "lessonId": 123,
            "targetType": "lesson",
            "targetId": 123,
            "writerId": "1677845612345",
            "writerName": "John Doe",
            "writerImage": "https://www.calamuseducation.com/uploads/placeholder.png",
            "body": "Great lesson!",
            "image": "",
            "time": 1710000000000,
            "parent": 0,
            "likes": 0,
            "isLiked": 0,
            "child": []
        }
    }
}
```

- **Notes:**
    - This increments `lessons.comment_count`.
    - Images are limited to 5MB and must be one of: `jpg`, `jpeg`, `png`, `gif`, `webp`.
    - Comments are stored in the `comment` table with:
        - `target_type = 'lesson'`
        - `target_id = {lessonId}`

Example (text comment):

```bash
curl -X POST "{APP_URL}/api/lessons/comment" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {USER_TOKEN}" \
  -d "lessonId=123" \
  -d "body=Great lesson!"
```

Example (reply):

```bash
curl -X POST "{APP_URL}/api/lessons/comment" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {USER_TOKEN}" \
  -d "lessonId=123" \
  -d "body=Replying here" \
  -d "parent=1710000000000"
```

---

## Related endpoints (existing)

Lesson comments listing + comment actions are still served from the shared comments module:

- `GET /comments/get` (pass `lessonId=123`)
- `POST /comments/like` (pass `commentId={comment.time}`)
- `POST /comments/delete`
- `POST /comments/update`
