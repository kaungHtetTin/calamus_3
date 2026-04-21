# API Documentation - User Profile Posts

This document describes how `GET /api/users/profile` returns post lists for a user profile, including **shared posts** with embedded parent post data.

**Base path:** `{APP_URL}/api/users`

---

## 1) Get user profile (posts / shared tabs)

### **GET `/users/profile`**

- **Authentication:** Not required
- **Query parameters:**

| Parameter | Type    | Required | Default | Description |
|----------|---------|----------|---------|-------------|
| `id`     | string  | Yes      | -       | Target user id |
| `tab`    | string  | No       | `posts` | `posts` or `shared` |
| `page`   | integer | No       | 1       | Page number |

### Tab behavior

#### `tab=posts`

- Returns only **original posts** created by the user.
- Excludes shared posts by enforcing `share = 0`.

#### `tab=shared`

- Returns only **shared posts** created by the user.
- Includes `parentPost` (original post) by linking:
  - `shared.share = parent.post_id`

---

## 2) Response format

### **Success response (200)**

```json
{
  "success": true,
  "data": {
    "id": 12,
    "userId": "10001",
    "name": "Alice",
    "email": "alice@example.com",
    "gender": "female",
    "birthday": { "day": 1, "month": 2, "year": 2000 },
    "image": "https://example.com/uploads/users/alice.jpg",
    "coverImage": "https://example.com/uploads/users/cover.jpg",
    "bio": "",
    "work": "",
    "education": "",
    "region": "",
    "posts": [],
    "totalPosts": 0,
    "page": 1
  },
  "meta": {
    "currentPage": 1,
    "lastPage": 1,
    "nextPageToken": null,
    "total": 0,
    "limit": 10
  }
}
```

> Note: keys are camelCased by the API response wrapper.

---

## 3) Post item shapes

### A) `tab=posts` item

```json
{
  "postId": 1710860123456,
  "body": "Hello",
  "postImage": "https://example.com/uploads/posts/p.png",
  "userId": "10001",
  "userName": "Alice",
  "userImage": "https://example.com/uploads/users/alice.jpg",
  "postLikes": 4,
  "comments": 2,
  "shareCount": 1,
  "viewCount": 10
}
```

### B) `tab=shared` item (with `parentPost`)

```json
{
  "postId": 1710860999999,
  "userId": "10001",
  "userName": "Alice",
  "userImage": "https://example.com/uploads/users/alice.jpg",
  "postLikes": 0,
  "comments": 0,
  "shareCount": 0,
  "viewCount": 0,
  "category": "english",
  "share": 1710860123456,
  "parentPost": {
    "postId": 1710860123456,
    "body": "Original post body",
    "postImage": "https://example.com/uploads/posts/original.png",
    "userId": "20002",
    "userName": "Bob",
    "userImage": "https://example.com/uploads/users/bob.jpg",
    "postLikes": 10,
    "comments": 3,
    "shareCount": 5,
    "viewCount": 99,
    "category": "english"
  }
}
```

### Parent post missing

If the parent post cannot be found (deleted/missing), `parentPost` is `null`:

```json
{
  "postId": 1710860999999,
  "share": 1710860123456,
  "parentPost": null
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

- `400` — when `id` is missing
- `404` — when user is not found

