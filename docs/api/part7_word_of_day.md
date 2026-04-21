# API Documentation - Part 7: Daily Word by Major

This part covers the daily word endpoint that returns exactly one word for a selected language major on a given local day.

## 1. Daily Word

### **GET `/word-of-day`**
Get one deterministic "word of the day" for a specific `major`.

- **Authentication:** Not Required
- **Request Parameters (Query):**
    - `major` (string, required): Language major (e.g., `english`, `korea`, `ko`).
    - `tz` (string, optional): IANA timezone (e.g., `Asia/Yangon`, `UTC`). Defaults to `UTC` if omitted.

- **Behavior:**
    - Returns only one word for the given major per local day.
    - Uses deterministic selection, so repeated requests with the same `major` + day + `tz` return the same word.
    - `major` is normalized to lowercase; aliases like `ko` and `kr` map to `korea`.

### Success Response (200)

```json
{
    "success": true,
    "data": {
        "id": 15,
        "major": "english",
        "word": "resilient",
        "translation": "strong",
        "speech": "adjective",
        "example": "She stayed resilient through challenges.",
        "thumb": null,
        "audio": null,
        "date": "2026-03-12",
        "timezone": "UTC"
    }
}
```

### Error Responses

#### 400 - Missing required major

```json
{
    "success": false,
    "error": "Missing required query parameter: major"
}
```

#### 400 - Invalid timezone

```json
{
    "success": false,
    "error": "Invalid timezone. Use a valid IANA timezone like Asia/Yangon."
}
```

#### 404 - No words for major

```json
{
    "success": false,
    "error": "No words found for major: english"
}
```

## 2. Usage Examples

### Default timezone (UTC)

`GET /api/word-of-day?major=english`

### With client timezone

`GET /api/word-of-day?major=english&tz=Asia/Yangon`

### Korean alias

`GET /api/word-of-day?major=ko&tz=Asia/Yangon`
