# API Documentation — Payments

Base URL for routes in this document: **`{APP_URL}/api`**.

Unless noted:

- `Accept: application/json`
- `Authorization: Bearer {SANCTUM_TOKEN}` (required for all endpoints in this document)

---

## Submit payment

### **POST `/payments/paid`**

Submit a payment record with a screenshot proof image. Stores `package_plan` inside `payments.meta` as JSON.

- **Authentication:** Required (`auth:sanctum`)
- **Content-Type:** `multipart/form-data` (because `screenshot` is a file upload)

### Request (multipart form)

| Field | Type | Required | Description |
| --- | --- | --- | --- |
| `amount` | number | Yes | Payment amount |
| `major` | string | Yes | Major / app section identifier |
| `courses[]` | array | Yes | List of course identifiers (array) |
| `package_plan` | mixed | Yes | Package plan payload. Can be an object/array (JSON) or a JSON string. Saved into `meta.packagePlan`. |
| `screenshot` | file | Yes | Image proof. `jpeg,png,jpg,gif,webp`, max 5MB |
| `transactionId` | string | Yes | Unique transaction id (`unique:payments,transaction_id`) |

Notes:

- When the client uses multipart uploads, it may send `package_plan` as a string. If the value looks like JSON (`{...}` or `[...]`) it is decoded and stored as JSON.
- The API response uses the common `{ success, data, meta }` envelope. `meta` is a string message for this endpoint.

### Example request (cURL)

```bash
curl -X POST "{APP_URL}/api/payments/paid" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer {SANCTUM_TOKEN}" \
  -F "amount=9.99" \
  -F "major=english" \
  -F "courses[]=course_1" \
  -F "courses[]=course_2" \
  -F "package_plan={\"id\":1,\"name\":\"VIP Monthly\"}" \
  -F "transactionId=txn_1234567890" \
  -F "screenshot=@/path/to/screenshot.jpg"
```

### Success (201)

```json
{
  "success": true,
  "data": {
    "userId": 123,
    "amount": 9.99,
    "major": "english",
    "courses": ["course_1", "course_2"],
    "meta": {
      "packagePlan": {
        "id": 1,
        "name": "VIP Monthly"
      }
    },
    "screenshot": "https://your-domain.com/uploads/...",
    "approve": false,
    "activated": false,
    "transactionId": "txn_1234567890",
    "date": "2026-04-10T09:00:00.000000Z"
  },
  "meta": "Payment submitted successfully. Please wait for approval."
}
```

### Typical errors

| HTTP | When |
| --- | --- |
| 401 | Missing/invalid token |
| 422 | Validation failed (missing fields, invalid image type, duplicate `transactionId`) |
| 500 | Unexpected server error while saving |
