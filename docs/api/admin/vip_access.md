# Admin API — VIP Access (Per Language)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- These endpoints are intended for **admin mobile** and admin tooling.
- Requires Sanctum authentication and admin access permission `user`.
- VIP scope is **per user + per language** (`major`).
- “All courses” means `courses.major = {major}`.
- Response keys are **camelCase**.

---

## Endpoints overview

| Method | Endpoint                                   | Purpose                                                         |
| ------ | ------------------------------------------ | --------------------------------------------------------------- |
| GET    | `/users/{userId}/vip-access?major={major}` | Get VIP status for all courses in a language scope              |
| POST   | `/users/{userId}/vip-access`               | Update VIP access for selected courses (creates payment record) |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## 1) Get VIP status (per user + major)

### **GET `/users/{userId}/vip-access`**

Query params:

| Param   | Type   | Required | Notes                           |
| ------- | ------ | -------- | ------------------------------- |
| `major` | string | Yes      | Language scope (e.g. `english`) |

Success response: **200**

```json
{
    "success": true,
    "data": {
        "userId": 445566,
        "major": "english",
        "isVip": 1,
        "isDiamond": 0,
        "selectedCourseIds": [1, 2],
        "packagePlans": [
            {
                "id": 10,
                "major": "english",
                "name": "VIP Plan",
                "description": "",
                "price": 15000,
                "active": true,
                "sortOrder": 1,
                "courseIds": [1, 2]
            }
        ],
        "courses": [
            {
                "courseId": 1,
                "title": "Course Title",
                "major": "english",
                "isVipCourse": 1,
                "active": 1,
                "fee": 15000,
                "isGrantedVip": true,
                "packagePlan": "vip"
            }
        ]
    }
}
```

Typical errors:

| HTTP | When                                                   |
| ---- | ------------------------------------------------------ |
| 401  | Missing/expired token                                  |
| 403  | Token is not an admin or admin lacks `user` permission |
| 422  | Missing `major` or required tables missing             |

---

## 2) Update VIP access (selected courses) + create payment record

### **POST `/users/{userId}/vip-access`**

This endpoint updates `vipusers` for the requested `userId + major`:

- Removes VIP rows for courses under the given `major` that are not in `selectedCourseIds`
- Inserts VIP rows for courses in `selectedCourseIds` (if missing)

It also creates a payment record with:

- `activated = 1`
- `approved = 0` (or `approve = 0` depending on column name)

Content type:

- `multipart/form-data` (required because screenshot is a file upload)

Form fields:

| Field                 | Type    | Required | Notes                            |
| --------------------- | ------- | -------- | -------------------------------- |
| `major`               | string  | Yes      | Language scope                   |
| `isVip`               | boolean | Yes      | Updates `user_data.is_vip`       |
| `isDiamond`           | boolean | Yes      | Updates `user_data.diamond_plan` |
| `selectedCourseIds[]` | int[]   | Yes      | Course ids under this major      |
| `amount`              | number  | Yes      | Payment amount                   |
| `transactionId`       | string  | Yes      | Payment transaction id           |
| `screenshot`          | file    | Yes      | Screenshot image                 |
| `partnerCode`         | string  | No       | Optional partner private code    |

Success response: **200**

```json
{
    "success": true,
    "data": {
        "userId": 445566,
        "major": "english",
        "isVip": 1,
        "isDiamond": 0,
        "selectedCourseIds": [1, 2],
        "insertedCourseIds": [2],
        "removedCourseIds": [3],
        "payment": {
            "activated": 1,
            "approved": 0,
            "amount": 15000,
            "transactionId": "TXN-123",
            "screenshotUrl": "https://.../uploads/...",
            "partnerCode": "ABC123"
        }
    }
}
```

Typical errors:

| HTTP | When                                                                        |
| ---- | --------------------------------------------------------------------------- |
| 401  | Missing/expired token                                                       |
| 403  | Token is not an admin or admin lacks `user` permission                      |
| 422  | Validation failed, partner code invalid, or required tables/columns missing |
