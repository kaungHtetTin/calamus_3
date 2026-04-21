<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MiniProgram\SongRequestController;
use App\Http\Controllers\MiniProgram\ExamController;
use App\Http\Controllers\MiniProgram\LibraryController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UiShowcaseController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\AdministrationController;
use App\Http\Controllers\Admin\LanguageController as AdminLanguageController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AdditionalLessonsController as AdminAdditionalLessonsController;
use App\Http\Controllers\Admin\FinancialManagementController as AdminFinancialManagementController;
use App\Http\Controllers\Admin\SongManagementController as AdminSongManagementController;
use App\Http\Controllers\Admin\ResourceManagementController as AdminResourceManagementController;
use App\Http\Controllers\Admin\SupportChatController as AdminSupportChatController;
use App\Http\Controllers\Admin\AdminNotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\SaveReplyController as AdminSaveReplyController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\VipAccessTransferController as AdminVipAccessTransferController;
use App\Http\Controllers\Admin\CommunityController as AdminCommunityController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest Routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'loginForm'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login']);
    });

    // Authenticated Routes
    Route::middleware(['auth:admin', 'admin.activity.log'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/ui-showcase', [UiShowcaseController::class, 'index'])->name('ui-showcase');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/certificate', [AdminCourseController::class, 'certificate'])->name('certificate')->middleware('admin.permission:administration,course');
        Route::get('/certificate/image-proxy', [AdminCourseController::class, 'certificateImageProxy'])->name('certificate.image-proxy')->middleware('admin.permission:administration,course');

        // Profile Routes
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');

        // User Management
        Route::prefix('users')->name('users.')->middleware('admin.permission:user')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/analysis', [AdminUserController::class, 'analysis'])->name('analysis');
            Route::get('/enroll-course', [AdminUserController::class, 'enrollCourse'])->name('enroll-course');
            Route::get('/vip-transfer', [AdminVipAccessTransferController::class, 'index'])->name('vip-transfer');
            Route::post('/vip-transfer', [AdminVipAccessTransferController::class, 'execute'])->name('vip-transfer.execute');
            Route::post('/enroll-course/{payment}/activate', [AdminUserController::class, 'activatePaymentCourses'])->whereNumber('payment')->name('enroll-course.activate');
            Route::get('/push', [AdminUserController::class, 'pushTopicForm'])->name('push-topic.form');
            Route::post('/push', [AdminUserController::class, 'pushTopicSend'])->name('push-topic.send');
            Route::get('/email', [AdminUserController::class, 'emailBroadcastForm'])->name('email-broadcast.form');
            Route::post('/email', [AdminUserController::class, 'emailBroadcastSend'])->name('email-broadcast.send');
            Route::get('/email/progress', [AdminUserController::class, 'emailBroadcastProgress'])->name('email-broadcast.progress');
            Route::get('/{id}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::post('/{id}/vip-access', [AdminUserController::class, 'saveVipAccess'])->middleware('admin.permission:administration')->name('vip-access.save');
            Route::post('/{id}/push', [AdminUserController::class, 'sendPush'])->name('push.send');
            Route::post('/{id}/email', [AdminUserController::class, 'sendEmail'])->name('email.send');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::patch('/{id}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminUserController::class, 'destroy'])->middleware('admin.permission:administration')->name('destroy');
        });

        Route::prefix('support-chat')->name('support-chat.')->middleware('admin.permission:user')->group(function () {
            Route::get('/', [AdminSupportChatController::class, 'index'])->name('index');
            Route::get('/conversations', [AdminSupportChatController::class, 'conversations'])->name('conversations');
            Route::get('/unread-count', [AdminSupportChatController::class, 'unreadCount'])->name('unread-count');
            Route::get('/messages', [AdminSupportChatController::class, 'messages'])->name('messages');
            Route::post('/messages', [AdminSupportChatController::class, 'send'])->name('messages.send');
            Route::post('/messages/image', [AdminSupportChatController::class, 'sendImage'])->name('messages.send-image');
        });

        Route::prefix('notifications')->name('notifications.')->middleware('admin.permission:user')->group(function () {
            Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/mark-one-read', [AdminNotificationController::class, 'markOneRead'])->name('mark-one-read');
            Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])->name('mark-all-read');
        });

        Route::prefix('faqs')->name('faqs.')->middleware('admin.permission:user')->group(function () {
            Route::get('/', [AdminFaqController::class, 'index'])->name('index');
            Route::post('/', [AdminFaqController::class, 'store'])->name('store');
            Route::patch('/{faq}', [AdminFaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [AdminFaqController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('discussions')->name('discussions.')->group(function () {
            Route::get('/', [AdminUserController::class, 'discussions'])->name('index');
            Route::post('/{postId}/hide', [AdminUserController::class, 'toggleDiscussionHide'])->name('hide');
            Route::post('/{postId}/like', [AdminUserController::class, 'toggleDiscussionLike'])->name('like');
            Route::get('/{postId}/comments', [AdminUserController::class, 'discussionComments'])->name('comments');
            Route::post('/{postId}/comments', [AdminUserController::class, 'addDiscussionComment'])->name('comments.store');
            Route::post('/comments/{commentTime}/like', [AdminUserController::class, 'toggleDiscussionCommentLike'])->name('comments.like');
            Route::patch('/comments/{commentTime}', [AdminUserController::class, 'updateDiscussionComment'])->name('comments.update');
            Route::delete('/comments/{commentTime}', [AdminUserController::class, 'deleteDiscussionComment'])->name('comments.destroy');
            Route::get('/{postId}', [AdminUserController::class, 'discussionDetail'])->name('show');
            Route::delete('/{postId}', [AdminUserController::class, 'deleteDiscussion'])->name('destroy');
        });

        Route::prefix('communities')->name('communities.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdminCommunityController::class, 'index'])->name('index');
            Route::post('/', [AdminCommunityController::class, 'store'])->name('store');
            Route::patch('/{community}', [AdminCommunityController::class, 'update'])->name('update');
            Route::delete('/{community}', [AdminCommunityController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('save-replies')->name('save-replies.')->group(function () {
            Route::get('/', [AdminSaveReplyController::class, 'index'])->name('index');
            Route::get('/options', [AdminSaveReplyController::class, 'options'])->name('options');
            Route::post('/', [AdminSaveReplyController::class, 'store'])->name('store');
            Route::patch('/{saveReply}', [AdminSaveReplyController::class, 'update'])->name('update');
            Route::delete('/{saveReply}', [AdminSaveReplyController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('activity-logs')->name('activity-logs.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdminActivityLogController::class, 'index'])->name('index');
            Route::post('/clear', [AdminActivityLogController::class, 'clear'])->name('clear');
        });

        // Administration Management
        Route::prefix('administration')->name('administration.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdministrationController::class, 'index'])->name('index');
            Route::post('/', [AdministrationController::class, 'store'])->name('store');
            Route::patch('/{admin}', [AdministrationController::class, 'update'])->name('update');
            Route::delete('/{admin}', [AdministrationController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('languages')->name('languages.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdminLanguageController::class, 'index'])->name('index');
            Route::post('/', [AdminLanguageController::class, 'store'])->name('store');
            Route::post('/{language}', [AdminLanguageController::class, 'update'])->name('update.post');
            Route::patch('/{language}', [AdminLanguageController::class, 'update'])->name('update');
            Route::delete('/{language}', [AdminLanguageController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('payment-methods')->name('payment-methods.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'store'])->name('store');
            Route::patch('/{paymentMethod}', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'update'])->name('update');
            Route::delete('/{paymentMethod}', [\App\Http\Controllers\Admin\PaymentMethodController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('package-plans')->name('package-plans.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PackagePlanController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\PackagePlanController::class, 'store'])->name('store');
            Route::patch('/{packagePlan}', [\App\Http\Controllers\Admin\PackagePlanController::class, 'update'])->name('update');
            Route::delete('/{packagePlan}', [\App\Http\Controllers\Admin\PackagePlanController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('announcements')->name('announcements.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnnouncementController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\AnnouncementController::class, 'store'])->name('store');
            Route::patch('/{announcement}', [\App\Http\Controllers\Admin\AnnouncementController::class, 'update'])->name('update');
            Route::delete('/{announcement}', [\App\Http\Controllers\Admin\AnnouncementController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('apps')->name('apps.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ApplicationsController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\ApplicationsController::class, 'store'])->name('store');
            Route::patch('/{app}', [\App\Http\Controllers\Admin\ApplicationsController::class, 'update'])->name('update');
            Route::delete('/{app}', [\App\Http\Controllers\Admin\ApplicationsController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('mini-programs')->name('mini-programs.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\MiniProgramController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Admin\MiniProgramController::class, 'store'])->name('store');
            Route::patch('/{miniProgram}', [\App\Http\Controllers\Admin\MiniProgramController::class, 'update'])->name('update');
            Route::delete('/{miniProgram}', [\App\Http\Controllers\Admin\MiniProgramController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('courses')->name('courses.')->middleware('admin.permission:administration,course')->group(function () {
            Route::get('/', [AdminCourseController::class, 'index'])->name('index');
            Route::get('/create', [AdminCourseController::class, 'create'])->name('create');
            Route::get('/{course}/edit', [AdminCourseController::class, 'edit'])->name('edit');
            Route::post('/', [AdminCourseController::class, 'store'])->name('store');
            Route::post('/{course}', [AdminCourseController::class, 'update'])->name('update.post');
            Route::patch('/{course}', [AdminCourseController::class, 'update'])->name('update');
            Route::delete('/{course}', [AdminCourseController::class, 'destroy'])->name('destroy');

            Route::post('/{course}/categories', [AdminCourseController::class, 'storeCategory'])->name('categories.store');
            Route::post('/{course}/categories/{category}', [AdminCourseController::class, 'updateCategory'])->name('categories.update.post');
            Route::patch('/{course}/categories/{category}', [AdminCourseController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/{course}/categories/{category}', [AdminCourseController::class, 'destroyCategory'])->name('categories.destroy');

            Route::post('/{course}/categories/{category}/lessons/{lesson}', [AdminCourseController::class, 'updateLesson'])->whereNumber('lesson')->name('lessons.update.post');
            Route::patch('/{course}/categories/{category}/lessons/{lesson}', [AdminCourseController::class, 'updateLesson'])->whereNumber('lesson')->name('lessons.update');
            Route::delete('/{course}/categories/{category}/lessons/{lesson}', [AdminCourseController::class, 'destroyLesson'])->whereNumber('lesson')->name('lessons.destroy');
            Route::post('/{course}/categories/{category}/lessons', [AdminCourseController::class, 'storeLesson'])->name('lessons.store');
            Route::get('/{course}/categories/{category}/lessons/{lesson}/video-detail', [AdminCourseController::class, 'videoLessonDetail'])->whereNumber('lesson')->name('lessons.video-detail');
            Route::post('/{course}/categories/{category}/lessons/vip-bulk', [AdminCourseController::class, 'bulkUpdateCategoryLessonsVip'])->name('lessons.vip-bulk');
            Route::post('/{course}/categories/{category}/lessons/{lesson}/comments', [AdminCourseController::class, 'storeVideoLessonComment'])->whereNumber('lesson')->name('lessons.comments.store');
            Route::patch('/{course}/categories/{category}/lessons/{lesson}/comments/{comment}', [AdminCourseController::class, 'updateVideoLessonComment'])->whereNumber('lesson')->name('lessons.comments.update');
            Route::delete('/{course}/categories/{category}/lessons/{lesson}/comments/{comment}', [AdminCourseController::class, 'destroyVideoLessonComment'])->whereNumber('lesson')->name('lessons.comments.destroy');
            Route::post('/{course}/categories/{category}/lessons/{lesson}/comments/{comment}/like', [AdminCourseController::class, 'toggleVideoLessonCommentLike'])->whereNumber('lesson')->name('lessons.comments.like');
            Route::get('/{course}/categories/{category}/lessons/{lesson}/html', [AdminCourseController::class, 'editLessonHtml'])->whereNumber('lesson')->name('lessons.html.edit');
            Route::patch('/{course}/categories/{category}/lessons/{lesson}/html', [AdminCourseController::class, 'updateLessonHtml'])->whereNumber('lesson')->name('lessons.html.update');

            Route::post('/{course}/study-plan', [AdminCourseController::class, 'storeStudyPlan'])->name('study-plan.store');
            Route::patch('/{course}/study-plan/{studyPlanId}', [AdminCourseController::class, 'updateStudyPlan'])->name('study-plan.update');
            Route::delete('/{course}/study-plan/{studyPlanId}', [AdminCourseController::class, 'destroyStudyPlan'])->name('study-plan.destroy');

            Route::patch('/{course}/reviews/{review}', [AdminCourseController::class, 'updateReview'])->name('reviews.update');
            Route::delete('/{course}/reviews/{review}', [AdminCourseController::class, 'destroyReview'])->name('reviews.destroy');
        });

        Route::prefix('teachers')->name('teachers.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdminTeacherController::class, 'index'])->name('index');
            Route::post('/', [AdminTeacherController::class, 'store'])->name('store');
            Route::post('/{teacher}', [AdminTeacherController::class, 'update'])->name('update.post');
            Route::patch('/{teacher}', [AdminTeacherController::class, 'update'])->name('update');
            Route::delete('/{teacher}', [AdminTeacherController::class, 'destroy'])->name('destroy');
        });

        // Additional Lessons (for courses where major = 'not')
        Route::prefix('additional-lessons')->name('additional-lessons.')->middleware('admin.permission:administration,course')->group(function () {
            Route::get('/', [AdminAdditionalLessonsController::class, 'index'])->name('index');
            Route::get('/workspace', [AdminAdditionalLessonsController::class, 'workspace'])->name('workspace');
            Route::get('/courses', [AdminAdditionalLessonsController::class, 'coursesIndex'])->name('courses.index');
            Route::post('/courses', [AdminAdditionalLessonsController::class, 'storeCourse'])->name('courses.store');
            Route::patch('/courses/{course}', [AdminAdditionalLessonsController::class, 'updateCourse'])->name('courses.update');
            Route::delete('/courses/{course}', [AdminAdditionalLessonsController::class, 'destroyCourse'])->name('courses.destroy');
            Route::get('/{courseId}', [AdminAdditionalLessonsController::class, 'manage'])
                ->whereNumber('courseId')
                ->name('manage');
        });

        Route::prefix('financial')->name('financial.')->middleware('admin.permission:administration')->group(function () {
            Route::get('/', [AdminFinancialManagementController::class, 'index'])->name('index');
            Route::get('/workspace', [AdminFinancialManagementController::class, 'workspace'])->name('workspace');
        });

        Route::prefix('songs')->name('songs.')->middleware('admin.permission:administration,course')->group(function () {
            Route::get('/', [AdminSongManagementController::class, 'index'])->name('index');
            Route::get('/workspace', [AdminSongManagementController::class, 'workspace'])->name('workspace');
            Route::post('/artists', [AdminSongManagementController::class, 'storeArtist'])->name('artists.store');
            Route::patch('/artists/{artist}', [AdminSongManagementController::class, 'updateArtist'])->whereNumber('artist')->name('artists.update');
            Route::delete('/artists/{artist}', [AdminSongManagementController::class, 'destroyArtist'])->whereNumber('artist')->name('artists.destroy');
            Route::post('/songs', [AdminSongManagementController::class, 'storeSong'])->name('songs.store');
            Route::patch('/songs/{song}', [AdminSongManagementController::class, 'updateSong'])->whereNumber('song')->name('songs.update');
            Route::delete('/songs/{song}', [AdminSongManagementController::class, 'destroySong'])->whereNumber('song')->name('songs.destroy');
            Route::get('/songs/{song}/lyric', [AdminSongManagementController::class, 'editSongLyric'])->whereNumber('song')->name('songs.lyric.edit');
            Route::patch('/songs/{song}/lyric', [AdminSongManagementController::class, 'updateSongLyric'])->whereNumber('song')->name('songs.lyric.update');
            Route::delete('/requested-songs/{requestedSong}', [AdminSongManagementController::class, 'destroyRequestedSong'])->whereNumber('requestedSong')->name('requested-songs.destroy');
        });

        Route::prefix('resources')->name('resources.')->middleware('admin.permission:administration,course')->group(function () {
            Route::get('/', [AdminResourceManagementController::class, 'index'])->name('index');
            Route::get('/workspace', [AdminResourceManagementController::class, 'workspace'])->name('workspace');
            Route::post('/word-of-day', [AdminResourceManagementController::class, 'storeWordOfDay'])->name('word-of-day.store');
            Route::patch('/word-of-day/{wordOfDay}', [AdminResourceManagementController::class, 'updateWordOfDay'])->whereNumber('wordOfDay')->name('word-of-day.update');
            Route::delete('/word-of-day/{wordOfDay}', [AdminResourceManagementController::class, 'destroyWordOfDay'])->whereNumber('wordOfDay')->name('word-of-day.destroy');
            Route::post('/mini-library/books', [AdminResourceManagementController::class, 'storeLibraryBook'])->name('mini-library.books.store');
            Route::patch('/mini-library/books/{libraryBook}', [AdminResourceManagementController::class, 'updateLibraryBook'])->whereNumber('libraryBook')->name('mini-library.books.update');
            Route::delete('/mini-library/books/{libraryBook}', [AdminResourceManagementController::class, 'destroyLibraryBook'])->whereNumber('libraryBook')->name('mini-library.books.destroy');
            Route::post('/game-words', [AdminResourceManagementController::class, 'storeGameWord'])->name('game-words.store');
            Route::patch('/game-words/{gameWord}', [AdminResourceManagementController::class, 'updateGameWord'])->whereNumber('gameWord')->name('game-words.update');
            Route::delete('/game-words/{gameWord}', [AdminResourceManagementController::class, 'destroyGameWord'])->whereNumber('gameWord')->name('game-words.destroy');
            Route::post('/speaking-bot/titles', [AdminResourceManagementController::class, 'storeSpeakingDialogueTitle'])->name('speaking-bot.titles.store');
            Route::patch('/speaking-bot/titles/{speakingDialogueTitle}', [AdminResourceManagementController::class, 'updateSpeakingDialogueTitle'])->whereNumber('speakingDialogueTitle')->name('speaking-bot.titles.update');
            Route::delete('/speaking-bot/titles/{speakingDialogueTitle}', [AdminResourceManagementController::class, 'destroySpeakingDialogueTitle'])->whereNumber('speakingDialogueTitle')->name('speaking-bot.titles.destroy');
            Route::post('/speaking-bot/dialogues', [AdminResourceManagementController::class, 'storeSpeakingDialogue'])->name('speaking-bot.dialogues.store');
            Route::patch('/speaking-bot/dialogues/{speakingDialogue}', [AdminResourceManagementController::class, 'updateSpeakingDialogue'])->whereNumber('speakingDialogue')->name('speaking-bot.dialogues.update');
            Route::delete('/speaking-bot/dialogues/{speakingDialogue}', [AdminResourceManagementController::class, 'destroySpeakingDialogue'])->whereNumber('speakingDialogue')->name('speaking-bot.dialogues.destroy');
            Route::post('/flashcards/decks', [AdminResourceManagementController::class, 'storeFlashcardDeck'])->name('flashcards.decks.store');
            Route::patch('/flashcards/decks/{deck}', [AdminResourceManagementController::class, 'updateFlashcardDeck'])->whereNumber('deck')->name('flashcards.decks.update');
            Route::delete('/flashcards/decks/{deck}', [AdminResourceManagementController::class, 'destroyFlashcardDeck'])->whereNumber('deck')->name('flashcards.decks.destroy');
            Route::post('/flashcards/cards', [AdminResourceManagementController::class, 'storeFlashcardCard'])->name('flashcards.cards.store');
            Route::post('/flashcards/cards/bulk', [AdminResourceManagementController::class, 'bulkUploadFlashcardCards'])->name('flashcards.cards.bulk');
            Route::patch('/flashcards/cards/{card}', [AdminResourceManagementController::class, 'updateFlashcardCard'])->whereNumber('card')->name('flashcards.cards.update');
            Route::delete('/flashcards/cards/{card}', [AdminResourceManagementController::class, 'destroyFlashcardCard'])->whereNumber('card')->name('flashcards.cards.destroy');
        });
    });
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/terms', function () {
    $activeLanguages = \App\Models\Language::where('is_active', 1)->orderBy('sort_order')->get();
    return view('pages.terms', compact('activeLanguages'));
});

Route::get('/privacy', function () {
    $activeLanguages = \App\Models\Language::where('is_active', 1)->orderBy('sort_order')->get();
    return view('pages.privacy', compact('activeLanguages'));
});

Route::get('/credits', function () {
    return view('pages.credits');
});

Route::get('/certificate', [App\Http\Controllers\MiniProgram\CertificateController::class, 'show'])->name('certificate.view');
Route::get('/certificate/image-proxy', [App\Http\Controllers\MiniProgram\CertificateController::class, 'imageProxy'])->name('certificate.image-proxy');

Route::prefix('mini-program')->name('mini-program.')->group(function () {
    Route::get('/vimeo-player', [LessonController::class, 'vimeoPlayer']);
   
    
    Route::prefix('song-request')->name('song-request.')->group(function () {
        Route::get('/', [SongRequestController::class, 'index'])->name('index');
        Route::get('/artist/{id}', [SongRequestController::class, 'showArtist'])->name('artist');
        Route::post('/vote', [SongRequestController::class, 'vote'])->name('vote');
        Route::post('/store', [SongRequestController::class, 'store'])->name('store');
    });

    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::get('/{major}/{category}/{id}', [ExamController::class, 'show'])->name('show');
    });

    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/', [LibraryController::class, 'index'])->name('index');
        Route::get('/{major}/{category}', [LibraryController::class, 'category'])->name('category');
    });
});
