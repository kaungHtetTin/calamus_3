# API — Lesson Access Flags (`hasAccess`, `hasDownloadAccess`)

This document explains how the app should interpret access flags returned in lesson list APIs.

Base URL for routes in this document: **`{APP_URL}/api`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {USER_TOKEN}` (for endpoints protected by Sanctum)

Notes:

- Response keys are camelCase (server camelizes keys).
- Some legacy endpoints still return `downloadAccess`. Treat it as an alias of `hasDownloadAccess`.

---

## Definitions

### `hasAccess` (lesson playback/read access)

Indicates whether the user can **open** the lesson content (video/document).

- `true` means allow opening the lesson.
- `false` means the client should block access and show a paywall/login prompt.

### `hasDownloadAccess` (download permission)

Indicates whether the user can request a **download URL** for the lesson.

- `true` means allow “Download” action.
- `false` means hide/disable download action.

Legacy alias:

- `downloadAccess` may exist on some endpoints and should be treated the same as `hasDownloadAccess`.

---

## Where these fields appear

### 1) Course Curriculum (Study Plan)

Endpoint:

- `GET /courses/curriculum`

Lesson item fields include:

- `hasAccess`
- `hasDownloadAccess`

Implementation reference:

- `CourseController@curriculum`

Example (lesson object):

```json
{
    "id": 1032,
    "title": "Lesson Title",
    "isVip": 1,
    "learned": 1,
    "hasAccess": true,
    "hasDownloadAccess": false
}
```

Client behavior:

- If `hasAccess` is `false`, block opening the lesson.
- If `hasDownloadAccess` is `false`, hide/disable download.

---

### 2) Additional Lessons (Category Lessons)

Endpoint:

- `POST /additional-lessons/get-lessons` (or whatever route is used in your app)

Lesson item fields include:

- `hasAccess`
- `hasDownloadAccess`
- `downloadAccess` (legacy alias)
- `learned`

Implementation reference:

- `AdditionalLessonController@getLessons`

Example (lesson object):

```json
{
    "id": 555,
    "title": "Extra Lesson",
    "isVip": 0,
    "learned": 0,
    "hasAccess": true,
    "hasDownloadAccess": true,
    "downloadAccess": true
}
```

Client behavior:

- Prefer `hasDownloadAccess`; if missing, fallback to `downloadAccess`.

---

### 3) Video Channel (Course 9)

Endpoint:

- `GET /video-channel?channel={major}`

Lesson item fields include:

- `hasAccess` (currently always `true` for this endpoint)
- `hasDownloadAccess`
- `downloadAccess` (legacy alias)
- `isLearned`

Implementation reference:

- `VideoChannelController@index`

Example (lesson object):

```json
{
    "id": 777,
    "title": "Video Lesson",
    "isLearned": 1,
    "hasAccess": true,
    "hasDownloadAccess": false,
    "downloadAccess": false
}
```

---

## Recommended client logic

Use the following logic for all lesson list pages:

1. Determine playback access:

- `canOpen = Boolean(lesson.hasAccess)`

2. Determine download access:

- `canDownload = lesson.hasDownloadAccess ?? lesson.downloadAccess ?? false`

3. UI handling:

- If `canOpen` is false: show lock/paywall state, prevent navigation to lesson detail/player.
- If `canDownload` is false: hide or disable the download action button.
