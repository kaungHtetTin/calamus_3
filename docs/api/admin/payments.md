# Admin API — Payments (Activation Queue)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- These endpoints are intended for **admin mobile** and admin tooling.
- Requires Sanctum authentication and admin access permission `user`.

---

## Endpoints overview

| Method | Endpoint                            | Purpose                                                         |
| ------ | ----------------------------------- | --------------------------------------------------------------- |
| GET    | `/payments/unactivated`             | List payments waiting for activation (`payments.activated = 0`) |
| GET    | `/payments/pending-approval`        | List pending payments where approval is not done (`approved = 0` or `approve = 0`) |
| GET    | `/payments/unactivated/{paymentId}` | Unactivated payment detail (payment + user + courses)           |
| POST   | `/payments/{paymentId}/activate`    | Activate a payment and grant course access                      |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## 1) List unactivated payments

### **GET `/payments/unactivated`**

Returns a paginated list of payments where `payments.activated = 0`, including user info and payment metadata used for activation.

Query params:

| Param   | Type | Required | Default | Notes               |
| ------- | ---- | -------- | ------- | ------------------- |
| `page`  | int  | No       | `1`     | Pagination page     |
| `limit` | int  | No       | `25`    | Min `10`, max `100` |

Success response: **200**

Response shape:

```json
{
    "success": true,
    "data": [
        {
            "userId": 445566,
            "username": "User Name",
            "email": "user@example.com",
            "phone": "09....",
            "paymentId": 123,
            "major": "english",
            "paymentAmount": 15000,
            "packagePlan": "diamond",
            "screenshotUrl": "https://.../uploads/..."
        }
    ],
    "meta": {
        "currentPage": 1,
        "lastPage": 10,
        "nextPageToken": "2",
        "total": 250,
        "limit": 25
    }
}
```

Field notes:

- `major` comes from `payments.major` (may be empty string).
- `packagePlan` comes from `payments.meta.packagePlan` when present. It can be `null`.
- `screenshotUrl` is normalized to an absolute URL when possible.

Typical errors:

| HTTP | When                                                   |
| ---- | ------------------------------------------------------ |
| 401  | Missing/expired token                                  |
| 403  | Token is not an admin or admin lacks `user` permission |

---

## 2) List pending approval payments

### **GET `/payments/pending-approval`**

Returns a paginated list of payments where approval is pending:

- Uses `payments.approved = 0` when `approved` column exists.
- Falls back to `payments.approve = 0` when `approve` column exists.

Query params:

| Param   | Type | Required | Default | Notes               |
| ------- | ---- | -------- | ------- | ------------------- |
| `page`  | int  | No       | `1`     | Pagination page     |
| `limit` | int  | No       | `25`    | Min `10`, max `100` |

Success response: **200**

```json
{
    "success": true,
    "data": [
        {
            "paymentId": 123,
            "userId": 445566,
            "username": "User Name",
            "email": "user@example.com",
            "phone": "09....",
            "major": "english",
            "paymentAmount": 15000,
            "packagePlan": "diamond",
            "screenshotUrl": "https://.../uploads/..."
        }
    ],
    "meta": {
        "currentPage": 1,
        "lastPage": 10,
        "nextPageToken": "2",
        "total": 250,
        "limit": 25
    }
}
```

Typical errors:

| HTTP | When                                                   |
| ---- | ------------------------------------------------------ |
| 401  | Missing/expired token                                  |
| 403  | Token is not an admin or admin lacks `user` permission |

---

## 3) Activate payment

### **POST `/payments/{paymentId}/activate`**

Activates a payment (sets `payments.activated = 1`), grants VIP access for the payment courses, and sends notifications.

Path params:

| Param       | Type | Required |
| ----------- | ---- | -------- |
| `paymentId` | int  | Yes      |

Request body:

- No body required.

Success response: **200**

```json
{
    "success": true,
    "data": {
        "paymentId": 123,
        "userId": 445566,
        "major": "english",
        "courses": [1, 2, 3],
        "activated": true
    }
}
```

Typical errors:

| HTTP | When                                                              |
| ---- | ----------------------------------------------------------------- |
| 401  | Missing/expired token                                             |
| 403  | Token is not an admin or admin lacks `user` permission            |
| 404  | Payment not found                                                 |
| 422  | Payment invalid (missing user/courses) or required tables missing |

---

## 4) Unactivated payment detail

### **GET `/payments/unactivated/{paymentId}`**

Returns an unactivated payment detail with:

- `paymentInfo`
- `userInfo`
- `coursesInfo`

Path params:

| Param       | Type | Required |
| ----------- | ---- | -------- |
| `paymentId` | int  | Yes      |

Success response: **200**

```json
{
    "success": true,
    "data": {
        "paymentInfo": {
            "paymentId": 123,
            "userId": 445566,
            "major": "english",
            "paymentAmount": 15000,
            "packagePlan": "diamond",
            "screenshotUrl": "https://.../uploads/..."
        },
        "userInfo": {
            "userId": 445566,
            "username": "User Name",
            "email": "user@example.com",
            "phone": "09....",
            "image": "https://.../uploads/..."
        },
        "coursesInfo": [
            {
                "courseId": 1,
                "title": "Course Title",
                "major": "english",
                "isVip": 1,
                "fee": 15000,
                "active": 1,
                "coverUrl": "https://.../uploads/..."
            }
        ]
    }
}
```

Typical errors:

| HTTP | When                                                     |
| ---- | -------------------------------------------------------- |
| 401  | Missing/expired token                                    |
| 403  | Token is not an admin or admin lacks `user` permission   |
| 404  | Payment not found                                        |
| 422  | Payment is already activated, or required tables missing |
