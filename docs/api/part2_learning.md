# API Documentation - Part 2: Learning System & Courses

This part covers courses, lessons, and video content.

## 1. Courses (`/courses`)

### **GET `/courses/`**

List all courses.

- **Request Parameters:**
    - `major` (string, optional): Filter by category (e.g., 'english', 'korea').
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "title": "Basic English",
                "description": "...",
                "duration": 3600,
                "rating": 4.5,
                "coverUrl": "https://...",
                "webCover": "https://...",
                "backgroundColor": "#ffffff",
                "fee": 15000,
                "major": "english",
                "lessonsCount": 20,
                "instructor": "Teacher Name",
                "instructorId": 10,
                "instructorImage": "https://...",
                "enrolledStudents": 1200
            }
        ],
        "meta": {
            "total": 25
        }
    }
    ```

### **GET `/courses/detail`**

Get detailed information for a specific course.

- **Request Parameters:**
    - `id` (integer, required): Course ID.
- **Authentication:** Optional (provide for progress tracking).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "id": 1,
            "title": "Course Title",
            "description": "...",
            "duration": 3600,
            "totalDuration": 15000,
            "rating": 4.8,
            "coverUrl": "https://...",
            "webCover": "...",
            "backgroundColor": "#ffffff",
            "fee": 15000,
            "major": "english",
            "lessonsCount": 20,
            "instructor": "Teacher Name",
            "instructorId": 10,
            "instructorImage": "...",
            "enrolledStudents": 1200,
            "hasVipAccess": true,
            "progress": 45,
            "learnedCount": 9
        }
    }
    ```

### **GET `/courses/curriculum`**

Get the curriculum for a course, organized by days.

- **Request Parameters:**
    - `courseId` (integer, required)
- **Authentication:** Optional (provide for `learned` status).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "courseId": 1,
            "curriculum": [
                [
                    {
                        "id": 101,
                        "title": "Lesson Title",
                        "duration": 600,
                        "isVip": 0,
                        "isVideo": 1,
                        "thumbnail": "https://...",
                        "categoryTitle": "Grammar",
                        "learned": 1,
                        "hasAccess": true
                    }
                ],
                [...]
            ]
        }
    }
    ```

### **GET `/courses/featured`** & **GET `/courses/new`**

Returns top 5 courses by rating or latest respectively. Response shape same as `/courses/`.

### **GET `/courses/get-certificate`**

Get or generate a completion certificate.

- **Authentication:** Required
- **Request Parameters:**
    - `courseId` (integer, required)
    - `userId` (string, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "name": "User Name",
            "course": "Basic English",
            "major": "english",
            "date": "2023-10-01",
            "ref": "ENG-00123",
            "url": "https://.../view.php?id=...",
            "download": "https://.../download.php?id=...",
            "platform": "English for Myanmar",
            "seal": "https://..."
        }
    }
    ```

## 2. Lessons (`/lessons`)

### **GET `/lessons/detail`**

Get details for a specific lesson (video or document).

- **Request Parameters:**
    - `id` (integer, required): Lesson ID.
- **Authentication:** Required for VIP content.
- **Link logic:**
    - If `isVideo = 1` -> `link` is from `lessons.link`
    - If `isVideo = 0` -> `link` is from `lessons.document_link`
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "lesson": {
                "id": 101,
                "title": "Lesson Title",
                "description": "...",
                "isVideo": 1,
                "isVip": 0,
                "duration": 600,
                "link": "https://player.vimeo.com/video/12345678",
                "vimeo": "12345678",
                "documentUrl": null,
                "viewCount": 500,
                "likeCount": 20,
                "isLiked": 0,
                "comments": 10,
                "shareCount": 2,
                "thumbnail": "https://...",
                "learned": 0,
                "hasAccess": true
            }
        }
    }
    ```

### **GET `/lessons/download`**

Get lesson download URL as a separate request.

- **Request Parameters:**
    - `id` (integer, required): Lesson ID.
- **Authentication:** required.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "lessonId": 101,
            "title": "Lesson Title",
            "downloadUrl": "https://.../video/file.mp4",
            "hasAccess": true
        }
    }
    ```

### **POST `/lessons/mark-learned`**

Mark a lesson as completed.

- **Authentication:** Required
- **Request Parameters:**
    - `lessonId` (integer, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "message": "Lesson marked as learned"
        }
    }
    ```

## 3. Additional Content

### **GET `/additional-lessons/courses`**

- **Request Parameters:** `channel` (string, default 'english').
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "course_id": 14,
                "title": "Translation Course",
                "categories": [
                    {
                        "id": 50,
                        "course_id": 14,
                        "category_title": "Unit 1",
                        "image_url": "...",
                        "sort_order": 1,
                        "major": "english"
                    }
                ]
            }
        ],
        "meta": { "channel": "english", "total": 3 }
    }
    ```

### **GET `/additional-lessons/lessons`**

- **Request Parameters:** `categoryId` (integer, required), `userId` (optional).
- **Response update:** `link` is no longer returned in lesson items.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "category": {
                "id": 50,
                "courseId": 14,
                "categoryTitle": "Unit 1",
                "imageUrl": "https://...",
                "major": "english"
            },
            "lessons": [
                {
                    "id": 101,
                    "title": "Lesson Title",
                    "titleMini": "Mini title",
                    "cate": "",
                    "isVideo": 1,
                    "isVip": 0,
                    "date": 1677845612345,
                    "thumbnail": "https://...",
                    "imageUrl": "https://...",
                    "duration": 600,
                    "hasAccess": true
                }
            ]
        },
        "meta": {
            "totalLessons": 1
        }
    }
    ```

### **GET `/video-channel/get`**

- **Request Parameters:** `channel` (string), `app` (integer appId).
- **Authentication:** Optional (Bearer token). If provided, each lesson includes `isLearned`.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "app": { "id": 1, "name": "...", "icon": "..." },
            "categories": [
                {
                    "id": 10,
                    "title": "Category Title",
                    "lessonsCount": 5,
                    "lessons": [
                        {
                            "id": 101,
                            "title": "Video Title",
                            "viewCount": 500,
                            "likeCount": 20,
                            "commentCount": 5,
                            "shareCount": 2,
                            "isLearned": 1
                        }
                    ]
                }
            ]
        },
        "meta": { "totalCategories": 1 }
    }
    ```

### **GET `/video-channel/video`**

- **Request Parameters:**
    - `id` (integer, required): Lesson ID.
- **Authentication:** Optional (Bearer token). If provided, response includes `isLearned` for the authenticated user.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "currentVideo": {
                "id": 101,
                "title": "Video Title",
                "duration": 600,
                "formattedDuration": "10:00",
                "thumbnail": "...",
                "viewCount": 500,
                "formattedViewCount": "500 views",
                "likeCount": 20,
                "isLiked": 1,
                "isLearned": 1,
                "commentCount": 5,
                "shareCount": 2,
                "vimeoId": "...",
                "category": "..."
            },
            "prevVideo": { ... },
            "nextVideo": { ... },
            "relatedVideos": [ ... ]
        }
    }
    ```
