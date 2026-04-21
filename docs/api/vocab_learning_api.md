# Vocabulary Learning API - Request/Response Reference

This document provides clear request and response formats for all vocab-learning endpoints. Use this when integrating from Android, iOS, or web clients.

**Base URL:** `/api/vocab-learning`

**Authentication:** Endpoints under "Auth Required" need `Authorization: Bearer <token>` header.

---

## Endpoints Overview

| Method | Endpoint | Auth | Purpose |
|--------|----------|------|---------|
| GET | `/get-languages` | No | List languages |
| GET | `/get-decks` | No | List decks |
| GET | `/get-cards` | Yes | Get learning session cards |
| GET | `/get-vocab-progress` | Yes | Get user progress |
| POST | `/rate-word` | Yes | Rate a word (SM2) |
| POST | `/skip-word` | Yes | Skip a word, get replacement |

---

## 1. GET `/get-languages`

No parameters required.

**Response:**
```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "English", "code": "en", "image": "..." }
  ]
}
```

---

## 2. GET `/get-decks`

**Query parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| major | string | No* | e.g. `english`, `korean` |
| languageId | integer | No* | Filter by language ID |
| userId | string | No | Include progress per deck |

*At least one of `major` or `languageId` is recommended.

**Example:** `GET /api/vocab-learning/get-decks?major=english`

---

## 3. GET `/get-cards` (Auth Required)

**Query parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| deckId | integer | **Yes** | Deck ID |
| wordCount | integer | No | Default: 10 |

**Example:** `GET /api/vocab-learning/get-cards?deckId=1&wordCount=15`

**Response:**
```json
{
  "success": true,
  "data": {
    "words": [...],
    "step": "step1",
    "deckId": 1,
    "word_counts": { "total": 10, "recall_words": 3, "new_words": 7 }
  }
}
```

---

## 4. GET `/get-vocab-progress` (Auth Required)

No parameters. Uses authenticated user.

**Example:** `GET /api/vocab-learning/get-vocab-progress`

---

## 5. POST `/rate-word` (Auth Required)

**Content-Type:** `application/json` or `application/x-www-form-urlencoded`

**Body parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| cardId | integer | **Yes** | Card ID being rated |
| quality | integer | **Yes** | 0–5 (0–2: forgot, 3–5: remembered) |

**JSON example:**
```json
{
  "cardId": 101,
  "quality": 4
}
```

**Form-urlencoded example:**
```
cardId=101&quality=4
```

---

## 6. POST `/skip-word` (Auth Required)

**Content-Type:** `application/json` or `application/x-www-form-urlencoded`

**Body parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| cardId | integer | **Yes** | Card ID to skip |
| deckId | integer | **Yes** | Deck ID (for replacement lookup) |
| reason | string | No | Default: `already_know` |
| sessionCardIds | array | No | IDs of cards in current session to avoid duplicates |

### `sessionCardIds` – Important for Android

`sessionCardIds` must be sent as an **array**. Accepted formats:

1. **JSON array (recommended):**
   ```json
   {
     "cardId": 101,
     "deckId": 1,
     "sessionCardIds": [101, 102, 103]
   }
   ```

2. **Form-urlencoded with bracket notation:**
   ```
   cardId=101&deckId=1&sessionCardIds[]=101&sessionCardIds[]=102&sessionCardIds[]=103
   ```

3. **JSON string of comma-separated IDs (also accepted):**
   ```json
   { "sessionCardIds": "101,102,103" }
   ```

4. **Omit entirely if empty:**
   ```json
   { "cardId": 101, "deckId": 1 }
   ```

**Response:**
```json
{
  "success": true,
  "data": {
    "replacement_word": {
      "card": { "id": 104, "word": "...", ... },
      "rich_data": { ... },
      "word_type": "new",
      "is_known": false
    },
    "skipped_word": {
      "card_id": 101,
      "paused_until": "permanent"
    }
  }
}
```

---

## Common Errors

| Code | Message | Cause |
|------|---------|-------|
| 400 | Missing required parameters: cardId, deckId | Required fields not sent |
| 404 | Deck not found | Invalid deckId |
| 401 | Unauthenticated | Missing or invalid Bearer token |

---

## Response keys (camelCase vs snake_case)

API responses use **camelCase** for top-level keys (via `ApiResponse` trait). Nested structures from Eloquent/DB may use **snake_case** (e.g. `rich_data`, `word_type`). Check actual responses and handle both styles if needed.
