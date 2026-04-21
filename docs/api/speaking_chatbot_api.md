# Speaking Chatbot API Documentation

This document describes the unified API for the Speaking Chatbot in Calamus V3.

## Base URL

`/api/speaking-chatbot`

---

## 1. Fetch Dialogues

Retrieve dialogue lines for the user's current level. The system automatically resolves the current level from `user_data.meta` or legacy `user_data.speaking_level`.

- **Endpoint**: `GET /dialogues`
- **Authentication**: Required (Sanctum)
- **Query Parameters**:
    - `major`: (Required) e.g., `english`, `korea`, `chinese`.
- **Response**:

```json
{
    "success": true,
    "data": {
        "level": 1,
        "title": "Introduction",
        "dialogues": [
            {
                "id": 1,
                "major": "english",
                "speakingDialogueTitleId": 1,
                "personAText": "Hello, how are you?",
                "personATranslation": "နေကောင်းလား?",
                "personBText": "I am fine, thank you.",
                "personBTranslation": "နေကောင်းပါတယ်၊ ကျေးဇူးတင်ပါတယ်။",
                "sortOrder": 1,
                "createdAt": "2026-04-01T16:23:03.000000Z",
                "updatedAt": "2026-04-01T16:23:03.000000Z"
            }
        ]
    },
    "message": "Operation successful"
}
```

---

## 2. Get User Progress

Retrieve the user's current speaking progress for a specific major.

- **Endpoint**: `GET /progress`
- **Authentication**: Required (Sanctum)
- **Query Parameters**:
    - `major`: (Required) e.g., `english`.
- **Response**:

```json
{
    "success": true,
    "data": {
        "userId": 912345678,
        "major": "english",
        "currentLevel": 1,
        "levelTitle": "Introduction"
    },
    "message": "Operation successful"
}
```

---

## 3. Complete Level

Advance the user to the next level. The system calculates the next level ID and updates the `user_data.meta` JSON column.

- **Endpoint**: `POST /complete-level`
- **Authentication**: Required (Sanctum)
- **Body (JSON)**:
    - `major`: (Required) e.g., `english`.
- **Response**:

```json
{
    "success": true,
    "data": {
        "major": "english",
        "currentLevel": 2,
        "levelTitle": "Daily Routine",
        "isMaxLevel": false
    },
    "message": "Progress updated successfully"
}
```

---

## 4. Record Error Log

Log an incorrect speech attempt for later analysis.

- **Endpoint**: `POST /error-log`
- **Authentication**: Required (Sanctum)
- **Body (JSON)**:
    - `major`: (Required) e.g., `english`.
    - `dialogue_id`: (Required) The ID of the dialogue line being attempted.
    - `error_text`: (Required) The text recognized by the speech engine.
- **Response**:

```json
{
    "success": true,
    "data": {
        "userId": "912345678",
        "major": "english",
        "dialogueId": "1",
        "errorText": "Hello how you",
        "updatedAt": "2026-04-02T06:20:00.000000Z",
        "createdAt": "2026-04-02T06:20:00.000000Z",
        "id": 101
    },
    "message": "Error log recorded successfully"
}
```
