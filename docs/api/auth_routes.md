# API Documentation — Authentication routes

Base URL for routes in this document: **`{APP_URL}/api`** (Laravel `RouteServiceProvider` prefixes `api`). Example: `https://your-domain.com/api/auth/login`.

Unless noted:

- `Accept: application/json`
- `Content-Type: application/json` for JSON bodies

---

## Overview

| Flow                                | Steps                                                                                                                                                                                                                                                                                                |
| ----------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Forgot password (not logged in)** | `POST /auth/forgot-password` → receive **6-digit OTP** by email → `POST /auth/confirm-forgot-password` with `email`, `code`, `password`, `passwordConfirmation`. Revokes all Sanctum tokens for that account.                                                                                        |
| **Change password (logged in)**     | `POST /auth/reset-password` with Bearer token + `oldPassword` (or `currentPassword`) + `password` + `passwordConfirmation`. **No OTP.** Does not revoke existing tokens. Same idea as `POST /users/change-password` (different field names; see [below](#6-reset-password-logged-in--old-password)). |
| **Verify email**                    | After register or `POST /auth/email/resend-verification` → receive **6-digit OTP** → `POST /auth/verify-email` with `email` + `code` (or `otp`).                                                                                                                                                     |

OTP expiry:

- Forgot-password codes: **`MAIL_PASSWORD_RESET_OTP_TTL`** (minutes, default `15`).
- Email verification codes: **`MAIL_EMAIL_VERIFICATION_OTP_TTL`** (minutes, default `15`).

---

## Response shape (AuthController)

Most `/auth/*` endpoints use the `ApiResponse` trait:

### Success

```json
{
    "success": true,
    "data": {}
}
```

Nested keys are converted to **camelCase** where applicable (e.g. `emailVerified`).

Optional `meta` may be present (e.g. logout).

### Error

```json
{
    "success": false,
    "error": "Human-readable message"
}
```

### Sanctum

Protected routes:

```http
Authorization: Bearer {SANCTUM_TOKEN}
```

---

## Related: `GET /api/user`

Defined outside `AuthController`; returns the **authenticated learner model directly** (not wrapped in `success` / `data`).

- **GET `/api/user`**
- **Authentication:** Required (`auth:sanctum`)

Use **`GET /auth/me`** instead if you need the same `{ success, data: { user } }` envelope as other auth endpoints.

---

## 1) Login

### **POST `/auth/login`**

- **Authentication:** Not required

### Request body

| Field        | Type   | Required                  | Description                                                          |
| ------------ | ------ | ------------------------- | -------------------------------------------------------------------- |
| `email`      | string | One of `email` or `phone` | Login with email                                                     |
| `phone`      | string | One of `email` or `phone` | Login with phone                                                     |
| `password`   | string | Yes                       | Account password                                                     |
| `major`      | string | No                        | Used for Sanctum token name and FCM sync (e.g. language app section) |
| `deviceType` | string | No                        | Default `mobile`; e.g. `tablet`, `ipad`                              |
| `fcmToken`   | string | No                        | Max 500 characters                                                   |

### Success (200)

```json
{
    "success": true,
    "data": {
        "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "user": {
            "id": "173000000000012345",
            "name": "Jane",
            "email": "jane@example.com",
            "phone": "09xxxxxxxx",
            "image": "https://...",
            "emailVerified": true
        }
    }
}
```

See [User object](#user-object-in-responses).

### Typical errors

| HTTP | When                                                     |
| ---- | -------------------------------------------------------- |
| 400  | Validation failed (e.g. missing email/phone or password) |
| 401  | Wrong password                                           |
| 404  | No account for that email/phone                          |

---

## 2) Register

### **POST `/auth/register`**

- **Authentication:** Not required

### Request body

| Field      | Type   | Required                  | Description                                      |
| ---------- | ------ | ------------------------- | ------------------------------------------------ |
| `name`     | string | Yes                       | Display name (not numeric-only); max 100 chars   |
| `phone`    | string | One of `phone` or `email` | Digits, `+`, spaces, hyphens; 6–32 chars pattern |
| `email`    | string | One of `phone` or `email` | Valid email if provided                          |
| `password` | string | Yes                       | 6–256 characters                                 |
| `fcmToken` | string | No                        | Max 500 characters                               |

If a valid `email` is provided, the server sends a **6-digit email verification OTP** after registration (PHPMailer / SMTP). Registration **still succeeds** if the email fails to send (failure is logged).

### Success (200)

Same shape as login: `data.token` and `data.user`. New accounts with an email may have `emailVerified: false` until [Verify email](#7-verify-email).

### Typical errors (400 / 500)

| Message (examples)                                 | When                 |
| -------------------------------------------------- | -------------------- |
| Validation messages (name, phone, email, password) | Client validation    |
| Phone/email already registered                     | Duplicate identifier |
| `Failed to create account: ...`                    | Server error (500)   |

---

## 3) Check account availability

### **POST `/auth/check-account`**

- **Authentication:** Not required

### Request body

| Field   | Type   | Required                  | Description    |
| ------- | ------ | ------------------------- | -------------- |
| `email` | string | One of `email` or `phone` | Email to check |
| `phone` | string | One of `email` or `phone` | Phone to check |

### Success (200)

```json
{
    "success": true,
    "data": {
        "email": {
            "value": "user@example.com",
            "isRegistered": false,
            "isAvailable": true
        },
        "phone": {
            "value": null,
            "isRegistered": false,
            "isAvailable": true
        }
    }
}
```

`value` is `null` when that field was not sent in the request.

---

## 4) Forgot password

### **POST `/auth/forgot-password`**

- **Authentication:** Not required

Sends a **6-digit password reset OTP** if a learner exists with that `learner_email`. The response does **not** reveal whether the address exists.

### Request body

| Field   | Type   | Required | Description      |
| ------- | ------ | -------- | ---------------- |
| `email` | string | Yes      | Email to look up |

### Success (200)

```json
{
    "success": true,
    "data": {
        "message": "If an account exists for that email, we sent a password reset code."
    }
}
```

### Errors

| HTTP | When                                                                 |
| ---- | -------------------------------------------------------------------- |
| 400  | Invalid email                                                        |
| 500  | Mail could not be sent (SMTP / `MAIL_HOST` empty / misconfiguration) |

---

## 5) Confirm forgot password (OTP)

### **POST `/auth/confirm-forgot-password`**

- **Authentication:** Not required

Call after [Forgot password](#4-forgot-password): send **email**, **6-digit code**, and **new password**. For users who are **not** logged in.

### Request body

| Field                  | Type   | Required | Description                                            |
| ---------------------- | ------ | -------- | ------------------------------------------------------ |
| `email`                | string | Yes      | Same email the reset code was sent to                  |
| `code`                 | string | Yes      | Six digits (alias: `otp`)                              |
| `password`             | string | Yes      | New password, 6–256 characters                         |
| `passwordConfirmation` | string | Yes      | Must match `password` (alias: `password_confirmation`) |

Non-digits in `code` / `otp` are stripped (e.g. `"123 456"` → `123456`).

### Success (200)

```json
{
    "success": true,
    "data": {
        "message": "Your password has been reset. You can sign in with your new password."
    }
}
```

All **Sanctum tokens** for that learner are revoked; the user must log in again with the new password.

### Typical errors

| HTTP | When                                                |
| ---- | --------------------------------------------------- |
| 400  | Invalid/expired OTP, wrong confirmation, validation |
| 404  | Account not found (edge case after data mismatch)   |

---

## 6) Reset password (logged in — old password)

### **POST `/auth/reset-password`**

- **Authentication:** Required (`auth:sanctum`)

Change password while logged in: **current password** + **new password**. **No OTP.** Not related to [Verify email](#7-verify-email).

### Request body

| Field                  | Type   | Required | Description                                            |
| ---------------------- | ------ | -------- | ------------------------------------------------------ |
| `oldPassword`          | string | Yes\*    | Current password                                       |
| `currentPassword`      | string | Yes\*    | Same as `oldPassword` (either field accepted)          |
| `password`             | string | Yes      | New password, 6–256 characters                         |
| `passwordConfirmation` | string | Yes      | Must match `password` (alias: `password_confirmation`) |

\*At least one of `oldPassword` or `currentPassword` must be non-empty.

### Success (200)

```json
{
    "success": true,
    "data": {
        "message": "Your password has been updated."
    }
}
```

Existing sessions / tokens are **not** revoked (same behaviour as **`POST /api/users/change-password`**, which uses `currentPassword` and `newPassword` instead).

### Typical errors

| HTTP | When                                                                            |
| ---- | ------------------------------------------------------------------------------- |
| 401  | Missing or invalid Bearer token                                                 |
| 400  | Missing old password, wrong current password, confirmation mismatch, validation |

---

## 7) Verify email

### **POST `/auth/verify-email`**

- **Authentication:** Not required

Confirms the email using the **6-digit OTP** from registration or [Resend verification](#10-resend-email-verification-otp).

### Request body

| Field   | Type   | Required | Description                    |
| ------- | ------ | -------- | ------------------------------ |
| `email` | string | Yes      | Address that received the code |
| `code`  | string | Yes      | Six digits (alias: `otp`)      |

Non-digits in `code` / `otp` are stripped.

### OTP lifetime

**`MAIL_EMAIL_VERIFICATION_OTP_TTL`** (minutes, default `15`).

### Success (200)

```json
{
    "success": true,
    "data": {
        "message": "Your email has been verified."
    }
}
```

### Typical errors

| HTTP | When                                             |
| ---- | ------------------------------------------------ |
| 400  | Invalid/incorrect code, expired code, validation |
| 404  | Account not found (edge case)                    |

---

## 8) Logout

### **POST `/auth/logout`**

- **Authentication:** Optional

- **With `Authorization: Bearer`** — revokes the **current** Sanctum token and clears `auth_token` on the learner.
- **Without Bearer** — request succeeds, but there is no token to revoke.

### Success (200)

```json
{
    "success": true,
    "data": [],
    "meta": {
        "status": "success"
    }
}
```

---

## 9) Current user (`me`)

### **GET `/auth/me`**

- **Authentication:** Required (`auth:sanctum`)
- **Query params:** `major` (optional)

When `major` is provided, backend checks `user_data.is_vip` for `(user_id, major)` and includes `blueMarkAccess` in `data.user`.

### Request examples

```http
GET /api/auth/me
Authorization: Bearer {SANCTUM_TOKEN}
Accept: application/json
```

```http
GET /api/auth/me?major=english
Authorization: Bearer {SANCTUM_TOKEN}
Accept: application/json
```

### Success (200)

```json
{
    "success": true,
    "data": {
        "user": {
            "id": "...",
            "name": "...",
            "email": "...",
            "phone": "...",
            "image": "...",
            "emailVerified": true
        }
    }
}
```

With `major`:

```json
{
    "success": true,
    "data": {
        "user": {
            "id": "...",
            "name": "...",
            "email": "...",
            "phone": "...",
            "image": "...",
            "emailVerified": true,
            "blueMarkAccess": false
        }
    }
}
```

### User object in responses

| Field            | Type    | Description                                                                            |
| ---------------- | ------- | -------------------------------------------------------------------------------------- |
| `id`             | string  | Learner `user_id`                                                                      |
| `name`           | string  | Display name                                                                           |
| `email`          | string  | Email                                                                                  |
| `phone`          | string  | Phone                                                                                  |
| `image`          | string  | Profile image URL                                                                      |
| `emailVerified`  | boolean | `true` if `email_verified_at` is set                                                   |
| `blueMarkAccess` | boolean | Only present when `major` is provided; `true` if `user_data.is_vip = 1` for that major |

### Errors

| HTTP | When                     |
| ---- | ------------------------ |
| 401  | Missing or invalid token |

---

## 10) Resend email verification (OTP)

### **POST `/auth/email/resend-verification`**

- **Authentication:** Required (`auth:sanctum`)
- Requires a non-empty `learner_email` and `email_verified_at` must be null.

### Request body

Empty object `{}` is fine.

### Success (200)

```json
{
    "success": true,
    "data": {
        "message": "We sent a new verification code to your email address."
    }
}
```

### Typical errors

| HTTP | When                             |
| ---- | -------------------------------- |
| 401  | Not authenticated                |
| 400  | No email, already verified, etc. |
| 500  | Mail could not be sent           |

---

## Environment variables (mail)

| Variable                                                                      | Purpose                                   |
| ----------------------------------------------------------------------------- | ----------------------------------------- |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | SMTP (PHPMailer)                          |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`                                         | From header                               |
| `MAIL_PASSWORD_RESET_OTP_TTL`                                                 | Forgot-password OTP lifetime (minutes)    |
| `MAIL_EMAIL_VERIFICATION_OTP_TTL`                                             | Email verification OTP lifetime (minutes) |

After changing `.env`, run `php artisan config:clear` if configuration is cached in production.

---

## Quick reference

| Method | Path                                  | Auth     |
| ------ | ------------------------------------- | -------- |
| GET    | `/api/user`                           | Yes      |
| POST   | `/api/auth/login`                     | No       |
| POST   | `/api/auth/register`                  | No       |
| POST   | `/api/auth/check-account`             | No       |
| POST   | `/api/auth/forgot-password`           | No       |
| POST   | `/api/auth/confirm-forgot-password`   | No       |
| POST   | `/api/auth/verify-email`              | No       |
| POST   | `/api/auth/logout`                    | Optional |
| GET    | `/api/auth/me`                        | Yes      |
| POST   | `/api/auth/reset-password`            | Yes      |
| POST   | `/api/auth/email/resend-verification` | Yes      |

Related (users profile, not under `/auth`): **`POST /api/users/change-password`** — same “logged-in password change” behaviour as `/auth/reset-password` with different JSON field names (`currentPassword`, `newPassword`).
