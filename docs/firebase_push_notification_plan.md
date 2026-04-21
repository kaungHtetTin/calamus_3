# Firebase Push Notification Development Plan

This plan outlines the integration of Firebase Cloud Messaging (FCM) into the Calamus V3 server API for cross-platform push notifications (Android/iOS) with deep-linking support.

## **1. Prerequisites & Dependencies**

### **Installation**

We will use the `kreait/laravel-firebase` package, which is the standard Laravel wrapper for the Firebase PHP SDK.

```bash
composer require kreait/laravel-firebase
```

### **Configuration**

- The project already contains `important-firebase-key.json`.
- Configure `config/services.php` or `config/firebase.php` to point to this JSON file.
- Add `FIREBASE_CREDENTIALS` to the `.env` file.

---

## **2. Core Architecture**

### **A. Custom FCM Notification Channel**

We will create a custom notification channel `App\Notifications\Channels\FcmChannel`.

- **Purpose**: To handle the delivery logic for FCM.
- **Logic**:
    1. Determine the target user's `major` (app context).
    2. Fetch tokens from `user_data.token` JSON (e.g., `{"android": "...", "ios": "..."}`).
    3. Construct the FCM message with `notification` (visible text) and `data` (navigation payload).
    4. Send via Firebase SDK.

### **B. Payload Structure (for Navigation)**

The `data` object in the FCM payload will match your existing notification structure:

```json
{
    "notification": {
        "title": "John Doe liked your post",
        "body": "..."
    },
    "data": {
        "routeName": "PostDetail",
        "params": {
            "postId": "123"
        },
        "type": "post.like"
    }
}
```

---

## **3. Implementation Roadmap**

### **Phase 1: Existing Notifications (Social)**

Update the following notifications to support FCM:

- **Post Like**: `PostLikeNotification`
- **Comment Like**: `CommentLikeNotification`
- **Post Comment**: `PostCommentNotification`
- **Comment Reply**: `PostCommentReplyNotification`

**Tasks**:

- Add `fcm` to the `via()` method.
- Implement `toFcm($notifiable)` method in each class.

### **Phase 2: New Notifications**

Implement push notifications for the following sections:

1. **Chat Messaging**:
    - Update `ChatService` or add a listener for `MessageSent` events.
    - Send push when a new message is received.
2. **Friend Requests**:
    - Create `FriendRequestNotification`.
    - Trigger when a request is sent/accepted.
3. **Announcements**:
    - Create `SystemAnnouncementNotification`.
    - Send to all users or filtered by `major`.

### **Phase 3: Client-Side Integration (Guidance)**

- **Background Handling**: Ensure the app can handle `remoteMessage.data` when the app is in the background or quit.
- **Navigation**: Map `routeName` and `params` directly to your client-side router.

---

## **4. Targeting Logic (The "Major" Filter)**

Since your system is multi-tenant (different apps for different languages/majors), we will:

1. Identify the `major` of the resource (e.g., `Post->major`).
2. Fetch the `UserData` row for the target user that matches that `major`.
3. Use the tokens from that specific row to ensure the push reaches the correct app instance.

---

## **5. Testing Strategy**

1. **Unit Tests**: Mock the Firebase SDK to ensure the correct payload is generated.
2. **Integration Tests**: Use a real FCM token to verify delivery to a physical device/emulator.
3. **Queue Support**: Ensure notifications are sent via Laravel Queues to prevent API latency.

---

## **6. Future Improvements**

- **Rate Limiting**: Prevent notification fatigue (e.g., don't send 50 pushes for 50 likes in 1 minute).
- **User Preferences**: Allow users to toggle specific notification types (Likes, Comments, Chat) in their profile settings.
