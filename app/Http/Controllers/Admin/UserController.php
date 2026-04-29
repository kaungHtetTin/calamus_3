<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\CommentCreated;
use App\Events\CommentLiked;
use App\Events\PostLiked;
use App\Jobs\EmailBroadcastChunk;
use App\Jobs\SendFcmToTokens;
use App\Jobs\SendFcmToTopic;
use App\Models\ActivationMessage;
use App\Models\Language;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Learner;
use App\Models\Message;
use App\Models\MyLike;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Report;
use App\Models\UserData;
use App\Services\FcmService;
use App\Services\NotificationDispatchService;
use App\Services\NotificationCleanupService;
use App\Services\PhpMailerMailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserController extends Controller
{
    private const SUPPORT_ADMIN_USER_ID = 10000;

    /**
     * Display a listing of the learners.
     */
    public function index(Request $request)
    {
        $query = Learner::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('learner_name', 'like', "%{$search}%")
                  ->orWhere('learner_email', 'like', "%{$search}%")
                  ->orWhere('learner_phone', $search);
            });
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by verification status
        if ($request->filled('verified')) {
            if ($request->verified === 'yes') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->verified === 'no') {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by region
        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        // Sorting
        $sortField = $request->input('sort', 'user_id');
        $sortOrder = $request->input('order', 'desc');
        
        $allowedSortFields = ['user_id', 'learner_name', 'learner_email', 'created_at'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('user_id', 'desc');
        }

        $users = $query->paginate(15)->withQueryString();

        // Get unique regions for the filter dropdown
        $regions = Learner::whereNotNull('region')->where('region', '!=', '')->distinct()->pluck('region');

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'gender', 'verified', 'region', 'sort', 'order']),
            'regions' => $regions,
        ]);
    }

    /**
     * Display the user analysis page.
     */
    public function analysis(Request $request)
    {
        $userDataQuery = DB::table('user_data as ud')
            ->join('learners as l', 'l.user_id', '=', 'ud.user_id');
        $selectedLanguageId = (string) $request->input('language_id', 'all');
        $languages = Language::where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'display_name', 'code', 'module_code']);

        $weekStart = Carbon::today()->subDays(6);
        $today = Carbon::today();

        $appUsers = (clone $userDataQuery)
            ->selectRaw("LOWER(COALESCE(NULLIF(ud.major, ''), 'unknown')) as app_scope, COUNT(DISTINCT l.user_id) as count")
            ->groupBy('app_scope')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                $scope = strtolower((string) $item->app_scope);
                return [
                    'scope' => $scope,
                    'name' => ucfirst($scope),
                    'value' => (int) $item->count,
                ];
            });

        $todayRegistrations = (clone $userDataQuery)
            ->whereNotNull('ud.first_join')
            ->whereDate('ud.first_join', $today->toDateString())
            ->selectRaw("LOWER(COALESCE(NULLIF(ud.major, ''), 'unknown')) as app_scope, COUNT(DISTINCT l.user_id) as count")
            ->groupBy('app_scope')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) use ($languages) {
                $scope = strtolower((string) $item->app_scope);
                $lang = $languages->first(function ($l) use ($scope) {
                    $aliases = collect([$l->name, $l->code, $l->module_code])
                        ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                        ->map(fn ($v) => strtolower(trim($v)));
                    return $aliases->contains($scope);
                });
                return [
                    'scope' => $scope,
                    'name' => $lang ? ($lang->display_name ?: $lang->name) : ucfirst($scope),
                    'value' => (int) $item->count,
                ];
            });

        $last7RegistrationsRows = (clone $userDataQuery)
            ->whereNotNull('ud.first_join')
            ->whereDate('ud.first_join', '>=', $weekStart->toDateString())
            ->whereDate('ud.first_join', '<=', $today->toDateString())
            ->selectRaw("DATE(ud.first_join) as join_date, LOWER(COALESCE(NULLIF(ud.major, ''), 'unknown')) as app_scope, COUNT(DISTINCT l.user_id) as count")
            ->groupBy('join_date', 'app_scope')
            ->orderBy('join_date')
            ->get();

        $appScopes = $last7RegistrationsRows
            ->pluck('app_scope')
            ->unique()
            ->values()
            ->map(function ($scope) use ($languages) {
                $lang = $languages->first(function ($l) use ($scope) {
                    $aliases = collect([$l->name, $l->code, $l->module_code])
                        ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                        ->map(fn ($v) => strtolower(trim($v)));
                    return $aliases->contains($scope);
                });
                return [
                    'scope' => $scope,
                    'label' => $lang ? ($lang->display_name ?: $lang->name) : ucfirst((string) $scope),
                ];
            });

        $rowsByDateScope = [];
        foreach ($last7RegistrationsRows as $row) {
            $dateKey = (string) $row->join_date;
            $scopeKey = (string) $row->app_scope;
            $rowsByDateScope[$dateKey][$scopeKey] = (int) $row->count;
        }

        $newRegsLast7ByApp = collect(range(0, 6))->map(function ($offset) use ($weekStart, $rowsByDateScope, $appScopes) {
            $date = (clone $weekStart)->addDays($offset);
            $dateKey = $date->toDateString();
            $item = [
                'date' => $dateKey,
                'name' => $date->format('D'),
            ];
            foreach ($appScopes as $scopeInfo) {
                $scope = (string) $scopeInfo['scope'];
                $label = (string) $scopeInfo['label'];
                $item[$label] = (int) ($rowsByDateScope[$dateKey][$scope] ?? 0);
            }
            return $item;
        });

        $activityQuery = (clone $userDataQuery);
        if ($selectedLanguageId !== 'all' && $selectedLanguageId !== '') {
            $selectedLanguage = $languages->firstWhere('id', (int) $selectedLanguageId);
            if ($selectedLanguage) {
                $aliases = collect([
                    $selectedLanguage->name,
                    $selectedLanguage->code,
                    $selectedLanguage->module_code,
                ])
                    ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                    ->map(fn ($value) => strtolower(trim($value)))
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($aliases)) {
                    $activityQuery->where(function ($query) use ($aliases) {
                        foreach ($aliases as $index => $alias) {
                            if ($index === 0) {
                                $query->whereRaw('LOWER(ud.major) = ?', [$alias]);
                            } else {
                                $query->orWhereRaw('LOWER(ud.major) = ?', [$alias]);
                            }
                        }
                    });
                }
            } else {
                $selectedLanguageId = 'all';
            }
        }

        $activityLookup = $activityQuery
            ->whereNotNull('ud.last_active')
            ->whereDate('ud.last_active', '>=', $weekStart->toDateString())
            ->selectRaw('DATE(ud.last_active) as activity_date, COUNT(DISTINCT l.user_id) as count')
            ->groupBy('activity_date')
            ->pluck('count', 'activity_date');

        $appWeeklyActivity = collect(range(0, 6))
            ->map(function ($offset) use ($weekStart, $activityLookup) {
                $date = (clone $weekStart)->addDays($offset);
                $dateKey = $date->toDateString();
                return [
                    'date' => $dateKey,
                    'name' => $date->format('D'),
                    'value' => (int) ($activityLookup[$dateKey] ?? 0),
                ];
            });

        $analytics = [
            'gender' => Learner::select('gender', \DB::raw('count(*) as count'))
                ->groupBy('gender')
                ->get()
                ->map(fn($item) => ['name' => $item->gender ?: 'Not set', 'value' => $item->count]),
            'verification' => [
                ['name' => 'Verified', 'value' => Learner::whereNotNull('email_verified_at')->count()],
                ['name' => 'Unverified', 'value' => Learner::whereNull('email_verified_at')->count()],
            ],
            'regions' => Learner::whereNotNull('region')
                ->where('region', '!=', '')
                ->select('region', \DB::raw('count(*) as count'))
                ->groupBy('region')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn($item) => ['name' => $item->region, 'value' => $item->count]),
            'total_users' => Learner::count(),
            'app_users' => $appUsers,
            'app_activity' => $appWeeklyActivity,
            'new_regs_today_by_app' => $todayRegistrations->values(),
            'new_regs_last7_by_app' => $newRegsLast7ByApp->values(),
        ];

        return Inertia::render('Admin/UserAnalysis', [
            'analytics' => $analytics,
            'activityLanguageOptions' => $languages->map(function ($language) {
                return [
                    'id' => (int) $language->id,
                    'name' => $language->name,
                    'display_name' => $language->display_name ?: $language->name,
                ];
            })->values(),
            'selectedActivityLanguageId' => $selectedLanguageId,
        ]);
    }

    public function enrollCourse(Request $request)
    {
        $perPage = 25;

        if (!Schema::hasTable('payments')) {
            return Inertia::render('Admin/EnrollCourse', [
                'payments' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $payments = Payment::query()
            ->where('activated', 0)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $paymentRows = collect($payments->items());
        $userIds = $paymentRows
            ->pluck('user_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '' && $value !== '0' && ctype_digit($value))
            ->unique()
            ->values();

        $learnerNameByUserId = collect();
        if (Schema::hasTable('learners') && Schema::hasColumn('learners', 'user_id')) {
            $nameColumn = Schema::hasColumn('learners', 'learner_name')
                ? 'learner_name'
                : (Schema::hasColumn('learners', 'name') ? 'name' : null);

            if ($nameColumn && $userIds->isNotEmpty()) {
                $learnerNameByUserId = DB::table('learners')
                    ->whereIn('user_id', $userIds->all())
                    ->pluck($nameColumn, 'user_id');
            }
        }

        $courseIds = $paymentRows
            ->flatMap(function ($paymentRow) {
                $courses = $paymentRow instanceof Payment ? ($paymentRow->courses ?? []) : ($paymentRow['courses'] ?? []);
                if (!is_array($courses)) {
                    return [];
                }
                return $courses;
            })
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        $courseTitleById = collect();
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'course_id') && $courseIds->isNotEmpty()) {
            $titleColumn = Schema::hasColumn('courses', 'title')
                ? 'title'
                : (Schema::hasColumn('courses', 'name') ? 'name' : null);
            if ($titleColumn) {
                $courseTitleById = DB::table('courses')
                    ->whereIn('course_id', $courseIds->all())
                    ->pluck($titleColumn, 'course_id');
            }
        }

        $payments->setCollection(
            $payments->getCollection()->map(function ($paymentRow) use ($learnerNameByUserId, $courseTitleById) {
                $userId = trim((string) ($paymentRow->user_id ?? ''));
                $name = trim((string) ($learnerNameByUserId[$userId] ?? ''));
                $courseIds = collect($paymentRow->courses ?? [])
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn ($value) => $value > 0)
                    ->unique()
                    ->values()
                    ->all();

                $courseTitles = collect($courseIds)
                    ->map(function ($courseId) use ($courseTitleById) {
                        $title = trim((string) ($courseTitleById[$courseId] ?? ''));
                        return $title !== '' ? $title : ('Course #' . $courseId);
                    })
                    ->values()
                    ->all();

                return [
                    'id' => (int) $paymentRow->id,
                    'user_id' => $userId,
                    'user_name' => $name,
                    'major' => (string) ($paymentRow->major ?? ''),
                    'amount' => $paymentRow->amount,
                    'courses' => $courseIds,
                    'course_titles' => $courseTitles,
                    'screenshot' => (string) ($paymentRow->screenshot ?? ''),
                    'activated' => (bool) ($paymentRow->activated ?? false),
                    'date' => (string) ($paymentRow->date ?? ''),
                    'meta' => $paymentRow->meta ?? null,
                ];
            })
        );

        return Inertia::render('Admin/EnrollCourse', [
            'payments' => $payments,
        ]);
    }

    public function activatePaymentCourses(Request $request, int $payment, NotificationDispatchService $dispatch)
    {
        if (!Schema::hasTable('payments')) {
            throw ValidationException::withMessages([
                'activate' => 'Payments table not found.',
            ]);
        }

        if (!Schema::hasTable('vipusers')) {
            throw ValidationException::withMessages([
                'activate' => 'Vipusers table not found.',
            ]);
        }

        $paymentRow = Payment::query()->findOrFail($payment);
        $userId = trim((string) ($paymentRow->user_id ?? ''));
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            throw ValidationException::withMessages([
                'activate' => 'Payment user_id is missing.',
            ]);
        }

        $courseIds = collect($paymentRow->courses ?? [])
            ->map(function ($value) {
                return (int) $value;
            })
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values();

        if ($courseIds->isEmpty()) {
            throw ValidationException::withMessages([
                'activate' => 'Payment courses are empty.',
            ]);
        }

        $hasVipUserId = Schema::hasColumn('vipusers', 'user_id');
        $hasVipPhone = Schema::hasColumn('vipusers', 'phone');
        $vipUserColumn = $hasVipUserId ? 'user_id' : ($hasVipPhone ? 'phone' : null);
        if ($vipUserColumn === null) {
            throw ValidationException::withMessages([
                'activate' => 'Vipusers user column not found.',
            ]);
        }

        $hasVipCourseId = Schema::hasColumn('vipusers', 'course_id');
        $hasVipCourse = Schema::hasColumn('vipusers', 'course');
        $vipCourseColumn = $hasVipCourseId ? 'course_id' : ($hasVipCourse ? 'course' : null);
        if ($vipCourseColumn === null) {
            throw ValidationException::withMessages([
                'activate' => 'Vipusers course column not found.',
            ]);
        }

        $hasVipMajor = Schema::hasColumn('vipusers', 'major');
        $hasVipDate = Schema::hasColumn('vipusers', 'date');
        $major = strtolower(trim((string) ($paymentRow->major ?? '')));
        $now = now();
        $paymentMeta = is_array($paymentRow->meta) ? $paymentRow->meta : [];
        $packagePlan = strtolower(trim((string) ($paymentMeta['packagePlan'] ?? '')));

        DB::transaction(function () use (
            $paymentRow,
            $userId,
            $courseIds,
            $vipUserColumn,
            $vipCourseColumn,
            $hasVipUserId,
            $hasVipPhone,
            $hasVipMajor,
            $hasVipDate,
            $major,
            $now,
            $packagePlan
        ) {
            $existingCourseIds = DB::table('vipusers')
                ->where($vipUserColumn, $userId)
                ->whereIn($vipCourseColumn, $courseIds->all())
                ->pluck($vipCourseColumn)
                ->map(function ($value) {
                    return (int) $value;
                })
                ->unique()
                ->values();

            $missingCourseIds = $courseIds->diff($existingCourseIds)->values();

            if ($missingCourseIds->isNotEmpty()) {
                $batch = [];
                foreach ($missingCourseIds as $courseId) {
                    $row = [
                        $vipUserColumn => $userId,
                        $vipCourseColumn => (int) $courseId,
                    ];

                    if ($hasVipUserId && $vipUserColumn !== 'user_id') {
                        $row['user_id'] = $userId;
                    }
                    if ($hasVipPhone && $vipUserColumn !== 'phone') {
                        $row['phone'] = (string) $userId;
                    }
                    if ($hasVipMajor && $major !== '') {
                        $row['major'] = $major;
                    }
                    if ($hasVipDate) {
                        $row['date'] = $now;
                    }

                    $batch[] = $row;
                }

                DB::table('vipusers')->insert($batch);
            }

            $update = ['is_vip'=>1,'updated_at'=>$now];
            if ($packagePlan === 'diamond') {
                $update['diamond_plan'] = 1;
            }

            $existingUserData = DB::table('user_data')
                ->where('user_id', $userId)
                ->whereRaw('LOWER(TRIM(major)) = ?', [$major])
                ->first();

            if ($existingUserData) {
                DB::table('user_data')
                    ->where('id', $existingUserData->id)
                    ->update($update);
            } else {
                $insertData = array_merge($update, [
                    'user_id' => $userId,
                    'major' => $major,
                    'created_at' => $now,
                ]);
                DB::table('user_data')->insert($insertData);
            }

            $paymentRow->activated = 1;
            $paymentRow->save();
        });

        $dispatch->notifyUserDatabase($userId, [
            'type' => 'payment.activated',
            'actor' => [
                'userId' => 10000,
                'name' => 'Admin',
            ],
            'target' => [
                'paymentId' => (int) ($paymentRow->id ?? 0),
                'major' => $major,
            ],
            'navigation' => [
                'routeName' => 'PurchasedCourses',
                'params' => [
                    'major' => (string) $major,
                ],
            ],
            'metadata' => [
                'courses' => array_values($courseIds->all()),
            ],
        ], 'App\\Notifications\\PaymentActivated');

        $dispatch->pushToUserTokens(
            $userId,
            'Subscription Activated',
            'Your course subscription has been activated.',
            [
                'type' => 'payment.activated',
                'major' => $major,
                'courses' => json_encode(array_values($courseIds->all())),
                'navigation' => [
                    'routeName' => 'PurchasedCourses',
                    'params' => [
                        'major' => (string) $major,
                    ],
                ],
            ]
        );

        $this->sendActivationChatMessage($userId, $major, $dispatch);

        return redirect()->back(303);
    }

    private function sendActivationChatMessage(string $userId, string $major, NotificationDispatchService $dispatch): void
    {
        $userId = trim((string) $userId);
        if ($userId === '' || $userId === '0' || !ctype_digit($userId)) {
            return;
        }

        if (!Schema::hasTable('activation_messages') || !Schema::hasTable('conversations') || !Schema::hasTable('messages')) {
            return;
        }

        $selectionMajor = strtolower(trim((string) $major));
        if ($selectionMajor === '') {
            $selectionMajor = 'english';
        }

        $messageText = ActivationMessage::query()
            ->whereRaw('LOWER(TRIM(major)) = ?', [$selectionMajor])
            ->orderByDesc('id')
            ->value('message');

        $messageText = is_string($messageText) ? trim($messageText) : '';
        if ($messageText === '') {
            $fallback = ActivationMessage::query()
                ->whereRaw('LOWER(TRIM(major)) = ?', ['english'])
                ->orderByDesc('id')
                ->value('message');
            $messageText = is_string($fallback) ? trim($fallback) : '';
        }
        if ($messageText === '') {
            return;
        }

        if (mb_strlen($messageText) > 2000) {
            $messageText = mb_substr($messageText, 0, 2000);
        }

        $conversation = Conversation::query()
            ->where(function ($q) use ($userId) {
                $q->where(function ($q2) use ($userId) {
                    $q2->where('user1_id', self::SUPPORT_ADMIN_USER_ID)->where('user2_id', $userId);
                })->orWhere(function ($q2) use ($userId) {
                    $q2->where('user2_id', self::SUPPORT_ADMIN_USER_ID)->where('user1_id', $userId);
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::query()->create([
                'user1_id' => self::SUPPORT_ADMIN_USER_ID,
                'user2_id' => $userId,
                'major' => 'english',
                'last_message_at' => null,
            ]);
        }

        if (!$conversation) {
            return;
        }

        $msg = new Message();
        $msg->conversation_id = (int) $conversation->id;
        $msg->sender_id = self::SUPPORT_ADMIN_USER_ID;
        $msg->major = 'english';
        $msg->message_type = 'text';
        $msg->message_text = $messageText;
        $msg->file_path = '';
        $msg->file_size = 0;
        $msg->is_read = 0;
        $msg->save();

        $conversation->last_message_at = now();
        $conversation->save();

        $dispatch->queuePushToUserTokens($userId, 'Support', $messageText, [
            'type' => 'chat.message',
            'conversationId' => (string) $conversation->id,
            'friendId' => (string) self::SUPPORT_ADMIN_USER_ID,
        ]);
    }

    public function edit(string $id)
    {
        $admin = auth('admin')->user();
        $canManageVipAccess = $admin && method_exists($admin, 'hasPermission')
            ? $admin->hasPermission('administration')
            : false;
        $canDeleteUser = $canManageVipAccess;

        $user = Learner::query()
            ->with(['userData' => function ($query) {
                $query->orderBy('major');
            }])
            ->findOrFail($id);

        $userIdCandidates = collect([
            $id,
            $user->learner_phone ?? null,
        ])
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $userDataSummary = DB::table('user_data')
            ->whereIn('user_id', $userIdCandidates)
            ->selectRaw('MIN(first_join) as first_join, MAX(last_active) as last_active')
            ->first();

        $totalPosts = 0;
        if (Schema::hasTable('posts') && !empty($userIdCandidates)) {
            $hasPostUserId = Schema::hasColumn('posts', 'user_id');
            $hasPostLearnerId = Schema::hasColumn('posts', 'learner_id');
            $totalPosts = DB::table('posts')
                ->where(function ($query) use ($userIdCandidates, $hasPostUserId, $hasPostLearnerId) {
                    if ($hasPostUserId) {
                        $query->whereIn('user_id', $userIdCandidates);
                        if ($hasPostLearnerId) {
                            $query->orWhereIn('learner_id', $userIdCandidates);
                        }
                        return;
                    }

                    if ($hasPostLearnerId) {
                        $query->whereIn('learner_id', $userIdCandidates);
                    }
                })
                ->count();
        }

        $totalComments = 0;
        if (Schema::hasTable('comment') && !empty($userIdCandidates) && Schema::hasColumn('comment', 'writer_id')) {
            $totalComments = DB::table('comment')->whereIn('writer_id', $userIdCandidates)->count();
        }

        $certificates = [];
        $certificateCount = 0;
        if (Schema::hasTable('certificates') && !empty($userIdCandidates)) {
            $certificateUserColumn = Schema::hasColumn('certificates', 'user_id')
                ? 'user_id'
                : (Schema::hasColumn('certificates', 'phone') ? 'phone' : null);
            $certificateIdColumn = Schema::hasColumn('certificates', 'id')
                ? 'id'
                : (Schema::hasColumn('certificates', 'certificate_id') ? 'certificate_id' : null);

            if ($certificateUserColumn) {
                $certificateCount = (int) DB::table('certificates')
                    ->whereIn("certificates.{$certificateUserColumn}", $userIdCandidates)
                    ->distinct('course_id')
                    ->count('course_id');

                $orderColumn = $certificateIdColumn ? "certificates.{$certificateIdColumn}" : 'certificates.course_id';
                $selectId = $certificateIdColumn
                    ? DB::raw("certificates.{$certificateIdColumn} as id")
                    : DB::raw("certificates.course_id as id");

                $certificateRows = DB::table('certificates')
                    ->leftJoin('courses', 'certificates.course_id', '=', 'courses.course_id')
                    ->whereIn("certificates.{$certificateUserColumn}", $userIdCandidates)
                    ->orderByDesc($orderColumn)
                    ->limit(100)
                    ->get([
                        $selectId,
                        'certificates.course_id',
                        'certificates.date',
                        'courses.title',
                        'courses.major',
                        'courses.certificate_code',
                    ]);

                $certificates = $certificateRows->map(function ($row) {
                    $encodedId = base64_encode((string) $row->id);
                    $code = trim((string) ($row->certificate_code ?? ''));
                    if ($code === '') {
                        $code = strtoupper(substr((string) ($row->major ?? ''), 0, 2) ?: 'CE');
                    }
                    return [
                        'id' => (int) $row->id,
                        'course_id' => (int) ($row->course_id ?? 0),
                        'title' => (string) ($row->title ?? ''),
                        'major' => (string) ($row->major ?? ''),
                        'date' => (string) ($row->date ?? ''),
                        'ref' => $code . '-' . str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                    ];
                })->values()->all();
            }
        }

        $vipCourses = collect();
        if ($canManageVipAccess && Schema::hasTable('vipusers') && !empty($userIdCandidates)) {
            $vipUserColumn = Schema::hasColumn('vipusers', 'user_id')
                ? 'user_id'
                : (Schema::hasColumn('vipusers', 'phone') ? 'phone' : null);

            if ($vipUserColumn) {
                $vipCoursesQuery = DB::table('vipusers')
                    ->leftJoin('courses', 'vipusers.course_id', '=', 'courses.course_id')
                    ->whereIn("vipusers.{$vipUserColumn}", $userIdCandidates)
                    ->whereRaw("LOWER(TRIM(COALESCE(courses.major, ''))) != ?", ['not'])
                    ->orderByDesc('vipusers.course_id');

                if (Schema::hasTable('languages')) {
                    $vipCoursesQuery->leftJoin('languages as l', function ($join) {
                        $join->on(DB::raw('LOWER(l.code)'), '=', DB::raw('LOWER(courses.major)'))
                            ->orOn(DB::raw('LOWER(l.name)'), '=', DB::raw('LOWER(courses.major)'))
                            ->orOn(DB::raw('LOWER(l.module_code)'), '=', DB::raw('LOWER(courses.major)'));
                    });
                }

                $vipCourses = $vipCoursesQuery->get([
                    'vipusers.course_id',
                    'courses.title',
                    'courses.major',
                    'courses.is_vip',
                    'courses.active',
                    DB::raw('l.display_name as language_display_name'),
                    DB::raw('l.image_path as language_image_path'),
                    DB::raw('l.name as language_name'),
                    DB::raw('l.code as language_code'),
                ]);
            }
        }

        $courseCatalog = collect();
        if ($canManageVipAccess && Schema::hasTable('courses')) {
            $catalogQuery = DB::table('courses');
            $catalogQuery->whereRaw("LOWER(TRIM(COALESCE(courses.major, ''))) != ?", ['not']);
            if (Schema::hasTable('languages')) {
                $catalogQuery->leftJoin('languages as l', function ($join) {
                    $join->on(DB::raw('LOWER(l.code)'), '=', DB::raw('LOWER(courses.major)'))
                        ->orOn(DB::raw('LOWER(l.name)'), '=', DB::raw('LOWER(courses.major)'))
                        ->orOn(DB::raw('LOWER(l.module_code)'), '=', DB::raw('LOWER(courses.major)'));
                });
            }
            $catalogQuery->orderBy('l.sort_order')
                ->orderByDesc('courses.sorting')
                ->orderBy('courses.title');
            $courseCatalog = $catalogQuery->get([
                'courses.course_id',
                'courses.title',
                'courses.major',
                'courses.is_vip',
                'courses.active',
                'courses.sorting',
                DB::raw('l.display_name as language_display_name'),
                DB::raw('l.image_path as language_image_path'),
                DB::raw('l.name as language_name'),
                DB::raw('l.code as language_code'),
            ]);
        }

        $userDataForUi = collect();
        if (Schema::hasTable('user_data') && !empty($userIdCandidates)) {
            if (Schema::hasTable('languages')) {
                $userDataForUi = DB::table('user_data as ud')
                    ->leftJoin('languages as l', function ($join) {
                        $join->on(DB::raw('LOWER(l.code)'), '=', DB::raw('LOWER(ud.major)'))
                            ->orOn(DB::raw('LOWER(l.name)'), '=', DB::raw('LOWER(ud.major)'))
                            ->orOn(DB::raw('LOWER(l.module_code)'), '=', DB::raw('LOWER(ud.major)'));
                    })
                    ->whereIn('ud.user_id', $userIdCandidates)
                    ->orderBy('ud.major')
                    ->get([
                        'ud.id',
                        'ud.user_id',
                        'ud.major',
                        'ud.is_vip',
                        'ud.diamond_plan',
                        'ud.token',
                        'ud.first_join',
                        'ud.last_active',
                        DB::raw('l.display_name as language_display_name'),
                        DB::raw('l.image_path as language_image_path'),
                        DB::raw('l.name as language_name'),
                        DB::raw('l.code as language_code'),
                    ]);
            } else {
                $userDataForUi = DB::table('user_data')
                    ->whereIn('user_id', $userIdCandidates)
                    ->orderBy('major')
                    ->get();
            }
        }

        return Inertia::render('Admin/UserEdit', [
            'user' => $user,
            'userData' => $userDataForUi,
            'canManageVipAccess' => (bool) $canManageVipAccess,
            'canDeleteUser' => (bool) $canDeleteUser,
            'vipCourses' => $vipCourses,
            'certificates' => $certificates,
            'courseCatalog' => $courseCatalog,
            'profileStats' => [
                'first_join' => $userDataSummary?->first_join,
                'last_active' => $userDataSummary?->last_active,
                'total_posts' => (int) $totalPosts,
                'total_comments' => (int) $totalComments,
            ],
        ]);
    }

    public function saveVipAccess(Request $request, int $id)
    {
        $admin = auth('admin')->user();
        if (!$admin || !method_exists($admin, 'hasPermission') || !$admin->hasPermission('administration')) {
            abort(403, 'Forbidden');
        }

        $data = $request->validate([
            'major' => ['required', 'string', 'max:50'],
            'selected_course_ids' => ['nullable', 'array'],
            'selected_course_ids.*' => ['integer', 'min:1'],
            'vip_access' => ['nullable', 'boolean'],
            'diamond_plan' => ['nullable', 'boolean'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'partner_code' => ['nullable', 'string', 'max:100'],
        ]);

        $user = Learner::query()->findOrFail($id);
        $userIdCandidates = collect([
            $id,
            $user->learner_phone ?? null,
        ])
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $major = strtolower(trim((string) $data['major']));
        $selectedCourseIds = collect($data['selected_course_ids'] ?? [])
            ->map(function ($value) {
                return (int) $value;
            })
            ->filter(function ($value) {
                return $value > 0;
            })
            ->unique()
            ->values()
            ->all();

        if (!Schema::hasTable('courses')) {
            throw ValidationException::withMessages([
                'selected_course_ids' => 'Courses table not found.',
            ]);
        }

        if ($selectedCourseIds !== []) {
            $validCourseIds = DB::table('courses')
                ->whereIn('course_id', $selectedCourseIds)
                ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                ->pluck('course_id')
                ->map(function ($value) {
                    return (int) $value;
                })
                ->all();

            $sortedSelected = $selectedCourseIds;
            sort($validCourseIds);
            sort($sortedSelected);
            if ($validCourseIds !== $sortedSelected) {
                throw ValidationException::withMessages([
                    'selected_course_ids' => 'Selected courses do not belong to the given language.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $data, $id, $user, $userIdCandidates, $major, $selectedCourseIds) {
            if (Schema::hasTable('vipusers')) {
                $courseIdsForMajor = DB::table('courses')
                    ->whereRaw("LOWER(TRIM(COALESCE(major, ''))) = ?", [$major])
                    ->pluck('course_id')
                    ->map(function ($value) {
                        return (int) $value;
                    })
                    ->all();

                if ($courseIdsForMajor !== []) {
                    DB::table('vipusers')
                        ->whereIn('course_id', $courseIdsForMajor)
                        ->where('user_id', $id)
                        ->delete();
                }

                foreach ($selectedCourseIds as $courseId) {
                    DB::table('vipusers')->updateOrInsert(
                        ['user_id' => $id, 'course_id' => $courseId, 'major'=>$major],
                        []
                    );
                }
            }

            if (Schema::hasTable('user_data')) {
                $vipAccess = $request->boolean('vip_access') ? 1 : 0;
                $diamondPlan = $request->boolean('diamond_plan') ? 1 : 0;
                $now = now();

                DB::table('user_data')
                    ->whereIn('user_id', $userIdCandidates)
                    ->whereRaw("LOWER(TRIM(major)) = ?", [$major])
                    ->update([
                        'is_vip' => $vipAccess,
                        'diamond_plan' => $diamondPlan,
                        'updated_at' => $now,
                    ]);

                DB::table('user_data')->updateOrInsert(
                    ['user_id' => $id, 'major' => $major],
                    [
                        'is_vip' => $vipAccess,
                        'diamond_plan' => $diamondPlan,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            $amount = $data['amount'] ?? null;
            $amountValue = $amount === null ? null : (float) $amount;
            if ($amountValue !== null && $amountValue > 0) {
                $payment = new Payment();
                $payment->user_id = $id;
                $payment->major = $major;
                $payment->amount = $amountValue;
                $payment->courses = $selectedCourseIds;
                $payment->screenshot = '';
                $payment->approve = 1;
                $payment->activated = 1;
                $payment->date = now();
                $payment->transaction_id = 'add_by_admin';
                $payment->save();

                $partnerCode = trim((string) ($data['partner_code'] ?? ''));
                if ($partnerCode !== '') {
                    if (!Schema::hasTable('partners')) {
                        throw ValidationException::withMessages([
                            'partner_code' => 'Partner system not configured.',
                        ]);
                    }

                    $partner = DB::table('partners')->where('private_code', $partnerCode)->first();
                    if (!$partner) {
                        throw ValidationException::withMessages([
                            'partner_code' => 'Wrong promotion Code! Activation fail',
                        ]);
                    }

                    if (Schema::hasTable('partner_earnings')) {
                        $commissionRate = (float) ($partner->commission_rate ?? 0);
                        $originalPrice = $amountValue / 0.9;
                        $amountReceived = ($originalPrice * $commissionRate) / 100;
                        $now = now();

                        $insert = [];
                        if (Schema::hasColumn('partner_earnings', 'partner_id')) $insert['partner_id'] = $partner->id ?? null;
                        if (Schema::hasColumn('partner_earnings', 'target_course_id')) $insert['target_course_id'] = null;
                        if (Schema::hasColumn('partner_earnings', 'target_package_id')) $insert['target_package_id'] = null;
                        if (Schema::hasColumn('partner_earnings', 'user_id')) $insert['user_id'] = $id;
                        if (Schema::hasColumn('partner_earnings', 'learner_phone')) $insert['learner_phone'] = $user->learner_phone ?? null;
                        if (Schema::hasColumn('partner_earnings', 'price')) $insert['price'] = $amountValue;
                        if (Schema::hasColumn('partner_earnings', 'commission_rate')) $insert['commission_rate'] = $commissionRate;
                        if (Schema::hasColumn('partner_earnings', 'amount_received')) $insert['amount_received'] = $amountReceived;
                        if (Schema::hasColumn('partner_earnings', 'status')) $insert['status'] = 'pending';
                        if (Schema::hasColumn('partner_earnings', 'created_at')) $insert['created_at'] = $now;
                        if (Schema::hasColumn('partner_earnings', 'updated_at')) $insert['updated_at'] = $now;

                        DB::table('partner_earnings')->insert($insert);
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'VIP access updated successfully.');
    }

    public function sendPush(Request $request, int $id)
    {
        $queueConnection = (string) config('queue.default', 'sync');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'major' => ['required', 'string', 'max:50'],
            'platform' => ['required', 'in:all,android,ios'],
        ]);

        $user = Learner::query()->findOrFail($id);

        $userIdCandidates = collect([
            $id,
            $user->learner_phone ?? null,
        ])
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!Schema::hasTable('user_data')) {
            throw ValidationException::withMessages([
                'major' => 'user_data table not found.',
            ]);
        }

        $major = strtolower(trim((string) $data['major']));
        $platform = $data['platform'];

        $query = DB::table('user_data')
            ->whereIn('user_id', $userIdCandidates)
            ->whereNotNull('token')
            ->where('token', '!=', '');

        if ($major !== 'all') {
            $query->whereRaw("LOWER(TRIM(major)) = ?", [$major]);
        }

        $rows = $query->get(['major', 'token']);

        $tokens = [];
        foreach ($rows as $row) {
            $tokenStr = $row->token;
            $parsed = null;

            if (is_string($tokenStr)) {
                $decoded = json_decode($tokenStr, true);
                if (is_array($decoded)) {
                    $parsed = $decoded;
                } else {
                    $parsed = ['android' => $tokenStr];
                }
            } elseif (is_array($tokenStr)) {
                $parsed = $tokenStr;
            }

            if (!is_array($parsed)) {
                continue;
            }

            if ($platform === 'android' || $platform === 'all') {
                $t = trim((string) ($parsed['android'] ?? ''));
                if ($t !== '') $tokens[] = $t;
            }

            if ($platform === 'ios' || $platform === 'all') {
                $t = trim((string) ($parsed['ios'] ?? ''));
                if ($t !== '') $tokens[] = $t;
            }
        }

        $tokens = array_values(array_unique($tokens));
        if ($tokens === []) {
            throw ValidationException::withMessages([
                'major' => 'No valid push tokens found for the selected filters.',
            ]);
        }

        if ($queueConnection === 'sync') {
            return redirect()->back()->with(
                'error',
                'Push requires an async queue (QUEUE_CONNECTION=database or redis). Current QUEUE_CONNECTION is sync.'
            );
        }

        dispatch(new SendFcmToTokens(
            tokens: $tokens,
            title: (string) $data['title'],
            body: (string) $data['body'],
            data: [
                'source' => 'admin',
                'user_id' => (string) $id,
                'major' => (string) $data['major'],
                'platform' => (string) $platform,
            ]
        ));

        return redirect()->back()->with('success', 'Push queued for delivery.');
    }

    public function pushTopicForm()
    {
        $languages = Language::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'display_name', 'image_path', 'firebase_topic_user', 'is_active']);

        return Inertia::render('Admin/PushTopic', [
            'languages' => $languages,
        ]);
    }

    public function pushTopicSend(Request $request)
    {
        $queueConnection = (string) config('queue.default', 'sync');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:500'],
            'language_ids' => ['required', 'array', 'min:1'],
            'language_ids.*' => ['integer'],
        ]);

        $languageIds = collect($data['language_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($languageIds === []) {
            throw ValidationException::withMessages([
                'language_ids' => 'Select at least one language.',
            ]);
        }

        $topics = Language::query()
            ->whereIn('id', $languageIds)
            ->pluck('firebase_topic_user')
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($topics === []) {
            throw ValidationException::withMessages([
                'language_ids' => 'No selected languages have a configured firebase_topic_user.',
            ]);
        }

        $image = trim((string) ($data['image'] ?? ''));
        $image = $image !== '' ? $image : null;

        if ($queueConnection === 'sync') {
            return redirect()->back()->with(
                'error',
                'Push requires an async queue (QUEUE_CONNECTION=database or redis). Current QUEUE_CONNECTION is sync.'
            );
        }

        foreach ($topics as $topic) {
            dispatch(new SendFcmToTopic(
                topic: (string) $topic,
                title: (string) $data['title'],
                body: (string) $data['body'],
                data: [
                    'source' => 'admin',
                    'topic' => (string) $topic,
                ],
                image: $image
            ));
        }

        return redirect()->back()->with('success', 'Push queued for delivery.');
    }

    public function emailBroadcastForm()
    {
        $verifiedCount = Learner::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('learner_email')
            ->where('learner_email', '!=', '')
            ->count();

        $totalCount = Learner::query()->count();
        $lastBroadcastId = trim((string) Cache::get('email_broadcast:last_id', ''));
        $broadcast = $lastBroadcastId !== '' ? $this->getEmailBroadcastStatus($lastBroadcastId) : null;

        return Inertia::render('Admin/EmailBroadcast', [
            'stats' => [
                'total_users' => (int) $totalCount,
                'verified_email_users' => (int) $verifiedCount,
            ],
            'broadcast' => $broadcast,
        ]);
    }

    public function emailBroadcastSend(Request $request, PhpMailerMailService $mail)
    {
        try {
            $queueConnection = (string) config('queue.default', 'sync');

            $data = $request->validate([
                'title' => ['required', 'string', 'max:120'],
                'body' => ['required', 'string', 'max:5000'],
            ]);

            $subject = (string) $data['title'];
            $body = (string) $data['body'];

            $this->logEmailBroadcast('info', 'send_request', [
                'queue' => $queueConnection,
                'subject_len' => strlen($subject),
                'body_len' => strlen($body),
            ]);

            if ($queueConnection === 'sync') {
                $this->logEmailBroadcast('warning', 'blocked_sync_queue', [
                    'queue' => $queueConnection,
                ]);

                return redirect()->back()->with(
                    'error',
                    'Email broadcast requires an async queue (QUEUE_CONNECTION=database or redis). Current QUEUE_CONNECTION is sync.'
                );
            }

            $total = (int) Learner::query()
                ->whereNotNull('email_verified_at')
                ->whereNotNull('learner_email')
                ->where('learner_email', '!=', '')
                ->count();

            if ($total === 0) {
                return redirect()->back()->with('error', 'No verified email users found.');
            }

            $broadcastId = (string) Str::uuid();
            $expiresAt = now()->addDays(3);
            $prefix = "email_broadcast:{$broadcastId}:";

            Cache::put('email_broadcast:last_id', $broadcastId, $expiresAt);
            Cache::put($prefix.'id', $broadcastId, $expiresAt);
            Cache::put($prefix.'status', 'dispatching', $expiresAt);
            Cache::put($prefix.'total', $total, $expiresAt);
            Cache::put($prefix.'sent', 0, $expiresAt);
            Cache::put($prefix.'failed', 0, $expiresAt);
            Cache::put($prefix.'processed', 0, $expiresAt);
            Cache::put($prefix.'jobs_total', 0, $expiresAt);
            Cache::put($prefix.'jobs_done', 0, $expiresAt);
            Cache::put($prefix.'dispatch_done', 0, $expiresAt);
            Cache::put($prefix.'started_at', now()->toDateTimeString(), $expiresAt);
            Cache::put($prefix.'finished_at', null, $expiresAt);

            $this->logEmailBroadcast('info', 'dispatch_start', [
                'id' => $broadcastId,
                'total' => $total,
                'has_job_batches' => Schema::hasTable('job_batches'),
            ]);

            DB::table('learners')
                ->whereNotNull('email_verified_at')
                ->whereNotNull('learner_email')
                ->where('learner_email', '!=', '')
                ->orderBy('user_id')
                ->select(['user_id'])
                ->chunkById(1000, function ($rows) use ($broadcastId, $subject, $body, $prefix, $expiresAt) {
                    $ids = [];
                    foreach ($rows as $row) {
                        $userId = trim((string) ($row->user_id ?? ''));
                        if ($userId !== '' && $userId !== '0' && ctype_digit($userId)) {
                            $ids[] = $userId;
                        }
                    }
                    if (count($ids) === 0) {
                        return;
                    }
                    dispatch(new EmailBroadcastChunk($broadcastId, $subject, $body, $ids));
                    Cache::increment($prefix.'jobs_total', 1);
                    Cache::put($prefix.'status', 'queued', $expiresAt);
                }, 'user_id');

            Cache::put($prefix.'dispatch_done', 1, $expiresAt);
            Cache::put($prefix.'status', 'queued', $expiresAt);

            $this->logEmailBroadcast('info', 'queued', [
                'id' => $broadcastId,
                'total' => $total,
                'jobs_total' => (int) Cache::get($prefix.'jobs_total', 0),
            ]);

            return redirect()->back()
                ->with('success', 'Email broadcast queued for delivery to verified users.')
                ->with('broadcast_id', $broadcastId);
        } catch (\Throwable $e) {
            $this->logEmailBroadcast('error', 'send_exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Email broadcast failed to start. Check storage/logs/email_broadcast.log for send_exception.');
        }
    }

    public function emailBroadcastProgress(Request $request): JsonResponse
    {
        $broadcastId = trim((string) ($request->query('broadcast_id') ?? $request->query('id') ?? ''));
        if ($broadcastId === '') {
            $broadcastId = trim((string) Cache::get('email_broadcast:last_id', ''));
        }

        if ($broadcastId === '') {
            return response()->json([
                'ok' => true,
                'broadcast' => null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'broadcast' => $this->getEmailBroadcastStatus($broadcastId),
        ]);
    }

    public function sendEmail(Request $request, int $id, PhpMailerMailService $mail)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $user = Learner::query()->findOrFail($id);
        $toEmail = trim((string) ($user->learner_email ?? ''));
        if ($toEmail === '') {
            throw ValidationException::withMessages([
                'email' => 'This user does not have an email address.',
            ]);
        }

        $toName = (string) ($user->learner_name ?? '');
        $subject = (string) $data['title'];
        $body = (string) $data['body'];

        try {
            $html = view('emails.admin.user_message', [
                'subject' => $subject,
                'body' => $body,
                'recipientName' => $toName,
                'appName' => config('app.name'),
            ])->render();
            $mail->sendHtml($toEmail, $toName, $subject, $html);
        } catch (\Throwable $e) {
            Log::warning('admin_user_email_send_failed: '.$e->getMessage(), [
                'user_id' => $id,
                'to' => $toEmail,
                'exception' => $e,
            ]);
            throw ValidationException::withMessages([
                'email' => 'Email could not be sent.',
            ]);
        }

        return redirect()->back()->with('success', 'Email sent successfully.');
    }

    private function getEmailBroadcastStatus(string $broadcastId): array
    {
        $prefix = "email_broadcast:{$broadcastId}:";

        $total = (int) Cache::get($prefix.'total', 0);
        $sent = (int) Cache::get($prefix.'sent', 0);
        $failed = (int) Cache::get($prefix.'failed', 0);
        $processed = (int) Cache::get($prefix.'processed', max(0, $sent + $failed));
        $jobsTotal = (int) Cache::get($prefix.'jobs_total', 0);
        $jobsDone = (int) Cache::get($prefix.'jobs_done', 0);
        $status = (string) Cache::get($prefix.'status', 'unknown');
        $startedAt = Cache::get($prefix.'started_at', null);
        $finishedAt = Cache::get($prefix.'finished_at', null);
        $lastUserId = Cache::get($prefix.'last_user_id', null);

        $percent = 0;
        if ($total > 0) {
            $percent = (int) min(100, floor(($processed / $total) * 100));
        }

        return [
            'id' => $broadcastId,
            'status' => $status,
            'total' => $total,
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'percent' => $percent,
            'jobs_total' => $jobsTotal,
            'jobs_done' => $jobsDone,
            'last_user_id' => $lastUserId,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ];
    }

    private function logEmailBroadcast(string $level, string $event, array $context = []): void
    {
        $payload = array_merge([
            'event' => $event,
        ], $context);

        try {
            Log::channel('email_broadcast')->log($level, $event, $payload);
        } catch (\Throwable $e) {
            try {
                Log::log('error', 'email_broadcast_log_channel_error', [
                    'event' => $event,
                    'level' => $level,
                    'channel_error' => $e->getMessage(),
                    'context' => $payload,
                ]);
            } catch (\Throwable $ignored) {
                return;
            }
        }
    }

    private function getGlobalAdminUserId(): int
    {
        return 10000;
    }

    private function getGlobalAdminLearner(): Learner
    {
        $userId = $this->getGlobalAdminUserId();
        $learner = Learner::where('user_id', $userId)->first();
        if ($learner) {
            return $learner;
        }

        return new Learner([
            'user_id' => $userId,
            'learner_name' => 'Admin',
            'learner_image' => '',
        ]);
    }

    public function discussions(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $language = trim((string) $request->query('language', 'all'));
        if ($language === '') {
            $language = 'all';
        }

        $category = '';
        if ($language !== 'all') {
            $category = $language;
        }

        if ($category === '') {
            $legacyCategory = trim((string) $request->query('category', ''));
            if ($legacyCategory !== '') {
                $category = $legacyCategory;
            }
        }
        $hidden = trim((string) $request->query('hidden', 'all'));

        $languages = Language::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'display_name', 'code', 'module_code', 'is_active']);

        $query = DB::table('posts')
            ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
            ->select([
                'posts.post_id as postId',
                'posts.body',
                'posts.image as postImage',
                'posts.hide as hidden',
                'posts.post_like as postLikes',
                'posts.comments',
                'posts.share_count as shareCount',
                'posts.view_count as viewCount',
                'posts.show_on_blog as showOnBlog',
                'posts.blog_title as blogTitle',
                'posts.major as category',
                'posts.user_id as userId',
                'learners.learner_name as userName',
                'learners.learner_image as userImage',
            ])
            ->where('posts.share', 0);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('posts.body', 'like', '%'.$search.'%')
                    ->orWhere('posts.blog_title', 'like', '%'.$search.'%')
                    ->orWhere('posts.post_id', 'like', '%'.$search.'%');
            });
        }

        if ($category !== '') {
            $query->whereRaw('LOWER(TRIM(COALESCE(posts.major, \'\'))) = ?', [mb_strtolower($category)]);
        }

        if ($hidden === 'hidden') {
            $query->where('posts.hide', 1);
        } elseif ($hidden === 'visible') {
            $query->where('posts.hide', 0);
        }

        $posts = $query->orderByDesc('posts.post_id')->paginate(20)->withQueryString();
        $adminUserId = (string) $this->getGlobalAdminUserId();

        $postIds = $posts->getCollection()
            ->pluck('postId')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $userLikes = [];
        if (!empty($postIds)) {
            $likesData = MyLike::whereIn('content_id', $postIds)->get(['content_id', 'likes']);
            foreach ($likesData as $row) {
                $arr = json_decode((string) $row->likes, true);
                if (!is_array($arr)) {
                    continue;
                }
                foreach ($arr as $item) {
                    if ((string) ($item['user_id'] ?? '') === $adminUserId) {
                        $userLikes[(int) $row->content_id] = true;
                        break;
                    }
                }
            }
        }

        $posts->setCollection(
            $posts->getCollection()->map(function ($post) use ($userLikes) {
                $pid = (int) ($post->postId ?? 0);
                $post->isLiked = isset($userLikes[$pid]) ? 1 : 0;
                return $post;
            })
        );

        return Inertia::render('Admin/Discussions', [
            'posts' => $posts,
            'languages' => $languages,
            'filters' => [
                'search' => $search,
                'language' => $category !== '' ? $category : 'all',
                'hidden' => $hidden,
            ],
        ]);
    }

    public function toggleDiscussionHide(Request $request, int $postId): JsonResponse
    {
        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $next = (int) ((int) ($post->hide ?? 0) === 1 ? 0 : 1);
        $post->update(['hide' => $next]);

        return response()->json([
            'success' => true,
            'postId' => (int) $postId,
            'hidden' => $next,
        ]);
    }

    public function deleteDiscussion(Request $request, int $postId): JsonResponse
    {
        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $commentTimes = DB::table('comment')
            ->where('target_type', 'post')
            ->where('target_id', $postId)
            ->pluck('time')
            ->map(fn ($t) => (int) $t)
            ->all();

        if (!empty($commentTimes)) {
            DB::table('comment_likes')->whereIn('comment_id', $commentTimes)->delete();
        }

        DB::table('comment')
            ->where('target_type', 'post')
            ->where('target_id', $postId)
            ->delete();

        DB::table('hidden_posts')->where('post_id', $postId)->delete();
        DB::table('report')->where('post_id', $postId)->delete();
        DB::table('mylikes')->where('content_id', $postId)->delete();
        NotificationCleanupService::forPost($postId);

        $post->delete();

        return response()->json(['success' => true]);
    }

    public function toggleDiscussionLike(Request $request, int $postId): JsonResponse
    {
        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $userId = (string) $this->getGlobalAdminUserId();
        $likesData = MyLike::where('content_id', $postId)->get();
        $targetRow = null;

        foreach ($likesData as $row) {
            $arr = json_decode($row->likes, true);
            if (is_array($arr)) {
                foreach ($arr as $k => $item) {
                    if ((string) ($item['user_id'] ?? '') === $userId) {
                        array_splice($arr, $k, 1);
                        $row->likes = json_encode($arr);
                        $row->save();
                        $post->decrement('post_like');

                        return response()->json([
                            'success' => true,
                            'isLiked' => 0,
                            'count' => (int) $post->post_like,
                        ]);
                    }
                }

                if (count($arr) < 1000) {
                    $targetRow = $row;
                }
            }
        }

        if ($targetRow) {
            $arr = json_decode($targetRow->likes, true);
            $arr = is_array($arr) ? $arr : [];
            $arr[] = ['user_id' => $userId];
            $targetRow->likes = json_encode($arr);
            $targetRow->save();
        } else {
            $newRowNo = $likesData->count();
            MyLike::create([
                'content_id' => $postId,
                'likes' => json_encode([['user_id' => $userId]]),
                'rowNo' => $newRowNo,
            ]);
        }

        $post->increment('post_like');

        $ownerId = $post->user_id ?? $post->learner_id;
        if ($ownerId && (string) $ownerId !== $userId) {
            PostLiked::dispatch($post, $this->getGlobalAdminLearner());
        }

        return response()->json([
            'success' => true,
            'isLiked' => 1,
            'count' => (int) $post->post_like,
        ]);
    }

    public function discussionComments(Request $request, int $postId): JsonResponse
    {
        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $payload = $this->buildDiscussionDetailPayload($postId, 60);

        return response()->json([
            'success' => true,
            'post' => $payload['post'],
            'comments' => $payload['comments'],
        ]);
    }

    public function discussionDetail(Request $request, int $postId)
    {
        $payload = $this->buildDiscussionDetailPayload($postId, 200);

        return Inertia::render('Admin/DiscussionDetail', [
            'post' => $payload['post'],
            'comments' => $payload['comments'],
        ]);
    }

    private function buildDiscussionDetailPayload(int $postId, int $limit): array
    {
        $post = DB::table('posts')
            ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
            ->where('posts.post_id', $postId)
            ->select([
                'posts.post_id as postId',
                'posts.body',
                'posts.image as postImage',
                'posts.hide as hidden',
                'posts.post_like as postLikes',
                'posts.comments',
                'posts.share_count as shareCount',
                'posts.view_count as viewCount',
                'posts.show_on_blog as showOnBlog',
                'posts.blog_title as blogTitle',
                'posts.major as category',
                'posts.user_id as userId',
                'learners.learner_name as userName',
                'learners.learner_image as userImage',
            ])
            ->first();

        if (!$post) {
            abort(404);
        }

        $adminUserId = $this->getGlobalAdminUserId();
        $isLiked = false;

        $likesRows = MyLike::where('content_id', $postId)->get(['likes']);
        foreach ($likesRows as $row) {
            $arr = json_decode((string) $row->likes, true);
            if (!is_array($arr)) {
                continue;
            }
            foreach ($arr as $item) {
                if ((string) ($item['user_id'] ?? '') === (string) $adminUserId) {
                    $isLiked = true;
                    break 2;
                }
            }
        }

        $postPayload = [
            'postId' => (int) $post->postId,
            'body' => (string) ($post->body ?? ''),
            'postImage' => (string) ($post->postImage ?? ''),
            'postLikes' => (int) ($post->postLikes ?? 0),
            'comments' => (int) ($post->comments ?? 0),
            'hidden' => (int) ($post->hidden ?? 0),
            'category' => (string) ($post->category ?? ''),
            'shareCount' => (int) ($post->shareCount ?? 0),
            'viewCount' => (int) ($post->viewCount ?? 0),
            'isLiked' => $isLiked ? 1 : 0,
            'userId' => (string) ($post->userId ?? ''),
            'userName' => (string) ($post->userName ?? 'Anonymous'),
            'userImage' => (string) ($post->userImage ?? 'https://www.calamuseducation.com/uploads/placeholder.png'),
        ];

        $parentCommentsQuery = DB::table('comment')
            ->leftJoin('learners', 'learners.user_id', '=', 'comment.writer_id')
            ->where('comment.target_type', 'post')
            ->where('comment.target_id', $postId)
            ->where('comment.parent', 0)
            ->orderByDesc('comment.time')
            ->limit($limit)
            ->get([
                'comment.id',
                'comment.post_id as postId',
                'comment.target_type as targetType',
                'comment.target_id as targetId',
                'comment.writer_id as writerId',
                'learners.learner_name as writerName',
                'learners.learner_image as writerImage',
                'comment.time',
                'comment.parent',
                'comment.likes',
                'comment.body',
                'comment.image',
            ]);

        $parentTimes = $parentCommentsQuery->pluck('time')->map(fn ($t) => (int) $t)->all();
        $replies = collect();
        if (!empty($parentTimes)) {
            $replies = DB::table('comment')
                ->leftJoin('learners', 'learners.user_id', '=', 'comment.writer_id')
                ->where('comment.target_type', 'post')
                ->where('comment.target_id', $postId)
                ->whereIn('comment.parent', $parentTimes)
                ->orderBy('comment.time')
                ->get([
                    'comment.id',
                    'comment.post_id as postId',
                    'comment.target_type as targetType',
                    'comment.target_id as targetId',
                    'comment.writer_id as writerId',
                    'learners.learner_name as writerName',
                    'learners.learner_image as writerImage',
                    'comment.time',
                    'comment.parent',
                    'comment.likes',
                    'comment.body',
                    'comment.image',
                ]);
        }

        $allTimes = array_merge(
            $parentTimes,
            $replies->pluck('time')->map(fn ($t) => (int) $t)->all()
        );

        $likedTimes = [];
        if (!empty($allTimes)) {
            $likedTimes = DB::table('comment_likes')
                ->where('user_id', $adminUserId)
                ->whereIn('comment_id', $allTimes)
                ->pluck('comment_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        $format = function ($row) use ($likedTimes) {
            $c = (array) $row;
            $c['isLiked'] = in_array((int) $c['time'], $likedTimes) ? 1 : 0;
            if (!isset($c['writerName']) || trim((string) $c['writerName']) === '') {
                $c['writerName'] = ((string) ($c['writerId'] ?? '') === '10000') ? 'Admin' : 'Unknown';
            }
            if (!isset($c['writerImage']) || trim((string) $c['writerImage']) === '') {
                $c['writerImage'] = 'https://www.calamuseducation.com/uploads/placeholder.png';
            }
            $c['child'] = [];
            return $c;
        };

        $grouped = [];
        foreach ($replies as $reply) {
            $grouped[(int) $reply->parent][] = $format($reply);
        }

        $final = [];
        foreach ($parentCommentsQuery as $parent) {
            $p = $format($parent);
            $p['child'] = $grouped[(int) $parent->time] ?? [];
            $final[] = $p;
        }

        return [
            'post' => $postPayload,
            'comments' => $final,
        ];
    }

    public function addDiscussionComment(Request $request, int $postId): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent' => ['nullable', 'integer', 'min:0'],
        ]);

        $post = Post::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['success' => false, 'message' => 'Post not found'], 404);
        }

        $parent = (int) ($data['parent'] ?? 0);
        if ($parent > 0) {
            $parentExists = Comment::where('time', $parent)
                ->where('target_type', 'post')
                ->where('target_id', $postId)
                ->exists();
            if (!$parentExists) {
                return response()->json(['success' => false, 'message' => 'Parent comment not found'], 404);
            }
        }

        $time = (int) round(microtime(true) * 1000);

        $comment = new Comment();
        $comment->post_id = $postId;
        $comment->target_type = 'post';
        $comment->target_id = $postId;
        $comment->writer_id = $this->getGlobalAdminUserId();
        $comment->body = trim((string) $data['body']);
        $comment->image = '';
        $comment->time = $time;
        $comment->parent = $parent;
        $comment->likes = 0;
        $comment->save();

        DB::table('posts')->where('post_id', $postId)->increment('comments');

        $parentComment = $parent > 0 ? Comment::where('time', $parent)->first() : null;
        CommentCreated::dispatch(
            $comment,
            'post',
            (string) $postId,
            $post,
            null,
            $parentComment
        );

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => $comment->id,
                'writerId' => (string) ($comment->writer_id ?? ''),
                'writerName' => 'Admin',
                'writerImage' => 'https://www.calamuseducation.com/uploads/placeholder.png',
                'time' => (int) $comment->time,
                'parent' => (int) $comment->parent,
                'likes' => (int) $comment->likes,
                'isLiked' => 0,
                'body' => $comment->body,
                'image' => '',
                'child' => [],
            ],
        ]);
    }

    public function toggleDiscussionCommentLike(Request $request, int $commentTime): JsonResponse
    {
        $commentTime = (int) $commentTime;
        if ($commentTime <= 0) {
            return response()->json(['success' => false, 'message' => 'Comment ID is required'], 400);
        }

        $comment = Comment::where('time', $commentTime)->first();
        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $userId = $this->getGlobalAdminUserId();
        $exists = DB::table('comment_likes')
            ->where('comment_id', $commentTime)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            DB::table('comment_likes')
                ->where('comment_id', $commentTime)
                ->where('user_id', $userId)
                ->delete();
            DB::table('comment')
                ->where('id', (int) $comment->id)
                ->update(['likes' => DB::raw('GREATEST(likes - 1, 0)')]);
            $exists = false;
        } else {
            DB::table('comment_likes')->insert([
                'comment_id' => $commentTime,
                'user_id' => $userId,
            ]);
            DB::table('comment')->where('id', (int) $comment->id)->increment('likes');
            $exists = true;

            if ((string) $comment->writer_id !== (string) $userId) {
                CommentLiked::dispatch($comment, $this->getGlobalAdminLearner());
            }
        }

        $likesCount = (int) DB::table('comment')->where('id', (int) $comment->id)->value('likes');

        return response()->json([
            'success' => true,
            'isLiked' => $exists ? 1 : 0,
            'likesCount' => $likesCount,
        ]);
    }

    public function updateDiscussionComment(Request $request, int $commentTime): JsonResponse
    {
        $commentTime = (int) $commentTime;
        if ($commentTime <= 0) {
            return response()->json(['success' => false, 'message' => 'Comment ID is required'], 400);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = Comment::where('time', $commentTime)
            ->where('target_type', 'post')
            ->first();

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $comment->body = trim((string) $data['body']);
        $comment->save();

        return response()->json([
            'success' => true,
            'comment' => [
                'id' => (int) $comment->id,
                'time' => (int) $comment->time,
                'body' => (string) $comment->body,
            ],
        ]);
    }

    public function deleteDiscussionComment(Request $request, int $commentTime): JsonResponse
    {
        $commentTime = (int) $commentTime;
        if ($commentTime <= 0) {
            return response()->json(['success' => false, 'message' => 'Comment ID is required'], 400);
        }

        $comment = Comment::where('time', $commentTime)
            ->where('target_type', 'post')
            ->first();

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $postId = (int) ($comment->target_id ?? 0);

        $deleteTimes = [$commentTime];
        $replyTimes = Comment::where('target_type', 'post')
            ->where('target_id', $postId)
            ->where('parent', $commentTime)
            ->pluck('time')
            ->map(fn ($t) => (int) $t)
            ->all();

        if (!empty($replyTimes)) {
            $deleteTimes = array_values(array_unique(array_merge($deleteTimes, $replyTimes)));
        }

        DB::table('comment_likes')->whereIn('comment_id', $deleteTimes)->delete();
        DB::table('comment')
            ->where('target_type', 'post')
            ->where('target_id', $postId)
            ->whereIn('time', $deleteTimes)
            ->delete();

        if ($postId > 0) {
            DB::table('posts')
                ->where('post_id', $postId)
                ->update(['comments' => DB::raw('GREATEST(comments - '.count($deleteTimes).', 0)')]);
        }

        $post = $postId > 0 ? Post::where('post_id', $postId)->first() : null;

        return response()->json([
            'success' => true,
            'postId' => $postId,
            'deleted' => count($deleteTimes),
            'post' => $post ? [
                'postId' => (int) $post->post_id,
                'postLikes' => (int) ($post->post_like ?? 0),
                'comments' => (int) ($post->comments ?? 0),
                'hidden' => (int) ($post->hide ?? 0),
            ] : null,
        ]);
    }

    /**
     * Store a newly created learner.
     */
    public function store(Request $request)
    {
        $request->validate([
            'learner_name' => ['required', 'string', 'max:255'],
            'learner_email' => ['required', 'string', 'email', 'max:255', 'unique:learners,learner_email'],
            'learner_phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'min:8'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
        ]);

        Learner::create([
            'learner_name' => $request->learner_name,
            'learner_email' => $request->learner_email,
            'learner_phone' => $request->learner_phone,
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
        ]);

        return redirect()->back()->with('success', 'User created successfully.');
    }

    /**
     * Update the specified learner.
     */
    public function update(Request $request, $id)
    {
        $learner = Learner::findOrFail($id);

        $request->validate([
            'learner_name' => ['required', 'string', 'max:255'],
            'learner_email' => ['required', 'string', 'email', 'max:255', "unique:learners,learner_email,{$id},user_id"],
            'learner_phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'password' => ['nullable', 'min:8'],
        ]);

        $data = [
            'learner_name' => $request->learner_name,
            'learner_email' => $request->learner_email,
            'learner_phone' => $request->learner_phone,
            'gender' => $request->gender,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $learner->update($data);

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified learner.
     */
    public function destroy($id)
    {
        $admin = auth('admin')->user();
        if (!$admin || !method_exists($admin, 'hasPermission') || !$admin->hasPermission('administration')) {
            abort(403, 'Forbidden');
        }

        $learner = Learner::findOrFail($id);
        $learner->delete();

        return redirect()->route('admin.users.index', [], 303)->with('success', 'User deleted successfully.');
    }
}
