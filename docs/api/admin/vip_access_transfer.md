# Admin — VIP Access Transfer

This feature is available via:

- Admin web routes (Inertia): **`/admin/users/vip-transfer`**
- Admin API routes (for mobile clients): **`/api/admin/users/vip-transfer`**

It is protected by:

- `auth:admin`
- `admin.permission:user`

---

## Endpoints overview

| Method | Endpoint | Purpose |
| ------ | -------- | ------- |
| GET | `/admin/users/vip-transfer` | Search/preview source + target accounts |
| POST | `/admin/users/vip-transfer` | Execute VIP transfer (move/copy) |
| GET | `/api/admin/users/vip-transfer/preview` | API preview for mobile clients |
| POST | `/api/admin/users/vip-transfer` | API execute for mobile clients |

---

## 1) Preview (search source/target)

### **GET `/admin/users/vip-transfer`**

Query params:

| Param | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `source` | string | No | Source account identifier (email / phone / user_id) |
| `target` | string | No | Target account identifier (email / phone / user_id) |

Behavior:

- Looks up both accounts by **email** or **phone** (and supports **numeric user_id**).
- If both are found, server builds a preview summary to validate that the source has transferable VIP access.

Success response:

- Inertia page: `Admin/VipAccessTransfer`

Page props returned (simplified, camelCase):

```json
{
  "sourceQuery": "source@example.com",
  "targetQuery": "target@example.com",
  "sourceUser": { "userId": 123, "name": "Source", "email": "source@example.com", "phone": "..." },
  "targetUser": { "userId": 456, "name": "Target", "email": "target@example.com", "phone": "..." },
  "preview": {
    "sourceHasVip": true,
    "source": {
      "userId": 123,
      "vipMajors": ["english"],
      "diamondMajors": [],
      "vipCoursesCount": 12
    },
    "target": { "userId": 456 }
  }
}
```

---

## 2) Execute transfer / copy

### **POST `/admin/users/vip-transfer`**

Request body (camelCase):

| Field | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `sourceUserId` | int | Yes | Source `learners.user_id` |
| `targetUserId` | int | Yes | Target `learners.user_id` |
| `mode` | string | Yes | `move` or `copy` |

Example:

```json
{
  "sourceUserId": 123,
  "targetUserId": 456,
  "mode": "move"
}
```

Validation rules:

- Source and target must exist.
- Source and target must be different.
- Source must have VIP access to transfer:
  - any `user_data.is_vip = 1`, or
  - any `user_data.diamond_plan = 1`, or
  - any rows in `vipusers` for the source.

What gets transferred:

- `user_data` VIP flags (`is_vip`, `diamond_plan`) per `major`
- `vipusers` course access rows (deduped on target)

Mode:

- `copy`: keeps VIP on source, adds VIP to target
- `move`: removes VIP from source (clears `is_vip` + `diamond_plan` for affected majors and deletes source `vipusers` rows), then adds VIP to target

Success response:

- Redirect back to `/admin/users/vip-transfer?source={sourceUserId}&target={targetUserId}` with a success flash message.

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Not logged in as admin |
| 403 | Missing `user` permission |
| 422 | Validation errors (including “source has no VIP access”) |

---

## 3) API preview (mobile)

### **GET `/api/admin/users/vip-transfer/preview`**

Headers:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Query params:

| Param | Type | Required | Description |
| ----- | ---- | -------- | ----------- |
| `source` | string | No | Source (email / phone / userId) |
| `target` | string | No | Target (email / phone / userId) |

Success response: **200**

```json
{
  "success": true,
  "data": {
    "sourceQuery": "source@example.com",
    "targetQuery": "target@example.com",
    "sourceUser": { "userId": 123, "name": "Source", "email": "source@example.com", "phone": "..." },
    "targetUser": { "userId": 456, "name": "Target", "email": "target@example.com", "phone": "..." },
    "preview": {
      "sourceHasVip": true,
      "source": { "userId": 123, "vipMajors": ["english"], "diamondMajors": [], "vipCoursesCount": 12 },
      "target": { "userId": 456 }
    }
  }
}
```

---

## 4) API execute (mobile)

### **POST `/api/admin/users/vip-transfer`**

Headers:

- `Accept: application/json`
- `Authorization: Bearer {ADMIN_TOKEN}`

Body (camelCase):

```json
{
  "sourceUserId": 123,
  "targetUserId": 456,
  "mode": "move"
}
```

Success response: **200**

```json
{
  "success": true,
  "data": {
    "mode": "move",
    "message": "VIP access transferred.",
    "previewBefore": { "sourceHasVip": true, "source": { "userId": 123 }, "target": { "userId": 456 } },
    "previewAfter": { "sourceHasVip": false, "source": { "userId": 123 }, "target": { "userId": 456 } }
  }
}
```

Typical errors:

| HTTP | When |
| ---- | ---- |
| 401 | Missing/expired token |
| 403 | Token is not an admin or lacks `user` permission |
| 422 | Validation error (including “source has no VIP access”) |
