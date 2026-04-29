# FAQ API (Global)

This project exposes a public API endpoint for fetching **global FAQs** (not separated by major/language).

## Endpoint

### Get FAQs

`GET /api/faqs/get`

Returns the list of FAQs that should be shown in the client app.

**Auth:** Not required  
**Query params:** None

## Response

Response (200):

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "question": "How do I reset my password?",
            "answer": "Go to Settings → Account → Reset Password.",
            "sortOrder": 0,
            "active": true
        }
    ]
}
```

### Fields

- `id` (number) – FAQ id
- `question` (string)
- `answer` (string)
- `sortOrder` (number) – sorting priority (ascending)
- `active` (boolean) – included when the `faqs.active` column exists (the API returns only active FAQs)

## Ordering

FAQs are ordered by:

1. `sort_order` ascending
2. `id` ascending

## Examples

```bash
curl "http://localhost/calamus-v3/public/api/faqs/get"
```

## Notes

- This is a **global** FAQ list. There is no `major` parameter and no `major` field in the response.
- If the `faqs` table does not exist, the API returns an empty list with `success: true`.
