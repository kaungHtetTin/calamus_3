# Client API Update (Request/Response)

This file is a client-facing changelog for request/response contract updates.

## General Rules

- All JSON keys use `camelCase`.
- Auth-required endpoints still use `Authorization: Bearer <token>`.
- For backward compatibility, some endpoints still accept legacy fields, but clients should migrate to the keys in this file.

## 1) Auth & User Profile Updates

### POST `/api/auth/login`

**Request updates**
- `fcmToken` (string, optional): Firebase token to store in `user_data.token`.

**Response notes**
- Response shape remains compatible; no required client-side parsing change except standard `camelCase` usage.

### POST `/api/auth/register`

**Request updates**
- `fcmToken` (string, optional): Firebase token to store in `user_data.token`.

**Response notes**
- Response shape remains compatible; no required client-side parsing change except standard `camelCase` usage.

### POST `/api/auth/check-account`

Check if account already exists by email or phone.

**Request**
- `email` (string, optional if `phone` provided)
- `phone` (string, optional if `email` provided)

**Response**
- Includes availability flags for provided identifiers.

### GET `/api/users/profile?id={userId}`

**Response updates**
- Includes `email`.
- Includes `gender`.
- Includes `birthday` object:
  - `birthday.day`
  - `birthday.month`
  - `birthday.year`

### POST `/api/users/update`

**Request updates**
- Accepts `gender` (`male|female|other`).
- Accepts birthday in either format:
  - Object format: `birthday.day`, `birthday.month`, `birthday.year`
  - Flat format: `birthdayDay`, `birthdayMonth`, `birthdayYear`

**Response updates**
- Returns updated profile fields including `gender` and `birthday`.

## 2) Learning API Updates

### GET `/api/lessons/detail?id={lessonId}`

**Response updates**
- Social fields now come from `lessons` directly:
  - `likeCount`
  - `comments`
  - `shareCount`
  - `viewCount`
- Includes `downloadUrl`.
- Includes `link` with this logic:
  - if `isVideo = 1` -> `link = lessons.link`
  - if `isVideo = 0` -> `link = lessons.document_link`
- `isLiked` is lesson-like based.
- `postId` is no longer part of lesson payload.

### GET `/api/video-channel/get`

**Behavior updates**
- Scoped to video-channel course (`course_id = 9`).
- If user is authenticated, each lesson includes `isLearned`.

### GET `/api/video-channel/video?id={lessonId}`

**Behavior/response updates**
- Scoped to video-channel course (`course_id = 9`).
- Includes lesson social fields from `lessons`.
- Includes `isLearned` for authenticated user.
- `isLiked` is lesson-like based.
- `postId` is not returned in `currentVideo`.

## 3) Social & Community API Updates

### GET `/api/discussions/likes?postId={postId}`

**New endpoint**
- Returns list of users who liked the post.

### POST `/api/discussions/update`

**New endpoint**
- Edit discussion post.

**Request**
- `postId` (required)
- `body` (optional but required if no image)
- `image` (optional)
- `removeImage` (optional boolean)
- `category` (optional)

### GET `/api/comments/get`

**Request updates (polymorphic target)**
- Can target by:
  - `postId`, or
  - `lessonId`, or
  - `targetType` + `targetId` where `targetType` is `post|lesson`

**Response updates**
- Each comment includes:
  - `postId` (nullable)
  - `lessonId` (nullable)
  - `targetType`
  - `targetId`

### POST `/api/comments/create`

**Request updates**
- Supports same polymorphic target rules as `/comments/get`.
- Supports `image` upload (base64 string).

**Response updates**
- Created comment includes `postId`, `lessonId`, `targetType`, and `targetId`.

### GET `/api/comments/likes?commentId={commentId}`

**New endpoint**
- Returns list of users who liked the comment.

## 4) Rating API Update

### POST `/api/ratings/delete`

**New endpoint**
- Deletes rating by body parameter (`id` or `ratingId`).

## Client Action Checklist

- Update client models to use `camelCase` keys.
- Add `fcmToken` to login/register request builders.
- Update profile UI/data model for `email`, `gender`, and `birthday`.
- Update lesson/video-channel parsing to remove `postId` dependency.
- Stop reading `link` from `/api/courses/curriculum`; fetch lesson content from `/api/lessons/detail`.
- Update comments flow to support polymorphic target (`post` or `lesson`).
- Integrate new like-list and discussion-edit endpoints.
