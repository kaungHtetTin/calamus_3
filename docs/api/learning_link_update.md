# Learning API Update: Link & Download Changes

This document covers the latest learning API contract changes for lesson link and download handling.

## Effective Change

- `GET /api/courses/curriculum` no longer returns `link` in each lesson item.
- `GET /api/additional-lessons/lessons` no longer returns `link` in each lesson item.
- Client must call `GET /api/lessons/detail?id={lessonId}` to get playable/openable lesson link.
- `GET /api/lessons/download` is the dedicated endpoint for download URL and requires authentication + `category`.

## 1) Curriculum Endpoint Update

### Endpoint
- `GET /api/courses/curriculum`

### Request
- `courseId` (integer, required)

### Response Change
- Removed: `lesson.link`
- Kept: `id`, `title`, `duration`, `isVip`, `isVideo`, `thumbnail`, `imageUrl`, `categoryTitle`, `learned`, `hasAccess`

### Updated Example (curriculum item)
```json
{
  "id": 101,
  "title": "Lesson Title",
  "duration": 600,
  "isVip": 0,
  "isVideo": 1,
  "thumbnail": "https://...",
  "imageUrl": "https://...",
  "categoryTitle": "Grammar",
  "learned": 1,
  "hasAccess": true
}
```

## 2) Lesson Detail Endpoint (Source of Truth for Link)

### Endpoint
- `GET /api/lessons/detail?id={lessonId}`

### Link Resolution Logic
- If `isVideo = 1`:
  - `link = lessons.link`
- If `isVideo = 0`:
  - `link = lessons.document_link`

### Response Example (relevant fields)
```json
{
  "success": true,
  "data": {
    "lesson": {
      "id": 101,
      "isVideo": 1,
      "isVip": 0,
      "link": "https://player.vimeo.com/video/12345678",
      "vimeo": "12345678",
      "documentUrl": null,
      "viewCount": 500,
      "likeCount": 20,
      "comments": 10,
      "shareCount": 2,
      "isLiked": 0,
      "learned": 0,
      "hasAccess": true
    }
  }
}
```

## 3) Additional Lessons Endpoint Update

### Endpoint
- `GET /api/additional-lessons/lessons?categoryId={categoryId}&userId={userId}`

### Response Change
- Removed: `lesson.link`
- Kept: `id`, `title`, `titleMini`, `cate`, `isVideo`, `isVip`, `date`, `thumbnail`, `imageUrl`, `duration`, `hasAccess`

### Updated Example (lesson item)
```json
{
  "id": 101,
  "title": "Lesson Title",
  "titleMini": "Mini title",
  "cate": "",
  "isVideo": 1,
  "isVip": 0,
  "date": 1677845612345,
  "thumbnail": "https://...",
  "imageUrl": "https://...",
  "duration": 600,
  "hasAccess": true
}
```

## 4) Download URL Endpoint (New)

### Endpoint
- `GET /api/lessons/download?id={lessonId}&category={major}`

### Purpose
- Returns `downloadUrl` as a separate request instead of in lesson detail payload.

### Request Requirements
- Authentication: **Required** (`Bearer <token>`)
- Query params:
  - `id` (integer, required): lesson id
  - `category` (string, required): major

### Access Rules
- User must be VIP in `user_data` for provided `category` (`is_vip = 1`).
- If lesson is VIP (`lessons.isVip = 1`), user must also have course purchase record.
- If lesson is non-VIP and user is VIP by category, `downloadUrl` is returned.

### Response Example
```json
{
  "success": true,
  "data": {
    "lessonId": 101,
    "title": "Lesson Title",
    "downloadUrl": "https://.../video/file.mp4",
    "hasAccess": true
  }
}
```

## Client Migration Checklist

- Stop using `link` from `/api/courses/curriculum`.
- Stop using `link` from `/api/additional-lessons/lessons`.
- After selecting a lesson, call `/api/lessons/detail?id={lessonId}`.
- Use `lesson.link` from lesson detail as the only content URL.
- If download button is needed, call `/api/lessons/download?id={lessonId}&category={major}` with auth token.
- Keep fallback handling for `vimeo` / `documentUrl` only if legacy client code still depends on them.

