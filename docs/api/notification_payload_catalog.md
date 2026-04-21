# Notification Payload Catalog

This file lists the **recommended notification payload structures** used in this project across:

- **Database notifications** (in-app notification list via `GET /api/notifications/get`)
- **FCM push** payloads (via `data` key)

The goal is to keep payloads consistent and make **notification click navigation** reliable.

---

## 1) Standard shape (database notification)

Stored in `notifications.data` (JSON):

```json
{
    "type": "string",
    "actor": { "userId": 0, "name": "", "image": "" },
    "target": {},
    "navigation": { "routeName": "", "params": {} },
    "metadata": {}
}
```

Notes:

- `type` is the primary discriminator.
- `actor.userId` uses learner `user_id`.
- For lesson navigation, always include `courseId` in `navigation.params`.
- `commentId` refers to legacy comment ID (`comment.time`).

---

## 2) Standard shape (FCM push)

`FcmService` converts nested arrays into JSON strings. So the client should parse JSON strings in `data`.

Recommended minimal `data`:

```json
{
    "type": "string",
    "navigation": "{\"routeName\":\"...\",\"params\":{...}}"
}
```

Recommended extended `data`:

```json
{
    "type": "string",
    "major": "english",
    "postId": "123",
    "lessonId": "456",
    "courseId": "12",
    "commentId": "1775980747108",
    "conversationId": "99",
    "navigation": "{\"routeName\":\"...\",\"params\":{...}}"
}
```

---

## 3) Social (Posts)

### 3.1 Post liked

**type:** `post.like`  
**action:** user likes a post  
**target:** post

```json
{
    "type": "post.like",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "postId": 9001 },
    "navigation": { "routeName": "PostDetail", "params": { "postId": "9001" } },
    "metadata": {}
}
```

### 3.2 Post comment created

**type:** `post.comment` (or legacy alias `comment.created`)  
**action:** user comments on a post  
**target:** post + comment

```json
{
    "type": "post.comment",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "postId": 9001, "commentId": 1775980747108 },
    "navigation": {
        "routeName": "PostDetail",
        "params": { "postId": "9001", "commentId": "1775980747108" }
    },
    "metadata": { "commentId": 1775980747108, "parentId": 0 }
}
```

### 3.3 Post comment reply

**type:** `post.comment_reply` (or legacy alias `comment.reply`)  
**action:** user replies to a post comment  
**target:** post + comment + parent

```json
{
    "type": "post.comment_reply",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": {
        "postId": 9001,
        "commentId": 1775980871697,
        "parentId": 1775980747108
    },
    "navigation": {
        "routeName": "PostDetail",
        "params": { "postId": "9001", "commentId": "1775980871697" }
    },
    "metadata": { "commentId": 1775980871697, "parentId": 1775980747108 }
}
```

### 3.4 Comment liked (post)

**type:** `comment.like`  
**action:** user likes a comment on a post  
**target:** post + comment

```json
{
    "type": "comment.like",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": {
        "targetType": "post",
        "targetId": "9001",
        "commentId": 1775980747108
    },
    "navigation": {
        "routeName": "PostDetail",
        "params": { "postId": "9001", "commentId": "1775980747108" }
    },
    "metadata": { "commentId": 1775980747108 }
}
```

---

## 4) Lessons

### 4.1 Lesson added (admin → users, topic)

**type:** `lesson.added`  
**action:** admin adds a lesson  
**target:** lesson + course

```json
{
    "type": "lesson.added",
    "actor": { "userId": 10000, "name": "Admin", "image": "" },
    "target": { "lessonId": 1032, "courseId": 12 },
    "navigation": {
        "routeName": "LessonDetail",
        "params": { "id": "1032", "courseId": "12" }
    },
    "metadata": {}
}
```

### 4.2 Lesson comment (users → admin, topic)

**type:** `lesson.comment`  
**action:** user comments on a lesson  
**target:** lesson + course + comment

```json
{
    "type": "lesson.comment",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "lessonId": 1032, "courseId": 12, "commentId": 1775980747108 },
    "navigation": {
        "routeName": "LessonDetail",
        "params": {
            "id": "1032",
            "courseId": "12",
            "commentId": "1775980747108"
        }
    },
    "metadata": { "commentId": 1775980747108 }
}
```

### 4.3 Lesson comment reply (users → admin OR users → users)

**type:** `lesson.comment_reply`  
**action:** reply to a lesson comment  
**target:** lesson + course + comment + parent

```json
{
    "type": "lesson.comment_reply",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": {
        "lessonId": 1032,
        "courseId": 12,
        "commentId": 1775980871697,
        "parentId": 1775980747108
    },
    "navigation": {
        "routeName": "LessonDetail",
        "params": {
            "lessonId": "1032",
            "courseId": "12",
            "commentId": "1775980871697"
        }
    },
    "metadata": { "commentId": 1775980871697, "parentId": 1775980747108 }
}
```

### 4.4 Comment liked (lesson)

**type:** `comment.like`  
**action:** user likes a comment on a lesson  
**target:** lesson + course + comment

```json
{
    "type": "comment.like",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": {
        "targetType": "lesson",
        "targetId": "1032",
        "courseId": "12",
        "commentId": 1775980747108
    },
    "navigation": {
        "routeName": "LessonDetail",
        "params": {
            "id": "1032",
            "courseId": "12",
            "commentId": "1775980747108"
        }
    },
    "metadata": { "commentId": 1775980747108 }
}
```

---

## 5) Payments

### 5.1 Payment created (users → admin, topic)

**type:** `payment.created`

```json
{
    "type": "payment.created",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "paymentId": 123, "major": "english" },
    "navigation": {
        "routeName": "AdminEnrollCourse",
        "params": { "paymentId": "123" }
    },
    "metadata": { "amount": "10", "transactionId": "..." }
}
```

### 5.2 Payment activated (admin → user, single token)

**type:** `payment.activated`

```json
{
    "type": "payment.activated",
    "actor": { "userId": 10000, "name": "Admin", "image": "" },
    "target": { "paymentId": 123, "major": "english", "courses": [12, 13] },
    "navigation": {
        "routeName": "PurchasedCourses",
        "params": { "major": "english" }
    },
    "metadata": { "courses": [12, 13] }
}
```

---

## 6) Chat

### 6.1 User → user message (single token)

**type:** `chat.message`

```json
{
    "type": "chat.message",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "conversationId": 999 },
    "navigation": {
        "routeName": "ChatDetail",
        "params": { "conversationId": "999", "friendId": "20001" }
    },
    "metadata": {}
}
```

### 6.2 User → admin support message (topic)

**type:** `chat.support`

```json
{
    "type": "chat.support",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "conversationId": 999 },
    "navigation": {
        "routeName": "SupportChat",
        "params": { "conversationId": "999" }
    },
    "metadata": {}
}
```

---

## 7) Friend requests

### 7.1 Friend request sent (single token)

**type:** `friend.request`

```json
{
    "type": "friend.request",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "userId": 30002 },
    "navigation": { "routeName": "FriendRequests", "params": {} },
    "metadata": {}
}
```

### 7.2 Friend request accepted (single token)

**type:** `friend.accept`

```json
{
    "type": "friend.accept",
    "actor": { "userId": 20001, "name": "User A", "image": "https://..." },
    "target": { "userId": 30002 },
    "navigation": {
        "routeName": "UserProfile",
        "params": { "userId": "20001" }
    },
    "metadata": {}
}
```

---

## 8) Topic vs single routing reference

- Single push: use `user_data.token`
- Topic push to users: `languages.firebase_topic_user`
- Topic push to admins: `languages.firebase_topic_admin`
