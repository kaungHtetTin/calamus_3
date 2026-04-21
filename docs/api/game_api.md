# API Documentation - Game

Game APIs for random question fetch, major-scoped leaderboard, and high-score update.

**Base path:** `{APP_URL}/api/games`

**Authentication:** Not required (public endpoints)

---

## 1. Get random game word

### **GET `/games/words/random`**

Returns one random game word for the requested `major`.

- **Query parameters:**

| Parameter | Type   | Required | Description                         |
|-----------|--------|----------|-------------------------------------|
| `major`   | string | Yes      | Learning major (e.g. `english`)     |

- **Success response (200):**

```json
{
    "success": true,
    "data": {
        "id": 1024,
        "major": "english",
        "displayWord": "apple",
        "displayImage": "https://example.com/uploads/game/apple.png",
        "displayAudio": "https://example.com/uploads/game/apple.mp3",
        "category": 1,
        "a": "apple",
        "b": "orange",
        "c": "banana",
        "ans": "a"
    }
}
```

- **Error responses:**
  - `400` — Missing/invalid `major`
  - `404` — No game words found for selected major

---

## 2. Get leaderboard by major

### **GET `/games/scores/leaderboard`**

Returns top scores from `user_data.game_score`, filtered by `major`.

- **Query parameters:**

| Parameter | Type    | Required | Default | Description                          |
|-----------|---------|----------|---------|--------------------------------------|
| `major`   | string  | Yes      | -       | Learning major (e.g. `english`)      |
| `limit`   | integer | No       | 50      | Number of rows (range: 1 to 100)     |

- **Success response (200):**

```json
{
    "success": true,
    "data": [
        {
            "userId": "10001",
            "learnerName": "Alice",
            "learnerImage": "https://example.com/uploads/users/alice.jpg",
            "gameScore": 89
        },
        {
            "userId": "10002",
            "learnerName": "Bob",
            "learnerImage": "https://example.com/uploads/users/bob.jpg",
            "gameScore": 73
        }
    ]
}
```

- **Notes:**
  - Only users with `game_score > 0` are returned.
  - Ordering: highest score first, then `user_id` ascending for ties.

- **Error responses:**
  - `400` — Missing/invalid `major` or invalid `limit`

---

## 3. Submit high score

### **POST `/games/scores`**

Submits a score for a user and major. Backend updates `user_data.game_score` **only if submitted score is greater than existing high score**.

- **Request body (JSON):**

| Field     | Type    | Required | Description                          |
|-----------|---------|----------|--------------------------------------|
| `user_id` | string  | Yes      | Learner user id                      |
| `major`   | string  | Yes      | Learning major (e.g. `english`)      |
| `score`   | integer | Yes      | Submitted run score (`>= 0`)         |

- **Success response (200):**

```json
{
    "success": true,
    "data": {
        "userId": "10001",
        "major": "english",
        "submittedScore": 64,
        "highScore": 89,
        "updated": false
    }
}
```

When high score is improved:

```json
{
    "success": true,
    "data": {
        "userId": "10001",
        "major": "english",
        "submittedScore": 95,
        "highScore": 95,
        "updated": true
    }
}
```

- **Behavior:**
  - Uses `user_id` (not `phone`).
  - If `user_data` row does not exist for `user_id + major`, it is created.
  - Existing high score is preserved when submitted score is lower/equal.

- **Error responses:**
  - `400` — Missing/invalid input (`user_id`, `major`, or `score`)
  - `404` — User not found

---

## Common error format

```json
{
    "success": false,
    "error": "Error message"
}
```
