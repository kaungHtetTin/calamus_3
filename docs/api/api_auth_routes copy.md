# API Auth & Route Matrix

This file lists all current API endpoints from `routes/api.php` and shows whether each route requires Sanctum auth.

Base URL prefix for all endpoints: `/api`

Auth header for protected routes:

```http
Authorization: Bearer {token}
```

---

## Public Endpoints (No `auth:sanctum` middleware)

| Method | Endpoint | Notes |
|---|---|---|
| POST | `/auth/login` | Login |
| POST | `/auth/register` | Register |
| POST | `/auth/check-account` | Check account availability |
| POST | `/auth/logout` | Public in route file (can be called without token) |
| GET | `/courses/` | Course list |
| GET | `/courses/all` | Alias of course list |
| GET | `/courses/detail` | Course detail |
| GET | `/courses/curriculum` | Course curriculum |
| GET | `/courses/lesson-categories` | Lesson categories |
| GET | `/courses/featured` | Featured courses |
| GET | `/courses/new` | New courses |
| GET | `/courses/get-certificate` | Certificate lookup |
| GET | `/lessons/detail` | Lesson detail |
| GET | `/users/profile` | User profile |
| GET | `/additional-lessons/courses` | Additional lesson courses |
| GET | `/additional-lessons/lessons` | Additional lesson lessons |
| GET | `/apps/get` | App config/data |
| GET | `/comments/get` | Comment list |
| GET | `/comments/likes` | Comment likes list |
| GET | `/about/stats` | About stats |
| GET | `/languages/get` | Languages |
| GET | `/instructors/all` | Instructor list |
| GET | `/instructors/detail` | Instructor detail |
| GET | `/vip-plan/get` | VIP plans |
| GET | `/stats/home` | Home stats |
| GET | `/mini-library/books` | Library books |
| GET | `/mini-library/categories` | Library categories |
| GET | `/video-channel/get` | Video channel list |
| GET | `/video-channel/video` | Video channel detail |
| GET | `/posts/pinned` | Pinned posts |
| GET | `/ratings/` | Rating list |
| GET | `/ratings/breakdown` | Rating breakdown |
| GET | `/ratings/latest` | Latest ratings |
| GET | `/discussions/get` | Discussion list |
| GET | `/discussions/detail` | Discussion detail |
| GET | `/discussions/likes` | Discussion likes |
| GET | `/songs/get` | Song list |
| GET | `/songs/search` | Song search |
| GET | `/songs/artists` | Artist list |
| POST | `/songs/download` | Increment download count |
| GET | `/announcements/get` | Announcements |
| GET | `/communities/get` | Communities |
| GET | `/vocab-learning/get-decks` | Deck list |
| GET | `/vocab-learning/get-languages` | Vocab languages |
| GET | `/word-of-day/` | Word of day |

---

## Protected Endpoints (Require `auth:sanctum`)

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/user` | Default Laravel authenticated user route |
| GET | `/auth/me` | Current user profile |
| GET | `/lessons/download` | Download lesson |
| POST | `/lessons/mark-learned` | Mark lesson learned |
| GET | `/users/my-learning` | My learning |
| POST | `/users/update` | Update profile |
| POST | `/users/change-password` | Change password |
| POST | `/users/delete-account` | Delete account |
| GET | `/chat/messages` | Get messages |
| POST | `/chat/messages` | Send message |
| POST | `/chat/mark-read` | Mark chat read |
| POST | `/chat/upload-image` | Upload chat image |
| GET | `/chat/conversations` | Get conversations |
| POST | `/chat/conversations` | Create conversation |
| PUT | `/chat/conversations` | Update conversation |
| DELETE | `/chat/conversations` | Delete conversation |
| POST | `/comments/create` | Create comment |
| POST | `/comments/delete` | Delete comment |
| POST | `/comments/update` | Update comment |
| POST | `/comments/like` | Like/unlike comment |
| GET | `/friends/get-friends` | Friend list |
| GET | `/friends/get-requests` | Incoming requests |
| POST | `/friends/add` | Add friend |
| POST | `/friends/confirm` | Confirm request |
| POST | `/friends/unfriend` | Unfriend |
| GET | `/friends/get-status` | Friendship status |
| POST | `/friends/block` | Block user |
| POST | `/friends/unblock` | Unblock user |
| GET | `/friends/get-blocked` | Blocked users |
| GET | `/friends/check-block` | Check block |
| GET | `/friends/people-you-may-know` | Suggestions |
| GET | `/friends/search` | Friend search |
| GET | `/notifications/get` | Notification list |
| POST | `/notifications/mark-seen` | Mark one/all as read |
| POST | `/notifications/mark-one-read` | Mark single as read |
| POST | `/ratings/store` | Create rating |
| POST | `/ratings/delete` | Delete rating |
| DELETE | `/ratings/{id}` | Delete rating by id |
| POST | `/discussions/create` | Create discussion |
| POST | `/discussions/update` | Update discussion |
| POST | `/discussions/delete` | Delete discussion |
| POST | `/discussions/report` | Report discussion |
| POST | `/discussions/like` | Like discussion |
| POST | `/discussions/hide` | Hide discussion |
| POST | `/songs/like` | Like/unlike song |
| POST | `/payments/paid` | Submit payment |
| GET | `/vocab-learning/get-cards` | Get learning cards |
| GET | `/vocab-learning/get-vocab-progress` | Get vocab progress |
| POST | `/vocab-learning/rate-word` | Rate a word |
| POST | `/vocab-learning/skip-word` | Skip a word |

---

## Notes

- This matrix is generated from route middleware declarations only.
- If controller/service code expects a logged-in user on a public route, behavior can still fail without a token even if middleware is not enforced.
- Current notable case: `/auth/logout` is declared as public in routes, but naturally works best when called with a valid bearer token.
