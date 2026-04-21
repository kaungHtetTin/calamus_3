# Speaking Chatbot Reverse Engineering Documentation

This document provides a detailed reverse engineering of the speaking chatbot functionality in the `SpeakingActivity` of the LearningRoom application.

## 1. Core Workflow

The speaking chatbot is a guided conversation experience where the user interacts with an AI bot (Person A) by speaking predefined phrases (Person B).

### Initializing and Setup
- **Permissions**: The activity requests `RECORD_AUDIO` permission.
- **Engines**: It initializes `SpeechRecognizer` for voice-to-text and `TextToSpeech` (TTS) for text-to-voice.
- **Data Fetching**: It fetches dialogue data from the backend using the `currentUserId` and `currentLevel`.

### Interaction Flow
1. **Bot Turn**: The bot (Person A) speaks its line using TTS and displays it in the chat.
2. **User Turn**: The user is shown what they should say (Person B).
3. **Speech Recording**: The user holds a "Speak" button (`bt_speak`) to record their voice.
4. **Recognition**: When the button is released, the `SpeechRecognizer` processes the audio and returns a list of text matches.
5. **Verification**: The app compares the first match from the recognizer with the expected phrase (Person B's English text).
   - Comparisons are case-insensitive and ignore punctuation.
6. **Outcome**:
   - **Correct**: The phrase is added to the chat, the bot moves to the next line in the dialogue.
   - **Incorrect**: The recognized text is displayed, and the error is recorded to the server.
7. **Level Completion**: Once the dialogue is finished, the app updates the user's progress and navigates to the `SpeakingNextLevelActivity`.

---

## 2. API Usage

The application uses three primary API endpoints for the speaking chatbot functionality.

### A. Fetch Dialogues
- **Endpoint**: `GET` `https://www.calamuseducation.com/calamus-v2/api/english/getdialogues`
- **Parameters**:
  - `phone`: User ID (usually a phone number).
  - `level`: (Optional) Current level to fetch.
- **Response**: A JSON array of dialogue objects. Each object contains:
  - `person_a`: Bot's English text.
  - `person_a_mm`: Bot's Myanmar translation.
  - `person_b`: Expected user's English text.
  - `person_b_mm`: User's Myanmar translation.
  - `dialogueId`: Unique identifier for the dialogue entry.

### B. Record Error Speech
- **Endpoint**: `POST` `https://www.calamuseducation.com/calamus-v2/api/english/chatbot/recorderrorspeech`
- **Fields**:
  - `phone`: User ID.
  - `error_speech`: The text that was incorrectly recognized.
  - `speech_id`: The ID of the dialogue entry being attempted.

### C. Update Progress
- **Endpoint**: `POST` `https://www.calamuseducation.com/calamus-v2/api/english/chatbot/updatelevel`
- **Fields**:
  - `phone`: User ID.
- **Purpose**: Marks the current level as completed.

---

## 3. Key Components

- **`SpeakingActivity.java`**: The main activity class managing the UI, speech recognition, and network communication.
- **`SpeechRecognizer`**: Android's built-in engine used to convert audio input to text.
- **`TextToSpeech` (TTS)**: Used to synthesize the bot's lines into spoken audio.
- **`MyHttp.java`**: A custom utility class for making HTTP requests asynchronously.
- **`DialogueAdapter`**: Manages the display of messages in a `RecyclerView` to create a chat-like interface.

---

## 4. Logical Comparisons

The verification logic is handled in `isSpeechCorrect(String personB, String user)`:
```java
private boolean isSpeechCorrect(String personB, String user) {
    personB = personB.toLowerCase().replaceAll("\\p{Punct}", "");
    user = user.toLowerCase().replaceAll("\\p{Punct}", "");
    return personB.equals(user);
}
```
This ensures that minor differences in punctuation or capitalization do not cause the user to fail.
