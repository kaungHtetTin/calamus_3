# API Documentation - Part 4: Educational Resources

This part covers supplemental learning resources like songs, library, and vocabulary.

## 1. Music & Songs (`/songs`)

### **GET `/songs/get`**

List songs, artists, and popular tracks.

- **Request Parameters:**
    - `category` (string, optional, default 'english').
    - `page` (integer, optional, default 1).
    - `limit` (integer, optional, default 15).
    - `userId` (string, optional, for `liked` status).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "popularSongs": [
                {
                    "id": 501,
                    "songId": 501,
                    "artistId": 1,
                    "title": "Song Title",
                    "artist": "Artist Name",
                    "url": "song_slug",
                    "likeCount": 150,
                    "downloadCount": 300,
                    "audioUrl": "https://.../audio/song_slug.mp3",
                    "imageUrl": "https://.../web/song_slug.png",
                    "thumbnailUrl": "https://.../image/song_slug.png",
                    "lyricsUrl": "https://.../lyrics/song_slug.txt",
                    "liked": true
                }
            ],
            "songs": [...],
            "artists": [
                {
                    "id": 1,
                    "name": "Artist Name",
                    "imageUrl": "https://.../web/artist_image_slug.png"
                }
            ]
        },
        "meta": {
            "current_page": 1,
            "last_page": 10,
            "next_page_token": "2",
            "total": 150,
            "limit": 15,
            "category": "english"
        }
    }
    ```

### **GET `/songs/search`**

Search songs by song name and/or artist name.

- **Request Parameters:**
    - `q` (string, optional): Search term matching both title and artist.
    - `title` (string, optional): Filter by song title (partial match).
    - `artist` (string, optional): Filter by artist name (partial match).
    - At least one of `q`, `title`, or `artist` is required.
    - `category` (string, optional, default 'english'): Song category filter.
    - `page` (integer, optional, default 1).
    - `limit` (integer, optional, default 20, max 50).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 501,
                "songId": 501,
                "artistId": 1,
                "title": "Song Title",
                "artist": "Artist Name",
                "audioUrl": "https://.../audio/slug.mp3",
                "imageUrl": "https://.../web/slug.png",
                "likeCount": 150,
                "downloadCount": 300,
                "liked": true
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 3,
            "nextPageToken": "2",
            "total": 45,
            "limit": 20
        }
    }
    ```

### **GET `/songs/artists`**

Get paginated artist list filtered by song category.

- **Request Parameters:**
    - `category` (string, optional, default 'english'): Song category filter.
    - `page` (integer, optional, default 1).
    - `limit` (integer, optional, default 20, max 50).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "Artist Name",
                "imageUrl": "https://www.calamuseducation.com/uploads/songs/web/artist_slug.png",
                "imageSlug": "artist_slug",
                "songCount": 12
            }
        ],
        "meta": {
            "currentPage": 1,
            "lastPage": 5,
            "nextPageToken": "2",
            "total": 85,
            "limit": 20
        }
    }
    ```

### **GET `/songs/by-artist`**

Get paginated songs for a specific artist, optionally filtered by category.

- **Request Parameters:**
    - `artistId` (integer, required): ID of the artist.
    - `category` (string, optional, default 'english'): Song category filter.
    - `page` (integer, optional, default 1).
    - `limit` (integer, optional, default 20, max 50).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "artist": {
                "id": 1,
                "name": "Artist Name",
                "imageUrl": "https://www.calamuseducation.com/uploads/songs/web/artist_slug.png"
            },
            "songs": [
                {
                    "id": 501,
                    "songId": 501,
                    "artistId": 1,
                    "title": "Song Title",
                    "artist": "Artist Name",
                    "audioUrl": "https://.../audio/slug.mp3",
                    "imageUrl": "https://.../web/slug.png",
                    "likeCount": 150,
                    "downloadCount": 300,
                    "liked": true
                }
            ]
        },
        "meta": {
            "currentPage": 1,
            "lastPage": 2,
            "nextPageToken": "2",
            "total": 25,
            "limit": 20
        }
    }
    ```

### **POST `/songs/like`**

Toggle like/unlike for a song.

- **Authentication:** Required (Sanctum)
- **Request Body:**
    - `songId` (integer, required): The internal `id` of the song.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "isLiked": true,
            "likeCount": 151
        }
    }
    ```

### **POST `/songs/download`**

Increment the download count for a song.

- **Request Body:**
    - `songId` (integer, required): The internal `id` of the song.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "success": true,
            "downloadCount": 301
        }
    }
    ```

## 2. Mini Library (`/mini-library`)

### **GET `/mini-library/books`**

List PDF books in the library.

- **Request Parameters:**
    - `major` (string, required): e.g., 'english'.
    - `category` (string, required): Category name.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 10,
                "title": "Book Title",
                "author": "Author Name",
                "pdfPath": "https://.../books/book.pdf",
                "coverImage": "https://.../covers/book.png",
                "category": "Grammar"
            }
        ],
        "meta": {
            "major": "english",
            "category": "Grammar",
            "total": 5
        }
    }
    ```

### **GET `/mini-library/categories`**

- **Request Parameters:** `major` (string, required).
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "category": "Grammar",
                "count": 12
            }
        ],
        "meta": {
            "major": "english",
            "total": 5
        }
    }
    ```

## 3. Vocabulary Learning (`/vocab-learning`)

### **GET `/vocab-learning/get-decks`**

Get vocabulary decks for a language.

- **Request Parameters:**
    - `major` (string, optional)
    - `languageId` (integer, optional)
    - `userId` (string, optional, for progress)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "Essential 1000",
                "major": "english",
                "progress": {
                    "totalCards": 1000,
                    "masteredCards": 150,
                    "learnedCards": 200,
                    "recallWords": 15,
                    "newWords": 5,
                    "progressPercent": 15,
                    "currentLearningDay": 12
                }
            }
        ],
        "meta": { "total": 5 }
    }
    ```

### **GET `/vocab-learning/get-cards`**

Get learning cards for a specific deck.

- **Request Parameters:**
    - `deckId` (integer, required)
    - `userId` (string, required)
    - `wordCount` (integer, optional, default 20)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "words": [
                {
                    "card": {
                        "id": 5001,
                        "word": "Apple",
                        "definition": "A fruit",
                        "example": "I eat an apple",
                        "audio": "..."
                    },
                    "wordType": "new",
                    "isKnown": false
                }
            ],
            "step": "step1",
            "nextStep": "step2",
            "learningDayNumber": 12,
            "wordCounts": {
                "total": 20,
                "recallWords": 15,
                "newWords": 5,
                "learningDayLimit": 20
            }
        }
    }
    ```

## 4. Languages (`/languages`)

### **GET `/languages/get`**

List supported languages.

- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "name": "English",
                "code": "en",
                "image": "https://..."
            }
        ],
        "meta": { "total": 2 }
    }
    ```
