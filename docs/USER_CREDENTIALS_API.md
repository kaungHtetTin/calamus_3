# User Credentials API (Email / Phone)

This document describes how a signed-in user can change credential data (email and phone) from the client app.

## Auth

All endpoints below require a valid Sanctum token.

Send header:

```http
Authorization: Bearer <token>
Content-Type: application/json
```

## Endpoints

### Update Email / Phone

`POST /api/users/update-credentials`

Updates the current user’s `learner_email` and/or `learner_phone`.  
For security, the current password is required.

**Body**

- `password` (string, required) – current password
- `email` (string, optional, nullable) – new email address
- `phone` (string, optional, nullable) – new phone number

Notes:

- You can send only `email`, only `phone`, or both.
- `email` must be unique in `learners.learner_email`.
- `phone` must be unique in `learners.learner_phone`.

**Example**

```bash
curl -X POST "http://localhost/calamus-v3/public/api/users/update-credentials" ^
  -H "Authorization: Bearer <token>" ^
  -H "Content-Type: application/json" ^
  -d "{\"password\":\"current_password\",\"email\":\"new@email.com\",\"phone\":\"628123456789\"}"
```

**Success Response (200)**

```json
{
    "success": true,
    "data": {
        "success": true,
        "message": "Credentials updated successfully",
        "user": {
            "email": "new@email.com",
            "phone": "628123456789"
        }
    }
}
```

## Error Responses

Typical errors:

- `401 Not authenticated`
- `400 Incorrect password`
- `400 This email is already associated with another account.`
- `400 This phone number is already associated with another account.`
- `400 <validation error message>`
