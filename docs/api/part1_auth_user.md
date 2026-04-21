# API Documentation - Part 1: Authentication & User Management

This part covers authentication, registration, and user profile management.

## 1. Authentication (`/auth`)

### **POST `/auth/login`**

Authenticate a user using phone or email.

- **Request Parameters:**
    - `phone` (string, optional): User's phone number.
    - `email` (string, optional): User's email address.
    - `password` (string, required): User's password.
    - `major` (string, optional): Current language/category context (e.g., 'english').
    - `deviceType` (string, optional): 'mobile', 'tablet', 'ipad'. Default is 'mobile'.
    - `fcmToken` (string, optional): Firebase Cloud Messaging token. If provided, it is saved to `user_data.token`.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "token": "123|abcdefg...",
            "user": {
                "id": "1677845612345",
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "09123456789",
                "image": "https://www.calamuseducation.com/uploads/placeholder.png"
            }
        }
    }
    ```

### **POST `/auth/register`**

Register a new user.

- **Request Parameters:**
    - `name` (string, required): Full name.
    - `phone` (string, optional): Phone number.
    - `email` (string, optional): Email address.
    - `password` (string, required): Minimum 6 characters.
    - `fcmToken` (string, optional): Firebase Cloud Messaging token. If provided, it is saved to `user_data.token`.
- **Response Shape:** Same as Login.

### **POST `/auth/check-account`**

Check whether an email or phone is already registered.

- **Request Parameters (JSON):**
    - `email` (string, optional): Email to check.
    - `phone` (string, optional): Phone number to check.
    - At least one of `email` or `phone` is required.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "email": {
                "value": "john@example.com",
                "isRegistered": true,
                "isAvailable": false
            },
            "phone": {
                "value": "09123456789",
                "isRegistered": false,
                "isAvailable": true
            }
        }
    }
    ```

### **POST `/auth/logout`**

Log out the current user and revoke the token.

- **Authentication:** Required (Bearer Token)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [],
        "meta": {
            "status": "success"
        }
    }
    ```

### **GET `/auth/me`**

Get currently authenticated user details.

- **Authentication:** Required (Bearer Token)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "user": {
                "id": "1677845612345",
                "name": "John Doe",
                "email": "john@example.com",
                "phone": "09123456789",
                "image": "https://www.calamuseducation.com/uploads/placeholder.png"
            }
        }
    }
    ```

## 2. User Profile (`/users`)

### **GET `/users/profile`**

Get public profile of a user.

- **Request Parameters:**
    - `id` (string, required): User ID.
    - `page` (integer, optional): Pagination for user's posts.
    - `tab` (string, optional): 'posts' (default) or 'shared'.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "id": 123,
            "userId": "1677845612345",
            "name": "John Doe",
            "email": "john@example.com",
            "gender": "male",
            "birthday": {
                "day": 13,
                "month": 8,
                "year": 2001
            },
            "image": "https://...",
            "coverImage": "https://...",
            "bio": "User biography text",
            "work": "Developer at Company",
            "education": "Computer Science",
            "region": "Yangon",
            "posts": [
                {
                    "postId": 1677845612345,
                    "body": "Post content...",
                    "postImage": "https://...",
                    "userId": "1677845612345",
                    "userName": "John Doe",
                    "userImage": "https://...",
                    "postLikes": 10,
                    "comments": 5,
                    "shareCount": 2,
                    "viewCount": 100
                }
            ],
            "totalPosts": 50,
            "page": 1
        },
        "meta": {
            "currentPage": 1,
            "lastPage": 5,
            "nextPageToken": "2",
            "total": 50,
            "limit": 10
        }
    }
    ```

### **GET `/users/my-learning`**

Get learning progress for the authenticated user.

- **Authentication:** Required
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "myCourses": [
                {
                    "id": 1,
                    "title": "Course Title",
                    "description": "...",
                    "duration": 3600,
                    "rating": 4.8,
                    "coverUrl": "...",
                    "webCover": "...",
                    "backgroundColor": "#ffffff",
                    "fee": 15000,
                    "major": "english",
                    "lessonsCount": 20,
                    "instructor": "Teacher Name",
                    "instructorImage": "...",
                    "enrolledStudents": 150,
                    "isPurchased": true,
                    "progress": 45,
                    "learnedCount": 9
                }
            ],
            "freeCourses": [...]
        },
        "meta": {
            "totalMyCourses": 5,
            "totalFreeCourses": 10
        }
    }
    ```

### **POST `/users/update`**

Update user profile information.

- **Authentication:** Required
- **Request Parameters (Multipart/Form-Data or JSON):**
    - `name` (string, required): Full name.
    - `bio` (string, optional): User bio.
    - `work` (string, optional): Work info.
    - `education` (string, optional): Education info.
    - `region` (string, optional): Region info.
    - `gender` (string, optional): `male`, `female`, or `other`.
    - `birthday` (object, optional): `{ "day": 1-31, "month": 1-12, "year": 1900-2100 }`.
    - `birthdayDay` (integer, optional): Legacy flat birthday day.
    - `birthdayMonth` (integer, optional): Legacy flat birthday month.
    - `birthdayYear` (integer, optional): Legacy flat birthday year.
    - `profileImage` (file, optional): Image file (max 5MB).
    - `coverImage` (file, optional): Image file (max 5MB).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "message": "Profile updated successfully",
            "user": {
                "id": 123,
                "name": "John Doe",
                "image": "https://...",
                "coverImage": "https://...",
                "bio": "Updated bio...",
                "gender": "male",
                "birthday": {
                    "day": 13,
                    "month": 8,
                    "year": 2001
                }
            }
        }
    }
    ```

### **POST `/users/change-password`**

Change user password.

- **Authentication:** Required
- **Request Parameters:**
    - `currentPassword` (string, required)
    - `newPassword` (string, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "message": "Password changed successfully"
        }
    }
    ```

### **POST `/users/delete-account`**

Permanently delete user account.

- **Authentication:** Required
- **Request Parameters:**
    - `password` (string, required)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "message": "Account deleted successfully"
        }
    }
    ```
