# GammingActivity Working Flow

This document explains how `GammingActivity` works from launch to exit.

## 1) Activity Startup

When `GammingActivity` opens:

- `onCreate()` sets `activity_gamming` layout.
- Shared preferences (`GeneralData`) are read for:
  - `currentUserId`
  - `username`
  - `profileImage`
  - `highestScore` (`GameScore`)
  - `isVip`
- `setUpView()` initializes all UI widgets, listeners, and first data loads.
- `postExecutor = ContextCompat.getMainExecutor(this)` is used to post background-thread results to the UI thread.
- If user is **not VIP**, AdMob is initialized and interstitial ad is preloaded (`loadAd()`).
- Back-press is overridden:
  - Show interstitial ad if ready.
  - Otherwise show confirm-exit dialog (`confirmExit()`).

## 2) View Setup and Initial Data

Inside `setUpView()`:

- All core views are bound (`findViewById`) including:
  - Prompt display (`tv_displayText`, `iv_displayImage`, `iv_displayAudio`)
  - Option buttons/text (`bt_a`, `bt_b`, `bt_c`, `tv_A`, `tv_B`, `tv_C`)
  - Score views (`tvCurrentScore`, `tv_score`, profile and name)
  - Leaderboard `RecyclerView`
- Option text views are made marquee single-line using `makeTextSingleLine(...)`.
- Leaderboard adapter (`TopGamePlayerAdapter`) is attached.
- Option button clicks call `checkAndLoad("a" | "b" | "c")` and store `selectedButton`.
- Back icon (`iv_back`) follows same ad-or-confirm-exit behavior as hardware back.
- Initial calls:
  - `fetchWord()` -> load first game prompt/question
  - `fetchTopGamePlayer()` -> load top players
- User profile UI is filled (`tv_name`, `iv_profile`) and highest score label uses `formatScore(highestScore)`.

## 3) Question Fetch Flow (`fetchWord`)

`fetchWord()` controls loading state and API request:

- Shows progress bar and disables option buttons.
- Starts a background thread using `MyHttp` GET to `Routing.GET_GAME_WORD`.
- On success:
  - UI-thread callback executes `doAsResult(response)`.
  - Re-enables option buttons.
  - Hides progress bar.
- On error:
  - Shows toast with error message.

## 4) Rendering a Prompt (`doAsResult`)

Response is parsed from JSON array first item:

- Reads:
  - `display_word`, `display_image`, `display_audio`
  - `category`
  - options `a`, `b`, `c`
  - correct answer key `ans`
- Writes options into UI (`A - ...`, `B - ...`, `C - ...`).

Display mode by `category`:

- `category == "1"`: text prompt
  - Shows `tv_displayText`, hides image/audio icon.
- `category == "2"`: image prompt
  - Loads image via `AppHandler.setPhotoFromRealUrl(...)`, hides others.
- Else: audio prompt
  - Shows audio icon, hides text/image.
  - Calls `playAudio(displayAudio)`.
  - Audio icon tap replays audio (`mPlayer.start()`).

## 5) Answer Selection and Round Transition (`checkAndLoad`)

When user chooses an option:

- If selected answer matches `ans`:
  - `currentScore++`
  - Score label updates
  - Next prompt loads immediately via `fetchWord()`

- If selected answer is wrong:
  - If current run score beats stored highest score:
    - `setMarkGameScore()` sends score to backend
    - local `highestScore` updated and saved to `SharedPreferences`
    - score label updated
  - Leaderboard list is reset and refreshed (`fetchTopGamePlayer()`).
  - A new prompt is pre-fetched (`fetchWord()`).
  - Result dialog is shown (`showGameOverDialog(currentScore)`).

## 6) Game Over Dialog (`showGameOverDialog`)

Dialog content includes:

- Highest score and current run points.
- Correct answer (`The answer is ( ans )`).
- Same prompt representation (text/image/audio) from the failed round.
- Option buttons with the selected wrong choice highlighted by `selectedButton`.

Actions:

- `Restart`:
  - Resets `currentScore` to 0
  - Updates current score label
  - Dismisses dialog
- `Quit`:
  - Finishes activity

## 7) Leaderboard Flow (`fetchTopGamePlayer`)

- Background GET request to `Routing.GET_GAME_SCORE`.
- Parses array of top players:
  - `learner_name`, `learner_image`, `game_score`, `user_id`
- Builds `TopGamePlayerModel` list and notifies adapter.
- On error: toast message.

## 8) Score Update API (`setMarkGameScore`)

- Background POST request to `Routing.UPDATE_GAME_SCORE`.
- Sends:
  - `phone = currentUserId`
  - `score = currentScore`
- Success and error callbacks currently do not update UI (error toast is intentionally suppressed in code).

## 9) Exit Flow

Exit can be triggered by:

- Hardware back press
- Back icon (`iv_back`)

Flow:

- If interstitial ad exists, ad is shown first.
- On ad dismiss:
  - If current score beats highest score, score is synced/saved.
  - Activity finishes.
- If ad unavailable:
  - `confirmExit()` dialog appears.
  - On confirm, latest high score is saved/synced (if improved), then activity finishes.

## 10) Audio Playback Helper (`playAudio`)

- Creates new `MediaPlayer`.
- Sets stream type to `STREAM_MUSIC`.
- Sets source URL and prepares asynchronously.
- Audio can be replayed via click handlers in both main screen and result dialog.

## 11) Utility Helpers

- `formatScore(int score)`:
  - Returns `"1 point"` or `"N points"` formatting.
- `makeTextSingleLine(TextView tv)`:
  - Applies marquee single-line behavior for long option text.

---

## High-Level Sequence (Quick View)

1. Start activity -> init data/UI -> optional ad preload.
2. Fetch first prompt + fetch leaderboard.
3. User selects answer:
   - Correct -> increment score -> fetch next prompt.
   - Wrong -> persist high score (if needed) -> show game over dialog -> reset/quit path.
4. On exit, attempt ad flow first for non-VIP users, then save score and finish.
