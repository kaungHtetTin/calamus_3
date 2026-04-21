<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'admin_app_url' => env('ADMIN_APP_URL', 'http://localhost/admin'),
            'auth' => [
                'admin' => $request->user('admin') ? [
                    'id' => $request->user('admin')->id,
                    'name' => $request->user('admin')->name,
                    'email' => $request->user('admin')->email,
                    'image_url' => $request->user('admin')->image_url,
                    'access' => $request->user('admin')->access,
                    'major_scope' => $request->user('admin')->major_scope,
                ] : null,
            ],
            'admin_counters' => $request->user('admin') ? [
                'unactivated_payments' => (int) (\App\Models\Payment::where('activated', 0)->count()),
                'support_chat_unread' => $this->supportChatUnreadCount(),
                'admin_notifications_unread' => $this->adminNotificationsUnreadCount(),
            ] : null,
        ]);
    }

    private function supportChatUnreadCount(): int
    {
        if (!Schema::hasTable('messages') || !Schema::hasTable('conversations')) {
            return 0;
        }

        return (int) DB::table('messages as m')
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('m.is_read', 0)
            ->where('m.sender_id', '!=', 10000)
            ->where(function ($q) {
                $q->where('c.user1_id', 10000)->orWhere('c.user2_id', 10000);
            })
            ->count();
    }

    private function adminNotificationsUnreadCount(): int
    {
        if (!Schema::hasTable('notifications')) {
            return 0;
        }
        return (int) DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\Learner')
            ->where('notifiable_id', 10000)
            ->whereNull('read_at')
            ->count();
    }
}
