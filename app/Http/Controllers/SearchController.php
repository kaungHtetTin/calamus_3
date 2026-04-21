<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Learner;
use App\Models\Post;
use App\Models\Song;
use App\Models\SongLike;
use App\Models\MyLike;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    use ApiResponse;

    /**
     * Universal search for users, songs, and discussions.
     */
    public function universalSearch(Request $request)
    {
        try {
            $q = trim((string)$request->query('q', ''));
            if ($q === '') {
                return $this->errorResponse('Search query (q) is required', 400);
            }

            $user = auth('sanctum')->user();
            $userId = $user ? $user->user_id : null;

            // 1. Search Users (Learners)
            // Match by email, phone, or name
            $users = Learner::where('learner_email', 'like', "%$q%")
                ->orWhere('learner_phone', 'like', "%$q%")
                ->orWhere('learner_name', 'like', "%$q%")
                ->limit(10)
                ->get();

            $formattedUsers = $users->map(fn ($u) => [
                'id' => (string)$u->user_id,
                'name' => $this->ensureUtf8($u->learner_name),
                'email' => $u->learner_email,
                'phone' => $u->learner_phone,
                'image' => $u->learner_image,
                'type' => 'user'
            ]);

            // 2. Search Songs
            // Match by song name or artist name
            $songs = Song::where('title', 'like', "%$q%")
                ->orWhereHas('artistRef', fn ($rel) => $rel->where('name', 'like', "%$q%"))
                ->with('artistRef:id,name')
                ->orderBy('like_count', 'desc')
                ->limit(10)
                ->get();

            $likedSongIds = [];
            if ($userId && $songs->isNotEmpty()) {
                $likedSongIds = SongLike::where('user_id', $userId)
                    ->whereIn('song_id', $songs->pluck('id'))
                    ->pluck('song_id')->flip()->toArray();
            }

            $songBaseUrl = 'https://www.calamuseducation.com/uploads/songs';
            $formattedSongs = $songs->map(function ($s) use ($songBaseUrl, $likedSongIds, $userId) {
                $u = $s->asset_slug ?? '';
                $row = [
                    'id' => (int)$s->id,
                    'title' => $this->ensureUtf8($s->title),
                    'artist' => $this->ensureUtf8($s->artistRef?->name ?? ''),
                    'audioUrl' => $s->audio_url ?: "$songBaseUrl/audio/{$u}.mp3",
                    'imageUrl' => $s->image_url ?: "$songBaseUrl/web/{$u}.png",
                    'likeCount' => (int)$s->like_count,
                    'type' => 'song'
                ];
                if ($userId) {
                    $row['liked'] = isset($likedSongIds[$s->id]);
                }
                return $row;
            });

            // 3. Search Discussions (Posts)
            // Match by body or blog title
            $postsQuery = DB::table('posts')
                ->leftJoin('learners', 'learners.user_id', '=', 'posts.user_id')
                ->select(
                    'posts.post_id as postId',
                    'posts.body',
                    'posts.image as postImage',
                    'posts.has_video',
                    'posts.vimeo',
                    'posts.post_like as postLikes',
                    'posts.comments',
                    'posts.blog_title',
                    'posts.user_id as userId',
                    'learners.learner_name as userName',
                    'learners.learner_image as userImage'
                )
                ->where('posts.hide', 0)
                ->where('posts.share', 0)
                ->where(function ($query) use ($q) {
                    $query->where('posts.body', 'like', "%$q%")
                          ->orWhere('posts.blog_title', 'like', "%$q%");
                });

            // Apply block filters if user is logged in
            if ($userId) {
                $postsQuery->whereNotExists(function ($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                        ->from('blocks')
                        ->where(function ($q) use ($userId) {
                            $q->where('blocks.user_id', (string)$userId)
                              ->whereColumn('blocks.blocked_user_id', 'posts.user_id')
                              ->orWhere(function ($q2) use ($userId) {
                                  $q2->where('blocks.blocked_user_id', (string)$userId)
                                     ->whereColumn('blocks.user_id', 'posts.user_id');
                              });
                        });
                });
            }

            $posts = $postsQuery->orderBy('posts.post_id', 'desc')->limit(10)->get();

            $userLikes = [];
            if ($userId && $posts->isNotEmpty()) {
                $likesData = MyLike::whereIn('content_id', $posts->pluck('postId'))->get();
                foreach ($likesData as $like) {
                    $likesArr = json_decode($like->likes, true);
                    if (is_array($likesArr)) {
                        $userIds = array_column($likesArr, 'user_id');
                        if (in_array($userId, $userIds)) {
                            $userLikes[$like->content_id] = true;
                        }
                    }
                }
            }

            $formattedPosts = $posts->map(fn ($p) => [
                'postId' => (int)$p->postId,
                'body' => $this->ensureUtf8($p->body),
                'postImage' => $p->postImage ?? '',
                'hasVideo' => (int)$p->has_video,
                'vimeo' => $p->vimeo ?? '',
                'postLikes' => (int)$p->postLikes,
                'comments' => (int)$p->comments,
                'userName' => $this->ensureUtf8($p->userName ?? 'Anonymous'),
                'userImage' => $p->userImage ?? null,
                'isLiked' => isset($userLikes[$p->postId]) ? 1 : 0,
                'type' => 'discussion'
            ]);

            return $this->successResponse([
                'users' => $formattedUsers,
                'songs' => $formattedSongs,
                'discussions' => $formattedPosts,
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Server error: ' . $e->getMessage(), 500);
        }
    }

    private function ensureUtf8($string)
    {
        return mb_convert_encoding($string ?? '', 'UTF-8', 'UTF-8');
    }
}
