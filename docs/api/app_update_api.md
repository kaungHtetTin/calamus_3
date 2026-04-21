# App Update Check API

This endpoint allows client applications (Android/iOS) to check for updates and determine if a mandatory (forced) update is required.

## Check Update

**Endpoint:** `GET /api/apps/check-update`

**Authentication:** Not Required

### Request Parameters

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `packageId` | `string` | Yes | The package identifier of the app (e.g., `com.calamus.easykorean`). |
| `platform` | `string` | Yes | The platform of the app: `android` or `ios`. |
| `versionCode` | `integer` | Yes | The current version code installed on the client. |

### Response Format

The API returns a JSON object with the following structure:

#### Success Response (200 OK)

```json
{
    "success": true,
    "data": {
        "updateAvailable": true,
        "forceUpdate": false,
        "latestVersionCode": 20,
        "latestVersionName": "2.0.0",
        "updateUrl": "https://play.google.com/store/apps/details?id=com.calamus.easykorean",
        "updateMessage": "New features added! We have improved the lesson player and added new vocabularies."
    }
}
```

#### Field Descriptions

- **`updateAvailable`**: (`boolean`) True if a newer version exists on the server.
- **`forceUpdate`**: (`boolean`) True if the current client version is below the minimum required version or the server has explicitly flagged a forced update.
- **`latestVersionCode`**: (`integer`) The latest version code available on the server.
- **`latestVersionName`**: (`string`) The display name of the latest version.
- **`updateUrl`**: (`string`) The store link or direct download URL for the update.
- **`updateMessage`**: (`string`) A custom message to display to the user in the update dialog.

#### Error Response (404 Not Found)

If the specified `packageId` or `platform` is not found in the database.

```json
{
    "success": false,
    "message": "App not found for the specified package ID and platform"
}
```

#### Error Response (422 Unprocessable Entity)

If required parameters are missing or invalid.

```json
{
    "message": "The package id field is required.",
    "errors": {
        "packageId": ["The package id field is required."]
    }
}
```

---

### Client Implementation Logic (Recommended)

1.  **On App Start**: Call `GET /api/apps/check-update`.
2.  **Check `updateAvailable`**:
    -   If `false`: Continue to app.
    -   If `true`:
        -   **Check `forceUpdate`**:
            -   If `true`: Show a modal dialog that **cannot be dismissed**. The only option should be "Update Now" which redirects to `updateUrl`.
            -   If `false`: Show a modal dialog with "Update Now" and "Later" buttons. Use `updateMessage` to inform the user about the update.
