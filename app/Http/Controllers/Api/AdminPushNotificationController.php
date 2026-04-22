<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendFcmToTopic;
use App\Models\Language;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminPushNotificationController extends Controller
{
    use ApiResponse;

    public function sendToUserTopics(Request $request)
    {
        $queueConnection = (string) config('queue.default', 'sync');
        if ($queueConnection === 'sync') {
            return $this->errorResponse(
                'Push requires an async queue (QUEUE_CONNECTION=database or redis). Current QUEUE_CONNECTION is sync.',
                422
            );
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
            'image' => ['nullable', 'string', 'max:500'],
            'data' => ['nullable', 'array'],
            'allLanguages' => ['sometimes', 'boolean'],
            'languageIds' => ['sometimes', 'array'],
            'languageIds.*' => ['integer'],
            'languageCodes' => ['sometimes', 'array'],
            'languageCodes.*' => ['string', 'max:50'],
        ]);

        if (!Schema::hasTable('languages') || !Schema::hasColumn('languages', 'firebase_topic_user')) {
            return $this->errorResponse('Languages topics are not configured.', 422);
        }

        $allLanguages = (bool) ($data['allLanguages'] ?? false);
        $languageIds = collect($data['languageIds'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values()
            ->all();
        $languageCodes = collect($data['languageCodes'] ?? [])
            ->map(fn ($v) => strtolower(trim((string) $v)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!$allLanguages && $languageIds === [] && $languageCodes === []) {
            return $this->errorResponse('Select allLanguages=true or provide languageIds/languageCodes.', 422);
        }

        $query = Language::query()->where('is_active', 1);
        if (!$allLanguages) {
            $query->where(function ($q) use ($languageIds, $languageCodes) {
                if ($languageIds !== []) {
                    $q->whereIn('id', $languageIds);
                }
                if ($languageCodes !== []) {
                    $q->orWhereIn('code', $languageCodes)->orWhereIn('name', $languageCodes)->orWhereIn('module_code', $languageCodes);
                }
            });
        }

        $languages = $query->get(['id', 'code', 'name', 'module_code', 'firebase_topic_user']);
        if ($languages->isEmpty()) {
            return $this->errorResponse('No languages matched.', 422);
        }

        $topics = $languages
            ->map(function ($l) {
                return [
                    'languageId' => (int) $l->id,
                    'code' => strtolower(trim((string) ($l->code ?: $l->name ?: $l->module_code))),
                    'topic' => trim((string) $l->firebase_topic_user),
                ];
            })
            ->filter(fn ($row) => ($row['topic'] ?? '') !== '')
            ->unique('topic')
            ->values();

        if ($topics->isEmpty()) {
            return $this->errorResponse('No selected languages have a configured firebase_topic_user.', 422);
        }

        $title = trim((string) $data['title']);
        $body = trim((string) $data['body']);
        $image = trim((string) ($data['image'] ?? ''));
        $image = $image !== '' ? $image : null;
        $extraData = is_array($data['data'] ?? null) ? $data['data'] : [];

        $results = [];

        foreach ($topics as $row) {
            $topic = (string) $row['topic'];
            $major = (string) $row['code'];
            $payload = array_merge($extraData, [
                'source' => 'admin',
                'major' => $major,
                'topic' => $topic,
            ]);

            dispatch(new SendFcmToTopic($topic, $title, $body, $payload, $image));

            $results[] = [
                'topic' => $topic,
                'major' => $major,
                'queued' => true,
            ];
        }

        return $this->successResponse([
            'queuedTopics' => $topics->count(),
            'results' => $results,
        ]);
    }
}

