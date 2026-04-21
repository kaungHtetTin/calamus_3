# API Documentation - Part 5: Platform & System

This part covers platform-level features, stats, and ratings.

## 1. Platform & Apps (`/apps`, `/about`, `/vip-plan`)

### **GET `/apps/get`**
Get list of platform apps/tools.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "Translation Helper",
                "icon": "https://...",
                "url": "https://...",
                "description": "..."
            }
        ],
        "meta": { "total": 1 }
    }
    ```

### **GET `/about/stats`**
Get general platform statistics for "About Us".
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "instructors": 15,
            "courses": 30,
            "lectures": 500,
            "enrollments": 10000,
            "languages": 2,
            "members": 25000
        }
    }
    ```

### **GET `/vip-plan/get`**
Get available VIP subscription plans and pricing.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "languages": [
                {
                    "id": "english",
                    "name": "English Language",
                    "icon": "🇬🇧",
                    "courses": [
                        {
                            "name": "Basic Course",
                            "price": 10000,
                            "priceLabel": "10,000 kyats",
                            "blueMark": true,
                            "remark": "",
                            "isFree": false
                        }
                    ],
                    "bundlePlans": [
                        {
                            "name": "Diamond Plan",
                            "price": 30000,
                            "priceLabel": "30,000 kyats",
                            "tier": "diamond",
                            "savings": "Save 25,000 kyats"
                        }
                    ]
                }
            ]
        }
    }
    ```

## 2. Statistics & Home (`/stats`)

### **GET `/stats/home`**
Get statistics for the home dashboard.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "totalCourses": 30,
            "totalLessons": 500,
            "videoLessons": 300,
            "documentLessons": 200,
            "totalInstructors": 15,
            "totalStudents": 10000,
            "avgRating": 4.8,
            "ratingCount": 1200,
            "topInstructors": [...]
        }
    }
    ```

## 3. Ratings & Reviews (`/ratings`)

### **GET `/ratings/`**
List reviews for a specific course.
- **Request Parameters:**
    - `courseId` (integer, required)
    - `page` (integer, optional)
    - `limit` (integer, optional)
    - `sort` (string, optional): 'recent', 'highest', 'lowest'.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 101,
                "courseId": 1,
                "learnerName": "John Doe",
                "learnerImage": "https://...",
                "star": 5,
                "review": "Great course!",
                "time": 1677845612345,
                "formattedTime": "2s ago"
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 10,
            "total": 100,
            "limit": 10
        }
    }
    ```

### **GET `/ratings/breakdown`**
Get star rating breakdown for a course.
- **Request Parameters:** `courseId` (integer, required).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "courseId": 1,
            "averageRating": 4.8,
            "totalRatings": 100,
            "breakdown": [
                { "star": 5, "count": 80, "percentage": 80.0 },
                { "star": 4, "count": 15, "percentage": 15.0 },
                ...
            ]
        }
    }
    ```

### **POST `/ratings/store`**
Submit or update a course rating.
- **Authentication:** Required
- **Request Body:**
    - `courseId` (integer, required)
    - `star` (integer, required, 1-5)
    - `review` (string, optional)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "message": "Rating saved successfully",
            "data": {
                "id": 101,
                "star": 5,
                "review": "...",
                ...
            }
        }
    }
    ```

### **POST `/ratings/delete`**
Delete a rating by ID (body-based endpoint).
- **Authentication:** Required
- **Request Body:**
    - `id` (integer, required) or `ratingId` (integer, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "message": "Rating deleted successfully"
        }
    }
    ```

### **DELETE `/ratings/{id}`**
Delete a rating by path parameter.
- **Authentication:** Required
- **Response Shape:** Same as `/ratings/delete`.

### **GET `/ratings/latest`**
- **Request Parameters:** `limit` (integer, optional, default 6).
- **Response Shape:** Same as `/ratings/` list but across all courses.

## 4. Universal Search (`/search`)

### **GET `/search/universal`**
Universal search for users, songs, and discussions.
- **Authentication:** Optional (Sanctum)
- **Request Parameters:**
    - `q` (string, required): Search query (email, phone, name, song title, artist, or discussion content).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "users": [
                {
                    "id": "123456789",
                    "name": "John Doe",
                    "email": "john@example.com",
                    "phone": "0912345678",
                    "image": "https://...",
                    "type": "user"
                }
            ],
            "songs": [
                {
                    "id": 1,
                    "title": "Song Title",
                    "artist": "Artist Name",
                    "audioUrl": "https://...",
                    "imageUrl": "https://...",
                    "likeCount": 100,
                    "liked": true,
                    "type": "song"
                }
            ],
            "discussions": [
                {
                    "postId": 1677845612345,
                    "body": "Discussion body...",
                    "postImage": "https://...",
                    "hasVideo": 1,
                    "vimeo": "123456789",
                    "postLikes": 10,
                    "comments": 5,
                    "userName": "Jane Doe",
                    "userImage": "https://...",
                    "isLiked": 1,
                    "type": "discussion"
                }
            ]
        }
    }
    ```
- **Notes:**
    - If authenticated, `liked` (songs) and `isLiked` (discussions) will reflect the current user's status.
    - Discussion results respect user blocks and hidden posts filters.
    - Results are limited to 10 items per category.
