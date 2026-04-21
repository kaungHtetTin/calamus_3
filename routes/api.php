<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdditionalLessonController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\VipPlanController;
use App\Http\Controllers\StatController;
use App\Http\Controllers\MiniLibraryController;
use App\Http\Controllers\VideoChannelController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\VocabLearningController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\WordOfDayController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SpeakingChatbotController;
use App\Http\Controllers\MiniProgram\MiniProgramController;
use App\Http\Controllers\Api\AdminAuthController as AdminApiAuthController;
use App\Http\Controllers\Api\AdminFlashcardController;
use App\Http\Controllers\Api\AdminLanguageController;
use App\Http\Controllers\Api\AdminPackagePlanController as AdminApiPackagePlanController;
use App\Http\Controllers\Api\AdminPaymentController;
use App\Http\Controllers\Api\AdminPushNotificationController as AdminApiPushNotificationController;
use App\Http\Controllers\Api\AdminSaveReplyController as AdminApiSaveReplyController;
use App\Http\Controllers\Api\AdminUserController as AdminApiUserController;
use App\Http\Controllers\Api\AdminVipAccessTransferController as AdminApiVipAccessTransferController;
use App\Http\Controllers\Api\AdminVipAccessController as AdminApiVipAccessController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;
use App\Http\Middleware\EnsureAdminApi;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']); //ok
    Route::post('/register', [AuthController::class, 'register']); //ok
    Route::post('/check-account', [AuthController::class, 'checkAccount']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/confirm-forgot-password', [AuthController::class, 'confirmForgotPassword']);
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/logout', [AuthController::class, 'logout']); //ok

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']); //ok
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/email/resend-verification', [AuthController::class, 'resendVerificationEmail']);
    });
});

Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'index']);
    Route::get('/all', [CourseController::class, 'index']); // Alias
    Route::get('/detail', [CourseController::class, 'show']);
    Route::get('/curriculum', [CourseController::class, 'curriculum']);
    Route::get('/lesson-categories', [CourseController::class, 'lessonCategories']);
    Route::get('/featured', [CourseController::class, 'featured']);
    Route::get('/new', [CourseController::class, 'new']); //ok
    Route::get('/get-certificate', [CourseController::class, 'certificate']);
});

Route::prefix('lessons')->group(function () {
    
    Route::get('/detail', [LessonController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/download', [LessonController::class, 'download']);
        Route::post('/mark-learned', [LessonController::class, 'markLearned']);
        Route::post('/like', [LessonController::class, 'like']);
        Route::post('/share', [LessonController::class, 'share']);
        Route::post('/comment', [LessonController::class, 'comment']);
    });
});

Route::prefix('users')->group(function () {
    Route::get('/profile', [UserController::class, 'profile']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-learning', [UserController::class, 'myLearning']);
        Route::post('/update', [UserController::class, 'update']);
        Route::post('/update-bio', [UserController::class, 'updateBio']);
        Route::post('/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('/update-credentials', [UserController::class, 'updateCredentials']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/delete-account', [UserController::class, 'deleteAccount']);
    });
});

Route::prefix('additional-lessons')->group(function () {
    Route::get('/courses', [AdditionalLessonController::class, 'getCourses']);
    Route::get('/lessons', [AdditionalLessonController::class, 'getLessons']);
});

Route::prefix('apps')->group(function () {
    Route::get('/get', [AppController::class, 'index']); //ok
    Route::get('/check-update', [AppController::class, 'checkUpdate']);
});

Route::get('/mini-programs', [MiniProgramController::class, 'index']);

Route::prefix('chat')->middleware('auth:sanctum')->group(function () {
    Route::get('/messages', [ChatController::class, 'getMessages']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    Route::post('/mark-read', [ChatController::class, 'markRead']);
    Route::post('/upload-image', [ChatController::class, 'uploadImage']);
    
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::post('/conversations', [ChatController::class, 'createConversation']);
    Route::put('/conversations', [ChatController::class, 'updateConversation']);
    Route::delete('/conversations', [ChatController::class, 'deleteConversation']);
});

Route::prefix('comments')->group(function () {
    Route::get('/get', [CommentController::class, 'index']);
    Route::get('/likes', [CommentController::class, 'likes']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/create', [CommentController::class, 'store']);
        Route::post('/delete', [CommentController::class, 'destroy']);
        Route::post('/update', [CommentController::class, 'update']);
        Route::post('/like', [CommentController::class, 'like']);
    });
});

Route::prefix('about')->group(function () {
    Route::get('/stats', [AboutController::class, 'stats']);
});

Route::prefix('languages')->group(function () {
    Route::get('/get', [LanguageController::class, 'index']);
});

Route::prefix('instructors')->group(function () {
    Route::get('/all', [InstructorController::class, 'index']);
    Route::get('/detail', [InstructorController::class, 'show']);
});

Route::prefix('vip-plan')->group(function () {
    Route::get('/get', [VipPlanController::class, 'index']);
});

Route::prefix('stats')->group(function () {
    Route::get('/home', [StatController::class, 'home']);
});

Route::prefix('mini-library')->group(function () {
    Route::get('/books', [MiniLibraryController::class, 'books']);
    Route::get('/categories', [MiniLibraryController::class, 'categories']);
});

Route::prefix('video-channel')->group(function () {
    Route::get('/get', [VideoChannelController::class, 'index']);
    Route::get('/video', [VideoChannelController::class, 'show']);
});

Route::prefix('posts')->group(function () {
    Route::get('/pinned', [PostController::class, 'pinned']); //ok
});

Route::prefix('friends')->group(function () {    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/get-friends', [FriendController::class, 'index']);
        Route::get('/get-requests', [FriendController::class, 'getIncomingRequests']);
        Route::post('/add', [FriendController::class, 'add']);
        Route::post('/confirm', [FriendController::class, 'confirm']);
        Route::post('/unfriend', [FriendController::class, 'unfriend']);
        Route::get('/get-status', [FriendController::class, 'getStatus']);
        Route::post('/block', [FriendController::class, 'block']);
        Route::post('/unblock', [FriendController::class, 'unblock']);
        Route::get('/get-blocked', [FriendController::class, 'getBlocked']);
        Route::get('/check-block', [FriendController::class, 'checkBlock']);
        Route::get('/people-you-may-know', [FriendController::class, 'peopleYouMayKnow']);
        Route::get('/search', [FriendController::class, 'search']);
    });
});

Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/get', [NotificationController::class, 'index']);
    Route::post('/mark-seen', [NotificationController::class, 'markSeen']);
    Route::post('/mark-one-read', [NotificationController::class, 'markOneRead']);
});

Route::prefix('ratings')->group(function () {
    Route::get('/', [RatingController::class, 'index']);
    Route::get('/breakdown', [RatingController::class, 'breakdown']);
    Route::get('/latest', [RatingController::class, 'latest']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/store', [RatingController::class, 'store']);
        Route::post('/delete', [RatingController::class, 'delete']);
        Route::delete('/{id}', [RatingController::class, 'destroy']);
    });
});

Route::prefix('discussions')->group(function () {
    Route::get('/get', [DiscussionController::class, 'index']);
    Route::get('/detail', [DiscussionController::class, 'show']);
    Route::get('/likes', [DiscussionController::class, 'likes']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/create', [DiscussionController::class, 'create']);
        Route::post('/share', [DiscussionController::class, 'share']);
        Route::post('/update', [DiscussionController::class, 'update']);
        Route::post('/delete', [DiscussionController::class, 'delete']);
        Route::post('/report', [DiscussionController::class, 'report']);
        Route::post('/like', [DiscussionController::class, 'like']);
        Route::post('/hide', [DiscussionController::class, 'hide']);
    });
});

Route::prefix('songs')->group(function () {
    Route::get('/get', [SongController::class, 'index']);
    Route::get('/search', [SongController::class, 'search']);
    Route::get('/artists', [SongController::class, 'artists']);
    Route::get('/by-artist', [SongController::class, 'getByArtist']);
    Route::post('/download', [SongController::class, 'incrementDownloadCount']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/like', [SongController::class, 'toggleLike']);
    });
});

Route::prefix('payments')->middleware('auth:sanctum')->group(function () {
    Route::post('/paid', [PaymentController::class, 'paid']);
    Route::get('/history', [PaymentController::class, 'history']);
});

Route::prefix('announcements')->group(function () {
    Route::get('/get', [AnnouncementController::class, 'index']);
});

Route::prefix('communities')->group(function () {
    Route::get('/get', [CommunityController::class, 'index']);
});

Route::prefix('vocab-learning')->group(function () {
    Route::get('/get-decks', [VocabLearningController::class, 'getDecks']);
    Route::get('/get-languages', [VocabLearningController::class, 'getLanguages']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/get-cards', [VocabLearningController::class, 'getCards']);
        Route::get('/get-vocab-progress', [VocabLearningController::class, 'getVocabProgress']);
        Route::post('/rate-word', [VocabLearningController::class, 'rateWord']);
        Route::post('/skip-word', [VocabLearningController::class, 'skipWord']);
    });
});

Route::prefix('admin')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AdminApiAuthController::class, 'login']);

        Route::middleware(['auth:sanctum', EnsureAdminApi::class, 'admin.activity.log'])->group(function () {
            Route::get('/me', [AdminApiAuthController::class, 'me']);
            Route::post('/logout', [AdminApiAuthController::class, 'logout']);
        });
    });

   

    Route::middleware(['auth:sanctum', EnsureAdminApi::class, 'admin.activity.log'])->group(function () {

        Route::get('/languages/get', [AdminLanguageController::class, 'index']);
        Route::get('/package-plans', [AdminApiPackagePlanController::class, 'index']);
        Route::get('/package-plans/{planId}', [AdminApiPackagePlanController::class, 'show'])->whereNumber('planId');
        Route::get('/save-replies', [AdminApiSaveReplyController::class, 'index']);
        Route::post('/save-replies', [AdminApiSaveReplyController::class, 'store']);
        Route::get('/save-replies/{id}', [AdminApiSaveReplyController::class, 'show'])->whereNumber('id');
        Route::patch('/save-replies/{id}', [AdminApiSaveReplyController::class, 'update'])->whereNumber('id');
        Route::delete('/save-replies/{id}', [AdminApiSaveReplyController::class, 'destroy'])->whereNumber('id');
        Route::post('/push/user-topics', [AdminApiPushNotificationController::class, 'sendToUserTopics']);
        Route::post('/flashcards/cards/bulk', [AdminFlashcardController::class, 'bulkUploadCards']);
        Route::get('/users/lookup', [AdminApiUserController::class, 'lookup']);
        Route::get('/users/{userId}', [AdminApiUserController::class, 'show'])->whereNumber('userId');
        Route::post('/users/reset-password', [AdminApiUserController::class, 'resetPassword']);
        Route::get('/users/{userId}/vip-access', [AdminApiVipAccessController::class, 'status'])->whereNumber('userId');
        Route::post('/users/{userId}/vip-access', [AdminApiVipAccessController::class, 'update'])->whereNumber('userId');
        Route::get('/users/vip-transfer/preview', [AdminApiVipAccessTransferController::class, 'preview']);
        Route::post('/users/vip-transfer', [AdminApiVipAccessTransferController::class, 'execute']);
        Route::get('/payments/unactivated', [AdminPaymentController::class, 'unactivated']);
        Route::get('/payments/pending-approval', [AdminPaymentController::class, 'pendingApproval']);
        Route::get('/payments/unactivated/{paymentId}', [AdminPaymentController::class, 'unactivatedDetail'])->whereNumber('paymentId');
        Route::post('/payments/{paymentId}/activate', [AdminPaymentController::class, 'activate'])->whereNumber('paymentId');

        Route::prefix('chat')->group(function () {
            Route::get('/conversations', [AdminSupportChatController::class, 'conversations']);
            Route::get('/conversation', [AdminSupportChatController::class, 'conversation']);
            Route::get('/messages', [AdminSupportChatController::class, 'messages']);
            Route::post('/messages', [AdminSupportChatController::class, 'send']);
            Route::post('/upload-image', [AdminSupportChatController::class, 'sendImage']);
            Route::get('/unread-count', [AdminSupportChatController::class, 'unreadCount']);
        });
    });
});

Route::prefix('word-of-day')->group(function () {
    Route::get('/', [WordOfDayController::class, 'get']);
});

Route::prefix('search')->group(function () {
    Route::get('/universal', [SearchController::class, 'universalSearch']);
});

Route::prefix('speaking-chatbot')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/dialogues', [SpeakingChatbotController::class, 'getDialogues']);
        Route::get('/progress', [SpeakingChatbotController::class, 'getProgress']);
        Route::post('/error-log', [SpeakingChatbotController::class, 'recordErrorLog']);
        Route::post('/complete-level', [SpeakingChatbotController::class, 'completeLevel']);
    });
});

Route::prefix('games')->group(function () {
    Route::get('/words/random', [GameController::class, 'getWord']);
    Route::get('/scores/leaderboard', [GameController::class, 'getTopScores']);
    Route::post('/scores', [GameController::class, 'updateScore']);
});
