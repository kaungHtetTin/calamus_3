# Banners API

## Get First Banner by Major Scope

Returns the latest banner (by `id DESC`) for a given major scope. Only one banner is returned.

**Endpoint**

`GET /api/banners/first`

**Query Params**

- `major` (string, optional)  
  Single major code (example: `english`).
- `major_scope` (string, optional)  
  Comma or whitespace separated list of major codes (example: `english,korea`).
- `majorScope` (string, optional)  
  Same as `major_scope` (camelCase alias).

At least one of `major`, `major_scope`, `majorScope` must be provided.

**Example**

`GET /api/banners/first?major=english`

`GET /api/banners/first?major_scope=english,korea`

**Success Response (200)**

```json
{
  "success": true,
  "data": {
    "banner": {
      "id": 1,
      "title": "Welcome",
      "major": "english",
      "imageUrl": "http://localhost/uploads/banners/xxxx.jpg",
      "link": "https://example.com"
    }
  }
}
```

If no banner exists for the given scope:

```json
{
  "success": true,
  "data": {
    "banner": null
  }
}
```

**Validation Error (422)**

```json
{
  "success": false,
  "error": "major is required"
}
```

