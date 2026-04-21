# API Documentation - Part 6: Vocabulary Learning System

This part covers the vocabulary learning system, including flashcard decks, spaced repetition (SM2), and learning progress tracking.

## 1. Languages & Decks

### **GET `/vocab-learning/get-languages`**
List all available languages for vocabulary learning.
- **Authentication:** Not Required
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
            },
            ...
        ]
    }
    ```

### **GET `/vocab-learning/get-decks`**
List available flashcard decks.
- **Request Parameters:**
    - `major` (string, optional): Filter by major (e.g., 'english', 'korean').
    - `languageId` (integer, optional): Filter by specific language ID.
    - `userId` (integer, optional): Provide to include user-specific progress for each deck.
- **Authentication:** Optional
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "id": 1,
                "title": "Essential 3000 Words",
                "major": "english",
                "languageId": 1,
                "progress": {
                    "totalCards": 3000,
                    "masteredCards": 450,
                    "learnedCards": 1200,
                    "recallWords": 15,
                    "newWords": 5,
                    "progressPercent": 15,
                    "currentLearningDay": 12
                }
            }
        ],
        "meta": {
            "total": 5
        }
    }
    ```

## 2. Learning Sessions

### **GET `/vocab-learning/get-cards`**
Get cards for a learning session. Returns a mix of recall words (due for review) and new words.
- **Authentication:** Required
- **Request Parameters:**
    - `deckId` (integer, required): The ID of the deck to learn from.
    - `wordCount` (integer, optional, default: 10): Requested number of words. The actual count may be limited by the user's daily learning limit.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "words": [
                {
                    "card": {
                        "id": 101,
                        "word": "Persistence",
                        "ipa": "/pəˈsɪstəns/",
                        "deckId": 1,
                        "languageId": 1
                    },
                    "richData": {
                        "word": "Persistence",
                        "ipa": "/pəˈsɪstəns/",
                        "pronunciationAudio": "audio_url",
                        "partsOfSpeech": ["noun"],
                        "burmeseTranslation": "ဇွဲ",
                        "exampleSentences": ["Persistence pays off."],
                        "synonyms": ["determination"],
                        "antonyms": ["laziness"],
                        "image": "image_url"
                    },
                    "wordType": "recall",
                    "isKnown": false
                },
                ...
            ],
            "step": "step1",
            "nextStep": "step2",
            "learningDayNumber": 12,
            "deckId": 1,
            "wordCounts": {
                "total": 10,
                "recallWords": 3,
                "newWords": 7,
                "requestedCount": 10,
                "learningDayLimit": 20,
                "actualLimit": 10
            }
        }
    }
    ```

### **POST `/vocab-learning/rate-word`**
Rate a word after seeing it to update its spaced repetition (SM2) status.
- **Authentication:** Required
- **Request Parameters (Body):**
    - `cardId` (integer, required): The ID of the card being rated.
    - `quality` (integer, required): Rating from 0 to 5.
        - 0-2: Forgot/Wrong (Resets interval)
        - 3-5: Remembered (Increases interval based on quality)
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "cardId": 101,
            "quality": 4,
            "sm2Result": {
                "ef": 2.6,
                "interval": 6,
                "repetitions": 2,
                "nextReviewLearningDay": 18,
                "currentLearningDay": 12
            }
        }
    }
    ```

### **POST `/vocab-learning/skip-word`**
Skip a word if the user already knows it. This marks the card as "permanently paused" and returns a replacement word.
- **Authentication:** Required
- **Request Parameters (Body):**
    - `cardId` (integer, required): The ID of the card to skip.
    - `deckId` (integer, required): The deck ID to get a replacement from.
    - `reason` (string, optional, default: 'already_know'): Reason for skipping.
    - `sessionCardIds` (array of integers, optional): IDs of cards already in the current session to avoid duplicates in replacement.
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": {
            "replacementWord": {
                "card": { ... },
                "richData": { ... },
                "wordType": "new",
                "isKnown": false
            },
            "skippedWord": {
                "cardId": 101,
                "pausedUntil": "permanent"
            }
        }
    }
    ```

## 3. Progress Tracking

### **GET `/vocab-learning/get-vocab-progress`**
Get comprehensive vocabulary learning progress for all decks the user has started.
- **Authentication:** Required
- **Response Shape:**
    ```json
    {
        "success": true,
        "data": [
            {
                "languageId": 1,
                "languageName": "English",
                "decks": [
                    {
                        "deckId": 1,
                        "deckTitle": "Essential 3000 Words",
                        "totalCards": 3000,
                        "masteredCards": 450,
                        "learnedCards": 1200,
                        "recallWords": 15,
                        "newWords": 5,
                        "progressPercent": 15,
                        "currentLearningDay": 12
                    }
                ]
            }
        ]
    }
    ```
