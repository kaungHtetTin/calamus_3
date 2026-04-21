# Admin API — Package Plans (Read Only)

Base URL for routes in this document: **`{APP_URL}/api/admin`**

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Notes:

- These endpoints are intended for **admin mobile** and admin tooling.
- Requires Sanctum authentication and `EnsureAdminApi`.
- Response keys are **camelCase**.
- Read-only: Create/Update/Delete are not provided.

---

## Endpoints overview

| Method | Endpoint                  | Purpose                                                |
| ------ | ------------------------- | ------------------------------------------------------ |
| GET    | `/package-plans`          | List package plans (optionally filter by major/active) |
| GET    | `/package-plans/{planId}` | Get a single package plan by id                        |

---

## Authentication

Use Admin Sanctum token:

```http
Authorization: Bearer {ADMIN_TOKEN}
```

Login endpoint (already exists):

- `POST {APP_URL}/api/admin/auth/login`

---

## 1) List package plans

### **GET `/package-plans`**

Query params:

| Param    | Type    | Required | Notes                             |
| -------- | ------- | -------- | --------------------------------- |
| `major`  | string  | No       | Filters by `package_plans.major`  |
| `active` | boolean | No       | Filters by `package_plans.active` |

Success response: **200**

```json
{
    "success": true,
    "data": [
        {
            "id": 10,
            "major": "english",
            "name": "VIP Plan",
            "description": "",
            "price": 15000,
            "active": true,
            "sortOrder": 1,
            "courseIds": [1, 2],
            "courses": [
                {
                    "courseId": 1,
                    "title": "Course Title",
                    "major": "english",
                    "fee": 15000,
                    "isVip": 1,
                    "active": 1,
                    "sorting": 1
                }
            ]
        }
    ],
    "meta": {
        "total": 1
    }
}
```

Typical errors:

| HTTP | When                  |
| ---- | --------------------- |
| 401  | Missing/expired token |
| 403  | Token is not an admin |

---

## 2) Get package plan by id

### **GET `/package-plans/{planId}`**

Path params:

| Param    | Type | Required |
| -------- | ---- | -------- |
| `planId` | int  | Yes      |

Success response: **200**

```json
{
    "success": true,
    "data": {
        "id": 10,
        "major": "english",
        "name": "VIP Plan",
        "description": "",
        "price": 15000,
        "active": true,
        "sortOrder": 1,
        "courseIds": [1, 2],
        "courses": [
            {
                "courseId": 1,
                "title": "Course Title",
                "major": "english",
                "fee": 15000,
                "isVip": 1,
                "active": 1,
                "sorting": 1
            }
        ]
    }
}
```

Typical errors:

| HTTP | When                          |
| ---- | ----------------------------- |
| 401  | Missing/expired token         |
| 403  | Token is not an admin         |
| 404  | Package plan not found        |
| 422  | Package plans table not found |
