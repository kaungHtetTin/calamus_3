# API Documentation - Part 3: Community & Social Features

This part covers friends, chat, discussions, and notifications.

## 1. Social Connections (`/friends`)

### **GET `/friends/get-friends`**

List user's friends.

- **Authentication:** Required
- **Request Parameters:** `page`, `limit` (optional, default 20).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "userName": "John Doe",
                "userImage": "https://...",
                "phone": "09123456789",
                "userId": "1677845612345"
            }
        ],
        "meta": {
            "current_page": 1,
            "last_page": 5,
            "next_page_token": "2",
            "total": 100,
            "limit": 20
        }
    }
    ```

### **GET `/friends/get-requests`**

List incoming friend requests.

- **Authentication:** Required
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "userName": "Jane Smith",
                "userImage": "https://...",
                "phone": "09987654321",
                "userId": "1677845612346",
                "requestId": 10,
                "createdAt": "2023-10-01 12:00:00"
            }
        ],
        "meta": {
            "current_page": 1,
            "last_page": 2,
            "next_page_token": "2",
            "total": 30,
            "limit": 20
        }
    }
    ```

### **POST `/friends/add`**

Send or unsend (if already sent) a friend request.

- **Authentication:** Required
- **Request Parameters:** `otherId` (string/ID, required).
- **Response Shape:** `{ "success": true, "data": { "action": "requested" } }` (or "unsent request")

### **POST `/friends/confirm`**

Accept a friend request.

- **Authentication:** Required
- **Request Parameters:** `otherId` (string/ID, required).
- **Response Shape:** `{ "success": true, "data": { "action": "accepted" } }`

### **POST `/friends/unfriend`**

Remove a friend.

- **Authentication:** Required
- **Request Parameters:** `otherId` (string/ID, required).
- **Response Shape:** `{ "success": true, "data": { "action": "unfriend" } }`

### **GET `/friends/get-status`**

Check friendship status with another user.

- **Authentication:** Required
- **Request Parameters:** `otherId` (string/ID, required).
- **Response Shape:** `{ "success": true, "data": { "status": "friend" } }` (none, pending_sent, pending_received, friend)

### **POST `/friends/block`** / **unblock**

Block or unblock a user.

- **Authentication:** Required
- **Request Parameters:** `otherId` (string/ID, required).
- **Response Shape:** `{ "success": true, "data": { "action": "blocked" } }` (or "unblocked")

## 2. Communication (`/chat`)

> **Note on `major` parameter:** The chat system is global across all apps. The `major` parameter (e.g. `english`, `korean`) may be recorded when sending a message to distinguish which app sent it, but it is **not** used to filter or select conversations/messages. Users see all their conversations regardless of which app they are using.

### **GET `/chat/messages`**

Get message history for a conversation.

- **Authentication:** Required
- **Request Parameters:**
    - `conversation_id` (integer, required)
    - `limit` (optional, default 50)
    - `oldest_message_id` (optional, for pagination)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 100,
                "conversation_id": 5,
                "sender_id": "1677845612345",
                "message_text": "Hello!",
                "message_type": "text",
                "file_path": null,
                "file_size": 0,
                "is_read": 1,
                "created_at": "2023-10-01T12:00:00.000000Z",
                "updated_at": "2023-10-01T12:00:00.000000Z"
            }
        ]
    }
    ```

### **POST `/chat/messages`**

Send a message.

- **Authentication:** Required
- **Request Parameters:**
    - `conversation_id` (integer, required)
    - `message_text` (string, required if not file)
    - `message_type` (string, required): 'text', 'image', 'file'.
- **Response Shape:** The created message object (same as above).

### **GET `/chat/conversations`**

List user's active conversations.

- **Authentication:** Required
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 5,
                "user1_id": "1677845612345",
                "user2_id": "1677845612346",
                "last_message_at": "...",
                "unread_count": 2,
                "last_message_text": "Hello!",
                "last_message_type": "text",
                "friend": {
                    "name": "Jane Smith",
                    "image": "https://...",
                    "blocked": false
                }
            }
        ]
    }
    ```

## 3. Discussions & Posts (`/discussions`, `/posts`, `/comments`)

### **GET `/discussions/get`**

List discussion posts with full interaction details.

> Note: Lesson-linked legacy posts are excluded. Discussions now return only actual community posts.

- **Request Parameters:**
    - `category` (string, optional): Language/Category context (e.g., 'english').
    - `page` (integer, optional): Current page.
    - `limit` (integer, optional): Items per page.
    - `userId` (string, optional): Current user ID to determine `isLiked` status.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "postId": 1677845612345,
                "body": "Post content text here...",
                "postImage": "https://www.calamuseducation.com/uploads/posts/image.png",
                "hidden": 0,
                "hasVideo": 1,
                "vimeo": "12345678",
                "postLikes": 25,
                "comments": 12,
                "shareCount": 5,
                "viewCount": 200,
                "isLiked": 1,
                "showOnBlog": 0,
                "blogTitle": "",
                "category": "english",
                "userId": "1677845612345",
                "userName": "John Doe",
                "userImage": "https://www.calamuseducation.com/uploads/placeholder.png",
                "vip": 0
            }
        ],
        "meta": {
            "current_page": 1,
            "last_page": 10,
            "next_page_token": "2",
            "total": 150,
            "limit": 15
        }
    }
    ```

### **GET `/discussions/detail`**

Get full detail for a single discussion post.

- **Request Parameters:**
    - `postId` (integer, required): ID of the post.
    - `userId` (string, optional): Current user ID to determine `isLiked` status.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "postId": 1677845612345,
            "body": "Post content text here...",
            "postImage": "https://www.calamuseducation.com/uploads/posts/image.png",
            "hidden": 0,
            "hasVideo": 1,
            "vimeo": "12345678",
            "postLikes": 25,
            "comments": 12,
            "shareCount": 5,
            "viewCount": 200,
            "isLiked": 1,
            "showOnBlog": 0,
            "blogTitle": "",
            "category": "english",
            "userId": "1677845612345",
            "userName": "John Doe",
            "userImage": "https://www.calamuseducation.com/uploads/placeholder.png",
            "vip": 0
        }
    }
    ```

### **GET `/discussions/likes`**

Get users who liked a specific discussion post.

- **Request Parameters:**
    - `postId` (integer, required)
    - `page` (integer, optional, default 1)
    - `limit` (integer, optional, default 20, max 100)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "userId": "1677845612345",
                "userName": "John Doe",
                "userImage": "https://www.calamuseducation.com/uploads/placeholder.png"
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 2,
            "nextPageToken": "2",
            "total": 25,
            "limit": 20
        }
    }
    ```

### **POST `/discussions/update`**

Edit an existing discussion post.

- **Authentication:** Required
- **Request Body:**
    - `postId` (integer, required): Post ID to edit.
    - `body` (string, optional): Updated post text.
    - `category` (string, optional): Updated category/major (e.g., `english`).
    - `image` (string, optional): New base64 image data URI.
    - `removeImage` (boolean, optional): Set `true` to remove current image.
    - At least one editable field is required (`body`, `category`, `image`, `removeImage`).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "postId": 1677845612345,
            "body": "Updated post text",
            "postImage": "https://www.calamuseducation.com/uploads/posts/1677845612345_1677000000000.jpg",
            "category": "english"
        }
    }
    ```

### **GET `/posts/pinned`**

Get featured/pinned posts for the blog section.

- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "postId": 1677845612345,
                "body": "Pinned post content...",
                "postImage": "https://...",
                "blogTitle": "Interesting Article Title",
                "major": "english",
                "userId": "1677845612345",
                "userName": "Teacher Name",
                "userImage": "https://..."
            }
        ],
        "meta": {
            "total": 6
        }
    }
    ```

### **GET `/comments/get`**

Get comments for a post or lesson target.

- **Request Parameters:**
    - `postId` (integer, optional): Target post ID.
    - `lessonId` (integer, optional): Target lesson ID.
    - `targetType` (string, optional): `post` or `lesson` (use with `targetId`).
    - `targetId` (integer, optional): Target ID (use with `targetType`).
    - `page` (integer, optional, default 1)
    - `limit` (integer, optional, default 20)
    - You must provide either `postId`, `lessonId`, or (`targetType` + `targetId`).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "postId": 1001,
                "lessonId": null,
                "targetType": "post",
                "targetId": 1001,
                "writerId": "...",
                "writerName": "...",
                "writerImage": "...",
                "time": 1677845612345,
                "parent": 0,
                "likes": 5,
                "body": "Comment text",
                "image": "...",
                "isLiked": 1,
                "child": [
                    {
                        "id": 2,
                        "postId": 1001,
                        "lessonId": null,
                        "targetType": "post",
                        "targetId": 1001,
                        "writerId": "...",
                        "writerName": "...",
                        "writerImage": "...",
                        "time": 1677845612346,
                        "parent": 1677845612345,
                        "likes": 0,
                        "body": "Reply text",
                        "image": "...",
                        "isLiked": 0
                    }
                ]
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 5,
            "total": 100,
            "limit": 20,
            "totalComments": 150
        }
    }
    ```

### **POST `/comments/create`**

Create a new comment or reply.

- **Authentication:** Required
- **Request Body:**
    - `postId` (integer, optional): Target post ID.
    - `lessonId` (integer, optional): Target lesson ID.
    - `targetType` (string, optional): `post` or `lesson` (use with `targetId`).
    - `targetId` (integer, optional): Target ID (use with `targetType`).
    - `body` (string, optional): Comment text.
    - `image` (string, optional): Base64 data URI image (`data:image/...;base64,...`).
    - `parent` (integer, optional): The `time` of the parent comment if it's a reply.
    - Target is required, and at least one of `body` or `image` is required.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "comment": {
                "id": 50,
                "postId": 1001,
                "lessonId": null,
                "targetType": "post",
                "targetId": 1001,
                "writerId": "...",
                "writerName": "...",
                "writerImage": "...",
                "body": "Comment text",
                "image": "https://www.calamuseducation.com/uploads/comments/1677845612347_1677000000000.jpg",
                "time": 1677845612347,
                "parent": 0,
                "likes": 0,
                "isLiked": 0,
                "child": []
            }
        }
    }
    ```

### **POST `/comments/like`**

Toggle like/unlike for a comment.

- **Authentication:** Required
- **Request Body:**
    - `commentId` (integer, required): The `time` field of the comment.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "isLiked": true,
            "likesCount": 6
        }
    }
    ```

### **GET `/comments/likes`**

Get users who liked a specific comment.

- **Request Parameters:**
    - `commentId` (integer, required): The `time` field of the comment.
    - `page` (integer, optional, default 1)
    - `limit` (integer, optional, default 20, max 100)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "userId": "1677845612345",
                "userName": "John Doe",
                "userImage": "https://www.calamuseducation.com/uploads/placeholder.png"
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 1,
            "nextPageToken": null,
            "total": 3,
            "limit": 20
        }
    }
    ```

### **POST `/comments/delete`**

Delete a comment.

- **Authentication:** Required
- **Request Body:**
    - `commentId` (integer, required): The `time` field of the comment.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true
        }
    }
    ```

### **POST `/comments/update`**

Update a comment body.

- **Authentication:** Required
- **Request Body:**
    - `commentId` (integer, required): The `time` field of the comment.
    - `body` (string, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true
        }
    }
    ```

## 4. Notifications (`/notifications`)

### **GET `/notifications/get`**

Get user notification list.

- **Authentication:** Required
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 500,
                "title": "New Like",
                "body": "Jane liked your post",
                "image": "...",
                "type": "like",
                "dataId": "1001",
                "seen": 0,
                "time": "1 hour ago"
            }
        ],
        "meta": {
            "current_page": 1,
            "unreadCount": 5,
            ...
        }
    }
    ```
