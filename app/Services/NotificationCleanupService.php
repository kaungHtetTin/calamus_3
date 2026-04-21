<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotificationCleanupService
{
    /**
     * Delete notifications that reference a comment (e.g. "X liked your comment").
     */
    public static function forComment(int $commentId): int
    {
        return DB::table('notifications')
            ->whereRaw("JSON_EXTRACT(data, '$.metadata.commentId') = ?", [$commentId])
            ->delete();
    }

    /**
     * Delete notifications that reference a post (e.g. "X commented on your post").
     */
    public static function forPost(int $postId): int
    {
        return DB::table('notifications')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.target.type')) = ?", ['post'])
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.target.id')) = ?", [(string) $postId])
            ->delete();
    }

    /**
     * Delete notifications for multiple posts (e.g. when user deletes account).
     */
    public static function forPosts(array $postIds): int
    {
        if (empty($postIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $bindings = array_map(fn ($id) => (string) $id, $postIds);

        return DB::table('notifications')
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.target.type')) = 'post'")
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.target.id')) IN ({$placeholders})", $bindings)
            ->delete();
    }

    /**
     * Delete all notifications sent to a learner (by morph).
     */
    public static function forNotifiable(string $notifiableType, $notifiableId): int
    {
        return DB::table('notifications')
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->delete();
    }
}
