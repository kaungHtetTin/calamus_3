# Social Login API (Google + Facebook)

This project supports social login using Google and Facebook via Laravel Socialite. Social login returns a Sanctum token and a learner profile.

## Setup

### 1) Run migrations

```bash
php artisan migrate
```

This creates the `social_accounts` table used to link a learner to a provider account.

### 2) Configure OAuth credentials

Add these to `.env`:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=...

FACEBOOK_CLIENT_ID=...
FACEBOOK_CLIENT_SECRET=...
FACEBOOK_REDIRECT_URI=...
```

The callback routes are:

- Google callback: `/api/auth/social/google/callback`
- Facebook callback: `/api/auth/social/facebook/callback`

If your app is served from a `/public` path (common in XAMPP), include it in the redirect URI you register with Google/Meta.

After updating `.env`:

```bash
php artisan config:clear
```

## Providers

Supported `provider` values:

- `google`
- `facebook`

## Endpoints

All endpoints are under the API prefix.

### 1) Redirect to provider (Web flow)

`GET /api/auth/social/{provider}/redirect`

Redirects the user agent to the provider authorization page.

Example:

```bash
curl -i "http://localhost/calamus-v3/public/api/auth/social/google/redirect"
```

### 2) Provider callback (Web flow)

`GET /api/auth/social/{provider}/callback`

The provider redirects back to this URL after login/consent. Returns JSON with a Sanctum token.

Optional query parameters (forwarded into the login result):

- `major` (string)
- `deviceType` (string; default `mobile`)
- `platform` (string; `ios|android|andorid`)
- `fcmToken` (string)

Example:

```text
GET /api/auth/social/google/callback?major=english&deviceType=mobile&platform=android&fcmToken=XYZ
```

Response (200):

```json
{
  "success": true,
  "data": {
    "token": "1|<sanctum_token>",
    "user": {
      "id": 123,
      "name": "User",
      "email": "user@example.com",
      "phone": 0,
      "image": "https://...",
      "emailVerified": false
    }
  }
}
```

### 3) Login using provider access token (Mobile flow)

`POST /api/auth/social/{provider}/token`

Use this when your mobile app already obtained the provider access token (Google/Facebook SDK) and you want your backend to issue a Sanctum token.

Body:

- `accessToken` (string, required)
- `major` (string, optional)
- `deviceType` (string, optional; default `mobile`)
- `platform` (string, optional; `ios|android|andorid`)
- `fcmToken` (string, optional)

Example:

```bash
curl -X POST "http://localhost/calamus-v3/public/api/auth/social/google/token" ^
  -H "Content-Type: application/json" ^
  -d "{\"accessToken\":\"<provider_access_token>\",\"major\":\"english\",\"platform\":\"android\"}"
```

Response (200): same structure as the callback endpoint.

## Behavior

On successful social login:

- The backend looks up `social_accounts` by `(provider, provider_user_id)`.
- If not found, it may link by email if the provider returns an email.
- If still not found, it creates a new learner in `learners` and then creates the social account link.
- A Sanctum token is created and returned as `data.token`.

## Error Responses

Typical errors:

- `400 Unsupported provider.`
- `400 Social login is not configured for this provider.`
- `400 Unable to authenticate with provider.`
- `400 Unable to authenticate with provider token.`
- `500 Social login is not available. Please run migrations.`

## Notes

- Make sure the redirect URI configured in Google/Meta matches your callback URL exactly.
- Keep OAuth secrets in `.env` only; never commit them.

